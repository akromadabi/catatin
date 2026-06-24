<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ActivityLog;
use App\Services\GeminiService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected $gemini;
    protected $whatsapp;

    public function __construct(GeminiService $gemini, WhatsAppService $whatsapp)
    {
        $this->gemini = $gemini;
        $this->whatsapp = $whatsapp;
    }

    /**
     * Handle incoming WhatsApp Webhook requests
     */
    public function handle(Request $request)
    {
        // Support both Fonnte (sender, message, url) and MPWA (from, message/body, url/file/media)
        $senderRaw = $request->input('sender') ?? $request->input('from');
        $message = trim($request->input('message') ?? $request->input('body') ?? '');
        $mediaUrl = $request->input('url') ?? $request->input('url_media') ?? $request->input('file') ?? $request->input('media');

        if (empty($senderRaw)) {
            return response()->json(['status' => 'ignored', 'message' => 'No sender phone number']);
        }

        // Normalize phone number (digits only, map leading 0 to 62)
        $sender = preg_replace('/[^0-9]/', '', $senderRaw);
        if (str_starts_with($sender, '0')) {
            $sender = '62' . substr($sender, 1);
        }

        // Find user by WhatsApp number
        $user = User::where('whatsapp_number', $sender)->first();
        if (!$user) {
            $reply = "Nomor WhatsApp Anda belum terdaftar di aplikasi Catat-in.\n\n"
                   . "Silakan login ke website Catat-in, buka menu 'Pengaturan/Edit Profil', "
                   . "lalu masukkan nomor WhatsApp Anda (+{$sender}) untuk menghubungkannya.";
            $this->whatsapp->sendMessage($sender, $reply);
            return response()->json(['status' => 'unregistered_user']);
        }

        // Find active project
        $projectId = $user->active_project_id;
        if (!$projectId) {
            // Fallback to first accessible project
            $project = $user->accessibleProjects()->first();
            if ($project) {
                $user->active_project_id = $project->id;
                $user->save();
                $projectId = $project->id;
            } else {
                $reply = "Anda belum memiliki proyek aktif di Catat-in. "
                       . "Silakan buat proyek baru di website Catat-in terlebih dahulu.";
                $this->whatsapp->sendMessage($sender, $reply);
                return response()->json(['status' => 'no_project']);
            }
        }

        // State Machine Check: Look for pending action
        $cacheKey = "whatsapp_pending_action_{$user->id}";
        $pending = Cache::get($cacheKey);

        if ($pending) {
            $cleanMsg = strtolower(trim($message));
            $confirmTerms = ['ya', 'yes', 'oke', 'ok', 'lanjut', 'lanjutkan', 'confirm', 'siap'];
            $cancelTerms = ['batal', 'cancel', 'tidak', 'no', 'jangan'];

            if (in_array($cleanMsg, $confirmTerms)) {
                // Execute action
                $successMessage = $this->executeAction($user, $projectId, $pending);
                Cache::forget($cacheKey);
                $this->whatsapp->sendMessage($sender, $successMessage);
                return response()->json(['status' => 'action_executed']);
            } elseif (in_array($cleanMsg, $cancelTerms)) {
                Cache::forget($cacheKey);
                $this->whatsapp->sendMessage($sender, "Aksi berhasil dibatalkan.");
                return response()->json(['status' => 'action_cancelled']);
            } else {
                // User typed something else entirely, discard the pending action and process it as a new intent
                Cache::forget($cacheKey);
            }
        }

        // Process as a new intent
        $mediaBase64 = null;
        $mediaMime = null;

        if ($mediaUrl) {
            try {
                $mediaResponse = Http::get($mediaUrl);
                if ($mediaResponse->successful()) {
                    $mediaBase64 = base64_encode($mediaResponse->body());
                    $mediaMime = $mediaResponse->header('Content-Type');

                    if (!$mediaMime) {
                        $ext = pathinfo(parse_url($mediaUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        $mediaMime = match (strtolower($ext)) {
                            'jpg', 'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'ogg' => 'audio/ogg',
                            'mp3' => 'audio/mp3',
                            'aac' => 'audio/aac',
                            'wav' => 'audio/wav',
                            'm4a' => 'audio/m4a',
                            default => 'application/octet-stream'
                        };
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to download WA media webhook: ' . $e->getMessage());
            }
        }

        // Fetch user categories for context
        $categories = Category::where('project_id', $projectId)
            ->get(['id', 'name', 'type'])
            ->toArray();

        // Fetch recent transactions for context
        $recentTxns = Transaction::where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get(['id', 'desc', 'amount', 'date', 'category', 'type'])
            ->toArray();

        // Analyze intent
        $result = $this->gemini->detectIntent($message, $mediaBase64, $mediaMime, $recentTxns, $categories);
        $action = $result['action'] ?? 'general_chat';
        $params = $result['parameters'] ?? [];
        $aiMessage = $result['message'] ?? 'Permintaan Anda sedang kami proses.';

        // Handle intent
        if (in_array($action, ['create_transaction', 'update_transaction', 'delete_transaction', 'create_category', 'delete_category'])) {
            // Write actions require confirmation: store in Cache and ask user
            Cache::put($cacheKey, $result, 300); // 5 minutes expiration
            $this->whatsapp->sendMessage($sender, $aiMessage);
            return response()->json(['status' => 'pending_confirmation']);
        } elseif ($action === 'get_report') {
            // Read Action: summary
            $reportData = $this->getQueryReport($projectId, $params);
            $naturalReply = $this->gemini->generateResponseWithData($message, $reportData);
            $this->whatsapp->sendMessage($sender, $naturalReply);
            return response()->json(['status' => 'report_sent']);
        } elseif ($action === 'get_history') {
            // Read Action: history list
            $historyData = $this->getQueryHistory($projectId, $params);
            $naturalReply = $this->gemini->generateResponseWithData($message, $historyData);
            $this->whatsapp->sendMessage($sender, $naturalReply);
            return response()->json(['status' => 'history_sent']);
        } else {
            // Chat
            $this->whatsapp->sendMessage($sender, $aiMessage);
            return response()->json(['status' => 'general_chat_sent']);
        }
    }

    /**
     * Execute the pending database modifying action
     */
    private function executeAction(User $user, $projectId, $pending)
    {
        $action = $pending['action'];
        $params = $pending['parameters'];

        switch ($action) {
            case 'create_transaction':
                $txn = Transaction::create([
                    'user_id' => $user->id,
                    'project_id' => $projectId,
                    'type' => $params['type'],
                    'amount' => $params['amount'],
                    'category' => $params['category'],
                    'desc' => $params['desc'] ?? null,
                    'date' => $params['date'] ?? now()->format('Y-m-d'),
                ]);

                ActivityLog::create([
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                    'action' => 'created',
                    'model_type' => 'Transaction',
                    'model_id' => $txn->id,
                    'data' => $txn->toArray(),
                ]);

                $this->notifyCollaborators($user, $projectId, $txn);

                $typeStr = $txn->type == 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
                $amountStr = number_format($txn->amount, 0, ',', '.');
                return "✅ **Transaksi Berhasil Dicatat!**\n\n"
                     . "* **Tipe**: {$typeStr}\n"
                     . "* **Jumlah**: Rp {$amountStr}\n"
                     . "* **Kategori**: {$txn->category}\n"
                     . "* **Keterangan**: " . ($txn->desc ?: '-') . "\n"
                     . "* **Tanggal**: {$txn->date}";

            case 'update_transaction':
                $txn = Transaction::where('project_id', $projectId)->find($params['transaction_id']);
                if (!$txn) {
                    return "❌ Transaksi tidak ditemukan atau Anda tidak memiliki akses.";
                }

                $old = $txn->toArray();
                $updateData = array_filter([
                    'amount' => $params['amount'] ?? null,
                    'desc' => $params['desc'] ?? null,
                    'category' => $params['category'] ?? null,
                    'date' => $params['date'] ?? null,
                    'type' => $params['type'] ?? null,
                ]);

                $txn->update($updateData);

                ActivityLog::create([
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                    'action' => 'updated',
                    'model_type' => 'Transaction',
                    'model_id' => $txn->id,
                    'data' => ['old' => $old, 'new' => $txn->toArray()],
                ]);

                $amountStr = number_format($txn->amount, 0, ',', '.');
                return "📝 **Transaksi Berhasil Diperbarui!**\n\n"
                     . "* **Kategori**: {$txn->category}\n"
                     . "* **Nominal**: Rp {$amountStr}\n"
                     . "* **Keterangan**: " . ($txn->desc ?: '-') . "\n"
                     . "* **Tanggal**: {$txn->date}";

            case 'delete_transaction':
                $txn = Transaction::where('project_id', $projectId)->find($params['transaction_id']);
                if (!$txn) {
                    return "❌ Transaksi tidak ditemukan atau Anda tidak memiliki akses.";
                }

                $old = $txn->toArray();
                $txn->delete();

                ActivityLog::create([
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                    'action' => 'deleted',
                    'model_type' => 'Transaction',
                    'model_id' => $params['transaction_id'],
                    'data' => $old,
                ]);

                $amountStr = number_format($old['amount'], 0, ',', '.');
                return "🗑️ **Transaksi Berhasil Dihapus!**\n\n"
                     . "* **Kategori**: {$old['category']}\n"
                     . "* **Nominal**: Rp {$amountStr}\n"
                     . "* **Keterangan**: " . ($old['desc'] ?: '-') . "\n"
                     . "* **Tanggal**: {$old['date']}";

            case 'create_category':
                $cat = Category::create([
                    'user_id' => $user->id,
                    'project_id' => $projectId,
                    'name' => $params['name'],
                    'type' => $params['type'],
                    'icon' => 'fas fa-tag',
                ]);

                ActivityLog::create([
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                    'action' => 'created',
                    'model_type' => 'Category',
                    'model_id' => $cat->id,
                    'data' => $cat->toArray(),
                ]);

                $typeStr = $cat->type == 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
                return "📂 **Kategori Baru Berhasil Dibuat!**\n\n"
                     . "* **Nama Kategori**: {$cat->name}\n"
                     . "* **Tipe**: {$typeStr}";

            case 'delete_category':
                $cat = Category::where('project_id', $projectId)->find($params['category_id']);
                if (!$cat) {
                    return "❌ Kategori tidak ditemukan atau Anda tidak memiliki akses.";
                }

                $old = $cat->toArray();
                $cat->delete();

                ActivityLog::create([
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                    'action' => 'deleted',
                    'model_type' => 'Category',
                    'model_id' => $params['category_id'],
                    'data' => $old,
                ]);

                return "🗑️ **Kategori '{$old['name']}' Berhasil Dihapus!**";

            default:
                return "❌ Aksi tidak dikenal.";
        }
    }

    /**
     * Get Database report summary data
     */
    private function getQueryReport($projectId, $params)
    {
        $period = $params['period'] ?? 'this_month';
        $categoryName = $params['category'] ?? null;

        [$startDate, $endDate] = $this->resolveDateRange($period);

        $query = Transaction::where('project_id', $projectId)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($categoryName) {
            $query->where('category', 'like', '%' . $categoryName . '%');
        }

        $transactions = $query->get(['type', 'amount', 'category', 'desc', 'date']);
        $totalPemasukan = $transactions->where('type', 'pemasukan')->sum('amount');
        $totalPengeluaran = $transactions->where('type', 'pengeluaran')->sum('amount');

        return [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'filtered_category' => $categoryName,
            'total_income' => $totalPemasukan,
            'total_expense' => $totalPengeluaran,
            'balance' => $totalPemasukan - $totalPengeluaran,
            'transaction_count' => $transactions->count(),
            'top_transactions' => $transactions->sortByDesc('amount')->take(5)->values()->toArray()
        ];
    }

    /**
     * Get Transaction history data
     */
    private function getQueryHistory($projectId, $params)
    {
        $limit = $params['limit'] ?? 5;
        $period = $params['period'] ?? null;

        $query = Transaction::where('project_id', $projectId);

        if ($period) {
            [$startDate, $endDate] = $this->resolveDateRange($period);
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $transactions = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get(['type', 'amount', 'category', 'desc', 'date'])
            ->toArray();

        return [
            'requested_limit' => $limit,
            'period_filter' => $period,
            'transactions' => $transactions
        ];
    }

    /**
     * Helper to resolve textual date ranges
     */
    private function resolveDateRange($period)
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        switch (strtolower(trim($period))) {
            case 'today':
            case 'hari ini':
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                break;
            case 'yesterday':
            case 'kemarin':
                $start = now()->subDay()->startOfDay();
                $end = now()->subDay()->endOfDay();
                break;
            case 'this_week':
            case 'minggu ini':
                $start = now()->startOfWeek();
                $end = now()->endOfWeek();
                break;
            case 'last_month':
            case 'bulan lalu':
                $start = now()->subMonth()->startOfMonth();
                $end = now()->subMonth()->endOfMonth();
                break;
            case 'this_month':
            case 'bulan ini':
            default:
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
                break;
        }

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    /**
     * Notify other members of a collaborative project
     */
    private function notifyCollaborators(User $user, $projectId, $txn)
    {
        try {
            $membersCount = ProjectMember::where('project_id', $projectId)->where('status', 'active')->count();
            if ($membersCount > 1) {
                $otherMembers = ProjectMember::where('project_id', $projectId)
                    ->where('user_id', '!=', $user->id)
                    ->where('status', 'active')
                    ->with('user')
                    ->get();
                
                $projectName = Project::find($projectId)->name ?? 'Proyek';
                $userName = $user->name;
                $typeStr = $txn->type == 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
                $amountStr = 'Rp ' . number_format($txn->amount, 0, ',', '.');
                
                foreach ($otherMembers as $member) {
                    if ($member->user) {
                        $member->user->notify(new \App\Notifications\GeneralPushNotification(
                            "Transaksi Baru (via WA)",
                            "{$userName} menambahkan {$typeStr} sebesar {$amountStr} di '{$projectName}'",
                            "/dashboard",
                            "/icons/icon-192x192.png"
                        ));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify collaborators from WA Webhook: ' . $e->getMessage());
        }
    }
}

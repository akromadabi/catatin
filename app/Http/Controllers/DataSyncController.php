<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DataSyncController extends Controller
{
    /**
     * Export all transactions of a project to JSON
     */
    public function export(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        
        // Ensure user has access
        abort_unless($project->isMember(auth()->id()), 403, 'Akses ditolak.');

        $transactions = Transaction::where('project_id', $projectId)->get();

        $exportData = $transactions->map(function ($txn) {
            return [
                'tanggal' => $txn->date,
                'nominal' => $txn->amount,
                'jenis' => $txn->type,
                'keterangan' => $txn->desc,
                'kategori' => $txn->category ? $txn->category : 'Lain-lain',
            ];
        });

        // Format: NAMA TOKO_TANGGAL_BULAN_TAHUN_BACKUP.json
        $projectName = Str::slug($project->name, '_');
        $fileName = strtoupper($projectName) . '_' . date('d_m_Y') . '_BACKUP.json';

        return response()->streamDownload(function () use ($exportData) {
            echo json_encode($exportData, JSON_PRETTY_PRINT);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import JSON transactions into a project
     */
    public function import(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        abort_unless($project->isMember(auth()->id()), 403, 'Akses ditolak.');

        $content = '';
        if ($request->has('json_data')) {
            $content = $request->input('json_data');
        } else {
            $file = $request->file('file');
            if (!$file) {
                $debugInfo = 'Keys in FILES: ' . implode(', ', array_keys($_FILES)) . ' | POST data keys: ' . implode(', ', array_keys($_POST));
                return response()->json(['success' => false, 'message' => 'File tidak ditemukan dalam request. ' . $debugInfo], 400);
            }
            if (!$file->isValid()) {
                return response()->json(['success' => false, 'message' => 'Gagal upload: ' . $file->getErrorMessage()], 400);
            }
            if ($file->getSize() > 5 * 1024 * 1024) {
                return response()->json(['success' => false, 'message' => 'Ukuran file lebih dari 5MB.'], 400);
            }
            $content = file_get_contents($file->getRealPath());
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file JSON tidak valid.',
            ], 400);
        }

        $overwrite = $request->input('overwrite') == '1';

        $successIn = 0;
        $successOut = 0;
        $failed = 0;
        $deletedCount = 0;

        DB::beginTransaction();

        try {
            if ($overwrite) {
                $deletedCount = Transaction::where('project_id', $project->id)->delete();
            }

            foreach ($data as $item) {
                // Validate item
                if (!isset($item['tanggal'], $item['nominal'], $item['jenis'], $item['kategori'])) {
                    $failed++;
                    continue;
                }

                $type = in_array(strtolower($item['jenis']), ['pemasukan', 'pengeluaran']) ? strtolower($item['jenis']) : null;
                if (!$type) {
                    $failed++;
                    continue;
                }

                $categoryName = trim($item['kategori']);
                
                // Find or create category
                $category = Category::where('project_id', $project->id)
                    ->where('name', $categoryName)
                    ->where('type', $type)
                    ->first();

                if (!$category) {
                    $category = Category::create([
                        'user_id' => auth()->id(),
                        'project_id' => $project->id,
                        'name' => $categoryName,
                        'type' => $type,
                        'icon' => $type == 'pengeluaran' ? 'fas fa-money-bill-wave' : 'fas fa-wallet', // default icon
                    ]);
                }

                // Create transaction
                Transaction::create([
                    'user_id' => auth()->id(),
                    'project_id' => $project->id,
                    'amount' => abs((float)$item['nominal']),
                    'type' => $type,
                    'category' => $category->name,
                    'desc' => $item['keterangan'] ?? null,
                    'date' => Carbon::parse($item['tanggal'])->format('Y-m-d'),
                ]);

                if ($type == 'pemasukan') {
                    $successIn++;
                } else {
                    $successOut++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Import berhasil!",
                'data' => [
                    'pemasukan' => $successIn,
                    'pengeluaran' => $successOut,
                    'gagal' => $failed,
                    'terhapus' => $deletedCount,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export to Cloud (Save on Server)
     */
    public function exportCloud(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        abort_unless($project->isMember(auth()->id()), 403, 'Akses ditolak.');

        $transactions = Transaction::where('project_id', $projectId)->get();

        $exportData = $transactions->map(function ($txn) {
            return [
                'tanggal' => $txn->date,
                'nominal' => $txn->amount,
                'jenis' => $txn->type,
                'keterangan' => $txn->desc,
                'kategori' => $txn->category ? $txn->category : 'Lain-lain',
            ];
        });

        $fileName = 'project_' . $projectId . '_' . date('d_m_Y_His') . '_BACKUP.json';
        
        // Save to user_backups directory
        Storage::disk('local')->put('user_backups/' . $fileName, json_encode($exportData, JSON_PRETTY_PRINT));

        // Keep only maximum 3 backups per project
        $files = Storage::disk('local')->files('user_backups');
        $projectBackups = [];

        foreach ($files as $file) {
            if (Str::startsWith(basename($file), 'project_' . $projectId . '_')) {
                $projectBackups[] = [
                    'path' => $file,
                    'timestamp' => Storage::disk('local')->lastModified($file)
                ];
            }
        }

        // Sort by newest first
        usort($projectBackups, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        // Delete excess backups
        if (count($projectBackups) > 3) {
            $toDelete = array_slice($projectBackups, 3);
            foreach ($toDelete as $oldBackup) {
                Storage::disk('local')->delete($oldBackup['path']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup berhasil disimpan di Cloud.'
        ]);
    }

    /**
     * Get Cloud Backups for a Project
     */
    public function getCloudBackups(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        abort_unless($project->isMember(auth()->id()), 403, 'Akses ditolak.');

        $files = Storage::disk('local')->files('user_backups');
        $backups = [];

        foreach ($files as $file) {
            if (Str::startsWith(basename($file), 'project_' . $projectId . '_')) {
                $backups[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => number_format(Storage::disk('local')->size($file) / 1024, 2) . ' KB',
                    'date' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file))->format('Y-m-d H:i:s'),
                    'timestamp' => Storage::disk('local')->lastModified($file)
                ];
            }
        }

        // Sort by newest
        usort($backups, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return response()->json([
            'success' => true,
            'backups' => $backups
        ]);
    }

    /**
     * Restore from Cloud Backup
     */
    public function importCloud(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        abort_unless($project->isMember(auth()->id()), 403, 'Akses ditolak.');

        $fileName = $request->input('file_name');
        if (!$fileName) {
            return response()->json(['success' => false, 'message' => 'Nama file tidak diberikan.'], 400);
        }

        // Ensure file belongs to this project
        if (!Str::startsWith($fileName, 'project_' . $projectId . '_')) {
            return response()->json(['success' => false, 'message' => 'File backup tidak valid.'], 403);
        }

        $path = 'user_backups/' . $fileName;

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'File backup tidak ditemukan di cloud.'], 404);
        }

        $content = Storage::disk('local')->get($path);
        $request->merge(['json_data' => $content, 'overwrite' => '1']); // Cloud restore always overwrites for simplicity

        // Re-use import logic
        return $this->import($request, $projectId);
    }

    /**
     * Helper to automatically run weekly backup for a project
     */
    public static function checkAndRunWeeklyBackup($project)
    {
        $projectId = $project->id;
        $files = \Illuminate\Support\Facades\Storage::disk('local')->files('user_backups');
        $latestBackupTime = 0;

        foreach ($files as $file) {
            if (\Illuminate\Support\Str::startsWith(basename($file), 'project_' . $projectId . '_')) {
                $time = \Illuminate\Support\Facades\Storage::disk('local')->lastModified($file);
                if ($time > $latestBackupTime) {
                    $latestBackupTime = $time;
                }
            }
        }

        // If no backup exists, or latest is older than 7 days
        if ($latestBackupTime == 0 || \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::createFromTimestamp($latestBackupTime)) >= 7) {
            $transactions = \App\Models\Transaction::where('project_id', $projectId)->get();
            if ($transactions->isEmpty()) return;

            $exportData = $transactions->map(function ($txn) {
                return [
                    'tanggal' => $txn->date,
                    'nominal' => $txn->amount,
                    'jenis' => $txn->type,
                    'keterangan' => $txn->desc,
                    'kategori' => $txn->category ? $txn->category : 'Lain-lain',
                ];
            });

            $fileName = 'project_' . $projectId . '_' . date('d_m_Y_His') . '_BACKUP.json';
            \Illuminate\Support\Facades\Storage::disk('local')->put('user_backups/' . $fileName, json_encode($exportData, JSON_PRETTY_PRINT));

            // Clean up old backups (keep max 3)
            $files = \Illuminate\Support\Facades\Storage::disk('local')->files('user_backups');
            $projectBackups = [];
            foreach ($files as $file) {
                if (\Illuminate\Support\Str::startsWith(basename($file), 'project_' . $projectId . '_')) {
                    $projectBackups[] = [
                        'path' => $file,
                        'timestamp' => \Illuminate\Support\Facades\Storage::disk('local')->lastModified($file)
                    ];
                }
            }

            usort($projectBackups, function($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });

            if (count($projectBackups) > 3) {
                $toDelete = array_slice($projectBackups, 3);
                foreach ($toDelete as $oldBackup) {
                    \Illuminate\Support\Facades\Storage::disk('local')->delete($oldBackup['path']);
                }
            }
        }
    }
}

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
}

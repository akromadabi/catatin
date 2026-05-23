<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function index()
    {
        $disk = Storage::disk('backups');
        $files = $disk->allFiles();
        
        $backups = collect($files)->filter(function ($file) {
            return str_ends_with($file, '.zip');
        })->map(function ($file) use ($disk) {
            return [
                'name' => basename($file),
                'path' => $file,
                'size' => $this->formatSizeUnits($disk->size($file)),
                'date' => Carbon::createFromTimestamp($disk->lastModified($file)),
            ];
        })->sortByDesc('date')->values();

        return view('admin.backups.index', compact('backups'));
    }

    public function store()
    {
        try {
            // Jalankan command backup (bisa butuh waktu beberapa detik/menit tergantung ukuran data)
            Artisan::call('backup:run', [
                '--disable-notifications' => true
            ]);
            
            return redirect()->route('admin.backups.index')->with('success', 'Backup baru berhasil dibuat.');
        } catch (\Exception $e) {
            Log::error('Backup Failed: ' . $e->getMessage());
            return redirect()->route('admin.backups.index')->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    public function download(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $disk = Storage::disk('backups');
        $path = $request->path;

        if ($disk->exists($path)) {
            return $disk->download($path);
        }

        return redirect()->route('admin.backups.index')->with('error', 'File backup tidak ditemukan.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $disk = Storage::disk('backups');
        $path = $request->path;

        if ($disk->exists($path)) {
            $disk->delete($path);
            return redirect()->route('admin.backups.index')->with('success', 'File backup berhasil dihapus.');
        }

        return redirect()->route('admin.backups.index')->with('error', 'File backup tidak ditemukan.');
    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}

@extends('layouts.admin')

@section('header', 'Sistem Backup Otomatis')

@section('content')
    <div class="mb-6 flex justify-end">
        <form action="{{ route('admin.backups.store') }}" method="POST" class="inline">
            @csrf
            <button type="submit" onclick="return confirm('Proses backup mungkin memakan waktu beberapa detik/menit tergantung ukuran data. Lanjutkan?')" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded-xl shadow-md transition-all flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                Buat Backup Sekarang
            </button>
        </form>
    </div>

    <div class="max-w-7xl mx-auto">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm" role="alert">
                    <p class="font-bold">Sukses</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r shadow-sm" role="alert">
                    <p class="font-bold">Error</p>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-100">
                <div class="p-6 bg-white border-b border-slate-200">
                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-slate-800">Daftar File Backup Lokal</h3>
                        <p class="text-sm text-slate-500">
                            Sistem secara otomatis membuat backup 1 minggu sekali dan menghapus backup yang berumur lebih dari 1 minggu untuk menghemat ruang penyimpanan.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nama File</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Ukuran</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Dibuat</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @forelse ($backups as $backup)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-brand-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                {{ $backup['name'] }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                            {{ $backup['size'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                            {{ $backup['date']->format('d M Y, H:i') }}
                                            <span class="text-xs text-slate-400 block">{{ $backup['date']->diffForHumans() }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form action="{{ route('admin.backups.download') }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="path" value="{{ $backup['path'] }}">
                                                <button type="submit" class="text-brand-600 hover:text-brand-900 mx-2 font-bold inline-flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    Download
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.backups.destroy') }}" method="POST" class="inline" onsubmit="return confirm('Hapus file backup ini secara permanen?')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="path" value="{{ $backup['path'] }}">
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 mx-2 font-bold inline-flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                            Belum ada file backup yang dibuat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

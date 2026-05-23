@extends('layouts.admin')

@section('header', 'Pengaturan Website')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="glass-card rounded-2xl p-6 md:p-8">
        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- App Name -->
            <div>
                <label for="app_name" class="block text-sm font-bold text-slate-700 mb-2">Nama Aplikasi</label>
                <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $settings['app_name'] ?? '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 outline-none transition" placeholder="Contoh: Catat-in">
                <p class="text-xs text-slate-500 mt-1">Nama ini akan muncul di Title Bar dan pesan WhatsApp.</p>
            </div>

            <!-- App Description -->
            <div>
                <label for="app_description" class="block text-sm font-bold text-slate-700 mb-2">Deskripsi Singkat</label>
                <textarea name="app_description" id="app_description" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 outline-none transition" placeholder="Deskripsi aplikasi...">{{ old('app_description', $settings['app_description'] ?? '') }}</textarea>
                <p class="text-xs text-slate-500 mt-1">Deskripsi ini akan muncul saat aplikasi dibagikan ke WhatsApp (OpenGraph Description).</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- App Icon -->
                <div>
                    <label for="app_icon" class="block text-sm font-bold text-slate-700 mb-2">Ikon Website (Favicon)</label>
                    <div class="flex items-center gap-4">
                        @if(isset($settings['app_icon']))
                            <div class="w-16 h-16 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200 overflow-hidden">
                                <img src="{{ asset('storage/' . $settings['app_icon']) }}" alt="Icon" class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="w-16 h-16 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200 text-slate-400">
                                <i class="fas fa-image text-2xl"></i>
                            </div>
                        @endif
                        <div class="flex-1">
                            <input type="file" name="app_icon" id="app_icon" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 transition">
                            <p class="text-xs text-slate-400 mt-2">Format: PNG, JPG (Disarankan resolusi kotak, max 1MB).</p>
                        </div>
                    </div>
                </div>

                <!-- App Photo (OG Image) -->
                <div>
                    <label for="app_photo" class="block text-sm font-bold text-slate-700 mb-2">Foto / Banner Aplikasi (OG Image)</label>
                    <div class="flex flex-col gap-4">
                        @if(isset($settings['app_photo']))
                            <div class="w-full h-32 rounded-xl bg-slate-100 flex items-center justify-center border border-slate-200 overflow-hidden">
                                <img src="{{ asset('storage/' . $settings['app_photo']) }}" alt="Photo" class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="w-full h-32 rounded-xl bg-slate-100 flex items-center justify-center border border-slate-200 text-slate-400">
                                <i class="fas fa-image text-3xl"></i>
                            </div>
                        @endif
                        <div class="flex-1">
                            <input type="file" name="app_photo" id="app_photo" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 transition">
                            <p class="text-xs text-slate-400 mt-2">Gambar ini akan muncul sebagai preview di WhatsApp / Sosial Media (Max 2MB).</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100">
                <button type="submit" class="w-full md:w-auto px-8 py-3 bg-brand-600 text-white rounded-xl font-bold hover:bg-brand-700 transition">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

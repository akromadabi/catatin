@extends('layouts.admin')

@section('header', 'Paket Langganan')

@section('content')
<div class="glass-card rounded-2xl p-4 md:p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 md:mb-6">
        <h3 class="text-base md:text-lg font-bold text-slate-900">Daftar Paket Layanan</h3>
        <button onclick="document.getElementById('modal-add').classList.remove('hidden')" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 md:py-2.5 rounded-xl text-xs md:text-sm font-bold shadow-lg shadow-brand-500/30 transition-colors">
            + Tambah Paket
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        @forelse($packages as $pkg)
        <div class="bg-white border border-slate-100 shadow-card rounded-2xl p-5 md:p-6 relative hover:shadow-lg transition-all duration-300">
            @if($pkg->is_active)
                <span class="absolute top-4 right-4 bg-emerald-50 text-emerald-600 font-bold text-[9px] md:text-[10px] px-2 py-1 rounded-md uppercase tracking-wider">Aktif</span>
            @else
                <span class="absolute top-4 right-4 bg-rose-50 text-rose-600 font-bold text-[9px] md:text-[10px] px-2 py-1 rounded-md uppercase tracking-wider">Nonaktif</span>
            @endif
            
            <h4 class="text-lg md:text-xl font-bold text-slate-900 mb-1 mt-3 md:mt-4">{{ $pkg->name }}</h4>
            <p class="text-2xl md:text-3xl font-bold text-brand-600 mb-3 md:mb-4">
                Rp {{ number_format($pkg->price, 0, ',', '.') }}<span class="text-xs md:text-sm text-slate-500 font-medium">/bln</span>
            </p>
            <p class="text-slate-500 text-xs md:text-sm font-medium mb-4 md:mb-6 leading-relaxed">{{ $pkg->description ?? 'Tidak ada deskripsi' }}</p>
            
            <ul class="text-xs md:text-sm font-medium text-slate-600 space-y-2 md:space-y-3 mb-6 md:mb-8">
                <li class="flex items-center gap-2 md:gap-3">
                    <div class="w-5 h-5 md:w-6 md:h-6 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 text-[10px] md:text-xs shrink-0">
                        <i class="fas fa-check"></i>
                    </div>
                    Limit: {{ $pkg->transaction_limit ? $pkg->transaction_limit . ' Trx/bln' : 'Tanpa Batas' }}
                </li>
                <li class="flex items-center gap-2 md:gap-3">
                    <div class="w-5 h-5 md:w-6 md:h-6 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 text-[10px] md:text-xs shrink-0">
                        <i class="fas fa-check"></i>
                    </div>
                    Total Pengguna: {{ $pkg->users_count }}
                </li>
            </ul>

            <div class="flex gap-2 md:gap-3 pt-4 border-t border-slate-100">
                <button onclick="editPackage({{ $pkg->toJson() }})" class="flex-1 bg-slate-100 hover:bg-brand-50 text-slate-700 hover:text-brand-700 py-2.5 md:py-3 rounded-xl font-bold text-xs md:text-sm transition-colors">Edit</button>
                <form action="{{ route('admin.packages.destroy', $pkg->id) }}" method="POST" class="flex-1" onsubmit="return confirm('Hapus paket ini? Pastikan tidak ada pengguna yang berlangganan paket ini.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 py-2.5 md:py-3 rounded-xl font-bold text-xs md:text-sm transition-colors">Hapus</button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full py-10 md:py-12 text-center font-medium text-slate-500 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 text-sm">
            Tidak ada paket yang tersedia.
        </div>
        @endforelse
    </div>
</div>

<!-- Modal Add Package -->
<div id="modal-add" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden z-50 flex items-end sm:items-center justify-center transition-all px-0 sm:px-4">
    <div class="bg-white border border-slate-100 shadow-card rounded-t-3xl sm:rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg md:text-xl font-bold text-slate-900 mb-4 md:mb-6">Tambah Paket</h3>
        <form action="{{ route('admin.packages.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Paket</label>
                    <input type="text" name="name" required placeholder="Contoh: Premium" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Harga (Rp)</label>
                    <input type="number" name="price" required min="0" placeholder="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Limit Transaksi per Bulan (Kosongkan jika unlimited)</label>
                    <input type="number" name="transaction_limit" min="1" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Deskripsi</label>
                    <textarea name="description" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition"></textarea>
                </div>
                <div class="flex items-center gap-2 mt-2">
                    <input type="checkbox" name="is_active" id="is_active_add" value="1" checked class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    <label for="is_active_add" class="text-xs md:text-sm font-bold text-slate-700 cursor-pointer">Aktifkan Paket Ini</label>
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3 pb-safe">
                <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="flex-1 sm:flex-none px-4 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-xl transition">Batal</button>
                <button type="submit" class="flex-1 sm:flex-none bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-brand-500/30 transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Package -->
<div id="modal-edit" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden z-50 flex items-end sm:items-center justify-center transition-all px-0 sm:px-4">
    <div class="bg-white border border-slate-100 shadow-card rounded-t-3xl sm:rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg md:text-xl font-bold text-slate-900 mb-4 md:mb-6">Edit Paket</h3>
        <form id="edit-form" method="POST">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Paket</label>
                    <input type="text" name="name" id="edit-name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Harga (Rp)</label>
                    <input type="number" name="price" id="edit-price" required min="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Limit Transaksi per Bulan</label>
                    <input type="number" name="transaction_limit" id="edit-limit" min="1" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Deskripsi</label>
                    <textarea name="description" id="edit-desc" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition"></textarea>
                </div>
                <div class="flex items-center gap-2 mt-2">
                    <input type="checkbox" name="is_active" id="edit-active" value="1" class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    <label for="edit-active" class="text-xs md:text-sm font-bold text-slate-700 cursor-pointer">Aktifkan Paket Ini</label>
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3 pb-safe">
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" class="flex-1 sm:flex-none px-4 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-xl transition">Batal</button>
                <button type="submit" class="flex-1 sm:flex-none bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-brand-500/30 transition">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editPackage(pkg) {
        document.getElementById('edit-name').value = pkg.name;
        document.getElementById('edit-price').value = pkg.price;
        document.getElementById('edit-limit').value = pkg.transaction_limit || '';
        document.getElementById('edit-desc').value = pkg.description || '';
        document.getElementById('edit-active').checked = pkg.is_active ? true : false;
        document.getElementById('edit-form').action = `/admin/packages/${pkg.id}`;
        document.getElementById('modal-edit').classList.remove('hidden');
    }
</script>
@endpush

@extends('layouts.admin')

@section('header', 'Kelola Pengguna')

@section('content')
<div class="glass-card rounded-2xl p-4 md:p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 md:mb-6">
        <h3 class="text-base md:text-lg font-bold text-slate-900">Daftar Pengguna</h3>
        <button onclick="document.getElementById('modal-add').classList.remove('hidden')" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 md:py-2.5 rounded-xl text-xs md:text-sm font-bold shadow-lg shadow-brand-500/30 transition-colors">
            + Tambah User
        </button>
    </div>

    <div class="overflow-x-auto -mx-4 md:mx-0">
        <div class="inline-block min-w-full align-middle px-4 md:px-0">
            <table class="min-w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-500 text-[10px] md:text-xs uppercase tracking-wider font-bold">
                        <th class="py-3 px-3 md:px-4 whitespace-nowrap">Nama</th>
                        <th class="py-3 px-3 md:px-4 whitespace-nowrap">Email</th>
                        <th class="py-3 px-3 md:px-4 whitespace-nowrap">Role</th>
                        <th class="py-3 px-3 md:px-4 whitespace-nowrap">Paket</th>
                        <th class="py-3 px-3 md:px-4 whitespace-nowrap text-center">Trx</th>
                        <th class="py-3 px-3 md:px-4 whitespace-nowrap hidden sm:table-cell">Terdaftar</th>
                        <th class="py-3 px-3 md:px-4 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-xs md:text-sm">
                    @forelse($users as $user)
                    <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-3 md:px-4 font-bold text-slate-900 whitespace-nowrap">{{ $user->name }}</td>
                        <td class="py-3 px-3 md:px-4 font-medium whitespace-nowrap">{{ $user->email }}</td>
                        <td class="py-3 px-3 md:px-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-md text-[10px] md:text-xs font-bold {{ $user->role === 'admin' ? 'bg-purple-50 text-purple-600' : 'bg-slate-100 text-slate-600' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="py-3 px-3 md:px-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-md text-[10px] md:text-xs font-bold bg-brand-50 text-brand-600">
                                {{ $user->package ? $user->package->name : 'Free' }}
                            </span>
                        </td>
                        <td class="py-3 px-3 md:px-4 font-semibold whitespace-nowrap text-center">{{ $user->transactions_count }}</td>
                        <td class="py-3 px-3 md:px-4 font-medium whitespace-nowrap hidden sm:table-cell">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="py-3 px-3 md:px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1 md:gap-2">
                                <button onclick="editUser({{ $user->toJson() }})" class="w-7 h-7 md:w-8 md:h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-brand-50 hover:text-brand-600 transition flex items-center justify-center">
                                    <i class="fas fa-edit text-xs md:text-sm"></i>
                                </button>
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline-block m-0" onsubmit="return confirm('Hapus user ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-7 h-7 md:w-8 md:h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-rose-50 hover:text-rose-600 transition flex items-center justify-center">
                                        <i class="fas fa-trash text-xs md:text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500 font-medium text-xs md:text-sm">Tidak ada pengguna.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add User -->
<div id="modal-add" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden z-50 flex items-end sm:items-center justify-center transition-all px-0 sm:px-4">
    <div class="bg-white border border-slate-100 shadow-card rounded-t-3xl sm:rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg md:text-xl font-bold text-slate-900 mb-4 md:mb-6">Tambah Pengguna</h3>
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Lengkap</label>
                    <input type="text" name="name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Role</label>
                        <select name="role" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none appearance-none transition">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Paket</label>
                        <select name="package_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none appearance-none transition">
                            <option value="">-- Pilih Paket --</option>
                            @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}">{{ $pkg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3 pb-safe">
                <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="flex-1 sm:flex-none px-4 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-xl transition">Batal</button>
                <button type="submit" class="flex-1 sm:flex-none bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-brand-500/30 transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div id="modal-edit" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden z-50 flex items-end sm:items-center justify-center transition-all px-0 sm:px-4">
    <div class="bg-white border border-slate-100 shadow-card rounded-t-3xl sm:rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg md:text-xl font-bold text-slate-900 mb-4 md:mb-6">Edit Pengguna</h3>
        <form id="edit-form" method="POST">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="edit-name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" id="edit-email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Password (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none transition">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Role</label>
                        <select name="role" id="edit-role" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none appearance-none transition">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Paket</label>
                        <select name="package_id" id="edit-package" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none appearance-none transition">
                            <option value="">-- Pilih Paket --</option>
                            @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}">{{ $pkg->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
    function editUser(user) {
        document.getElementById('edit-name').value = user.name;
        document.getElementById('edit-email').value = user.email;
        document.getElementById('edit-role').value = user.role;
        document.getElementById('edit-package').value = user.package_id || '';
        document.getElementById('edit-form').action = `/admin/users/${user.id}`;
        document.getElementById('modal-edit').classList.remove('hidden');
    }
</script>
@endpush

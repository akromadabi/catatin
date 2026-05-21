<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /** Ambil semua proyek yang bisa diakses user (owned + collaborated) */
    public function index()
    {
        $projects = auth()->user()->accessibleProjects()
            ->withCount(['transactions', 'categories', 'members' => fn($q) => $q->where('status', 'active')])
            ->orderBy('created_at')
            ->get();

        // Add role info for current user
        $projects->each(function ($p) {
            $membership = ProjectMember::where('project_id', $p->id)
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->first();
            $p->my_role = $membership?->role ?? 'member';
        });

        return response()->json($projects);
    }

    /** Buat proyek baru dan seed kategori default */
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'icon'  => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $project = Project::create([
            'user_id' => auth()->id(),
            'name'    => $request->name,
            'icon'    => $request->icon ?? 'fas fa-wallet',
            'color'   => $request->color ?? '#6c63ff',
        ]);

        // Insert owner into project_members
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'role'       => 'owner',
            'status'     => 'active',
            'joined_at'  => now(),
        ]);

        // Seed default household categories for the new project
        $defaultCategories = [
            // Pemasukan
            ['name' => 'Gaji & Utama', 'type' => 'pemasukan', 'icon' => 'fas fa-wallet', 'keywords' => ['gaji', 'payroll', 'thr', 'bonus']],
            ['name' => 'Usaha & Sampingan', 'type' => 'pemasukan', 'icon' => 'fas fa-store', 'keywords' => ['jualan', 'omset', 'profit', 'freelance']],
            ['name' => 'Investasi & Pasif', 'type' => 'pemasukan', 'icon' => 'fas fa-chart-line', 'keywords' => ['bunga', 'dividen', 'reksadana', 'cashback']],
            ['name' => 'Pemberian & Lainnya', 'type' => 'pemasukan', 'icon' => 'fas fa-gift', 'keywords' => ['dikasih', 'pemberian', 'utang dibayar', 'warisan']],
            
            // Pengeluaran
            ['name' => 'Belanja Dapur / Pokok', 'type' => 'pengeluaran', 'icon' => 'fas fa-shopping-basket', 'keywords' => ['alfamart', 'indomaret', 'pasar', 'sembako', 'supermarket']],
            ['name' => 'Makan & Jajan', 'type' => 'pengeluaran', 'icon' => 'fas fa-utensils', 'keywords' => ['gofood', 'grabfood', 'shopeefood', 'kopi', 'jajan']],
            ['name' => 'Tagihan & Utilitas', 'type' => 'pengeluaran', 'icon' => 'fas fa-bolt', 'keywords' => ['listrik', 'token', 'pdam', 'pulsa', 'indihome']],
            ['name' => 'Tempat Tinggal', 'type' => 'pengeluaran', 'icon' => 'fas fa-home', 'keywords' => ['sewa', 'kpr', 'iuran', 'tukang', 'prabot']],
            ['name' => 'Transportasi', 'type' => 'pengeluaran', 'icon' => 'fas fa-motorcycle', 'keywords' => ['bensin', 'gojek', 'grab', 'parkir', 'tol']],
            ['name' => 'Pendidikan & Anak', 'type' => 'pengeluaran', 'icon' => 'fas fa-graduation-cap', 'keywords' => ['spp', 'sekolah', 'susu', 'popok', 'buku']],
            ['name' => 'Kesehatan & Perawatan', 'type' => 'pengeluaran', 'icon' => 'fas fa-medkit', 'keywords' => ['apotek', 'dokter', 'skincare', 'salon', 'rumah sakit']],
            ['name' => 'Hiburan & Liburan', 'type' => 'pengeluaran', 'icon' => 'fas fa-gamepad', 'keywords' => ['bioskop', 'hotel', 'tiket', 'liburan', 'mainan']],
            ['name' => 'Sosial & Kewajiban', 'type' => 'pengeluaran', 'icon' => 'fas fa-hands-helping', 'keywords' => ['sedekah', 'zakat', 'kondangan', 'sumbangan', 'kado']],
            ['name' => 'Lain-lain', 'type' => 'pengeluaran', 'icon' => 'fas fa-money-bill-wave', 'keywords' => ['admin bank', 'pajak', 'denda', 'transfer']],
        ];

        foreach ($defaultCategories as $cat) {
            \App\Models\Category::create([
                'user_id'    => auth()->id(),
                'project_id' => $project->id,
                'name'       => $cat['name'],
                'type'       => $cat['type'],
                'icon'       => $cat['icon'],
                'keywords'   => $cat['keywords'] ?? null,
            ]);
        }

        // Set as active project
        session(['active_project_id' => $project->id]);

        return response()->json(['success' => true, 'project' => $project]);
    }

    /** Update proyek (owner & admin only) */
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        
        // Ensure user is owner or admin (or just member if you want to allow everyone, but usually owner)
        abort_unless($project->isOwner(auth()->id()), 403, 'Hanya pemilik yang bisa mengedit proyek.');

        $request->validate([
            'name'  => 'required|string|max:100',
            'icon'  => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $project->update([
            'name'  => $request->name,
            'icon'  => $request->icon ?? $project->icon,
            'color' => $request->color ?? $project->color,
        ]);
        
        // Log activity
        \App\Models\ActivityLog::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'action'     => 'updated',
            'model_type' => 'Project',
            'data'       => ['name' => $project->name],
        ]);

        return response()->json(['success' => true, 'project' => $project]);
    }

    /** Hapus proyek beserta semua kategori & transaksinya (owner only) */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        abort_unless($project->isOwner(auth()->id()), 403, 'Hanya pemilik yang bisa menghapus proyek.');
        
        // If deleting active project, clear session
        if (session('active_project_id') == $project->id) {
            session()->forget('active_project_id');
        }

        $project->delete(); // cascade deletes categories, transactions, members, logs

        return response()->json(['success' => true]);
    }

    /** Ganti proyek aktif (simpan di session) */
    public function switchProject($id)
    {
        $project = Project::findOrFail($id);
        abort_unless($project->isMember(auth()->id()), 403, 'Kamu bukan anggota proyek ini.');
        session(['active_project_id' => $project->id]);
        return response()->json(['success' => true, 'project' => $project]);
    }
}

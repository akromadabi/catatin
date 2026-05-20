<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\ActivityLog;
use App\Models\ProjectMember;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /** Get active project ID — now validates via project_members */
    private function activeProjectId()
    {
        $pid = session('active_project_id');
        if ($pid) {
            // Validate user has access
            $hasAccess = ProjectMember::where('project_id', $pid)
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->exists();
            if ($hasAccess) return $pid;
        }

        // Fallback: pick first accessible project
        $project = auth()->user()->accessibleProjects()->first();
        if ($project) {
            session(['active_project_id' => $project->id]);
            return $project->id;
        }

        return null;
    }

    /** Check if current project is collaborative (>1 member) */
    private function isCollaborative($projectId)
    {
        return ProjectMember::where('project_id', $projectId)
            ->where('status', 'active')
            ->count() > 1;
    }

    public function storeTransaction(Request $request)
    {
        $request->validate([
            'type'     => 'required',
            'amount'   => 'required|numeric',
            'category' => 'required',
            'date'     => 'required|date'
        ]);

        $projectId = $this->activeProjectId();
        if (!$projectId) {
            return response()->json(['error' => 'No active project'], 422);
        }

        $txn = Transaction::create([
            'user_id'    => auth()->id(),
            'project_id' => $projectId,
            'type'       => $request->type,
            'amount'     => $request->amount,
            'category'   => $request->category,
            'desc'       => $request->desc,
            'date'       => $request->date,
        ]);

        // Log activity
        ActivityLog::create([
            'project_id' => $projectId,
            'user_id'    => auth()->id(),
            'action'     => 'created',
            'model_type' => 'Transaction',
            'model_id'   => $txn->id,
            'data'       => $txn->toArray(),
        ]);

        // Notify other project members via Web Push
        if ($this->isCollaborative($projectId)) {
            $otherMembers = ProjectMember::where('project_id', $projectId)
                ->where('user_id', '!=', auth()->id())
                ->where('status', 'active')
                ->with('user')
                ->get();
            
            $projectName = \App\Models\Project::find($projectId)->name ?? 'Proyek';
            $userName = auth()->user()->name;
            $typeStr = $request->type == 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
            $amountStr = 'Rp ' . number_format($request->amount, 0, ',', '.');
            
            foreach ($otherMembers as $member) {
                if ($member->user) {
                    $member->user->notify(new \App\Notifications\GeneralPushNotification(
                        "Transaksi Baru",
                        "{$userName} menambahkan {$typeStr} sebesar {$amountStr} di '{$projectName}'",
                        "/dashboard",
                        "/icons/icon-192x192.png"
                    ));
                }
            }
        }

        return response()->json($txn);
    }

    public function deleteTransaction($id)
    {
        $projectId = $this->activeProjectId();
        $txn = Transaction::where('project_id', $projectId)->findOrFail($id);

        // Log before delete (save snapshot for undo)
        ActivityLog::create([
            'project_id' => $projectId,
            'user_id'    => auth()->id(),
            'action'     => 'deleted',
            'model_type' => 'Transaction',
            'model_id'   => $txn->id,
            'data'       => $txn->toArray(),
        ]);

        $txn->delete();
        return response()->json(['success' => true]);
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required',
        ]);

        $projectId = $this->activeProjectId();
        if (!$projectId) {
            return response()->json(['error' => 'No active project'], 422);
        }

        $cat = Category::create([
            'user_id'    => auth()->id(),
            'project_id' => $projectId,
            'name'       => $request->name,
            'type'       => $request->type,
            'icon'       => $request->icon ?? 'fas fa-tag',
            'color'      => $request->color ?? null,
            'keywords'   => $this->parseKeywords($request->keywords),
        ]);

        ActivityLog::create([
            'project_id' => $projectId,
            'user_id'    => auth()->id(),
            'action'     => 'created',
            'model_type' => 'Category',
            'model_id'   => $cat->id,
            'data'       => $cat->toArray(),
        ]);

        return response()->json($cat);
    }

    public function deleteCategory($id)
    {
        $projectId = $this->activeProjectId();

        // Owner check for delete
        $project = \App\Models\Project::findOrFail($projectId);
        if (!$project->isOwner(auth()->id())) {
            return response()->json(['error' => 'Hanya pemilik proyek yang bisa menghapus kategori.'], 403);
        }

        $cat = Category::where('project_id', $projectId)->findOrFail($id);

        ActivityLog::create([
            'project_id' => $projectId,
            'user_id'    => auth()->id(),
            'action'     => 'deleted',
            'model_type' => 'Category',
            'model_id'   => $cat->id,
            'data'       => $cat->toArray(),
        ]);

        $cat->delete();
        return response()->json(['success' => true]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user->name = $request->name;

        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        // Log profile update activity
        $projectId = $this->activeProjectId();
        if ($projectId) {
            ActivityLog::create([
                'project_id' => $projectId,
                'user_id'    => $user->id,
                'action'     => 'updated',
                'model_type' => 'User',
                'data'       => ['user_name' => $user->name],
            ]);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null
            ]
        ]);
    }

    public function updateCategory(Request $request, $id)
    {
        $projectId = $this->activeProjectId();
        $cat = Category::where('project_id', $projectId)->findOrFail($id);

        $cat->update([
            'name'     => $request->name     ?? $cat->name,
            'icon'     => $request->icon     ?? $cat->icon,
            'color'    => $request->color    ?? $cat->color,
            'keywords' => $this->parseKeywords($request->keywords),
        ]);

        ActivityLog::create([
            'project_id' => $projectId,
            'user_id'    => auth()->id(),
            'action'     => 'updated',
            'model_type' => 'Category',
            'model_id'   => $cat->id,
            'data'       => $cat->toArray(),
        ]);

        return response()->json($cat);
    }

    /**
     * Parse keywords from various input formats:
     * - null / empty → null
     * - array → trimmed array
     * - comma-separated string → array
     */
    private function parseKeywords($raw): ?array
    {
        if (is_null($raw) || $raw === '' || $raw === []) return null;
        if (is_array($raw)) {
            return array_values(array_filter(array_map('trim', $raw)));
        }
        if (is_string($raw)) {
            $parts = preg_split('/[,،\s]+/', $raw);
            $parts = array_values(array_filter(array_map('trim', $parts)));
            return empty($parts) ? null : $parts;
        }
        return null;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollaborationController extends Controller
{
    /**
     * List members of a project.
     */
    public function listMembers($projectId)
    {
        $project = Project::findOrFail($projectId);
        abort_unless($project->isMember(auth()->id()), 403);

        $members = $project->members()
            ->where('status', 'active')
            ->with('user:id,name,email,avatar')
            ->get()
            ->map(fn($m) => [
                'id'        => $m->id,
                'user_id'   => $m->user_id,
                'name'      => $m->user->name,
                'email'     => $m->user->email,
                'avatar'    => $m->user->avatar ? asset('storage/' . $m->user->avatar) : null,
                'role'      => $m->role,
                'joined_at' => $m->joined_at?->format('d M Y'),
            ]);

        $isOwner = $project->isOwner(auth()->id());

        return response()->json([
            'members'  => $members,
            'is_owner' => $isOwner,
            'project'  => [
                'id'   => $project->id,
                'name' => $project->name,
            ],
        ]);
    }

    /**
     * Generate invite link (owner only).
     */
    public function generateInvite($projectId)
    {
        $project = Project::findOrFail($projectId);
        abort_unless($project->isOwner(auth()->id()), 403, 'Hanya pemilik yang bisa mengundang.');

        $token = Str::random(32);

        // Create a pending membership with invite token
        $member = ProjectMember::create([
            'project_id'   => $project->id,
            'user_id'      => null, // completely nullable link invite
            'role'         => 'member',
            'status'       => 'pending',
            'invite_token' => $token,
            'invited_at'   => now(),
        ]);

        $link = url("/invite/{$token}");

        // WhatsApp share message
        $projectName = $project->name;
        $userName = auth()->user()->name;
        $waMessage = urlencode("Hei! {$userName} mengundang kamu untuk bergabung ke proyek \"{$projectName}\" di Catat-in. Klik link berikut:\n{$link}");
        $waUrl = "https://wa.me/?text={$waMessage}";

        return response()->json([
            'link'   => $link,
            'wa_url' => $waUrl,
            'token'  => $token,
        ]);
    }

    /**
     * Accept invite via token.
     */
    public function acceptInvite($token)
    {
        $invite = ProjectMember::where('invite_token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invite) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Undangan tidak valid atau sudah digunakan.'], 404);
            }
            return redirect('/dashboard')->with('error', 'Undangan tidak valid atau sudah digunakan.');
        }

        // Check if token is expired (7 days)
        if ($invite->invited_at && $invite->invited_at->diffInDays(now()) > 7) {
            $invite->delete();
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Undangan sudah kedaluwarsa.'], 410);
            }
            return redirect('/dashboard')->with('error', 'Undangan sudah kedaluwarsa.');
        }

        $userId = auth()->id();

        // Not logged in? Redirect to login with invite token
        if (!$userId) {
            return redirect("/login?invite={$token}");
        }

        // Check if already a member
        $existing = ProjectMember::where('project_id', $invite->project_id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            session(['active_project_id' => $invite->project_id]);
            $invite->delete(); // clean up unused invite
            return redirect('/dashboard')->with('success', 'Kamu sudah menjadi anggota proyek ini.');
        }

        // Update the invite: assign the actual user and activate
        $invite->update([
            'user_id'      => $userId,
            'status'       => 'active',
            'invite_token' => null, // consume the token
            'joined_at'    => now(),
        ]);

        // Set as active project
        session(['active_project_id' => $invite->project_id]);

        // Log activity
        ActivityLog::create([
            'project_id' => $invite->project_id,
            'user_id'    => $userId,
            'action'     => 'joined',
            'model_type' => 'ProjectMember',
            'model_id'   => $invite->id,
            'data'       => ['user_name' => auth()->user()->name],
        ]);

        return redirect('/dashboard')->with('success', 'Berhasil bergabung ke proyek!');
    }

    /**
     * Remove a member (owner only).
     */
    public function removeMember($projectId, $userId)
    {
        $project = Project::findOrFail($projectId);
        abort_unless($project->isOwner(auth()->id()), 403, 'Hanya pemilik yang bisa menghapus anggota.');

        // Can't remove the owner
        $member = ProjectMember::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->firstOrFail();

        if ($member->role === 'owner') {
            return response()->json(['error' => 'Tidak bisa menghapus pemilik proyek.'], 422);
        }

        $memberName = $member->user->name ?? 'Unknown';
        $member->delete();

        ActivityLog::create([
            'project_id' => $projectId,
            'user_id'    => auth()->id(),
            'action'     => 'removed_member',
            'model_type' => 'ProjectMember',
            'model_id'   => null,
            'data'       => ['removed_user' => $memberName, 'removed_user_id' => $userId],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Leave a project (member leaves voluntarily).
     */
    public function leaveProject($projectId)
    {
        $member = ProjectMember::where('project_id', $projectId)
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->firstOrFail();

        if ($member->role === 'owner') {
            return response()->json(['error' => 'Pemilik tidak bisa meninggalkan proyek. Hapus proyek jika ingin menghapusnya.'], 422);
        }

        $member->delete();

        // Clear active project if leaving the active one
        if (session('active_project_id') == $projectId) {
            session()->forget('active_project_id');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get recent activity log for a project.
     */
    public function activityLog($projectId)
    {
        $project = Project::findOrFail($projectId);
        abort_unless($project->isMember(auth()->id()), 403);

        $logs = ActivityLog::where('project_id', $projectId)
            ->with('user:id,name,avatar')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn($log) => [
                'id'         => $log->id,
                'user_name'  => $log->user->name ?? 'Deleted',
                'user_avatar'=> $log->user?->avatar ? asset('storage/' . $log->user->avatar) : null,
                'action'     => $log->action,
                'model_type' => $log->model_type,
                'data'       => $log->data,
                'undone'     => $log->undone,
                'time'       => $log->created_at->diffForHumans(),
            ]);

        return response()->json($logs);
    }

    /**
     * Undo a logged action.
     */
    public function undoAction($logId)
    {
        $log = ActivityLog::findOrFail($logId);
        $project = Project::findOrFail($log->project_id);
        abort_unless($project->isMember(auth()->id()), 403);

        if ($log->undone) {
            return response()->json(['error' => 'Aksi ini sudah di-undo.'], 422);
        }

        $data = $log->data;

        switch ($log->action) {
            case 'deleted':
                // Restore the deleted model
                if ($log->model_type === 'Transaction' && $data) {
                    \App\Models\Transaction::create($data);
                } elseif ($log->model_type === 'Category' && $data) {
                    \App\Models\Category::create($data);
                }
                break;

            case 'created':
                // Delete the created model
                if ($log->model_type === 'Transaction' && $log->model_id) {
                    \App\Models\Transaction::find($log->model_id)?->delete();
                } elseif ($log->model_type === 'Category' && $log->model_id) {
                    \App\Models\Category::find($log->model_id)?->delete();
                }
                break;
        }

        $log->update(['undone' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Invite a user to a project directly by their email address.
     */
    public function inviteByEmail(Request $request, $projectId)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $project = Project::findOrFail($projectId);
        abort_unless($project->isOwner(auth()->id()), 403, 'Hanya pemilik yang bisa mengundang.');

        $email = strtolower(trim($request->email));
        $invitedUser = \App\Models\User::where('email', $email)->first();

        if ($invitedUser) {
            // Check if already a member
            $existing = ProjectMember::where('project_id', $project->id)
                ->where('user_id', $invitedUser->id)
                ->first();

            if ($existing) {
                if ($existing->status === 'active') {
                    return response()->json(['error' => 'User tersebut sudah menjadi anggota proyek.'], 422);
                } else {
                    return response()->json(['error' => 'Undangan pending untuk user tersebut sudah dikirim.'], 422);
                }
            }

            // Create pending membership in-app
            ProjectMember::create([
                'project_id' => $project->id,
                'user_id'    => $invitedUser->id,
                'role'       => 'member',
                'status'     => 'pending',
                'invited_at' => now(),
            ]);

            // Send push notification
            $invitedUser->notify(new \App\Notifications\GeneralPushNotification(
                "Undangan Proyek",
                auth()->user()->name . " mengundangmu ke proyek '{$project->name}'",
                "/dashboard",
                "/icons/icon-192x192.png"
            ));

            return response()->json(['success' => true, 'message' => "Undangan berhasil dikirim ke {$invitedUser->name}! Notifikasi akan muncul di aplikasinya."]);
        } else {
            // Email is not registered yet. Create a pending anonymous invite link
            $token = Str::random(32);
            ProjectMember::create([
                'project_id'   => $project->id,
                'user_id'      => null, // completely nullable
                'role'         => 'member',
                'status'       => 'pending',
                'invite_token' => $token,
                'invited_at'   => now(),
            ]);

            $link = url("/invite/{$token}");
            return response()->json([
                'success' => true,
                'message' => 'User belum terdaftar. Tautan undangan berhasil dibuat!',
                'link'    => $link
            ]);
        }
    }

    /**
     * Log a generic activity from the frontend (like PDF download).
     */
    public function logGenericActivity(Request $request, $projectId)
    {
        $request->validate([
            'action'     => 'required|string',
            'model_type' => 'required|string',
            'data'       => 'nullable|array',
        ]);

        $project = Project::findOrFail($projectId);
        abort_unless($project->isMember(auth()->id()), 403);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'action'     => $request->action,
            'model_type' => $request->model_type,
            'data'       => $request->data,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Load recent notifications and invitations for the current user.
     */
    public function loadNotifications()
    {
        $userId = auth()->id();

        // 1. Pending invitations to collaborate
        $invites = ProjectMember::where('user_id', $userId)
            ->where('status', 'pending')
            ->with(['project.owner:id,name'])
            ->get()
            ->map(fn($inv) => [
                'id'           => $inv->id,
                'type'         => 'invite',
                'project_name' => $inv->project->name ?? 'Proyek Tanpa Nama',
                'owner_name'   => $inv->project->owner->name ?? 'Pengguna Lain',
                'invited_at'   => $inv->invited_at?->diffForHumans() ?? 'Baru saja',
            ]);

        // 2. Recent activities from other members in the active project
        $activeProjectId = session('active_project_id');
        $activities = collect();

        if ($activeProjectId) {
            $project = Project::find($activeProjectId);
            if ($project && $project->isMember($userId)) {
                $activities = ActivityLog::where('project_id', $activeProjectId)
                    ->where('user_id', '!=', $userId)
                    ->with('user:id,name,avatar')
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get()
                    ->map(fn($log) => [
                        'id'         => $log->id,
                        'type'       => 'activity',
                        'user_name'  => $log->user->name ?? 'Pengguna',
                        'avatar'     => $log->user?->avatar ? asset('storage/' . $log->user->avatar) : null,
                        'action'     => $log->action,
                        'model_type' => $log->model_type,
                        'data'       => $log->data,
                        'time'       => $log->created_at->diffForHumans() ?? 'Baru saja',
                    ]);
            }
        }

        return response()->json([
            'invites'    => $invites,
            'activities' => $activities,
        ]);
    }

    /**
     * Accept in-app invitation.
     */
    public function acceptInAppInvite($id)
    {
        $invite = ProjectMember::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->firstOrFail();

        $invite->update([
            'status'    => 'active',
            'joined_at' => now(),
        ]);

        // Set as active project
        session(['active_project_id' => $invite->project_id]);

        // Log activity
        ActivityLog::create([
            'project_id' => $invite->project_id,
            'user_id'    => auth()->id(),
            'action'     => 'joined',
            'model_type' => 'ProjectMember',
            'model_id'   => $invite->id,
            'data'       => ['user_name' => auth()->user()->name],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Decline/delete in-app invitation.
     */
    public function declineInAppInvite($id)
    {
        $invite = ProjectMember::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->firstOrFail();

        $invite->delete();

        return response()->json(['success' => true]);
    }
}


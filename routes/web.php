<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CollaborationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\ApiController;

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    $user = auth()->user();

    // Resolve active project (now membership-based)
    $activeProjectId = session('active_project_id');
    $activeProject = null;

    if ($activeProjectId) {
        // Verify user is a member
        $activeProject = $user->accessibleProjects()->find($activeProjectId);
    }

    // If no active project, pick the first accessible one
    if (!$activeProject) {
        $activeProject = $user->accessibleProjects()->orderBy('created_at')->first();
        if ($activeProject) {
            session(['active_project_id' => $activeProject->id]);
        }
    }

    // Get all accessible projects (owned + collaborated)
    $allProjects = $user->accessibleProjects()
        ->withCount(['transactions', 'members' => fn($q) => $q->where('status', 'active')])
        ->orderBy('created_at')
        ->get();

    // Add role info
    $allProjects->each(function ($p) use ($user) {
        $membership = \App\Models\ProjectMember::where('project_id', $p->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
        $p->my_role = $membership?->role ?? 'member';
    });

    // Is this project collaborative?
    $isCollaborative = false;
    if ($activeProject) {
        $isCollaborative = $activeProject->activeMemberCount() > 1;
    }

    // Load data scoped to active project
    if ($activeProject) {
        $user->load([
            'categories' => fn($q) => $q->where('project_id', $activeProject->id),
            'transactions' => fn($q) => $q->where('project_id', $activeProject->id)->orderByDesc('created_at'),
        ]);

        // Load user names for transactions (for collaborative display)
        if ($isCollaborative) {
            $user->transactions->load('user:id,name,avatar');
        }
    } else {
        $user->setRelation('categories', collect());
        $user->setRelation('transactions', collect());
    }

    return view('app', compact('activeProject', 'allProjects', 'isCollaborative'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Routes
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function() {
        Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'index'])->name('dashboard');
        Route::resource('users', \App\Http\Controllers\AdminUserController::class)->except(['create', 'show', 'edit']);
        Route::resource('packages', \App\Http\Controllers\AdminPackageController::class)->except(['create', 'show', 'edit']);
    });

    // API Routes for SPA
    Route::post('/api/transactions', [ApiController::class, 'storeTransaction']);
    Route::delete('/api/transactions/{id}', [ApiController::class, 'deleteTransaction']);
    Route::post('/api/categories', [ApiController::class, 'storeCategory']);
    Route::delete('/api/categories/{id}', [ApiController::class, 'deleteCategory']);
    Route::post('/api/profile', [ApiController::class, 'updateProfile']);

    // Project Routes
    Route::get('/api/projects', [ProjectController::class, 'index']);
    Route::post('/api/projects', [ProjectController::class, 'store']);
    Route::delete('/api/projects/{id}', [ProjectController::class, 'destroy']);
    Route::post('/api/projects/{id}/switch', [ProjectController::class, 'switchProject']);

    // Collaboration Routes
    Route::get('/api/projects/{id}/members', [CollaborationController::class, 'listMembers']);
    Route::post('/api/projects/{id}/invite', [CollaborationController::class, 'generateInvite']);
    Route::post('/api/projects/{id}/invite-email', [CollaborationController::class, 'inviteByEmail']);
    Route::post('/api/projects/{id}/activity/log', [CollaborationController::class, 'logGenericActivity']);
    Route::delete('/api/projects/{projectId}/members/{userId}', [CollaborationController::class, 'removeMember']);
    Route::post('/api/projects/{id}/leave', [CollaborationController::class, 'leaveProject']);
    Route::get('/api/projects/{id}/activity', [CollaborationController::class, 'activityLog']);
    Route::post('/api/activity/{id}/undo', [CollaborationController::class, 'undoAction']);

    // Notifications and In-App Invites
    Route::get('/api/notifications', [CollaborationController::class, 'loadNotifications']);
    Route::post('/api/notifications/invites/{id}/accept', [CollaborationController::class, 'acceptInAppInvite']);
    Route::post('/api/notifications/invites/{id}/decline', [CollaborationController::class, 'declineInAppInvite']);
});

// Accept invite (unauthenticated — will redirect to login)
Route::get('/invite/{token}', function ($token) {
    if (auth()->check()) {
        return app(CollaborationController::class)->acceptInvite($token);
    }
    return redirect("/login?invite={$token}");
})->name('invite.public');

require __DIR__.'/auth.php';

// Google Auth Routes
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

// Quick Login (Local Testing Only)
if (app()->environment('local')) {
    Route::get('/quick-login/{email}', function ($email) {
        $user = \App\Models\User::where('email', $email)->first();
        if ($user) {
            auth()->login($user);
            return redirect($user->role === 'admin' ? '/admin/dashboard' : '/dashboard');
        }
        return redirect('/login');
    })->name('quick.login');
}

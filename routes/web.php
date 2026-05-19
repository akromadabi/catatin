<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\ApiController;

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    auth()->user()->load(['categories', 'transactions' => function($q) {
        $q->orderByDesc('created_at');
    }]);
    return view('app');
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
});

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

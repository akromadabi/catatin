<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Log successful login activity
        $user = auth()->user();
        $project = $user->accessibleProjects()->first();
        if ($project) {
            \App\Models\ActivityLog::create([
                'project_id' => $project->id,
                'user_id'    => $user->id,
                'action'     => 'login',
                'model_type' => 'User',
                'data'       => ['user_name' => $user->name],
            ]);
        }

        // If there's a pending invite token, redirect to accept it
        if ($request->has('invite') && $request->invite) {
            return redirect("/invite/{$request->invite}");
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

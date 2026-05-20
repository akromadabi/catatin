<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Seed default household categories
        $defaultCategories = [
            ['name' => 'Gaji/Upah', 'type' => 'pemasukan', 'icon' => 'fas fa-wallet'],
            ['name' => 'Bonus/THR', 'type' => 'pemasukan', 'icon' => 'fas fa-gift'],
            ['name' => 'Hasil Usaha', 'type' => 'pemasukan', 'icon' => 'fas fa-store'],
            ['name' => 'Lain-lain', 'type' => 'pemasukan', 'icon' => 'fas fa-star'],
            
            ['name' => 'Makan & Belanja', 'type' => 'pengeluaran', 'icon' => 'fas fa-utensils'],
            ['name' => 'Listrik/Air/Internet', 'type' => 'pengeluaran', 'icon' => 'fas fa-bolt'],
            ['name' => 'Transportasi', 'type' => 'pengeluaran', 'icon' => 'fas fa-motorcycle'],
            ['name' => 'Cicilan/Sewa', 'type' => 'pengeluaran', 'icon' => 'fas fa-home'],
            ['name' => 'Kesehatan', 'type' => 'pengeluaran', 'icon' => 'fas fa-medkit'],
            ['name' => 'Hiburan/Jajan', 'type' => 'pengeluaran', 'icon' => 'fas fa-gamepad'],
            ['name' => 'Sedekah', 'type' => 'pengeluaran', 'icon' => 'fas fa-hands-helping'],
            ['name' => 'Lain-lain', 'type' => 'pengeluaran', 'icon' => 'fas fa-money-bill-wave']
        ];
        
        foreach ($defaultCategories as $cat) {
            \App\Models\Category::create([
                'user_id' => $user->id,
                'name' => $cat['name'],
                'type' => $cat['type'],
                'icon' => $cat['icon']
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        // If there's a pending invite token, redirect to accept it
        if ($request->has('invite') && $request->invite) {
            return redirect("/invite/{$request->invite}");
        }

        return redirect(route('dashboard', absolute: false));
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('google_id', $googleUser->id)->orWhere('email', $googleUser->email)->first();

            if ($user) {
                // Update google_id if it was not set (e.g. registered via email)
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->id]);
                }
                Auth::login($user);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    // 'role' is defaulted to 'user' in migration
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
                
                Auth::login($user);
            }

            return redirect('/dashboard');
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Gagal login menggunakan Google.');
        }
    }
}

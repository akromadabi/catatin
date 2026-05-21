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
                    ['name' => 'Lain-lain', 'type' => 'pengeluaran', 'icon' => 'fas fa-money-bill-wave', 'keywords' => ['admin bank', 'pajak', 'denda', 'transfer']]
                ];
                
                foreach ($defaultCategories as $cat) {
                    \App\Models\Category::create([
                        'user_id' => $user->id,
                        'name' => $cat['name'],
                        'type' => $cat['type'],
                        'icon' => $cat['icon'],
                        'keywords' => $cat['keywords'] ?? null
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

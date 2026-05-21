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

        event(new Registered($user));

        Auth::login($user);

        // If there's a pending invite token, redirect to accept it
        if ($request->has('invite') && $request->invite) {
            return redirect("/invite/{$request->invite}");
        }

        return redirect(route('dashboard', absolute: false));
    }
}

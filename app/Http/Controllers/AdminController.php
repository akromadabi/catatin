<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Package;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Anda bukan admin.');
        }

        $users = User::withCount('transactions')->orderByDesc('created_at')->limit(5)->get();
        $totalTxn = Transaction::count();
        $totalVol = Transaction::where('type', 'pemasukan')->sum('amount');
        $totalPackages = Package::where('is_active', true)->count();
        $totalUsers = User::count();

        // Chart Data: Last 7 days global transactions
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $last7Days->push(now()->subDays($i)->format('Y-m-d'));
        }

        $chartData = [
            'labels' => $last7Days->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray(),
            'pemasukan' => [],
            'pengeluaran' => []
        ];

        foreach ($last7Days as $date) {
            $in = Transaction::where('type', 'pemasukan')->whereDate('date', $date)->sum('amount');
            $out = Transaction::where('type', 'pengeluaran')->whereDate('date', $date)->sum('amount');
            $chartData['pemasukan'][] = $in;
            $chartData['pengeluaran'][] = $out;
        }

        return view('admin.dashboard', compact('users', 'totalTxn', 'totalVol', 'totalPackages', 'totalUsers', 'chartData'));
    }

    public function settings()
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_description' => 'nullable|string',
            'app_icon' => 'nullable|image|max:1024',
            'app_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->has('app_name')) {
            \App\Models\Setting::updateOrCreate(['key' => 'app_name'], ['value' => $request->app_name]);
        }
        if ($request->has('app_description')) {
            \App\Models\Setting::updateOrCreate(['key' => 'app_description'], ['value' => $request->app_description]);
        }
        if ($request->hasFile('app_icon')) {
            $path = $request->file('app_icon')->store('settings', 'public');
            \App\Models\Setting::updateOrCreate(['key' => 'app_icon'], ['value' => $path]);
        }
        if ($request->hasFile('app_photo')) {
            $path = $request->file('app_photo')->store('settings', 'public');
            \App\Models\Setting::updateOrCreate(['key' => 'app_photo'], ['value' => $path]);
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui!');
    }
}

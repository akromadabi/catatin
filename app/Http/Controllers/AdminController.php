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
}

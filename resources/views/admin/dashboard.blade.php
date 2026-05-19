@extends('layouts.admin')

@section('header', 'Overview Dashboard')

@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
    <div class="glass-card rounded-2xl p-4 md:p-6 transition-all duration-300 hover:shadow-lg">
        <h3 class="text-slate-500 text-xs md:text-sm font-medium mb-1 truncate">Total Pengguna</h3>
        <p class="text-xl md:text-3xl font-bold text-slate-900">{{ $totalUsers }}</p>
    </div>
    <div class="glass-card rounded-2xl p-4 md:p-6 transition-all duration-300 hover:shadow-lg">
        <h3 class="text-slate-500 text-xs md:text-sm font-medium mb-1 truncate">Total Transaksi</h3>
        <p class="text-xl md:text-3xl font-bold text-slate-900">{{ $totalTxn }}</p>
    </div>
    <div class="glass-card rounded-2xl p-4 md:p-6 transition-all duration-300 hover:shadow-lg col-span-2 md:col-span-1">
        <h3 class="text-slate-500 text-xs md:text-sm font-medium mb-1 truncate">Volume Masuk</h3>
        <p class="text-xl md:text-3xl font-bold text-emerald-500 truncate">Rp {{ number_format($totalVol, 0, ',', '.') }}</p>
    </div>
    <div class="glass-card rounded-2xl p-4 md:p-6 transition-all duration-300 hover:shadow-lg col-span-2 md:col-span-1">
        <h3 class="text-slate-500 text-xs md:text-sm font-medium mb-1 truncate">Paket Aktif</h3>
        <p class="text-xl md:text-3xl font-bold text-slate-900">{{ $totalPackages }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
    <!-- Chart -->
    <div class="lg:col-span-2 glass-card rounded-2xl p-4 md:p-6">
        <h3 class="text-base md:text-lg font-bold mb-4 text-slate-900">Aktivitas Transaksi (7 Hari)</h3>
        <div class="relative h-60 md:h-72">
            <canvas id="trxChart"></canvas>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="glass-card rounded-2xl p-4 md:p-6">
        <h3 class="text-base md:text-lg font-bold mb-4 text-slate-900">Pengguna Baru</h3>
        <div class="space-y-4">
            @forelse($users as $user)
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 last:border-0 last:pb-0 transition-colors">
                <div class="overflow-hidden">
                    <p class="font-bold text-slate-900 truncate">{{ $user->name }}</p>
                    <p class="text-xs font-medium text-slate-500 truncate">{{ $user->email }}</p>
                </div>
                <div class="text-[10px] md:text-xs text-brand-600 font-bold bg-brand-50 px-2 py-1 rounded-md shrink-0 ml-2">
                    {{ $user->transactions_count }} Trx
                </div>
            </div>
            @empty
            <p class="text-slate-500 text-sm">Belum ada pengguna.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('trxChart').getContext('2d');
    const chartData = @json($chartData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: chartData.pemasukan,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Pengeluaran',
                    data: chartData.pengeluaran,
                    borderColor: '#f43f5e',
                    backgroundColor: 'rgba(244, 63, 94, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#64748b', font: { family: 'Inter', weight: '600', size: 10 } },
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { color: '#64748b', font: { family: 'Inter', weight: '500', size: 10 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { family: 'Inter', weight: '500', size: 10 }, maxRotation: 45, minRotation: 45 }
                }
            }
        }
    });
</script>
@endpush

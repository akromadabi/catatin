<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Panel - Catat-in</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png?v=2">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6',
                            600: '#2563eb', 700: '#1d4ed8', 900: '#1e3a8a',
                        }
                    },
                    boxShadow: {
                        'card': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #334155; }
        .sidebar-item.active { background-color: #eff6ff; color: #2563eb; border-left: 4px solid #2563eb; }
        .glass-card { background: #ffffff; border: 1px solid #f1f5f9; box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05); }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-[#f8fafc]">

    <!-- Sidebar (Desktop Only) -->
    <aside class="hidden md:flex w-64 bg-white border-r border-slate-100 flex-col shrink-0 z-20">
        <div class="h-16 flex items-center px-6 border-b border-slate-100">
            <span class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center text-white text-sm">C</div>
                AdminPanel
            </span>
        </div>
        
        <nav class="flex-1 overflow-y-auto py-4">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-item flex items-center px-6 py-3 text-slate-500 font-medium hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie w-6 text-center"></i>
                <span class="ml-2">Dashboard</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="sidebar-item flex items-center px-6 py-3 text-slate-500 font-medium hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fas fa-users w-6 text-center"></i>
                <span class="ml-2">Kelola Pengguna</span>
            </a>
            <a href="{{ route('admin.packages.index') }}" class="sidebar-item flex items-center px-6 py-3 text-slate-500 font-medium hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}">
                <i class="fas fa-box w-6 text-center"></i>
                <span class="ml-2">Paket Langganan</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center w-full px-4 py-2 text-rose-500 font-medium hover:bg-rose-50 rounded-xl transition-colors">
                    <i class="fas fa-sign-out-alt w-6 text-center"></i>
                    <span class="ml-2">Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col relative overflow-hidden bg-[#f8fafc]">
        <!-- Topbar -->
        <header class="h-16 flex items-center justify-between px-4 md:px-8 bg-white border-b border-slate-100 shrink-0 z-10">
            <h1 class="text-lg md:text-xl font-bold text-slate-900 truncate">@yield('header')</h1>
            <div class="flex items-center gap-2 md:gap-3 shrink-0">
                <a href="{{ url('/') }}" class="text-[10px] md:text-xs font-bold text-brand-600 bg-brand-50 px-2 md:px-3 py-1.5 md:py-2 rounded-lg hover:bg-brand-100 transition">
                    Aplikasi User
                </a>
                <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center text-sm font-bold text-slate-600 shrink-0">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 overflow-auto p-4 md:p-6 pb-28 md:pb-6">
            @if(session('success'))
                <div class="mb-4 md:mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3 font-medium text-sm md:text-base">
                    <i class="fas fa-check-circle text-emerald-500"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 md:mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center gap-3 font-medium text-sm md:text-base">
                    <i class="fas fa-exclamation-circle text-rose-500"></i>
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 md:mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700">
                    <ul class="list-disc pl-5 font-medium text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Bottom Navigation (Mobile Only) -->
    <nav class="md:hidden fixed bottom-0 w-full bg-white/90 backdrop-blur-md shadow-[0_-4px_25px_rgba(0,0,0,0.05)] h-[72px] px-2 flex justify-around items-center z-40 pb-safe rounded-t-3xl border-t border-slate-100 transition-all">
        <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center gap-1 w-16 {{ request()->routeIs('admin.dashboard') ? 'text-brand-600' : 'text-slate-400' }}">
            <i class="fas fa-chart-pie text-xl transition-transform {{ request()->routeIs('admin.dashboard') ? '-translate-y-1' : '' }}"></i>
            <span class="text-[10px] font-bold">Home</span>
        </a>
        <a href="{{ route('admin.users.index') }}" class="flex flex-col items-center gap-1 w-16 {{ request()->routeIs('admin.users.*') ? 'text-brand-600' : 'text-slate-400' }}">
            <i class="fas fa-users text-xl transition-transform {{ request()->routeIs('admin.users.*') ? '-translate-y-1' : '' }}"></i>
            <span class="text-[10px] font-bold">User</span>
        </a>
        <a href="{{ route('admin.packages.index') }}" class="flex flex-col items-center gap-1 w-16 {{ request()->routeIs('admin.packages.*') ? 'text-brand-600' : 'text-slate-400' }}">
            <i class="fas fa-box text-xl transition-transform {{ request()->routeIs('admin.packages.*') ? '-translate-y-1' : '' }}"></i>
            <span class="text-[10px] font-bold">Paket</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="flex flex-col items-center w-16 text-slate-400 hover:text-rose-500 transition-colors">
            @csrf
            <button type="submit" class="flex flex-col items-center gap-1 focus:outline-none">
                <i class="fas fa-sign-out-alt text-xl"></i>
                <span class="text-[10px] font-bold">Keluar</span>
            </button>
        </form>
    </nav>

    @stack('scripts')
</body>
</html>

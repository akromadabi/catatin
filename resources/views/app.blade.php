<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
  <title>Catat-in App</title>
  <meta name="theme-color" content="#ffffff">
  <link rel="manifest" href="/manifest.json?v=2">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
  <link rel="apple-touch-icon" href="/icons/icon-192x192.png?v=2">
  <!-- pdfmake PDF library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- Using SF Pro / Inter font stack -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- FontAwesome for icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: {
            brand: {
              50: '#eff6ff',
              100: '#dbeafe',
              500: '#3b82f6',
              600: '#2563eb', // Primary Blue from reference
              700: '#1d4ed8',
              900: '#1e3a8a',
            }
          },
          boxShadow: {
            'card': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
            'nav': '0 -4px 20px -2px rgba(0, 0, 0, 0.05)'
          }
        }
      }
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    body, html { margin: 0; padding: 0; height: 100%; background-color: #f8fafc; overscroll-behavior-y: auto; }
    /* Hide scrollbar for clean app look */
    ::-webkit-scrollbar { width: 0px; background: transparent; }
    
    @media all and (display-mode: standalone) {
        #pwa-install-setting, #pwa-install-banner { display: none !important; }
    }

    /* App Container for Desktop to look like mobile */
    .app-container {
        max-width: 480px;
        margin: 0 auto;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ffffff;
        box-shadow: 0 0 40px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    .page-content {
        flex: 1;
        overflow-y: auto;
        padding-bottom: 90px; /* space for bottom nav */
        -webkit-overflow-scrolling: touch;
    }

    .page { display: none; animation: fadeIn 0.3s ease; }
    .page.active { display: block; }
    /* Categories page uses absolute positioning, so active state should not alter its flex layout */
    #page-categories.active { display: block; }
    .edit-cat-modal-open { display: flex !important; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* Custom Gradients & Shapes */
    .blue-card {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
    }
    
    .pill-tab {
        transition: all 0.2s ease;
    }
    .pill-tab.active {
        background-color: #ffffff;
        color: #1e293b;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .bottom-nav-item {
        color: #94a3b8;
        transition: all 0.2s ease;
    }
    .bottom-nav-item.active {
        color: #2563eb;
    }
    .bottom-nav-item.active i {
        transform: translateY(-2px);
    }
    
    .quick-action-btn {
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        transition: transform 0.1s;
    }
    .quick-action-btn:active { transform: scale(0.95); }
    
    .icon-circle-blue { background: #eff6ff; color: #2563eb; }
    .icon-circle-orange { background: #fff7ed; color: #f97316; }
    .icon-circle-green { background: #f0fdf4; color: #10b981; }
    .icon-circle-purple { background: #faf5ff; color: #a855f7; }

    /* Floating Action Button (FAB) */
    .floating-action-button {
        position: absolute;
        bottom: 90px;
        right: 20px;
        width: 56px;
        height: 56px;
        background-color: #2563eb;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
        cursor: pointer;
        transition: transform 0.2s;
        z-index: 40;
    }
    .floating-action-button:active { transform: scale(0.9); }

    /* Voice Modal Override */
    .voice-overlay {
        position: fixed; top:0; left:0; right:0; bottom:0;
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(8px);
        display: none; justify-content: center; align-items: flex-end;
        z-index: 100;
        pointer-events: none;
    }
    .voice-overlay.active { display: flex; pointer-events: auto; opacity: 1; }
    .voice-modal {
        background: white; width: 100%; max-width: 480px;
        border-radius: 32px; padding: 32px 24px;
        margin-bottom: 110px; margin-inline: 16px;
        text-align: center; animation: slideUp 0.3s ease;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    #fab-mic { transition: all 0.2s ease; }
    #fab-mic.holding { 
        transform: scale(1.15) translateY(-5px); 
        background-color: #ef4444; 
        border-color: #fca5a5; 
        box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.5); 
    }
    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

  </style>
  <script>
    window.authUser = @json(auth()->user());
    window.authUserId = {{ auth()->id() }};
    window.baseUrl = "{{ url('/') }}";
    window.activeProject = @json($activeProject ?? null);
    window.allProjects = @json($allProjects ?? []);
    window.isCollaborative = {{ json_encode($isCollaborative ?? false) }};
  </script>
</head>
<body>

<div class="app-container">

    <!-- CONTENT AREA -->
    <main class="page-content" id="main-scroll">
        
        <!-- PAGE: HOME -->
        <div class="page active" id="page-home">
            <!-- Header -->
            <div class="px-6 pt-8 pb-4 flex justify-between items-center bg-white">
                <!-- Left: Project Switcher -->
                <div class="flex-1 min-w-0 pr-4">
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-0.5">Proyek Aktif</p>
                    @if(isset($activeProject) && $activeProject)
                    @php
                        $projColor = $activeProject->color ?? '#6c63ff';
                        $isFa = str_starts_with($activeProject->icon ?? '', 'fas') || str_contains($activeProject->icon ?? '', 'fa-');
                    @endphp
                    <button onclick="openProjectSwitcher(false)" class="flex items-center gap-2 w-full text-left group">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition" style="background:{{ $projColor }}15; color:{{ $projColor }};">
                            @if($isFa)
                                <i class="{{ $activeProject->icon }} text-sm"></i>
                            @else
                                <span class="text-sm leading-none">{{ $activeProject->icon }}</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex items-center gap-1.5">
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 truncate max-w-[120px] sm:max-w-[180px]">{{ $activeProject->name }}</h2>
                            <i class="fas fa-chevron-down text-[10px] text-slate-400 group-hover:text-slate-600 transition shrink-0"></i>
                        </div>
                    </button>
                    @else
                    <button onclick="openProjectSwitcher(false)" class="flex items-center gap-2 group">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-rose-50 text-rose-500 shrink-0">
                            <i class="fas fa-plus text-sm"></i>
                        </div>
                        <span class="text-base font-extrabold text-slate-900">Buat Proyek</span>
                    </button>
                    @endif
                </div>

                <!-- Right: Bell & Avatar -->
                <div class="flex items-center gap-3 shrink-0">
                    <button onclick="openNotificationsModal()" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:bg-slate-50 rounded-full relative transition border border-slate-100 bg-white shrink-0 shadow-sm">
                        <i class="fas fa-bell text-sm"></i>
                        <span id="notif-badge-static" class="absolute -top-1 -right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[10px] font-bold rounded-full border-2 border-white hidden">0</span>
                    </button>
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 shrink-0 cursor-pointer border-2 border-white shadow-sm" onclick="navigateTo('profile')">
                        <img src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=f1f5f9&color=475569' }}" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>

            <!-- PWA Install Banner -->
            <div id="pwa-install-banner" class="px-6 mb-2 hidden">
                <div class="bg-brand-50 border border-brand-100 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center text-brand-600 shrink-0">
                            <i class="fas fa-download"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm">Install Catat-in</h3>
                            <p class="text-[10px] text-slate-500">Akses lebih cepat & mudah dari layar utama</p>
                        </div>
                    </div>
                    <button onclick="installPWA()" class="bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-sm shadow-brand-500/30 transition shrink-0">
                        Install
                    </button>
                </div>
            </div>

            <!-- Balance Card -->
            <div class="px-6 py-2">
                <div class="blue-card rounded-[28px] p-6 text-white relative overflow-hidden">
                    <!-- Decor circles -->
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white opacity-10"></div>
                    <div class="absolute bottom-0 right-10 -mb-10 w-24 h-24 rounded-full bg-white opacity-10"></div>
                    
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <p class="text-blue-100 text-sm font-medium opacity-90 m-0">Total Saldo</p>
                                <span id="home-filter-badge" class="px-2 py-0.5 bg-white/20 rounded-full text-[9px] font-bold uppercase tracking-wider hidden text-white"></span>
                            </div>
                            <h1 class="text-4xl font-bold mt-1 mb-6" id="home-balance">Rp 0</h1>
                        </div>
                        <button onclick="toggleBalance()" class="bg-white/20 backdrop-blur-sm rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold hover:bg-white/30 transition">
                            <i class="fas fa-eye" id="toggle-eye-icon"></i>
                        </button>
                    </div>

                    <div class="flex gap-3 relative z-10">
                        <button onclick="document.getElementById('nav-add').click()" class="flex-1 bg-white text-brand-600 rounded-xl px-4 py-3 text-left hover:bg-slate-50 transition overflow-hidden">
                            <div class="flex items-center gap-1.5 opacity-80 mb-1">
                                <i class="fas fa-arrow-down text-[10px]"></i> <span class="text-[10px] font-bold uppercase tracking-wider">Pemasukan</span>
                            </div>
                            <div class="text-sm font-bold truncate balance-value" id="home-income">Rp 0</div>
                        </button>
                        <button onclick="document.getElementById('nav-add').click()" class="flex-1 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white border border-white/20 rounded-xl px-4 py-3 text-left transition overflow-hidden">
                            <div class="flex items-center gap-1.5 opacity-80 mb-1">
                                <i class="fas fa-arrow-up text-[10px]"></i> <span class="text-[10px] font-bold uppercase tracking-wider">Pengeluaran</span>
                            </div>
                            <div class="text-sm font-bold truncate balance-value" id="home-expense">Rp 0</div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- AI Insights -->
            <div class="px-6 mt-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-slate-900">Insight AI</h3>
                    <button class="text-brand-600 text-sm font-medium">Lihat semua</button>
                </div>
                
                <div class="bg-white border border-slate-100 shadow-card rounded-2xl p-4 mb-3 flex gap-4 items-start" id="insight-box-1">
                    <div class="w-10 h-10 rounded-full icon-circle-green flex items-center justify-center shrink-0">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Kerja bagus! Pengeluaran menurun</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Pengeluaran Anda menurun dibandingkan minggu lalu.</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="px-6 mt-6">
                <h3 class="font-bold text-slate-900 mb-4">Aksi Cepat</h3>
                <div class="grid grid-cols-4 gap-3">
                    <button class="quick-action-btn flex flex-col items-center justify-center py-4 rounded-2xl gap-2" onclick="openVoiceModal()">
                        <div class="w-12 h-12 rounded-full icon-circle-blue flex items-center justify-center text-xl">
                            <i class="fas fa-microphone"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-700">Suara</span>
                    </button>
                    <button class="quick-action-btn flex flex-col items-center justify-center py-4 rounded-2xl gap-2" onclick="document.getElementById('nav-wallet').click()">
                        <div class="w-12 h-12 rounded-full icon-circle-orange flex items-center justify-center text-xl">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-700">Riwayat</span>
                    </button>
                    <button class="quick-action-btn flex flex-col items-center justify-center py-4 rounded-2xl gap-2" onclick="document.getElementById('nav-analytics').click()">
                        <div class="w-12 h-12 rounded-full icon-circle-green flex items-center justify-center text-xl">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-700">Laporan</span>
                    </button>
                    <button class="quick-action-btn flex flex-col items-center justify-center py-4 rounded-2xl gap-2" onclick="document.getElementById('nav-profile').click()">
                        <div class="w-12 h-12 rounded-full icon-circle-purple flex items-center justify-center text-xl">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-700">Setelan</span>
                    </button>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="px-6 mt-8 mb-6">
                <h3 class="font-bold text-slate-900 mb-4">Transaksi Terbaru</h3>
                <div class="bg-white border border-slate-100 shadow-card rounded-2xl p-2" id="home-recent-list">
                    <!-- Populated by JS -->
                    <div class="p-6 text-center text-slate-500 text-sm">Memuat...</div>
                </div>
            </div>
        </div>

        <!-- PAGE: ANALYTICS -->
        <div class="page" id="page-analytics">
            <div class="px-6 pt-8 pb-4 flex justify-between items-center bg-white sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=f1f5f9&color=475569" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                    <h2 class="text-lg font-bold text-slate-900">Analitik</h2>
                </div>
                <button onclick="downloadReport()" class="w-10 h-10 rounded-full bg-white border border-slate-100 shadow-sm flex items-center justify-center text-slate-700">
                    <i class="fas fa-download"></i>
                </button>
            </div>

            <div class="px-6 mt-2">
                <!-- Time Filter -->
                <div class="bg-slate-100 rounded-full p-1 flex mb-6">
                    <button class="pill-tab flex-1 py-2 rounded-full text-sm font-semibold text-slate-500" data-period="week">Minggu</button>
                    <button class="pill-tab active flex-1 py-2 rounded-full text-sm font-semibold" data-period="month">Bulan</button>
                    <button class="pill-tab flex-1 py-2 rounded-full text-sm font-semibold text-slate-500" data-period="year">Tahun</button>
                    <button class="pill-tab flex-1 py-2 rounded-full text-sm font-semibold text-slate-500" data-period="all">Semua</button>
                </div>

                <!-- Date Navigator -->
                <div id="analytics-date-nav" class="flex justify-between items-center mb-4 bg-white border border-slate-100 rounded-xl px-3 py-2 shadow-sm">
                    <button onclick="changeAnalyticsOffset(-1)" class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition">
                        <i class="fas fa-chevron-left text-[10px]"></i>
                    </button>
                    <span id="analytics-date-label" class="text-xs font-bold text-slate-700 tracking-wide"></span>
                    <button onclick="changeAnalyticsOffset(1)" class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition" id="btn-next-date">
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>
                </div>

                <!-- Chart -->
                <div class="bg-white border border-slate-100 shadow-card rounded-3xl p-5 mb-6">
                    <!-- Chart Toggle -->
                    <div class="bg-slate-50 border border-slate-100 rounded-full p-1 flex mb-4">
                        <button class="chart-tab active flex-1 py-1.5 rounded-full text-xs font-bold text-slate-800 bg-white shadow-sm" data-chart="bar" onclick="setChartView('bar')">Arus Kas</button>
                        <button class="chart-tab flex-1 py-1.5 rounded-full text-xs font-bold text-slate-400 hover:text-slate-600 transition" data-chart="donut" onclick="setChartView('donut')">Kategori</button>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-slate-900" id="chart-title">Arus Kas</h3>
                        <div id="bar-legend" class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <span class="w-2 h-2 rounded-full bg-brand-600"></span> Pengeluaran
                            <span class="w-2 h-2 rounded-full bg-slate-200 ml-1"></span> Pemasukan
                        </div>
                        <div id="donut-legend" class="hidden flex items-center gap-2 text-xs font-medium text-slate-500">
                            <span class="px-2 py-0.5 bg-brand-50 text-brand-600 rounded-full text-[9px] font-bold uppercase tracking-wider">Pengeluaran</span>
                        </div>
                    </div>
                    <div class="h-56 relative flex items-center justify-center">
                        <canvas id="main-chart"></canvas>
                        <!-- Center text for donut chart -->
                        <div id="donut-center-text" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none hidden">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">TOTAL</span>
                            <span class="text-sm font-bold text-slate-800" id="donut-total">Rp 0</span>
                        </div>
                    </div>
                </div>

                <!-- Income & Expense Cards -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-white border border-slate-100 shadow-card rounded-2xl p-4">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Pemasukan</p>
                        <p class="text-lg font-bold text-emerald-500" id="analytics-income">Rp 0</p>
                    </div>
                    <div class="bg-white border border-slate-100 shadow-card rounded-2xl p-4">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Pengeluaran</p>
                        <p class="text-lg font-bold text-brand-600" id="analytics-spent">Rp 0</p>
                    </div>
                </div>

                <!-- Breakdown -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-slate-900 text-sm" id="breakdown-title">Rincian</h3>
                        <div class="flex bg-slate-100 rounded-lg p-1">
                            <button class="px-3 py-1 text-xs font-bold rounded-md bg-white shadow-sm text-slate-900" id="btn-breakdown-out" onclick="setBreakdownTab('pengeluaran')">Pengeluaran</button>
                            <button class="px-3 py-1 text-xs font-bold rounded-md text-slate-500" id="btn-breakdown-in" onclick="setBreakdownTab('pemasukan')">Pemasukan</button>
                        </div>
                    </div>
                    <div class="bg-white border border-slate-100 shadow-card rounded-3xl p-5 space-y-5" id="analytics-breakdown">
                        <p class="text-sm text-slate-500 text-center">Tidak ada data</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- PAGE: WALLET / HISTORY -->
        <div class="page" id="page-wallet">
            <div class="px-6 pt-8 pb-4 flex justify-between items-center bg-white sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=f1f5f9&color=475569" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Riwayat Transaksi</p>
                        <h2 class="text-sm font-bold text-slate-900">{{ auth()->user()->name }}</h2>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-700">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <div class="px-6 mt-2">


                <!-- Transaction List -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-slate-900 text-lg">Transaksi</h3>
                </div>

                <!-- Filters built-in -->
                <div class="flex gap-2 mb-4 items-center">
                    <select id="wallet-filter-type" class="shrink-0 bg-slate-100 text-slate-700 text-xs font-semibold py-2 px-3 rounded-full outline-none border-r-8 border-transparent focus:border-brand-600 transition" onchange="updateWalletFilterCat(); updateWallet()">
                        <option value="all">Semua Tipe</option>
                        <option value="pemasukan">Pemasukan</option>
                        <option value="pengeluaran">Pengeluaran</option>
                    </select>
                    <select id="wallet-filter-cat" class="shrink-0 bg-slate-100 text-slate-700 text-xs font-semibold py-2 px-3 rounded-full outline-none border-r-8 border-transparent focus:border-brand-600 transition" onchange="updateWallet()">
                        <option value="all">Semua Kategori</option>
                    </select>

                    <!-- Sort Icon Button -->
                    <div class="relative ml-auto shrink-0">
                        <button id="sort-btn" onclick="toggleSortMenu()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-brand-50 hover:text-brand-600 transition">
                            <i class="fas fa-sort-amount-down text-xs"></i>
                        </button>
                        <!-- Sort Popup -->
                        <div id="sort-menu" class="hidden absolute right-0 top-10 bg-white border border-slate-100 rounded-2xl shadow-lg z-30 overflow-hidden w-36">
                            <div class="py-1">
                                <button onclick="setSortOption('newest')" class="sort-opt w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-clock w-3 text-slate-400"></i> Terbaru</button>
                                <button onclick="setSortOption('oldest')" class="sort-opt w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-history w-3 text-slate-400"></i> Terlama</button>
                                <div class="border-t border-slate-50 my-1"></div>
                                <button onclick="setSortOption('highest')" class="sort-opt w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-arrow-up w-3 text-slate-400"></i> Terbesar</button>
                                <button onclick="setSortOption('lowest')" class="sort-opt w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-arrow-down w-3 text-slate-400"></i> Terkecil</button>
                                <div class="border-t border-slate-50 my-1"></div>
                                <button onclick="setSortOption('az')" class="sort-opt w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-sort-alpha-down w-3 text-slate-400"></i> A - Z</button>
                                <button onclick="setSortOption('za')" class="sort-opt w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"><i class="fas fa-sort-alpha-up w-3 text-slate-400"></i> Z - A</button>
                            </div>
                        </div>
                    </div>
                    <!-- Hidden sort value -->
                    <input type="hidden" id="wallet-sort" value="newest">
                </div>

                <div class="bg-white border border-slate-100 shadow-card rounded-2xl p-2 mb-20" id="wallet-txn-list">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <!-- PAGE: PROFILE / SETTINGS -->
        <div class="page" id="page-profile">
            <div class="px-6 pt-8 pb-4 bg-white sticky top-0 z-20 border-b border-slate-100">
                <h1 class="text-xl font-bold text-slate-900 text-center">Pengaturan</h1>
            </div>

            <div class="px-6 mt-6">
                <!-- Profile Header -->
                <div class="flex flex-col items-center mb-8">
                    <div class="w-24 h-24 rounded-full overflow-hidden bg-slate-200 border-4 border-white shadow-md mb-4">
                        <img id="profile-avatar-img" src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=f1f5f9&color=475569' }}" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                    <h2 id="profile-name-display" class="text-xl font-bold text-slate-900">{{ auth()->user()->name }}</h2>
                    <p class="text-sm text-slate-500">{{ auth()->user()->email }}</p>
                    <span class="mt-2 px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold uppercase tracking-wider">{{ auth()->user()->role }}</span>
                </div>

                <!-- Settings Blocks -->
                <div class="bg-white border border-slate-100 shadow-sm rounded-3xl overflow-hidden mb-6">


                    <div class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="openEditProfileModal()">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <span class="font-semibold text-slate-700">Edit Profil</span>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </div>
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="openProjectSwitcher(true)">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Kelola Proyek</span>
                                <p class="text-xs text-slate-400" id="profile-project-count">{{ count($allProjects ?? []) }} proyek aktif</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </div>
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="openMembersModal()">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Kelola Anggota</span>
                                <p class="text-xs text-slate-400">Undang atau kelola kolaborator</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </div>
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="navigateTo('categories')">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center">
                                <i class="fas fa-tags"></i>
                            </div>
                            <span class="font-semibold text-slate-700">Kelola Kategori</span>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </div>

                    <!-- Dashboard Time Filter Setting -->
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="openDashboardFilterModal()">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center shrink-0">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Filter Kartu Utama</span>
                                <p class="text-xs text-slate-400" id="dashboard-filter-label">Semua Waktu</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </div>

                    <!-- Push Notification Enable Button -->
                    <div id="push-notification-setting" class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="enablePushNotifications()">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Notifikasi HP</span>
                                <p class="text-xs text-slate-400" id="push-status-text">Ketuk untuk mengaktifkan</p>
                            </div>
                        </div>
                        <i class="fas fa-toggle-off text-slate-300 text-lg" id="push-toggle-icon"></i>
                    </div>

                    <!-- PWA Install Button -->
                    <div id="pwa-install-setting" class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="installPWA()">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Install Aplikasi</span>
                                <p class="text-xs text-slate-400">Tambahkan ke layar utama HP</p>
                            </div>
                        </div>
                        <i class="fas fa-download text-brand-500 text-sm"></i>
                    </div>

                    <div class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="openActivityLogModal()">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-history"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Log Aktivitas</span>
                                <p class="text-xs text-slate-400">Log aksi, masuk, & unduh laporan</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </div>

                    <!-- Backup & Restore Data -->
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="openBackupRestoreModal()">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center">
                                <i class="fas fa-cloud-download-alt"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Backup & Restore Data</span>
                                <p class="text-xs text-slate-400">Amankan & pulihkan data (JSON)</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </div>
                    <input type="file" id="import-file" class="hidden" accept=".json" onchange="importData(this)">

                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="p-4 border-b border-slate-100 flex items-center justify-between hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-crown"></i>
                            </div>
                            <span class="font-semibold text-slate-700">Dashboard Admin</span>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="p-4 flex items-center justify-between cursor-pointer hover:bg-rose-50 transition" onclick="this.submit()">
                        @csrf
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <span class="font-semibold text-rose-600">Keluar</span>
                        </div>
                        <i class="fas fa-chevron-right text-rose-300 text-sm"></i>
                    </form>
                </div>
            </div>
        </div>

        <!-- PAGE: ADD (HIDDEN BY DEFAULT, ACCESSED VIA MODAL OR OVERLAY IN SPA) -->
        <div class="page" id="page-add">
            <div class="px-6 pt-8 pb-4 flex items-center bg-white sticky top-0 z-20 border-b border-slate-100">
                <button onclick="document.getElementById('nav-home').click()" class="w-10 h-10 flex items-center justify-center text-slate-700 mr-2">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1 class="text-xl font-bold text-slate-900">Tambah Transaksi</h1>
            </div>

            <div class="px-4 mt-4">
                <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-4">
                    <form id="txn-form" autocomplete="off">
                        <!-- Type -->
                        <div class="flex bg-slate-100 rounded-xl p-1 mb-4">
                            <button type="button" class="flex-1 py-1.5 rounded-lg text-sm font-semibold active bg-white shadow-sm text-slate-900" id="btn-type-out" onclick="setTxnType('pengeluaran')">Pengeluaran</button>
                            <button type="button" class="flex-1 py-1.5 rounded-lg text-sm font-semibold text-slate-500" id="btn-type-in" onclick="setTxnType('pemasukan')">Pemasukan</button>
                        </div>
                        <input type="hidden" id="txn-type" value="pengeluaran">

                        <div class="mb-3">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nominal (Rp)</label>
                            <input type="text" inputmode="numeric" id="txn-amount" required class="w-full text-2xl font-bold text-slate-900 border-0 border-b-2 border-slate-200 focus:border-brand-600 focus:ring-0 px-0 py-1 bg-transparent outline-none transition-colors" placeholder="0" oninput="formatCurrencyInput(this)">
                        </div>

                        <div class="mb-3">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Kategori</label>
                            <select id="txn-category" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 font-medium focus:border-brand-600 focus:ring-1 focus:ring-brand-600 outline-none appearance-none">
                                <option value="">Pilih Kategori</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tanggal</label>
                            <input type="date" id="txn-date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 font-medium focus:border-brand-600 outline-none">
                        </div>

                        <div class="mb-5">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Catatan (Opsional)</label>
                            <input type="text" id="txn-desc" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 font-medium focus:border-brand-600 outline-none" placeholder="Untuk keperluan apa?">
                        </div>

                        <button type="submit" class="w-full bg-brand-600 text-white rounded-xl py-3 font-bold text-base hover:bg-brand-700 transition shadow-lg shadow-brand-500/30">
                            Simpan Transaksi
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <!-- PAGE: CATEGORIES (sits over everything, hidden by default via .page class) -->
    <div class="page absolute top-0 left-0 w-full h-full bg-[#f8fafc] z-[60] overflow-hidden" id="page-categories">
      <div class="flex flex-col w-full h-full">
        <!-- Header -->
        <div class="px-4 pt-8 pb-4 bg-white flex items-center gap-4 border-b border-slate-100 shrink-0">
            <button onclick="navigateTo('profile')" class="w-10 h-10 flex items-center justify-center text-slate-700 hover:bg-slate-50 rounded-full transition">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h1 class="text-xl font-bold text-slate-900">Kelola Kategori</h1>
        </div>

        <div class="flex-1 overflow-y-auto pb-24">
            <div class="px-4 mt-4">
                <!-- Tab -->
                <input type="hidden" id="add-cat-type" value="pengeluaran">
                <div class="flex bg-slate-200 rounded-xl p-1 mb-4">
                    <button class="flex-1 py-1.5 rounded-lg text-sm font-semibold bg-white shadow-sm text-slate-900" id="cat-tab-out" onclick="setCategoryTab('pengeluaran')">Pengeluaran</button>
                    <button class="flex-1 py-1.5 rounded-lg text-sm font-semibold text-slate-500" id="cat-tab-in" onclick="setCategoryTab('pemasukan')">Pemasukan</button>
                </div>

                <!-- List -->
                <div class="bg-white border border-slate-100 shadow-sm rounded-2xl mb-4" id="category-list-container">
                    <!-- Populated via JS -->
                </div>
            </div>
        </div>

        <!-- FAB Button -->
        <button onclick="openAddCatModal()" class="absolute bottom-6 right-6 w-14 h-14 rounded-full shadow-xl flex items-center justify-center text-white text-2xl z-10 transition active:scale-95" style="background:#6c63ff">
            <i class="fas fa-plus"></i>
        </button>
      </div>
    </div>

    <!-- EDIT CATEGORY MODAL -->
    <div id="edit-cat-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none !important">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditCatModal()"></div>
        <div class="relative bg-white w-full max-w-sm rounded-2xl p-5">
            <h3 class="font-bold text-slate-900 mb-4 text-lg">Edit Kategori</h3>
            <input type="hidden" id="edit-cat-id">
            <input type="hidden" id="edit-cat-type-val">
            <div class="mb-4">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Kategori</label>
                <input type="text" id="edit-cat-name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:border-brand-600 outline-none" placeholder="Nama Kategori">
            </div>
            <div class="mb-5">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Kata Kunci Suara <span class="text-slate-400 font-normal normal-case">(Opsional)</span>
                </label>
                <input type="text" id="edit-cat-keywords"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:border-brand-600 outline-none"
                    placeholder="kado, surprise, gift (pisahkan koma)">
                <p class="text-[10px] text-slate-400 mt-1">Dikenali otomatis saat input suara</p>
            </div>
            <div class="flex gap-3">
                <button onclick="confirmDeleteCategory()" class="flex-1 bg-rose-50 text-rose-500 border border-rose-200 rounded-xl py-3 font-bold text-sm transition hover:bg-rose-100">
                    <i class="fas fa-trash mr-2"></i>Hapus
                </button>
                <button onclick="confirmEditCategory()" class="flex-1 bg-brand-600 text-white rounded-xl py-3 font-bold text-sm transition hover:bg-brand-700">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- ADD CATEGORY MODAL (new rich modal) -->
    <div id="add-cat-modal" class="fixed inset-0 z-[70] flex items-center justify-center p-4" style="display:none !important">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAddCatModal()"></div>
        <div class="relative bg-white w-full max-w-sm rounded-2xl flex flex-col max-h-[92vh]">
            <div class="px-5 py-3 border-b border-slate-100 shrink-0">
                <h3 class="font-bold text-slate-900 text-base">Kategori Baru</h3>
            </div>

            <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">
                <!-- Type Toggle -->
                <div class="flex bg-slate-100 rounded-xl p-1">
                    <button id="add-cat-btn-out" onclick="setAddCatType('pengeluaran')" class="flex-1 py-2 rounded-lg text-sm font-semibold text-slate-500 transition">Pengeluaran</button>
                    <button id="add-cat-btn-in" onclick="setAddCatType('pemasukan')" class="flex-1 py-2 rounded-lg text-sm font-semibold text-white transition" style="background:#6c63ff">Pemasukan</button>
                </div>

                <!-- Preview -->
                <div id="add-cat-preview" class="flex items-center gap-3 rounded-2xl px-4 py-3" style="background:#fef9c3">
                    <div id="add-cat-preview-icon" class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background:#fde68a;color:#b45309">
                        <i class="fas fa-star"></i>
                    </div>
                    <span id="add-cat-preview-name" class="font-bold text-slate-700 text-base">Nama Kategori</span>
                </div>

                <!-- Name Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Kategori</label>
                    <input type="text" id="add-cat-name-input" maxlength="30" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 focus:border-[#6c63ff] outline-none" placeholder="Nama kategori..." oninput="updateAddCatPreview()">
                    <p class="text-[10px] text-slate-400 mt-1" id="add-cat-charcount">0/30</p>
                </div>

                <!-- Color Picker -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Warna</label>
                    <div class="flex gap-2 flex-wrap">
                        @foreach(['#6c63ff','#3b82f6','#06b6d4','#10b981','#84cc16','#f59e0b','#ef4444','#ec4899','#8b5cf6','#f97316'] as $color)
                        <button onclick="selectAddCatColor('{{ $color }}')" class="add-cat-color-btn w-9 h-9 rounded-full transition border-4 border-transparent hover:scale-110" style="background:{{ $color }}" data-color="{{ $color }}"></button>
                        @endforeach
                    </div>
                </div>

                <!-- Icon Pack -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ikon</label>
                    <div class="grid grid-cols-5 gap-2" id="add-cat-icon-grid">
                        @php
                        $icons = [
                            'fa-store','fa-credit-card','fa-piggy-bank','fa-landmark','fa-chart-line',
                            'fa-shopping-basket','fa-bicycle','fa-utensils','fa-cash-register','fa-calculator',
                            'fa-box','fa-dollar-sign','fa-ellipsis-h','fa-layer-group','fa-tag',
                            'fa-star','fa-heart','fa-flag','fa-bookmark','fa-briefcase',
                            'fa-car','fa-home','fa-plane','fa-graduation-cap','fa-medkit',
                            'fa-bolt','fa-wifi','fa-gamepad','fa-music','fa-gift',
                            'fa-wallet','fa-coins','fa-receipt','fa-percent','fa-mobile-alt'
                        ];
                        @endphp
                        @foreach($icons as $icon)
                        <button onclick="selectAddCatIcon('fa-{{ str_replace('fa-','',$icon) }}')" class="add-cat-icon-btn w-full aspect-square rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition text-sm" data-icon="fas {{ $icon }}">
                            <i class="fas {{ $icon }}"></i>
                        </button>
                        @endforeach
                    </div>
                </div>

                <input type="hidden" id="add-cat-selected-icon" value="fas fa-star">
                <input type="hidden" id="add-cat-selected-color" value="#6c63ff">
                <input type="hidden" id="add-cat-selected-type" value="pemasukan">

                <!-- Keywords for Voice Recognition -->
                <div class="mt-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                        Kata Kunci Suara <span class="text-slate-400 font-normal normal-case">(Opsional)</span>
                    </label>
                    <input type="text" id="add-cat-keywords-input"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#6c63ff] transition"
                        placeholder="kado, surprise, gift (pisahkan dengan koma)">
                    <p class="text-[10px] text-slate-400 mt-1">Kata ini dikenali saat input suara. Misal kategori "Hadiah" → keyword: kado, surprise</p>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-5 py-4 border-t border-slate-100 flex gap-3 shrink-0">
                <button onclick="closeAddCatModal()" class="flex-1 bg-slate-100 text-slate-600 rounded-2xl py-3 font-bold text-sm">Batal</button>
                <button onclick="submitAddCatModal()" class="flex-1 text-white rounded-2xl py-3 font-bold text-sm" style="background:#6c63ff">Simpan</button>
            </div>
        </div>
    </div>

    <!-- BOTTOM NAVIGATION -->
    <nav class="absolute bottom-0 w-full bg-white shadow-[0_-4px_25px_rgba(0,0,0,0.05)] h-[72px] px-2 flex justify-around items-center z-30 pb-safe rounded-t-3xl">
        <a href="#" class="bottom-nav-item active flex flex-col items-center gap-1 w-16" data-page="home" id="nav-home">
            <i class="fas fa-home text-xl"></i>
            <span class="text-[10px] font-bold">Home</span>
        </a>
        <a href="#" class="bottom-nav-item flex flex-col items-center gap-1 w-16" data-page="analytics" id="nav-analytics">
            <i class="fas fa-chart-pie text-xl"></i>
            <span class="text-[10px] font-bold">Analitik</span>
        </a>
        
        <!-- CENTER MIC BUTTON PLACEHOLDER -->
        <div class="w-16"></div>

        <a href="#" class="bottom-nav-item flex flex-col items-center gap-1 w-16" data-page="wallet" id="nav-wallet">
            <i class="fas fa-wallet text-xl"></i>
            <span class="text-[10px] font-bold">Riwayat</span>
        </a>
        <a href="#" class="bottom-nav-item flex flex-col items-center gap-1 w-16" data-page="profile" id="nav-profile">
            <i class="fas fa-cog text-xl mb-1 nav-icon transition duration-300"></i>
            <span class="text-[10px] font-bold">Pengaturan</span>
        </a>
        <a href="#" class="hidden" data-page="add" id="nav-add"></a>
    </nav>

    <!-- FLOATING MIC BUTTON -->
    <div id="mic-wrapper" class="absolute bottom-0 w-full h-[72px] px-2 flex justify-around items-center pointer-events-none z-40 pb-safe">
        <div class="w-16"></div>
        <div class="w-16"></div>
        <div class="relative w-16 flex justify-center">
            <button class="absolute -top-10 w-16 h-16 rounded-full bg-brand-600 text-white flex items-center justify-center text-2xl shadow-lg border-4 border-white transition-transform active:scale-90 pointer-events-auto" id="fab-mic">
                <i class="fas fa-microphone"></i>
            </button>
        </div>
        <div class="w-16"></div>
        <div class="w-16"></div>
    </div>

</div>

<!-- VOICE OVERLAY MODAL -->
<div class="voice-overlay" id="voice-overlay">
    <div class="voice-modal">
        <div class="w-12 h-1 bg-slate-200 rounded-full mx-auto mb-6"></div>
        <div class="w-20 h-20 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-3xl mx-auto mb-6 relative animate-pulse" id="mic-pulse">
            <i class="fas fa-microphone"></i>
        </div>
        <h3 class="text-xl font-bold text-slate-900 mb-2">Mendengarkan...</h3>
        <p class="text-slate-500 text-sm mb-6" id="voice-modal-transcript">Coba sebutkan "Bayar listrik 150 ribu"</p>
        
        <div class="mt-4 text-center flex flex-col items-center gap-2 opacity-75">
            <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 mb-1">
                <i class="fas fa-hand-pointer animate-bounce"></i>
            </div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Lepaskan jari untuk memproses</p>
        </div>
    </div>
</div>

<!-- TOAST -->
<div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-6 py-3 rounded-full text-sm font-medium shadow-lg z-[9999] transition-all duration-300 transform -translate-y-20 opacity-0">
    Message
</div>

<!-- EDIT TXN MODAL -->
<div id="edit-txn-modal" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none !important">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditTxnModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">
        <div class="px-6 py-3 flex justify-between items-center border-b border-slate-100">
            <h3 class="font-bold text-slate-800 text-base">Edit Transaksi</h3>
            <button onclick="closeEditTxnModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form id="edit-txn-form" onsubmit="submitEditTxn(event)">
                <input type="hidden" id="edit-txn-id">
                <input type="hidden" id="edit-txn-type">
                
                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nominal</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-slate-500 font-bold">Rp</span>
                        </div>
                        <input type="text" id="edit-txn-amount" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-lg font-bold rounded-2xl focus:ring-brand-500 focus:border-brand-500 block pl-12 p-3.5 outline-none transition" required oninput="formatCurrencyInput(this)">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Kategori</label>
                    <select id="edit-txn-category" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-2xl focus:ring-brand-500 focus:border-brand-500 block p-3.5 outline-none transition appearance-none" required>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tanggal</label>
                    <input type="date" id="edit-txn-date" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-2xl focus:ring-brand-500 focus:border-brand-500 block p-3.5 outline-none transition" required>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" id="edit-txn-desc" class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-2xl focus:ring-brand-500 focus:border-brand-500 block p-3.5 outline-none transition" placeholder="Contoh: Makan siang bareng teman">
                </div>

                <button type="submit" class="w-full text-white bg-brand-600 hover:bg-brand-700 font-bold rounded-2xl text-sm px-5 py-4 text-center transition shadow-lg shadow-brand-500/30">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ONBOARDING MODAL: shown when user has no project -->
@if(empty($allProjects) || count($allProjects) === 0)
<div id="onboarding-modal" class="fixed inset-0 z-[80] bg-slate-900/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl p-6 text-center">
        <div class="w-16 h-16 bg-[#6c63ff]/10 text-[#6c63ff] rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4">
            <i class="fas fa-wallet"></i>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 mb-2">Selamat datang!</h2>
        <p class="text-slate-500 text-sm mb-6">Buat proyek pertama Anda untuk mulai mencatat keuangan. Anda bisa membuat banyak proyek nanti (misal: Keluarga, Usaha, dll).</p>
        <input type="text" id="onboarding-project-name" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-2xl p-4 outline-none mb-4 text-center text-lg focus:border-[#6c63ff]" placeholder="Nama proyek (misal: Keuangan Keluarga)" value="Keuangan Pribadi">
        <div class="flex gap-2 justify-center mb-5 flex-wrap" id="onboarding-icon-picker">
            @foreach(['fa-wallet','fa-home','fa-store','fa-briefcase','fa-plane','fa-graduation-cap','fa-heart','fa-star'] as $icon)
            <button onclick="selectOnboardingIcon('fas {{ $icon }}')" class="w-10 h-10 rounded-xl flex items-center justify-center border-2 border-transparent hover:border-[#6c63ff] transition onboarding-icon-btn bg-slate-50 text-slate-500 {{ $icon === 'fa-wallet' ? 'border-[#6c63ff] bg-[#6c63ff]/10 text-[#6c63ff]' : '' }}" data-icon="fas {{ $icon }}">
                <i class="fas {{ $icon }} text-base"></i>
            </button>
            @endforeach
        </div>
        <input type="hidden" id="onboarding-project-icon" value="fas fa-wallet">
        <button onclick="submitOnboarding()" id="btn-onboarding-submit" class="w-full bg-[#6c63ff] hover:bg-[#5b52e0] text-white font-bold rounded-2xl py-4 text-base transition shadow-lg shadow-[#6c63ff]/30 flex items-center justify-center gap-2">
            <i class="fas fa-rocket"></i> Mulai Sekarang
        </button>
    </div>
</div>
@endif

<!-- PROJECT SWITCHER / MANAGER MODAL -->
<div id="project-switcher-modal" class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="display:none !important">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeProjectSwitcher()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl flex flex-col max-h-[80vh]">
        <div class="px-5 py-3.5 flex justify-between items-center border-b border-slate-100">
            <h3 class="font-bold text-slate-800 text-base" id="project-modal-title">Pilih Proyek</h3>
            <button onclick="closeProjectSwitcher()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <div class="p-4 overflow-y-auto flex-1" id="project-list-container"></div>
        <div class="px-4 pb-4" id="project-modal-footer" style="display:none">
            <button onclick="closeProjectSwitcher(); openProjectModal()" class="w-full flex items-center justify-center gap-2 bg-slate-50 border border-dashed border-slate-300 text-slate-600 rounded-2xl py-3 font-semibold text-sm hover:bg-slate-100 transition">
                <i class="fas fa-plus text-xs"></i> Buat Proyek Baru
            </button>
        </div>
    </div>
</div>
<!-- ADD PROJECT MODAL -->
<div id="add-project-modal" class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="display:none !important">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeProjectModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl flex flex-col max-h-[92vh]">
        <div class="px-5 py-3.5 border-b border-slate-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-slate-900 text-base" id="modal-project-title">Proyek Baru</h3>
            <button onclick="closeProjectModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">
            <div id="add-proj-preview" class="flex items-center gap-3 rounded-2xl px-4 py-3" style="background:#6c63ff22">
                <div id="add-proj-preview-icon" class="w-10 h-10 rounded-xl flex items-center justify-center text-lg" style="background:#6c63ff44;color:#6c63ff">
                    <i class="fas fa-wallet"></i>
                </div>
                <span id="add-proj-preview-name" class="font-bold text-slate-700 text-base">Nama Proyek</span>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Proyek</label>
                <input type="text" id="add-proj-name-input" maxlength="40"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-700 outline-none"
                    placeholder="Nama proyek..." oninput="updateAddProjPreview()">
                <p class="text-[10px] text-slate-400 mt-1" id="add-proj-charcount">0/40</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Warna</label>
                <div class="flex gap-2 flex-wrap" id="add-proj-color-row">
                    <button onclick="selectAddProjColor('#6c63ff')" class="add-proj-color-btn w-9 h-9 rounded-full hover:scale-110 transition border-4 border-white outline outline-[3px] outline-[#6c63ff]" style="background:#6c63ff" data-color="#6c63ff"></button>
                    <button onclick="selectAddProjColor('#3b82f6')" class="add-proj-color-btn w-9 h-9 rounded-full hover:scale-110 transition border-4 border-transparent" style="background:#3b82f6" data-color="#3b82f6"></button>
                    <button onclick="selectAddProjColor('#06b6d4')" class="add-proj-color-btn w-9 h-9 rounded-full hover:scale-110 transition border-4 border-transparent" style="background:#06b6d4" data-color="#06b6d4"></button>
                    <button onclick="selectAddProjColor('#10b981')" class="add-proj-color-btn w-9 h-9 rounded-full hover:scale-110 transition border-4 border-transparent" style="background:#10b981" data-color="#10b981"></button>
                    <button onclick="selectAddProjColor('#f59e0b')" class="add-proj-color-btn w-9 h-9 rounded-full hover:scale-110 transition border-4 border-transparent" style="background:#f59e0b" data-color="#f59e0b"></button>
                    <button onclick="selectAddProjColor('#ef4444')" class="add-proj-color-btn w-9 h-9 rounded-full hover:scale-110 transition border-4 border-transparent" style="background:#ef4444" data-color="#ef4444"></button>
                    <button onclick="selectAddProjColor('#ec4899')" class="add-proj-color-btn w-9 h-9 rounded-full hover:scale-110 transition border-4 border-transparent" style="background:#ec4899" data-color="#ec4899"></button>
                    <button onclick="selectAddProjColor('#f97316')" class="add-proj-color-btn w-9 h-9 rounded-full hover:scale-110 transition border-4 border-transparent" style="background:#f97316" data-color="#f97316"></button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ikon</label>
                <div class="grid grid-cols-6 sm:grid-cols-8 gap-2">
                    @foreach(['fa-wallet','fa-home','fa-store','fa-briefcase','fa-plane','fa-graduation-cap','fa-heart','fa-leaf','fa-car','fa-utensils','fa-piggy-bank','fa-chart-line','fa-gift','fa-star','fa-gamepad','fa-music','fa-medkit','fa-bolt','fa-landmark','fa-coins','fa-credit-card','fa-bicycle','fa-shopping-basket','fa-tag','fa-flag', 'fa-book', 'fa-laptop', 'fa-tshirt', 'fa-paw', 'fa-tools', 'fa-box', 'fa-coffee'] as $projIcon)
                    <button onclick="selectAddProjIcon('{{ $projIcon }}')" class="add-proj-icon-btn w-full aspect-square rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 hover:border-slate-300 transition text-lg" data-icon="fas {{ $projIcon }}">
                        <i class="fas {{ $projIcon }}"></i>
                    </button>
                    @endforeach
                </div>
            </div>
            <input type="hidden" id="add-proj-selected-icon" value="fas fa-wallet">
            <input type="hidden" id="add-proj-selected-color" value="#6c63ff">
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex gap-3 shrink-0">
            <button onclick="closeProjectModal()" class="flex-1 bg-slate-100 text-slate-600 rounded-2xl py-3 font-bold text-sm">Batal</button>
            <button onclick="submitProjectModal()" id="btn-submit-project" class="flex-1 text-white rounded-2xl py-3 font-bold text-sm" style="background:#6c63ff">Buat Proyek</button>
        </div>
    </div>
</div>

<!-- COLLABORATION: MEMBERS MODAL -->
<div id="members-modal" class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="display:none !important">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeMembersModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl flex flex-col max-h-[85vh]">
        <div class="px-5 py-3.5 border-b border-slate-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-slate-900 text-base">Anggota Proyek</h3>
            <button onclick="closeMembersModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Tabs -->
        <div class="px-5 pt-3 pb-0 flex gap-1 border-b border-slate-100 shrink-0" id="members-tabs">
            <button onclick="switchMembersTab('members')" id="tab-members"
                class="px-4 py-2 text-xs font-bold rounded-t-xl border-b-2 border-brand-600 text-brand-600 transition">
                <i class="fas fa-users mr-1"></i>Anggota
            </button>
            <button onclick="switchMembersTab('invites')" id="tab-invites"
                class="px-4 py-2 text-xs font-bold rounded-t-xl border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition hidden" id="tab-invites">
                <i class="fas fa-envelope mr-1"></i>Undangan <span id="invite-count-badge" class="ml-1 bg-amber-100 text-amber-700 text-[9px] font-bold px-1.5 py-0.5 rounded-full hidden"></span>
            </button>
        </div>

        <!-- Tab: Anggota -->
        <div id="tab-panel-members" class="overflow-y-auto flex-1 p-4" id="members-list-container">
            <div class="text-center text-slate-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat...</div>
        </div>

        <!-- Tab: Undangan -->
        <div id="tab-panel-invites" class="overflow-y-auto flex-1 p-4 hidden">
            <div id="invites-list-container">
                <div class="text-center text-slate-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat...</div>
            </div>
        </div>

        <!-- Invite via Email & Link Section (Shown dynamically for Owner) -->
        <div class="px-4 pb-4 space-y-3 shrink-0 hidden border-t border-slate-100 pt-3" id="invite-box-section">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Undang via Email</label>
                <div class="flex gap-2">
                    <input type="email" id="invite-email-input" placeholder="Masukkan email..." class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 outline-none focus:border-[#6c63ff] transition">
                    <button onclick="inviteByEmailAction()" class="bg-[#6c63ff] text-white rounded-xl px-4 py-2 font-bold text-xs hover:bg-[#5b52e5] transition shrink-0">Undang</button>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Metode Lain</label>
                <div class="flex gap-2">
                    <button id="invite-wa-btn" onclick="generateInviteLink(true)" class="flex-1 flex items-center justify-center gap-1.5 text-white rounded-xl py-2 font-bold text-xs transition" style="background:#25D366"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                    <button onclick="generateInviteLink(false)" class="flex-1 flex items-center justify-center gap-1.5 bg-slate-100 text-slate-700 rounded-xl py-2 font-bold text-xs hover:bg-slate-200 transition"><i class="fas fa-link"></i> Buat Link</button>
                </div>
                <div id="copy-link-wrapper" class="hidden mt-1 bg-slate-50 rounded-xl p-2.5 border border-slate-100 flex items-center gap-2">
                    <input type="text" id="invite-link-display" readonly class="bg-transparent border-0 text-[10px] text-slate-500 w-full outline-none focus:ring-0 p-0 font-mono" onclick="this.select()">
                    <button onclick="copyInviteLinkAction()" class="text-xs text-[#6c63ff] font-bold shrink-0 hover:underline">Salin</button>
                </div>
            </div>
        </div>
        <div class="px-4 pb-4 space-y-2 shrink-0" id="members-footer">
            <!-- Populated by JS: leave button (member only) -->
        </div>
    </div>
</div>

<!-- COLLABORATION: ACTIVITY LOG & UNDO MODAL -->
<div id="activity-log-modal" class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="display:none !important">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeActivityLogModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl flex flex-col max-h-[85vh]">
        <div class="px-5 py-3.5 border-b border-slate-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-slate-900 text-base">Log Aktivitas</h3>
            <button onclick="closeActivityLogModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <!-- Filter Pills -->
        <div class="px-5 py-2.5 border-b border-slate-100 flex gap-2 overflow-x-auto no-scrollbar shrink-0" style="-ms-overflow-style: none; scrollbar-width: none;">
            <style> .no-scrollbar::-webkit-scrollbar { display: none; } </style>
            <button onclick="filterActivityLog('all', this)" class="activity-filter-btn px-3 py-1 border border-transparent bg-slate-800 text-white text-[10px] font-bold rounded-full whitespace-nowrap transition">Semua</button>
            <button onclick="filterActivityLog('created', this)" class="activity-filter-btn px-3 py-1 border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 text-[10px] font-bold rounded-full whitespace-nowrap transition">Tambah</button>
            <button onclick="filterActivityLog('updated', this)" class="activity-filter-btn px-3 py-1 border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 text-[10px] font-bold rounded-full whitespace-nowrap transition">Edit</button>
            <button onclick="filterActivityLog('deleted', this)" class="activity-filter-btn px-3 py-1 border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 text-[10px] font-bold rounded-full whitespace-nowrap transition">Hapus</button>
            <button onclick="filterActivityLog('login', this)" class="activity-filter-btn px-3 py-1 border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 text-[10px] font-bold rounded-full whitespace-nowrap transition">Login</button>
        </div>
        <div class="overflow-y-auto flex-1 p-4 space-y-3" id="activity-log-container">
            <div class="text-center text-slate-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat...</div>
        </div>
    </div>
</div>

<!-- NOTIFICATIONS & IN-APP INVITES MODAL -->
<div id="notifications-modal" class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="display:none !important">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeNotificationsModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl flex flex-col max-h-[80vh]">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center shrink-0">
            <div class="flex items-center gap-2">
                <i class="fas fa-bell text-[#6c63ff] text-base"></i>
                <h3 class="font-bold text-slate-900 text-base">Notifikasi</h3>
            </div>
            <button onclick="closeNotificationsModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 text-slate-500 hover:bg-slate-100 transition">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 p-4 space-y-4" id="notifications-container">
            <div class="text-center text-slate-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat...</div>
        </div>
    </div>
</div>

<!-- EDIT PROFILE MODAL -->
<div id="edit-profile-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none !important">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditProfileModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">
        <div class="px-6 py-3 flex justify-between items-center border-b border-slate-100">
            <h3 class="font-bold text-slate-800 text-base">Edit Profil</h3>
            <button onclick="closeEditProfileModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form id="edit-profile-form" onsubmit="submitEditProfile(event)" enctype="multipart/form-data">
                <div class="mb-6 flex flex-col items-center">
                    <div class="w-24 h-24 rounded-full overflow-hidden bg-slate-200 border-4 border-white shadow-md mb-3 relative group cursor-pointer" onclick="document.getElementById('edit-profile-avatar').click()">
                        <img id="edit-profile-preview" src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=f1f5f9&color=475569' }}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="fas fa-camera text-white text-xl"></i>
                        </div>
                    </div>
                    <span class="text-xs text-brand-600 font-semibold cursor-pointer" onclick="document.getElementById('edit-profile-avatar').click()">Ganti Foto</span>
                    <input type="file" id="edit-profile-avatar" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Lengkap</label>
                    <input type="text" id="edit-profile-name" value="{{ auth()->user()->name }}" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-semibold rounded-2xl focus:ring-brand-500 focus:border-brand-500 block p-3.5 outline-none transition" required>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Password Baru <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <div class="relative">
                        <input type="password" id="edit-profile-password" class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-2xl focus:ring-brand-500 focus:border-brand-500 block p-3.5 pr-12 outline-none transition" placeholder="Biarkan kosong jika tidak diubah">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600">
                            <i class="fas fa-eye" id="edit-profile-password-eye"></i>
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1.5">*Isi jika ingin mengatur password (misal bagi pendaftar Google).</p>
                </div>

                <button type="submit" id="btn-save-profile" class="w-full text-white bg-brand-600 hover:bg-brand-700 font-bold rounded-2xl text-sm px-5 py-4 text-center transition shadow-lg shadow-brand-500/30 flex justify-center items-center gap-2">
                    Simpan Profil
                </button>
            </form>
        </div>
    </div>
</div>

<!-- PDF REPORT TEMPLATE (Hidden until generated) -->
<div id="pdf-report-template" style="position:absolute; top:-99999px; left:-99999px; width:794px; background:white; padding:40px; font-family: 'Inter', sans-serif; z-index:99999; min-height:1px; pointer-events:none;">
    <!-- Header -->
    <div class="border-b-2 border-slate-200 pb-4 mb-6 text-center">
        <h1 class="text-2xl font-bold text-brand-600 mb-1">Laporan Keuangan Catat-in</h1>
        <p class="text-sm font-medium text-slate-500" id="pdf-date-label">Periode: 1 Mei 2026 - 31 Mei 2026</p>
    </div>

    <!-- Summary Cards -->
    <div class="flex gap-4 mb-8">
        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pemasukan</p>
            <p class="text-lg font-bold text-emerald-600" id="pdf-summary-in">Rp 0</p>
        </div>
        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Pengeluaran</p>
            <p class="text-lg font-bold text-rose-600" id="pdf-summary-out">Rp 0</p>
        </div>
        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Selisih (Netto)</p>
            <p class="text-lg font-bold text-slate-800" id="pdf-summary-balance">Rp 0</p>
        </div>
    </div>

    <!-- Chart -->
    <div class="mb-8">
        <h3 class="font-bold text-slate-800 mb-3 border-b border-slate-100 pb-2">Arus Transaksi</h3>
        <img id="pdf-chart-img" src="" alt="Chart" class="w-full h-auto rounded-lg border border-slate-100">
    </div>

    <!-- Breakdowns -->
    <div class="flex gap-6 mb-8">
        <div class="flex-1">
            <h3 class="font-bold text-slate-800 mb-3 border-b border-slate-100 pb-2">Rincian Pemasukan</h3>
            <div id="pdf-breakdown-in" class="text-sm"></div>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-slate-800 mb-3 border-b border-slate-100 pb-2">Rincian Pengeluaran</h3>
            <div id="pdf-breakdown-out" class="text-sm"></div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="html2pdf__page-break"></div>
    <div>
        <h3 class="font-bold text-slate-800 mb-3 border-b border-slate-100 pb-2">Daftar Transaksi</h3>
        <table class="w-full text-left text-sm border-collapse">
            <thead>
                <tr class="border-b-2 border-slate-200">
                    <th class="py-2 px-2 text-slate-500 font-bold w-1/5">Tanggal</th>
                    <th class="py-2 px-2 text-slate-500 font-bold w-1/4">Kategori</th>
                    <th class="py-2 px-2 text-slate-500 font-bold w-2/5">Catatan</th>
                    <th class="py-2 px-2 text-slate-500 font-bold w-1/5 text-right">Nominal</th>
                </tr>
            </thead>
            <tbody id="pdf-txn-list" class="divide-y divide-slate-100">
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- BACKUP & RESTORE MODAL -->
<div id="backup-restore-modal" class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="display:none !important">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeBackupRestoreModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl flex flex-col">
        <div class="px-5 py-3.5 border-b border-slate-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-slate-900 text-base">Backup & Restore Data</h3>
            <button onclick="closeBackupRestoreModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <div class="p-5 space-y-4">
            <div class="bg-rose-50 rounded-xl p-4 text-xs text-rose-600 leading-relaxed">
                <strong>Catatan Penting:</strong> File backup akan disimpan secara lokal di perangkat Anda (folder Download). Jika HP hilang, rusak, atau di-reset, data tidak bisa dikembalikan jika Anda tidak memindahkannya ke tempat aman (contoh: Google Drive).
            </div>
            
            <div id="last-backup-info" class="bg-slate-50/80 rounded-xl p-3.5 border border-slate-100 hidden">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Backup Terakhir</p>
                <div class="space-y-1.5 text-[11px] text-slate-600">
                    <div class="flex gap-2">
                        <span class="w-16 shrink-0 text-slate-400">Nama File</span>
                        <span class="shrink-0 text-slate-300">:</span>
                        <span id="last-backup-filename" class="font-medium text-slate-700 break-words leading-tight"></span>
                    </div>
                    <div class="flex gap-2">
                        <span class="w-16 shrink-0 text-slate-400">Tanggal</span>
                        <span class="shrink-0 text-slate-300">:</span>
                        <span id="last-backup-date" class="font-medium text-slate-700"></span>
                    </div>
                    <div class="flex gap-2">
                        <span class="w-16 shrink-0 text-slate-400">Folder</span>
                        <span class="shrink-0 text-slate-300">:</span>
                        <span class="font-medium text-slate-700">/Download</span>
                    </div>
                </div>
            </div>
            <button onclick="backupData()" class="w-full flex items-center gap-3 bg-teal-50 hover:bg-teal-100 text-teal-700 p-4 rounded-2xl transition">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm text-lg">
                    <i class="fas fa-cloud-download-alt text-teal-500"></i>
                </div>
                <div class="text-left flex-1">
                    <div class="font-bold text-sm">Backup Data</div>
                    <div class="text-xs text-teal-600/70">Unduh riwayat transaksi ke JSON</div>
                </div>
            </button>
            <button onclick="document.getElementById('import-file').click()" class="w-full flex items-center gap-3 bg-orange-50 hover:bg-orange-100 text-orange-700 p-4 rounded-2xl transition">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm text-lg">
                    <i class="fas fa-cloud-upload-alt text-orange-500"></i>
                </div>
                <div class="text-left flex-1">
                    <div class="font-bold text-sm">Restore Data</div>
                    <div class="text-xs text-orange-600/70">Gabungkan riwayat dari JSON</div>
                </div>
            </button>
        </div>
    </div>
</div>

<!-- CATEGORY DETAILS OVERLAY -->
<div id="category-detail-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999; background: rgba(10,15,30,0.8); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); justify-content: center; align-items: flex-end; opacity: 0; transition: opacity 0.3s ease; touch-action: none;" onclick="closeCategoryDetails()">
    <div id="category-detail-modal" style="background: white; width: 100%; max-width: 28rem; border-radius: 1.5rem 1.5rem 0 0; box-shadow: 0 -10px 40px rgba(0,0,0,0.2); display: flex; flex-direction: column; transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); height: 80vh; pointer-events: auto; touch-action: auto;" onclick="event.stopPropagation()">
        
        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; background: white; border-radius: 1.5rem 1.5rem 0 0; z-index: 10;">
            <div>
                <h3 id="cat-detail-title" style="font-size: 1.125rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.2;">Rincian Kategori</h3>
                <p id="cat-detail-subtitle" style="font-size: 0.75rem; color: #64748b; margin: 0; margin-top: 0.25rem; font-weight: 500;">Bulan Ini</p>
            </div>
            <button type="button" onclick="closeCategoryDetails()" style="width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; border-radius: 9999px; background: #f1f5f9; color: #64748b; border: none; cursor: pointer; outline: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="cat-detail-list" style="flex: 1; overflow-y: auto; padding: 0.5rem 1rem;">
            <!-- Transactions injected here -->
        </div>
    </div>
</div>

<script src="{{ asset('js/data.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/app.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/voice.js') }}?v={{ time() }}"></script>
</body>
</html>

<!-- DASHBOARD FILTER MODAL -->
<div id="dashboard-filter-modal" class="fixed inset-0 z-[90] flex items-end justify-center sm:items-center p-0 sm:p-4" style="display:none !important">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDashboardFilterModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-t-3xl sm:rounded-2xl shadow-2xl flex flex-col transform transition-transform translate-y-full sm:translate-y-0 duration-300" id="dashboard-filter-modal-content">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-slate-900 text-base">Filter Kartu Utama</h3>
            <button type="button" onclick="closeDashboardFilterModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 outline-none">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <div class="p-4 space-y-2">
            <button type="button" onclick="selectDashboardFilter('all')" class="w-full flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition dashboard-filter-btn" data-val="all">
                <span class="font-semibold text-slate-700">Semua Waktu</span>
                <i class="fas fa-check text-brand-600 hidden"></i>
            </button>
            <button type="button" onclick="selectDashboardFilter('year')" class="w-full flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition dashboard-filter-btn" data-val="year">
                <span class="font-semibold text-slate-700">Tahun Ini</span>
                <i class="fas fa-check text-brand-600 hidden"></i>
            </button>
            <button type="button" onclick="selectDashboardFilter('month')" class="w-full flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition dashboard-filter-btn" data-val="month">
                <span class="font-semibold text-slate-700">Bulan Ini</span>
                <i class="fas fa-check text-brand-600 hidden"></i>
            </button>
            <button type="button" onclick="selectDashboardFilter('week')" class="w-full flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition dashboard-filter-btn" data-val="week">
                <span class="font-semibold text-slate-700">Minggu Ini</span>
                <i class="fas fa-check text-brand-600 hidden"></i>
            </button>
        </div>
    </div>
</div>

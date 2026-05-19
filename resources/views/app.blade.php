<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Catat-in App</title>
  <meta name="theme-color" content="#ffffff">
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
    body, html { margin: 0; padding: 0; height: 100%; background-color: #f8fafc; overflow: hidden; }
    /* Hide scrollbar for clean app look */
    ::-webkit-scrollbar { width: 0px; background: transparent; }
    
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
    .fab {
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
    .fab:active { transform: scale(0.9); }

    /* Voice Modal Override */
    .voice-overlay {
        position: fixed; top:0; left:0; right:0; bottom:0;
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(8px);
        display: none; justify-content: center; align-items: flex-end;
        z-index: 100;
    }
    .voice-overlay.active { display: flex; }
    .voice-modal {
        background: white; width: 100%; max-width: 480px;
        border-radius: 24px 24px 0 0; padding: 32px 24px;
        text-align: center; animation: slideUp 0.3s ease;
    }
    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

  </style>
  <script>
    window.authUser = @json(auth()->user());
    window.baseUrl = "{{ url('/') }}";
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
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=f1f5f9&color=475569" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Selamat datang,</p>
                        <h2 class="text-sm font-bold text-slate-900" id="header-name">{{ explode(' ', auth()->user()->name)[0] }}</h2>
                    </div>
                </div>
                <button class="w-10 h-10 rounded-full bg-white border border-slate-100 shadow-sm flex items-center justify-center text-slate-700 relative">
                    <i class="fas fa-bell"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-rose-500 rounded-full border-2 border-white"></span>
                </button>
            </div>

            <!-- Balance Card -->
            <div class="px-6 py-2">
                <div class="blue-card rounded-[28px] p-6 text-white relative overflow-hidden">
                    <!-- Decor circles -->
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white opacity-10"></div>
                    <div class="absolute bottom-0 right-10 -mb-10 w-24 h-24 rounded-full bg-white opacity-10"></div>
                    
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-blue-100 text-sm font-medium opacity-90">Total Saldo</p>
                            <h1 class="text-4xl font-bold mt-1 mb-6" id="home-balance">Rp 0</h1>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-full px-3 py-1 flex items-center gap-1 text-xs font-semibold">
                            <i class="fas fa-chart-line"></i> <span id="home-change">+0%</span>
                        </div>
                    </div>

                    <div class="flex gap-3 relative z-10">
                        <button onclick="document.getElementById('nav-add').click()" class="flex-1 bg-white text-brand-600 rounded-xl py-3 font-semibold text-sm flex items-center justify-center gap-2 hover:bg-slate-50">
                            <i class="fas fa-arrow-down"></i> Pemasukan
                        </button>
                        <button onclick="document.getElementById('nav-add').click()" class="flex-1 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white border border-white/20 rounded-xl py-3 font-semibold text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-arrow-up"></i> Pengeluaran
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
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-slate-900">Arus Transaksi</h3>
                        <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-brand-600"></span> Pengeluaran
                            <span class="w-2 h-2 rounded-full bg-slate-200 ml-2"></span> Pemasukan
                        </div>
                    </div>
                    <div class="h-48 relative">
                        <canvas id="main-chart"></canvas>
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
                <h1 class="text-xl font-bold text-slate-900 text-center">Profil</h1>
            </div>

            <div class="px-6 mt-6">
                <!-- Profile Header -->
                <div class="flex flex-col items-center mb-8">
                    <div class="w-24 h-24 rounded-full overflow-hidden bg-slate-200 border-4 border-white shadow-md mb-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=f1f5f9&color=475569" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">{{ auth()->user()->name }}</h2>
                    <p class="text-sm text-slate-500">{{ auth()->user()->email }}</p>
                    <span class="mt-2 px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold uppercase tracking-wider">{{ auth()->user()->role }}</span>
                </div>

                <!-- Settings Blocks -->
                <div class="bg-white border border-slate-100 shadow-sm rounded-3xl overflow-hidden mb-6">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition" onclick="navigateTo('categories')">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center">
                                <i class="fas fa-tags"></i>
                            </div>
                            <span class="font-semibold text-slate-700">Kelola Kategori</span>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </div>
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <span class="font-semibold text-slate-700">Atur Saldo Awal</span>
                        </div>
                        <i class="fas fa-chevron-right text-slate-400 text-sm"></i>
                    </div>
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
    <div class="page absolute top-0 left-0 w-full h-full bg-[#f8fafc] z-40 overflow-hidden" id="page-categories">
      <div class="flex flex-col w-full h-full">
        <!-- Header -->
        <div class="px-4 pt-8 pb-4 bg-white flex items-center gap-4 border-b border-slate-100 shrink-0">
            <button onclick="navigateTo('profile')" class="w-10 h-10 flex items-center justify-center text-slate-700 hover:bg-slate-50 rounded-full transition">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h1 class="text-xl font-bold text-slate-900">Kelola Kategori</h1>
        </div>

        <div class="flex-1 overflow-y-auto pb-8">
            <div class="px-4 mt-4">
                <!-- Tab -->
                <div class="flex bg-slate-200 rounded-xl p-1 mb-4">
                    <button class="flex-1 py-1.5 rounded-lg text-sm font-semibold bg-white shadow-sm text-slate-900" id="cat-tab-out" onclick="setCategoryTab('pengeluaran')">Pengeluaran</button>
                    <button class="flex-1 py-1.5 rounded-lg text-sm font-semibold text-slate-500" id="cat-tab-in" onclick="setCategoryTab('pemasukan')">Pemasukan</button>
                </div>
                
                <!-- List -->
                <div class="bg-white border border-slate-100 shadow-sm rounded-2xl mb-4" id="category-list-container">
                    <!-- Populated via JS -->
                </div>

                <!-- Add Form -->
                <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-4">
                    <h3 class="font-bold text-slate-900 mb-3 text-sm">Tambah Kategori Baru</h3>
                    <form id="add-category-form" autocomplete="off" onsubmit="handleAddCategory(event)">
                        <input type="hidden" id="add-cat-type" value="pengeluaran">
                        <div class="flex gap-2">
                            <input type="text" id="add-cat-name" required class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:border-brand-600 outline-none" placeholder="Nama Kategori">
                            <button type="submit" class="bg-brand-600 text-white px-4 rounded-xl font-bold hover:bg-brand-700 transition">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
    </div>

    <!-- EDIT CATEGORY MODAL -->
    <div id="edit-cat-modal" class="fixed inset-0 z-50 flex items-end justify-center" style="display:none !important">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditCatModal()"></div>
        <div class="relative bg-white w-full max-w-[480px] rounded-t-3xl p-6">
            <div class="w-12 h-1 bg-slate-200 rounded-full mx-auto mb-5"></div>
            <h3 class="font-bold text-slate-900 mb-4 text-lg">Edit Kategori</h3>
            <input type="hidden" id="edit-cat-id">
            <input type="hidden" id="edit-cat-type-val">
            <div class="mb-4">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Kategori</label>
                <input type="text" id="edit-cat-name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:border-brand-600 outline-none" placeholder="Nama Kategori">
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

    <!-- EDIT TRANSACTION MODAL -->
    <div id="edit-txn-modal" class="fixed inset-0 z-50 flex items-end justify-center" style="display:none !important">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditTxnModal()"></div>
        <div class="relative bg-white w-full max-w-[480px] rounded-t-3xl p-6">
            <div class="w-12 h-1 bg-slate-200 rounded-full mx-auto mb-5"></div>
            <h3 class="font-bold text-slate-900 mb-4 text-lg">Edit Transaksi</h3>
            
            <form id="edit-txn-form" autocomplete="off" onsubmit="submitEditTxn(event)">
                <input type="hidden" id="edit-txn-id">
                <input type="hidden" id="edit-txn-type">
                
                <div class="mb-3">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nominal</label>
                    <input type="text" id="edit-txn-amount" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:border-brand-600 outline-none" oninput="formatCurrencyInput(this)">
                </div>
                
                <div class="mb-3">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Kategori</label>
                    <select id="edit-txn-category" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:border-brand-600 outline-none">
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Catatan</label>
                    <input type="text" id="edit-txn-desc" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:border-brand-600 outline-none">
                </div>
                
                <div class="mb-5">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tanggal</label>
                    <input type="date" id="edit-txn-date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:border-brand-600 outline-none">
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeEditTxnModal()" class="flex-1 bg-slate-100 text-slate-600 rounded-xl py-3 font-bold text-sm transition hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 bg-brand-600 text-white rounded-xl py-3 font-bold text-sm transition hover:bg-brand-700">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FLOATING ACTION BUTTON (Old FAB removed) -->

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
        
        <!-- CENTER MIC BUTTON -->
        <div class="relative w-16 flex justify-center">
            <button class="absolute -top-10 w-16 h-16 rounded-full bg-brand-600 text-white flex items-center justify-center text-2xl shadow-lg border-4 border-white transition-transform active:scale-90" onclick="openVoiceModal()" id="fab-mic">
                <i class="fas fa-microphone"></i>
            </button>
        </div>

        <a href="#" class="bottom-nav-item flex flex-col items-center gap-1 w-16" data-page="wallet" id="nav-wallet">
            <i class="fas fa-wallet text-xl"></i>
            <span class="text-[10px] font-bold">Riwayat</span>
        </a>
        <a href="#" class="bottom-nav-item flex flex-col items-center gap-1 w-16" data-page="profile" id="nav-profile">
            <i class="fas fa-user text-xl"></i>
            <span class="text-[10px] font-bold">Profil</span>
        </a>
        <a href="#" class="hidden" data-page="add" id="nav-add"></a>
    </nav>

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
        
        <button class="w-full bg-slate-900 text-white rounded-xl py-4 font-bold text-sm" id="mic-stop-btn">
            Berhenti Merekam
        </button>
    </div>
</div>

<!-- TOAST -->
<div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-6 py-3 rounded-full text-sm font-medium shadow-lg z-50 transition-all duration-300 transform -translate-y-20 opacity-0">
    Message
</div>

<!-- PDF REPORT TEMPLATE (Hidden until generated) -->
<div id="pdf-report-template" style="position:absolute; top:-99999px; left:-99999px; width:794px; background:white; padding:40px; font-family: 'Inter', sans-serif; z-index:99999; min-height:1px;">
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

<script src="{{ asset('js/data.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/voice.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/app.js') }}?v={{ time() }}"></script>
</body>
</html>

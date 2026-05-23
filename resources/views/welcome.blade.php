<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @php
      $appName = \App\Models\Setting::get('app_name', 'Catat-in — Catat Keuangan UMKM Pakai Suara');
      $appDesc = \App\Models\Setting::get('app_description', 'Aplikasi pencatatan keuangan UMKM terpintar. Cukup ucapkan transaksimu, Catat-in akan mencatatnya otomatis. Gratis, mudah, dan akurat.');
      $appIcon = \App\Models\Setting::get('app_icon') ? asset('storage/' . \App\Models\Setting::get('app_icon')) : asset('favicon.png');
      $appPhoto = \App\Models\Setting::get('app_photo') ? asset('storage/' . \App\Models\Setting::get('app_photo')) : asset('favicon.png');
  @endphp
  <title>{{ $appName }}</title>
  <meta name="description" content="{{ $appDesc }}">
  <meta property="og:title" content="{{ $appName }}">
  <meta property="og:description" content="{{ $appDesc }}">
  <meta property="og:image" content="{{ $appPhoto }}">
  <meta property="og:type" content="website">
  <link rel="icon" type="image/png" href="{{ $appIcon }}">
  <link rel="apple-touch-icon" href="{{ $appIcon }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
</head>
<body>

  <!-- NAV -->
  <nav class="navbar" id="navbar">
    <div class="nav-container">
      <a href="index.html" class="nav-logo">
        <div class="logo-icon">💰</div>
        <span>Catat<span class="accent">-in</span></span>
      </a>
      <div class="nav-links">
        <a href="#fitur">Fitur</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="#testimoni">Testimoni</a>
        <a href="{{ route('dashboard') }}" class="btn-nav">Mulai Gratis →</a>
      </div>
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-bg">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
      <div class="blob blob-3"></div>
      <div class="grid-overlay"></div>
    </div>
    <div class="container">
      <div class="hero-content">
        <div class="hero-badge">
          <span class="badge-dot"></span>
          Aplikasi Keuangan UMKM #1 Indonesia
        </div>
        <h1 class="hero-title">
          Catat Keuangan<br>
          <span class="gradient-text">Pakai Suara</span><br>
          Semudah Ngobrol
        </h1>
        <p class="hero-desc">
          Cukup ucapkan <em>"bayar listrik 150rb"</em> atau <em>"dapat bayaran 2 juta"</em> —
          Catat-in langsung mencatat, mengelompokkan, dan melapor otomatis.
          Tanpa ribet, tanpa salah.
        </p>
        <div class="hero-actions">
          <a href="{{ route('dashboard') }}" class="btn-primary" id="cta-hero">
            <span>🎙️</span> Coba Sekarang — Gratis
          </a>
          <a href="#cara-kerja" class="btn-ghost">
            Lihat Demo ↓
          </a>
        </div>
        <div class="hero-stats">
          <div class="stat">
            <strong>50K+</strong>
            <span>UMKM Terbantu</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat">
            <strong>2 Juta+</strong>
            <span>Transaksi Dicatat</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat">
            <strong>98%</strong>
            <span>Akurasi Suara</span>
          </div>
        </div>
      </div>
      <div class="hero-visual">
        <div class="phone-mockup">
          <div class="phone-screen">
            <!-- Topbar -->
            <div class="mock-topbar">
              <div class="mock-project">
                <div class="mock-project-icon"><i class="fas fa-wallet"></i></div>
                <div class="mock-project-name">Rumah Tangga</div>
              </div>
              <div style="display:flex; align-items:center; gap:12px;">
                <i class="fas fa-bell" style="color:#94a3b8;"></i>
                <div class="mock-avatar"></div>
              </div>
            </div>

            <!-- Balance Card -->
            <div class="mock-balance-card">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div class="mock-balance-label">Total Saldo <span style="background:rgba(255,255,255,0.2); padding:2px 6px; border-radius:100px; margin-left:4px; font-size:8px;">BULAN INI</span></div>
                <i class="fas fa-eye-slash" style="background:rgba(255,255,255,0.2); padding:6px; border-radius:50%; font-size:10px;"></i>
              </div>
              <div class="mock-balance-value">Rp &bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
              <div class="mock-balance-row">
                <div class="mock-balance-box">
                  <span>↓ PEMASUKAN</span>
                  <strong>Rp &bull;&bull;&bull;&bull;&bull;&bull;&bull;</strong>
                </div>
                <div class="mock-balance-box">
                  <span>↑ PENGELUARAN</span>
                  <strong>Rp &bull;&bull;&bull;&bull;&bull;&bull;&bull;</strong>
                </div>
              </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; margin-bottom:8px; padding:0 4px;">
              <div class="mock-section-title" style="margin-bottom:0;">Insight AI</div>
              <span style="font-size:10px; color:#6c63ff; font-weight:700;">Lihat semua</span>
            </div>

            <!-- Insight -->
            <div class="mock-insight">
              <div class="mock-insight-icon"><i class="fas fa-chart-line"></i></div>
              <div class="mock-insight-text">
                <strong>Kerja bagus! Pengeluaran menurun</strong>
                <p>Pengeluaran Anda menurun dibandingkan minggu lalu.</p>
              </div>
            </div>

            <div class="mock-section-title" style="margin-top:20px;">Aksi Cepat</div>
            <!-- Actions -->
            <div class="mock-actions">
              <div class="mock-action">
                <div class="mock-action-icon c1"><i class="fas fa-microphone"></i></div>
                <span>Suara</span>
              </div>
              <div class="mock-action">
                <div class="mock-action-icon c2"><i class="fas fa-list-ul"></i></div>
                <span>Riwayat</span>
              </div>
              <div class="mock-action">
                <div class="mock-action-icon c3"><i class="fas fa-chart-pie"></i></div>
                <span>Laporan</span>
              </div>
              <div class="mock-action">
                <div class="mock-action-icon c4"><i class="fas fa-cog"></i></div>
                <span>Setelan</span>
              </div>
            </div>

            <div class="mock-section-title" style="margin-top:24px;">Transaksi Terbaru</div>
            <!-- Txn List -->
            <div class="mock-txn-list">
              <div class="mock-txn">
                <div class="mock-txn-icon" style="color:#ef4444;"><i class="fas fa-bolt"></i></div>
                <div class="mock-txn-detail">
                  <span>Beli Token Listrik</span>
                  <small>09:30</small>
                </div>
                <span class="mock-txn-amount out">-150.000</span>
              </div>
              <div class="mock-txn">
                <div class="mock-txn-icon" style="color:#10b981;"><i class="fas fa-briefcase"></i></div>
                <div class="mock-txn-detail">
                  <span>Gajian Bulanan</span>
                  <small>08:15</small>
                </div>
                <span class="mock-txn-amount in">+4.500.000</span>
              </div>
            </div>

            <!-- Bottom Nav -->
            <div class="mock-bottom-nav">
              <div class="mock-nav-item active"><i class="fas fa-home"></i></div>
              <div class="mock-nav-item"><i class="fas fa-chart-pie"></i></div>
              <div class="mock-nav-item" style="width:40px;"></div>
              <div class="mock-nav-item"><i class="fas fa-wallet"></i></div>
              <div class="mock-nav-item"><i class="fas fa-cog"></i></div>
            </div>
          </div>
          <div class="mic-pulse">
            <div class="mic-ring ring-1"></div>
            <div class="mic-ring ring-2"></div>
            <button class="mic-btn-hero"><i class="fas fa-microphone"></i></button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="section" id="fitur">
    <div class="container">
      <div class="section-header">
        <div class="section-tag">Fitur Unggulan</div>
        <h2>Semua yang Kamu Butuhkan<br>untuk Kelola Keuangan Usaha</h2>
        <p>Dari pencatatan cepat sampai laporan mendalam, semuanya ada di Catat-in.</p>
      </div>
      <div class="features-grid">
        <div class="feature-card feature-primary">
          <div class="feature-icon"><i class="fas fa-microphone"></i></div>
          <h3>Pencatatan Cepat via Suara</h3>
          <p>Fitur andalan kami. Ucapkan transaksi dalam bahasa sehari-hari — AI Catat-in memahami dan mencatatnya otomatis tanpa perlu mengetik manual.</p>
          <div class="feature-examples">
            <div class="example">"bayar listrik 150rb" →</div>
            <div class="example-result"><i class="fas fa-check-circle"></i> Pengeluaran Rp 150.000</div>
            <div class="example">"dapat bayaran 2 juta" →</div>
            <div class="example-result"><i class="fas fa-check-circle"></i> Pemasukan Rp 2.000.000</div>
          </div>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-users"></i></div>
          <h3>Kelola Proyek & Kolaborasi Tim</h3>
          <p>Buat banyak proyek berbeda (Rumah Tangga, Usaha A, Usaha B) dan undang anggota tim untuk berkolaborasi mencatat keuangan bersama.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
          <h3>Insight AI Cerdas</h3>
          <p>Dapatkan analisis pintar tentang tren pengeluaran dan saran langsung dari AI untuk mengoptimalkan kesehatan keuangan Anda.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
          <h3>Filter & Laporan Otomatis</h3>
          <p>Pantau laporan harian, mingguan, dan bulanan yang dibuat otomatis. Filter riwayat transaksi berdasarkan kategori atau rentang tanggal dengan mudah.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-tags"></i></div>
          <h3>Kategori Fleksibel</h3>
          <p>Puluhan kategori bawaan atau buat custom sendiri. Tambahkan warna ikon sesuai selera agar laporan semakin terstruktur dan mudah dibaca.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-lock"></i></div>
          <h3>Privasi Aman Terlindungi</h3>
          <p>Data keuangan Anda disimpan dengan aman dan dapat diakses dengan cepat kapan saja melalui perangkat smartphone maupun laptop.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="section section-dark" id="cara-kerja">
    <div class="container">
      <div class="section-header">
        <div class="section-tag">Cara Kerja</div>
        <h2>Catat Transaksi dalam<br><span class="gradient-text">3 Detik</span></h2>
        <p>Sesederhana berbicara dengan teman.</p>
      </div>
      <div class="steps">
        <div class="step">
          <div class="step-number">01</div>
          <div class="step-icon"><i class="fas fa-hand-pointer text-brand-500"></i></div>
          <h3>Tekan Tombol Mic</h3>
          <p>Klik tombol mikrofon di dashboard. Izinkan akses mikrofon browser.</p>
        </div>
        <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
        <div class="step">
          <div class="step-number">02</div>
          <div class="step-icon"><i class="fas fa-comment-dots text-brand-500"></i></div>
          <h3>Ucapkan Transaksi</h3>
          <p>Bicara normal seperti biasa: <em>"beli bahan jualan 300 ribu"</em></p>
        </div>
        <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
        <div class="step">
          <div class="step-number">03</div>
          <div class="step-icon"><i class="fas fa-check-circle text-emerald-500"></i></div>
          <h3>Konfirmasi & Simpan</h3>
          <p>Cek hasil parsing, koreksi jika perlu, lalu simpan. Selesai!</p>
        </div>
      </div>

      <!-- Live demo -->
      <div class="demo-box">
        <div class="demo-label">✨ Contoh Kalimat yang Bisa Diucapkan</div>
        <div class="demo-examples">
          <div class="demo-chip out">bayar listrik 150 ribu</div>
          <div class="demo-chip in">dapat bayaran 2 juta</div>
          <div class="demo-chip out">beli bahan jualan 300rb</div>
          <div class="demo-chip in">customer bayar 750 ribu</div>
          <div class="demo-chip out">bayar karyawan 1.5 juta</div>
          <div class="demo-chip in">hasil jualan hari ini 4 juta</div>
          <div class="demo-chip out">bayar gojek 25 ribu</div>
          <div class="demo-chip in">pelanggan transfer 500rb</div>
          <div class="demo-chip out">modal usaha masuk 10 juta</div>
          <div class="demo-chip out">bayar internet 250 ribu</div>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section class="section" id="testimoni">
    <div class="container">
      <div class="section-header">
        <div class="section-tag">Testimoni</div>
        <h2>Mereka Sudah Merasakan<br>Manfaatnya</h2>
      </div>
      <div class="testi-grid">
        <div class="testi-card">
          <div class="testi-stars">★★★★★</div>
          <p>"Dulu catat di buku, sering lupa & salah. Sekarang tinggal ngomong ke HP, langsung kerekam. Laporan bulanan langsung jadi!"</p>
          <div class="testi-author">
            <div class="testi-avatar" style="background:#10b981">S</div>
            <div>
              <strong>Sari</strong>
              <span>Pemilik Warung Sembako</span>
            </div>
          </div>
        </div>
        <div class="testi-card testi-featured">
          <div class="testi-stars">★★★★★</div>
          <p>"Bisnis online shop makin gampang dikelola. Pas lagi packing, tinggal bilang 'customer bayar 150rb' langsung tercatat. Nggak perlu buka notes dulu!"</p>
          <div class="testi-author">
            <div class="testi-avatar" style="background:#8b5cf6">A</div>
            <div>
              <strong>Andi</strong>
              <span>Online Shop Owner</span>
            </div>
          </div>
        </div>
        <div class="testi-card">
          <div class="testi-stars">★★★★★</div>
          <p>"Fitur laporan PDF-nya keren banget. Langsung bisa kirim ke akuntan. Hemat waktu & uang!"</p>
          <div class="testi-author">
            <div class="testi-avatar" style="background:#f59e0b">R</div>
            <div>
              <strong>Rina</strong>
              <span>Pengusaha Katering</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="section section-cta">
    <div class="container">
      <div class="cta-box">
        <div class="cta-blob"></div>
        <h2>Siap Kelola Keuangan<br>Usahamu Lebih Cerdas?</h2>
        <p>Gratis selamanya untuk fitur dasar. Mulai dalam 30 detik.</p>
        <a href="{{ route('dashboard') }}" class="btn-primary btn-large" id="cta-bottom">
          <span>🚀</span> Mulai Gratis Sekarang
        </a>
        <div class="cta-note">Tidak perlu daftar. Langsung pakai.</div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="container">
      <div class="footer-top">
        <div class="footer-brand">
          <div class="nav-logo">
            <div class="logo-icon">💰</div>
            <span>Catat<span class="accent">-in</span></span>
          </div>
          <p>Aplikasi pencatatan keuangan UMKM<br>dengan teknologi voice recognition.</p>
        </div>
        <div class="footer-links">
          <div class="footer-col">
            <h4>Produk</h4>
            <a href="#fitur">Fitur</a>
            <a href="#cara-kerja">Cara Kerja</a>
            <a href="{{ route('dashboard') }}">Mulai Pakai</a>
          </div>
          <div class="footer-col">
            <h4>Dukungan</h4>
            <a href="#">Panduan</a>
            <a href="#">FAQ</a>
            <a href="#">Kontak</a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© 2026 Catat-in. Dibuat dengan ❤️ untuk UMKM Indonesia.</span>
      </div>
    </div>
  </footer>

  <script>
    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    // Hamburger
    document.getElementById('hamburger').addEventListener('click', function() {
      document.querySelector('.nav-links').classList.toggle('open');
      this.classList.toggle('active');
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', e => {
        e.preventDefault();
        document.querySelector(a.getAttribute('href'))?.scrollIntoView({ behavior: 'smooth' });
      });
    });

    // Intersection Observer for animations
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.feature-card, .step, .testi-card').forEach(el => {
      el.classList.add('animate-on-scroll');
      observer.observe(el);
    });
  </script>
</body>
</html>

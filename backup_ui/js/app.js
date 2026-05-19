/**
 * app.js
 * Main UI Logic and DOM Manipulation
 */

// UI State
let currentPage = 'dashboard';

// Initialize App
document.addEventListener('DOMContentLoaded', () => {
  initUI();
  initEvents();
  initVoice();
  updateProfileUI();
  updateDashboard();
});

function initUI() {
  // Populate category dropdowns
  populateCategorySelect();
  populateFilterCat();
  renderSettingsCategories('pengeluaran');
  
  // Set default dates
  const today = new Date().toISOString().split('T')[0];
  const dateInput = document.getElementById('txn-date');
  if(dateInput) dateInput.value = today;

  document.getElementById('greeting-date').textContent = formatDate(new Date());
}

function initEvents() {
  // Navigation
  document.querySelectorAll('.nav-item').forEach(el => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      navigateTo(el.dataset.page);
    });
  });

  // Mobile menu
  const menuBtn = document.getElementById('menu-btn');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  
  const toggleMenu = () => {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
  };
  
  menuBtn.addEventListener('click', toggleMenu);
  overlay.addEventListener('click', toggleMenu);

  // Add Txn Form
  const form = document.getElementById('txn-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      handleManualSubmit();
    });
  }

  // Type Toggle
  document.querySelectorAll('.type-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('txn-type').value = btn.dataset.type;
      populateCategorySelect(); // refresh options based on type
    });
  });

  // Filters
  document.getElementById('filter-type')?.addEventListener('change', updateHistory);
  document.getElementById('filter-cat')?.addEventListener('change', updateHistory);
  document.getElementById('filter-period')?.addEventListener('change', updateHistory);
  document.getElementById('search-input')?.addEventListener('input', updateHistory);

  // Reports
  document.querySelectorAll('.period-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      updateReport(btn.dataset.period);
    });
  });

  // Voice setup
  document.getElementById('voice-btn-dashboard')?.addEventListener('click', () => {
     navigateTo('add');
     openVoiceModal();
  });
  document.getElementById('quick-mic-btn')?.addEventListener('click', () => {
     navigateTo('add');
     openVoiceModal();
  });
  document.getElementById('mic-button')?.addEventListener('click', toggleRecordingCard);
  document.getElementById('mic-stop-btn')?.addEventListener('click', stopRecording);
  document.getElementById('voice-modal-close')?.addEventListener('click', stopRecording);
  
  document.getElementById('reparsed-close')?.addEventListener('click', () => {
     document.getElementById('parsed-card').style.display = 'none';
     document.getElementById('voice-status-label').textContent = 'Tekan tombol mikrofon untuk mulai';
     document.getElementById('voice-transcript-box').innerHTML = '<span class="transcript-placeholder">Ucapan kamu akan muncul di sini...</span>';
  });

  // Settings
  document.getElementById('save-profile-btn')?.addEventListener('click', () => {
     appData.profile.name = document.getElementById('setting-name').value || 'Bos UMKM';
     appData.profile.business = document.getElementById('setting-business').value || 'Usaha Mandiri';
     saveData(appData);
     updateProfileUI();
     showToast('Profil berhasil disimpan');
  });

  document.getElementById('save-balance-btn')?.addEventListener('click', () => {
     const bal = parseFloat(document.getElementById('setting-balance').value) || 0;
     appData.profile.initialBalance = bal;
     saveData(appData);
     updateDashboard();
     showToast('Saldo awal berhasil diperbarui');
  });

  document.getElementById('clear-data-btn')?.addEventListener('click', () => {
     showConfirm('Hapus Semua Data', 'Anda yakin ingin menghapus semua data transaksi? Tindakan ini tidak bisa dibatalkan.', () => {
        appData.transactions = [];
        saveData(appData);
        updateDashboard();
        showToast('Semua data berhasil dihapus');
     });
  });

  // Categories Settings Tab
  document.querySelectorAll('.cat-tab').forEach(tab => {
     tab.addEventListener('click', () => {
        document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        renderSettingsCategories(tab.dataset.catType);
     });
  });

  document.getElementById('add-cat-btn')?.addEventListener('click', () => {
     const name = document.getElementById('new-cat-input').value.trim();
     const icon = document.getElementById('new-cat-icon').value.trim() || '📌';
     if(!name) return;
     
     const type = document.querySelector('.cat-tab.active').dataset.catType;
     appData.categories[type].push({ id: 'cat_' + Date.now(), name, icon });
     saveData(appData);
     
     document.getElementById('new-cat-input').value = '';
     document.getElementById('new-cat-icon').value = '';
     renderSettingsCategories(type);
     populateCategorySelect();
     showToast('Kategori ditambahkan');
  });
}

function navigateTo(pageId) {
  // Update nav UI
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  const activeNav = document.getElementById('nav-' + pageId);
  if (activeNav) activeNav.classList.add('active');

  // Update Page UI
  document.querySelectorAll('.page').forEach(el => el.classList.remove('active'));
  document.getElementById('page-' + pageId).classList.add('active');

  // Update Title
  const titles = {
    'dashboard': 'Dashboard',
    'add': 'Tambah Transaksi',
    'history': 'Riwayat Transaksi',
    'report': 'Laporan Keuangan',
    'settings': 'Pengaturan'
  };
  document.getElementById('page-title').textContent = titles[pageId] || 'Catat-in';

  // Close mobile sidebar if open
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('active');

  currentPage = pageId;

  // Page Specific Actions
  if (pageId === 'dashboard') updateDashboard();
  if (pageId === 'history') updateHistory();
  if (pageId === 'report') updateReport('week');
  if (pageId === 'settings') {
     document.getElementById('setting-name').value = appData.profile.name;
     document.getElementById('setting-business').value = appData.profile.business;
     document.getElementById('setting-balance').value = appData.profile.initialBalance;
  }
}

function updateProfileUI() {
  document.getElementById('sidebar-username').textContent = appData.profile.name;
  document.getElementById('greeting-text').textContent = `Selamat pagi, ${appData.profile.name.split(' ')[0]}! 👋`;
}

/* ===== DASHBOARD ===== */
function updateDashboard() {
  const { totalIn, totalOut, balance } = calculateBalances();
  
  document.getElementById('saldo-hari-ini').textContent = formatRupiah(balance);
  document.getElementById('pemasukan-hari-ini').textContent = formatRupiah(totalIn);
  document.getElementById('pengeluaran-hari-ini').textContent = formatRupiah(totalOut);
  
  const inCount = appData.transactions.filter(t => t.type === 'pemasukan').length;
  const outCount = appData.transactions.filter(t => t.type === 'pengeluaran').length;
  
  document.getElementById('pemasukan-count').textContent = `${inCount} transaksi`;
  document.getElementById('pengeluaran-count').textContent = `${outCount} transaksi`;

  // Recent transactions
  const recentList = document.getElementById('recent-txn-list');
  const recent = appData.transactions.slice(0, 5);
  
  if (recent.length === 0) {
    recentList.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">📭</div>
        <p>Belum ada transaksi. Mulai catat sekarang!</p>
        <button class="btn-primary-sm" onclick="navigateTo('add')">+ Tambah Transaksi</button>
      </div>`;
    
    document.getElementById('insight-text').textContent = "Belum ada transaksi. Yuk mulai catat pemasukan & pengeluaran hari ini!";
  } else {
    recentList.innerHTML = recent.map(t => createTxnHTML(t)).join('');
    
    const todayTxns = getTodayTransactions();
    if(todayTxns.length === 0) {
       document.getElementById('insight-text').textContent = "Kamu belum mencatat transaksi hari ini. Jangan sampai ada yang terlewat ya!";
    } else {
       const todayOut = todayTxns.filter(t=>t.type==='pengeluaran').reduce((s,t)=>s+t.amount,0);
       const todayIn = todayTxns.filter(t=>t.type==='pemasukan').reduce((s,t)=>s+t.amount,0);
       if(todayOut > todayIn && todayIn > 0) {
          document.getElementById('insight-text').textContent = `Pengeluaran hari ini lebih besar dari pemasukan. Kurangi biaya operasional besok.`;
       } else {
          document.getElementById('insight-text').textContent = `Bagus! Kamu sudah mencatat ${todayTxns.length} transaksi hari ini.`;
       }
    }
  }

  // Update mini chart (ensure it exists before rendering)
  if(document.getElementById('mini-chart')) {
    setTimeout(() => renderCharts('week'), 100); 
  }
}

function createTxnHTML(t) {
  const isOut = t.type === 'pengeluaran';
  const icon = getCategoryIcon(t.category, t.type);
  const amtClass = isOut ? 'out' : 'in';
  const sign = isOut ? '-' : '+';
  
  return `
    <div class="txn-item">
      <div class="txn-left">
        <div class="txn-icon-box">${icon}</div>
        <div class="txn-info">
          <h4>${t.desc || t.category}</h4>
          <p>${formatDate(t.date)} • ${t.category}</p>
        </div>
      </div>
      <div class="txn-right">
        <div class="txn-amt ${amtClass}">${sign}${formatRupiah(t.amount)}</div>
        <div class="txn-actions">
           <button class="btn-del" onclick="handleDelete('${t.id}')" title="Hapus">🗑️</button>
        </div>
      </div>
    </div>
  `;
}

function handleDelete(id) {
   showConfirm('Hapus Transaksi', 'Yakin ingin menghapus transaksi ini?', () => {
      deleteTransaction(id);
      if(currentPage === 'dashboard') updateDashboard();
      if(currentPage === 'history') updateHistory();
      showToast('Transaksi dihapus');
   });
}

/* ===== ADD TRANSACTION / VOICE ===== */
let pendingVoiceTxn = null;

function populateCategorySelect() {
  const type = document.getElementById('txn-type').value;
  const select = document.getElementById('txn-category');
  if(!select) return;

  const cats = appData.categories[type] || [];
  
  select.innerHTML = '<option value="">— Pilih Kategori —</option>';
  cats.forEach(c => {
    select.innerHTML += `<option value="${c.name}">${c.icon} ${c.name}</option>`;
  });
}

function handleManualSubmit() {
  const type = document.getElementById('txn-type').value;
  const amount = parseFloat(document.getElementById('txn-amount').value);
  const category = document.getElementById('txn-category').value;
  const desc = document.getElementById('txn-desc').value;
  const date = document.getElementById('txn-date').value;

  if (!amount || !category || !date) {
    showToast('Harap isi field wajib (nominal, kategori, tanggal)');
    return;
  }

  addTransaction({ type, amount, category, desc, date });
  showToast('Transaksi berhasil disimpan!');
  resetForm();
  navigateTo('dashboard');
}

function resetForm() {
  const form = document.getElementById('txn-form');
  if(form) form.reset();
  const today = new Date().toISOString().split('T')[0];
  const dateInput = document.getElementById('txn-date');
  if(dateInput) dateInput.value = today;
}

// Voice Integration
window.onVoiceStart = () => {
  document.getElementById('mic-button')?.classList.add('listening');
  document.getElementById('voice-status-label').textContent = 'Mendengarkan... (Silakan bicara)';
  document.getElementById('voice-overlay').classList.add('active');
  document.getElementById('voice-modal-transcript').textContent = 'Mendengarkan...';
};

window.onVoiceResult = (text, isFinal) => {
  document.getElementById('voice-transcript-box').innerHTML = text;
  document.getElementById('voice-modal-transcript').textContent = text;
  
  if (isFinal) {
    processVoiceText(text);
    document.getElementById('voice-overlay').classList.remove('active');
  }
};

window.onVoiceEnd = () => {
  document.getElementById('mic-button')?.classList.remove('listening');
  document.getElementById('voice-overlay').classList.remove('active');
  if(document.getElementById('voice-status-label').textContent.includes('Mendengarkan')) {
      document.getElementById('voice-status-label').textContent = 'Selesai mendengarkan.';
  }
};

window.onVoiceError = (err) => {
  showToast('Error suara: ' + err);
  document.getElementById('voice-status-label').textContent = 'Terjadi kesalahan. Coba lagi.';
};

function processVoiceText(text) {
  const parsed = parseTransactionText(text);
  if (!parsed || parsed.amount === 0) {
    showToast('Tidak bisa mengenali nominal. Coba ulangi dengan jelas.');
    return;
  }

  pendingVoiceTxn = parsed;

  const card = document.getElementById('parsed-card');
  const res = document.getElementById('parsed-result');
  
  const icon = getCategoryIcon(parsed.category, parsed.type);
  const typeStr = parsed.type === 'pengeluaran' ? '🔴 Pengeluaran' : '🟢 Pemasukan';

  res.innerHTML = `
    <p><strong>Tipe:</strong> ${typeStr}</p>
    <p><strong>Nominal:</strong> ${formatRupiah(parsed.amount)}</p>
    <p><strong>Kategori:</strong> ${icon} ${parsed.category}</p>
    <p><strong>Keterangan:</strong> ${parsed.desc}</p>
  `;
  card.style.display = 'block';

  // Setup save button
  document.getElementById('parsed-save-btn').onclick = () => {
    addTransaction({
      ...pendingVoiceTxn,
      date: new Date().toISOString().split('T')[0]
    });
    card.style.display = 'none';
    showToast('Transaksi suara berhasil disimpan!');
    navigateTo('dashboard');
  };

  // Setup edit button
  document.getElementById('parsed-edit-btn').onclick = () => {
    // Fill manual form
    const btnType = pendingVoiceTxn.type === 'pengeluaran' ? 'btn-type-out' : 'btn-type-in';
    document.getElementById(btnType).click();
    
    document.getElementById('txn-amount').value = pendingVoiceTxn.amount;
    
    // Attempt to set category if exists
    setTimeout(() => {
        document.getElementById('txn-category').value = pendingVoiceTxn.category;
    }, 100);
    
    document.getElementById('txn-desc').value = pendingVoiceTxn.desc;
    
    card.style.display = 'none';
    document.getElementById('txn-amount').focus();
    // Scroll to manual form
    document.getElementById('manual-form-card').scrollIntoView({behavior: 'smooth'});
  };
}

function toggleRecordingCard() {
  if (isRecording) stopRecording();
  else startRecording();
}

function openVoiceModal() {
  if (!isRecording) startRecording();
}


/* ===== HISTORY ===== */
function populateFilterCat() {
   const select = document.getElementById('filter-cat');
   if(!select) return;
   
   select.innerHTML = '<option value="all">Semua Kategori</option>';
   appData.categories.pemasukan.forEach(c => select.innerHTML += `<option value="${c.name}">${c.name}</option>`);
   appData.categories.pengeluaran.forEach(c => select.innerHTML += `<option value="${c.name}">${c.name}</option>`);
}

function updateHistory() {
  const typeF = document.getElementById('filter-type').value;
  const catF = document.getElementById('filter-cat').value;
  const perF = document.getElementById('filter-period').value;
  const q = document.getElementById('search-input').value.toLowerCase();

  let txns = filterTransactions(perF);

  if (typeF !== 'all') txns = txns.filter(t => t.type === typeF);
  if (catF !== 'all') txns = txns.filter(t => t.category === catF);
  if (q) {
    txns = txns.filter(t => 
       t.category.toLowerCase().includes(q) || 
       (t.desc && t.desc.toLowerCase().includes(q))
    );
  }

  // Summary
  const inTot = txns.filter(t=>t.type==='pemasukan').reduce((s,t)=>s+t.amount, 0);
  const outTot = txns.filter(t=>t.type==='pengeluaran').reduce((s,t)=>s+t.amount, 0);
  
  document.getElementById('hs-total').textContent = `${txns.length} transaksi`;
  document.getElementById('hs-in').textContent = formatRupiah(inTot);
  document.getElementById('hs-out').textContent = formatRupiah(outTot);

  const list = document.getElementById('history-txn-list');
  if (txns.length === 0) {
    list.innerHTML = `<div class="empty-state"><div class="empty-icon">📭</div><p>Tidak ada transaksi sesuai filter.</p></div>`;
  } else {
    // Group by Date
    const grouped = {};
    txns.forEach(t => {
       if(!grouped[t.date]) grouped[t.date] = [];
       grouped[t.date].push(t);
    });

    let html = '';
    Object.keys(grouped).sort((a,b) => new Date(b) - new Date(a)).forEach(dateStr => {
       html += `<div style="padding: 10px 16px; background: rgba(0,0,0,0.2); border-radius: 8px; margin-top: 16px; margin-bottom: 8px; font-weight: 600; font-size: 0.85rem; color: var(--primary-light)">${formatDate(dateStr)}</div>`;
       grouped[dateStr].forEach(t => {
          html += createTxnHTML(t);
       });
    });

    list.innerHTML = html;
  }
}

/* ===== REPORT ===== */
function updateReport(period) {
   const txns = filterTransactions(period);
   
   const inTot = txns.filter(t=>t.type==='pemasukan').reduce((s,t)=>s+t.amount, 0);
   const outTot = txns.filter(t=>t.type==='pengeluaran').reduce((s,t)=>s+t.amount, 0);
   const profit = inTot - outTot;

   document.getElementById('rsum-in').textContent = formatRupiah(inTot);
   document.getElementById('rsum-out').textContent = formatRupiah(outTot);
   document.getElementById('rsum-profit').textContent = formatRupiah(profit);
   
   const pEl = document.getElementById('rsum-profit');
   if(profit < 0) {
      pEl.style.color = 'var(--red)';
   } else {
      pEl.style.color = 'var(--primary-light)';
   }

   setTimeout(() => {
     renderCharts(period);
     generateInsights(period);
   }, 100);
}

/* ===== SETTINGS ===== */
function renderSettingsCategories(type) {
   const cats = appData.categories[type];
   const list = document.getElementById('cat-list');
   if(!list) return;

   list.innerHTML = cats.map(c => `
      <div class="cat-item">
         <span>${c.icon} ${c.name}</span>
         <button class="btn-del" onclick="deleteCategory('${type}', '${c.id}')">✕</button>
      </div>
   `).join('');
}

function deleteCategory(type, id) {
   appData.categories[type] = appData.categories[type].filter(c => c.id !== id);
   saveData(appData);
   renderSettingsCategories(type);
   populateCategorySelect();
   populateFilterCat();
}

/* ===== UTILS ===== */
function showToast(msg) {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

function showConfirm(title, msg, onOk) {
  const overlay = document.getElementById('confirm-modal');
  document.getElementById('confirm-title').textContent = title;
  document.getElementById('confirm-body').textContent = msg;
  
  const btnOk = document.getElementById('confirm-ok');
  const btnCancel = document.getElementById('confirm-cancel');
  
  const close = () => {
     overlay.classList.remove('active');
     btnOk.replaceWith(btnOk.cloneNode(true)); // remove listeners
     btnCancel.replaceWith(btnCancel.cloneNode(true));
  };
  
  overlay.classList.add('active');
  
  document.getElementById('confirm-cancel').addEventListener('click', close);
  document.getElementById('confirm-ok').addEventListener('click', () => {
     onOk();
     close();
  });
}

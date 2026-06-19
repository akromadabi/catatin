/**
 * app.js - UI Logic for Redesigned App
 */

let currentPage = 'home';

window.escapeHtml = function(unsafe) {
    if (unsafe == null) return '';
    return unsafe.toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
};
let mainChart = null;
let barChart = null;
let currentChartView = 'bar';
let currentBreakdownTab = 'pengeluaran';
let editTxnId = null;
let currentDateOffset = 0;

document.addEventListener('DOMContentLoaded', () => {
    initUI();
    initEvents();
    initVoice();
    updateDashboard();

    // History API (Back button) support
    window.addEventListener('popstate', (e) => {
        // Close any open modal first before navigating pages
        if (e.state && e.state.modal) {
            closeModalByName(e.state.modal);
            return;
        }
        if (closeAnyOpenModal()) return;
        if (e.state && e.state.pageId) {
            navigateTo(e.state.pageId, false);
        } else {
            navigateTo('home', false);
        }
    });

    const initialHash = window.location.hash.replace('#', '');
    if (initialHash && document.getElementById('page-' + initialHash)) {
        history.replaceState({ pageId: initialHash }, '', '#' + initialHash);
        navigateTo(initialHash, false);
    } else {
        history.replaceState({ pageId: 'home' }, '', '#home');
        navigateTo('home', false);
    }
});

// Modal History Helpers (Global Scope)
function pushModalHistory(modalName) {
    history.pushState({ modal: modalName, pageId: window.location.hash.replace('#', '') || 'home' }, '', '#modal-' + modalName);
    document.getElementById(modalName).setAttribute('data-history-pushed', 'true');
}

function closeModalByName(modalName) {
    if (modalName === 'edit-cat-modal') closeEditCatModal(true);
    else if (modalName === 'add-cat-modal') closeAddCatModal(true);
}

function closeAnyOpenModal() {
    const modals = ['edit-cat-modal', 'add-cat-modal'];
    for (let m of modals) {
        const el = document.getElementById(m);
        // Check if open but without history flag (meaning history already popped)
        if (el && el.style.display !== 'none' && !el.hasAttribute('data-history-pushed')) {
            closeModalByName(m);
            return true;
        }
    }
    return false;
}

function initUI() {
    populateCategorySelect();
    populateFilterCat();
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('txn-date');
    if(dateInput) dateInput.value = today;
}

function initEvents() {
    // Navigation
    document.querySelectorAll('.bottom-nav-item, #nav-add').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            navigateTo(el.dataset.page);
        });
    });

    updateDashboardFilterLabel();

    // Add Form Submit
    const form = document.getElementById('txn-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            handleManualSubmit();
        });
    }

    // Analytics Filters
    document.querySelectorAll('.pill-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pill-tab').forEach(b => b.classList.remove('active', 'text-slate-900'));
            document.querySelectorAll('.pill-tab').forEach(b => b.classList.add('text-slate-500'));
            
            btn.classList.add('active', 'text-slate-900');
            btn.classList.remove('text-slate-500');
            
            updateAnalytics(btn.dataset.period);
        });
    });

    // Close sort menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#sort-btn') && !e.target.closest('#sort-menu')) {
            document.getElementById('sort-menu')?.classList.add('hidden');
        }
    });

    // Voice Modal Close
    document.getElementById('mic-stop-btn')?.addEventListener('click', stopRecording);
}

function toggleSortMenu() {
    const menu = document.getElementById('sort-menu');
    menu?.classList.toggle('hidden');
}

function setSortOption(val) {
    document.getElementById('wallet-sort').value = val;
    document.getElementById('sort-menu')?.classList.add('hidden');
    
    // Update icon to reflect active sort type
    const icons = { newest: 'sort-amount-down', oldest: 'sort-amount-up', highest: 'arrow-up', lowest: 'arrow-down', az: 'sort-alpha-down', za: 'sort-alpha-up' };
    const icon = document.querySelector('#sort-btn i');
    if(icon) icon.className = `fas fa-${icons[val] || 'sort-amount-down'} text-xs`;
    
    // Highlight active option
    document.querySelectorAll('.sort-opt').forEach(btn => btn.classList.remove('text-brand-600', 'bg-brand-50'));
    const activeBtn = document.querySelector(`.sort-opt[onclick="setSortOption('${val}')"]`);
    if(activeBtn) activeBtn.classList.add('text-brand-600', 'bg-brand-50');
    
    updateWallet();
}

function navigateTo(pageId, pushToHistory = true) {

    // Nav active state
    document.querySelectorAll('.bottom-nav-item').forEach(el => el.classList.remove('active'));
    const navItem = document.getElementById('nav-' + pageId);
    if(navItem && navItem.classList.contains('bottom-nav-item')) {
        navItem.classList.add('active');
    }

    // Page active state
    document.querySelectorAll('.page').forEach(el => el.classList.remove('active'));
    const targetPage = document.getElementById('page-' + pageId);
    if (targetPage) targetPage.classList.add('active');

    if (pushToHistory) {
        history.pushState({ pageId: pageId }, '', '#' + pageId);
    }

    // Remove the logic that hides the center mic button
    // It should stay visible so the navigation bar layout doesn't break.
    
    if (pageId === 'add' && !window.isEditingTxn) {
        editTxnId = null;
        const form = document.getElementById('txn-form');
        if(form) form.reset();
        const dateInput = document.getElementById('txn-date');
        if(dateInput) dateInput.value = new Date().toISOString().split('T')[0];
    }
    window.isEditingTxn = false;

    currentPage = pageId;

    if (pageId === 'home') updateDashboard();
    if (pageId === 'analytics') updateAnalytics('month');
    if (pageId === 'wallet') updateWallet();
    if (pageId === 'categories') updateCategoriesPage();
    
    // Scroll to top
    document.getElementById('main-scroll').scrollTop = 0;
}

function setTxnType(type) {
    document.getElementById('txn-type').value = type;
    
    const btnOut = document.getElementById('btn-type-out');
    const btnIn = document.getElementById('btn-type-in');
    
    if(type === 'pengeluaran') {
        btnOut.classList.add('active', 'bg-white', 'shadow-sm', 'text-slate-900');
        btnOut.classList.remove('text-slate-500');
        
        btnIn.classList.remove('active', 'bg-white', 'shadow-sm', 'text-slate-900');
        btnIn.classList.add('text-slate-500');
    } else {
        btnIn.classList.add('active', 'bg-white', 'shadow-sm', 'text-slate-900');
        btnIn.classList.remove('text-slate-500');
        
        btnOut.classList.remove('active', 'bg-white', 'shadow-sm', 'text-slate-900');
        btnOut.classList.add('text-slate-500');
    }
    
    populateCategorySelect();
}

function populateCategorySelect() {
    const type = document.getElementById('txn-type').value;
    const select = document.getElementById('txn-category');
    if(!select) return;

    const cats = appData.categories[type] || [];
    select.innerHTML = '<option value="">Pilih Kategori</option>';
    cats.forEach(c => {
        // Don't include icon class string in option text - only show name
        select.innerHTML += `<option value="${c.name}">${c.name}</option>`;
    });
}


function populateFilterCat() {
    const select = document.getElementById('wallet-filter-cat');
    if(!select) return;
    select.innerHTML = '<option value="all">Semua Kategori</option>';
    appData.categories.pemasukan.forEach(c => select.innerHTML += `<option value="${c.name}">${c.name}</option>`);
    appData.categories.pengeluaran.forEach(c => select.innerHTML += `<option value="${c.name}">${c.name}</option>`);
}

function updateWalletFilterCat() {
    const typeF = document.getElementById('wallet-filter-type').value;
    const catSelect = document.getElementById('wallet-filter-cat');
    if(!catSelect) return;
    
    catSelect.innerHTML = '<option value="all">Semua Kategori</option>';
    
    if (typeF === 'all' || typeF === 'pemasukan') {
        appData.categories.pemasukan.forEach(c => catSelect.innerHTML += `<option value="${c.name}">${c.name}</option>`);
    }
    if (typeF === 'all' || typeF === 'pengeluaran') {
        appData.categories.pengeluaran.forEach(c => catSelect.innerHTML += `<option value="${c.name}">${c.name}</option>`);
    }
}

function formatCurrencyInput(input) {
    let val = input.value.replace(/[^0-9]/g, '');
    if (val !== '') {
        val = parseInt(val, 10).toLocaleString('id-ID');
    }
    input.value = val;
}

// Pending transaction data saat modal duplikat terbuka
let _pendingTxnData = null;

function handleManualSubmit() {
    const type = document.getElementById('txn-type').value;
    const amountStr = document.getElementById('txn-amount').value.replace(/\./g, '');
    const amount = parseFloat(amountStr);
    const category = document.getElementById('txn-category').value;
    const desc = document.getElementById('txn-desc').value;
    const date = document.getElementById('txn-date').value;

    if (!amount || !category || !date) {
        showToast('Harap isi semua kolom');
        return;
    }

    // Mode edit: langsung update, tidak perlu cek duplikat
    if (editTxnId) {
        updateTransaction(editTxnId, { type, amount, category, desc, date });
        showToast('Transaksi berhasil diperbarui!');
        editTxnId = null;
        document.getElementById('txn-form').reset();
        document.getElementById('txn-amount').value = '';
        document.getElementById('txn-date').value = new Date().toISOString().split('T')[0];
        updateDashboard();
        navigateTo('home');
        return;
    }

    // Cek duplikat sebelum simpan
    const similarTxns = findSimilarTransactions({ type, amount, date });
    if (similarTxns.length > 0) {
        // Simpan data sementara, tampilkan modal
        _pendingTxnData = { type, amount, category, desc, date };
        showDuplicateModal(_pendingTxnData, similarTxns);
        return;
    }

    // Tidak ada duplikat, langsung simpan
    doSaveTransaction({ type, amount, category, desc, date });
}

/**
 * Cari transaksi serupa:
 * - Tipe sama (pengeluaran / pemasukan)
 * - Nominal dalam toleransi ±20%
 * - Tercatat dalam 30 hari terakhir dari tanggal yang diinput
 */
function findSimilarTransactions({ type, amount, date }) {
    const TOLERANCE = 0.20; // 20%
    const DAYS_RANGE = 30;

    const inputDate = new Date(date);
    const cutoffDate = new Date(inputDate);
    cutoffDate.setDate(cutoffDate.getDate() - DAYS_RANGE);

    const low  = amount * (1 - TOLERANCE);
    const high = amount * (1 + TOLERANCE);

    return (appData.transactions || []).filter(t => {
        if (t.type !== type) return false;
        const tAmount = parseFloat(t.amount);
        if (tAmount < low || tAmount > high) return false;
        const tDate = new Date(t.date);
        if (tDate < cutoffDate || tDate > inputDate) return false;
        return true;
    });
}

function showDuplicateModal(newTxn, similarList) {
    const modal = document.getElementById('duplicate-txn-modal');
    if (!modal) return;

    // Isi detail transaksi baru
    const typeColor = newTxn.type === 'pengeluaran' ? '#ef4444' : '#10b981';
    const typePrefix = newTxn.type === 'pengeluaran' ? '−' : '+';
    const fmt = v => 'Rp ' + Number(v).toLocaleString('id-ID');
    const fmtDate = d => {
        const dt = new Date(d);
        return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    };

    document.getElementById('dup-new-category').textContent = newTxn.category;
    document.getElementById('dup-new-date').textContent = fmtDate(newTxn.date);
    document.getElementById('dup-new-amount').innerHTML =
        `<span style="color:${typeColor}">${typePrefix} ${fmt(newTxn.amount)}</span>`;

    // Isi daftar transaksi serupa
    const list = document.getElementById('dup-similar-list');
    list.innerHTML = similarList.slice(0, 5).map(t => {
        const tc = t.type === 'pengeluaran' ? '#ef4444' : '#10b981';
        const tp = t.type === 'pengeluaran' ? '−' : '+';
        const selisihPct = Math.abs((parseFloat(t.amount) - newTxn.amount) / newTxn.amount * 100).toFixed(0);
        const selisihLabel = selisihPct == 0
            ? '<span class="text-[9px] bg-rose-100 text-rose-600 font-bold px-1.5 py-0.5 rounded-full ml-1">Sama persis</span>'
            : `<span class="text-[9px] bg-amber-100 text-amber-600 font-bold px-1.5 py-0.5 rounded-full ml-1">Selisih ${selisihPct}%</span>`;

        return `<div class="flex items-center justify-between bg-amber-50 border border-amber-100 rounded-2xl px-3.5 py-2.5">
            <div class="min-w-0">
                <div class="flex items-center flex-wrap gap-1">
                    <p class="font-bold text-slate-800 text-sm truncate">${t.category || '—'}</p>
                    ${selisihLabel}
                </div>
                <p class="text-xs text-slate-400 mt-0.5">${fmtDate(t.date)}${t.desc ? ' · ' + t.desc : ''}</p>
            </div>
            <p class="font-bold text-sm ml-3 shrink-0" style="color:${tc}">${tp} ${fmt(t.amount)}</p>
        </div>`;
    }).join('');

    // Tampilkan modal
    modal.style.removeProperty('display');
    modal.style.display = 'flex';
}

function closeDuplicateModal() {
    const modal = document.getElementById('duplicate-txn-modal');
    if (modal) modal.style.display = 'none';
    _pendingTxnData = null;
}

function confirmSaveDuplicate() {
    closeDuplicateModal();
    if (_pendingTxnData) {
        doSaveTransaction(_pendingTxnData);
        _pendingTxnData = null;
    }
}

function doSaveTransaction(txnData) {
    addTransaction(txnData);
    showToast('Transaksi berhasil disimpan!');

    document.getElementById('txn-form').reset();
    document.getElementById('txn-amount').value = '';
    document.getElementById('txn-date').value = new Date().toISOString().split('T')[0];

    updateDashboard();
    navigateTo('home');
}



let isBalanceHidden = localStorage.getItem('hideBalance') === 'true';

function applyBalanceVisibility() {
    const hiddenText = 'Rp •••••••';
    const balanceEl = document.getElementById('home-balance');
    const incomeEl = document.getElementById('home-income');
    const expenseEl = document.getElementById('home-expense');
    const eyeIcon = document.getElementById('toggle-eye-icon');
    
    if(isBalanceHidden) {
        if(balanceEl) balanceEl.textContent = hiddenText;
        if(incomeEl) incomeEl.textContent = hiddenText;
        if(expenseEl) expenseEl.textContent = hiddenText;
        if(eyeIcon) {
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        }
    } else {
        if(balanceEl) balanceEl.textContent = balanceEl.dataset.value;
        if(incomeEl) incomeEl.textContent = incomeEl.dataset.value;
        if(expenseEl) expenseEl.textContent = expenseEl.dataset.value;
        if(eyeIcon) {
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }
}

function toggleBalance() {
    isBalanceHidden = !isBalanceHidden;
    localStorage.setItem('hideBalance', isBalanceHidden);
    applyBalanceVisibility();
}

let dashboardFilter = localStorage.getItem('dashboardFilter') || 'all';

function updateDashboardFilterLabel() {
    const labels = { 'all': 'Semua Waktu', 'year': 'Tahun Ini', 'month': 'Bulan Ini', 'week': 'Minggu Ini', 'today': 'Hari Ini' };
    const labelEl = document.getElementById('dashboard-filter-label');
    if (labelEl) labelEl.textContent = labels[dashboardFilter] || 'Semua Waktu';
}

function selectDashboardFilter(val) {
    dashboardFilter = val;
    localStorage.setItem('dashboardFilter', dashboardFilter);
    updateDashboard();
    updateDashboardFilterLabel();
    closeDashboardFilterModal();
}

function openDashboardFilterModal() {
    const modal = document.getElementById('dashboard-filter-modal');
    const content = document.getElementById('dashboard-filter-modal-content');
    if(!modal) return;
    
    // Update checkmarks
    document.querySelectorAll('.dashboard-filter-btn').forEach(btn => {
        const icon = btn.querySelector('i');
        if (btn.dataset.val === dashboardFilter) {
            btn.classList.add('bg-brand-50', 'text-brand-700');
            if(icon) icon.classList.remove('hidden');
        } else {
            btn.classList.remove('bg-brand-50', 'text-brand-700');
            if(icon) icon.classList.add('hidden');
        }
    });

    modal.style.setProperty('display', 'flex', 'important');
    setTimeout(() => {
        if(content) content.classList.remove('translate-y-full');
    }, 10);
}

function closeDashboardFilterModal() {
    const modal = document.getElementById('dashboard-filter-modal');
    const content = document.getElementById('dashboard-filter-modal-content');
    if(!modal) return;
    
    if(content) content.classList.add('translate-y-full');
    setTimeout(() => {
        modal.style.setProperty('display', 'none', 'important');
    }, 300);
}

/* ================= HOME ================= */
function updateDashboard() {
    const txns = filterTransactions(dashboardFilter);
    const { totalIn, totalOut, balance } = calculateBalances(txns);
    
    const badge = document.getElementById('home-filter-badge');
    if (badge) {
        if (dashboardFilter === 'all') {
            badge.classList.add('hidden');
        } else {
            badge.classList.remove('hidden');
            const labels = { 'year': 'Tahun Ini', 'month': 'Bulan Ini', 'week': 'Minggu Ini', 'today': 'Hari Ini' };
            badge.textContent = labels[dashboardFilter];
        }
    }
    
    const balanceEl = document.getElementById('home-balance');
    if(balanceEl) balanceEl.dataset.value = formatRupiah(balance);
    
    const incomeEl = document.getElementById('home-income');
    if(incomeEl) incomeEl.dataset.value = formatRupiah(totalIn);
    
    const expenseEl = document.getElementById('home-expense');
    if(expenseEl) expenseEl.dataset.value = formatRupiah(totalOut);
    
    applyBalanceVisibility();
    
    // Recent Txns
    const list = document.getElementById('home-recent-list');
    const recent = appData.transactions.slice(0, 4);
    
    if(recent.length === 0) {
        list.innerHTML = `<div class="p-6 text-center text-slate-500 text-sm">Belum ada transaksi.</div>`;
    } else {
        // No delete button on home page
        list.innerHTML = recent.map(t => createTxnListItem(t, false)).join('');
    }
}

/* ================= ANALYTICS ================= */
function updateAnalytics(period) {
    const txns = filterTransactions(period, currentDateOffset);
    
    const { totalIn, totalOut } = calculateBalances(txns);

    document.getElementById('analytics-spent').textContent = formatRupiah(totalOut);
    document.getElementById('analytics-income').textContent = formatRupiah(totalIn);

    // Save current active period
    document.querySelectorAll('.pill-tab').forEach(b => {
        if(b.classList.contains('active')) {
            b.dataset.active = 'true';
        } else {
            b.dataset.active = 'false';
        }
    });

    // Update date nav
    const nav = document.getElementById('analytics-date-nav');
    if (nav) {
        if (period === 'all') {
            nav.style.display = 'none';
        } else {
            nav.style.display = 'flex';
            updateDateLabel(period);
        }
    }

    renderBarChart(period);
    if (currentChartView === 'donut') {
        renderDonutChart(period);
    }
    renderBreakdown(period);
}

function setChartView(type) {
    currentChartView = type;
    
    // Update active tab styles
    document.querySelectorAll('.chart-tab').forEach(b => {
        if (b.dataset.chart === type) {
            b.classList.add('active', 'text-slate-800', 'bg-white', 'shadow-sm');
            b.classList.remove('text-slate-400');
        } else {
            b.classList.remove('active', 'text-slate-800', 'bg-white', 'shadow-sm');
            b.classList.add('text-slate-400');
        }
    });

    const breakdownList = document.getElementById('analytics-breakdown');
    const donutContainer = document.getElementById('analytics-donut-container');
    const donutLegend = document.getElementById('donut-legend');

    if (type === 'bar') {
        if (breakdownList) breakdownList.classList.remove('hidden');
        if (donutContainer) donutContainer.classList.add('hidden');
        if (donutLegend) donutLegend.classList.add('hidden');
    } else {
        if (breakdownList) breakdownList.classList.add('hidden');
        if (donutContainer) donutContainer.classList.remove('hidden');
        if (donutLegend) donutLegend.classList.remove('hidden');
    }

    // Determine the active period
    let activePeriod = 'month';
    document.querySelectorAll('.pill-tab').forEach(b => {
        if (b.classList.contains('active')) activePeriod = b.dataset.period;
    });

    // Re-render chart if needed
    if (type === 'donut') {
        renderDonutChart(activePeriod);
    }
}

function renderBarChart(period) {
    const ctx = document.getElementById('bar-chart-canvas');
    if(!ctx) return;

    if(barChart) barChart.destroy();
    
    const txns = filterTransactions(period, currentDateOffset);
    
    let labels = [];
    let outData = [];
    let inData = [];
    
    if (period === 'week') {
        labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        outData = Array(7).fill(0);
        inData = Array(7).fill(0);
        txns.forEach(t => {
            const d = new Date(t.date).getDay();
            const idx = d === 0 ? 6 : d - 1; // Mon=0, Sun=6
            const amt = parseFloat(t.amount) || 0;
            if(t.type === 'pengeluaran') outData[idx] += amt;
            else inData[idx] += amt;
        });
    } else if (period === 'month') {
        const now = new Date();
        const targetDate = new Date(now.getFullYear(), now.getMonth() + currentDateOffset, 1);
        const daysInMonth = new Date(targetDate.getFullYear(), targetDate.getMonth() + 1, 0).getDate();
        labels = Array.from({length: daysInMonth}, (_, i) => i + 1);
        outData = Array(daysInMonth).fill(0);
        inData = Array(daysInMonth).fill(0);
        txns.forEach(t => {
            const idx = new Date(t.date).getDate() - 1;
            const amt = parseFloat(t.amount) || 0;
            if(t.type === 'pengeluaran') outData[idx] += amt;
            else inData[idx] += amt;
        });
    } else if (period === 'year') {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        labels = [...months];
        outData = Array(12).fill(0);
        inData = Array(12).fill(0);
        txns.forEach(t => {
            const idx = new Date(t.date).getMonth();
            const amt = parseFloat(t.amount) || 0;
            if(t.type === 'pengeluaran') outData[idx] += amt;
            else inData[idx] += amt;
        });
    } else {
        const years = txns.map(t => new Date(t.date).getFullYear());
        const minYear = years.length ? Math.min(...years) : new Date().getFullYear();
        const maxYear = years.length ? Math.max(...years) : new Date().getFullYear();
        for(let y = minYear; y <= maxYear; y++) {
            labels.push(y);
            outData.push(0);
            inData.push(0);
        }
        txns.forEach(t => {
            const y = new Date(t.date).getFullYear();
            const idx = labels.indexOf(y);
            const amt = parseFloat(t.amount) || 0;
            if(idx > -1) {
                if(t.type === 'pengeluaran') outData[idx] += amt;
                else inData[idx] += amt;
            }
        });
    }

    // Dynamic legend based on currentBreakdownTab
    const barLegend = document.getElementById('bar-legend');
    if (barLegend) {
        if (currentBreakdownTab === 'pengeluaran') {
            barLegend.innerHTML = '<span class="w-2 h-2 rounded-full bg-brand-600"></span> Pengeluaran';
        } else {
            barLegend.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pemasukan';
        }
    }

    barChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pengeluaran',
                    data: outData,
                    backgroundColor: '#2563eb',
                    hidden: currentBreakdownTab !== 'pengeluaran',
                    borderRadius: 4,
                    barPercentage: 0.6
                },
                {
                    label: 'Pemasukan',
                    data: inData,
                    backgroundColor: '#10b981',
                    hidden: currentBreakdownTab !== 'pemasukan',
                    borderRadius: 4,
                    barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, stacked: true, border: {display: false} },
                y: { display: false, stacked: true }
            }
        }
    });
}

function changeAnalyticsOffset(dir) {
    currentDateOffset += dir;
    let activePeriod = 'month';
    document.querySelectorAll('.pill-tab').forEach(b => {
        if(b.classList.contains('active')) activePeriod = b.dataset.period;
    });
    updateAnalytics(activePeriod);
}

function updateDateLabel(period) {
    const label = document.getElementById('analytics-date-label');
    const now = new Date();
    const btnNext = document.getElementById('btn-next-date');
    
    // Disable next button if offset >= 0
    if (currentDateOffset >= 0) {
        currentDateOffset = 0;
        if(btnNext) {
            btnNext.style.opacity = '0.3';
            btnNext.style.pointerEvents = 'none';
        }
    } else {
        if(btnNext) {
            btnNext.style.opacity = '1';
            btnNext.style.pointerEvents = 'auto';
        }
    }

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    if (period === 'week') {
        const end = new Date(now.getTime() + (currentDateOffset * 7 * 24 * 60 * 60 * 1000));
        const start = new Date(end.getTime() - 6 * 24 * 60 * 60 * 1000); // 7 days inclusive
        label.textContent = `${start.getDate()} ${months[start.getMonth()]} - ${end.getDate()} ${months[end.getMonth()]}`;
    } else if (period === 'month') {
        const d = new Date(now.getFullYear(), now.getMonth() + currentDateOffset, 1);
        label.textContent = `${months[d.getMonth()]} ${d.getFullYear()}`;
    } else if (period === 'year') {
        label.textContent = `${now.getFullYear() + currentDateOffset}`;
    }
}

function setBreakdownTab(type) {
    currentBreakdownTab = type;
    const btnOut = document.getElementById('btn-breakdown-out');
    const btnIn = document.getElementById('btn-breakdown-in');
    if(type === 'pengeluaran') {
        btnOut.classList.add('bg-white', 'shadow-sm', 'text-slate-900');
        btnOut.classList.remove('text-slate-500');
        btnIn.classList.remove('bg-white', 'shadow-sm', 'text-slate-900');
        btnIn.classList.add('text-slate-500');
    } else {
        btnIn.classList.add('bg-white', 'shadow-sm', 'text-slate-900');
        btnIn.classList.remove('text-slate-500');
        btnOut.classList.remove('bg-white', 'shadow-sm', 'text-slate-900');
        btnOut.classList.add('text-slate-500');
    }
    
    // Find active period
    let activePeriod = 'month';
    document.querySelectorAll('.pill-tab').forEach(b => {
        if(b.classList.contains('active')) activePeriod = b.dataset.period;
    });
    
    renderBarChart(activePeriod);
    if (currentChartView === 'donut') {
        renderDonutChart(activePeriod);
    }
    renderBreakdown(activePeriod);
}

function downloadReport() {
    showToast('Menyiapkan laporan PDF...');
    
    let activePeriod = 'month';
    document.querySelectorAll('.pill-tab').forEach(b => {
        if(b.classList.contains('active')) activePeriod = b.dataset.period;
    });
    const periodLabel = document.getElementById('analytics-date-label')?.textContent || 'Semua Waktu';

    const txns = filterTransactions(activePeriod, currentDateOffset);
    const { totalIn, totalOut } = calculateBalances(txns);
    const netto = totalIn - totalOut;
    const nettoColor = netto >= 0 ? '#059669' : '#e11d48';
    const nettoPrefix = netto >= 0 ? '+' : '-';

    const BRAND = '#1d4ed8';
    const GREEN = '#059669';
    const RED = '#e11d48';
    const GRAY = '#64748b';

    // Helper: format date nicely
    const fmtDate = (dateStr) => {
        const d = new Date(dateStr);
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    };

    // Helper: breakdown rows
    const buildBreakdownRows = (type) => {
        const typeTxns = txns.filter(t => t.type === type);
        let total = 0;
        const catMap = {};
        typeTxns.forEach(t => {
            const amt = parseFloat(t.amount) || 0;
            catMap[t.category] = (catMap[t.category] || 0) + amt;
            total += amt;
        });
        if (total === 0) return [{ text: 'Tidak ada data', italics: true, color: '#94a3b8', fontSize: 8, margin: [6, 4, 0, 4] }];
        return Object.entries(catMap)
            .sort((a,b) => b[1]-a[1])
            .map(([cat, amt], idx) => {
                const pct = Math.round((amt/total)*100);
                return {
                    table: {
                        widths: ['*', 30, 80],
                        body: [[
                            { text: cat, fontSize: 8.5, color: '#1e293b', border: [false,false,false,false] },
                            { text: `${pct}%`, fontSize: 8, color: GRAY, alignment: 'right', border: [false,false,false,false] },
                            { text: formatRupiah(amt), fontSize: 8.5, bold: true, alignment: 'right', color: type === 'pemasukan' ? GREEN : RED, border: [false,false,false,false] }
                        ]]
                    },
                    layout: 'noBorders',
                    fillColor: idx % 2 === 0 ? '#f8fafc' : '#ffffff',
                    margin: [0, 0, 0, 0]
                };
            });
    };

    // Helper: build separate transaction table by type
    const buildTypeTxnTable = (type) => {
        const filtered = [...txns]
            .filter(t => t.type === type)
            .sort((a,b) => new Date(b.date) - new Date(a.date));

        const isOut = type === 'pengeluaran';
        const accentColor = isOut ? RED : GREEN;

        const header = [
            { text: 'Tanggal', bold: true, fontSize: 8, color: '#ffffff', fillColor: accentColor },
            { text: 'Kategori', bold: true, fontSize: 8, color: '#ffffff', fillColor: accentColor },
            { text: 'Catatan', bold: true, fontSize: 8, color: '#ffffff', fillColor: accentColor },
            { text: 'Nominal', bold: true, fontSize: 8, color: '#ffffff', fillColor: accentColor, alignment: 'right' }
        ];

        if (filtered.length === 0) {
            return [header, [
                { text: 'Tidak ada data', colSpan: 4, alignment: 'center', italics: true, color: GRAY, fontSize: 8 },
                {}, {}, {}
            ]];
        }

        const rows = filtered.map((t, idx) => [
            { text: fmtDate(t.date), fontSize: 8, color: '#374151', fillColor: idx % 2 === 0 ? '#f9fafb' : '#ffffff' },
            { text: t.category, fontSize: 8, color: '#374151', fillColor: idx % 2 === 0 ? '#f9fafb' : '#ffffff' },
            { text: t.desc || '-', fontSize: 8, color: GRAY, fillColor: idx % 2 === 0 ? '#f9fafb' : '#ffffff' },
            { text: (isOut ? '- ' : '+ ') + parseFloat(t.amount).toLocaleString('id-ID'), fontSize: 8, bold: true, alignment: 'right', color: accentColor, fillColor: idx % 2 === 0 ? '#f9fafb' : '#ffffff' }
        ]);

        return [header, ...rows];
    };

    const tableLayout = {
        hLineWidth: () => 0.5,
        vLineWidth: () => 0,
        hLineColor: () => '#e5e7eb',
        paddingTop: () => 5,
        paddingBottom: () => 5,
        paddingLeft: () => 6,
        paddingRight: () => 6,
    };

    const totalInTxns = txns.filter(t => t.type === 'pemasukan').length;
    const totalOutTxns = txns.filter(t => t.type === 'pengeluaran').length;

    const docDef = {
        pageSize: 'A4',
        pageMargins: [36, 36, 36, 50],

        footer: (currentPage, pageCount) => ({
            columns: [
                { text: 'Catat-in — Laporan Keuangan Pribadi', fontSize: 7, color: GRAY, margin: [36, 0, 0, 0] },
                { text: `Halaman ${currentPage} dari ${pageCount}`, fontSize: 7, color: GRAY, alignment: 'right', margin: [0, 0, 36, 0] }
            ]
        }),

        content: [
            // ─── HEADER BANNER ───
            {
                table: {
                    widths: ['*'],
                    body: [[{
                        stack: [
                            { text: 'Catat-in', fontSize: 10, color: '#93c5fd', margin: [0, 0, 0, 4] },
                            { text: 'LAPORAN KEUANGAN', fontSize: 22, bold: true, color: '#ffffff' },
                            { text: 'Periode: ' + periodLabel, fontSize: 10, color: '#bfdbfe', italics: true, margin: [0, 4, 0, 0] }
                        ],
                        fillColor: BRAND,
                        border: [false, false, false, false],
                        margin: [16, 20, 16, 20]
                    }]]
                },
                layout: 'noBorders',
                margin: [0, 0, 0, 20]
            },

            // ─── SUMMARY CARDS ───
            {
                columns: [
                    {
                        table: { widths: ['*'], body: [[{ stack: [
                            { text: 'PEMASUKAN', fontSize: 8, color: GREEN, bold: true, margin: [0,0,0,5] },
                            { text: formatRupiah(totalIn), fontSize: 14, bold: true, color: GREEN },
                            { text: `${totalInTxns} transaksi`, fontSize: 7.5, color: GRAY, margin: [0,3,0,0] }
                        ], border: [false,false,false,false], margin: [10,10,10,10], fillColor: '#f0fdf4' }]] },
                        layout: 'noBorders'
                    },
                    { width: 6, text: '' },
                    {
                        table: { widths: ['*'], body: [[{ stack: [
                            { text: 'PENGELUARAN', fontSize: 8, color: RED, bold: true, margin: [0,0,0,5] },
                            { text: formatRupiah(totalOut), fontSize: 14, bold: true, color: RED },
                            { text: `${totalOutTxns} transaksi`, fontSize: 7.5, color: GRAY, margin: [0,3,0,0] }
                        ], border: [false,false,false,false], margin: [10,10,10,10], fillColor: '#fff1f2' }]] },
                        layout: 'noBorders'
                    },
                    { width: 6, text: '' },
                    {
                        table: { widths: ['*'], body: [[{ stack: [
                            { text: 'SELISIH NETTO', fontSize: 8, color: nettoColor, bold: true, margin: [0,0,0,5] },
                            { text: nettoPrefix + ' ' + formatRupiah(Math.abs(netto)), fontSize: 14, bold: true, color: nettoColor },
                            { text: netto >= 0 ? 'Keuangan positif' : 'Defisit!', fontSize: 7.5, color: GRAY, margin: [0,3,0,0] }
                        ], border: [false,false,false,false], margin: [10,10,10,10], fillColor: netto >= 0 ? '#f0fdf4' : '#fff1f2' }]] },
                        layout: 'noBorders'
                    }
                ],
                margin: [0, 0, 0, 22]
            },

            // ─── BREAKDOWN ───
            {
                columns: [
                    {
                        width: '48%',
                        stack: [
                            { table: { widths: ['*'], body: [[{ text: 'Rincian Pemasukan', fontSize: 9, bold: true, color: '#ffffff', fillColor: GREEN, border: [false,false,false,false], margin: [8,6,8,6] }]] }, layout: 'noBorders', margin: [0,0,0,0] },
                            ...buildBreakdownRows('pemasukan')
                        ]
                    },
                    { width: '4%', text: '' },
                    {
                        width: '48%',
                        stack: [
                            { table: { widths: ['*'], body: [[{ text: 'Rincian Pengeluaran', fontSize: 9, bold: true, color: '#ffffff', fillColor: RED, border: [false,false,false,false], margin: [8,6,8,6] }]] }, layout: 'noBorders', margin: [0,0,0,0] },
                            ...buildBreakdownRows('pengeluaran')
                        ]
                    }
                ],
                margin: [0, 0, 0, 24]
            },

            // ─── INCOME TRANSACTIONS ───
            { text: 'Daftar Transaksi Pemasukan', fontSize: 11, bold: true, color: GREEN, margin: [0, 0, 0, 6] },
            {
                table: { widths: [72, 72, '*', 72], headerRows: 1, body: buildTypeTxnTable('pemasukan') },
                layout: tableLayout,
                margin: [0, 0, 0, 20]
            },

            // ─── EXPENSE TRANSACTIONS ───
            { text: 'Daftar Transaksi Pengeluaran', fontSize: 11, bold: true, color: RED, margin: [0, 0, 0, 6] },
            {
                table: { widths: [72, 72, '*', 72], headerRows: 1, body: buildTypeTxnTable('pengeluaran') },
                layout: tableLayout,
                margin: [0, 0, 0, 8]
            }

        ],
        defaultStyle: { font: 'Roboto', color: '#1e293b', lineHeight: 1.3 }
    };

    const filename = `Laporan_Catatin_${periodLabel.replace(/\s+/g, '_')}.pdf`;
    try {
        pdfMake.createPdf(docDef).download(filename, () => {
            showToast('Laporan PDF berhasil diunduh!');
            logGenericActivityOnBackend('download_pdf', 'Project', { period: periodLabel });
        });
    } catch(err) {
        console.error('pdfmake error:', err);
        showToast('Gagal membuat PDF: ' + err.message);
    }
}

let showDonutLegend = false;
function toggleDonutLegend() {
    const cb = document.getElementById('toggle-donut-legend');
    if (cb) showDonutLegend = cb.checked;
    
    let activePeriod = 'month';
    document.querySelectorAll('.pill-tab').forEach(b => {
        if(b.classList.contains('active')) activePeriod = b.dataset.period;
    });
    renderDonutChart(activePeriod);
}




function renderDonutChart(period) {
    const ctx = document.getElementById('main-chart');
    if(!ctx) return;

    if(mainChart) mainChart.destroy();
    
    // Filter for donut chart
    const txns = filterTransactions(period, currentDateOffset).filter(t => t.type === currentBreakdownTab);
    
    let total = 0;
    const catMap = {};
    txns.forEach(t => {
        const amt = parseFloat(t.amount) || 0;
        catMap[t.category] = (catMap[t.category] || 0) + amt;
        total += amt;
    });

    // Sort by amount descending
    const sortedCats = Object.keys(catMap).sort((a, b) => catMap[b] - catMap[a]);
    
    const labels = sortedCats;
    const data = sortedCats.map(c => catMap[c]);
    
    // Dynamic distinct colors for categories
    const colors = ['#2563eb', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#10b981', '#06b6d4', '#64748b', '#3b82f6', '#d946ef', '#f472b6'];
    const bgColors = labels.map((_, i) => colors[i % colors.length]);

    // Update center text
    const totalEl = document.getElementById('donut-total');
    if (totalEl) totalEl.textContent = formatRupiah(total);

    // If legend is shown, we might want to hide the center text so it doesn't look too cramped
    // Or we just let chart.js render the legend
    const legendOptions = {
        display: showDonutLegend,
        position: 'right',
        labels: { boxWidth: 12, font: { size: 10 } }
    };

    if (total === 0) {
        // empty state
        mainChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Kosong'],
                datasets: [{ data: [1], backgroundColor: ['#f1f5f9'], borderWidth: 0 }]
            },
            options: {
                cutout: '75%', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });
        return;
    }

    mainChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: bgColors,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 4
            }]
        },
        options: {
            onClick: (e, elements) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const catName = labels[index];
                    openCategoryDetails(catName, period);
                }
            },
            responsive: true,
            maintainAspectRatio: false,
            cutout: showDonutLegend ? '60%' : '75%', // Shrink hole if legend is shown
            plugins: { 
                legend: legendOptions,
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const val = context.raw;
                            const perc = ((val / total) * 100).toFixed(1);
                            return ` Rp ${val.toLocaleString('id-ID')} (${perc}%)`;
                        }
                    }
                }
            }
        }
    });
}

function renderBreakdown(period) {
    const txns = filterTransactions(period, currentDateOffset).filter(t => t.type === currentBreakdownTab);
    const box = document.getElementById('analytics-breakdown');
    
    if(txns.length === 0) {
        box.innerHTML = `<p class="text-sm text-slate-500 text-center py-4">Tidak ada data</p>`;
        return;
    }
    
    const catMap = {};
    let total = 0;
    txns.forEach(t => {
        const amt = parseFloat(t.amount) || 0;
        catMap[t.category] = (catMap[t.category] || 0) + amt;
        total += amt;
    });
    
    const sorted = Object.entries(catMap).sort((a,b)=>b[1]-a[1]);
    
    let html = '';
    const colors = ['bg-brand-600', 'bg-purple-500', 'bg-emerald-500', 'bg-orange-500', 'bg-rose-500'];
    
    sorted.forEach((item, idx) => {
        const cat = item[0];
        const amt = item[1];
        const pct = Math.round((amt / total) * 100);
        const color = colors[idx % colors.length];
        
        const safeCat = cat.replace(/'/g, "\\'");
        const safePeriod = period.replace(/'/g, "\\'");
        
        html += `
        <div class="mb-5 cursor-pointer hover:bg-slate-50 p-2 -mx-2 rounded-xl transition" onclick="openCategoryDetails('${safeCat}', '${safePeriod}')">
            <div class="flex justify-between text-sm font-bold text-slate-900 mb-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full ${color}"></span>
                    ${cat}
                </div>
                <div class="flex items-center gap-2">
                    <span>${formatRupiah(amt)}</span>
                    <span class="text-xs text-slate-400 font-medium">${pct}%</span>
                </div>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2">
                <div class="${color} h-2 rounded-full" style="width: ${pct}%"></div>
            </div>
        </div>
        `;
    });
    
    box.innerHTML = html;
}

/* ================= CATEGORY DETAILS ================= */
function openCategoryDetails(catName, period) {
    const overlay = document.getElementById('category-detail-overlay');
    const modal = document.getElementById('category-detail-modal');
    if (!overlay || !modal) return;
    
    document.getElementById('cat-detail-title').textContent = catName;
    document.getElementById('cat-detail-subtitle').textContent = period;
    
    const txns = filterTransactions(period, currentDateOffset).filter(t => t.type === currentBreakdownTab && t.category === catName);
    txns.sort((a, b) => new Date(b.date) - new Date(a.date));
    
    const list = document.getElementById('cat-detail-list');
    
    if (txns.length === 0) {
        list.innerHTML = `<div class="p-6 text-center text-slate-500 text-sm">Tidak ada transaksi.</div>`;
    } else {
        const toShow = txns.slice(0, 50);
        list.innerHTML = toShow.map(t => createTxnListItem(t, false)).join('');
        if (txns.length > 50) {
            list.innerHTML += `<div class="p-4 text-center text-xs text-slate-400">Menampilkan 50 transaksi terbaru.</div>`;
        }
    }
    
    overlay.style.display = 'flex';
    void overlay.offsetWidth; // Force reflow
    overlay.style.opacity = '1';
    modal.style.transform = 'translateY(0)';
}

function closeCategoryDetails() {
    const overlay = document.getElementById('category-detail-overlay');
    const modal = document.getElementById('category-detail-modal');
    if (!overlay || !modal) return;
    
    overlay.style.opacity = '0';
    modal.style.transform = 'translateY(100%)';
    setTimeout(() => {
        overlay.style.display = 'none';
    }, 300);
}

/* ================= WALLET ================= */
let walletPage = 1;
const WALLET_PER_PAGE = 30;
let filteredWalletTxns = [];
let isWalletGrouped = true;

function toggleWalletGroup() {
    isWalletGrouped = !isWalletGrouped;
    const btn = document.getElementById('toggle-group-btn');
    const icon = document.getElementById('toggle-group-icon');
    const text = document.getElementById('toggle-group-text');
    
    if(btn && icon && text) {
        if(isWalletGrouped) {
            btn.classList.replace('bg-slate-100', 'bg-brand-50');
            btn.classList.replace('text-slate-600', 'text-brand-600');
            btn.classList.replace('border-slate-200', 'border-brand-100');
            icon.className = 'fas fa-calendar-check text-xs';
            text.textContent = 'Hari: Tampil';
        } else {
            btn.classList.replace('bg-brand-50', 'bg-slate-100');
            btn.classList.replace('text-brand-600', 'text-slate-600');
            btn.classList.replace('border-brand-100', 'border-slate-200');
            icon.className = 'fas fa-calendar-minus text-xs';
            text.textContent = 'Hari: Sembunyi';
        }
    }
    updateWallet();
}

function updateWallet() {
    let txns = [...appData.transactions];
    const typeF = document.getElementById('wallet-filter-type')?.value || 'all';
    const catF = document.getElementById('wallet-filter-cat')?.value || 'all';
    const sortVal = document.getElementById('wallet-sort')?.value || 'newest';
    const searchVal = document.getElementById('wallet-search-input')?.value?.toLowerCase() || '';
    
    if(typeF !== 'all') txns = txns.filter(t => t.type === typeF);
    if(catF !== 'all') txns = txns.filter(t => t.category === catF);
    if(searchVal) {
        txns = txns.filter(t => 
            (t.desc && t.desc.toLowerCase().includes(searchVal)) || 
            (t.category && t.category.toLowerCase().includes(searchVal))
        );
    }
    
    txns.sort((a, b) => {
        if (sortVal === 'newest') return new Date(b.date) - new Date(a.date);
        if (sortVal === 'oldest') return new Date(a.date) - new Date(b.date);
        if (sortVal === 'highest') return parseFloat(b.amount) - parseFloat(a.amount);
        if (sortVal === 'lowest') return parseFloat(a.amount) - parseFloat(b.amount);
        if (sortVal === 'az') return (a.desc || a.category).localeCompare(b.desc || b.category);
        if (sortVal === 'za') return (b.desc || b.category).localeCompare(a.desc || a.category);
        return 0;
    });
    
    filteredWalletTxns = txns;
    walletPage = 1;
    
    renderWalletList();
}

function renderWalletList() {
    const list = document.getElementById('wallet-txn-list');
    if(!list) return;
    
    if(filteredWalletTxns.length === 0) {
        list.innerHTML = `<div class="p-6 text-center text-slate-500 text-sm">Tidak ada transaksi ditemukan.</div>`;
        const loadMoreBtn = document.getElementById('wallet-load-more');
        if (loadMoreBtn) loadMoreBtn.remove();
        return;
    }
    
    const start = (walletPage - 1) * WALLET_PER_PAGE;
    const end = walletPage * WALLET_PER_PAGE;
    const toShow = filteredWalletTxns.slice(start, end);
    
    const sortVal = document.getElementById('wallet-sort')?.value || 'newest';
    const shouldGroup = (sortVal === 'newest' || sortVal === 'oldest') && isWalletGrouped;
    const daysArr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const monthsArr = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    let html = '';
    for (let i = 0; i < toShow.length; i++) {
        const t = toShow[i];
        
        if (shouldGroup) {
            const tDate = new Date(t.date);
            const y = tDate.getFullYear();
            const m = String(tDate.getMonth() + 1).padStart(2, '0');
            const d = String(tDate.getDate()).padStart(2, '0');
            const dateStr = `${y}-${m}-${d}`;
            
            let prevDateStr = null;
            if (i > 0) {
                const pDate = new Date(toShow[i-1].date);
                prevDateStr = `${pDate.getFullYear()}-${String(pDate.getMonth() + 1).padStart(2, '0')}-${String(pDate.getDate()).padStart(2, '0')}`;
            } else if (start > 0) {
                const pDate = new Date(filteredWalletTxns[start-1].date);
                prevDateStr = `${pDate.getFullYear()}-${String(pDate.getMonth() + 1).padStart(2, '0')}-${String(pDate.getDate()).padStart(2, '0')}`;
            }
            
            if (dateStr !== prevDateStr) {
                const dailyTxns = filteredWalletTxns.filter(tx => {
                    const txDate = new Date(tx.date);
                    return txDate.getFullYear() === y && String(txDate.getMonth()+1).padStart(2,'0') === m && String(txDate.getDate()).padStart(2,'0') === d;
                });
                let dayIn = 0;
                let dayOut = 0;
                dailyTxns.forEach(tx => {
                    if (tx.type === 'pemasukan') dayIn += parseFloat(tx.amount);
                    if (tx.type === 'pengeluaran') dayOut += parseFloat(tx.amount);
                });
                
                const dayName = daysArr[tDate.getDay()];
                const monthName = monthsArr[tDate.getMonth()];
                
                html += `
                <div class="flex items-center justify-between py-2.5 px-3 mt-3 first:mt-0 -mx-2 border-b-2 border-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex flex-col items-center justify-center text-center leading-none shrink-0 shadow-[inset_0_1px_2px_rgba(0,0,0,0.05)]">
                            <span class="text-[9px] font-bold text-slate-400 uppercase mb-0.5 tracking-wider">${dayName.substring(0,3)}</span>
                            <span class="text-base font-black text-slate-700 leading-none">${d}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-slate-700">${dayName}, ${d} ${monthName}</span>
                            <span class="text-[10px] font-semibold text-slate-400">${y}</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1 text-[10px] font-bold">
                        ${dayIn > 0 ? `<span class="text-emerald-500 bg-emerald-50 px-1.5 py-0.5 rounded-sm">+ ${formatRupiah(dayIn)}</span>` : ''}
                        ${dayOut > 0 ? `<span class="text-rose-500 bg-rose-50 px-1.5 py-0.5 rounded-sm">- ${formatRupiah(dayOut)}</span>` : ''}
                    </div>
                </div>
                `;
            }
        }
        
        html += createTxnListItem(t, true);
    }
    
    if (walletPage === 1) {
        list.innerHTML = html;
    } else {
        list.insertAdjacentHTML('beforeend', html);
    }
    
    let loadMoreBtn = document.getElementById('wallet-load-more');
    if (end < filteredWalletTxns.length) {
        if (!loadMoreBtn) {
            list.insertAdjacentHTML('afterend', `
                <div id="wallet-load-more" class="py-4 text-center">
                    <div class="inline-block animate-spin rounded-full h-5 w-5 border-b-2 border-brand-600"></div>
                </div>
            `);
        }
    } else {
        if (loadMoreBtn) loadMoreBtn.remove();
    }
}

function loadMoreWallet() {
    if (walletPage * WALLET_PER_PAGE < filteredWalletTxns.length) {
        walletPage++;
        renderWalletList();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const mainScroll = document.getElementById('main-scroll');
    if (mainScroll) {
        mainScroll.addEventListener('scroll', () => {
            if (currentPage === 'wallet') {
                if (mainScroll.scrollTop + mainScroll.clientHeight >= mainScroll.scrollHeight - 50) {
                    const loadMoreBtn = document.getElementById('wallet-load-more');
                    if (loadMoreBtn) {
                        loadMoreWallet();
                    }
                }
            }
        });
    }
});

function createTxnListItem(t, showDelete = false) {
    const isOut = t.type === 'pengeluaran';
    let icon = getCategoryIcon(t.category, t.type);
    
    // Convert old emojis to FA classes (for legacy data)
    const emojiToFa = {
        '🛍️': 'fas fa-store', '💰': 'fas fa-wallet', '✨': 'fas fa-star',
        '📦': 'fas fa-box', '⚡': 'fas fa-bolt', '👥': 'fas fa-users',
        '🛵': 'fas fa-motorcycle', '🏠': 'fas fa-home', '💸': 'fas fa-money-bill-wave'
    };
    if (emojiToFa[icon]) icon = emojiToFa[icon];
    
    const iconHtml = icon.startsWith('fas ') ? `<i class="${icon}"></i>` : icon;
    
    const amtColor = isOut ? 'text-rose-600' : 'text-emerald-500';
    const sign = isOut ? '' : '+';
    
    // Avatar background color based on text
    const bgColors = ['bg-rose-100 text-rose-600', 'bg-blue-100 text-blue-600', 'bg-emerald-100 text-emerald-600', 'bg-purple-100 text-purple-600', 'bg-orange-100 text-orange-600'];
    const colorIdx = t.category.charCodeAt(0) % bgColors.length;
    const bgClass = bgColors[colorIdx];
    
    const dateStr = new Date(t.date).toLocaleDateString('id-ID', {day:'2-digit', month:'short'});
    const actionBtns = showDelete ? `
        <div class="flex items-center ml-2">
            <button data-edit-id="${t.id}" class="btn-edit-txn text-blue-400 bg-blue-50 w-7 h-7 rounded-full flex items-center justify-center shrink-0 hover:bg-blue-100 transition mr-1">
                <i class="fas fa-pen text-[10px]"></i>
            </button>
            <button onclick="handleDelete('${t.id}')" class="text-rose-400 bg-rose-50 w-7 h-7 rounded-full flex items-center justify-center shrink-0 hover:bg-rose-100 transition">
                <i class="fas fa-trash text-[10px]"></i>
            </button>
        </div>` : '';
    
    return `
    <div class="flex items-center py-2.5 border-b border-slate-50 last:border-0 hover:bg-slate-50 transition">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-9 h-9 rounded-full ${bgClass} flex items-center justify-center text-base shrink-0">
                ${iconHtml}
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-bold text-slate-900 leading-tight truncate">${window.escapeHtml(t.desc) || window.escapeHtml(t.category)}</h4>
                <div class="flex items-center text-[11px] text-slate-500 mt-0.5">
                    <span class="truncate">${window.escapeHtml(t.category)} &bull; ${dateStr}</span>
                    ${window.isCollaborative && t.user && t.user.name ? `
                    <div class="w-4 h-4 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[8px] font-bold shrink-0 ml-1" title="${window.escapeHtml(t.user.name)}">
                        ${t.user.name.trim().split(/\s+/).length === 1 ? window.escapeHtml(t.user.name.trim().substring(0, 2).toUpperCase()) : window.escapeHtml((t.user.name.trim().split(/\s+/)[0][0] + t.user.name.trim().split(/\s+/).pop()[0]).toUpperCase())}
                    </div>
                    ` : ''}
                </div>
            </div>
        </div>
        <div class="flex items-center shrink-0 ml-2">
            <span class="text-sm font-bold ${amtColor} whitespace-nowrap">${sign}${formatRupiah(t.amount)}</span>
            ${actionBtns}
        </div>
    </div>
    `;
}

/* ================= EDIT TRANSACTION (rebuilt) ================= */
function openEditTxnModal(id) {
    const t = appData.transactions.find(x => String(x.id) === String(id));
    if (!t) {
        alert('Transaksi tidak ditemukan (id=' + id + ')');
        return;
    }

    // Populate hidden fields
    document.getElementById('edit-txn-id').value = id;
    document.getElementById('edit-txn-type').value = t.type || 'pengeluaran';

    // Amount
    document.getElementById('edit-txn-amount').value = t.amount ? parseFloat(t.amount).toLocaleString('id-ID') : '';

    // Category dropdown
    const catSelect = document.getElementById('edit-txn-category');
    catSelect.innerHTML = '';
    const cats = (appData.categories && appData.categories[t.type]) ? appData.categories[t.type] : [];
    cats.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.name;
        opt.textContent = c.name;
        if (c.name === t.category) opt.selected = true;
        catSelect.appendChild(opt);
    });

    // Description & Date
    document.getElementById('edit-txn-desc').value = t.desc || '';
    document.getElementById('edit-txn-date').value = t.date ? t.date.substring(0, 10) : '';

    // Show modal
    const modal = document.getElementById('edit-txn-modal');
    modal.style.display = 'flex';
}

function closeEditTxnModal() {
    const modal = document.getElementById('edit-txn-modal');
    modal.style.display = 'none';
    document.getElementById('edit-txn-form').reset();
}

function submitEditTxn(e) {
    e.preventDefault();
    const id = document.getElementById('edit-txn-id').value;
    const type = document.getElementById('edit-txn-type').value;
    const amountStr = document.getElementById('edit-txn-amount').value.replace(/[^0-9]/g, '');
    const amount = parseFloat(amountStr);
    const category = document.getElementById('edit-txn-category').value;
    const desc = document.getElementById('edit-txn-desc').value;
    const date = document.getElementById('edit-txn-date').value;

    if (!amount || !category || !date) {
        showToast('Harap isi semua kolom yang wajib');
        return;
    }

    updateTransaction(id, { type, amount, category, desc, date });
    showToast('Transaksi berhasil diperbarui!');
    closeEditTxnModal();
    updateWallet();
    updateDashboard();
    if (currentPage === 'analytics') updateAnalytics(document.querySelector('.pill-tab.active')?.dataset.period || 'month');
}

// Event delegation — attached once after DOM ready
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-edit-txn');
    if (btn) {
        const id = btn.getAttribute('data-edit-id');
        openEditTxnModal(id);
    }
});


function handleDelete(id) {
    if(confirm('Hapus transaksi ini?')) {
        deleteTransaction(id);
        if(currentPage === 'home') updateDashboard();
        if(currentPage === 'wallet') updateWallet();
        if(currentPage === 'analytics') updateAnalytics(document.querySelector('.pill-tab.active').dataset.period);
        showToast('Transaksi dihapus');
    }
}

/* ================= VOICE UI ================= */
function openVoiceModal() {
    startRecording();
}

function closeVoiceOverlay() {
    document.getElementById('voice-overlay').classList.remove('active');
}

window.onVoiceStart = () => {
    document.getElementById('voice-overlay').classList.add('active');
    document.getElementById('voice-modal-transcript').textContent = 'Mendengarkan...';
};

window.onVoiceResult = (text, isFinal) => {
    document.getElementById('voice-modal-transcript').textContent = text;
    if (isFinal) {
        processVoiceText(text);
        document.getElementById('voice-overlay').classList.remove('active');
    }
};

window.onVoiceEnd = () => {
    document.getElementById('voice-overlay').classList.remove('active');
};

window.onVoiceError = (err) => {
    showToast('Error: ' + err);
};

function processVoiceText(text) {
    const parsed = parseTransactionText(text);
    
    // First, navigate to the Add page which will reset the form
    navigateTo('add');

    if (!parsed || parsed.amount === 0) {
        showToast('Nominal tidak dikenali. Silakan input manual.');
        return;
    }

    // Pre-fill the form instead of saving directly
    setTxnType(parsed.type);
    
    // Use formatRupiah or just exact number for input type=number
    // If the input is type=number, it cannot take dots from toLocaleString.
    const amountInput = document.getElementById('txn-amount');
    if (amountInput) {
        // If it's a formatted text input we could use toLocaleString, but in Catat-in it's a number input.
        amountInput.value = parsed.amount; 
        
        // Trigger formatting logic if any exists on input
        const event = new Event('input', { bubbles: true });
        amountInput.dispatchEvent(event);
    }
    
    const catSelect = document.getElementById('txn-category');
    if (catSelect) {
        let hasCat = false;
        for (let i = 0; i < catSelect.options.length; i++) {
            if (catSelect.options[i].value === parsed.category) {
                catSelect.selectedIndex = i;
                hasCat = true;
                break;
            }
        }
        if (!hasCat) catSelect.selectedIndex = 0;
    }

    const descInput = document.getElementById('txn-desc');
    if (descInput) descInput.value = parsed.desc;
    
    const dateInput = document.getElementById('txn-date');
    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

    showToast('Harap tinjau kembali sebelum menyimpan.');
}

/* ================= UTILS ================= */
function showToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.style.transform = 'translate(-50%, 20px)';
    toast.style.opacity = '1';
    
    setTimeout(() => {
        toast.style.transform = 'translate(-50%, -20px)';
        toast.style.opacity = '0';
    }, 3000);
}



/* ================= CATEGORIES PAGE ================= */
function setCategoryTab(type) {
    document.getElementById('add-cat-type').value = type;
    
    const btnOut = document.getElementById('cat-tab-out');
    const btnIn = document.getElementById('cat-tab-in');
    
    if(type === 'pengeluaran') {
        btnOut.classList.add('active', 'bg-white', 'shadow-sm', 'text-slate-900');
        btnOut.classList.remove('text-slate-500');
        btnIn.classList.remove('active', 'bg-white', 'shadow-sm', 'text-slate-900');
        btnIn.classList.add('text-slate-500');
    } else {
        btnIn.classList.add('active', 'bg-white', 'shadow-sm', 'text-slate-900');
        btnIn.classList.remove('text-slate-500');
        btnOut.classList.remove('active', 'bg-white', 'shadow-sm', 'text-slate-900');
        btnOut.classList.add('text-slate-500');
    }
    
    updateCategoriesPage();
}

function updateCategoriesPage() {
    const type = document.getElementById('add-cat-type')?.value || 'pengeluaran';
    const list = document.getElementById('category-list-container');
    if(!list) return;
    
    const cats = appData.categories[type] || [];
    
    if(cats.length === 0) {
        list.innerHTML = `<div class="p-6 text-center text-slate-500 text-sm">Tidak ada kategori.</div>`;
        return;
    }
    
    let html = '';
    cats.forEach(c => {
        let icon = c.icon;
        const emojiToFa = {
            '🛍️': 'fas fa-store', '💰': 'fas fa-wallet', '✨': 'fas fa-star',
            '📦': 'fas fa-box', '⚡': 'fas fa-bolt', '👥': 'fas fa-users',
            '🛵': 'fas fa-motorcycle', '🏠': 'fas fa-home', '💸': 'fas fa-money-bill-wave'
        };
        if (emojiToFa[icon]) icon = emojiToFa[icon];
        const iconHtml = icon.startsWith('fas ') ? `<i class="${icon}"></i>` : icon;
        const isDefault = c.id.toString().startsWith('in_') || c.id.toString().startsWith('out_');
        const kwBadges = (c.keywords && c.keywords.length > 0)
            ? c.keywords.map(k => `<span class="inline-block text-[9px] bg-indigo-50 text-indigo-500 font-semibold px-1.5 py-0.5 rounded-full mr-0.5">${k}</span>`).join('')
            : '';
            
        let txnCount = 0;
        if(appData.transactions) {
            txnCount = appData.transactions.filter(t => t.category === c.name && t.type === c.type).length;
        }
        const txnCountBadge = `<span class="text-[9px] bg-emerald-50 text-emerald-600 font-bold px-2 py-0.5 rounded-full ml-2">${txnCount} trx</span>`;
        
        html += `
        <div class="category-row flex items-center justify-between p-3 border-b border-slate-50 last:border-0 hover:bg-slate-50 transition cursor-pointer" data-id="${c.id}" data-type="${type}">
            <div class="flex items-center gap-3 pointer-events-none">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 text-lg">
                    ${iconHtml}
                </div>
                <div>
                    <div class="flex items-center">
                        <span class="font-bold text-slate-900 text-sm">${c.name}</span>
                        ${isDefault ? '<span class="text-[10px] text-slate-400 ml-2">bawaan</span>' : ''}
                        ${txnCount > 0 ? txnCountBadge : ''}
                    </div>
                    ${kwBadges ? `<div class="mt-0.5 flex flex-wrap gap-0.5">${kwBadges}</div>` : ''}
                </div>
            </div>
            <button class="text-slate-400 p-2 hover:bg-slate-100 rounded-full transition pointer-events-none">
                <i class="fas fa-ellipsis-v text-sm"></i>
            </button>
        </div>`;
    });
    
    list.innerHTML = html;
    
    // Attach event listeners safely to the entire row
    list.querySelectorAll('.category-row').forEach(row => {
        row.addEventListener('click', function(e) {
            e.preventDefault();
            openEditCatModal(this.dataset.id, this.dataset.type);
        });
    });
}

function handleAddCategory(e) {
    e.preventDefault();
    const name = document.getElementById('add-cat-name').value;
    const type = document.getElementById('add-cat-type').value;
    if(!name) return;
    
    const newCat = {
        id: 'cat_' + Date.now(),
        name: name,
        icon: 'fas fa-tag',
        type: type
    };
    
    appData.categories[type].push(newCat);
    saveData(appData);
    
    document.getElementById('add-cat-name').value = '';
    updateCategoriesPage();
    populateCategorySelect();
    populateFilterCat();
    
    if (window.authUser) {
        const url = window.baseUrl ? window.baseUrl + '/api/categories' : '/api/categories';
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify(newCat)
        }).then(r => r.json()).then(data => {
            newCat.id = data.id;
            saveData(appData);
            updateCategoriesPage();
        }).catch(err => console.error(err));
    }
}

function handleDeleteCategory(id, type) {
    if(!confirm('Hapus kategori ini?')) return;
    
    // Remove locally first for instant feedback
    const backup = [...appData.categories[type]];
    appData.categories[type] = appData.categories[type].filter(c => c.id != id);
    saveData(appData);
    
    updateCategoriesPage();
    populateCategorySelect();
    populateFilterCat();
    
    if (window.authUser && !String(id).startsWith('cat_')) {
        const url = window.baseUrl ? window.baseUrl + `/api/categories/${id}` : `/api/categories/${id}`;
        fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': getCsrfToken() }
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                // Rollback: restore the deleted category
                appData.categories[type] = backup;
                saveData(appData);
                updateCategoriesPage();
                populateCategorySelect();
                populateFilterCat();
                showToast('Gagal hapus: ' + data.error);
            } else {
                showToast('Kategori berhasil dihapus');
            }
        })
        .catch(err => {
            // Rollback on network error
            appData.categories[type] = backup;
            saveData(appData);
            updateCategoriesPage();
            showToast('Gagal hapus: koneksi bermasalah');
            console.error(err);
        });
    } else {
        showToast('Kategori berhasil dihapus');
    }
}

function openEditCatModal(id, type) {
    try {
        const catList = appData.categories[type] || [];
        const cat = catList.find(c => c.id == id);
        if(!cat) {
            showToast("Gagal: Kategori tidak ditemukan!");
            return;
        }
        
        document.getElementById('edit-cat-id').value = id;
        document.getElementById('edit-cat-type-val').value = type;
        document.getElementById('edit-cat-name').value = cat.name;
        document.getElementById('edit-cat-preview-name').textContent = cat.name;

        // Pre-fill color & icon
        const color = cat.color || '#6c63ff';
        const icon  = cat.icon || 'fas fa-star';
        selectEditCatColor(color);
        selectEditCatIcon(icon.replace('fas ', ''));
        
        // Pre-fill keywords
        const kwInput = document.getElementById('edit-cat-keywords');
        if (kwInput) kwInput.value = cat.keywords ? cat.keywords.join(', ') : '';
        
        const modal = document.getElementById('edit-cat-modal');
        if(!modal) { showToast("Gagal: HTML Modal tidak ada!"); return; }
        modal.style.removeProperty('display');
        modal.classList.add('edit-cat-modal-open');
        pushModalHistory('edit-cat-modal');
    } catch (error) {
        showToast("Error: " + error.message);
    }
}

function selectEditCatColor(hex) {
    document.getElementById('edit-cat-selected-color').value = hex;
    document.querySelectorAll('.edit-cat-color-btn').forEach(btn => {
        const selected = btn.dataset.color === hex;
        btn.style.borderColor = selected ? '#fff' : 'transparent';
        btn.style.outline = selected ? `3px solid ${hex}` : 'none';
        btn.style.transform = selected ? 'scale(1.15)' : 'scale(1)';
    });
    updateEditCatPreview();
}

function selectEditCatIcon(iconName) {
    const full = iconName.startsWith('fas ') ? iconName : 'fas ' + iconName;
    document.getElementById('edit-cat-selected-icon').value = full;
    const color = document.getElementById('edit-cat-selected-color').value || '#6c63ff';
    document.querySelectorAll('.edit-cat-icon-btn').forEach(btn => {
        const selected = btn.dataset.icon === full;
        btn.style.background = selected ? color + '22' : '';
        btn.style.color = selected ? color : '';
        btn.style.outline = selected ? `2px solid ${color}` : 'none';
    });
    updateEditCatPreview();
}

function updateEditCatPreview() {
    const name  = document.getElementById('edit-cat-name').value || 'Nama Kategori';
    const color = document.getElementById('edit-cat-selected-color').value || '#6c63ff';
    const icon  = document.getElementById('edit-cat-selected-icon').value || 'fas fa-star';
    
    document.getElementById('edit-cat-preview-name').textContent = name;
    const preview     = document.getElementById('edit-cat-preview');
    const previewIcon = document.getElementById('edit-cat-preview-icon');
    if (preview) preview.style.background = color + '22';
    if (previewIcon) {
        previewIcon.style.background = color + '33';
        previewIcon.style.color = color;
        previewIcon.innerHTML = `<i class="${icon}"></i>`;
    }
}

function closeEditCatModal(fromHistory = false) {
    const modal = document.getElementById('edit-cat-modal');
    if (!fromHistory && modal.hasAttribute('data-history-pushed')) {
        modal.removeAttribute('data-history-pushed');
        history.back();
        return;
    }
    modal.style.display = 'none';
    modal.classList.remove('edit-cat-modal-open');
}

async function confirmEditCategory() {
    const id      = document.getElementById('edit-cat-id').value;
    const type    = document.getElementById('edit-cat-type-val').value;
    const newName = document.getElementById('edit-cat-name').value.trim();
    const newColor = document.getElementById('edit-cat-selected-color').value || '#6c63ff';
    const newIcon  = document.getElementById('edit-cat-selected-icon').value || 'fas fa-star';
    const kwRaw   = document.getElementById('edit-cat-keywords')?.value?.trim() || '';
    const keywords = kwRaw
        ? kwRaw.split(',').map(k => k.trim().toLowerCase()).filter(Boolean)
        : [];
    if (!newName) return;
    
    const catList = appData.categories[type];
    const cat = catList.find(c => c.id == id);
    if (!cat) return;

    const oldName = cat.name;

    // Check if another category with the same name already exists
    const duplicate = catList.find(c => c.id != id && c.name.toLowerCase() === newName.toLowerCase());
    if (duplicate) {
        // Offer merge option
        const confirmMerge = confirm(
            `Kategori "${newName}" sudah ada.\n\nKlik OK untuk MENGGABUNGKAN semua transaksi "${oldName}" ke "${duplicate.name}", lalu hapus "${oldName}".\n\nKlik Batal untuk tidak jadi.`
        );
        if (!confirmMerge) return;

        // Merge: move all transactions from old cat to existing duplicate
        if (appData.transactions) {
            appData.transactions.forEach(t => {
                if (t.category === oldName && t.type === type) {
                    t.category = duplicate.name;
                }
            });
        }
        // Delete the old category
        appData.categories[type] = catList.filter(c => c.id != id);
        saveData(appData);
        updateCategoriesPage();
        populateCategorySelect();
        populateFilterCat();
        updateWallet();
        showToast(`Kategori "${oldName}" digabung ke "${duplicate.name}"`);
        closeEditCatModal();
        return;
    }

    // Normal rename
    cat.name     = newName;
    cat.color    = newColor;
    cat.icon     = newIcon;
    cat.keywords = keywords;

    // Update all linked transactions
    if (appData.transactions && oldName !== newName) {
        appData.transactions.forEach(t => {
            if (t.category === oldName && t.type === type) {
                t.category = newName;
            }
        });
    }

    saveData(appData);
    updateCategoriesPage();
    populateCategorySelect();
    populateFilterCat();
    updateWallet();
    showToast('Kategori berhasil diperbarui');

    // Persist to backend
    if (window.authUser && !String(id).startsWith('cat_') && !String(id).startsWith('in_') && !String(id).startsWith('out_')) {
        fetch(`${window.baseUrl}/api/categories/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify({ name: newName, color: newColor, icon: newIcon, keywords })
        }).catch(() => {});
    }
    closeEditCatModal();
}

function confirmDeleteCategory() {
    const id = document.getElementById('edit-cat-id').value;
    const type = document.getElementById('edit-cat-type-val').value;
    closeEditCatModal();
    handleDeleteCategory(id, type);
}

/* ================= EDIT PROFILE ================= */
function openEditProfileModal() {
    const m = document.getElementById('edit-profile-modal');
    m.style.removeProperty('display');
    m.style.display = 'flex';
}
function closeEditProfileModal() {
    document.getElementById('edit-profile-modal').style.display = 'none';
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('edit-profile-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePasswordVisibility() {
    const pwdInput = document.getElementById('edit-profile-password');
    const icon = document.getElementById('edit-profile-password-eye');
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        pwdInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

async function submitEditProfile(e) {
    e.preventDefault();
    if (!window.authUser) {
        showToast('Hanya pengguna yang login yang bisa edit profil');
        return;
    }

    const btn = document.getElementById('btn-save-profile');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('name', document.getElementById('edit-profile-name').value);
    
    const pwd = document.getElementById('edit-profile-password').value;
    if (pwd) formData.append('password', pwd);
    
    const avatarFile = document.getElementById('edit-profile-avatar').files[0];
    if (avatarFile) formData.append('avatar', avatarFile);

    const url = window.baseUrl ? window.baseUrl + '/api/profile' : '/api/profile';
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: formData
        });

        if (response.ok) {
            const data = await response.json();
            showToast('Profil berhasil diperbarui!');
            
            // Update local user data
            window.authUser.name = data.user.name;
            if (data.user.avatar) window.authUser.avatar = data.user.avatar;
            
            // Update UI
            const nameDisplays = document.querySelectorAll('#profile-name-display');
            nameDisplays.forEach(el => el.textContent = data.user.name);
            
            if (data.user.avatar) {
                const imgDisplays = document.querySelectorAll('#profile-avatar-img');
                imgDisplays.forEach(el => el.src = data.user.avatar);
            }
            
            // Clear password field
            document.getElementById('edit-profile-password').value = '';
            closeEditProfileModal();
        } else {
            const err = await response.json();
            showToast(err.message || 'Gagal menyimpan profil');
        }
    } catch (error) {
        console.error(error);
        showToast('Terjadi kesalahan koneksi');
    } finally {
        btn.innerHTML = 'Simpan Profil';
        btn.disabled = false;
    }
}

/* ================= PROJECT MANAGEMENT ================= */
let _projectManageMode = false;

function openProjectSwitcher(manageMode = false) {
    _projectManageMode = manageMode;
    // Update title
    const title = document.getElementById('project-modal-title');
    if (title) title.textContent = manageMode ? 'Kelola Proyek' : 'Pilih Proyek';
    // Show/hide add footer
    const footer = document.getElementById('project-modal-footer');
    if (footer) footer.style.display = manageMode ? 'block' : 'none';
    renderProjectList();
    const modal = document.getElementById('project-switcher-modal');
    modal.style.removeProperty('display');
    modal.style.display = 'flex';
}

function closeProjectSwitcher() {
    const modal = document.getElementById('project-switcher-modal');
    modal.style.display = 'none';
}

function renderProjectList() {
    const container = document.getElementById('project-list-container');
    if (!container) return;
    const projects = window.allProjects || [];
    const activeId = window.activeProject ? window.activeProject.id : null;
    if (projects.length === 0) {
        container.innerHTML = '<div class="text-center text-slate-400 py-8 text-sm">Belum ada proyek. Buat proyek baru di bawah.</div>';
        return;
    }
    container.innerHTML = projects.map(p => {
        const isActive = p.id === activeId;
        const color = p.color || '#6c63ff';
        const isFa = p.icon && (p.icon.startsWith('fa') || p.icon.includes('fa-'));
        const iconHtml = isFa ? `<i class="${p.icon}"></i>` : p.icon || '💰';

        const deleteBtn = (_projectManageMode && !isActive)
            ? `<button onclick="event.stopPropagation();deleteProject(${p.id},'${p.name.replace(/'/g,"\\'")}')"
                      class="w-8 h-8 rounded-full bg-rose-50 text-rose-400 flex items-center justify-center hover:bg-rose-100 transition ml-1">
                   <i class="fas fa-trash text-[10px]"></i>
               </button>`
            : '';
        const editBtn = (_projectManageMode)
            ? `<button onclick="event.stopPropagation();closeProjectSwitcher();openProjectModal(${JSON.stringify({id: p.id, name: p.name, color: color, icon: p.icon}).replace(/"/g, '&quot;')})"
                      class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-200 transition ml-1">
                   <i class="fas fa-pen text-[10px]"></i>
               </button>`
            : '';
        const activeBadge = isActive ? `<span class="text-xs font-bold px-2.5 py-1 rounded-full" style="background:${color}22;color:${color}">Aktif</span>` : '';
        const clickAction = isActive ? '' : `onclick="switchToProject(${p.id})"`;

        return `<div class="flex items-center gap-3 p-3 rounded-2xl mb-2 cursor-pointer transition ${isActive ? 'border' : 'hover:bg-slate-50 border border-transparent'}" style="${isActive ? 'border-color:' + color + '33;background:' + color + '0a' : ''}" ${clickAction}>
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-lg shrink-0" style="background:${color}22;color:${color}">${iconHtml}</div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-900 text-sm">${p.name}</p>
                <p class="text-xs text-slate-400">${p.transactions_count ?? 0} transaksi</p>
            </div>
            ${activeBadge}${editBtn}${deleteBtn}
        </div>`;
    }).join('');
}
async function switchToProject(id) {
    try {
        await fetch(`${window.baseUrl}/api/projects/${id}/switch`, { method: 'POST', headers: { 'X-CSRF-TOKEN': getCsrfToken() } });
        showToast('Berpindah proyek...');
        setTimeout(() => location.reload(), 800);
    } catch (e) { showToast('Gagal berpindah proyek'); }
}
async function createNewProject() {
    const name = document.getElementById('new-project-name').value.trim();
    const icon = document.getElementById('new-project-icon').value;
    if (!name) { showToast('Masukkan nama proyek terlebih dahulu'); return; }
    try {
        const res = await fetch(`${window.baseUrl}/api/projects`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify({ name, icon })
        });
        if (res.ok) { showToast('Proyek dibuat!'); setTimeout(() => location.reload(), 900); }
        else showToast('Gagal membuat proyek');
    } catch (e) { showToast('Terjadi kesalahan koneksi'); }
}
async function deleteProject(id, name) {
    if (!confirm(`Hapus proyek "${name}"?\nSemua transaksi & kategori akan ikut terhapus!`)) return;
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': getCsrfToken() } });
        if (res.ok) { showToast('Proyek dihapus'); setTimeout(() => location.reload(), 800); }
        else showToast('Gagal menghapus proyek');
    } catch (e) { showToast('Terjadi kesalahan'); }
}
function selectOnboardingIcon(icon) {
    document.getElementById('onboarding-project-icon').value = icon;
    document.querySelectorAll('.onboarding-icon-btn').forEach(btn => {
        const match = btn.dataset.icon === icon;
        btn.style.borderColor = match ? '#6c63ff' : 'transparent';
        btn.style.background = match ? '#6c63ff22' : '';
        btn.style.color = match ? '#6c63ff' : '';
    });
}
async function submitOnboarding() {
    const name = document.getElementById('onboarding-project-name')?.value?.trim();
    const icon = document.getElementById('onboarding-project-icon')?.value || 'fas fa-wallet';
    if (!name) { showToast('Masukkan nama proyek'); return; }
    const btn = document.getElementById('btn-onboarding-submit');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membuat...';
    btn.disabled = true;
    try {
        const res = await fetch(`${window.baseUrl}/api/projects`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify({ name, icon })
        });
        if (res.ok) { showToast('Proyek berhasil dibuat!'); setTimeout(() => location.reload(), 800); }
        else { showToast('Gagal membuat proyek'); btn.innerHTML = '<i class="fas fa-rocket"></i> Mulai Sekarang'; btn.disabled = false; }
    } catch (e) { showToast('Terjadi kesalahan'); btn.innerHTML = '<i class="fas fa-rocket"></i> Mulai Sekarang'; btn.disabled = false; }
}

/* ================= ADD CATEGORY MODAL ================= */
function openAddCatModal() {
    // Reset state
    setAddCatType('pemasukan');
    document.getElementById('add-cat-name-input').value = '';
    document.getElementById('add-cat-charcount').textContent = '0/30';
    document.getElementById('add-cat-preview-name').textContent = 'Nama Kategori';
    selectAddCatColor('#6c63ff');
    selectAddCatIcon('fa-star');
    const m = document.getElementById('add-cat-modal');
    m.style.removeProperty('display');
    m.style.display = 'flex';
    pushModalHistory('add-cat-modal');
    setTimeout(() => document.getElementById('add-cat-name-input').focus(), 300);
}

function closeAddCatModal(fromHistory = false) {
    const m = document.getElementById('add-cat-modal');
    if (!fromHistory && m.hasAttribute('data-history-pushed')) {
        m.removeAttribute('data-history-pushed');
        history.back();
        return;
    }
    m.style.display = 'none';
}

function setAddCatType(type) {
    document.getElementById('add-cat-selected-type').value = type;
    const btnOut = document.getElementById('add-cat-btn-out');
    const btnIn  = document.getElementById('add-cat-btn-in');
    if (type === 'pengeluaran') {
        btnOut.style.cssText = 'background:#6c63ff;color:white';
        btnIn.style.cssText  = 'background:transparent;color:#64748b';
    } else {
        btnIn.style.cssText  = 'background:#6c63ff;color:white';
        btnOut.style.cssText = 'background:transparent;color:#64748b';
    }
    updateAddCatPreview();
}

function selectAddCatColor(hex) {
    document.getElementById('add-cat-selected-color').value = hex;
    document.querySelectorAll('.add-cat-color-btn').forEach(btn => {
        const selected = btn.dataset.color === hex;
        btn.style.borderColor = selected ? '#fff' : 'transparent';
        btn.style.outline = selected ? `3px solid ${hex}` : 'none';
        btn.style.transform = selected ? 'scale(1.15)' : 'scale(1)';
    });
    updateAddCatPreview();
}

function selectAddCatIcon(iconName) {
    const full = 'fas ' + iconName;
    document.getElementById('add-cat-selected-icon').value = full;
    const color = document.getElementById('add-cat-selected-color').value || '#6c63ff';
    document.querySelectorAll('.add-cat-icon-btn').forEach(btn => {
        const selected = btn.dataset.icon === full;
        btn.style.background = selected ? color + '22' : '';
        btn.style.color = selected ? color : '';
        btn.style.outline = selected ? `2px solid ${color}` : 'none';
    });
    updateAddCatPreview();
}

function updateAddCatPreview() {
    const name  = document.getElementById('add-cat-name-input').value || 'Nama Kategori';
    const icon  = document.getElementById('add-cat-selected-icon').value || 'fas fa-star';
    const color = document.getElementById('add-cat-selected-color').value || '#6c63ff';
    const count = document.getElementById('add-cat-name-input').value.length;
    document.getElementById('add-cat-charcount').textContent = count + '/30';
    document.getElementById('add-cat-preview-name').textContent = name;
    const preview     = document.getElementById('add-cat-preview');
    const previewIcon = document.getElementById('add-cat-preview-icon');
    preview.style.background = color + '22';
    previewIcon.style.background = color + '44';
    previewIcon.style.color = color;
    previewIcon.innerHTML = `<i class="${icon}"></i>`;
}

async function submitAddCatModal() {
    const name     = document.getElementById('add-cat-name-input').value.trim();
    const type     = document.getElementById('add-cat-selected-type').value;
    const icon     = document.getElementById('add-cat-selected-icon').value;
    const color    = document.getElementById('add-cat-selected-color').value;
    const keywordsRaw = document.getElementById('add-cat-keywords-input')?.value?.trim() || '';
    // Parse comma-separated keywords into array
    const keywords = keywordsRaw
        ? keywordsRaw.split(',').map(k => k.trim().toLowerCase()).filter(Boolean)
        : [];

    if (!name) { showToast('Masukkan nama kategori'); return; }
    try {
        const res = await fetch(`${window.baseUrl}/api/categories`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify({ name, type, icon, color, keywords })
        });
        if (res.ok) {
            const cat = await res.json();
            if (!appData.categories[type]) appData.categories[type] = [];
            appData.categories[type].push({ id: cat.id, name, type, icon, color, keywords: cat.keywords || [] });
            saveData(appData);
            updateCategoriesPage();
            showToast('Kategori berhasil ditambahkan!');
            closeAddCatModal();
            // Clear keywords input for next use
            const kwInput = document.getElementById('add-cat-keywords-input');
            if (kwInput) kwInput.value = '';
        } else showToast('Gagal menyimpan kategori');
    } catch(e) { showToast('Terjadi kesalahan'); }
}

/* ================= PROJECT MODAL (ADD & EDIT) ================= */
window.editProjectId = null;

function openProjectModal(editProject = null) {
    if (editProject) {
        window.editProjectId = editProject.id;
        document.getElementById('modal-project-title').textContent = 'Edit Proyek';
        document.getElementById('add-proj-name-input').value = editProject.name;
        document.getElementById('add-proj-charcount').textContent = editProject.name.length + '/40';
        selectAddProjColor(editProject.color || '#6c63ff');
        selectAddProjIcon(editProject.icon ? editProject.icon.replace('fas ', '') : 'fa-wallet');
        document.getElementById('btn-submit-project').textContent = 'Simpan Perubahan';
    } else {
        window.editProjectId = null;
        document.getElementById('modal-project-title').textContent = 'Proyek Baru';
        document.getElementById('add-proj-name-input').value = '';
        document.getElementById('add-proj-charcount').textContent = '0/40';
        selectAddProjColor('#6c63ff');
        selectAddProjIcon('fa-wallet');
        document.getElementById('btn-submit-project').textContent = 'Buat Proyek';
    }
    const m = document.getElementById('add-project-modal');
    m.style.removeProperty('display');
    m.style.display = 'flex';
    setTimeout(() => document.getElementById('add-proj-name-input').focus(), 200);
}
function closeProjectModal() {
    document.getElementById('add-project-modal').style.display = 'none';
}
function selectAddProjColor(hex) {
    document.getElementById('add-proj-selected-color').value = hex;
    document.querySelectorAll('.add-proj-color-btn').forEach(btn => {
        const sel = btn.dataset.color === hex;
        btn.style.outline = sel ? `3px solid ${hex}` : 'none';
        btn.style.borderColor = sel ? '#fff' : 'transparent';
        btn.style.transform = sel ? 'scale(1.15)' : 'scale(1)';
    });
    updateAddProjPreview();
}
function selectAddProjIcon(iconName) {
    const full = 'fas ' + iconName;
    document.getElementById('add-proj-selected-icon').value = full;
    const color = document.getElementById('add-proj-selected-color').value || '#6c63ff';
    document.querySelectorAll('.add-proj-icon-btn').forEach(btn => {
        const sel = btn.dataset.icon === full;
        btn.style.background = sel ? color + '22' : '';
        btn.style.color = sel ? color : '';
        btn.style.outline = sel ? `2px solid ${color}` : 'none';
    });
    updateAddProjPreview();
}
function updateAddProjPreview() {
    const name  = document.getElementById('add-proj-name-input').value || 'Nama Proyek';
    const icon  = document.getElementById('add-proj-selected-icon').value || 'fas fa-wallet';
    const color = document.getElementById('add-proj-selected-color').value || '#6c63ff';
    document.getElementById('add-proj-charcount').textContent = document.getElementById('add-proj-name-input').value.length + '/40';
    document.getElementById('add-proj-preview-name').textContent = name;
    const preview = document.getElementById('add-proj-preview');
    const previewIcon = document.getElementById('add-proj-preview-icon');
    preview.style.background = color + '22';
    previewIcon.style.background = color + '44';
    previewIcon.style.color = color;
    previewIcon.innerHTML = `<i class="${icon}"></i>`;
}
async function submitProjectModal() {
    const name  = document.getElementById('add-proj-name-input').value.trim();
    const icon  = document.getElementById('add-proj-selected-icon').value;
    const color = document.getElementById('add-proj-selected-color').value;
    if (!name) { showToast('Masukkan nama proyek'); return; }
    
    const isEdit = window.editProjectId !== null;
    const url = isEdit ? `${window.baseUrl}/api/projects/${window.editProjectId}` : `${window.baseUrl}/api/projects`;
    const method = isEdit ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify({ name, icon, color })
        });
        const data = await res.json();
        if (data.success) {
            showToast(isEdit ? 'Proyek berhasil diperbarui!' : 'Proyek berhasil dibuat!');
            closeProjectModal();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Gagal memproses proyek');
        }
    } catch(e) { showToast('Terjadi kesalahan jaringan'); }
}

/* ================= COLLABORATION / MEMBERS ================= */
let _membersIsOwner = false;

function openMembersModal() {
    const m = document.getElementById('members-modal');
    m.style.removeProperty('display');
    m.style.display = 'flex';
    switchMembersTab('members');
    loadMembers();
}
function closeMembersModal() {
    document.getElementById('members-modal').style.display = 'none';
}

function switchMembersTab(tab) {
    const panelMembers = document.getElementById('tab-panel-members');
    const panelInvites = document.getElementById('tab-panel-invites');
    const tabMembers   = document.getElementById('tab-members');
    const tabInvites   = document.getElementById('tab-invites');
    const inviteBox    = document.getElementById('invite-box-section');

    if (tab === 'members') {
        panelMembers.classList.remove('hidden');
        panelInvites.classList.add('hidden');
        tabMembers.classList.add('border-brand-600', 'text-brand-600');
        tabMembers.classList.remove('border-transparent', 'text-slate-500');
        tabInvites.classList.remove('border-brand-600', 'text-brand-600');
        tabInvites.classList.add('border-transparent', 'text-slate-500');
        if (_membersIsOwner) inviteBox?.classList.remove('hidden');
    } else {
        panelMembers.classList.add('hidden');
        panelInvites.classList.remove('hidden');
        tabMembers.classList.remove('border-brand-600', 'text-brand-600');
        tabMembers.classList.add('border-transparent', 'text-slate-500');
        tabInvites.classList.add('border-brand-600', 'text-brand-600');
        tabInvites.classList.remove('border-transparent', 'text-slate-500');
        inviteBox?.classList.add('hidden');
        loadInvites();
    }
}

async function loadMembers() {
    const projectId = window.activeProject?.id;
    if (!projectId) { showToast('Pilih proyek terlebih dahulu'); return; }
    const container = document.getElementById('tab-panel-members');
    const footer = document.getElementById('members-footer');
    container.innerHTML = '<div class="text-center text-slate-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat...</div>';
    footer.innerHTML = '';
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${projectId}/members`, {
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
        });
        const data = await res.json();
        _membersIsOwner = data.is_owner;
        renderMembers(data.members, data.is_owner);

        // Show invites tab only for owner
        const tabInvites = document.getElementById('tab-invites');
        if (data.is_owner) {
            tabInvites?.classList.remove('hidden');
        }
    } catch(e) { container.innerHTML = '<div class="text-center text-rose-400 py-8">Gagal memuat anggota</div>'; }
}

function renderMembers(members, isOwner) {
    const container = document.getElementById('tab-panel-members');
    const footer = document.getElementById('members-footer');
    if (!members || members.length === 0) {
        container.innerHTML = '<div class="text-center text-slate-400 py-8 text-sm">Belum ada anggota</div>';
        return;
    }
    container.innerHTML = members.map(m => {
        const avatarUrl = m.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(m.name)}&background=f1f5f9&color=475569&size=80`;
        const roleLabel = m.role === 'owner'
            ? '<span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Pemilik</span>'
            : '<span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Anggota</span>';
        const removeBtn = (isOwner && m.role !== 'owner')
            ? `<button onclick="event.stopPropagation();removeMemberAction(${m.user_id},'${m.name.replace(/'/g,"\\'")}')\" class="w-7 h-7 rounded-full bg-rose-50 text-rose-400 flex items-center justify-center hover:bg-rose-100 transition shrink-0"><i class="fas fa-times text-[10px]"></i></button>`
            : '';
        return `<div class="flex items-center gap-3 p-3 rounded-2xl mb-2 hover:bg-slate-50 transition">
            <img src="${avatarUrl}" class="w-10 h-10 rounded-full object-cover shrink-0" alt="">
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-900 text-sm truncate">${m.name}</p>
                <p class="text-xs text-slate-400 truncate">${m.email}</p>
            </div>
            ${roleLabel}${removeBtn}
        </div>`;
    }).join('');

    // Show/hide invite section based on role
    const inviteBox = document.getElementById('invite-box-section');
    if (inviteBox) {
        if (isOwner) inviteBox.classList.remove('hidden');
        else inviteBox.classList.add('hidden');
        document.getElementById('invite-email-input').value = '';
        document.getElementById('copy-link-wrapper').classList.add('hidden');
        document.getElementById('invite-link-display').value = '';
    }

    // Footer buttons
    let footerHtml = '';
    if (!isOwner) {
        footerHtml += `<button onclick="leaveProjectAction()" class="w-full flex items-center justify-center gap-2 bg-rose-50 text-rose-600 border border-rose-200 rounded-2xl py-3 font-bold text-sm hover:bg-rose-100 transition"><i class="fas fa-sign-out-alt"></i> Keluar dari Proyek</button>`;
    }
    footer.innerHTML = footerHtml;
}

async function loadInvites() {
    const projectId = window.activeProject?.id;
    if (!projectId) return;
    const container = document.getElementById('invites-list-container');
    container.innerHTML = '<div class="text-center text-slate-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat...</div>';
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${projectId}/invites`, {
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
        });
        const data = await res.json();
        renderInvites(data.invites || []);
    } catch(e) {
        container.innerHTML = '<div class="text-center text-rose-400 py-8">Gagal memuat undangan</div>';
    }
}

function renderInvites(invites) {
    const container = document.getElementById('invites-list-container');
    const badge = document.getElementById('invite-count-badge');

    // Update badge
    if (badge) {
        if (invites.length > 0) {
            badge.textContent = invites.length;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    if (!invites || invites.length === 0) {
        container.innerHTML = `<div class="text-center py-10">
            <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-envelope-open text-slate-400 text-xl"></i>
            </div>
            <p class="text-slate-500 text-sm font-semibold">Tidak ada undangan pending</p>
            <p class="text-slate-400 text-xs mt-1">Undang anggota dari tab Anggota</p>
        </div>`;
        return;
    }

    container.innerHTML = invites.map(inv => {
        const avatarUrl = inv.avatar || (inv.name
            ? `https://ui-avatars.com/api/?name=${encodeURIComponent(inv.name)}&background=f1f5f9&color=475569&size=80`
            : `https://ui-avatars.com/api/?name=?&background=f1f5f9&color=475569&size=80`);
        const nameDisplay = inv.name || '(Link tanpa nama)';
        const emailDisplay = inv.email || (inv.is_link ? 'Undangan via link' : 'Email tidak diketahui');
        return `<div class="flex items-center gap-3 p-3 rounded-2xl mb-2 bg-amber-50 border border-amber-100">
            <img src="${avatarUrl}" class="w-10 h-10 rounded-full object-cover shrink-0" alt="">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="font-bold text-slate-900 text-sm truncate">${nameDisplay}</p>
                    ${inv.is_link ? '<span class="text-[9px] bg-blue-100 text-blue-600 font-bold px-1.5 py-0.5 rounded-full">Link</span>' : ''}
                </div>
                <p class="text-xs text-slate-400 truncate">${emailDisplay}</p>
                <p class="text-[10px] text-amber-600 font-medium mt-0.5"><i class="fas fa-clock mr-0.5"></i> ${inv.invited_at || 'baru saja'}</p>
            </div>
            <div class="flex flex-col items-end gap-1 shrink-0">
                <span class="text-[9px] font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Menunggu</span>
                <button onclick="cancelInviteAction(${inv.id})" class="text-[10px] text-rose-500 font-bold hover:text-rose-700 transition flex items-center gap-1">
                    <i class="fas fa-trash text-[9px]"></i> Batalkan
                </button>
            </div>
        </div>`;
    }).join('');
}

async function cancelInviteAction(memberId) {
    if (!confirm('Batalkan undangan ini?')) return;
    const projectId = window.activeProject?.id;
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${projectId}/invites/${memberId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': getCsrfToken() }
        });
        if (res.ok) {
            showToast('Undangan dibatalkan');
            loadInvites();
        } else {
            showToast('Gagal membatalkan undangan');
        }
    } catch(e) { showToast('Terjadi kesalahan'); }
}

async function inviteByEmailAction() {
    const email = document.getElementById('invite-email-input')?.value?.trim();
    if (!email) { showToast('Masukkan email terlebih dahulu'); return; }
    const projectId = window.activeProject?.id;
    if (!projectId) return;
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${projectId}/invite-email`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        const data = await res.json();
        if (res.ok) {
            showToast(data.message || 'Undangan terkirim!');
            document.getElementById('invite-email-input').value = '';
            // Refresh invite list
            const tabInvites = document.getElementById('tab-invites');
            tabInvites?.classList.remove('hidden');
            // Reload invites in background to update badge
            const pid = projectId;
            fetch(`${window.baseUrl}/api/projects/${pid}/invites`, {
                headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => {
                const badge = document.getElementById('invite-count-badge');
                if (badge && d.invites?.length > 0) {
                    badge.textContent = d.invites.length;
                    badge.classList.remove('hidden');
                }
            }).catch(() => {});
        } else {
            showToast(data.error || 'Gagal mengundang');
        }
    } catch(e) { showToast('Terjadi kesalahan'); }
}

async function generateInviteLink(isWa = false) {
    const projectId = window.activeProject?.id;
    if (!projectId) return;
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${projectId}/invite`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.link) {
            const wrapper = document.getElementById('copy-link-wrapper');
            const display = document.getElementById('invite-link-display');
            if (wrapper && display) {
                wrapper.classList.remove('hidden');
                display.value = data.link;
            }
            if (isWa && data.wa_url) {
                const opened = window.open(data.wa_url, '_blank');
                if (!opened) showToast('Link dibuat! Salin di bawah karena pop-up diblokir.');
                else showToast('Link undangan siap dibagikan!');
            } else {
                showToast('Link undangan berhasil dibuat!');
            }
        } else {
            showToast('Gagal membuat undangan');
        }
    } catch(e) { showToast('Terjadi kesalahan'); }
}

async function removeMemberAction(userId, name) {
    if (!confirm(`Hapus ${name} dari proyek ini?`)) return;
    const projectId = window.activeProject?.id;
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${projectId}/members/${userId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': getCsrfToken() }
        });
        if (res.ok) { showToast('Anggota dihapus'); loadMembers(); }
        else { const d = await res.json(); showToast(d.error || 'Gagal menghapus'); }
    } catch(e) { showToast('Terjadi kesalahan'); }
}

async function leaveProjectAction() {
    if (!confirm('Keluar dari proyek ini? Kamu tidak bisa mengakses data proyek ini lagi.')) return;
    const projectId = window.activeProject?.id;
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${projectId}/leave`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': getCsrfToken() }
        });
        if (res.ok) { 
            showToast('Keluar dari proyek...'); 
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Gagal keluar dari proyek');
        }
    } catch(e) {
        showToast('Terjadi kesalahan');
    }
}


/* ================= ACTIVITY LOG & UNDO ================= */
function openActivityLogModal() {
    const m = document.getElementById('activity-log-modal');
    m.style.removeProperty('display');
    m.style.display = 'flex';
    loadActivityLog();
}
function closeActivityLogModal() {
    document.getElementById('activity-log-modal').style.display = 'none';
}

window.currentActivityLogs = [];
window.currentActivityFilter = 'all';

async function loadActivityLog() {
    const projectId = window.activeProject?.id;
    if (!projectId) { showToast('Pilih proyek terlebih dahulu'); return; }
    const container = document.getElementById('activity-log-container');
    container.innerHTML = '<div class="text-center text-slate-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat...</div>';
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${projectId}/activity`, {
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
        });
        const logs = await res.json();
        window.currentActivityLogs = logs;
        renderActivityLog();
    } catch(e) { container.innerHTML = '<div class="text-center text-rose-400 py-8">Gagal memuat riwayat aktivitas</div>'; }
}

function filterActivityLog(type, btnElement) {
    window.currentActivityFilter = type;
    
    // Update button styles
    const btns = document.querySelectorAll('.activity-filter-btn');
    btns.forEach(btn => {
        btn.classList.remove('bg-slate-900', 'text-white', 'border-transparent');
        btn.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
    });
    
    if (btnElement) {
        btnElement.classList.remove('bg-white', 'text-slate-600', 'border-slate-200');
        btnElement.classList.add('bg-slate-900', 'text-white', 'border-transparent');
    }
    
    renderActivityLog();
}

function renderActivityLog() {
    const container = document.getElementById('activity-log-container');
    let logs = window.currentActivityLogs || [];
    
    if (window.currentActivityFilter !== 'all') {
        logs = logs.filter(l => l.action === window.currentActivityFilter);
    }
    
    if (!logs || logs.length === 0) {
        container.innerHTML = '<div class="text-center text-slate-400 py-8 text-sm">Belum ada aktivitas tercatat</div>';
        return;
    }
    container.innerHTML = logs.map(log => {
        const avatarUrl = log.user_avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(log.user_name)}&background=f1f5f9&color=475569&size=80`;
        
        // Describe the activity beautifully
        let desc = '';
        let iconClass = 'fa-info-circle text-slate-500 bg-slate-50';
        
        if (log.action === 'created') {
            iconClass = 'fa-plus-circle text-emerald-500 bg-emerald-50';
            if (log.model_type === 'Transaction') {
                const typeText = log.data?.type === 'pemasukan' ? 'pemasukan' : 'pengeluaran';
                desc = `menambahkan ${typeText} <strong>"${log.data?.desc || log.data?.category}"</strong> senilai <strong>Rp ${formatRupiah(log.data?.amount)}</strong>`;
            } else if (log.model_type === 'Category') {
                desc = `membuat kategori baru <strong>"${log.data?.name}"</strong>`;
            }
        } else if (log.action === 'deleted') {
            iconClass = 'fa-trash-alt text-rose-500 bg-rose-50';
            if (log.model_type === 'Transaction') {
                const typeText = log.data?.type === 'pemasukan' ? 'pemasukan' : 'pengeluaran';
                desc = `menghapus ${typeText} <strong>"${log.data?.desc || log.data?.category}"</strong> senilai <strong>Rp ${formatRupiah(log.data?.amount)}</strong>`;
            } else if (log.model_type === 'Category') {
                desc = `menghapus kategori <strong>"${log.data?.name}"</strong>`;
            }
        } else if (log.action === 'joined') {
            iconClass = 'fa-user-plus text-blue-500 bg-blue-50';
            desc = `bergabung ke dalam proyek kolaborasi`;
        } else if (log.action === 'removed_member') {
            iconClass = 'fa-user-minus text-rose-500 bg-rose-50';
            desc = `mengeluarkan <strong>"${log.data?.removed_user}"</strong> dari proyek`;
        } else if (log.action === 'login') {
            iconClass = 'fa-user-check text-violet-500 bg-violet-50';
            desc = `masuk ke dalam aplikasi (Login)`;
        } else if (log.action === 'download_pdf') {
            iconClass = 'fa-file-pdf text-amber-500 bg-amber-50';
            desc = `mengunduh laporan PDF (${log.data?.period || 'Semua Waktu'})`;
        } else if (log.action === 'updated') {
            iconClass = 'fa-user-cog text-cyan-500 bg-cyan-50';
            if (log.model_type === 'User') {
                desc = `memperbarui profil`;
            } else if (log.model_type === 'Category') {
                desc = `memperbarui kategori <strong>"${log.data?.name}"</strong>`;
            } else if (log.model_type === 'Project') {
                desc = `memperbarui proyek`;
            }
        }

        // Action button (Undo)
        const canUndo = (log.action === 'created' || log.action === 'deleted') && !log.undone;
        const undoBtn = canUndo 
            ? `<button onclick="undoActivityAction(${log.id})" class="text-[10px] font-bold text-[#6c63ff] bg-[#6c63ff]/10 hover:bg-[#6c63ff]/20 px-2.5 py-1 rounded-lg transition shrink-0 ml-1"><i class="fas fa-undo mr-1"></i> Undo</button>` 
            : (log.undone ? `<span class="text-[9px] text-slate-400 font-bold bg-slate-100 px-2 py-0.5 rounded-full shrink-0 ml-1"><i class="fas fa-check mr-1"></i> Undone</span>` : '');

        // MORE COMPACT CARD: p-2.5, rounded-xl, gap-2.5, smaller image
        return `<div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-white border border-slate-100 shadow-sm transition">
            <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 mt-0.5 border border-slate-100 relative">
                <img src="${avatarUrl}" class="w-7 h-7 rounded-full object-cover" alt="">
                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center border border-white shadow-sm ${iconClass.split(' ').slice(1).join(' ')}" style="font-size: 7px;">
                    <i class="${iconClass.split(' ')[0]}"></i>
                </div>
            </div>
            <div class="flex-1 min-w-0 text-slate-700 text-xs leading-tight">
                <span class="font-bold text-slate-900">${log.user_name}</span> ${desc}
                <p class="text-[9px] text-slate-400 mt-0.5">${log.time}</p>
            </div>
            ${undoBtn}
        </div>`;
    }).join('');
}

async function undoActivityAction(logId) {
    showToast('Membatalkan aksi...');
    try {
        const res = await fetch(`${window.baseUrl}/api/activity/${logId}/undo`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' }
        });
        if (res.ok) {
            showToast('Aksi berhasil dibatalkan!');
            closeActivityLogModal();
            setTimeout(() => location.reload(), 800);
        } else {
            const data = await res.json();
            showToast(data.error || 'Gagal membatalkan aksi');
        }
    } catch(e) { showToast('Terjadi kesalahan'); }
}

async function inviteByEmailAction2() {
    const emailInput = document.getElementById('invite-email-input');
    const email = emailInput?.value?.trim();
    if (!email) { showToast('Masukkan email terlebih dahulu'); return; }

    const projectId = window.activeProject?.id;
    if (!projectId) return;

    showToast('Mengirim undangan...');
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${projectId}/invite-email`, {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email: email })
        });
        const data = await res.json();
        if (res.ok) {
            showToast(data.message || 'Undangan terkirim!');
            emailInput.value = '';
            
            if (data.link) {
                const wrapper = document.getElementById('copy-link-wrapper');
                const display = document.getElementById('invite-link-display');
                if (wrapper && display) {
                    wrapper.classList.remove('hidden');
                    display.value = data.link;
                }
            } else {
                loadMembers();
            }
        } else {
            showToast(data.error || 'Gagal mengirim undangan');
        }
    } catch(e) { showToast('Terjadi kesalahan'); }
}

function copyInviteLinkAction() {
    const display = document.getElementById('invite-link-display');
    if (!display || !display.value) return;
    
    display.select();
    display.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(display.value).then(() => {
        showToast('Link disalin ke clipboard!');
    }).catch(() => {
        showToast('Gagal menyalin otomatis');
    });
}

async function logGenericActivityOnBackend(action, modelType, data = null) {
    const projectId = window.activeProject?.id;
    if (!projectId) return;
    try {
        await fetch(`${window.baseUrl}/api/projects/${projectId}/activity/log`, {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ action: action, model_type: modelType, data: data })
        });
    } catch(e) { console.error('Failed to log generic activity:', e); }
}

// --- NOTIFICATIONS SYSTEM ---

function openNotificationsModal() {
    const modal = document.getElementById('notifications-modal');
    if (modal) {
        modal.style.setProperty('display', 'flex', 'important');
        document.body.style.overflow = 'hidden';
        loadNotifications();
    }
}

function closeNotificationsModal() {
    const modal = document.getElementById('notifications-modal');
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
        document.body.style.overflow = '';
    }
}

async function loadNotifications() {
    const container = document.getElementById('notifications-container');
    const badgeStatic = document.getElementById('notif-badge-static');
    const badgePing = document.getElementById('notif-badge');
    
    if (container) container.innerHTML = '<div class="text-center text-slate-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat...</div>';
    
    try {
        const res = await fetch(`${window.baseUrl}/api/notifications`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        
        let html = '';
        let pendingCount = 0;
        
        // Render Invites
        if (data.invites && data.invites.length > 0) {
            pendingCount += data.invites.length;
            html += `<h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Undangan Proyek</h4>`;
            html += data.invites.map(inv => `
                <div class="p-3 bg-indigo-50/50 border border-indigo-100 rounded-xl mb-3">
                    <p class="text-xs text-slate-700 mb-2"><strong>${inv.owner_name}</strong> mengundang Anda ke proyek <strong>"${inv.project_name}"</strong></p>
                    <div class="flex gap-2">
                        <button onclick="acceptInviteAction(${inv.id})" class="flex-1 bg-[#6c63ff] text-white text-[11px] font-bold py-1.5 rounded-lg hover:bg-[#5b52e5] transition">Terima</button>
                        <button onclick="declineInviteAction(${inv.id})" class="flex-1 bg-white border border-rose-200 text-rose-600 text-[11px] font-bold py-1.5 rounded-lg hover:bg-rose-50 transition">Tolak</button>
                    </div>
                </div>
            `).join('');
        }
        
        // Render Activities
        const lastReadId = parseInt(localStorage.getItem('catatin_last_read_activity_id') || '0');
        let maxActivityId = lastReadId;

        if (data.activities && data.activities.length > 0) {
            html += `<h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mt-4 mb-2">Aktivitas Terbaru</h4>`;
            html += data.activities.map(log => {
                if (log.id > maxActivityId) maxActivityId = log.id;
                if (log.id > lastReadId) pendingCount++;

                const avatarUrl = log.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(log.user_name)}&background=f1f5f9&color=475569&size=80`;
                let desc = '';
                
                if (log.action === 'created') {
                    if (log.model_type === 'Transaction') desc = `menambahkan ${log.data?.type || 'transaksi'} baru`;
                    else if (log.model_type === 'Category') desc = `membuat kategori <strong>${log.data?.name}</strong>`;
                } else if (log.action === 'deleted') {
                    if (log.model_type === 'Transaction') desc = `menghapus transaksi`;
                    else if (log.model_type === 'Category') desc = `menghapus kategori <strong>${log.data?.name}</strong>`;
                } else if (log.action === 'updated') {
                    desc = `melakukan pembaruan`;
                } else {
                    desc = `beraktivitas dalam proyek`;
                }
                
                // Highlight unread items slightly
                const unreadClass = log.id > lastReadId ? 'bg-indigo-50/30 border-indigo-100' : 'border-transparent hover:border-slate-100';

                return `
                    <div class="flex items-start gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition border ${unreadClass}">
                        <img src="${avatarUrl}" class="w-8 h-8 rounded-full object-cover shrink-0 mt-0.5 border border-slate-100" alt="">
                        <div class="flex-1 min-w-0 text-slate-700 text-xs">
                            <span class="font-bold text-slate-900">${log.user_name}</span> ${desc}
                            <p class="text-[10px] text-slate-400 mt-1">${log.time}</p>
                        </div>
                    </div>
                `;
            }).join('');

            // Update localStorage to mark as read
            localStorage.setItem('catatin_last_read_activity_id', maxActivityId.toString());
        }
        
        if (!html) {
            html = '<div class="text-center text-slate-400 py-8 text-sm">Belum ada notifikasi baru</div>';
        }
        
        if (container) container.innerHTML = html;
        
        // Update badge
        if (pendingCount > 0) {
            if (badgeStatic) {
                badgeStatic.innerText = pendingCount > 99 ? '99+' : pendingCount;
                badgeStatic.classList.remove('hidden');
            }
        } else {
            if (badgeStatic) badgeStatic.classList.add('hidden');
        }
        
    } catch(e) {
        if (container) container.innerHTML = '<div class="text-center text-rose-500 py-8 text-sm">Gagal memuat notifikasi</div>';
    }
}

async function acceptInviteAction(id) {
    showToast('Menerima undangan...');
    try {
        const res = await fetch(`${window.baseUrl}/api/notifications/invites/${id}/accept`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' }
        });
        if (res.ok) {
            showToast('Undangan diterima!');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Gagal menerima undangan');
        }
    } catch(e) { showToast('Terjadi kesalahan'); }
}

async function declineInviteAction(id) {
    showToast('Menolak undangan...');
    try {
        const res = await fetch(`${window.baseUrl}/api/notifications/invites/${id}/decline`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Content-Type': 'application/json' }
        });
        if (res.ok) {
            showToast('Undangan ditolak');
            loadNotifications();
        } else {
            showToast('Gagal menolak undangan');
        }
    } catch(e) { showToast('Terjadi kesalahan'); }
}

// Initial check for notifications
document.addEventListener('DOMContentLoaded', () => {
    // Check notifications silently on load
    fetch(`${window.baseUrl}/api/notifications`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            let pendingCount = 0;
            if (data.invites && data.invites.length > 0) {
                pendingCount += data.invites.length;
            }
            
            const lastReadId = parseInt(localStorage.getItem('catatin_last_read_activity_id') || '0');
            if (data.activities && data.activities.length > 0) {
                const unreadActivities = data.activities.filter(a => a.id > lastReadId).length;
                pendingCount += unreadActivities;
            }

            const badgeStatic = document.getElementById('notif-badge-static');
            if (badgeStatic) {
                if (pendingCount > 0) {
                    badgeStatic.innerText = pendingCount > 99 ? '99+' : pendingCount;
                    badgeStatic.classList.remove('hidden');
                } else {
                    badgeStatic.classList.add('hidden');
                }
            }
        }).catch(e => console.error(e));
});

/* ================= PWA LOGIC ================= */
let deferredPrompt;

// Register Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('ServiceWorker registered'))
            .catch(err => console.log('ServiceWorker registration failed: ', err));
    });
}

// Check for iOS and show install elements manually if not installed (since iOS Safari doesn't support beforeinstallprompt)
window.addEventListener('load', () => {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;
    
    if (isIOS && !isStandalone) {
        const pwaBanner = document.getElementById('pwa-install-banner');
        if (pwaBanner) pwaBanner.classList.remove('hidden');
        
        const pwaSetting = document.getElementById('pwa-install-setting');
        if (pwaSetting) pwaSetting.classList.remove('hidden');
    }
});

// Catch beforeinstallprompt (Android / Chrome)
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    // Stash the event so it can be triggered later
    deferredPrompt = e;
    
    // Show the install UI elements
    const pwaBanner = document.getElementById('pwa-install-banner');
    if (pwaBanner) pwaBanner.classList.remove('hidden');
    
    const pwaSetting = document.getElementById('pwa-install-setting');
    if (pwaSetting) pwaSetting.classList.remove('hidden');
});

// Detect when the app is successfully installed
window.addEventListener('appinstalled', (evt) => {
    console.log('Catat-in App installed');
    
    // Hide the install UI elements
    const pwaBanner = document.getElementById('pwa-install-banner');
    if (pwaBanner) pwaBanner.classList.add('hidden');
    
    const pwaSetting = document.getElementById('pwa-install-setting');
    if (pwaSetting) pwaSetting.classList.add('hidden');
});

// Function triggered by the Install buttons
function installPWA() {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    if (isIOS) {
        showIosInstallInstructions();
        return;
    }
    
    if (!deferredPrompt) {
        showToast('Fitur install tidak tersedia di browser ini atau aplikasi sudah diinstall.');
        return;
    }
    // Show the install prompt
    deferredPrompt.prompt();
    
    // Wait for the user to respond to the prompt
    deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
            console.log('User accepted the install prompt');
            // Hide UI immediately since they accepted
            const pwaBanner = document.getElementById('pwa-install-banner');
            if (pwaBanner) pwaBanner.classList.add('hidden');
            const pwaSetting = document.getElementById('pwa-install-setting');
            if (pwaSetting) pwaSetting.classList.add('hidden');
        } else {
            console.log('User dismissed the install prompt');
        }
        // We can only use the prompt once, but if they dismissed, they might trigger it again on page reload.
        // Modern browsers usually reset it on reload.
        deferredPrompt = null;
    });
}

function showIosInstallInstructions() {
    const m = document.getElementById('ios-install-modal');
    if (m) {
        m.style.removeProperty('display');
        m.style.display = 'flex';
    }
}

function closeIosInstallModal() {
    const m = document.getElementById('ios-install-modal');
    if (m) {
        m.style.display = 'none';
    }
}

/* ================= PUSH NOTIFICATION SUBSCRIPTION ================= */

/**
 * Convert a URL-safe base64 string to Uint8Array.
 * Required for the applicationServerKey in pushManager.subscribe()
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

/**
 * Check push notification status on load and update the settings toggle
 */
function checkPushStatus() {
    if (!('Notification' in window) || !('serviceWorker' in navigator)) return;
    
    const statusText = document.getElementById('push-status-text');
    const toggleIcon = document.getElementById('push-toggle-icon');
    
    if (Notification.permission === 'granted') {
        if (statusText) statusText.textContent = 'Notifikasi aktif';
        if (toggleIcon) {
            toggleIcon.className = 'fas fa-toggle-on text-indigo-600 text-lg';
        }
    } else if (Notification.permission === 'denied') {
        if (statusText) statusText.textContent = 'Diblokir (ubah di pengaturan browser)';
        if (toggleIcon) {
            toggleIcon.className = 'fas fa-toggle-off text-red-400 text-lg';
        }
    } else {
        if (statusText) statusText.textContent = 'Ketuk untuk mengaktifkan';
        if (toggleIcon) {
            toggleIcon.className = 'fas fa-toggle-off text-slate-300 text-lg';
        }
    }
}

/**
 * Request permission and subscribe the user to push notifications
 */
async function enablePushNotifications() {
    if (!('Notification' in window)) {
        showToast('Browser Anda tidak mendukung notifikasi.');
        return;
    }
    if (!('serviceWorker' in navigator)) {
        showToast('Service Worker tidak tersedia.');
        return;
    }

    if (Notification.permission === 'denied') {
        showToast('Notifikasi diblokir. Silakan aktifkan di pengaturan browser Anda.');
        return;
    }

    if (Notification.permission === 'granted') {
        // Already granted, just re-subscribe to make sure
        await subscribeToPush();
        showToast('Notifikasi sudah aktif!');
        return;
    }

    // Request permission
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
        await subscribeToPush();
        showToast('Notifikasi HP berhasil diaktifkan! 🔔');
        checkPushStatus();
    } else {
        showToast('Izin notifikasi ditolak.');
    }
}

/**
 * Subscribe to push notifications and send subscription to server
 */
async function subscribeToPush() {
    const vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    if (!vapidPublicKey) {
        console.warn('VAPID public key not found in meta tag');
        return;
    }

    const registration = await navigator.serviceWorker.ready;

    // Check if already subscribed
    let subscription = await registration.pushManager.getSubscription();
    if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });
    }

    // Send subscription to Laravel server
    await fetch(window.baseUrl + '/api/push-subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
            endpoint: subscription.endpoint,
            keys: {
                p256dh: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('p256dh')))),
                auth: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('auth')))),
            }
        })
    });
}

// Check push status when page loads
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(checkPushStatus, 500);
});

/* ================= BACKUP & IMPORT ================= */
function openBackupRestoreModal() {
    const m = document.getElementById('backup-restore-modal');
    if(m) {
        // Load last backup info
        if (window.activeProject) {
            const lastBackup = localStorage.getItem('last_backup_' + window.activeProject.id);
            const infoBox = document.getElementById('last-backup-info');
            if (lastBackup && infoBox) {
                try {
                    const data = JSON.parse(lastBackup);
                    document.getElementById('last-backup-filename').textContent = data.filename;
                    document.getElementById('last-backup-date').textContent = data.date;
                    infoBox.classList.remove('hidden');
                } catch(e) {}
            } else if (infoBox) {
                infoBox.classList.add('hidden');
            }
        }
        
        m.style.removeProperty('display');
        m.style.display = 'flex';
    }
}

function closeBackupRestoreModal() {
    const m = document.getElementById('backup-restore-modal');
    if(m) m.style.display = 'none';
}

function backupData() {
    if(!window.activeProject) {
        showToast('Pilih proyek terlebih dahulu.');
        return;
    }
    
    // Generate filename for localStorage
    const projName = window.activeProject.name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    const now = new Date();
    const dd = String(now.getDate()).padStart(2, '0');
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const yyyy = now.getFullYear();
    const filename = `${projName.toUpperCase()}_${dd}_${mm}_${yyyy}_BACKUP.json`;
    
    // Format date string for display (e.g. 21/05/2026 14:30:00)
    const hh = String(now.getHours()).padStart(2, '0');
    const min = String(now.getMinutes()).padStart(2, '0');
    const sec = String(now.getSeconds()).padStart(2, '0');
    const dateStr = `${dd}/${mm}/${yyyy} ${hh}:${min}:${sec}`;
    
    localStorage.setItem('last_backup_' + window.activeProject.id, JSON.stringify({
        filename: filename,
        date: dateStr
    }));

    // Arahkan browser ke URL export, yang akan memaksa download file JSON
    window.location.href = window.baseUrl + '/api/projects/' + window.activeProject.id + '/export';
    
    closeBackupRestoreModal();

    // Tampilkan notifikasi setelah sedikit jeda karena proses download terjadi di background
    setTimeout(() => {
        showToast('Berhasil mengunduh backup JSON!');
    }, 1500);
}

/* ================= MIC HOLD LOGIC ================= */
document.addEventListener('DOMContentLoaded', () => {
    const fabMic = document.getElementById('fab-mic');
    const micWrapper = document.getElementById('mic-wrapper');
    if (!fabMic) return;
    
    let micHoldTimer;
    let isMicHolding = false;

    const startHold = (e) => {
        // Only prevent default for touch to avoid duplicate mouse events
        if(e.type === 'touchstart' && e.cancelable) e.preventDefault();
        
        isMicHolding = false;
        micHoldTimer = setTimeout(() => {
            isMicHolding = true;
            if (navigator.vibrate) navigator.vibrate(50);
            fabMic.classList.add('holding');
            if (micWrapper) micWrapper.style.zIndex = '2001';
            openVoiceModal(); 
        }, 300);
    };

    const endHold = (e) => {
        if((e.type === 'touchend' || e.type === 'touchcancel') && e.cancelable) e.preventDefault();
        
        clearTimeout(micHoldTimer);
        fabMic.classList.remove('holding');
        if (micWrapper) micWrapper.style.zIndex = '40';
        if (isMicHolding) {
            // Was holding, now release -> stop recording
            stopRecording();
        } else {
            // Was a short tap -> go to manual typing
            navigateTo('add');
        }
        isMicHolding = false;
    };

    // Events for mouse
    fabMic.addEventListener('mousedown', startHold);
    fabMic.addEventListener('mouseup', endHold);
    fabMic.addEventListener('mouseleave', () => {
        clearTimeout(micHoldTimer);
        fabMic.classList.remove('holding');
        if (micWrapper) micWrapper.style.zIndex = '40';
        if (isMicHolding) {
            stopRecording();
            isMicHolding = false;
        }
    });

    // Events for touch
    fabMic.addEventListener('touchstart', startHold, {passive: false});
    fabMic.addEventListener('touchend', endHold, {passive: false});
    fabMic.addEventListener('touchcancel', () => {
        clearTimeout(micHoldTimer);
        fabMic.classList.remove('holding');
        if (micWrapper) micWrapper.style.zIndex = '40';
        if (isMicHolding) {
            stopRecording();
            isMicHolding = false;
        }
    });
});

let tempRestoreFile = null;

function importData(input) {
    if(!window.activeProject) {
        showToast('Pilih proyek terlebih dahulu.');
        return;
    }
    const file = input.files[0];
    if(!file) {
        alert("Pilih file JSON terlebih dahulu.");
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        window.tempRestoreContent = e.target.result;
        closeBackupRestoreModal();
        
        const existingTxns = (appData && appData.transactions) ? appData.transactions.length : 0;
        if (existingTxns > 0) {
            const rom = document.getElementById('restore-option-modal');
            rom.style.removeProperty('display');
            rom.classList.add('restore-option-open');
        } else {
            processRestore(0);
        }
    };
    reader.onerror = function() {
        alert("Gagal membaca file dari HP Anda.");
    };
    reader.readAsText(file);
}

function closeRestoreOptionModal() {
    const rom = document.getElementById('restore-option-modal');
    rom.classList.remove('restore-option-open');
    rom.style.display = 'none';
    tempRestoreFile = null;
}

function closeRestoreSuccessModal() { document.getElementById('import-file').value = '';
    document.getElementById('restore-success-modal').style.display = 'none';
    window.location.reload();
}

function processRestore(overwriteFlag) {
    if(!window.tempRestoreContent) {
        alert("Tidak ada data file untuk di-restore.");
        return;
    }
    
    closeRestoreOptionModal();
    showToast('Memproses import data, mohon tunggu...');
    
    const formData = new FormData();
    formData.append('json_data', window.tempRestoreContent);
    formData.append('overwrite', overwriteFlag);
    
    fetch(window.baseUrl + '/api/projects/' + window.activeProject.id + '/import', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            let successTotal = (data.data.pemasukan || 0) + (data.data.pengeluaran || 0);
            document.getElementById('restore-stat-success').textContent = successTotal + " Data";
            document.getElementById('restore-stat-deleted').textContent = (data.data.terhapus || 0) + " Data";
            document.getElementById('restore-stat-failed').textContent = (data.data.gagal || 0) + " Data";
            
            document.getElementById('restore-success-modal').style.display = 'flex';
        } else {
            showToast('Gagal import: ' + (data.message || 'Format salah.'));
        }
    })
    .finally(() => { document.getElementById('import-file').value = ''; }).catch(err => {
        console.error(err);
        showToast('Terjadi kesalahan sistem saat import.');
    });
}

// --- CLOUD BACKUP ---
async function cloudBackupData() {
    const activeProjectId = window.activeProject ? window.activeProject.id : null;
    if (!activeProjectId) {
        showToast('Proyek tidak aktif');
        return;
    }
    if (!confirm('Simpan backup transaksi ke Cloud Server? Backup sebelumnya (jika ada) akan ditimpa.')) return;

    showToast('Sedang menyimpan ke Cloud...');
    
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${activeProjectId}/cloud-backup`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
            loadCloudBackups();
        } else {
            showToast(data.message || 'Gagal backup');
        }
    } catch (e) {
        showToast('Terjadi kesalahan koneksi');
    }
}

async function loadCloudBackups() {
    const activeProjectId = window.activeProject ? window.activeProject.id : null;
    if (!activeProjectId) return;
    
    // Set toggle state
    const toggle = document.getElementById('cloud-backup-toggle');
    if (toggle) {
        toggle.checked = window.activeProject.is_cloud_backup_enabled == 1;
    }

    const list = document.getElementById('cloud-backups-list');
    if (!list) return;

    list.innerHTML = `<div class="p-4 text-center text-xs text-slate-400"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat data...</div>`;

    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${activeProjectId}/cloud-backups`);
        const data = await res.json();

        if (data.success) {
            if (data.backups.length === 0) {
                list.innerHTML = `<div class="p-4 text-center text-xs text-slate-400">Belum ada file backup tersimpan di cloud.</div>`;
                return;
            }

            list.innerHTML = '';
            data.backups.forEach(backup => {
                const isAuto = backup.name.includes('_AUTO_BACKUP');
                const badge = isAuto 
                    ? `<span class="bg-brand-100 text-brand-700 text-[9px] px-1.5 py-0.5 rounded-full ml-2">Otomatis</span>`
                    : `<span class="bg-slate-100 text-slate-600 text-[9px] px-1.5 py-0.5 rounded-full ml-2">Manual</span>`;

                list.innerHTML += `
                    <div class="px-4 py-3 border-b border-slate-50 flex items-center justify-between hover:bg-slate-50 transition">
                        <div>
                            <div class="font-bold text-slate-700 text-xs flex items-center">${backup.date} ${badge}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">${backup.size} • ${backup.name}</div>
                        </div>
                        <button onclick="restoreCloudBackup('${backup.name}')" class="bg-orange-100 hover:bg-orange-200 text-orange-700 p-2 rounded-xl text-xs font-bold transition flex items-center gap-1">
                            <i class="fas fa-cloud-download-alt"></i> Restore
                        </button>
                    </div>
                `;
            });
        }
    } catch (e) {
        list.innerHTML = `<div class="p-4 text-center text-xs text-rose-400">Gagal memuat list backup</div>`;
    }
}

async function toggleCloudBackup() {
    const activeProjectId = window.activeProject ? window.activeProject.id : null;
    if (!activeProjectId) return;
    
    const toggle = document.getElementById('cloud-backup-toggle');
    if (!toggle) return;
    
    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${activeProjectId}/toggle-cloud-backup`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        
        if (data.success) {
            window.activeProject.is_cloud_backup_enabled = data.is_enabled;
            if (data.is_enabled) {
                showToast('Data Anda aman, tersimpan di-backup setiap Senin pukul 3 pagi');
            } else {
                showToast(data.message);
            }
        } else {
            toggle.checked = !toggle.checked; // revert
            showToast(data.message || 'Gagal mengubah pengaturan backup');
        }
    } catch(e) {
        toggle.checked = !toggle.checked; // revert
        showToast('Terjadi kesalahan koneksi');
    }
}

async function restoreCloudBackup(fileName) {
    const activeProjectId = window.activeProject ? window.activeProject.id : null;
    if (!activeProjectId) return;
    if (!confirm('PERHATIAN: Restore data ini akan menimpa (menghapus) seluruh transaksi Anda saat ini. Lanjutkan?')) return;

    showToast('Sedang me-restore data...');

    try {
        const res = await fetch(`${window.baseUrl}/api/projects/${activeProjectId}/cloud-restore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ file_name: fileName })
        });
        
        const data = await res.json();
        if (data.success) {
            showToast('Restore berhasil! Memuat ulang...');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Gagal restore data');
        }
    } catch (e) {
        showToast('Terjadi kesalahan koneksi');
    }
}

// Intercept navigateTo to load backups when backup page opens
const originalNavigateTo = window.navigateTo;
window.navigateTo = function(pageId, pushState = true) {
    if (originalNavigateTo) {
        originalNavigateTo(pageId, pushState);
    }
    if (pageId === 'backup') {
        loadCloudBackups();
    }
}
window.showPage = window.navigateTo;



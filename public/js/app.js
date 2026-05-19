/**
 * app.js - UI Logic for Redesigned App
 */

let currentPage = 'home';
let mainChart = null;
let currentBreakdownTab = 'pengeluaran';
let editTxnId = null;
let currentDateOffset = 0;

document.addEventListener('DOMContentLoaded', () => {
    initUI();
    initEvents();
    initVoice();
    updateDashboard();
});

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

function navigateTo(pageId) {

    // Nav active state
    document.querySelectorAll('.bottom-nav-item').forEach(el => el.classList.remove('active'));
    const navItem = document.getElementById('nav-' + pageId);
    if(navItem && navItem.classList.contains('bottom-nav-item')) {
        navItem.classList.add('active');
    }

    // Page active state
    document.querySelectorAll('.page').forEach(el => el.classList.remove('active'));
    document.getElementById('page-' + pageId).classList.add('active');

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

    if (editTxnId) {
        updateTransaction(editTxnId, { type, amount, category, desc, date });
        showToast('Transaksi berhasil diperbarui!');
        editTxnId = null;
    } else {
        addTransaction({ type, amount, category, desc, date });
        showToast('Transaksi berhasil disimpan!');
    }
    
    document.getElementById('txn-form').reset();
    document.getElementById('txn-amount').value = '';
    
    // reset date
    document.getElementById('txn-date').value = new Date().toISOString().split('T')[0];
    
    // Update dashboard since we might be navigating back
    updateDashboard();
    
    navigateTo('home');
}

/* ================= HOME ================= */
function updateDashboard() {
    const { totalIn, totalOut, balance } = calculateBalances();
    
    document.getElementById('home-balance').textContent = formatRupiah(balance);
    
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
    renderBreakdown(period);
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
    const title = document.getElementById('breakdown-title');
    
    if(type === 'pengeluaran') {
        btnOut.classList.add('bg-white', 'shadow-sm', 'text-slate-900');
        btnOut.classList.remove('text-slate-500');
        btnIn.classList.remove('bg-white', 'shadow-sm', 'text-slate-900');
        btnIn.classList.add('text-slate-500');
        title.textContent = 'Rincian (Pengeluaran)';
    } else {
        btnIn.classList.add('bg-white', 'shadow-sm', 'text-slate-900');
        btnIn.classList.remove('text-slate-500');
        btnOut.classList.remove('bg-white', 'shadow-sm', 'text-slate-900');
        btnOut.classList.add('text-slate-500');
        title.textContent = 'Rincian (Pemasukan)';
    }
    
    // Find active period
    let activePeriod = 'month';
    document.querySelectorAll('.pill-tab').forEach(b => {
        if(b.classList.contains('active')) activePeriod = b.dataset.period;
    });
    
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
        });
    } catch(err) {
        console.error('pdfmake error:', err);
        showToast('Gagal membuat PDF: ' + err.message);
    }
}

function renderBarChart(period) {
    const ctx = document.getElementById('main-chart');
    if(!ctx) return;

    if(mainChart) mainChart.destroy();
    
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
        for (let y = minYear; y <= maxYear; y++) {
            labels.push(y);
            outData.push(0);
            inData.push(0);
        }
        txns.forEach(t => {
            const idx = new Date(t.date).getFullYear() - minYear;
            const amt = parseFloat(t.amount) || 0;
            if(t.type === 'pengeluaran') outData[idx] += amt;
            else inData[idx] += amt;
        });
    }

    mainChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pengeluaran',
                    data: outData,
                    backgroundColor: '#2563eb',
                    borderRadius: 4,
                    barPercentage: 0.6
                },
                {
                    label: 'Pemasukan',
                    data: inData,
                    backgroundColor: '#e2e8f0',
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
        
        html += `
        <div>
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

/* ================= WALLET ================= */
function updateWallet() {
    let txns = [...appData.transactions];
    const typeF = document.getElementById('wallet-filter-type')?.value || 'all';
    const catF = document.getElementById('wallet-filter-cat')?.value || 'all';
    const sortVal = document.getElementById('wallet-sort')?.value || 'newest';
    
    if(typeF !== 'all') txns = txns.filter(t => t.type === typeF);
    if(catF !== 'all') txns = txns.filter(t => t.category === catF);
    
    txns.sort((a, b) => {
        if (sortVal === 'newest') return new Date(b.date) - new Date(a.date);
        if (sortVal === 'oldest') return new Date(a.date) - new Date(b.date);
        if (sortVal === 'highest') return parseFloat(b.amount) - parseFloat(a.amount);
        if (sortVal === 'lowest') return parseFloat(a.amount) - parseFloat(b.amount);
        if (sortVal === 'az') return (a.desc || a.category).localeCompare(b.desc || b.category);
        if (sortVal === 'za') return (b.desc || b.category).localeCompare(a.desc || a.category);
        return 0;
    });
    
    const list = document.getElementById('wallet-txn-list');
    if(txns.length === 0) {
        list.innerHTML = `<div class="p-6 text-center text-slate-500 text-sm">Tidak ada transaksi ditemukan.</div>`;
    } else {
        // Show delete button only in wallet page
        list.innerHTML = txns.map(t => createTxnListItem(t, true)).join('');
    }
}

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
    
    const amtColor = isOut ? 'text-slate-900' : 'text-emerald-500';
    const sign = isOut ? '-' : '+';
    
    // Avatar background color based on text
    const bgColors = ['bg-rose-100 text-rose-600', 'bg-blue-100 text-blue-600', 'bg-emerald-100 text-emerald-600', 'bg-purple-100 text-purple-600', 'bg-orange-100 text-orange-600'];
    const colorIdx = t.category.charCodeAt(0) % bgColors.length;
    const bgClass = bgColors[colorIdx];
    
    const dateStr = new Date(t.date).toLocaleDateString('id-ID', {day:'2-digit', month:'short'});
    const actionBtns = showDelete ? `
        <div class="flex items-center ml-2">
            <button onclick="handleEdit('${t.id}')" class="text-blue-400 bg-blue-50 w-7 h-7 rounded-full flex items-center justify-center shrink-0 hover:bg-blue-100 transition mr-1">
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
                <h4 class="text-sm font-bold text-slate-900 leading-tight truncate">${t.desc || t.category}</h4>
                <p class="text-[11px] text-slate-500 mt-0.5">${t.category} • ${dateStr}</p>
            </div>
        </div>
        <div class="flex items-center shrink-0 ml-2">
            <span class="text-sm font-bold ${amtColor} whitespace-nowrap">${sign}${formatRupiah(t.amount)}</span>
            ${actionBtns}
        </div>
    </div>
    `;
}

function handleEdit(id) {
    const t = appData.transactions.find(x => x.id === id);
    if(!t) return;
    
    document.getElementById('edit-txn-id').value = id;
    document.getElementById('edit-txn-type').value = t.type;
    
    // Set form fields
    document.getElementById('edit-txn-amount').value = parseFloat(t.amount).toLocaleString('id-ID');
    
    // Set category options
    const catSelect = document.getElementById('edit-txn-category');
    catSelect.innerHTML = '';
    const cats = appData.categories[t.type] || [];
    cats.forEach(c => {
        catSelect.innerHTML += `<option value="${c.name}">${c.name}</option>`;
    });
    
    // Select the category
    let hasCat = false;
    for (let i = 0; i < catSelect.options.length; i++) {
        if (catSelect.options[i].value === t.category) {
            catSelect.selectedIndex = i;
            hasCat = true;
            break;
        }
    }
    if (!hasCat && catSelect.options.length > 0) catSelect.selectedIndex = 0;
    
    document.getElementById('edit-txn-desc').value = t.desc || '';
    document.getElementById('edit-txn-date').value = t.date.split('T')[0];
    
    const modal = document.getElementById('edit-txn-modal');
    modal.style.display = 'flex';
}

function closeEditTxnModal() {
    document.getElementById('edit-txn-modal').style.display = 'none';
    document.getElementById('edit-txn-form').reset();
}

function submitEditTxn(e) {
    e.preventDefault();
    const id = document.getElementById('edit-txn-id').value;
    const type = document.getElementById('edit-txn-type').value;
    const amountStr = document.getElementById('edit-txn-amount').value.replace(/\./g, '');
    const amount = parseFloat(amountStr);
    const category = document.getElementById('edit-txn-category').value;
    const desc = document.getElementById('edit-txn-desc').value;
    const date = document.getElementById('edit-txn-date').value;

    if (!amount || !category || !date) {
        showToast('Harap isi semua kolom');
        return;
    }

    updateTransaction(id, { type, amount, category, desc, date });
    showToast('Transaksi berhasil diperbarui!');
    
    closeEditTxnModal();
    updateWallet();
    updateDashboard(); // Also refresh home
}

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
    if (!parsed || parsed.amount === 0) {
        showToast('Nominal tidak dikenali. Silakan input manual.');
        navigateTo('add');
        return;
    }

    // Pre-fill the form instead of saving directly
    setTxnType(parsed.type);
    document.getElementById('txn-amount').value = parsed.amount.toLocaleString('id-ID');
    
    const catSelect = document.getElementById('txn-category');
    let hasCat = false;
    for (let i = 0; i < catSelect.options.length; i++) {
        if (catSelect.options[i].value === parsed.category) {
            catSelect.selectedIndex = i;
            hasCat = true;
            break;
        }
    }
    if (!hasCat) catSelect.selectedIndex = 0;

    document.getElementById('txn-desc').value = parsed.desc;
    document.getElementById('txn-date').value = new Date().toISOString().split('T')[0];

    showToast('Harap tinjau kembali sebelum menyimpan.');
    navigateTo('add');
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
        
        html += `
        <div class="flex items-center justify-between p-3 border-b border-slate-50 last:border-0 hover:bg-slate-50 transition">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 text-lg">
                    ${iconHtml}
                </div>
                <div>
                    <span class="font-bold text-slate-900 text-sm">${c.name}</span>
                    ${isDefault ? '<span class="text-[10px] text-slate-400 ml-2">bawaan</span>' : ''}
                </div>
            </div>
            <button onclick="openEditCatModal('${c.id}', '${type}', '${c.name.replace(/'/g, '&#39;')}')" class="text-slate-400 p-2 hover:bg-slate-100 rounded-full transition">
                <i class="fas fa-ellipsis-v text-sm"></i>
            </button>
        </div>`;
    });
    
    list.innerHTML = html;
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
        }).catch(err => console.error(err));
    }
}

function openEditCatModal(id, type, name) {
    document.getElementById('edit-cat-id').value = id;
    document.getElementById('edit-cat-type-val').value = type;
    document.getElementById('edit-cat-name').value = name;
    const modal = document.getElementById('edit-cat-modal');
    modal.style.removeProperty('display');
    modal.classList.add('edit-cat-modal-open');
}

function closeEditCatModal() {
    const modal = document.getElementById('edit-cat-modal');
    modal.style.display = 'none';
    modal.classList.remove('edit-cat-modal-open');
}

function confirmEditCategory() {
    const id = document.getElementById('edit-cat-id').value;
    const type = document.getElementById('edit-cat-type-val').value;
    const newName = document.getElementById('edit-cat-name').value.trim();
    if (!newName) return;
    
    const cat = appData.categories[type].find(c => c.id == id);
    if (cat) {
        cat.name = newName;
        saveData(appData);
        updateCategoriesPage();
        populateCategorySelect();
        populateFilterCat();
        showToast('Kategori berhasil diperbarui');
    }
    closeEditCatModal();
}

function confirmDeleteCategory() {
    const id = document.getElementById('edit-cat-id').value;
    const type = document.getElementById('edit-cat-type-val').value;
    closeEditCatModal();
    handleDeleteCategory(id, type);
}

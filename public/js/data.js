/**
 * data.js
 * Handle localStorage and basic data operations for Catat-in
 */

const STORAGE_KEY = 'catatin_data_v1';

// Default data structure
const DEFAULT_DATA = {
  profile: {
    name: 'Bos UMKM',
    business: 'Usaha Mandiri',
    initialBalance: 0
  },
  categories: {
    pemasukan: [
      { id: 'in_1', name: 'Penjualan', icon: 'fas fa-store' },
      { id: 'in_2', name: 'Modal', icon: 'fas fa-wallet' },
      { id: 'in_3', name: 'Lain-lain', icon: 'fas fa-star' }
    ],
    pengeluaran: [
      { id: 'out_1', name: 'Bahan Baku', icon: 'fas fa-box' },
      { id: 'out_2', name: 'Utilitas (Listrik/Air)', icon: 'fas fa-bolt' },
      { id: 'out_3', name: 'Karyawan', icon: 'fas fa-users' },
      { id: 'out_4', name: 'Transportasi', icon: 'fas fa-motorcycle' },
      { id: 'out_5', name: 'Sewa', icon: 'fas fa-home' },
      { id: 'out_6', name: 'Lain-lain', icon: 'fas fa-money-bill-wave' }
    ]
  },
  transactions: []
};

// Initialize or load data
// Use backend data if available
function loadData() {
  if (window.authUser) {
    const user = window.authUser;
    let inCats = DEFAULT_DATA.categories.pemasukan;
    let outCats = DEFAULT_DATA.categories.pengeluaran;
    
    if (user.categories && user.categories.length > 0) {
       inCats = user.categories.filter(c => c.type === 'pemasukan');
       outCats = user.categories.filter(c => c.type === 'pengeluaran');
    }
    
    return {
      profile: {
        name: user.name,
        business: 'Usaha ' + user.name,
        initialBalance: 0
      },
      categories: {
        pemasukan: inCats,
        pengeluaran: outCats
      },
      transactions: user.transactions || []
    };
  }

  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored) {
    try {
      return JSON.parse(stored);
    } catch (e) {
      console.error('Error loading data', e);
      return JSON.parse(JSON.stringify(DEFAULT_DATA));
    }
  }
  return JSON.parse(JSON.stringify(DEFAULT_DATA));
}

function saveData(data) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

let appData = loadData();

// Get CSRF Token
function getCsrfToken() {
   const tokenMeta = document.querySelector('meta[name="csrf-token"]');
   return tokenMeta ? tokenMeta.getAttribute('content') : '';
}

// Format Currency
function formatRupiah(amount) {
  let num = Number(amount);
  if (isNaN(num)) num = 0;
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(num);
}

// Format Date
function formatDate(dateStr) {
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateStr).toLocaleDateString('id-ID', options);
}

function formatTime(dateStr) {
  return new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

// Transaction CRUD
function generateId() {
  return 'txn_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
}

function addTransaction(txn) {
  const newTxn = {
    ...txn,
    id: generateId(),
    createdAt: new Date().toISOString()
  };
  appData.transactions.unshift(newTxn);
  // Sort by date desc, then by createdAt desc
  appData.transactions.sort((a, b) => {
    if (a.date === b.date) {
      return new Date(b.createdAt) - new Date(a.createdAt);
    }
    return new Date(b.date) - new Date(a.date);
  });
  saveData(appData);

  // Sync to Backend
  if (window.authUser) {
     const url = window.baseUrl ? window.baseUrl + '/api/transactions' : '/api/transactions';
     fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        body: JSON.stringify(newTxn)
     }).then(r => r.json()).then(data => {
        // Replace temp ID with real DB ID
        newTxn.id = data.id;
     }).catch(e => console.error('Failed to sync transaction', e));
  }

  return newTxn;
}

function deleteTransaction(id) {
  appData.transactions = appData.transactions.filter(t => t.id != id);
  saveData(appData);

  // Sync to Backend
  if (window.authUser && !String(id).startsWith('txn_')) {
     const url = window.baseUrl ? window.baseUrl + `/api/transactions/${id}` : `/api/transactions/${id}`;
     fetch(url, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() }
     }).catch(e => console.error('Failed to delete transaction', e));
  }
}

function updateTransaction(id, updatedData) {
  const idx = appData.transactions.findIndex(t => t.id == id);
  if (idx !== -1) {
    appData.transactions[idx] = { ...appData.transactions[idx], ...updatedData };
    
    // Resort transactions
    appData.transactions.sort((a, b) => {
      if (a.date === b.date) {
        return new Date(b.createdAt) - new Date(a.createdAt);
      }
      return new Date(b.date) - new Date(a.date);
    });
    
    saveData(appData);

    // Sync to Backend
    if (window.authUser && !String(id).startsWith('txn_')) {
       const url = window.baseUrl ? window.baseUrl + `/api/transactions/${id}` : `/api/transactions/${id}`;
       fetch(url, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
          body: JSON.stringify(appData.transactions[idx])
       }).catch(e => console.error('Failed to update transaction', e));
    }
  }
}

function getCategoryIcon(catName, type) {
  const cats = appData.categories[type] || [];
  const found = cats.find(c => c.name === catName);
  return found ? found.icon : (type === 'pemasukan' ? 'fas fa-arrow-down' : 'fas fa-arrow-up');
}

// Analytics Helpers
function calculateBalances(transactions = appData.transactions) {
  let totalIn = 0;
  let totalOut = 0;

  transactions.forEach(t => {
    let amt = parseFloat(t.amount);
    if (isNaN(amt)) amt = 0;
    
    if (t.type === 'pemasukan') totalIn += amt;
    else if (t.type === 'pengeluaran') totalOut += amt;
  });

  return {
    totalIn,
    totalOut,
    balance: appData.profile.initialBalance + totalIn - totalOut
  };
}

function getTodayTransactions() {
  const today = new Date().toISOString().split('T')[0];
  return appData.transactions.filter(t => t.date.startsWith(today));
}

function filterTransactions(period, offset = 0) {
  const now = new Date();
  const txns = appData.transactions;

  if (period === 'all') return txns;

  return txns.filter(t => {
    const d = new Date(t.date);
    if (period === 'today') {
      return d.toDateString() === now.toDateString();
    }
    if (period === 'week') {
      // 7 days interval shifted by offset
      const end = new Date(now.getTime() + (offset * 7 * 24 * 60 * 60 * 1000));
      const start = new Date(end.getTime() - 7 * 24 * 60 * 60 * 1000);
      return d > start && d <= end;
    }
    if (period === 'month') {
      const targetMonth = new Date(now.getFullYear(), now.getMonth() + offset, 1);
      return d.getMonth() === targetMonth.getMonth() && d.getFullYear() === targetMonth.getFullYear();
    }
    if (period === 'year') {
      return d.getFullYear() === now.getFullYear() + offset;
    }
    if (period === '3month') {
      const past3 = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
      return d >= past3 && d <= now;
    }
    return true;
  });
}

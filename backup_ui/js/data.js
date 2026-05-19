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
      { id: 'in_1', name: 'Penjualan', icon: '🛍️' },
      { id: 'in_2', name: 'Modal', icon: '💰' },
      { id: 'in_3', name: 'Lain-lain', icon: '✨' }
    ],
    pengeluaran: [
      { id: 'out_1', name: 'Bahan Baku', icon: '📦' },
      { id: 'out_2', name: 'Utilitas (Listrik/Air)', icon: '⚡' },
      { id: 'out_3', name: 'Karyawan', icon: '👥' },
      { id: 'out_4', name: 'Transportasi', icon: '🛵' },
      { id: 'out_5', name: 'Sewa', icon: '🏠' },
      { id: 'out_6', name: 'Lain-lain', icon: '💸' }
    ]
  },
  transactions: []
};

// Initialize or load data
function loadData() {
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

// Format Currency
function formatRupiah(amount) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount);
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
  return newTxn;
}

function deleteTransaction(id) {
  appData.transactions = appData.transactions.filter(t => t.id !== id);
  saveData(appData);
}

function getCategoryIcon(catName, type) {
  const cats = appData.categories[type] || [];
  const found = cats.find(c => c.name === catName);
  return found ? found.icon : (type === 'pemasukan' ? '💰' : '💸');
}

// Analytics Helpers
function calculateBalances(transactions = appData.transactions) {
  let totalIn = 0;
  let totalOut = 0;

  transactions.forEach(t => {
    if (t.type === 'pemasukan') totalIn += t.amount;
    else if (t.type === 'pengeluaran') totalOut += t.amount;
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

function filterTransactions(period) {
  const now = new Date();
  const txns = appData.transactions;

  if (period === 'all') return txns;

  return txns.filter(t => {
    const d = new Date(t.date);
    if (period === 'today') {
      return d.toDateString() === now.toDateString();
    }
    if (period === 'week') {
      const pastWeek = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
      return d >= pastWeek && d <= now;
    }
    if (period === 'month') {
      return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    }
    if (period === '3month') {
      const past3 = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
      return d >= past3 && d <= now;
    }
    return true;
  });
}

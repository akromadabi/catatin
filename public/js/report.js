/**
 * report.js
 * Chart rendering and analytics using Chart.js
 */

let barChartInstance = null;
let pieOutInstance = null;
let pieInInstance = null;

function renderCharts(period = 'week') {
  const txns = filterTransactions(period);
  
  // Group Data by Date for Bar Chart
  const dateMap = {};
  const today = new Date();
  
  // Initialize map based on period
  if (period === 'week') {
    for (let i = 6; i >= 0; i--) {
      const d = new Date(today.getTime() - i * 24 * 60 * 60 * 1000);
      dateMap[d.toISOString().split('T')[0]] = { in: 0, out: 0 };
    }
  } else if (period === 'month') {
     const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
     for(let i=1; i<=daysInMonth; i++) {
        const d = new Date(today.getFullYear(), today.getMonth(), i);
        // format YYYY-MM-DD local
        const str = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
        dateMap[str] = { in:0, out:0 };
     }
  } else {
     // dynamic
     txns.forEach(t => {
        const d = t.date;
        if(!dateMap[d]) dateMap[d] = { in:0, out:0 };
     });
  }

  // Populate data
  txns.forEach(t => {
    const d = t.date;
    if (dateMap[d]) {
      if (t.type === 'pemasukan') dateMap[d].in += t.amount;
      if (t.type === 'pengeluaran') dateMap[d].out += t.amount;
    }
  });

  const labels = Object.keys(dateMap).sort();
  const inData = labels.map(l => dateMap[l].in);
  const outData = labels.map(l => dateMap[l].out);

  // Formatting dates for labels
  const shortLabels = labels.map(l => {
     const d = new Date(l);
     return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
  });

  // Render Bar Chart
  const ctxBar = document.getElementById('bar-chart');
  if (ctxBar) {
    if (barChartInstance) barChartInstance.destroy();
    barChartInstance = new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: shortLabels,
        datasets: [
          { label: 'Pemasukan', data: inData, backgroundColor: '#10b981', borderRadius: 4 },
          { label: 'Pengeluaran', data: outData, backgroundColor: '#ef4444', borderRadius: 4 }
        ]
      },
      options: {
        responsive: true,
        plugins: {
           legend: { labels: { color: '#f1f5f9' } }
        },
        scales: {
          y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
          x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
        }
      }
    });
  }

  // Render Mini Chart for Dashboard
  const ctxMini = document.getElementById('mini-chart');
  if (ctxMini && period === 'week') {
    if (window.miniChartInstance) window.miniChartInstance.destroy();
    window.miniChartInstance = new Chart(ctxMini, {
      type: 'line',
      data: {
        labels: shortLabels,
        datasets: [
          { label: 'Pemasukan', data: inData, borderColor: '#10b981', tension: 0.4, borderWidth: 2, pointRadius: 0 },
          { label: 'Pengeluaran', data: outData, borderColor: '#ef4444', tension: 0.4, borderWidth: 2, pointRadius: 0 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { display: false, min: 0 },
          x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 } } }
        }
      }
    });
  }

  // Render Pie Charts
  renderPieChart('pengeluaran', txns, 'pie-chart-out', '#ef4444');
  renderPieChart('pemasukan', txns, 'pie-chart-in', '#10b981');
}

function renderPieChart(type, txns, canvasId, colorBase) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  const dataMap = {};
  txns.filter(t => t.type === type).forEach(t => {
    if (!dataMap[t.category]) dataMap[t.category] = 0;
    dataMap[t.category] += t.amount;
  });

  const labels = Object.keys(dataMap);
  const data = Object.values(dataMap);

  // Generate colors based on base color
  const colors = [
    colorBase,
    colorBase === '#ef4444' ? '#f87171' : '#34d399',
    colorBase === '#ef4444' ? '#fca5a5' : '#6ee7b7',
    colorBase === '#ef4444' ? '#991b1b' : '#047857',
    colorBase === '#ef4444' ? '#7f1d1d' : '#064e3b',
    '#94a3b8'
  ];

  const instanceVar = type === 'pengeluaran' ? pieOutInstance : pieInInstance;
  if (instanceVar) instanceVar.destroy();

  const newInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: colors.slice(0, labels.length),
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'right', labels: { color: '#f1f5f9', font: { size: 11 } } }
      },
      cutout: '70%'
    }
  });

  if (type === 'pengeluaran') pieOutInstance = newInstance;
  else pieInInstance = newInstance;
}

function generateInsights(period = 'month') {
  const txns = filterTransactions(period);
  const list = document.getElementById('insight-list');
  if(!list) return;

  if (txns.length < 3) {
    list.innerHTML = '<p class="insight-empty">Tambah lebih banyak transaksi untuk mendapatkan insight yang akurat.</p>';
    return;
  }

  let inTotal = 0;
  let outTotal = 0;
  const outCats = {};

  txns.forEach(t => {
     if(t.type === 'pemasukan') inTotal += t.amount;
     if(t.type === 'pengeluaran') {
        outTotal += t.amount;
        if(!outCats[t.category]) outCats[t.category] = 0;
        outCats[t.category] += t.amount;
     }
  });

  const html = [];

  // Insight 1: Profit/Loss
  if (inTotal > outTotal) {
     html.push(`<p>Keren! Usahamu menghasilkan untung bersih sebesar <strong>${formatRupiah(inTotal - outTotal)}</strong> di periode ini.</p>`);
  } else if (outTotal > inTotal) {
     html.push(`<p>Waspada! Pengeluaranmu lebih besar dari pemasukan. Selisih <strong>${formatRupiah(outTotal - inTotal)}</strong>.</p>`);
  }

  // Insight 2: Biggest expense
  let maxCat = '';
  let maxCatAmt = 0;
  for(let c in outCats) {
     if(outCats[c] > maxCatAmt) {
        maxCatAmt = outCats[c];
        maxCat = c;
     }
  }

  if (maxCatAmt > 0) {
     const p = (maxCatAmt / outTotal * 100).toFixed(0);
     html.push(`<p>Pengeluaran terbesar adalah <strong>${maxCat}</strong> (${p}% dari total pengeluaran). Coba evaluasi pos ini.</p>`);
  }

  // Insight 3: Ratio
  if (inTotal > 0 && outTotal > 0) {
     const ratio = (outTotal / inTotal * 100).toFixed(0);
     if (ratio < 50) html.push(`<p>Sangat Sehat! Biaya operasionalmu hanya ${ratio}% dari omset.</p>`);
     else if (ratio > 80) html.push(`<p>Hati-hati, biaya operasional mencapai ${ratio}% dari omset. Margin keuntungan sangat tipis.</p>`);
  }

  list.innerHTML = html.join('');
}

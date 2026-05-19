/**
 * voice.js
 * Handle Web Speech API and basic NLP for parsing transactions
 */

const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
let recognition;
let isRecording = false;

// NLP dictionaries
const DICT = {
  inKeywords: ['dapat', 'terima', 'masuk', 'hasil', 'cair', 'pendapatan', 'transferan', 'bayar dari', 'customer bayar', 'pelanggan bayar'],
  outKeywords: ['bayar', 'beli', 'keluar', 'pengeluaran', 'belanja', 'tagihan', 'kasih', 'setor'],
  multipliers: {
    'ribu': 1000,
    'ribuan': 1000,
    'juta': 1000000,
    'jutaan': 1000000,
    'miliar': 1000000000
  }
};

function initVoice() {
  if (!SpeechRecognition) {
    console.error('Speech Recognition API not supported in this browser.');
    return false;
  }
  
  recognition = new SpeechRecognition();
  recognition.lang = 'id-ID';
  recognition.interimResults = true;
  recognition.maxAlternatives = 1;

  recognition.onstart = () => {
    isRecording = true;
    if (window.onVoiceStart) window.onVoiceStart();
  };

  recognition.onresult = (event) => {
    let finalTranscript = '';
    let interimTranscript = '';

    for (let i = event.resultIndex; i < event.results.length; ++i) {
      if (event.results[i].isFinal) {
        finalTranscript += event.results[i][0].transcript;
      } else {
        interimTranscript += event.results[i][0].transcript;
      }
    }
    
    if (window.onVoiceResult) {
      window.onVoiceResult(interimTranscript || finalTranscript, !!finalTranscript);
    }
  };

  recognition.onerror = (event) => {
    console.error('Speech recognition error', event.error);
    stopRecording();
    if (window.onVoiceError) window.onVoiceError(event.error);
  };

  recognition.onend = () => {
    isRecording = false;
    if (window.onVoiceEnd) window.onVoiceEnd();
  };

  return true;
}

function startRecording() {
  if (recognition && !isRecording) {
    try {
      recognition.start();
    } catch(e) {
      console.error(e);
    }
  }
}

function stopRecording() {
  if (recognition && isRecording) {
    recognition.stop();
  }
}

/**
 * Basic NLP Parser to extract transaction data from text
 * e.g., "bayar listrik 150 ribu" -> { type: 'pengeluaran', amount: 150000, category: 'Utilitas', desc: 'bayar listrik' }
 */
function parseTransactionText(text) {
  text = text.toLowerCase().trim();
  if (!text) return null;

  let type = 'pengeluaran'; // Default
  let amount = 0;
  let category = '';
  let desc = text;

  // 1. Determine Type
  for (let kw of DICT.inKeywords) {
    if (text.includes(kw)) {
      type = 'pemasukan';
      break;
    }
  }

  // 2. Extract Amount
  // Match numbers and potential multipliers (e.g., 150 ribu, 2.5 juta, 50000)
  const regexNum = /([\d\.,]+)\s*(ribu|juta|miliar)?/gi;
  let match;
  let maxAmount = 0;
  
  while ((match = regexNum.exec(text)) !== null) {
    let numStr = match[1].replace(/\./g, '').replace(/,/g, '.'); // Handle ID locale numbers
    let num = parseFloat(numStr);
    
    if (isNaN(num)) continue;

    let mult = match[2] ? match[2].toLowerCase() : '';
    if (DICT.multipliers[mult]) {
      num *= DICT.multipliers[mult];
    } else if (num < 1000 && !mult) {
       // if user says "seratus lima puluh" often speech to text writes "150"
       // but in context of IDR, 150 usually means 150.000 if not specified.
       // Let's keep it literal for now to avoid wrong guesses, unless it's very small
       if(num > 0 && num < 1000) num *= 1000; 
    }

    if (num > maxAmount) maxAmount = num;
  }
  
  // Try matching words like "seratus ribu" if numbers fail (simplified)
  if (maxAmount === 0) {
     if(text.includes('cepek')) maxAmount = 100000;
     if(text.includes('goceng')) maxAmount = 5000;
     if(text.includes('ceban')) maxAmount = 10000;
  }

  amount = maxAmount;

  // Clean description from amount if found
  if (amount > 0) {
    // Basic cleanup - just use the original text as desc, it's safer.
  }

  // 3. Match Category
  const cats = appData.categories[type];
  for (let cat of cats) {
    const catNameLower = cat.name.toLowerCase();
    // basic matching
    if (text.includes(catNameLower) || text.includes(catNameLower.split(' ')[0])) {
      category = cat.name;
      break;
    }
  }
  
  // Custom keyword to category mapping for common items
  const catMap = {
    'listrik': 'Utilitas (Listrik/Air)',
    'air': 'Utilitas (Listrik/Air)',
    'pdam': 'Utilitas (Listrik/Air)',
    'internet': 'Utilitas (Listrik/Air)',
    'wifi': 'Utilitas (Listrik/Air)',
    'gaji': 'Karyawan',
    'karyawan': 'Karyawan',
    'pegawai': 'Karyawan',
    'bensin': 'Transportasi',
    'gojek': 'Transportasi',
    'grab': 'Transportasi',
    'parkir': 'Transportasi',
    'beras': 'Bahan Baku',
    'sayur': 'Bahan Baku',
    'daging': 'Bahan Baku',
    'modal': 'Modal',
    'jualan': 'Penjualan'
  };

  for (let key in catMap) {
    if (text.includes(key)) {
      if (appData.categories[type].find(c => c.name === catMap[key])) {
         category = catMap[key];
         break;
      }
    }
  }

  if (!category) {
    category = 'Lain-lain';
  }

  // Clean description - remove number + multiplier patterns from the text
  let cleanDesc = text;
  // Remove patterns like: 150 ribu, 2.5 juta, 50000, 7.000, rp 5000, rp5.000
  cleanDesc = cleanDesc.replace(/rp\.?\s?/gi, '');
  cleanDesc = cleanDesc.replace(/[\d][\d\.,]*\s*(ribu|ribuan|juta|jutaan|miliar)?/gi, '');
  cleanDesc = cleanDesc.replace(/\s{2,}/g, ' ').trim();
  
  // If cleanup results in empty or too short string, fallback to original text without numbers
  if (cleanDesc.length < 3) cleanDesc = text.replace(/[\d\.,]+\s*(ribu|juta|miliar)?/gi, '').trim();

  return {
    type,
    amount,
    category,
    desc: cleanDesc.charAt(0).toUpperCase() + cleanDesc.slice(1)
  };
}

// Export for window
window.initVoice = initVoice;
window.startRecording = startRecording;
window.stopRecording = stopRecording;
window.parseTransactionText = parseTransactionText;

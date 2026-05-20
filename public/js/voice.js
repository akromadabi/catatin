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
  const regexNum = /([\d\.,]+)\s*(ribu|ribuan|juta|jutaan|miliar)?/gi;
  let match;
  let maxAmount = 0;
  
  while ((match = regexNum.exec(text)) !== null) {
    let numStr = match[1];
    
    // Parse Indonesian locale numbers correctly
    if (numStr.includes('.') && numStr.includes(',')) {
        numStr = numStr.replace(/\./g, '').replace(/,/g, '.');
    } else if (numStr.includes('.')) {
        // If last dot is followed by exactly 3 digits, it's a thousands separator
        if (/\.\d{3}$/.test(numStr)) {
            numStr = numStr.replace(/\./g, '');
        }
    } else if (numStr.includes(',')) {
        numStr = numStr.replace(/,/g, '.');
    }
    
    let num = parseFloat(numStr);
    if (isNaN(num)) continue;

    let mult = match[2] ? match[2].toLowerCase() : '';
    if (DICT.multipliers[mult]) {
      num *= DICT.multipliers[mult];
    } else if (num < 1000 && !mult) {
       // e.g. "bayar listrik 150" -> 150000
       if(num > 0 && num < 1000) num *= 1000; 
    }

    if (num > maxAmount) maxAmount = num;
  }
  
  // Try matching words if numbers fail
  if (maxAmount === 0) {
     if(text.includes('cepek')) maxAmount = 100000;
     if(text.includes('goceng')) maxAmount = 5000;
     if(text.includes('ceban')) maxAmount = 10000;
     if(text.includes('sejut') || text.includes('satu juta')) maxAmount = 1000000;
  }

  amount = maxAmount;

  // 3. Match Category
  const cats = appData.categories[type] || [];
  
  // Pre-defined mapping for standard keywords
  const catMap = {
    'listrik': 'Listrik',
    'air': 'Air',
    'pdam': 'Air',
    'internet': 'Internet',
    'wifi': 'Internet',
    'gaji': 'Gaji',
    'karyawan': 'Karyawan',
    'bensin': 'Transportasi',
    'gojek': 'Transportasi',
    'grab': 'Transportasi',
    'parkir': 'Transportasi',
    'makan': 'Makan',
    'minum': 'Makan',
    'jajan': 'Jajan',
    'beras': 'Makan',
    'sayur': 'Makan',
    'belanja': 'Belanja',
    'pulsa': 'Internet'
  };

  for (let key in catMap) {
    if (text.includes(key)) {
      // Find a category that contains the mapped keyword
      const matchedCat = cats.find(c => c.name.toLowerCase().includes(catMap[key].toLowerCase()));
      if (matchedCat) {
         category = matchedCat.name;
         break;
      }
    }
  }

  if (!category) {
    for (let cat of cats) {
      const catNameLower = cat.name.toLowerCase();
      const parts = catNameLower.split(/[\s/&]+/); 
      if (text.includes(catNameLower)) {
        category = cat.name;
        break;
      }
      for (let part of parts) {
        if (part.length > 2 && text.includes(part)) {
          category = cat.name;
          break;
        }
      }
      if (category) break;
    }
  }

  if (!category) {
    category = 'Lain-lain';
  }

  // 4. Clean Description
  let cleanDesc = text;
  // Remove action keywords to leave only the item name
  const allKws = [...DICT.inKeywords, ...DICT.outKeywords];
  for (let kw of allKws) {
      cleanDesc = cleanDesc.replace(new RegExp(`\\b${kw}\\b`, 'gi'), '');
  }
  
  // Remove numbers and multipliers
  cleanDesc = cleanDesc.replace(/rp\.?\s?/gi, '');
  cleanDesc = cleanDesc.replace(/[\d][\d\.,]*\s*(ribu|ribuan|juta|jutaan|miliar)?/gi, '');
  cleanDesc = cleanDesc.replace(/\s{2,}/g, ' ').trim();
  
  // If cleanup results in empty or too short string, fallback to original text without numbers
  if (cleanDesc.length < 3) {
      cleanDesc = text.replace(/[\d\.,]+\s*(ribu|ribuan|juta|jutaan|miliar)?/gi, '').trim();
      // Also remove rp
      cleanDesc = cleanDesc.replace(/rp\.?\s?/gi, '').trim();
  }
  
  // Final fallback if literally just a number was spoken
  if (!cleanDesc) cleanDesc = type === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';

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

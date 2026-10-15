function syncSafetyStockData(fromWebApp) {
  // Konfigurasi
  const TARGET_SS_ID = "172L0GTPras725MUDwzfnCQBwf-IV_bt8yywEMWf4EJA";
  const SOURCE_SS_ID = "1uOd7ZQcYhnClYybBj_boCZE36vTvW2EzumY-f-hr0ns";
  const SOURCE_SHEET_NAME = "Database Komponen";
  const TARGET_SHEET_NAME = "Safety Stock";
  const TARGET_HEADER_ROW = 2; // Sesuai permintaan, header ada di baris 2

  try {
    // Dapatkan Spreadsheet saat ini (tempat tombol berada)
    const targetSS = SpreadsheetApp.getActiveSpreadsheet() || SpreadsheetApp.openById(TARGET_SS_ID);
    const targetSheet = targetSS.getSheetByName(TARGET_SHEET_NAME);
    if (!targetSheet) {
      if (fromWebApp === true) return { status: 'error', message: "Sheet '" + TARGET_SHEET_NAME + "' tidak ditemukan!" };
      else SpreadsheetApp.getUi().alert("❌ Sheet '" + TARGET_SHEET_NAME + "' tidak ditemukan!");
      return;
    }

    // Buka Spreadsheet Sumber
    const sourceSS = SpreadsheetApp.openById(SOURCE_SS_ID);
    const sourceSheet = sourceSS.getSheetByName(SOURCE_SHEET_NAME);
    if (!sourceSheet) {
      if (fromWebApp === true) return { status: 'error', message: "Sheet '" + SOURCE_SHEET_NAME + "' di sumber tidak ditemukan!" };
      else SpreadsheetApp.getUi().alert("❌ Sheet '" + SOURCE_SHEET_NAME + "' di sumber tidak ditemukan!");
      return;
    }

    // 1. Ambil & Mapping Header Target (Baris 2)
    const targetMaxCol = targetSheet.getLastColumn();
    if (targetMaxCol === 0) {
      if (fromWebApp === true) return { status: 'error', message: "Header di sheet target kosong." };
      else SpreadsheetApp.getUi().alert("❌ Header di sheet target kosong. Pastikan header sudah diketik di baris 2.");
      return;
    }
    const targetHeaders = targetSheet.getRange(TARGET_HEADER_ROW, 1, 1, targetMaxCol).getValues()[0];
    const targetHeaderMap = {};
    targetHeaders.forEach((header, index) => {
      if (header) targetHeaderMap[String(header).trim().toLowerCase()] = index;
    });

    // 2. Ambil Data Sumber
    const sourceData = sourceSheet.getDataRange().getValues();
    if (sourceData.length === 0) return;

    // Deteksi Otomatis Baris Header di Sumber (mencari baris yang punya 'kode komponen')
    let sourceHeaderRowIdx = -1;
    for (let i = 0; i < Math.min(5, sourceData.length); i++) {
      if (sourceData[i].some(cell => String(cell).toLowerCase().includes("kode komponen"))) {
        sourceHeaderRowIdx = i;
        break;
      }
    }

    if (sourceHeaderRowIdx === -1) {
      if (fromWebApp === true) return { status: 'error', message: "Tidak menemukan header 'Kode Komponen' di file sumber." };
      else SpreadsheetApp.getUi().alert("❌ Tidak menemukan header 'Kode Komponen' di file sumber.");
      return;
    }

    const sourceHeaders = sourceData[sourceHeaderRowIdx];
    const sourceHeaderMap = {};
    sourceHeaders.forEach((header, index) => {
      if (header) sourceHeaderMap[String(header).trim().toLowerCase()] = index;
    });

    // Cek keberadaan header Saldo dan Saldo Minimum di file sumber
    if (sourceHeaderMap["saldo"] === undefined || sourceHeaderMap["saldo minimum"] === undefined) {
      if (fromWebApp === true) return { status: 'error', message: "Kolom 'Saldo' atau 'Saldo Minimum' tidak ditemukan." };
      else SpreadsheetApp.getUi().alert("❌ Kolom 'Saldo' atau 'Saldo Minimum' tidak ditemukan di database sumber. Pastikan nama header sudah benar untuk proses filter.");
      return;
    }

    // 3. Proses Penarikan Data secara Dinamis (Fleksibel sesuai nama header)
    const dataToWrite = [];
    for (let i = sourceHeaderRowIdx + 1; i < sourceData.length; i++) {
      const row = sourceData[i];
      if (!row.some(cell => cell !== "")) continue; // Abaikan baris kosong

      // Ambil indeks kolom dari map header
      const saldoIdx = sourceHeaderMap["saldo"];
      const saldoMinIdx = sourceHeaderMap["saldo minimum"];

      // Konversi ke angka (jika kosong atau teks tidak valid, anggap 0)
      const saldoVal = Number(row[saldoIdx]) || 0;
      const saldoMinVal = Number(row[saldoMinIdx]) || 0;

      // SELEKSI: Lewati baris ini jika Saldo masih lebih besar atau sama dengan Saldo Minimum (Aman)
      if (saldoVal >= saldoMinVal) {
        continue;
      }

      const newRow = new Array(targetHeaders.length).fill(""); // Isi default kosong

      for (const targetHeaderLower in targetHeaderMap) {
        const targetIdx = targetHeaderMap[targetHeaderLower];
        if (sourceHeaderMap.hasOwnProperty(targetHeaderLower)) {
          const sourceIdx = sourceHeaderMap[targetHeaderLower];
          newRow[targetIdx] = row[sourceIdx]; // Tarik data jika nama headernya cocok
        }
      }
      dataToWrite.push(newRow);
    }

    // 4. Bersihkan Data Lama & Tulis Data Baru ke Sheet "Safety Stock"
    const lastRowTarget = targetSheet.getLastRow();
    // Bersihkan data lama (hanya dari baris ke-3 ke bawah) terlebih dahulu
    if (lastRowTarget > TARGET_HEADER_ROW) {
      targetSheet.getRange(TARGET_HEADER_ROW + 1, 1, lastRowTarget - TARGET_HEADER_ROW, targetMaxCol).clearContent();
    }

    if (dataToWrite.length > 0) {
      // Tulis ulang data baru
      targetSheet.getRange(TARGET_HEADER_ROW + 1, 1, dataToWrite.length, targetHeaders.length).setValues(dataToWrite);
      const msg = "Sinkronisasi berhasil!\n" + dataToWrite.length + " baris data telah diperbarui.";
      if (fromWebApp === true) return { status: 'success', message: msg };
      else SpreadsheetApp.getUi().alert("✅ " + msg);
    } else {
      const msg = "Sinkronisasi berhasil!\nTidak ada komponen yang ditarik (Semua saldo dalam batas aman). Data sebelumnya telah dibersihkan.";
      if (fromWebApp === true) return { status: 'success', message: msg };
      else SpreadsheetApp.getUi().alert("✅ " + msg);
    }

  } catch (e) {
    if (fromWebApp === true) return { status: 'error', message: e.toString() };
    else SpreadsheetApp.getUi().alert("❌ Terjadi Kesalahan:\n" + e.toString());
  }
}

function getSafetyStockList(userDept) {
  const TARGET_SS_ID = "172L0GTPras725MUDwzfnCQBwf-IV_bt8yywEMWf4EJA";
  const TARGET_SHEET_NAME = "Safety Stock";
  try {
    const ss = SpreadsheetApp.openById(TARGET_SS_ID);
    const sheet = ss.getSheetByName(TARGET_SHEET_NAME);
    if (!sheet) return { status: 'error', message: "Sheet Safety Stock tidak ditemukan." };

    const lastRow = sheet.getLastRow();
    const lastCol = sheet.getLastColumn();

    if (lastRow < 2) return { status: 'success', data: [] };

    const data = sheet.getRange(2, 1, lastRow - 1, lastCol).getValues();
    const headers = data[0];
    const rows = data.slice(1);

    // --- Cek Silang (Cross-Reference) WR Aktif - OPSI 1 (Personal) ---
    let activeWRs = [];
    try {
      const allWRs = getLogWRData(); // Panggil fungsi global dari code.js
      const userDeptList = normalizeSafetyStockDeptList(userDept);

      activeWRs = allWRs.filter(wr => {
        const pengaju = String(wr.pengaju || "").trim().toLowerCase();
        if (!userDeptList.includes(pengaju)) return false;

        const sPengaju = String(wr.statusPengaju).toLowerCase();
        const sPenerima = String(wr.statusPenerima).toLowerCase();
        const sBarang = String(wr.status).toLowerCase();

        if (sPengaju.includes('ditolak') || sPenerima.includes('ditolak')) return false;
        if (sBarang.includes('siap diambil') || sBarang.includes('selesai') || sBarang.includes('sudah diambil')) return false;

        return true;
      });
    } catch (e) {
      console.error("Gagal sinkronisasi Log WR: " + e);
    }

    const result = rows.map(row => {
      let obj = {};
      headers.forEach((h, i) => {
        if (h) obj[String(h).trim()] = row[i];
      });

      // Timpa nilai Status Pengajuan secara real-time
      const kodeKomponen = String(obj['Kode Komponen'] || "").trim();
      const hasActiveWR = activeWRs.some(wr => String(wr.kode).trim() === kodeKomponen);
      obj['Status Pengajuan'] = hasActiveWR ? "Sedang Diajukan" : "Belum diajukan";

      return obj;
    });

    return { status: 'success', data: result };
  } catch (e) {
    return { status: 'error', message: e.toString() };
  }
}

function normalizeSafetyStockDeptList(userDept) {
  let list = [];

  if (Array.isArray(userDept)) {
    list = userDept;
  } else if (userDept) {
    list = String(userDept).split(",");
  }

  return [...new Set(list
    .map(item => String(item || "").trim().toLowerCase())
    .filter(item => item !== "" && item !== "-"))];
}

const SPREADSHEET_ID = "172L0GTPras725MUDwzfnCQBwf-IV_bt8yywEMWf4EJA";

function doGet() {
  return HtmlService.createTemplateFromFile('Index')
    .evaluate()
    .setTitle('Material Handling - PT Hydraxle Perkasa')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

// FUNGSI UNTUK MEMANGGIL FILE HTML/CSS/JS LAIN
function include(filename) {
  return HtmlService.createHtmlOutputFromFile(filename).getContent();
}

// =============================================
// AUTHENTICATION
// =============================================

function checkLogin(username, password) {
  try {
    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = ss.getSheetByName("Data User MH");

    if (!sheet) return { status: 'error', message: 'Sheet "Data User MH" tidak ditemukan di Database.' };

    const lastRow = sheet.getLastRow();
    if (lastRow < 2) return { status: 'error', message: 'Data user MH kosong' };

    // === DYNAMIC HEADER DETECTION ===
    // Scan baris 1-5 untuk menemukan header "ID", "User", "Pasword"
    const maxHeaderRow = Math.min(5, lastRow);
    const maxCol = sheet.getLastColumn();
    if (maxCol < 1) return { status: 'error', message: 'Sheet "Data User MH" tidak memiliki kolom.' };

    const headerBlock = sheet.getRange(1, 1, maxHeaderRow, maxCol).getValues();

    let colID = -1, colUser = -1, colPass = -1, colNamaAkun = -1;
    let headerRow = -1;

    for (let r = 0; r < headerBlock.length; r++) {
      for (let c = 0; c < headerBlock[r].length; c++) {
        const cleanH = String(headerBlock[r][c]).toLowerCase().replace(/[^a-z0-9]/g, '');
        if (cleanH === 'id') { colID = c; headerRow = Math.max(headerRow, r); }
        else if (cleanH === 'user' || cleanH === 'dept') { colUser = c; headerRow = Math.max(headerRow, r); }
        else if (cleanH === 'pasword' || cleanH === 'password') { colPass = c; headerRow = Math.max(headerRow, r); }
        else if (cleanH === 'namaakun' || cleanH === 'nama') { colNamaAkun = c; headerRow = Math.max(headerRow, r); }
      }
      // Jika ketiga header ditemukan di baris ini, berhenti
      if (colID !== -1 && colUser !== -1 && colPass !== -1) break;
    }

    if (colID === -1 || colPass === -1) {
      return { status: 'error', message: 'Header "ID" atau "Pasword" tidak ditemukan di sheet.' };
    }

    // Data dimulai dari baris setelah header
    const dataStartRow = headerRow + 2; // +1 karena 0-indexed, +1 lagi untuk baris setelah header
    if (dataStartRow > lastRow) return { status: 'error', message: 'Data user MH kosong (tidak ada data setelah header).' };

    const data = sheet.getRange(dataStartRow, 1, lastRow - dataStartRow + 1, maxCol).getValues();

    const inputUser = String(username).trim().toLowerCase();
    const inputPass = String(password).trim();

    // CARI DARI ATAS KE BAWAH (Normal)
    for (let i = 0; i < data.length; i++) {
      const row = data[i];
      const dbID = String(row[colID]).trim();
      if (!dbID) continue;

      const dbPass = String(row[colPass]).trim();

      if (dbID.toLowerCase() === inputUser && dbPass === inputPass) {

        const valNama = colNamaAkun !== -1 ? String(row[colNamaAkun]).trim() : "";
        const valUser = colUser !== -1 ? String(row[colUser]).trim() : "";

        let dbNama = "";
        if (colNamaAkun === -1) {
          dbNama = "Error: Header 'Nama Akun' Tidak Ketemu";
        } else if (valNama === "") {
          dbNama = "Error: Sel Nama Akun Kosong!";
        } else {
          dbNama = valNama;
        }

        const deptMap = getDeptUserMapping();
        const deptInfo = resolveDeptUserValue(valUser, deptMap);
        const dbDept = deptInfo.names.join(', ') || 'Gudang';

        return {
          status: 'success',
          nama: dbNama,
          dept: dbDept,
          depts: deptInfo.names,
          deptCodes: deptInfo.codes,
          foto: 'https://cdn-icons-png.flaticon.com/512/149/149071.png',
          username: dbID,
          access: ['Gudang'],
          accessCodes: ['6']
        };
      }
    }
    return { status: 'error', message: 'Username atau Password salah' };
  } catch (error) {
    return { status: 'error', message: 'Terjadi kesalahan sistem: ' + error.toString() };
  }
}

function getAccessMapping(ss) {
  try {
    if (!ss) ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = ss.getSheetByName("Akses User");
    if (!sheet) return {};

    const lastRow = sheet.getLastRow();
    if (lastRow < 4) return {};

    const data = sheet.getRange(4, 2, lastRow - 3, 2).getValues();
    const map = {};

    // Gunakan for loop standar karena sedikit lebih cepat dari forEach di V8 Engine
    for (let i = 0; i < data.length; i++) {
      const code = String(data[i][0]).trim();
      const name = String(data[i][1]).trim();
      if (code && name) map[code] = name;
    }
    return map;
  } catch (e) {
    return {};
  }
}

function getDeptUserMapping() {
  try {
    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = ss.getSheetByName("Akses Dept User");
    if (!sheet) return {};

    const lastRow = sheet.getLastRow();
    if (lastRow < 3) return {}; // Data mulai baris 3 (Header baris 2)

    const lastCol = sheet.getLastColumn();
    const headers = sheet.getRange(2, 1, 1, lastCol).getValues()[0];

    let masterIdx = -1;
    let idAkunIdx = -1;

    headers.forEach((h, idx) => {
      const text = String(h).trim().toLowerCase();
      if (text === "master") {
        masterIdx = idx;
      } else if (text === "id akun" || text.includes("id akun")) {
        idAkunIdx = idx;
      }
    });

    if (masterIdx === -1) masterIdx = 1;
    if (idAkunIdx === -1) idAkunIdx = 2;

    const data = sheet.getRange(3, 1, lastRow - 2, lastCol).getValues();
    const map = {};

    for (let i = 0; i < data.length; i++) {
      const key = String(data[i][masterIdx]).trim();
      const val = String(data[i][idAkunIdx]).trim();
      if (key !== "" && val !== "") {
        map[key] = val;
        // Normalisasi jika numerik (agar "1" dan "01" saling mencocokkan)
        const num = parseFloat(key);
        if (!isNaN(num)) {
          map[String(num)] = val;
        }
      }
    }
    return map;
  } catch (e) {
    return {};
  }
}

function resolveDeptUserValue(rawDept, deptMap) {
  const raw = String(rawDept || "").trim();
  if (!raw) return { primary: "-", names: [], codes: [] };

  // Mendukung split koma, titik koma, dan garis miring
  const tokens = raw.split(/[,;/]+/).map(v => String(v).trim()).filter(v => v !== "");
  const names = [];
  const codes = [];

  tokens.forEach(token => {
    const num = parseFloat(token);
    const normalizedNumber = !isNaN(num) ? String(num) : "";

    // Coba cari dengan token asli, lalu angka ternormalisasi (misal "1")
    let mappedName = deptMap[token] || (normalizedNumber ? deptMap[normalizedNumber] : undefined);

    // Fallback: Jika tidak ketemu dan bernilai numerik 1 digit, coba tambahkan leading zero (misal "01")
    if (!mappedName && normalizedNumber && normalizedNumber.length === 1) {
      mappedName = deptMap["0" + normalizedNumber];
    }

    codes.push(token);
    names.push(mappedName || token);
  });

  const uniqueNames = [...new Set(names.filter(v => v && v !== "-"))];
  const uniqueCodes = [...new Set(codes.filter(v => v && v !== "-"))];

  return {
    primary: uniqueNames[0] || "-",
    names: uniqueNames,
    codes: uniqueCodes
  };
}

function getDeptPenerimaList() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  let sheet = ss.getSheetByName("List Dept Penerima");
  if (!sheet) sheet = ss.getSheetByName("List Dept");
  if (!sheet) return [];

  const lastRow = sheet.getLastRow();
  if (lastRow < 4) return [];

  const data = sheet.getRange(4, 2, lastRow - 3, 1).getValues();
  return data.map(r => r[0]).filter(item => item !== "");
}

// =============================================
// DATA RETRIEVAL
// =============================================

function getLogWRData() {
  try {
    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = ss.getSheetByName("Log WR");
    if (!sheet) return [];

    const lastRow = sheet.getLastRow();
    if (lastRow < 3) return [];

    if (sheet.getMaxColumns() < 24) {
      sheet.insertColumnsAfter(sheet.getMaxColumns(), 24 - sheet.getMaxColumns());
    }

    const data = sheet.getRange(3, 2, lastRow - 2, 23).getValues();
    const validData = data.filter(r => r[1] && r[1].toString() !== "");

    return validData.map(r => {
      let tglStr = "-";
      let tglRaw = "";
      if (r[0] instanceof Date) {
        tglStr = Utilities.formatDate(r[0], Session.getScriptTimeZone(), "dd MMM yyyy");
        tglRaw = Utilities.formatDate(r[0], Session.getScriptTimeZone(), "yyyy-MM-dd");
      } else if (r[0]) {
        tglStr = String(r[0]);
        tglRaw = String(r[0]).substring(0, 10);
      }

      const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${encodeURIComponent(String(r[1] || "-"))}`;

      const rawLogs = String(r[17] || "");
      const parsedLogs = [];
      if (rawLogs) {
        const entries = rawLogs.split(" ; ");
        entries.forEach(entry => {
          const clean = entry.replace(/^\[\s*/, "").replace(/\s*\]$/, "");
          const parts = clean.split(" - ");
          if (parts.length >= 1) {
            parsedLogs.push({
              status: parts[0] ? parts[0].trim() : "",
              qty: parts[1] ? parts[1].trim() : "-",
              time: parts[2] ? parts[2].trim() : "-",
              note: parts[3] ? parts[3].trim() : "-"
            });
          }
        });
      }

      return {
        tgl: tglStr,
        tglRaw: tglRaw,
        noWR: String(r[1] || "-"),
        kode: String(r[2] || "-"),
        nama: String(r[3] || "-"),
        gambar: String(r[4] || ""),
        routing: String(r[5] || "-"),
        dimensi: String(r[6] || "-"),
        qty: String(r[7] || "0"),
        pengaju: String(r[8] || "-"),
        penerima: String(r[9] || "-"),
        deskripsi: String(r[10] || "-"),
        statusPengaju: String(r[11] || "Belum Verifikasi"),
        statusPenerima: String(r[12] || "Belum Verifikasi"),
        status: String(r[13] || "Belum Verifikasi"),
        statusCetak: String(r[14] || "Belum Dicetak"),
        qrUrl: qrUrl,
        logs: parsedLogs,
        alasan: String(r[22] || ""),
        logRevisi: String(r[18] || "")
      };
    }).reverse();
  } catch (e) {
    throw "Gagal mengambil data: " + e.toString();
  }
}

// =============================================
// STATUS UPDATES
// =============================================

function sanitizeStatus(status) {
  if (!status) return "";
  return String(status).replace(/Parsial\s*>\s*/gi, "").trim();
}

function updateWRStatus(noWR, type, newStatus, additionalInfo, reason) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Log WR");

  const lastRow = sheet.getLastRow();
  const dataNoWR = sheet.getRange(1, 3, lastRow, 1).getValues();

  const targetWR = String(noWR).trim();
  const cleanStatus = sanitizeStatus(newStatus);

  for (let i = dataNoWR.length - 1; i >= 2; i--) {
    const rowWR = String(dataNoWR[i][0]).trim();
    if (rowWR === targetWR) {
      let colIndex;
      if (type === 'pengaju') colIndex = 13;
      else if (type === 'penerima') colIndex = 14;
      else if (type === 'barang') colIndex = 15;
      else return "Tipe update tidak valid";

      sheet.getRange(i + 1, colIndex).setValue(cleanStatus);
      SpreadsheetApp.flush();

      if (type === 'penerima' && cleanStatus === 'Diterima') {
        sheet.getRange(i + 1, 15).setValue("Dalam antrian");
      }

      if (additionalInfo || reason) {
        const timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "dd/MM HH:mm");
        const qtyStr = additionalInfo || "-";
        const noteStr = reason || "-";
        const statusStr = cleanStatus;

        const newLogEntry = `[ ${statusStr} - ${qtyStr} - ${timestamp} - ${noteStr} ]`;

        const logCell = sheet.getRange(i + 1, 19);
        const currentLog = String(logCell.getValue());

        let finalLog = currentLog;
        if (currentLog && currentLog !== "") {
          finalLog = currentLog + " ; " + newLogEntry;
        } else {
          finalLog = newLogEntry;
        }
        logCell.setValue(finalLog);
      }

      SpreadsheetApp.flush();
      return "Status berhasil diperbarui";
    }
  }
  return "Data tidak ditemukan: " + targetWR;
}

function processDefectAndScan(originNoWR, nextStatus, processQty, defectQty, reason, userIdent, defectReasonInput) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Log WR");
  const lastRow = sheet.getLastRow();

  const dataNoWR = sheet.getRange(3, 3, lastRow - 2, 1).getValues();
  let rowIndex = -1;

  for (let i = dataNoWR.length - 1; i >= 0; i--) {
    if (String(dataNoWR[i][0]).trim() === String(originNoWR).trim()) {
      rowIndex = i + 3;
      break;
    }
  }

  if (rowIndex === -1) return { status: 'error', message: "Data WR Asli tidak ditemukan." };

  let defectMsg = "";

  try {
    if (defectQty > 0) {
      const qtyCell = sheet.getRange(rowIndex, 9);
      let currentQty = parseInt(qtyCell.getValue());
      if (isNaN(currentQty)) currentQty = 0;

      let newQty = currentQty - defectQty;
      if (newQty < 0) newQty = 0;
      qtyCell.setValue(newQty);

      const timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "dd/MM HH:mm");
      const defectReason = defectReasonInput ? `${defectReasonInput} (Oleh: ${userIdent || "Unknown"})` : `Koreksi Defect (Oleh: ${userIdent || "Unknown"})`;
      const defectLog = `[ Defect > ${nextStatus} - ${defectQty} - ${timestamp} - ${defectReason} ]`;

      const logCell = sheet.getRange(rowIndex, 19);
      const currentLog = String(logCell.getValue());
      logCell.setValue(currentLog ? currentLog + " ; " + defectLog : defectLog);

      SpreadsheetApp.flush();
      defectMsg = `Defect <b>${defectQty} pcs</b> dicatat.<br>Total Qty WR disesuaikan menjadi <b>${newQty} pcs</b>.<br>`;
    }

    const processReason = defectQty > 0 ? "" : reason;
    const partialResult = submitPartialLog(originNoWR, nextStatus, processQty, processReason);
    if (partialResult.status === 'success') {
      const finalMsg = defectMsg + (partialResult.message ? "<br>" + partialResult.message : "");
      return { status: 'success', message: finalMsg };
    } else {
      return { status: 'error', message: defectMsg + "Gagal memproses status: " + partialResult.message };
    }
  } catch (e) {
    return { status: 'error', message: e.toString() };
  }
}

function submitPartialLog(originNoWR, nextStatus, partialQty, reason) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Log WR");
  const lastRow = sheet.getLastRow();

  const dataNoWR = sheet.getRange(3, 3, lastRow - 2, 1).getValues();
  let rowIndex = -1;

  for (let i = dataNoWR.length - 1; i >= 0; i--) {
    if (String(dataNoWR[i][0]).trim() === String(originNoWR).trim()) {
      rowIndex = i + 3;
      break;
    }
  }

  if (rowIndex === -1) return { status: 'error', message: "Data WR Asli tidak ditemukan." };

  try {
    const originRowData = sheet.getRange(rowIndex, 2, 1, 17).getValues()[0];
    const currentQty = parseInt(originRowData[7]);
    const inputQty = parseInt(partialQty) || 0;

    if (isNaN(currentQty) || isNaN(inputQty)) return { status: 'error', message: "Format Qty tidak valid." };

    const cleanNextStatus = sanitizeStatus(nextStatus);
    const targetClean = cleanNextStatus.toLowerCase();

    const logCell = sheet.getRange(rowIndex, 19);
    const rawLogs = String(logCell.getValue());

    let totalProcessedForNextStep = 0;

    if (rawLogs) {
      const entries = rawLogs.split(" ; ");
      entries.forEach(entry => {
        const cleanEntry = sanitizeStatus(entry).toLowerCase();
        if (cleanEntry.includes(targetClean)) {
          if (!targetClean.includes('defect') && cleanEntry.includes('defect')) {
            return;
          }
          const parts = entry.split(" - ");
          if (parts.length >= 2) {
            const q = parseInt(parts[1]);
            if (!isNaN(q)) totalProcessedForNextStep += q;
          }
        }
      });
    }
    const newTotalProcessed = totalProcessedForNextStep + inputQty;

    let totalDefectsAll = 0;
    if (rawLogs) {
      rawLogs.split(" ; ").forEach(entry => {
        if (entry.toLowerCase().includes('defect')) {
          const parts = entry.split(" - ");
          if (parts.length >= 2) totalDefectsAll += (parseInt(parts[1]) || 0);
        }
      });
    }
    const absoluteMax = currentQty + totalDefectsAll;

    if (newTotalProcessed > absoluteMax && absoluteMax > 0) {
      return { status: 'error', message: `Input Qty (${inputQty}) melebihi sisa maksimal proses (${absoluteMax - totalProcessedForNextStep} pcs).` };
    }

    const timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "dd/MM HH:mm");
    let newLogEntry = "";
    let returnMessage = "";

    if (newTotalProcessed >= currentQty && currentQty > 0) {
      sheet.getRange(rowIndex, 15).setValue(cleanNextStatus);
      if (inputQty > 0) {
        newLogEntry = `[ ${cleanNextStatus} - ${inputQty} (Final) - ${timestamp} - ${reason || "Parsial Terakhir Selesai"} ]`;
        returnMessage = `<b>${inputQty} pcs</b> diproses.<br>Total tercapai (${newTotalProcessed}/${currentQty}).<br>Status diperbarui ke: <b>${cleanNextStatus}</b>`;
      } else {
        returnMessage = `Sisa kuota otomatis terpenuhi (Total: ${currentQty}).<br>Status diperbarui ke: <b>${cleanNextStatus}</b>`;
      }
    } else {
      if (inputQty > 0) {
        newLogEntry = `[ Parsial > ${cleanNextStatus} - ${inputQty} - ${timestamp} - ${reason || "Progres Parsial"} ]`;
        returnMessage = `<b>${inputQty} pcs</b> dicatat.<br>Progres: <b>${newTotalProcessed}/${currentQty}</b>.<br>Status utama belum berubah.`;
      } else {
        returnMessage = "Hanya mencatat defect, tidak ada progres baru.";
      }
    }

    // --- INJEKSI LOGIKA UPDATE STOK KOMPONEN (JIKA SUDAH DIAMBIL) ---
    if (targetClean === 'sudah diambil' && inputQty > 0) {
      const kodeKomponen = originRowData[2]; // Index 2 = Kolom D = Kode Komponen
      const stockUpdate = updateComponentStock(kodeKomponen, inputQty);
      const colorMsg = stockUpdate.success ? '#00c851' : '#ff9800';
      const iconMsg = stockUpdate.success ? 'fa-check-circle' : 'fa-exclamation-circle';
      returnMessage += `<br><div style="margin-top: 10px; padding: 8px; border-radius: 8px; background: #f8f9fa; border-left: 3px solid ${colorMsg}; font-size: 0.85rem; color: #555;"><i class="fas ${iconMsg}" style="color:${colorMsg}; margin-right: 5px;"></i>${stockUpdate.message}</div>`;
    }

    if (newLogEntry !== "") {
      const currentLog = rawLogs;
      logCell.setValue(currentLog ? currentLog + " ; " + newLogEntry : newLogEntry);
    }

    return { status: 'success', message: returnMessage };
  } catch (e) {
    return { status: 'error', message: e.toString() };
  }
}

// =============================================
// UPDATE ROUTING
// =============================================

function updateRoutingWR(noWR, newRouting, newPenerima, reason) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Log WR");

  const lastRow = sheet.getLastRow();
  const dataNoWR = sheet.getRange(3, 3, lastRow - 2, 1).getValues();

  for (let i = dataNoWR.length - 1; i >= 0; i--) {
    if (String(dataNoWR[i][0]).trim() === String(noWR).trim()) {
      const rowIndex = i + 3;

      sheet.getRange(rowIndex, 7).setValue(newRouting);
      sheet.getRange(rowIndex, 11).setValue(newPenerima);

      const logCell = sheet.getRange(rowIndex, 20);
      const currentLog = String(logCell.getValue());
      const timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "dd/MM");
      const newLogEntry = `[${timestamp} - ${reason}]`;

      if (currentLog && currentLog !== "") {
        logCell.setValue(currentLog + " ; " + newLogEntry);
      } else {
        logCell.setValue(newLogEntry);
      }

      SpreadsheetApp.flush();
      return "Berhasil memperbarui Routing & Penerima.";
    }
  }
  throw new Error("Data Work Request tidak ditemukan.");
}

/**
 * FUNGSI SETUP IZIN
 * Jalankan fungsi ini SEKALI SAJA secara manual dari Editor Apps Script
 */
function authorizeScript() {
  UrlFetchApp.fetch("https://www.google.com");
  const temp = DriveApp.createFile("temp_auth_trigger.txt", "Hanya pemicu izin");
  temp.setTrashed(true);
  console.log("Izin berhasil diberikan.");
}

// --- FUNGSI BARU: UPDATE STOK KOMPONEN SAAT WR DITERIMA ---
function updateComponentStock(kodeKomponen, addQty) {
  if (!kodeKomponen || String(kodeKomponen).trim().toUpperCase() === "BARU" || addQty <= 0) {
    return { success: false, message: "Catatan: Saldo tidak ditambah otomatis karena komponen baru/belum terdaftar." };
  }

  try {
    const TARGET_SS_ID = "1uOd7ZQcYhnClYybBj_boCZE36vTvW2EzumY-f-hr0ns";
    const ss = SpreadsheetApp.openById(TARGET_SS_ID);
    const sheet = ss.getSheetByName("Database Komponen");

    if (!sheet) return { success: false, message: "Catatan: Gagal tambah saldo (Sheet 'Database Komponen' tidak ditemukan)." };

    const lastRow = sheet.getLastRow();
    if (lastRow < 3) return { success: false, message: "Catatan: Gagal tambah saldo (Data komponen kosong)." };

    // Ambil Header di baris 2
    const headers = sheet.getRange(2, 1, 1, sheet.getLastColumn()).getValues()[0];
    let colKode = 2; // Fallback Clue: Kolom B (Index 2)
    let colSaldo = 6; // Fallback Clue: Kolom F (Index 6)

    headers.forEach((h, idx) => {
      const text = String(h).trim().toLowerCase();
      if (text === "kode komponen") colKode = idx + 1;
      if (text === "saldo") colSaldo = idx + 1;
    });

    // Cari baris dengan kode komponen yang sesuai
    const dataKode = sheet.getRange(3, colKode, lastRow - 2, 1).getValues();
    for (let i = 0; i < dataKode.length; i++) {
      if (String(dataKode[i][0]).trim().toUpperCase() === String(kodeKomponen).trim().toUpperCase()) {
        const targetRow = i + 3; // +3 karena getRange mulai dari baris 3
        const cellSaldo = sheet.getRange(targetRow, colSaldo);
        const currentSaldo = Number(cellSaldo.getValue()) || 0;
        cellSaldo.setValue(currentSaldo + addQty);
        return { success: true, message: `Saldo master data otomatis diperbarui (+${addQty} pcs).` };
      }
    }
    return { success: false, message: "Catatan: Saldo tidak ditambah (Kode komponen belum terdaftar di database master)." };
  } catch (e) { return { success: false, message: `Catatan: Gagal tambah saldo otomatis (${e.message}).` }; }
}

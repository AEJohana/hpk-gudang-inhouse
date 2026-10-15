const SPREADSHEET_ID = "172L0GTPras725MUDwzfnCQBwf-IV_bt8yywEMWf4EJA";

function doGet() {
  // Ubah menjadi createTemplateFromFile dan tambahkan .evaluate()
  return HtmlService.createTemplateFromFile('Index')
    .evaluate()
    .setTitle('WR System - PT Hydraxle Perkasa')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}



// DEBUG FUNCTION: Inspect "List Dept" or "Format WR" structure and "Log WR" statuses
function inspectSheetStructure() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);

  // 1. Inspect Format WR
  const formatSheet = ss.getSheetByName("Format WR");
  let formatData = [];
  if (formatSheet) {
    // Read B1:H30 (First 30 rows should cover header mapping)
    // Using H because G is 7th column, lets read a bit wider
    const values = formatSheet.getRange(1, 2, 30, 7).getValues();
    formatData = values.map((row, i) => `Row ${i + 1}: ${row.join(" | ")}`);
  }

  // 2. Inspect Log WR Statuses
  const logSheet = ss.getSheetByName("Log WR");
  let statuses = [];
  if (logSheet) {
    const lastRow = logSheet.getLastRow();
    if (lastRow > 2) {
      // Column M is Status Pengaju (Index 13, so Column 13 from A) -> Column M
      // A=1, B=2 ... M=13
      const statusValues = logSheet.getRange(3, 13, lastRow - 2, 1).getValues();
      // Flatten and Filter Unique
      const flatStatuses = statusValues.map(r => r[0]).filter(s => s !== "");
      statuses = flatStatuses.filter((v, i, a) => a.indexOf(v) === i);
    }
  }

  return {
    formatStructure: formatData,
    logStatuses: statuses
  };
}

function getRoutingMap() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("ID_Routing");
  if (!sheet) return {};

  const lastRow = sheet.getLastRow();
  if (lastRow < 3) return {};

  // UPDATE: Ambil Data range lebih lebar A3:Z (26 Kolom) agar kolom Identitas baru terbaca
  // Header ada di Baris 3.
  const data = sheet.getRange(3, 1, lastRow - 2, 26).getValues();

  // Cari Header dengan Normalisasi (LowerCase + Trim)
  // Kita cari "id" dan "routing"
  const header = data[0].map(h => String(h).toLowerCase().trim());

  let idIdx = -1;
  let routingIdx = -1;
  let identityIdx = -1; // Index untuk kolom Identitas

  // 1. Coba cari exact match "id"
  idIdx = header.indexOf("id");
  // Jika tidak ketemu, cari yang mengandung kata "id" (misal "nomor id", "id routing")
  // UPDATE: Pastikan tidak match dengan "identitas" saat mencari "id"
  if (idIdx === -1) idIdx = header.findIndex(h => h.includes("id") && !h.includes("identitas"));

  // 2. Coba cari exact match "routing"
  routingIdx = header.indexOf("routing");
  // Jika tidak ketemu, cari yang mengandung kata "routing" (misal "nama routing", "routing name")
  if (routingIdx === -1) routingIdx = header.findIndex(h => h.includes("routing"));

  // 3. Coba cari exact match "identitas" atau "dept"
  identityIdx = header.indexOf("identitas");
  if (identityIdx === -1) identityIdx = header.findIndex(h => h.includes("identitas") || h.includes("dept") || h.includes("tujuan") || h.includes("penerima"));

  // FALLBACK MANUAL (HARDCODE) SESUAI REQUEST USER
  // User bilang: Header Baris 3. Header Routing di Kolom C (Index 2). ID itu biasanya Kolom A (Index 0).
  // Jika auto-detect gagal, kita paksa pakai Index 1 (B) atau 0 (A) untuk ID, dan 2 (C) untuk Routing.
  if (routingIdx === -1) routingIdx = 2; // Paksa Kolom C
  // idIdx fallback is handled by possibleIdIndices below

  const map = {};

  // Loop data mulai index 1
  for (let i = 1; i < data.length; i++) {
    const row = data[i];

    // Ambil target Routing (Kolom 2 / C, atau hasil deteksi)
    const routingVal = String(row[routingIdx] || "").trim();
    if (!routingVal) continue;

    // Ambil Identitas (jika ada kolomnya)
    const identityVal = identityIdx !== -1 ? String(row[identityIdx] || "").trim() : "";

    // STRATEGI GREEDY:
    // User bilang '1,9'. 1 itu ID, 9 itu ID.
    // Kita map semua kemungkinan kolom ID (A dan B) jika header tidak pasti.
    // Jika header "id" ketemu, pakai itu. Jika tidak, pakai A dan B.

    const possibleIdIndices = [];
    if (idIdx !== -1) {
      possibleIdIndices.push(idIdx);
    } else {
      possibleIdIndices.push(0); // Kolom A
      possibleIdIndices.push(1); // Kolom B
    }

    possibleIdIndices.forEach(idx => {
      let val = row[idx];
      let key = "";
      if (typeof val === 'number') {
        key = String(val); // "1"
      } else {
        key = String(val || "").trim();
      }

      if (key && key !== "") {
        // Simpan Object { name: "Bubut", identity: "Dept Bubut" }
        // Agar kompatibel, kita simpan object, nanti di getComponentData kita parse
        map[key] = { name: routingVal, identity: identityVal };
      }
    });
  }

  return map;
}

// AMBIL DATA DARI SHEET "Database Komponen" atau "Database SPO"
function getComponentData(userDept) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const deptCode = String(userDept || "").trim();
  const sheetName = deptCode === "12" ? "Database SPO" : "Database Komponen";
  const sheet = ss.getSheetByName(sheetName);

  if (!sheet) return [];

  const lastRow = sheet.getLastRow();
  if (lastRow < 3) return [];

  const routingMap = getRoutingMap();

  if (deptCode === "12") {
    // --- LOGIKA DINAMIS UNTUK DEPT SPO (Kode: 12) ---
    const lastCol = sheet.getLastColumn();
    if (lastCol < 1) return [];
    
    // Ambil header dari baris ke-2
    const headers = sheet.getRange(2, 1, 1, lastCol).getValues()[0].map(h => String(h).trim().toLowerCase());
    
    // Cari letak kolom (0-based index)
    const idxKode = headers.findIndex(h => h === "kode komponen");
    const idxNama = headers.findIndex(h => h === "nama komponen");
    const idxGambar = headers.findIndex(h => h === "gambar komponen" || h === "gambar");
    const idxRouting = headers.findIndex(h => h === "routing");
    const idxDimensi = headers.findIndex(h => h === "dimensi");
    
    // Ambil data mulai dari baris ke-3
    const data = sheet.getRange(3, 1, lastRow - 2, lastCol).getValues();
    
    return data.filter(r => idxKode !== -1 && String(r[idxKode]).trim() !== "").map(r => {
      let rawRouting = idxRouting !== -1 ? String(r[idxRouting] || "").trim() : "-";
      let resolvedRouting = "-";
      let resolvedIdentity = "";
      
      if (rawRouting && rawRouting !== "-") {
        const ids = rawRouting.split(/[\s,;.|]+/);
        const identities = [];
        ids.forEach(id => {
          const key = id.trim();
          if (!key) return;
          const entry = routingMap[key] || routingMap[parseFloat(key)];
          if (entry) {
            if (typeof entry === 'object') {
              identities.push(entry.identity || entry.name);
            } else {
              identities.push(entry);
            }
          } else {
            identities.push(key);
          }
        });
        if (identities.length > 0) {
          resolvedIdentity = identities[0];
        }
        resolvedRouting = ids.map(id => {
          let key = id.trim();
          if (!key) return null;
          const entry = routingMap[key] || routingMap[parseFloat(key)];
          if (entry && typeof entry === 'object') return entry.name;
          return entry || key;
        }).filter(item => item !== null).join(' - ');
      }
      return {
        kode: r[idxKode],
        nama: idxNama !== -1 ? r[idxNama] : "-",
        gambar: idxGambar !== -1 ? r[idxGambar] : "",
        routing: resolvedRouting,
        dimensi: idxDimensi !== -1 ? (r[idxDimensi] || "-") : "-",
        dept: resolvedIdentity
      };
    });
  } else {
    // --- LOGIKA LAMA UNTUK GUDANG / LAINNYA (Hardcode Kolom C-G) ---
    const data = sheet.getRange(3, 3, lastRow - 2, 5).getValues();

    return data.filter(r => r[0] !== "").map(r => {
      let rawRouting = String(r[3] || "").trim();
      let resolvedRouting = "-";
      let resolvedIdentity = "";

      if (rawRouting && rawRouting !== "-") {
        const ids = rawRouting.split(/[\s,;.|]+/);
        const identities = [];
        ids.forEach(id => {
          const key = id.trim();
          if (!key) return;
          const entry = routingMap[key] || routingMap[parseFloat(key)];
          if (entry) {
            if (typeof entry === 'object') {
              identities.push(entry.identity || entry.name);
            } else {
              identities.push(entry);
            }
          } else {
            identities.push(key);
          }
        });
        if (identities.length > 0) {
          resolvedIdentity = identities[0];
        }
        resolvedRouting = ids.map(id => {
          let key = id.trim();
          if (!key) return null;
          const entry = routingMap[key] || routingMap[parseFloat(key)];
          if (entry && typeof entry === 'object') return entry.name;
          return entry || key;
        }).filter(item => item !== null).join(' - ');
      }
      return {
        kode: r[0],
        nama: r[1],
        gambar: r[2],
        routing: resolvedRouting,
        dimensi: r[4] || "-",
        dept: resolvedIdentity
      };
    });
  }
}

// AMBIL OPSI DROPDOWN UNTUK KOMPONEN BARU
function getNewComponentOptions() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Panduan Kode Komponen");
  if (!sheet) return { karakter: [], type: [], material: [] };

  const lastRow = sheet.getLastRow();
  // Data mulai baris 4 (agar header tidak terbawa)
  if (lastRow < 4) return { karakter: [], type: [], material: [] };

  // Kita butuh kolom B (Indeks 1), E (Indeks 4), H (Indeks 7).
  // Ambil range A4:H sampai baris terakhir
  const data = sheet.getRange(4, 1, lastRow - 3, 8).getValues();

  // Filter unik, tidak kosong, dan bukan header
  const filterValid = (val) => val && String(val).trim() !== "" && !["Karakter", "Type Subassy", "Tipe Material"].includes(String(val));

  const karakter = [...new Set(data.map(r => r[1]).filter(filterValid))]; // Kolom B
  const type = [...new Set(data.map(r => r[4]).filter(filterValid))];     // Kolom E
  const material = [...new Set(data.map(r => r[7]).filter(filterValid))]; // Kolom H

  return { karakter, type, material };
}

// SIMPAN KOMPONEN BARU KE SHEET "Komponen baru"
function saveNewComponent(data) {
  const result = _internalSaveNewComponent(data);
  return "Komponen baru berhasil disimpan dengan kode: " + result.code;
}

// HELPER INTERNAL: Simpan Komponen Baru & Return Kode (Dipakai oleh submitBulkWR)
function _internalSaveNewComponent(data) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  let sheet = ss.getSheetByName("Komponen baru");
  if (!sheet) {
    sheet = ss.insertSheet("Komponen baru");
    // Header Baris 4 (Sesuai request)
    // B=Tanggal, C=Kode, D=Nama, E=Foto, F=Karakter, G=Type, H=Material, I=Panjang, J=Lebar, K=Tinggi, L=Duplikat, M=Dimensi
    sheet.getRange("B4:M4").setValues([["Tanggal", "Kode Barang", "Nama Barang", "Foto", "Karakter", "Type Subassy", "Tipe Material", "Panjang (cm)", "Lebar (cm)", "Tinggi (mm)", "Duplikat", "Dimensi"]]);
  }

  let imgUrl = "";
  if (data.foto && data.foto !== "") {
    try {
      // Decode Base64
      const contentType = data.foto.substring(5, data.foto.indexOf(';'));
      const base64 = data.foto.substring(data.foto.indexOf(',') + 1);
      const blob = Utilities.newBlob(Utilities.base64Decode(base64), contentType, data.fotoName);

      // Simpan ke Folder Khusus Foto Komponen Baru
      const folderId = "1ncxDmKCeNkGuOx9EAUVCVyS_h9ADy3nt";
      const folder = DriveApp.getFolderById(folderId);
      const file = folder.createFile(blob);

      // Coba set public, jika gagal (karena batasan domain) biarkan saja agar tidak error
      try {
        file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
      } catch (e) { /* Ignore sharing error */ }

      imgUrl = file.getUrl();
    } catch (e) {
      imgUrl = "Error Upload: " + e.toString();
    }
  }

  // LOGIKA GENERATE KODE BARANG OTOMATIS
  // UPDATE: Tidak ada generate kode lagi, set menjadi "Baru"
  let generatedKode = "Baru";

  // MENENTUKAN BARIS TARGET SECARA MANUAL
  // Agar data tidak masuk di paling bawah (misal baris 1000) jika ada baris kosong berformat
  const lastRow = sheet.getLastRow();
  let targetRow = lastRow + 1;

  // Cek kolom B (Tanggal) dari baris 5 ke bawah untuk mencari celah kosong pertama
  if (lastRow >= 5) {
    const rangeB = sheet.getRange(5, 2, lastRow - 4, 1).getValues();
    for (let i = 0; i < rangeB.length; i++) {
      if (rangeB[i][0] === "") {
        targetRow = 5 + i;
        break;
      }
    }
  } else {
    targetRow = 5; // Jika sheet baru/kosong, mulai baris 5
  }

  // Tulis data menggunakan setValues (lebih presisi daripada appendRow)
  // Range: Baris target, Kolom 1 (A), 1 Baris, 13 Kolom (A-M)
  sheet.getRange(targetRow, 1, 1, 13).setValues([[
    "", // A
    new Date(), // B: Tanggal
    generatedKode, // C: Kode Barang (Otomatis)
    data.nama, // D: Nama Barang
    imgUrl, // E: Foto
    "", // F: Karakter (Removed)
    "", // G: Type (Removed)
    data.material || "", // H: Material
    data.panjang, // I: Panjang
    data.lebar, // J: Lebar
    data.tinggi, // K: Tinggi
    "", // L: Duplikat (Removed)
    data.dimensi // M: Dimensi (New)
  ]]);

  return { code: generatedKode, url: imgUrl };
}

// INPUT DATA KE SHEET "Log WR" (Mulai Kolom B)
function submitWR(formData) {
  // Menggunakan fungsi bulk agar format nomor WR konsisten
  // Fungsi ini membungkus data tunggal menjadi array agar bisa diproses oleh submitBulkWR
  return submitBulkWR([formData]);
}

function checkLogin(username, password) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Data User");

  const lastRow = sheet.getLastRow();
  if (lastRow < 3) return { status: 'error', message: 'Data user kosong' };

  // AMBIL DATA PASTI DARI KOLOM A SAMPAI G (7 KOLOM)
  // Kolom: A=0, B=1, C=2, D=3, E=4, F=5, G=6
  // Data diambil mulai baris 3 karena header ada di baris 2
  const data = sheet.getRange(3, 1, lastRow - 2, 7).getValues();

  // Ambil Mapping Akses dari sheet "Akses User"
  const accessMap = getAccessMapping();
  const deptMap = getDeptUserMapping();

  // CARI DARI BAWAH KE ATAS (Ambil data paling baru jika ID ganda)
  for (let i = data.length - 1; i >= 0; i--) {
    const row = data[i];

    const dbUser = String(row[1]).trim(); // Kolom B (ID/Username)
    if (!dbUser || dbUser.toLowerCase() === 'id') continue; // Lewati jika kosong atau terbaca header

    const dbPass = String(row[3]).trim(); // Kolom D (Password)

    // Cek Username (Case Insensitive) & Password (Case Sensitive)
    if (dbUser.toLowerCase() === String(username).trim().toLowerCase() && dbPass === String(password).trim()) {

      const valDept = String(row[2]).trim();     // Kolom C (Dept)
      const dbAksesCode = String(row[4]).trim(); // Kolom E (Akses)
      const valNama = String(row[5]).trim();     // Kolom F (Nama Akun)
      const valFoto = String(row[6]).trim();     // Kolom G (Foto)

      // JIKA KOSONG TAMPILKAN PERINGATAN BUKAN ID LAGI
      const dbNama = valNama !== "" ? valNama : "NAMA DI KOLOM F KOSONG";

      const deptInfo = resolveDeptUserValue(valDept, deptMap);
      const dbDept = deptInfo.primary || "-";
      const dbFoto = valFoto !== "" ? valFoto : "https://cdn-icons-png.flaticon.com/512/149/149071.png";

      // Terjemahkan Kode Akses (misal "1,2,3") menjadi Nama Akses (misal "Membuat Work Request")
      let userAccessList = [];
      let userAccessCodes = []; // Array untuk menyimpan kode mentah (misal ["1", "5"])

      if (dbAksesCode) {
        const codes = dbAksesCode.split(',');
        userAccessCodes = codes.map(c => c.trim()); // Simpan kode mentah

        userAccessList = codes.map(code => {
          const cleanCode = code.trim();
          return accessMap[cleanCode] || null;
        }).filter(item => item !== null);
      }

      return {
        status: 'success',
        nama: dbNama,
        dept: dbDept,
        depts: deptInfo.names,
        deptCodes: deptInfo.codes,
        foto: dbFoto,
        username: dbUser,
        access: userAccessList, // Kirim list nama akses (untuk menu)
        accessCodes: userAccessCodes // Kirim list kode akses (untuk logika verifikasi no 5)
      };
    }
  }
  return { status: 'error', message: 'Username atau Password salah' };
}

// HELPER: Membaca Sheet "Akses User" untuk mapping Kode -> Nama Akses
function getAccessMapping() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Akses User");
  if (!sheet) return {};

  const lastRow = sheet.getLastRow();
  if (lastRow < 4) return {}; // Data mulai baris 4 (Header baris 3)

  // Header Baris 3: B=Kode Akses, C=Nama Akses
  // Ambil data B4:C
  const data = sheet.getRange(4, 2, lastRow - 3, 2).getValues();

  const map = {};
  data.forEach(r => {
    const code = String(r[0]).trim(); // Kolom B
    const name = String(r[1]).trim(); // Kolom C
    if (code && name) {
      map[code] = name;
    }
  });
  return map;
}

function getDeptUserMapping() {
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

  // Fallback ke kolom B (indeks 1) dan C (indeks 2) jika tidak terdeteksi
  if (masterIdx === -1) masterIdx = 1;
  if (idAkunIdx === -1) idAkunIdx = 2;

  const data = sheet.getRange(3, 1, lastRow - 2, lastCol).getValues();
  const map = {};

  data.forEach(row => {
    const key = String(row[masterIdx]).trim();
    const val = String(row[idAkunIdx]).trim();
    if (key !== "" && val !== "") {
      map[key] = val;
      // Normalisasi jika numerik (agar "1" dan "01" saling mencocokkan)
      const num = parseFloat(key);
      if (!isNaN(num)) {
        map[String(num)] = val;
      }
    }
  });

  return map;
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

function getDeptPemohonList() {
  const map = getDeptUserMapping();
  // Ambil semua nama departemen (values dari map), hapus duplikat
  const uniqueDepts = [...new Set(Object.values(map))];
  
  // Karena map numerik digandakan (misal "1" dan "01" punya value sama), 
  // Object.values(map) sudah cukup, filter yang kosong.
  return uniqueDepts.filter(dept => dept && dept.trim() !== "");
}

function getDeptPenerimaList() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  // Coba ambil sheet "List Dept Penerima" atau fallback ke "List Dept"
  let sheet = ss.getSheetByName("List Dept Penerima");
  if (!sheet) sheet = ss.getSheetByName("List Dept");

  if (!sheet) return [];

  const lastRow = sheet.getLastRow();
  // Data mulai baris 4 (agar header tidak terbawa)
  if (lastRow < 4) return [];

  // Ambil data kolom B (indeks 2) dari baris 4 sampai akhir
  const data = sheet.getRange(4, 2, lastRow - 3, 1).getValues();
  return data.map(r => r[0]).filter(item => item !== "");
}

// INPUT BANYAK DATA SEKALIGUS (BULK INSERT)
function submitBulkWR(listFormData) {
  try {
    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    const logSheet = ss.getSheetByName("Log WR");

    const lastRow = logSheet.getLastRow();
    // Jika sheet baru (hanya header), baris target adalah 3. Jika sudah ada data, lastRow + 1.
    let targetRow = lastRow < 2 ? 3 : lastRow + 1;

    // 1. Ambil data yang sudah ada untuk menghitung urutan harian
    let existingData = [];
    if (lastRow >= 3) {
      // Ambil Kolom B (Tanggal), C (No WR), D (Kode Komponen)
      // Baris 3, Kolom 2, sebanyak (lastRow-2), 3 kolom
      existingData = logSheet.getRange(3, 2, lastRow - 2, 3).getValues();
    }

    // Ambil info dasar dari item pertama (asumsi tanggal & dept sama dalam 1 batch)
    const tglOrderInput = listFormData[0].tglOrder; // String YYYY-MM-DD
    const deptPengaju = listFormData[0].deptPengaju;

    // Format Tanggal untuk ID (YYYYMMDD) agar rapi
    const dateForID = tglOrderInput.replace(/-/g, "");

    // Sanitize Dept untuk filter counter (agar urutan per departemen)
    const safeDeptInput = String(deptPengaju).replace(/\//g, "-");

    // 2. Hitung Counter Harian
    let maxWrSeq = 0;
    const itemDailyCounts = {}; // Map: KodeBarang -> JumlahOrderHariIni

    existingData.forEach(row => {
      let rowDate = row[0];
      const rowNoWR = String(row[1]);
      const rowKode = String(row[2]);

      // Konversi tanggal row ke string YYYY-MM-DD
      let rowDateStr = "";
      if (rowDate instanceof Date) {
        rowDateStr = Utilities.formatDate(rowDate, Session.getScriptTimeZone(), "yyyy-MM-dd");
      } else {
        rowDateStr = String(rowDate).substring(0, 10);
      }

      // Jika tanggal sama dengan input (Reset Harian)
      if (rowDateStr === tglOrderInput) {
        // Hitung Max WR Sequence (Urutan WR Hari Ini)
        // Format: WR/Dept/Date/SeqWR/Code/SeqItem
        const parts = rowNoWR.split('/');
        // Index: 0=WR, 1=Dept, 2=Date, 3=SeqWR
        if (parts.length >= 4) {
          const rowDept = parts[1]; // Ambil bagian Dept dari ID yang ada

          // Filter: Hanya hitung sequence jika Dept sama (Urutan per Dept)
          if (rowDept === safeDeptInput) {
            const seq = parseInt(parts[3], 10);
            if (!isNaN(seq) && seq > maxWrSeq) {
              maxWrSeq = seq;
            }

            // Hitung Item Sequence (Urutan Barang Hari Ini PER DEPT)
            // Dipindahkan ke sini agar hanya menghitung history milik Dept yang sama
            if (!itemDailyCounts[rowKode]) itemDailyCounts[rowKode] = 0;
            itemDailyCounts[rowKode]++;
          }
        }
      }
    });

    // WR Sequence baru untuk batch ini (Global per hari)
    // Kita gunakan running counter agar setiap item dalam batch mendapat nomor urut baru
    let runningWrSeq = maxWrSeq;

    // Pastikan Header Kolom R (QR Code) ada
    if (logSheet.getRange("R2").getValue() === "") logSheet.getRange("R2").setValue("QR Code");

    // --- DETEKSI HEADER DINAMIS UNTUK OP PENGAJU ---
    const headers = logSheet.getRange(2, 1, 1, logSheet.getLastColumn() || 1).getValues()[0];
    let cOpP = 25; // Default fallback (Kolom Y)
    headers.forEach((h, idx) => {
      if (String(h).trim().toLowerCase() === 'op pengaju') cOpP = idx + 1;
    });

    // Tentukan panjang array dinamis dari Kolom B (minimal sampai R=18, yaitu index 16 -> length 17)
    const reqColsLength = Math.max(17, cOpP - 1);

    const rowsToWrite = [];

    listFormData.forEach((formData, index) => {
      // --- LOGIKA BARU: CEK APAKAH INI KOMPONEN BARU? ---
      // Jika ada data 'newComponentData', simpan dulu ke database komponen
      // lalu ambil kode yang baru digenerate untuk dipakai di WR.
      if (formData.newComponentData) {
        try {
          const result = _internalSaveNewComponent(formData.newComponentData);
          formData.kodeKomponen = result.code; // Update kode komponen di object WR

          // FIX: Ganti data gambar (Base64) dengan URL Drive agar tidak error 50k karakter
          if (result.url) {
            formData.gambar = result.url;
          }

          // PENTING: Paksa simpan perubahan sheet Komponen Baru agar tidak hilang jika script timeout nanti
          SpreadsheetApp.flush();
        } catch (errComp) {
          formData.kodeKomponen = "ERROR_SAVE"; // Tandai jika gagal
          console.error("Gagal simpan komponen baru: " + errComp.toString());
        }
      }

      runningWrSeq++; // Increment urutan WR untuk setiap item (Jadi beda-beda tiap baris)

      // Update counter item
      if (!itemDailyCounts[formData.kodeKomponen]) itemDailyCounts[formData.kodeKomponen] = 0;
      itemDailyCounts[formData.kodeKomponen]++;
      const currentItemSeq = itemDailyCounts[formData.kodeKomponen];

      // Sanitize Dept agar tidak merusak format ID
      const safeDept = String(formData.deptPengaju).replace(/\//g, "-");

      // Format Baru: WR/Dept Pengaju/tanggal/Wr keberapa/Code barang/pada hari itu keberapa kali barang itu dipesan
      const noWR = `WR/${safeDept}/${dateForID}/${runningWrSeq}/${formData.kodeKomponen}/${currentItemSeq}`;

      // --- FORMULA QR CODE OTOMATIS DI LOG WR (KOLOM R) ---
      // Baris data saat ini = targetRow + index
      const currentRow = targetRow + index;
      const formulaQR = `=IMAGE("https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" & ENCODEURL(C${currentRow}))`;

      // Susun data secara dinamis berdasarkan letak Header
      const rowArr = new Array(reqColsLength).fill("");
      rowArr[0] = formData.tglOrder;       // B
      rowArr[1] = noWR;                    // C
      rowArr[2] = formData.kodeKomponen;   // D
      rowArr[3] = formData.namaKomponen;   // E
      rowArr[4] = formData.gambar;         // F
      rowArr[5] = formData.routing;        // G
      rowArr[6] = formData.dimensi;        // H
      rowArr[7] = formData.qty;            // I
      rowArr[8] = formData.deptPengaju;    // J
      rowArr[9] = formData.deptPenerima;   // K
      rowArr[10] = formData.deskripsi;     // L
      rowArr[11] = "Belum Verifikasi";     // M
      rowArr[12] = "Belum Verifikasi";     // N
      rowArr[13] = "Belum Verifikasi";     // O
      rowArr[14] = "Belum Dicetak";        // P
      rowArr[16] = formulaQR;              // R

      // Injeksi OP Pengaju ke kolom yang tepat secara dinamis
      if (cOpP - 2 >= 0) {
        rowArr[cOpP - 2] = formData.opPengaju || "";
      }

      rowsToWrite.push(rowArr);
    });


    if (rowsToWrite.length > 0) {
      // Masukkan ke Range B, sebanyak baris data dan sejumlah panjang kolom dinamis
      logSheet.getRange(targetRow, 2, rowsToWrite.length, reqColsLength).setValues(rowsToWrite);
    }

    return "Berhasil disimpan " + rowsToWrite.length + " item Work Request!";
  } catch (e) {
    return "Error: " + e.toString();
  }
}

// HELPER BARU: Membersihkan string status dari embel-embel "Parsial > "
function sanitizeStatus(status) {
  if (!status) return "";
  return String(status).replace(/Parsial\s*>\s*/gi, "").trim();
}

// UPDATE STATUS WR (Terima/Tolak)
function updateWRStatus(noWR, type, newStatus, additionalInfo, reason, userIdent, assignedOP) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Log WR");

  const lastRow = sheet.getLastRow();
  const dataNoWR = sheet.getRange(1, 3, lastRow, 1).getValues(); // Kolom C = 3

  const targetWR = String(noWR).trim();
  // Pastikan status dibersihkan dari "Parsial >" sebelum diproses lebih lanjut
  const cleanStatus = sanitizeStatus(newStatus);

  // --- DETEKSI HEADER DINAMIS (VERIFIKASI & PENUGASAN) ---
  const headers = sheet.getRange(2, 1, 1, sheet.getLastColumn() || 1).getValues()[0];
  let cKaP = 26, cOpR = 27, cKaR = 28; // Default fallback
  headers.forEach((h, idx) => {
    const txt = String(h).trim().toLowerCase();
    if (txt === 'kepala pengaju') cKaP = idx + 1;
    if (txt === 'op penerima') cOpR = idx + 1;
    if (txt.includes('kepala penerima')) cKaR = idx + 1; // Menangkap typo "Penerimaa"
  });

  // PERBAIKAN: Loop dari BAWAH ke ATAS (Reverse)
  // Agar jika ada duplikat No WR, yang terupdate adalah yang TERBARU (paling bawah),
  // sesuai dengan tampilan dashboard yang me-reverse data.
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

      // --- NEW LOGIC: SIMPAN IDENTITAS NAMA KETIKA DITERIMA ---
      if (type === 'pengaju' && cleanStatus === 'Diterima') {
        if (cKaP > 0) sheet.getRange(i + 1, cKaP).setValue(userIdent || ""); // Dinamis Kepala Pengaju
      } else if (type === 'penerima' && cleanStatus === 'Diterima') {
        if (cOpR > 0) sheet.getRange(i + 1, cOpR).setValue(assignedOP || ""); // Dinamis OP Penerima
        if (cKaR > 0) sheet.getRange(i + 1, cKaR).setValue(userIdent || ""); // Dinamis Kepala Penerima
      }

      // --- LOGIKA BARU: AUTO STATUS BARANG "DALAM ANTRIAN" ---
      // Jika Penerima klik "Diterima", Status Barang (Kolom 15) otomatis jadi "Dalam antrian"
      if (type === 'penerima' && cleanStatus === 'Diterima') {
        sheet.getRange(i + 1, 15).setValue("Dalam antrian");
      }

      // --- LOGIKA BARU (REVISI): CENTRALIZED LOG DI KOLOM S (19) ---
      // Format: [ Status - Qty - Tgl/Jam - Note ]
      // Semua history (Qty, Waktu, Alasan) masuk ke satu kolom S, dipisah titik koma (;)
      if (additionalInfo || reason) {
        const timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "dd/MM HH:mm");
        const qtyStr = additionalInfo || "-";
        const noteStr = reason || "-";
        const statusStr = cleanStatus; // Status saat log ini dibuat (misal: Proses: Bubut (In))

        // Format Log String
        const newLogEntry = `[ ${statusStr} - ${qtyStr} - ${timestamp} - ${noteStr} ]`;

        const logCell = sheet.getRange(i + 1, 19); // Kolom S (Index 19)
        const currentLog = String(logCell.getValue());

        let finalLog = currentLog;
        if (currentLog && currentLog !== "") {
          finalLog = currentLog + " ; " + newLogEntry; // Append dengan separator
        } else {
          finalLog = newLogEntry;
        }

        logCell.setValue(finalLog);
      }

      SpreadsheetApp.flush(); // <--- PENTING: Simpan perubahan status ke sheet SEBELUM generate PDF

      // --- AUTO GENERATE PDF & SAVE TO DRIVE (Saat Penerima Menerima) ---
      // UPDATE: Tambahkan trigger untuk Pengaju juga sesuai request
      let pdfMsg = "";
      if (type === 'penerima' && cleanStatus === 'Diterima') {
        try {
          saveWRToDrive(targetWR);
          pdfMsg = " (PDF tersimpan di Drive)";
        } catch (e) {
          console.error("Gagal Auto Save PDF: " + e.toString());
          return "Status berhasil diperbarui, TAPI Gagal Simpan PDF: " + e.toString();
        }
      }

      return "Status berhasil diperbarui" + pdfMsg;
    }
  }
  return "Data tidak ditemukan: " + targetWR;
}

// FUNGSI BARU: Proses Qty Defect dan Qty Normal Secara Bersamaan
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
    // 1. TANGANI DEFECT JIKA ADA (POTONG DATABASE LANGSUNG)
    if (defectQty > 0) {
      const qtyCell = sheet.getRange(rowIndex, 9); // Kolom I = Qty
      let currentQty = parseInt(qtyCell.getValue());
      if (isNaN(currentQty)) currentQty = 0;

      let newQty = currentQty - defectQty;
      if (newQty < 0) newQty = 0;
      qtyCell.setValue(newQty); // Potong Qty Asli

      const timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "dd/MM HH:mm");
      const defectReason = defectReasonInput ? `${defectReasonInput} (Oleh: ${userIdent || "Unknown"})` : `Koreksi Defect (Oleh: ${userIdent || "Unknown"})`;
      const defectLog = `[ Defect > ${nextStatus} - ${defectQty} - ${timestamp} - ${defectReason} ]`;

      const logCell = sheet.getRange(rowIndex, 19); // Kolom S
      const currentLog = String(logCell.getValue());
      logCell.setValue(currentLog ? currentLog + " ; " + defectLog : defectLog);

      SpreadsheetApp.flush(); // Pastikan pemotongan tersimpan agar submitPartialLog membaca data baru
      defectMsg = `Defect <b>${defectQty} pcs</b> dicatat.<br>Total Qty WR disesuaikan menjadi <b>${newQty} pcs</b>.<br>`;
    }

    // 2. TANGANI QTY PROSES & EVALUASI STATUS
    // FIX: Cegah log catatan dobel. Jika ada defect, catatan ditarik khusus untuk defect saja.
    // Jika tidak ada defect, catatan baru masuk ke log proses normal.
    const processReason = defectQty > 0 ? "" : reason;
    // Panggil logika Parsial untuk evaluasi, meskipun qty proses 0 (untuk cek jika sisa kuota terpenuhi)
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

// FUNGSI BARU: SUBMIT PARSIAL LOG (TANPA PECAH BARIS)
function submitPartialLog(originNoWR, nextStatus, partialQty, reason) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Log WR");
  const lastRow = sheet.getLastRow();

  // Cari Baris Data Asli
  // Kolom C (Index 3) = No WR
  const dataNoWR = sheet.getRange(3, 3, lastRow - 2, 1).getValues();
  let rowIndex = -1;

  // Loop dari bawah (ambil data terbaru jika ada duplikat)
  for (let i = dataNoWR.length - 1; i >= 0; i--) {
    if (String(dataNoWR[i][0]).trim() === String(originNoWR).trim()) {
      rowIndex = i + 3; // +3 karena data mulai baris 3
      break;
    }
  }

  if (rowIndex === -1) return { status: 'error', message: "Data WR Asli tidak ditemukan." };

  try {
    // 1. Ambil Data Baris Asli (Kolom B sampai R) -> 17 Kolom
    // Kita butuh data lengkap untuk diduplikasi
    const originRowData = sheet.getRange(rowIndex, 2, 1, 17).getValues()[0];

    // Index Data (0-based dari array originRowData):
    // 0:Tgl, 1:NoWR, 2:Kode, ..., 7:Qty (Col I), ..., 13:StatusBarang (Col O), ..., 16:QR

    const currentQty = parseInt(originRowData[7]);
    const inputQty = parseInt(partialQty) || 0;

    if (isNaN(currentQty) || isNaN(inputQty)) return { status: 'error', message: "Format Qty tidak valid." };

    // Bersihkan nextStatus agar penulisan & perbandingan aman
    const cleanNextStatus = sanitizeStatus(nextStatus);
    const targetClean = cleanNextStatus.toLowerCase();

    // --- HITUNG AKUMULASI PARSIAL SEBELUMNYA ---
    const logCell = sheet.getRange(rowIndex, 19); // Kolom S
    const rawLogs = String(logCell.getValue());

    let totalProcessedForNextStep = 0;

    if (rawLogs) {
      const entries = rawLogs.split(" ; ");
      entries.forEach(entry => {
        // Bersihkan log entry lama agar kebal terhadap variasi huruf/spasi
        const cleanEntry = sanitizeStatus(entry).toLowerCase();
        if (cleanEntry.includes(targetClean)) {
          // CEGAH DOUBLE COUNT DEFECT DI BACKEND: Abaikan jika pencarian bukan defect, tapi log adalah defect
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

    // Hitung total semua defect untuk mendapatkan batas maksimal absolut (original Qty)
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
      // CLOSING: Jika kuota 100% terpenuhi, tulis status bersih ke database utama!
      sheet.getRange(rowIndex, 15).setValue(cleanNextStatus); // Update Kolom O (Status Barang)
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

// AMBIL DATA WR UNTUK DICETAK (Status Pengaju = "Verifikasi" atau "Disetujui" atau apapun yang valid)
function getPrintableWRData(deptUser) {
  try {
    const data = getLogWRData();

    // Filter Awal: Wajib Sesuai Dept Pengaju (Jika parameter ada)
    let filtered = data;
    const deptList = normalizeDeptUserList(deptUser);
    if (deptList.length > 0) {
      filtered = data.filter(item => {
        const pengaju = String(item.pengaju || "").trim().toLowerCase();
        return deptList.some(dept => pengaju === dept);
      });
    }

    // Filter Kedua: Status.
    // UPDATE: Tampilkan data yang SUDAH DIVERIFIKASI (Diterima) oleh Dept PENERIMA.

    return filtered.filter(item => {
      const s = String(item.statusPenerima).toLowerCase();
      return s === "diterima";
    });
  } catch (e) {
    return [];
  }
}

function normalizeDeptUserList(deptUser) {
  let list = [];

  if (Array.isArray(deptUser)) {
    list = deptUser;
  } else if (deptUser) {
    list = String(deptUser).split(",");
  }

  return [...new Set(list
    .map(item => String(item || "").trim().toLowerCase())
    .filter(item => item !== "" && item !== "-"))];
}

// Helper untuk membaca Mapping dari Sheet "Format_CetakWR"
function getPrintMapping() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Format_CetakWR");

  // Default config
  const result = { map: {}, templateSheetName: "Format WR" };

  if (!sheet) return result;

  // Ambil Template Sheet Name (Cek D2 dan D3 untuk fleksibilitas Header/Value)
  const rangeName = sheet.getRange("D2:D3").getValues();
  const d2 = String(rangeName[0][0]).trim();
  const d3 = String(rangeName[1][0]).trim();

  // Logika Prioritas: Cek mana yang benar-benar ada di Spreadsheet
  if (d2 && ss.getSheetByName(d2)) {
    result.templateSheetName = d2;
  } else if (d3 && ss.getSheetByName(d3)) {
    result.templateSheetName = d3;
  } else if (d2) {
    // Jika tidak ada yang valid, tebak mana yang dimaksud user
    const lower = d2.toLowerCase();
    // Jika D2 terlihat seperti header (isi kata "Sheet"/"Template"), pakai D3
    if ((lower.includes("sheet") || lower.includes("template") || lower.includes("nama")) && d3) {
      result.templateSheetName = d3;
    } else {
      result.templateSheetName = d2;
    }
  }

  const lastRow = sheet.getLastRow();
  if (lastRow < 3) return result; // Data mulai baris 3 (Header baris 2)

  // Ambil Kolom B (Alamat/Field) dan C (Tujuan/Cell)
  // getRange(row, col, numRows, numCols) -> getRange(3, 2, ..., 2)
  const data = sheet.getRange(3, 2, lastRow - 2, 2).getValues();

  // Regex untuk mendeteksi format alamat sel (misal C4, AA10, $C$4)
  const cellRegex = /^[$]?[A-Za-z]{1,3}[$]?\d+$/;

  data.forEach(r => {
    const valB = String(r[0]).trim(); // Kolom B (Alamat?)
    const valC = String(r[1]).trim(); // Kolom C (Tujuan?)

    if (valB && valC) {
      // Logika Deteksi Otomatis: Mana yang Alamat Sel?
      if (cellRegex.test(valB)) {
        // B adalah Address (C4), C adalah Field Name (No WR)
        result.map[valC.toLowerCase()] = valB.toUpperCase().replace(/[$]/g, "");
      } else if (cellRegex.test(valC)) {
        // C adalah Address, B adalah Field Name (Jaga-jaga terbalik)
        result.map[valB.toLowerCase()] = valC.toUpperCase().replace(/[$]/g, "");
      } else {
        // Fallback: Asumsi User B=Field Name, C=Cell Address (Sesuai "Tujuan" di C)
        result.map[valB.toLowerCase()] = valC;
      }
    }
  });
  return result;
}

// FUNGSI BARU: Ambil daftar operator berdasarkan departemen
function getOperatorsByDept(dept) {
  if (!dept) return [];
  try {
    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = ss.getSheetByName("Data User");
    if (!sheet) return [];

    const lastRow = sheet.getLastRow();
    if (lastRow < 3) return [];

    // Ambil data dari Kolom C (Dept) dan F (Nama Akun)
    const data = sheet.getRange(3, 1, lastRow - 2, 6).getValues();
    const targetDept = String(dept).trim().toLowerCase();
    const operators = [];

    // Gunakan Mapping Dept (Sesuai Masteran Baru)
    const deptMap = getDeptUserMapping();

    data.forEach(row => {
      const valDept = String(row[2]).trim(); // Kolom C (Raw ID)
      const userName = String(row[5]).trim(); // Kolom F

      // Resolve ID Dept ke Nama Dept
      const deptInfo = resolveDeptUserValue(valDept, deptMap);

      // Cek apakah targetDept ada di dalam daftar departemen user ini
      const isMatch = deptInfo.names.some(n => n.toLowerCase() === targetDept);

      if (isMatch && userName) {
        operators.push(userName);
      }
    });

    return [...new Set(operators)].sort(); // Return unique sorted names
  } catch (e) { return []; }
}

// HELPER BARU: Ambil Mapping User ke Link TTD dari Data User
function getUserTTDMap() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const map = {};

  const sheet = ss.getSheetByName("Data User");
  if (sheet) {
    const lastRow = sheet.getLastRow();
    if (lastRow >= 3) {
      const data = sheet.getRange(3, 6, lastRow - 2, 3).getValues(); // F: Nama, G: Foto, H: Link TTD
      data.forEach(row => {
        const name = String(row[0]).trim();
        const ttd = String(row[2]).trim();
        if (name && ttd) map[name.toLowerCase()] = ttd;
      });
    }
  }
  return map;
}

// HELPER BARU: Convert Drive link to lh3 Image format untuk Cetak
function getDriveImageUrl(url) {
  if (!url) return "";
  const match = url.match(/\/d\/(.+?)\//) || url.match(/id=(.+?)(&|$)/);
  if (match && match[1]) return `https://lh3.googleusercontent.com/d/${match[1]}`;
  return url;
}

// HELPER: Isi Sheet Cetak WR (Dipisah agar bisa dipakai printBulkWR dan saveWRToDrive)
function _populateCetakWRSheet(noWRList, updateStatus = true) {
  if (!noWRList || noWRList.length === 0) return { status: 'error', message: "Tidak ada WR dipilih" };

  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  let printSheet = ss.getSheetByName("Cetak WR");
  if (!printSheet) {
    printSheet = ss.insertSheet("Cetak WR");
  }
  printSheet.clear();

  try {
    const config = getPrintMapping();
    const mapping = config.map;
    const templateName = config.templateSheetName;
    const ttdMap = getUserTTDMap(); // Ambil list TTD

    const formatSheet = ss.getSheetByName(templateName);
    if (!formatSheet) return { status: 'error', message: `Sheet Template '${templateName}' tidak ditemukan.` };

    const templateRange = formatSheet.getRange("B3:I23");

    for (let c = 2; c <= 9; c++) {
      printSheet.setColumnWidth(c, formatSheet.getColumnWidth(c));
    }

    const rowHeights = [];
    for (let r = 3; r <= 23; r++) {
      rowHeights.push(formatSheet.getRowHeight(r));
    }

    const allWRData = getLogWRData();
    let currentStartRow = 1;
    const templateHeight = 21;
    let count = 0;

    for (const noWR of noWRList) {
      const wrData = allWRData.find(d => d.noWR === noWR);
      if (!wrData) continue;

      // REPEAT 3 TIMES FOR EACH WR (MENDUPLIKAT CETAKAN MENJADI 3 RANGKAP)
      for (let copy = 0; copy < 3; copy++) {
        templateRange.copyTo(printSheet.getRange(currentStartRow, 2));

        for (let h = 0; h < rowHeights.length; h++) {
          try { printSheet.setRowHeight(currentStartRow + h, rowHeights[h]); } catch (e) { }
        }

        for (const [key, cellAddr] of Object.entries(mapping)) {
          const match = cellAddr.match(/^([A-Z]+)(\d+)$/);
          if (match) {
            const colStr = match[1];
            const rowNum = parseInt(match[2], 10);
            const targetRow = rowNum + (currentStartRow - 3);

            let val = "";
            let isImage = false;
            const k = key.toLowerCase().replace(/[^a-z0-9]/g, "");

            // Tentukan apakah cell ini harus di-center
            let shouldCenter = false;
            if (k.includes("ttd") || k.includes("routing") || (k.includes("op") && (k.includes("pengaju") || k.includes("penerima"))) || (k.includes("kepala") && (k.includes("pengaju") || k.includes("penerima"))) || (k.includes("kabag") && (k.includes("pengaju") || k.includes("penerima")))) {
              shouldCenter = true;
            }


            if (k.includes("no") && k.includes("wr")) val = wrData.noWR;
            else if (k.includes("tanggal") || k.includes("tgl")) val = wrData.tgl;
            else if ((k.includes("pengaju") || k.includes("pemohon") || k.includes("dari")) && !k.includes("op") && !k.includes("kepala") && !k.includes("kabag") && !k.includes("ttd")) val = wrData.pengaju;
            else if ((k.includes("penerima") || k.includes("kepada") || k.includes("untuk")) && !k.includes("op") && !k.includes("kepala") && !k.includes("kabag") && !k.includes("ttd")) val = wrData.penerima;
            else if (k.includes("kode")) val = wrData.kode;
            else if (k.includes("nama") && !k.includes("op") && !k.includes("kepala") && !k.includes("kabag")) val = wrData.nama;
            else if (k.includes("routing")) val = wrData.routing;
            else if (k.includes("dimensi")) val = wrData.dimensi;
            else if (k.includes("qty") || k.includes("jumlah")) val = wrData.qty;
            else if (k.includes("deskripsi") || k.includes("ket")) val = wrData.deskripsi;
            else if (k.includes("gambar") || k.includes("foto")) val = "Terlampir";

            // --- IDENTITAS DARI KOLOM BARU ---
            else if (k.includes("op") && k.includes("pengaju") && !k.includes("ttd")) val = wrData.opPengaju;
            else if ((k.includes("kepala") || k.includes("kabag")) && k.includes("pengaju") && !k.includes("ttd")) val = wrData.kaPengaju;
            else if (k.includes("op") && k.includes("penerima") && !k.includes("ttd")) val = wrData.opPenerima;
            else if ((k.includes("kepala") || k.includes("kabag")) && k.includes("penerima") && !k.includes("ttd")) val = wrData.kaPenerima;

            // --- TTD LOGIC (IMAGE MATCHING) ---
            else if (k.includes("ttd") && k.includes("op") && k.includes("pengaju")) {
              const link = ttdMap[(wrData.opPengaju || "").toLowerCase()];
              if (link) { val = `=IMAGE("${getDriveImageUrl(link)}")`; isImage = true; }
            }
            else if (k.includes("ttd") && (k.includes("kepala") || k.includes("kabag")) && k.includes("pengaju")) {
              const link = ttdMap[(wrData.kaPengaju || "").toLowerCase()];
              if (link) { val = `=IMAGE("${getDriveImageUrl(link)}")`; isImage = true; }
            }
            else if (k.includes("ttd") && k.includes("op") && k.includes("penerima")) {
              const link = ttdMap[(wrData.opPenerima || "").toLowerCase()];
              if (link) { val = `=IMAGE("${getDriveImageUrl(link)}")`; isImage = true; }
            }
            else if (k.includes("ttd") && (k.includes("kepala") || k.includes("kabag")) && k.includes("penerima")) {
              const link = ttdMap[(wrData.kaPenerima || "").toLowerCase()];
              if (link) { val = `=IMAGE("${getDriveImageUrl(link)}")`; isImage = true; }
            }

            if (val !== "") {
              const cell = printSheet.getRange(`${colStr}${targetRow}`);
              if (isImage) {
                cell.setFormula(val);
              } else {
                cell.setValue(val);
                cell.setWrapStrategy(SpreadsheetApp.WrapStrategy.WRAP);
              }
              cell.setFontSize(9);
              cell.setHorizontalAlignment(shouldCenter ? "center" : "left");
              cell.setVerticalAlignment("middle");
            }
          }
        }
        currentStartRow += templateHeight;
      }

      // TAMBAHAN SPACER: Baris pemisah antar WR
      printSheet.setRowHeight(currentStartRow, 30);
      currentStartRow += 1;

      count++;
    }

    if (count === 0) return { status: 'error', message: "Data WR tidak ditemukan" };
    SpreadsheetApp.flush();

    if (updateStatus) {
      _updatePrintStatus(noWRList);
    }

    return { status: 'success', sheetId: printSheet.getSheetId(), lastRowIndex: currentStartRow - 2 };

  } catch (e) {
    return { status: 'error', message: e.toString() };
  }
}

// HELPER: Update Status Cetak di Log WR (Kolom P / Index 16)
function _updatePrintStatus(noWRList) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Log WR");
  const lastRow = sheet.getLastRow();
  if (lastRow < 3) return;

  // Ambil Kolom C (No WR) untuk pencarian
  const dataNoWR = sheet.getRange(3, 3, lastRow - 2, 1).getValues();
  const targetSet = new Set(noWRList.map(String));

  // Loop dan update baris yang sesuai
  for (let i = 0; i < dataNoWR.length; i++) {
    if (targetSet.has(String(dataNoWR[i][0]))) {
      // Baris = i + 3. Kolom P = 16.
      sheet.getRange(i + 3, 16).setValue("Sudah Dicetak");
    }
  }
}

// FUNGSI BULK PRINT (Satu Sheet "Cetak WR", Stacked)
function printBulkWR(noWRList) {
  try {
    const prep = _populateCetakWRSheet(noWRList, true);
    if (prep.status === 'error') return prep;

    const url = "https://docs.google.com/spreadsheets/d/" + SPREADSHEET_ID + "/export" +
      "?format=pdf" +
      "&size=A4" +
      "&portrait=true" +
      "&fitw=true" +
      "&gridlines=false" +
      "&top_margin=0.25&bottom_margin=0.25&left_margin=0.25&right_margin=0.25" +
      "&gid=" + prep.sheetId +
      "&r1=0&c1=1&c2=9&r2=" + prep.lastRowIndex;

    return { status: 'success', url: url };

  } catch (e) {
    return { status: 'error', message: "Print Error: " + e.toString() };
  }
}

// FUNGSI SIMPAN PDF KE DRIVE (Otomatis)
function saveWRToDrive(noWR) {
  const fileName = noWR.replace(/[\/\\:]/g, "_") + ".pdf";
  _savePDFToDrive([noWR], fileName, false);
}

// FUNGSI CETAK WR (Single - Wrapper ke Bulk)
function printWR(noWR) {
  return printBulkWR([noWR]);
}

// FUNGSI PREVIEW PDF SINGLE
function getPDFPreviewUrl(noWR) {
  // Alihkan ke fungsi Bulk Preview agar selalu meng-generate preview terbaru (real-time)
  return getBulkPDFPreviewUrl([noWR]);
}

// --- FUNGSI BULK PREVIEW ---
function getBulkPDFPreviewUrl(noWRList) {
  const timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyyMMdd_HHmmss");
  const fileName = `Preview_Bulk_${timestamp}.pdf`;

  try {
    // 1. Generate & Save PDF Sementara menggunakan fungsi penumpukan tabel (Berurutan)
    const fileId = _savePDFToDrive(noWRList, fileName, false);

    // 2. Kembalikan URL Preview
    return { status: 'success', url: "https://drive.google.com/file/d/" + fileId + "/preview" };

  } catch (e) {
    return { status: 'error', message: e.toString() };
  }
}

// HELPER: CORE LOGIC SAVE PDF TO DRIVE
function _savePDFToDrive(noWRList, fileName, updateStatus = true) {
  const FOLDER_ID = "1tFEQgBC1IJyPM4mRZ9sUrO2taQjNuZbq";

  const prep = _populateCetakWRSheet(noWRList, updateStatus);
  if (prep.status === 'error') throw prep.message;

  const url = "https://docs.google.com/spreadsheets/d/" + SPREADSHEET_ID + "/export" +
    "?format=pdf" +
    "&size=A4" +
    "&portrait=true" +
    "&fitw=true" +
    "&gridlines=false" +
    "&top_margin=0.25&bottom_margin=0.25&left_margin=0.25&right_margin=0.25" +
    "&gid=" + prep.sheetId +
    "&r1=0&c1=1&c2=9&r2=" + prep.lastRowIndex;

  const token = ScriptApp.getOAuthToken();
  const response = UrlFetchApp.fetch(url, {
    headers: {
      'Authorization': 'Bearer ' + token
    }, muteHttpExceptions: true
  });

  if (response.getResponseCode() !== 200) {
    throw "Gagal Download PDF (" + response.getResponseCode() + "): " + response.getContentText();
  }

  const blob = response.getBlob().setName(fileName);
  const folder = DriveApp.getFolderById(FOLDER_ID);
  const file = folder.createFile(blob);

  // Set permission to anyone with link to ensure preview works in iframe
  try {
    file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
  } catch (e) { /* Ignore if fails */ }

  return file.getId();
}


// AMBIL DATA LOG WR UNTUK DASHBOARD
function getLogWRData() {
  try {
    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = ss.getSheetByName("Log WR");
    if (!sheet) return []; // Sheet tidak ditemukan

    const lastRow = sheet.getLastRow();

    // Data mulai baris 3 (Baris 1 Judul, Baris 2 Header)
    if (lastRow < 3) return [];

    // --- DETEKSI HEADER DINAMIS (MEMBACA DATA) ---
    const headers = sheet.getRange(2, 1, 1, sheet.getLastColumn() || 1).getValues()[0];
    let cOpP = 25, cKaP = 26, cOpR = 27, cKaR = 28;
    headers.forEach((h, idx) => {
      const txt = String(h).trim().toLowerCase();
      if (txt === 'op pengaju') cOpP = idx + 1;
      if (txt === 'kepala pengaju') cKaP = idx + 1;
      if (txt === 'op penerima') cOpR = idx + 1;
      if (txt.includes('kepala penerima')) cKaR = idx + 1;
    });

    const maxReqCol = Math.max(24, cOpP, cKaP, cOpR, cKaR);

    if (sheet.getMaxColumns() < maxReqCol) {
      sheet.insertColumnsAfter(sheet.getMaxColumns(), maxReqCol - sheet.getMaxColumns());
    }

    // Ambil data mulai dari kolom B(2) sampai kolom terakhir yang terdeteksi
    const data = sheet.getRange(3, 2, lastRow - 2, maxReqCol - 1).getValues();

    // Filter baris yang kosong (Pastikan No WR / Kolom C tidak kosong)
    const validData = data.filter(r => r[1] && r[1].toString() !== "");

    // Map data agar mudah dibaca di frontend
    return validData.map(r => {
      // Format Tanggal di Server agar aman dan tidak bikin crash di frontend
      let tglStr = "-";
      let tglRaw = "";
      if (r[0] instanceof Date) {
        tglStr = Utilities.formatDate(r[0], Session.getScriptTimeZone(), "dd MMM yyyy");
        tglRaw = Utilities.formatDate(r[0], Session.getScriptTimeZone(), "yyyy-MM-dd");
      } else if (r[0]) {
        tglStr = String(r[0]);
        tglRaw = String(r[0]).substring(0, 10);
      }

      // Generate URL QR Code Server-side (Dikirim ke Dashboard sebagai string URL gambar)
      const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${encodeURIComponent(String(r[1] || "-"))}`;

      // --- PARSE LOGS DARI KOLOM S (Index 17) ---
      // Format Raw: [ Status - Qty - Time - Note ] ; [ ... ]
      const rawLogs = String(r[17] || "");
      const parsedLogs = [];
      if (rawLogs) {
        const entries = rawLogs.split(" ; ");
        entries.forEach(entry => {
          // Bersihkan kurung siku [ ]
          const clean = entry.replace(/^\[\s*/, "").replace(/\s*\]$/, "");
          // Split berdasarkan " - "
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
        tgl: tglStr, // Sudah jadi string, aman dikirim
        tglRaw: tglRaw, // Format YYYY-MM-DD untuk filtering
        noWR: String(r[1] || "-"),
        kode: String(r[2] || "-"),
        nama: String(r[3] || "-"),
        gambar: String(r[4] || ""), // Kolom F (Index 4)
        routing: String(r[5] || "-"),
        dimensi: String(r[6] || "-"),
        qty: String(r[7] || "0"),
        pengaju: String(r[8] || "-"),
        penerima: String(r[9] || "-"),
        deskripsi: String(r[10] || "-"),
        statusPengaju: String(r[11] || "Belum Verifikasi"), // Kolom M
        statusPenerima: String(r[12] || "Belum Verifikasi"), // Kolom N
        status: String(r[13] || "Belum Verifikasi"), // Kolom O
        statusCetak: String(r[14] || "Belum Dicetak"), // Kolom P
        qrUrl: qrUrl, // URL Gambar QR untuk Dashboard (Menggantikan pembacaan kolom R yang berisi formula)
        // LOGS BARU (Array of Objects dari Kolom S)
        logs: parsedLogs,
        alasan: String(r[22] || ""), // Kolom X (Index 22 dari range B-X)
        logRevisi: String(r[18] || ""), // Kolom T 
        opPengaju: (cOpP - 2 >= 0 && r[cOpP - 2]) ? String(r[cOpP - 2]) : "-",
        kaPengaju: (cKaP - 2 >= 0 && r[cKaP - 2]) ? String(r[cKaP - 2]) : "-",
        opPenerima: (cOpR - 2 >= 0 && r[cOpR - 2]) ? String(r[cOpR - 2]) : "-",
        kaPenerima: (cKaR - 2 >= 0 && r[cKaR - 2]) ? String(r[cKaR - 2]) : "-"
      };
    }).reverse();
  } catch (e) {
    throw "Gagal mengambil data: " + e.toString();
  }
}

/**
 * FUNGSI SETUP IZIN (PENTING!)
 * Jalankan fungsi ini SEKALI SAJA secara manual dari Editor Apps Script
 * untuk memunculkan popup izin akses UrlFetchApp (Download PDF).
 */
function authorizeScript() {
  UrlFetchApp.fetch("https://www.google.com");
  // Memicu izin akses Google Drive FULL (Write/Create File)
  const temp = DriveApp.createFile("temp_auth_trigger.txt", "Hanya pemicu izin");
  temp.setTrashed(true); // Langsung hapus agar tidak nyampah
  console.log("Izin UrlFetchApp dan DriveApp (Full Access) berhasil diberikan.");
}
// FUNGSI UNTUK MEMANGGIL FILE HTML/CSS/JS LAIN
function include(filename) {
  return HtmlService.createHtmlOutputFromFile(filename).getContent();
}

// --- FUNGSI BARU (WAJIB ADA): UPDATE ROUTING & PENERIMA ---
function updateRoutingWR(noWR, newRouting, newPenerima, reason) {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName("Log WR");

  const lastRow = sheet.getLastRow();
  // Ambil Kolom C (No WR) untuk pencarian -> Index 3
  const dataNoWR = sheet.getRange(3, 3, lastRow - 2, 1).getValues();

  // Loop Reverse (Bawah ke Atas) untuk mencari data terbaru
  for (let i = dataNoWR.length - 1; i >= 0; i--) {
    if (String(dataNoWR[i][0]).trim() === String(noWR).trim()) {
      const rowIndex = i + 3; // +3 karena offset header & array

      // 1. Update Kolom G (Routing) -> Index 7
      sheet.getRange(rowIndex, 7).setValue(newRouting);

      // 2. Update Kolom K (Dept Penerima) -> Index 11
      sheet.getRange(rowIndex, 11).setValue(newPenerima);

      // 3. Update Log Revisi di Kolom T -> Index 20
      // Format: [dd/MM - Alasan]
      const logCell = sheet.getRange(rowIndex, 20);
      const currentLog = String(logCell.getValue());
      const timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "dd/MM");

      // Format Log Baru
      const newLogEntry = `[${timestamp} - ${reason}]`;

      // Gabungkan dengan log lama
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
    let colNama = 3; // Fallback Clue: Kolom C (Index 3)

    headers.forEach((h, idx) => {
      const text = String(h).trim().toLowerCase();
      if (text === "kode komponen" || text === "kode qr") colKode = idx + 1;
      if (text === "saldo") colSaldo = idx + 1;
      if (text === "nama barang" || text === "nama komponen") colNama = idx + 1;
    });

    // Cari baris dengan kode komponen yang sesuai
    const dataKode = sheet.getRange(3, colKode, lastRow - 2, 1).getValues();
    for (let i = 0; i < dataKode.length; i++) {
      if (String(dataKode[i][0]).trim().toUpperCase() === String(kodeKomponen).trim().toUpperCase()) {
        const targetRow = i + 3; // +3 karena getRange mulai dari baris 3

        // Update Saldo
        const cellSaldo = sheet.getRange(targetRow, colSaldo);
        const currentSaldo = Number(cellSaldo.getValue()) || 0;
        const newSaldo = currentSaldo + addQty;
        cellSaldo.setValue(newSaldo);

        // --- TULIS HISTORY KE LOG KOMPONEN ---
        const logSheet = ss.getSheetByName("Log Komponen");
        if (logSheet) {
          const namaBarang = sheet.getRange(targetRow, colNama).getValue();

          // Membaca header di baris ke-2 secara dinamis
          const logLastCol = logSheet.getLastColumn();
          if (logLastCol > 0) {
            const logHeaders = logSheet.getRange(2, 1, 1, logLastCol).getValues()[0];
            const newRowData = new Array(logLastCol).fill(""); // Siapkan array kosong

            // Cocokkan nama header dan isi array sesuai index kolomnya
            logHeaders.forEach((h, idx) => {
              const text = String(h).trim().toLowerCase();
              if (text === "tanggal") newRowData[idx] = new Date();
              else if (text === "jenis") newRowData[idx] = "Masuk";
              else if (text === "kode qr" || text === "kode komponen") newRowData[idx] = kodeKomponen;
              else if (text === "nama barang" || text === "nama komponen") newRowData[idx] = namaBarang;
              else if (text === "jumlah" || text === "qty") newRowData[idx] = addQty;
              else if (text === "saldo awal") newRowData[idx] = currentSaldo;
              else if (text === "saldo akhir") newRowData[idx] = newSaldo;
              else if (text === "departemen") newRowData[idx] = "-";
              else if (text === "status") newRowData[idx] = "Work Request";
            });

            // Tambahkan baris baru yang sudah tersusun rapi sesuai urutan kolom dinamis
            logSheet.appendRow(newRowData);
          } else {
            // Fallback jika sheet log benar-benar kosong dan tidak ada header
            logSheet.appendRow([
              new Date(), "Masuk", kodeKomponen, namaBarang, addQty, currentSaldo, newSaldo, "-", "Work Request"
            ]);
          }
        }

        return { success: true, message: `Saldo master data otomatis diperbarui (+${addQty} pcs).` };
      }
    }
    return { success: false, message: "Catatan: Saldo tidak ditambah (Kode komponen belum terdaftar di database master)." };
  } catch (e) { return { success: false, message: `Catatan: Gagal tambah saldo otomatis (${e.message}).` }; }
}

function testAksesDeptUser() {
  const map = getDeptUserMapping();
  console.log("getDeptUserMapping output:", JSON.stringify(map));

  const testResolve = resolveDeptUserValue("11,1,3,2", map);
  console.log("resolveDeptUserValue output:", JSON.stringify(testResolve));
}
// --- FUNGSI BARU: DATA KOMPONEN DASHBOARD ---
function getComponentDataForDashboard() {
  try {
    const TARGET_SS_ID = '1uOd7ZQcYhnClYybBj_boCZE36vTvW2EzumY-f-hr0ns'; // ID yang Bener!!
    const ss = SpreadsheetApp.openById(TARGET_SS_ID);
    const sheet = ss.getSheetByName("Database Komponen");

    if (!sheet) {
      return { status: 'error', message: 'Sheet Database Komponen tidak ditemukan di spreadsheet tersebut.' };
    }

    const displayValues = sheet.getDataRange().getDisplayValues();
    const rawValues = sheet.getDataRange().getValues();

    const headerRowIndex = 1; // Baris 2
    if (displayValues.length <= headerRowIndex) {
      return { status: 'success', data: [] };
    }

    const headers = displayValues[headerRowIndex].map(h => h.toString().trim());
    const findCol = (name) => headers.findIndex(h => h.toLowerCase().includes(name.toLowerCase()));

    const colIndex = {
      kodeKomponen: findCol('Kode Komponen'),
      namaKomponen: findCol('Nama Komponen'),
      kodeRak: findCol('Kode Rak'),
      saldo: findCol('Saldo'),
      saldoMinimum: findCol('Saldo Minimum'),
      panjang: findCol('Panjang'),
      diameterLebar: findCol('Diameter / Lebar'),
      tebal: findCol('Tebal'),
      gambar: Math.max(findCol('Gambar Komponen'), findCol('Gambar'))
    };

    const result = [];

    for (let i = headerRowIndex + 1; i < displayValues.length; i++) {
      const rowDisplay = displayValues[i];
      const rowRaw = rawValues[i];

      if (colIndex.kodeKomponen !== -1 && !rowDisplay[colIndex.kodeKomponen]) continue;

      const cleanNumber = (val) => {
        if (!val) return 0;
        const cleaned = val.toString().replace(/[^0-9.-]+/g, "");
        const parsed = parseFloat(cleaned);
        return isNaN(parsed) ? 0 : parsed;
      };

      const saldo = colIndex.saldo !== -1 ? cleanNumber(rowDisplay[colIndex.saldo]) : 0;
      const saldoMin = colIndex.saldoMinimum !== -1 ? cleanNumber(rowDisplay[colIndex.saldoMinimum]) : 0;

      result.push({
        kode: colIndex.kodeKomponen !== -1 ? rowDisplay[colIndex.kodeKomponen] : '-',
        nama: colIndex.namaKomponen !== -1 ? rowDisplay[colIndex.namaKomponen] : '-',
        rak: colIndex.kodeRak !== -1 ? rowDisplay[colIndex.kodeRak] : '-',
        saldo: saldo,
        saldoMin: saldoMin,
        panjang: colIndex.panjang !== -1 ? rowDisplay[colIndex.panjang] : '',
        lebar: colIndex.diameterLebar !== -1 ? rowDisplay[colIndex.diameterLebar] : '',
        tebal: colIndex.tebal !== -1 ? rowDisplay[colIndex.tebal] : '',
        gambar: colIndex.gambar !== -1 ? rowRaw[colIndex.gambar] : ''
      });
    }

    return { status: 'success', data: result };

  } catch (e) {
    return { status: 'error', message: e.toString() };
  }
}

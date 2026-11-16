# Buku Panduan Resmi & Catatan Migrasi Data: Log WR Spreadsheet ke HPK WMS

Dokumen ini adalah catatan teknis resmi dan panduan pemeliharaan permanen mengenai proses migrasi data dari sistem lama berbasis **Google Spreadsheet (`System WR lama/Spreadsheet/Form Work Request - Log WR.csv`)** ke dalam database relasional **HPK WMS (Laravel 10 + MySQL)**.

Setiap developer, staf IT, maupun AI Agent CLI di masa mendatang **WAJIB membaca dokumen ini** sebelum melakukan sinkronisasi atau perubahan data Work Request lama.

---

## 1. Latar Belakang & Sumber Data

- **Sumber Data Asli:**
  1. `System WR lama/Spreadsheet/Form Work Request - Log WR.csv` (822 baris data transaksi WRI historis).
  2. `System WR lama/Spreadsheet/Form Work Request - Komponen baru.csv` (119 baris pendaftaran komponen non-katalog).
  3. `System WR lama/Spreadsheet/Form Work Request - ID_Routing.csv` (12 routing departemen pengerjaan).
- **Tujuan Migrasi:**
  1. Menyelamatkan seluruh arsip historis permohonan pengerjaan dan log audit pengerjaan mesin.
  2. Mengaktifkan kembali **159 tiket berjalan (*in-progress*)** yang belum selesai di spreadsheet agar dapat dilanjutkan proses fisiknya melalui stasiun mesin dan modul Material Handling di sistem baru.
  3. Mengunduh 491 berkas foto fisik dari Google Drive ke server master lokal On-Premise NAS (`Z:\...` / `uploads/components/`).
  4. Menjaga integritas inventori gudang dengan **Nol Perubahan Stok (*Zero Stock Disturbance*)**.

---

## 2. Standardisasi Format Nomor WR (6-Segmen)

Format nomor dokumen WR yang dibakukan dan diselaraskan:
$$\text{WR} \;/\; \{\text{Dept Pengaju}\} \;/\; \{\text{YYYYMMDD}\} \;/\; \{\text{Urutan WR Harian}\} \;/\; \{\text{Kode Part / "Baru"}\} \;/\; \{\text{Urutan Item Harian}\}$$

### Contoh Riil:
- **Komponen Master Terdaftar:**
  `WR/Dept Gudang/20260608/1/CFPP10100100031W/1`
- **Komponen Belum Terdaftar (Baru):**
  `WR/Dept Gudang/20260611/2/Baru/1`

### Aturan Identitas `"Baru"`:
- Pada nomor dokumen fisik WR (surat jalan, antrean mesin, label cetak), slot ke-5 **tetap tercetak literal `"Baru"`**.
- Di balik layar database, tabel `components` membuatkan rekaman master sementara dengan kode: **`NEW-{YYYYMMDD}-{Seq}`** (contoh: `NEW-20260611-001`) dan status `pending_catalog` agar relasi foreign key `work_requests.component_id` tetap valid 100%.

---

## 3. Protokol Auto-Download Foto Google Drive ke NAS

- **Total Berkas Foto Unik:** Terdapat **491 foto unik** di Google Drive lama.
- **Format Penamaan File Foto di NAS:**
  1. *Komponen Resmi (331 part):* Disimpan dengan nama kode part resminya: **`[part_number].jpg`** (contoh: `CFPP10100100031W.jpg`).
  2. *Komponen Baru (209 foto):* Disimpan dengan nama kode part sementaranya: **`NEW-{YYYYMMDD}-{Seq}.jpg`** (contoh: `NEW-20260617-001.jpg`).
- **Lokasi Penyimpanan Ganda:**
  - **Master Storage:** Drive NAS `Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House` (fallback cadangan: `D:\Ali Magang\KOMPONEN`).
  - **Web Mirror:** `public/uploads/components/` (untuk akses HTTP cepat stasiun mesin).
- **Penanganan Gambar Gagal / Privat:**
  Jika link Google Drive mati (404/403), script memasang fallback ke `images/placeholder_part.svg` tanpa membatalkan pembuatan tiket WR.

---

## 4. Jaminan Nol Perubahan Stok (Zero Stock Disturbance Protocol)

> [!CAUTION]
> **ATURAN MUTLAK:** Migrasi data WRI lama adalah perpindahan data job order administratif dan jejak pengerjaan mesin, **BUKAN TRANSAKSI MUTASI INVENTORI GUDANG**.

1. **Isolasi Mutlak Tabel Stok:**
   - Script migrasi **DILARANG KERAS** menambah baris ke tabel `transactions` (`inbound`, `outbound`, `transfer`).
   - Nilai kolom `components.total_stock` **TIDAK BOLEH BERUBAH 1 PCS PUN**.
2. **Flag Data Migrasi (`is_legacy`):**
   - Seluruh tiket yang berasal dari migrasi spreadsheet ditandai dengan flag `is_legacy = true`.
   - Ketika 159 tiket in-progress kelak diselesaikan di sistem baru, sistem menyediakan validasi apakah mutasi fisik barang sudah dihitung di masa lalu atau hanya penyelesaian status administratif.

---

## 5. Protokol Anti-Duplikasi (Idempotensi Penuh)

Proses migrasi dapat dijalankan berulang kali secara aman (*safe & idempotent*):
1. **Idempotensi File Foto:** Sistem memeriksa apakah file `[part_number].[ext]` sudah ada di NAS atau folder mirror. Jika file sudah ada (> 100 bytes), unduhan **otomatis dilewati (*SKIP*)**.
2. **Idempotensi Komponen "Baru":** Jika nama barang dan spesifikasi dimensi sama persis dengan baris sebelumnya (contoh: *"sirip plangkon d7"* yang muncul 3 kali di spreadsheet), sistem **menggunakan kembali `component_id` yang sudah ada (*REUSE*)**, mencegah master part ganda.
3. **Idempotensi Tiket WR:** Sebelum insert, sistem mengecek kolom `wri_number`. Jika tiket sudah pernah diimpor, baris tersebut **otomatis dilewati (*SKIP*)**.

---

## 6. Kelanjutan 159 Tiket Belum Selesai (In-Progress)

Dari 820 baris data spreadsheet, 661 tiket sudah berstatus `Sudah diambil` (selesai penuh sebagai arsip). Sedangkan **159 tiket in-progress** langsung aktif di sistem baru:

| Status Asli di Spreadsheet | Qty Tiket | Mapping Status di HPK WMS Baru | Modul & Aksi Lanjutan di Sistem Baru |
| :--- | :---: | :--- | :--- |
| **`Belum Verifikasi`** | 90 | `status: 'submitted'`<br>`approval_pengaju: 'Menunggu'` | Tab **Antrean WRI**. Kepala Bagian Pengaju/Penerima dapat langsung klik **[Terima]** / **[Tolak]**. |
| **`Dalam antrian`** | 42 | `status: 'Dalam antrian'`<br>`approval: 'Diterima'` | Dashboard stasiun mesin tujuan (Laser, M1, UNP, dll.). Operator mesin dapat klik **[Mulai Proses Mesin]**. |
| **`Proses: Dept ... (In)`** | 7 | `status: 'Proses: Dept ... (In)'`<br>`current_process_status: 'Proses In'` | Antrean aktif mesin. Operator mesin dapat klik **[Proses Selesai]** atau **[Parsial]**. |
| **`Siap diambil`** | 18 | `status: 'Siap diambil'`<br>`current_process_status: 'Siap diambil'` | Modul **Material Handling (MH)** tab *Tugas Penjemputan (Pickup)*. Kurir MH dapat scan/ambil barang. |
| **`Menuju Pengaju`** | 2 | `status: 'Menuju Pengaju'`<br>`current_process_status: 'Menuju Pengaju'` | Modul **Material Handling** tab *Menuju Pengaju* dan POV Pengaju untuk konfirmasi **[Sudah Diambil]**. |

### Syarat Teknis yang Dipenuhi:
- Setiap tiket dibuatkan relasi ke tabel `work_request_steps` yang menunjuk ke ID mesin yang tepat.
- Kuantitas yang sudah diambil (`quantity_received`) dan siap diambil (`quantity_produced`) dihitung presisi dari teks log kolom `Data Proses`.

---

## 7. Perintah Eksekusi (Artisan Commands)

Dua perintah CLI Laravel yang disediakan:

### 1. Unduh Seluruh Foto ke NAS & Mirror
```bash
# Unduh seluruh foto unik Google Drive ke NAS dan uploads/components
php artisan wr:download-legacy-photos
```

### 2. Migrasi Data Log WR ke Database
```bash
# 1. Uji coba simulasi (Tanpa Menulis ke Database)
php artisan wr:migrate-legacy-spreadsheet --dry-run

# 2. Eksekusi riil pemindahan data 820 baris
php artisan wr:migrate-legacy-spreadsheet

# 3. Eksekusi khusus hanya 159 tiket in-progress (opsional)
php artisan wr:migrate-legacy-spreadsheet --only-in-progress
```

---

## 8. Panduan untuk AI Agent & Pengembang Selanjutnya

Jika di masa mendatang pengguna ingin melakukan migrasi tambahan dari file spreadsheet baru:
1. **Periksa Status Eksisting:** Jalankan `WorkRequest::where('wri_number', 'like', 'WR/%')->count()` untuk melihat jumlah tiket migrasi yang sudah ada.
2. **Pertahankan Zero Stock:** Jangan pernah menambahkan trigger mutasi stok pada data berstatus `is_legacy = true`.
3. **Patuhi Idempotensi:** Selalu gunakan pencocokan `wri_number` sebelum insert.
4. **Sinkronisasi Dokumentasi:** Selalu perbarui berkas ini jika terdapat perubahan skema atau penambahan fitur baru pada modul Work Request.

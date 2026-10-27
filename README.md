# HPK Gudang Inhouse (Warehouse Management & Work Request System)

Aplikasi terpadu manajemen pergudangan (*Warehouse Management System* - WMS) dan sistem permintaan kerja antar stasiun mesin (*Work Request Inhouse* - WRI) serta logistik internal (*Material Handling* - MH) milik **PT Hydraxle Perkasa**.

---

## 🚀 Gambaran Umum

Sistem ini dirancang untuk mendigitalisasi dan menyatukan seluruh siklus logistik manufaktur secara terpusat:
1. **Penyimpanan & Stok Gudang (WMS):** Pelacakan posisi fisik barang pada rak-rak gudang (Zona A s/d F), saldo *real-time*, transaksi keluar-masuk (*inbound*, *outbound*, *transfer*, *return*), dan *Cycle Counting* (Stok Opname).
2. **Work Request Inhouse (WRI):** Permintaan komponen/material antar 15 departemen stasiun mesin secara digital, verifikasi berjenjang (*Smart Approval*), perutean mesin otomatis (*Auto-Routing*), pengerjaan parsial (cicilan), dan pelacakan SLA 3 hari.
3. **Material Handling (MH) Command Center:** Pengaturan penjemputan (*pickup*), transit, dan distribusi fisik material antar stasiun mesin dengan bantuan pemindai kamera QR Code *real-time*.
4. **Engineering & Quality Assurance:** Pengelolaan perubahan komponen (*Engineering Change Request* - ECR) dan afkir material (*Disposal & Scrap*).
5. **Keamanan & Otoritas Hak Akses (RBAC):** Pembatasan menu dan aksi operasional berbasis peran (*Role-Based Access Control*) dan departemen menggunakan Spatie Permission.

---

## 🛠️ Tech Stack

- **Backend:** Laravel 10 (PHP 8.1+)
- **Frontend:** Laravel Blade, Alpine.js, Tailwind CSS
- **Database:** MySQL (Database: `hpk_gudang`)
- **Build Tool:** Vite
- **Pustaka Utama:**
  - `spatie/laravel-permission` (Manajemen Peran & Izin Pengguna)
  - `html5-qrcode` (Pemindaian Barcode & QR Code via Kamera Browser)
  - `simplesoftwareio/simple-qrcode` & `qrcode` (Pembangkit Kode QR)
- **Autentikasi:** Laravel Breeze dengan Bcrypt Password Hashing

---

## 🏢 15 Departemen Resmi

Sistem mengadopsi 15 departemen standar pabrik yang termigrasi dari basis data acuan perusahaan:
1. `Laser`
2. `M1` (Mesin Center 1)
3. `UNP`
4. `Sharing`
5. `Band Saw`
6. `Aksesoris`
7. `Press`
8. `Hydraulic`
9. `CNC`
10. `Sharing & Bending`
11. `Gudang`
12. `SPO`
13. `Robot`
14. `Uji 1`
15. `Uji 2`

---

## 📦 Modul Utama Aplikasi

1. **Dashboard (Beranda Dinamis):** Menampilkan modul-modul kartu yang hanya diizinkan untuk peran yang sedang login.
2. **Peta Gudang & Lokasi:** Visualisasi tata letak rak fisik (Zona A s/d F, Aisle, Rack, Bin, Level, dan kapasitas muat).
3. **Master Data Komponen:** Database material dengan tipe spesifikasi, min/max stok, payload QR code, dan rute pengerjaan mesin (`process_route`).
4. **Transaksi Komponen:** Pencatatan arus barang (*Inbound*, *Outbound*, *Transfer*, *Return*) terhubung ke nomor SPK dan saldo rak (`stock_balances`).
5. **Work Request Inhouse (WRI):**
   - Penomoran otomatis `WRI/YYYYMMDD/...`.
   - *Hybrid Smart Approval* (Operator membutuhkan verifikasi Ka. Bagian; SPV/Admin/Urgent Line Stop otomatis lolos).
   - Prasyarat Penerima (Departemen tujuan tidak dapat melihat/memproses tiket sebelum disetujui Pengaju).
   - Pengerjaan Parsial (*Cicilan*) dengan pelacakan kuota bertahap (`Parsial > ...`).
   - Pop-up Kuantitas Minimalis: Input kuantitas cepat dengan *hero stepper* touch-friendly (`[-]` / `[+]`), shortcut instan `[Semua / Max]`, dan *collapsible defect drawer* tanpa membuat layar bising (*non-cluttered*).
   - Auto-Update Saldo Fisik: Saat tiket berstatus `Sudah Diambil`, stok di rak tujuan otomatis bertambah dan transaksi `inbound` resmi tercatat.
   - Peringatan SLA 3 Hari & Filter Interaktif: Badge `[ ⚠️ X Tiket Melewati SLA > 72 Jam ]` interaktif untuk menyaring dan menyortir tiket terlambat dari yang tertunda paling lama.
   - Unified 4-Status Control Bar: Perampingan antrean tiket menjadi 4 kategori operasional (*Perlu Verifikasi, Sedang Proses, Siap Ambil, Selesai*) dengan eliminasi kontrol dobel dan sinkronisasi 5 kartu KPI interaktif.
   - Cetak Dokumen WR Resmi 3 Rangkap: Pemicu cetak mutlak (Approval Pengaju & Penerima Diterima + Operator pengerjaan ditugaskan) dengan format triplicate slip (Pemohon, Mesin, Gudang) dalam 1 lembar kertas A4, QR Code lokal vektor SVG, 4 kolom tanda tangan resmi, dan antrean cetak massal (*Bulk Print*) di `/work-requests/print-queue`.
6. **Material Handling (Command Center):** 4 Tab operasional (*Tasks Pickup*, *Tasks Transit*, *Tasks Distribution*, *Tasks History*) dilengkapi pemindai kamera QR Code langsung.
7. **Engineering Change Request (ECR):** Workflow pengajuan dan persetujuan perubahan spesifikasi komponen oleh QC dan Kepala Departemen.
8. **Disposal & Scrap:** Pengajuan afkir material dengan pelacakan estimasi berat (kg), nilai sisa (*salvage value*), dan bukti foto.
9. **Cetak Label QR:** Pembangkit dan pencetak label barcode/QR untuk barang, boks, maupun rak fisik (mendukung printer Thermal & Sheet).
10. **Cycle Counting (Stok Opname):** Rekonsiliasi fisik vs sistem dengan pencatatan deviasi kuantitas dan persetujuan penyesuaian.
11. **Manajemen Pengguna & Otoritas (RBAC):** Pengaturan akun, penetapan peran/departemen, hak akses menu granular, dan reset cache izin otomatis.

---

## 🔒 Aturan Pengembangan & Keamanan (Guardrails)

Dalam menjaga integritas kode dan basis data operasional, setiap developer dan AI Agent wajib mematuhi panduan pada [.gemini/rules/guardrails.md](.gemini/rules/guardrails.md):
- **NO BLIND OVERWRITE:** Jangan pernah menimpa file Blade, Controller, atau JS secara utuh. Gunakan modifikasi baris presisi agar fitur eksisting tidak hilang.
- **ZERO DATABASE REGRESSION:** Dilarang keras menjalankan `migrate:fresh` atau `db:wipe` pada database operasional `hpk_gudang`.
- **ISOLATED AUTOMATED TESTING:** Seluruh pengujian otomatis dijalankan pada database memori SQLite (`:memory:`) terisolasi:
  ```powershell
  php artisan test --filter=WorkRequestAuthorizationTest
  ```
- **SINKRONISASI DOKUMENTASI WAJIB:** Setiap kali ada pembaruan fitur atau logika, file dokumentasi Markdown (`.md`) wajib diperbarui secara simultan.

---

## 📚 Tautan Dokumentasi Terkait

- [about.md](about.md) - Analisis Arsitektur dan Rincian Seluruh Modul WMS & WRI
- [docs/SOP_DAN_ALUR_PROSES.md](docs/SOP_DAN_ALUR_PROSES.md) - SOP Operasional, Diagram Alur Proses, dan Aturan Bisnis (BR-01 s/d BR-10)
- [SESSION_RESUME_AKSES_AKUN.md](SESSION_RESUME_AKSES_AKUN.md) - Log Checkpoint Proyek, Pemetaan Hak Akses & Riwayat Implementasi
- [GEMINI.md](GEMINI.md) - Panduan Kerja AI Agent
- [System WR lama/README.md](System%20WR%20lama/README.md) - Arsip Historis Sistem Google Apps Script & Sheets (Legacy)

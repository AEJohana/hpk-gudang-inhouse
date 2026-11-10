# HPK Gudang Inhouse (Warehouse Management & Work Request System)

Aplikasi terpadu manajemen pergudangan (*Warehouse Management System* - WMS) dan sistem permintaan kerja antar stasiun mesin (*Work Request Inhouse* - WRI) serta logistik internal (*Material Handling* - MH) milik **PT Hydraxle Perkasa**.

---

## 🚀 Gambaran Umum

Sistem ini dirancang untuk mendigitalisasi dan menyatukan seluruh siklus logistik manufaktur secara terpusat:
1. **Penyimpanan & Stok Gudang (WMS):** Pelacakan posisi fisik barang pada rak-rak gudang (Zona A s/d F), saldo *real-time*, transaksi keluar-masuk (*inbound*, *outbound*, *transfer*, *return*), dan *Cycle Counting* (Stok Opname).
2. **Work Request Inhouse (WRI):** Permintaan komponen/material antar 15 departemen stasiun mesin secara digital, verifikasi berjenjang (*Smart Approval*), perutean mesin otomatis (*Auto-Routing*), pengerjaan parsial (cicilan), dan peringatan keterlambatan pengerjaan (> 3 hari).
3. **Material Handling (MH) Command Center:** Pengaturan penjemputan (*pickup*), transit, dan distribusi fisik material antar stasiun mesin dengan bantuan pemindai kamera QR Code *real-time*.
4. **Engineering & Quality Assurance:** Pengelolaan perubahan komponen (*Engineering Change Request* - ECR) dan afkir material (*Disposal & Scrap*).
5. **Keamanan & Otoritas Hak Akses (RBAC):** Pembatasan menu dan aksi operasional berbasis peran (*Role-Based Access Control*) dan departemen menggunakan Spatie Permission.

---

## 🛠️ Tech Stack

- **Backend:** Laravel 10 (PHP 8.1+)
- **Frontend:** Laravel Blade, Alpine.js, Tailwind CSS (100% Offline Pure On-Premise Asset, Bebas CDN Luar)
- **Database:** MySQL (Database: `hpk_gudang`)
- **Build Tool:** Vite (Kompilasi Lokal `public/build/`)
- **Pustaka Utama:**
  - `spatie/laravel-permission` (Manajemen Peran & Izin Pengguna)
  - `html5-qrcode` (Pemindaian Barcode & QR Code via Kamera Browser Lokal di `public/js/`)
  - `chillerlan/php-qrcode` (Pembangkit Kode QR Backend SVG / Base64 Offline)
  - `FontAwesome Free` & `Poppins Font` (Lokal Offline di `public/vendor/` & `public/fonts/`)
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
3. **Master Data Komponen:** Database material dengan tipe spesifikasi, min/max stok, payload QR code, rute pengerjaan mesin (`process_route`), dan penyimpanan foto/gambar teknik luring (*pure local offline on-premise* terpusat di server jaringan `Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House` dan Direct UNC `\\192.168.1.150\...` pada mode pengembangan Windows, dengan redundansi cadangan lokal di `D:\Ali Magang\KOMPONEN` saat offline, atau mount NAS Linux `/mnt/nas-gudang/Gudang Komponen In House` pada mode produksi via `ComponentPhotoStorageService`) dengan konfigurasi `COMPONENT_EXTERNAL_PHOTO_DIR` dan toggle lingkungan `COMPONENT_ENABLE_GDRIVE_FALLBACK=false`. Sistem menerapkan otomatisasi komprehensif: simpan langsung & auto-rename kode part fisik untuk upload form/kamera, auto-download & simpan lokal untuk impor Excel bertautan GDrive/web, auto-detection cerdas tanpa tautan untuk berkas fisik yang sudah ada di folder master NAS (cukup simpan berkas dengan nama `[kode_item].[ext]`), resiliensi terhadap *case-sensitivity* sistem operasi Linux (ekstensi huruf kapital `.JPG`, `.PNG`, `.PDF` dan nama part berspasi/raw), serta normalisasi path Linux otomatis. Dilengkapi dukungan dokumen cetak biru CAD (`.pdf`) hingga 15MB, interactive zoom lightbox & PDF modal viewer di stasiun kerja (*work center*), artisan command audit & sinkronisasi `components:check-nas`, ekspor gdrive `components:export-to-local`, perintah pembersihan dummy aman `components:clean-dummies`, serta streaming gateway HTTP terisolasi (`/components/{component}/photo-stream`) berbasis `StreamedResponse` berkecepatan tinggi. Berkas sumber acuan resmi tersinkronisasi di `Data Acuan Resmi\Form Work Request - Data komponen.csv`.
4. **Transaksi Komponen:** Pencatatan arus barang (*Inbound*, *Outbound*, *Transfer*) dengan alur yang disederhanakan: 1 tombol tunggal `[ + Transaksi ]` di header `/transactions`, dropdown 3 jenis transaksi yang terarah, eliminasi kolom SPK/PO yang tidak relevan, pengisian tanggal real-time terkunci (*readonly*), pengisian otomatis rak asal (*Ambil dari Rak*) sesuai alamat default komponen atau fallback *-- Tidak Ada Rak Terdaftar --*, tata letak metadata anti-collision (`break-all` pada dokumen acuan panjang), latar belakang polos (bebas grid), 2 kolom tanda tangan ringkas (*Dikeluarkan Oleh (Gudang)* & *Diterima Oleh: ( .................................... )* tanpa kolom QC), serta fitur **Cetak Massal Hemat Kertas (Bulk Print @2 Slip per 1 Lembar A4)** via multi-select checkbox di `/transactions`.
5. **Work Request Inhouse (WRI):**
   - Penomoran otomatis `WRI/YYYYMMDD/...`.
   - *Hybrid Smart Approval* (Operator membutuhkan verifikasi Ka. Bagian; SPV/Admin/Urgent Line Stop otomatis lolos).
   - Prasyarat Penerima (Departemen tujuan tidak dapat melihat/memproses tiket sebelum disetujui Pengaju).
   - Pengerjaan Parsial (*Cicilan*) dengan pelacakan kuota bertahap (`Parsial > ...`).
   - Pop-up Kuantitas Minimalis: Input kuantitas cepat dengan *hero stepper* touch-friendly (`[-]` / `[+]`), shortcut instan `[Semua / Max]`, dan *collapsible defect drawer* tanpa membuat layar bising (*non-cluttered*).
   - Auto-Update Saldo Fisik: Saat tiket berstatus `Sudah Diambil`, stok di rak tujuan otomatis bertambah dan transaksi `inbound` resmi tercatat.
   - Peringatan Keterlambatan (> 3 Hari) & Quick Filter Interaktif: Banner peringatan merah prominen (*"Peringatan! Terdapat X Work Request yang tertunda lebih dari 3 hari"*) sesuai desain operasional pabrik, lengkap dengan tombol `[ 🔻 Lihat yang Terlambat ]` dan reset toggle `[ ✕ Tampilkan Semua ]`, badge lencana merah pada tabel & kartu, peringatan pada modal detail, serta eliminasi seluruh istilah asing "SLA".
   - Unified 4-Status Control Bar & Streamlined Search: Perampingan antrean tiket menjadi 4 kategori operasional (*Perlu Verifikasi, Sedang Proses, Siap Ambil, Selesai*) dengan eliminasi kontrol dobel, penghapusan tombol toggle semu scope dept, sinkronisasi 5 kartu KPI interaktif, dan perluasan kotak pencarian cepat.
   - Cetak Dokumen WR Resmi 3 Rangkap: Pemicu cetak mutlak (Approval Pengaju & Penerima Diterima + Operator pengerjaan ditugaskan) dengan format triplicate slip (Pemohon, Mesin, Gudang) dalam 1 lembar kertas A4, QR Code lokal vektor SVG, 4 kolom tanda tangan resmi, dan antrean cetak massal (*Bulk Print*) di `/work-requests/print-queue`.
6. **Material Handling (Command Center):** 4 Tab operasional (*Tasks Pickup*, *Tasks Transit*, *Tasks Distribution*, *Tasks History*) dilengkapi pemindai kamera QR Code langsung.
7. **Engineering Change Request (ECR):** Workflow pengajuan dan persetujuan perubahan spesifikasi komponen oleh QC dan Kepala Departemen.
8. **Disposal & Scrap:** Pengajuan afkir material dengan pelacakan estimasi berat (kg), nilai sisa (*salvage value*), dan bukti foto.
9. **Cetak Label QR:** Pembangkit dan pencetak label barcode/QR untuk barang, boks, maupun rak fisik (mendukung printer Thermal 70x45mm & Sheet A4). Memuat payload komposit cerdas 3-in-1 (`Nama Komponen ; Kode Komponen ; Kode Rak`) yang dapat dibaca offline oleh kamera apapun, antrean kerja otomatis fokus ke tab **Menunggu Dicetak** (*default* `status=pending`), 3 kartu indikator KPI interaktif (*Menunggu Dicetak*, *Selesai Dicetak*, *Total Permintaan*), lencana kuantitas riil (*count badges*), pencarian cepat terpadu, **Panel Geser Pratinjau Foto & Identitas Komponen Interaktif**, **Sistem Keranjang Pengajuan Massal (Cart Workflow)** dengan 2 pilihan format label mandiri (*Label QR Rak* @10 label/set & *Label QR Kartu* fleksibel), serta **Redesign Cetak Presisi (Rak 3-Blok Grid 20/HVS & Kartu 2-Kolom)** dengan fitur **Multi-Select Checkbox & Cetak Massal Bersamaan (Bulk Print)** yang otomatis memisahkan halaman cetak (*smart page-break*) secara rapi.
10. **Cycle Counting (Stok Opname):** Rekonsiliasi fisik vs sistem dengan pencatatan deviasi kuantitas dan persetujuan penyesuaian.
11. **Manajemen Pengguna & Otoritas (RBAC):** Pengaturan akun, penetapan peran/departemen, matriks terkurasi 31 hak akses granular murni (bebas izin semu/ghost), dan reset cache izin otomatis.
12. **Log Aktivitas & Audit Trail Sistem (`/activities` & `/admin/audit-logs`):** Pusat pemantauan jejak operasional pengguna terpadu dengan perekam otomatis `ActivityLogger` (mencatat login/logout, penerbitan & approval WRI, pengerjaan mesin, defect/reject, material handling, transaksi stok, upload master komponen/CAD, dan cetak QR), dilengkapi 4 kartu indikator KPI interaktif, filter multi-kriteria (rentang waktu, modul, user, event), pencarian teks bebas, dan modal viewer payload JSON diff.
13. **Hierarki Navigasi Panel Admin & Pusat Kendali Administrator (`/admin/dashboard`):** Tata kelola navigasi intuitif dua tingkat; seluruh tombol `Kembali` pada submenu admin (`users`, `roles`, `categories`, `uoms`, `locations`, `settings`, `audit-logs`) konsisten mengarahkan pengguna kembali ke Pusat Kendali Administrator (`route('admin.dashboard')`), serta tombol pintas `[ < Kembali ke WMS ]` pada dashboard admin untuk kembali ke beranda logistik utama (`route('dashboard')`).

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
- [docs/PANDUAN_STRUKTUR_FOLDER_DAN_FRAMEWORK.md](docs/PANDUAN_STRUKTUR_FOLDER_DAN_FRAMEWORK.md) - Buku Panduan Resmi Struktur Folder, Arsitektur Framework Laravel, Node.js & Esensi Berkas Sistem
- [docs/SOP_DAN_ALUR_PROSES.md](docs/SOP_DAN_ALUR_PROSES.md) - SOP Operasional, Diagram Alur Proses, dan Aturan Bisnis (BR-01 s/d BR-14)
- [SESSION_RESUME_AKSES_AKUN.md](SESSION_RESUME_AKSES_AKUN.md) - Log Checkpoint Proyek, Pemetaan Hak Akses & Riwayat Implementasi
- [GEMINI.md](GEMINI.md) - Panduan Kerja AI Agent
- [System WR lama/README.md](System%20WR%20lama/README.md) - Arsip Historis Sistem Google Apps Script & Sheets (Legacy)

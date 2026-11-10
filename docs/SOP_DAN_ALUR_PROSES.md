# Standar Operasional Prosedur (SOP) & Alur Proses Terpadu
**Sistem:** HPK Gudang Inhouse (WMS, Work Request Inhouse & Material Handling)  
**Perusahaan:** PT Hydraxle Perkasa  
**Versi Sistem:** Laravel 10 (MySQL & Spatie RBAC)

---

## 1. Pendahuluan & Ruang Lingkup

Dokumen ini adalah acuan operasional resmi untuk seluruh proses pergerakan barang, permintaan komponen antar departemen (*Work Request Inhouse* - WRI), proses manufaktur pada stasiun mesin (*Machine Centers*), dan perpindahan logistik internal (*Material Handling* - MH) di PT Hydraxle Perkasa.

---

## 2. Diagram Alur Kerja End-to-End (Mermaid Workflow)

```mermaid
flowchart TD
    subgraph FASE_1["1. INISIASI & APPROVAL PENGAJU"]
        A([Pemohon / Operator Asal]) -->|Cari Komponen / Scan QR| B[Isi Form Buat WRI (Minimalis & Ringkas)]
        B --> C{Pangkat / Tipe Tiket?}
        C -->|Operator Biasa| D[Status Pengaju: Menunggu]
        C -->|Supervisor / Admin / Urgent Line Stop| E[Status Pengaju: Diterima]
        D -->|Ka. Bagian Pengaju Review (Dropdown Aksi)| F{Keputusan Ka. Bagian}
        F -->|Tolak (Alasan Wajib)| G([Tiket Dibatalkan / Selesai])
        F -->|Terima| E
    end

    subgraph FASE_2["2. PENERIMAAN DEPARTEMEN TUJUAN (BR-06)"]
        E -->|Tiket Muncul di Penerima| H[Antrian Departemen / Mesin Center]
        H --> I{Kepala / OP Penerima Verifikasi}
        I -->|Tolak| G
        I -->|Terima| J[Status Operasional: Dalam Antrian]
    end

    subgraph FASE_3["3. PROSES OPERASIONAL MESIN CENTER"]
        J -->|Mulai Pengerjaan Mesin| K[Status Operasional: Proses In]
        K -->|Selesai Pengerjaan Mesin| L{Pengerjaan Parsial?}
        L -->|Ya: Sebagian Selesai| M[Status: Parsial > Proses Out]
        L -->|Tidak: Selesai Penuh| N[Status: Proses Out]
    end

    subgraph FASE_4["4. LOGISTIK MATERIAL HANDLING (MH COMMAND CENTER)"]
        M & N -->|Masuk Tab tasksPickup| O[Kurir MH Scan QR Tiket / Barang]
        O -->|Verifikasi Penjemputan Fisik| P[Status: MH Pickup / tasksTransit]
        P --> Q{Ada Rute Mesin Lanjutan?}
        Q -->|Ya: Multi-Routing| R[Antar ke Mesin Berikutnya] --> J
        Q -->|Tidak: Menuju Pengaju| S[Status: Siap Diambil / Menuju Pengaju]
    end

    subgraph FASE_5["5. SERAH TERIMA & AUTO-UPDATE STOK (BR-08)"]
        S -->|Serah Terima Fisik ke Pemohon| T[Status Akhir: Sudah Diambil]
        T --> U[(Otomatis Tambah Saldo Fisik Rak: stock_balances)]
        T --> V[(Otomatis Terbitkan Transaksi Inbound Resmi)]
        V --> W([Tiket Selesai / Closed])
    end
```

---

## 3. Matriks Aturan Bisnis Resmi (BR-01 s/d BR-14)

Seluruh logika bisnis dari sistem lama telah diimplementasikan secara terstruktur ke dalam framework Laravel:

| ID Aturan | Nama Aturan Bisnis | Implementasi Nyata di Kode Sumber Laravel | Keterangan & Dampak Operasional |
|:---|:---|:---|:---|
| **BR-01** | **Otorisasi & Password Security** | `Hash::make()` (Bcrypt) & Spatie Permission pada `app/Models/User.php`. | Kata sandi diamankan secara kriptografis standar industri (menghapus risiko kebocoran password teks biasa di spreadsheet lama). |
| **BR-02** | **Penomoran WRI Otomatis** | `WorkRequest::generateWriNumber()` di `app/Models/WorkRequest.php`. | Format: `WRI/YYYYMMDD/[Urutan]`. Urutan otomatis dihitung berdasarkan kuota harian. |
| **BR-03** | **Pembangkitan Kode QR Lokal** | Pustaka `SimpleSoftwareIO\QrCode` & `qrcode/html5-qrcode`. | QR Code digenerate secara lokal tanpa dependensi API eksternal pihak ketiga (`api.qrserver.com`), sehingga dapat berjalan aman di jaringan intranet pabrik. |
| **BR-04** | **Penyimpanan Komponen Baru** | Laravel Storage disk `public` (`storage/app/public/...`). | Foto komponen baru dan bukti lampiran tersimpan aman di server lokal perusahaan dengan tautan simbolik publik. |
| **BR-05** | **Auto-Routing Stasiun Mesin** | Kolom `components.process_route`, tabel `work_request_steps`, dan `machines`. | Sistem membaca rute urutan mesin (misal: *Laser -> Bending -> Gudang*) dan secara cerdas mengarahkan antrian ke stasiun yang aktif. |
| **BR-06** | **Prasyarat Tampil di Penerima** | `scopeVisibleToDepartment` & filter `approval_pengaju === 'Diterima'` pada `WorkRequest.php`. | Departemen penerima / mesin tujuan **TIDAK BISA** melihat atau memproses tiket sebelum atasan peminta menyetujui pengajuan. |
| **BR-07** | **Pengerjaan Parsial, Defect Reaktif & Multi-Batch** | Handler `processAction` di `WorkRequestController.php`, `WorkRequest.php`, dan modal Blade. | Jika pemohon memesan 20 pcs dan mesin menyelesaikan 15 pcs unit bagus serta melaporkan 5 pcs defect mesin, sistem secara reaktif membatasi batas unit bagus maksimal 15 pcs (`20 - 5`). Karena 15 bagus + 5 defect = 20 (seluruh bahan tuntas), tahapan mesin **SEKETIKA BERHENTI TUNTAS** (`saldo_out = 0`) dan beralih ke MH (15 pcs). Tiket **TETAP TERBUKA AKTIF** (`is_final = false`) sampai seluruh kuantitas terpenuhi (100%), dan tombol pengerjaan sisa hanya muncul jika memang masih ada saldo fisik yang belum diproses. |
| **BR-08** | **Auto-Update Saldo Fisik Gudang** | Logika event `Sudah Diambil` di `WorkRequestController.php` (baris 709-760). | Begitu tiket berstatus `Sudah Diambil`, saldo kuantitas pada rak tujuan di `stock_balances` **OTOMATIS BERTAMBAH** dan transaksi masuk resmi terbit di tabel `transactions`. |
| **BR-09** | **Sanitasi String Status** | Scope pembersihan status dan normalisasi nama status di Model `WorkRequest.php`. | String `Parsial >` dipisahkan secara otomatis saat evaluasi fase logika status dilakukan oleh sistem backend. |
| **BR-10** | **Peringatan Keterlambatan (> 3 Hari) & Filter Interaktif** | `scopeOverdueSla`, `isOverdue()` di `WorkRequest.php` + query `overdue_sla=1` & `oldest('created_at')` di `WorkRequestController.php`. | Tiket yang belum berstatus `Sudah Diambil` setelah melewati batas toleransi 72 jam (3 hari) otomatis memicu **Banner Peringatan Merah Prominen** di atas antrean tiket (*"Peringatan! Terdapat X Work Request yang tertunda lebih dari 3 hari"*). Banner ini dilengkapi tombol aksi interaktif `[ 🔻 Lihat yang Terlambat ]` dan `[ ✕ Tampilkan Semua ]`. Setiap tiket terlambat juga ditandai lencana peringatan merah `⚠️ Terlambat > 3 Hari` pada tabel dan kartu mobile, serta kotak info peringatan pada pop-up detail tiket. Seluruh kosakata asing "SLA" dihilangkan dari antarmuka lapangan demi kemudahan operator. |
| **BR-11** | **Unified 4-Status Control Bar & Bebas Redundansi** | Mapping status di `WorkRequestController.php` & tab navigasi `index.blade.php`. | Mengeliminasi baris ganda bertumpuk dan kontrol dobel. Antrean WRI disederhanakan menjadi 4 kategori operasional fungsional (+ Semua): **Perlu Verifikasi**, **Sedang Proses**, **Siap Ambil**, dan **Selesai**, dengan 5 kartu KPI interaktif di header. |
| **BR-12** | **Otorisasi Cetak Lembar WRI Resmi 3 Rangkap & Antrean Print** | `WorkRequest::isPrintable()`, `WorkRequestController::printSheet()`, `printQueue()`, dan `print_sheet.blade.php`. | Tiket hanya boleh dicetak jika: Approval Pengaju `Diterima`, Approval Penerima `Diterima`, dan Operator Pengerjaan telah dipilih (`assigned_operator_name`). Dicetak dalam format triplicate 3 rangkap (Pemohon, Mesin, Gudang) dalam 1 lembar HVS A4 dengan QR lokal SVG dan 4 kolom otorisasi tanda tangan resmi. Didukung fitur cetak ulang dengan log tracking dan cetak massal (*Bulk Print*). |
| **BR-13** | **Format QR Komposit 3-in-1 (`Nama;Kode;Rak`) & Parser Multi-Token** | `Component::getQrCompositePayloadAttribute()`, `ComponentController::searchApi()`, `WorkStationSupplyController::scanLookup()`, dan view cetak label QR. | Seluruh kode QR komponen memuat format komposit `Nama Komponen ; Kode Komponen ; Kode Rak` yang dapat dibaca offline oleh kamera apapun. Parser scanner otomatis mengekstrak part number dengan delimiter titik koma (`;`) tanpa merusak barcode lama (`backward-compatible`). |
| **BR-14** | **Penyimpanan Gambar Teknik & CAD PDF Lokal, Server NAS Windows & Produksi Linux (Pure Local Mode)** | `COMPONENT_EXTERNAL_PHOTO_DIR` & `COMPONENT_ENABLE_GDRIVE_FALLBACK` (.env / .env.production), `ComponentPhotoStorageService`, `Component::getLocalPhotoFilePathAttribute()`, `Component::getImageUrlAttribute()`, `ComponentController::photoStream()`, `CheckNasPhotosCommand`, `CleanDummyComponentsCommand`, dan `image_preview_modal.blade.php`. | Seluruh foto komponen dan gambar teknik CAD tersimpan terpusat di server jaringan bersama on-premise: pada server pengembangan Windows (`Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House\[part_number].[ext]` dengan auto-bridge UNC `\\192.168.1.150\...` dan redundansi cadangan lokal di `D:\Ali Magang\KOMPONEN`) atau pada server produksi Linux melalui mount NAS (`/mnt/nas-gudang/Gudang Komponen In House/[part_number].[ext]`) dengan Pure Local Mode (`COMPONENT_ENABLE_GDRIVE_FALLBACK=false`) yang mematikan ketergantungan koneksi Google Drive. Seluruh penambahan/pembaruan via Form Input, Edit, Quick Store WR, dan Impor Excel otomatis disimpan ke master folder NAS dengan nama bersih `[part_number].[ext]`. **Fitur Auto-Detection Cerdas:** Untuk komponen yang belum ada gambarnya, pengguna cukup menyalin file ke folder master NAS dan menamainya sesuai kode item (`[part_number].[ext]`), sistem langsung mengenalinya tanpa perlu update database. Dilengkapi normalisasi leading slash otomatis (`mnt/...` -> `/mnt/...`), resiliensi *case-sensitivity* Linux untuk ekstensi huruf kapital (`.JPG`, `.PNG`, `.PDF`), serta pencarian nama part fleksibel (raw spasi, tersanitasi, maupun tanpa simbol). Mendukung dokumen CAD format `.pdf` serta gambar hingga 15MB, dilengkapi **Interactive Drawing Zoom Modal** dan **Native PDF Modal Viewer Fullscreen** (zoom 500%, multi-halaman, cetak langsung) di stasiun mesin (*work center*). Dilengkapi streaming berkecepatan tinggi `StreamedResponse`, perintah audit konektivitas `php artisan components:check-nas`, perintah pembersihan dummy aman `php artisan components:clean-dummies`, dan arsip data sumber resmi di `Data Acuan Resmi\Form Work Request - Data komponen.csv`. |
| **BR-15** | **Manajemen Antrean Cetak Label QR (Default Menunggu Dicetak, Indikator KPI, Keranjang Pengajuan, Redesign Presisi Rak & Kartu, serta Bulk Print)** | `QrRequestController`, `resources/views/qr_requests/`, dan `QrRequestQueueTest.php`. | Akses awal menu `/qr-requests` tanpa parameter langsung menyaring antrean berstatus `pending` (*Menunggu Dicetak*) agar operator fokus pada label yang perlu diproses. Dilengkapi 3 kartu indikator KPI interaktif (*Menunggu Dicetak*, *Selesai Dicetak*, *Total Permintaan*), lencana kuantitas riil (*count badges*) pada setiap tab, serta pencarian terpadu. Pada formulir `/qr-requests/create`, disematkan **Panel Geser Pratinjau Foto & Identitas Komponen Interaktif**, 2 opsi format label mandiri (*Label QR Rak* @10 label/set & *Label QR Kartu* fleksibel per part) yang dapat dipilih keduanya sekaligus, serta **Sistem Keranjang Pengajuan Massal (Cart Workflow)**. Di halaman antrean disematkan **Multi-Select Checkbox** dan **Floating Action Bar** untuk mencetak massal bersamaan (`/qr-requests/bulk-print`) dengan dokumen cetak A4 yang menerapkan *smart page-break*: lembar Label QR Rak tercetak dalam grid 2x10 (20 label/HVS) dengan 3 blok horizontal (kotak teks identitas, QR code SVG, kotak kosong stempel/paraf), sedangkan lembar Label QR Kartu tercetak dalam grid 2 kolom dengan 2 blok horizontal tanpa kotak kosong. |
| **BR-16** | **Pusat Log Aktivitas Pengguna & Audit Trail Terpadu** | `ActivityLogger`, `AuditLogController`, `LogSuccessfulLogin`, `LogSuccessfulLogout`, dan `tests/Unit/ActivityLoggerTest.php`. | Seluruh peristiwa operasional penting (Login/Logout, Pembuatan & Approval WRI, Penugasan Operator, Pengerjaan Mesin, Pelaporan Defect/Reject, Material Handling, Mutasi Stok Gudang, Master Komponen/CAD, dan Cetak Label QR) wajib direkam secara otomatis dengan deskripsi naratif berbahasa Indonesia yang jelas, nama pelaku, departemen, dan IP address. Dapat diakses via `/activities` dan `/admin/audit-logs` dengan 4 kartu KPI interaktif, filter multi-kriteria, dan modal detail JSON diff. |

---

## 4. Siklus Hidup Status Tiket (*Ticket Status Lifecycle*)

### A. Status Persetujuan (Approval)
1. **Menunggu:** Status awal saat operator mengajukan tiket. Verifikator disajikan dropdown aksi (*Pilih Status... &rarr; Diterima / Ditolak*).
2. **Diterima:** 
   - **Pada Sts Pengaju:** Pengajuan disahkan dan otomatis diteruskan ke antrian departemen penerima.
   - **Pada Sts Penerima:** Memilih opsi *Diterima* langsung membuka modal penugasan operator mesin pelaksana (`assign_operator_modal`) secara instan.
3. **Ditolak:** Verifikator diarahkan ke modal konfirmasi penolakan tiket dengan isian alasan wajib. Pengajuan dibatalkan secara permanen (`is_final = true`) dengan catatan alasan tersimpan di log audit.
4. **Visualisasi Tracking Modal:** Log otorisasi verifikator diletakkan transparan pada kartu *Dual Verifikator* di bagian atas modal detail. Timeline tahapan fisik stasiun mesin dimulai murni dari status **MASUK (In)** dengan urutan kronologis alami (atas ke bawah).
5. **Pemisahan Logistik MH vs Gudang:** Log transit kurir Material Handling (*Menuju Pengaju*) ditandai dengan balok warna Biru dan ikon kurir (`fa-truck-ramp-box`), sedangkan penerimaan fisik di rak gudang (*Sudah Diambil*) ditandai dengan balok warna Hijau dan ikon gudang (`fa-warehouse`).
6. **QR Code Interaktif:** Gambar QR Code tiket pada modal detail dapat diklik untuk membuka modal zoom resolusi tinggi guna mempermudah pemindaian via smartphone atau barcode gun. Strip kuota pesanan menggunakan teks hitam bersih dengan hanya kuota cacat (*Defect*) yang berwarna merah mencolok.

### B. Status Operasional Mesin Center
1. **Dalam antrian:** Tiket telah tiba di stasiun mesin tujuan dan siap dieksekusi.
2. **Proses (In):** Operator mesin telah mengambil tiket dan material sedang diproses di mesin.
3. **Proses (Out):** Pengerjaan mesin telah selesai. Barang diletakkan di *staging out* stasiun kerja menunggu kurir Material Handling.
4. **Parsial > Proses (Out):** Sebagian kuantitas selesai diproses di mesin dan siap dijemput sebagian oleh kurir MH.

### C. Status Logistik Material Handling & Serah Terima
1. **MH Pickup:** Kurir logistik Material Handling telah memindai QR code tiket dan mengambil fisik barang dari stasiun mesin.
2. **Siap Diambil:** Barang telah tiba di area serah terima / gudang tujuan.
3. **Menuju Pengaju:** Kurir sedang dalam perjalanan mengantarkan barang ke departemen pemohon.
4. **Sudah Diambil (Parsial):** Serah terima batch cicilan divalidasi oleh gudang. Kuantitas fisik batch masuk stok, sementara tiket tetap terbuka aktif (`is_final = false`) dengan rekomendasi pengerjaan kembali ke mesin sisa.
5. **Sudah Diambil (Final):** Serah terima fisik telah tuntas 100% divalidasi oleh pemohon/gudang. Memicu eksekusi penambahan stok otomatis (BR-08) dan tiket resmi ditutup (*Closed*, `is_final = true`).

### D. Pengelompokan 4 Tab Antrean Operasional WRI (Unified Control Bar)
Untuk mempercepat navigasi operator dan supervisor di lapangan tanpa redundansi tombol kontrol ganda, antrean WRI disederhanakan menjadi 4 kategori status operasional utama (+ Semua):
1. **Semua:** Menampilkan seluruh tiket pada lingkup departemen user tanpa filter status.
2. **Perlu Verifikasi:** Tiket yang statusnya masih membutuhkan persetujuan (`approval_pengaju = 'Menunggu'` atau `approval_penerima = 'Menunggu'`).
3. **Sedang Proses:** Tiket yang telah masuk siklus produksi, mencakup antrian mesin (`Dalam antrian`, `submitted`), pengerjaan mesin (`Proses: ... (In/Out)`), dan penjemputan logistik kurir antar stasiun mesin (`MH Pickup: ...`).
4. **Siap Ambil:** Tiket yang tahapan mesin terakhirnya telah selesai dan barang siap diambil kurir MH (`Siap Diambil`) atau sedang dalam perjalanan diantar kurir menuju pengaju/gudang (`Menuju Pengaju`).
5. **Selesai:** Tiket yang seluruh kuotanya telah tuntas diverifikasi dan diterima fisik di rak gudang/pengaju (`Sudah Diambil`, `is_final = true`).

---

## 5. Matriks Hak Akses & Pembagian Peran (RBAC)

Berdasarkan Spatie Permission dan konfigurasi antarmuka dinamis:

| Peran Pengguna | Akses Menu Beranda | Hak Otoritas Operasional | Presets Default Akun |
|:---|:---|:---|:---|
| **Admin Gudang** | Seluruh Modul (Peta Gudang, Master Komponen, Transaksi, ECR, Disposal, Cycle Count, WRI, MH, User Management) | Akses global penuh untuk memantau, mencetak massal, dan mengelola seluruh tiket antar departemen. | Hak Akses Lengkap |
| **Operator Mesin Center** (Laser, M1, UNP, CNC, dll) | **Khusus Modul Work Request** (Modul lain disembunyikan otomatis) | Mengajukan WR, memproses status mesin (`Dalam antrian`, `Proses (In)`, `Proses (Out)`), cetak form WR stasiunnya. | `work-request.view`, `work-request.process`, `work-request.print` |
| **Petugas Material Handling (MH)** | **Khusus Modul Material Handling** (Modul lain disembunyikan otomatis) | Membuka MH Command Center, scan QR kamera, update status penjemputan (`MH Pickup`), transit, dan serah terima (`Sudah Diambil`). | `material-handling.view`, `material-handling.manage`, `material-handling.process` |
| **Kepala Departemen / Supervisor** | Work Request, Peringatan Keterlambatan, Laporan, ECR Approval, Disposal Approval | Otorisasi persetujuan pengajuan bawahan (*Approval Pengaju/Penerima*), pengawasan keterlambatan pengerjaan > 3 hari. | `work-request.approve`, `work-request.view`, dll |
| **Engineering & QC** | ECR, Master Komponen, Disposal & Scrap | Pengajuan dan peninjauan perubahan spesifikasi material, verifikasi barang afkir / reject. | `ecr.manage`, `disposal.manage`, dll |

---

## 6. Prosedur Verifikasi Kuantitas Fisik & Penanganan Defect di 3 Titik Serah Terima

Untuk memastikan transparansi dan integritas fisik komponen yang berpindah antar divisi, sistem menerapkan **Verifikasi Kuantitas Tiga Titik (*Three-Point Handover Verification*)** dan pemotongan kuota defect matematis:

```
+--------------------------------------------------------------------------------------------------------------------------+
|                                        TITIK 1: OUTPUT STASIUN MESIN                                                     |
|  - Operator mesin menginput unit bagus yang selesai dikerjakan (`process_qty`).                                          |
|  - Jika ada komponen cacat pengerjaan, operator mencatat `defect_qty` & alasannya.                                       |
|  - Dampak Kuota: Kuota order tiket dipotong sebesar defect (`quantity_requested = initial - defect`).                    |
|  - Saldo Mesin: `saldoOut = max(0, totalIn - totalOut - stepDefects)`. Tidak ada sisa kuota hantu.                       |
+--------------------------------------------------------------------------------------------------------------------------+
                                                             |
                                                             v
+--------------------------------------------------------------------------------------------------------------------------+
|                                   TITIK 2: PENJEMPUTAN MATERIAL HANDLING (MH PICKUP)                                     |
|  - Kurir MH wajib menghitung fisik barang yang ada di staging mesin dan menginput kuantitas yang diambil.               |
|  - Jika ditemukan selisih (misal mesin lapor 20 tapi MH hanya mendapati 19), 1 unit dicatat sebagai Defect/Hilang.       |
|  - Dampak Kuota: Kuota order langsung dikurangi 1 pcs, tercatat di audit log dengan identitas kurir MH, tanggal, & jam.  |
|  - Batch Transit: Kuantitas yang dibawa oleh MH (`current_batch_qty`) terkunci tepat sebesar 19 pcs.                     |
+--------------------------------------------------------------------------------------------------------------------------+
                                                             |
                                                             v
+--------------------------------------------------------------------------------------------------------------------------+
|                                    TITIK 3: PENERIMAAN FISIK DI GUDANG (SUDAH DIAMBIL)                                   |
|  - Staf gudang menghitung fisik barang yang tiba dari kurir MH sebelum dimasukkan ke rak tujuan.                        |
|  - Jika ada barang rusak akibat pengangkutan/transit, staf gudang mencatat `defect_qty` & alasan kerusakannya.          |
|  - Dampak Stok Fisik: HANYA kuantitas unit bagus yang ditambahkan ke saldo rak gudang (`stock_balances`).               |
|  - Transaksi Inbound: Diterbitkan resmi pada tabel `transactions` sebesar unit bagus yang diterima fisik.                |
|  - Penutupan Tiket: Jika `quantity_received >= quantity_requested`, status tiket resmi ditutup (`is_final = true`).     |
+--------------------------------------------------------------------------------------------------------------------------+
```

### Formula Perhitungan Kuota Matematis:
1. **Kuantitas Permintaan Awal:**  
   $$\text{Order Awal} = \text{Target Aktif} + \text{Akumulasi Defect}$$
2. **Koreksi Kuota Aktif:**  
   $$\text{Target Aktif} = \text{Order Awal} - \text{Akumulasi Defect}$$
3. **Pencegahan Over-Processing (Batas Atas):**  
   $$\text{Unit Bagus Diproses} + \text{Defect Dilaporkan} \le \text{Saldo Tersedia dari Tahap Sebelumnya}$$
4. **Audit Trail Defect:**  
   Setiap pencatatan defect mencatat secara permanen: **Nama Operator & Departemen**, **Tanggal dan Jam (DD/MM/YYYY HH:mm)**, **Titik Terjadinya Masalah**, dan **Alasan Defect**.

---

## 5. Tata Letak Tabel Antrean WRI Desktop (9 Kolom Standar Industri)

Tabel antrean WRI pada desktop dirancang dengan struktur 9 kolom presisi untuk visibilitas maksimal:
1. **No WR / Status:** Tautan nomor tiket WRI (klik membuka detail lengkap) + tombol aksi eksekusi status (aktif jika user berwenang) atau lencana status informatif.
2. **Tanggal:** Tanggal dan jam tiket WRI diajukan (`DD MMM YYYY, HH:mm WIB`).
3. **Komponen:** Nama komponen tebal beserta nomor kode part number.
4. **Sts Pengaju:** Status persetujuan pengaju (`Diterima`, `Menunggu`, `Ditolak`) dengan tombol cepat persetujuan untuk Ka. Pengaju/SPV.
5. **Sts Penerima:** Status persetujuan penerima (`Belum Verifikasi`, `Diterima`) dengan tombol penugasan operator bagi Ka. Penerima.
6. **Foto:** Tombol kapsul `[Lihat]` yang memicu modal pop-up foto part beresolusi penuh.
7. **Routing / Dimensi / Qty:** Menampilkan departemen tujuan penerima, dimensi spesifikasi part, serta kuantitas order (beserta kuota defect jika ada).
8. **Deskripsi:** Catatan order pengajuan (`notes`) dan referensi SPK.
9. **Pengaju / Penerima:** Menampilkan departemen pemohon, departemen pelaksana, dan nama operator yang ditugaskan.
*(Catatan: Kolom lama Target Slot Gudang dan Lokasi Gambar ditiadakan dari tabel utama demi kerapian alur kerja).*

---

## 6. Antarmuka Pop-Up Detail Tracking Tiket WRI (Dual Verification & Defect Box)

Pop-up modal detail tiket (`detail_modal.blade.php`) menyajikan visualisasi hierarkis proses pengerjaan:
1. **Hero Status Header:** Ikon melingkar besar (Kuning Jam: `Belum Verifikasi`, Hijau Centang: `Siap Diambil`, Emerald Centang Ganda: `Sudah Diambil`, Biru Gear: `Sedang Diproses`).
2. **Kartu QR & Identitas Tiket:** Menampilkan kode QR pemindai fisik, nomor WRI tebal, nama komponen, dan part number.
3. **Dual Verification Card:** Menampilkan identitas otorisasi ganda secara transparan:
   - **Verifikator Pengaju:** Ka. Pengaju / SPV, waktu otorisasi, status persetujuan.
   - **Verifikator Penerima:** Ka. Penerima, waktu verifikasi, dan nama operator pelaksana yang ditugaskan.
4. **Strip Kuota Order & Defect Transparan:** Menampilkan Order Awal, Potongan Defect, Target Aktif, dan Total Diterima Gudang.
5. **Tracking Proses (Station Event Boxes):**
   - **Biru (MASUK):** Kuantitas masuk stasiun pengerjaan & waktu.
   - **Kuning (PARSIAL):** Kuantitas selesai bertahap/cicilan.
   - **Merah (DEFECT):** Kuantitas rusak/hilang, nama operator, dan alasan kerusakan (kuota otomatis berkurang).
   - **Hijau (SELESAI FINAL):** Kuantitas tuntas pengerjaan stasiun.
   - **Node Pengiriman Gudang:** Konfirmasi fisik barang diterima di rak gudang.
6. **Penyelarasan Alur Pembuatan Tiket:** Pembuatan tiket WRI baru tidak lagi me-redirect ke halaman penuh terpisah, melainkan langsung kembali ke antrean utama dengan modal detail otomatis terbuka.

---

## 7. Antarmuka Pop-Up Quantity (Input Qty & Defect Minimalis)

Pop-up modal input kuantitas pengerjaan dan serah terima (`process_modal.blade.php` dan `show.blade.php`) mengadopsi prinsip desain bersih (*clean industrial*), ergonomis, dan tidak bising (*non-cluttered*):
1. **Dimensi Kompak & Bebas Scroll (`max-w-md` ~420px):** Seluruh elemen aksi esensial muat dalam 1 layar tanpa mengharuskan operator/kurir menggulir layar (*scrolling*).
2. **Ikon Melingkar Berwarna Lembut (Soft Pastel Badge):** Mengindikasikan konteks proses (Teal untuk Mesin Masuk, Indigo untuk Mesin Keluar, Amber untuk MH Pickup/Antar, Emerald untuk Rak Gudang).
3. **Kartu Header Tiket Bersih:** Nomor tiket WRI dan Part Number disajikan secara leluasa tanpa penumpukan teks operator terpotong (`Op:...`), sehingga tampilan lebih rapi dan keterbacaan data maksimal.
4. **Workflow Transition Strip (Alur Pergerakan Tiket Visual):** Menggantikan breadcrumb teks datar dengan kontainer alur dinamis yang menampilkan status saat ini (pill netral kiri), indikator panah transisi sirkular, dan target langkah berikutnya dengan aksen warna tematik (pill kanan) agar arah pengerjaan langsung dipahami seketika tanpa distraksi warna berlebih.
5. **Hero Stepper Touch-Friendly:** Dilengkapi tombol minus `[-]`, input angka besar font mono tebal di tengah, tombol plus `[+]`, serta tombol cepat `[Semua / Max: X pcs]` untuk mengisi kuantitas penuh secara instan tanpa mengetik manual.
6. **Smart Defect Drawer (Opsional & Dapat Dilipat):** Tombol toggle `[+ Ada Defect / Rusak? (Opsional)]` menyembunyikan form defect jika tidak ada reject (90%+ kondisi normal). Jika dibuka, menyediakan input kuantitas defect dan alasan secara kompak dengan kalkulasi otomatis pemotongan kuota.
7. **Penguncian Tahap Mesin Masuk `(In)` & Popover Tooltip `(i)`:** Kuantitas otomatis terkunci penuh dengan lencana gembok. Paragraf penjelasan panjang dialihkan ke dalam tombol ikon info interaktif `(i)` dengan tooltip melayang (*hover & click popover*) di samping teks *Terkunci Penuh*, menjaga tinggi modal tetap ramping.
8. **Catatan Proses Minimalis:** Label catatan proses menggunakan tipografi tipis normal `Catatan (opsional)` dengan placeholder ringkas `Tambahkan catatan jika perlu...`.
9. **Logistics Route Strip pada Modal Verifikasi Ambil MH (`work_station_supplies/index.blade.php`):** Rute penjemputan komponen logistik (`Asal → Tujuan`) dipisahkan ke dalam kontainer alur tersendiri dengan penanda titik asal (`📍 Asal`), bulatan transisi kurir, dan target pengantaran (`🎯 Tujuan`) secara simetris, mengeliminasi masalah tumpang tindih teks rute dengan nomor tiket WRI.
10. **Pemisahan Peran Modal Detail & Aksi Cepat Terintegrasi (`detail_modal.blade.php`):** Modal Detail difokuskan murni sebagai pusat informasi & audit (*Read-Only & Tracking*). Form eksekusi langkah pengerjaan yang panjang dihapus dan digantikan oleh **Action Banner Kompak** (`Langkah Siap Dikerjakan`). Tombol pemicu pada banner tersebut (`openProcessFromDetail()`) secara otomatis menutup modal detail dan seketika membuka modal proses khusus (`process_modal`) dengan alur pengerjaan yang lengkap dan ergonomis.
11. **Integritas Fungsi & Zero Regression:** Seluruh parameter form (`action_type`, `target_status`, `work_request_id`, `process_qty`, `defect_qty`, `defect_reason`, `notes`), route aksi, dan validasi database tetap berjalan 100% identik tanpa perubahan logika backend.

---

## 8. Alur Pemilihan & Pendaftaran Komponen Baru pada Formulir Membuat WR

Formulir pengajuan pesanan pembuatan part (`/work-requests/create`) menerapkan alur pemilihan dan pendaftaran on-the-fly yang mulus:
1. **Searchable Combobox (Pencarian Cepat 627+ Komponen):**
   - Pengaju mengetik nama part atau kode part pada kolom input interaktif.
   - Panel hasil pencarian secara instan menyaring daftar katalog komponen karoseri, menampilkan foto thumbnail, kode part, nama, kategori, dimensi (P x L x T), dan saldo stok fisik gudang.
   - Memilih part langsung menampilkan kartu identitas visual dan mengisi rekomendasi stasiun mesin fabrikasi (*Smart Suggest Routing*) secara otomatis.
2. **Fallback Pendaftaran Part Baru On-The-Fly (Modal Registrasi Komponen Minimalis):**
   - Apabila part yang diketik belum terdaftar di sistem (*Zero Results*), sistem secara otomatis menampilkan kartu informasi ramah dengan tombol `[+ Daftarkan Part Baru Ini]`.
   - Nama part yang diketik otomatis diisikan (*pre-filled*) ke dalam form modal registrasi tanpa mengharuskan pengguna mengetik ulang.
   - Form modal menerapkan tipografi bersih dan santai (Sentence Case, `font-medium`, tanpa penumpukan bold kapital), input dimensi terpadu dengan micro-suffix satuan (`P (mm)`, `L (mm)`, `T (mm)`), kartu unggah foto part yang rapi, serta pilihan **Kategori Part (Opsional)** dengan default aman (*Subframe / Karoseri*).
   - Penyimpanan via AJAX seketika menambahkan komponen ke katalog master dan langsung memilihnya di formulir WR tanpa me-refresh halaman.
3. **Standarisasi Zero-FOUC (Bebas Kedipan Modal):**
   - Seluruh modal dilindungi oleh deklarasi CSS global `[x-cloak] { display: none !important; }` dan inline style `style="display: none;"` untuk menjamin tidak ada modal yang muncul sekilas saat halaman pertama kali dibuka.
4. **Desain Formulir Single Unified Card Minimalis:**
   - Formulir pengajuan WR meninggalkan pola kotak multi-warna bertingkat (*rainbow cards*) dan beralih ke struktur kartu tunggal putih yang bersih (*Single Unified Card*) dengan border halus `border-slate-200/80`.
   - Menggunakan tipografi Sentence Case yang proporsional (`font-medium text-slate-700`), menghilangkan penumpukan label uppercase dan bold tebal.
   - Sub-header identitas pemohon (*Applicant Bar*) berformat bar horizontal ringkas abu-abu lembut (`bg-slate-50/70 border border-slate-100 rounded-xl`), menampilkan nama pemohon, shift kerja, dan waktu sistem secara rapi tanpa memakan ruang vertikal berlebih.
   - Pilihan prioritas menggunakan segmen pill minimalis (Normal, Mendesak, Darurat).
   - Repeater rute departemen (*Routing Stepper*) menggunakan nomor step monokrom halus dengan tombol aksi ikonik yang rapi.
5. **Optimasi Performa Loading Formulir (Zero Latency & Anti-Timeout):**
   - **On-Demand Photo Streaming:** Pemanggilan URL foto komponen `$c->image_url` pada 627+ daftar master part digantikan oleh route streaming asinkron `route('components.photo-stream', ['component' => $c->id])`. Ini mengeliminasi 45.000+ operasi I/O `file_exists()` sinkron ke penyimpanan SMB/NAS saat halaman dirender.
   - **In-Memory Eager Loading Stock:** Pengecekan saldo stok pada `Component::getTotalStockAttribute()` dioptimalkan dengan memeriksa apakah relasi `stockBalances` telah di-eager-load. Jika sudah, kalkulasi dilakukan langsung pada memori koleksi (`$this->stockBalances->sum('quantity')`), menghilangkan 627+ query SQL redundan (N+1 query problem).
   - Mengatasi secara tuntas masalah waktu muat halaman yang lama (15–45 detik) dan risiko *connection timeout* atau mental kembali ke menu monitoring WR.

---

## 9. Standar Navigasi Kembali (Universal Back Navigation) & Tombol Refresh Antrean

Untuk menjamin kelancaran navigasi operator dan mencegah kebingungan pengguna di lantai produksi:
1. **Tombol Kembali Prominen pada Seluruh Menu Utama (Index Views) Menuju Beranda:**
   - Seluruh halaman menu utama (*Index Views*: Work Request, Material Handling Center, Master Komponen, Transaksi Gudang, Peta Gudang 2D, ECR, Disposal, Stok Opname, dan Cetak Label QR) dilengkapi tombol navigasi `[ < Kembali ]` di sisi kiri judul menu yang langsung mengarahkan pengguna kembali ke **Beranda Utama (`/dashboard`)** dalam 1-klik, mengeliminasi kebutuhan mencari tombol logo atau menu sidebar samping.
2. **Navigasi Hierarkis Dua Tingkat Panel Admin (Pusat Kendali Administrator):**
   - Seluruh submenu di dalam area Administrator (`admin/users`, `admin/roles`, `admin/component-categories`, `admin/uoms`, `admin/locations-master`, `admin/settings`, dan `admin/audit-logs`) mengarahkan tombol `[ < Kembali ]` secara hierarkis ke **Pusat Kendali Administrator (`/admin/dashboard`)**, bukan langsung melompat keluar ke beranda gudang.
   - Halaman **Pusat Kendali Administrator (`/admin/dashboard`)** menyematkan tombol khusus `[ < Kembali ke WMS ]` menuju `route('dashboard')` untuk kembali ke sistem logistik pergudangan utama.
   - Halaman Audit Log (`audit_logs/index.blade.php`) beroperasi secara adaptif: mengarahkan kembali ke `route('admin.dashboard')` bila diakses dari admin panel, atau ke `route('dashboard')` bila diakses dari navbar atas `/activities`.
3. **Tombol Kembali Prominen pada Seluruh Sub-View Jendela:**
   - Seluruh halaman input dan detail (*create, edit, show*) di modul Work Request, Master Komponen, Transaksi, ECR, Disposal, dan Cycle Count dilengkapi tombol navigasi `[ < Kembali ]` berbasis border fisik (`border-slate-300`), latar belakang putih kontras, ikon panah `fa-arrow-left`, dan teks tebal yang mengarahkan kembali ke indeks modul induk masing-masing.
4. **Standarisasi Tombol Batal & Tutup pada Seluruh Pop-up Modal Dialog:**
   - Bagian header modal detail WRI dilengkapi tombol navigasi `[ < Kembali ]` di samping tombol silang `(X)`.
   - Tombol penutup pada sticky footer modal detail diperjelas menjadi `[ < Kembali / Tutup ]`.
   - Modal proses kuantitas, penugasan operator, pembatalan/penolakan tiket, pembesar QR, dan pratinjau foto part distandarisasi dengan tombol pembatalan eksplisit `[ < Kembali / Batal ]` atau `[ < Kembali / Tutup ]`.
5. **Tombol Refresh 1-Klik Antrean WRI & Stasiun Mesin:**
   - Header antrean [work_requests/index.blade.php](file:///C:/xampp/htdocs/hpk-gudang-inhouse/resources/views/work_requests/index.blade.php) dan antrean Material Handling / Mesin [work_station_supplies/index.blade.php](file:///C:/xampp/htdocs/hpk-gudang-inhouse/resources/views/work_station_supplies/index.blade.php) menyediakan tombol **Refresh** 1-klik (`fa-solid fa-arrows-rotate`).
   - Tombol ini menjalankan penyegaran instan (`window.location.reload()`) yang mempertahankan filter status tab dan pencarian aktif secara aman tanpa mengharuskan pengguna menekan F5 browser.

---

## 10. Optimasi Responsivitas Antarmuka (Instant Click & Zero-Ghost-Click Protection)

Untuk menjamin setiap klik tombol, dropdown, atau tautan langsung merespon seketika tanpa fenomena *double-click* atau *unresponsive refresh*:
1. **Pembersihan File Hot Module Replacement (`public/hot`):**
   - File `public/hot` dinonaktifkan sehingga peramban berjalan 100% pada aset statis terkompilasi (`public/build/`).
   - Mencegah server dev WebSocket port 5173 memicu `window.location.reload()` otomatis secara tiba-tiba di tengah aktivitas klik pengguna.
2. **Pencegahan Race-Condition Navigasi Dropdown:**
   - Komponen dropdown navigasi atas dioptimasi agar event klik tautan (`<a>`) dieksekusi tuntas oleh peramban tanpa terbatalkan di tengah jalan oleh Alpine.js *DOM unmounting*.
3. **Penonaktifan Seketika Pointer Events pada Backdrop Transisi Modal:**
   - Seluruh modal dialog dan drawer menu samping dipasangi kelas dinamis `:class="{ 'pointer-events-none': !isOpen }"`.
   - Menjamin bahwa saat modal sedang dalam animasi memudar (fade-out 200–300 ms), lapisan transparan tidak menelan klik pengguna berikutnya.
4. **Indikator Visual & Proteksi Double Submission pada Dropdown WRI:**
   - Aksi pemilihan persetujuan pada tiket WRI langsung menampilkan status visual loading (`Memproses...` disertai animasi spinner) dan mengunci input untuk mencegah double klik selama proses pengiriman form ke server.

---

## 11. Prosedur & Tata Kelola Impor Massal Master Komponen Karoseri (Bulk Excel Import)

Untuk mengakomodasi kebutuhan penginputan data master komponen karoseri dalam jumlah banyak dan mendesak (*urgent batch input*):

1. **Format Resmi Template Excel 2-Sheet:**
   - Pengguna mengunduh berkas template resmi melalui tombol `[ Download Template Excel ]` pada modal impor di halaman `/components`.
   - **Sheet 1 (`INPUT DATA KOMPONEN`):** Formulir pengisian data komponen berheader Dark Navy HPK (`#0A2342`) dengan 3 baris sampel pengisian riil.
   - **Sheet 2 (`PEDOMAN & REFERENSI DROPDOWN`):** Kamus referensi master data terkini:
     - Tabel Kategori Resmi (subframe, assy, accessories, pto, potong_chassis, spo).
     - Tabel Satuan Resmi (PCS, SET, BATANG, LEMBAR, KG, LITER, BOX, METER).
     - Tabel Kode Rak Fisik Aktif Gudang (sinkron langsung dari database `locations`).
     - Panduan Kolom Wajib (*Mandatory*) vs Kolom Opsional (*Optional*).

2. **Fleksibilitas Kolom & Nilai Bawaan (Default Values):**
   - **Kolom Wajib:** Hanya **Kode Komponen** (`part_number`) dan **Nama Komponen** (`name`).
   - **Kolom Opsional (Boleh Kosong):**
     - Kategori: jika kosong, tersimpan sebagai belum dikategorikan (`null`) dan otomatis masuk antrean filter *Perlu Kategori*.
     - Satuan (UoM): jika kosong, otomatis menggunakan default `PCS`.
     - Dimensi (P, L, T) & Spek: boleh dikosongkan.
     - Stok Minimum: jika kosong, default `0`.
     - Saldo Stok Awal: jika kosong, default `0`. Jika diisi > 0, otomatis mencatat mutasi stok awal.
     - Kode Rak: jika kosong, default ditempatkan pada area penampungan sementara (`Area Penampungan Awal / Belum Dialokasikan ke Rak`).

3. **Tata Kelola Foto & Gambar Teknik CAD Komponen (Pure Local On-Premise Storage):**
   Sistem menerapkan penyimpanan luring (*offline pure local mode*) terpusat pada direktori master server jaringan `Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House` (dengan fallback redundansi cadangan lokal di `D:\Ali Magang\KOMPONEN`) melalui layanan terpadu `ComponentPhotoStorageService` dengan variabel lingkungan `COMPONENT_EXTERNAL_PHOTO_DIR='Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House'` dan `COMPONENT_ENABLE_GDRIVE_FALLBACK=false`.

   Berikut adalah rincian perilaku sistem pada 3 skenario operasional:
   - **Skenario A: Tambah Baru / Edit Manual via Formulir Master Komponen atau Kamera Web:**
     - Operator/Engineering mengunggah berkas foto (`.jpg`, `.jpeg`, `.png`, `.webp`, `.svg`) atau dokumen cetak biru CAD (`.pdf`) hingga ukuran maksimal 15MB, atau mengambil snapshot kamera web.
     - Sistem membersihkan kode komponen via `sanitizePartNumber()` agar aman di tingkat filesystem OS Windows.
     - Berkas **otomatis dinamai ulang (*auto-rename*)** menjadi format standar: `[part_number].[ext]`.
     - Berkas **langsung disimpan ke folder fisik master `Z:\...\[part_number].[ext]`** dan dicerminkan (*mirrored*) ke `public/uploads/components/`.
     - Sistem melakukan *clean overwrite* (otomatis menghapus berkas lama jika terjadi pergantian format ekstensi pada part yang sama).
   - **Skenario B: Impor Massal Excel dengan Kolom Foto Diisi Tautan (Google Drive / URL Web):**
     - Pada baris Excel yang memiliki tautan di kolom L ("Foto Komponen"), sistem backend mengunduh berkas gambar/PDF asli melalui HTTP Client (otomatis mengonversi ID sharing Google Drive menjadi stream beresolusi tinggi `lh3.googleusercontent.com/d/{fileId}`).
     - Sistem **otomatis me-rename** berkas hasil unduhan menjadi format standar `[part_number].[ext]`.
     - Berkas **otomatis disimpan langsung ke folder master `Z:\...`** dan folder mirror webroot.
     - Kolom `image_path` di database diisi alamat relatif `uploads/components/[part_number].[ext]`.
   - **Skenario C: Impor Massal Excel dengan Kolom Foto Dikosongkan (Offline Drop-in / Auto-Detection):**
     - Kolom foto pada Excel sangat dianjurkan dikosongkan jika tim gudang/engineering sudah mengumpulkan foto/PDF komponen secara massal langsung di komputer server/lokal.
     - Sistem **tidak melakukan unduhan jaringan**, sehingga proses impor ratusan part selesai dalam hitungan detik.
     - **Deteksi Otomatis Fisik:** Sistem melalui accessor `Component::getLocalPhotoFilePathAttribute()` dan `ComponentPhotoStorageService::findLocalPhoto()` **secara otomatis mencocokkan kode part fisik di folder master drive Z:** (dengan fallback lokal `D:` jika Z: tidak terhubung). Selama file bernama `[part_number].[ext]` (misal `CBMP110004800602T.jpg`) ada di folder tersebut, sistem langsung menampilkan foto/PDF tersebut di web tanpa memerlukan link URL sama sekali di database!
     - Jika file fisik belum ditaruh di folder master, sistem menyajikan *Placeholder SVG Karoseri HPK* tanpa error dan tanpa menghubungi internet luar.

   ```mermaid
   flowchart TD
       Start([Aksi Penambahan / Pembaruan Komponen]) --> Choice{Metode Penginputan?}
       
       Choice -->|Formulir Input / Edit / Kamera| ManualUpload[Unggah File Gambar / Dokumen CAD PDF / Snap Kamera]
       ManualUpload --> CleanManual[Sanitize Kode Part: [part_number].[ext]]
       CleanManual --> SavePhysical[Tulis Berkas Fisik ke Drive Z: Master]
       SavePhysical --> MirrorPublic[Salin Mirror ke public/uploads/components/]
       
       Choice -->|Impor Massal Excel| CheckCol{Kolom L Tautan Foto Diisi?}
       CheckCol -->|Ya: Berisi Link GDrive / Web| DownloadHTTP[Backend Unduh Berkas via HTTP Stream]
       DownloadHTTP --> CleanExcel[Auto Rename: [part_number].[ext]]
       CleanExcel --> SavePhysical
       
       CheckCol -->|Tidak: Kolom L Dikosongkan| SkipDownload[Lewati Download Jaringan / Cepat]
       SkipDownload --> ScanDisk{Apakah Berkas [part_number].* Ada di Z: atau D:?}
       ScanDisk -->|Ada di Disk Master/Backup| LocalFound[Sistem Otomatis Deteksi & Tampilkan Berkas Fisik]
       ScanDisk -->|Belum Ada di Disk| FallbackSVG[Sistem Tampilkan Placeholder SVG Karoseri HPK]

       SavePhysical --> StreamingGW[Streaming Gateway HTTP /components/id/photo-stream via StreamedResponse]
       MirrorPublic --> StreamingGW
       LocalFound --> StreamingGW
       StreamingGW --> BrowserDisplay[Antarmuka Stasiun Mesin & Gudang: Interactive Lightbox / Native PDF Viewer 500%]
   ```

   - **Gateway Aliran Berkas HTTP Terisolasi (`/components/{component}/photo-stream`):**
     Karena standar keamanan browser melarang akses protokol file lokal (`file:///...`), controller menyajikan rute streaming terisolasi `ComponentController::photoStream()`. Sistem membaca file master fisik di server NAS `Z:\` (didukung direct UNC `\\192.168.1.150\...`, fallback lokal `D:\Ali Magang\KOMPONEN`, dan mount Linux `/mnt/nas-gudang/...`) secara on-the-fly via `StreamedResponse`, menginjeksi header MIME dan cache HTTP 24 jam (`max-age=86400`), serta menyajikan dokumen PDF secara inline untuk penampil CAD modal interaktif di stasiun kerja mesin (*work center*). Berkas sumber data acuan resmi tersinkronisasi di folder `Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House\Data Acuan Resmi\Form Work Request - Data komponen.csv`.

4. **Alur Validasi 2 Tahap (Pre-Import Live Preview & Audit):**
   - **Langkah 1 (Upload & Opsi Duplikat):** Pengguna mengunggah file `.xlsx` atau `.csv` dan memilih salah satu kebijakan duplikat:
     - *Lewati Duplikat (Skip):* Mempertahankan data lama jika kode part sudah terdaftar di database.
     - *Perbarui Duplikat (Update):* Menimpa spesifikasi, kategori, rak, dan stok dengan data terbaru dari Excel.
   - **Langkah 2 (Pratinjau Live):** Sistem menampilkan kartu ringkasan KPI (Total Baris, Komponen Baru, Duplikat Dilewati/Diupdate, Error) dan tabel baris lengkap dengan badge warna status. Baris error dapat ditinjau tanpa merusak baris valid lainnya.
   - **Langkah 3 (Eksekusi Atomik):** Pengguna menekan tombol `[ Konfirmasi & Simpan ke Database ]`. Seluruh data valid disimpan dalam transaksi database terisolasi (`DB::beginTransaction` / `commit` / `rollBack`), memunculkan ringkasan audit hasil impor, dan menyajikan tombol muat ulang halaman.

---

## 12. Standar Operasional & Ergonomi Antarmuka Ponsel (Mobile Smartphone UX)

Untuk memastikan kelancaran operasional para operator mesin, kurir Material Handling, dan petugas gudang saat mengakses sistem menggunakan smartphone/tablet lapangan:

1. **Prinsip Bebas Tabrakan (Zero-Collision Layout):**
   - Seluruh baris judul dan tombol aksi header menggunakan pembungkus fleksibel (`flex-wrap gap-2 w-full sm:w-auto`), mencegah teks dan tombol saling mendesak atau meluap dari tepi layar.
   - Pada judul navbar atas, teks brand perusahaan dibatasi secara proporsional (`truncate max-w-[125px] sm:max-w-none`) agar tombol pintas scanner kamera dan avatar profil akun tetap memiliki ruang sentuh yang lega.

2. **Tata Letak Kartu Data & Tombol Aksi Simetris:**
   - Kartu katalog komponen pada tampilan ponsel menyematkan baris aksi 4-kolom berjarak seragam (`QR`, `Edit`, `Detail`, `Transaksi`), memberikan kemudahan pemilihan aksi tanpa risiko salah tekan (*misclick*).
   - Seluruh elemen sentuh memenuhi batas ergonomis minimal 42px – 44px (`touch-target`) yang nyaman untuk jempol tangan.

3. **Kemudahan Input Formulir & Tombol Aksi Penuh (Full-Width Submit):**
   - Formulir pembuatan pesanan produksi (`/work-requests/create`) memisahkan judul seksi dengan tombol pendaftaran part baru secara vertikal adaptif pada layar sempit, mencegah elemen saling bertumpuk.
   - Tombol pengiriman formulir (*Kirim & Terbitkan WR*) diubah menjadi tombol berukuran penuh (*full-width*) di bagian bawah layar ponsel untuk memudahkan pengiriman pesanan dalam satu kali sentuhan.

4. **Navigasi & Tabel Lebar Geser Samping (Horizontal Swipe):**
   - Tabel pratinjau impor komponen (10 kolom) dan tabel data padat lainnya dilindungi batas lebar minimum (`min-w-[720px]`) disertai petunjuk gestur sentuh *"Geser tabel ke samping"*, menjamin tidak ada kolom atau badge status yang remuk atau terpotong pada layar ponsel.
   - Tombol konfirmasi pada modal dialog dialihkan ke susunan vertikal terbalik (`flex-col-reverse sm:flex-row`), memprioritaskan tombol aksi utama di bagian atas jangkauan jari.
5. **Anti-Overflow Global Navbar & Vaksin Kanvas (`overflow-x-clip`):**
   - Mengeliminasi kebocoran warna latar belakang dan pemotongan tampilan pada mode HP dengan menyembunyikan teks brand panjang secara adaptif pada navbar ponsel (`hidden sm:flex`) saat judul submenu aktif ditampilkan, menjaga total lebar navbar < 280px sehingga avatar profil akun `PA` tetap berada di dalam batas navbar biru navy.
   - Menerapkan `overflow-x-clip` dan `max-w-full` pada `html, body` serta `<nav>` untuk menjamin tidak ada elemen anak yang dapat merentangkan kanvas secara liar ke samping.
   - Sub-navigasi WRI mengadopsi susunan bertumpuk fleksibel (`flex-col sm:flex-row`), mencegah tab navigasi dan pill status peringatan saling mendesak pada layar ponsel.
6. **Standarisasi Badge Defisit Anti-Nekuk & Dual View Safety Stock:**
   - Badge angka defisit dilindungi atribut `whitespace-nowrap inline-flex items-center gap-1` dengan tipografi monospaced dan micro-suffix satuan (`-7,936 PCS`), mencegah teks tertekuk (*wrapped*) menjadi dua baris vertikal yang merusak bentuk pill.
   - Halaman Safety Stock menerapkan arsitektur **Dual View**:
     - *Desktop & Tablet (`hidden md:block`):* Tabel komprehensif dengan `min-w-[720px]` dan pembagian kolom yang proporsional.
     - *Mode Ponsel (`block md:hidden`):* Kartu komponen vertikal ramah sentuh jempol (foto thumbnail streaming bebas latensi, info part & rak, grid metrik 3 kolom: Stok Fisik, Safety Stock, Defisit, serta tombol aksi penuh *Pesan WRI Sekarang*), menghadirkan fleksibilitas maksimal tanpa keharusan menggeser tabel secara canggung.
7. **Bypass Turbo Cache pada Sub-Navigasi (`data-turbo="false"`):**
   - Baris sub-navigasi WRI (*Antrean WRI*, *Safety Stock*, *Cetak WR*) dilindungi atribut `data-turbo="false"`. Navigasi antar sub-menu dilakukan secara bersih (*clean page navigation*), mengeliminasi fenomena kedipan (*flicker*) akibat snapshot cache Turbo dan mencegah konflik *zombie listener* (`popstate`) yang sebelumnya menyebabkan halaman mental kembali ke sub-menu sebelumnya.
8. **Optimalisasi In-Memory Accessor WorkRequest (Zero N+1 Query Render):**
   - Seluruh accessor operasional pada model `WorkRequest` (`getRoutingStepsList`, `getTotalProcessedForStatus`, `getStepBalance`, `getPrintCountAttribute`) dioptimalkan untuk memprioritaskan relasi yang sudah di-eager load (`$this->relationLoaded('steps')` dan `$this->relationLoaded('logs')`).
   - Mengeliminasi 53+ query SQL redundan pada setiap perenderan baris tiket antrean, memangkas total query render dari 55 query menjadi hanya 2 query serta mempercepat respon perpindahan tab hingga 13x lebih cepat.
9. **Standar Akses Pengujian Multi-Device LAN (Tablet & Smartphone via Port 8000):**
   - Untuk pengujian di perangkat seluler/tablet pada jaringan lokal (`192.168.x.x`), pengembang/staf menjalankan `npm run build` sekali di terminal. Seluruh file JavaScript (Alpine.js, modal, scanner) dan CSS disajikan langsung melalui **Port 8000** mandiri.
   - Menghindari ketergantungan pada port 5173 (*Vite dev server*) yang sering terblokir oleh Windows Defender Firewall pada koneksi masuk perangkat luar.

---

## 13. Standar Operasional Cetak Dokumen Fisik Work Request (Triplicate Slip SOP)

Untuk memfasilitasi kebutuhan tanda tangan fisik, instruksi kerja stasiun mesin, dan penelusuran perpindahan material secara resmi di lantai pabrik:

1. **3 Syarat Mutlak Pemicu Cetak (Print Validation Gatekeeper):**
   Dokumen Work Request Inhouse (WRI) **HANYA BISA DICETAK** jika telah memenuhi 3 syarat kumulatif:
   - Status Pengajuan telah disetujui oleh Ka. Bagian Pengaju (`approval_pengaju === 'Diterima'`).
   - Status Penerima telah disetujui oleh Ka. Bagian Penerima (`approval_penerima === 'Diterima'`).
   - Operator pelaksana pengerjaan telah dipilih / ditugaskan (`assigned_operator_name` terisi).
   *Jika salah satu syarat belum terpenuhi, tombol cetak di modal detail dan antrean akan dinonaktifkan (disabled) disertai keterangan validasi yang transparan.*

2. **Format Standar Triplicate Slip (3 Rangkap dalam 1 Lembar HVS A4 Portrait):**
   Setiap pencetakan tiket menghasilkan 3 salinan slip identik yang dipisahkan oleh garis putus-putus (`✂ Potong di sini`):
   - **Lembar 1 (Pemohon / Putih):** Dipegang oleh departemen pengaju sebagai bukti pesanan resmi.
   - **Lembar 2 (Mesin / Pelaksana / Kuning):** Diserahkan ke operator stasiun mesin dan ditempelkan pada wadah fisik material kerja.
   - **Lembar 3 (Gudang / MH / Merah):** Digunakan oleh kurir Material Handling dan diserahkan ke gudang sebagai tanda terima barang jadi.

3. **Komponen Resmi Formulir Cetak:**
   - **Header Perusahaan & Nomor Tiket:** Logo resmi PT Hydraxle Perkasa, judul *WORK REQUEST PERMINTAAN PEKERJAAN*, dan nomor unik `WRI/...`.
   - **Kotak "Scan Here" & QR Code Vektor Lokal (SVG):** Menghasilkan kode QR tajam berbasis SVG Data URI tanpa ketergantungan API eksternal dan tanpa PHP GD extension, mempermudah pemindaian via barcode gun.
   - **Tabel Data Komponen:** Memuat Kode Komponen, Tanggal Order, Nama Komponen, Qty & Satuan, Dimensi, Dept Pengaju, dan Alur Routing/Dept Penerima.
   - **4 Kotak Otorisasi Tanda Tangan & Nama Jelas:**
     - `Pemohon`: Nama pembuat tiket pengajuan.
     - `Ka. Bagian Pemohon`: Nama kepala bagian / supervisor yang menyetujui pengajuan.
     - `Penerima`: Nama operator mesin yang ditugaskan mengerjakan komponen.
     - `Ka. Bagian Penerima`: Nama kepala bagian / supervisor mesin yang memverifikasi order.

4. **Kebijakan Cetak Ulang & Audit Trail:**
   - Dokumen **DIPERBOLEHKAN** untuk dicetak ulang jika lembar fisik rusak atau hilang di lantai pabrik.
   - Setiap cetak ulang diberi penanda khusus `[Cetak Ulang]` pada header slip dan frekuensi cetak (`print_count`) dilacak otomatis di log audit (`action_type: 'printed'`).

5. **Antrean Cetak & Fitur Cetak Massal (Bulk Printing):**
   - Akses cepat antrean cetak melalui menu tab `[ 🖨️ Cetak Dokumen WR ]` di samping *Safety Stock Alert*.
   - Menyediakan filter cepat: *Semua Siap Cetak*, *Belum Dicetak*, dan *Sudah Dicetak*.
   - Fitur *Bulk Print*: Petugas dapat mencentang beberapa tiket sekaligus dan mencetaknya dalam satu perintah cetak multi-halaman (`/work-requests/bulk-print`).

6. **Hak Akses Otoritas Cetak (`work-request.print`):**
   - Diberikan secara bawaan kepada Staf Gudang (`work_area: 'gudang'`), Administrator Gudang (`admin_gudang`), Supervisor (`supervisor`), dan Operator Mesin (`operator_mesin`).

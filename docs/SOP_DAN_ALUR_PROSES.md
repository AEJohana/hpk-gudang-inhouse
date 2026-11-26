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
        S -->|Serah Terima Fisik ke Pemohon| T[Status: Sudah Diambil]
        T --> U[(Otomatis Tambah Saldo Fisik Rak: stock_balances)]
        T --> V[(Otomatis Terbitkan Transaksi Inbound Resmi)]
        V --> X{Seluruh Qty Terpenuhi 100%?}
        X -->|Ya: Final| W([Tiket Selesai / Closed: is_final = true])
        X -->|Tidak: Parsial| Y[Tiket Tetap Aktif: is_final = false] -->|Kembali ke Mesin untuk Sisa Qty| J
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
| **BR-06** | **Prasyarat Tampil di Penerima & Isolasi Murni Departemen** | `scopeForUser` & filter `approval_pengaju === 'Diterima'` pada `WorkRequest.php`. | Departemen penerima / stasiun tujuan **TIDAK BISA** melihat atau memproses tiket sebelum atasan peminta menyetujui pengajuan. Pembatasan dan isolasi antrean antar-departemen dievaluasi murni berdasarkan identitas **15 Departemen Resmi** (`dept_penerima`, `routing_text`, dan `steps.process_name`) tanpa ketergantungan pada tabel mesin dummy (`machines` telah dihapus). |
| **BR-07** | **Pengerjaan Parsial, Defect Reaktif & Multi-Batch** | Handler `processAction` di `WorkRequestController.php`, `WorkRequest.php`, modal Blade, dan pedoman mutlak [docs/ATURAN_STATUS_DAN_ALUR_PARSIAL_WR.md](ATURAN_STATUS_DAN_ALUR_PARSIAL_WR.md). | Jika pemohon memesan 20 pcs dan mesin menyelesaikan 15 pcs unit bagus serta melaporkan 5 pcs defect mesin, sistem secara reaktif membatasi batas unit bagus maksimal 15 pcs (`20 - 5`). Karena 15 bagus + 5 defect = 20 (seluruh bahan tuntas), tahapan mesin **SEKETIKA BERHENTI TUNTAS** (`saldo_out = 0`) dan beralih ke MH (15 pcs). Tiket **TETAP TERBUKA AKTIF** (`is_final = false`) sampai seluruh kuantitas terpenuhi (100%), dan tombol pengerjaan sisa hanya muncul jika memang masih ada saldo fisik yang belum diproses. Selama sisa barang masih dikerjakan di stasiun mesin dan belum ada fisik yang menunggu kurir MH, status di sisi pengaju tetap menampilkan `Proses Line Dept {Mesin}`. |
| **BR-08** | **Auto-Update Saldo Fisik Gudang** | Logika event `Sudah Diambil` di `WorkRequestController.php` (baris 709-760). | Begitu tiket berstatus `Sudah Diambil`, saldo kuantitas pada rak tujuan di `stock_balances` **OTOMATIS BERTAMBAH** dan transaksi masuk resmi terbit di tabel `transactions`. |
| **BR-09** | **Sanitasi String Status** | Scope pembersihan status dan normalisasi nama status di Model `WorkRequest.php`. | String `Parsial >` dipisahkan secara otomatis saat evaluasi fase logika status dilakukan oleh sistem backend. |
| **BR-10** | **Peringatan Keterlambatan (> 3 Hari) & Filter Interaktif** | `scopeOverdueSla`, `isOverdue()` di `WorkRequest.php` + query `overdue_sla=1` & `oldest('created_at')` di `WorkRequestController.php`. | Tiket yang belum berstatus `Sudah Diambil` setelah melewati batas toleransi 72 jam (3 hari) otomatis memicu **Banner Peringatan Merah Prominen** di atas antrean tiket (*"Peringatan! Terdapat X Work Request yang tertunda lebih dari 3 hari"*). Banner ini dilengkapi tombol aksi interaktif `[ 🔻 Lihat yang Terlambat ]` dan `[ ✕ Tampilkan Semua ]`. Setiap tiket terlambat juga ditandai lencana peringatan merah `⚠️ Terlambat > 3 Hari` pada tabel dan kartu mobile, serta kotak info peringatan pada pop-up detail tiket. Seluruh kosakata asing "SLA" dihilangkan dari antarmuka lapangan demi kemudahan operator. |
| **BR-11** | **Unified 4-Status Control Bar & Bebas Redundansi** | Mapping status di `WorkRequestController.php` & tab navigasi `index.blade.php`. | Mengeliminasi baris ganda bertumpuk dan kontrol dobel. Antrean WRI disederhanakan menjadi 4 kategori operasional fungsional (+ Semua): **Perlu Verifikasi**, **Sedang Proses**, **Siap Ambil**, dan **Selesai**, dengan 5 kartu KPI interaktif di header. |
| **BR-12** | **Otorisasi Cetak Lembar WRI Resmi 3 Rangkap & Antrean Print** | `WorkRequest::isPrintable()`, `WorkRequestController::printSheet()`, `printQueue()`, dan `print_sheet.blade.php`. | Tiket hanya boleh dicetak jika: Approval Pengaju `Diterima`, Approval Penerima `Diterima`, dan Operator Pengerjaan telah dipilih (`assigned_operator_name`). Dicetak dalam format triplicate 3 rangkap (Pemohon, Mesin, Gudang) dalam 1 lembar HVS A4 dengan QR lokal SVG dan 4 kolom otorisasi tanda tangan resmi. Didukung fitur cetak ulang dengan log tracking dan cetak massal (*Bulk Print*). |
| **BR-13** | **Format QR Komposit 3-in-1 (`Nama;Kode;Rak`) & Parser Multi-Token** | `Component::getQrCompositePayloadAttribute()`, `ComponentController::searchApi()`, `WorkStationSupplyController::scanLookup()`, dan view cetak label QR. | Seluruh kode QR komponen memuat format komposit `Nama Komponen ; Kode Komponen ; Kode Rak` yang dapat dibaca offline oleh kamera apapun. Parser scanner otomatis mengekstrak part number dengan delimiter titik koma (`;`) tanpa merusak barcode lama (`backward-compatible`). |
| **BR-14** | **Penyimpanan Gambar Teknik & CAD PDF Lokal, Server NAS Windows & Produksi Linux (Pure Local Mode)** | `COMPONENT_EXTERNAL_PHOTO_DIR` & `COMPONENT_ENABLE_GDRIVE_FALLBACK` (.env / .env.production), `ComponentPhotoStorageService`, `Component::getLocalPhotoFilePathAttribute()`, `Component::getImageUrlAttribute()`, `ComponentController::photoStream()`, `CheckNasPhotosCommand`, `CleanDummyComponentsCommand`, dan `image_preview_modal.blade.php`. | Seluruh foto komponen dan gambar teknik CAD tersimpan terpusat di server jaringan bersama on-premise: pada server pengembangan Windows (`Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House\[part_number].[ext]` dengan auto-bridge UNC `\\192.168.1.150\...` dan redundansi cadangan lokal di `D:\Ali Magang\KOMPONEN`) atau pada server produksi Linux melalui mount NAS (`/mnt/nas-gudang/Gudang Komponen In House/[part_number].[ext]`) dengan Pure Local Mode (`COMPONENT_ENABLE_GDRIVE_FALLBACK=false`) yang mematikan ketergantungan koneksi Google Drive. Seluruh penambahan/pembaruan via Form Input, Edit, Quick Store WR, dan Impor Excel otomatis disimpan ke master folder NAS dengan nama bersih `[part_number].[ext]`. **Fitur Auto-Detection Cerdas:** Untuk komponen yang belum ada gambarnya, pengguna cukup menyalin file ke folder master NAS dan menamainya sesuai kode item (`[part_number].[ext]`), sistem langsung mengenalinya tanpa perlu update database. Dilengkapi normalisasi leading slash otomatis (`mnt/...` -> `/mnt/...`), resiliensi *case-sensitivity* Linux untuk ekstensi huruf kapital (`.JPG`, `.PNG`, `.PDF`), serta pencarian nama part fleksibel (raw spasi, tersanitasi, maupun tanpa simbol). Mendukung dokumen CAD format `.pdf` serta gambar hingga 15MB, dilengkapi **Interactive Drawing Zoom Modal** dan **Native PDF Modal Viewer Fullscreen** (zoom 500%, multi-halaman, cetak langsung) di stasiun mesin (*work center*). Dilengkapi streaming berkecepatan tinggi `StreamedResponse`, perintah audit konektivitas `php artisan components:check-nas`, perintah pembersihan dummy aman `php artisan components:clean-dummies`, dan arsip data sumber resmi di `Data Acuan Resmi\Form Work Request - Data komponen.csv`. |
| **BR-15** | **Manajemen Antrean Cetak Label QR (Default Menunggu Dicetak, Indikator KPI, Centered Control Dock Windows 11, Keranjang Pengajuan, Redesign Presisi Rak & Kartu, serta Bulk Print)** | `QrRequestController`, `resources/views/qr_requests/`, dan `QrRequestQueueTest.php`. | Akses awal menu `/qr-requests` tanpa parameter langsung menyaring antrean berstatus `pending` (*Menunggu Dicetak*) agar operator fokus pada label yang perlu diproses. Dilengkapi 3 kartu indikator KPI interaktif (*Menunggu Dicetak*, *Selesai Dicetak*, *Total Permintaan*), **Papan Kendali Terpusat (Centered Control Dock bergaya Taskbar Windows 11)** yang menyatukan 3 tab antrean dengan kolom pencarian cepat dalam 1 kapsul floating terpadu di tengah layar, lencana kuantitas riil (*count badges*) pada setiap tab, serta divider pemisah halus. Pada formulir `/qr-requests/create`, disematkan **Panel Geser Pratinjau Foto & Identitas Komponen Interaktif**, 2 opsi format label mandiri (*Label QR Rak* @10 label/set & *Label QR Kartu* fleksibel per part) yang dapat dipilih keduanya sekaligus, serta **Sistem Keranjang Pengajuan Massal (Cart Workflow)**. Di halaman antrean disematkan **Multi-Select Checkbox** dan **Floating Action Bar** untuk mencetak massal bersamaan (`/qr-requests/bulk-print`) dengan dokumen cetak A4 yang menerapkan *smart page-break*: lembar Label QR Rak tercetak dalam grid 2x10 (20 label/HVS) dengan 3 blok horizontal (kotak teks identitas, QR code SVG, kotak kosong stempel/paraf), sedangkan lembar Label QR Kartu tercetak dalam grid 2 kolom dengan 2 blok horizontal tanpa kotak kosong. |
| **BR-16** | **Pusat Log Aktivitas Pengguna & Audit Trail Terpadu** | `ActivityLogger`, `AuditLogController`, `LogSuccessfulLogin`, `LogSuccessfulLogout`, dan `tests/Unit/ActivityLoggerTest.php`. | Seluruh peristiwa operasional penting (Login/Logout, Pembuatan & Approval WRI, Penugasan Operator, Pengerjaan Mesin, Pelaporan Defect/Reject, Material Handling, Mutasi Stok Gudang, Master Komponen/CAD, dan Cetak Label QR) wajib direkam secara otomatis dengan deskripsi naratif berbahasa Indonesia yang jelas, nama pelaku, departemen, dan IP address. Dapat diakses via `/activities` dan `/admin/audit-logs` dengan 4 kartu KPI interaktif, filter multi-kriteria, dan modal detail JSON diff. |
| **BR-17** | **Studio Pembuatan Work Request Terintegrasi (2-Column Studio Layout, Sistem Keranjang, Multi-Routing, Pendaftaran Part Non-Master & Modal Interaktif)** | `WorkRequestController::create()`, `WorkRequestController::bulkStore()`, `resources/views/work_requests/create.blade.php`, `WorkRequestBulkStoreTest.php`, dan `WorkRequestCreateCartTest.php`. | Modul pembuatan pesanan fabrikasi (`/work-requests/create`) mengadopsi tata letak **2-Column Studio Layout**: Sisi kiri (60%) memuat Form Builder (metadata strip, dock pencarian terpadu ber-scanner QR kamera & tombol part baru, drawer pendaftaran komponen non-master, kuantitas dengan lencana satuan docked, rute mesin fleksibel, dan tombol `Tambah ke List`), sementara sisi kanan (40% sticky) memuat `Daftar Item Siap Kirim` (*Live Cart*) dengan kartu ringkasan part, tombol hapus halus, total pesanan, dan tombol hijau `Kirim Work Request`. Seluruh dialog konfirmasi dan pesan peringatan menggunakan modal interaktif kustom berbasis Alpine.js dengan zero native browser dialog. Mendukung multi-routing berurutan (misal `Dept Laser Cutting -> Dept M1`) dan transaksi atomik database aman. |
| **BR-18** | **Prinsip Satu Pintu Master Komponen & Isolasi Mutlak Part Ad-Hoc WR** | Kolom `components.source` (`'master'` vs `'wr_adhoc'`), scope `masterCatalog()`, `ComponentController::index()`, `ComponentController::quickStore()`, `DashboardController`. | **Katalog Master Komponen murni satu pintu:** Seluruh penambahan data ke katalog Master Komponen mutlak harus melalui menu Master Komponen resmi oleh Engineering/Admin Gudang. Komponen yang didaftarkan melalui form Work Request (`quickStore`) secara otomatis berstatus `wr_adhoc` ("buat lewat aja untuk kebutuhan pengerjaan saat itu") dan **100% terisolasi/disembunyikan dari modul Master Komponen, Safety Stock Alert, dan KPI Dashboard**, sehingga katalog master resmi HPK selalu steril dan bebas dari part coba-coba atau part non-standar. |

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
1. **MH Pickup (Antar-Mesin):** Kurir logistik Material Handling telah memindai QR code tiket dan menjemput fisik barang dari stasiun mesin sebelumnya untuk diantar ke stasiun mesin berikutnya (`Diantar MH ke {Dept Tujuan}` sampai departemen tujuan memproses `(In)`). Otorisasi eksekusi tahapan ke-2 dst secara ketat dikunci hanya untuk departemen tahapan aktif tersebut.
2. **Siap Diambil:** Barang telah tiba di area serah terima / gudang tujuan.
3. **Menuju Pengaju:** Kurir sedang dalam perjalanan mengantarkan barang ke departemen pemohon.
4. **Sudah Diambil (Parsial):** Serah terima batch cicilan divalidasi oleh gudang. Kuantitas fisik batch masuk stok gudang, sementara tiket tetap terbuka aktif (`is_final = false`). Sistem antrean WRI secara presisi memeriksa ketersediaan saldo fisik riil di lantai pabrik: jika sisa barang masih dikerjakan di stasiun mesin (`hasPhysicalWaitingPickup() = false` dan `inTransitQty = 0`), status tiket pengaju menampilkan **`Proses Line Dept {Mesin}`** (gear berputar). Lencana **`Menunggu Diambil MH`** dan tombol **`[Buka Menu MH]`** HANYA tampil jika stasiun mesin telah menyelesaikan pengerjaan dan barang fisik telah berada di palet tunggu penjemputan MH (`hasPhysicalWaitingPickup() = true`). Pedoman rinci mengacu pada [docs/ATURAN_STATUS_DAN_ALUR_PARSIAL_WR.md](ATURAN_STATUS_DAN_ALUR_PARSIAL_WR.md).
5. **Sudah Diambil (Final):** Serah terima fisik telah tuntas 100% divalidasi oleh pemohon/gudang. Memicu eksekusi penambahan stok otomatis (BR-08) dan tiket resmi ditutup (*Closed*, `is_final = true`).

### D. Pengelompokan 5 Tab Antrean Operasional WRI (Unified Control Bar Mutually Exclusive)
Untuk mempercepat navigasi operator dan supervisor di lapangan tanpa redundansi, antrean WRI dikelompokkan secara terpartisi ketat (*mutually exclusive*) menjadi 5 kategori status operasional (+ Semua):
1. **Semua:** Menampilkan seluruh tiket pada lingkup departemen user tanpa filter status.
2. **Perlu Verifikasi:** Tiket aktif yang sah membutuhkan persetujuan (`approval_pengaju = 'Menunggu'` atau `approval_pengaju = 'Diterima'` dengan `approval_penerima = 'Menunggu'`). Tiket yang ditolak mutlak tidak masuk ke tab ini.
3. **Sedang Proses:** Tiket yang telah disetujui penuh oleh kedua belah pihak dan berada dalam siklus pengerjaan pabrik: antrian mesin (`Dalam antrian`, `submitted`), pengerjaan aktif mesin (`Proses: ... (In/Out)`), penjemputan logistik kurir antar-mesin (`MH Pickup: ...`), atau pengerjaan cicilan bertahap (`Parsial`).
4. **Siap Diambil:** Tiket yang pengerjaan mesinnya tuntas dan menunggu penjemputan kurir MH (`Siap Diambil`) atau sedang dalam perjalanan kurir menuju pengaju/gudang (`Menuju Pengaju`).
5. **Selesai:** Tiket yang kuotanya telah tuntas 100% diterima fisik di rak tujuan gudang/pengaju (`Sudah Diambil`, `is_final = true`).
6. **Ditolak / Batal:** Tiket yang ditolak oleh Kepala Bagian Pengaju, ditolak oleh Kepala Bagian Penerima, atau dibatalkan sistem (`approval_pengaju = 'Ditolak'`, `approval_penerima = 'Ditolak'`, atau `status = 'cancelled'`).

> [!IMPORTANT]
> **Aturan Baku Hierarki Approval & Penolakan Tiket (WRI Governance):**
> Jika Kepala Bagian Pengaju menolak tiket (`approval_pengaju = 'Ditolak'`), tiket **SEKETIKA DITUTUP PERMANEN (Hard Close)** dengan `status = 'cancelled'`, `is_final = true`, dan `approval_penerima = 'Ditolak'`. Tiket tidak pernah diteruskan ke stasiun mesin penerima, tidak meminta verifikasi penerima, dan kolom status penerima menampilkan `Batal`.

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
2. **Fallback Pendaftaran Part Non-Master On-The-Fly (Tombol Terintegrasi Amber):**
   - Apabila komponen yang ingin dibuatkan pesanan belum tercatat di katalog master, pengguna dapat langsung menekan tombol `[+ Part Belum Ada di Master]` yang terintegrasi di sisi kanan kolom input pencarian komponen (addon suffix button bernuansa amber).
   - Redaksi disesuaikan dari sebelumnya *"Daftarkan Part Baru"* menjadi *"Part Belum Ada di Master"* untuk menegaskan konteks bahwa fitur ini ditujukan untuk menerbitkan Work Request atas part yang belum terdaftar di master, bukan sekadar CRUD master komponen.
   - Apabila kata kunci yang diketik tidak ditemukan (*Zero Results*), sistem secara otomatis menampilkan kartu informasi ramah dengan tombol fallback `[+ Part Belum Ada di Master]`.
   - Nama part yang diketik otomatis diisikan (*pre-filled*) ke dalam form modal registrasi tanpa mengharuskan pengguna mengetik ulang.
   - Form modal menerapkan tipografi bersih dan santai (Sentence Case, `font-medium`, tanpa penumpukan bold kapital), input dimensi terpadu dengan micro-suffix satuan (`P (mm)`, `L (mm)`, `T (mm)`), kartu unggah foto part yang rapi, serta pilihan **Kategori Part (Opsional)** dengan default aman (*Subframe / Karoseri*).
   - **Isolasi Mutlak Non-Master (`source = 'wr_adhoc'`):** Penyimpanan via AJAX `/api/components/quick-store` mencatat komponen dengan penanda `source = 'wr_adhoc'` dan memilihnya pada formulir WR untuk keperluan pesanan saat itu saja ("buat lewat aja sesuai kebutuhan"). Part ini **100% TIDAK dimasukkan ke dalam katalog Master Komponen** guna menjaga integritas dan kebersihan master katalog resmi pabrik. Penambahan ke katalog master tetap mutlak satu pintu melalui menu Master Komponen.
3. **Standarisasi Zero-FOUC (Bebas Kedipan Modal):**
   - Seluruh modal dilindungi oleh deklarasi CSS global `[x-cloak] { display: none !important; }` dan inline style `style="display: none;"` untuk menjamin tidak ada modal yang muncul sekilas saat halaman pertama kali dibuka.
4. **Desain Formulir Kompak & Sejajar (Side-by-Side Kuantitas & Prioritas):**
   - Formulir pengajuan WR beralih ke struktur kartu tunggal putih yang bersih (*Single Unified Card*) dengan border halus `border-slate-200/80` dan tipografi Sentence Case yang proporsional.
   - **Pemangkasan Ketinggian Form (Zero Clutter):** Menghilangkan tombol terpisah di header Bagian 1, menghemat ruang vertikal secara drastis.
   - **Tombol Addon Terintegrasi:** Tombol `[+ Part Belum Ada di Master]` disatukan langsung ke dalam kolom pencarian komponen (`flex items-stretch`) berkonsep mirip addon satuan `PCS`, memberikan estetika rapi dan hemat ruang.
   - **Side-by-Side Kuantitas & Prioritas:** Kolom `Jumlah Permintaan` (`col-span-5`) dan `Tingkat Prioritas` (`col-span-7`) disusun sejajar berdampingan dalam satu baris (grid 12 kolom) baik di mode desktop maupun mobile, mengoptimalkan tata letak tanpa memakan ruang vertikal bertumpuk.
   - Sub-header identitas pemohon (*Applicant Bar*) berformat bar horizontal ringkas abu-abu lembut (`bg-slate-50/70 border border-slate-100 rounded-xl`), menampilkan nama pemohon, shift kerja, dan waktu sistem secara rapi tanpa memakan ruang vertikal berlebih.
   - **Pita Alur Horizontal Profesional (Clean Stepper Ribbon):** Menyajikan visual alur pengerjaan stasiun fabrikasi dan logistik Material Handling dalam bentuk pita horizontal yang ringkas (~38px), rapi, dan formal tanpa emoji (`[ 1 Laser Cutting ] ➔ [ MH Transfer ] ➔ [ 2 Bending ] ➔ [ MH Antar ] ➔ [ Gudang (Selesai) ]`).
   - **Baris Tahapan Ultra-Compact Simetris (Zero Overlap):** Setiap tahapan disusun dalam kontainer putih bersih berborder halus dengan penomoran badge formal `[1] Tahap 1`, tombol aksi `[ ✕ Hapus ]`, serta grid 12 kolom simetris (5 kolom dropdown stasiun mesin dan 7 kolom catatan pengerjaan) tanpa tumpang tindih margin negatif.
   - **Tombol Tambah Proses Standar:** Tombol `[ + Tambah Proses ]` diletakkan di header seksi kanan atas dengan estetika minimalis, memudahkan operator menambahkan tahapan baru secara tertib.
5. **Optimasi Performa Loading Formulir (Zero Latency, Anti-Timeout & Payload 60% Ramping):**
   - **On-Demand Photo Streaming (Zero SSR Disk/SMB I/O):** Pemanggilan URL foto komponen `$c->image_url` pada seluruh daftar master part menghasilkan rute streaming asinkron `route('components.photo-stream', ['component' => $c->id, 'v' => updated_at])` tanpa menyentuh sistem berkas/NAS saat halaman dirender, dipadukan dengan `withSum` stok pada query `WorkRequestController::create()` serta *circuit breaker* SMB pada `ComponentPhotoStorageService`. Ini mengeliminasi risiko timeout SMB 60 detik secara permanen saat membuka form WR.
   - **In-Memory Eager Loading Stock:** Pengecekan saldo stok pada `Component::getTotalStockAttribute()` dioptimalkan dengan memeriksa apakah relasi `stockBalances` telah di-eager-load. Jika sudah, kalkulasi dilakukan langsung pada memori koleksi (`$this->stockBalances->sum('quantity')`), menghilangkan 627+ query SQL redundan (N+1 query problem).
   - **Eliminasi 627 Tag Option Tersembunyi:** Mengganti elemen `<select class="hidden">` yang memuat 627 `<option>` dengan 13 atribut `data-*` panjang menjadi satu tag input tersembunyi murni `<input type="hidden" name="component_id">`, memangkas ukuran payload HTML mentah dari **1.074 KB menjadi 437 KB** (pemangkasan > 60%) dan memangkas waktu render hingga 50%, sehingga formulir terbuka secara instan.
6. **Sistem Keranjang Pemesanan WRI Massal Safety Stock (*Multi-Select Cart Workflow & Kartu Order*):**
   - Pada halaman **Safety Stock Alert** (`/work-requests/safety-stock`), staf gudang dapat memilih lebih dari satu komponen defisit sekaligus melalui checkbox baris tabel desktop, master checkbox (*Pilih Semua*), maupun kartu mobile, atau dengan mengklik tombol `[+ Pesan WRI]`.
   - **Floating Action Bar Melayang:** Saat ada komponen yang dipilih, muncul bar aksi melayang di bagian bawah layar yang merangkum jumlah part terpilih serta tombol `[ Atur Kartu Order ]`.
   - **Modal Kartu Order Komprehensif:**
     - Menampilkan kartu order tersendiri untuk setiap part defisit.
     - **Jumlah Permintaan (Qty):** Nilai awal otomatis diisi sebesar **nilai defisit riil** (misal defisit -7.936, langsung otomatis terisi `7936 PCS`, fleksibel untuk diedit).
     - **Stasiun Mesin Tujuan:** Dropdown pilihan stasiun mesin fabrikasi dengan default cerdas (*Smart Suggest Routing* berdasarkan nama part).
     - **Tingkat Prioritas:** Pilihan *Normal (Produksi)*, *Tinggi (High Priority)*, atau *URGENT (Line Stop)*.
     - **Keterangan:** Kolom catatan pengerjaan khusus per part (opsional).
     - **Tanggal Otomatis:** Kolom tanggal disembunyikan dari antarmuka karena penanggalan tiket diisi otomatis oleh waktu sistem.
   - **Penerbitan Sekaligus (`/work-requests/bulk-store`):** Menerbitkan tiket Work Request untuk seluruh item keranjang dalam satu `DB::transaction` atomik dengan nomor urut WRI yang sekuensial dan pencatatan log audit otomatis.

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
     Karena standar keamanan browser melarang akses protokol file lokal (`file:///...`), controller menyajikan rute streaming terisolasi `ComponentController::photoStream()`. Sistem membaca file master fisik di server NAS `Z:\` (didukung fallback direct UNC `\\192.168.1.150\...` saat Drive primer tidak terjangkau, fallback lokal `D:\Ali Magang\KOMPONEN`, dan mount Linux `/mnt/nas-gudang/...` dengan *directory index cache* & *circuit breaker*) secara on-the-fly via `StreamedResponse`, menginjeksi header MIME dan cache revalidasi ringan (`public, max-age=300, must-revalidate` + `ETag` `304 Not Modified`), serta menyajikan dokumen PDF secara inline untuk penampil CAD modal interaktif di stasiun kerja mesin (*work center*). Berkas sumber data acuan resmi tersinkronisasi di folder `Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House\Data Acuan Resmi\Form Work Request - Data komponen.csv`.

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

3. **Kemudahan Input Formulir Kompak & Tombol Aksi Penuh (Full-Width Submit):**
   - Formulir pembuatan pesanan produksi (`/work-requests/create`) menyatukan tombol penambahan part non-master langsung ke dalam kolom pencarian komponen (`[+ Part Belum Ada di Master]`) dan menyejajarkan input `Jumlah Permintaan` (`col-span-5`) serta `Tingkat Prioritas` (`col-span-7`) dalam satu baris berdampingan (side-by-side grid), menghemat ruang vertikal secara drastis pada layar ponsel dan tablet.
   - Tombol pengiriman formulir (*Kirim & Terbitkan WR*) diubah menjadi tombol berukuran penuh (*full-width*) di bagian bawah layar ponsel untuk memudahkan pengiriman pesanan dalam satu kali sentuhan.

4. **Navigasi & Tabel Lebar Geser Samping (Horizontal Swipe):**
   - Tabel pratinjau impor komponen (10 kolom) dan tabel data padat lainnya dilindungi batas lebar minimum (`min-w-[720px]`) disertai petunjuk gestur sentuh *"Geser tabel ke samping"*, menjamin tidak ada kolom atau badge status yang remuk atau terpotong pada layar ponsel.
   - Tombol konfirmasi pada modal dialog dialihkan ke susunan vertikal terbalik (`flex-col-reverse sm:flex-row`), memprioritaskan tombol aksi utama di bagian atas jangkauan jari.

5. **Executive User Profile Dock pada Sidebar Drawer:**
   - Menyatukan kartu identitas akun di bagian bawah bilah samping (*Sticky Bottom Profile Dock*), menghadirkan avatar inisial berindikator aktif online hijau menyala, rincian peran & departemen riil, tombol pintas pengaturan profil berikon gear, tombol keluar (*Keluar*) berukuran penuh yang mudah dijangkau satu tangan, dan watermark resmi *PT. Hydraxle Perkasa*.
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

---

## 16. Standar Pengaturan Akun & Keamanan Profil Pengguna (`/profile`)

1. **Tata Kelola Identitas Pengguna:**
   - Setiap pengguna berhak memperbarui nama lengkap operasional dan username akun (`alpha_dash`, 3–30 karakter, unik) masing-masing melalui halaman `/profile` (sistem 100% otentikasi murni username tanpa ketergantungan email).
   - Peran sistem (*Role*) dan penempatan stasiun kerja (*Department / Work Area*) dikunci (*readonly*) dan hanya dapat dikonfigurasi melalui Administrator pada Pusat Kendali Manajemen Pengguna demi kepatuhan integritas audit.

2. **Protokol Keamanan Kata Sandi:**
   - Pengguna disarankan menggunakan kata sandi minimal 8 karakter dengan kombinasi huruf, angka, dan simbol.
   - Antarmuka menyediakan toggle visibilitas kata sandi interaktif (*Eye Toggle*) via Alpine.js untuk mempermudah pengecekan pengetikan tanpa menyimpan karakter pada papan klip (*clipboard*) perangkat bersama. Tombol aksi dirancang dengan warna Royal Blue kontras tinggi yang dilengkapi fallback gaya inline agar tidak terpengaruh siklus dev server.
   - Pembaruan kata sandi mewajibkan verifikasi kata sandi saat ini (*current password confirmation*).

3. **Prosedur Penghapusan Akun (Zona Bahaya):**
   - Penghapusan akun bersifat permanen dan mewajibkan verifikasi kata sandi aktif melalui modal dialog konfirmasi.
   - Seluruh log aktivitas historis, tanda tangan elektronik pada dokumen WRI, dan riwayat mutasi stok gudang tetap dipertahankan (*preserved audit log trail*) dan tidak dihapus secara berantai, menjamin kesinambungan data fisik pabrik.

---

## 17. Prosedur Migrasi & Sinkronisasi Data Work Request Historis (Spreadsheet ke MySQL)

1. **Jaminan Nol Perubahan Stok (Zero Stock Disturbance):** Seluruh data tiket WR dan riwayat proses dari spreadsheet lama (`System WR lama/Spreadsheet/Form Work Request - Log WR.csv`) diimpor murni sebagai data pencatatan alur pengerjaan dan TIDAK BOLEH memicu penambahan atau pengurangan stok inventori gudang (`components.total_stock` dan tabel `transactions`).
2. **Kelanjutan Tiket In-Progress (159 Tiket):** Tiket berstatus `Belum Verifikasi`, `Dalam antrian`, `Proses In`, `Siap diambil`, dan `Menuju Pengaju` langsung aktif di stasiun mesin dan modul Material Handling dengan routing mesin (`work_request_steps`) terpasang otomatis.
3. **Penyimpanan Foto di Server NAS On-Premise:** 491 berkas foto fisik dari Google Drive lama diunduh dan disimpan langsung ke Drive Master NAS `Z:\...` dan `public/uploads/components/` dengan format penamaan standar (`[part_number].jpg` untuk part resmi, dan `NEW-YYYYMMDD-SEQ.jpg` untuk part baru).
4. **Dokumentasi & Prosedur Teknis Lengkap:** Rincian teknis, pemetaan skema, dan panduan perintah CLI tercatat di [docs/MIGRASI_LOG_WR_SPREADSHEET.md](docs/MIGRASI_LOG_WR_SPREADSHEET.md).
5. **Penyelarasan Aksi Scan Diterima & Implied Approval (Update 18 Nov 2026):**
   - Tiket yang telah menyelesaikan siklus fabrikasi mesin hingga tahap akhir wajib dijemput oleh Material Handling (`Menuju Pengaju`), lalu otomatis menampilkan tombol aksi hijau **`Scan Diterima (X pcs)`** bagi petugas Gudang (Pengaju) sesuai Alur Wajib Logistik MH (Guardrail 4). Barikade sistem secara ketat menolak penerimaan gudang langsung dari mesin jika barang belum dijemput kurir MH.
   - Tiket dengan aktivitas pengerjaan fisik aktif (`quantity_produced > 0`) atau telah selesai dikerjakan tidak terkunci oleh badge "Menunggu Penerima" (penerapan *implied operational approval* dari alur lama).
   - Otorisasi dan pembagian peran tetap terisolasi: stasiun mesin tidak dapat mengambil aksi terima barang, dan penjemputan Material Handling antar-stasiun mesin tetap terintegrasi secara runtut.
6. **SOP Pemutakhiran Data CSV Berkala & Production Cutover (Runbook Bab 12):**
   - Apabila di masa mendatang berkas CSV dimutakhirkan dengan data transaksi pabrik yang lebih baru, proses sinkronisasi wajib mengikuti panduan langkah-demi-langkah pada [Bab 12 MIGRASI_LOG_WR_SPREADSHEET.md](docs/MIGRASI_LOG_WR_SPREADSHEET.md#12-panduan-operasional-resmi-pemutakhiran-csv--migrasi-ulang-di-masa-depan-production-cutover--sync-runbook).
   - Urutan eksekusi: Cek format berkas $\rightarrow$ Simulasi Dry-Run (`php artisan wr:migrate-legacy-spreadsheet --sync --dry-run`) $\rightarrow$ Eksekusi Aktual (`php artisan wr:migrate-legacy-spreadsheet --sync`) $\rightarrow$ Unduh Foto Baru (`php artisan wr:download-legacy-photos`) $\rightarrow$ Uji Otomatis (`php artisan test --filter=LegacyWrMigrationTest`) $\rightarrow$ Verifikasi Visual Antarmuka.

---

## 18. Standar Interaksi Pengguna & Protokol Notifikasi Pabrik (3 Pola Interaksi)

Guna menjamin kelancaran kerja operator di lantai pabrik dan menghindari kelelahan mental akibat pop-up berlebih (*modal fatigue*), sistem membagi interaksi pengguna ke dalam 3 pola ergonomis:

1. **Pola 1 — Pop-Up Konfirmasi Sebelum Aksi (*Confirmation Modal*):**
   - **Karakter:** Mengunci layar (*blocking overlay*) dengan tombol `[Batal]` vs `[Ya, Lanjutkan]`.
   - **Titik Penggunaan:** Khusus untuk aksi berisiko, destruktif, atau tidak dapat dibatalkan (*irreversible*):
     - Keluar Sistem (*Logout*).
     - Pengosongan keranjang permintaan label QR & keranjang pesanan Safety Stock WRI.
     - Penolakan tiket WRI (*Reject Approval*).
     - Persetujuan rekonsiliasi hasil stok opname fisik (*Cycle Count Reconcile*).
     - Eksekusi pemusnahan barang rusak (*Complete Disposal*).
     - Penghapusan akun pengguna atau data master (kategori/satuan).
     - Pengembalian tata letak denah rak & pallet ke standar (*Reset Warehouse Layout*).
   - **Tujuan:** Mencegah salah klik (*accidental click*) operator di perangkat sentuh/ponsel.

2. **Pola 2 — Floating Glass Toast Notification (*Reactive Process Feedback*):**
   - **Karakter:** Notifikasi melayang di kanan atas layar desktop / pill melayang di ponsel dengan latar kaca semi-transparan (*5. Elegant Glass*).
   - **Fitur:** Otomatis menghilang dalam 3.5–5.5 detik (*countdown timer*), dapat dijeda saat kursor diarahkan (*pause on hover*), memiliki tombol tutup cepat `[X]`, dan **tidak memblokir layar**.
   - **Titik Penggunaan:** Untuk 90% proses rutin operasional:
     - Update progress tahapan mesin (`In` / `Out` / Qty / Defect).
     - Penugasan operator mesin dan approval bertahap.
     - Penjemputan material oleh kurir Material Handling (*MH Pickup*).
     - Pembaruan informasi profil akun & kata sandi.
     - Transaksi keluar/masuk inventori standar.
   - **Tujuan:** Memberikan kepastian data tersimpan tanpa menghambat ritme kerja operator.

3. **Pola 3 — Milestone Success Pop-Up (*Ringkasan Bukti & Tombol Lanjutan*):**
   - **Karakter:** Pop-up dialog tengah layar yang memuat kartu ringkasan bukti hasil dan tombol aksi cepat lanjutan.
   - **Titik Penggunaan (3 Pencapaian Besar):**
     - **Penerimaan Akhir di Rak Gudang (WRI Final Receive):** Menampilkan nama part, jumlah diterima, slot rak tujuan, serta tombol pintas `[ 🗺️ Lihat di Peta Rak ]`.
     - **Penerbitan Tiket WRI Baru:** Menampilkan nomor tiket, part, kuota, serta tombol pintas `[ Buka Detail Tiket ]` atau cetak.
     - **Persetujuan Rekonsiliasi Stok Opname:** Menampilkan nomor lembar opname, zona, total item disesuaikan, dan status sinkronisasi stok.

---

## 19. Standar Autentikasi: Fitur "Ingat Saya" & Prosedur Pemulihan Kata Sandi Akun Pabrik

Guna menjaga keamanan akun operasional dan kenyamanan pengguna di perangkat kerja pabrik bersama (*shared workstations*), sistem menerapkan tata kelola autentikasi berikut:

1. **Fitur "Ingat Saya" (Remember Me & Remember Username):**
   - **Mekanisme Simpan Username:** Ketika checkbox *"Ingat saya"* dicentang saat submit login, sistem menyimpan identitas pengguna (Username) pada media penyimpanan peramban lokal (`localStorage.setItem('hpk_remembered_username', ...)`).
   - **Auto-Fill & Auto-Focus:** Saat pengguna membuka kembali halaman login (baik setelah sesi kedaluwarsa maupun setelah klik *Logout*), kolom username otomatis terisi dan kursor otomatis difokuskan ke kolom kata sandi.
   - **Penghapusan Simpanan:** Jika pengguna melakukan login tanpa mencentang *"Ingat saya"*, riwayat username tersimpan di peramban otomatis dibersihkan demi privasi.
   - **Persistensi Sesi Backend:** Didukung oleh cookie sesi jangka panjang (`remember_token` di tabel `users`) sehingga sesi tetap aktif apabila peramban/tab ditutup tanpa menekan tombol *Logout*.

2. **Prosedur Pemulihan Kata Sandi Akun Pabrik (Lupa Password via Admin / Opsi 2):**
   - **Alasan Kebijakan Keamanan:** Di area lantai bengkel manufaktur dan pergudangan, komputer sering digunakan bersama oleh banyak operator. Formulir penggantian kata sandi mandiri (*self-service*) tanpa otentikasi dua faktor (2FA) berisiko tinggi disalahgunakan oleh pihak lain untuk mengubah atau mengunci akun rekan kerjanya.
   - **Layar Bantuan Resmi Ramah Intranet Offline (`/forgot-password`):** Halaman lupa password dirancang khusus untuk lingkungan jaringan lokal intranet/offline pabrik HPK WMS. Mengeliminasi ketergantungan WhatsApp eksternal (`wa.me`) dan memusatkan alur pada tombol **1-Klik Salin Nomor Telepon (Offline Copy)** dengan animasi interaktif, tombol panggilan langsung (`tel:`), serta informasi pos bantuan fisik di Ruang Admin Gudang (Lantai 1).
   - **Fitur 1-Klik Reset Password Administrator:** Pada menu Manajemen Pengguna (`/admin/users`), Administrator Gudang atau Supervisor memiliki tombol aksi cepat **"Reset Password"** (ikon kunci warna amber) untuk mengembalikan kata sandi akun pemohon ke standar default pabrik (`hpkgudang`).
   - **Pencatatan Audit Trail Resmi:** Seluruh eksekusi reset kata sandi oleh Administrator otomatis dicatat secara permanen di log aktivitas sistem (`audit_logs`) dengan label event `password_reset`, mencatat identitas Administrator pelaksana, akun sasaran, dan cap waktu presisi untuk transparansi kepatuhan audit.

---

## 19. Standar Otentikasi Murni Username & Manajemen Otorisasi Satu Pintu

1. **Otentikasi Murni Username (Eliminasi Total Email Pengguna):**
   - Mengingat seluruh operator dan staf lantai pabrik beroperasi di jaringan lokal intranet tanpa kewajiban memiliki akun email perusahaan, sistem beroperasi 100% menggunakan identitas akun tunggal: **Username** dan **Password**.
   - Kolom `email` dan `email_verified_at` telah dilepas sepenuhnya dari tabel `users` database operasional melalui migrasi aman tanpa regresi data.
   - Seluruh alur login, pembuatan akun baru, pengeditan pengguna, dan pengaturan profil berfokus murni pada username dan kata sandi.

2. **Manajemen Pengguna & Otorisasi Satu Pintu (Eliminasi Kartu Ganda):**
   - Menghapus redundansi kartu "Otorisasi & Izin" dari Dashboard Administrator yang sebelumnya mengarah ke destinasi yang sama dengan "Manajemen User".
   - Mengintegrasikan seluruh pengaturan hak akses 64 izin modul (Spatie Permission) ke dalam menu terpusat **"Manajemen Pengguna & Otorisasi"** (`/admin/users`).
   - Setiap pengguna dapat diberikan hak akses presisi melalui matriks checklist hierarki modul yang didukung oleh preset instan per peran (*Supervisor, Admin Gudang, Staff, Operator*) dan bagian kerja operasional (*Gudang, Mesin Center, Material Handling*).

---

## 20. Fitur Fleksibilitas Alur / Edit Routing Tiket Work Request Komponen Baru Sebelum Diproses

Guna mengakomodasi kebutuhan pengerjaan pesanan pabrik (*Work Request Inhouse*) yang menggunakan **komponen baru** (belum terdaftar pada katalog master data resmi), sistem menyediakan fitur pengubahan alur/routing yang fleksibel:

1. **Latar Belakang & Tantangan Komponen Baru:**
   - Komponen baru / non-master yang dibuat melalui form WR (*Part Belum Ada di Master*) sering kali belum memiliki kepastian urutan stasiun mesin yang baku.
   - Tanpa fitur ini, kesalahan penentuan stasiun awal memaksa pengguna membatalkan (*reject*) tiket dan menginput ulang nomor tiket dari awal.

2. **Tombol Aksi Cepat Ikon Pensil pada Tabel WR:**
   - Pada kolom antrean **"Routing / Dimensi / Qty"**, tepat di samping teks stasiun routing (misal: `Routing : Dept M1`), tersedia tombol pensil interaktif warna amber.
   - Tombol ini membuka pop-up modal **"Ubah Alur / Routing Mesin"** yang menampilkan identitas part, status badge *"Part Baru (Non-Master)"*, dan formulir penyusun alur multi-tahap berantai.

3. **Guardrail Keamanan Mutlak (*Zero Risk Gatekeeper*):**
   - Tombol pensil dan akses ubah routing **HANYA AKTIF** jika tiket memenuhi syarat kumulatif:
     - Belum final (`is_final == false`),
     - Pengerjaan fisik belum dimulai sama sekali (`quantity_produced == 0` dan `quantity_received == 0`),
     - Belum masuk ke status pengerjaan mesin aktif (*in_progress*, *out*, atau *handover MH*),
     - Belum ada log produksi aktif.
   - Begitu operator mesin memulai pengerjaan fisik (`quantity_produced > 0`), alur terkunci permanen demi integritas pelacakan batch dan mutasi stok.

4. **Reset Otomatis Otorisasi Stasiun Penerima Baru (*Smart Approval Reset*):**
   - Jika stasiun penerima pertama dialihkan (misalnya dari `Dept M1` ke `Dept Laser Cutting`), sistem **secara otomatis me-reset status `approval_penerima` ke 'Menunggu'** serta mengosongkan penugasan operator (`assigned_operator_name = null`).
   - Langkah ini menjamin Kepala Mesin di stasiun yang baru memiliki wewenang penuh untuk memverifikasi kapasitas stasiunnya dan menugaskan operatornya sendiri.

5. **Pencatatan Audit Trail Resmi:**
   - Setiap kali terjadi pengubahan alur routing, sistem mencatat riwayat secara permanen pada `work_request_logs` (event `routing_update`) dan `activity_log`, mencatat alur lama, alur baru, nama operator pelaksana, dan alasan penyesuaian untuk kepatuhan audit pabrik.

---

## 21. Standardisasi Desain Visual Kolom Status Antrean Work Request (STS Penerima)

Guna memastikan konsistensi antarmuka pengguna (*design system & visual hierarchy*), format badge status pada kolom antrean Work Request diseragamkan:
1. **Penyeragaman Bentuk Horizontal Pill:**
   - Seluruh status persetujuan pada kolom **STS PENERIMA** (`Menunggu Pengaju`, `Belum Verifikasi`, dan `Batal`) menggunakan ukuran, padding (`px-2 py-1`), sudut kelengkungan (`rounded-lg`), ukuran font (`text-[11px] font-bold`), dan sifat baris tunggal (`whitespace-nowrap`) yang seragam 100%.
2. **Pencegahan Teks Terlipat (*Anti-Wrap*):**
   - Menghilangkan tampilan kotak bertumpuk 2 baris pada status *"Menunggu Pengaju"* sehingga seluruh badge dalam tabel tampil sejajar, bersih, dan profesional.

---

## 22. Sistem Keranjang (Cart / List) & Pendaftaran Komponen Baru pada Pembuatan Work Request

Guna mempercepat efisiensi operator dan staf gudang saat menerbitkan pesanan fabrikasi multi-item ke stasiun mesin center:
1. **Mekanisme Keranjang Pemesanan (*Cart / List System*):**
   - Pengguna dapat menambahkan beberapa komponen sekaligus ke dalam daftar **Daftar Item Siap Kirim (X)** sebelum menekan tombol utama **`Kirim Work Request`**.
   - Menghilangkan kebutuhan bolak-balik mengisi dan submit tiket satu per satu.
2. **Kombinasi Pemindai Barcode/QR & Input Komponen Baru (Non-Master):**
   - **Tombol Scan Suffix Navy (`[ :: Scan ]`):** Terhubung ke kamera perangkat menggunakan pustaka `html5-qrcode` untuk pencarian instan part katalog melalui kode QR/Barcode etiket.
   - **Inline Card "INPUT KOMPONEN BARU":** Memungkinkan pendaftaran part non-master secara langsung tanpa berpindah layar (*on-the-fly*), mencakup Nama, Tipe Material (Plat Hitam, Stainless, ST52, Hardox, SM490, dll), Dimensi (P cm, L cm, T mm), dan Foto Komponen via AJAX `/api/components/quick-store`. Part yang didaftarkan berstatus `source = 'wr_adhoc'` yang terisolasi total dari katalog Master Komponen. Penambahan komponen katalog master resmi HPK mutlak harus melalui modul Master Komponen.
3. **Penerbitan Massal Multi-Routing Transaksional:**
   - Melalui `WorkRequestController::bulkStore`, seluruh item di keranjang diproses dalam satu transaksi basis data aman (`DB::transaction`).
   - Setiap item otomatis dibuatkan nomor tiket resmi 6-segmen berurutan, baris tahapan mesin (`WorkRequestStep`) untuk alur multi-stasiun, serta log audit penerbitan tiket.

---

## 23. Validasi Scan QR Operasional Fisik & Modul Pengaturan Operasional Dinamis (BR-19)

Guna memastikan disiplin operasional di lantai pabrik dan mencegah kesalahan eksekusi fisik mesin maupun penjemputan material handling:
1. **Diferensiasi Alur Approval Administratif vs Operasional Fisik:**
   - **Tahap Persetujuan (Approval):** Persetujuan Ka. Pengaju dan penugasan operator oleh Ka. Penerima tetap menggunakan mekanisme tombol klik manual tanpa kewajiban pemindaian.
   - **Tahap Operasional Fisik:** Mulai dari pengerjaan mesin (`Proses ... (In)`), penyelesaian pengerjaan (`Proses ... (Out)`), serah terima material handling (`MH Pickup ...` & `Menuju Pengaju`), hingga penerimaan akhir di gudang (`Sudah Diambil`) terintegrasi dengan validasi scan lembar fisik WRI.
2. **Modul Pengaturan Operasional Administrator (`/admin/operational-settings`):**
   - Panel Administrator memiliki sub-menu dedicated **"Pengaturan Operasional"** yang dapat diakses melalui Sidebar Navigasi Admin (`admin.operational-settings.index`).
   - Menyediakan sakelar toggle fleksibel (berbasis iOS toggle switch) untuk mengatur parameter sistem `wr_scan_required` (default saat pertama rilis: **NONAKTIF / OFF**).
   - Menggunakan mekanisme cache performa tinggi (`Cache::remember` 60 detik) dengan pembersihan instan (`clearSettingCache`) setiap kali administrator menyimpan perubahan.
3. **Mekanisme Kerja Antarmuka saat Fitur Aktif (ON) - Pure Physical Scanning Only:**
   - **Eliminasi Total Pengetikan Manual (Anti-Bypass):** Antarmuka verifikasi tidak menyediakan kolom input teks atau tombol "Cek". Operator mutlak dilarang mengetik nomor WRI secara manual di keyboard guna menjamin integritas kehadiran fisik barang/lembar kerja di stasiun terkait.
   - **Dua Saluran Pemindaian Resmi:**
     1. **Kamera Live Perangkat (`html5-qrcode`):** Tombol utama besar *"Buka Kamera Pemindai QR"* untuk membaca QR code lembar kerja secara instan via kamera HP, tablet, atau laptop.
     2. **Alat Pemindai Barcode Gun Fisik (*Hardware Burst Listener*):** Listener otomatis mendeteksi semburan data berkecepatan tinggi khas alat scanner hardware (< 60ms/karakter). Jika dicoba diketik lambat menggunakan keyboard biasa, sistem secara otomatis menolak dan memblokir input tersebut (*"Pengetikan manual dilarang!"*).
   - **Proteksi Penguncian Tombol (*Action Button Lock*):** Tombol aksi proses terkunci (`disabled`) berstatus *"Pindai QR Lembar WRI Dahulu"* hingga kode QR lembar kerja tiket terverifikasi valid dan cocok dengan nomor WRI tiket atau part number terkait.
   - **Verifikasi Ganda Sisi Server (Backend Validation):** `WorkRequestController::processAction` memvalidasi input `scanned_wri_code` sebelum transaksi basis data dieksekusi, menolak mutasi status secara ketat jika kode QR fisik tidak dipindai saat konfigurasi berstatus aktif.
4. **Mekanisme Kerja saat Fitur Nonaktif (OFF):**
   - Seluruh proses berjalan seperti sedia kala (mode manual responsif), tombol aksi dapat langsung diklik tanpa jeda pemindaian QR.
5. **Integrasi Pemindai QR di Material Handling (`Verifikasi Ambil MH`) & Pratinjau QR di Pop-up Next Proses:**
   - Pada menu **Material Handling (`/work-station-supplies`)**, modal **"Verifikasi Ambil MH"** mengikuti pengaturan `wr_scan_required` secara ketat (seragam dengan `process_modal.blade.php`):
     - Saat `wr_scan_required` **ON (AKTIF)**: panel **Wajib Pindai QR Tiket Fisik** muncul (**Kamera HP/Laptop via `html5-qrcode`**, **Hardware Gun Scanner**, serta fallback **Foto / Pilih Gambar QR** via `scanFile`), tombol *Konfirmasi Ambil Material* terkunci hingga QR valid terverifikasi, dan field `scanned_wri_code` dikirim ke backend `quickAction`.
     - Saat `wr_scan_required` **OFF (NONAKTIF)**: panel pemindai QR **disembunyikan penuh** sehingga modal kembali ringkas dalam mode klik tombol bebas.
   - Pop-up **"Next Proses"** pada daftar WRI (`process_modal.blade.php`) maupun halaman detail (`work_requests/show.blade.php`) menyertakan thumbnail **QR Code WRI** yang dapat diklik untuk membuka tampilan perbesar (`qr_zoom_modal.blade.php`).

---

## 24. Standar Interaksi & Animasi Enterprise Modern (GSAP, ScrollTrigger, Lenis & Motion)

Guna menghadirkan pengalaman visual kelas dunia (*visual delight*) tanpa mengurangi keandalan dan kecepatan operasional pabrik:
1. **Arsitektur "Enterprise-Safe Hybrid Mode":**
   - **Lenis Smooth Scroll:** Memberikan efek pergerakan gulir halus (*momentum scroll*) yang nyaman di desktop kantor (`lerp: 0.08`, `wheelMultiplier: 0.95`).
   - **Isolasi Penuh Modal & Tabel Data (`data-lenis-prevent`):**
     - Seluruh tabel transaksi antrean tiket (`#wri-table-container`, `.table-responsive`) dan modal dialog pop-up (`process_modal`, `process_camera_modal`, `role="dialog"`) secara otomatis diproteksi menggunakan atribut `data-lenis-prevent="true"`.
     - Operator gudang dan mesin tetap mendapatkan scrolling instan presisi 100% tanpa efek "licin/tergelincir" (*scroll fatigue*).
   - **Nonaktif Otomatis pada Layar Sentuh Lapangan (Touchscreen):**
     - Perangkat mobile (smartphone/tablet HPK) otomatis dibebaskan dari Lenis untuk menghemat baterai dan menggunakan native touch physics OS.
2. **GSAP + ScrollTrigger (Reveal & Stagger):**
   - **Title Word Reveal:** Judul utama halaman (`.gsap-reveal-title`) muncul meluncur halus per kata dengan durasi cepat (< 0.45 detik) dan bersifat sekali jalan (`once: true`) agar informasi judul tiket dan halaman langsung terbaca jelas tanpa menghambat pembacaan.
   - **Staggered Cards Entrance:** Kartu modul dan KPI (`.gsap-stagger-cards`) muncul mengalir rapi secara berurutan (*staggered fade-up*).
   - **Animated Metric Counters:** Angka metrik ringkasan (`.counter-value`) berputar dinamis dari 0 hingga nilai riil saat masuk viewport.
3. **Motion (Spring Micro-Interactions) & Akselerasi Hardware GPU:**
   - Mikro-interaksi hover pada kartu menggunakan kurva pegas organik (`.hover-spring` cubic-bezier GPU-accelerated) dengan performa stabil 60 FPS tanpa membebani thread utama CPU.
4. **Siklus Hidup Aman Hotwire Turbo:**
   - Inisialisasi dijalankan pada event `DOMContentLoaded` dan `turbo:load`.
   - Seluruh listener, RAF ticker, dan ScrollTrigger instance dibersihkan secara instan pada `turbo:before-render` dan `turbo:before-visit` guna menjamin tidak adanya kebocoran memori (*zero memory leak*).

---

## 25. Standardisasi Hierarki Status Otorisasi Work Request (Antara Pengaju & Penerima)

Guna memastikan kejelasan informasi dan menghindari kesalahpahaman operasional di antarmuka tabel antrean:
1. **Tahap Verifikasi Penerima Sebelum Pengerjaan Mesin:**
   - Saat tiket telah disetujui oleh Kepala Bagian Pengaju (`approval_pengaju = 'Diterima'`), namun belum diverifikasi oleh Kepala Bagian Penerima (`approval_penerima = 'Menunggu'`), lencana pada kolom **NO WR / STATUS** menampilkan status:
     **`Menunggu Approval Penerima`** (dengan ikon jam tunggu `fa-regular fa-clock` bertema amber).
   - Tiket **DILARANG KERAS** menampilkan lencana `Proses Line ...` dengan ikon gear berputar sebelum Kepala Departemen Penerima menyetujui kesanggupan mesin dan menetapkan operator pelaksana.
2. **Otorisasi Penugasan Operator:**
   - Kepala Bagian Penerima (Ka. Mesin) memiliki tombol aksi cepat `[ 👤 Tugaskan ]` pada kolom STS Penerima atau dapat mengklik langsung lencana tunggu status untuk membuka pop-up penugasan operator.
3. **Peralihan Resmi ke Status Mesin:**
   - Begitu operator pelaksana ditugaskan (`approval_penerima = 'Diterima'`), barulah lencana status secara resmi beralih ke tahapan pengerjaan mesin aktif (`Mulai: Dept ... (In)` atau `Proses: Dept ...`).





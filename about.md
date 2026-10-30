# HPK Gudang Inhouse - Project Analysis

## Overview
**HPK Gudang Inhouse** is a custom Warehouse Management System (WMS) built specifically for internal use. The application tracks inventory, manages warehouse locations (specifically "HPK 1-Building Warehouse" with Zones A-F), handles engineering change requests (ECR), material disposals, and QR-based inventory tracking.

## Technology Stack
- **Backend Framework:** Laravel 10 (PHP 8.1+)
- **Frontend:** Laravel Blade, Alpine.js, Tailwind CSS
- **Database:** MySQL (Database name: `hpk_gudang`)
- **Build Tooling:** Vite
- **Key Libraries:** `html5-qrcode` & `qrcode` (for QR scanning and generation capabilities), `simplesoftwareio/simple-qrcode`
- **Authentication & RBAC:** Laravel Breeze & Spatie Laravel Permission (`spatie/laravel-permission`)

## Core Features & Workflows

### 1. User Management, Departments & Roles
Users have specific roles and departmental scopes that dictate their permissions and workflows:
- **Roles:** `admin_gudang`, `operator`, `qc`, `engineering`, `supervisor`, `operator_mesin`, `material_handling`.
- **15 Standardized Departments:** Laser, M1, UNP, Sharing, Band Saw, Aksesoris, Press, Hydraulic, CNC, Sharing & Bending, Gudang, SPO, Robot, Uji 1, Uji 2.
- **Dynamic Access Control:** Users only see navigation menus and dashboard modules that they are authorized to access. Machine center operators default strictly to Work Request, while logistics operators default strictly to Material Handling.

### 2. Location Management (Peta Gudang)
- Manages physical warehouse mapping (specifically handling Zones A through F).
- Tracks locations with high granularity (Aisle, Rack Number, Bin Level) and capacity limits.

### 3. Master Data Komponen (Component Management)
- Manages parts and materials categorized by types (e.g., `hydraulic`, `raw_material`, `fastener`, `accessories`, `electrical`, `chemical_paint`).
- Tracks specifications, Units of Measure (UOM), and minimum/maximum stock thresholds to prevent stockouts.
- Generates and stores QR Code payloads for easy physical tracking and labeling.
- **Unified & Streamlined Single-Row Toolbar:** Bilah pencarian, dropdown kategori, dan tombol filter yang diperkecil disejajarkan dalam satu baris terpadu bersama tombol status (*Semua Part*, *Perlu Kategori*, *Perlu Alokasi Rak*, *Stok Kritis*), mengeliminasi tombol kategori redundan (*Subframe s/d SPO*) untuk menghemat ruang vertikal dan menyajikan tampilan yang ringkas dan bersih.
- **Penyimpanan Foto & Dokumen CAD Terpusat (Pure Local Mode):** Mendukung migrasi foto dari Google Drive ke penyimpanan direktori luring (*pure local on-premise*) terpusat di `D:\Ali Magang\KOMPONEN` melalui `ComponentPhotoStorageService` dengan opsi penonaktifan total fallback Google Drive (`COMPONENT_ENABLE_GDRIVE_FALLBACK=false`). Seluruh penambahan dan pembaruan komponen (Form Input, Edit, Quick Store WR, dan Impor Excel) secara otomatis menyimpan file langsung ke folder master `D:\` dengan penamaan standar `[part_number].[ext]` (menimpa revisi lama secara bersih). Mendukung dokumen gambar teknik CAD format `.pdf` serta gambar (`.jpg, .png, .webp, .svg`) hingga 15MB. Controller menyediakan gateway streaming `/components/{component}/photo-stream` dengan HTTP caching terisolasi, serta antarmuka stasiun kerja yang dilengkapi **Interactive Zoom Lightbox** dan **Native PDF Modal Viewer Fullscreen** (zoom 500%, multi-halaman CAD, dan cetak langsung) untuk membaca dimensi part secara detail. Dilengkapi pula perintah pembersihan aman `php artisan components:clean-dummies` untuk memurnikan database dari part seeder awal.

### 4. Inventory Transactions (Transaksi Komponen)
- Handles core inventory movements: `inbound`, `outbound`, `transfer`, and `return`.
- Tracks real-time stock balances tied to specific components and their locations within the warehouse.
- Supports linking transactions to SPK (Surat Perintah Kerja) numbers.

### 5. Engineering Change Requests (ECR)
- A structured workflow for proposing and approving changes to components (e.g., `spec_change`, `part_replacement`, `discontinue`).
- Engineers can request changes, define old/new specs, and set stock run-out policies (`run_out`, `immediate_scrap`, `rework`).
- Includes a strict approval workflow (Submitted -> Reviewed by QC -> Approved/Rejected).

### 6. Disposal & Scrap (Pengajuan Disposal / Afkir)
- Manages the process of discarding materials safely and accountably (`scrap_iron`, `damaged_part`, `expired_chemical`, `obsolete`).
- Tracks estimated weight (kg) and expected salvage value.
- Includes a multi-level approval workflow (QC, Manager) and supports attaching photo proofs of the condition.

### 7. QR Label Management (Permintaan Label QR)
- A dedicated queue/module to request and print QR/Barcode labels for parts, packaging boxes, and warehouse racks.
- **Default Queue Fokus ke "Menunggu Dicetak":** Saat pengguna membuka menu `/qr-requests` tanpa parameter, sistem secara otomatis mengaktifkan filter antrean **Menunggu Dicetak** (`status=pending`), mengarahkan perhatian petugas cetak langsung ke pesanan label yang belum terselesaikan. Riwayat lengkap tetap dapat diakses melalui tab *Semua Antrian* (`status=all`).
- **3 Kartu Indikator KPI Interaktif:** Panel ringkasan di atas antrean menyajikan metrik *real-time* untuk **Menunggu Dicetak** (Amber), **Selesai Dicetak** (Emerald), dan **Total Permintaan** (Cyan), yang sekaligus berfungsi sebagai jalan pintas filter 1-klik dengan indikator aktif berbingkai kontras.
- **Lencana Kuantitas (Count Badges) & Pencarian Terpadu:** Tab status dilengkapi badge jumlah dinamis dan bilah pencarian cepat terintegrasi untuk mencari nomor request (`request_number`), kode part (`part_number`), atau nama komponen secara instan.
- **Composite 3-in-1 QR Payload (`Nama Komponen ; Kode Komponen ; Kode Rak`):** Kode QR meng-encode nama part, part number, dan kode rak penyimpanan fisik secara dinamis, sehingga saat dipindai oleh pemindai/kamera apapun secara luring (*offline*), ketiga data vital langsung terbaca jelas tanpa perlu akses internet.
- **Multi-Token Scanner Parsing:** Endpoint pencarian API (`searchApi`) dan pemindai kamera kurir logistik (`scanLookup`) otomatis memilah delimiter titik koma (`;`) untuk mengekstraksi part number secara instan, dengan dukungan penuh terhadap format lama (`HPK-PART|...`).
- **High-Visibility Thermal & Sheet Print Layouts:** Desain cetak stiker Thermal (70mm x 45mm) dan kertas lembar A4 menyematkan kotak *RAK* berbingkai kontras tinggi (`.rack-badge`), part number monospace tebal, nama part, dan satuan (*UoM*).
- Supports printing for individual items (`item`), boxes (`box`), or entire warehouse racks (`rack`).
- Dedicated routes for different printer types (Thermal printers vs. standard Sheet printers).

### 8. Cycle Counting (Stok Opname)
- Reconciles system database quantities with actual physical quantities in the warehouse.
- Targets specific warehouse zones for organized, periodic counting.
- Tracks variances (System Qty vs. Physical Qty) and requires documented reasons for discrepancies, culminating in an official reconciliation approval process.

### 9. Work Request Inhouse (WRI) & Multi-Step Machine Routing
- Internal inter-departmental request system replacing legacy Google Sheets.
- **Auto-Routing:** Dynamically assigns the next processing machine/station based on component configuration (`process_route`).
- **Hybrid Smart Approval:** Requester operators require Section Head approval (`Menunggu` -> `Diterima`), while Supervisors, Admins, and Urgent Line Stops bypass approval automatically (`Diterima`).
- **Prerequisite Gate:** Receiving departments cannot view or process tickets until requester approval is `Diterima`.
- **Partial Processing & Multi-Batch Protection:** Supports completing and sending quantities in increments (`[Parsial] ...`) with cumulative quota tracking. Tickets remain fully open (`is_final = false`) and machine action buttons remain active for remaining balances until 100% of the requested order is delivered to the warehouse.
- **Three-Point Handover Verification & Reactive Defect Auto-Balance:** Kuantitas fisik diverifikasi interaktif pada 3 titik serah terima (**Mesin Out &rarr; MH Pickup &rarr; Gudang Terima**). Kolom kuantitas terhubung reaktif (*live bi-directional link*) dengan input defect: batas maksimal unit bagus otomatis terkunci `max = sedia - defect`. Jika ada komponen cacat atau hilang, kuota order tiket otomatis dipotong (`quantity_requested` berkurang), dicatat dalam audit trail lengkap (operator, tanggal, jam, alasan, nama stasiun), dan saldo pengerjaan mesin seketika berhenti (*stop*) saat seluruh bahan (`bagus + defect`) tuntas teralokasi.
- **Redesigned 9-Column Monitoring Queue & Photo Preview Modal:** Tabel desktop mengadopsi format 9 kolom presisi: No WR/Status (dengan tombol aksi status terintegrasi), Tanggal, Komponen (Nama & Kode), Status Pengaju (dengan dropdown aksi Diterima/Ditolak interaktif), Status Penerima (dengan dropdown aksi Diterima langsung pilih operator/Ditolak), Tombol Lihat Foto Pop-up (resolusi penuh), Routing/Dimensi/Qty terpadu, Deskripsi order/SPK, dan Pengaju/Penerima/Operator.
- **Hierarchical Pop-Up Detail Tracking & Dual Verification:** Modal detail WRI mengadopsi estetika hierarki bersih dengan QR card interaktif (dapat diklik untuk zoom pembesar scan barcode), No WR hitam bersih, kartu verifikasi ganda (Ka. Pengaju & Ka. Penerima beserta operator pelaksana), strip kuota monokrom elegan (Order Awal, Target Aktif, Diterima Gudang dalam warna hitam, Defect tetap merah mencolok), serta balok visual proses pengerjaan per stasiun urut kronologis (mulai bersih dari MASUK, Kuning Parsial, Merah Defect, dan Hijau Selesai).
- **Visual Separation of Logistics (MH) vs Warehouse Receiving:** Pada modal detail pelacakan, log perpindahan kurir Material Handling (*Menuju Pengaju*) ditandai dengan balok **Biru** dan ikon truk logistik (`fa-truck-ramp-box`), terpisah secara tegas dari konfirmasi penerimaan fisik rak gudang (*Sudah Diambil*) yang menggunakan balok **Hijau** dan ikon gudang (`fa-warehouse`).
- **Interactive Action Dropdown & Formal Rejection Modal:** Kolom *Sts Pengaju* dan *Sts Penerima* menyediakan dropdown seleksi (*Pilih Status... &rarr; Diterima / Ditolak*). Memilih Diterima pada Sts Penerima otomatis membuka pop-up penugasan operator mesin. Memilih Ditolak pada verifikator manapun membuka modal konfirmasi pembatalan tiket dengan input alasan penolakan wajib untuk audit trail.
- **Ergonomic & Minimalist Pop-Up Quantity Interface:** Pop-up kuantitas pengerjaan dan serah terima mengadopsi ukuran kompak (`max-w-md` ~420px) bebas scroll yang terinspirasi dari kesederhanaan sistem sebelumnya. Menampilkan hero touch-friendly stepper (`[-]`, `[+]`, direct input) dengan shortcut 1-klik `[Semua / Max: X pcs]`, drawer defect yang dapat dilipat (*collapsible smart toggle*), dan penguncian bersih untuk tahap `(In)`. Seluruh fungsi validasi parsial dan pengurangan defect backend tetap terjaga 100%.
- **Automatic Stock Balance Update:** Once a ticket achieves `Sudah Diambil` status, the physical warehouse rack balance (`stock_balances`) increments automatically (hanya unit bagus) and generates an official inbound `transactions` record. Final ticket closure occurs only when total received units match the order quota (`is_final = true`).
- **Searchable Combobox Component Picker & Live Filter:** Formulir `+ Membuat WR` dilengkapi input pencarian interaktif responsif untuk menyaring 627+ master komponen secara instan (nama part, kode, UoM, dimensi, stok fisik), mengeliminasi keterbatasan select dropdown polos.
- **Streamlined Minimalist WR Creation Form:** Antarmuka pembuatan tiket WR dibersihkan dari elemen teks panjang dan label visual berlebih: badge *"Anti-Manipulasi Sistem"*, badge kuning *"Terkunci"*, dan badge hijau *"Real-Time"* dieliminasi dengan menggantinya menggunakan field abu-abu readonly yang tenang. Teks pencarian komponen dibuat dengan bobot normal (*un-bold*) dan placeholder singkat, deskripsi alur mesin diringkas, pratinjau alur fabrikasi diseragamkan dengan kotak monokrom slate elegan tanpa warna-warni ramai, serta placeholder catatan instruksi fabrikasi dipersingkat.
- **Universal High-Visibility Back Navigation & 1-Click Queue Refresh:** Seluruh jendela sub-view (*create, edit, show*) dan **seluruh menu-menu utama (*Index Views*: WRI, Material Handling, Master Komponen, Transaksi, Peta Gudang, ECR, Disposal, Stok Opname, Cetak Label QR, dan Admin Area)** dilengkapi tombol navigasi kembali prominen `[ < Kembali ]` dengan border dan kontras tinggi yang memudahkan operator kembali ke Beranda/Dashboard utama dalam 1-klik tanpa mengandalkan navbar/sidebar. Seluruh jendela pop-up dialog (*detail modal, process modal, assign modal, reject modal, qr zoom modal, preview foto*) distandarisasi dengan tombol pembatalan/penutup `[ < Kembali / Tutup ]` atau `[ < Kembali / Batal ]`. Menu antrean Work Request (WRI) dan Material Handling Center (Mesin) dilengkapi tombol **Refresh** 1-klik (`fa-solid fa-arrows-rotate`) di header untuk memperbarui status antrean seketika tanpa perlu menekan F5.
- **Instant Response Optimization & Zero-Ghost-Click Protection:** Menghapus ketergantungan file `public/hot` (Vite WebSocket dev server) sehingga peramban berjalan 100% menggunakan aset terkompilasi statis bebas dari auto-reload misterius (`full-reload`). Menghilangkan race condition pada komponen dropdown navigasi atas agar tautan navigasi berpindah seketika tanpa terputus Alpine DOM destruction. Melindungi seluruh backdrop transisi modal (*detail, process, assign, reject, qr zoom, foto, sidebar*) dengan kelas responsif `:class="{ 'pointer-events-none': !isOpen }"` sehingga transisi memudar (fade-out) tidak lagi menelan klik pengguna berikutnya. Menyematkan indikator visual instan (*spinner & loading state*) pada form seleksi persetujuan WRI.
- **Integrated Fallback & On-The-Fly Quick Registration:** Jika part belum terdaftar di katalog master, pengguna langsung disajikan tombol pendaftaran cepat dengan nama part yang otomatis terisi (*pre-filled*). Modal pendaftaran mendukung pemilihan kategori part opsional (*subframe, karoseri, assy, aksesoris, dll.*) serta dilengkapi pelindung *zero-FOUC* ganda (`[x-cloak]` CSS global & inline style) untuk mencegah modal berkedip sekilas saat halaman dimuat.
- **SLA 3-Day Monitoring & Interactive Quick Filter:** Tiket yang melewati batas SLA 72 jam ditandai dengan badge peringatan merah prominen `[ ⚠️ X Tiket Melewati SLA > 72 Jam ]`. Badge ini berfungsi sebagai filter interaktif: jika diklik, sistem seketika menyortir antrean menampilkan hanya tiket terlambat dari yang tertunda paling lama (`created_at ASC`) untuk diprioritaskan, lengkap dengan tombol reset toggle 1-klik.
- **Unified 4-Status Lifecycle Tabs & Zero-Duplicate Control Bar:** Merampingkan antrean tiket dari baris bertumpuk ganda menjadi satu baris kontrol terpadu yang bebas redundansi. Tab status dikelompokkan menjadi 4 kategori operasional intuitif (+ Semua): **Semua**, **Perlu Verifikasi** (approval pengaju & penerima), **Sedang Proses** (antrian, pengerjaan mesin, transit MH antar mesin), **Siap Ambil** (barang jadi siap diantar atau menuju pengaju), dan **Selesai** (tuntas diterima pengaju/gudang). 5 kartu ringkasan KPI di atas tabel diselaraskan secara proporsional dan berfungsi sebagai jalan pintas filter interaktif.

### 10. Material Handling (MH) Command Center
- Dedicated logistics and internal transportation queue for physical movement of components between machine centers and the warehouse.
- **4 Tab Command Center:** `tasksPickup` (items waiting at machine stations), `tasksTransit` (items currently carried by couriers), `tasksDistribution` (items ready for handover), and `tasksHistory` (audit trail of completed transfers).
- **Interactive Verification & Defect Reporting:** Kurir MH dapat memverifikasi jumlah fisik aktual saat pickup serta melaporkan barang hilang/rusak sebelum transit.
- **Live Camera QR Scanning:** Integrates `html5-qrcode` to scan ticket/component barcodes directly from smartphone or tablet cameras for instantaneous verification.
- **Strict Role Gating:** Machine operators can only process their machine stage (`Dalam antrian` -> `Proses (In)` -> `Proses (Out)`), while only Material Handling personnel can pick up and distribute goods (`MH Pickup` -> `Siap Diambil` -> `Menuju Pengaju` -> `Sudah Diambil`).

### 11. Bulk Import & Manajemen Massal Komponen Karoseri (Excel 2-Sheet Streaming)
- **High-Volume Urgent Data Ingestion:** Memfasilitasi pendaftaran ratusan hingga ribuan katalog komponen sekaligus secara instan melalui impor berkas spreadsheet (`.xlsx` dan `.csv`).
- **Official 2-Sheet Corporate Template:** Disediakan generator template Excel resmi (`ComponentExcelTemplateService`) berbasis library streaming `OpenSpout` yang sangat efisien memori. Template memuat 2 sheet terintegrasi:
  1. `INPUT DATA KOMPONEN`: Lembar kerja formulir berheader Dark Navy HPK (`#0A2342`) dengan petunjuk pengisian dan 3 baris data sampel riil.
  2. `PEDOMAN & REFERENSI DROPDOWN`: Kamus data sistem yang dinamis, memuat daftar kategori part valid, satuan resmi HPK, daftar kode rak fisik gudang aktif (sinkron langsung dari database `locations`), serta panduan kolom wajib vs kolom opsional.
- **Flexible Column Tolerances & Intelligent Normalization:** Kolom wajib dibatasi hanya pada **Kode Komponen** dan **Nama Komponen**. Seluruh kolom lain (kategori, satuan, dimensi P x L x T, spesifikasi teknis, stok minimum, stok awal, kode rak, tautan foto) bersifat opsional dan otomatis dinormalisasi dengan fallback aman (`PCS` untuk satuan, `null` untuk kategori dan dimensi, `0` untuk stok).
- **Google Drive & URL Photo Ingestion:** Mendukung tautan foto dari Google Drive (berbagi publik) atau URL web umum. Sistem secara otomatis mengekstrak file ID Drive, mengunduh file ke penyimpanan lokal server (`public/uploads/components/`), dan menautkannya ke profil komponen tanpa membebani operator. Kolom foto juga dapat dikosongkan terlebih dahulu untuk input mendesak (komponen langsung aktif dengan QR Code otomatis).
- **Safe 2-Step Live Preview & Audit Reconciliation:**
  1. *Langkah 1 (Upload & Opsi Duplikat):* Operator mengunggah berkas Excel dan memilih kebijakan penanganan duplikat: **Lewati Duplikat (Skip)** untuk mempertahankan data lama, atau **Perbarui (Update)** untuk memperbarui spek/kategori/rak/stok dengan data terbaru Excel.
  2. *Langkah 2 (Live Preview Modal):* Sistem mem-parsing berkas di memori dan menyajikan tabel pratinjau interaktif dengan kartu indikator KPI (Total Baris, Komponen Baru, Duplikat Dilewati/Diupdate, Error) beserta badge status per baris.
  3. *Langkah 3 (Eksekusi Transaksi DB):* Operator mengonfirmasi simpan, seluruh data valid di-commit dalam satu transaksi database atomik (`DB::beginTransaction` / `commit` / `rollBack`), dan sistem menampilkan ringkasan audit hasil impor.

### 12. Role-Based Access Control (RBAC) & Dynamic Dashboard
- Implemented using Spatie Laravel Permission with granular abilities (`work-request.view`, `work-request.process`, `material-handling.view`, `material-handling.manage`, etc.).
- **Dynamic Beranda & Navigation:** Non-authorized modules are completely omitted from the UI, preventing unauthorized clicks or route access.
- **Intelligent Presets:** New or edited users assigned to Machine Center roles automatically receive Work Request permissions, while Material Handling roles receive logistics permissions.
- **Streamlined Minimalist Dashboard:** Beranda utama disederhanakan dengan mengeliminasi widget tracker mesin fabrikasi yang redundan, kotak grafis denah gedung 2D yang berat, dan antrean tugas panjang. Tata letak ditata ulang dengan hierarki informasi eksekutif yang logis: 4 kartu metrik KPI ringkas (*Total SKU*, *Stok Menipis Kritis*, *Transaksi Hari Ini*, *Menunggu Approval*) diletakkan langsung di bagian atas tepat di bawah hero wave banner dan di atas judul *Modul Aplikasi Pergudangan HPK*, diikuti 8 kartu modul navigasi utama dengan badge counter real-time. Hal ini menghemat 10+ query database per pemuatan halaman sehingga beranda terbuka secara instan.

### 13. Mobile Ergonomics & Responsive Smartphone Optimization (Layar Ramah HP)
- **Universal Zero-Collision Layout:** Seluruh halaman, modal dialog, formulir input, dan antrean kartu data dioptimasi untuk resolusi layar smartphone (360px – 430px) tanpa teks yang bertumpuk (*collision-free*).
- **Adaptive Touch-Friendly Header & Action Buttons:**
  - Tombol aksi header (*Import Excel*, *Tambah Part*, *Refresh*, *Scan QR*) disusun adaptif (`flex-wrap gap-2 w-full sm:w-auto`) dengan tinggi sentuhan ergonomis (`min-h-[42px]`) agar mudah ditekan satu tangan.
  - Kartu data mobile komponen menyematkan aksi 4-kolom simetris (`QR`, `Edit`, `Detail`, `Transaksi`) yang proporsional dan tidak berantakan.
- **Horizontal Swipe Protection for Data Tables:** Tabel pratinjau import data lebar (10 kolom) dilengkapi batas lebar minimum (`min-w-[720px]`) dan indikator visual geser samping, menjamin font dan badge status tetap terbaca jelas tanpa terhimpit sempit.
- **Full-Width Hero Modal Actions:** Tombol simpan/konfirmasi pada modal dialog dan formulir WRI otomatis melebar penuh (*full-width*) di layar HP sehingga mudah dijangkau jempol operator di lantai bengkel karoseri.

### 14. Official Work Request Triplicate Printing & Print Queue Management (Cetak WRI Resmi 3 Rangkap)
- **3 Syarat Mutlak Cetak (Gatekeeper):** Dokumen WRI hanya dapat dicetak apabila memenuhi 3 kriteria: Status Pengaju disetujui (`Diterima`), Status Penerima disetujui (`Diterima`), dan Operator pelaksana pengerjaan telah dipilih / ditugaskan (`assigned_operator_name`).
- **Format Cetak Resmi 3 Rangkap (Triplicate Slip in 1 A4 Page):** 1 nomor WRI dicetak menjadi 3 slip identik dalam 1 lembar kertas HVS A4 portrait dengan garis potong bergunting (`✂ Potong di sini`):
  1. *Lembar 1 (Pemohon / Putih):* Disimpan sebagai arsip departemen pengaju.
  2. *Lembar 2 (Mesin / Pelaksana / Kuning):* Dibawa bersama fisik benda kerja ke mesin fabrikasi.
  3. *Lembar 3 (Gudang / MH / Merah):* Bukti serah terima fisik material handling ke gudang.
- **Lokal & Offline QR Code (SVG Base64):** Pembangkitan kode QR lokal tanpa dependensi internet dan tanpa ekstensi PHP GD, menghasilkan kualitas cetak vektor tajam (*razor-sharp*) untuk pemindaian scanner barcode.
- **4 Kolom Otorisasi Tanda Tangan & Nama Jelas:** Menampilkan nama Pemohon, Ka. Bagian Pemohon, Penerima (Operator Pengerjaan), dan Ka. Bagian Penerima.
- **Audit Trail & Penanda Cetak Ulang:** Mendukung pencetakan ulang dengan label otomatis `[Cetak Ulang]`, penghitung `print_count`, dan pencatatan audit log `action_type: 'printed'`.
- **Antrean Cetak & Bulk Printing (`/work-requests/print-queue`):** Menu navigasi tab `[ 🖨️ Cetak Dokumen WR ]` menyediakan daftar tiket siap cetak, filter status (*Semua Siap Cetak*, *Belum Dicetak*, *Sudah Dicetak*), kotak centang multi-pilih, tombol *Cetak Masal Terpilih* multi-halaman, serta penggabungan kolom terpadu (*Aksi / Status*) untuk merampingkan lebar tabel.
- **Filter Rentang Waktu Fleksibel & Dropdown Mobile-Friendly:** Menyematkan dropdown filter waktu ringkas persis di samping kotak pencarian (bebas dari baris panjang yang memakan ruang layar ponsel). Menyediakan pilihan cepat (*Semua Waktu*, *Hari Ini*, *3 Hari Terakhir* sesuai batas SLA 72 jam, *7 Hari Terakhir*, *Bulan Ini*) serta pemilih rentang tanggal kustom (*Custom Date Range*) `start_date` & `end_date` yang tersinkronisasi otomatis dengan kartu ringkasan KPI dan formulir pencarian.
- **Otoritas Hak Akses Cetak (`work-request.print`):** Diberikan secara bawaan kepada Staf Gudang, Admin Gudang, Supervisor, dan Operator Mesin.

## Summary
The system is an enterprise-grade, unified Warehouse Management System (WMS) and Work Request (WRI) platform. It seamlessly connects raw material warehouse storage (Zones A-F), multi-station production machine routing (Laser, M1, UNP, CNC, etc.), internal logistics transit (Material Handling), quality engineering control (ECR & Disposal), and inventory reconciliation into a single cohesive, auditable, and QR-driven ecosystem.

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

## 3. Matriks Aturan Bisnis Resmi (BR-01 s/d BR-10)

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
| **BR-10** | **Pemantauan SLA 3 Hari (Red Alert)** | `scopeOverdueSla` di `WorkRequest.php`: `where('created_at', '<=', now()->subDays(3))`. | Tiket yang belum berstatus `Sudah Diambil` setelah melewati batas waktu 72 jam (3 hari) otomatis diberi label merah peringatan darurat (*Urgent SLA Alert*). |

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

---

## 5. Matriks Hak Akses & Pembagian Peran (RBAC)

Berdasarkan Spatie Permission dan konfigurasi antarmuka dinamis:

| Peran Pengguna | Akses Menu Beranda | Hak Otoritas Operasional | Presets Default Akun |
|:---|:---|:---|:---|
| **Admin Gudang** | Seluruh Modul (Peta Gudang, Master Komponen, Transaksi, ECR, Disposal, Cycle Count, WRI, MH, User Management) | Akses global penuh untuk memantau, mencetak massal, dan mengelola seluruh tiket antar departemen. | Hak Akses Lengkap |
| **Operator Mesin Center** (Laser, M1, UNP, CNC, dll) | **Khusus Modul Work Request** (Modul lain disembunyikan otomatis) | Mengajukan WR, memproses status mesin (`Dalam antrian`, `Proses (In)`, `Proses (Out)`), cetak form WR stasiunnya. | `work-request.view`, `work-request.process`, `work-request.print` |
| **Petugas Material Handling (MH)** | **Khusus Modul Material Handling** (Modul lain disembunyikan otomatis) | Membuka MH Command Center, scan QR kamera, update status penjemputan (`MH Pickup`), transit, dan serah terima (`Sudah Diambil`). | `material-handling.view`, `material-handling.manage`, `material-handling.process` |
| **Kepala Departemen / Supervisor** | Work Request, Monitoring SLA, Laporan, ECR Approval, Disposal Approval | Otorisasi persetujuan pengajuan bawahan (*Approval Pengaju/Penerima*), monitoring SLA 3 hari. | `wri.approve`, `wri.monitoring`, dll |
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

Pop-up modal input kuantitas pengerjaan dan serah terima (`process_modal.blade.php`) mengadopsi prinsip desain bersih (*clean industrial*), ergonomis, dan tidak bising (*non-cluttered*):
1. **Dimensi Kompak & Bebas Scroll (`max-w-md` ~420px):** Seluruh elemen aksi esensial muat dalam 1 layar tanpa mengharuskan operator/kurir menggulir layar (*scrolling*).
2. **Ikon Melingkar Berwarna Lembut (Soft Pastel Badge):** Mengindikasikan konteks proses (Teal untuk Mesin Masuk, Indigo untuk Mesin Keluar, Amber untuk MH Pickup/Antar, Emerald untuk Rak Gudang).
3. **Hero Stepper Touch-Friendly:** Dilengkapi tombol minus `[-]`, input angka besar font mono tebal di tengah, tombol plus `[+]`, serta tombol cepat `[Semua / Max: X pcs]` untuk mengisi kuantitas penuh secara instan tanpa mengetik manual.
4. **Smart Defect Drawer (Opsional & Dapat Dilipat):** Tombol toggle `[+ Ada Defect / Rusak? (Opsional)]` menyembunyikan form defect jika tidak ada reject (90%+ kondisi normal). Jika dibuka, menyediakan input kuantitas defect dan alasan secara kompak dengan kalkulasi otomatis pemotongan kuota.
5. **Penguncian Tahap Mesin Masuk `(In)`:** Kuantitas otomatis terkunci penuh dengan lencana gembok, memberi informasi jelas bahwa input kuantitas hasil dan defect dilakukan saat mesin selesai `(Out)`.
6. **Integritas Fungsi & Zero Regression:** Seluruh 7 parameter form (`action_type`, `target_status`, `operator_name`, `process_qty`, `defect_qty`, `defect_reason`, `notes`), route aksi, dan validasi database tetap berjalan 100% identik tanpa perubahan logika backend.

---

## 8. Alur Pemilihan & Pendaftaran Komponen Baru pada Formulir Membuat WR

Formulir pengajuan pesanan pembuatan part (`/work-requests/create`) menerapkan alur pemilihan dan pendaftaran on-the-fly yang mulus:
1. **Searchable Combobox (Pencarian Cepat 627+ Komponen):**
   - Pengaju mengetik nama part atau kode part pada kolom input interaktif.
   - Panel hasil pencarian secara instan menyaring daftar katalog komponen karoseri, menampilkan foto thumbnail, kode part, nama, kategori, dimensi (P x L x T), dan saldo stok fisik gudang.
   - Memilih part langsung menampilkan kartu identitas visual dan mengisi rekomendasi stasiun mesin fabrikasi (*Smart Suggest Routing*) secara otomatis.
2. **Fallback Pendaftaran Part Baru On-The-Fly:**
   - Apabila part yang diketik belum terdaftar di sistem (*Zero Results*), sistem secara otomatis menampilkan kartu informasi ramah dengan tombol `[+ Daftarkan Part Baru Ini]`.
   - Nama part yang diketik otomatis diisikan (*pre-filled*) ke dalam form modal registrasi tanpa mengharuskan pengguna mengetik ulang.
   - Form modal menyediakan pilihan **Kategori Part (Opsional)** dengan default aman (*Subframe / Karoseri*) dan input dimensi teknis.
   - Penyimpanan via AJAX seketika menambahkan komponen ke katalog master dan langsung memilihnya di formulir WR tanpa me-refresh halaman.
3. **Standarisasi Zero-FOUC (Bebas Kedipan Modal):**
   - Seluruh modal dilindungi oleh deklarasi CSS global `[x-cloak] { display: none !important; }` dan inline style `style="display: none;"` untuk menjamin tidak ada modal yang muncul sekilas saat halaman pertama kali dibuka.

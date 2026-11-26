# HPK WMS - RESUME OPERASIONAL & ROADMAP PENGEMBANGAN
**Terakhir Diperbarui:** 23 Oktober 2026

Dokumen ini adalah *checkpoint* resmi agar AI Agent / Developer di sesi berikutnya langsung memahami konteks proyek secara 100% utuh tanpa ada informasi yang hilang.

---

## 1. Status Proyek Saat Ini (Sudah Selesai, Disetujui & Teruji)

### A. Work Request Inhouse (WRI) & Material Handling (MH)
- **Multi-Routing Manufaktur:** `Dalam antrian` -> `Proses (In)` -> `Proses (Out)` -> `MH Pickup` -> `Siap Diambil` -> `Menuju Pengaju` -> `Sudah Diambil`.
- **Pengerjaan & Pengiriman Parsial (Cicilan):** Label otomatis `Parsial > ...` dan tracking kuota akumulatif per tiket.
- **Koreksi Defect / Reject:** Pemotongan otomatis kuantitas order tiket & pencatatan alasan defect di audit log.
- **Auto-Update Saldo Fisik Gudang:** Saat status `Sudah Diambil`, stok di rak tujuan otomatis bertambah (`stock_balances`) & transaksi inbound resmi diterbitkan (BR-08).
- **Material Handling Command Center:** 4 Tab (`tasksPickup`, `tasksTransit`, `tasksDistribution`, `tasksHistory`) + Live Camera QR Scanner.
- **SLA 3 Hari (Red Alert):** Scope `scopeOverdueSla` otomatis memberi tanda darurat untuk tiket > 72 jam (BR-10).

### B. Otoritas Hak Akses (RBAC) & Isolasi Peran (Baru Selesai & Teruji 100%)
- **Middleware Proteksi Route:** Seluruh route pada `routes/web.php` telah diproteksi dengan middleware `can:...` (Work Request, Material Handling, ECR, Disposal, Cycle Count, Users).
- **Tampilan Beranda Dinamis (`dashboard.blade.php`):** Modul kartu yang tidak diizinkan untuk pengguna login otomatis disembunyikan.
- **Navigasi & Sidebar Dinamis (`navigation.blade.php` & `app.blade.php`):** Hanya tautan menu berizin yang tampil di desktop & mobile bar.
- **Pemisahan Aksi Ketat (Mesin Center vs MH):** 
  - Operator Mesin Center hanya berhak memproses tahapan mesin (`Dalam antrian`, `Proses (In)`, `Proses (Out)`).
  - Kurir MH hanya berhak memproses logistik (`MH Pickup`, `Siap Diambil`, `Menuju Pengaju`, `Sudah Diambil`).
  - Divalidasi di model `WorkRequest.php` (`canUserProcessAction` & `getAvailableActionsForUser`) dan Blade.
- **Default Presets Akun Baru (`UserController.php`):**
  - Akun yang ditetapkan sebagai Mesin Center otomatis didefaultkan HANYA memiliki izin Work Request.
  - Akun yang ditetapkan sebagai Material Handling otomatis didefaultkan HANYA memiliki izin Material Handling.
- **Cache Invalidation Otomatis:** Pembaruan akun dan role langsung menjalankan `forgetCachedPermissions()`.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/WorkRequestAuthorizationTest.php`).

### C. Verifikasi Kuantitas 3 Titik Serah Terima & Defect Presisi (Baru Selesai & Teruji 100%)
- **Input Kuantitas Interaktif di 3 Titik:**
  - Mesin Out: Operator mesin verifikasi unit selesai & input defect mesin.
  - MH Pickup: Mengganti hidden field dengan input angka interaktif kurir MH + opsi defect/barang hilang.
  - Gudang Penerima (Sudah Diambil): Mengganti hidden field dengan input angka interaktif staf gudang + opsi defect transit.
- **Koreksi Saldo Mesin Tanpa Celah:** `saldoOut = max(0, totalIn - totalOut - stepMachineDefects)` dan `waitingPickup = max(0, totalOut - totalPickup - pickupDefects)`. Tidak ada lagi aksi sisa hantu setelah defect dilaporkan.
- **Penguncian Batas Atas (Anti Over-Processing):** Kuantitas yang diproses/diambil dikunci secara ketat di backend dan frontend agar tidak pernah melebihi sisa pesanan aktif.
- **Audit Trail Defect Transparan:** Mencatat Nama Operator, Departemen, Tanggal & Jam Presisi (`d/m/Y H:i`), serta Alasan Defect di log audit dan modal detail.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/WorkRequestDefectAndHandoverTest.php` - 5 passed, 19 assertions).

### D. Siklus Parsial Multi-Batch & Pencegahan Penutupan Prematur (Baru Selesai & Teruji 100%)
- **Pembersihan Jebakan String Blade:** Menghapus pengecekan `stripos(status, 'Sudah Diambil') === false` pada `index.blade.php`, `show.blade.php`, dan `detail_modal.blade.php`. Penutupan tiket murni dikontrol oleh boolean `$workRequest->is_final`.
- **Akses Tombol Pengerjaan Sisa Mesin:** Ketika Gudang menerima 50 pcs parsial dari pesanan 100 pcs, tiket tetap berstatus aktif (`is_final = false`). Tombol aksi pengerjaan sisa 50 pcs untuk Mesin tetap muncul dan dapat dieksekusi secara instan.
- **Penyempurnaan `getNextStatusLabel()`:** Ketika tiket parsial belum final, sistem otomatis memeriksa sisa saldo pengerjaan mesin yang belum selesai (`saldo_out > 0`) sehingga rekomendasi alur tetap menunjuk ke pengerjaan sisa mesin (`Siap Diambil` / `Proses: M1 (Out)`), bukan `"Selesai"`.
- **Koreksi Filter Tab & Counter Selesai:** Tiket berstatus `Parsial > Sudah Diambil` tetap berada di tab Antrian Aktif dan Parsial, dan tidak dimasukkan ke tab atau counter `Selesai` hingga seluruh unit diterima (100%).
- **Pengujian Unit Otomatis:** Lulus 100% pada SQLite `:memory:` (`test_partial_delivery_keeps_ticket_open_and_allows_machine_to_finish_remaining_quantity`).

### E. Sinkronisasi Reaktif Defect & Penghentian Tuntas Mesin (Baru Selesai & Teruji 100%)
- **Penghentian Otomatis Tahapan Mesin (Auto-Stop):** Menyelesaikan bug di mana pengerjaan 15 pcs unit bagus + 5 pcs defect mesin dari 20 pcs pengajuan tidak menghentikan mesin. Formula `getStepBalance` kini mengatribusikan defect pada status `Siap Diambil` ke stasiun mesin terakhir dan menerapkan batas `saldoOut = max(0, min($totalIn - $totalOut - $stepMachineDefects, $totalReq - $totalOut)) = 0`. Mesin seketika berhenti (*stop*).
- **Link Reaktif Input Defect & Process Qty:** Modal proses menghubungkan secara real-time `maxProcessQty = availableQty - defectQty`. Jika defect diisi 5 pada saldo sedia 20, batas maksimal unit bagus otomatis terkunci menjadi 15 pcs.
- **Kartu Kalkulasi Alokasi Fisik Kontekstual di 3 Titik:**
  - **Mesin Center:** `Sisa di Mesin: 20 | Defect: 5 -> Batas Maksimal Unit Bagus: 15 PCS`.
  - **Material Handling:** `Siap di Mesin: 15 | Defect/Hilang: 1 -> Batas Maksimal Diangkut: 14 PCS`.
  - **Gudang Penerima:** `Dikirim Kurir MH: 14 | Defect Transit: 1 -> Batas Maksimal Masuk Rak: 13 PCS`.
- **Pengujian Unit Otomatis:** Lulus 100% pada SQLite `:memory:` (`tests/Unit/WorkRequestDefectAndHandoverTest.php` - 6 passed, 30 assertions).

### F. Perombakan Layout Tabel Antrean WRI Desktop & Foto Preview (Baru Selesai & Teruji 100%)
- **Struktur 9 Kolom Standar Industri:**
  1. `No WR / Status`: Nomor tiket (klik untuk modal detail) + Tombol aksi status interaktif terintegrasi / lencana status informatif.
  2. `Tanggal`: Tanggal dan jam pengajuan (`d M Y, H:i WIB`).
  3. `Komponen`: Nama komponen tebal dan part number mono.
  4. `Sts Pengaju`: Persetujuan pengaju dengan tombol cepat `Setujui` untuk Ka. Pengaju/SPV.
  5. `Sts Penerima`: Persetujuan penerima dengan tombol `Tugaskan` operator untuk Ka. Penerima.
  6. `Foto`: Tombol kapsul `[Lihat]` yang memunculkan pop-up modal foto komponen resolusi penuh.
  7. `Routing / Dimensi / Qty`: Menampilkan penerima tujuan, dimensi part, dan kuantitas order (beserta tag defect jika ada).
  8. `Deskripsi`: Catatan order pengajuan (`notes`) dan referensi SPK.
  9. `Pengaju / Penerima`: Departemen pengaju, departemen penerima, dan nama operator pelaksana.
- **Penghapusan Kolom Non-Krusial:** Kolom *Target Slot Gudang* dan *Lokasi Gambar* resmi ditiadakan dari tabel utama.
- **Modal Foto Komponen (`image_preview_modal.blade.php`):** Pratinjau foto komponen resolusi tinggi dengan Alpine.js tanpa reload.
- **Pengujian & Validasi:** Seluruh 11 pengujian unit lolos 100% pada SQLite `:memory:` dan kompilasi Blade cache sukses 100%.

### G. Redesign Pop-Up Detail Tracking (Lampiran 1-3) & Penyelarasan Alur Show (Baru Selesai & Teruji 100%)
- **Ikon Status Hero Melingkar:** Visualisasi status di bagian atas (Kuning Jam: `Belum Verifikasi`, Hijau Centang: `Siap Diambil`, Emerald Centang Ganda: `Sudah Diambil`, Biru Gear: `Sedang Diproses`).
- **Dual Approval Card Transparan:** Menampilkan verifikator pengaju dan penerima secara terpisah (nama Ka. Bagian, tanggal & jam, status approval, serta nama operator pelaksana).
- **Tracking Proses dengan Balok Event Warna-warni:**
  - Biru: `MASUK` (kuantitas & waktu stasiun)
  - Kuning: `PARSIAL (SELESAI)` (pengerjaan cicilan bertahap)
  - Merah: `DEFECT / REJECT` (kuantitas defect, operator, dan alasan kerusakan yang transparan memotong kuota)
  - Hijau: `SELESAI (FINAL)` (penyelesaian stasiun tuntas)
  - Cyan/Emerald: `DITERIMA GUDANG` (penerimaan fisik di rak gudang)
- **Penyelarasan Alur Redirect:** Pembuatan tiket WRI baru tidak lagi melempar pengguna ke halaman show mandiri (Lampiran 4), melainkan langsung kembali ke antrean utama dengan modal detail otomatis terbuka.

### H. Pembersihan Widget Dummy "Status Mesin Utama (Machine Center)" (Baru Selesai & Teruji 100%)
- **Penghapusan Widget Dummy:** Blok pill grid 7 mesin fiktif (`MC-LC-01` s/d `MC-WLD-01`) pada `resources/views/work_requests/index.blade.php` telah dihapus seutuhnya.
- **Pembersihan Beban Query Backend:** Query `$activeMachines` di `WorkRequestController::index()` ditiadakan untuk mempercepat waktu render antrean WRI.
- **Kepatuhan Zero Database Regression:** Tabel `machines` dan foreign key relasi langkah WRI pada database operasional `hpk_gudang` tetap utuh 100% tanpa ada penghapusan data tabel.
- **Tampilan WRI Ringkas & Efisien:** Halaman antrean WRI kini langsung menyajikan Kartu KPI Utama, Filter Scope, dan Tabel 9 Kolom tanpa elemen statis yang membingungkan operator pabrik.

### I. Perombakan Pop-Up Modal Quantity (Input Qty & Defect Minimalis - Baru Selesai & Teruji 100%)
- **Terinspirasi dari Sistem Lama (`System WR lama`):** Pop-up berukuran ramping (`max-w-md` ~420px) dengan visual lembut, bebas scroll, dan fokus pada input angka pengerjaan yang cepat dan intuitif.
- **Hero Stepper Touch-Friendly:** Dilengkapi tombol minus `[-]`, input angka besar di tengah (`text-3xl font-black font-mono`), tombol plus `[+]`, serta shortcut instan `[Semua / Max: X pcs]` agar operator atau kurir MH dapat mengisi kuota penuh hanya dengan 1 kali klik tanpa mengetik manual.
- **Smart Defect Toggle (Minimalis):** Form defect tidak lagi memakan ruang secara permanen; disembunyikan rapi di balik tombol toggle ramping `[+ Ada Defect / Rusak? (Opsional)]`. Jika aktif, memunculkan input kuantitas defect dan alasan dengan kalkulasi reaktif instan ke unit bagus.
- **Fase Mulai Mesin `(In)` Terkunci Rapi:** Menampilkan kuantitas pengerjaan terkunci dengan badge gembok informatif tanpa membebani layar dengan formulir defect yang belum diperlukan.
- **Integritas Fungsi & Zero Regression 100%:** Seluruh 7 parameter form (`action_type`, `target_status`, `operator_name`, `process_qty`, `defect_qty`, `defect_reason`, `notes`), validasi parsial, dan backend handling tetap berjalan tanpa perubahan fungsi. Unit tests lolos 100%.

### J. Penyederhanaan SLA Peringatan & Filter Antrean 4 Kategori (Baru Selesai & Teruji 100%)
- **Filter Interaktif Peringatan SLA 3 Hari (72 Jam):** Badge peringatan merah `[ ⚠️ X Tiket Melewati SLA > 72 Jam ]` kini menjadi tombol filter dinamis. Saat diklik, sistem langsung memfilter dan menyortir tiket dari yang tertunda paling lama (`created_at ASC`) lengkap dengan tombol reset toggle 1-klik.
- **Unified 4-Status Control Bar:** Merampingkan antrean tiket WRI yang sebelumnya bertumpuk ganda menjadi 4 kategori operasional intuitif (+ Semua): **Perlu Verifikasi**, **Sedang Proses**, **Siap Ambil**, dan **Selesai**. Redundansi baris filter dihilangkan seutuhnya dan 5 kartu KPI header disinkronkan sebagai tombol pintas filter instan.
- **Pengujian Unit Otomatis:** Teruji 100% pada SQLite `:memory:` (`tests/Unit/WorkRequestSlaAndStatusFilterTest.php`).

### K. Modul Cetak Dokumen WR Resmi 3 Rangkap & Antrean Print (Baru Selesai & Teruji 100%)
- **Gatekeeper Validasi 3 Syarat Mutlak Cetak:** Dokumen WRI hanya boleh dicetak jika:
  1. `approval_pengaju === 'Diterima'` (Disetujui Ka. Pengaju)
  2. `approval_penerima === 'Diterima'` (Disetujui Ka. Penerima)
  3. `assigned_operator_name` terisi (Operator pengerjaan telah ditugaskan)
  Jika belum terpenuhi, tombol cetak dinonaktifkan (`disabled`) dengan keterangan tooltip validasi yang informatif.
- **Format Resmi Triplicate 3 Rangkap dalam 1 Lembar HVS A4:** 1 tiket WRI dicetak 3 slip identik vertikal dengan garis putus-putus (`✂ Potong di sini`):
  - *Lembar Pemohon (Putih)*
  - *Lembar Mesin/Pelaksana (Kuning)*
  - *Lembar Gudang/MH (Merah)*
- **Lokal & Offline QR Code Vektor SVG:** Menggunakan SVG Data URI lokal tanpa dependensi internet dan tanpa ekstensi PHP GD, menghasilkan kualitas cetak barcode tajam pada printer desktop dan barcode gun.
- **4 Kolom Otorisasi Tanda Tangan & Nama Jelas:** Menampilkan nama Pemohon, Ka. Bagian Pemohon, Penerima (Operator), dan Ka. Bagian Penerima.
- **Kebijakan Cetak Ulang & Audit Log:** Mendukung cetak ulang dengan tanda otomatis `[Cetak Ulang]`, tracking frekuensi cetak (`print_count`), dan pencatatan riwayat `action_type: 'printed'`.
- **Menu Tab Antrean Cetak (`/work-requests/print-queue`) & Cetak Massal (*Bulk Print*):**
  - Tab navigasi `[ 🖨️ Cetak Dokumen WR ]` di samping *Safety Stock Alert*.
  - Menyediakan filter cepat (*Semua Siap Cetak*, *Belum Dicetak*, *Sudah Dicetak*).
  - Checkbox multi-select dan aksi massal `[ 🖨️ Cetak Masal Terpilih ]` (`/work-requests/bulk-print`).
- **Otoritas Izin Spatie (`work-request.print`):** Diberikan secara bawaan kepada Staf Gudang, Admin Gudang, Supervisor, dan Operator Mesin.
- **Pengujian Unit Otomatis:** Teruji 100% pada SQLite `:memory:` (`tests/Unit/WorkRequestPrintValidationTest.php` - 6 passed, 21 assertions).

### L. Format Label QR Komposit 3-in-1 & Scanner Multi-Token (Baru Selesai & Teruji 100%)
- **Payload Komposit Dinamis:** Model `Component` mengimplementasikan accessor `qr_composite_payload` dengan format `Nama Komponen ; Kode Komponen ; Kode Rak` (BR-13), dievaluasi dinamis terhadap `default_full_location_code` dengan fallback aman `Gudang HPK`.
- **Ekstraksi Cerdas pada Scanner:** `ComponentController::searchApi` dan `WorkStationSupplyController::scanLookup` mengenali delimiter titik koma (`;`), mengekstrak kandidat kode/nama secara simultan, dan tetap mendukung format lama (`HPK-PART|...` dan plain part number) secara transparan.
- **Tampilan Stiker Thermal (70x45mm) & Lembar A4 Dipertegas:** Menyematkan `.rack-badge` berbingkai kontras tinggi untuk memperjelas posisi rak fisik bagi operator gudang.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/ComponentQrCodeTest.php` - 4 passed, 7 assertions).

### M. Migrasi Foto Komponen Lokal `D:\Ali Magang\KOMPONEN` & Streaming Gateway (Baru Selesai & Teruji 100%)
- **Migrasi 611 Foto Google Drive ke Offline On-Premise:** Artisan command `components:export-to-local --path="D:\Ali Magang\KOMPONEN"` berhasil mengunduh 611/611 foto (100% berhasil, 0 gagal) dengan penamaan file standar kode part (`[part_number].jpg`).
- **Paket Arsip ZIP Otomatis:** Menghasilkan arsip kompresi `foto_komponen_hpk.zip` (~138 MB) di dalam direktori `D:\Ali Magang\KOMPONEN` yang siap disinkronisasikan ke Network Share Drive (misal `Y:\...` atau `Z:\01.Drawing`).
- **Resolusi Jalur Luring & Fallback:** Model `Component` memiliki accessor `local_photo_file_path` dan pembaruan `image_url` yang dipandu oleh environment variable `COMPONENT_EXTERNAL_PHOTO_DIR`. Jika file offline ditemukan di disk lokal, tautan langsung diarahkan ke gateway streaming internal.
- **Gateway Streaming Terpusat (`/components/{component}/photo-stream`):** `ComponentController::photoStream` melayani file gambar ke browser klien (HP, tablet, desktop) dengan HTTP cache header (`max-age=86400`), melintasi batasan sandbox browser tanpa perlu mengekspos folder fisik ke publik.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/ComponentQrCodeTest.php` - 5 passed, 10 assertions).

### N. Penyatuan Storage Gambar Teknik CAD PDF, Pure Local Mode & Pembersihan Dummy (Baru Selesai & Teruji 100%)
- **Pure Local Mode (Tutup Fallback Google Drive):** Menambahkan `COMPONENT_ENABLE_GDRIVE_FALLBACK=false` di `.env`. Sistem 100% luring membaca folder master `D:\Ali Magang\KOMPONEN`. Jika file belum ada di disk lokal, sistem langsung menyajikan placeholder SVG resmi karoseri tanpa pernah menghubungi Google Drive (kolom database `image_path` tetap aman).
- **Layanan Penyimpanan Terpadu (`ComponentPhotoStorageService`) & 3 Skenario Input:**
  - *Skenario Form / Kamera Web:* Berkas gambar/CAD PDF otomatis disanitasi kodenya, di-rename menjadi `[part_number].[ext]`, disimpan langsung ke `D:\Ali Magang\KOMPONEN`, dan dicerminkan ke `public/uploads/components/` dengan *clean overwrite*.
  - *Skenario Impor Excel (Ada Link GDrive/Web):* Backend otomatis mengunduh berkas via HTTP, me-rename menjadi `[part_number].[ext]`, dan menyimpannya langsung ke folder fisik `D:\Ali Magang\KOMPONEN`.
  - *Skenario Impor Excel (Kolom Foto Kosong):* Melewati unduhan jaringan untuk kecepatan tinggi; sistem melalui accessor `Component::getLocalPhotoFilePathAttribute()` secara otomatis mendeteksi (*auto-detect*) dan menampilkan berkas fisik dari `D:\Ali Magang\KOMPONEN` selama nama berkasnya cocok dengan kode part.
  - *Streaming Gateway HTTP:* Disajikan aman via `/components/{component}/photo-stream` dengan HTTP cache header 24 jam.
- **Dukungan Dokumen Gambar Teknik CAD Format `.PDF` hingga 15MB:** Memperluas validasi controller untuk menerima `.pdf` dan format gambar (`.jpg, .png, .webp, .svg`) hingga 15MB. Gateway streaming menyajikan file PDF secara inline (`Content-Type: application/pdf`).
- **Interactive Zoom Lightbox & Native PDF Modal Viewer di Work Center:** Pada `image_preview_modal.blade.php`, detail WRI (`work_requests/show.blade.php`), dan Master Komponen (`master_components/show.blade.php`), gambar ditampilkan `object-contain` dan dilengkapi kontrol zoom in/out/reset untuk gambar teknik serta penampil PDF layar penuh (*fullscreen iframe*) dengan fitur zoom 500%, navigasi lembar CAD, rotasi, dan cetak langsung.
- **Pembersihan Aman 15 Part Dummy Seeder (`components:clean-dummies`):** Berhasil menghapus 15 part dummy seeder (`HYD-...`, `RAW-...`, `ACC-...`, `CSFP10300250082`) dan 1 part tes manual `NEW-20261026-LAGL` ("cek cek") dalam transaksi database. Seluruh komponen asli karoseri HPK (termasuk part tanpa foto seperti `CSBA10320130000` dan `CRDA10200390000L`) 100% aman tersimpan.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/ComponentPhotoStorageTest.php` dan `tests/Unit/ComponentQrCodeTest.php` - 39 passed, 159 assertions).

### O. Pembaruan Menu Permintaan Label QR (Default Menunggu Dicetak & Indikator KPI) (Baru Selesai & Teruji 100%)
- **Default Antrean Otomatis ke "Menunggu Dicetak":** Saat pengguna membuka URL `/qr-requests` tanpa parameter status, sistem otomatis memfilter data dengan status `pending` (`Menunggu Dicetak`). Operator dan tim gudang langsung dihadapkan pada tiket label yang perlu dicetak tanpa perlu memilah manual dari riwayat masa lalu. Seluruh antrean riwayat tetap dapat diakses via opsi `?status=all` (*Semua Antrian*).
- **3 Kartu Indikator KPI Interaktif:** Bagian atas antrean dilengkapi 3 panel statistik dinamis:
  - ⏳ **Menunggu Dicetak (`pending`):** Total tiket label yang belum dicetak (aksen Amber).
  - ✅ **Selesai Dicetak (`printed`):** Akumulasi label yang telah selesai dicetak (aksen Emerald).
  - 🏷️ **Total Permintaan (`all`):** Seluruh riwayat pengajuan label QR (aksen Cyan).
  - Masing-masing kartu KPI interaktif (dapat diklik langsung untuk filter antrean) dan memiliki indikator cincin fokus (*ring active*) saat dipilih.
- **Lencana Kuantitas (Count Badges) & Pencarian Cepat Multi-Kriteria:** Tab navigasi status menyematkan badge angka riil (`counts->pending`, dll.) serta form pencarian instan berdasarkan nomor tiket request (`request_number`), kode part (`part_number`), atau nama komponen dengan pelestarian status aktif.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/QrRequestQueueTest.php` - 4 passed, 17 assertions).

### P. Pusat Log Aktivitas Pengguna & Audit Trail Terpadu (Baru Selesai & Teruji 100%)
- **Perekam Otomatis Lintas Modul (`ActivityLogger`):** Service terpusat yang secara otomatis merekam login/logout, penerbitan & approval WRI, pengerjaan mesin, defect/reject, material handling, transaksi stok gudang, penambahan/edit master komponen/CAD, dan cetak label QR.
- **Revitalisasi Halaman Audit Log (`/activities` & `/admin/audit-logs`):** Menghidupkan halaman audit log (yang sebelumnya kosong) menjadi pusat monitoring interaktif dengan 4 kartu KPI (*Total Hari Ini*, *Pengguna Aktif*, *Defect & Penolakan Kritis*, *Modul Teraktif*), toolbar filter multi-kriteria (rentang waktu, modul, pengguna, event), dan modal JSON diff.
- **Navigasi Resmi Terbuka:** Ditambahkan ke navbar dropdown 'Kontrol & Audit', drawer sidebar mobile/tablet, dan kartu dashboard untuk role pengawas: `admin_gudang`, `supervisor`, `qc`, dan `engineering`.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/ActivityLoggerTest.php` - 4 passed, 23 assertions; total suite 47 passed, 199 assertions).

### Q. Standardisasi Hierarki Navigasi Tombol Kembali Menu Admin (Baru Selesai & Teruji 100%)
- **Penyelarasan Rute Kembali Submenu Admin:** Tombol `[ < Kembali ]` pada seluruh 6 submenu admin (`admin/users`, `admin/roles`, `admin/component-categories`, `admin/uoms`, `admin/locations-master`, `admin/settings`) diarahkan secara konsisten ke **Pusat Kendali Administrator** (`route('admin.dashboard')`), mencegah admin keluar mendadak ke dashboard umum pergudangan saat berpindah konfigurasi.
- **Navigasi Cerdas Halaman Audit Log:** Tombol kembali pada `audit_logs/index.blade.php` mendeteksi rute secara adaptif (`request()->routeIs('admin.*')` kembali ke `route('admin.dashboard')`, sedangkan akses via navbar atas `/activities` kembali ke `route('dashboard')`).
- **Tombol Pintas Kembali ke WMS di Admin Dashboard:** Disematkan tombol `[ < Kembali ke WMS ]` di wave header `admin/dashboard.blade.php` menuju `route('dashboard')` untuk kemudahan navigasi 1-klik kembali ke operasional logistik utama.

### R. Panel Geser Pratinjau Foto & Identitas Komponen Permintaan Label QR (Baru Selesai & Teruji 100%)
- **Animasi Panel Geser Interaktif (Sliding Preview Card):** Pada formulir Buat Permintaan Label QR (`/qr-requests/create`), begitu komponen dipilih, panel visual bergeser turun secara mulus (*smooth transition*) menyajikan foto fisik komponen (atau indikator gambar CAD jika `.pdf`) dengan tombol perbesar ukuran penuh.
- **Kartu Identitas Lengkap:** Menampilkan Kode Part (`font-mono font-bold`), Nama Komponen, Kategori, Lokasi Rak Simpan Default, Dimensi Fisik (P x L/D x T), dan Saldo Stok Gudang Terkini. Jika pilihan komponen dikosongkan, panel melipat kembali secara halus.
- **Klarifikasi Format Label:** Mendokumentasikan status opsi Format / Jenis Label (`item`, `box`, `rack`) yang tersimpan riil di database dan audit trail.
- **Pengujian Unit Otomatis Terisolasi:** Lulus 100% pada SQLite `:memory:` (`tests/Unit/QrRequestQueueTest.php` - 5 passed, 25 assertions; total suite 48 passed, 207 assertions).

### S. Konsep Keranjang Permintaan Cetak & 2 Format Label QR (Rak & Kartu) (Baru Selesai & Teruji 100%)
- **2 Kapsul Pilihan Format Label (Pill Checkboxes):** Mengubah pilihan menjadi 2 jenis kapsul yang dapat dicentang salah satu maupun **keduanya sekaligus**: 🏷️ **Label QR Rak** (`rack`) dan 📇 **Label QR Kartu** (`card`).
- **Aturan Multiplier Kuantitas Cetak:**
  - Label QR Rak: 1 input set = 10 lembar label rak fisik (1 HVS muat 20 label = 2 set).
  - Label QR Kartu: 1 input = 1 lembar label stiker kartu part (fleksibel: 2 pcs untuk 1 baris, 10 pcs, 20 pcs).
- **Konsep Keranjang Draft Multi-Item (Cart Workflow):** Pengguna dapat memasukkan banyak komponen ke dalam tabel keranjang pengajuan sebelum dikirim. Jika kedua jenis label dicentang untuk 1 komponen, sistem otomatis memecahnya menjadi 2 baris terpisah di keranjang.
- **Penyimpanan Batch & Pemisahan Tiket Independen:** Pengiriman massal memproses array `cart_data` di dalam transaksi database atomik (`DB::transaction`) dan menyimpan setiap item sebagai record tiket `QrRequest` terpisah berstatus `pending` lengkap dengan penomoran unik dan catatan audit log.
- **Pengujian Unit Otomatis Terisolasi:** Lulus 100% pada SQLite `:memory:` (`tests/Unit/QrRequestQueueTest.php` - 6 passed, 38 assertions; total suite 49 passed, 220 assertions).

### T. Redesign Presisi Label QR Rak (Grid 20/HVS 3-Blok) & Kartu (2-Kolom) serta Engine Cetak Massal Bersamaan (Baru Selesai & Teruji 100%)
- **Redesign Label QR Rak Standar Pabrik (Grid 20/HVS):**
  - Mengimplementasikan layout presisi Lampiran 1 & 2: 1 lembar kertas A4/HVS portrait memuat **20 label** (2 kolom x 10 baris).
  - Struktur 3 blok horizontal: **Kotak Teks Berbingkai** (Header `KARTU QR KOMPONEN`, Kode Rak, Kode Komponen, Nama Komponen dengan titik dua rata), **QR Code SVG Lokal**, dan **Kotak Kosong Berbingkai** di sisi kanan untuk stempel/paraf manual.
  - Multiplier 10x: 1 set = 10 label (setengah HVS), sehingga 2 jenis komponen atau 2 set pas mengisi 1 lembar HVS penuh (20 label).
- **Redesign Label QR Kartu Part (Grid 2 Kolom):**
  - Mengimplementasikan layout presisi Lampiran 3 & 4: Dimensi luar identik dengan label rak, namun terdiri dari 2 blok tanpa kotak kosong (kotak teks berbingkai lebih lebar + QR Code SVG di sisi kanan).
  - Penempatan pada kertas A4 mengisi baris atas berdampingan (kiri Komponen 1, kanan Komponen 2).
- **Multi-Select Checkbox & Floating Action Bar Antrean:**
  - Menambahkan checkbox multi-select pada setiap baris (desktop) dan kartu (mobile) di `/qr-requests/index.blade.php`.
  - Checkbox master `Select All` di header tabel.
  - Floating Action Bar interaktif dengan tombol `[ 🖨️ Cetak Bersamaan (A4) ]` yang mengirim form POST ke `/qr-requests/bulk-print` dengan target `_blank`.
- **Engine Cetak Gabungan & Smart Page-Break (`QrRequestController::bulkPrint`):**
  - Memproses pencetakan banyak tiket sekaligus dalam 1 sesi browser print (`window.print()`).
  - Pemisahan halaman cerdas: Tiket Label Rak dicetak terlebih dahulu dalam lembar format Rak (grid 20/hvs), lalu otomatis diikuti lembar format Kartu dengan `page-break` tanpa mencampur format dalam satu lembar potong.
  - Mendukung cetak antrean baru (`pending`) dan cetak ulang massal (*reprint*) pada tab `printed` / `all`.
  - **Presisi Centering & Margin Simetris Kertas A4 (210mm x 297mm):**
    - `@page { size: A4 portrait; margin: 0; }` dengan padding eksplisit `.page-sheet { padding: 10.2mm 8mm; }`.
    - Margin Kiri & Kanan presisi 8.0 mm simetris (Grid 2 kolom 194mm).
    - Margin Atas & Bawah presisi 10.2 mm simetris (10 baris label 276.6mm), mengeliminasi masalah rata kiri dan margin bawah berlebih.
    - Perbaikan `overflow-wrap: break-word` agar nama komponen tidak terpotong di tengah kata.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/QrRequestQueueTest.php` - 8 passed, 49 assertions; total suite unit tests 51 passed, 231 assertions).

### U. Perbaikan Error Filter Safety Stock / Low Stock (Paginator Subquery & Blade Defensif) (Baru Selesai & Teruji 100%)
- **Eliminasi BadMethodCallException (`Collection::total does not exist`):**
  - Mengubah logika query `low_stock` pada `ComponentController::index()` dari in-memory collection filtering (`$query->get()->filter(...)`) menjadi query level database SQL langsung menggunakan subquery: `where('minimum_stock', '>', 0)->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE stock_balances.component_id = components.id) <= components.minimum_stock')`.
  - Mengembalikan objek `LengthAwarePaginator` secara konsisten via `paginate(15)->withQueryString()`, sehingga pemanggilan `$components->total()` dan navigasi `$components->links()` berjalan 100% normal tanpa error.
- **Proteksi Defensif Antarmuka Blade:** Menambahkan pengecekan `method_exists($components, 'total')` pada baris 104 `resources/views/master_components/index.blade.php` agar kebal dari kegagalan render bila tipe data berupa collection.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/ComponentLowStockFilterTest.php` - 1 passed, 5 assertions; total suite unit tests 52 passed, 236 assertions).

### V. Pembersihan Sistem & Akselerasi Kinerja Dashboard (Baru Selesai & Teruji 100%)
- **Eliminasi 600+ N+1 Query pada `DashboardController.php`:**
  - Mengganti penarikan seluruh data komponen ke RAM (`Component::all()->filter()`) dengan single SQL aggregated query: `Component::where('minimum_stock', '>', 0)->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE stock_balances.component_id = components.id) <= components.minimum_stock')->count()`.
  - Memangkas waktu render beranda dashboard secara drastis dari ratusan milidetik menjadi < 5ms dengan konsumsi memori minimal.
- **Pengaktifan Tab "Monitoring SLA & Durasi WRI":**
  - Menambahkan tombol tab navigasi `[ 📈 Monitoring SLA & Durasi ]` pada bilah sub-navigasi antrean WRI (`work_requests/index.blade.php`, `safety_stock.blade.php`, dan `print_queue.blade.php`) di bawah izin `work-request.monitoring`.
  - Menghubungkan fitur monitoring SLA dan utilisasi mesin yang sebelumnya tersembunyi agar dapat diakses langsung oleh operator dan pengawas.
- **Pembersihan Berkas Boilerplate Menganggur:**
  - Menghapus berkas starter awal Laravel `resources/views/welcome.blade.php` yang tidak lagi digunakan oleh route mana pun.
- **Pengujian Unit Otomatis Terisolasi:** Seluruh 52 pengujian unit lulus 100% pada SQLite `:memory:` (236 assertions).

### W. Restrukturisasi Matriks Peran & Otorisasi Sistem (37 Hak Akses Presisi - Baru Selesai & Teruji 100%)
- **Eliminasi 28 Izin Fiktif (*Ghost Permissions*):**
  - Menghapus perulangan generik 6 aksi (`view, create, edit, delete, approve, export`) yang memunculkan kotak centang kosong tanpa fungsi (seperti *Approve/Export Peta Gudang*, *Edit/Delete Transaksi Mutasi*, *Delete Permintaan QR*).
  - Merestrukturisasi sistem menjadi **37 hak akses presisi** di mana setiap kotak centang terhubung langsung ke tombol, aksi, dan middleware otorisasi riil di backend.
- **Daftar 11 Modul & 37 Izin Presisi:**
  1. `work-request` (8): `view`, `create`, `process`, `approve`, `print`, `monitoring`, `safety-stock`, `view-all`
  2. `material-handling` (3): `view`, `manage`, `process`
  3. `peta-gudang` (3): `view`, `create`, `edit`
  4. `master-komponen` (4): `view`, `create`, `edit`, `delete`
  5. `transaksi-komponen` (2): `view`, `create`
  6. `label-qr` (3): `view`, `create`, `print`
  7. `ecr` (3): `view`, `create`, `approve`
  8. `disposal` (3): `view`, `create`, `approve`
  9. `cycle-count` (3): `view`, `create`, `approve`
  10. `audit-log` (2): `view`, `export` (merevitalisasi modul fiktif "Laporan" menjadi Log Aktivitas Sistem)
  11. `admin-panel` (3): `view`, `users`, `settings`
- **Pembaruan UI & Bahasa Indonesia Ramah Pengguna:**
  - `resources/views/admin/users/create.blade.php`, `edit.blade.php`, dan `resources/views/admin/roles/edit.blade.php` menyematkan judul berbahasa Indonesia yang jelas untuk setiap centang izin beserta badge aksi tematik.
- **Presets Rekomendasi Otomatis Berbasis Area Kerja:**
  - Pemilihan role & area kerja (`gudang`, `mesin_center`, `material_handling`) secara otomatis mencentang hak akses yang sesuai tanpa perlu admin memilih manual satu per satu.
- **Proteksi Rute Baru (`routes/web.php`):**
  - Rute cetak QR thermal, sheet, dan bulk print diproteksi dengan `can:label-qr.print`.
  - Rute log aktivitas diproteksi dengan `can:audit-log.view`.
- **Kepatuhan Zero Database Regression:**
  - Diterapkan melalui migrasi database aman `2026_11_02_110000_refactor_permissions_matrix.php` tanpa menghapus akun maupun data operasional. Seluruh unit tests lulus 100%.

### X. Pengaktifan Otomatis Izin Approval Penerima pada Preset Staff Mesin Center (Baru Selesai & Teruji 100%)
- **Penambahan `work-request.approve` pada Preset Staff Mesin Center:**
  - Memperbarui `getPermissionPresets()` di `app/Http/Controllers/Admin/UserController.php` sehingga saat admin memilih Role: **Staff** dan Bagian: **Mesin Center**, kotak izin approval otomatis tercentang (`checked`).
  - Staf Mesin Center kini langsung memiliki wewenang untuk menyetujui tiket masuk ke stasiun mesinnya (`approval_penerima = 'Diterima'`), membuka modal `[ Diterima (Tugaskan) ]`, dan menugaskan operator pelaksana (`assigned_operator_name`).
- **Klarifikasi Label Antarmuka (UI Friendly Title):**
  - Mengubah label izin pada `create.blade.php`, `edit.blade.php`, dan `roles/edit.blade.php` dari `Sub: Approval Ka. Bagian` menjadi:
    **`Sub: Approval (Ka. Pengaju & Penerima / Tugaskan Operator)`**.
  - Mengeliminasi kebingungan pengguna mengenai apakah kotak approval tersebut mencakup verifikasi kesanggupan mesin penerima dan penugasan operator.
- **Pengujian Unit Otomatis Terisolasi:**
  - Menambahkan pengujian khusus pada `tests/Unit/PermissionMatrixIntegrityTest.php` yang memverifikasi bahwa preset staff mesin center wajib memiliki `work-request.approve`, sedangkan operator mesin fisik tetap tidak memiliki izin manajerial approval. Seluruh 55 unit tests lulus 100% (285 assertions).

### Y. Perbaikan Trigger Otorisasi Penerima & Aktivasi Tombol Routing Pengerjaan Mesin (Baru Selesai & Teruji 100%)
- **Akar Masalah (Root Cause Bug Dropdown):**
  - Pada tabel desktop WRI (`resources/views/work_requests/index.blade.php`), dropdown `STS PENERIMA` menggunakan tag `<select @change="handlePenerimaAction($event, @json($assignPayload)...)">`. Penggunaan `@json()` di dalam atribut berkutip ganda (`"`) menghasilkan tabrakan sintaksis HTML (*quote collision*) yang memotong script menjadi `@change="handlePenerimaAction($event, {"`.
  - Hal ini memicu *JavaScript SyntaxError* pada Alpine.js. Akibatnya saat opsi "Diterima" dipilih, nilai dropdown berubah secara visual namun modal penugasan operator tidak pernah muncul dan form tidak pernah tersubmit ke backend, sehingga tiket tertahan di status `Menunggu Penerima` dan alur routing mesin tidak pernah aktif.
- **Solusi Komprehensif & Modernisasi Antarmuka:**
  - **Penggantian Dropdown ke Tombol Aksi Langsung (Kolom 5 STS PENERIMA):**
    - Mengganti elemen `<select>` yang rawan bug dengan tombol aksi interaktif presisi:
      - `[ 👤 Tugaskan ]`: Membuka modal verifikasi penerima & penugasan operator menggunakan atribut berkutip tunggal `@click='openAssignModal(@json($assignPayload))'`.
      - `[ ✕ ]`: Membuka modal penolakan tiket WRI (`openRejectModal`).
  - **Aktivasi Tombol Aksi di Kolom 1 (NO WR / STATUS):**
    - Jika tiket masih `Menunggu Penerima` dan pengguna memiliki hak otorisasi penerima, badge pasif digantikan menjadi tombol aksi interaktif: `[ 👤 Tugaskan Operator ]`.
    - Jika tiket masih `Menunggu Pengaju` dan pengguna berwenang, tombol `[ Setujui Pengaju ]` langsung muncul.
  - **Modernisasi Kolom 4 (STS PENGAJU):**
    - Mengganti select box pengaju dengan tombol direct `[ Setujui ]` dan `[ ✕ ]`, serta menambahkan method helper `handlePengajuActionDirect(approveUrl)` pada Alpine.js.
  - **Aktivasi Alur Routing Mesin Seketika:**
    - Begitu staf/kepala penerima menginput nama operator pelaksana dan menekan `[ Verifikasi & Masukkan Antrian ]`, backend mengubah status tiket menjadi `approval_penerima = 'Diterima'` dan `status = 'Dalam antrian'`.
    - Seketika tombol routing stasiun pertama langsung muncul di Kolom 1: `[ ▶ Mulai: [Mesin] (In) ]` (contoh: `[ ▶ Mulai: Bubut (In) ]`), siap untuk dieksekusi oleh operator pelaksana.
- **Validasi & Pengujian:**
  - Bebas dari *JavaScript SyntaxError* di browser.
  - Seluruh pengujian otomatis `tests/Unit` (55 tests, 285 assertions) lulus 100%.

### Z. Isolasi Penuh Aksi Material Handling ke Menu MH & Penegasan Alur Penerimaan Gudang (Baru Selesai & Teruji 100%)
- **Penghapusan Tombol Eksekusi MH dari Antrean WRI:**
  - Menghilangkan modal pop-up serah terima kurir MH (Lampiran 1: *"Pengantaran ke Pengaju (MH)"*) dari tabel antrean WRI (`resources/views/work_requests/index.blade.php`) dan detail WRI (`resources/views/work_requests/show.blade.php`).
  - Parameter `$forWriView = true` pada `getAvailableActionsForUser()` dan `canUserProcessAction()` di `app/Models/WorkRequest.php` memastikan aksi `pickup` dan `pickup_final` tidak pernah ditawarkan sebagai tombol eksekusi di WRI, bahkan untuk akun Admin.
  - Menambahkan proteksi lapis ganda di `WorkRequestController::processAction` yang menolak eksekusi aksi MH jika tidak berasal dari rute `work-station-supplies.*`.
- **Indikator Informatif & Tautan Cepat ke Menu MH di WRI:**
  - Jika tiket berstatus `Siap Diambil` (selesai di mesin), antrean WRI menampilkan badge oranye: `🚚 Siap Dijemput Kurir MH` + tombol link `[ 🚚 Buka Menu MH ]` menuju `/work-station-supplies` bagi akun yang memiliki hak akses MH.
  - Jika tiket berstatus `Menuju Pengaju` (sedang diangkut kurir), antrean WRI menampilkan badge ungu: `🚚 Sedang Diantar Kurir MH` + link `[ Pantau di MH ]`.
- **Penegasan Alur Penerimaan Gudang (Tidak Pernah Ditutup Otomatis):**
  - Setelah Kurir MH mengonfirmasi penjemputan dari menu MH (`/work-station-supplies`), tiket berstatus `Menuju Pengaju` dan **TETAP TERBUKA** (`is_final = false`).
  - Di tampilan Staf Gudang, tombol aksi wajib **`[ 📦 Terima di Rak Gudang ]`** (`Sudah Diambil`) aktif.
  - Staf Gudang memeriksa unit fisik masuk rak, dan hanya setelah konfirmasi terima, stok gudang (`stock_balances`) bertambah dan tiket resmi tuntas (**Final**).
- **Pengujian Unit Otomatis Terisolasi:**
  - Menambahkan pengujian `test_wri_view_excludes_material_handling_actions` dan `test_gudang_must_receive_goods_after_mh_delivery_and_ticket_remains_open` di `tests/Unit/WorkRequestAuthorizationTest.php`. Seluruh 57 unit tests lulus 100% (292 assertions).

### AA. Koreksi Presisi Siklus Status MH: 'Menunggu Diambil MH' vs 'Sedang Diantar Kurir MH' (Baru Selesai & Teruji 100%)
- **Penyebab Masalah (Root Cause):**
  - Pengecekan kondisi status di `index.blade.php` sebelumnya menggunakan `$isMenuju = stripos($nextStatus, 'Menuju Pengaju') !== false;`. Karena setelah mesin selesai (`Siap Diambil`) target langkah berikutnya memang adalah `Menuju Pengaju`, variabel `$isMenuju` bernilai `true` secara prematur, sehingga baris tiket melompat ke label *"Sedang Diantar Kurir MH"* meskipun barang fisik masih berada di mesin dan kurir MH belum mengambil barang.
  - Pada modal detail pelacakan tiket (`detail_modal.blade.php`), kartu *Pengiriman ke Dept Gudang* menampilkan keterangan default *"Menunggu tahapan pengerjaan mesin selesai"*, padahal mesin M1 sudah berstatus *SELESAI (FINAL)*.
- **Penyempurnaan Logika Tampilan Berbasis Status Riil Database:**
  - Mengganti pengecekan berbasis target langkah berikutnya dengan pengecekan status aktif database:
    - **Status `Siap Diambil` (Mesin Selesai & Belum Diambil MH):**
      - Tabel & Kartu WRI: Badge oranye **`🚚 Menunggu Diambil MH`** + tautan **`[ 🚚 Buka Menu MH ]`**.
      - Modal Detail Tracking: Kartu Pengiriman ke Gudang menampilkan badge amber **`Menunggu Diambil MH`** & keterangan *"Pengerjaan Mesin Selesai • Siap Dijemput. Menunggu verifikasi pengambilan oleh Kurir Material Handling di menu MH."*
    - **Status `Menuju Pengaju` (Hanya Setelah Kurir MH Verifikasi & Submit di Menu MH):**
      - Tabel & Kartu WRI: Badge ungu **`🚚 Sedang Diantar Kurir MH`** (barang sedang di jalan/troli kurir) + tautan **`[ Pantau di MH ]`**.
      - Bagi Staf Gudang: Tombol aktif **`[ 📦 Terima di Rak Gudang ]`**.
      - Modal Detail Tracking: Log resmi Kurir MH warna biru (*"MATERIAL HANDLING (MENUJU PENGAJU)"*) aktif.
- **Hasil Pengujian Unit:**
  - 57 tests passed (292 assertions) lulus 100%.

### BB. Dokumentasi Resmi Struktur Folder, Arsitektur Framework & Manual Pengembang (Baru Selesai & Teruji 100%)
- **Penerbitan Dokumen Panduan Resmi:**
  - Menerbitkan `docs/PANDUAN_STRUKTUR_FOLDER_DAN_FRAMEWORK.md` sebagai panduan komprehensif bagi pengembang penerus yang berkolaborasi dengan AI Agent.
  - Membedah konsep MVC Laravel dengan analogi pabrik karoseri (chassis standar vs bodi karoseri khusus).
  - Merinci fungsi, alasan keberadaan, dan cara kerja seluruh folder/subfolder (termasuk folder historis: `Data acuan`, `referens`, `System WR lama`).
  - Mengulas hubungan Node.js vs Laravel, penegasan bahwa Node.js tidak perlu selalu menyala di pabrik (cukup `npm run build` sekali di awal), serta bahaya fatal jika file konfigurasi `.json`, `.lock`, dan `.xml` dihapus.
  - Menetapkan aturan tata kelola berkas `.md`: menegaskan bahwa `GEMINI.md` dan `README.md` **wajib tetap berada di root direktori** agar guardrails otomatis AI tidak terputus.

### CC. Eliminasi Tabrakan Teks Dokumen Acuan & Redesign Margin Cetak A4 Slip Mutasi Gudang (Baru Selesai & Teruji 100%)
- **Eliminasi Teks Nabrakan (Anti-Collision Metadata Grid):**
  - Pada halaman detail mutasi barang gudang (`resources/views/transactions/show.blade.php`), ke-4 kolom metadata dibungkus dalam kontainer berbingkai bersih (`bg-slate-50/80 rounded-xl border border-slate-200/80`) dengan `min-w-0` pada setiap sel kolom.
  - Nilai kolom `Dokumen Acuan (PO/SJ)` yang memuat string panjang tanpa spasi (misal kode WRI `WRI/DeptGudang/20261102/1/SDTPBRDD3000000`) disematkan kelas `break-all font-mono text-[11px] leading-tight`, sehingga teks terlipat rapi ke bawah dan tidak lagi menabrak nama `Petugas Gudang: Pak Panji`.
  - Blok Catatan transaksi disematkan `break-words [overflow-wrap:anywhere]`.
- **Redesign Margin & Layout Cetak A4 Presisi (Warehouse Slip Print):**
  - Menggantikan trik lama `position: absolute; left: 0; top: 0; padding: 0;` dengan arsitektur cetak standar industri `@page { size: A4 portrait; margin: 12mm 15mm; }`.
  - Elemen `#printable-slip` diatur `position: static !important; width: 100% !important; padding: 6mm 8mm !important; border: 1.5px solid #0f172a !important; border-radius: 8px !important;`, menghasilkan slip gudang formal dengan jarak nafas aman tanpa ada logo atau nomor transaksi yang terpotong oleh margin printer.
  - Mengunci layout 4 kolom metadata (`print:grid-cols-4`) dan 3 kolom tanda tangan (`print:grid-cols-3`) secara eksplisit di mode cetak.
- **Pengujian Unit Otomatis Terisolasi:**
  - Menambahkan pengujian `tests/Unit/TransactionSlipTest.php` untuk memvalidasi anti-collision styling, break-all, @page A4 portrait, dan print layout. Seluruh 58 unit tests lulus 100% (303 assertions).

### DD. Penghapusan Kolom QC, Penyederhanaan Kolom Penerima, Latar Polos, dan Cetak Massal Slip Mutasi (Hemat Kertas @2 Slip/HVS) (Baru Selesai & Teruji 100%)
- **Penyesuaian Otorisasi Tanda Tangan (2 Kolom Simetris):**
  - Menghapus kolom `Diperiksa / QC Inspeksi: ( Staff QC )` pada slip mutasi gudang sesuai arahan.
  - Mengubah kolom penerima menjadi `Diterima Oleh:` dengan garis dan placeholder nama `( .................................... )`.
  - Mengunci layout 2 kolom simetris: `Dikeluarkan Oleh (Gudang): {{ $transaction->user->name }}` di sebelah kiri dan `Diterima Oleh: ( .................................... )` di sebelah kanan.
- **Pembersihan Background Grid:**
  - Menambahkan override CSS `background-image: none !important; background-color: #f8fafc !important;` pada tampilan layar dan `background-color: #ffffff !important;` pada mode cetak untuk menghilangkan motif kotak-kotak grid 30px di belakang slip.
- **Fitur Cetak Massal Hemat Kertas (Multi-Select Bulk Print @2 Slip / Lembar A4):**
  - Menambahkan route `transactions.bulk-print` dan method `TransactionController::bulkPrint`.
  - Menambahkan kotak centang multi-pilih (checkbox) pada header tabel (Pilih Semua), tiap baris tabel desktop, dan kartu mobile di `/transactions`.
  - Floating action bar otomatis muncul di bagian bawah saat ada transaksi yang dicentang, menampilkan counter transaksi terpilih dan tombol `[ 🖨️ Cetak Slip Terpilih ]`.
  - Menerbitkan template cetak presisi `resources/views/transactions/print_sheet.blade.php`: membagi data menjadi 2 slip per 1 lembar kertas A4 (`@page { size: A4 portrait; margin: 8mm 10mm; }`), dilengkapi garis panduan potong gunting (`✂ POTONG DI SINI / TEAR HERE`), mengeliminasi pemborosan kertas hingga 50%.
- **Pengujian Unit Otomatis Terisolasi:**
  - Memperbarui `tests/Unit/TransactionSlipTest.php` untuk memvalidasi ketiadaan kolom QC, teks 'Diterima Oleh:', format tanda tangan titik, penghilangan background grid, serta pembagian 2 slip per halaman A4. Seluruh 59 unit tests lulus 100% (315 assertions).

### EE. Eliminasi Toggle Semu Scope Dept pada Antrean WRI & Perluasan Kotak Pencarian (Baru Selesai & Teruji 100%)
- **Hasil Analisis Arsitektur Otorisasi Antrean WRI:**
  - Sebelumnya terdapat tombol toggle pill `[ 👤 Dept Saya | 🌐 Semua Dept (*) ]` di halaman antrean Work Request Inhouse (`resources/views/work_requests/index.blade.php`).
  - Berdasarkan audit logika backend `WorkRequest::scopeForUser()`, user dengan role global/admin (`canAccessAllDepartments() == true`) langsung di-short-circuit sehingga filter `my_dept` diabaikan dan seluruh tiket tetap ditampilkan. Sementara itu, operator divisi reguler sejak awal dibatasi oleh Blade `@if(Auth::user()->canAccessAllDepartments())`, sehingga tombol toggle ini semu (*redundant*) dan membingungkan pengguna.
- **Pembersihan Antarmuka & Perluasan Search Input:**
  - Menghapus komponen tombol toggle pill tersebut dari `resources/views/work_requests/index.blade.php`.
  - Memperluas kotak pencarian (search box) menjadi `w-full sm:w-72 lg:w-80` dengan placeholder yang lebih komprehensif: `"Cari No. WRI, komponen, SPK, departemen..."`, memberikan ruang baca yang lebih lega dan antarmuka yang bersih (*clean*).
- **Penyederhanaan Logika Controller (`WorkRequestController::index`):**
  - Menghapus pengondisian parameter `$scope === 'all'` vs `'my_dept'`.
  - Jika user tidak memiliki akses global (`!$user->canAccessAllDepartments()`), query secara otomatis menerapkan `forUser($user)` (hanya tiket milik departemennya). Jika user berhak atas seluruh departemen (Admin/Superadmin/PPIC/Gudang), seluruh tiket otomatis tersaji tanpa beban tombol toggle tambahan.
- **Pengujian Unit Otomatis Terisolasi:**
  - Menjalankan `WorkRequestAuthorizationTest` (5 passed), `WorkRequestSlaAndStatusFilterTest` (2 passed), serta seluruh unit test suite (59 passed, 315 assertions) pada SQLite in-memory, terkonfirmasi 100% stabil tanpa regresi.

### FF. Transformasi SLA Menjadi Banner Peringatan Terlambat (> 3 Hari) & Pembersihan Tab Navigasi WRI (Baru Selesai & Teruji 100%)
- **Pembersihan Bahasa Asing / Istilah Teknis SLA:**
  - Menghapus seluruh akronim asing "SLA" dari antarmuka pengguna WRI yang membingungkan operator lapangan. Menggantinya dengan bahasa operasional yang membumi: *"Tertunda lebih dari 3 hari"*, *"Terlambat > 3 Hari"*, dan *"Peringatan Terlambat"*.
- **Penerapan Banner Peringatan Keterlambatan (Mengacu Lampiran 1 Desain Pengguna):**
  - Tampil otomatis di atas daftar tiket WRI saat terdapat tiket tertunda lebih dari 3 hari (`$overdueSlaCount > 0` atau saat filter aktif).
  - Dilengkapi ikon segitiga seru merah, judul tebal **"Peringatan!"**, keterangan jumlah tiket terlambat, dan tombol interaktif:
    - Saat normal: Tombol merah bulat **`[ 🔻 Lihat yang Terlambat ]`** untuk menyaring tiket terlambat.
    - Saat filter aktif: Berubah menjadi tombol gelap **`[ ✕ Tampilkan Semua ]`** untuk mengembalikan antrean penuh dalam 1 klik.
  - Menghilangkan tombol kecil SLA lama di bilah kontrol filter sehingga antarmuka kontrol menjadi jauh lebih rapi.
- **Penghapusan Tab Navigasi "Monitoring SLA & Durasi" (Mengacu Lampiran 2):**
  - Menghapus tab tombol navigasi `[ 📈 Monitoring SLA & Durasi ]` dari sub-menu antrean WRI (`resources/views/work_requests/index.blade.php`, `safety_stock.blade.php`, dan `print_queue.blade.php`).
  - Sub-navigasi WRI kini murni terdiri dari 3 pilar operasional:
    1. `[ 📋 Antrian & Tiket WRI ]`
    2. `[ 🛡️ Safety Stock Alert ]`
    3. `[ 🖨️ Cetak Dokumen WR ]`
- **Penyematan Indikator Terlambat Langsung pada Data Tiket WRI:**
  - Menambahkan method pembantu `$wri->isOverdue(): bool` dan accessor `is_overdue` pada model `WorkRequest`.
  - **Tabel Desktop:** Menyematkan lencana peringatan merah `⚠️ Terlambat > 3 Hari` pada kolom No WR / Status dan durasi `Tertunda X hari` pada kolom Tanggal.
  - **Kartu Mobile:** Menampilkan lencana peringatan merah di samping status badge dan durasi umur tiket.
  - **Modal Detail Pelacakan (`detail_modal.blade.php`):** Menyematkan kotak peringatan (*alert box*) khusus jika tiket yang dibuka berstatus tertunda > 3 hari agar segera ditindaklanjuti.
- **Pengujian Unit Otomatis Terisolasi:**
  - Memperbarui `tests/Unit/WorkRequestSlaAndStatusFilterTest.php` untuk menguji `isOverdue()` dan accessor `is_overdue`. Seluruh 59 unit tests lulus 100% (321 assertions).

### GG. Pembersihan Bersih 6 Hak Akses Semu/Ghost & Perampingan Matriks Otorisasi (37 -> 31 Hak Akses) (Baru Selesai & Teruji 100%)
- **Hasil Audit Empiris Matriks Otorisasi RBAC:**
  - Melakukan investigasi menyeluruh terhadap seluruh 37 izin di `routes/web.php`, `app/Http/Controllers/`, dan `resources/views/`.
  - Mengidentifikasi 6 hak akses yang tidak memiliki fitur fisik, semu (ghost), atau redundan:
    1. `work-request.monitoring`: Usang karena SLA sudah diubah menjadi Banner Peringatan terbuka dan filter interaktif di halaman utama WRI.
    2. `work-request.view-all`: Ghost permission (0 kali dibaca kode sistem) karena akses 15 departemen dikendalikan secara mutlak oleh kolom database `users.can_access_all_departments` dan fungsi `canAccessAllDepartments()`.
    3. `audit-log.export`: Fitur ekspor/unduh log audit tidak pernah ada di aplikasi (tidak ada rute, controller, maupun tombol export).
    4. `admin-panel.view`: Redundan karena seluruh area `/admin/*` diproteksi secara mutlak oleh `role:admin_gudang`.
    5. `admin-panel.users`: Redundan terhadap role admin.
    6. `admin-panel.settings`: Redundan terhadap role admin.
- **Pembersihan Database Non-Destruktif & Antarmuka Admin:**
  - Menerbitkan migrasi `2026_11_03_150000_remove_obsolete_and_ghost_permissions.php` untuk menghapus ke-6 izin tersebut dari tabel `permissions`, `role_has_permissions`, dan `model_has_permissions`, serta membersihkan cache izin Spatie.
  - Menghapus ke-6 izin tersebut dari formulir *Edit Role* (`resources/views/admin/roles/edit.blade.php`) dan *Tambah/Edit User* (`resources/views/admin/users/create.blade.php` & `edit.blade.php`).
  - Memperbarui keterangan bantuan mode penuh admin dari *"Seluruh 37 hak akses..."* menjadi *"Seluruh 31 hak akses modul WMS diaktifkan secara otomatis untuk pimpinan / admin sistem."*
  - Mengarahkan rute lama `/work-requests/monitoring` secara mulus ke antrean WRI berfilter terlambat (`/work-requests?overdue_sla=1`).
- **Pengujian Unit Otomatis Terisolasi:**
  - Memperbarui `tests/Unit/PermissionMatrixIntegrityTest.php` untuk mengunci matriks pada **tepat 31 hak akses resmi** dan memasukkan ke-6 izin semu tersebut ke dalam daftar hitam ghost permissions test (`test_no_ghost_permissions_in_tailored_list`).
  - Seluruh 59 unit tests lulus 100% (327 assertions).

### HH. Simplifikasi Antarmuka & Logika Transaksi Komponen Gudang HPK (Baru Selesai & Teruji 100%)
- **Penyatuan Sub-menu Header Index:**
  - Menggantikan 3 tombol terpisah (`Inbound`, `Outbound`, `Transfer Rak`) pada header `resources/views/transactions/index.blade.php` menjadi **1 tombol tunggal `[ + Transaksi ]`** yang langsung membuka formulir transaksi (`route('transactions.create')`).
- **3 Pilihan Menu Dropdown:**
  - Pada formulir input transaksi `resources/views/transactions/create.blade.php`, pilihan `JENIS TRANSAKSI *` difokuskan menjadi 3 opsi yang jelas dan ramah pengguna:
    1. `Inbound (Barang Masuk / Penerimaan)` (`inbound`)
    2. `Outbound (Barang Keluar / Perakitan)` (`outbound`)
    3. `Transfer Antar Rak / Zona` (`transfer`)
- **Penghapusan Kolom Usang:**
  - Menghilangkan input `No. SPK Karoseri (opsional)` dan `No. Surat Jalan / PO` dari formulir Blade sehingga form lebih bersih, cepat diisi, dan efisien untuk operator gudang.
  - Memperbarui `TransactionController::store` untuk menangani field tersebut secara `nullable` (default `null`).
- **Tanggal Transaksi Real-time & Terkunci (Readonly):**
  - Input tanggal transaksi otomatis terisi tanggal hari ini dan dikunci (`readonly`, `cursor-not-allowed select-none bg-slate-100`) dilengkapi badge gembok *Real-time*.
  - Penegakan integritas server-side di `TransactionController::store`: `transaction_date` selalu diset menggunakan `now()->toDateString()` sehingga tidak dapat dimanipulasi oleh input sisi klien.
- **Auto-Fill Rak Asal Sesuai Alamat Default Komponen & Fallback:**
  - Dropdown `Ambil dari Rak` (`fromLocId`) otomatis terisi sesuai alamat rak default komponen (`default_location_id`).
  - Menambahkan `<option value="">-- Tidak Ada Rak Terdaftar --</option>` sebagai opsi pertama. Jika komponen master belum memiliki lokasi rak yang ditentukan, dropdown otomatis menampilkan `-- Tidak Ada Rak Terdaftar --` (*"kalau tidak ada, berarti tidak ada"*), bukan menampilkan rak acak dari daftar.
  - Saat ditambahkan ke daftar rincian komponen siap diproses, item tanpa rak ditampilkan dengan label `- (Tanpa Rak)` yang jelas.
- **Pengujian Unit Otomatis Terisolasi:**
  - Membuat pengujian komprehensif di `tests/Unit/TransactionFormSimplificationTest.php` (3 test cases, 20 assertions) yang menguji tombol tunggal, dropdown 3 opsi, ketiadaan field SPK/PO, status readonly tanggal, dan server-side enforcement. Seluruh 62 unit test aplikasi lulus 100% (347 assertions).

### II. Integrasi Server NAS Terpadu (Windows Drive Z:, Direct UNC \\192.168.1.150, Fallback Cadangan D:, & Linux NAS Produksi)
- **Arsitektur Penyimpanan Multi-Tier Komponen:**
  - **Tier 1 (NAS Jaringan Bersama):** Folder master server perusahaan `Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House` berisi 614 berkas foto & gambar teknik CAD (280 MB) yang terhubung ke 611 dari 613 komponen database (99.7%).
  - **Tier 1b (Direct UNC Auto-Bridge):** Sistem secara otomatis memetakan `Z:\` ke `\\192.168.1.150\HPK_Eng_Online\...` jika drive letter Windows tidak ter-mount di sesi background/CLI.
  - **Tier 2 (Redundansi Cadangan Lokal D:):** Folder `D:\Ali Magang\KOMPONEN` disiapkan sebagai fallback otomatis jika perangkat Windows dibawa keluar kantor (tanpa LAN).
  - **Tier 3 (Webroot Mirror):** Salinan lokal di `public/uploads/components/` untuk performa tinggi.
  - **Tier Produksi (Linux NAS Mount):** Mendukung path `/mnt/nas-gudang/Gudang Komponen In House` dengan auto-prepending slash normalizer.
- **Auto-Detection Cerdas Tanpa Modifikasi Database:**
  - Penambahan foto baru untuk komponen yang belum memiliki gambar cukup dilakukan dengan menyalin file foto/CAD ke folder master NAS dan menamainya sesuai kode item: `[part_number].[ext]` (misal: `CSBA10320130000.jpg` atau `CRDA10200390000L.jpg`).
  - Sistem langsung mengenali dan menyajikannya secara instan tanpa perlu mengubah database, tanpa impor ulang Excel, dan tanpa restart server.
- **Resiliensi Case-Sensitivity & Normalisasi:**
  - Method `getCandidateExtensions()` menyediakan varian ekstensi lowercase dan uppercase (`.jpg`, `.JPG`, `.png`, `.PNG`, `.pdf`, `.PDF`, `.svg`, `.SVG`, `.webp`, `.WEBP`).
  - Pencarian nama berkas mendukung nama tersanitasi (`$cleanPn`), nama asli berspasi (`$rawPn`), dan nama tanpa tanda baca (`$condensedPn`).
- **Penyajian StreamedResponse & Efisiensi Memori:**
  - Mengalirkan file via `ComponentController::photoStream()` menggunakan `response()->stream()` (`StreamedResponse`), menjamin konsumsi memori server tetap 0 MB saat mengalirkan berkas CAD berukuran besar.
- **Perintah Konsol Baru:**
  - `php artisan components:check-nas`: Memeriksa status kesehatan 4 jalur penyimpanan (Z:, UNC, D:, Mirror), menghitung persentase kelengkapan foto komponen, dan menyertakan opsi `--pull-to-local` untuk sinkronisasi offline.
  - `php artisan components:export-to-local`: Mengunduh dan mengekspor foto secara dinamis sesuai konfigurasi lingkungan.
- **Pengujian Unit Otomatis Terisolasi:**
  - `tests/Unit/ComponentPhotoStorageTest.php` lulus 100% (8 tests passed, 26 assertions pada SQLite `:memory:`).

### JJ. Standardisasi Tata Letak Header 1 Baris Sejajar (Single-Row Header Alignment - Baru Selesai & Teruji 100%)
- **Identifikasi Masalah & Akar Penyebab:**
  - Tombol aksi kanan pada modul utama (seperti `Work Request Mesin`, dll.) turun ke baris kedua meskipun ruang layar masih lega. Hal ini disebabkan kontainer header menggunakan `flex flex-col sm:flex-row sm:items-center sm:justify-between`. Pada Tailwind CSS, breakpoint `sm:` membutuhkan lebar minimal `640px`. Ketika resolusi layar atau split-screen berada di bawah 640px, kontainer beralih ke orientasi vertikal (`flex-col`), sehingga tombol aksi jatuh ke bawah judul.
- **Solusi & Penyelarasan Menyeluruh (11 Modul Views):**
  - Mengunci kontainer header menjadi 1 baris horizontal permanen: `flex items-center justify-between gap-2.5 sm:gap-4 w-full`.
  - Sisi kiri (tombol kembali, ikon, judul) disematkan `min-w-0` dengan teks judul `truncate` agar judul panjang tidak mendorong tombol keluar layar.
  - Sisi kanan (tombol aksi, reload, shortcut) disematkan `shrink-0 ml-auto` agar selalu menempel rapi di tepi kanan dalam satu garis horizontal yang sejajar dan presisi.
- **Cakupan 11 Modul yang Diselaraskan:**
  1. `work_requests/index.blade.php` (Antrian WRI)
  2. `work_requests/safety_stock.blade.php` (Safety Stock Buffer)
  3. `work_requests/print_queue.blade.php` (Antrean Cetak Label)
  4. `work_requests/monitoring.blade.php` (Monitoring & SLA)
  5. `master_components/index.blade.php` (Master Data Komponen)
  6. `transactions/index.blade.php` (Log Mutasi Stok)
  7. `work_station_supplies/index.blade.php` (Suplai Mesin & MH)
  8. `ecrs/index.blade.php` (Engineering Change Request)
  9. `cycle_counts/index.blade.php` (Cycle Count / Stock Opname)
  10. `disposals/index.blade.php` (Pengajuan Disposal)
  11. `qr_requests/index.blade.php` (Cetak Label QR Mesin)
- **Verifikasi & Build Produksi:**
  - Build aset CSS via `npm run build` berjalan sukses (56 modules transformed, 0 error).
  - Seluruh 62 pengujian unit aplikasi (`php artisan test --testsuite=Unit`) lolos 100% (347 assertions).

### KK. Modernisasi Sub-Navigasi Tabs Segmented Control Kartu Putih & Pembersihan Dev Hotfile LAN (Baru Selesai & Teruji 100%)
- **Identifikasi Masalah & Masukan Pengguna:**
  - Bilah sub-navigasi WRI sebelumnya mengambang langsung di atas pola grid latar belakang tanpa wadah pemersatu, tombol aktif terlalu dominan kontrasnya sedangkan tombol inaktif melayang tanpa latar, label teks terlalu panjang dan boros ruang (`"Antrian & Tiket WRI"`, `"Safety Stock Alert"`, `"Cetak Dokumen WR"`), serta badge notifikasi angka kaku di ujung teks.
  - Akses IP LAN (`192.168.3.101:8000`) mengalami `ERR_CONNECTION` pada berkas CSS akibat keberadaan artefak dev `public/hot` yang merujuk ke port dev lokal `5173`.
- **Solusi Komprehensif & Reusable Component:**
  - **Pembersihan Artefak & Build Mandiri:** Menghapus `public/hot` dan melakukan `npm run build`. Sistem kembali ke pembacaan manifest lokal `public/build/manifest.json` yang 100% stabil diakses lintas perangkat LAN pabrik.
  - **Segmented Control Kartu Putih Berbayangan (*Elevated White Card*):** Mengembangkan partial reusable `resources/views/work_requests/partials/sub_nav.blade.php` dengan kontainer `bg-white p-1 sm:p-1.5 rounded-2xl border border-slate-200/80 shadow-xs sm:shadow-sm inline-flex items-center gap-1 sm:gap-1.5` yang tampak melayang lembut dan hidup (*lively*).
  - **Pemadatan Teks Ergonomis (Hemat ~35% Ruang):** Meringkas teks menjadi `"Antrean WRI"`, `"Safety Stock"`, dan `"Cetak WR"` tanpa kehilangan konteks.
  - **Smart Floating Badges:** Mengubah notifikasi counter cetak dan peringatan restock menjadi mini-pill bulat dinamis (`min-w-[18px] h-[18px] px-1.5 text-[10px] font-black rounded-full`) yang elegan dan hemat tempat.
  - **Harmonisasi Global `.super-nav-pills`:** Memperbarui styling `.super-nav-pills` di `resources/css/app.css` agar filter status pada `qr_requests`, `master_components`, `cycle_counts`, `disposals`, dan `ecrs` ikut mengadopsi latar kartu putih elegan dan bayangan halus yang seragam.
- **Pengujian & Integritas Sistem:**
  - Terintegrasi penuh di `work_requests/index.blade.php`, `safety_stock.blade.php`, `print_queue.blade.php`, dan `monitoring.blade.php`.
  - Seluruh 62 pengujian unit aplikasi (`php artisan test --testsuite=Unit`) lolos 100% (347 assertions).

### LL. Sub-Navigasi Adaptif (Icon-Only Inactive, Expand Active) & Anti-Collision Tablet (Baru Selesai & Teruji 100%)
- **Penanganan Masalah Mode Tablet & Layar Menengah:**
  - Menghilangkan peregangan kosong kotak putih di mode tablet (Lampiran 2) dengan menyematkan kelas `w-fit shrink-0` pada kontainer `.bg-white` di `resources/views/work_requests/partials/sub_nav.blade.php`.
  - Mengeliminasi tabrakan antara bilah tab dan badge Departemen di sebelah kanan (Lampiran 3).
- **Penerapan Konsep Adaptif Usulan Pengguna:**
  - **Tab Aktif (Dipilih):** Menampilkan **Ikon + Teks Label (+ Badge jika ada)** dengan kontras tebal `#0a2342` dan teks putih.
  - **Tab Inaktif (Tidak Dipilih):** Teks label **disembunyikan (ngumpet)**, menyisakan **Ikon + Badge Notifikasi** yang ringkas (contoh: ikon printer + badge `10`).
  - Dilengkapi atribut HTML `title="..."` untuk menampilkan tooltip fungsi saat kursor mengarah ke ikon.
- **Efisiensi Ruang Maksimal:**
  - Memangkas lebar keseluruhan bilah navigasi dari ~340px menjadi hanya **~200px**, memberikan jarak aman lapang (> 400px) sehingga info departemen di kanan tidak pernah bersenggolan di mode tablet maupun smartphone.
- **Pengujian & Build:**
  - Build Vite produksi: `npm run build` sukses (56 modul transformed, 2.09s).
  - Pengujian unit otomatis: 62 passed (347 assertions).

### MM. Penguncian Sub-Navigasi 1 Baris Sejajar & Penyelarasan Badge Departemen (Baru Selesai & Teruji 100%)
- **Penataan Single-Row Permanen:**
  - Menghilangkan `flex-col sm:flex-row` pada kontainer luar `resources/views/work_requests/partials/sub_nav.blade.php`, menguncinya menjadi `flex items-center justify-between gap-2.5 sm:gap-4 w-full`.
  - Menyematkan `shrink-0 ml-auto` pada kontainer badge `[ 🏢 Dept Gudang ]`.
- **Hasil Visual:**
  - Badge Departemen langsung naik ke baris utama dan duduk sejajar rapi di samping kanan bilah tab kartu putih di semua ukuran layar (desktop, tablet, mobile).
  - Memanfaatkan ruang horizontal yang lapang secara optimal tanpa menjatuhkan elemen ke baris bawah.
- **Pengujian & Build:**
  - Build Vite produksi: `npm run build` sukses (56 modules transformed, 2.20s).
  - Pengujian unit otomatis: 62 passed (347 assertions).

### NN. Indikator Visual Geser (Horizontal Scroll Affordance) untuk Layar HP & Tablet (Baru Selesai & Teruji 100%)
- **Penanganan Masalah Tab Terpotong di Layar Sentuh:**
  - Tab status filter WRI yang meluap ke kanan sebelumnya terpotong kaku tanpa ada tanda visual bahwa tab tersebut bisa digeser (*swipeable*).
- **Implementasi Smart Scroll Controller (Alpine.js):**
  - Menerapkan pembungkus reaktif cerdas pada filter status di `work_requests/index.blade.php` dan `work_requests/print_queue.blade.php`.
  - **Gradient Fade Masking:** Sisi kanan otomatis menampilkan efek bayangan pudar putih halus (`bg-gradient-to-l from-white via-white/90 to-transparent`) yang memberi isyarat bahwa ada konten tersembunyi di balik tirai.
  - **Tombol Panah Geser Mini (`[ > ]` & `[ < ]`):** Mengapung di atas bayangan gradasi dengan animasi denyut (*pulse hint*). Pengguna HP dapat menggeser manual dengan jari (*swipe*), atau mengetuk tombol `[ > ]` untuk menggeser otomatis 160px secara halus.
  - **Otomatis Bersembunyi di Layar Lebar:** Jika layar desktop cukup lebar dan seluruh tab muat, tombol panah dan gradasi otomatis disembunyikan (*clean desktop view*).
  - **Harmonisasi Global `.super-nav-pills`:** Menambahkan `scroll-behavior: smooth` dan padding kanan touch `padding-right: 1.25rem` pada `resources/css/app.css` agar seluruh filter tab modul lain (`qr_requests`, `master_components`, `cycle_counts`, `disposals`, `ecrs`) memiliki efek intip (*peek affordance*) yang jelas di ponsel.
- **Pengujian & Build:**
  - Build Vite produksi: `npm run build` sukses (56 modules transformed, 2.21s).
  - Pengujian unit otomatis: 62 passed (347 assertions).

### OO. Perbaikan Stabilitas Scope Alpine.js & Pencegahan Tag HTML Mismatch (Baru Selesai & Teruji 100%)
- **Penyelesaian Error Console `detailModalOpen is not defined`:**
  - Mendiagnosis dan menghapus duplikasi 3 baris tag penutup HTML `</form></div></div>` pada `resources/views/work_station_supplies/index.blade.php` (baris 1063–1065).
  - Menghilangkan penutupan prematur pada container `x-data="taskGudangHandler()"` sehingga Modal Detail Pelacakan Tiket (`x-show="detailModalOpen"`) dan Modal QR Scanner kembali berada seutuhnya di dalam scope Alpine.js.
- **Perbaikan Keseimbangan Tag Import Modal:**
  - Melengkapi penutup `</div>` yang kurang pada wrapper utama `<div x-data="componentImportModal()"` di `resources/views/master_components/partials/import_modal.blade.php`.
- **Pengujian Unit Otomatis Anti-Regresi:**
  - Membuat test suite `tests/Unit/BladeAlpineScopeTest.php` untuk memvalidasi kesimetrisan pembuka & penutup tag HTML (`<div>`, `<form>`, `<table>`, `<template>`) di seluruh file `.blade.php` dan memastikan `taskGudangHandler()` tidak pernah ditutup prematur.
  - Lulus 100% pada SQLite in-memory `:memory:` (2 passed, 5 assertions).

### PP. Desain Minimalis Modal Penugasan Operator Mesin (Baru Selesai & Teruji 100%)
- **Penyederhanaan Visual & Ukuran Ramping:** Merampingkan modal `assign_operator_modal.blade.php` dari `sm:max-w-lg` (512px) menjadi `sm:max-w-md` (420px) berlatar putih bersih dengan soft badge, senada dengan bahasa desain modal proses kuantitas.
- **Peniadaan Textarea Catatan Jadwal Mesin & Baris Chips:** Menghapus textarea catatan kesanggupan/jadwal mesin yang memakan tempat serta mengeliminasi baris chips repetitif di bawah input.
- **Pemilihan Operator Cerdas Berbasis Stasiun Tujuan (Combobox):** Memetakan operator aktif per departemen (`$departmentOperatorsMap`) sehingga dropdown hanya memuat nama operator dari stasiun tujuan tiket (misal: Dept M1 hanya menampilkan operator Dept M1).
- **Auto-Fallback Diri Sendiri & Zero Duplication:** Jika departemen tujuan belum memiliki operator yang terdaftar di sistem, input otomatis diarahkan ke nama user yang sedang login (*Ka. Mesin / Diri Sendiri*). User yang sedang login secara otomatis difilter dari daftar operator rekanan (`otherOperators`) sehingga namanya tidak pernah muncul ganda di dropdown.
- **Input Bebas / Hybrid:** Pengguna tetap dapat memilih via dropdown atau mengetik nama baru secara bebas di dalam input.
- **Perbaikan Posisi Checkmark Tombol Verifikasi:** Menyelaraskan tombol "Verifikasi & Masukkan Antrian" dengan `whitespace-nowrap`, `gap-2`, dan ikon checkmark putih (`shrink-0`) sehingga tidak lagi terpotong/menempel aneh di pinggir tombol.
- **Pengujian Unit Otomatis Terisolasi:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/WorkRequestAssignOperatorModalTest.php` - 4 passed, 23 assertions).

### QQ. Fitur Show / Hide Password Presisi Lampiran 3 & 4 gaya recent.design (Baru Selesai & Teruji 100%)
- **Wadah Kapsul & Tombol Pastel (Lampiran 3):** Input password ditata ulang menjadi kapsul lapang dengan tombol kotak rounded pastel ungu/biru lembut (`bg-[#EEF0FE] text-[#4F46E5]`) dan ikon mata `<i class="fa-solid fa-eye"></i>` di ujung kanan.
- **Animated Countdown Line Indicator (Lampiran 4):**
  - Saat tombol diklik untuk melihat password, latar tombol berganti menjadi hijau pastel lembut (`bg-[#E8FAF0] text-[#16A34A]`) dan ikon berganti menjadi `<i class="fa-solid fa-eye-slash"></i>`.
  - Dilengkapi garis tepi border SVG animasi (`stroke="#22c55e"` dengan `pathLength="100"`) yang melingkari sudut tombol secara halus menghitung mundur dari 5 detik hingga 0 detik.
  - Setelah 5 detik habis (atau diklik ulang), password otomatis tersamarkan kembali dan tombol kembali ke kondisi awal Lampiran 3.
- **Zero Clipboard Risk (Pabrik Security Best Practice):** Menghilangkan fungsi copy ke clipboard agar kredensial tidak tertinggal di komputer kerja bersama (*shared terminal*).
- **Automated Unit Testing:** Lulus 100% pada `tests/Unit/LoginPasswordToggleTest.php` (1 passed, 13 assertions).

### RR. Auto-Dismiss & Smooth Fade-Out Flash Notification Banner (Baru Selesai & Teruji 100%)
- **Auto-Dismiss 3.5 Detik & Transisi Halus:** Menambahkan Alpine.js controller `x-data="{ show: true }"` dengan `x-init="setTimeout(() => show = false, 3500)"` pada banner notifikasi sukses/error layout utama, layout admin, dan halaman detail tiket.
- **Transisi Fade-Out:** Menggunakan `x-transition:leave` (`opacity-100 scale-100` ke `opacity-0 scale-95`) dengan durasi 300ms agar banner menghilang secara halus tanpa patah.
- **Tombol Tutup Manual [ ✕ ]:** Menyematkan tombol tutup silang cepat di sisi kanan notifikasi (`@click="show = false"`).
- **Automated Unit Testing:** Lulus 100% pada `tests/Unit/FlashNotificationDismissTest.php` (2 passed, 11 assertions).

### SS. Penataan Ulang & Optimasi Ruang UI Material Handling (Baru Selesai & Teruji 100%)
- **Penjajaran Sebaris Permanen (Single-Row Nav):** Mengunci kontainer tab utama (`Antar Mesin` dan `Siap Diambil`) bersama tombol `Refresh Data` dalam satu baris horizontal permanen (`flex items-center justify-between gap-2`), mengeliminasi masalah tombol refresh jatuh/turun ke baris bawah pada layar tablet dan perangkat sentuh.
- **Eliminasi Badge Indikator Redundan:** Menghapus badge teks `[ X Item Menunggu Ambil ]` dan `[ X Item Siap Diambil ]` yang memboroskan ruang horizontal, karena jumlah tiket sudah tertera jelas pada counter tab masing-masing.
- **Rampingkan Panel Filter (Simetris 2x2):** Menyusun ulang panel filter dari yang sebelumnya 3 baris boros menjadi format grid 2x2 simetris di tablet/mobile (Baris 1: Tanggal & Asal, Baris 2: Tujuan & Cari Kata Kunci) dan otomatis mengalir rapi dalam 1 baris lurus di desktop (`lg:`/`md:`).
- **Automated Unit Testing:** Lulus 100% pada `tests/Unit/MaterialHandlingResponsiveLayoutTest.php` (1 passed, 6 assertions).

### TT. Eliminasi Tombol Refresh Redundan di Header Atas Material Handling (Baru Selesai & Teruji 100%)
- **Penghapusan Tombol Refresh di Header Atas:** Menghapus tombol refresh yang terletak di header atas persis di samping tombol `Scan QR` pada `resources/views/work_station_supplies/index.blade.php`.
- **Fokus Kontrol Tunggal:** Memusatkan kontrol penyegaran antrean tugas logistik MH ke tombol `Refresh Data` presisi di samping tab navigasi `Antar Mesin` dan `Siap Diambil`, menjaga kerapian header atas agar hanya menyisakan tombol aksi krusial (`Scan QR` dan `Semua WRI`).
- **Automated Unit Testing:** Diperbarui dan lulus 100% pada `tests/Unit/MaterialHandlingResponsiveLayoutTest.php` (1 passed, 8 assertions) dan `tests/Unit/BladeAlpineScopeTest.php` (2 passed, 5 assertions).

### UU. Penataan Sejajar Filter Transaksi & Auto-Submit Instan Saat Memilih Opsi (Baru Selesai & Teruji 100%)
- **Penjajaran Sebaris (Horizontal Single Row):** Mengubah bilah pencarian & filter transaksi (`resources/views/transactions/index.blade.php`) dari format grid yang sebelumnya menumpuk vertikal menjadi 3 baris di perangkat sentuh/mobile menjadi 1 baris lurus rapi (`flex items-center gap-2 w-full`). Input pencarian fleksibel (`flex-1`), dropdown jenis transaksi proporsional (`w-36 sm:w-56`), dan tombol aksi `Cari` & `Reset` tertambat sejajar di sisi kanan.
- **Eksekusi Filter Otomatis (Auto-Submit on Select):** Menambahkan atribut `onchange="this.form.submit()"` pada seluruh dropdown filter sistem:
  - Transaksi Komponen: `select[name="type"]`
  - Master Komponen: `select[name="category"]`
  - Manajemen Pengguna (Admin Users): `role`, `work_area`, dan `department_id`
  - Audit Trail (Admin Audit Logs): `period`, `module`, dan `user_id`
  Pengguna tidak perlu lagi memindahkan kursor ke tombol "Cari" atau menekan tombol Enter; filter langsung aktif seketika setelah suatu opsi dipilih.
- **Automated Unit Testing:** Berhasil 100% pada SQLite `:memory:` (`tests/Unit/TransactionFilterBarAlignmentTest.php` - 2 passed, 17 assertions).

### VV. Inovasi Penyeimbangan Presisi Bottom Navigation Bar Mobile untuk Pengguna Akses Tunggal (Baru Selesai & Teruji 100%)
- **Akar Masalah Posisi Tombol SCAN Melenceng:** Pada pengguna yang hanya memiliki 1 hak akses menu (seperti Operator Stasiun Mesin yang hanya memiliki izin `work-request.view`), Slot 2 terisi `WRI`, tetapi Slot 4 kosong karena user tidak memiliki akses `Transaksi` maupun `Material Handling`. Akibatnya, bilah navigasi hanya memuat 4 item (`Home`, `WRI`, `SCAN`, `Menu`), menyebabkan tombol SCAN terdorong ke posisi 62.5% layar (miring ke kanan) bukan di tengah-tengah.
- **Inovasi Formasi Simetris 2-1-2:** Mengisi Slot 4 secara dinamis dan bermakna:
  - Jika user memiliki akses `work-request.create`: Menampilkan tombol aksi cepat **`+ Buat WR`** (`route('work-requests.create')`) dengan ikon `<i class="fa-solid fa-circle-plus"></i>`.
  - Fallback aman lainnya: `Cetak WR` (`route('work-requests.print-queue')`) atau `Profil` (`route('profile.edit')`).
- **Hasil Visual:**
  - Sayap Kiri: `Home` & `WRI` (2 item)
  - Titik Tengah: `SCAN` (Floating FAB tepat di titik 50% horizontal layar)
  - Sayap Kanan: `+ Buat WR` & `Menu` (2 item)
  Setiap slot memiliki bobot flex yang seimbang (`flex: 1`), menjamin tombol SCAN selalu berada presisi di tengah-tengah layar ponsel pada seluruh role pengguna.
- **Automated Unit Testing:** Lulus 100% pada SQLite `:memory:` (`tests/Unit/MobileBottomNavSymmetryTest.php` - 3 passed, 20 assertions).

### WW. Transformasi Bar Tab Work Request Menjadi Dropdown Kaya Sejajar Pencarian (Baru Selesai & Teruji 100%)
- **Eliminasi Baris Tab Meluap & Navigasi Geser:** Mengganti barisan tab status horizontal panjang yang meluap dan membutuhkan scrollbar/tombol geser chevron di perangkat sentuh menjadi **Alpine.js Custom Dropdown** popover yang kaya dan kompak.
- **Formasi 2-Kolom Sejajar (Single Row):** Menata bilah kontrol WRI menjadi 1 baris lurus (`flex items-center gap-2 w-full`) dengan 2 kolom:
  - **Kolom 1:** Dropdown Status Kaya (`w-48 sm:w-60 lg:w-64 shrink-0`) menampilkan ikon dinamis (`fa-list`, `fa-stamp`, `fa-gears`, `fa-dolly`, `fa-circle-check`), label status aktif, dan badge notifikasi angka real-time.
  - **Kolom 2:** Kotak Pencarian (`flex-1 min-w-0`) yang fleksibel melebar mengisi sisa ruang tanpa terputus ke baris bawah.
- **Sinkronisasi Ganda (Two-Way Sync):** Mengklik salah satu dari 5 kartu ringkasan KPI di bagian atas tetap berfungsi penuh dan secara otomatis memperbarui label, ikon, dan badge tombol dropdown di bawahnya secara instan tanpa reload halaman.
- **Automated Unit Testing:** Lulus 100% pada SQLite `:memory:` (`tests/Unit/WorkRequestControlBarDropdownTest.php` - 1 passed, 13 assertions).

### XX. Penambahan Indikator KPI & Tab Dropdown Filter Tiket Work Request yang Ditolak/Batal (Baru Selesai & Teruji 100%)
- **Scope Query Rejection Terpusat:** Menambahkan metode `scopeRejected(Builder $query)` pada model [WorkRequest.php](file:///C:/xampp/htdocs/hpk-gudang-inhouse/app/Models/WorkRequest.php) untuk menyaring seluruh tiket yang mengalami penolakan (`approval_pengaju = 'Ditolak'` atau `approval_penerima = 'Ditolak'`) atau dibatalkan (`status = 'cancelled'`).
- **Penanganan Controller & Agregasi Real-time:** Memperbarui [WorkRequestController.php](file:///C:/xampp/htdocs/hpk-gudang-inhouse/app/Http/Controllers/WorkRequestController.php) untuk menangani status filter `ditolak`, `batal`, `rejected`, maupun `cancelled`. Menambahkan kolom agregasi SQL `SUM(CASE WHEN approval_pengaju = 'Ditolak' OR approval_penerima = 'Ditolak' OR status = 'cancelled' THEN 1 ELSE 0 END) as rejected_count` dan meneruskan variabel `$rejectedCount` ke tampilan view.
- **Simetri Grid 6 Kartu KPI Sempurna:** Memperluas kartu ringkasan KPI di antrean WRI ([resources/views/work_requests/index.blade.php](file:///C:/xampp/htdocs/hpk-gudang-inhouse/resources/views/work_requests/index.blade.php)) dari 5 menjadi 6 kartu dengan formasi responsif yang simetris sempurna di semua breakpoint:
  - Mobile: `grid-cols-2` (3 baris x 2 kolom simetris, tanpa kartu yatim/ganjil).
  - Tablet: `sm:grid-cols-3` (2 baris x 3 kolom simetris).
  - Desktop: `lg:grid-cols-6` (1 baris lurus 6 kolom).
  - Kartu ke-6 (`Ditolak / Batal`) didesain dengan aksen warna rose/merah tegas, ikon `<i class="fa-solid fa-ban"></i>`, dan badge counter reaktif. Mengklik kartu ini langsung memicu `switchTab('ditolak', ...)` tanpa reload halaman.
- **Opsi ke-6 pada Dropdown Popover WRI:** Menambahkan item ke-6 pada menu dropdown status serta memperbarui pemicu dropdown untuk menampilkan ikon `fa-ban text-rose-600`, label `Ditolak / Batal`, dan badge `bg-rose-100 text-rose-800` saat status 'ditolak' aktif.
- **Automated Unit Testing:** Lulus 100% pada SQLite `:memory:` (`tests/Unit/WorkRequestControlBarDropdownTest.php` - 1 passed, 16 assertions dan `tests/Unit/WorkRequestSlaAndStatusFilterTest.php` - 2 passed, 26 assertions).

### YY. Safe Scanner Teardown & Fail-Safe Camera Modal Dismissal (Baru Selesai & Teruji 100%)
- **Akar Masalah Crash "Cannot stop, scanner is not running":** Pustaka `html5-qrcode` melemparkan *synchronous exception* (`throw "Cannot stop..."`) saat metode `.stop()` dipanggil pada scanner yang belum berhasil aktif (misalnya saat pengguna belum mengizinkan akses kamera browser). Exception ini menginterupsi eksekusi Alpine.js pada `@click="closeScanner()"`, sehingga baris `this.scannerOpen = false` tidak pernah dicapai dan modal pemindai terkunci tidak bisa ditutup.
- **Fail-Safe Close (Prioritas Penutupan Modal):** Mengubah urutan eksekusi pada `closeScanner()` dan `closeMhScanner()` di mana variabel status modal (`this.scannerOpen = false` / `this.mhScannerOpen = false`) dieksekusi seketika di baris pertama sebelum proses teardown kamera. Modal dijamin 100% langsung tertutup tanpa hambatan.
- **Pengecekan State Kamera & Proteksi Try-Catch Menyeluruh:**
  - Memeriksa state scanner secara aman melalui `scanner.getState() === 2 || scanner.getState() === 3` sebelum memanggil `scanner.stop()`.
  - Membungkus seluruh proses `stop()` dan `clear()` ke dalam blok `try ... catch` terisolasi sehingga kegagalan perangkat keras kamera tidak akan memicu unhandled rejection di browser console.
  - Memutus referensi instance `this.html5QrCode = null` seketika untuk mencegah kondisi balapan (*race conditions*) atau *double-stop*.
- **Pemberitahuan Izin Kamera Ramah Pengguna:** Menambahkan panel pesan informatif bernuansa amber lembut (`x-show="cameraErrorMessage"`) ketika izin kamera ditolak atau kamera sedang sibuk, mengedukasi pengguna bahwa mereka tetap dapat mencari part dengan mengetik kode secara manual atau memindai menggunakan scanner gun barcode USB/Bluetooth.
- **Dukungan Menyeluruh di Seluruh Modul:** Diterapkan serentak pada layout operasional (`layouts/app.blade.php`), layout admin (`layouts/admin.blade.php`), Material Handling (`work_station_supplies/index.blade.php`), dan form transaksi (`transactions/create.blade.php`).
- **Automated Unit Testing:** Lulus 100% pada SQLite `:memory:` (`tests/Unit/GlobalScannerSafetyTest.php` - 4 passed, 21 assertions dan `tests/Unit/BladeAlpineScopeTest.php` - 2 passed, 5 assertions).

### ZZ. Format Catatan Defect Ringkas & Pemisahan Kartu Defect Logistik / Gudang (Baru Selesai & Teruji 100%)
- **Catatan Defect Presisi & Ringkas (Issue 1):**
  - Jika pengguna mengisi alasan defect pada modal aksi, sistem menyimpan dan menampilkan alasan tersebut secara persis (*verbatim* tanpa tambahan teks bertele-tele).
  - Jika pengguna mengosongkan alasan defect, sistem secara otomatis menetapkan catatan ringkas `"Cacat / reject fisik"` menggantikan teks *boilerplate* lama yang terlalu panjang.
  - Di frontend Alpine.js, ditambahkan pembantu `formatDefectNotes(notes)` yang secara cerdas membersihkan dan mengekstrak alasan inti dari data historis/lama sehingga tampilan selalu bersih dan elegan.
- **Kartu Khusus Riwayat Defect pada Timeline Logistik / Gudang (Issue 2):**
  - Sebelumnya, saat Material Handling menginput defect pada tahap *Menuju Pengaju* atau Gudang menginput defect pada tahap *Sudah Diambil*, riwayat ditampilkan keliru dalam format kartu pengiriman biru (*MATERIAL HANDLING*) atau kartu penerimaan hijau (*DITERIMA GUDANG*).
  - Kini dipisahkan menjadi kartu khusus bernuansa rose/merah lembut (`bg-rose-50/80 border-rose-200`) dengan judul spesifik:
    - `DEFECT / REJECT SAAT PICKUP MH` (untuk penjemputan logistik)
    - `DEFECT / REJECT PENERIMAAN GUDANG` (untuk serah terima fisik gudang)
  - Menampilkan angka pengurangan kuantitas tegas bernuansa merah (`-X PCS`), ikon peringatan tanda seru bulat `fa-circle-exclamation text-rose-600`, nama kurir/penerima, serta catatan alasan defect yang ringkas dan jelas.
- **Penutupan Otomatis Tiket Jika 100% Kuantitas Defect:**
  - Jika seluruh kuantitas tiket dicatat sebagai defect oleh MH atau operator (sehingga kuota aktif tersisa 0 pcs), sistem tidak lagi mencatat *ghost process log* berkuantitas 0 pcs.
  - Tiket secara otomatis ditandai `is_final = true` dengan status `'Selesai (Defect Penuh)'`.
  - Header modal pelacakan menampilkan indikator peringatan segitiga merah dengan badge informatif: `'Semua Defect (0 Pcs)'`.
- **Automated Unit Testing:** Lulus 100% pada SQLite `:memory:` (`tests/Unit/WorkRequestDefectCardAndNotesTest.php` - 5 passed, 27 assertions dan `tests/Unit/BladeAlpineScopeTest.php` - 2 passed, 5 assertions).

### AAA. Optimasi Performa Transaksi & Penyempurnaan Rincian Slip Mutasi (Baru Selesai & Teruji 100%)
- **Eliminasi Karakter "+" Ganda pada Tombol Transaksi Baru:** Memperbaiki tombol aksi pada [resources/views/transactions/index.blade.php](resources/views/transactions/index.blade.php) sehingga tidak lagi menampilkan tanda plus ganda (`[+ + Transaksi Baru]`), melainkan tampil rapi dan elegan: `[+ Transaksi Baru]`.
- **Solusi Performa Lemot (Pemangkasan dari ~5.000 ms Menjadi ~8 ms):**
  - *Akar Masalah:* Saat memuat halaman transaksi baru (`/transactions/create`), pemetaan 613 komponen ke JSON secara sinkron memanggil `$c->image_url` yang memicu 5.000 kali pengecekan berkas fisik ke disk/NAS, ditambah 613 query N+1 `stockBalances()->sum('quantity')`.
  - *Solusi:* Menggunakan satu query builder agregat terpadu (`COALESCE(SUM(stock_balances.quantity), 0)`) dan memindahkan streaming foto komponen menjadi asinkron (`/components/{id}/photo-stream`) yang hanya dipanggil peramban ketika komponen tersebut dipilih oleh pengguna. Waktu muat terpangkas hingga 600x lebih cepat.
- **Penyempurnaan Tampilan Rincian Transaksi / Slip Mutasi:**
  - *Logika Rak Kontekstual:* Pada transaksi Inbound (penerimaan), barang berasal dari supplier eksternal / hasil pengerjaan WRI, bukan dari rak gudang yang sama (`Dari: Rak 8 -> Ke: Rak 8`). Pada slip rincian dan cetak massal, kolom kini kontekstual: Inbound menampilkan `Asal Pengiriman (Vendor/WR)` dan `Rak Penerimaan Gudang`; Outbound menampilkan `Rak Asal Pengambilan` dan `Tujuan Distribusi (Lini/SPK)`.
  - *Blok Tanda Tangan Kontekstual:* Pada Inbound, petugas gudang adalah **Penerima** (sisi kanan), sedangkan pihak luar adalah **Pengirim/Kurir** (sisi kiri). Pada Outbound, petugas gudang adalah **Pengeluar** (sisi kiri) dan lini/user adalah **Penerima** (sisi kanan).
  - *Solusi Garis Tanda Tangan Pecah di Mobile:* Mengganti titik-titik `( .................................... )` yang terpotong menjadi 3 baris di mobile menjadi garis tanda tangan solid anti-overflow dengan teks `( Nama Terang & TTD )`.
- **Diferensiasi Inbound WR vs Inbound Langsung pada Filter & Riwayat:**
  - Menambahkan filter dropdown: `Inbound dari Work Request (WRI)` vs `Inbound Transaksi Langsung (PO/Supplier)`.
  - Menyematkan badge visual terpisah pada riwayat transaksi: badge ungu/indigo berikon pabrik `Inbound (Hasil WR)` dengan tautan tiket WRI vs badge hijau zamrud berikon pengantaran `Inbound (Langsung)`.
  - Normalisasi otomatis pada backend: Transaksi Inbound otomatis memaksa `from_location_id = null`.
- **Automated Unit Testing:** Lulus 100% pada SQLite `:memory:` (`tests/Unit/TransactionPerformanceAndRefinementTest.php` - 5 passed, 29 assertions, `tests/Unit/TransactionSlipTest.php` - 2 passed, 23 assertions, dan `tests/Unit/TransactionFormSimplificationTest.php` - 3 passed, 20 assertions).

### BBB. Dynamic Accordion Hover Navigation & Micro-Interactions Sub-Menu WRI (Baru Selesai & Teruji 100%)
- **Transformasi Sub-Navigasi Work Request (*Dynamic Accordion Pill*):**
  - Mengubah perilaku sub-navigasi [resources/views/work_requests/partials/sub_nav.blade.php](resources/views/work_requests/partials/sub_nav.blade.php) yang sebelumnya menyembunyikan total label non-aktif via kondisional Blade PHP. Seluruh teks label kini dirender ke DOM dengan kelas utility Tailwind yang reaktif dan beranimasi mulus.
  - **Status Default (Compact Pill):** Tab yang sedang aktif tetap terbuka penuh (*fully expanded*) sebagai penanda halaman aktif, sedangkan tab non-aktif berada dalam kondisi ramping (*collapsed* - hanya menampilkan Icon + Badge).
  - **Efek Hover Meluncur (*Smooth Slide-Fade Reveal*):** Saat kursor mouse diarahkan (*hover*) atau difokuskan lewat keyboard (`focus-visible`), label teks meluncur keluar secara instan dan halus (`transition-all duration-300 ease-out max-w-0 opacity-0 -> group-hover:max-w-[130px] group-hover:opacity-100 group-hover:ml-1.5`).
  - **Efek Menguncup Lembut (*Mouse Leave*):** Saat kursor dijauhkan, teks label menyusut kembali dengan mulus tanpa loncatan layout (*zero layout jitter/shift*).
- **Inovasi Micro-Interactions & Gliding Badges:**
  - **Gliding Badges:** Notifikasi counter cetak (misal antrean 15 tiket siap cetak) dan badge restock kritis bergeser ke samping secara harmonis mengikuti pergerakan teks label tanpa berkedip atau loncat.
  - **Micro-Pop Scale Effect:** Icon tab merespons sentuhan kursor dengan pembesaran mikro halus `group-hover:scale-110 transition-transform duration-200`.
  - **Keyboard Accessibility (`a11y`):** Mendukung navigasi tombol `Tab` keyboard via state `group-focus-visible:`, memastikan kesetaraan aksesibilitas tanpa mouse.
- **Kompilasi & Pengujian:**
  - Build Vite produksi: `npm run build` sukses (57 modules transformed, aset terkompilasi ke `public/build/assets/app-DSdvPfXH.css`).
  - Integritas Blade & Tag HTML: Lulus 100% pada `tests/Unit/BladeAlpineScopeTest.php` (2 passed, 5 assertions).

### CCC. Pembatasan Hak Akses Indikator KPI Dashboard Khusus Gudang, Supervisor, & Admin (Baru Selesai & Teruji 100%)
- **Penanganan Akses Metrik Gudang bagi Staf/Operator Lini:**
  - Sebelumnya, 4 kartu ringkasan KPI Dashboard (*Total Komponen SKU*, *Stok Menipis Kritis*, *Transaksi Hari Ini*, dan *Menunggu Approval*) dapat dilihat oleh seluruh pengguna, termasuk operator dan staf mesin center yang tidak menangani operasional gudang.
- **Implementasi Helper Terstandar `canViewWarehouseMetrics()` pada Model User:**
  - Ditambahkan ke [app/Models/User.php](app/Models/User.php) untuk memeriksa hak otorisasi secara presisi:
    1. Admin (`admin_gudang`, `admin`) atau user dengan hak akses global (`can_access_all_departments = true`).
    2. Supervisor (`supervisor`).
    3. Personel Bagian Gudang: Staf atau operator dengan `work_area = 'gudang'` atau departemen berlabel `Gudang`.
  - Non-gudang (operator/staf mesin Laser, M1, UNP, Band Saw, Material Handling non-gudang) secara otomatis dievaluasi `false`.
- **Proteksi Antarmuka Dashboard:**
  - Seluruh blok `<!-- 2. TOP KPI METRICS STATS -->` pada [resources/views/dashboard.blade.php](resources/views/dashboard.blade.php) dibungkus dengan `@if(Auth::user()->canViewWarehouseMetrics())`.
  - Staf/operator mesin disajikan antarmuka bersih yang langsung fokus ke menu *Work Request Mesin* tanpa interupsi metrik pergudangan.
- **Pengujian & Integritas:**
  - Dibuat test suite baru [tests/Unit/DashboardMetricsAccessTest.php](tests/Unit/DashboardMetricsAccessTest.php) di SQLite in-memory: 5 passed (11 assertions).
  - Validasi Blade tag balance [tests/Unit/BladeAlpineScopeTest.php](tests/Unit/BladeAlpineScopeTest.php): 2 passed (5 assertions).
  - Build aset Vite: `npm run build` selesai tanpa kendala.

### DDD. Fitur Pengaturan Profil & Pengubahan Username Akun (Baru Selesai & Teruji 100%)
- **Tata Letak Kartu Tunggal Bertingkat (Single-Column Stacked Cards):**
  - Mengubah antarmuka `/profile` dari 2 kolom menjadi tata letak bertumpuk yang teratur: Hero Profile Card di atas, diikuti kartu formulir informasi profil, kartu perbarui kata sandi, dan kartu hapus akun di bagian bawah.
  - Avatar squircle dengan gradasi HPK Blue (`#6ba1f7` ke `#3a7bf0`), inisial nama tebal, serta titik hijau berkedip status online.
- **Dukungan Pengubahan Username:**
  - Menambahkan input field *Username Akun* dengan leading icon `@` (`fa-solid fa-at`) dalam grid 3-kolom bersama Nama Lengkap dan Alamat Email.
  - Aturan validasi ketat di `ProfileUpdateRequest`: `required`, `string`, `min:3`, `max:30`, `alpha_dash`, dan `Rule::unique` untuk mencegah duplikasi username.
- **Tombol Aksi Pil Royal Blue:**
  - Tombol `[ 🔄 Perbarui Kata Sandi > ]` dan `[ Simpan Perubahan ]` menggunakan gaya Royal Blue kontras tinggi dengan fallback gaya inline anti-hilang saat dev server mati.
- **Pengujian Unit Terisolasi:**
  - Lulus 100% pada [tests/Unit/ProfileSettingsPageTest.php](tests/Unit/ProfileSettingsPageTest.php) (5 passed, 71 assertions).

### EEE. Redesign Sidebar Drawer Menjadi Gaya "5. Elegant Glass" (Baru Selesai & Teruji 100%)
- **Translucent Frosted Glass Effect:**
  - Sidebar drawer mengadopsi latar belakang kaca semi-transparan (`.hpk-sidebar` dengan `backdrop-filter: blur(28px)` dan gradien lembut `linear-gradient(175deg, rgba(224, 242, 254, 0.94) 0%, ...)`).
- **Header Identitas Resmi PT. Hydraxle Perkasa:**
  - Menampilkan logo resmi HPK (`images/logo_hpk.webp`), judul perusahaan **PT. HYDRAXLE PERKASA**, dan subjudul identitas **"WMS In-House Karoseri"** pada `layouts/app.blade.php` dan `layouts/admin.blade.php`.
- **Navigasi 2-Tone & Kapsul Royal Blue:**
  - Menu aktif disorot dengan kapsul pill Royal Blue (`#1a56db`) dengan teks dan ikon putih bersih, sementara menu inaktif berlatar transparan dengan transisi hover kaca.
- **Dock Pengguna Bawah Terpadu:**
  - Kartu profil bawah menampilkan avatar bulat, inisial nama, online indicator dot, tombol gear ke pengaturan profil, serta tombol keluar berkonfirmasi.
- **Pengujian Unit:**
  - Lulus 100% pada [tests/Unit/SidebarProfileDockTest.php](tests/Unit/SidebarProfileDockTest.php) (2 passed, 18 assertions).

### FFF. Sistem Pop-Up Konfirmasi & Notifikasi Feedback Global (3 Pola Interaksi - Baru Selesai & Teruji 100%)
- **Eliminasi 100% Dialog Native Browser (`confirm()`):**
  - Seluruh pemanggilan `confirm(...)` dihilangkan dan dialihkan ke komponen modal dialog terpadu.
- **Pola 1 — Global Confirmation Modal (`<x-confirm-modal />`):**
  - Backdrop kaca frosted glass, pulsing squircle icon, colored ambient glow (Rose/Amber/Blue), eksekusi formulir dinamis otomatis ber-CSRF (`POST`/`DELETE`/`GET`) maupun JavaScript callback.
  - Dipasang pada Logout (Sidebar & Nav), Kosongkan Keranjang QR & Safety Stock, Tolak Tiket WRI, Setujui Rekonsiliasi Opname, Eksekusi Pemusnahan Disposal, Hapus User/Kategori/UoM, Reset Denah Rak, dan Sinkronisasi WR.
- **Pola 2 — Global Floating Glass Toast Notification (`<x-toast-notification />`):**
  - Notifikasi melayang (*Floating Glass HUD*) di kanan atas layar (desktop) dan pill melayang di ponsel.
  - Otomatis menangkap flash session Laravel (`session('success')`, `session('error')`, `session('warning')`, `session('status')`) dan event JavaScript window `$dispatch('toast', ...)`.
  - Dilengkapi garis hitung mundur (*countdown progress timer* 3.5–5.5 detik), fitur jeda saat cursor diarahkan (*pause on hover*), tombol tutup instan `[X]`, dan tidak memblokir ritme kerja operator di lapangan (*anti modal fatigue*).
- **Pola 3 — Milestone Success Modal (`<x-milestone-modal />`):**
  - Pop-up dialog tengah layar khusus untuk 3 pencapaian besar: **Penerimaan Akhir di Gudang (WRI Final Receive + Auto Stok Tambah)**, **Penerbitan Tiket WRI Baru**, dan **Persetujuan Rekonsiliasi Stok Opname**.
  - Menyajikan kartu bukti data (*Part, Qty, Slot Rak Gudang, Status Stok*) serta tombol aksi cepat lanjutan (*Lihat di Peta Rak / Detail Tiket / Selesai*).
- **Pengujian & Validasi Kualitas:**
  - Lulus 100% pada [tests/Unit/GlobalConfirmAndFeedbackModalTest.php](tests/Unit/GlobalConfirmAndFeedbackModalTest.php) (5 passed, 18 assertions) dan [tests/Unit/FlashNotificationDismissTest.php](tests/Unit/FlashNotificationDismissTest.php).
  - Kepatuhan Zero Regression: Saldo Stok tetap **90.092,00** dan Transaksi Mutasi tetap **21**.

### J. Pemulihan Akun via Admin (Opsi 2) & Fitur Remember Me Otomatis (Update 18 Nov 2026 - Baru Selesai & Teruji 100%)
- **Fitur "Ingat Saya" (Remember Me & Remember Username):**
  - Penyimpanan identitas akun (Username/Email) otomatis pada `localStorage` peramban saat checkbox *"Ingat saya"* dicentang saat submit login.
  - Auto-fill dan auto-focus ke input password saat membuka kembali layar login (bahkan setelah logout atau sesi berakhir).
  - Pembersihan nilai default hardcoded (`'admin'` dan `'password'`) yang sebelumnya mengunci form.
- **Halaman Pusat Bantuan Lupa Password Resmi Ramah Intranet Offline (`/forgot-password`):**
  - Redesain total bertema HPK WMS (glassmorphism & logo resmi PT Hydraxle Perkasa).
  - Mengeliminasi ketergantungan server email eksternal/SMTP dan tautan WhatsApp publik (`wa.me`) yang tidak dapat beroperasi pada intranet/localhost pabrik offline.
  - Memusatkan bantuan pada fitur 1-klik Salin Nomor HP (Pure Client-side Clipboard Copy) dengan animasi hijau zamrud, tombol panggil langsung (`tel:`), serta pos tatap muka di Ruang Admin Gudang (Lantai 1).
  - Menghapus kolom input username formulir yang kini redundan sehingga layar lebih bersih, ringkas, dan cepat dimuat.
- **Fitur 1-Klik Reset Password Administrator (`/admin/users`):**
  - Tombol aksi cepat ikon kunci pada tabel manajemen pengguna (desktop & mobile) untuk mengembalikan kata sandi akun ke default (`hpkgudang`).
  - Dilengkapi dialog konfirmasi keamanan modal sebelum eksekusi.
  - Pencatatan otomatis ke `audit_logs` dengan event `password_reset` (nama admin pelaksana, target user, dan timestamp).
- **Pengujian Otomatis:**
  - Lulus 100% pada [tests/Feature/Auth/PasswordResetTest.php](tests/Feature/Auth/PasswordResetTest.php) dan [tests/Feature/Auth/AuthenticationTest.php](tests/Feature/Auth/AuthenticationTest.php) (10 passed, 38 assertions).

### GGG. Otentikasi Murni Username & Penyatuan "Satu Pintu" Manajemen Pengguna & Otorisasi (Update 18 Nov 2026 - Baru Selesai & Teruji 100%)
- **Eliminasi Total Email Pengguna Pabrik (Pure Username Authentication):**
  - Kolom `email` dan `email_verified_at` dilepas secara permanen dari tabel `users` database operasional melalui migrasi aman (`2026_11_18_140000_remove_email_columns_from_users_table.php`).
  - Seluruh 10 akun pengguna operasional pabrik tetap utuh (*Zero Data Loss*).
  - Login dan register kini 100% menggunakan `username` dan `password`.
  - Pembersihan menyeluruh pada `app/Models/User.php`, `LoginRequest.php`, `UserController.php`, dan `ProfileController.php`.
- **Pembersihan Antarmuka Pengguna & Profil:**
  - Form Tambah Pengguna (`admin/users/create.blade.php`) dan Edit Pengguna (`admin/users/edit.blade.php`) dibersihkan dari input email.
  - Tabel Manajemen Pengguna (`admin/users/index.blade.php`) memperbarui tajuk kolom menjadi `ID Masuk (Username)` dan placeholder pencarian `Cari nama atau username...`.
  - Formulir Profil (`profile/edit.blade.php`) beralih ke grid 2 kolom (Nama Lengkap & Username) dan banner verifikasi email dihapus.
- **Penyederhanaan Dashboard Admin ("Satu Pintu"):**
  - Menghapus redundansi kartu "Otorisasi & Izin" dari [resources/views/admin/dashboard.blade.php](resources/views/admin/dashboard.blade.php) yang sebelumnya memiliki tautan sama ke `admin.users.index`.
  - Menggabungkannya ke dalam kartu terpadu **"Manajemen Pengguna & Otorisasi"** dengan ikon `fa-users-gear`.
  - Menghapus rute usang `RoleController` dan direktori `resources/views/admin/roles/` yang tidak terpakai, serta menghapus rute verifikasi email Breeze usang dari `routes/auth.php`.
- **Pengujian & Validasi Kualitas:**
  - Lulus 100% pada seluruh rangkaian pengujian fitur: [tests/Feature/Auth/AuthenticationTest.php](tests/Feature/Auth/AuthenticationTest.php), [tests/Feature/Auth/PasswordResetTest.php](tests/Feature/Auth/PasswordResetTest.php), [tests/Feature/Auth/PasswordUpdateTest.php](tests/Feature/Auth/PasswordUpdateTest.php), [tests/Feature/Auth/RegistrationTest.php](tests/Feature/Auth/RegistrationTest.php), [tests/Feature/ProfileTest.php](tests/Feature/ProfileTest.php), dan [tests/Feature/UnifiedUserManagementTest.php](tests/Feature/UnifiedUserManagementTest.php) (26 passed, 126 assertions).

### HHH. Fleksibilitas Alur / Edit Routing WR Komponen Baru Sebelum Diproses (Update 20 Nov 2026 - Baru Selesai & Teruji 100%)
- **Tombol Aksi Ikon Pensil di Kolom Routing:**
  - Disematkan pada baris `Routing : Dept ...` di tabel antrean WR (`resources/views/work_requests/partials/_table_content.blade.php`).
  - Membuka modal pop-up interaktif (`resources/views/work_requests/partials/edit_routing_modal.blade.php`) dengan dukungan multi-tahap (tambah/hapus baris stasiun) dan catatan revisi alur.
- **Guardrail Keamanan Mutlak (*Zero Risk*):**
  - Hanya dapat diakses dan dieksekusi jika tiket belum final, pengerjaan fisik belum dimulai (`quantity_produced == 0` dan `quantity_received == 0`), serta belum ada log proses aktif.
- **Smart Approval Reset:**
  - Jika stasiun penerima awal dialihkan (misal dari `Dept M1` ke `Dept Laser`), sistem me-reset `approval_penerima` ke `'Menunggu'` dan mengosongkan penugasan operator agar Ka. Mesin stasiun baru dapat memeriksa kapasitasnya secara mandiri.
- **Pencatatan Audit Trail:**
  - Perubahan dicatat otomatis ke `work_request_logs` (event `routing_update`) dan `activity_log`.
- **Pengujian Otomatis:**
  - Lulus 100% pada [tests/Feature/WorkRequestRoutingUpdateTest.php](tests/Feature/WorkRequestRoutingUpdateTest.php) (6 passed, 26 assertions).

### III. Standardisasi Visual Badge Kolom Status Penerima Antrean Work Request (Update 20 Nov 2026 - Baru Selesai & Teruji 100%)
- **Penyeragaman Bentuk Horizontal Pill:**
  - Badge `Menunggu Pengaju` di kolom `STS PENERIMA` pada [resources/views/work_requests/partials/_table_content.blade.php](resources/views/work_requests/partials/_table_content.blade.php) diseragamkan styling-nya dengan `Belum Verifikasi` (`px-2 py-1 text-slate-700 bg-slate-100 border border-slate-300 rounded-lg text-[11px] font-bold whitespace-nowrap`).
  - Menghilangkan pembungkusan baris bertumpuk (*anti-wrap*) sehingga seluruh badge tabel sejajar dan konsisten.
- **Pengujian Otomatis:**
  - Lulus 100% pada [tests/Unit/WorkRequestTablePartialStatusTest.php](tests/Unit/WorkRequestTablePartialStatusTest.php) (2 passed, 14 assertions).

### JJJ. Sistem Keranjang (Cart / List) & Pendaftaran Komponen Baru pada Pembuatan Work Request (Update 20 Nov 2026 - Baru Selesai & Teruji 100%)
- **Dukungan Multi-Routing & Bulk Store Transaksional:**
  - `WorkRequestController::bulkStore` mendukung multi-routing (seperti `Dept Laser Cutting -> Dept M1`) dan memproses seluruh antrean keranjang dalam 1 transaksi database aman dengan penomoran resmi berurutan.
- **Pengujian Otomatis:**
  - Lulus 100% pada [tests/Unit/WorkRequestBulkStoreTest.php](tests/Unit/WorkRequestBulkStoreTest.php) (2 passed, 24 assertions) dan [tests/Feature/WorkRequestCreateCartTest.php](tests/Feature/WorkRequestCreateCartTest.php) (2 passed, 28 assertions).

### KKK. Redesign Halaman Buat Work Request Menjadi "2-Column Studio Layout" Berstandar Enterprise (Update 20 Nov 2026 - Baru Selesai & Teruji 100%)
- **Eliminasi "AI-Generated Look" & Harmonisa Desain:**
  - Menghilangkan *Rainbow Button Syndrome* (tabrakan 5 warna acak) menjadi palet terpadu: **Industrial Deep Navy (`#0a2342`)** untuk aksi utama, **Emerald Solid (`#16a34a`)** untuk tombol kirim pesanan, dan **Amber Soft** untuk penanganan part baru (non-master).
  - Menggantikan tombol bata *full-width* dengan komponen berbobot proporsional dan mikro-interaksi (*docked UOM PCS badge*, *manufacturing spec preview card*).
- **Tata Letak 2 Kolom Responsif (Studio Layout):**
  - **Sisi Kiri (Kolom 7/12):** Form builder pesanan terstruktur (Metadata strip pemohon/tanggal, unified search dock dengan tombol Scan & Part Baru terpasang rapi, inline drawer pendaftaran part non-master, kuantitas, alur stasiun penerima, dan tombol `Tambah ke List`).
  - **Sisi Kanan (Kolom 5/12 - Sticky):** *Live Cart Sidebar* (`Daftar Item Siap Kirim`) yang selalu terlihat di samping layar desktop tanpa perlu scroll ke bawah, lengkap dengan kartu item informatif, tombol hapus halus (*subtle trash icon*), ringkasan total tiket, dan tombol `Kirim Work Request`.
  - Pada layar mobile/tablet, otomatis bertransformasi menjadi 1 kolom vertikal yang intuitif dan nyaman disentuh.
- **Modal Popup Interaktif Kustom (Zero Native Browser Dialog):**
  - Menggantikan seluruh `window.alert()` dan `window.confirm()` bawaan browser dengan modal popup kustom berbasis Alpine.js (`confirmModal` & `alertModal`) berlatar *backdrop blur*, icon badge dinamis, dan tipografi selaras.
- **Pengujian Otomatis Terisolasi:**
  - Lulus 100% pada [tests/Feature/WorkRequestCreateCartTest.php](tests/Feature/WorkRequestCreateCartTest.php) dan [tests/Unit/WorkRequestBulkStoreTest.php](tests/Unit/WorkRequestBulkStoreTest.php) (4 passed, 52 assertions).

### LLL. Pemisahan Mutlak Komponen Ad-Hoc WR dari Master Komponen (Update 23 Nov 2026 - Selesai & Teruji 100%)
- **Prinsip Satu Pintu Master Komponen & Isolasi Part Ad-Hoc WR:**
  - Menambahkan kolom `source` (`'master'` vs `'wr_adhoc'`) pada tabel `components` melalui migrasi `2026_11_23_080000_add_source_to_components_table.php` dengan backfill aman atas 185 part lama berkode `NEW-%`.
  - Halaman **Master Komponen** (`ComponentController@index`), penghitungan Safety Stock (`WorkRequestController@safetyStock`), dan kartu metrik inventaris (`DashboardController`) secara ketat menyaring hanya `Component::masterCatalog()` (`source = 'master'`).
  - Komponen non-master yang dibuat melalui form WR (`quickStore`) disimpan dengan `source = 'wr_adhoc'` untuk kebutuhan pesanan saat itu saja ("buat lewat aja"), dan **100% terisolasi/disembunyikan** dari katalog Master Komponen tanpa tab sekunder.
- **Pembersihan Data Trial / Uji Coba:**
  - Menghapus bersih data trial lama dari database operasional: tiket `WR ID 969` beserta log/steps, serta komponen `Uji coba` (ID 16077), `asdsad` (ID 16078), dan `asd` (ID 16079).
- **Sinkronisasi Dokumentasi & Guardrails:**
  - Mencatat aturan bisnis **BR-18** ke dalam [docs/SOP_DAN_ALUR_PROSES.md](docs/SOP_DAN_ALUR_PROSES.md), [GEMINI.md](GEMINI.md), dan [.gemini/rules/guardrails.md](.gemini/rules/guardrails.md).
- **Pengujian Otomatis:**
  - Lulus 100% pada [tests/Unit/ComponentQuickStoreTest.php](tests/Unit/ComponentQuickStoreTest.php) (3 passed, 9 assertions) dan verifikasi script database mandiri.

### MMM. Modul Pengaturan Operasional & Fitur Validasi Scan QR Lembar Kerja WRI (Update 23 Nov 2026 - Selesai & Teruji 100%)
- **Tujuan & Ruang Lingkup:**
  - Memberikan fleksibilitas penuh bagi administrator untuk mengaktifkan atau menonaktifkan kewajiban pemindaian QR code lembar kerja fisik tiket WRI pada seluruh tahapan operasional lapangan (mulai pengerjaan mesin `(In)`, selesai mesin `(Out)`, serah terima MH `Pickup` & `Menuju Pengaju`, hingga penerimaan di gudang `Sudah Diambil`).
  - Tahap persetujuan administratif (Ka. Pengaju & Ka. Penerima) tetap berjalan via tombol klik manual tanpa kewajiban scan.
- **Modul Pengaturan Operasional Administrator (`/admin/operational-settings`):**
  - **Migrasi Database:** Menjalankan `2026_11_23_083000_add_operational_settings_defaults.php` menambahkan record konfigurasi `key = 'wr_scan_required'`, default `'0'` (NONAKTIF / OFF), grup `'operational'`.
  - **Model & Caching:** Memperbarui [app/Models/Setting.php](app/Models/Setting.php) dengan metode performa tinggi `isWrScanRequired(): bool` (cache 60 detik) dan `clearSettingCache(?string $key)`.
  - **Controller & Routing:** Menambahkan [app/Http/Controllers/Admin/OperationalSettingController.php](app/Http/Controllers/Admin/OperationalSettingController.php) serta rute admin `admin.operational-settings.index` dan `admin.operational-settings.update`.
  - **Antarmuka Pengaturan:** Menerbitkan [resources/views/admin/operational_settings/index.blade.php](resources/views/admin/operational_settings/index.blade.php) dengan iOS toggle switch interaktif dan kartu panduan operasional. Ditautkan pada sidebar admin, dropdown navigasi, dan dasbor admin. Menggunakan knob bulat fisik (`<span>`) reaktif berbasis Alpine.js dengan indikator status icon (check/xmark) dan transisi halus tanpa bergantung pada pseudo-class CSS.
- **Integrasi Antarmuka Operasional (Blade & Alpine.js):**
  - **Modal Proses & Dok Verifikasi:** Memperbarui [resources/views/work_requests/partials/process_modal.blade.php](resources/views/work_requests/partials/process_modal.blade.php) dan [resources/views/work_requests/show.blade.php](resources/views/work_requests/show.blade.php).
  - Saat pengaturan `wr_scan_required` aktif dan tiket memasuki langkah fisik:
    - Tombol aksi proses terkunci (`disabled`) berstatus *"Pindai QR Lembar WRI Dahulu"*.
    - **Penegakan Pemindaian Murni (Anti-Ketik Manual):** Menghilangkan kolom input teks manual dan tombol "Cek". Verifikasi murni melalui tombol utama *"Buka Kamera Pemindai QR"* (live camera `html5-qrcode`) dan *hardware burst listener* untuk alat barcode scanner gun fisik (< 60ms/karakter; otomatis memblokir pengetikan keyboard biasa).
    - Setelah nomor WRI atau part number terverifikasi cocok, tombol aksi langsung terbuka (`unlocked`) dan input tersembunyi `scanned_wri_code` dikirim ke backend.
- **Validasi Sisi Server (Backend):**
  - [app/Http/Controllers/WorkRequestController.php](app/Http/Controllers/WorkRequestController.php) pada method `processAction` secara ketat memverifikasi bahwa `scanned_wri_code` sesuai dengan nomor tiket WRI atau nomor part sebelum transaksi perubahan status dieksekusi.
- **Pengujian Otomatis Terisolasi:**
  - Lulus 100% pada [tests/Unit/OperationalSettingTest.php](tests/Unit/OperationalSettingTest.php) (4 passed, 6 assertions) dan [tests/Unit/ComponentQuickStoreTest.php](tests/Unit/ComponentQuickStoreTest.php) (3 passed, 9 assertions).
- **Perbaikan Audit Log ActivityLogger:**
  - Menyelaraskan pemanggilan log audit pengaturan pada `OperationalSettingController` menggunakan `ActivityLogger::system('updated', ...)` serta menambahkan alias metode `ActivityLogger::user` pada [app/Services/ActivityLogger.php](app/Services/ActivityLogger.php) guna mencegah potensi crash `Call to undefined method ActivityLogger::user()`.
- **Perbaikan Layering Z-Index Modal Kamera:**
  - Memperbaiki hierarki z-index [process_camera_modal.blade.php](resources/views/work_requests/partials/process_camera_modal.blade.php) menjadi `z-[99999]` (`style="z-index: 99999 !important;"`) dan backdrop `style="z-index: 99998 !important;"`, serta mendaftarkan ekstensi `zIndex` pada `tailwind.config.js`. Menghilangkan bug di mana kamera scanner sebelumnya tertimpa di belakang modal proses (`z-50`).

### NNN. Integrasi Animasi Enterprise Modern (GSAP + ScrollTrigger + Lenis + Motion) (Update 23 Nov 2026 - Selesai & Teruji 100%)
- **Tujuan & Arsitektur "Enterprise-Safe Hybrid Mode":**
  - Meningkatkan estetika visual sistem (*visual delight*, Awwwards/Apple-grade UI) ke level kelas atas menggunakan pustaka animasi industri modern: **GSAP (GreenSock)**, **ScrollTrigger**, **Lenis** (smooth scroll berbasis momentum), dan **Motion** (spring micro-interactions).
  - Menerapkan arsitektur hibrida aman yang mengutamakan kenyamanan kerja staf operasional pabrik:
    - **Proteksi Tabel & Modal (`data-lenis-prevent`):** Lenis dinonaktifkan secara otomatis pada tabel antrean panjang (`#wri-table-container`, `.table-responsive`) dan seluruh modal dialog (`[role="dialog"]`, `process_modal`, `process_camera_modal`) sehingga scrolling di dalam tabel dan modal tetap 100% instan, presisi, dan tidak mengalami efek licin/melayang (*scroll fatigue*).
    - **Deteksi Otomatis Perangkat Sentuh (Mobile Touchscreen):** Lenis dinonaktifkan pada perangkat touchscreen (smartphone/tablet lapangan) untuk menghemat baterai dan menjaga responsivitas geser native OS.
    - **Pencegahan Jitter Hardware Barcode Scanner:** Input burst dari alat pemindai fisik tidak mengalami scroll jump paksa.
- **Modul JavaScript Animasi Terisolasi:**
  - [resources/js/animations/smooth-scroll.js](resources/js/animations/smooth-scroll.js): Mengelola instance Lenis (`lerp: 0.08`, `wheelMultiplier: 0.95`), sinkronisasi `gsap.ticker`, dan otomatisasi `protectModalsAndTables()` via `MutationObserver`.
  - [resources/js/animations/motion-effects.js](resources/js/animations/motion-effects.js):
    - `initTitleWordReveal()`: Efek reveal judul kata per kata pada elemen `.gsap-reveal-title` dengan durasi cepat (< 0.45s) dan `once: true`.
    - `initCardStagger()`: Efek kartu masuk mengalir bertahap pada kontainer `.gsap-stagger-cards`.
    - `initNumberCounters()`: Efek rolling counter angka metrik KPI dari 0 ke nilai riil pada `.counter-value`.
    - `initMotionSpring()`: Spring physics modern berbasis Web Animations API untuk mikro-interaksi tombol/kartu.
    - `cleanupMotionEffects()`: Pembersihan instan memori ScrollTrigger saat pergantian halaman Turbo.
- **Integrasi Lifecycle Hotwire Turbo di [resources/js/app.js](resources/js/app.js):**
  - Menginisialisasi animasi pada `DOMContentLoaded` dan `turbo:load`.
  - Memanggil pembersihan memori `destroySmoothScroll()` dan `cleanupMotionEffects()` pada `turbo:before-render` dan `turbo:before-visit` untuk mencegah kebocoran memori (*memory leak*).
- **CSS Utility Akselerasi GPU di [resources/css/app.css](resources/css/app.css):**
  - Menambahkan utility `.hover-spring` berbasis cubic-bezier GPU-accelerated (`transform: translate3d`) untuk interaksi hover organik 60 FPS tanpa beban CPU.
- **Pengujian & Kompilasi:**
  - Build Vite aset produksi (`npm run build`) berhasil 100% tanpa kendala (`public/build/assets/app-93Y7tpIE.js` & `app-CGP5SnFE.css`).
  - Seluruh pengujian unit backend lulus 100% (`Tests: 7 passed, 15 assertions`).

### OOO. Standardisasi Label Status Menunggu Approval Penerima (Update 23 Nov 2026 - Selesai & Teruji 100%)
- **Koreksi Tampilan Status Kolom No WR / Status:**
  - Memperbaiki bug tampilan pada [resources/views/work_requests/partials/_table_content.blade.php](resources/views/work_requests/partials/_table_content.blade.php) di mana tiket berstatus `approval_pengaju = 'Diterima'` dan `approval_penerima = 'Menunggu'` sebelumnya keliru jatuh ke lencana `Proses Line Dept ...` dengan ikon gear berputar.
  - Menyelaraskan status tunggu ke **Opsi 1**: Lencana amber bersih bertuliskan **`Menunggu Approval Penerima`** (`<i class="fa-solid fa-clock"></i>`).
  - Tiket baru resmi beralih ke proses pengerjaan mesin (`Proses: Dept ... (In)`) setelah Kepala Departemen Penerima menyetujui kesanggupan mesin dan menugaskan operator pelaksana via modal penugasan.
- **Pengujian & Integritas:**
  - Lulus 100% pada seluruh rangkaian unit test Work Request dan kompilasi Vite.

### PPP. Optimalisasi Streaming Foto Komponen Master Shared Drive Z: & Pusat Kendali NAS Admin (Update 24 Nov 2026 - Selesai & Teruji 100%)
- **Fokus 100% Pembacaan Langsung Master Network Drive Z: (Direct Zero-Copy NAS Streaming):**
  - Mengabulkan kebutuhan operasional pabrik agar sistem memprioritaskan dan mengambil gambar komponen langsung dari folder master:
    `Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House`
    tanpa mewajibkan duplikasi atau pemindahan berkas manual.
- **In-Memory Directory Indexing Super Cepat (4.27 ms untuk Seluruh Katalog):**
  - Menggantikan 108 kali pengecekan `file_exists` bolak-balik melintasi kabel LAN per komponen dengan pembacaan satu kali daftar direktori ke memori RAM (*in-memory hash table*).
  - Benchmark membuktikan seluruh 963 komponen di database kini diselesaikan dalam **4.27 milidetik** tanpa pernah memicu *lag* atau *Request Timeout* pada server intranet (`http://192.168.3.101:8000`).
- **Koreksi Bug Sintaks Regex Fallback UNC (`\\192.168.1.150\...`):**
  - Memperbaiki konversi path `Z:` pada [app/Services/ComponentPhotoStorageService.php](app/Services/ComponentPhotoStorageService.php) agar awalan selalu membentuk format UNC resmi dengan *double backslash* (`\\192.168.1.150\...`).
  - Menghilangkan bug di mana `preg_replace` sebelumnya memotong satu backslash menjadi `\192.168.1.150\...` yang ditolak Windows. Sistem kini memiliki daya tahan 100% saat huruf drive `Z:` mengalami *SMB idle sleep*.
- **Pembersihan Total Negative Cache Poisoning:**
  - Menghilangkan penguncian `null` 30 menit pada `Cache::remember`. Sistem hanya menyimpan cache saat berkas fisik berhasil ditemukan, sehingga penambahan foto baru di drive `Z:` langsung terdeteksi seketika.
- **Pusat Pemantauan Storage NAS & Tombol 1-Klik di Admin Dashboard (`/admin/dashboard`):**
  - Menerbitkan widget kartu pemantauan berstandar industri pada [resources/views/admin/dashboard.blade.php](resources/views/admin/dashboard.blade.php):
    - Badge status real-time koneksi Server NAS: `[ ONLINE / TERHUBUNG ]` atau `[ OFFLINE ]`.
    - Indikator 4 metrik berkas: Master NAS (618 berkas), Webroot Mirror SSD (241 berkas), Belum Tercermin, dan Total Part Resmi.
    - Tombol Aksi 1-Klik: **`[ 🔄 Sinkronkan ke Webroot Mirror ]`** (`admin.sync-photos`) dan **`[ 🧹 Reset Cache Foto ]`** (`admin.clear-photo-cache`) dengan konfirmasi modal global `<x-confirm-modal />` (*Zero Stock Disturbance*).
- **Pembaruan Artisan Command:**
  - Menambahkan opsi `--sync-mirror` pada [app/Console/Commands/CheckNasPhotosCommand.php](app/Console/Commands/CheckNasPhotosCommand.php) untuk opsi sinkronisasi otomatis via CLI.
- **Pengujian & Validasi Kualitas:**
  - Lulus 100% pada [tests/Unit/ComponentPhotoStorageTest.php](tests/Unit/ComponentPhotoStorageTest.php) (8 passed, 26 assertions), kompilasi Blade view cache sukses 100%, dan pengujian HTTP client streaming 10 foto konkuren berhasil dalam 0.9 detik.

### QQQ. Auto-Download & Pencocokan Foto Komponen Spreadsheet WR Lama ke Drive Z (Update 24 Nov 2026 - Berhasil 100%)
- **Sinkronisasi Katalog Master CSV ke Storage NAS Z:**
  - Menghubungkan berkas acuan `System WR lama/Spreadsheet/Form Work Request - Database Komponen.csv` ke dalam artisan command `wr:download-legacy-photos` ([app/Console/Commands/DownloadLegacyWrPhotos.php](app/Console/Commands/DownloadLegacyWrPhotos.php)).
  - Menggabungkan 3 sumber data: Katalog Master (799 part), Komponen Baru (82 part `NEW-...`), dan Log Permintaan WR (987 part unik total).
- **Idempotensi & Pencegahan Duplikasi:**
  - Sistem memeriksa ketersediaan berkas di Drive Z (`Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House`) sebelum mengunduh. Dari 987 part terdaftar, 695 part yang sudah ada langsung dilewati (*zero overwrite/skip*).
- **Multi-Part Drawing In-Memory Caching:**
  - Untuk berkas gambar/drawing teknik yang dipakai bersama oleh beberapa kode komponen (seperti `CCSPSIDEFRAMEA4` hingga `F4`), file biner dari Google Drive diunduh satu kali ke memori dan disimpan ke seluruh kode part terkait secara instan tanpa panggilan HTTP berulang.
- **Dukungan Flag `--dry-run` & Uji Coba Terisolasi:**
  - Menambahkan opsi `--dry-run` untuk simulasi pencocokan tanpa mengunduh berkas fisik, serta penanganan izin tulis SMB Mapped Drive yang aman via metode `isDirectoryWritable()`.
- **Hasil Eksekusi Nyata:**
  - Berhasil mengunduh dan menyimpan **291 berkas komponen baru** langsung ke Drive Z: (`Z:\...`) dan cermin lokal (`public/uploads/components`).
  - Total berkas di folder Drive Z bertambah dari **616 berkas** menjadi **906 berkas**.
  - Part `CCSPSIDEFRAMEA4` sampai `CCSPSIDEFRAMEF4` yang sebelumnya kosong di Halaman 1 kini langsung memiliki berkas resolusi tinggi (~408 KB) dan sukses di-*stream* dengan status `HTTP 200 OK`.
  - Cache indeks foto otomatis disegarkan via `ComponentPhotoStorageService::clearCache()`.

### RRR. Perbaikan Browser Disk Cache Buster & Validasi MIME-Type Gambar Drive Z (Update 24 Nov 2026 - Tuntas 100%)
- **Penyebab Gambar Belum Tampil di Browser Klien:**
  - Sebelumnya saat berkas foto belum ada di Drive Z, browser pengguna menerima respon gambar placeholder dengan header `Cache-Control: public, max-age=86400` (24 jam). Google Chrome/Edge menyimpan gambar kosong di disk cache lokal pengguna sehingga saat file baru ditambahkan ke Drive Z, browser tidak melakukan permintaan baru ke server intranet.
- **Implementasi Auto Cache-Buster (`?v=mtime_size`):**
  - Menambahkan accessor `photo_version` pada [app/Models/Component.php](app/Models/Component.php) yang menghasilkan stempel versi unik berbasis waktu modifikasi berkas fisik (`@filemtime`) dan ukuran berkas (`@filesize`).
  - Memperbarui `getImageUrlAttribute()` sehingga menyematkan parameter query otomatis: `/components/{id}/photo-stream?v=timestamp_size`.
  - Dampak: Begitu berkas foto baru tersedia atau diubah di Drive Z, URL otomatis berubah sehingga seluruh perangkat (PC Admin, laptop, dan HP operator) **langsung memuat gambar asli tanpa perlu 'Clear Browser Cache' atau menekan 'Ctrl+F5' manual**.
- **Larangan Cache pada Placeholder (`max-age=0`):**
  - Mengubah header pada [app/Http/Controllers/ComponentController.php](app/Http/Controllers/ComponentController.php) saat menyajikan `placeholder_part.svg` menjadi `Cache-Control: no-cache, no-store, must-revalidate, max-age=0` dengan `Pragma: no-cache`. Browser dilarang menyimpan status kosong jika foto belum diunggah.
- **Deteksi MIME-Type Biner Otomatis (`mime_content_type`):**
  - Menggantikan deteksi MIME berbasis ekstensi teks dengan pemindaian biner nyata (`mime_content_type`). Berkas biner PNG yang dinamai `.jpg` kini secara otomatis dikirim dengan header resmi `Content-Type: image/png` sehingga kompatibel 100% dengan seluruh peramban web dan modal zoom.
- **Dukungan Header ETag & HTTP 304 Not Modified:**
  - Menambahkan validasi `If-None-Match` pada Controller untuk menghemat bandwidth jaringan pabrik saat gambar sudah pernah diunduh.
- **Pengujian & Validasi:**
  - Unit test lulus 100% (8 passed, 26 assertions).
  - Blade template cache berhasil dikompilasi 100%.
  - Uji streaming HTTP pada `CCSPSIDEFRAMEA4` (ID: 15953) mengembalikan `HTTP/1.1 200 OK` (408.756 bytes, `image/png`) dan `HTTP/1.1 304 Not Modified` saat menggunakan ETag.

### RRR. Penyimpanan Gambar Komponen Non-Master WR Ad-hoc ke Subfolder Dedikasi Drive Z: (NewKomponenWR)
- **Subfolder Terpisah & Terisolasi di NAS:**
  - Komponen ad-hoc/non-master yang dibuat melalui form Work Request (`/work-requests/create` via `quickStore` dengan `source = 'wr_adhoc'`) disimpan langsung ke subfolder:
    `Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House\NewKomponenWR`
  - Fallback jalur UNC: `\\192.168.1.150\HPK_Eng_Online\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House\NewKomponenWR`
  - Salinan lokal otomatis dicerminkan ke `public/uploads/components/` untuk redundansi offline intranet.
- **Pembaruan Service & Controller:**
  - `ComponentPhotoStorageService.php`: Ditambahkan method `getNewWrComponentDirectory()`, `getNewWrUncDirectory()`, parameter `$isNewWr` pada penyimpanan unggahan/kamera (`storeUploadedFile`, `storeBase64`, `storeBinary`), serta pembersihan file lama di kedua folder (`cleanExistingFilesForPart`).
  - `ComponentController.php`: Method `quickStore()` menandai `$isNewWr = true` saat menyimpan gambar komponen dari WR ad-hoc.
  - Multi-Folder Auto Search: `doFindLocalPhoto()` memindai folder master dan subfolder `NewKomponenWR` (serta jalur UNC masing-masing) secara mikrodetik via in-memory indexing.
- **Migrasi & Sinkronisasi Backfill:**
  - Dibuat Artisan command: `php artisan wr:sync-new-komponen-to-nas`.
  - Berhasil menyalin **190 berkas foto** komponen `NEW-...` dari webroot ke subfolder Drive Z `NewKomponenWR` dengan status sukses 100% (0 gagal).
- **Pengujian & Kepatuhan Guardrails:**
  - Unit test: `php artisan test tests/Unit/ComponentPhotoStorageTest.php` lulus 100% (8 passed, 26 assertions).
  - Isolasi Guardrails Terjaga: Komponen `source = 'wr_adhoc'` tetap 100% terisolasi dari Master Komponen dan Safety Stock, namun gambar tetap dapat dilihat dengan jernih di tiket WR dan modal detail proses.

### TTT. Penegakan Alur Wajib Material Handling & Eliminasi Total Kode Bypass Warisan
- **Akar Masalah Terselesaikan:**
  - Ditemukan sisa blok kode warisan prototipe lama (*Fleksibilitas Sistem Lama / js_dashboard.html*) pada `WorkRequest.php` yang secara keliru menghitung saldo penerimaan gudang dari `totalSiap` (mesin) ketika `totalMenuju == 0`.
  - Hal ini menyebabkan bagian Gudang sempat langsung melihat tombol hijau `Scan Diterima` sesaat setelah operator mesin menekan `Siap Diambil`, memotong penjemputan logistik kurir MH.
- **Pembersihan Kode Total & Penegakan Rantai Logistik:**
  - `app/Models/WorkRequest.php` (`getAvailableActionsForUser`): Seluruh kode warisan `$totalSiap` dihapus total. Saldo terima Gudang kini murni dihitung dari `max(0, $totalMenuju - $totalSudah - $gudangDefects)`. Jika kurir MH belum menjemput (`totalMenuju == 0`), akun Gudang 100% TIDAK memiliki tombol aksi terima.
  - `app/Models/WorkRequest.php` (`canUserProcessAction`): Validasi ketat otorisasi penerimaan akhir `Sudah Diambil`. Bahkan untuk Admin, aksi `Sudah Diambil` ditolak jika barang fisik belum berada dalam pengantaran MH (`totalMenuju <= totalSudah`).
  - `app/Http/Controllers/WorkRequestController.php` (`processAction`): Ditambahkan validasi proteksi ganda backend (Guard 1d) yang menolak request penerimaan gudang jika belum ada log pengantaran kurir MH (`Menuju Pengaju`).
- **Sinkronisasi Tampilan Antarmuka (Blade):**
  - Saat status `Siap Diambil`: Baik POV Gudang maupun POV Mesin sama-sama menampilkan lencana kuning `Menunggu Diambil MH`.
  - Di menu Material Handling (`/material-handling`), tiket otomatis masuk ke Tab `Siap Diambil / Distribusi ke Pengaju` untuk dijemput kurir.
  - Hanya setelah kurir MH menjemput dan status beralih ke `Menuju Pengaju`, tombol `Scan Diterima` akan aktif di akun Gudang.
- **Pengujian & Validasi Kualitas:**
  - Unit test `WorkRequestAuthorizationTest` lulus 100% (6 passed, 16 assertions), termasuk test case baru `test_gudang_cannot_receive_goods_directly_from_machine_when_status_is_siap_diambil()`.
  - Unit test `WorkRequestDefectAndHandoverTest` lulus 100% (6 passed, 30 assertions).
  - Verifikasi langsung pada database operasional untuk tiket No. 973 (`WR/Dept Gudang/20261124/1/CSAA50550200000/1`): Tombol `Scan Diterima` hilang 100% dari akun Gudang, dan antarmuka kini tertib menampilkan `Menunggu Diambil MH`.

### UUU. Audit dan Pembersihan Kode Warisan (Legacy Cleanup) & Penutupan Celah Bypass Sistem (Update 24 Nov 2026 - Tuntas 100%)
- **Penghapusan Rute & Method Mati (Dead Code Elimination):**
  - Menghapus rute POST `/work-requests/{workRequest}/steps/{step}/advance` dari [routes/web.php](routes/web.php).
  - Menghapus method `advanceStep()` (102 baris) dari [app/Http/Controllers/WorkRequestController.php](app/Http/Controllers/WorkRequestController.php) yang merupakan sisa prototipe lama yang dapat memotong MH dengan langsung mengubah status tiket menjadi `ready_for_warehouse`.
- **Pengetatan Validasi `action_type` (Eliminasi `quick_advance`):**
  - Membersihkan aturan validasi pada [app/Http/Controllers/WorkRequestController.php](app/Http/Controllers/WorkRequestController.php) dan [app/Http/Controllers/WorkStationSupplyController.php](app/Http/Controllers/WorkStationSupplyController.php) dari `'required|in:process,defect,quick_advance'` menjadi `'required|in:process,defect'`.
- **Penegakan Logistik MH Antar-Mesin Multi-Routing:**
  - Pada rute multi-mesin (contoh: Laser -> M1 -> Sharing), menambahkan Guard 1e pada [app/Http/Controllers/WorkRequestController.php](app/Http/Controllers/WorkRequestController.php) dan validasi pada [app/Models/WorkRequest.php](app/Models/WorkRequest.php) (`canUserProcessAction()`).
  - Stasiun mesin kedua atau seterusnya (`step_index > 0`) **DILARANG KERAS** memulai `Proses: {Step} (In)` jika barang dari mesin sebelumnya belum dijemput dan diantar oleh kurir Material Handling (`saldo_in <= 0`).
- **Koreksi Metrik Dashboard WRI "Siap Gudang":**
  - Pada [app/Http/Controllers/DashboardController.php](app/Http/Controllers/DashboardController.php), memperbarui query `$readyForWarehouseWriCount` yang sebelumnya mencari status usang `ready_for_warehouse` (selalu bernilai 0 di database operasional) menjadi menghitung tiket aktif berstatus `Siap Diambil` dan `ready_for_warehouse` yang belum tuntas (`is_final = false`). Angka lencana kini akurat 100% mencerminkan kondisi riil di lantai pabrik.
- **Konfigurasi Fleksibel Cadangan Foto Komponen:**
  - Pada [app/Services/ComponentPhotoStorageService.php](app/Services/ComponentPhotoStorageService.php), mengganti direktori cadangan lokal hardcoded `D:\Ali Magang\KOMPONEN` menjadi `env('COMPONENT_LOCAL_BACKUP_DIR', 'D:\Ali Magang\KOMPONEN')`.
- **Pengujian & Validasi Kualitas:**
  - Seluruh pengujian otomatis `WorkRequestAuthorizationTest`, `WorkRequestDefectAndHandoverTest`, dan `ComponentPhotoStorageTest` (20 passed, 72 assertions) lulus 100%.
  - Route cache & Blade view templates berhasil dikompilasi ulang dengan status sukses 100%.

### VVV. Eliminasi Total Timeout 60 Detik & Optimasi Loading Submenu Membuat WR (Update 26 Nov 2026 - Tuntas 100%)
- **Akar Masalah Berulang (`ComponentPhotoStorageService.php:373`):**
  - Pada implementasi sebelumnya (`b81b2c3`), `Component::getImageUrlAttribute()` memanggil `$this->photo_version` -> `local_photo_file_path` -> `findLocalPhoto()` secara sinkron. Akibatnya, saat `work_requests/create.blade.php` memetakan seluruh 777+ komponen master ke JSON, PHP melakukan scan direktori SMB/NAS berulang kali.
  - Selain itu, `dir_index_*` hanya bertahan 60 detik, `getDirectoryIndex()` memanggil `@realpath()`/`@is_dir()` sebelum mengecek cache, tidak ada *negative cache* untuk part tanpa foto, serta belum ada *circuit breaker* saat Drive `Z:` / UNC `\\192.168.1.150` lambat atau putus.
- **Perbaikan Arsitektural Permanen:**
  - `app/Models/Component.php`: `getPhotoVersionAttribute()` kini murni menggunakan stempel waktu DB (`updated_at->timestamp`, **0 I/O disk/SMB** saat SSR), dan `getTotalStockAttribute()` mendukung fast-path atribut agregat `total_stock_sum` dari `withSum`.
  - `app/Services/ComponentPhotoStorageService.php`: Implementasi **cache-first `getDirectoryIndex()`** (TTL 300s + generasi `component_photo_cache_version` O(1)), **circuit breaker `isDirectoryAvailable()`** (`dir_unreachable_*` 60s), **negative cache** 60s untuk part tanpa foto, serta **smart UNC fallback** (jalur UNC hanya dicek jika Drive primer `Z:` / mount tidak terjangkau).
  - `app/Http/Controllers/ComponentController.php`: `photoStream()` menggunakan `Cache-Control: public, max-age=300, must-revalidate` + `ETag` (`304 Not Modified`), dan `update()` memanggil `$component->touch()` saat foto diperbarui.
  - `app/Http/Controllers/WorkRequestController.php` & `QrRequestController.php`: Query `create()` menggunakan `withSum('stockBalances as total_stock_sum', 'quantity')` serta menghindari scan SMB sinkron saat memuat daftar komponen.

### WWW. Penyederhanaan Warna Line Border Kartu KPI Antrean WRI (Update 26 Nov 2026 - Tuntas 100%)
- **Pembaruan Antarmuka (`resources/views/work_requests/index.blade.php`):**
  - Menghilangkan seluruh garis border dan *focus ring* berwarna (`border-[#0a2342]`, `border-amber-*`, `border-blue-*`, `border-teal-*`, `border-emerald-*`, `border-rose-*`, `ring-2`) pada ke-6 kartu KPI status (`Total WRI`, `Perlu Verifikasi`, `Sedang Proses`, `Siap Diambil`, `Selesai`, `Ditolak / Batal`).
  - Menyeragamkan seluruh kartu dengan border abu-abu halus netral (`border-slate-200/80`) dan penanda kartu aktif melalui latar lembut + shadow (`bg-slate-50/90 shadow-sm`), menjaga aksen warna tetap terfokus pada ikon dan angka KPI.

### XXX. Penyeragaman Border Netral pada Banner SLA, Tombol, dan Pill Informasi WRI (Update 26 Nov 2026 - Tuntas 100%)
- **Pembaruan Antarmuka (`index.blade.php`, `_table_content.blade.php`, `WorkRequest.php`):**
  - Menghilangkan garis border berwarna pada banner Peringatan SLA > 3 Hari (`border-red-200/90` -> `border-slate-200/80`).
  - Menyeragamkan seluruh border pill status, badge approval, badge SLA, serta tombol aksi di tabel WRI dan `WorkRequest::getStatusBadgeAttribute()` menjadi garis abu-abu halus `border-slate-200/80` selaras dengan kartu KPI di atasnya.

### YYY. Optimasi Breakpoint & Kepadatan Kartu KPI WRI Anti-Mepet (Update 26 Nov 2026 - Tuntas 100%)
- **Pembaruan Responsivitas (`resources/views/work_requests/index.blade.php` & Bundel Vite):**
  - Mengubah grid kartu KPI dari `lg:grid-cols-6` menjadi `grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3`, sehingga pada rentang layar laptop/scaling `640px–1279px` kartu otomatis turun simetris **3 kolom × 2 baris** sebelum terjepit.
  - Merampingkan ruang internal kartu (`p-3.5 xl:p-3`, ikon `w-10 h-10 xl:w-9 xl:h-9`, `space-x-2.5`, serta `min-w-0 flex-1` + `block truncate` pada judul & sub-label) agar pada layar lebar (`>= 1280px`) seluruh 6 kartu dan sub-label seperti `Pengaju & Penerima` muat **1 baris rapi**.
  - Mengompilasi ulang aset CSS Tailwind via `npm run build`.

### ZZZ. Transisi FLIP Grid Responsif & Transisi Pergantian Filter WRI (Update 26 Nov 2026 - Tuntas 100%)
- **Animasi FLIP Grid (`resources/js/animations/motion-effects.js` & `resources/js/app.js`):**
  - Menambahkan `initResponsiveGridFlip()` berbasis `ResizeObserver` + GSAP GPU `transform` (`duration: 0.34s`, *stagger* `0.02s`) pada kontainer `[data-flip-grid]` dan item `[data-flip-card]`. Ketika lebar layar berpindah antar breakpoint (`6×1 ⇄ 3×2 ⇄ 2×3`), kartu KPI meluncur halus ke posisi baris baru, lengkap dengan dukungan `prefers-reduced-motion` dan pembersihan observer otomatis pada `cleanupMotionEffects()` saat navigasi Turbo.
- **Transisi Kartu & Konten Tabel (`resources/views/work_requests/index.blade.php`):**
  - Menambahkan `duration-300 ease-out` + subtle lift `-translate-y-0.5` saat kartu aktif, serta mengekstrak helper `renderTableHtml()` pada `wriQueueManager` dengan transisi *fade + slide* (`220ms ease-out`) saat `switchTab()` atau `fetchPartial()` mengganti isi tabel `#wri-table-container`.

### AAAA. Sistem Live Auto-Filter Terpadu (Tanpa Enter / Tanpa Tombol Filter) di 8 Menu Utama (Update 26 Nov 2026 - Tuntas 100%)
- **Frontend Helper Terpadu (`resources/js/components/live-filter.js` & `resources/js/app.js`):**
  - Dibuat modul reusable `initLiveFilters()` & `cleanupLiveFilters()` yang terintegrasi dengan lifecycle Turbo (`turbo:load`, `turbo:before-render`, `turbo:before-visit`).
  - Mendukung filter instan saat memilih dropdown (`<select>`) dan input tanggal (`<input type="date">`), serta *live search* dengan **debounce 350ms** dan **`AbortController`** (mencegah *race condition* antar ketikan), spinner loading, tombol clear `×`, tombol reset dinamis, AJAX pagination, sinkronisasi URL via `history.replaceState`, serta pembaruan `allIds` / `availableComponents` checkbox massal melalui custom event `live-filter-updated`.
- **Backend Laravel `@fragment('results')` (`_live=1`):**
  - Diterapkan pada `ComponentController::index`, `TransactionController::index`, `QrRequestController::index`, `WorkRequestController::safetyStock`, `WorkRequestController::printQueue`, `Admin\UserController::index`, dan `Admin\AuditLogController::index` sehingga hanya fragmen hasil (tabel, mobile cards, pagination) yang dikirim ulang tanpa merender shell navbar/sidebar dan tanpa memutus fokus kursor pencarian.
- **Work Request Mesin (`resources/views/work_requests/index.blade.php`):**
  - Menghubungkan input pencarian `search` ke `wriQueueManager()` (`onSearchInput`, `applySearchNow`, `clearSearch`, `AbortController`) sehingga mengetik pencarian langsung memfilter tabel via `_partial=1` serta tetap terbawa saat berpindah tab status.
- **Pembersihan Tombol Lama:**
  - Menghapus tombol submit `[Filter]` / `[Cari]` / `[Terapkan]` / `[Terapkan Rentang]` dan `onchange="this.form.submit()"` yang usang pada ke-8 menu terkait.

### BBBB. Pagination Responsif Atas-Bawah, Dropdown `per_page` Bar Atas, & Minimal 4 Tab di HP (Update 26 Nov 2026 - Tuntas 100%)
- **Template Pagination Kustom Global (`resources/views/vendor/pagination/tailwind.blade.php`, `resources/views/components/pagination-bar.blade.php`, & `AppServiceProvider::boot()`):**
  - Mengganti template bawaan Laravel yang menghilangkan angka halaman di `< 640px` (`sm:hidden`) dan memicu kotak hitam `dark:bg-gray-800` saat browser/OS dalam mode gelap.
  - Menerapkan *sliding window* kompak dengan **minimal 4 tab angka** pada layar HP & laptop sedang (`< 1280px` / `xl:hidden`: `« ‹ 1 2 3 ... N › »`) serta *wide window* di desktop `>= 1280px` (`hidden xl:flex`). Teks ringkasan dikunci `whitespace-nowrap shrink-0` dan grup kanan `per_page` + tombol dikunci `sm:flex-nowrap` sehingga seluruh kontrol sejajar **1 baris utuh** tanpa teks `"data"` turun atau dropdown terlempar ke baris atas.
  - Menempatkan bar pagination secara seragam di **atas** (dengan dropdown **`N / halaman`** memakai `h-8 py-0 leading-tight pr-7` agar bebas *clipping* padding default `@tailwindcss/forms`, tetap tampil saat total > 0 meski hasil 1 halaman) dan **bawah** (**tanpa dropdown**) di seluruh 12 halaman ber-pagination (`Work Request`, `Master Komponen`, `Transaksi`, `Cetak QR`, `Safety Stock`, `Antrean Cetak WR`, `Manajemen User`, `Audit Log`, `ECR`, `Disposal`, `Cycle Count`, `Lokasi Admin`).
- **Whitelist Backend & Sinkronisasi Live Filter (`Controller::resolvePerPage`, `live-filter.js`, `wriQueueManager`):**
  - Menambahkan `resolvePerPage(Request $request, int $default = 15, array $allowed = [10, 12, 15, 20, 25, 50, 100])` di base `Controller` untuk mencegah penyalahgunaan query ekstrem sekaligus menjaga default awal tiap modul.
  - Mengubah dropdown `per_page` pada 8 modul live-filter agar memuat ulang fragmen via AJAX (reset ke `page=1` + invalidasi cache tab WRI) serta mempertahankan nilai `per_page` saat user mengetik pencarian atau pindah filter/tab.

### CCCC. Penyempurnaan Label Notifikasi Kartu Menu Beranda Utama (Update 26 Nov 2026 - Tuntas 100%)
- **Beranda Utama (`resources/views/dashboard.blade.php`):**
  - Mengubah badge kartu **Work Request Mesin** dari `{{ $readyForWarehouseWriCount }} Siap Gudang` menjadi **`{{ $readyForWarehouseWriCount }} Siap Pickup MH`** agar selaras dengan alur logistik resmi (`Siap Diambil` dari mesin menunggu penjemputan kurir Material Handling).
  - Menghapus badge redundan `{{ $todayTransactionsCount }} Hari Ini` pada kartu menu **Transaksi Komponen** karena ringkasan *Transaksi Hari Ini* sudah tersedia pada kartu KPI bagian atas.
  - Mempertahankan badge pada **Master Komponen** (`Perlu Rak`, `SKU`) dan **Permintaan Label QR** (`Siap Cetak`).

### DDDD. Penyeragaman Skema Warna Kartu KPI Beranda (Monokrom Hitam–Abu + Aksen Kritis Terfokus, Update 26 Nov 2026 - Tuntas 100%)
- **Kartu KPI Beranda (`resources/views/dashboard.blade.php`):**
  - Menyeragamkan seluruh teks judul (`text-slate-400`), angka utama (`text-slate-900`), sub-label bawah kartu 1, 3, dan 4 (`text-slate-500`), serta kotak ikon kanan (`bg-slate-100 text-slate-700`) ke skema monokrom hitam dan abu-abu.
  - Menghapus border merah, ring merah (`border-rose-300 ring-2 ring-rose-100 hover:border-rose-400`), serta titik merah berdenyut pada kartu **Stok Menipis (Kritis)** (`border-slate-200/80`).
  - Memfokuskan aksen warna merah hanya pada **ikon tanda seru (`bg-rose-50 text-rose-600`)** dan **teks bawah `Di bawah safety stock →` (`text-rose-600`)**.

### EEEE. Penyeragaman Ikon Beranda `bg-slate-100` + HPK Teal (`#4a9e9e`) (Update 26 Nov 2026 - Tuntas 100%)
- **Kartu KPI & Menu Modul Beranda (`resources/views/dashboard.blade.php` & `resources/css/app.css`):**
  - Menyeragamkan ke-10 kotak ikon (`.icon-box`) di grid `Modul Aplikasi Pergudangan HPK` sekaligus ke-3 kotak ikon KPI non-alert (`Total Komponen`, `Transaksi Hari Ini`, `Menunggu Approval`) menjadi **`bg-slate-100 text-[#4a9e9e]`** (`hpk.teal`) dengan border kartu netral halus (`rgba(226, 232, 240, 0.9)`), *watermark* `.card-icon-bg` `#0f172a`, dan panah hover `var(--hpk-teal)`.
  - Tetap mempertahankan warna merah khusus pada tanda seru (`bg-rose-50 text-rose-600`) dan teks `Di bawah safety stock →` kartu `Stok Menipis`, serta badge notifikasi operasional pada kartu menu.

### FFFF. Redesign Antarmuka Material Handling Bergaya Kartu Mengambang (*Floating Card Rows*, Update 26 Nov 2026 - Tuntas 100%)
- **Tampilan Material Handling (`resources/views/work_station_supplies/index.blade.php` & `partials/_task_list.blade.php`):**
  - Merombak bar tab utama (`Antar Mesin` | `Siap Diambil`) ke dalam 1 *shape* pembungkus putih besar (`rounded-3xl border border-slate-200/80 shadow-sm`) bersama sub-tab 3 kolom (`overflow-x-auto` tanpa terpotong di HP), counter dinamis `X Item`, filter pill `rounded-full` + search bar, serta 1 struktur tabel *floating-card* tunggal (`overflow-x-auto` `min-w-[780px]`) agar di mode HP tetap memakai tabel yang dapat digeser kanan–kiri tanpa card vertikal terpisah.
  - Seluruh logika backend controller, modal pickup `openActionModal()`, modal detail `openDetailModal()`, serta scanner QR wajib (`isWrScanRequired`) tetap utuh 100%.

### GGGG. Aktivasi *Silent Drag & Drop* Upload Foto & Dokumen di 7 Form (Update 26 Nov 2026 - Tuntas 100%)
- **Frontend Helper (`resources/js/components/silent-file-drop.js` & `resources/js/app.js`):**
  - Mengaktifkan *drag & drop* file langsung pada input `Choose File` maupun kotak pratinjau tanpa menambah teks petunjuk atau tampilan dropzone khusus (*silent enhancement*), terintegrasi dengan siklus `turbo:load` / `turbo:before-render` / `turbo:before-visit`.
  - Mendukung validasi atribut `accept` (`image/*`, `application/pdf`, `.pdf`, `.jpg`, `.png`, `.zip`), mengisi `input.files` via `DataTransfer`, dan memicu event `change` sehingga handler Alpine (`handleFileUpload`, `previewFile`) berjalan otomatis.
- **7 Form Terintegrasi (`[data-drop-zone]`):**
  - `work_requests/create`, `master_components/create`, `master_components/edit`, `ecrs/create`, `disposals/create`, `admin/users/create`, dan `admin/users/edit` (sedangkan `import_modal.blade.php` tetap memakai dropzone Excel bawaan).

### HHHH. Penghapusan *Akses Cepat Uji Coba (1-Click Fill)* pada Halaman Login (Update 26 Nov 2026 - Tuntas 100%)
- **Halaman Login (`resources/views/auth/login.blade.php`):**
  - Menghapus blok tombol demo *Akses Cepat Uji Coba (1-Click Fill)* (`@admin`, `@operator`, `@spv_gudang`, `@engineer`) beserta fungsi JS usang `fillUser(username)` yang sebelumnya meng-hardcode `'password'` di HTML klien.
  - Mempertahankan fitur resmi `Ingat saya` (`hpk_remembered_username`), tombol toggle password 5 detik (`togglePassword`, `hidePassword`), tautan `Lupa password?`, dan menjaga seluruh data akun di database tetap utuh.

### IIII. Perbaikan Bug Dropdown Profil Akun Navbar Terpotong CSS (Update 26 Nov 2026 - Tuntas 100%)
- **Navbar & Komponen Dropdown (`navigation.blade.php`, `admin-navigation.blade.php`, `dropdown.blade.php`, `dropdown-link.blade.php`):**
  - Menghapus `overflow-hidden` pada elemen `<nav>` agar dropdown akun (`Login sebagai`, `Profil Akun`, `Dashboard WMS`, `Keluar`), serta dropdown Admin (`Master Data`, `Pengaturan`) tidak lagi terpotong di batas bawah navbar (`64px`).
  - Memperbaiki mapping `width="56"` -> `w-56` (serta `w-64`) di `dropdown.blade.php` yang sebelumnya menghasilkan class invalid `"56"`, menghapus kelas `dark:bg-gray-700` / `dark:text-gray-300`, dan menambahkan `type="button"` serta rotasi halus ikon chevron saat terbuka.

---

## 2. Struktur 15 Departemen Resmi
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

## 3. Tautan Panduan Terkait
- [about.md](about.md) - Rincian Arsitektur dan 11 Modul WMS & WRI
- [docs/PANDUAN_STRUKTUR_FOLDER_DAN_FRAMEWORK.md](docs/PANDUAN_STRUKTUR_FOLDER_DAN_FRAMEWORK.md) - Buku Panduan Resmi Struktur Folder, Arsitektur Framework Laravel, Node.js & Esensi Berkas Sistem
- [README.md](README.md) - Dokumentasi Utama Proyek
- [docs/SOP_DAN_ALUR_PROSES.md](docs/SOP_DAN_ALUR_PROSES.md) - Standar Operasional Prosedur & Aturan Bisnis Modern
- [.gemini/rules/guardrails.md](.gemini/rules/guardrails.md) - Aturan Wajib Pengembangan & Kepatuhan Prompt AI Agent

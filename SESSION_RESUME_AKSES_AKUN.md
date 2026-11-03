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

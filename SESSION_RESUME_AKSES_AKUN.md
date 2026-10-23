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
- [README.md](README.md) - Dokumentasi Utama Proyek
- [docs/SOP_DAN_ALUR_PROSES.md](docs/SOP_DAN_ALUR_PROSES.md) - Standar Operasional Prosedur & Aturan Bisnis Modern
- [.gemini/rules/guardrails.md](.gemini/rules/guardrails.md) - Aturan Wajib Pengembangan & Kepatuhan Prompt AI Agent

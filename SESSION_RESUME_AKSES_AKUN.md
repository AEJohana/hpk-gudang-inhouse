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

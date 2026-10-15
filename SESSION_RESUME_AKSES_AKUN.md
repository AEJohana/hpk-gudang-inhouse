# HPK WMS - RESUME OPERASIONAL & ROADMAP PENGEMBANGAN
**Terakhir Diperbarui:** 13 Oktober 2026

Dokumen ini adalah *checkpoint* resmi agar AI Agent / Developer di sesi berikutnya langsung memahami konteks proyek secara 100% utuh tanpa ada informasi yang hilang.

---

## 1. Status Proyek Saat Ini (Sudah Selesai & Disetujui)
- **Work Request Inhouse (WRI) & Material Handling (Supply Stasiun Kerja)** telah selesai 100% dan diuji:
  - Alur Multi-Routing: `Dalam antrian` -> `Proses (In)` -> `Proses (Out)` -> `MH Pickup` -> `Siap Diambil` -> `Menuju Pengaju` -> `Sudah Diambil`.
  - Pengerjaan & Pengiriman Parsial (Cicilan): label otomatis `Parsial > ...` dan tracking kuota akumulatif.
  - Koreksi Defect / Reject: pemotongan otomatis kuantitas order tiket & pencatatan alasan defect di audit log.
  - Auto-Update Saldo Fisik Gudang: saat status `Sudah Diambil`, stok di rak tujuan otomatis bertambah (`stock_balances`) & transaksi inbound resmi diterbitkan.
  - Material Handling Command Center: 4 Tab (`tasksPickup`, `tasksTransit`, `tasksDistribution`, `tasksHistory`) + Live Camera QR Scanner.
  - Dokumen Walkthrough: `walkthrough_wri_material_handling.md` (Approved).

---

## 2. Agenda Pembahasan Saat Ini: Akses Akun, Identitas Departemen & Hak Operasional

### A. Rujukan Data Acuan Sistem Lama (`System WR lama/`):
- `System WR lama/Spreadsheet/Form Work Request - Data User.csv` (Akun, ID Departemen, Password, Kode Akses, Nama, Link TTD).
- `System WR lama/Spreadsheet/Form Work Request - Akses User.csv` (Kode Akses 1 s/d 8).
- `System WR lama/Spreadsheet/Form Work Request - Akses Dept User.csv` (15 Departemen: Laser, M1, UNP, Sharing, Band Saw, Aksesoris, Press, Hydraulic, CNC, Sharing & Bending, Gudang, SPO, Robot, Uji 1, Uji 2).
- `System WR lama/Spreadsheet/Form Work Request - Data User MH.csv` (User logistik Material Handling).
- `System WR lama/js_auth.html` & `Kode.js` (`checkLogin`, `applyAccessControl`, scoping data per departemen).

### B. Dokumen Rencana Teknis yang Telah Dibuat:
- **Lokasi Plan**: `plan_akses_akun_dan_identitas.md`
- **Poin Arsitektur Inti**:
  1. **Master Departemen**: Tabel baru `departments` (ID 1-15 sesuai legacy) menggantikan string bebas, relasi `department_id` di `users`. Kolom `can_access_all_departments` (padanan tanda `*` di akun admin lama).
  2. **Pemetaan Hak Akses Spatie**:
     - Akses 1: `wri.create`
     - Akses 2: `wri.view`
     - Akses 3: `wri.print`
     - Akses 4: `wri.monitoring`
     - Akses 5: `wri.approve` (Approval Ka. Bagian)
     - Akses 6: `material-handling.manage`
     - Akses 7: `safety-stock.view`
     - Akses 8: `components.manage`
     - Tambahan: `wri.view-all` (Akses global tiket semua departemen)
  3. **Identitas Mempengaruhi Proses (Process Gating)**:
     - Form Buat WRI: `dept_pengaju` otomatis terkunci ke departemen user login.
     - Scoping Data WRI: Tab `Tiket Departemen Saya` (default untuk operator/SPV) vs `Semua Departemen` (Admin).
     - Hybrid Smart Approval: Operator butuh persetujuan kepala bagian (`approval_pengaju = 'Menunggu'`), sedangkan SPV/Admin/Urgent Line Stop otomatis `Diterima` (Bypass cerdas).
     - Tombol Approval Pengaju/Penerima hanya aktif bagi user yang memiliki hak `wri.approve` dan departemennya cocok.
  4. **Tanda Tangan Digital**: Field `signature_path` pada tabel `users` untuk otomatisasi cetak form WRI.

---

## 3. Keputusan Pengguna yang Perlu Dikonfirmasi Saat Resume:
1. **Daftar Departemen**: Gunakan 15 departemen dari data acuan legacy.
2. **Aturan Approval**: Setuju menggunakan Hybrid Smart Approval.
3. **Tanda Tangan Digital**: Apakah fitur upload tanda tangan digital diaktifkan.

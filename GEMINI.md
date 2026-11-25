# HPK GUDANG INHOUSE - ATURAN DAN PETUNJUK KERJA AI AGENT

Dokumen ini dimuat secara otomatis oleh Antigravity pada setiap sesi interaksi. Seluruh AI Agent dan developer wajib mematuhi panduan ini.

## Aturan Utama (Guardrails)
Detail lengkap aturan tata kelola proyek tercantum di [.gemini/rules/guardrails.md](.gemini/rules/guardrails.md). Poin-poin utama yang **WAJIB** dipatuhi:

1. **NO BLIND OVERWRITE (JANGAN HAPUS FITUR LAMA):**
   - Jangan pernah menimpa file tampilan (Blade/JS/CSS/Controller) secara utuh.
   - Periksa kode eksisting terlebih dahulu (modal, QR scanner, audit logs, layout) dan gunakan modifikasi presisi (`edit_file`).

2. **ZERO DATABASE REGRESSION (JANGAN HAPUS DATA):**
   - **DILARANG KERAS** menjalankan `php artisan migrate:fresh`, `db:wipe`, atau perintah destruktif lainnya pada database operasional `hpk_gudang`.
   - Seluruh pengujian otomatis (`php artisan test`) wajib berjalan pada SQLite in-memory `:memory:` yang terisolasi.

3. **VALIDASI FORM & UI AKTUAL:**
   - Seluruh tombol aksi, modal proses, input defect/cicilan, dan approval wajib ada di antarmuka aktual (Blade/Alpine.js) dan terhubung ke backend.

4. **ALUR WAJIB LOGISTIK (MATERIAL HANDLING):**
   - Perpindahan antar stasiun mesin dan ke gudang wajib melalui penjemputan Material Handling (`Proses Mesin (Out) -> MH Pickup -> [Berikutnya / Menuju Pengaju] -> Sudah Diambil`).

5. **SINKRONISASI DOKUMENTASI WAJIB (.MD ALWAYS IN SYNC):**
   - Setiap ada update fitur, perbaikan bug, alur baru, atau modifikasi logika, file `.md` terkait (`about.md`, `README.md`, `docs/SOP_DAN_ALUR_PROSES.md`, atau `SESSION_RESUME_AKSES_AKUN.md`) **WAJIB DIPERBARUI SECARA SIMULTAN**.

6. **KEPATUHAN PADA SETIAP PROMPT & SESI:**
   - Seluruh prompt, rencana kerja (`/plan`), dan respons wajib tunduk pada aturan ini tanpa pengecualian.

7. **PRINSIP SATU PINTU MASTER KOMPONEN (DILARANG MASUK DARI WR):**
   - Penambahan katalog master resmi HPK mutlak melalui menu Master Komponen oleh pihak berwenang.
   - Komponen non-master yang dibuat melalui form Work Request (WR) berstatus `source = 'wr_adhoc'` untuk kebutuhan pesanan saat itu saja ("buat lewat aja") dan **100% TERISOLASI/TIDAK BOLEH TAMPIL** di modul Master Komponen, Safety Stock, maupun KPI inventaris utama.

8. **PEMBERSIHAN KODE USANG & KONFIRMASI PENGHAPUSAN (NO DEAD CODE, KEEP REAL DATA):**
   - **Kode/Tampilan Lama Wajib Dihapus Jika Tidak Dipakai:** Setiap kali ada pengembangan atau pembaruan yang menggantikan fitur, logika, file, atau elemen antarmuka lama, bagian lama yang sudah tidak terpakai **WAJIB dihapus** agar program tetap bersih, ringan, dan bebas bug tersembunyi.
   - **Wajib Memastikan & Konfirmasi ke User:** Sebelum menghapus kode atau fitur lama, AI Agent **wajib menanyakan/memastikan terlebih dahulu kepada user** apakah bagian lama tersebut benar-benar sudah tidak terpakai dan boleh dihapus.
   - **Batas Mutlak Larangan Hapus Data:** Kewajiban penghapusan ini **HANYA berlaku untuk kode program dan tampilan (*source code / UI*)** serta data dummy yang diizinkan user. **DILARANG KERAS menghapus database, tabel produksi, atau data riwayat transaksi operasional (WRI, Stok, Komponen, User) yang sudah berjalan.**

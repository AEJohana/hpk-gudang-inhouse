# ATURAN PENGEMBANGAN & KEAMANAN PROYEK (HPK GUDANG INHOUSE)

Dokumen ini adalah aturan wajib (*mandatory guardrails*) yang harus dipatuhi oleh seluruh AI Agent / Developer dalam sesi percakapan apapun:

## 1. NO BLIND OVERWRITE (JANGAN HAPUS FITUR LAMA)
- Jangan pernah menimpa file tampilan (Blade/JS/CSS/Controller) secara utuh jika hanya menambahkan atau memperbaiki sebagian fitur.
- Periksa kode eksisting terlebih dahulu (seperti modal, QR code, tombol khusus, gambar, audit logs, atau layout) dan pastikan fitur yang sudah berjalan sebelumnya **TIDAK HILANG** saat fitur baru ditambahkan.
- Gunakan modifikasi presisi pada blok baris yang bersangkutan saja (`edit_file`).

## 2. ZERO DATABASE REGRESSION (JANGAN HAPUS DATA)
- **DILARANG KERAS** menjalankan perintah destruktif seperti:
  - `php artisan migrate:fresh`
  - `php artisan db:wipe`
  - Perintah seeder yang menghapus atau menimpa data manual di database operasional `hpk_gudang`.
- Seluruh pengujian otomatis (`php artisan test`) wajib berjalan pada database terisolasi (SQLite in-memory `:memory:`), sehingga tidak pernah mengubah, me-rollback, atau menghapus data pengujian manual pengguna di MySQL.

## 3. VALIDASI FORM & UI AKTUAL
- Jangan hanya berasumsi pada dokumen teori. Setiap alur (seperti pengerjaan parsial/cicilan, defect, atau approval) **WAJIB** diverifikasi bahwa tombol dan form input di antarmuka (Blade/Alpine.js) benar-benar ada dan terhubung ke backend, bukan berupa hidden field instan.

## 4. ALUR WAJIB LOGISTIK (MATERIAL HANDLING)
- Alur perpindahan komponen antar stasiun mesin dan ke gudang **WAJIB** melalui Material Handling:
  `Proses Mesin (Out) -> MH Pickup -> [Mesin Berikutnya / Menuju Pengaju] -> Sudah Diambil`.
- Bagian Gudang tidak boleh mem-bypass penjemputan Material Handling (tidak boleh langsung menyatakan "Sudah Diambil" sebelum barang dijemput dan dibawa oleh kurir logistik MH).

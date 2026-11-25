





## 3. VALIDASI FORM & UI AKTUAL
- Jangan hanya berasumsi pada dokumen teori. Setiap alur (seperti pengerjaan parsial/cicilan, defect, atau approval) **WAJIB** diverifikasi bahwa tombol dan form input di antarmuka (Blade/Alpine.js) benar-benar ada dan terhubung ke backend, bukan berupa hidden field instan.

## 4. ALUR WAJIB LOGISTIK (MATERIAL HANDLING)
- Alur perpindahan komponen antar stasiun mesin dan ke gudang **WAJIB** melalui Material Handling:
  `Proses Mesin (Out) -> MH Pickup -> [Mesin Berikutnya / Menuju Pengaju] -> Sudah Diambil`.
- Bagian Gudang tidak boleh mem-bypass penjemputan Material Handling (tidak boleh langsung menyatakan "Sudah Diambil" sebelum barang dijemput dan dibawa oleh kurir logistik MH).

## 5. SINKRONISASI DOKUMENTASI WAJIB (.MD ALWAYS IN SYNC)
- Setiap kali developer / AI Agent menambah fitur, mengubah alur kerja, memperbaiki bug, memodifikasi skema basis data, atau mengubah antarmuka pengguna, file dokumentasi Markdown (`.md`) terkait (`about.md`, `README.md`, `docs/SOP_DAN_ALUR_PROSES.md`, atau `SESSION_RESUME_AKSES_AKUN.md`) **WAJIB DIPERBARUI SECARA SIMULTAN**.
- Dokumentasi harus selalu menjadi cerminan akurat 100% dari kode program yang berjalan secara nyata (*living documentation*).
- **DILARANG KERAS** membiarkan perubahan program selesai tanpa memperbarui dokumentasinya, agar seluruh anggota tim dan sistem selalu dapat memantau kondisi terkini proyek secara transparan.

## 6. KEPATUHAN PADA SETIAP PROMPT & SESI (MANDATORY PROMPT COMPLIANCE)
- Seluruh instruksi, rencana kerja (`/plan`), eksekusi kode, maupun pengujian oleh AI Agent pada setiap prompt **WAJIB** mematuhi seluruh butir aturan di dalam `guardrails.md` ini tanpa pengecualian.
- Selalu prioritaskan keamanan data operasional (*Zero Database Regression*) dan keutuhan fitur eksisting (*No Blind Overwrite*).

## 7. PRINSIP SATU PINTU MASTER KOMPONEN (DILARANG MASUK DARI WR)
- Penambahan katalog master resmi HPK mutlak melalui menu Master Komponen oleh pihak berwenang.
- Komponen non-master yang dibuat melalui form Work Request (WR) berstatus `source = 'wr_adhoc'` untuk kebutuhan pesanan saat itu saja ("buat lewat aja") dan **100% TERISOLASI/DILARANG TAMPIL** di modul Master Komponen, Safety Stock, maupun KPI inventaris utama.

## 8. PEMBERSIHAN KODE USANG & KONFIRMASI PENGHAPUSAN (NO DEAD CODE, KEEP REAL DATA)
- **Kode/Tampilan Lama Wajib Dihapus Jika Tidak Dipakai:** Setiap kali ada pengembangan atau pembaruan yang menggantikan fitur, logika, file, atau elemen tampilan lama, bagian lama yang sudah tidak terpakai **WAJIB dihapus** agar program tetap bersih, ringan, dan tidak memicu bug tersembunyi.
- **Wajib Memastikan & Konfirmasi ke User:** Sebelum menghapus kode atau fitur lama, AI Agent **wajib menanyakan/memastikan terlebih dahulu kepada user** apakah bagian lama tersebut benar-benar sudah tidak terpakai dan boleh dihapus.
- **Batas Mutlak Larangan Hapus Data:** Kewajiban penghapusan ini **HANYA berlaku untuk kode program dan tampilan (*source code / UI*)** serta data dummy yang diizinkan user. **DILARANG KERAS menghapus database, tabel produksi, atau data riwayat transaksi operasional (WRI, Stok, Komponen, User) yang sedang berjalan.**

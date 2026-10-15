# WR System (Work Request) - PT Hydraxle Perkasa

## Tentang Program
**WR System (Sistem Permintaan Kerja)** adalah aplikasi berbasis web internal milik PT Hydraxle Perkasa. Tujuan utama program ini adalah untuk mempermudah, melacak, dan mengelola alur permintaan komponen atau barang antar departemen di dalam perusahaan secara terpusat dan efisien.

## Masalah yang Diselesaikan
Sebelumnya, pelacakan proses permintaan kerja dan perpindahan komponen antar departemen kemungkinan dilakukan secara manual atau kurang terpusat, sehingga menyulitkan pelacakan (tracking) dokumen, verifikasi (approval), dan pengukuran kecepatan waktu proses (SLA). Sistem ini menyelesaikan masalah tersebut dengan digitalisasi penuh yang memastikan alur perpindahan barang (routing) selalu tepat, setiap tahapan tercatat *real-time*, dan memudahkan pengawasan kinerja melalui dasbor.

## Pengguna dan Perannya
Berdasarkan sistem otentikasi (hak akses) dan data departemen, pengguna utama dari sistem ini meliputi:
- **Pengaju / Peminta (Departemen Asal):** Pengguna yang mengajukan permintaan kerja atau pengambilan komponen, memantau status pesanan, serta melakukan konfirmasi penyelesaian (terima/tolak).
- **Penerima (Departemen Tujuan):** Pengguna yang menerima permintaan dari departemen lain, memproses barang, dan memperbarui status operasional barang di lapangan.
- **Operator (OP) / Kepala:** Penanggung jawab yang melakukan verifikasi (approval) atas dokumen Work Request (WR).
- **Admin / Manajemen:** Pengguna yang memiliki wewenang memantau dasbor analitik secara keseluruhan dan dapat mencetak dokumen secara massal.

## Daftar Fitur Utama
- **Otentikasi & Manajemen Akses:** Login aman dengan pembatasan fitur (menu) berdasarkan kode akses dan asal departemen.
- **Pembuatan Work Request (Form WR):** Pengajuan form dinamis dengan fitur pencarian teks maupun pemindaian (*scan*) QR Code/Barcode komponen.
- **Sistem Routing Otomatis:** Otomatis menentukan departemen tujuan berdasarkan aturan (ID Routing) dari komponen yang dipilih.
- **Manajemen Komponen Baru:** Pendaftaran komponen/barang baru secara langsung melalui aplikasi dengan fitur unggah foto otomatis ke Google Drive.
- **Persetujuan & Pelacakan Berjenjang:** Fitur verifikasi dokumen (terima/tolak) yang memperbarui status transaksi secara langsung (real-time).
- **Dasbor Pemantauan (Monitoring Dashboard):** Visualisasi data kinerja seperti *Service Level Agreement* (SLA), peringatan keterlambatan (Urgent Alert), dan volume permintaan per departemen.
- **Cetak (Print) Cerdas:** Dukungan mencetak lembar kerja Work Request secara satuan maupun massal (*Bulk Print*).

## Teknologi yang Dipakai
Aplikasi ini dikembangkan menggunakan ekosistem *serverless* Google Workspace:
- **Backend / Server:** Google Apps Script (JavaScript)
- **Database Utama:** Google Sheets
- **Penyimpanan Berkas (Foto):** Google Drive
- **Frontend / Antarmuka:** HTML5, CSS3, JavaScript Murni (Vanilla JS)
- **Framework & Pustaka Eksternal:**
  - Bootstrap 5.3 (Sistem UI dan Grid)
  - FontAwesome (Ikon Visual)
  - HTML5-QRCode (Pemindaian Barcode / QR Code)
  - ECharts (Grafik Interaktif dan Visualisasi Data)

## Daftar Isi Dokumentasi
Untuk penjelasan yang lebih detail mengenai alur kerja, teknis, dan aturan bisnis aplikasi, silakan merujuk ke dokumen-dokumen berikut yang berada di dalam folder `docs/`:

1. [PRD (Product Requirements Document)](docs/PRD.md) - Rincian kebutuhan produk, fitur, dan peran pengguna.
2. [WORKFLOW (Alur Kerja)](docs/WORKFLOW.md) - Alur proses utama secara end-to-end (dilengkapi diagram alur).
3. [DATA DICTIONARY (Kamus Data)](docs/DATA_DICTIONARY.md) - Rincian struktur tabel pada Google Sheets, fungsi kolom, dan rumus.
4. [BUSINESS RULES (Aturan Bisnis)](docs/BUSINESS_RULES.md) - Daftar aturan operasional, perhitungan, dan syarat validasi.
5. [TECHNICAL (Dokumentasi Teknis)](docs/TECHNICAL.md) - Rincian struktur file kode, daftar fungsi aplikasi, integrasi eksternal, dan teknis implementasi.
6. [DESIGN (Desain Antarmuka)](docs/DESIGN.md) - Penjelasan komponen visual, palet warna, dan kerangka halaman (wireframe).

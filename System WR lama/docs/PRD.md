# Product Requirements Document (PRD) - WR System

## 1. Latar Belakang dan Masalah
Di dalam operasional PT Hydraxle Perkasa, pergerakan komponen material antar departemen sangat intensif. Proses permintaan, pengambilan, dan serah terima komponen (Work Request) secara manual menimbulkan beberapa masalah:
- **Sulit Dilacak (Tracking):** Tidak ada riwayat terpusat mengenai dokumen WR, siapa yang meminta, dan posisi barang saat ini.
- **Rute yang Salah (Misrouting):** Terkadang barang dikirim atau diminta ke departemen yang salah karena ketidaktahuan peminta.
- **SLA Tidak Terukur:** Manajemen kesulitan mengetahui berapa lama suatu departemen merespon permintaan, sehingga kinerja atau hambatan produksi (bottleneck) sulit dievaluasi.
- **Validasi Ganda:** Dokumen fisik mudah hilang, terselip, atau dipalsukan persetujuannya.

## 2. Tujuan Program
Membangun sebuah aplikasi web terpusat (berbasis ekosistem Google Workspace) untuk mendigitalisasi proses Work Request secara *end-to-end*, memastikan perpindahan komponen selalu tercatat dengan *routing* yang tepat, memudahkan verifikasi oleh penerima, serta menyajikan dasbor analitik bagi manajemen untuk pemantauan performa (SLA).

## 3. Pengguna dan Peran (Roles)
| Peran | Tanggung Jawab / Hak Akses |
|-------|-----------------------------|
| **Pemohon / Pengaju** | Membuat dokumen Work Request (mencari barang atau scan QR), menambahkan komponen baru, memantau status pesanan, serta memberikan verifikasi akhir. |
| **Penerima** | Menerima notifikasi atau daftar antrian WR, memproses barang, dan memperbarui status WR (misal: Disiapkan, Ditolak, Selesai). |
| **Operator (OP) / Kepala** | Berfungsi sebagai pemberi izin (Approver) dan penanggung jawab di lapangan atas barang yang masuk/keluar. |
| **Manajemen / Admin** | Memiliki akses ke Dashboard analitik, dapat melihat peringatan (Urgent Alert) untuk SLA yang terlewati, dan dapat mencetak arsip WR massal. |
*(Hak akses lebih rinci dikendalikan menggunakan kode angka yang disimpan di lembar konfigurasi `Akses User` dan `Akses Dept User` pada database).*

## 4. Daftar Fitur
| ID | Nama Fitur | Deskripsi | Aturan Bisnis Terkait |
|----|------------|-------------|-------------------------|
| **F-01** | **Otentikasi & Otorisasi** | Sistem login menggunakan ID/Username dan Password. Menu yang tampil (sidebar) akan menyesuaikan dengan kode wewenang departemen. | BR-01 |
| **F-02** | **Form Pengajuan WR** | Keranjang/Cart elektronik untuk meminta komponen. Bisa dengan mengetik nama/kode barang atau memindai (scan) Barcode/QR Code via kamera. | BR-02, BR-03 |
| **F-03** | **Sistem Routing Otomatis** | Berdasarkan barang yang dipilih, sistem otomatis merujuk siapa "Departemen Penerima" sehingga tidak salah sasaran. | BR-04 |
| **F-04** | **Pendaftaran Komponen Baru** | Jika barang tidak ada di sistem, pengguna dapat meregistrasikan barang baru dengan mengisi spesifikasi dan mengunggah foto (otomatis masuk ke Google Drive). | BR-05 |
| **F-05** | **Persetujuan (Approval) & Verifikasi** | Sistem penerimaan dan penolakan antar departemen. Mencatat siapa OP/Kepala yang mengesahkan. | BR-06 |
| **F-06** | **Dashboard & SLA Monitoring** | Halaman analitik visual (grafik ECharts) yang menunjukkan metrik SLA, volume departemen, dan memunculkan *Urgent Alert* (peringatan merah) jika tiket tidak direspons > 3 hari. | BR-07 |
| **F-07** | **Safety Stock Limit** | Halaman untuk memantau batasan aman jumlah barang di gudang. | BR-08 |
| **F-08** | **Print & Bulk Print** | Fasilitas untuk memfilter lalu mencetak dokumen Work Request sebagai arsip/jalan fisik (baik satu per satu atau massal). | BR-09 |

## 5. User Flow (Alur Pengguna)

### A. Alur Pemohon (Pengaju)
1. **Login** menggunakan akun departemen asal.
2. Buka menu **Buat Work Request**.
3. **Cari Barang** (ketik) atau **Scan QR Code**. 
   - *Jika barang tidak ada:* Klik "Komponen Baru", isi data dimensi/material, foto barang, lalu simpan.
4. Barang masuk ke keranjang sementara. Masukkan kuantitas (jumlah). 
5. Klik **Kirim / Submit**. Sistem akan memberikan nomor WR otomatis.
6. Pantau di halaman **Data WR** (Tab Pengajuan).
7. Setelah departemen tujuan memproses dan barang siap, Pemohon akan melakukan konfirmasi final di dalam sistem.

### B. Alur Penerima (Departemen Tujuan)
1. **Login** menggunakan akun departemen penerima.
2. Buka halaman **Data WR** (Tab Penerimaan).
3. Lihat daftar permintaan yang masuk (*Pending / Menunggu*).
4. Klik **Verifikasi**, lalu pilih untuk "Terima" atau "Tolak" berdasarkan kondisi lapangan atau stok.
5. Jika diterima, identitas OP Penerima akan tercatat dan status berubah menjadi *Diproses* atau sejenisnya.

### C. Alur Manajemen
1. **Login** menggunakan akun dengan hak akses tinggi.
2. Buka halaman **Dashboard**.
3. Lihat visualisasi metrik dan evaluasi baris yang terkena *Urgent Alert*.
4. (Opsional) Buka halaman **Cetak WR** untuk membuat laporan hardcopy akhir bulan.

## 6. Batasan dan Kekurangan Saat Ini
- **Ketergantungan Ekosistem:** Semua data mengandalkan Google Sheets. Pembacaan dan penulisan massal secara bersamaan dapat menyebabkan batas waktu/batas muat (Quota Limit / Race Conditions) dari Google Apps Script.
- **Kinerja Data Besar:** Jika jumlah `Log WR` sudah puluhan ribu baris, waktu tunggu antarmuka saat menarik data akan mulai melambat, sehingga diperlukan *archiving* rutin.
- **Keamanan:** Kredensial tidak di-enkripsi di database (disimpan dalam *plaintext* pada Spreadsheet) karena saat program dibuat, sistem pengelolaan keamanan belum diterapkan. Sehingga, data pengguna *(password)* masih terbuka begitu saja dan siapa pun yang memiliki akses file Excel tersebut dapat melihatnya. Hal ini rentan, namun belum ada rencana khusus untuk mengubahnya.

## 7. Referensi Aturan Bisnis
Lihat dokumen [BUSINESS_RULES.md](BUSINESS_RULES.md) untuk detail lengkap mengenai penomoran unik, rumus otomatisasi, dan validasi data.

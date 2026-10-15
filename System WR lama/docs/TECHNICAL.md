# Dokumentasi Teknis (TECHNICAL) - WR System

Dokumen ini ditujukan bagi pengembang atau administrator IT untuk memahami jeroan sistem, arsitektur kode, integrasi eksternal, dan potensi risiko teknis.

## 1. Struktur File & Modul
- `Kode.js`: Modul *Backend* (Server-side) utama yang berinteraksi dengan Google Sheets dan Drive.
- `Index.html`: Berkas *Frontend* utama (Layout HTML). Memuat struktur wadah aplikasi.
- `style.html`: Berisi semua kode desain CSS.
- `js_auth.html`: Menangani logika validasi login dan penyembunyian antarmuka awal.
- `js_global.html`: Modul fungsi umum (Navigasi tab, popup peringatan/toast, dan format data).
- `js_form_wr.html`: Modul untuk membuat *cart* (keranjang) permintaan dan *scanning* komponen.
- `js_dashboard.html`: Memproses data mentah menjadi analitik visual (grafik).
- `js_print.html`: Logika memuat, memfilter, dan mengatur orientasi cetak PDF.
- `js_safety_stock.html`: Logika perhitungan peringatan ketersediaan stok kritis.
- `Material Handling/`: Sub-folder berisi kloningan sistem serupa yang disesuaikan (*customized*) khusus untuk alur kerja di Gudang.

## 2. Daftar Fungsi Utama (Backend - `Kode.js`)

| Nama Fungsi | Tugas (Bahasa Awam) | Dipanggil Oleh | Membaca / Menulis Sheet |
|-------------|---------------------|----------------|-------------------------|
| `doGet()` | Menyajikan halaman web ke layar pengguna (browser). | Google Apps Script (Otomatis) | Tidak ada |
| `checkLogin()` | Mencocokkan kata sandi dan mengatur akses menu. | Frontend (`js_auth.html`) | Membaca: `Data User`, `Akses User`, `Akses Dept User` |
| `getRoutingMap()` | Mencari tahu siapa departemen yang berhak menerima barang tertentu. | `getComponentData()`, `submitBulkWR()` | Membaca: `ID_Routing` |
| `getComponentData()` | Menarik seluruh daftar barang dari database utama. | Frontend (`js_form_wr.html`) | Membaca: `Database Komponen` / `Database SPO` |
| `_internalSaveNewComponent()`| Menyimpan foto dan info barang yang belum ada di database. | `submitBulkWR()` | Menulis: `Komponen baru` |
| `submitBulkWR()` | Memasukkan banyak permintaan tiket sekaligus, serta membuat nomor seri dan QR Code. | Frontend (`js_form_wr.html`) | Membaca & Menulis: `Log WR` |
| `updateWRStatus()` | Mengganti status (Terima/Tolak/Selesai) dan mencatat nama penyetuju. | Frontend (`js_dashboard.html`) | Membaca & Menulis: `Log WR` |
| `processDefectAndScan()` | Merekam log pengerjaan, cicilan (parsial), dan komponen yang cacat (defect). | Frontend (Tombol Proses) | Membaca & Menulis: `Log WR` |
| `updateComponentStock()` | Menambahkan jumlah saldo akhir jika barang telah diserahkan (Selesai). | `processDefectAndScan()` | Membaca & Menulis: `Database Komponen` |

## 3. Integrasi Eksternal
1. **Google Drive (App & API):** Secara program menyimpan gambar/foto komponen baru dalam bentuk Base64/Blob ke dalam folder khusus (`folderId`).
2. **API QR Server (`api.qrserver.com`):** Melakukan konversi teks (ID Transaksi) menjadi gambar visual Barcode secara langsung (Injeksi Formula ke Sheet).
3. **Pustaka Pihak Ketiga (CDN):** 
   - *Bootstrap 5.3* (Grid dan Responsivitas UI)
   - *ECharts* (Pembuatan Diagram dan Visualisasi Data)
   - *HTML5-QRCode* (Mengakses Kamera Komputer/HP untuk pemindaian fisik)

## 4. Konfigurasi Variabel Penting
Di dalam `Kode.js`, terdapat beberapa konstanta (Hardcoded) yang sangat penting (Jika sheet pindah atau direplika, ID ini harus disesuaikan):
- `SPREADSHEET_ID`: Menyimpan ID URL Spreadsheet utama (Log transaksi & Hak Akses).
- `TARGET_SS_ID`: Menyimpan ID URL Spreadsheet Master Data (Database Komponen).
- `folderId` (`1ncxDmKCeNkGuOx9EAUVCVyS_h9ADy3nt`): Lokasi spesifik di Drive tempat jatuhnya *upload* gambar dari pengguna.

## 5. Cara Deploy dan Pengaturan Akses
Aplikasi ini di-*deploy* sebagai **Google Apps Script Web App**.
- **Siapa yang bisa membuka:** Biasanya diatur sebagai *"Anyone within [Nama Perusahaan]"* atau *"Anyone with the link"*. Pengamanan dikendalikan murni melalui sistem Login di dalam layar aplikasi itu sendiri.
- **Tugas Rutin Admin:**
  - *Menambah User Baru:* Cukup ketik manual di Sheet `Data User` baris terbawah.
  - *Memperbarui Routing:* Ubah peta teks di Sheet `ID_Routing`. Sistem akan membacanya secara dinamis.

## 6. Masalah Teknis dan Risiko (Technical Debt)
- **Kelemahan Keamanan:** *Password* pengguna di `Data User` berbentuk teks murni (*Plain-text*). Hal ini dikarenakan saat pengembangan awal belum ada implementasi keamanan khusus, sehingga data *password* dibiarkan terbuka. Jika seseorang memiliki akses *View* atau *Edit* ke file Excel (Sheet), ia bisa membajak akun pengguna mana pun. Belum ada rencana perbaikan dalam waktu dekat, namun ke depannya disarankan menggunakan enkripsi (*Hash/MD5*).
- **Potensi Lambat (Performance Bottleneck):** Aplikasi menggunakan *Looping* ke belakang (Reverse For-Loop) saat memperbarui status di `updateWRStatus`. Jika jumlah baris di Sheet `Log WR` sudah mencapai belasan ribu, pergerakan status akan terganggu karena butuh waktu lama untuk menemukan ID di dasar tabel. Perlu *Archiving* setiap tahun.
- **Kode Duplikat (Redundancy):** Folder `Material Handling` merupakan *copy-paste* sistem utama yang dimodifikasi. Jika ada perbaikan (*bug-fix*), developer harus memperbaikinya di dua tempat terpisah.
- **Batas Kuota Eksekusi (Timeout):** Pada fungsi `submitBulkWR`, pemanggilan penyisipan baris tunggal (`appendRow`) pada log riwayat bisa memicu batas waktu (Timeout 6 menit) dari Google jika beban input keranjang dalam satu waktu terlalu besar.

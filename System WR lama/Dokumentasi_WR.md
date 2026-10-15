# Dokumentasi Sistem Work Request (WR) - PT Hydraxle Perkasa

Dokumen ini berisi penjelasan teknis dan fungsional secara komprehensif mengenai **WR System** (Sistem Permintaan Kerja / Work Request) milik PT Hydraxle Perkasa. Sistem ini dibangun menggunakan ekosistem **Google Workspace** khususnya **Google Apps Script (GAS)** dan menjadikan **Google Sheets** sebagai basis data utama.

---

## 1. Gambaran Umum Proyek

**WR System** adalah aplikasi berbasis web internal yang dirancang untuk mempermudah, melacak, dan mengelola alur permintaan komponen antar departemen di dalam PT Hydraxle Perkasa. 

**Teknologi yang Digunakan:**
- **Backend/Server:** Google Apps Script (JavaScript)
- **Database:** Google Sheets
- **Frontend/Antarmuka:** HTML5, CSS3, JavaScript murni (Vanilla JS)
- **Framework & Library:** 
  - Bootstrap 5.3 (Sistem Grid dan Komponen UI)
  - FontAwesome (Ikon)
  - HTML5-QRCode (Pemindaian Barcode/QR Code)
  - ECharts (Visualisasi Data & Grafik Dashboard)

---

## 2. Arsitektur & Struktur File

Sistem ini memisahkan antara logika server, tata letak antarmuka (UI), dan skrip interaksi pengguna melalui beberapa file terpisah untuk memudahkan perawatan (maintenance).

### Daftar File Utama:
1. **`Kode.js`**: Merupakan jantung (backend) dari aplikasi. File ini bertugas untuk berkomunikasi langsung dengan Google Sheets (membaca, menulis, memperbarui data) dan mengirimkan data tersebut ke tampilan antarmuka.
2. **`Index.html`**: File kerangka antarmuka utama. Menyimpan layout struktur web seperti menu Sidebar, Header, dan kerangka halaman/tab aplikasi. Semua file HTML lainnya digabungkan ke dalam file ini saat di-render.
3. **`style.html`**: Menyimpan semua kode CSS (Desain). Mengatur tata letak responsif, warna tema (seperti *glassmorphism* dan animasi tombol), serta penyesuaian gaya tabel.
4. **`js_*.html` (Modul Frontend):**
   - `js_auth.html`: Mengelola fungsi validasi saat proses Login.
   - `js_dashboard.html`: Skrip untuk menampilkan data monitoring, analitik, dan grafik interaktif (ECharts).
   - `js_form_wr.html`: Mengelola interaksi saat pembuatan Work Request baru (pencarian barang, input data, list keranjang/cart).
   - `js_global.html`: Menyimpan fungsi utilitas yang dipakai di banyak tempat (seperti navigasi antar halaman, memunculkan notifikasi/Toast, format tanggal).
   - `js_print.html`: Logika untuk menyaring, menampilkan, dan mencetak dokumen Work Request.
   - `js_safety_stock.html`: Mengelola halaman pemantauan limit stok aman.
5. **Folder `Material Handling/`**: Sub-sistem terpisah yang memuat program turunan/serupa namun dikhususkan untuk prosedur *Material Handling* (Gudang). Memiliki set file `Kode.js`, `Index.html`, dan skrip `js_*_mh.html` tersendiri.

---

## 3. Fitur Utama & Alur Kerja (Workflows)

### A. Otentikasi & Akses (Login System)
- Pengguna harus memasukkan Username dan Password.
- Sistem mengecek kecocokan data dengan Sheet `Data User`.
- Setelah berhasil, sistem akan menyesuaikan **tampilan menu sidebar** berdasarkan "Akses Code" (Wewenang) dan Departemen yang dimiliki pengguna. 

### B. Pembuatan Work Request (Form WR)
- **Pemilihan Komponen**: Pengguna memohon komponen dengan mencari dari database menggunakan teks atau pemindai (Scan) QR Code.
- **Routing Otomatis**: Sistem secara otomatis menentukan departemen "Penerima" / tujuan berdasarkan ID Routing komponen.
- **Komponen Baru**: Jika barang belum ada di database, pengguna dapat menambahkan "Komponen Baru". Sistem akan meminta input spesifikasi (dimensi, material) dan foto barang. Foto akan langsung diunggah (upload) ke folder khusus di Google Drive, dan barang akan langsung mendapatkan kode/QR Code baru.
- **Penomoran Unik**: Setiap WR yang dikirim akan secara otomatis dibuatkan nomor urut unik dengan format: `WR/Dept/Tanggal/Urutan/KodeBarang/UrutanBarangHarian`.

### C. Persetujuan & Verifikasi Dokumen
- Terdapat halaman **Data Work Request** tempat memantau progres persetujuan.
- **Pengaju (Peminta)** maupun **Penerima** wajib melakukan verifikasi ("Terima" atau "Tolak").
- Jika disetujui, identitas pembuat/penerima (OP/Kepala) akan tersimpan, dan status operasional barang (misal: "Dalam Antrian") dapat diperbarui secara terpusat (real-time).

### D. Dashboard & Monitoring
- Data transaksi ditarik ke dalam visualisasi grafis (diagram lingkaran/pie, diagram batang/bar).
- Memungkinkan manajemen melihat metrik SLA (Service Level Agreement), kecepatan respon, dan departemen yang memiliki permintaan tertinggi.
- Memiliki fitur "Urgent Alert" (Peringatan Merah) untuk WR yang belum selesai melampaui batas waktu tertentu (> 3 Hari).

### E. Cetak (Print) Dokumen
- Pengguna dengan wewenang khusus dapat mencetak riwayat WR (baik yang baru diajukan maupun arsip).
- Cetak bisa dilakukan satu per satu atau **Cetak Massal (Bulk Print)**.

---

## 4. Skema Database (Google Sheets)

Semua penyimpanan data menggunakan Spreadsheet (ID: `172L0GTPras725MUDwzfnCQBwf-IV_bt8yywEMWf4EJA`). Berikut adalah lembar (Sheet) yang dikelola:

1. **`Log WR`**: Tabel transaksi paling krusial. Menyimpan semua pengajuan WR, termasuk informasi Pemohon, Penerima, No WR, Link Foto, Status Approval, Nama OP, dan Formula QR Code (mengambil data dari API eksternal).
2. **`Database Komponen` & `Database SPO`**: Lembar kerja berisi ribuan daftar nama barang/komponen baku milik perusahaan, gambar, dan kode referensinya.
3. **`Komponen baru`**: Lembar kerja penampung sementara bagi komponen yang didaftarkan langsung oleh OP via aplikasi sebelum dinormalisasi ke database utama.
4. **`ID_Routing` & `Format WR`**: Tabel konfigurasi. Menyimpan map/rute perjalanan barang, misalnya Komponen A alurnya harus melewati Departemen B lalu ke C.
5. **`Data User`, `Akses User`, & `Akses Dept User`**: Tabel sekuritas. Menyimpan data login, pembagian hak akses menu (berdasarkan kode seperti 1,2,3), dan mapping identitas departemen pengguna.

---

## 5. Penjelasan Fungsi Teknis (Backend API - `Kode.js`)

Beberapa fungsi utama (API) yang ditulis dalam `Kode.js` dan menghubungkan logika bisnis perusahaan:

- **`doGet()`**
  Menyajikan file `Index.html` sebagai halaman web (Web App) ke browser pengguna, serta mengatur tag meta agar situs responsif di perangkat mobile.

- **`checkLogin(username, password)`**
  Mengambil data baris-per-baris dari sheet `Data User`. Jika cocok, fungsi ini mem-parsing hak akses dan menerjemahkan kode departemen menggunakan fungsi `resolveDeptUserValue()`, lalu mengembalikannya ke sisi frontend dalam bentuk objek JSON.

- **`getComponentData(userDept)`**
  Sistem pintar yang mengambil daftar barang. Jika `userDept` kodenya 12 (SPO), sistem membaca `Database SPO`. Fungsi ini juga mencocokkan rute (routing) barang menggunakan fungsi `getRoutingMap()` sehingga barang tahu harus diantar ke mana.

- **`submitBulkWR(listFormData)`**
  Fungsi paling kompleks untuk memasukkan (insert) data dari "Keranjang/Cart" ke tabel `Log WR`. 
  - Melakukan *loop* untuk setiap form.
  - Memeriksa jumlah order hari itu dan memberikan nomor urutan dinamis (`runningWrSeq`).
  - Menginjeksi rumus Google Sheets (Formula) untuk membuat QR Code langsung di kolom yang sesuai.
  
- **`updateWRStatus(noWR, type, newStatus, ...)`**
  Melakukan pencarian (*reverse loop*, dari bawah ke atas tabel) untuk mencari nomor WR, dan memperbarui sel status (misal: "Diterima"). Fungsi ini otomatis mengisi nama Penanggung Jawab pada kolom yang sesuai.

- **`_internalSaveNewComponent(data)`**
  Menangani komponen yang baru pertama kali diciptakan. 
  - Mengonversi data gambar (*Base64*) menjadi *Blob*.
  - Menunggah (upload) Blob ke folder Google Drive (`1ncxDmKCeNkGuOx9EAUVCVyS_h9ADy3nt`).
  - Mendapatkan URL Gambar dan menyimpannya di baris baru sheet `Komponen baru`.

---

*(Dokumentasi ini bersifat adaptif dan dapat diperbarui menyesuaikan versi pengembangan aplikasi di masa depan).*

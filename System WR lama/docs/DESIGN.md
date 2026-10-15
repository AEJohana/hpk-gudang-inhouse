# Desain Antarmuka (DESIGN) - WR System

Dokumen ini mendeskripsikan kerangka tampilan (UI/UX) dan sistem desain dari aplikasi berdasarkan analisis CSS (`style.html`) dan kerangka HTML.

## 1. Tema & Palet Warna
Sistem ini menggunakan tema profesional yang bersih (Clean) bergaya *Glassmorphism* (Efek kaca transparan dan *blur*) dengan perpaduan warna korporat.

**CSS Variables Utama:**
- `--hpk-blue` : **#0a2342** (Navy Blue / Biru Dongker) - Digunakan untuk Sidebar latar utama dan Header Utama.
- `--hpk-teal` : **#4a9e9e** (Teal / Hijau Kebiruan) - Aksen primer, digunakan untuk efek *Gradient* bersama Navy, warna ikon *hover*, dan tombol aksi.
- `--hpk-red` : **#e11d48** (Rose Red) - Digunakan untuk tombol peringatan, pembatalan (Tolak), dan peringatan Urgent SLA.
- `--hpk-light`: **#f0f4f8** (Abu-abu sangat terang) - Latar belakang sekunder untuk kartu (Cards).
- `Latar Aplikasi`: Bersih putih dengan pola grid samar (Technical Grid Pattern).

**Tipografi:**
- Font Utama: **'Poppins'**, sans-serif (Font modern berciri khas lengkungan tegas dan proporsional untuk UI aplikasi).

## 2. Struktur Tata Letak (Layout) Dasar
Secara umum, aplikasi membagi layar menjadi 2 panel utama di Desktop (dan model *Off-canvas* / *Hamburger Menu* di Mobile).
1. **Panel Sidebar Kiri:** Berwarna gelap (Navy Blue / Putih opsional), berisi identitas perusahaan, profil *user* yang sedang login, dan daftar menu vertikal.
2. **Area Konten Utama (Kanan):** 
   - **Header:** Berisi nama halaman aktif yang menggunakan efek transisi melengkung (Gradient latar).
   - **Body:** Menggunakan model *Card-based* putih melayang dengan efek bayangan (Drop Shadow halus).

## 3. Wireframe Halaman (Teks Deskriptif)

### A. Halaman Login
- Layar penuh tengah (*Centered Modal*).
- Logo perusahaan di atas.
- Input Box untuk *Username* dan *Password* berdesain melingkar memanjang (Pill shape).
- Tombol *Login* lebar.

### B. Halaman Form WR (Buat Work Request)
- **Top Bar:** Input *Search Box* panjang. Di sebelahnya ada ikon Kamera untuk memicu modal "Scanner Barcode" berkedip garis Teal.
- **Section Komponen Baru:** Tombol + Komponen Baru yang membuka Popup (*Modal*) untuk *upload* foto dari galeri/kamera perangkat.
- **Section Keranjang (Cart):** Daftar kartu barang yang dipilih secara dinamis. Setiap kartu berisi gambar kecil (kiri), deskripsi barang (tengah), dan Input Qty (kanan).
- **Footer Aksi:** Tombol "Submit / Kirim WR".

### C. Halaman Data WR (List Tiket)
- **Tab Navigasi Atas:** Gaya *Pills* Glassmorphism ("Pengajuan Saya" dan "Penerimaan").
- **Daftar Kartu Tiket (Feed):** Ditampilkan berurutan ke bawah.
  - *Sisi Kiri:* Ikon *Badge* berputar (Spin/Pulse) menandakan status aktif, beserta *QR Code* mini.
  - *Tengah:* No WR, Deskripsi barang, Routing.
  - *Sisi Kanan (Aksi):* Tombol Verifikasi ("Terima/Tolak") bagi kepala. Jika telah disetujui, tombol berubah menjadi aksi "Update Status / Proses".

### D. Halaman Dashboard
- **Header Atas:** Ringkasan KPI (*Total Masuk*, *Selesai*, *Menunggu*, dll) dalam bentuk kotak melayang.
- **Tengah:** 
  - Bagan Lingkaran (Pie Chart) persentase status dari pustaka *ECharts*.
  - Bagan Batang (Bar Chart) departemen paling sibuk.
- **Bawah (Urgent Alert):** Tabel atau barisan daftar tiket merah (keterlambatan SLA di atas 3 Hari).

### E. Komponen Notifikasi (Toast)
- Muncul dari sudut bawah atau atas layar.
- Menginformasikan status *(Success/Error)* yang menghilang otomatis setelah 3 detik. Menggunakan animasi geser.

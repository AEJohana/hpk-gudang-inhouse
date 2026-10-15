# Kamus Data (DATA DICTIONARY) - WR System

Dokumen ini menjelaskan struktur penyimpanan data (Google Sheets) yang berfungsi sebagai basis data utama aplikasi.

## 1. Sheet `Log WR`
**Fungsi:** Menyimpan riwayat utama transaksi Work Request dari semua departemen secara *real-time*.
**Kualitas Data (Isu):** Karena diisi otomatis oleh bot, tipe datanya cukup rapi, namun berpotensi membesar tak terbatas (*bloated*) sehingga akan memperlambat pembacaan *(query)* ke depannya jika tidak di-arsip.

| Kolom | Nama Kolom | Tipe Data | Diisi Oleh | Keterangan |
|-------|------------|-----------|------------|------------|
| B | Tanggal Order | Date / Text | Otomatis | Tanggal transaksi pengajuan. |
| C | No WR | String | Otomatis | ID Transaksi Unik (Format: WR/Dept/Tanggal/Urut). |
| D | Kode Komponen | String | Otomatis | Kode identitas barang dari database. |
| E | Nama Komponen | String | Otomatis | Nama deskriptif barang. |
| F | Link Gambar | URL | Otomatis | Tautan URL gambar ke Google Drive (jika ada). |
| G | Routing | String | Otomatis | Rute asal departemen ke tujuan. |
| H | Dimensi | String | Otomatis | Ukuran spesifik komponen. |
| I | Qty (Jumlah) | Number | Manual (Peminta) | Jumlah barang yang direquest. |
| J | Dept Pengaju | String | Otomatis | Departemen asal pengguna yang login. |
| K | Dept Penerima| String | Otomatis | Departemen tujuan (berdasarkan Mapping Routing). |
| L | Deskripsi | Text | Manual (Peminta) | Catatan / instruksi khusus dari peminta. |
| M | Status Pengaju| String | Manual (Ka.Pengaju) | Persetujuan asal (Menunggu / Diterima / Ditolak). |
| N | Status Penerima| String | Manual (Ka.Penerima) | Persetujuan tujuan (Menunggu / Diterima / Ditolak). |
| O | Status Barang | String | Manual (Penerima) | Operasional (Antrian / Diproses / Siap Diambil / dll). |
| P | Status Cetak | String | Otomatis | Menandakan tiket sudah diprint (Belum/Sudah Dicetak). |
| Q | Lokasi Gambar | Kosong | Terlewat (Skip) | Kolom ini dibiarkan kosong/dilewatkan oleh program saat ini. |
| R | QR Code (Rumus) | Formula | Otomatis (Injeksi) | `=IMAGE("https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" & ENCODEURL(C...))` (Menghasilkan gambar barcode dari nomor WR). |
| S | Logs (Riwayat) | String | Otomatis | Log beruntun pembaruan operasional, parsial, dan waktu (Data Proses). |
| Y/Z/AA | Operator & Ka. | String | Otomatis / Validasi | Nama/Identitas petugas yang melakukan pengesahan. |

## 2. Sheet `Database Komponen` (dan `Database SPO`)
**Fungsi:** Master data berisi daftar semua komponen, material, atau barang di perusahaan.
**Hubungan:** Kolom 'Kode Komponen' pada Sheet ini menjadi rujukan utama bagi sheet `Log WR` kolom D.
**Struktur Kolom Utama (Berdasarkan pembacaan dinamis script):**
- **Kode Komponen:** Identifier unik barang.
- **Nama Komponen:** Deskripsi barang.
- **Routing:** Rute wajib dari barang tersebut.
- **Saldo:** Sisa kuantitas barang di gudang.
- **Kode Rak:** Posisi fisik komponen di gudang.
- **Gambar:** Tautan visual ke foto produk.

## 3. Sheet `Komponen baru`
**Fungsi:** Penampung sementara (*Staging*) untuk komponen yang baru didaftarkan secara mandiri oleh user melalui form, sebelum akhirnya di-review dan dimasukkan ke Database Utama oleh Admin Gudang.
| Kolom | Nama Kolom | Diisi Oleh | Keterangan |
|-------|------------|------------|------------|
| B | Tanggal | Otomatis | Kapan data dimasukkan. |
| C | Kode Barang | Otomatis | Akan diisi "Baru" (Menunggu penomoran resmi). |
| D | Nama Barang | Manual (User)| - |
| E | Foto | Otomatis | Berisi URL gambar yang berhasil diunggah ke Google Drive. |
| F-K | (Spesifikasi) | Manual (User)| Karakter, Tipe Material, Panjang, Lebar, Tinggi, Duplikat. |
| M | Dimensi | Otomatis | Hasil penggabungan perhitungan ukuran spesifikasi. |

## 4. Sheet Konfigurasi Otentikasi & Routing
### A. `Data User`
Tabel master pengguna (Login Credentials).
- **Kolom B (ID/Username):** Nama login (Case insensitive).
- **Kolom C (Dept):** Kode identitas Departemen (Merujuk ke *Akses Dept User*).
- **Kolom D (Password):** Kata sandi rahasia (*Masalah Data: Disimpan dalam plain-text, rentan secara keamanan*).
- **Kolom E (Kode Akses):** Daftar angka wewenang menu (misal: "1,2,3") merujuk ke *Akses User*.
- **Kolom F (Nama Akun):** Nama profil petugas / operator.

### B. `Akses User`
Tabel kamus (Dictionary) penerjemah wewenang.
- **Kolom B (Kode Akses):** "1", "2", dsb.
- **Kolom C (Nama Akses):** Misal "Membuat Work Request", "Melihat Dashboard", dsb.

### C. `Akses Dept User`
Menerjemahkan identitas departemen numerik menjadi nama aslinya. (Misal kode 12 menjadi "SPO").

### D. `ID_Routing`
Menentukan departemen mana yang berhak memproses sebuah barang.
- **ID:** Kode referensi dari database.
- **Routing:** Nama routing / proses (Misal: "Bubut").
- **Identitas (Dept Tujuan):** Departemen yang ditugaskan (Misal: "Dept Machining").
*Fungsi:* Jika user memilih barang dengan routing "Bubut", aplikasi akan otomatis mengisi "Dept Penerima" sebagai "Dept Machining".

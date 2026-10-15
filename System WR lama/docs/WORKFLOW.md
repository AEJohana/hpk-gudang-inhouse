# Alur Kerja & Proses (WORKFLOW) - WR System

Dokumen ini menjelaskan alur kerja dari sistem pembuatan Work Request (WR) hingga tiket dinyatakan selesai (Closed), termasuk pembagian peran dan perubahan status dokumen.

## 1. Alur Proses End-to-End (Ujung-ke-Ujung)

Secara garis besar, pergerakan Work Request melalui 5 fase utama:
1. **Fase Inisiasi (Pengajuan):** Operator/Peminta membuat daftar permintaan (Cart) dan mengirimkannya ke server. Sistem meng-generate Nomor WR unik dan QR Code.
2. **Fase Verifikasi Internal (Pengaju):** Kepala departemen asal harus menekan tombol "Terima" agar tiket ini sah untuk diteruskan ke departemen tujuan. Jika "Tolak", alur berhenti di sini.
3. **Fase Verifikasi Tujuan (Penerima):** Departemen tujuan (misal Gudang atau Produksi) melihat antrian tiket masuk. Mereka memverifikasi kesanggupan ("Terima" atau "Tolak"). Jika diterima, barang masuk ke status operasional.
4. **Fase Operasional (Proses Lapangan):** Penerima mulai mengerjakan (merakit, mencari di gudang, dll). Status bergerak ke *Antrian -> Diproses -> Siap Diambil*. Pengerjaan bisa dilakukan secara parsial (dicicil) jika stok tidak langsung cukup.
5. **Fase Penyelesaian (Serah Terima):** Pihak pengaju mengambil barang. Status diperbarui menjadi *Menuju Pengaju* atau *Sudah Diambil*, dan tiket dinyatakan tertutup.

## 2. Alur Berdasarkan Peran

### A. Peran: Pengaju (Pemohon)
- **Tugas:** Membuat WR melalui tab pencarian barang atau menggunakan alat *Scan Barcode/QR Code*.
- **Opsi Lanjutan:** Jika barang belum terdaftar, pengaju wajib mengisi *Form Komponen Baru* yang disertai foto (kamera *smartphone*/komputer).
- **Tindakan Akhir:** Memantau halaman `Data WR`, mengambil barang yang telah berstatus *Siap Diambil*, dan memastikan riwayat log transaksi selesai.

### B. Peran: Kepala / OP Pengaju
- **Tugas:** Mengotorisasi pengajuan yang dibuat oleh tim/bawahannya.
- **Validasi:** Mengubah status Pengaju dari "Menunggu" menjadi "Diterima" atau "Ditolak".

### C. Peran: Penerima (Departemen Tujuan)
- **Tugas:** Menerima notifikasi visual (kartu *Pending*) di menu `Data WR`.
- **Validasi Pertama:** Memberikan respon "Terima" (sanggup memproses) atau "Tolak" (stok kosong/salah departemen).
- **Update Operasional (Live Status):** Bertanggung jawab memperbarui perjalanan barang (misal: klik "Diproses" lalu "Siap Diambil").
- **Parsial (Cicilan):** Dapat menggunakan fitur serah-terima *Parsial* jika jumlah yang diminta (misal 100) baru bisa diselesaikan sebagian (misal 50).

### D. Peran: Manajemen (Admin/SPO)
- **Tugas:** Melihat Dashboard analitik, memonitor peringatan keterlambatan layanan (SLA *Urgent Alert*), dan menggunakan menu *Cetak WR* (*Bulk Print*) untuk rekapitulasi data.

## 3. Diagram Alur Utama (Flowchart)

```mermaid
flowchart TD
    A([Mulai: Pemohon Buat WR]) --> B{Barang Ada di Sistem?}
    B -- Tidak --> C[Buat Komponen Baru & Upload Foto]
    C --> D
    B -- Ya --> D[Masukkan ke Keranjang (Cart)]
    D --> E[Submit WR & Generate QR Code]
    E --> F[Status Pengaju: Menunggu]
    F --> G{Kepala Pengaju Verifikasi}
    G -- Tolak --> H([Tiket Dibatalkan/Selesai])
    G -- Terima --> I[Status Pengaju: Diterima]
    I --> J[Tiket Masuk ke Antrian Penerima]
    J --> K{Kepala Penerima Verifikasi}
    K -- Tolak --> H
    K -- Terima --> L[Status Operasional: Dalam Antrian]
    L --> M[Status Operasional: Diproses]
    M --> N{Selesai Semua atau Sebagian?}
    N -- Sebagian (Parsial) --> O[Status Operasional: Parsial > Siap Diambil]
    O --> P(Pengaju Mengambil Sebagian)
    P --> M
    N -- Semua (Final) --> Q[Status Operasional: Siap Diambil]
    Q --> R[Pengaju Mengambil Barang]
    R --> S([Status Akhir: Sudah Diambil / Selesai])
```

## 4. Daftar Status Dokumen (Lifecycle)

| Tipe Status | Label Status | Penjelasan | Pihak yang Mengubah |
|-------------|--------------|------------|---------------------|
| **Approval Pengaju** | Menunggu | Tiket baru saja diajukan. | Sistem (Otomatis) |
| **Approval Pengaju** | Diterima | Kepala asal setuju permintaan dilanjutkan. | Kepala Pengaju |
| **Approval Pengaju** | Ditolak | Permintaan ditolak (dibatalkan) oleh pihak internal. | Kepala Pengaju |
| **Approval Penerima** | Menunggu | Menunggu respon sanggup/tidaknya departemen tujuan. | Sistem (Otomatis) |
| **Approval Penerima** | Diterima | Departemen tujuan menyetujui untuk memproses barang. | Penerima (OP/Ka) |
| **Approval Penerima** | Ditolak | Departemen tujuan menolak permintaan. | Penerima (OP/Ka) |
| **Operasional (Log)** | Antrian | Barang mulai masuk daftar tunggu produksi/gudang. | OP Penerima |
| **Operasional (Log)** | Diproses | Komponen/Barang sedang dicari, dirakit, atau disiapkan. | OP Penerima |
| **Operasional (Log)** | Siap Diambil | Barang telah selesai dan siap dijemput oleh peminta. | OP Penerima |
| **Operasional (Log)** | Parsial > Siap Diambil | Sebagian (tidak seluruh kuantitas) sudah siap diambil. | OP Penerima |
| **Operasional (Log)** | Sudah Diambil | Tiket selesai *(Closed)*, barang telah berada di tangan peminta. | Pemohon / Penerima |

## 5. Notifikasi Otomatis

**Catatan:** Saat ini, berdasarkan konfigurasi di `Kode.js`, sistem **tidak memiliki** pemicu (trigger) untuk pengiriman notifikasi via *Email* (seperti `MailApp` atau `GmailApp`). 
Pembaruan status bersifat *In-App* (Notifikasi dalam aplikasi melalui pemuatan ulang halaman `Data WR` atau warna *Badge* di Dashboard). 

- Kapan pengguna menyadari ada tiket baru? Saat mereka masuk dan membuka tab **Data WR (Penerimaan)** atau melihat grafik *Urgent Alert* pada halaman **Dashboard**.
- Isinya apa? Nomor WR unik, identitas peminta, foto/barcode barang, kuantitas, dan batas waktu *SLA*.

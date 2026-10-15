# Aturan Bisnis (BUSINESS RULES) - WR System

Dokumen ini memuat seluruh logika bisnis, validasi, dan rumus perhitungan yang tertanam di dalam aplikasi.

| ID | Aturan Bisnis | Lokasi (File / Fungsi) | Contoh Kasus / Keterangan |
|----|---------------|------------------------|---------------------------|
| **BR-01** | **Otorisasi Login (Case Sensitivity)** | `Kode.js` (`checkLogin`) | *Username* bebas besar/kecil (Case-Insensitive), namun *Password* harus sangat identik (Case-Sensitive). |
| **BR-02** | **Penomoran WR Dinamis (ID Generator)** | `Kode.js` (`submitBulkWR`) | Nomor WR digenerate dengan format: `WR/Dept/Tanggal/Urutan/KodeBarang/UrutanBarang`. Urutan reset setiap hari. Contoh: `WR/12/20261008/1/KP-01/1`. |
| **BR-03** | **Pembuatan QR Code Otomatis** | `Kode.js` (`submitBulkWR`) | Aplikasi menginjeksi rumus Google Sheets (`=IMAGE(...)`) ke Kolom R `Log WR` menggunakan public API *qrserver.com*. Saat dicetak, gambar akan muncul. |
| **BR-04** | **Penyimpanan Komponen Baru** | `Kode.js` (`_internalSaveNewComponent`) | Jika "Komponen Baru", ID komponen sementara diset menjadi "Baru". Foto (Base64) di-*convert* menjadi Blob dan diunggah secara publik ke Google Drive folder `1ncx...`. |
| **BR-05** | **Pemetaan Departemen (Auto-Routing)** | `Kode.js` (`getRoutingMap`) | Peminta tidak perlu tahu siapa tujuan barang. Sistem akan melihat kata kunci routing barang di sheet `ID_Routing` dan menentukan otomatis siapa penanggung jawabnya (misal: Routing "Bubut" -> Tujuan "Machining"). |
| **BR-06** | **Prasyarat Tampil di Penerima** | `js_dashboard.html` / `Kode.js` | Permintaan (Tiket) baru tidak akan muncul di layar "Penerima" atau Gudang jika status dari Kepala "Pengaju" belum diubah menjadi "Diterima". |
| **BR-07** | **Pengerjaan Parsial (Cicilan)** | `Kode.js` (`processDefectAndScan`) | Jika peminta order 100 pcs, dan penerima baru siap 50 pcs, sistem akan melabeli status dengan awalan `Parsial > [Status]`. Hanya jika 100 pcs terpenuhi, label `(Final)` diberikan. |
| **BR-08** | **Penambahan Saldo Otomatis (Stock Update)** | `Kode.js` (`updateComponentStock`) | Jika status tiket telah mencapai fase akhir yaitu **"Sudah diambil"**, sistem akan membuka Spreadsheet Master (`1uOd7ZQcY...`) dan *menambahkan* kuantitas pesanan (`+Qty`) ke dalam Saldo barang tersebut. |
| **BR-09** | **Pemisahan String Status (Sanitization)** | `Kode.js` (`sanitizeStatus`) | Saat memvalidasi logika perubahan status, sistem harus menghapus frasa `Parsial >` dari kata menggunakan Regex, agar sistem murni mengevaluasi nama status aslinya. |
| **BR-10** | **SLA & Peringatan Urgent (Red Alert)** | `js_dashboard.html` | Pada Dashboard, tiket yang berstatus 'Menunggu', 'Diproses', atau belum 'Selesai', dan sudah melewati batas waktu 3 Hari (72 jam) akan diwarnai merah dan diklasifikasikan sebagai *Urgent*. Catatan: Angka 3 hari ini **bukan aturan baku/resmi perusahaan**, melainkan asumsi/preferensi pengembang saat ini. Ke depannya, batas waktu ini mungkin perlu dibuat dinamis agar dapat disesuaikan oleh Manajemen. |

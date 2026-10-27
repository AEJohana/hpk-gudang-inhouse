<?php

namespace App\Services;

use App\Models\Location;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ComponentExcelTemplateService
{
    /**
     * Generate 2-Sheet Excel Template for Bulk Component Import.
     * Sheet 1: INPUT DATA KOMPONEN
     * Sheet 2: PEDOMAN & REFERENSI DROPDOWN
     */
    public function generateTemplate(string $outputPath): string
    {
        $writer = new Writer();
        $writer->openToFile($outputPath);

        // STYLES
        $titleStyle = (new Style())
            ->setFontBold()
            ->setFontSize(14)
            ->setFontColor('0A2342');

        $subtitleStyle = (new Style())
            ->setFontItalic()
            ->setFontSize(9)
            ->setFontColor('475569');

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontSize(10)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('0A2342');

        $sampleStyle1 = (new Style())
            ->setFontSize(10)
            ->setBackgroundColor('F8FAFC');

        $sampleStyle2 = (new Style())
            ->setFontSize(10)
            ->setBackgroundColor('FFFFFF');

        $sectionHeaderStyle = (new Style())
            ->setFontBold()
            ->setFontSize(11)
            ->setFontColor('0A2342')
            ->setBackgroundColor('E2E8F0');

        $subHeaderStyle = (new Style())
            ->setFontBold()
            ->setFontSize(10)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('1E293B');

        // ==========================================
        // SHEET 1: INPUT DATA KOMPONEN
        // ==========================================
        $sheet1 = $writer->getCurrentSheet();
        $sheet1->setName('INPUT DATA KOMPONEN');

        // Baris 1: Header Kolom Langsung (Siap Diisi Mulai Baris 2)
        $headers = [
            'Kode Komponen (*Wajib)',
            'Nama Komponen (*Wajib)',
            'Kategori (Lihat Sheet 2)',
            'Satuan / UoM (Lihat Sheet 2)',
            'Panjang (mm)',
            'Lebar / Diameter (mm)',
            'Tebal / Tinggi (mm)',
            'Spesifikasi Teknis',
            'Batas Minimum Stok',
            'Saldo Stok Awal Fisik',
            'Kode Lokasi Rak Gudang',
            'Foto Komponen (Link GDrive / URL Web)',
        ];
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        // ==========================================
        // SHEET 2: PEDOMAN & REFERENSI DROPDOWN
        // ==========================================
        $sheet2 = $writer->addNewSheetAndMakeItCurrent();
        $sheet2->setName('PEDOMAN & REFERENSI DROPDOWN');

        // Judul Sheet 2
        $writer->addRow(Row::fromValues([
            'KAMUS DATA & PEDOMAN PENGISIAN IMPORT KOMPONEN HPK'
        ], $titleStyle));
        $writer->addRow(Row::fromValues(['Gunakan daftar nilai di bawah ini untuk mengisi kolom Kategori, Satuan (UoM), dan Lokasi Rak.'], $subtitleStyle));
        $writer->addRow(Row::fromValues(['']));

        // TABEL 1: CONTOH PENGISIAN DATA KOMPONEN (SAMPEL RIIL)
        $writer->addRow(Row::fromValues(['1. CONTOH PENGISIAN DATA KOMPONEN (SAMPLE EXAMPLES)'], $sectionHeaderStyle));
        $writer->addRow(Row::fromValues([
            'Kode Komponen', 'Nama Komponen', 'Kategori', 'Satuan', 'Panjang (mm)', 'Lebar (mm)', 'Tebal (mm)', 'Spesifikasi', 'Min Stok', 'Stok Awal', 'Kode Rak', 'Tautan Foto'
        ], $subHeaderStyle));

        $sample1 = [
            'UNP100-STD-01',
            'UNP 100 KANAL STANDAR KAROSERI',
            'subframe',
            'BATANG',
            '6000',
            '100',
            '5',
            'Baja SS400 profil kanal UNP bodi karoseri dump',
            '10',
            '50',
            'A-01',
            'https://drive.google.com/file/d/sample1/view',
        ];
        $writer->addRow(Row::fromValues($sample1, $sampleStyle1));

        $sample2 = [
            'CLOP10260190110',
            'LODGE ENGSEL ADR7 BESAR',
            'accessories',
            'PCS',
            '25.1',
            '184.8',
            '10.1',
            'Material engsel pintu bak karoseri tebal plat 10mm',
            '5',
            '100',
            'B-02',
            '',
        ];
        $writer->addRow(Row::fromValues($sample2, $sampleStyle2));

        $sample3 = [
            'BRKT-STOP-99',
            'BRACKET STOP LAMP KAROSERI TIPE R',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];
        $writer->addRow(Row::fromValues($sample3, $sampleStyle1));

        $writer->addRow(Row::fromValues(['']));

        // TABEL 2: KATEGORI KOMPONEN
        $writer->addRow(Row::fromValues(['2. DAFTAR KATEGORI VALID SISTEM'], $sectionHeaderStyle));
        $writer->addRow(Row::fromValues(['Nilai Kategori (Input Excel)', 'Nama Kategori Karoseri', 'Keterangan'], $subHeaderStyle));
        
        $categories = [
            ['subframe', 'Subframe Karoseri', 'Rangka bawah, penguat sasis, dan dudukan dump'],
            ['assy', 'Assembly (Perakitan)', 'Komponen bodi sub-assy dan kit rakitan'],
            ['accessories', 'Aksesoris & Bodi', 'Engsel, kunci grendel, penahan pintu, lampu'],
            ['pto', 'PTO & Hidrolik', 'Power Take-Off, pompa hidrolik, valve, hoist dump'],
            ['potong_chassis', 'Potong Chassis', 'Komponen modifikasi sasis dan sambungan bodi'],
            ['spo', 'SPO', 'Standard Production Order khusus'],
            ['(Dikosongkan)', 'Uncategorized', 'Boleh dikosongkan jika belum dikelompokkan'],
        ];
        foreach ($categories as $cat) {
            $writer->addRow(Row::fromValues($cat, $sampleStyle2));
        }

        $writer->addRow(Row::fromValues(['']));

        // TABEL 3: SATUAN UKUR (UoM)
        $writer->addRow(Row::fromValues(['3. DAFTAR SATUAN UKUR (UoM) RESMI'], $sectionHeaderStyle));
        $writer->addRow(Row::fromValues(['Nilai Satuan (Input Excel)', 'Nama Satuan', 'Keterangan'], $subHeaderStyle));

        $uoms = [
            ['PCS', 'Pieces / Satuan Buah', 'Nilai default jika kolom satuan dikosongkan'],
            ['SET', 'Set / Pasang', 'Part berpasangan (misal engsel kanan-kiri)'],
            ['BATANG', 'Batang (6 Meter)', 'Besi kanal, pipa, UNP standar 6 meter'],
            ['LEMBAR', 'Lembar Pelat', 'Pelat baja SS400, SPHC, plat bordes'],
            ['KG', 'Kilogram', 'Material curah, kawat las, baut kiloan'],
            ['LITER', 'Liter', 'Oli hidrolik, cat primer, thinner'],
            ['BOX', 'Box / Dus', 'Fastener box, fitting per box'],
            ['METER', 'Meter Lari', 'Karet lis pintu, selang hidrolik per meter'],
        ];
        foreach ($uoms as $uom) {
            $writer->addRow(Row::fromValues($uom, $sampleStyle2));
        }

        $writer->addRow(Row::fromValues(['']));

        // TABEL 4: KODE RAK AKTIF GUDANG
        $writer->addRow(Row::fromValues(['4. DAFTAR KODE RAK FISIK AKTIF GUDANG HPK (ZONA A - F)'], $sectionHeaderStyle));
        $writer->addRow(Row::fromValues(['Kode Rak', 'Zona Gudang', 'Nomor Rak', 'Keterangan'], $subHeaderStyle));

        $locations = Location::where('rack_code', '!=', 'HOLDING')
            ->orderBy('zone_code')
            ->orderBy('rack_number')
            ->get();

        if ($locations->isEmpty()) {
            $writer->addRow(Row::fromValues(['A-01', 'Zona A', 'Rak 1', 'Rak aktif pabrik HPK'], $sampleStyle2));
            $writer->addRow(Row::fromValues(['B-01', 'Zona B', 'Rak 1', 'Rak aktif pabrik HPK'], $sampleStyle2));
        } else {
            foreach ($locations as $loc) {
                $writer->addRow(Row::fromValues([
                    $loc->rack_code,
                    'Zona ' . $loc->zone_code,
                    'Rak ' . ($loc->rack_number ?: '-'),
                    $loc->notes ?: 'Tersedia untuk alokasi part',
                ], $sampleStyle2));
            }
        }

        $writer->addRow(Row::fromValues(['']));

        // TABEL 5: ATURAN PENGISIAN & KOLOM KOSONG
        $writer->addRow(Row::fromValues(['5. ATURAN PENGISIAN & PENANGANAN KOLOM KOSONG'], $sectionHeaderStyle));
        $writer->addRow(Row::fromValues(['Pertanyaan / Kasus', 'Penjelasan & Penanganan Sistem'], $subHeaderStyle));

        $rules = [
            ['Apakah semua kolom harus diisi?', 'TIDAK. Hanya kolom "Kode Komponen" dan "Nama Komponen" yang wajib. Seluruh kolom lainnya boleh dikosongkan.'],
            ['Jika Kategori dikosongkan?', 'Komponen otomatis berstatus "Uncategorized" dan dapat dikelompokkan belakangan.'],
            ['Jika Satuan / UoM dikosongkan?', 'Sistem otomatis memberikan satuan bawaan "PCS".'],
            ['Jika Saldo Stok Awal dikosongkan?', 'Saldo fisik komponen otomatis dimulai dari 0.'],
            ['Jika Lokasi Rak dikosongkan?', 'Komponen berstatus "Belum Dialokasikan" (Unallocated) dan bisa dipetakan belakangan.'],
            ['Bagaimana cara mengisi foto Google Drive?', 'Pastikan link Google Drive diatur "Anyone with the link can view". Tempelkan link lengkapnya ke kolom L.'],
            ['Bolehkah Foto dikosongkan?', 'SANGAT BOLEH. Komponen tetap aktif dan langsung memiliki QR code otomatis. Foto bisa difoto belakangan via kamera HP/tablet di gudang.'],
            ['Bagaimana jika Kode Part sudah pernah terdaftar?', 'Sistem menyediakan pilihan saat upload: "Lewati (Skip)" data lama, atau "Perbarui (Update)" data lama.'],
        ];
        foreach ($rules as $r) {
            $writer->addRow(Row::fromValues($r, $sampleStyle2));
        }

        $writer->close();

        return $outputPath;
    }
}

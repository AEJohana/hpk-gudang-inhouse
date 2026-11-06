<?php

namespace Tests\Unit;

use App\Models\Component;
use App\Services\ComponentPhotoStorageService;
use Tests\TestCase;

class ComponentPhotoStorageTest extends TestCase
{
    /**
     * Test part number sanitization for safe filesystem naming.
     */
    public function test_part_number_sanitization(): void
    {
        $service = new ComponentPhotoStorageService();

        $clean = $service->sanitizePartNumber('CASP/1011/0040.100#A');
        $this->assertEquals('CASP-1011-0040-100-A', $clean);

        $clean2 = $service->sanitizePartNumber('ASPET 106 133');
        $this->assertEquals('ASPET-106-133', $clean2);
    }

    /**
     * Test storing binary data saves file with standardized name.
     */
    public function test_store_binary_creates_file_with_standard_naming(): void
    {
        $service = new ComponentPhotoStorageService();
        $dummyBinary = str_repeat('A', 200);

        $relative = $service->storeBinary($dummyBinary, 'TEST-PN-001', 'jpg');

        $this->assertEquals('uploads/components/TEST-PN-001.jpg', $relative);

        $mirrorFile = public_path('uploads/components/TEST-PN-001.jpg');
        $this->assertFileExists($mirrorFile);
        $this->assertGreaterThan(50, filesize($mirrorFile));

        // Cleanup
        @unlink($mirrorFile);
    }

    /**
     * Test overwriting clean existing files when new revision is saved.
     */
    public function test_clean_overwrite_when_storing_revision(): void
    {
        $service = new ComponentPhotoStorageService();
        $fileJpg = public_path('uploads/components/TEST-REV-001.jpg');
        $filePng = public_path('uploads/components/TEST-REV-001.png');

        file_put_contents($fileJpg, str_repeat('J', 100));
        $this->assertFileExists($fileJpg);

        // Store new revision as PNG
        $service->storeBinary(str_repeat('P', 200), 'TEST-REV-001', 'png');

        $this->assertFileExists($filePng);
        $this->assertFileDoesNotExist($fileJpg); // Old JPG removed

        // Cleanup
        @unlink($filePng);
    }

    /**
     * Test PDF CAD drawing detection on Component model.
     */
    public function test_is_pdf_drawing_accessor(): void
    {
        $compImage = new Component([
            'part_number' => 'TEST-IMG-001',
            'image_path' => 'uploads/components/TEST-IMG-001.jpg',
        ]);
        $this->assertFalse($compImage->is_pdf_drawing);

        $compPdf = new Component([
            'part_number' => 'TEST-PDF-001',
            'image_path' => 'uploads/components/TEST-PDF-001.pdf',
        ]);
        $this->assertTrue($compPdf->is_pdf_drawing);
    }

    /**
     * Test pure local mode: when gdrive fallback is false, does not fallback to Google Drive.
     */
    public function test_pure_local_mode_does_not_fallback_to_gdrive(): void
    {
        config(['services.components.enable_gdrive_fallback' => false]);

        $comp = new Component([
            'id' => 99999,
            'part_number' => 'NON-EXISTING-PART-XYZ',
            'image_path' => 'https://drive.google.com/open?id=1K6frWYtUBJMQFjMZ6pG1rGjHy-mcioC6',
        ]);

        $url = $comp->image_url;

        // In pure local mode without local file, it must serve the official SVG placeholder, not Google Drive CDN
        $this->assertStringContainsString('placeholder_part.svg', $url);
        $this->assertStringNotContainsString('drive.google.com', $url);
    }

    /**
     * Test Linux mount path normalization (prepends leading slash if missing).
     */
    public function test_external_directory_path_normalization(): void
    {
        $service = new ComponentPhotoStorageService();

        // Path with mnt/ without leading slash
        config(['services.components.photo_dir' => 'mnt/nas-gudang/Gudang Komponen In House']);
        $this->assertEquals('/mnt/nas-gudang/Gudang Komponen In House', $service->getExternalDirectory());

        // Path with proper leading slash
        config(['services.components.photo_dir' => '/mnt/nas-gudang/Gudang Komponen In House']);
        $this->assertEquals('/mnt/nas-gudang/Gudang Komponen In House', $service->getExternalDirectory());

        // Path with surrounding quotes
        config(['services.components.photo_dir' => "'/mnt/nas-gudang/Gudang Komponen In House'"]);
        $this->assertEquals('/mnt/nas-gudang/Gudang Komponen In House', $service->getExternalDirectory());

        // Windows path remains unchanged
        config(['services.components.photo_dir' => 'D:\Ali Magang\KOMPONEN']);
        $this->assertEquals('D:\Ali Magang\KOMPONEN', $service->getExternalDirectory());
    }

    /**
     * Test candidate extensions contains both lowercase and uppercase for Linux case-sensitive NAS.
     */
    public function test_candidate_extensions_include_uppercase(): void
    {
        $service = new ComponentPhotoStorageService();
        $exts = $service->getCandidateExtensions();

        $this->assertContains('jpg', $exts);
        $this->assertContains('JPG', $exts);
        $this->assertContains('png', $exts);
        $this->assertContains('PNG', $exts);
        $this->assertContains('pdf', $exts);
        $this->assertContains('PDF', $exts);
    }

    /**
     * Test resolving photo with uppercase extension and raw name in simulated NAS directory.
     */
    public function test_find_local_photo_resolves_uppercase_and_raw_names_in_nas_dir(): void
    {
        $service = new ComponentPhotoStorageService();
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_nas_komponen_' . uniqid();
        @mkdir($tempDir, 0777, true);

        config(['services.components.photo_dir' => $tempDir]);

        // File 1: Uppercase extension .JPG
        $file1 = $tempDir . DIRECTORY_SEPARATOR . 'KOMP-UPPER-01.JPG';
        file_put_contents($file1, str_repeat('X', 100));

        // File 2: Raw name with space and .PNG
        $file2 = $tempDir . DIRECTORY_SEPARATOR . 'CYLINDER 50.PNG';
        file_put_contents($file2, str_repeat('Y', 100));

        // File 3: CAD PDF with uppercase .PDF
        $file3 = $tempDir . DIRECTORY_SEPARATOR . 'DRAWING-TEST.PDF';
        file_put_contents($file3, str_repeat('Z', 100));

        try {
            $found1 = $service->findLocalPhoto('KOMP-UPPER-01');
            $this->assertEquals(realpath($file1), $found1);

            $found2 = $service->findLocalPhoto('CYLINDER 50');
            $this->assertEquals(realpath($file2), $found2);

            $found3 = $service->findLocalPhoto('DRAWING-TEST');
            $this->assertEquals(realpath($file3), $found3);

            // Access via Component model
            $comp = new Component(['part_number' => 'KOMP-UPPER-01']);
            $this->assertEquals(realpath($file1), $comp->local_photo_file_path);
        } finally {
            @unlink($file1);
            @unlink($file2);
            @unlink($file3);
            @rmdir($tempDir);
        }
    }
}

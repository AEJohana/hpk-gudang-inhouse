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
}

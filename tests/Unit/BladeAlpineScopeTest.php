<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class BladeAlpineScopeTest extends TestCase
{
    /**
     * Test that all Blade views have balanced HTML tags (div, form, table, template).
     */
    public function test_all_blade_views_have_balanced_html_tags(): void
    {
        $viewsPath = realpath(__DIR__ . '/../../resources/views');
        $this->assertNotFalse($viewsPath);

        $dir = new RecursiveDirectoryIterator($viewsPath);
        $ite = new RecursiveIteratorIterator($dir);
        $files = new RegexIterator($ite, '/\.blade\.php$/');

        $tagsToCheck = ['div', 'form', 'table', 'template'];
        $mismatches = [];

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            $relPath = str_replace('\\', '/', substr($file->getPathname(), strlen($viewsPath) + 1));

            foreach ($tagsToCheck as $tag) {
                preg_match_all("/<{$tag}\b[^>]*>/is", $content, $opens);
                preg_match_all("/<\/{$tag}>/is", $content, $closes);
                $openCount = count($opens[0]);
                $closeCount = count($closes[0]);

                if ($openCount !== $closeCount) {
                    $mismatches[] = "{$relPath} [tag: <{$tag}>] opens={$openCount}, closes={$closeCount} (diff=" . ($openCount - $closeCount) . ")";
                }
            }
        }

        $this->assertEmpty($mismatches, "Found unbalanced HTML tags in views:\n" . implode("\n", $mismatches));
    }

    /**
     * Test that Material Handling index view has zero premature x-data closures.
     */
    public function test_work_station_supplies_xdata_encloses_all_modals(): void
    {
        $filePath = realpath(__DIR__ . '/../../resources/views/work_station_supplies/index.blade.php');
        $this->assertNotFalse($filePath);

        $content = file_get_contents($filePath);

        // Find main x-data container
        $token = 'taskGudangHandler()';
        $pos = strpos($content, $token);
        $this->assertNotFalse($pos, 'taskGudangHandler() not found in work_station_supplies/index.blade.php');

        $divPos = strrpos(substr($content, 0, $pos), '<div');
        $sub = substr($content, $divPos);

        preg_match_all('/(<div\b[^>]*>|<\/div>)/is', $sub, $matches, PREG_OFFSET_CAPTURE);

        $depth = 0;
        $prematureZeroLine = null;

        foreach ($matches[0] as $match) {
            $tag = $match[0];
            $offset = $match[1];
            if (stripos($tag, '<div') === 0) {
                $depth++;
            } else {
                $depth--;
            }

            if ($depth <= 0) {
                // Check if this closure happened before the end of the view file (line 1200+)
                $linesBefore = substr_count(substr($sub, 0, $offset), "\n");
                $lineNum = substr_count(substr($content, 0, $divPos), "\n") + $linesBefore + 1;
                if ($lineNum < 1200) {
                    $prematureZeroLine = $lineNum;
                    break;
                }
            }
        }

        $this->assertNull($prematureZeroLine, "taskGudangHandler() container was prematurely closed around line {$prematureZeroLine}");
    }
}

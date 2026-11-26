<?php

namespace Tests\Unit;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ResponsivePaginationTest extends TestCase
{
    private function makePaginator(int $total, int $perPage, int $currentPage): LengthAwarePaginator
    {
        $items = array_fill(0, min($perPage, max(0, $total - (($currentPage - 1) * $perPage))), 'item');

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => '/components']
        );
    }

    public function test_mobile_renders_at_least_four_page_tabs_on_first_page_without_dark_classes(): void
    {
        $paginator = $this->makePaginator(864, 12, 1);

        $topHtml = $paginator->links('vendor.pagination.tailwind', ['showPerPage' => true])->toHtml();
        $bottomHtml = $paginator->links('vendor.pagination.tailwind', ['showPerPage' => false])->toHtml();

        // No dark mode override classes
        $this->assertStringNotContainsString('dark:bg-gray-800', $topHtml);
        $this->assertStringNotContainsString('dark:text-gray-', $topHtml);

        // Single-row alignment locks: summary uses whitespace-nowrap, right controls use sm:flex-nowrap
        $this->assertStringContainsString('whitespace-nowrap shrink-0', $topHtml);
        $this->assertStringContainsString('sm:flex-nowrap', $topHtml);

        // Mobile navigation contains pages 1, 2, 3 and 72 (4 tabs)
        preg_match('/aria-label="Navigasi Halaman Mobile"[^>]*>(.*?)<\/ul>/s', $topHtml, $mobileMatch);
        $this->assertNotEmpty($mobileMatch[1] ?? '');
        $mobileHtml = $mobileMatch[1];

        $this->assertStringContainsString('>1<', str_replace(["\n", "\r", ' '], '', $mobileHtml));
        $this->assertStringContainsString('Halaman 2', $mobileHtml);
        $this->assertStringContainsString('Halaman 3', $mobileHtml);
        $this->assertStringContainsString('Halaman 72', $mobileHtml);
        $this->assertStringContainsString('Halaman Terakhir', $mobileHtml);

        // Top shows per-page dropdown (including non-standard 12 default) with py-0 to prevent @tailwindcss/forms clipping
        $this->assertStringContainsString('data-per-page-select', $topHtml);
        $this->assertStringContainsString('h-8 py-0 pl-2.5 pr-7', $topHtml);
        $this->assertStringContainsString('12 / halaman', $topHtml);
        $this->assertStringContainsString('20 / halaman', $topHtml);
        $this->assertStringNotContainsString('data-per-page-select', $bottomHtml);
    }

    public function test_mobile_sliding_window_keeps_four_tabs_on_middle_and_end_pages(): void
    {
        $middle = $this->makePaginator(864, 12, 15);
        $middleHtml = $middle->links('vendor.pagination.tailwind')->toHtml();

        preg_match('/aria-label="Navigasi Halaman Mobile"[^>]*>(.*?)<\/ul>/s', $middleHtml, $middleMobile);
        $this->assertStringContainsString('Halaman 1', $middleMobile[1]);
        $this->assertStringContainsString('>15<', str_replace(["\n", "\r", ' '], '', $middleMobile[1]));
        $this->assertStringContainsString('Halaman 16', $middleMobile[1]);
        $this->assertStringContainsString('Halaman 72', $middleMobile[1]);

        $end = $this->makePaginator(864, 12, 71);
        $endHtml = $end->links('vendor.pagination.tailwind')->toHtml();

        preg_match('/aria-label="Navigasi Halaman Mobile"[^>]*>(.*?)<\/ul>/s', $endHtml, $endMobile);
        $this->assertStringContainsString('Halaman 1', $endMobile[1]);
        $this->assertStringContainsString('Halaman 70', $endMobile[1]);
        $this->assertStringContainsString('>71<', str_replace(["\n", "\r", ' '], '', $endMobile[1]));
        $this->assertStringContainsString('Halaman 72', $endMobile[1]);
    }

    public function test_pagination_bar_component_keeps_top_per_page_when_single_page_and_hides_bottom(): void
    {
        $singlePage = $this->makePaginator(8, 20, 1);

        $topHtml = Blade::render('<x-pagination-bar :paginator="$paginator" position="top" />', [
            'paginator' => $singlePage,
        ]);
        $bottomHtml = Blade::render('<x-pagination-bar :paginator="$paginator" position="bottom" />', [
            'paginator' => $singlePage,
        ]);

        $this->assertStringContainsString('data-per-page-select', $topHtml);
        $this->assertStringContainsString('20 / halaman', $topHtml);
        $this->assertStringContainsString('Menampilkan', $topHtml);
        $this->assertSame('', trim($bottomHtml));
    }
}

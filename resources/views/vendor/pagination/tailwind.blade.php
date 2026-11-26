@php
    $showPerPage = (bool) ($showPerPage ?? false);
    $isLengthAware = method_exists($paginator, 'total') && method_exists($paginator, 'lastPage');
    $total = $isLengthAware ? (int) $paginator->total() : $paginator->count();
    $current = (int) $paginator->currentPage();
    $lastPage = $isLengthAware ? max(1, (int) $paginator->lastPage()) : max(1, $current + ($paginator->hasMorePages() ? 1 : 0));
    $activePerPage = (int) $paginator->perPage();

    $perPageChoices = collect([10, 15, 20, 50, 100])
        ->when($activePerPage > 0 && ! in_array($activePerPage, [10, 15, 20, 50, 100], true), fn ($c) => $c->push($activePerPage))
        ->sort()
        ->values();

    $shouldRender = $paginator->hasPages() || ($showPerPage && $total > 0);

    // Compact sliding window (< 1280px: HP, tablet, laptop ber-sidebar, scaling 125%)
    // Menjamin minimal 4 tab angka di HP & menjaga lebar 1 baris tetap ramping (~528px) di sm/md/lg
    $buildCompactWindow = static function (int $current, int $last): array {
        if ($last <= 5) {
            return range(1, $last);
        }
        if ($current <= 2) {
            return [1, 2, 3, '...', $last];
        }
        if ($current === 3) {
            return [1, 2, 3, 4, '...', $last];
        }
        if ($current >= $last - 1) {
            return [1, '...', $last - 2, $last - 1, $last];
        }
        if ($current === $last - 2) {
            return [1, '...', $last - 3, $last - 2, $last - 1, $last];
        }
        return [1, '...', $current, $current + 1, '...', $last];
    };

    // Wide desktop sliding window (>= 1280px / xl)
    $buildWideWindow = static function (int $current, int $last): array {
        if ($last <= 7) {
            return range(1, $last);
        }
        if ($current <= 3) {
            return [1, 2, 3, 4, 5, '...', $last];
        }
        if ($current >= $last - 2) {
            return [1, '...', $last - 4, $last - 3, $last - 2, $last - 1, $last];
        }
        return [1, '...', $current - 1, $current, $current + 1, '...', $last];
    };

    $compactItems = $isLengthAware ? $buildCompactWindow($current, $lastPage) : [$current];
    $wideItems = $isLengthAware ? $buildWideWindow($current, $lastPage) : [$current];
    $perPageSelectClass = 'h-8 py-0 pl-2.5 pr-7 text-xs font-semibold leading-tight text-slate-700 bg-white border border-slate-200/90 rounded-lg shadow-2xs hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#0a2342]/15 focus:border-[#0a2342] cursor-pointer transition-colors';

    $navVariants = [
        [
            'items' => $compactItems,
            'ulClass' => 'pagination flex xl:hidden flex-wrap sm:flex-nowrap items-center justify-center gap-0.5 sm:gap-1',
            'aria' => 'Navigasi Halaman Mobile',
            'arrowClass' => 'min-w-[26px] h-7 sm:h-8 px-1.5 text-[11px] sm:text-xs',
            'numClass' => 'min-w-[26px] h-7 sm:h-8 px-1.5 text-[11px] sm:text-xs',
            'dotsClass' => 'min-w-[16px] h-7 sm:h-8 px-0.5',
        ],
        [
            'items' => $wideItems,
            'ulClass' => 'pagination hidden xl:flex flex-nowrap items-center gap-1',
            'aria' => 'Navigasi Halaman Desktop',
            'arrowClass' => 'w-8 h-8 text-xs',
            'numClass' => 'min-w-[30px] h-8 px-2.5 text-xs',
            'dotsClass' => 'min-w-[22px] h-8 px-1',
        ],
    ];
@endphp

@if ($shouldRender)
    <nav role="navigation"
         aria-label="{{ __('Pagination Navigation') }}"
         class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3 text-xs text-slate-600">
        {{-- Left: Data Summary (dikunci 1 baris) + Mobile-inline Per-Page Select (< 640px) --}}
        <div class="flex flex-wrap sm:flex-nowrap items-center justify-between sm:justify-start gap-2 shrink-0">
            <p class="text-[11px] sm:text-xs text-slate-500 leading-none whitespace-nowrap shrink-0">
                @if ($total > 0 && $paginator->firstItem() !== null)
                    Menampilkan
                    <span class="font-bold text-slate-800">{{ number_format($paginator->firstItem(), 0, ',', '.') }}&ndash;{{ number_format($paginator->lastItem(), 0, ',', '.') }}</span>
                    @if ($isLengthAware)
                        dari
                        <span class="font-bold text-slate-800">{{ number_format($total, 0, ',', '.') }}</span>
                        data
                    @endif
                @else
                    Menampilkan <span class="font-bold text-slate-800">0</span> data
                @endif
            </p>

            @if ($showPerPage)
                <div class="sm:hidden shrink-0">
                    <label class="sr-only" for="per-page-mobile-{{ spl_object_id($paginator) }}">Data per halaman</label>
                    <select id="per-page-mobile-{{ spl_object_id($paginator) }}"
                            data-per-page-select
                            onchange="if (this.closest('[data-live-pagination=&quot;true&quot;]')) return; const u = new URL(window.location.href); u.searchParams.set('per_page', this.value); u.searchParams.delete('page'); window.location.href = u.toString();"
                            class="{{ $perPageSelectClass }}">
                        @foreach ($perPageChoices as $choice)
                            <option value="{{ $choice }}" @selected($activePerPage === $choice)>{{ $choice }} / halaman</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        {{-- Right: Per-Page Select (>= 640px, bar atas saja) + Pagination Buttons (1 baris sm:flex-nowrap) --}}
        <div class="flex flex-wrap sm:flex-nowrap items-center justify-center sm:justify-end gap-1.5 sm:gap-2 shrink-0">
            @if ($showPerPage)
                <div class="hidden sm:block shrink-0">
                    <label class="sr-only" for="per-page-desktop-{{ spl_object_id($paginator) }}">Data per halaman</label>
                    <select id="per-page-desktop-{{ spl_object_id($paginator) }}"
                            data-per-page-select
                            onchange="if (this.closest('[data-live-pagination=&quot;true&quot;]')) return; const u = new URL(window.location.href); u.searchParams.set('per_page', this.value); u.searchParams.delete('page'); window.location.href = u.toString();"
                            class="{{ $perPageSelectClass }}">
                        @foreach ($perPageChoices as $choice)
                            <option value="{{ $choice }}" @selected($activePerPage === $choice)>{{ $choice }} / halaman</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @foreach ($navVariants as $variant)
                <ul class="{{ $variant['ulClass'] }}" aria-label="{{ $variant['aria'] }}">
                    {{-- First Page («) --}}
                    @if ($paginator->onFirstPage())
                        <li>
                            <span aria-disabled="true" aria-label="Halaman Pertama"
                                  class="inline-flex items-center justify-center {{ $variant['arrowClass'] }} rounded-lg border border-slate-200/70 bg-white/80 text-slate-300 cursor-not-allowed select-none">
                                &laquo;
                            </span>
                        </li>
                    @else
                        <li>
                            <a href="{{ $paginator->url(1) }}" aria-label="Halaman Pertama"
                               class="inline-flex items-center justify-center {{ $variant['arrowClass'] }} rounded-lg border border-slate-200/90 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-semibold transition-colors">
                                &laquo;
                            </a>
                        </li>
                    @endif

                    {{-- Previous Page (‹) --}}
                    @if ($paginator->onFirstPage())
                        <li>
                            <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                                  class="inline-flex items-center justify-center {{ $variant['arrowClass'] }} rounded-lg border border-slate-200/70 bg-white/80 text-slate-300 cursor-not-allowed select-none">
                                &lsaquo;
                            </span>
                        </li>
                    @else
                        <li>
                            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                               class="inline-flex items-center justify-center {{ $variant['arrowClass'] }} rounded-lg border border-slate-200/90 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-semibold transition-colors">
                                &lsaquo;
                            </a>
                        </li>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach ($variant['items'] as $item)
                        @if ($item === '...')
                            <li>
                                <span aria-disabled="true"
                                      class="inline-flex items-center justify-center {{ $variant['dotsClass'] }} text-slate-400 font-semibold select-none">
                                    &hellip;
                                </span>
                            </li>
                        @elseif ((int) $item === $current)
                            <li>
                                <span aria-current="page"
                                      class="inline-flex items-center justify-center {{ $variant['numClass'] }} rounded-lg border border-[#0a2342] bg-[#0a2342] text-white font-bold shadow-2xs select-none">
                                    {{ $item }}
                                </span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $paginator->url((int) $item) }}"
                                   aria-label="Halaman {{ $item }}"
                                   class="inline-flex items-center justify-center {{ $variant['numClass'] }} rounded-lg border border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold transition-colors">
                                    {{ $item }}
                                </a>
                            </li>
                        @endif
                    @endforeach

                    {{-- Next Page (›) --}}
                    @if ($paginator->hasMorePages())
                        <li>
                            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                               class="inline-flex items-center justify-center {{ $variant['arrowClass'] }} rounded-lg border border-slate-200/90 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-semibold transition-colors">
                                &rsaquo;
                            </a>
                        </li>
                    @else
                        <li>
                            <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                                  class="inline-flex items-center justify-center {{ $variant['arrowClass'] }} rounded-lg border border-slate-200/70 bg-white/80 text-slate-300 cursor-not-allowed select-none">
                                &rsaquo;
                            </span>
                        </li>
                    @endif

                    {{-- Last Page (») --}}
                    @if ($isLengthAware && $paginator->hasMorePages())
                        <li>
                            <a href="{{ $paginator->url($lastPage) }}" aria-label="Halaman Terakhir"
                               class="inline-flex items-center justify-center {{ $variant['arrowClass'] }} rounded-lg border border-slate-200/90 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-semibold transition-colors">
                                &raquo;
                            </a>
                        </li>
                    @else
                        <li>
                            <span aria-disabled="true" aria-label="Halaman Terakhir"
                                  class="inline-flex items-center justify-center {{ $variant['arrowClass'] }} rounded-lg border border-slate-200/70 bg-white/80 text-slate-300 cursor-not-allowed select-none">
                                &raquo;
                            </span>
                        </li>
                    @endif
                </ul>
            @endforeach
        </div>
    </nav>
@endif

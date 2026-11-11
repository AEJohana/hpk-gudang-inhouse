<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2.5 sm:gap-4 w-full">
            <!-- Left: Back Button + Icon Badge + Title (Judul Saja Tanpa Deskripsi) -->
            <div class="flex items-center space-x-2 sm:space-x-3.5 min-w-0">
                <a href="{{ route('dashboard') }}" 
                   class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200/80 flex items-center justify-center text-slate-700 transition shadow-2xs shrink-0 cursor-pointer" 
                   title="Kembali ke Beranda">
                    <i class="fa-solid fa-arrow-left text-sm text-slate-600"></i>
                </a>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-700 shadow-2xs shrink-0">
                    <i class="fa-solid fa-industry text-base sm:text-lg"></i>
                </div>
                <h2 class="font-extrabold text-base sm:text-xl text-[#0a2342] tracking-tight leading-tight truncate">
                    Work Request Mesin
                </h2>
            </div>

            <!-- Right: Action Buttons (Anchored to Far Right) -->
            <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0 ml-auto">
                <button type="button" 
                        onclick="window.location.reload()" 
                        class="px-3 py-2 sm:px-3.5 bg-white hover:bg-slate-50 text-slate-700 font-medium text-xs rounded-xl border border-slate-300 shadow-2xs transition flex items-center justify-center space-x-1.5 cursor-pointer active:scale-95"
                        title="Segarkan antrean tiket WRI">
                    <i class="fa-solid fa-arrows-rotate text-xs text-slate-500"></i>
                    <span class="hidden md:inline">Refresh</span>
                </button>
                <a href="{{ route('work-station-supplies.index') }}" class="px-3 py-2 sm:px-3.5 bg-white hover:bg-slate-50 text-slate-700 font-medium text-xs rounded-xl border border-slate-300 shadow-2xs transition flex items-center justify-center text-center space-x-1.5">
                    <i class="fa-solid fa-truck-ramp-box text-xs text-slate-500"></i>
                    <span class="hidden md:inline">Material Handling</span>
                </a>
                @can('work-request.create')
                <a href="{{ route('work-requests.create') }}" class="px-3.5 py-2 bg-[#0a2342] hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center justify-center text-center space-x-1.5 whitespace-nowrap">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Membuat WR</span>
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
         x-data="wriQueueManager()"
         @if(isset($openDetailPayload) && $openDetailPayload)
             x-init='openDetailModal(@json($openDetailPayload))'
         @endif>

        <!-- 0. INTEGRATED WRI SUB-NAVIGATION TABS (Antrean, Safety Stock, Cetak WR) -->
        @include('work_requests.partials.sub_nav')

        <!-- 1. KPI SUMMARY STATS CARDS (INTERACTIVE QUICK FILTERS) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <a href="{{ route('work-requests.index', ['scope' => $scope]) }}" 
               @click.prevent="switchTab('all', '{{ route('work-requests.index', ['scope' => $scope]) }}')"
               class="bg-white hover:bg-slate-50 rounded-2xl p-4 border shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer"
               :class="activeTab === 'all' ? 'border-[#0a2342] ring-2 ring-[#0a2342]/10 bg-slate-50/80' : 'border-slate-200'"
               title="Klik untuk menampilkan semua tiket">
                <div class="w-11 h-11 rounded-xl bg-slate-100 group-hover:bg-[#0a2342] group-hover:text-amber-400 text-slate-700 flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total WRI</span>
                    <h3 class="text-xl font-black text-slate-900 leading-tight mt-0.5">{{ $totalWri }}</h3>
                    <span class="text-[10px] text-slate-500">Semua tiket order</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'verifikasi']) }}" 
               @click.prevent="switchTab('verifikasi', '{{ route('work-requests.index', ['scope' => $scope, 'status' => 'verifikasi']) }}')"
               class="bg-white hover:bg-amber-50/50 rounded-2xl p-4 border shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer"
               :class="activeTab === 'verifikasi' ? 'border-amber-400 ring-2 ring-amber-400/20 bg-amber-50/30' : '{{ $needsApprovalCount > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200' }}'"
               title="Klik untuk menyaring tiket yang butuh verifikasi">
                <div class="w-11 h-11 rounded-xl {{ $needsApprovalCount > 0 ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-stamp"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Perlu Verifikasi</span>
                    <h3 class="text-xl font-black text-amber-950 leading-tight mt-0.5">{{ $needsApprovalCount }}</h3>
                    <span class="text-[10px] text-amber-700 font-medium">Pengaju & Penerima</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'proses']) }}" 
               @click.prevent="switchTab('proses', '{{ route('work-requests.index', ['scope' => $scope, 'status' => 'proses']) }}')"
               class="bg-white hover:bg-blue-50/50 rounded-2xl p-4 border shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer"
               :class="activeTab === 'proses' ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/30' : 'border-blue-200'"
               title="Klik untuk menyaring tiket yang sedang dalam proses / antrian">
                <div class="w-11 h-11 rounded-xl bg-blue-50 group-hover:bg-blue-600 group-hover:text-white text-blue-700 flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-gears animate-spin" style="animation-duration: 6s;"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600">Sedang Proses</span>
                    <h3 class="text-xl font-black text-blue-700 leading-tight mt-0.5">{{ $inProcessCount }}</h3>
                    <span class="text-[10px] text-blue-500">Antrian, Mesin, MH</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'siap']) }}" 
               @click.prevent="switchTab('siap', '{{ route('work-requests.index', ['scope' => $scope, 'status' => 'siap']) }}')"
               class="bg-white hover:bg-teal-50/50 rounded-2xl p-4 border shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer"
               :class="activeTab === 'siap' ? 'border-teal-500 ring-2 ring-teal-500/20 bg-teal-50/40' : 'border-teal-200 bg-teal-50/20'"
               title="Klik untuk menyaring tiket siap diambil / menuju pengaju">
                <div class="w-11 h-11 rounded-xl bg-teal-500 group-hover:bg-teal-600 text-white flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-dolly"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-teal-800">Siap Diambil</span>
                    <h3 class="text-xl font-black text-teal-900 leading-tight mt-0.5">{{ $readyForWarehouseCount }}</h3>
                    <span class="text-[10px] text-teal-700 font-semibold">Menuju Pengaju</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'selesai']) }}" 
               @click.prevent="switchTab('selesai', '{{ route('work-requests.index', ['scope' => $scope, 'status' => 'selesai']) }}')"
               class="bg-white hover:bg-emerald-50/50 rounded-2xl p-4 border shadow-xs flex items-center space-x-3 col-span-2 sm:col-span-1 transition group active:scale-[0.99] cursor-pointer"
               :class="activeTab === 'selesai' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/30' : 'border-emerald-200'"
               title="Klik untuk menyaring tiket yang sudah selesai">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 group-hover:bg-emerald-600 group-hover:text-white text-emerald-700 flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Selesai</span>
                    <h3 class="text-xl font-black text-emerald-700 leading-tight mt-0.5">{{ $receivedCount }}</h3>
                    <span class="text-[10px] text-emerald-600">Diterima Pengaju</span>
                </div>
            </a>
        </div>

        <!-- 2. UNIFIED WORK REQUEST CONTROL BAR (BEBAS DARI INPUT DOBEL) -->
        <div class="bg-white p-2.5 sm:p-3 rounded-2xl border border-slate-200 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <!-- Left: 5 Status Tabs (Swipeable with Gradient Cue & Mini Chevrons) -->
            <div class="relative w-full lg:w-auto min-w-0 shrink-0" 
                 x-data="{
                     canScrollLeft: false,
                     canScrollRight: false,
                     checkScroll() {
                         const el = this.$refs.scrollContainer;
                         if (!el) return;
                         this.canScrollLeft = el.scrollLeft > 6;
                         this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 6);
                     },
                     scrollLeft() {
                         this.$refs.scrollContainer.scrollBy({ left: -160, behavior: 'smooth' });
                     },
                     scrollRight() {
                         this.$refs.scrollContainer.scrollBy({ left: 160, behavior: 'smooth' });
                     }
                 }"
                 x-init="$nextTick(() => checkScroll()); window.addEventListener('resize', () => checkScroll())">

                <!-- Left Scroll Gradient & Chevron Button -->
                <div x-show="canScrollLeft" 
                     x-transition.opacity.duration.200ms
                     class="absolute left-0 top-0 bottom-0 z-10 flex items-center pr-4 pl-0.5 bg-gradient-to-r from-white via-white/90 to-transparent pointer-events-none"
                     style="display: none;">
                    <button type="button" 
                            @click="scrollLeft()" 
                            class="w-6 h-6 rounded-full bg-white border border-slate-300 shadow-xs text-slate-700 hover:text-slate-950 hover:scale-105 active:scale-95 flex items-center justify-center pointer-events-auto transition cursor-pointer text-[10px]"
                            title="Geser ke kiri">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                </div>

                <!-- Scrollable Tabs Container -->
                <div x-ref="scrollContainer" 
                     @scroll.passive="checkScroll()"
                     class="flex items-center gap-1.5 overflow-x-auto no-scrollbar flex-nowrap py-0.5 scroll-smooth pe-4 lg:pe-0">
                    <!-- Tab: Semua -->
                    <a href="{{ route('work-requests.index', ['scope' => $scope]) }}" 
                       @click.prevent="switchTab('all', '{{ route('work-requests.index', ['scope' => $scope]) }}')"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                       :class="activeTab === 'all' ? 'bg-[#0a2342] text-amber-400 shadow-xs' : 'text-slate-600 hover:bg-slate-100'">
                        <span>Semua</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                              :class="activeTab === 'all' ? 'bg-amber-400 text-slate-950' : 'bg-slate-200 text-slate-700'">
                            {{ $totalWri }}
                        </span>
                    </a>

                    <!-- Tab: Perlu Verifikasi -->
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'verifikasi', 'overdue_sla' => null])) }}" 
                       @click.prevent="switchTab('verifikasi', '{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'verifikasi', 'overdue_sla' => null])) }}')"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                       :class="activeTab === 'verifikasi' ? 'bg-amber-400 text-slate-950 font-black shadow-xs' : 'text-slate-600 hover:bg-slate-100'">
                        <i class="fa-solid fa-stamp text-[11px]"></i>
                        <span>Perlu Verifikasi</span>
                        @if($needsApprovalCount > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                                  :class="activeTab === 'verifikasi' ? 'bg-slate-950 text-white' : 'bg-rose-600 text-white animate-pulse'">
                                {{ $needsApprovalCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Tab: Sedang Proses -->
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'proses', 'overdue_sla' => null])) }}" 
                       @click.prevent="switchTab('proses', '{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'proses', 'overdue_sla' => null])) }}')"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                       :class="activeTab === 'proses' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'">
                        <i class="fa-solid fa-gears text-[11px]"></i>
                        <span>Sedang Proses</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                              :class="activeTab === 'proses' ? 'bg-blue-800 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $inProcessCount }}
                        </span>
                    </a>

                    <!-- Tab: Siap Ambil -->
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'siap', 'overdue_sla' => null])) }}" 
                       @click.prevent="switchTab('siap', '{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'siap', 'overdue_sla' => null])) }}')"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                       :class="activeTab === 'siap' ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'">
                        <i class="fa-solid fa-dolly text-[11px]"></i>
                        <span>Siap Ambil</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                              :class="activeTab === 'siap' ? 'bg-teal-800 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $readyForWarehouseCount }}
                        </span>
                    </a>

                    <!-- Tab: Selesai -->
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'selesai', 'overdue_sla' => null])) }}" 
                       @click.prevent="switchTab('selesai', '{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'selesai', 'overdue_sla' => null])) }}')"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                       :class="activeTab === 'selesai' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'">
                        <i class="fa-solid fa-circle-check text-[11px]"></i>
                        <span>Selesai</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                              :class="activeTab === 'selesai' ? 'bg-emerald-800 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $receivedCount }}
                        </span>
                    </a>
                </div>

                <!-- Right Scroll Gradient & Chevron Button (Tanda Geser Halus) -->
                <div x-show="canScrollRight" 
                     x-transition.opacity.duration.200ms
                     class="absolute right-0 top-0 bottom-0 z-10 flex items-center pl-4 pr-0.5 bg-gradient-to-l from-white via-white/90 to-transparent pointer-events-none"
                     style="display: none;">
                    <button type="button" 
                            @click="scrollRight()" 
                            class="w-6 h-6 rounded-full bg-white border border-slate-300 shadow-xs text-slate-700 hover:text-slate-950 hover:scale-105 active:scale-95 flex items-center justify-center pointer-events-auto transition cursor-pointer text-[10px] animate-pulse"
                            title="Geser ke kanan untuk melihat tab lainnya">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Right: Search Input Form -->
            <div class="w-full sm:w-72 lg:w-80">
                <form method="GET" action="{{ route('work-requests.index') }}" class="relative w-full">
                    @if(request()->filled('status'))
                        <input type="hidden" name="status" value="{{ request()->status }}">
                    @endif
                    @if(request()->boolean('overdue_sla'))
                        <input type="hidden" name="overdue_sla" value="1">
                    @endif
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request()->search }}" 
                           placeholder="Cari No. WRI, komponen, SPK, departemen..." 
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none transition shadow-2xs">
                </form>
            </div>
        </div>

        <!-- 2.5. PERINGATAN TERLAMBAT (> 3 HARI) BANNER (LAMPIRAN 1 STYLE) -->
        @if($overdueSlaCount > 0 || request()->boolean('overdue_sla'))
        <div class="bg-red-50/80 border border-red-200/90 rounded-2xl p-3.5 sm:px-5 sm:py-3.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0 text-xl shadow-2xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <strong class="text-red-700 font-extrabold text-sm block">Peringatan!</strong>
                    <span class="text-slate-700 text-xs sm:text-sm">
                        Terdapat <b class="text-red-950 font-black">{{ $overdueSlaCount }}</b> Work Request yang tertunda lebih dari 3 hari.
                    </span>
                </div>
            </div>
            <div>
                @if(request()->boolean('overdue_sla'))
                    <a href="{{ route('work-requests.index', array_merge(request()->except('overdue_sla', 'page'))) }}" 
                       @click.prevent="switchTab('all', '{{ route('work-requests.index', array_merge(request()->except('overdue_sla', 'page'))) }}')"
                       class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-full shadow-sm transition inline-flex items-center space-x-1.5 whitespace-nowrap active:scale-95 cursor-pointer"
                       title="Tampilkan seluruh tiket">
                        <i class="fa-solid fa-xmark text-xs"></i>
                        <span>Tampilkan Semua</span>
                    </a>
                @else
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['overdue_sla' => 1])) }}" 
                       @click.prevent="switchTab('overdue', '{{ route('work-requests.index', array_merge(request()->query(), ['overdue_sla' => 1])) }}')"
                       class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-full shadow-sm transition inline-flex items-center space-x-1.5 whitespace-nowrap active:scale-95 cursor-pointer"
                       title="Saring tiket yang tertunda lebih dari 3 hari">
                        <i class="fa-solid fa-filter text-xs"></i>
                        <span>Lihat yang Terlambat</span>
                    </a>
                @endif
            </div>
        </div>
        @endif

        <!-- 3. WORK REQUESTS UNIFIED RESPONSIVE TABLE (DESKTOP & HP) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden relative min-h-[320px]">
            <!-- Elegant Non-Blocking Skeleton Loader -->
            <div x-show="tabLoading" 
                 x-transition.opacity.duration.150ms 
                 class="absolute inset-0 bg-white/80 backdrop-blur-2xs z-20 flex flex-col items-center justify-center space-y-3 p-8"
                 style="display: none;">
                <div class="w-9 h-9 rounded-full border-3 border-amber-400 border-t-transparent animate-spin"></div>
                <span class="text-xs font-bold text-slate-700 tracking-wide animate-pulse">Memuat data tiket...</span>
            </div>

            <div id="wri-table-container">
                @include('work_requests.partials._table_content')
            </div>
        </div>

        <!-- Include Pop-up Modals -->
        @include('work_requests.partials.assign_operator_modal')
        @include('work_requests.partials.process_modal')
        @include('work_requests.partials.detail_modal')
        @include('work_requests.partials.image_preview_modal')
        @include('work_requests.partials.reject_modal')
        @include('work_requests.partials.qr_zoom_modal')

    </div>

    <script>
        function wriQueueManager() {
            return {
                activeTab: '{{ request()->boolean('overdue_sla') ? 'overdue' : (request('status') ?: 'all') }}',
                tabLoading: false,
                tabCache: {},
                departmentOperators: @json($departmentOperatorsMap ?? []),
                currentUserName: @json(auth()->user()->name),
                init() {
                    const container = document.getElementById('wri-table-container');
                    if (container) {
                        this.tabCache[this.activeTab] = container.innerHTML;
                    }

                    // Intercept pagination clicks within the table container for seamless navigation
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('#wri-table-container .pagination a, #wri-table-container a[href*="page="]');
                        if (link && link.href) {
                            e.preventDefault();
                            this.fetchPartial(link.href, null, false);
                        }
                    });

                    // Handle browser back/forward buttons
                    window.addEventListener('popstate', (e) => {
                        if (e.state && e.state.tab) {
                            this.switchTab(e.state.tab, window.location.href, false);
                        }
                    });
                },
                switchTab(tabKey, targetUrl, pushState = true) {
                    if (this.activeTab === tabKey && this.tabCache[tabKey]) {
                        return;
                    }
                    this.activeTab = tabKey;
                    if (pushState) {
                        window.history.pushState({ tab: tabKey }, '', targetUrl);
                    }

                    // Check in-memory cache first (0ms instant response)
                    if (this.tabCache[tabKey]) {
                        const container = document.getElementById('wri-table-container');
                        if (container) {
                            container.innerHTML = this.tabCache[tabKey];
                            if (window.Alpine) {
                                window.Alpine.initTree(container);
                            }
                        }
                        return;
                    }

                    // Otherwise fetch partial via AJAX
                    this.fetchPartial(targetUrl, tabKey, true);
                },
                fetchPartial(url, cacheKey = null, saveToCache = true) {
                    this.tabLoading = true;
                    const fetchUrl = url + (url.includes('?') ? '&' : '?') + '_partial=1';
                    
                    fetch(fetchUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Partial': '1'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        const container = document.getElementById('wri-table-container');
                        if (container) {
                            container.innerHTML = html;
                            if (window.Alpine) {
                                window.Alpine.initTree(container);
                            }
                        }
                        if (saveToCache && cacheKey) {
                            this.tabCache[cacheKey] = html;
                        }
                        this.tabLoading = false;
                    })
                    .catch(err => {
                        console.error('Gagal memuat partial tab:', err);
                        this.tabLoading = false;
                        window.location.href = url;
                    });
                },
                processModalOpen: false,
                detailModalOpen: false,
                assignModalOpen: false,
                imageModalOpen: false,
                rejectModalOpen: false,
                qrZoomModal: false,
                rejectData: {
                    actionUrl: '',
                    wriNumber: '',
                    reason: '',
                },
                qrZoomData: {
                    qrUrl: '',
                    wriNumber: '',
                    componentName: '',
                },
                imageData: {
                    imageUrl: '',
                    componentName: '',
                    partNumber: '',
                    dimension: '',
                    isPdf: false,
                    zoomLevel: 1.0,
                },
                assignData: {
                    id: null,
                    wriNumber: '',
                    componentName: '',
                    partNumber: '',
                    deptPengaju: '',
                    deptPenerima: '',
                    totalQty: 0,
                    uom: 'PCS',
                    operatorName: '',
                    availableOperators: [],
                    actionUrl: '',
                },
                processData: {
                    id: null,
                    wriNumber: '',
                    componentName: '',
                    partNumber: '',
                    uom: 'PCS',
                    currentStatus: '',
                    nextStatus: '',
                    nextActionLabel: '',
                    totalQty: 0,
                    remainingQty: 0,
                    processQty: 0,
                    currentBatchQty: 0,
                    targetRack: '',
                    assignedOperator: '',
                    hasDefect: false,
                    defectQty: null,
                    defectReason: '',
                    notes: '',
                    actionUrl: '',
                },
                detailData: {
                    id: null,
                    wriNumber: '',
                    componentName: '',
                    partNumber: '',
                    dimension: '',
                    uom: 'PCS',
                    imageUrl: '',
                    qrUrl: '',
                    deptPengaju: '',
                    deptPenerima: '',
                    priority: '',
                    priorityClass: '',
                    currentStatus: '',
                    statusClass: '',
                    targetRack: '',
                    totalQty: 0,
                    receivedQty: 0,
                    defectQty: 0,
                    progressPercentage: 0,
                    routingText: '',
                    requestedByName: '',
                    approvalPengajuStatus: '',
                    approvalPengajuBy: '',
                    approvalPengajuAt: '',
                    approvalPenerimaStatus: '',
                    approvalPenerimaBy: '',
                    approvalPenerimaAt: '',
                    assignedOperator: '',
                    steps: [],
                    logs: [],
                    printUrl: '#',
                    isPrintable: false,
                    printValidationMessage: '',
                    printCount: 0,
                    isOverdue: false,
                },
                openAssignModal(data) {
                    const cleanDept = (data.deptPenerima || '').toLowerCase().replace(/[^a-z0-9]/g, '');
                    let operators = [];
                    for (const [key, names] of Object.entries(this.departmentOperators || {})) {
                        if (cleanDept.includes(key) || key.includes(cleanDept)) {
                            names.forEach(n => {
                                if (!operators.includes(n)) operators.push(n);
                            });
                        }
                    }

                    const hasOperators = operators.length > 0;
                    const defaultOperator = data.assignedOperator || (hasOperators ? operators[0] : this.currentUserName);

                    this.assignData = {
                        ...data,
                        operatorName: defaultOperator,
                        availableOperators: operators,
                    };
                    this.assignModalOpen = true;
                },
                closeAssignModal() {
                    this.assignModalOpen = false;
                },
                openProcessModal(data) {
                    const isHandover = data.nextStatus && (data.nextStatus.includes('MH Pickup') || data.nextStatus.includes('Menuju Pengaju') || data.nextStatus.includes('Sudah Diambil'));
                    const initialQty = (isHandover && data.currentBatchQty > 0) ? Number(data.currentBatchQty) : Number(data.remainingQty || 1);
                    this.processData = {
                        ...data,
                        availableQty: initialQty,
                        processQty: initialQty,
                        hasDefect: false,
                        defectQty: null,
                        defectReason: '',
                        notes: '',
                    };
                    this.processModalOpen = true;
                },
                getMaxProcessQty() {
                    const avail = Number(this.processData.availableQty || (this.processData.currentBatchQty > 0 ? this.processData.currentBatchQty : this.processData.remainingQty) || 0);
                    const defect = (this.processData.hasDefect && this.processData.defectQty > 0) ? Number(this.processData.defectQty) : 0;
                    return Math.max(0, avail - defect);
                },
                syncProcessQtyWithDefect() {
                    const maxGood = this.getMaxProcessQty();
                    if (Number(this.processData.processQty) > maxGood || this.processData.processQty === null || this.processData.processQty === undefined) {
                        this.processData.processQty = maxGood;
                    }
                },
                incrementProcessQty(step = 1) {
                    const max = this.getMaxProcessQty();
                    let cur = Number(this.processData.processQty || 0);
                    if (cur + step <= max) {
                        this.processData.processQty = cur + step;
                    } else {
                        this.processData.processQty = max;
                    }
                },
                decrementProcessQty(step = 1) {
                    let cur = Number(this.processData.processQty || 0);
                    if (cur - step >= 0) {
                        this.processData.processQty = cur - step;
                    } else {
                        this.processData.processQty = 0;
                    }
                },
                setMaxProcessQty() {
                    this.processData.processQty = this.getMaxProcessQty();
                },
                toggleDefect() {
                    this.processData.hasDefect = !this.processData.hasDefect;
                    if (!this.processData.hasDefect) {
                        this.processData.defectQty = null;
                        this.processData.defectReason = '';
                    }
                    this.syncProcessQtyWithDefect();
                },
                closeProcessModal() {
                    this.processModalOpen = false;
                },
                openDetailModal(data) {
                    this.detailData = { ...data };
                    this.detailModalOpen = true;
                },
                closeDetailModal() {
                    this.detailModalOpen = false;
                },
                openProcessFromDetail() {
                    const payload = this.detailData.processPayload || {
                        id: this.detailData.id,
                        wriNumber: this.detailData.wriNumber,
                        componentName: this.detailData.componentName,
                        partNumber: this.detailData.partNumber,
                        uom: this.detailData.uom,
                        currentStatus: this.detailData.currentStatus,
                        nextStatus: this.detailData.nextStatus,
                        nextActionLabel: this.detailData.nextActionLabel || 'Lanjutkan Pengerjaan',
                        totalQty: this.detailData.totalQty,
                        remainingQty: this.detailData.remainingQty,
                        currentBatchQty: this.detailData.currentBatchQty || 0,
                        targetRack: this.detailData.targetRack,
                        assignedOperator: (this.detailData.assignedOperator && this.detailData.assignedOperator !== 'Belum ditentukan') ? this.detailData.assignedOperator : '',
                        actionUrl: this.detailData.actionUrl,
                    };
                    this.closeDetailModal();
                    this.openProcessModal(payload);
                },
                openImageModal(data) {
                    this.imageData = { 
                        ...data, 
                        isPdf: Boolean(data.isPdf || (data.imageUrl && data.imageUrl.includes('.pdf'))),
                        zoomLevel: 1.0 
                    };
                    this.imageModalOpen = true;
                },
                closeImageModal() {
                    this.imageModalOpen = false;
                },
                openRejectModal(actionUrl, wriNumber) {
                    this.rejectData = {
                        actionUrl: actionUrl,
                        wriNumber: wriNumber,
                        reason: '',
                    };
                    this.rejectModalOpen = true;
                },
                closeRejectModal() {
                    this.rejectModalOpen = false;
                },
                openQrZoom(qrUrl, wriNumber, componentName) {
                    this.qrZoomData = {
                        qrUrl: qrUrl,
                        wriNumber: wriNumber,
                        componentName: componentName || '',
                    };
                    this.qrZoomModal = true;
                },
                closeQrZoom() {
                    this.qrZoomModal = false;
                },
                handlePengajuActionDirect(approveUrl) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = approveUrl;
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);
                    document.body.appendChild(form);
                    form.submit();
                },
                handlePengajuAction(event, approveUrl, rejectUrl, wriNumber) {
                    const select = event.target;
                    const val = select.value;
                    if (val === 'terima') {
                        select.disabled = true;
                        select.classList.add('opacity-50', 'cursor-wait');
                        const parent = select.parentElement;
                        if (parent) {
                            parent.insertAdjacentHTML('beforeend', '<span class="inline-flex items-center text-[10px] font-bold text-amber-700 ms-1 animate-pulse"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...</span>');
                        }
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = approveUrl;
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);
                        document.body.appendChild(form);
                        form.submit();
                    } else if (val === 'tolak') {
                        select.value = '';
                        this.openRejectModal(rejectUrl, wriNumber);
                    }
                },
                handlePenerimaAction(event, assignPayload, rejectUrl, wriNumber) {
                    const select = event.target;
                    const val = select.value;
                    select.value = '';
                    if (val === 'terima') {
                        this.openAssignModal(assignPayload);
                    } else if (val === 'tolak') {
                        this.openRejectModal(rejectUrl, wriNumber);
                    }
                },
                getStepLogs(stepName, stepIndex) {
                    if (!this.detailData || !this.detailData.logs) return [];
                    const cleanName = (stepName || '').replace(/^Dept\s+/i, '').trim().toLowerCase();
                    const sLower = (stepName || '').toLowerCase();
                    const isLastStep = Boolean(this.detailData.steps && stepIndex === this.detailData.steps.length - 1);
                    return this.detailData.logs.filter(l => {
                        // Abaikan log approval / reject agar stasiun mesin dimulai bersih dari event MASUK
                        if (l.action_type === 'approval' || l.action_type === 'reject') return false;
                        const status = (l.status || '').toLowerCase();
                        const notes = (l.notes || '').toLowerCase();
                        if (status.includes('otorisasi') || notes.includes('otorisasi disetujui')) return false;

                        if (status.includes(sLower) || status.includes(cleanName) || notes.includes(sLower) || notes.includes(cleanName)) {
                            return true;
                        }
                        if (isLastStep && status.includes('siap diambil') && !status.includes('pickup')) {
                            return true;
                        }
                        return false;
                    });
                },
                getGudangLogs() {
                    if (!this.detailData || !this.detailData.logs) return [];
                    return this.detailData.logs.filter(l => {
                        const status = (l.status || '').toLowerCase();
                        const notes = (l.notes || '').toLowerCase();
                        return status.includes('sudah diambil') || status.includes('menuju pengaju') || notes.includes('sudah diambil') || notes.includes('menuju pengaju');
                    }).map(l => {
                        const status = (l.status || '').toLowerCase();
                        const notes = (l.notes || '').toLowerCase();
                        const isMh = status.includes('menuju pengaju') || notes.includes('menuju pengaju') || status.includes('pickup') || notes.includes('pickup');
                        return {
                            ...l,
                            is_mh: isMh,
                            is_gudang: !isMh
                        };
                    });
                }
            };
        }
    </script>
</x-app-layout>

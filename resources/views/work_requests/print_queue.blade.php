<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start space-x-3">
                <a href="{{ route('work-requests.index') }}" 
                   class="mt-1 px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer shrink-0" 
                   title="Kembali ke Antrean WRI Utama">
                    <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                    <span>Kembali</span>
                </a>
                <div>
                    <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight">
                        Pusat Antrean Cetak Dokumen WR
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Daftar Work Request yang telah disahkan (Pengaju + Penerima + Operator) dan siap dicetak ke format resmi 3 rangkap HVS A4
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-2 self-start sm:self-auto">
                <button type="button" 
                        onclick="window.location.reload()" 
                        class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-800 font-bold text-xs rounded-xl border border-slate-300 shadow-xs transition flex items-center justify-center space-x-1.5 cursor-pointer active:scale-95">
                    <i class="fa-solid fa-arrows-rotate text-xs text-indigo-600"></i>
                    <span>Segarkan</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="printQueueManager()">

        <!-- 0. INTEGRATED WRI SUB-NAVIGATION TABS (Antrian, Safety Stock, Cetak Dokumen WR) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-3">
            <div class="flex items-center space-x-2 overflow-x-auto pb-1 sm:pb-0">
                <a href="{{ route('work-requests.index') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100 whitespace-nowrap">
                    <i class="fa-solid fa-list-check text-xs"></i>
                    <span>Antrian & Tiket WRI</span>
                </a>
                @can('work-request.safety-stock')
                <a href="{{ route('work-requests.safety-stock') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100 whitespace-nowrap">
                    <i class="fa-solid fa-shield-halved text-xs text-rose-500"></i>
                    <span>Safety Stock Alert</span>
                </a>
                @endcan
                <a href="{{ route('work-requests.print-queue') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 bg-[#0a2342] text-amber-400 shadow-md whitespace-nowrap">
                    <i class="fa-solid fa-print text-xs"></i>
                    <span>Cetak Dokumen WR</span>
                    @if($totalPrintable > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 ml-1">
                            {{ $totalPrintable }}
                        </span>
                    @endif
                </a>
            </div>

            <div class="flex items-center space-x-2 text-xs">
                <span class="text-slate-400 font-semibold">Departemen:</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-800 font-extrabold flex items-center space-x-1">
                    <i class="fa-solid fa-building-user text-indigo-600"></i>
                    <span>{{ Auth::user()->effective_department_name }}</span>
                    @if(Auth::user()->canAccessAllDepartments())
                        <span class="text-[10px] text-teal-600 font-bold ml-1">(Akses Global *)</span>
                    @endif
                </span>
            </div>
        </div>

        <!-- 1. KPI SUMMARY STATS CARDS (DENGAN PRESERVE FILTER WAKTU & SCOPE) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <a href="{{ route('work-requests.print-queue', array_merge(request()->query(), ['print_status' => 'all'])) }}" 
               class="bg-white hover:bg-slate-50 rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer">
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Siap Cetak</span>
                    <h3 class="text-xl font-black text-slate-900 leading-tight mt-0.5">{{ $totalPrintable }}</h3>
                    <span class="text-[10px] text-slate-500">Memenuhi 3 syarat otorisasi</span>
                </div>
            </a>

            <a href="{{ route('work-requests.print-queue', array_merge(request()->query(), ['print_status' => 'belum'])) }}" 
               class="bg-white hover:bg-amber-50/50 rounded-2xl p-4 border {{ $totalUnprinted > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200' }} shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer">
                <div class="w-11 h-11 rounded-xl {{ $totalUnprinted > 0 ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center flex-shrink-0 text-lg font-bold">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Belum Dicetak</span>
                    <h3 class="text-xl font-black text-amber-950 leading-tight mt-0.5">{{ $totalUnprinted }}</h3>
                    <span class="text-[10px] text-amber-700 font-medium">Perlu dicetak ke bengkel</span>
                </div>
            </a>

            <a href="{{ route('work-requests.print-queue', array_merge(request()->query(), ['print_status' => 'sudah'])) }}" 
               class="bg-white hover:bg-emerald-50/50 rounded-2xl p-4 border border-emerald-200 shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                    <i class="fa-solid fa-print"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Sudah Pernah Dicetak</span>
                    <h3 class="text-xl font-black text-emerald-700 leading-tight mt-0.5">{{ $totalPrinted }}</h3>
                    <span class="text-[10px] text-emerald-600">Tersedia opsi cetak ulang (re-print)</span>
                </div>
            </a>
        </div>

        <!-- 2. CONTROL BAR (STATUS FILTER, BULK PRINT ACTION, DATE FILTER & SEARCH) -->
        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <!-- Filter Status Cetak Tabs -->
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 lg:pb-0">
                <a href="{{ route('work-requests.print-queue', array_merge(request()->query(), ['print_status' => 'all'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ !request()->filled('print_status') || request()->print_status === 'all' ? 'bg-[#0a2342] text-amber-400 shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    Semua ({{ $totalPrintable }})
                </a>
                <a href="{{ route('work-requests.print-queue', array_merge(request()->query(), ['print_status' => 'belum'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->print_status === 'belum' ? 'bg-amber-400 text-slate-950 font-black shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-clock-rotate-left me-1 text-[10px]"></i> Belum Dicetak ({{ $totalUnprinted }})
                </a>
                <a href="{{ route('work-requests.print-queue', array_merge(request()->query(), ['print_status' => 'sudah'])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->print_status === 'sudah' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-check me-1 text-[10px]"></i> Sudah Dicetak ({{ $totalPrinted }})
                </a>
            </div>

            <!-- Right: Bulk Print Action, Date Filter Dropdown & Search Box (Mobile Friendly) -->
            <div class="flex items-center gap-2 w-full lg:w-auto">
                <!-- Tombol Cetak Massal (Muncul jika ada checkbox dipilih) -->
                <button type="button" 
                        x-show="selectedIds.length > 0" 
                        x-cloak
                        @click="executeBulkPrint()" 
                        class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center justify-center space-x-1.5 animate-pulse cursor-pointer flex-shrink-0">
                    <i class="fa-solid fa-print text-xs"></i>
                    <span>Cetak <strong x-text="selectedIds.length"></strong></span>
                </button>

                <!-- Filter Waktu Dropdown (Sebelah Kotak Pencarian) -->
                @php
                    $dateLabel = match($datePreset) {
                        'today' => 'Hari Ini',
                        '3days' => '3 Hari Terakhir',
                        '7days' => '7 Hari Terakhir',
                        'this_month' => 'Bulan Ini',
                        'custom' => (!empty($startDate) && !empty($endDate)) 
                            ? \Carbon\Carbon::parse($startDate)->format('d/m') . '-' . \Carbon\Carbon::parse($endDate)->format('d/m')
                            : 'Kustom',
                        default => 'Semua Waktu',
                    };

                    $buildDateUrl = function($preset) use ($scope, $printStatus) {
                        $params = ['scope' => $scope];
                        if ($printStatus && $printStatus !== 'all') $params['print_status'] = $printStatus;
                        if (request()->filled('search')) $params['search'] = request()->search;
                        $params['date_preset'] = $preset;
                        return route('work-requests.print-queue', $params);
                    };
                @endphp

                <div class="relative flex-shrink-0" x-data="{ openDateDropdown: false }" @click.outside="openDateDropdown = false" @keydown.escape.window="openDateDropdown = false">
                    <button type="button" 
                            @click="openDateDropdown = !openDateDropdown" 
                            class="px-3 py-1.5 text-xs rounded-xl font-bold transition flex items-center space-x-1.5 shadow-xs cursor-pointer border {{ $datePreset === '3days' ? 'bg-amber-400 text-slate-950 border-amber-500 font-black' : ($datePreset !== 'all' ? 'bg-indigo-50 text-indigo-900 border-indigo-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200') }}"
                            title="Filter rentang waktu pembuatan WR">
                        @if($datePreset === '3days')
                            <i class="fa-solid fa-bolt text-amber-900 text-[11px]"></i>
                        @else
                            <i class="fa-regular fa-calendar-days text-[11px] {{ $datePreset !== 'all' ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                        @endif
                        <span class="whitespace-nowrap">{{ $dateLabel }}</span>
                        <i class="fa-solid fa-chevron-down text-[9px] transition-transform ms-0.5 {{ $datePreset === '3days' ? 'text-slate-950' : 'text-slate-400' }}" :class="openDateDropdown ? 'rotate-180' : ''"></i>
                    </button>

                    <!-- Dropdown Popover Menu -->
                    <div x-show="openDateDropdown" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-72 max-w-[calc(100vw-32px)] bg-white rounded-2xl shadow-xl border border-slate-200 p-3 z-50">
                        
                        <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                            <span class="text-[11px] font-bold text-slate-700 flex items-center">
                                <i class="fa-regular fa-calendar-days me-1.5 text-slate-400"></i> Filter Rentang Waktu
                            </span>
                            @if($datePreset !== 'all')
                                <a href="{{ $buildDateUrl('all') }}" class="text-[10px] font-bold text-rose-600 hover:text-rose-700 flex items-center space-x-1" title="Reset filter waktu ke semua">
                                    <i class="fa-solid fa-rotate-left text-[9px]"></i>
                                    <span>Reset</span>
                                </a>
                            @endif
                        </div>

                        <!-- Presets List -->
                        <div class="space-y-1">
                            <a href="{{ $buildDateUrl('all') }}" 
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs font-semibold transition {{ $datePreset === 'all' ? 'bg-[#0a2342] text-amber-400 font-bold' : 'text-slate-700 hover:bg-slate-100' }}">
                                <span class="flex items-center space-x-2">
                                    <i class="fa-regular fa-calendar text-[11px] w-4 text-center"></i>
                                    <span>Semua Waktu</span>
                                </span>
                                @if($datePreset === 'all')
                                    <i class="fa-solid fa-check text-xs text-amber-400"></i>
                                @endif
                            </a>

                            <a href="{{ $buildDateUrl('today') }}" 
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs font-semibold transition {{ $datePreset === 'today' ? 'bg-[#0a2342] text-amber-400 font-bold' : 'text-slate-700 hover:bg-slate-100' }}">
                                <span class="flex items-center space-x-2">
                                    <i class="fa-regular fa-sun text-[11px] w-4 text-center"></i>
                                    <span>Hari Ini</span>
                                </span>
                                @if($datePreset === 'today')
                                    <i class="fa-solid fa-check text-xs text-amber-400"></i>
                                @endif
                            </a>

                            <a href="{{ $buildDateUrl('3days') }}" 
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs font-semibold transition {{ $datePreset === '3days' ? 'bg-amber-400 text-slate-950 font-black' : 'text-slate-700 hover:bg-amber-50/60' }}">
                                <span class="flex items-center space-x-2">
                                    <i class="fa-solid fa-bolt text-[11px] w-4 text-center text-amber-600"></i>
                                    <span>3 Hari Terakhir</span>
                                </span>
                                @if($datePreset === '3days')
                                    <i class="fa-solid fa-check text-xs text-slate-950"></i>
                                @endif
                            </a>

                            <a href="{{ $buildDateUrl('7days') }}" 
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs font-semibold transition {{ $datePreset === '7days' ? 'bg-[#0a2342] text-amber-400 font-bold' : 'text-slate-700 hover:bg-slate-100' }}">
                                <span class="flex items-center space-x-2">
                                    <i class="fa-regular fa-calendar-days text-[11px] w-4 text-center"></i>
                                    <span>7 Hari Terakhir</span>
                                </span>
                                @if($datePreset === '7days')
                                    <i class="fa-solid fa-check text-xs text-amber-400"></i>
                                @endif
                            </a>

                            <a href="{{ $buildDateUrl('this_month') }}" 
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs font-semibold transition {{ $datePreset === 'this_month' ? 'bg-[#0a2342] text-amber-400 font-bold' : 'text-slate-700 hover:bg-slate-100' }}">
                                <span class="flex items-center space-x-2">
                                    <i class="fa-regular fa-calendar-check text-[11px] w-4 text-center"></i>
                                    <span>Bulan Ini</span>
                                </span>
                                @if($datePreset === 'this_month')
                                    <i class="fa-solid fa-check text-xs text-amber-400"></i>
                                @endif
                            </a>
                        </div>

                        <!-- Rentang Kustom Form -->
                        <div class="mt-2.5 pt-2.5 border-t border-slate-100">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Rentang Tanggal Kustom</span>
                            <form method="GET" action="{{ route('work-requests.print-queue') }}" class="space-y-2">
                                <input type="hidden" name="scope" value="{{ $scope }}">
                                @if(request()->filled('print_status'))
                                    <input type="hidden" name="print_status" value="{{ request()->print_status }}">
                                @endif
                                @if(request()->filled('search'))
                                    <input type="hidden" name="search" value="{{ request()->search }}">
                                @endif
                                <input type="hidden" name="date_preset" value="custom">

                                <div class="grid grid-cols-2 gap-1.5">
                                    <div>
                                        <label for="custom_start_date" class="block text-[10px] text-slate-500 font-semibold mb-0.5">Dari:</label>
                                        <input type="date" 
                                               id="custom_start_date" 
                                               name="start_date" 
                                               value="{{ $startDate }}" 
                                               class="w-full text-xs p-1.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label for="custom_end_date" class="block text-[10px] text-slate-500 font-semibold mb-0.5">Sampai:</label>
                                        <input type="date" 
                                               id="custom_end_date" 
                                               name="end_date" 
                                               value="{{ $endDate }}" 
                                               class="w-full text-xs p-1.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                    </div>
                                </div>

                                <button type="submit" 
                                        class="w-full py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center space-x-1 cursor-pointer">
                                    <i class="fa-solid fa-filter text-[10px]"></i>
                                    <span>Terapkan Rentang</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Search Input Form -->
                <form method="GET" action="{{ route('work-requests.print-queue') }}" class="relative flex-1 sm:w-64">
                    <input type="hidden" name="scope" value="{{ $scope }}">
                    @if(request()->filled('print_status'))
                        <input type="hidden" name="print_status" value="{{ request()->print_status }}">
                    @endif
                    @if($datePreset && $datePreset !== 'custom')
                        <input type="hidden" name="date_preset" value="{{ $datePreset }}">
                    @endif
                    @if($startDate)
                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                    @endif
                    @if($endDate)
                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                    @endif
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request()->search }}" 
                           placeholder="Cari No. WRI, komponen..." 
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                </form>
            </div>
        </div>

        <!-- 4. PRINT QUEUE TABLE (DENGAN CHECKBOX MULTI-SELECT & AKSI CETAK) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <form id="bulkPrintForm" method="POST" action="{{ route('work-requests.bulk-print') }}" target="_blank">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-3 text-center w-10">
                                <input type="checkbox" 
                                       @change="toggleSelectAll($event)" 
                                       :checked="isAllSelected()"
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            </th>
                            <th class="py-3.5 px-3 min-w-[170px]">No WR / Tanggal</th>
                            <th class="py-3.5 px-3 min-w-[200px]">Komponen</th>
                            <th class="py-3.5 px-3 text-center">Qty Order</th>
                            <th class="py-3.5 px-3 min-w-[150px]">Routing / Alur</th>
                            <th class="py-3.5 px-3 min-w-[170px]">4 Otorisasi Resmi</th>
                            <th class="py-3.5 px-3 text-center whitespace-nowrap min-w-[125px]">Aksi / Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($workRequests as $wri)
                        @php
                            $printCount = $wri->logs->where('action_type', 'printed')->count();
                            $lastPrintLog = $wri->logs->where('action_type', 'printed')->sortByDesc('id')->first();
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition group">
                            <!-- Checkbox -->
                            <td class="py-3 px-3 text-center align-middle">
                                <input type="checkbox" 
                                       value="{{ $wri->id }}" 
                                       x-model="selectedIds"
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            </td>

                            <!-- No WR / Tanggal -->
                            <td class="py-3 px-3 align-top">
                                <div class="font-mono font-bold text-slate-900 group-hover:text-indigo-600 transition">
                                    {{ $wri->wri_number }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $wri->created_at->format('d M Y H:i') }}
                                </div>
                                @if($wri->spk_reference)
                                    <div class="text-[10px] font-mono text-blue-700 font-bold mt-0.5">
                                        SPK: {{ $wri->spk_reference }}
                                    </div>
                                @endif
                            </td>

                            <!-- Komponen -->
                            <td class="py-3 px-3 align-top">
                                <div class="font-extrabold text-slate-900 line-clamp-1" title="{{ $wri->component?->name }}">
                                    {{ $wri->component?->name ?? 'Komponen' }}
                                </div>
                                <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                    {{ $wri->component?->part_number ?? '-' }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 truncate" title="{{ $wri->component?->dimension_formatted ?: ($wri->component?->specification ?: '-') }}">
                                    Dimensi: {{ $wri->component?->dimension_formatted ?: ($wri->component?->specification ?: '-') }}
                                </div>
                            </td>

                            <!-- Qty Order -->
                            <td class="py-3 px-3 text-center align-top">
                                <span class="font-black text-slate-900 text-sm">
                                    {{ number_format($wri->quantity_requested, 0) }}
                                </span>
                                <span class="block text-[10px] text-slate-500 uppercase font-semibold">
                                    {{ $wri->component?->uom ?? 'PCS' }}
                                </span>
                            </td>

                            <!-- Routing / Alur -->
                            <td class="py-3 px-3 align-top">
                                <div class="text-xs font-bold text-slate-800 line-clamp-2" title="{{ $wri->routing_text ?: $wri->dept_penerima }}">
                                    {{ $wri->routing_text ?: ($wri->dept_penerima ?: 'Pengerjaan Mesin') }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    Dari: <span class="font-bold text-slate-700">{{ $wri->dept_pengaju ?: 'Gudang' }}</span>
                                </div>
                            </td>

                            <!-- 4 Otorisasi Resmi -->
                            <td class="py-3 px-3 align-top">
                                <div class="text-[11px] space-y-0.5">
                                    <div><span class="text-slate-400">Pemohon:</span> <strong class="text-slate-800">{{ $wri->requestedBy?->name ?? 'Pemohon' }}</strong></div>
                                    <div><span class="text-slate-400">Ka. Pengaju:</span> <strong class="text-emerald-700">{{ $wri->approval_pengaju_log?->operator_name ?? 'Disetujui' }}</strong></div>
                                    <div><span class="text-slate-400">Operator:</span> <strong class="text-indigo-700">{{ $wri->assigned_operator_name ?: '-' }}</strong></div>
                                    <div><span class="text-slate-400">Ka. Penerima:</span> <strong class="text-emerald-700">{{ $wri->approval_penerima_log?->operator_name ?? 'Disetujui' }}</strong></div>
                                </div>
                            </td>

                            <!-- Aksi & Status Cetak (Digabung) -->
                            <td class="py-3 px-3 text-center align-middle whitespace-nowrap">
                                <div class="inline-flex flex-col items-center space-y-1.5">
                                    <a href="{{ route('work-requests.print-sheet', $wri) }}" 
                                       target="_blank" 
                                       class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center space-x-1.5 cursor-pointer"
                                       title="Cetak formulir 3 rangkap dalam 1 lembar HVS A4">
                                        <i class="fa-solid fa-print text-[11px]"></i>
                                        <span>{{ $printCount > 0 ? 'Cetak Ulang' : 'Cetak WR' }}</span>
                                    </a>

                                    @if($printCount === 0)
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center space-x-1">
                                            <i class="fa-solid fa-clock-rotate-left text-[8px]"></i>
                                            <span>Belum Dicetak</span>
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center space-x-1" 
                                              title="Terakhir dicetak: {{ $lastPrintLog?->created_at?->format('d/m/Y H:i') }} oleh {{ $lastPrintLog?->operator_name }}">
                                            <i class="fa-solid fa-circle-check text-[8px]"></i>
                                            <span>Sudah ({{ $printCount }}x)</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-300 flex items-center justify-center mx-auto mb-3 text-2xl">
                                    <i class="fa-solid fa-print"></i>
                                </div>
                                <p class="text-sm font-bold text-slate-700">Tidak ada dokumen yang siap dicetak</p>
                                <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                                    Dokumen WR hanya akan masuk ke antrean cetak ini setelah disetujui oleh Ka. Pengaju, Ka. Penerima, dan operator pelaksana telah ditugaskan.
                                </p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($workRequests->hasPages())
            <div class="px-4 py-3 bg-slate-50/70 border-t border-slate-200">
                {{ $workRequests->links() }}
            </div>
            @endif
        </div>

    </div>

    <!-- Alpine.js Manager Script untuk Checkbox & Bulk Print -->
    <script>
        function printQueueManager() {
            return {
                selectedIds: [],
                allIds: @json($workRequests->pluck('id')->values()),
                
                isAllSelected() {
                    return this.allIds.length > 0 && this.selectedIds.length === this.allIds.length;
                },

                toggleSelectAll(event) {
                    if (event.target.checked) {
                        this.selectedIds = [...this.allIds];
                    } else {
                        this.selectedIds = [];
                    }
                },

                executeBulkPrint() {
                    if (this.selectedIds.length === 0) return;
                    document.getElementById('bulkPrintForm').submit();
                }
            };
        }
    </script>
</x-app-layout>

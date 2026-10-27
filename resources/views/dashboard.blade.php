<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER (REFERENCE STYLE) -->
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <!-- Curved Wave Header Background -->
            <div class="wave-header !h-[260px] sm:!h-[310px]">
                <div class="wave-shape-2"></div>
            </div>

            <!-- Content Centered Over Wave Header -->
            <div class="relative z-10 pt-5 sm:pt-6 pb-10 sm:pb-12 text-center px-4">
                <!-- Floating Circular Badge with HPK Logo -->
                <div class="floating-logo-badge mb-2.5 sm:mb-3 shadow-xl">
                    <img src="{{ asset('images/logo_hpk.webp') }}" alt="PT Hydraxle Perkasa Karoseri" class="w-14 h-14 sm:w-16 sm:h-16 object-contain">
                </div>

                <!-- Brand & Page Titles -->
                <div class="font-extrabold text-[10px] sm:text-xs uppercase tracking-widest text-teal-300">PT. HYDRAXLE PERKASA</div>
                <h1 class="font-black text-xl sm:text-3xl text-white tracking-wide mt-1">WAREHOUSE MANAGEMENT SYSTEM</h1>
                <p class="text-xs text-slate-300 mt-1 sm:mt-1.5 max-w-xl mx-auto px-2">
                    Sistem Pergudangan Karoseri In-House, Fabrikasi & Komponen Hidrolik (1 Gedung Terpadu)
                </p>

                <!-- Status Pills -->
                <div class="mt-3.5 sm:mt-4 flex flex-wrap items-center justify-center gap-2 sm:gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 backdrop-blur-xs">
                        <span class="w-2 h-2 me-1.5 bg-emerald-400 rounded-full animate-ping"></span>
                        Operasional Aktif
                    </span>
                    <span class="text-xs text-slate-300 font-mono bg-white/10 px-3.5 py-1 rounded-full backdrop-blur-xs border border-white/10">
                        <i class="fa-regular fa-calendar me-1.5 text-teal-300"></i> {{ now()->translatedFormat('l, d F Y') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. TOP KPI METRICS STATS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total SKU Aktif -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between hover:shadow-md transition">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Komponen (SKU)</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $totalComponents }} <span class="text-xs font-normal text-slate-500">Item</span></h3>
                    <p class="text-[11px] text-teal-600 mt-1 flex items-center font-semibold">
                        <i class="fa-solid fa-check me-1.5"></i>
                        Katalog Part Terdaftar
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-xs">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>

            <!-- Alert Stok Kritis / Minimum -->
            @can('master-komponen.view')
            <a href="{{ route('components.index', ['low_stock' => 1]) }}" class="bg-white rounded-2xl p-5 border {{ $lowStockCount > 0 ? 'border-rose-300 ring-2 ring-rose-100' : 'border-slate-200/80' }} shadow-sm flex items-center justify-between group hover:border-rose-400 hover:shadow-md transition">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-rose-600 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-rose-500 me-1.5 animate-pulse"></span>
                        Stok Menipis (Kritis)
                    </p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $lowStockCount }} <span class="text-xs font-normal text-slate-500">Item</span></h3>
                    <p class="text-[11px] text-rose-600 mt-1 font-semibold group-hover:underline">
                        Di bawah safety stock &rarr;
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </a>
            @else
            <div class="bg-white rounded-2xl p-5 border {{ $lowStockCount > 0 ? 'border-rose-300 ring-2 ring-rose-100' : 'border-slate-200/80' }} shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-rose-600 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-rose-500 me-1.5 animate-pulse"></span>
                        Stok Menipis (Kritis)
                    </p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $lowStockCount }} <span class="text-xs font-normal text-slate-500">Item</span></h3>
                    <p class="text-[11px] text-slate-400 mt-1 font-semibold">
                        Katalog Gudang
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            @endcan

            <!-- Transaksi Hari Ini -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between hover:shadow-md transition">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Transaksi Hari Ini</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $todayTransactionsCount }} <span class="text-xs font-normal text-slate-500">Mutasi</span></h3>
                    <p class="text-[11px] text-blue-600 mt-1 font-medium">Inbound, Outbound & Transfer</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-xs">
                    <i class="fa-solid fa-right-left"></i>
                </div>
            </div>

            <!-- Antrian Approval (ECR & Disposal) -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between hover:shadow-md transition">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Menunggu Approval</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $pendingApprovalsCount }} <span class="text-xs font-normal text-slate-500">Pengajuan</span></h3>
                    <p class="text-[11px] text-purple-600 mt-1 font-semibold">ECR & Pengajuan Disposal</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-xs">
                    <i class="fa-solid fa-stamp"></i>
                </div>
            </div>
        </div>

        <!-- 3. QUICK ACTION DASHBOARD CARDS GRID (DASH-CARD FROM REFERENS) -->
        <div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-lg">Modul Aplikasi Pergudangan HPK</h3>
                    <p class="text-xs text-slate-500">Pilih menu navigasi untuk input data form atau transaksi barang harian</p>
                </div>
                <button type="button" 
                        @click="$dispatch('open-global-scanner')" 
                        class="hidden sm:inline-flex items-center px-4 py-2 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-md border border-white/10 transition self-start sm:self-auto">
                    <i class="fa-solid fa-qrcode me-2 text-sm"></i>
                    Buka Scanner QR / Barcode
                </button>
            </div>

            <div class="dashboard-grid">
                <!-- 1. Master Komponen -->
                @can('master-komponen.view')
                <a href="{{ route('components.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-boxes-stacked card-icon-bg"></i>
                    <div class="icon-box text-[#4a9e9e] bg-teal-50">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-teal-700 transition">Master Komponen</h4>
                            <div class="flex items-center gap-1.5">
                                @if(!empty($unallocatedComponentsCount) && $unallocatedComponentsCount > 0)
                                    <span class="text-[10px] font-extrabold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-200" title="{{ $unallocatedComponentsCount }} part butuh alokasi rak">
                                        ⚠️ {{ $unallocatedComponentsCount }} Perlu Rak
                                    </span>
                                @endif
                                <span class="text-[11px] font-bold text-teal-700 bg-teal-100/70 px-2 py-0.5 rounded-full">{{ $totalComponents }} SKU</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Katalog part, upload foto fisik, spesifikasi teknis & batas safety stock</p>
                    </div>
                </a>
                @endcan

                <!-- 2. Transaksi Barang -->
                @can('transaksi-komponen.view')
                <a href="{{ route('transactions.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-right-left card-icon-bg"></i>
                    <div class="icon-box text-blue-600 bg-blue-50">
                        <i class="fa-solid fa-right-left"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-blue-700 transition">Transaksi Komponen</h4>
                            <span class="text-[11px] font-bold text-blue-700 bg-blue-100/70 px-2 py-0.5 rounded-full">{{ $todayTransactionsCount }} Hari Ini</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Inbound supplier, outbound serah terima lini perakitan & transfer antar rak</p>
                    </div>
                </a>
                @endcan

                <!-- 3. ECR Revisi Komponen -->
                @can('ecr.view')
                <a href="{{ route('ecrs.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-file-pen card-icon-bg"></i>
                    <div class="icon-box text-purple-600 bg-purple-50">
                        <i class="fa-solid fa-file-pen"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-purple-700 transition">ECR Revisi Part</h4>
                            @if ($pendingEcrsCount > 0)
                                <span class="text-[11px] font-bold text-purple-700 bg-purple-100/70 px-2 py-0.5 rounded-full">{{ $pendingEcrsCount }} Review</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Engineering Change Request, pembaruan dimensi spek & persetujuan berjenjang</p>
                    </div>
                </a>
                @endcan

                <!-- 4. Cycle Count (Stok Opname) -->
                @can('cycle-count.view')
                <a href="{{ route('cycle-counts.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-clipboard-check card-icon-bg"></i>
                    <div class="icon-box text-amber-600 bg-amber-50">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-amber-700 transition">Cycle Count</h4>
                            <span class="text-[11px] font-bold text-amber-700 bg-amber-100/70 px-2 py-0.5 rounded-full">Opname Fisik</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Audit stok periodik per zona rak gudang, input jumlah fisik & rekonsiliasi selisih</p>
                    </div>
                </a>
                @endcan

                <!-- 5. Permintaan Label QR -->
                @can('label-qr.view')
                <a href="{{ route('qr-requests.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-qrcode card-icon-bg"></i>
                    <div class="icon-box text-cyan-600 bg-cyan-50">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-cyan-700 transition">Permintaan Label QR</h4>
                            @if ($pendingQrRequestsCount > 0)
                                <span class="text-[11px] font-bold text-cyan-700 bg-cyan-100/70 px-2 py-0.5 rounded-full">{{ $pendingQrRequestsCount }} Siap Cetak</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Pengajuan stiker barcode / QR code thermal untuk part baru & identifikasi rak</p>
                    </div>
                </a>
                @endcan

                <!-- 6. Pengajuan Disposal Scrap -->
                @can('disposal.view')
                <a href="{{ route('disposals.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-trash-can card-icon-bg"></i>
                    <div class="icon-box text-rose-600 bg-rose-50">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-rose-700 transition">Pengajuan Disposal</h4>
                            @if ($pendingDisposalsCount > 0)
                                <span class="text-[11px] font-bold text-rose-700 bg-rose-100/70 px-2 py-0.5 rounded-full">{{ $pendingDisposalsCount }} Pending</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Scrap potongan plat besi, material rusak/afkir & otorisasi pimpinan</p>
                    </div>
                </a>
                @endcan

                <!-- 8. Work Request Inhouse (Machine Center) -->
                @can('work-request.view')
                <a href="{{ route('work-requests.index') }}" class="dash-card group border-amber-300 bg-amber-50/20">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-industry card-icon-bg"></i>
                    <div class="icon-box text-amber-800 bg-amber-100">
                        <i class="fa-solid fa-industry"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-amber-800 transition">Work Request Mesin</h4>
                            @if ($readyForWarehouseWriCount > 0)
                                <span class="text-[11px] font-black text-slate-950 bg-amber-400 px-2 py-0.5 rounded-full animate-pulse">{{ $readyForWarehouseWriCount }} Siap Gudang</span>
                            @elseif ($inProductionWriCount > 0)
                                <span class="text-[11px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">{{ $inProductionWriCount }} di Mesin</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Order fabrikasi ke Mesin Center (Potong Laser & Bending) & serah terima stok</p>
                    </div>
                </a>
                @endcan

                <!-- 9. Material Handling (Task Gudang) -->
                @can('material-handling.view')
                <a href="{{ route('work-station-supplies.index') }}" class="dash-card group border-teal-200 bg-teal-50/20">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-truck-ramp-box card-icon-bg"></i>
                    <div class="icon-box text-teal-700 bg-teal-100">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-teal-800 transition">Material Handling</h4>
                            <span class="text-[11px] font-bold text-teal-800 bg-teal-100 px-2 py-0.5 rounded-full">Task Gudang</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Pengambilan & pengantaran komponen antar mesin fabrikasi dan ke gudang</p>
                    </div>
                </a>
                @endcan
            </div>
        </div>

        <!-- System Footer -->
        <div class="pt-4 border-t border-slate-200/70 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400">
            <span>Versi Sistem: v2.4 HPK Build</span>
            <div class="flex items-center space-x-3">
                <span class="text-slate-400">PT. Hydraxle Perkasa Karoseri</span>
                <span class="text-slate-300">&bull;</span>
                <a href="{{ route('transactions.index') }}" class="font-semibold text-teal-700 hover:underline">Semua Transaksi &rarr;</a>
            </div>
        </div>

    </div>
</x-app-layout>

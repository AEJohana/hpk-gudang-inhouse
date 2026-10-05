<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-8 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER (REFERENCE STYLE) -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <!-- Curved Wave Header Background -->
            <div class="wave-header" style="height: 310px;">
                <div class="wave-shape-2"></div>
            </div>

            <!-- Content Centered Over Wave Header -->
            <div class="relative z-10 pt-6 pb-12 text-center px-4">
                <!-- Floating Circular Badge with HPK Logo -->
                <div class="floating-logo-badge mb-3 shadow-xl">
                    <img src="{{ asset('images/logo_hpk.webp') }}" alt="PT Hydraxle Perkasa Karoseri" class="w-16 h-16 object-contain">
                </div>

                <!-- Brand & Page Titles -->
                <div class="font-extrabold text-xs uppercase tracking-widest text-teal-300">PT. HYDRAXLE PERKASA</div>
                <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide mt-1">WAREHOUSE MANAGEMENT SYSTEM</h1>
                <p class="text-xs text-slate-300 mt-1.5 max-w-xl mx-auto">
                    Sistem Pergudangan Karoseri In-House, Fabrikasi & Komponen Hidrolik (1 Gedung Terpadu)
                </p>

                <!-- Status Pills -->
                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
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

        <!-- 2. QUICK ACTION DASHBOARD CARDS GRID (DASH-CARD FROM REFERENS) -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-lg">Modul Aplikasi Pergudangan HPK</h3>
                    <p class="text-xs text-slate-500">Pilih menu navigasi untuk input data form atau transaksi barang harian</p>
                </div>
                <button type="button" 
                        @click="$dispatch('open-global-scanner')" 
                        class="hidden sm:inline-flex items-center px-4 py-2 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-md border border-white/10 transition">
                    <i class="fa-solid fa-qrcode me-2 text-sm"></i>
                    Buka Scanner QR / Barcode
                </button>
            </div>

            <div class="dashboard-grid">
                <!-- 1. Master Komponen -->
                <a href="{{ route('components.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-boxes-stacked card-icon-bg"></i>
                    <div class="icon-box text-[#4a9e9e] bg-teal-50">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-teal-700 transition">Master Komponen</h4>
                            <span class="text-[11px] font-bold text-teal-700 bg-teal-100/70 px-2 py-0.5 rounded-full">{{ $totalComponents }} SKU</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Katalog part, upload foto fisik, spesifikasi teknis & batas safety stock</p>
                    </div>
                </a>

                <!-- 2. Transaksi Barang -->
                <a href="{{ route('transactions.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-right-left card-icon-bg"></i>
                    <div class="icon-box text-blue-600 bg-blue-50">
                        <i class="fa-solid fa-right-left"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-blue-700 transition">Transaksi Komponen</h4>
                            <span class="text-[11px] font-bold text-blue-700 bg-blue-100/70 px-2 py-0.5 rounded-full">{{ $todayTransactionsCount }} Hari Ini</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Inbound supplier, outbound serah terima lini perakitan & transfer antar rak</p>
                    </div>
                </a>

                <!-- 3. ECR Revisi Komponen -->
                <a href="{{ route('ecrs.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-file-pen card-icon-bg"></i>
                    <div class="icon-box text-purple-600 bg-purple-50">
                        <i class="fa-solid fa-file-pen"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-purple-700 transition">ECR Revisi Part</h4>
                            @if ($pendingEcrs->count() > 0)
                                <span class="text-[11px] font-bold text-purple-700 bg-purple-100/70 px-2 py-0.5 rounded-full">{{ $pendingEcrs->count() }} Review</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Engineering Change Request, pembaruan dimensi spek & persetujuan berjenjang</p>
                    </div>
                </a>

                <!-- 4. Cycle Count (Stok Opname) -->
                <a href="{{ route('cycle-counts.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-clipboard-check card-icon-bg"></i>
                    <div class="icon-box text-amber-600 bg-amber-50">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-amber-700 transition">Cycle Count</h4>
                            <span class="text-[11px] font-bold text-amber-700 bg-amber-100/70 px-2 py-0.5 rounded-full">Opname Fisik</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Audit stok periodik per zona rak gudang, input jumlah fisik & rekonsiliasi selisih</p>
                    </div>
                </a>

                <!-- 5. Permintaan Label QR -->
                <a href="{{ route('qr-requests.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-qrcode card-icon-bg"></i>
                    <div class="icon-box text-cyan-600 bg-cyan-50">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-cyan-700 transition">Permintaan Label QR</h4>
                            @if ($pendingQrRequests->count() > 0)
                                <span class="text-[11px] font-bold text-cyan-700 bg-cyan-100/70 px-2 py-0.5 rounded-full">{{ $pendingQrRequests->count() }} Siap Cetak</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Pengajuan stiker barcode / QR code thermal untuk part baru & identifikasi rak</p>
                    </div>
                </a>

                <!-- 6. Pengajuan Disposal Scrap -->
                <a href="{{ route('disposals.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-trash-can card-icon-bg"></i>
                    <div class="icon-box text-rose-600 bg-rose-50">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-rose-700 transition">Pengajuan Disposal</h4>
                            @if ($pendingDisposals->count() > 0)
                                <span class="text-[11px] font-bold text-rose-700 bg-rose-100/70 px-2 py-0.5 rounded-full">{{ $pendingDisposals->count() }} Pending</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Scrap potongan plat besi, material rusak/afkir & otorisasi pimpinan</p>
                    </div>
                </a>

                <!-- 8. Work Request Inhouse (Machine Center) -->
                <a href="{{ route('work-requests.index') }}" class="dash-card group border-amber-300 bg-amber-50/20">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-industry card-icon-bg"></i>
                    <div class="icon-box text-amber-800 bg-amber-100">
                        <i class="fa-solid fa-industry"></i>
                    </div>
                    <div>
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

                <!-- 9. Supply Stasiun Kerja -->
                <a href="{{ route('work-station-supplies.create') }}" class="dash-card group border-blue-200 bg-blue-50/20">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-truck-ramp-box card-icon-bg"></i>
                    <div class="icon-box text-blue-600 bg-blue-100">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-blue-700 transition">Supply Stasiun Kerja</h4>
                            <span class="text-[11px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">{{ $todaySupplyCount }} Hari Ini</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Pengeluaran komponen rak ke Lini Dump Truck, Tangki, Mixer & Sub-Assembly</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- 3. TOP KPI METRICS STATS -->
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

        <!-- 3B. MACHINE CENTER LIVE ROUTING TRACKER -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-900 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-industry"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Machine Center &bull; Alur Fabrikasi Komponen In-House</h3>
                        <p class="text-xs text-slate-500">Pelacakan proses potong laser, bending press brake, hingga masuk rak gudang</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('work-requests.create') }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition flex items-center">
                        <i class="fa-solid fa-plus me-1 text-[10px]"></i> Order WRI
                    </a>
                    <a href="{{ route('work-requests.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                        Semua WRI &rarr;
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                @forelse($activeWorkRequests as $activeWri)
                <div class="p-3.5 rounded-2xl border {{ $activeWri->status === 'ready_for_warehouse' ? 'bg-amber-50/60 border-amber-300 ring-2 ring-amber-300/40' : 'bg-slate-50 border-slate-200' }} flex flex-col justify-between space-y-3">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-[10px] font-bold text-slate-700 bg-white px-1.5 py-0.5 rounded border border-slate-200">
                                {{ $activeWri->wri_number }}
                            </span>
                            <span class="px-2 py-0.2 rounded text-[10px] font-bold {{ $activeWri->status_badge['class'] }}">
                                {{ $activeWri->status_badge['label'] }}
                            </span>
                        </div>
                        <h5 class="font-extrabold text-xs text-slate-900 line-clamp-1 mt-1.5">
                            {{ $activeWri->component?->name ?? 'Komponen #' . $activeWri->component_id }}
                        </h5>
                        <span class="font-mono text-[10px] text-slate-500 block">
                            {{ $activeWri->component?->part_number ?? '-' }} &bull; {{ number_format($activeWri->quantity_requested, 0) }} {{ $activeWri->component?->uom ?? 'PCS' }}
                        </span>
                    </div>

                    <div>
                        <!-- Stepper dots -->
                        <div class="space-y-1 mb-2">
                            @foreach($activeWri->steps as $st)
                            <div class="flex items-center space-x-1.5 text-[10px]">
                                <span class="w-3.5 h-3.5 rounded-full flex items-center justify-center text-[8px] font-bold {{ $st->status === 'completed' ? 'bg-emerald-500 text-white' : ($st->status === 'in_progress' ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500') }}">
                                    {{ $st->step_number }}
                                </span>
                                <span class="truncate text-slate-700 font-semibold">{{ $st->machine->code }}: {{ $st->process_name }}</span>
                            </div>
                            @endforeach
                        </div>

                        @if($activeWri->status === 'ready_for_warehouse')
                            <a href="{{ route('work-requests.show', $activeWri) }}#putaway-section" class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] rounded-xl text-center block transition shadow-xs">
                                <i class="fa-solid fa-boxes-packing me-1"></i> Terima di Gudang
                            </a>
                        @else
                            <a href="{{ route('work-requests.show', $activeWri) }}" class="w-full py-1 bg-white hover:bg-slate-100 text-slate-700 font-semibold text-[11px] rounded-xl text-center block border border-slate-200 transition">
                                Pantau Mesin &rarr;
                            </a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="col-span-full py-6 text-center text-slate-400 text-xs">
                    Belum ada Work Request aktif di Machine Center saat ini.
                </div>
                @endforelse
            </div>
        </div>

        <!-- 4. SPLIT SECTION: 2D FLOOR PLAN & OPERATIONAL TASKS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- 4A. INTERACTIVE 2D FLOOR PLAN OF 1-BUILDING HPK WAREHOUSE (2 COLS) -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Tata Letak Visual Gedung Gudang HPK</h3>
                        <p class="text-xs text-slate-500">Denah pembagian 6 zona penyimpanan material dalam 1 gedung tunggal pabrik karoseri</p>
                    </div>
                    <a href="{{ route('warehouse-map.index') }}" class="text-xs font-bold text-teal-700 hover:text-teal-800 flex items-center">
                        Buka Peta Lengkap &rarr;
                    </a>
                </div>

                <!-- 2D Floor Plan Grid Map -->
                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex-1 flex flex-col justify-between space-y-3">
                    <div class="flex items-center justify-between text-[11px] text-slate-400 border-b border-slate-800 pb-2">
                        <span class="font-mono flex items-center">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 me-1.5"></span>
                            HPK MAIN WAREHOUSE BUILDING (FLOOR PLAN)
                        </span>
                        <span class="text-[10px] text-amber-400 font-semibold"><i class="fa-solid fa-door-open me-1"></i> Pintu Gerbang Crane Timur</span>
                    </div>

                    <!-- Warehouse Blocks -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- ZONA A: Raw Material Baja -->
                        <div class="p-3.5 rounded-lg bg-blue-950/60 border border-blue-600/40 text-blue-100 hover:border-blue-400 transition cursor-pointer">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-mono text-xs font-bold text-blue-400 bg-blue-900/60 px-1.5 py-0.5 rounded">ZONA A</span>
                                <span class="text-[10px] text-blue-300 font-semibold">{{ $zones['A']['occupancy_rate'] }}% Terisi</span>
                            </div>
                            <h4 class="text-xs font-bold text-white">Raw Material Baja</h4>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">Pelat Hitam/Bordes, UNP, WF, Pipa</p>
                            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                                <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $zones['A']['occupancy_rate'] }}%"></div>
                            </div>
                        </div>

                        <!-- ZONA B: Komponen Hidrolik -->
                        <div class="p-3.5 rounded-lg bg-indigo-950/60 border border-indigo-600/40 text-indigo-100 hover:border-indigo-400 transition cursor-pointer">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-mono text-xs font-bold text-indigo-400 bg-indigo-900/60 px-1.5 py-0.5 rounded">ZONA B</span>
                                <span class="text-[10px] text-indigo-300 font-semibold">{{ $zones['B']['occupancy_rate'] }}% Terisi</span>
                            </div>
                            <h4 class="text-xs font-bold text-white">Hidrolik & Presisi</h4>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">Silinder Hoist, Pompa, Valve, PTO</p>
                            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                                <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ $zones['B']['occupancy_rate'] }}%"></div>
                            </div>
                        </div>

                        <!-- ZONA C: Fastener & Hardware -->
                        <div class="p-3.5 rounded-lg bg-emerald-950/60 border border-emerald-600/40 text-emerald-100 hover:border-emerald-400 transition cursor-pointer">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-mono text-xs font-bold text-emerald-400 bg-emerald-900/60 px-1.5 py-0.5 rounded">ZONA C</span>
                                <span class="text-[10px] text-emerald-300 font-semibold">{{ $zones['C']['occupancy_rate'] }}% Terisi</span>
                            </div>
                            <h4 class="text-xs font-bold text-white">Hardware & Fastener</h4>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">Multi-Tier Bins Baut HT, Engsel, Lampu</p>
                            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $zones['C']['occupancy_rate'] }}%"></div>
                            </div>
                        </div>

                        <!-- ZONA D: Chemical & Cat -->
                        <div class="p-3.5 rounded-lg bg-amber-950/60 border border-amber-600/40 text-amber-100 hover:border-amber-400 transition cursor-pointer">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-mono text-xs font-bold text-amber-400 bg-amber-900/60 px-1.5 py-0.5 rounded">ZONA D</span>
                                <span class="text-[10px] text-amber-300 font-semibold">{{ $zones['D']['occupancy_rate'] }}% Terisi</span>
                            </div>
                            <h4 class="text-xs font-bold text-white">Chemical & Cat</h4>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">Drum Cat PU, Primer, Thinner, Gas Las</p>
                            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                                <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $zones['D']['occupancy_rate'] }}%"></div>
                            </div>
                        </div>

                        <!-- ZONA E: Buffer Staging Perakitan -->
                        <div class="p-3.5 rounded-lg bg-cyan-950/60 border border-cyan-600/40 text-cyan-100 hover:border-cyan-400 transition cursor-pointer">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-mono text-xs font-bold text-cyan-400 bg-cyan-900/60 px-1.5 py-0.5 rounded">ZONA E</span>
                                <span class="text-[10px] text-cyan-300 font-semibold">{{ $zones['E']['occupancy_rate'] }}% Terisi</span>
                            </div>
                            <h4 class="text-xs font-bold text-white">Staging Perakitan</h4>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">Buffer Material Siap Rakit Karoseri</p>
                            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                                <div class="bg-cyan-500 h-1.5 rounded-full" style="width: {{ $zones['E']['occupancy_rate'] }}%"></div>
                            </div>
                        </div>

                        <!-- ZONA F: Karantina & Scrap Yard -->
                        <div class="p-3.5 rounded-lg bg-rose-950/60 border border-rose-600/40 text-rose-100 hover:border-rose-400 transition cursor-pointer">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-mono text-xs font-bold text-rose-400 bg-rose-900/60 px-1.5 py-0.5 rounded">ZONA F</span>
                                <span class="text-[10px] text-rose-300 font-semibold">{{ $zones['F']['occupancy_rate'] }}% Terisi</span>
                            </div>
                            <h4 class="text-xs font-bold text-white">Karantina & Scrap</h4>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">Material Rusak / Afkir & Potongan Besi</p>
                            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2.5 overflow-hidden">
                                <div class="bg-rose-500 h-1.5 rounded-full" style="width: {{ $zones['F']['occupancy_rate'] }}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="text-[10px] text-slate-500 flex items-center justify-between border-t border-slate-800 pt-2">
                        <span><i class="fa-solid fa-truck-ramp-box me-1 text-slate-400"></i> Akses Jalan Forklift Tengah (Lebar 4.5m)</span>
                        <span><i class="fa-solid fa-industry me-1 text-slate-400"></i> Pintu Akses Fabrikasi & Perakitan Barat</span>
                    </div>
                </div>
            </div>

            <!-- 4B. OPERATIONAL FEED & TASKS (1 COL) -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-base mb-1">Antrian Tugas & Aktivitas</h3>
                    <p class="text-xs text-slate-500 mb-4">Pengajuan ECR, Disposal & Permintaan Label QR</p>

                    <!-- ECR Pending Items -->
                    @if ($pendingEcrs->isNotEmpty())
                        <div class="mb-4">
                            <span class="text-[11px] font-bold uppercase text-purple-700 tracking-wider flex items-center">
                                <i class="fa-solid fa-file-pen me-1.5"></i> ECR Menunggu Review:
                            </span>
                            <div class="mt-2 space-y-2">
                                @foreach ($pendingEcrs as $ecr)
                                    <a href="{{ route('ecrs.show', $ecr) }}" class="block p-2.5 rounded-xl bg-purple-50/70 border border-purple-200 hover:border-purple-300 transition text-xs group">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-purple-900">{{ $ecr->ecr_number }}</span>
                                            <span class="text-[10px] text-slate-500">{{ $ecr->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="font-semibold text-slate-800 mt-0.5 truncate group-hover:text-purple-700">{{ $ecr->title }}</p>
                                        <p class="text-[10px] text-slate-600">Part: {{ $ecr->component?->name ?? '-' }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Disposal Pending Items -->
                    @if ($pendingDisposals->isNotEmpty())
                        <div class="mb-4">
                            <span class="text-[11px] font-bold uppercase text-rose-700 tracking-wider flex items-center">
                                <i class="fa-solid fa-trash-can me-1.5"></i> Disposal Menunggu Approval:
                            </span>
                            <div class="mt-2 space-y-2">
                                @foreach ($pendingDisposals as $dsp)
                                    <a href="{{ route('disposals.show', $dsp) }}" class="block p-2.5 rounded-xl bg-rose-50/70 border border-rose-200 hover:border-rose-300 transition text-xs group">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-rose-900">{{ $dsp->disposal_number }}</span>
                                            <span class="text-[10px] font-bold text-rose-700">{{ $dsp->estimated_weight_kg }} Kg</span>
                                        </div>
                                        <p class="text-slate-700 mt-0.5 line-clamp-1 group-hover:text-rose-700">{{ $dsp->reason }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- QR Requests Pending -->
                    @if ($pendingQrRequests->isNotEmpty())
                        <div>
                            <span class="text-[11px] font-bold uppercase text-cyan-700 tracking-wider flex items-center">
                                <i class="fa-solid fa-qrcode me-1.5"></i> Cetak Label QR Siap:
                            </span>
                            <div class="mt-2 space-y-2">
                                @foreach ($pendingQrRequests as $qr)
                                    <div class="p-2.5 rounded-xl bg-cyan-50/70 border border-cyan-200 flex items-center justify-between text-xs">
                                        <div>
                                            <span class="font-mono font-bold text-cyan-900">{{ $qr->component?->part_number ?? '-' }}</span>
                                            <p class="text-[11px] text-slate-600 truncate">{{ $qr->component?->name ?? '-' }} ({{ $qr->print_qty }} label)</p>
                                        </div>
                                        <a href="{{ route('qr-requests.print-thermal', $qr) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-[10px] shadow-xs">
                                            <i class="fa-solid fa-print me-1"></i> Cetak
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Versi Sistem: v2.4 HPK Build</span>
                    <a href="{{ route('transactions.index') }}" class="font-semibold text-teal-700 hover:underline">Semua Transaksi &rarr;</a>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>

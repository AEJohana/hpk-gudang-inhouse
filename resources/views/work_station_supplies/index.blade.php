<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 text-[11px] font-bold uppercase rounded-md bg-blue-100 text-blue-900 border border-blue-300">
                        Material Handling &bull; Logistik Antar Stasiun
                    </span>
                    <span class="text-xs text-slate-500 font-medium">PT Hydraxle Perkasa Karoseri</span>
                </div>
                <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight mt-1">
                    Material Handling Command Center
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Pusat komando logistik lapangan: Penjemputan antar mesin fabrikasi, transit komponen, serah terima pengaju, dan auto-update saldo stok fisik
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <button type="button" 
                        @click="$dispatch('open-mh-scanner')" 
                        class="px-3.5 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                    <i class="fa-solid fa-qrcode me-1.5 text-sm"></i>
                    Scan QR Tiket WRI
                </button>
                <a href="{{ route('work-station-supplies.create') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center">
                    <i class="fa-solid fa-dolly me-1.5 text-blue-600"></i>
                    Supply Bahan Baku
                </a>
                <a href="{{ route('work-requests.index') }}" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center">
                    <i class="fa-solid fa-list-check me-1.5"></i>
                    Semua Tiket WRI
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
         x-data="materialHandlingCenter()" 
         @open-mh-scanner.window="openMhScanner()">

        <!-- 1. KPI LOGISTICS SUMMARY CARDS -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Pickup -->
            <div @click="activeTab = 'pickup'" 
                 class="cursor-pointer bg-white rounded-2xl p-4 border transition-all shadow-xs"
                 :class="activeTab === 'pickup' ? 'border-indigo-500 ring-2 ring-indigo-200 bg-indigo-50/20' : 'border-slate-200 hover:border-slate-300'">
                <div class="flex items-center justify-between">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-truck-pickup"></i>
                    </div>
                    <span class="text-xs font-bold text-indigo-700 bg-indigo-100/70 px-2 py-0.5 rounded-full">Tab 1</span>
                </div>
                <div class="mt-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Antrian Pickup Mesin</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ $pickupCount }}</h3>
                    <span class="text-[10px] text-slate-500">Selesai proses, tunggu MH</span>
                </div>
            </div>

            <!-- 2. Transit -->
            <div @click="activeTab = 'transit'" 
                 class="cursor-pointer bg-white rounded-2xl p-4 border transition-all shadow-xs"
                 :class="activeTab === 'transit' ? 'border-amber-500 ring-2 ring-amber-200 bg-amber-50/20' : 'border-slate-200 hover:border-slate-300'">
                <div class="flex items-center justify-between">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <span class="text-xs font-bold text-amber-800 bg-amber-100/70 px-2 py-0.5 rounded-full">Tab 2</span>
                </div>
                <div class="mt-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Transit Antar Mesin</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ $transitCount }}</h3>
                    <span class="text-[10px] text-slate-500">Sedang diangkut MH</span>
                </div>
            </div>

            <!-- 3. Siap Distribusi -->
            <div @click="activeTab = 'distribution'" 
                 class="cursor-pointer bg-white rounded-2xl p-4 border transition-all shadow-xs"
                 :class="activeTab === 'distribution' ? 'border-cyan-500 ring-2 ring-cyan-200 bg-cyan-50/20' : 'border-slate-200 hover:border-slate-300'">
                <div class="flex items-center justify-between">
                    <div class="w-11 h-11 rounded-xl bg-cyan-50 text-cyan-800 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-dolly"></i>
                    </div>
                    <span class="text-xs font-bold text-cyan-800 bg-cyan-100/70 px-2 py-0.5 rounded-full">Tab 3</span>
                </div>
                <div class="mt-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Siap Distribusi</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ $distributionCount }}</h3>
                    <span class="text-[10px] text-slate-500">Menuju Gudang / Pengaju</span>
                </div>
            </div>

            <!-- 4. Riwayat / Selesai -->
            <div @click="activeTab = 'history'" 
                 class="cursor-pointer bg-white rounded-2xl p-4 border transition-all shadow-xs"
                 :class="activeTab === 'history' ? 'border-emerald-500 ring-2 ring-emerald-200 bg-emerald-50/20' : 'border-slate-200 hover:border-slate-300'">
                <div class="flex items-center justify-between">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg font-bold">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-full">Tab 4</span>
                </div>
                <div class="mt-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Riwayat Serah Terima</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ $historyCount }}</h3>
                    <span class="text-[10px] text-slate-500">Closed & Stok Terupdate</span>
                </div>
            </div>
        </div>

        <!-- 2. TAB NAVIGASI UTAMA -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="border-b border-slate-200 px-4 pt-3 bg-slate-50/60 flex items-center space-x-2 overflow-x-auto">
                <button type="button" 
                        @click="activeTab = 'pickup'" 
                        class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                        :class="activeTab === 'pickup' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-truck-pickup"></i>
                    <span>1. Antrian Pickup Mesin</span>
                    <span class="px-2 py-0.2 rounded-full text-[10px] bg-indigo-100 text-indigo-800">{{ $pickupCount }}</span>
                </button>

                <button type="button" 
                        @click="activeTab = 'transit'" 
                        class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                        :class="activeTab === 'transit' ? 'border-amber-500 text-amber-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>2. Transit Antar Mesin</span>
                    <span class="px-2 py-0.2 rounded-full text-[10px] bg-amber-100 text-amber-800">{{ $transitCount }}</span>
                </button>

                <button type="button" 
                        @click="activeTab = 'distribution'" 
                        class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                        :class="activeTab === 'distribution' ? 'border-cyan-600 text-cyan-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-dolly"></i>
                    <span>3. Siap Distribusi ke Pengaju</span>
                    <span class="px-2 py-0.2 rounded-full text-[10px] bg-cyan-100 text-cyan-800">{{ $distributionCount }}</span>
                </button>

                <button type="button" 
                        @click="activeTab = 'history'" 
                        class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                        :class="activeTab === 'history' ? 'border-emerald-600 text-emerald-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>4. Riwayat & Log Aktivitas</span>
                    <span class="px-2 py-0.2 rounded-full text-[10px] bg-emerald-100 text-emerald-800">{{ $historyCount }}</span>
                </button>
            </div>

            <!-- TAB 1: ANTRIAN PICKUP MESIN -->
            <div x-show="activeTab === 'pickup'" class="p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-900">Daftar Komponen Selesai Proses Mesin &bull; Menunggu Pickup Material Handling</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Barang dengan status <code>(Out)</code> siap diangkut ke stasiun kerja berikutnya atau ke pengaju.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($tasksPickup as $task)
                    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs space-y-3 hover:border-indigo-300 transition">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-xs font-bold text-slate-900 block">{{ $task->wri_number }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $task->created_at->format('d M, H:i') }}</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $task->status_badge['class'] }}">
                                {{ $task->status_badge['label'] }}
                            </span>
                        </div>

                        <div class="flex items-center space-x-3 p-2.5 bg-slate-50 rounded-xl">
                            <img src="{{ $task->component?->image_url ?? asset('images/logo_hpk.webp') }}" class="w-12 h-12 rounded-lg object-cover border border-slate-200 bg-white flex-shrink-0">
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-slate-900 text-xs block truncate">{{ $task->component?->name }}</span>
                                <span class="font-mono text-[10px] font-semibold text-amber-900 bg-amber-100/70 px-1.5 py-0.2 rounded inline-block">
                                    {{ $task->component?->part_number }}
                                </span>
                                <div class="text-[11px] font-black text-slate-900 mt-1">
                                    Qty: {{ number_format($task->quantity_requested, 0) }} {{ $task->component?->uom }}
                                </div>
                            </div>
                        </div>

                        <div class="text-xs text-slate-600 bg-indigo-50/50 p-2 rounded-xl border border-indigo-100">
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Alur Proses:</span>
                            <span class="font-semibold text-indigo-950">{{ $task->routing_text ?: 'Mesin Fabrikasi' }}</span>
                        </div>

                        <!-- Action Button Pickup MH -->
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <a href="{{ route('work-requests.show', $task) }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium">
                                Detail Tiket
                            </a>
                            <form method="POST" action="{{ route('work-station-supplies.quick-action') }}">
                                @csrf
                                <input type="hidden" name="work_request_id" value="{{ $task->id }}">
                                <input type="hidden" name="action_type" value="process">
                                <input type="hidden" name="target_status" value="{{ $task->getNextStatusLabel() }}">
                                <input type="hidden" name="process_qty" value="{{ $task->quantity_requested }}">
                                <button type="submit" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5">
                                    <i class="fa-solid fa-truck-pickup text-[11px]"></i>
                                    <span>Pickup MH</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full py-12 text-center text-slate-400">
                        <i class="fa-solid fa-truck-pickup text-3xl mb-2 text-slate-300 block"></i>
                        Tidak ada antrian komponen yang menunggu pickup dari mesin saat ini.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- TAB 2: TRANSIT ANTAR MESIN -->
            <div x-show="activeTab === 'transit'" class="p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-900">Daftar Komponen Dalam Angkutan Material Handling (Transit Lapangan)</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Barang dengan status <code>MH Pickup</code> sedang dibawa menuju stasiun kerja / mesin berikutnya.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($tasksTransit as $task)
                    <div class="bg-white rounded-2xl p-4 border border-amber-300 bg-amber-50/20 shadow-xs space-y-3 hover:border-amber-400 transition">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-xs font-bold text-slate-900 block">{{ $task->wri_number }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $task->created_at->format('d M, H:i') }}</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $task->status_badge['class'] }}">
                                {{ $task->status_badge['label'] }}
                            </span>
                        </div>

                        <div class="flex items-center space-x-3 p-2.5 bg-white rounded-xl border border-amber-200">
                            <img src="{{ $task->component?->image_url ?? asset('images/logo_hpk.webp') }}" class="w-12 h-12 rounded-lg object-cover border border-slate-200 bg-white flex-shrink-0">
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-slate-900 text-xs block truncate">{{ $task->component?->name }}</span>
                                <span class="font-mono text-[10px] font-semibold text-amber-900 bg-amber-100/70 px-1.5 py-0.2 rounded inline-block">
                                    {{ $task->component?->part_number }}
                                </span>
                                <div class="text-[11px] font-black text-slate-900 mt-1">
                                    Qty: {{ number_format($task->quantity_requested, 0) }} {{ $task->component?->uom }}
                                </div>
                            </div>
                        </div>

                        <div class="text-xs text-slate-600 bg-white p-2.5 rounded-xl border border-amber-200">
                            <span class="text-amber-800 text-[10px] uppercase font-bold block">Tujuan Berikutnya:</span>
                            <span class="font-extrabold text-amber-950">{{ $task->getNextStatusLabel() }}</span>
                        </div>

                        <!-- Action Button Drop di Mesin Berikutnya -->
                        <div class="pt-2 border-t border-amber-200/60 flex items-center justify-between">
                            <a href="{{ route('work-requests.show', $task) }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium">
                                Detail Tiket
                            </a>
                            <form method="POST" action="{{ route('work-station-supplies.quick-action') }}">
                                @csrf
                                <input type="hidden" name="work_request_id" value="{{ $task->id }}">
                                <input type="hidden" name="action_type" value="process">
                                <input type="hidden" name="target_status" value="{{ $task->getNextStatusLabel() }}">
                                <input type="hidden" name="process_qty" value="{{ $task->quantity_requested }}">
                                <button type="submit" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5">
                                    <i class="fa-solid fa-location-dot text-[11px]"></i>
                                    <span>Drop di Stasiun Berikutnya</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full py-12 text-center text-slate-400">
                        <i class="fa-solid fa-truck-fast text-3xl mb-2 text-slate-300 block"></i>
                        Tidak ada barang yang sedang transit antar mesin.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- TAB 3: SIAP DISTRIBUSI KE PENGAJU / GUDANG -->
            <div x-show="activeTab === 'distribution'" class="p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-900">Daftar Komponen Selesai Fabrikasi &bull; Siap Diantar / Serah Terima ke Pengaju</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Komponen siap didistribusikan ke Gudang atau Lini Perakitan. Konfirmasi akhir akan otomatis menambah stok.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($tasksDistribution as $task)
                    <div class="bg-white rounded-2xl p-4 border border-cyan-300 bg-cyan-50/20 shadow-xs space-y-3 hover:border-cyan-400 transition">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-xs font-bold text-slate-900 block">{{ $task->wri_number }}</span>
                                <div class="text-[10px] text-slate-500 mt-0.5">
                                    {{ $task->dept_pengaju ?: 'Gudang' }} &bull; Slot: <span class="font-mono font-bold text-blue-900">{{ $task->target_specific_location_code }}</span>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $task->status_badge['class'] }}">
                                {{ $task->status_badge['label'] }}
                            </span>
                        </div>

                        <div class="flex items-center space-x-3 p-2.5 bg-white rounded-xl border border-cyan-200">
                            <img src="{{ $task->component?->image_url ?? asset('images/logo_hpk.webp') }}" class="w-12 h-12 rounded-lg object-cover border border-slate-200 bg-white flex-shrink-0">
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-slate-900 text-xs block truncate">{{ $task->component?->name }}</span>
                                <span class="font-mono text-[10px] font-semibold text-amber-900 bg-amber-100/70 px-1.5 py-0.2 rounded inline-block">
                                    {{ $task->component?->part_number }}
                                </span>
                                <div class="text-[11px] font-black text-slate-900 mt-1">
                                    Qty: {{ number_format($task->quantity_requested, 0) }} {{ $task->component?->uom }}
                                </div>
                            </div>
                        </div>

                        <!-- Action Button Berdasarkan Status (Siap Diambil vs Menuju Pengaju) -->
                        <div class="pt-2 border-t border-cyan-200/60 flex items-center justify-between">
                            <a href="{{ route('work-requests.show', $task) }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium">
                                Detail Tiket
                            </a>
                            <form method="POST" action="{{ route('work-station-supplies.quick-action') }}">
                                @csrf
                                <input type="hidden" name="work_request_id" value="{{ $task->id }}">
                                <input type="hidden" name="action_type" value="process">
                                <input type="hidden" name="target_status" value="{{ $task->getNextStatusLabel() }}">
                                <input type="hidden" name="process_qty" value="{{ $task->quantity_requested }}">

                                @if(stripos($task->status, 'Menuju Pengaju') !== false)
                                <button type="submit" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-check text-[11px]"></i>
                                    <span>Konfirmasi Diterima & Masuk Stok</span>
                                </button>
                                @else
                                <button type="submit" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5">
                                    <i class="fa-solid fa-dolly text-[11px]"></i>
                                    <span>Antar ke Pengaju</span>
                                </button>
                                @endif
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full py-12 text-center text-slate-400">
                        <i class="fa-solid fa-dolly text-3xl mb-2 text-slate-300 block"></i>
                        Tidak ada barang yang menunggu distribusi atau serah terima.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- TAB 4: RIWAYAT & LOG AKTIVITAS -->
            <div x-show="activeTab === 'history'" class="p-5 space-y-6">
                <!-- Selesai Hari Ini -->
                <div>
                    <h4 class="font-extrabold text-sm text-slate-900 mb-3">Tiket Selesai & Diterima di Gudang (Closed)</h4>
                    <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">No. WRI</th>
                                    <th class="py-2.5 px-3">Komponen</th>
                                    <th class="py-2.5 px-3 text-center">Jumlah Diterima</th>
                                    <th class="py-2.5 px-3">Target Slot Rak</th>
                                    <th class="py-2.5 px-3">Waktu Penerimaan</th>
                                    <th class="py-2.5 px-3">Penerima</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($tasksHistory as $hist)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900">
                                        <a href="{{ route('work-requests.show', $hist) }}" class="hover:text-blue-600">
                                            {{ $hist->wri_number }}
                                        </a>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="font-bold text-slate-800 block truncate max-w-xs">{{ $hist->component?->name }}</span>
                                        <span class="font-mono text-[10px] text-amber-800">{{ $hist->component?->part_number }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-extrabold text-emerald-700">
                                        +{{ number_format($hist->quantity_received ?: $hist->quantity_requested, 0) }} {{ $hist->component?->uom }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono font-semibold text-blue-900">
                                        {{ $hist->target_specific_location_code }}
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-500">
                                        {{ $hist->received_at ? $hist->received_at->format('d M Y, H:i') : $hist->updated_at->format('d M Y, H:i') }}
                                    </td>
                                    <td class="py-2.5 px-3 font-medium text-slate-700">
                                        {{ $hist->receivedBy?->name ?? 'Gudang' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400 italic">
                                        Belum ada riwayat serah terima yang tercatat.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Log Aktivitas Lapangan Terkini -->
                <div>
                    <h4 class="font-extrabold text-sm text-slate-900 mb-3">Audit Trail & Log Pergerakan Material Handling</h4>
                    <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">Waktu</th>
                                    <th class="py-2.5 px-3">Tiket & Komponen</th>
                                    <th class="py-2.5 px-3">Status / Pergerakan</th>
                                    <th class="py-2.5 px-3 text-center">Qty</th>
                                    <th class="py-2.5 px-3">Operator</th>
                                    <th class="py-2.5 px-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($recentLogs as $log)
                                <tr class="hover:bg-slate-50/70 transition {{ $log->action_type === 'defect' ? 'bg-rose-50/60' : '' }}">
                                    <td class="py-2.5 px-3 font-mono text-[10px] text-slate-400">
                                        {{ $log->created_at->format('d/m H:i') }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <a href="{{ route('work-requests.show', $log->workRequest) }}" class="font-mono font-bold text-slate-900 hover:text-blue-600 block">
                                            {{ $log->workRequest?->wri_number }}
                                        </a>
                                        <span class="text-[11px] text-slate-500 truncate block max-w-xs">{{ $log->workRequest?->component?->name }}</span>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if($log->action_type === 'defect')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $log->status_label }}
                                            </span>
                                        @else
                                            <span class="font-semibold text-slate-800">{{ $log->status_label }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-extrabold {{ $log->action_type === 'defect' ? 'text-rose-600' : 'text-slate-900' }}">
                                        {{ number_format($log->quantity, 0) }}
                                    </td>
                                    <td class="py-2.5 px-3 font-medium text-slate-700">
                                        {{ $log->operator_name ?: '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-[11px] text-slate-500">
                                        {{ $log->notes ?: '-' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400 italic">
                                        Belum ada aktivitas baru.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. SCANNER QR & BARCODE MODAL KHUSUS MATERIAL HANDLING -->
        <div x-show="mhScannerOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
            
            <div @click.away="closeMhScanner()" class="bg-white rounded-3xl shadow-2xl max-w-xl w-full max-h-[92vh] overflow-y-auto border border-slate-200 transition-all">
                <!-- Modal Header -->
                <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between rounded-t-3xl">
                    <div class="flex items-center space-x-2.5">
                        <div class="p-2 bg-amber-400 text-slate-950 rounded-xl font-bold">
                            <i class="fa-solid fa-qrcode text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-white">Scanner Tiket WRI Lapangan</h3>
                            <p class="text-[11px] text-slate-400">Scan QR Code tiket pada pallet / part untuk eksekusi cepat MH</p>
                        </div>
                    </div>
                    <button type="button" @click="closeMhScanner()" class="text-slate-400 hover:text-white p-1 rounded-xl">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 space-y-4">
                    <!-- Camera Viewport -->
                    <div class="relative bg-slate-950 rounded-2xl overflow-hidden min-h-[220px] flex items-center justify-center border border-slate-800">
                        <div id="mh-scanner-reader" class="w-full"></div>
                        <div x-show="isScanningMh" class="absolute inset-x-8 top-1/2 -translate-y-1/2 h-1 bg-amber-400 shadow-[0_0_8px_#fbbf24] animate-pulse pointer-events-none"></div>
                    </div>

                    <!-- Manual Input / Barcode Scanner Gun -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Ketik / Gun Barcode Scanner:
                        </label>
                        <div class="flex space-x-2">
                            <input type="text" 
                                   x-model="scanQuery" 
                                   @keydown.enter.prevent="performLookup(scanQuery)"
                                   placeholder="Contoh: WRI/Gudang/20261013/1/... atau kode part" 
                                   class="flex-1 px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold">
                            <button type="button" 
                                    @click="performLookup(scanQuery)" 
                                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition">
                                Cari Tiket
                            </button>
                        </div>
                    </div>

                    <!-- Scan Error -->
                    <div x-show="scanErrorMessage" style="display: none;" class="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl font-bold" x-text="scanErrorMessage"></div>

                    <!-- Scan Result Card & Fast Action Form -->
                    <div x-show="scannedTicket" style="display: none;" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-xs font-black text-slate-900 block" x-text="scannedTicket?.wri_number"></span>
                                <span class="text-xs font-bold text-slate-800 block mt-0.5" x-text="scannedTicket?.component_name"></span>
                                <span class="font-mono text-[10px] text-amber-900 bg-amber-100 px-1.5 py-0.2 rounded mt-0.5 inline-block" x-text="scannedTicket?.part_number"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-black text-slate-900 block" x-text="scannedTicket?.quantity_requested + ' ' + (scannedTicket?.uom || '')"></span>
                                <span class="text-[10px] text-slate-500 block" x-text="'Slot: ' + (scannedTicket?.target_location || '-')"></span>
                            </div>
                        </div>

                        <div class="p-2.5 bg-white rounded-xl border border-slate-200 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 text-[10px] uppercase font-bold">Status Saat Ini:</span>
                                <span class="font-bold text-slate-900" x-text="scannedTicket?.status"></span>
                            </div>
                            <div class="flex items-center justify-between mt-1 pt-1 border-t border-slate-100">
                                <span class="text-indigo-600 text-[10px] uppercase font-bold">Langkah Berikutnya:</span>
                                <span class="font-extrabold text-indigo-700" x-text="scannedTicket?.next_status"></span>
                            </div>
                        </div>

                        <!-- Fast Execute Button -->
                        <form method="POST" action="{{ route('work-station-supplies.quick-action') }}" class="pt-2">
                            @csrf
                            <input type="hidden" name="work_request_id" :value="scannedTicket?.id">
                            <input type="hidden" name="action_type" value="process">
                            <input type="hidden" name="target_status" :value="scannedTicket?.next_status">
                            <input type="hidden" name="process_qty" :value="scannedTicket?.quantity_requested">

                            <div class="flex items-center justify-between space-x-2">
                                <a :href="scannedTicket?.show_url" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition">
                                    Buka Detail Tiket &rarr;
                                </a>
                                <button type="submit" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs rounded-xl shadow-md transition flex items-center justify-center space-x-1.5">
                                    <i class="fa-solid fa-bolt text-xs"></i>
                                    <span x-text="'Eksekusi: ' + scannedTicket?.next_status"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function materialHandlingCenter() {
            return {
                activeTab: 'pickup',
                mhScannerOpen: false,
                isScanningMh: false,
                scanQuery: '',
                scannedTicket: null,
                scanErrorMessage: null,
                html5QrMh: null,

                openMhScanner() {
                    this.mhScannerOpen = true;
                    this.scannedTicket = null;
                    this.scanErrorMessage = null;
                    this.scanQuery = '';
                    this.$nextTick(() => {
                        this.startCameraMh();
                    });
                },

                closeMhScanner() {
                    this.stopCameraMh();
                    this.mhScannerOpen = false;
                },

                startCameraMh() {
                    const el = document.getElementById('mh-scanner-reader');
                    if (!el || typeof Html5Qrcode === 'undefined') return;

                    this.html5QrMh = new Html5Qrcode('mh-scanner-reader');
                    const config = { fps: 10, qrbox: { width: 220, height: 220 } };

                    this.html5QrMh.start(
                        { facingMode: 'environment' },
                        config,
                        (decodedText) => {
                            this.isScanningMh = true;
                            this.performLookup(decodedText);
                        },
                        (error) => {}
                    ).catch(err => {
                        console.warn('Camera start issue:', err);
                    });
                },

                stopCameraMh() {
                    if (this.html5QrMh && this.html5QrMh.isScanning) {
                        this.html5QrMh.stop().catch(e => console.error(e));
                    }
                },

                async performLookup(code) {
                    if (!code || !code.trim()) return;
                    this.scanErrorMessage = null;

                    try {
                        const res = await fetch(`{{ route('work-station-supplies.scan-lookup') }}?code=${encodeURIComponent(code.trim())}`);
                        const data = await res.json();

                        if (data.success && data.work_request) {
                            this.scannedTicket = data.work_request;
                        } else {
                            this.scannedTicket = null;
                            this.scanErrorMessage = data.message || 'Tiket WRI tidak ditemukan.';
                        }
                    } catch (e) {
                        this.scanErrorMessage = 'Terjadi kesalahan jaringan saat memindai kode.';
                    }
                }
            };
        }
    </script>
</x-app-layout>

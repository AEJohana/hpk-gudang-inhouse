<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2.5 sm:gap-4 w-full">
            <!-- Left: Back Button + Icon Badge + Title + Digital Clock (Judul Saja Tanpa Deskripsi) -->
            <div class="flex items-center space-x-2 sm:space-x-3.5 min-w-0">
                <a href="{{ route('dashboard') }}" 
                   class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200/80 flex items-center justify-center text-slate-700 transition shadow-2xs shrink-0 cursor-pointer" 
                   title="Kembali ke Beranda">
                    <i class="fa-solid fa-arrow-left text-sm text-slate-600"></i>
                </a>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-cyan-50 border border-cyan-200/80 flex items-center justify-center text-cyan-700 shadow-2xs shrink-0">
                    <i class="fa-solid fa-truck-ramp-box text-base sm:text-lg"></i>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-3 truncate">
                    <h2 class="font-extrabold text-base sm:text-xl text-[#0a2342] tracking-tight leading-tight truncate">
                        Material Handling
                    </h2>
                    <!-- Jam Digital Ringan Kotak (Sesuai Desain Legacy) -->
                    <div class="hidden sm:inline-flex items-center gap-1.5 px-2 sm:px-2.5 py-0.5 rounded-lg bg-white border border-slate-200/80 shadow-2xs text-[11px] font-bold text-slate-600 font-mono shrink-0" x-data="{ time: new Date().toLocaleTimeString('id-ID') }" x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID'), 1000)">
                        <i class="fa-regular fa-clock text-slate-400 text-[10px]"></i>
                        <span x-text="time">--:--:--</span>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons (Anchored to Far Right) -->
            <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0 ml-auto">
                <button type="button" 
                        @click="$dispatch('open-mh-scanner')" 
                        class="px-3.5 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center text-center cursor-pointer whitespace-nowrap">
                    <i class="fa-solid fa-qrcode me-1 text-xs"></i>
                    <span>Scan QR</span>
                </button>
                <a href="{{ route('work-requests.index') }}" class="px-3 py-2 sm:px-3.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center text-center whitespace-nowrap">
                    <i class="fa-solid fa-list-check me-1.5 text-xs"></i>
                    <span class="hidden sm:inline">Semua WRI</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
         x-data="taskGudangHandler()" 
         @open-mh-scanner.window="openMhScanner()">

        <!-- 1. FLOATING NAVIGATION PANEL (2 TAB UTAMA PERSIS SISTEM LAMA) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-2 flex items-center justify-between gap-2 w-full">
            <div class="flex items-center gap-1.5 sm:gap-2 flex-1 min-w-0">
                <button type="button" 
                        @click="mainTab = 'transfer'" 
                        class="flex-1 sm:flex-initial px-3 sm:px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-1.5 sm:gap-2 shadow-xs cursor-pointer"
                        :class="mainTab === 'transfer' ? 'bg-[#0f172a] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    <i class="fa-solid fa-right-left"></i>
                    <span class="truncate">Antar Mesin</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                          :class="mainTab === 'transfer' ? 'bg-amber-400 text-slate-950' : 'bg-slate-200 text-slate-700'">
                        {{ count($transferWaiting) }}
                    </span>
                </button>

                <button type="button" 
                        @click="mainTab = 'final'" 
                        class="flex-1 sm:flex-initial px-3 sm:px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-1.5 sm:gap-2 shadow-xs cursor-pointer"
                        :class="mainTab === 'final' ? 'bg-[#0f172a] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    <i class="fa-solid fa-box-open"></i>
                    <span class="truncate">Siap Diambil</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                          :class="mainTab === 'final' ? 'bg-emerald-400 text-slate-950' : 'bg-slate-200 text-slate-700'">
                        {{ count($finalWaiting) }}
                    </span>
                </button>
            </div>

            <div class="flex items-center shrink-0">
                <button type="button" 
                        onclick="window.location.reload()" 
                        title="Segarkan Data"
                        class="px-3 sm:px-3.5 py-2 sm:py-2.5 rounded-xl bg-teal-50 border border-teal-200 text-teal-700 hover:bg-teal-100 font-bold text-xs transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-rotate"></i>
                    <span class="hidden md:inline">Refresh Data</span>
                </button>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 1: ANTAR MESIN (TRANSFER ANTAR STASIUN)              -->
        <!-- ======================================================== -->
        <div x-show="mainTab === 'transfer'" class="space-y-4">
            <!-- Sub-tab Navigation (3 Sub-Tab) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="border-b border-slate-200 px-4 pt-3 bg-slate-50/60 flex items-center space-x-2 overflow-x-auto">
                    <button type="button" 
                            @click="transferSubTab = 'waiting'" 
                            class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                            :class="transferSubTab === 'waiting' ? 'border-amber-500 text-amber-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
                        <i class="fa-solid fa-list-check text-amber-500"></i>
                        <span>Menunggu Ambil</span>
                        <span class="px-2 py-0.2 rounded-full text-[10px] bg-amber-100 text-amber-900 font-extrabold">{{ count($transferWaiting) }}</span>
                    </button>

                    <button type="button" 
                            @click="transferSubTab = 'transit'" 
                            class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                            :class="transferSubTab === 'transit' ? 'border-sky-500 text-sky-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
                        <i class="fa-solid fa-truck-fast text-sky-500"></i>
                        <span>Dalam Perjalanan</span>
                        <span class="px-2 py-0.2 rounded-full text-[10px] bg-sky-100 text-sky-900 font-extrabold">{{ count($transferTransit) }}</span>
                    </button>

                    <button type="button" 
                            @click="transferSubTab = 'history'" 
                            class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                            :class="transferSubTab === 'history' ? 'border-slate-500 text-slate-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
                        <i class="fa-solid fa-clock-rotate-left text-slate-400"></i>
                        <span>Riwayat Transfer</span>
                        <span class="px-2 py-0.2 rounded-full text-[10px] bg-slate-100 text-slate-700 font-extrabold">{{ count($transferHistory) }}</span>
                    </button>
                </div>

                <!-- Filter Panel Antar Mesin (Grid 2x2 Ramping di Mobile/Tablet, 1 Baris di Desktop) -->
                <div class="p-3 bg-white border-b border-slate-100">
                    <div class="grid grid-cols-2 md:flex md:items-center gap-2 w-full">
                        <select x-model="filterDate" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5 w-full md:w-auto">
                            <option value="all">Semua Tgl</option>
                            <option value="today">Hari Ini</option>
                            <option value="last3">3 Hari</option>
                            <option value="week">Minggu Ini</option>
                        </select>

                        <select x-model="filterOrigin" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5 w-full md:w-auto">
                            <option value="all">Semua Asal</option>
                            @foreach($filterOrigins as $orig)
                            <option value="{{ $orig }}">{{ $orig }}</option>
                            @endforeach
                        </select>

                        <select x-model="filterDestination" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5 w-full md:w-auto">
                            <option value="all">Semua Tujuan</option>
                            @foreach($filterDestinations as $dest)
                            <option value="{{ $dest }}">{{ $dest }}</option>
                            @endforeach
                        </select>

                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Cari No WR / Komponen..." 
                               class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5 px-3 w-full md:w-56 md:ml-auto">
                    </div>
                </div>

                <!-- SUB-TAB 1: MENUNGGU AMBIL (ANTAR MESIN) -->
                <div x-show="transferSubTab === 'waiting'">
                    <!-- Mobile Card List View (< md screens) -->
                    <div class="block md:hidden p-3.5 space-y-3">
                        @forelse($transferWaiting as $t)
                        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3"
                             x-show="matchesFilter('{{ $t['tgl_raw'] }}', '{{ $t['origin'] }}', '{{ $t['destination'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-bold text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ $t['wri_number'] }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                    Menunggu Ambil
                                </span>
                            </div>
                            <div>
                                <h4 class="font-bold text-indigo-900 text-sm">{{ $t['component_name'] }}</h4>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">Kode: {{ $t['part_number'] }} | Dim: {{ $t['dimension'] }}</div>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-location-dot text-rose-500 me-1.5"></i>Asal:</span>
                                    <span class="font-bold text-slate-800">{{ $t['origin'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-bullseye text-blue-500 me-1.5"></i>Tujuan:</span>
                                    <span class="font-bold text-blue-700">{{ $t['destination'] }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                    <span class="text-slate-500">Jumlah:</span>
                                    <span class="font-extrabold text-sm text-slate-900">{{ $t['qty'] }} <span class="text-xs font-normal text-slate-500">pcs</span></span>
                                </div>
                            </div>
                            <button type="button" 
                                    @click="openActionModal({{ json_encode($t) }})"
                                    class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 active:scale-98 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center space-x-1.5 cursor-pointer touch-target">
                                <i class="fa-solid fa-qrcode text-sm"></i>
                                <span>Ambil Material (Pickup)</span>
                            </button>
                        </div>
                        @empty
                        <div class="py-10 text-center text-slate-400 italic bg-white rounded-2xl border border-slate-200">
                            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <p class="font-bold text-slate-700 text-xs">Beres! Semua komponen antar stasiun sudah di-pickup.</p>
                        </div>
                        @endforelse
                    </div>

                    <!-- Desktop Table View (>= md screens) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-4">No WR / Tanggal</th>
                                    <th class="py-3 px-4">Komponen & Dimensi</th>
                                    <th class="py-3 px-4">Tujuan Mesin</th>
                                    <th class="py-3 px-4">Status & Qty</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($transferWaiting as $t)
                                <tr class="hover:bg-slate-50/80 transition"
                                    x-show="matchesFilter('{{ $t['tgl_raw'] }}', '{{ $t['origin'] }}', '{{ $t['destination'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-black text-slate-900 text-xs">{{ $t['wri_number'] }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fa-regular fa-calendar me-1"></i>{{ $t['tgl'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-indigo-700 text-xs">{{ $t['component_name'] }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">Kode: {{ $t['part_number'] }} | Dim: {{ $t['dimension'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800"><i class="fa-solid fa-location-dot text-rose-500 me-1"></i> Asal: {{ $t['origin'] }}</div>
                                        <div class="text-[10px] text-slate-500 mt-0.5"><i class="fa-solid fa-bullseye text-blue-500 me-1"></i> Tujuan: {{ $t['destination'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                            Menunggu Ambil
                                        </span>
                                        <div class="text-xs font-black text-slate-900 mt-1">Qty: <b>{{ $t['qty'] }}</b> pcs</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" 
                                                @click="openActionModal({{ json_encode($t) }})"
                                                class="inline-flex items-center justify-center px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-full shadow-xs transition cursor-pointer">
                                            <i class="fa-solid fa-qrcode me-1.5 text-[10px]"></i> Ambil
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 italic">
                                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                        <p class="font-bold text-slate-700 text-xs">Beres! Semua komponen antar stasiun sudah di-pickup.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUB-TAB 2: DALAM PERJALANAN (ANTAR MESIN) -->
                <div x-show="transferSubTab === 'transit'">
                    <!-- Mobile Card List View (< md screens) -->
                    <div class="block md:hidden p-3.5 space-y-3">
                        @forelse($transferTransit as $t)
                        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3"
                             x-show="matchesFilter('{{ $t['tgl_raw'] }}', '{{ $t['origin'] }}', '{{ $t['destination'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-bold text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ $t['wri_number'] }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-900 border border-sky-300">
                                    Sedang Dibawa MH
                                </span>
                            </div>
                            <div>
                                <h4 class="font-bold text-indigo-900 text-sm">{{ $t['component_name'] }}</h4>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">Kode: {{ $t['part_number'] }} | Dim: {{ $t['dimension'] }}</div>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-location-dot text-rose-500 me-1.5"></i>Asal:</span>
                                    <span class="font-bold text-slate-800">{{ $t['origin'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-bullseye text-blue-500 me-1.5"></i>Tujuan:</span>
                                    <span class="font-bold text-blue-700">{{ $t['destination'] }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                    <span class="text-slate-500">Jumlah:</span>
                                    <span class="font-extrabold text-sm text-slate-900">{{ $t['qty'] }} <span class="text-xs font-normal text-slate-500">pcs</span></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 bg-sky-50 text-sky-700 rounded-full font-bold">
                                    <i class="fa-solid fa-truck-fast text-sky-600 me-1"></i> Perjalanan
                                </span>
                                <span><i class="fa-regular fa-clock me-1 text-slate-400"></i> Diambil: <b>{{ $t['pickup_time'] }}</b></span>
                            </div>
                        </div>
                        @empty
                        <div class="py-10 text-center text-slate-400 italic bg-white rounded-2xl border border-slate-200">
                            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                <i class="fa-solid fa-truck"></i>
                            </div>
                            <p class="font-bold text-slate-600 text-xs">Kosong. Tidak ada barang dalam perjalanan antar mesin.</p>
                        </div>
                        @endforelse
                    </div>

                    <!-- Desktop Table View (>= md screens) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-4">No WR / Tanggal</th>
                                    <th class="py-3 px-4">Komponen & Dimensi</th>
                                    <th class="py-3 px-4">Rute Transfer</th>
                                    <th class="py-3 px-4">Status & Qty</th>
                                    <th class="py-3 px-4 text-right">Info Logistik</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($transferTransit as $t)
                                <tr class="hover:bg-slate-50/80 transition"
                                    x-show="matchesFilter('{{ $t['tgl_raw'] }}', '{{ $t['origin'] }}', '{{ $t['destination'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-black text-slate-900 text-xs">{{ $t['wri_number'] }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fa-regular fa-calendar me-1"></i>{{ $t['tgl'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-indigo-700 text-xs">{{ $t['component_name'] }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">Kode: {{ $t['part_number'] }} | Dim: {{ $t['dimension'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800"><i class="fa-solid fa-location-dot text-rose-500 me-1"></i> Asal: {{ $t['origin'] }}</div>
                                        <div class="text-[10px] text-slate-500 mt-0.5"><i class="fa-solid fa-bullseye text-blue-500 me-1"></i> Tujuan: {{ $t['destination'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-900 border border-sky-300">
                                            Sedang Dibawa MH
                                        </span>
                                        <div class="text-xs font-black text-slate-900 mt-1">Qty: <b>{{ $t['qty'] }}</b> pcs</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-full text-[10px] font-bold">
                                            <i class="fa-solid fa-truck-fast text-sky-600 me-1"></i> Perjalanan
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-1"><i class="fa-regular fa-clock me-1"></i> Diambil: <b>{{ $t['pickup_time'] }}</b></div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 italic">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                            <i class="fa-solid fa-truck"></i>
                                        </div>
                                        <p class="font-bold text-slate-600 text-xs">Kosong. Tidak ada barang dalam perjalanan antar mesin.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUB-TAB 3: RIWAYAT TRANSFER (ANTAR MESIN) -->
                <div x-show="transferSubTab === 'history'">
                    <!-- Mobile Card List View (< md screens) -->
                    <div class="block md:hidden p-3.5 space-y-3">
                        @forelse($transferHistory as $t)
                        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3"
                             x-show="matchesFilter('{{ $t['tgl_raw'] }}', '{{ $t['origin'] }}', '{{ $t['destination'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-bold text-xs text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ $t['wri_number'] }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    {{ $t['status'] }}
                                </span>
                            </div>
                            <div>
                                <h4 class="font-bold text-indigo-900 text-sm">{{ $t['component_name'] }}</h4>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">Kode: {{ $t['part_number'] }} | Dim: {{ $t['dimension'] }}</div>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-location-dot text-rose-500 me-1.5"></i>Asal:</span>
                                    <span class="font-medium text-slate-700">{{ $t['origin'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-bullseye text-blue-500 me-1.5"></i>Tujuan:</span>
                                    <span class="font-medium text-slate-700">{{ $t['destination'] }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                    <span class="text-slate-500">Jumlah:</span>
                                    <span class="font-bold text-slate-900">{{ $t['qty'] }} pcs</span>
                                </div>
                            </div>
                            <button type="button" 
                                    @click="openDetailModal({{ json_encode($t) }})"
                                    class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center justify-center space-x-1.5 cursor-pointer touch-target">
                                <i class="fa-solid fa-circle-info text-blue-500"></i>
                                <span>Lihat Rincian Alur</span>
                            </button>
                        </div>
                        @empty
                        <div class="py-10 text-center text-slate-400 italic bg-white rounded-2xl border border-slate-200">
                            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <p class="font-bold text-slate-600 text-xs">Belum ada riwayat transfer antar mesin yang tercatat.</p>
                        </div>
                        @endforelse
                    </div>

                    <!-- Desktop Table View (>= md screens) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-4">No WR / Tanggal</th>
                                    <th class="py-3 px-4">Komponen & Dimensi</th>
                                    <th class="py-3 px-4">Rute Transfer</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($transferHistory as $t)
                                <tr class="hover:bg-slate-50/80 transition"
                                    x-show="matchesFilter('{{ $t['tgl_raw'] }}', '{{ $t['origin'] }}', '{{ $t['destination'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-bold text-slate-800 text-xs">{{ $t['wri_number'] }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fa-regular fa-calendar me-1"></i>{{ $t['tgl'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-indigo-700 text-xs">{{ $t['component_name'] }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">Qty: <b>{{ $t['qty'] }}</b> | Dim: {{ $t['dimension'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-medium text-slate-700"><i class="fa-solid fa-location-dot text-rose-500 me-1"></i> Asal: {{ $t['origin'] }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fa-solid fa-bullseye text-blue-500 me-1"></i> Tujuan: {{ $t['destination'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            {{ $t['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" 
                                                @click="openDetailModal({{ json_encode($t) }})"
                                                class="inline-flex items-center px-3 py-1 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-100 font-bold text-xs shadow-xs transition">
                                            <i class="fa-solid fa-circle-info me-1 text-blue-500"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 italic">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </div>
                                        <p class="font-bold text-slate-600 text-xs">Belum ada riwayat transfer antar mesin yang tercatat.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 2: SIAP DIAMBIL (DISTRIBUSI KE PENGAJU / GUDANG)     -->
        <!-- ======================================================== -->
        <div x-show="mainTab === 'final'" class="space-y-4">
            <!-- Sub-tab Navigation (3 Sub-Tab) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="border-b border-slate-200 px-4 pt-3 bg-slate-50/60 flex items-center space-x-2 overflow-x-auto">
                    <button type="button" 
                            @click="finalSubTab = 'waiting'" 
                            class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                            :class="finalSubTab === 'waiting' ? 'border-emerald-600 text-emerald-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
                        <i class="fa-solid fa-box-open text-emerald-600"></i>
                        <span>Siap Diambil</span>
                        <span class="px-2 py-0.2 rounded-full text-[10px] bg-emerald-100 text-emerald-900 font-extrabold">{{ count($finalWaiting) }}</span>
                    </button>

                    <button type="button" 
                            @click="finalSubTab = 'transit'" 
                            class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                            :class="finalSubTab === 'transit' ? 'border-sky-500 text-sky-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
                        <i class="fa-solid fa-truck-fast text-sky-500"></i>
                        <span>Dalam Perjalanan</span>
                        <span class="px-2 py-0.2 rounded-full text-[10px] bg-sky-100 text-sky-900 font-extrabold">{{ count($finalTransit) }}</span>
                    </button>

                    <button type="button" 
                            @click="finalSubTab = 'history'" 
                            class="pb-3 px-3 text-xs font-bold transition border-b-2 flex items-center space-x-2 whitespace-nowrap"
                            :class="finalSubTab === 'history' ? 'border-slate-500 text-slate-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
                        <i class="fa-solid fa-clock-rotate-left text-slate-400"></i>
                        <span>Riwayat Selesai</span>
                        <span class="px-2 py-0.2 rounded-full text-[10px] bg-slate-100 text-slate-700 font-extrabold">{{ count($finalHistory) }}</span>
                    </button>
                </div>

                <!-- Filter Panel Siap Diambil (Ramping Tanpa Badge Redundan) -->
                <div class="p-3 bg-white border-b border-slate-100">
                    <div class="grid grid-cols-2 md:flex md:items-center gap-2 w-full">
                        <select x-model="filterDate" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5 w-full md:w-auto">
                            <option value="all">Semua Tgl</option>
                            <option value="today">Hari Ini</option>
                            <option value="last3">3 Hari</option>
                            <option value="week">Minggu Ini</option>
                        </select>

                        <select x-model="filterFinalLocation" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5 w-full md:w-auto">
                            <option value="all">Semua Lokasi Selesai</option>
                            @foreach($filterFinalLocations as $floc)
                            <option value="{{ $floc }}">{{ $floc }}</option>
                            @endforeach
                        </select>

                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Cari No WR / Komponen..." 
                               class="col-span-2 md:col-span-1 text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5 px-3 w-full md:w-56 md:ml-auto">
                    </div>
                </div>

                <!-- SUB-TAB 1: SIAP DIAMBIL (FINAL) -->
                <div x-show="finalSubTab === 'waiting'">
                    <!-- Mobile Card List View (< md screens) -->
                    <div class="block md:hidden p-3.5 space-y-3">
                        @forelse($finalWaiting as $t)
                        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3"
                             x-show="matchesFilterFinal('{{ $t['tgl_raw'] }}', '{{ $t['location'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-bold text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ $t['wri_number'] }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    Siap Distribusi
                                </span>
                            </div>
                            <div>
                                <h4 class="font-bold text-indigo-900 text-sm">{{ $t['component_name'] }}</h4>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">Kode: {{ $t['part_number'] }} | Dim: {{ $t['dimension'] }}</div>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-map-pin text-emerald-600 me-1.5"></i>Selesai di:</span>
                                    <span class="font-bold text-slate-800">{{ $t['location'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-warehouse text-teal-600 me-1.5"></i>Tujuan:</span>
                                    <span class="font-bold text-teal-700">{{ $t['destination'] }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                    <span class="text-slate-500">Jumlah:</span>
                                    <span class="font-extrabold text-sm text-slate-900">{{ $t['qty'] }} <span class="text-xs font-normal text-slate-500">pcs</span></span>
                                </div>
                            </div>
                            <button type="button" 
                                    @click="openActionModal({{ json_encode($t) }})"
                                    class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-98 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center space-x-1.5 cursor-pointer touch-target">
                                <i class="fa-solid fa-truck-fast text-sm"></i>
                                <span>Ambil Menuju Pengaju</span>
                            </button>
                        </div>
                        @empty
                        <div class="py-10 text-center text-slate-400 italic bg-white rounded-2xl border border-slate-200">
                            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                <i class="fa-solid fa-check-double"></i>
                            </div>
                            <p class="font-bold text-slate-600 text-xs">Aman! Tidak ada barang menunggu pengambilan akhir ke gudang.</p>
                        </div>
                        @endforelse
                    </div>

                    <!-- Desktop Table View (>= md screens) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-4">No WR / Tanggal</th>
                                    <th class="py-3 px-4">Komponen & Dimensi</th>
                                    <th class="py-3 px-4">Lokasi Selesai</th>
                                    <th class="py-3 px-4">Status & Qty</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($finalWaiting as $t)
                                <tr class="hover:bg-slate-50/80 transition"
                                    x-show="matchesFilterFinal('{{ $t['tgl_raw'] }}', '{{ $t['location'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-black text-slate-900 text-xs">{{ $t['wri_number'] }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fa-regular fa-calendar me-1"></i>{{ $t['tgl'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-indigo-700 text-xs">{{ $t['component_name'] }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">Kode: {{ $t['part_number'] }} | Dim: {{ $t['dimension'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800"><i class="fa-solid fa-map-pin text-emerald-600 me-1"></i> Selesai di: {{ $t['location'] }}</div>
                                        <div class="text-[10px] text-slate-500 mt-0.5"><i class="fa-solid fa-warehouse text-teal-600 me-1"></i> Tujuan: {{ $t['destination'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                            Siap Distribusi
                                        </span>
                                        <div class="text-xs font-black text-slate-900 mt-1">Qty: <b>{{ $t['qty'] }}</b> pcs</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" 
                                                @click="openActionModal({{ json_encode($t) }})"
                                                class="inline-flex items-center justify-center px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-full shadow-xs transition cursor-pointer">
                                            <i class="fa-solid fa-truck-fast me-1.5 text-[10px]"></i> Ambil
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 italic">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                            <i class="fa-solid fa-check-double"></i>
                                        </div>
                                        <p class="font-bold text-slate-600 text-xs">Aman! Tidak ada barang menunggu pengambilan akhir ke gudang.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUB-TAB 2: DALAM PERJALANAN (FINAL KE PENGAJU) -->
                <div x-show="finalSubTab === 'transit'">
                    <!-- Mobile Card List View (< md screens) -->
                    <div class="block md:hidden p-3.5 space-y-3">
                        @forelse($finalTransit as $t)
                        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3"
                             x-show="matchesFilterFinal('{{ $t['tgl_raw'] }}', '{{ $t['location'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-bold text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ $t['wri_number'] }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-900 border border-sky-300">
                                    Perjalanan (Final)
                                </span>
                            </div>
                            <div>
                                <h4 class="font-bold text-indigo-900 text-sm">{{ $t['component_name'] }}</h4>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">Kode: {{ $t['part_number'] }}</div>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-location-dot text-rose-500 me-1.5"></i>Dari:</span>
                                    <span class="font-bold text-slate-800">{{ $t['location'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-bullseye text-blue-500 me-1.5"></i>Menuju:</span>
                                    <span class="font-bold text-blue-700">{{ $t['destination'] }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                    <span class="text-slate-500">Jumlah:</span>
                                    <span class="font-extrabold text-sm text-slate-900">{{ $t['qty'] }} pcs</span>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pt-2 border-t border-slate-100 text-[11px] text-slate-500 gap-1.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded-full font-bold self-start sm:self-auto">
                                    <i class="fa-solid fa-hourglass-half text-amber-500 me-1.5"></i> Menunggu Penerimaan {{ $t['destination'] }}
                                </span>
                                <span><i class="fa-regular fa-clock me-1 text-slate-400"></i> Diambil: <b>{{ $t['pickup_time'] }}</b></span>
                            </div>
                        </div>
                        @empty
                        <div class="py-10 text-center text-slate-400 italic bg-white rounded-2xl border border-slate-200">
                            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                <i class="fa-solid fa-truck"></i>
                            </div>
                            <p class="font-bold text-slate-600 text-xs">Kosong. Tidak ada barang dalam perjalanan ke pengaju/gudang.</p>
                        </div>
                        @endforelse
                    </div>

                    <!-- Desktop Table View (>= md screens) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-4">No WR / Tanggal</th>
                                    <th class="py-3 px-4">Komponen & Dimensi</th>
                                    <th class="py-3 px-4">Lokasi & Tujuan</th>
                                    <th class="py-3 px-4">Status & Qty</th>
                                    <th class="py-3 px-4 text-right">Status Penyerahan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($finalTransit as $t)
                                <tr class="hover:bg-slate-50/80 transition"
                                    x-show="matchesFilterFinal('{{ $t['tgl_raw'] }}', '{{ $t['location'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-black text-slate-900 text-xs">{{ $t['wri_number'] }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fa-regular fa-calendar me-1"></i>{{ $t['tgl'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-indigo-700 text-xs">{{ $t['component_name'] }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">Kode: {{ $t['part_number'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800"><i class="fa-solid fa-location-dot text-rose-500 me-1"></i> Dari: {{ $t['location'] }}</div>
                                        <div class="text-[10px] text-slate-500 mt-0.5"><i class="fa-solid fa-bullseye text-blue-500 me-1"></i> Menuju: {{ $t['destination'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-900 border border-sky-300">
                                            Perjalanan (Final)
                                        </span>
                                        <div class="text-xs font-black text-slate-900 mt-1">Qty: <b>{{ $t['qty'] }}</b> pcs</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <span class="inline-flex items-center px-3 py-1 bg-amber-50 text-amber-800 border border-amber-300 rounded-full text-[10px] font-bold">
                                            <i class="fa-solid fa-hourglass-half text-amber-500 me-1.5"></i> Menunggu Penerimaan {{ $t['destination'] }}
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-1"><i class="fa-regular fa-clock me-1"></i> Diambil: <b>{{ $t['pickup_time'] }}</b></div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 italic">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                            <i class="fa-solid fa-truck"></i>
                                        </div>
                                        <p class="font-bold text-slate-600 text-xs">Kosong. Tidak ada barang dalam perjalanan ke pengaju/gudang.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUB-TAB 3: RIWAYAT SELESAI (FINAL) -->
                <div x-show="finalSubTab === 'history'">
                    <!-- Mobile Card List View (< md screens) -->
                    <div class="block md:hidden p-3.5 space-y-3">
                        @forelse($finalHistory as $t)
                        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3"
                             x-show="matchesFilterFinal('{{ $t['tgl_raw'] }}', '{{ $t['location'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-bold text-xs text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ $t['wri_number'] }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    {{ $t['status'] }}
                                </span>
                            </div>
                            <div>
                                <h4 class="font-bold text-indigo-900 text-sm">{{ $t['component_name'] }}</h4>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">Qty: <b>{{ $t['qty'] }}</b> | Dim: {{ $t['dimension'] }}</div>
                            </div>
                            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-solid fa-warehouse text-emerald-600 me-1.5"></i>Penerima:</span>
                                    <span class="font-bold text-slate-800">{{ $t['destination'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fa-regular fa-clock text-slate-400 me-1.5"></i>Waktu:</span>
                                    <span class="text-slate-600 font-medium">Diterima {{ $t['received_time'] }}</span>
                                </div>
                            </div>
                            <button type="button" 
                                    @click="openDetailModal({{ json_encode($t) }})"
                                    class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center justify-center space-x-1.5 cursor-pointer touch-target">
                                <i class="fa-solid fa-circle-info text-emerald-600"></i>
                                <span>Lihat Rincian Selesai</span>
                            </button>
                        </div>
                        @empty
                        <div class="py-10 text-center text-slate-400 italic bg-white rounded-2xl border border-slate-200">
                            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <p class="font-bold text-slate-600 text-xs">Belum ada riwayat serah terima yang tercatat.</p>
                        </div>
                        @endforelse
                    </div>

                    <!-- Desktop Table View (>= md screens) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-4">No WR / Tanggal</th>
                                    <th class="py-3 px-4">Komponen & Dimensi</th>
                                    <th class="py-3 px-4">Penerima Akhir</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($finalHistory as $t)
                                <tr class="hover:bg-slate-50/80 transition"
                                    x-show="matchesFilterFinal('{{ $t['tgl_raw'] }}', '{{ $t['location'] }}', '{{ strtolower($t['wri_number'] . ' ' . $t['component_name']) }}')">
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-bold text-slate-800 text-xs">{{ $t['wri_number'] }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fa-regular fa-calendar me-1"></i>{{ $t['tgl'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-indigo-700 text-xs">{{ $t['component_name'] }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">Qty: <b>{{ $t['qty'] }}</b> | Dim: {{ $t['dimension'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800"><i class="fa-solid fa-warehouse text-emerald-600 me-1"></i> {{ $t['destination'] }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">Diterima: {{ $t['received_time'] }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            {{ $t['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button type="button" 
                                                @click="openDetailModal({{ json_encode($t) }})"
                                                class="inline-flex items-center px-3 py-1 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-100 font-bold text-xs shadow-xs transition">
                                            <i class="fa-solid fa-circle-info me-1 text-emerald-600"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 italic">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                                            <i class="fa-solid fa-circle-check"></i>
                                        </div>
                                        <p class="font-bold text-slate-600 text-xs">Belum ada riwayat serah terima yang tercatat.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL: KONFIRMASI AMBIL MATERIAL (PICKUP ACTION)         -->
        <!-- ======================================================== -->
        <div x-show="actionModalOpen" 
             style="display: none;" 
             data-lenis-prevent="true"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            <div @click.away="if (!actionCameraModalOpen) closeActionModal()"
                 :class="{ 'opacity-50 scale-[0.98] pointer-events-none': actionCameraModalOpen }"
                 class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200/80 transition-all text-slate-800 my-auto">
                <form method="POST" action="{{ route('work-station-supplies.quick-action') }}">
                    @csrf
                    <input type="hidden" name="work_request_id" :value="activeItem?.id">
                    <input type="hidden" name="action_type" value="process">
                    <input type="hidden" name="target_status" :value="activeItem?.target_status">
                    <input type="hidden" name="scanned_wri_code" :value="actionScannedCode">

                    <!-- Modal Header Bersih & Kompak -->
                    <div class="p-5 pb-3 border-b border-slate-100 relative">
                        <button type="button" @click="closeActionModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>

                        <div class="flex items-center space-x-3 pr-8">
                            <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200/70 flex items-center justify-center text-lg flex-shrink-0 shadow-2xs">
                                <i class="fa-solid fa-truck-ramp-box"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-extrabold text-slate-900 leading-tight truncate">Verifikasi Ambil MH</h3>
                                <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">Ambil komponen untuk dipindahkan ke tujuan</p>
                            </div>
                        </div>

                        <!-- Tiket & Info Komponen Ringkas -->
                        <div class="mt-3 p-2.5 bg-slate-50 border border-slate-200/70 rounded-2xl flex items-center justify-between text-xs">
                            <div class="min-w-0 flex-1 pr-2">
                                <div>
                                    <span class="text-[10px] font-mono font-black uppercase text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200/60 inline-block truncate max-w-full" x-text="activeItem?.wri_number"></span>
                                </div>
                                <div class="font-bold text-slate-800 text-xs truncate mt-1" x-text="activeItem?.component_name"></div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-[10px] text-slate-400 font-mono block" x-text="activeItem?.part_number"></span>
                                <template x-if="activeItem?.target_status">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200/80 inline-block mt-0.5" x-text="activeItem?.target_status"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Logistics Route Strip (Rute Penjemputan Kurir MH) -->
                        <div class="mt-2.5 p-2 bg-slate-50/90 border border-slate-200/70 rounded-xl flex items-center justify-between gap-1.5">
                            <!-- Titik Asal (Pickup Point) -->
                            <div class="min-w-0 flex-1 flex items-center space-x-1.5 px-2.5 py-1 bg-white border border-slate-200/80 rounded-lg shadow-2xs">
                                <i class="fa-solid fa-location-dot text-rose-500 text-[10px] flex-shrink-0"></i>
                                <div class="min-w-0 flex-1">
                                    <span class="text-[9px] text-slate-400 uppercase font-bold block leading-none">Asal</span>
                                    <span class="text-[11px] font-bold text-slate-800 truncate block mt-0.5" x-text="activeItem?.origin || activeItem?.location || '-'"></span>
                                </div>
                            </div>

                            <!-- Indikator Transisi Logistik Kurir -->
                            <div class="w-6 h-6 rounded-full bg-amber-100/90 border border-amber-200/80 text-amber-800 flex items-center justify-center flex-shrink-0 text-[10px] shadow-2xs" title="Kurir Material Handling">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>

                            <!-- Titik Tujuan (Drop/Delivery Point) -->
                            <div class="min-w-0 flex-1 flex items-center space-x-1.5 px-2.5 py-1 bg-blue-50/90 border border-blue-200/80 rounded-lg shadow-2xs">
                                <i class="fa-solid fa-bullseye text-blue-600 text-[10px] flex-shrink-0"></i>
                                <div class="min-w-0 flex-1">
                                    <span class="text-[9px] text-blue-500 uppercase font-bold block leading-none">Tujuan</span>
                                    <span class="text-[11px] font-extrabold text-blue-900 truncate block mt-0.5" x-text="activeItem?.destination || '-'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Body Kompak & Fungsional -->
                    <div class="p-5 pt-4 space-y-3.5 text-xs" x-data="{ hasDefectAction: false, defectQtyVal: null, defectReasonVal: '' }">
                        <!-- DOK VERIFIKASI SCAN QR TIKET (KAMERA HP / GUN SCANNER / FOTO QR) -->
                        <div class="rounded-2xl transition-all border p-3"
                             :class="actionScanVerified ? 'bg-emerald-50/80 border-emerald-300' : (isWrScanRequired ? 'bg-amber-50/90 border-amber-300 shadow-xs' : 'bg-slate-50 border-slate-200')">

                            <!-- Terverifikasi -->
                            <div x-show="actionScanVerified" class="flex items-center justify-between gap-2">
                                <div class="flex items-center space-x-2.5 min-w-0">
                                    <div class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs flex-shrink-0 shadow-xs">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-black text-emerald-950 flex flex-wrap items-center gap-1">
                                            <span>QR Tiket Terverifikasi</span>
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-900 text-[9px] font-mono break-all" x-text="actionScannedCode"></span>
                                        </div>
                                        <p class="text-[10px] text-emerald-700 font-medium">QR lembar tiket WRI valid & cocok.</p>
                                    </div>
                                </div>
                                <button type="button" 
                                        @click="resetActionScanVerification()" 
                                        class="text-[10px] text-emerald-700 hover:text-emerald-900 font-bold underline px-2 py-1 rounded hover:bg-emerald-100 transition cursor-pointer flex-shrink-0">
                                    Pindai Ulang
                                </button>
                            </div>

                            <!-- Belum Terverifikasi -->
                            <div x-show="!actionScanVerified" class="space-y-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center space-x-2 min-w-0">
                                        <div class="w-6 h-6 rounded-lg flex items-center justify-center text-xs flex-shrink-0 font-bold shadow-2xs"
                                             :class="isWrScanRequired ? 'bg-amber-500 text-slate-950' : 'bg-slate-800 text-amber-300'">
                                            <i class="fa-solid fa-qrcode"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-black uppercase tracking-wide"
                                                :class="isWrScanRequired ? 'text-amber-950' : 'text-slate-900'"
                                                x-text="isWrScanRequired ? 'Wajib Pindai QR Tiket Fisik' : 'Pindai QR Tiket Material Handling'"></h4>
                                            <p class="text-[10px] font-medium leading-tight"
                                               :class="isWrScanRequired ? 'text-amber-800' : 'text-slate-500'">
                                                Pindai QR lembar tiket WRI via kamera HP atau gun scanner.
                                            </p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider flex-shrink-0"
                                          :class="isWrScanRequired ? 'bg-amber-200 text-amber-900' : 'bg-indigo-100 text-indigo-800'"
                                          x-text="isWrScanRequired ? 'Terkunci' : 'Siaga'"></span>
                                </div>

                                <!-- Tombol Utama Pemindai Kamera QR -->
                                <button type="button" 
                                        @click="openActionCameraModal()" 
                                        class="w-full py-2.5 bg-[#0a2342] hover:bg-slate-800 active:scale-[0.99] text-white rounded-2xl text-xs font-black transition flex items-center justify-center space-x-2 shadow-md hover:shadow-lg cursor-pointer">
                                    <i class="fa-solid fa-camera text-amber-300 text-sm"></i>
                                    <span class="tracking-wide">Buka Kamera Pemindai QR</span>
                                    <span class="px-2 py-0.5 rounded-full bg-white/10 text-slate-200 text-[10px] font-normal font-sans">Kamera HP / Laptop</span>
                                </button>

                                <!-- Fallback Foto QR / Barcode Scanner Gun -->
                                <div class="flex items-center gap-2">
                                    <label class="flex-1 p-2 bg-white/90 hover:bg-slate-50 rounded-xl border border-dashed border-slate-300 flex items-center justify-center space-x-1.5 text-[10.5px] font-bold text-slate-700 cursor-pointer transition">
                                        <i class="fa-solid fa-image text-indigo-600"></i>
                                        <span>Foto / Pilih Gambar QR</span>
                                        <input type="file" accept="image/*" capture="environment" class="hidden" @change="scanActionQrFile($event)">
                                    </label>
                                    <div class="px-2.5 py-2 bg-white/90 rounded-xl border border-amber-200/90 flex items-center space-x-1.5 text-[10px] font-extrabold text-amber-900 shrink-0" title="Arahkan barcode scanner gun & tembak QR lembar kerja">
                                        <span class="relative flex h-2 w-2 shrink-0">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                        </span>
                                        <span>Gun Auto</span>
                                    </div>
                                </div>

                                <template x-if="actionScanErrorMessage">
                                    <div class="p-2 bg-rose-100 border border-rose-300 text-rose-900 rounded-xl text-[10px] font-bold flex items-center space-x-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-rose-600 shrink-0"></i>
                                        <span x-text="actionScanErrorMessage"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Hero Numeric Stepper -->
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <label class="font-extrabold text-slate-700 uppercase tracking-wider text-[11px]">
                                    Unit yang Diambil Kurir <span class="text-rose-500">*</span>
                                </label>
                                
                                <!-- Shortcut Button 'Semua' -->
                                <button type="button" 
                                        @click="setMaxPickupQty()" 
                                        class="px-2 py-0.5 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold rounded-lg text-[10px] border border-amber-200/80 transition flex items-center space-x-1">
                                    <span>Semua:</span>
                                    <strong x-text="activeItem?.qty"></strong>
                                    <span x-text="activeItem?.uom || 'PCS'">PCS</span>
                                </button>
                            </div>

                            <!-- Stepper Container (Touch-Friendly) -->
                            <div class="flex items-center rounded-2xl border-2 border-slate-200 bg-slate-50/70 focus-within:border-amber-500 focus-within:bg-white focus-within:ring-4 focus-within:ring-amber-500/10 transition-all p-1">
                                <button type="button" 
                                        @click="decrementPickupQty()" 
                                        :disabled="Number(pickupQty) <= 1"
                                        class="w-11 h-11 rounded-xl bg-white hover:bg-slate-100 active:bg-slate-200 border border-slate-200 text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-sm font-bold shadow-2xs transition">
                                    <i class="fa-solid fa-minus"></i>
                                </button>

                                <div class="flex-1 relative flex items-center justify-center">
                                    <input type="number" 
                                           name="process_qty" 
                                           x-model.number="pickupQty" 
                                           required 
                                           min="1" 
                                           :max="activeItem?.qty"
                                           @input="if(Number(pickupQty) > activeItem?.qty) pickupQty = activeItem?.qty"
                                           step="1" 
                                           class="w-full text-center text-2xl sm:text-3xl font-black text-slate-900 border-0 bg-transparent focus:ring-0 focus:outline-none p-0 py-1 font-mono">
                                </div>

                                <span class="text-xs font-extrabold text-slate-400 font-mono pr-2 select-none">PCS</span>

                                <button type="button" 
                                        @click="incrementPickupQty()" 
                                        :disabled="Number(pickupQty) >= activeItem?.qty"
                                        class="w-11 h-11 rounded-xl bg-white hover:bg-slate-100 active:bg-slate-200 border border-slate-200 text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-sm font-bold shadow-2xs transition">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>

                            <!-- Micro Status & Calculation -->
                            <div class="mt-1.5 flex items-center justify-between px-1 text-[11px] text-slate-500">
                                <span>Tersedia: <strong class="text-slate-800" x-text="activeItem?.qty + ' PCS'"></strong></span>
                                <template x-if="Number(pickupQty) < activeItem?.qty">
                                    <span class="text-amber-600 font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-clock-rotate-left text-[10px]"></i> Ambil Sebagian (Parsial)
                                    </span>
                                </template>
                                <template x-if="Number(pickupQty) >= activeItem?.qty">
                                    <span class="text-emerald-600 font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-check text-[10px]"></i> Ambil Penuh
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Smart Defect Toggle (Ramping & Opsional) -->
                        <div class="pt-1 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <button type="button" 
                                        @click="hasDefectAction = !hasDefectAction" 
                                        class="inline-flex items-center space-x-1.5 text-[11px] font-bold transition px-2.5 py-1 rounded-xl"
                                        :class="hasDefectAction ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'text-slate-600 hover:text-rose-600 hover:bg-rose-50 border border-dashed border-slate-300'">
                                    <i :class="hasDefectAction ? 'fa-solid fa-circle-xmark text-rose-600' : 'fa-solid fa-triangle-exclamation text-amber-500'"></i>
                                    <span x-text="hasDefectAction ? 'Tutup Laporan Cacat / Hilang' : '+ Ada Cacat / Hilang saat Pickup? (Opsional)'"></span>
                                </button>
                            </div>

                            <div x-show="hasDefectAction" 
                                 x-transition 
                                 class="mt-2 p-3 bg-rose-50/70 border border-rose-200 rounded-2xl space-y-2">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label class="block font-bold text-rose-900 text-[10px] uppercase tracking-wider mb-1">
                                            Jumlah Cacat / Hilang
                                        </label>
                                        <input type="number" 
                                               name="defect_qty" 
                                               x-model.number="defectQtyVal" 
                                               min="0.01" 
                                               :max="activeItem?.qty" 
                                               step="any" 
                                               placeholder="0" 
                                               class="w-full px-2.5 py-1.5 text-xs bg-white border border-rose-300 rounded-xl font-bold text-rose-950 focus:ring-2 focus:ring-rose-500 text-center font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-rose-900 text-[10px] uppercase tracking-wider mb-1">
                                            Alasan Selisih
                                        </label>
                                        <input type="text" 
                                               name="defect_reason" 
                                               x-model="defectReasonVal" 
                                               placeholder="Contoh: Hilang / retak saat serah terima" 
                                               class="w-full px-2.5 py-1.5 text-xs bg-white border border-rose-300 rounded-xl font-medium text-slate-800 focus:ring-2 focus:ring-rose-500">
                                    </div>
                                </div>
                                <p class="text-[10px] text-rose-700 leading-tight">
                                    <i class="fa-solid fa-info-circle me-1"></i> Barang cacat/hilang akan otomatis memotong kuota tiket pesanan.
                                </p>
                            </div>
                        </div>

                        <!-- Catatan Kurir Logistik -->
                        <div>
                            <label class="block text-slate-500 text-xs font-normal mb-1">
                                Catatan Kurir MH <span class="text-slate-400 text-[11px]">(opsional)</span>
                            </label>
                            <input type="text" 
                                   name="notes" 
                                   placeholder="Catatan nomor pallet / troli..." 
                                   class="w-full px-3 py-2 text-xs border border-slate-200 bg-slate-50 focus:bg-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-normal text-slate-800 placeholder:text-slate-400 transition">
                        </div>

                        <!-- Footer Tombol Aksi Simetris -->
                        <div class="pt-2 border-t border-slate-100 flex items-center space-x-2">
                            <button type="button" 
                                    @click="closeActionModal()" 
                                    class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" 
                                    :disabled="isWrScanRequired && !actionScanVerified"
                                    :class="(isWrScanRequired && !actionScanVerified) ? 'bg-slate-400 text-white cursor-not-allowed opacity-60 shadow-none' : 'bg-amber-500 hover:bg-amber-600 text-slate-950 font-black shadow-md shadow-amber-500/20 cursor-pointer'"
                                    class="flex-1 py-2.5 rounded-xl text-xs transition flex items-center justify-center space-x-1.5">
                                <template x-if="isWrScanRequired && !actionScanVerified">
                                    <span class="flex items-center space-x-1.5 font-extrabold">
                                        <i class="fa-solid fa-lock text-xs"></i>
                                        <span>Pindai QR Lembar WRI Dahulu</span>
                                    </span>
                                </template>
                                <template x-if="!(isWrScanRequired && !actionScanVerified)">
                                    <span class="flex items-center space-x-1.5">
                                        <i class="fa-solid fa-truck-ramp-box"></i>
                                        <span>Konfirmasi Ambil Material</span>
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL KAMERA SCANNER AKSI VERIFIKASI AMBIL MH            -->
        <!-- ======================================================== -->
        <div x-show="actionCameraModalOpen" 
             x-cloak 
             style="display: none; z-index: 99999 !important;"
             :class="{ 'pointer-events-none': !actionCameraModalOpen }"
             class="fixed inset-0 z-[99999] overflow-y-auto" 
             role="dialog" 
             aria-modal="true">
            <div x-show="actionCameraModalOpen"
                 style="z-index: 99998 !important;"
                 class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm transition-opacity"
                 @click="closeActionCameraModal()"></div>

            <div class="flex min-h-full items-center justify-center p-3 text-center sm:p-0 relative"
                 style="z-index: 99999 !important;">
                <div x-show="actionCameraModalOpen"
                     style="z-index: 99999 !important;"
                     class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-md border border-slate-200">
                    
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-xl bg-[#0a2342] text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                                <i class="fa-solid fa-camera text-amber-300"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-900 leading-tight">Pindai QR Tiket Material Handling</h4>
                                <p class="text-[10px] text-slate-500 font-medium">Arahkan kamera ke kode QR lembar kerja WRI</p>
                            </div>
                        </div>
                        <button type="button" 
                                @click="closeActionCameraModal()" 
                                class="w-7 h-7 rounded-full bg-white hover:bg-slate-200 border border-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>

                    <div class="p-4 space-y-3 bg-slate-950/90 text-white">
                        <div class="relative w-full rounded-2xl overflow-hidden bg-black border border-slate-800 min-h-[240px] flex items-center justify-center">
                            <div id="mh-action-camera-reader" class="w-full h-full min-h-[240px]"></div>
                        </div>

                        <template x-if="actionCameraError">
                            <div class="p-2.5 bg-amber-950/80 border border-amber-500/60 text-amber-200 rounded-xl text-[11px] font-medium space-y-2">
                                <div class="flex items-start space-x-2">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-400 mt-0.5 shrink-0"></i>
                                    <span x-text="actionCameraError"></span>
                                </div>
                                <label class="w-full py-2 px-3 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs rounded-xl flex items-center justify-center space-x-1.5 cursor-pointer transition">
                                    <i class="fa-solid fa-camera"></i>
                                    <span>Ambil Foto / Pilih Gambar QR</span>
                                    <input type="file" accept="image/*" capture="environment" class="hidden" @change="scanActionQrFile($event)">
                                </label>
                            </div>
                        </template>

                        <div class="p-2.5 bg-slate-900/90 border border-slate-800 rounded-xl flex items-center justify-between text-xs gap-2">
                            <div class="min-w-0">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Tiket Target:</span>
                                <span class="font-mono font-black text-amber-400 text-xs break-all" x-text="activeItem?.wri_number"></span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Part:</span>
                                <span class="font-bold text-white text-xs truncate max-w-[140px] block" x-text="activeItem?.component_name"></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                        <label class="px-3 py-2 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-800 font-bold text-xs rounded-xl transition cursor-pointer inline-flex items-center space-x-1.5">
                            <i class="fa-solid fa-image"></i>
                            <span>Upload / Foto QR</span>
                            <input type="file" accept="image/*" capture="environment" class="hidden" @change="scanActionQrFile($event)">
                        </label>
                        <button type="button" 
                                @click="closeActionCameraModal()" 
                                class="flex-1 py-2 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                            Tutup Kamera
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL: DETAIL STATUS & TIMELINE ALUR (PERSIS LEGACY)     -->
        <!-- ======================================================== -->
        <div x-show="detailModalOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
            <div @click.away="closeDetailModal()" class="bg-white rounded-3xl shadow-2xl max-w-xl w-full max-h-[92vh] overflow-y-auto border border-slate-200 transition-all">
                <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between rounded-t-3xl">
                    <div class="flex items-center space-x-2.5">
                        <div class="p-2 bg-teal-400 text-slate-950 rounded-xl font-bold">
                            <i class="fa-solid fa-timeline text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-white">Detail Alur & Histori Logistik Tiket</h3>
                            <p class="text-[11px] text-slate-400" x-text="detailItem?.wri_number"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeDetailModal()" class="text-slate-400 hover:text-white p-1 rounded-xl">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Hero Status Box -->
                    <div class="p-4 rounded-2xl bg-gradient-to-r from-teal-50 to-blue-50 border border-teal-100 flex items-center space-x-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-xl font-bold flex-shrink-0 shadow-sm">
                            <i class="fa-solid fa-truck-ramp-box"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-[10px] font-bold text-teal-800 uppercase tracking-wider block">Status Saat Ini:</span>
                            <h4 class="font-black text-slate-900 text-sm" x-text="detailItem?.current_status || detailItem?.status"></h4>
                            <p class="text-xs text-slate-600 mt-0.5 truncate" x-text="detailItem?.component_name + ' &bull; ' + (detailItem?.routing || '')"></p>
                        </div>
                    </div>

                    <!-- Routing Steps Timeline -->
                    <div>
                        <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Tahapan Routing & Lokasi:</h5>
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl font-mono text-xs font-bold text-indigo-900" x-text="detailItem?.routing"></div>
                    </div>

                    <!-- Target Location in Warehouse -->
                    <div class="p-3 bg-white border border-slate-200 rounded-2xl flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-semibold">Target Rak Penyimpanan Gudang:</span>
                        <span class="font-mono font-bold text-teal-800 bg-teal-50 border border-teal-200 px-2.5 py-0.5 rounded-lg" x-text="detailItem?.target_location || '-'"></span>
                    </div>

                    <!-- Direct link to full show view -->
                    <div class="pt-2 text-center">
                        <a :href="'/work-requests/' + (detailItem?.id || '')" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                            <span>Buka Halaman Lengkap Tiket WRI</span>
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL: CAMERA SCANNER (QR & BARCODE)                     -->
        <!-- ======================================================== -->
        <div x-show="mhScannerOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
            <div @click.away="closeMhScanner()" class="bg-white rounded-3xl shadow-2xl max-w-xl w-full max-h-[92vh] overflow-y-auto border border-slate-200 transition-all">
                <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between rounded-t-3xl">
                    <div class="flex items-center space-x-2.5">
                        <div class="p-2 bg-amber-400 text-slate-950 rounded-xl font-bold">
                            <i class="fa-solid fa-qrcode text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-white">Scanner Tiket WRI Lapangan</h3>
                            <p class="text-[11px] text-slate-400">Scan QR Code tiket untuk eksekusi cepat Material Handling</p>
                        </div>
                    </div>
                    <button type="button" @click="closeMhScanner()" class="text-slate-400 hover:text-white p-1 rounded-xl">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <div class="relative bg-slate-950 rounded-2xl overflow-hidden min-h-[220px] flex items-center justify-center border border-slate-800">
                        <div id="mh-scanner-reader" class="w-full"></div>
                        <div x-show="isScanningMh" class="absolute inset-x-8 top-1/2 -translate-y-1/2 h-1 bg-amber-400 shadow-[0_0_8px_#fbbf24] animate-pulse pointer-events-none"></div>
                    </div>

                    <template x-if="mhCameraError">
                        <div class="p-3 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs space-y-2">
                            <div class="font-bold flex items-start space-x-2">
                                <i class="fa-solid fa-camera-slash text-amber-600 mt-0.5 shrink-0"></i>
                                <span x-text="mhCameraError"></span>
                            </div>
                            <label class="w-full py-2 px-3 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs rounded-xl flex items-center justify-center space-x-1.5 cursor-pointer transition">
                                <i class="fa-solid fa-camera"></i>
                                <span>Ambil Foto / Pilih Gambar QR</span>
                                <input type="file" accept="image/*" capture="environment" class="hidden" @change="scanMhQrFile($event)">
                            </label>
                        </div>
                    </template>

                    <div class="flex items-center justify-between gap-2">
                        <label class="w-full py-2 px-3 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-800 font-bold text-xs rounded-xl flex items-center justify-center space-x-1.5 cursor-pointer transition">
                            <i class="fa-solid fa-image"></i>
                            <span>Scan dari Foto Kamera / Galeri QR</span>
                            <input type="file" accept="image/*" capture="environment" class="hidden" @change="scanMhQrFile($event)">
                        </label>
                    </div>

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

                    <div x-show="scanErrorMessage" style="display: none;" class="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl font-bold" x-text="scanErrorMessage"></div>

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

                        <form method="POST" action="{{ route('work-station-supplies.quick-action') }}" class="pt-2 space-y-2.5" x-data="{ scanDefect: false, scanQty: null }">
                            @csrf
                            <input type="hidden" name="work_request_id" :value="scannedTicket?.id">
                            <input type="hidden" name="action_type" value="process">
                            <input type="hidden" name="target_status" :value="scannedTicket?.next_status">

                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                                    Verifikasi Kuantitas Fisik:
                                </label>
                                <div class="relative">
                                    <input type="number" 
                                           name="process_qty" 
                                           :value="scannedTicket?.quantity_requested" 
                                           x-model="scanQty"
                                           required 
                                           step="any" 
                                           min="0.01" 
                                           :max="scannedTicket?.quantity_requested"
                                           class="w-full px-3 py-1.5 text-sm font-black border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 font-mono text-center">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400" x-text="scannedTicket?.uom"></span>
                                </div>
                            </div>

                            <!-- Toggle Defect Scanner -->
                            <div>
                                <label class="flex items-center space-x-1.5 cursor-pointer text-[10px] font-bold text-rose-600">
                                    <input type="checkbox" x-model="scanDefect" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                    <span>Ada Defect / Barang Hilang saat Scan?</span>
                                </label>
                                <div x-show="scanDefect" x-transition class="mt-1.5 p-2 bg-rose-50 border border-rose-200 rounded-xl space-y-1.5 text-xs">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[9px] font-bold text-rose-900 uppercase">Qty Defect</label>
                                            <input type="number" name="defect_qty" step="any" min="0.01" placeholder="0" class="w-full px-2 py-1 text-xs border border-rose-300 rounded-lg bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-rose-900 uppercase">Alasan</label>
                                            <input type="text" name="defect_reason" placeholder="Alasan defect..." class="w-full px-2 py-1 text-xs border border-rose-300 rounded-lg bg-white">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between space-x-2 pt-1">
                                <a :href="scannedTicket?.show_url" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition">
                                    Detail Tiket &rarr;
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
        function taskGudangHandler() {
            return {
                mainTab: 'transfer',
                transferSubTab: 'waiting',
                finalSubTab: 'waiting',

                filterDate: 'all',
                filterOrigin: 'all',
                filterDestination: 'all',
                filterFinalLocation: 'all',
                searchQuery: '',

                actionModalOpen: false,
                activeItem: null,
                pickupQty: 1,
                isWrScanRequired: {{ ($isWrScanRequired ?? false) ? 'true' : 'false' }},
                actionScanVerified: false,
                actionScannedCode: '',
                actionScanErrorMessage: '',
                actionCameraModalOpen: false,
                actionCameraError: '',
                html5ActionQrCode: null,
                gunScanBuffer: '',
                gunScanStartTime: 0,
                gunLastCharTime: 0,
                gunScanHandler: null,

                detailModalOpen: false,
                detailItem: null,

                mhScannerOpen: false,
                isScanningMh: false,
                mhCameraError: '',
                scanQuery: '',
                scannedTicket: null,
                scanErrorMessage: null,
                html5QrMh: null,

                matchesFilter(tglRaw, origin, destination, textSearch) {
                    if (!this.checkDate(tglRaw)) return false;
                    if (this.filterOrigin !== 'all' && origin !== this.filterOrigin) return false;
                    if (this.filterDestination !== 'all' && destination !== this.filterDestination) return false;
                    if (this.searchQuery && !textSearch.includes(this.searchQuery.toLowerCase())) return false;
                    return true;
                },

                matchesFilterFinal(tglRaw, location, textSearch) {
                    if (!this.checkDate(tglRaw)) return false;
                    if (this.filterFinalLocation !== 'all' && location !== this.filterFinalLocation) return false;
                    if (this.searchQuery && !textSearch.includes(this.searchQuery.toLowerCase())) return false;
                    return true;
                },

                checkDate(tglRaw) {
                    if (this.filterDate === 'all' || !tglRaw) return true;
                    const parts = tglRaw.split('-');
                    if (parts.length < 3) return false;
                    const itemDate = new Date(parts[0], parts[1] - 1, parts[2]);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    const diffTime = today - itemDate;
                    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

                    if (this.filterDate === 'today') return diffDays === 0;
                    if (this.filterDate === 'last3') return diffDays >= 0 && diffDays <= 3;
                    if (this.filterDate === 'week') return diffDays >= 0 && diffDays <= 7;
                    return true;
                },

                openActionModal(item) {
                    this.activeItem = item;
                    this.pickupQty = item.qty;
                    this.actionScanVerified = false;
                    this.actionScannedCode = '';
                    this.actionScanErrorMessage = '';
                    this.actionCameraError = '';
                    this.actionModalOpen = true;
                    this.attachActionGunScannerListener();
                },

                incrementPickupQty() {
                    if (this.activeItem && this.pickupQty < this.activeItem.qty) {
                        this.pickupQty++;
                    }
                },

                decrementPickupQty() {
                    if (this.pickupQty > 1) {
                        this.pickupQty--;
                    }
                },

                setMaxPickupQty() {
                    if (this.activeItem) {
                        this.pickupQty = this.activeItem.qty;
                    }
                },

                closeActionModal() {
                    this.detachActionGunScannerListener();
                    if (this.actionCameraModalOpen) {
                        this.closeActionCameraModal();
                    }
                    this.actionModalOpen = false;
                    this.activeItem = null;
                    this.actionScanVerified = false;
                    this.actionScannedCode = '';
                    this.actionScanErrorMessage = '';
                },

                attachActionGunScannerListener() {
                    if (this.gunScanHandler) return;
                    this.gunScanBuffer = '';
                    this.gunScanStartTime = 0;
                    this.gunLastCharTime = 0;

                    this.gunScanHandler = (e) => {
                        if (!this.actionModalOpen || this.actionScanVerified) return;

                        if (e.target && ['notes', 'defect_reason', 'defect_qty'].includes(e.target.name)) {
                            return;
                        }

                        const now = Date.now();

                        if (e.key === 'Enter') {
                            if (this.gunScanBuffer.length > 0) {
                                e.preventDefault();
                                const totalTime = now - this.gunScanStartTime;
                                const charCount = this.gunScanBuffer.length;
                                const avgCharSpeed = totalTime / charCount;

                                if (charCount >= 3 && avgCharSpeed <= 60) {
                                    this.verifyActionScannedCode(this.gunScanBuffer);
                                } else {
                                    this.actionScanErrorMessage = 'Pengetikan manual dilarang! Gunakan pemindai kamera atau scanner gun fisik.';
                                }
                                this.gunScanBuffer = '';
                                this.gunScanStartTime = 0;
                            }
                            return;
                        }

                        if (e.key && e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                            if (!this.gunScanStartTime || (now - this.gunLastCharTime > 150)) {
                                this.gunScanBuffer = '';
                                this.gunScanStartTime = now;
                            }
                            this.gunLastCharTime = now;
                            this.gunScanBuffer += e.key;
                        }
                    };
                    window.addEventListener('keydown', this.gunScanHandler);
                },

                detachActionGunScannerListener() {
                    if (this.gunScanHandler) {
                        window.removeEventListener('keydown', this.gunScanHandler);
                        this.gunScanHandler = null;
                    }
                    this.gunScanBuffer = '';
                    this.gunScanStartTime = 0;
                    this.gunLastCharTime = 0;
                },

                verifyActionScannedCode(rawCode) {
                    if (!rawCode || !rawCode.trim() || !this.activeItem) return;
                    const input = rawCode.trim();
                    const wriNum = (this.activeItem.wri_number || '').trim();
                    const partNum = (this.activeItem.part_number || '').trim();

                    const cleanScanned = input.replace(/HPK-PART\|/gi, '').replace(/[\s\-\/]/g, '').toLowerCase();
                    const cleanWri = wriNum.replace(/[\s\-\/]/g, '').toLowerCase();
                    const cleanPart = partNum.replace(/[\s\-\/]/g, '').toLowerCase();

                    let isValid = false;
                    if (cleanScanned && cleanWri && (cleanScanned === cleanWri || cleanScanned.includes(cleanWri) || cleanWri.includes(cleanScanned))) {
                        isValid = true;
                    } else if (cleanScanned && cleanPart && (cleanScanned === cleanPart || cleanScanned.includes(cleanPart))) {
                        isValid = true;
                    }

                    if (isValid) {
                        this.actionScannedCode = input;
                        this.actionScanVerified = true;
                        this.actionScanErrorMessage = '';
                        this.detachActionGunScannerListener();
                        if (this.actionCameraModalOpen) {
                            this.closeActionCameraModal();
                        }
                    } else {
                        this.actionScanErrorMessage = `Kode "${input}" tidak cocok dengan tiket ${wriNum}!`;
                    }
                },

                resetActionScanVerification() {
                    this.actionScanVerified = false;
                    this.actionScannedCode = '';
                    this.actionScanErrorMessage = '';
                    this.attachActionGunScannerListener();
                },

                openActionCameraModal() {
                    this.actionCameraError = '';
                    this.actionCameraModalOpen = true;
                    this.$nextTick(() => {
                        this.startActionCameraScanner();
                    });
                },

                async closeActionCameraModal() {
                    await this.stopActionCameraScanner();
                    this.actionCameraModalOpen = false;
                },

                async startActionCameraScanner() {
                    const el = document.getElementById('mh-action-camera-reader');
                    if (!el || typeof Html5Qrcode === 'undefined') {
                        this.actionCameraError = 'Komponen scanner kamera tidak tersedia. Gunakan tombol Ambil Foto / Pilih Gambar QR di bawah.';
                        return;
                    }
                    if (this.html5ActionQrCode) {
                        await this.stopActionCameraScanner();
                    }
                    try {
                        this.html5ActionQrCode = new Html5Qrcode('mh-action-camera-reader');
                        const config = { fps: 10, qrbox: { width: 220, height: 220 } };
                        await this.html5ActionQrCode.start(
                            { facingMode: 'environment' },
                            config,
                            (decodedText) => {
                                this.verifyActionScannedCode(decodedText);
                            },
                            () => {}
                        );
                        if (!this.actionCameraModalOpen) {
                            await this.stopActionCameraScanner();
                        }
                    } catch (err) {
                        console.warn('Action camera start issue:', err);
                        this.actionCameraError = 'Kamera live tidak dapat dibuka (pastikan izin kamera aktif atau gunakan HTTPS/localhost). Anda tetap dapat memindai memakai tombol Ambil Foto / Pilih Gambar QR di bawah.';
                    }
                },

                async stopActionCameraScanner() {
                    if (this.html5ActionQrCode) {
                        const scanner = this.html5ActionQrCode;
                        this.html5ActionQrCode = null;
                        try {
                            const state = typeof scanner.getState === 'function' ? scanner.getState() : null;
                            if (state === 2 || state === 3) {
                                await scanner.stop();
                            }
                        } catch (e) {}
                        try {
                            scanner.clear();
                        } catch (e) {}
                    }
                },

                async scanActionQrFile(event) {
                    const file = event?.target?.files?.[0];
                    if (!file) return;
                    event.target.value = '';
                    if (typeof Html5Qrcode === 'undefined') {
                        this.actionScanErrorMessage = 'Library pembaca QR tidak ditemukan.';
                        return;
                    }
                    await this.stopActionCameraScanner();
                    try {
                        const fileScanner = new Html5Qrcode('mh-action-camera-reader');
                        const decodedText = await fileScanner.scanFile(file, false);
                        try { fileScanner.clear(); } catch (e) {}
                        this.verifyActionScannedCode(decodedText);
                    } catch (err) {
                        const msg = 'QR Code tidak terbaca pada foto tersebut. Pastikan gambar QR jelas dan tidak buram.';
                        this.actionScanErrorMessage = msg;
                        if (this.actionCameraModalOpen) {
                            this.actionCameraError = msg;
                        }
                    }
                },

                openDetailModal(item) {
                    this.detailItem = item;
                    this.detailModalOpen = true;
                },

                closeDetailModal() {
                    this.detailModalOpen = false;
                    this.detailItem = null;
                },

                openMhScanner() {
                    this.mhScannerOpen = true;
                    this.scannedTicket = null;
                    this.scanErrorMessage = null;
                    this.mhCameraError = '';
                    this.scanQuery = '';
                    this.$nextTick(() => {
                        this.startCameraMh();
                    });
                },

                closeMhScanner() {
                    this.mhScannerOpen = false;
                    this.stopCameraMh();
                },

                startCameraMh() {
                    const el = document.getElementById('mh-scanner-reader');
                    if (!el || typeof Html5Qrcode === 'undefined') {
                        this.mhCameraError = 'Komponen kamera tidak siap. Gunakan tombol Scan dari Foto / Galeri atau kolom ketik di bawah.';
                        return;
                    }

                    if (this.html5QrMh) {
                        this.stopCameraMh();
                    }

                    try {
                        this.html5QrMh = new Html5Qrcode('mh-scanner-reader');
                        const config = { fps: 10, qrbox: { width: 220, height: 220 } };

                        this.html5QrMh.start(
                            { facingMode: 'environment' },
                            config,
                            (decodedText) => {
                                this.isScanningMh = true;
                                this.performLookup(decodedText, true);
                            },
                            (error) => {}
                        ).then(() => {
                            if (!this.mhScannerOpen) {
                                this.stopCameraMh();
                                return;
                            }
                            this.isScanningMh = true;
                        }).catch(err => {
                            console.warn('Camera start issue:', err);
                            this.isScanningMh = false;
                            this.mhCameraError = 'Kamera live tidak dapat dibuka (di HP pastikan izin kamera aktif & gunakan HTTPS/localhost). Gunakan tombol Ambil Foto / Pilih Gambar QR atau Scan Gun di bawah.';
                        });
                    } catch (e) {
                        console.warn('Failed to init MH camera:', e);
                        this.isScanningMh = false;
                        this.mhCameraError = 'Gagal menginisialisasi kamera perangkat. Gunakan foto QR atau Scan Gun.';
                    }
                },

                async stopCameraMh() {
                    this.isScanningMh = false;
                    if (this.html5QrMh) {
                        const scanner = this.html5QrMh;
                        this.html5QrMh = null;
                        try {
                            const state = typeof scanner.getState === 'function' ? scanner.getState() : null;
                            if (state === 2 || state === 3) {
                                await scanner.stop();
                            }
                        } catch (e) {
                            console.warn('Silently handled MH scanner stop:', e);
                        }
                        try {
                            scanner.clear();
                        } catch (e) {}
                    }
                },

                async scanMhQrFile(event) {
                    const file = event?.target?.files?.[0];
                    if (!file) return;
                    event.target.value = '';
                    this.scanErrorMessage = null;
                    if (typeof Html5Qrcode === 'undefined') {
                        this.scanErrorMessage = 'Library pembaca QR tidak tersedia.';
                        return;
                    }
                    await this.stopCameraMh();
                    try {
                        const fileScanner = new Html5Qrcode('mh-scanner-reader');
                        const decodedText = await fileScanner.scanFile(file, false);
                        try { fileScanner.clear(); } catch (e) {}
                        this.performLookup(decodedText, true);
                    } catch (err) {
                        this.scanErrorMessage = 'QR Code gagal terbaca dari foto. Pastikan kode QR terlihat jelas.';
                    }
                },

                async performLookup(code, stopCameraOnSuccess = false) {
                    if (!code || !code.trim()) return;
                    this.scanErrorMessage = null;

                    try {
                        const res = await fetch(`{{ route('work-station-supplies.scan-lookup') }}?code=${encodeURIComponent(code.trim())}`);
                        const data = await res.json();

                        if (data.success && data.work_request) {
                            this.scannedTicket = data.work_request;
                            if (stopCameraOnSuccess) {
                                this.stopCameraMh();
                            }
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

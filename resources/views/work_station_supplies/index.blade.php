<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 text-[11px] font-bold uppercase rounded-md bg-teal-100 text-teal-900 border border-teal-300">
                        Task Gudang &bull; Logistik Antar Stasiun
                    </span>
                    <span class="text-xs text-slate-500 font-medium">PT. Hydraxle Perkasa</span>
                </div>
                <div class="flex items-center space-x-3 mt-1">
                    <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight">
                        Material Handling
                    </h2>
                    <!-- Jam Digital Ringan Kotak (Sesuai Desain Legacy) -->
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white border border-slate-200/80 shadow-xs text-xs font-bold text-slate-600 font-mono" x-data="{ time: new Date().toLocaleTimeString('id-ID') }" x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID'), 1000)">
                        <i class="fa-regular fa-clock text-slate-400 text-[11px]"></i>
                        <span x-text="time">--:--:--</span>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Proses pengambilan & pengiriman barang antar mesin dan ke gudang
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <button type="button" 
                        @click="$dispatch('open-mh-scanner')" 
                        class="px-3.5 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                    <i class="fa-solid fa-qrcode me-1.5 text-sm"></i>
                    Scan QR Tiket WRI
                </button>
                <a href="{{ route('work-requests.index') }}" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center">
                    <i class="fa-solid fa-list-check me-1.5"></i>
                    Semua Tiket WRI
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
         x-data="taskGudangHandler()" 
         @open-mh-scanner.window="openMhScanner()">

        <!-- 1. FLOATING NAVIGATION PANEL (2 TAB UTAMA PERSIS SISTEM LAMA) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-2 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="button" 
                        @click="mainTab = 'transfer'" 
                        class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-xs"
                        :class="mainTab === 'transfer' ? 'bg-[#0f172a] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    <i class="fa-solid fa-right-left"></i>
                    <span>Antar Mesin</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                          :class="mainTab === 'transfer' ? 'bg-amber-400 text-slate-950' : 'bg-slate-200 text-slate-700'">
                        {{ count($transferWaiting) }}
                    </span>
                </button>

                <button type="button" 
                        @click="mainTab = 'final'" 
                        class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-xs"
                        :class="mainTab === 'final' ? 'bg-[#0f172a] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    <i class="fa-solid fa-box-open"></i>
                    <span>Siap Diambil</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                          :class="mainTab === 'final' ? 'bg-emerald-400 text-slate-950' : 'bg-slate-200 text-slate-700'">
                        {{ count($finalWaiting) }}
                    </span>
                </button>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-auto">
                <button type="button" 
                        onclick="window.location.reload()" 
                        title="Segarkan Data"
                        class="px-3.5 py-2.5 rounded-xl bg-teal-50 border border-teal-200 text-teal-700 hover:bg-teal-100 font-bold text-xs transition flex items-center gap-1.5 shadow-xs">
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

                <!-- Filter Panel Antar Mesin -->
                <div class="p-4 bg-white border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <span class="px-3 py-1 bg-blue-50 text-blue-800 border border-blue-200 rounded-full text-xs font-bold">
                        <span x-text="transferSubTab === 'waiting' ? '{{ count($transferWaiting) }} Item Menunggu Ambil' : (transferSubTab === 'transit' ? '{{ count($transferTransit) }} Item Dalam Perjalanan' : '{{ count($transferHistory) }} Riwayat')"></span>
                    </span>

                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <select x-model="filterDate" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5">
                            <option value="all">Semua Tgl</option>
                            <option value="today">Hari Ini</option>
                            <option value="last3">3 Hari</option>
                            <option value="week">Minggu Ini</option>
                        </select>

                        <select x-model="filterOrigin" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5">
                            <option value="all">Semua Asal</option>
                            @foreach($filterOrigins as $orig)
                            <option value="{{ $orig }}">{{ $orig }}</option>
                            @endforeach
                        </select>

                        <select x-model="filterDestination" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5">
                            <option value="all">Semua Tujuan</option>
                            @foreach($filterDestinations as $dest)
                            <option value="{{ $dest }}">{{ $dest }}</option>
                            @endforeach
                        </select>

                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Cari No WR / Komponen..." 
                               class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5 px-3 w-full sm:w-48">
                    </div>
                </div>

                <!-- SUB-TAB 1: MENUNGGU AMBIL (ANTAR MESIN) -->
                <div x-show="transferSubTab === 'waiting'" class="overflow-x-auto">
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
                                            class="inline-flex items-center justify-center px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-full shadow-xs transition">
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

                <!-- SUB-TAB 2: DALAM PERJALANAN (ANTAR MESIN) -->
                <div x-show="transferSubTab === 'transit'" class="overflow-x-auto">
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

                <!-- SUB-TAB 3: RIWAYAT TRANSFER (ANTAR MESIN) -->
                <div x-show="transferSubTab === 'history'" class="overflow-x-auto">
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

                <!-- Filter Panel Siap Diambil -->
                <div class="p-4 bg-white border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-bold">
                        <span x-text="finalSubTab === 'waiting' ? '{{ count($finalWaiting) }} Item Siap Diambil' : (finalSubTab === 'transit' ? '{{ count($finalTransit) }} Item Dalam Perjalanan ke Pengaju' : '{{ count($finalHistory) }} Riwayat Selesai')"></span>
                    </span>

                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <select x-model="filterDate" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5">
                            <option value="all">Semua Tgl</option>
                            <option value="today">Hari Ini</option>
                            <option value="last3">3 Hari</option>
                            <option value="week">Minggu Ini</option>
                        </select>

                        <select x-model="filterFinalLocation" class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5">
                            <option value="all">Semua Lokasi Selesai</option>
                            @foreach($filterFinalLocations as $floc)
                            <option value="{{ $floc }}">{{ $floc }}</option>
                            @endforeach
                        </select>

                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Cari No WR / Komponen..." 
                               class="text-xs font-semibold rounded-xl border-slate-200 focus:ring-teal-500 py-1.5 px-3 w-full sm:w-48">
                    </div>
                </div>

                <!-- SUB-TAB 1: SIAP DIAMBIL (FINAL) -->
                <div x-show="finalSubTab === 'waiting'" class="overflow-x-auto">
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
                                            class="inline-flex items-center justify-center px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-full shadow-xs transition">
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

                <!-- SUB-TAB 2: DALAM PERJALANAN (FINAL KE PENGAJU) -->
                <div x-show="finalSubTab === 'transit'" class="overflow-x-auto">
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

                <!-- SUB-TAB 3: RIWAYAT SELESAI (FINAL) -->
                <div x-show="finalSubTab === 'history'" class="overflow-x-auto">
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

        <!-- ======================================================== -->
        <!-- MODAL: KONFIRMASI AMBIL MATERIAL (PICKUP ACTION)         -->
        <!-- ======================================================== -->
        <div x-show="actionModalOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            <div @click.away="closeActionModal()" class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200/80 transition-all text-slate-800">
                <form method="POST" action="{{ route('work-station-supplies.quick-action') }}">
                    @csrf
                    <input type="hidden" name="work_request_id" :value="activeItem?.id">
                    <input type="hidden" name="action_type" value="process">
                    <input type="hidden" name="target_status" :value="activeItem?.target_status">

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
                                <div class="flex items-center space-x-1.5">
                                    <span class="text-[10px] font-mono font-black uppercase text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200/60" x-text="activeItem?.wri_number"></span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200" x-text="activeItem?.target_status"></span>
                                </div>
                                <div class="font-bold text-slate-800 text-xs truncate mt-1" x-text="activeItem?.component_name"></div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-[10px] text-slate-400 font-medium block">Rute:</span>
                                <span class="text-[11px] font-bold text-slate-700" x-text="(activeItem?.origin || activeItem?.location || '-') + ' → ' + (activeItem?.destination || '-')"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Body Kompak & Fungsional -->
                    <div class="p-5 pt-4 space-y-3.5 text-xs" x-data="{ hasDefectAction: false, defectQtyVal: null, defectReasonVal: '' }">
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
                                    <span>PCS</span>
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
                            <label class="block font-bold text-slate-500 uppercase tracking-wider text-[10px] mb-1">
                                Catatan Kurir MH <span class="font-normal text-slate-400 lowercase">(opsional)</span>:
                            </label>
                            <input type="text" 
                                   name="notes" 
                                   placeholder="Catatan nomor pallet / troli..." 
                                   class="w-full px-3 py-2 text-xs border border-slate-200 bg-slate-50 focus:bg-white rounded-xl focus:ring-2 focus:ring-amber-500 font-medium text-slate-800 transition">
                        </div>

                        <!-- Footer Tombol Aksi Simetris -->
                        <div class="pt-2 border-t border-slate-100 flex items-center space-x-2">
                            <button type="button" 
                                    @click="closeActionModal()" 
                                    class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-center space-x-1.5">
                                <i class="fa-solid fa-truck-ramp-box"></i>
                                <span>Konfirmasi Ambil Material</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
                </form>
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

                detailModalOpen: false,
                detailItem: null,

                mhScannerOpen: false,
                isScanningMh: false,
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
                    this.actionModalOpen = true;
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
                    this.actionModalOpen = false;
                    this.activeItem = null;
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

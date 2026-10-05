<x-admin-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 240px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-12 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-boxes-stacked me-1 text-[9px]"></i> STRUKTUR FISIK
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Master Gudang & Rak
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                            Gudang, Zona & Lokasi Rak
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            Pemetaan fisik 1 gedung HPK Karoseri terbagi menjadi 6 zona operasional (A sampai F) dan titik bin level.
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0 flex items-center space-x-3">
                        <a href="{{ route('warehouse-map.index') }}" 
                           class="inline-flex items-center px-4 py-2.5 rounded-xl bg-teal-500/20 hover:bg-teal-500/30 text-teal-300 hover:text-teal-200 border border-teal-400/30 font-bold text-xs tracking-wide shadow-xs transition">
                            <i class="fa-solid fa-map-location-dot me-2 text-sm"></i>
                            Buka Peta Visual Gudang
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. TWO-COLUMN LAYOUT -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Left Column: Warehouse & Zone Selector -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-[#0a2342] text-white rounded-2xl border border-slate-700/80 shadow-md p-5">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-warehouse text-amber-400 text-sm"></i>
                            <span class="font-extrabold text-xs uppercase tracking-wider text-slate-200">Gudang Utama</span>
                        </div>
                        <span class="text-[10px] bg-amber-400/20 text-amber-300 px-2 py-0.5 rounded-full font-bold">1 Gedung</span>
                    </div>

                    @foreach($warehouses as $wh)
                        <div class="mb-4">
                            <div class="font-black text-sm text-white mb-1">{{ $wh->name }}</div>
                            <p class="text-[11px] text-slate-300 leading-relaxed mb-3">{{ $wh->description }}</p>

                            <!-- Zona Selector Pills -->
                            <div class="space-y-1.5">
                                <a href="{{ route('admin.locations-master.index') }}" 
                                   class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition {{ !request('zone_id') ? 'bg-amber-400 text-slate-950 shadow-xs' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                    <span>Semua Zona</span>
                                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full {{ !request('zone_id') ? 'bg-slate-950/20 text-slate-950' : 'bg-white/10 text-slate-300' }}">
                                        {{ \App\Models\Location::count() }}
                                    </span>
                                </a>

                                @foreach($wh->zones as $z)
                                    @php
                                        $zoneLetter = $z->code;
                                        $zoneColor = match($zoneLetter) {
                                            'A' => 'text-amber-400',
                                            'B' => 'text-blue-400',
                                            'C' => 'text-teal-400',
                                            'D' => 'text-rose-400',
                                            'E' => 'text-purple-400',
                                            'F' => 'text-orange-400',
                                            default => 'text-slate-300',
                                        };
                                        $isSelected = request('zone_id') == $z->id;
                                    @endphp
                                    <a href="{{ route('admin.locations-master.index', ['warehouse_id' => $wh->id, 'zone_id' => $z->id]) }}" 
                                       class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition {{ $isSelected ? 'bg-white/20 text-amber-300 font-black border border-white/20' : 'text-slate-300 hover:bg-white/10 hover:text-white font-medium' }}">
                                        <div class="flex items-center space-x-2">
                                            <span class="w-5 h-5 rounded-md bg-white/10 flex items-center justify-center font-bold text-[10px] {{ $zoneColor }}">
                                                {{ $zoneLetter }}
                                            </span>
                                            <span class="truncate max-w-[130px]">{{ $z->name }}</span>
                                        </div>
                                        <span class="text-[10px] font-mono bg-white/10 px-2 py-0.5 rounded-full text-slate-300">
                                            {{ $z->locations_count }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Column: Locations Table -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base">Titik Rak & Penyimpanan</h3>
                            <p class="text-xs text-slate-500">
                                @if(request('zone_id'))
                                    Menampilkan lokasi untuk zona terpilih
                                @else
                                    Menampilkan seluruh titik rak gudang HPK
                                @endif
                            </p>
                        </div>
                        <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                            {{ $locations->total() ?? $locations->count() }} Lokasi
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200/80 shadow-xs">
                        <table class="w-full text-left text-xs whitespace-nowrap">
                            <thead class="bg-slate-50 uppercase tracking-wider text-slate-600 font-extrabold border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3.5">Kode Rak / Bay</th>
                                    <th class="px-5 py-3.5">Zona Gudang</th>
                                    <th class="px-5 py-3.5">Lorong (Aisle) & Level</th>
                                    <th class="px-5 py-3.5">Kapasitas Maks</th>
                                    <th class="px-5 py-3.5">Fungsi / Deskripsi Area</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                @forelse($locations as $loc)
                                    @php
                                        $zoneBadge = match($loc->zone_code) {
                                            'A' => 'bg-amber-100 text-amber-800 border-amber-300',
                                            'B' => 'bg-blue-100 text-blue-800 border-blue-300',
                                            'C' => 'bg-teal-100 text-teal-800 border-teal-300',
                                            'D' => 'bg-rose-100 text-rose-800 border-rose-300',
                                            'E' => 'bg-purple-100 text-purple-800 border-purple-300',
                                            'F' => 'bg-orange-100 text-orange-800 border-orange-300',
                                            default => 'bg-slate-100 text-slate-800 border-slate-300',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Kode Rak -->
                                        <td class="px-5 py-3.5">
                                            <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200 text-xs">
                                                {{ $loc->rack_number ?? 'AREA-FL' }}
                                            </span>
                                        </td>

                                        <!-- Zona -->
                                        <td class="px-5 py-3.5">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $zoneBadge }}">
                                                Zona {{ $loc->zone_code }} &bull; {{ $loc->zone_name }}
                                            </span>
                                        </td>

                                        <!-- Aisle & Level -->
                                        <td class="px-5 py-3.5">
                                            <div class="font-bold text-slate-800">{{ $loc->aisle ?? '-' }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono">Level: {{ $loc->bin_level ?? '-' }}</div>
                                        </td>

                                        <!-- Kapasitas -->
                                        <td class="px-5 py-3.5">
                                            <div class="font-bold text-slate-900">{{ $loc->max_capacity }} Unit</div>
                                            <div class="w-24 bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                                <div class="bg-teal-500 h-1.5 rounded-full" style="width: 60%"></div>
                                            </div>
                                        </td>

                                        <!-- Deskripsi -->
                                        <td class="px-5 py-3.5 text-slate-600 max-w-xs truncate">
                                            {{ $loc->description ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                                                <i class="fa-solid fa-boxes-stacked"></i>
                                            </div>
                                            <p class="font-bold text-sm text-slate-700">Tidak ada lokasi pada zona ini</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if(method_exists($locations, 'links'))
                    <div class="mt-5">
                        {{ $locations->links() }}
                    </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
</x-admin-layout>

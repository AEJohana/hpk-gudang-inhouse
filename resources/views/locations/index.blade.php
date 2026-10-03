<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-slate-900 leading-tight">
                Peta & Tata Letak Denah Gudang HPK (1 Gedung Terpadu)
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Visualisasi 6 zona fisik gudang karoseri: Raw Material Baja, Hidrolik Presisi, Fastener, Chemical, Buffer Perakitan, dan Scrap Yard</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- 2D Floor Plan Master Layout Banner -->
        <div class="bg-slate-950 text-white rounded-2xl p-6 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-3">
                    <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                    <h3 class="font-mono font-bold text-sm text-slate-200">DENAH LANTAI GUDANG PABRIK KAROSERI HPK</h3>
                </div>
                <span class="text-xs text-slate-400">Total Luas: 3.600 m² &bull; Tinggi Ceiling: 12 Meter</span>
            </div>

            <!-- Floor Plan Interactive Graphic Matrix -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                @foreach ($zones as $code => $zone)
                    <div class="p-5 rounded-xl border transition-all duration-200 
                        {{ match($code) {
                            'A' => 'bg-blue-950/40 border-blue-600/50 hover:border-blue-400',
                            'B' => 'bg-indigo-950/40 border-indigo-600/50 hover:border-indigo-400',
                            'C' => 'bg-emerald-950/40 border-emerald-600/50 hover:border-emerald-400',
                            'D' => 'bg-amber-950/40 border-amber-600/50 hover:border-amber-400',
                            'E' => 'bg-cyan-950/40 border-cyan-600/50 hover:border-cyan-400',
                            'F' => 'bg-rose-950/40 border-rose-600/50 hover:border-rose-400',
                            default => 'bg-slate-900 border-slate-700'
                        } }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono font-bold text-xs px-2 py-0.5 rounded
                                {{ match($code) {
                                    'A' => 'bg-blue-900 text-blue-200',
                                    'B' => 'bg-indigo-900 text-indigo-200',
                                    'C' => 'bg-emerald-900 text-emerald-200',
                                    'D' => 'bg-amber-900 text-amber-200',
                                    'E' => 'bg-cyan-900 text-cyan-200',
                                    'F' => 'bg-rose-900 text-rose-200',
                                    default => 'bg-slate-800 text-white'
                                } }}">
                                ZONA {{ $code }}
                            </span>
                            <span class="text-xs text-slate-400 font-semibold">{{ $zone['locations']->count() }} Titik Rak</span>
                        </div>

                        <h4 class="font-bold text-sm text-white">{{ $zone['name'] }}</h4>
                        <p class="text-xs text-slate-400 mt-1 line-clamp-2 leading-relaxed">{{ $zone['description'] }}</p>

                        <!-- Racks in this Zone -->
                        <div class="mt-4 pt-3 border-t border-slate-800/80 space-y-1.5">
                            <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Rak / Lokasi Penyimpanan:</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($zone['locations'] as $loc)
                                    <a href="{{ route('locations.show', $loc) }}" class="px-2 py-1 bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700/80 rounded font-mono text-[11px] transition">
                                        {{ $loc->rack_number }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between text-xs text-slate-400 border-t border-slate-800 pt-3">
                <div class="flex items-center space-x-4">
                    <span class="flex items-center"><span class="w-2.5 h-2.5 bg-blue-500 rounded-sm me-1.5"></span> Baja/Plat</span>
                    <span class="flex items-center"><span class="w-2.5 h-2.5 bg-indigo-500 rounded-sm me-1.5"></span> Hidrolik</span>
                    <span class="flex items-center"><span class="w-2.5 h-2.5 bg-emerald-500 rounded-sm me-1.5"></span> Fastener</span>
                    <span class="flex items-center"><span class="w-2.5 h-2.5 bg-amber-500 rounded-sm me-1.5"></span> Chemical</span>
                    <span class="flex items-center"><span class="w-2.5 h-2.5 bg-cyan-500 rounded-sm me-1.5"></span> Buffer Perakitan</span>
                    <span class="flex items-center"><span class="w-2.5 h-2.5 bg-rose-500 rounded-sm me-1.5"></span> Scrap/Afkir</span>
                </div>
                <span>Sistem Denah HPK &bull; Skala 1:1 In-House</span>
            </div>
        </div>

        <!-- Detailed Zone Cards with Component Breakdown -->
        <div class="space-y-6">
            @foreach ($zones as $code => $zone)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="font-mono text-sm font-bold px-2.5 py-1 rounded bg-slate-900 text-white">
                                ZONA {{ $code }}
                            </span>
                            <div>
                                <h3 class="font-bold text-sm text-slate-900">{{ $zone['name'] }}</h3>
                                <p class="text-xs text-slate-500">{{ $zone['description'] }}</p>
                            </div>
                        </div>
                        <a href="{{ route('cycle-counts.create') }}" class="text-xs text-amber-600 hover:text-amber-700 font-bold">
                            + Buat Stok Opname Zona Ini
                        </a>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($zone['locations'] as $loc)
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col justify-between space-y-3">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="font-mono font-bold text-sm text-slate-900">{{ $loc->rack_number }}</span>
                                            <span class="text-[10px] text-slate-500">{{ $loc->bin_level }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 mt-1">{{ $loc->description }}</p>
                                    </div>

                                    <div class="pt-2 border-t border-slate-200/80">
                                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Komponen Tersimpan:</span>
                                        @if ($loc->stockBalances->isEmpty())
                                            <span class="text-xs text-slate-400 italic">Rak kosong</span>
                                        @else
                                            <div class="space-y-1">
                                                @foreach ($loc->stockBalances as $sb)
                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="font-mono font-semibold text-slate-800 truncate max-w-[180px]">{{ $sb->component->name }}</span>
                                                        <span class="font-bold text-slate-900">{{ number_format($sb->quantity, 0) }} {{ $sb->component->uom }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <a href="{{ route('locations.show', $loc) }}" class="text-xs font-semibold text-blue-600 hover:underline pt-1 block text-right">
                                        Rincian Rak &rarr;
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</x-app-layout>

<x-admin-layout>
    <div class="wave-header pb-12 pt-8 relative overflow-hidden" style="min-height: 180px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="bg-hpk-orange/20 text-amber-300 border border-amber-300/50 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-widest uppercase">
                            Admin
                        </span>
                        <span class="text-teal-300 text-xs font-bold tracking-widest uppercase">Master Lokasi</span>
                    </div>
                    <h1 class="text-3xl font-black text-white tracking-wide mb-1">
                        Gudang, Zona & Rak
                    </h1>
                </div>
                <a href="#" class="px-4 py-2 bg-[#0a2342] border border-amber-400/50 hover:bg-slate-800 text-amber-400 font-bold text-sm rounded-xl shadow-md transition flex items-center">
                    <i class="fa-solid fa-plus mr-2"></i> Tambah Lokasi
                </a>
            </div>
        </div>
        <div class="wave-shape-2">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,60 C320,120 420,0 720,60 C1020,120 1120,0 1440,60 L1440,120 L0,120 Z" fill="#f0f4f8"></path>
            </svg>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20 pb-12">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Sidebar / Warehouse Hierarki (Panel Gelap) -->
            <div class="col-span-1">
                <div class="bg-slate-900 rounded-2xl border border-slate-700 shadow-lg p-5">
                    <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4 border-b border-white/10 pb-2">Hierarki Gudang</h3>
                    
                    <ul class="space-y-4">
                        @foreach($warehouses as $wh)
                            <li>
                                <div class="flex items-center text-teal-400 font-bold text-sm mb-2">
                                    <i class="fa-solid fa-warehouse mr-2"></i> {{ $wh->name }}
                                </div>
                                <ul class="pl-5 space-y-2 border-l border-white/10 ml-2">
                                    @foreach($wh->zones as $z)
                                        <li>
                                            <a href="{{ route('admin.locations-master.index', ['warehouse_id' => $wh->id, 'zone_id' => $z->id]) }}" class="flex items-center justify-between text-xs transition {{ request('zone_id') == $z->id ? 'text-amber-400 font-bold' : 'text-slate-300 hover:text-white' }}">
                                                <span>{{ $z->name }}</span>
                                                <span class="bg-white/10 text-white px-2 py-0.5 rounded-full text-[10px]">{{ $z->locations_count }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Detail List (Tabel Putih) -->
            <div class="col-span-1 md:col-span-3">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <h2 class="font-bold text-slate-900 text-lg">Daftar Lokasi Tersimpan</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="uppercase tracking-wider text-slate-500 font-bold bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4">Kode Lokasi</th>
                                    <th class="px-6 py-4">Aisle / Rak / Bin</th>
                                    <th class="px-6 py-4">Gudang & Zona</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($locations as $loc)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4">
                                        <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded-full text-xs">
                                            {{ $loc->full_location_code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-700">
                                        {{ $loc->aisle ?? '-' }} / {{ $loc->rack_number ?? '-' }} / {{ $loc->bin_level ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-800">{{ $loc->warehouse->name ?? '-' }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $loc->zone->name ?? $loc->zone_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <a href="#" class="text-blue-600 hover:text-blue-800 px-2 py-1 bg-blue-50 rounded-lg transition" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                        Tidak ada data lokasi (Pilih zona atau tambah baru).
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $locations->links() }}
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-admin-layout>

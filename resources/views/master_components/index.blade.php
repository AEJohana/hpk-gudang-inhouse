<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                Master Data Komponen Karoseri
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Katalog material, spesifikasi teknis, batas stok & posisi rak fisik HPK</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('components.create') }}" class="inline-flex items-center px-4 py-2 bg-[#0a2342] hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-md transition">
                <i class="fa-solid fa-plus me-1.5 text-teal-400"></i>
                Tambah Komponen Baru
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Search & Filter Bar -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <form method="GET" action="{{ route('components.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-5 relative">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari Kode Part, Nama Komponen, atau Spek..." 
                           class="w-full pl-10 pr-4 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-3.5 top-3 text-xs"></i>
                </div>

                <div class="sm:col-span-4">
                    <select name="category" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:outline-none text-slate-700">
                        <option value="">-- Semua Kategori Komponen --</option>
                        @foreach ($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}" {{ request('category') == $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-3 flex space-x-2">
                    <button type="submit" class="flex-1 py-2 px-4 bg-[#0a2342] hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-filter me-1.5 text-teal-400"></i> Filter
                    </button>
                    @if (request()->hasAny(['search', 'category', 'low_stock']))
                        <a href="{{ route('components.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs rounded-xl flex items-center justify-center font-semibold">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <!-- Quick Filter Pills (Super Nav Pills) -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <div class="super-nav-pills flex-wrap">
                    <a href="{{ route('components.index') }}" class="super-nav-pill {{ !request('category') && !request('low_stock') ? 'active' : '' }}">
                        Semua Part
                    </a>
                    <a href="{{ route('components.index', ['low_stock' => 1]) }}" class="super-nav-pill {{ request('low_stock') ? 'active' : '' }} text-rose-600">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Stok Kritis
                    </a>
                    <a href="{{ route('components.index', ['category' => 'hydraulic']) }}" class="super-nav-pill {{ request('category') == 'hydraulic' ? 'active' : '' }}">
                        Hidrolik
                    </a>
                    <a href="{{ route('components.index', ['category' => 'raw_material']) }}" class="super-nav-pill {{ request('category') == 'raw_material' ? 'active' : '' }}">
                        Baja & Pelat
                    </a>
                    <a href="{{ route('components.index', ['category' => 'fastener']) }}" class="super-nav-pill {{ request('category') == 'fastener' ? 'active' : '' }}">
                        Fastener
                    </a>
                </div>

                <span class="text-xs text-slate-400 font-medium">Menampilkan {{ $components->total() }} komponen</span>
            </div>
        </div>

        <!-- Super Table with Floating Separated Rows -->
        <div class="overflow-x-auto pb-4">
            <table class="super-table">
                <thead>
                    <tr>
                        <th class="py-3 px-4">Foto & Part Number</th>
                        <th class="py-3 px-4">Nama & Spesifikasi</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">Lokasi Rak</th>
                        <th class="py-3 px-4 text-center">Stok Fisik</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($components as $component)
                        <tr class="super-row">
                            <!-- Photo & Part Number -->
                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ $component->image_url }}" alt="{{ $component->name }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200 bg-slate-50 shadow-xs flex-shrink-0">
                                    <div>
                                        <span class="font-mono font-bold text-slate-900 block text-xs">{{ $component->part_number }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold">Satuan: {{ $component->uom }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Name & Specs -->
                            <td class="py-4 px-4 max-w-xs">
                                <a href="{{ route('components.show', $component) }}" class="font-bold text-slate-900 hover:text-teal-700 block line-clamp-1 transition">
                                    {{ $component->name }}
                                </a>
                                <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ $component->specification ?: '-' }}</p>
                            </td>

                            <!-- Category -->
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $component->category_label }}
                                </span>
                            </td>

                            <!-- Location -->
                            <td class="py-4 px-4">
                                @if ($component->defaultLocation)
                                    <span class="font-mono text-xs font-bold text-slate-800 block">
                                        {{ $component->defaultLocation->full_location_code }}
                                    </span>
                                    <span class="text-[10px] text-teal-700 font-medium">{{ $component->defaultLocation->zone_name }}</span>
                                @else
                                    <span class="text-slate-400 italic text-xs">Belum diatur</span>
                                @endif
                            </td>

                            <!-- Stock Status -->
                            <td class="py-4 px-4 text-center">
                                <div class="inline-flex flex-col items-center">
                                    <span class="font-black text-sm {{ $component->is_low_stock ? 'text-rose-600' : 'text-slate-900' }}">
                                        {{ number_format($component->total_stock, 0) }} {{ $component->uom }}
                                    </span>
                                    @if ($component->is_low_stock)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-rose-100 text-rose-700 mt-1">
                                            <i class="fa-solid fa-circle-exclamation me-1"></i> Min: {{ $component->minimum_stock }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400 mt-0.5">Min: {{ $component->minimum_stock }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('components.qr-label', $component) }}" target="_blank" title="Cetak Label QR" class="p-2 inline-flex text-slate-600 hover:text-teal-700 hover:bg-teal-50 rounded-xl transition">
                                    <i class="fa-solid fa-qrcode text-sm"></i>
                                </a>
                                <a href="{{ route('components.show', $component) }}" class="p-2 inline-flex text-teal-700 hover:bg-teal-50 rounded-xl transition" title="Lihat Detail">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </a>
                                <a href="{{ route('components.edit', $component) }}" class="p-2 inline-flex text-slate-600 hover:bg-slate-100 rounded-xl transition" title="Edit Data">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 bg-white rounded-2xl shadow-sm">
                                <i class="fa-solid fa-box-open text-3xl mb-2 text-slate-300 block"></i>
                                Tidak ada data komponen yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($components instanceof \Illuminate\Pagination\LengthAwarePaginator && $components->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                {{ $components->links() }}
            </div>
        @endif

    </div>
</x-app-layout>

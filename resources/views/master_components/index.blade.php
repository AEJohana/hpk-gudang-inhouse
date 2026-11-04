<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 w-full">
            <!-- Left: Back Button + Icon Badge + Title (Judul Saja Tanpa Deskripsi) -->
            <div class="flex items-center space-x-3 sm:space-x-3.5 min-w-0">
                <a href="{{ route('dashboard') }}" 
                   class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200/80 flex items-center justify-center text-slate-700 transition shadow-2xs shrink-0 cursor-pointer" 
                   title="Kembali ke Beranda">
                    <i class="fa-solid fa-arrow-left text-sm text-slate-600"></i>
                </a>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-700 shadow-2xs shrink-0">
                    <i class="fa-solid fa-cubes-stacked text-base sm:text-lg"></i>
                </div>
                <h2 class="font-extrabold text-base sm:text-xl text-[#0a2342] tracking-tight leading-tight truncate">
                    Master Data Komponen
                </h2>
            </div>

            <!-- Right: Action Buttons (Anchored to Far Right) -->
            <div class="flex items-center space-x-2 shrink-0 ml-auto sm:ml-0">
                <button type="button" 
                        @click="$dispatch('open-import-modal')" 
                        class="px-3 py-2 sm:px-3.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-xs transition touch-target cursor-pointer flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-file-excel text-emerald-200 text-xs"></i>
                    <span class="hidden md:inline">Import Excel</span>
                </button>
                <a href="{{ route('components.create') }}" class="px-3.5 py-2 bg-[#0a2342] hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition touch-target whitespace-nowrap flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-plus text-teal-400 text-xs"></i>
                    <span>+ Komponen Baru</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Search, Filter & Quick Status Bar (Sejajar dalam 1 Baris Terpadu) -->
        <div class="bg-white rounded-2xl p-3 sm:p-4 border border-slate-200/80 shadow-xs flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
            <!-- Left: Quick Status Buttons (Semua Part & Peringatan Status) -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5 -mx-1 px-1 flex-shrink-0">
                <a href="{{ route('components.index') }}" class="super-nav-pill whitespace-nowrap {{ !request('category') && !request('low_stock') && !request('unallocated') && !request('uncategorized') ? 'active' : '' }}">
                    Semua Part
                </a>
                <a href="{{ route('components.index', ['uncategorized' => 1]) }}" class="super-nav-pill whitespace-nowrap {{ request('uncategorized') ? 'active' : '' }} text-amber-700">
                    <i class="fa-solid fa-tags me-1 text-[11px]"></i> Perlu Kategori ({{ $uncategorizedCount }})
                </a>
                <a href="{{ route('components.index', ['unallocated' => 1]) }}" class="super-nav-pill whitespace-nowrap {{ request('unallocated') ? 'active' : '' }} text-amber-700">
                    <i class="fa-solid fa-triangle-exclamation me-1 text-[11px]"></i> Perlu Alokasi Rak ({{ $unallocatedCount }})
                </a>
                <a href="{{ route('components.index', ['low_stock' => 1]) }}" class="super-nav-pill whitespace-nowrap {{ request('low_stock') ? 'active' : '' }} text-rose-600">
                    <i class="fa-solid fa-triangle-exclamation me-1 text-[11px]"></i> Stok Kritis
                </a>
            </div>

            <!-- Right: Search Input, Category Select & Compact Filter Button (Sejajar) -->
            <form method="GET" action="{{ route('components.index') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full xl:w-auto">
                @if(request('uncategorized'))
                    <input type="hidden" name="uncategorized" value="1">
                @endif
                @if(request('unallocated'))
                    <input type="hidden" name="unallocated" value="1">
                @endif
                @if(request('low_stock'))
                    <input type="hidden" name="low_stock" value="1">
                @endif

                <!-- Search Input -->
                <div class="relative flex-1 sm:w-56 md:w-64 min-w-[170px]">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari Kode Part, Nama, Spek..." 
                           class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none transition">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 text-xs"></i>
                </div>

                <!-- Category Select Dropdown -->
                <div class="w-full sm:w-44 flex-shrink-0">
                    <select name="category" class="w-full py-2 px-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none text-slate-700 font-medium">
                        <option value="">-- Semua Kategori --</option>
                        @foreach ($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}" {{ request('category') == $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tombol Filter (Diperkecil) & Reset -->
                <div class="flex items-center space-x-1 flex-shrink-0">
                    <button type="submit" 
                            class="py-2 px-3 bg-[#0a2342] hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center space-x-1 cursor-pointer"
                            title="Terapkan Filter">
                        <i class="fa-solid fa-filter text-teal-400 text-xs"></i>
                        <span>Filter</span>
                    </button>
                    @if (request()->hasAny(['search', 'category', 'low_stock', 'unallocated', 'uncategorized']))
                        <a href="{{ route('components.index') }}" 
                           class="py-2 px-2.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 text-xs rounded-xl inline-flex items-center justify-center font-bold transition cursor-pointer"
                           title="Reset Seluruh Filter">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                        </a>
                    @endif
                </div>

                <!-- Total Count -->
                <span class="text-xs text-slate-400 font-medium whitespace-nowrap hidden 2xl:inline-block ms-1">
                    ({{ method_exists($components, 'total') ? $components->total() : $components->count() }})
                </span>
            </form>
        </div>

        <!-- 1. Mobile Card List View (< md screens) -->
        <div class="block md:hidden space-y-3">
            @forelse ($components as $component)
                <div class="mobile-data-card space-y-3">
                    <div class="flex items-start space-x-3">
                        <img src="{{ $component->image_url }}" alt="{{ $component->name }}" class="w-16 h-16 rounded-xl object-cover border border-slate-200 bg-slate-50 flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <span class="font-mono font-bold text-[11px] text-amber-900 bg-amber-100 px-2 py-0.5 rounded">{{ $component->part_number }}</span>
                                @if(!$component->category)
                                    <span class="text-[10px] font-bold text-amber-800 bg-amber-100 border border-amber-200 px-2 py-0.5 rounded">⚠️ Belum Dikategorikan</span>
                                @else
                                    <span class="text-[10px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">{{ $component->category_label }}</span>
                                @endif
                            </div>
                            <a href="{{ route('components.show', $component) }}" class="font-bold text-slate-900 text-sm block truncate hover:text-teal-700">
                                {{ $component->name }}
                            </a>
                            @if($component->dimension_formatted !== '-')
                                <span class="inline-flex items-center gap-1 text-[10px] font-mono text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded mt-0.5">
                                    <i class="fa-solid fa-ruler text-[8px] text-slate-400"></i> {{ $component->dimension_formatted }}
                                </span>
                            @endif
                            <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ $component->specification ?: 'Tanpa keterangan' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Lokasi Rak:</span>
                            @if ($component->defaultLocation)
                                <span class="font-mono font-bold text-slate-800 text-[11px]">{{ $component->defaultLocation->full_location_code }}</span>
                            @else
                                <div class="mt-0.5 space-y-0.5">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Belum Ditempatkan
                                    </span>
                                    <a href="{{ route('components.edit', $component) }}" class="block text-[10px] font-bold text-blue-600 hover:underline">
                                        + Tentukan Rak
                                    </a>
                                </div>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Stok Fisik:</span>
                            <span class="font-black text-sm {{ $component->is_low_stock ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ number_format($component->total_stock, 0) }} {{ $component->uom }}
                            </span>
                            @if ($component->is_low_stock)
                                <span class="block text-[9px] font-extrabold text-rose-600">
                                    <i class="fa-solid fa-circle-exclamation me-0.5"></i> Min: {{ $component->minimum_stock }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-1.5 pt-2 border-t border-slate-100 text-center">
                        <a href="{{ route('components.qr-label', $component) }}" target="_blank" class="py-1.5 px-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg flex items-center justify-center gap-1">
                            <i class="fa-solid fa-qrcode text-[11px]"></i> <span>QR</span>
                        </a>
                        <a href="{{ route('components.edit', $component) }}" class="py-1.5 px-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg flex items-center justify-center gap-1">
                            <i class="fa-solid fa-pen text-[10px]"></i> <span>Edit</span>
                        </a>
                        <a href="{{ route('components.show', $component) }}" class="py-1.5 px-1 bg-[#0a2342] text-white text-xs font-bold rounded-lg flex items-center justify-center gap-1">
                            <i class="fa-solid fa-eye text-[10px] text-teal-400"></i> <span>Detail</span>
                        </a>
                        <a href="{{ route('transactions.create', ['component_id' => $component->id]) }}" class="py-1.5 px-1 bg-amber-500 text-slate-950 text-xs font-bold rounded-lg flex items-center justify-center gap-1">
                            <i class="fa-solid fa-plus text-[10px]"></i> <span>Trans.</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-slate-400 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <i class="fa-solid fa-box-open text-3xl mb-2 text-slate-300 block"></i>
                    Tidak ada data komponen yang sesuai dengan filter pencarian.
                </div>
            @endforelse
        </div>

        <!-- 2. Desktop & Tablet Super Table (>= md screens) -->
        <div class="hidden md:block overflow-x-auto pb-4">
            <table class="super-table">
                <thead>
                    <tr>
                        <th class="py-3 px-4">Foto & Kode Komponen</th>
                        <th class="py-3 px-4">Nama & Keterangan</th>
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
                                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                    @if($component->dimension_formatted !== '-')
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-slate-100 text-slate-600 text-[10px] rounded font-mono font-semibold">
                                            <i class="fa-solid fa-ruler-combined text-[9px] text-slate-400"></i> {{ $component->dimension_formatted }}
                                        </span>
                                    @endif
                                    <span class="text-[11px] text-slate-500 line-clamp-1">{{ $component->specification ?: '-' }}</span>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="py-4 px-4">
                                @if(!$component->category)
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 whitespace-nowrap">
                                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Belum Dikategorikan
                                        </span>
                                        <a href="{{ route('components.edit', $component) }}" class="block text-[10px] font-bold text-blue-600 hover:underline">
                                            + Pilih Kategori
                                        </a>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        {{ $component->category_label }}
                                    </span>
                                @endif
                            </td>

                            <!-- Location -->
                            <td class="py-4 px-4">
                                @if ($component->defaultLocation)
                                    <span class="font-mono text-xs font-bold text-slate-800 block">
                                        {{ $component->defaultLocation->full_location_code }}
                                    </span>
                                    <span class="text-[10px] text-teal-700 font-medium">{{ $component->defaultLocation->zone_name }}</span>
                                @else
                                    <div class="inline-flex flex-col items-start gap-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Belum Ditempatkan
                                        </span>
                                        <a href="{{ route('components.edit', $component) }}" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-1">
                                            <i class="fa-solid fa-location-dot text-[9px]"></i> Tentukan Rak
                                        </a>
                                    </div>
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

    {{-- Modal Import Massal Excel --}}
    @include('master_components.partials.import_modal')
</x-app-layout>

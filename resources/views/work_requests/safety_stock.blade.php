<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2.5 sm:gap-4 w-full">
            <div class="min-w-0">
                <h2 class="font-extrabold text-base sm:text-2xl text-slate-900 tracking-tight truncate">
                    <span class="sr-only">Safety Stock Buffer Alert</span>
                    Safety Stock & Pemicu Order WRI
                </h2>
            </div>

            <div class="flex items-center space-x-2 shrink-0 ml-auto">
                <a href="{{ route('work-requests.index') }}" class="px-3 sm:px-3.5 py-1.5 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center shadow-2xs">
                    <i class="fa-solid fa-arrow-left me-1.5"></i>
                    <span class="hidden sm:inline">Kembali ke Antrian WRI</span>
                    <span class="sm:hidden">Antrian WRI</span>
                </a>
            </div>
        </div>
    </x-slot>

@php
    $criticalComponentsJson = $criticalComponents->map(function($c) {
        $deficit = max(1, (int)$c->minimum_stock - (int)$c->total_stock);
        return [
            'id' => $c->id,
            'name' => $c->name,
            'part_number' => $c->part_number,
            'category' => $c->category->name ?? 'Fabrikasi',
            'stock' => (float)$c->total_stock,
            'min_stock' => (float)$c->minimum_stock,
            'deficit' => (float)$deficit,
            'uom' => $c->uom ?: 'PCS',
            'location' => $c->defaultLocation ? $c->defaultLocation->code : '-',
            'image_url' => route('components.photo-stream', ['component' => $c->id]),
        ];
    });

    $fabricationDepartmentsJson = $fabricationDepartments->map(function($d) {
        return [
            'id' => $d->id,
            'code' => $d->code,
            'name' => $d->name,
        ];
    });
@endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
         x-data="safetyStockCart({ 
             components: @js($criticalComponentsJson), 
             fabricationDepartments: @js($fabricationDepartmentsJson) 
         })">

        <!-- 0. INTEGRATED WRI SUB-NAVIGATION TABS (Antrean, Safety Stock, Cetak WR) -->
        @include('work_requests.partials.sub_nav')

        <!-- 1. ALERT BANNER -->
        @if($totalCritical > 0)
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg font-bold shrink-0">
                    <i class="fa-solid fa-bell animate-bounce"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-rose-900">Peringatan: Stok Komponen Di Bawah Batas Minimum</h4>
                    <p class="text-xs text-rose-700 mt-0.5">Komponen berikut berisiko habis. Pilih komponen untuk dimasukkan ke keranjang pemesanan WRI massal.</p>
                </div>
            </div>
            <span class="hidden sm:inline-block px-3 py-1 bg-rose-600 text-white text-xs font-black rounded-lg shrink-0">
                {{ $totalCritical }} Kritis
            </span>
        </div>
        @else
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl mx-auto mb-2">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h4 class="font-extrabold text-base text-emerald-900">Semua Saldo Stok Aman</h4>
            <p class="text-xs text-emerald-700 mt-0.5">Tidak ada komponen fabrikasi yang berada di bawah ambang batas safety stock saat ini.</p>
        </div>
        @endif

        <!-- 2. CRITICAL COMPONENTS CONTAINER (DUAL VIEW: DESKTOP TABLE & MOBILE CARDS) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-boxes-stacked text-slate-700"></i>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-800">Daftar Komponen Defisit Stok</h4>
                </div>

                <!-- Search -->
                <form method="GET" action="{{ route('work-requests.safety-stock') }}" class="relative w-full sm:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request()->search }}" 
                           placeholder="Cari part number atau nama..." 
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                </form>
            </div>

            <!-- DESKTOP / TABLET VIEW (TABLE WITH MIN-WIDTH & NO-WRAP BADGES) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 min-w-[720px]">
                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-3 w-10 text-center">
                                <input type="checkbox" 
                                       @change="selectAllOnPage()" 
                                       :checked="isAllSelectedOnPage()" 
                                       class="rounded border-slate-300 text-amber-500 focus:ring-amber-500 cursor-pointer"
                                       title="Pilih semua di halaman ini">
                            </th>
                            <th class="py-3 px-4 min-w-[200px]">Komponen</th>
                            <th class="py-3 px-3 min-w-[90px]">Kategori</th>
                            <th class="py-3 px-3 text-center whitespace-nowrap">Stok Fisik</th>
                            <th class="py-3 px-3 text-center whitespace-nowrap">Safety Stock</th>
                            <th class="py-3 px-3 text-center whitespace-nowrap">Defisit</th>
                            <th class="py-3 px-3 whitespace-nowrap">Lokasi Default</th>
                            <th class="py-3 px-4 text-end whitespace-nowrap">Aksi Pemicu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($criticalComponents as $comp)
                        @php
                            $deficit = max(1, (int)$comp->minimum_stock - (int)$comp->total_stock);
                            $compRowData = [
                                'id' => $comp->id,
                                'name' => $comp->name,
                                'part_number' => $comp->part_number,
                                'category' => $comp->category->name ?? 'Fabrikasi',
                                'stock' => (float)$comp->total_stock,
                                'min_stock' => (float)$comp->minimum_stock,
                                'deficit' => (float)$deficit,
                                'uom' => $comp->uom ?: 'PCS',
                                'location' => $comp->defaultLocation ? $comp->defaultLocation->code : '-',
                                'image_url' => route('components.photo-stream', ['component' => $comp->id]),
                            ];
                        @endphp
                        <tr class="hover:bg-rose-50/30 transition" :class="isInCart({{ $comp->id }}) ? 'bg-amber-50/50' : ''">
                            <td class="py-3 px-3 text-center">
                                <input type="checkbox" 
                                       @change="toggleCart(@js($compRowData))" 
                                       :checked="isInCart({{ $comp->id }})" 
                                       class="rounded border-slate-300 text-amber-500 focus:ring-amber-500 cursor-pointer">
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ route('components.photo-stream', ['component' => $comp->id]) }}" 
                                         alt="{{ $comp->name }}"
                                         loading="lazy"
                                         class="w-10 h-10 rounded-lg object-cover bg-slate-100 border border-slate-200 flex-shrink-0"
                                         x-on:error="$el.src = '{{ asset('images/placeholder_part.svg') }}'">
                                    <div>
                                        <span class="font-extrabold text-slate-900 block leading-tight">{{ $comp->name }}</span>
                                        <span class="inline-block font-mono text-[10px] text-amber-900 bg-amber-100/80 px-1.5 py-0.5 rounded font-bold mt-0.5">
                                            {{ $comp->part_number }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-slate-700 font-medium">
                                {{ $comp->category->name ?? 'Fabrikasi' }}
                            </td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <span class="text-sm font-black text-rose-600">
                                    {{ number_format($comp->total_stock, 0) }}
                                </span>
                                <span class="text-[10px] text-slate-400 block uppercase">{{ $comp->uom }}</span>
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-slate-700 whitespace-nowrap">
                                {{ number_format($comp->minimum_stock, 0) }} <span class="text-[10px] uppercase font-normal text-slate-400">{{ $comp->uom }}</span>
                            </td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black font-mono bg-rose-100 text-rose-800 border border-rose-300 shadow-2xs whitespace-nowrap">
                                    <span>-{{ number_format($deficit, 0) }}</span>
                                    <span class="text-[10px] font-bold text-rose-600 uppercase">{{ $comp->uom }}</span>
                                </span>
                            </td>
                            <td class="py-3 px-3 font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                {{ $comp->defaultLocation ? $comp->defaultLocation->code : '-' }}
                            </td>
                            <td class="py-3 px-4 text-end whitespace-nowrap">
                                @can('work-request.create')
                                <button type="button" 
                                        @click="toggleCart(@js($compRowData))" 
                                        :class="isInCart({{ $comp->id }}) ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-amber-500 hover:bg-amber-400 text-slate-950'"
                                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-xs cursor-pointer active:scale-98">
                                    <i :class="isInCart({{ $comp->id }}) ? 'fa-solid fa-check text-[10px]' : 'fa-solid fa-cart-plus text-[10px]'"></i>
                                    <span x-text="isInCart({{ $comp->id }}) ? 'Di Keranjang' : 'Pesan WRI'"></span>
                                </button>
                                @else
                                <span class="text-[10px] text-slate-400 italic">Hanya Pemohon</span>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 italic">
                                Tidak ada komponen yang defisit di bawah batas safety stock.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- MOBILE VIEW (< md): TOUCH-FRIENDLY COMPONENT CARDS (ANTI-CUTOFF & SUPER FLEXIBLE) -->
            <div class="block md:hidden divide-y divide-slate-100">
                @forelse($criticalComponents as $comp)
                @php
                    $deficit = max(1, (int)$comp->minimum_stock - (int)$comp->total_stock);
                    $compRowData = [
                        'id' => $comp->id,
                        'name' => $comp->name,
                        'part_number' => $comp->part_number,
                        'category' => $comp->category->name ?? 'Fabrikasi',
                        'stock' => (float)$comp->total_stock,
                        'min_stock' => (float)$comp->minimum_stock,
                        'deficit' => (float)$deficit,
                        'uom' => $comp->uom ?: 'PCS',
                        'location' => $comp->defaultLocation ? $comp->defaultLocation->code : '-',
                        'image_url' => route('components.photo-stream', ['component' => $comp->id]),
                    ];
                @endphp
                <div class="p-4 space-y-3 hover:bg-rose-50/20 transition" :class="isInCart({{ $comp->id }}) ? 'bg-amber-50/40' : ''">
                    <!-- Top Row: Checkbox + Thumbnail + Component Name + Part Number -->
                    <div class="flex items-start space-x-3">
                        <div class="pt-1">
                            <input type="checkbox" 
                                   @change="toggleCart(@js($compRowData))" 
                                   :checked="isInCart({{ $comp->id }})" 
                                   class="rounded border-slate-300 text-amber-500 focus:ring-amber-500 cursor-pointer">
                        </div>
                        <img src="{{ route('components.photo-stream', ['component' => $comp->id]) }}" 
                             alt="{{ $comp->name }}"
                             loading="lazy"
                             class="w-12 h-12 rounded-xl object-cover bg-slate-100 border border-slate-200 shrink-0"
                             x-on:error="$el.src = '{{ asset('images/placeholder_part.svg') }}'">
                        <div class="min-w-0 flex-1">
                            <h5 class="font-extrabold text-xs text-slate-900 leading-snug truncate">{{ $comp->name }}</h5>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span class="font-mono text-[10px] text-amber-900 bg-amber-100/80 px-1.5 py-0.5 rounded font-bold">
                                    {{ $comp->part_number }}
                                </span>
                                <span class="text-[10px] text-slate-400">
                                    &bull; {{ $comp->category->name ?? 'Fabrikasi' }}
                                </span>
                                @if($comp->defaultLocation)
                                <span class="font-mono text-[10px] text-slate-500 bg-slate-100 px-1 rounded">
                                    {{ $comp->defaultLocation->code }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Middle Row: 3 Metrics Grid (Stok Fisik, Safety Stock, Defisit) -->
                    <div class="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-center">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-medium">Stok Fisik</span>
                            <span class="text-xs font-black text-rose-600">{{ number_format($comp->total_stock, 0) }}</span>
                            <span class="text-[9px] text-slate-400 uppercase ml-0.5">{{ $comp->uom }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block font-medium">Safety Stock</span>
                            <span class="text-xs font-bold text-slate-700">{{ number_format($comp->minimum_stock, 0) }}</span>
                            <span class="text-[9px] text-slate-400 uppercase ml-0.5">{{ $comp->uom }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-rose-600 block font-bold">Defisit</span>
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-md text-xs font-black font-mono bg-rose-100 text-rose-800 border border-rose-300 whitespace-nowrap">
                                -{{ number_format($deficit, 0) }} <span class="text-[9px] uppercase ml-0.5">{{ $comp->uom }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Action Button (Full Width on Mobile) -->
                    <div>
                        @can('work-request.create')
                        <button type="button" 
                                @click="toggleCart(@js($compRowData))" 
                                :class="isInCart({{ $comp->id }}) ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-amber-500 hover:bg-amber-400 text-slate-950'"
                                class="w-full py-2.5 px-3 rounded-xl text-xs font-extrabold transition shadow-xs flex items-center justify-center space-x-1.5 active:scale-[0.98] cursor-pointer">
                            <i :class="isInCart({{ $comp->id }}) ? 'fa-solid fa-check text-xs' : 'fa-solid fa-cart-plus text-xs'"></i>
                            <span x-text="isInCart({{ $comp->id }}) ? 'Sudah Masuk Keranjang (Klik Batalkan)' : '+ Masukkan Keranjang Pesanan WRI'"></span>
                        </button>
                        @else
                        <div class="text-center py-1 text-[11px] text-slate-400 italic">Hanya Pemohon yang dapat memicu WRI</div>
                        @endcan
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-slate-400 italic text-xs">
                    Tidak ada komponen yang defisit di bawah batas safety stock.
                </div>
                @endforelse
            </div>

            @if($criticalComponents->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $criticalComponents->links() }}
            </div>
            @endif
        </div>

        <!-- 3. FLOATING ACTION BAR KERANJANG (Melayang saat ada item dipilih) -->
        <div x-show="cartItems.length > 0" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-8"
             x-cloak 
             style="display: none;"
             class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:right-8 z-40 bg-slate-900/95 backdrop-blur-md text-white px-4 py-3 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center justify-between gap-4 max-w-xl">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-sm shrink-0 shadow-xs">
                    <i class="fa-solid fa-basket-shopping"></i>
                </div>
                <div>
                    <div class="text-xs font-bold leading-tight flex items-center space-x-1.5">
                        <span class="text-amber-400 font-extrabold text-sm" x-text="cartItems.length"></span>
                        <span>Komponen Defisit Dipilih</span>
                    </div>
                    <span class="text-[10px] text-slate-400">Siap diatur kartu order dan diterbitkan</span>
                </div>
            </div>

            <div class="flex items-center space-x-2 shrink-0">
                <button type="button" 
                        @click="clearCart()" 
                        class="px-2.5 py-1.5 text-[11px] font-semibold text-slate-400 hover:text-white transition cursor-pointer">
                    Kosongkan
                </button>
                <button type="button" 
                        @click="showCartModal = true" 
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs rounded-xl transition flex items-center space-x-1.5 shadow-md active:scale-98 cursor-pointer">
                    <span>Atur Kartu Order</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        </div>

        <!-- 4. MODAL KARTU ORDER KERANJANG WRI MASSAL -->
        <div x-show="showCartModal" 
             x-cloak 
             style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200"
                 @click.outside="showCartModal = false">
                
                <!-- Modal Header -->
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200/80 flex items-center justify-center text-sm shadow-2xs">
                            <i class="fa-solid fa-cart-flatbed"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base text-slate-900 leading-tight flex items-center space-x-2">
                                <span>Keranjang Pemesanan Work Request</span>
                                <span class="text-xs bg-amber-100 text-amber-900 font-bold px-2 py-0.5 rounded-full" x-text="cartItems.length + ' Item'"></span>
                            </h3>
                            <p class="text-[11px] text-slate-500 font-normal mt-0.5">
                                Atur kuantitas pesanan, stasiun mesin pengerjaan, dan tingkat prioritas untuk masing-masing part defisit.
                            </p>
                        </div>
                    </div>
                    <button type="button" 
                            @click="showCartModal = false" 
                            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Modal Body: Scrollable Order Cards List -->
                <div class="p-4 sm:p-5 overflow-y-auto space-y-3.5 flex-1">
                    <template x-for="(item, idx) in cartItems" :key="item.component_id">
                        <div class="p-3.5 bg-slate-50/70 hover:bg-slate-50 border border-slate-200/90 rounded-2xl shadow-2xs space-y-3 transition">
                            
                            <!-- Header Kartu: Foto, Part, Defisit, & Hapus -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center space-x-3 min-w-0">
                                    <img :src="item.image_url" 
                                         :alt="item.name" 
                                         class="w-11 h-11 rounded-xl object-cover bg-white border border-slate-200 shrink-0"
                                         x-on:error="$el.src = '{{ asset('images/placeholder_part.svg') }}'">
                                    <div class="min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono text-[10px] font-bold text-slate-900 bg-white px-1.5 py-0.5 rounded border border-slate-200" x-text="item.part_number"></span>
                                            <span class="text-[10px] text-slate-400 font-medium" x-text="'Rak: ' + item.location"></span>
                                        </div>
                                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 truncate mt-0.5" x-text="item.name"></h4>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2 shrink-0">
                                    <!-- Badge Defisit -->
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-rose-100 text-rose-800 border border-rose-300">
                                        <span x-text="'-' + item.deficit + ' ' + item.uom"></span>
                                    </span>

                                    <!-- Tombol Hapus per Item -->
                                    <button type="button" 
                                            @click="removeFromCart(idx)" 
                                            class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition cursor-pointer"
                                            title="Keluarkan dari keranjang">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Grid Input Kontrol: Jumlah, Stasiun Mesin, & Prioritas -->
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 pt-2 border-t border-slate-200/60">
                                <!-- Jumlah Permintaan (4 Kolom) -->
                                <div class="sm:col-span-4">
                                    <label class="block text-[11px] font-medium text-slate-600 mb-1">
                                        Jumlah Permintaan <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <div class="relative flex items-stretch rounded-xl border border-slate-200 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 overflow-hidden bg-white">
                                        <input type="number" 
                                               x-model="item.quantity" 
                                               min="1" 
                                               step="any" 
                                               required 
                                               class="w-full px-3 py-1.5 text-xs border-0 focus:ring-0 focus:outline-none font-bold text-slate-900">
                                        <span class="inline-flex items-center px-2 py-1.5 bg-slate-100 text-slate-600 font-bold text-xs border-l border-slate-200 select-none whitespace-nowrap" 
                                              x-text="item.uom"></span>
                                    </div>
                                </div>

                                <!-- Stasiun Mesin Tujuan (5 Kolom) -->
                                <div class="sm:col-span-5">
                                    <label class="block text-[11px] font-medium text-slate-600 mb-1">
                                        Stasiun Mesin Tujuan <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <select x-model="item.department_name" 
                                            required 
                                            class="w-full px-2.5 py-1.5 text-xs border border-slate-200 bg-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-semibold text-slate-800 transition">
                                        @foreach($fabricationDepartments as $fDept)
                                            <option value="{{ $fDept->name }}">
                                                [{{ $fDept->code }}] {{ $fDept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Prioritas (3 Kolom) -->
                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-medium text-slate-600 mb-1">
                                        Prioritas <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <select x-model="item.priority" 
                                            required 
                                            class="w-full px-2.5 py-1.5 text-xs border border-slate-200 bg-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-semibold text-slate-800 transition">
                                        <option value="normal">Normal (Produksi)</option>
                                        <option value="high">Tinggi (High)</option>
                                        <option value="urgent_line_stop">URGENT (Line Stop)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Kolom Keterangan / Instruksi Khusus -->
                            <div>
                                <input type="text" 
                                       x-model="item.notes" 
                                       placeholder="Catatan atau instruksi khusus pengerjaan (opsional)..." 
                                       class="w-full px-3 py-1.5 text-xs border border-slate-200 bg-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-normal text-slate-700 placeholder:text-slate-400 transition">
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer: Submit Form -->
                <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 rounded-b-3xl flex flex-col-reverse sm:flex-row items-center justify-between gap-3 shrink-0">
                    <button type="button" 
                            @click="showCartModal = false" 
                            class="w-full sm:w-auto px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 font-semibold rounded-xl text-xs border border-slate-200 transition text-center cursor-pointer">
                        <i class="fa-solid fa-arrow-left text-[10px] me-1.5"></i>
                        <span>Tambah Komponen Lain</span>
                    </button>

                    <form method="POST" action="{{ route('work-requests.bulk-store') }}" class="w-full sm:w-auto">
                        @csrf
                        <input type="hidden" name="cart_data" :value="JSON.stringify(cartItems)">
                        <button type="submit" 
                                class="w-full sm:w-auto px-6 py-2.5 bg-[#0a2342] hover:bg-slate-800 active:scale-98 text-white font-extrabold rounded-xl text-xs shadow-md transition flex items-center justify-center space-x-2 cursor-pointer">
                            <i class="fa-solid fa-paper-plane text-amber-300 text-xs"></i>
                            <span x-text="'Terbitkan Semua Work Request (' + cartItems.length + ' Tiket)'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- JAVASCRIPT LOGIC SAFETY STOCK CART -->
    <script>
        function safetyStockCart({ components = [], fabricationDepartments = [] }) {
            return {
                availableComponents: components || [],
                fabricationDepartments: fabricationDepartments || [],
                cartItems: [],
                showCartModal: false,

                isInCart(componentId) {
                    return this.cartItems.some(item => item.component_id === componentId);
                },

                toggleCart(comp) {
                    if (!comp || !comp.id) return;
                    const index = this.cartItems.findIndex(item => item.component_id === comp.id);
                    if (index > -1) {
                        this.cartItems.splice(index, 1);
                    } else {
                        this.addToCart(comp);
                    }
                },

                addToCart(comp) {
                    if (this.isInCart(comp.id)) return;

                    // Smart suggest routing:
                    let defaultDept = 'Dept Laser Cutting';
                    const upper = (comp.name || '').toUpperCase();
                    if (upper.includes('BENDING') || upper.includes('SIKU') || upper.includes('COVER') || upper.includes('BUMPER')) {
                        defaultDept = 'Dept Bending';
                    } else if (upper.includes('HANDLE') || upper.includes('PIN') || upper.includes('AS ') || upper.includes('BOLT') || upper.includes('PIPA')) {
                        defaultDept = 'Dept Band Saw';
                    } else if (upper.includes('UNP')) {
                        defaultDept = 'Dept UNP';
                    }

                    const matchedDept = this.fabricationDepartments.find(d => d.name === defaultDept || d.name.includes(defaultDept))?.name || this.fabricationDepartments[0]?.name || 'Dept Laser Cutting';

                    this.cartItems.push({
                        component_id: comp.id,
                        name: comp.name,
                        part_number: comp.part_number,
                        uom: comp.uom || 'PCS',
                        location: comp.location || '-',
                        image_url: comp.image_url,
                        deficit: comp.deficit || 1,
                        quantity: comp.deficit || 1, // Default = angka defisit riil
                        department_name: matchedDept,
                        priority: 'normal',
                        notes: ''
                    });
                },

                removeFromCart(index) {
                    this.cartItems.splice(index, 1);
                    if (this.cartItems.length === 0) {
                        this.showCartModal = false;
                    }
                },

                clearCart() {
                    if (confirm('Kosongkan semua komponen dari keranjang pesanan?')) {
                        this.cartItems = [];
                        this.showCartModal = false;
                    }
                },

                selectAllOnPage() {
                    const allSelected = this.isAllSelectedOnPage();
                    if (allSelected) {
                        this.availableComponents.forEach(c => {
                            const idx = this.cartItems.findIndex(item => item.component_id === c.id);
                            if (idx > -1) this.cartItems.splice(idx, 1);
                        });
                    } else {
                        this.availableComponents.forEach(c => {
                            if (!this.isInCart(c.id)) {
                                this.addToCart(c);
                            }
                        });
                    }
                },

                isAllSelectedOnPage() {
                    if (this.availableComponents.length === 0) return false;
                    return this.availableComponents.every(c => this.isInCart(c.id));
                }
            };
        }
    </script>
</x-app-layout>

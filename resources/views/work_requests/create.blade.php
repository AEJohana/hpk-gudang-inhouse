<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center space-x-3 min-w-0">
                <a href="{{ route('work-requests.index') }}" 
                   class="w-9 h-9 rounded-xl bg-white hover:bg-slate-100 text-slate-600 hover:text-slate-900 border border-slate-200/90 shadow-2xs flex items-center justify-center transition cursor-pointer shrink-0" 
                   title="Kembali ke Antrean WRI">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </a>
                <div class="min-w-0">
                    <h2 class="font-extrabold text-lg sm:text-xl text-slate-900 tracking-tight leading-tight">
                        Membuat Work Request Baru
                    </h2>
                    <p class="text-xs text-slate-500 font-normal truncate mt-0.5">
                        Penerbitan pesanan tiket fabrikasi komponen ke stasiun mesin center dengan sistem keranjang
                    </p>
                </div>
            </div>

            <!-- Compact Sub-Header Bar: Pengaju & Tanggal (Desktop) -->
            <div class="hidden md:flex items-center space-x-2 text-xs bg-white px-3.5 py-1.5 rounded-xl border border-slate-200/80 shadow-2xs text-slate-600 font-medium">
                <span class="inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-user-circle text-slate-400"></i>
                    <strong>{{ auth()->user()->name }}</strong>
                </span>
                <span class="text-slate-300">&bull;</span>
                <span class="text-slate-500">{{ $userDept }}</span>
                <span class="text-slate-300">&bull;</span>
                <span class="text-slate-500">{{ now()->translatedFormat('d M Y') }}</span>
            </div>
        </div>
    </x-slot>

@php
    $componentsJsonData = $components->map(function($c) {
        return [
            'id' => $c->id,
            'part_number' => $c->part_number ?? '-',
            'name' => $c->name,
            'category' => $c->category ?? 'subframe',
            'uom' => $c->uom ?: 'PCS',
            'specs' => $c->specification ?: '',
            'image_url' => $c->image_url,
            'length_mm' => (string) ($c->length_mm ?? ''),
            'width_mm' => (string) ($c->width_or_diameter_mm ?? ''),
            'thickness_mm' => (string) ($c->thickness_or_height_mm ?? ''),
            'dimensions' => $c->dimension_formatted ?: '-',
            'stock' => (float) $c->total_stock,
            'min_stock' => (float) ($c->minimum_stock ?: 0),
            'rack' => $c->default_full_location_code ?: 'Belum di Rak',
            'routing' => '-',
        ];
    })->values();

    $deptList = $fabricationDepartments->pluck('name')->values()->all();
@endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="createWrCartApp(@js($componentsJsonData), @js($deptList), '{{ $userDept }}')">

        <!-- 2-COLUMN STUDIO LAYOUT RESPONSIF: FORM BUILDER (60%) & LIVE CART (40%) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- ========================================== -->
            <!-- KOLOM KIRI (7/12): FORM BUILDER ORDER      -->
            <!-- ========================================== -->
            <div class="lg:col-span-7 space-y-5">
                
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-5 sm:p-7 space-y-6">
                    
                    <!-- BARIS 1: PEMOHON & TANGGAL ORDER (METADATA STRIP) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                        <!-- Pemohon -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-building-user text-slate-400 text-xs"></i>
                                <span>Pemohon</span>
                            </label>
                            @if($canAccessAll)
                                <select x-model="selectedDeptPengaju" 
                                        class="w-full px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#0a2342]/20 focus:border-[#0a2342] focus:outline-none transition">
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->name }}" {{ $dept->name === $userDept ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <div class="relative">
                                    <input type="text" 
                                           :value="selectedDeptPengaju" 
                                           readonly 
                                           class="w-full px-3.5 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl text-slate-700 cursor-not-allowed select-none">
                                    <i class="fa-solid fa-lock text-[10px] text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2"></i>
                                </div>
                            @endif
                        </div>

                        <!-- Tanggal Order -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar text-slate-400 text-xs"></i>
                                <span>Tanggal Order</span>
                            </label>
                            <input type="text" 
                                   value="{{ now()->format('d/m/Y') }}" 
                                   readonly 
                                   class="w-full px-3.5 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl text-slate-700 cursor-not-allowed select-none">
                        </div>
                    </div>

                    <!-- SEKSI 1: CARI KOMPONEN & UNIFIED SEARCH DOCK -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-extrabold text-sm text-slate-900 leading-tight flex items-center gap-2">
                                <span class="w-5 h-5 rounded-md bg-[#0a2342] text-white text-[11px] font-bold flex items-center justify-center">1</span>
                                <span>Cari Komponen Fabrikasi</span>
                            </h3>
                            <span class="text-[11px] text-slate-400 font-medium" x-text="componentsList.length + ' Part Katalog'"></span>
                        </div>

                        <!-- UNIFIED DOCK: INPUT SEARCH + TOMBOL SCAN QR + TOMBOL PART BARU TERINTEGRASI -->
                        <div class="relative" @click.outside="isSearchOpen = false">
                            <div class="flex items-stretch rounded-xl border border-slate-300 focus-within:ring-2 focus-within:ring-[#0a2342]/20 focus-within:border-[#0a2342] overflow-hidden bg-white shadow-2xs transition">
                                <div class="relative flex-1 min-w-0 flex items-center pl-3">
                                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs pointer-events-none"></i>
                                    <input type="text" 
                                           x-model="searchQuery" 
                                           @focus="if(!showNewCompForm) isSearchOpen = true" 
                                           @input="if(!showNewCompForm) isSearchOpen = true; if(!searchQuery) clearSelectedComponent()" 
                                           placeholder="Ketik kode part atau nama komponen..." 
                                           class="w-full pl-2.5 pr-8 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 border-0 focus:ring-0 focus:outline-none bg-transparent">
                                    
                                    <!-- Tombol Clear Search -->
                                    <button type="button" 
                                            x-show="searchQuery || selectedComponent" 
                                            @click="clearSelectedComponent()" 
                                            class="absolute right-2 text-slate-400 hover:text-slate-600 transition p-1" 
                                            title="Bersihkan pilihan">
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                </div>

                                <!-- Tombol Aksi Dock Sisi Kanan: Scan QR & Part Baru -->
                                <div class="flex items-stretch shrink-0 divide-x divide-slate-200 border-l border-slate-200">
                                    <!-- Scan QR Suffix -->
                                    <button type="button" 
                                            @click="openQrScannerModal()" 
                                            style="background-color: #0a2342 !important; color: #ffffff !important;"
                                            class="px-3.5 py-2 hover:bg-slate-800 active:scale-98 font-bold text-xs flex items-center space-x-1.5 transition select-none cursor-pointer" 
                                            title="Scan QR / Barcode Part via Kamera">
                                        <i class="fa-solid fa-qrcode text-xs"></i>
                                        <span>Scan</span>
                                    </button>

                                    <!-- Tambah Komponen Baru Suffix -->
                                    <button type="button" 
                                            @click="openNewComponentForm()" 
                                            style="background-color: #f59e0b !important; color: #0f172a !important;"
                                            class="px-3.5 py-2 hover:bg-amber-400 active:scale-98 font-extrabold text-xs flex items-center space-x-1.5 transition select-none cursor-pointer" 
                                            title="Daftarkan Part Baru (Non-Master)">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                        <span class="hidden sm:inline">Tambah Komponen Baru</span>
                                        <span class="sm:hidden">Part Baru</span>
                                    </button>
                                </div>
                            </div>

                            <!-- LIVE SEARCH DROPDOWN -->
                            <div x-show="isSearchOpen && !showNewCompForm && searchQuery.trim().length > 0" 
                                 x-cloak 
                                 style="display: none;" 
                                 class="absolute left-0 right-0 z-40 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl max-h-72 overflow-y-auto divide-y divide-slate-100">
                                <template x-for="comp in filteredComponents()" :key="comp.id">
                                    <div @click="selectComponent(comp)" 
                                         class="p-3 hover:bg-slate-50 cursor-pointer transition flex items-center justify-between group">
                                        <div class="min-w-0 pr-2">
                                            <div class="font-extrabold text-xs text-[#0a2342] group-hover:text-blue-600 transition font-mono tracking-tight" x-text="comp.part_number"></div>
                                            <div class="text-[11px] font-semibold text-slate-700 truncate mt-0.5" x-text="comp.name"></div>
                                            <div class="text-[10px] text-slate-400 flex items-center space-x-2 mt-0.5">
                                                <span x-text="'Dim: ' + (comp.dimensions || '-')"></span>
                                                <span>&bull;</span>
                                                <span x-text="'Rak: ' + (comp.rack || '-')"></span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                  :class="comp.stock > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500'"
                                                  x-text="'Stok: ' + comp.stock + ' ' + comp.uom"></span>
                                        </div>
                                    </div>
                                </template>

                                <!-- State Tidak Ditemukan -->
                                <div x-show="filteredComponents().length === 0" class="p-4 text-center space-y-2">
                                    <div class="text-xs font-bold text-slate-700">Part tidak ditemukan di master katalog.</div>
                                    <p class="text-[11px] text-slate-500">Anda dapat langsung mendaftarkannya sebagai komponen baru (non-master).</p>
                                    <button type="button" 
                                            @click="openNewComponentForm(searchQuery)" 
                                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl text-xs font-bold transition">
                                        <i class="fa-solid fa-plus text-[10px]"></i>
                                        <span>Daftarkan Sebagai Part Baru</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- INLINE DRAWER: INPUT KOMPONEN BARU (NON-MASTER) -->
                    <div x-show="showNewCompForm" 
                         x-cloak 
                         x-transition 
                         style="display: none;" 
                         class="border border-amber-300 rounded-2xl p-4 sm:p-5 bg-amber-50/20 space-y-4 shadow-2xs">
                        
                        <div class="flex items-center justify-between border-b border-amber-200/60 pb-2.5">
                            <div class="flex items-center space-x-2 text-amber-900 font-extrabold text-xs sm:text-sm tracking-wide">
                                <i class="fa-solid fa-cube text-amber-600"></i>
                                <span>INPUT KOMPONEN BARU (NON-MASTER)</span>
                            </div>
                            <button type="button" @click="cancelNewComponentForm()" class="text-slate-400 hover:text-slate-600">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>

                        <!-- Field 1: Nama Komponen -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center space-x-1.5">
                                <i class="fa-solid fa-tag text-amber-600 text-[11px]"></i>
                                <span>Nama Komponen <span class="text-rose-500">*</span></span>
                            </label>
                            <input type="text" 
                                   x-model="newComp.name" 
                                   placeholder="Contoh: Air pipa assy karoseri..." 
                                   class="w-full px-3.5 py-2 text-xs font-medium bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                        </div>

                        <!-- Field 2: Tipe Material -->
                        <div class="relative" @click.outside="isMaterialDropdownOpen = false">
                            <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center space-x-1.5">
                                <i class="fa-solid fa-flask text-amber-600 text-[11px]"></i>
                                <span>Tipe Material</span>
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       x-model="newComp.material" 
                                       @focus="isMaterialDropdownOpen = true" 
                                       placeholder="Pilih atau Ketik Material..." 
                                       class="w-full px-3.5 py-2 pr-8 text-xs font-medium bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                                <button type="button" 
                                        @click="isMaterialDropdownOpen = !isMaterialDropdownOpen" 
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </button>
                            </div>

                            <!-- Dropdown List Material -->
                            <div x-show="isMaterialDropdownOpen" 
                                 x-cloak 
                                 style="display: none;" 
                                 class="absolute left-0 right-0 z-30 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100">
                                <template x-for="mat in materialPresets" :key="mat">
                                    <div @click="newComp.material = mat; isMaterialDropdownOpen = false" 
                                         class="px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-amber-50 hover:text-amber-900 cursor-pointer transition" 
                                         x-text="mat"></div>
                                </template>
                            </div>
                        </div>

                        <!-- Field 3: Dimensi P, L, T -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center space-x-1.5">
                                <i class="fa-solid fa-ruler-combined text-amber-600 text-[11px]"></i>
                                <span>Dimensi Ukuran</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2.5">
                                <input type="text" 
                                       x-model="newComp.length_cm" 
                                       placeholder="P (cm)" 
                                       class="w-full px-3 py-2 text-xs text-center font-medium bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                                <input type="text" 
                                       x-model="newComp.width_cm" 
                                       placeholder="L (cm)" 
                                       class="w-full px-3 py-2 text-xs text-center font-medium bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                                <input type="text" 
                                       x-model="newComp.thickness_mm" 
                                       placeholder="T (mm)" 
                                       class="w-full px-3 py-2 text-xs text-center font-medium bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                            </div>
                        </div>

                        <!-- Field 4: Foto Komponen -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center space-x-1.5">
                                <i class="fa-solid fa-camera text-amber-600 text-[11px]"></i>
                                <span>Foto Komponen (Opsional)</span>
                            </label>
                            <div data-drop-zone class="p-2 bg-white border border-slate-200 rounded-xl">
                                <input type="file" 
                                       @change="handleFileUpload($event)" 
                                       accept="image/*" 
                                       class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100 cursor-pointer">
                            </div>
                        </div>

                        <!-- Tombol Aksi Form Komponen Baru -->
                        <div class="grid grid-cols-2 gap-2.5 pt-2">
                            <button type="button" 
                                    @click="cancelNewComponentForm()" 
                                    class="w-full py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                                Batal
                            </button>
                            <button type="button" 
                                    @click="saveAndPreviewNewComponent()" 
                                    :disabled="isSubmittingNewComp" 
                                    style="background-color: #0a2342 !important; color: #ffffff !important;"
                                    class="w-full py-2.5 hover:bg-slate-800 active:scale-98 font-extrabold text-xs rounded-xl shadow-xs transition flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50"
                                    title="Simpan komponen baru dan tampilkan kartu preview">
                                <i class="fa-solid fa-spinner fa-spin" x-show="isSubmittingNewComp"></i>
                                <i class="fa-solid fa-check" x-show="!isSubmittingNewComp"></i>
                                <span x-text="isSubmittingNewComp ? 'Menyimpan...' : 'Tampilkan Preview'">Tampilkan Preview</span>
                            </button>
                        </div>
                    </div>

                    <!-- KARTU PREVIEW KOMPONEN TERPILIH (MANUFACTURING SPEC BOX) -->
                    <div x-show="selectedComponent && !showNewCompForm" 
                         x-cloak 
                         x-transition 
                         style="display: none;" 
                         class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/90 shadow-2xs">
                        
                        <div class="flex flex-col sm:flex-row gap-4 items-start">
                            <!-- Thumbnail Foto Part -->
                            <div class="w-full sm:w-28 sm:h-28 rounded-xl overflow-hidden bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-2xs relative">
                                <img :src="selectedComponent?.image_url" 
                                     :alt="selectedComponent?.name" 
                                     class="w-full h-full object-cover" 
                                     x-on:error="$el.src = '{{ asset('images/placeholder_part.svg') }}'">
                            </div>

                            <!-- Rincian Identitas Part -->
                            <div class="flex-1 min-w-0 space-y-2">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono text-xs font-bold text-slate-900 bg-white px-2 py-0.5 rounded-lg border border-slate-200 shadow-2xs" x-text="selectedComponent?.part_number"></span>
                                    <template x-if="selectedComponent?.is_new_part">
                                        <span class="text-[9px] bg-amber-100 text-amber-900 font-bold px-2 py-0.5 rounded-full border border-amber-300">Part Baru</span>
                                    </template>
                                </div>
                                <h4 class="font-extrabold text-sm text-slate-900 leading-tight" x-text="selectedComponent?.name"></h4>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-1 border-t border-slate-200/60 text-xs">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">Dimensi</span>
                                        <span class="font-bold text-slate-800" x-text="selectedComponent?.dimensions || '-'"></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">Stok Gudang</span>
                                        <span class="font-extrabold text-slate-900" x-text="(selectedComponent?.stock || 0) + ' ' + (selectedComponent?.uom || 'PCS')"></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">Rak Tujuan</span>
                                        <span class="font-mono font-bold text-blue-700" x-text="selectedComponent?.rack || 'Belum di Rak'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 2: PARAMETER PRODUKSI (KUANTITAS & ALUR STASIUN) -->
                    <div class="space-y-4 pt-2 border-t border-slate-100">
                        <h3 class="font-extrabold text-sm text-slate-900 leading-tight flex items-center gap-2">
                            <span class="w-5 h-5 rounded-md bg-[#0a2342] text-white text-[11px] font-bold flex items-center justify-center">2</span>
                            <span>Parameter Kuantitas & Alur Mesin</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Qty dengan Attached UOM Badge -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Jumlah Permintaan (Qty) <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-stretch rounded-xl border border-slate-300 focus-within:ring-2 focus-within:ring-[#0a2342]/20 focus-within:border-[#0a2342] overflow-hidden bg-white shadow-2xs transition">
                                    <input type="number" 
                                           x-model.number="itemQty" 
                                           min="0.1" 
                                           step="any" 
                                           placeholder="0" 
                                           class="w-full px-3.5 py-2 text-xs font-extrabold text-slate-900 border-0 focus:ring-0 focus:outline-none bg-transparent">
                                    <span class="px-3 py-2 bg-slate-100 text-slate-600 font-bold text-xs border-l border-slate-200 select-none flex items-center" 
                                          x-text="selectedComponent?.uom || 'PCS'"></span>
                                </div>
                            </div>

                            <!-- Stasiun Departemen Penerima -->
                            <div class="relative" @click.outside="isDeptDropdownOpen = false">
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Stasiun Mesin Tujuan <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-stretch rounded-xl border border-slate-300 focus-within:ring-2 focus-within:ring-[#0a2342]/20 focus-within:border-[#0a2342] bg-white shadow-2xs overflow-hidden transition">
                                    <input type="text" 
                                           x-model="itemDepartment" 
                                           @focus="isDeptDropdownOpen = true" 
                                           placeholder="Pilih Dept (Urut)" 
                                           class="w-full px-3.5 py-2 text-xs font-bold text-slate-800 placeholder:text-slate-400 border-0 focus:ring-0 focus:outline-none bg-transparent">
                                    
                                    <!-- Tombol Reset Rute -->
                                    <button type="button" 
                                            @click="itemDepartment = ''" 
                                            class="px-2.5 text-slate-400 hover:text-rose-600 transition" 
                                            title="Reset Penerima">
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                </div>

                                <!-- Dropdown List Departemen -->
                                <div x-show="isDeptDropdownOpen" 
                                     x-cloak 
                                     style="display: none;" 
                                     class="absolute left-0 right-0 z-30 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-slate-100">
                                    <template x-for="dept in availableDepartments" :key="dept">
                                        <div @click="selectDepartment(dept)" 
                                             class="px-3.5 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-blue-700 cursor-pointer transition flex items-center justify-between">
                                            <span x-text="dept"></span>
                                            <i class="fa-solid fa-plus text-[10px] text-slate-400"></i>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Pengerjaan Tambahan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Catatan / Instruksi Pengerjaan <span class="text-slate-400 text-[11px] font-normal">(opsional)</span>
                            </label>
                            <input type="text" 
                                   x-model="itemNotes" 
                                   placeholder="Contoh: Potong pelat profil segera / tekuk toleransi 1mm..." 
                                   class="w-full px-3.5 py-2 text-xs font-medium bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#0a2342]/20 focus:border-[#0a2342] focus:outline-none transition">
                        </div>

                        <!-- TOMBOL TAMBAH KE ANTREAN (BRAND PRIMARY NAVY) -->
                        <div class="pt-2">
                            <button type="button" 
                                    @click="addItemToCart()" 
                                    style="background-color: #0a2342 !important; color: #ffffff !important;"
                                    class="w-full py-3 hover:bg-slate-800 active:scale-[0.99] font-extrabold text-xs sm:text-sm rounded-xl shadow-xs transition flex items-center justify-center space-x-2 cursor-pointer select-none">
                                <i class="fa-solid fa-plus text-xs"></i>
                                <span>Tambah ke List</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- KOLOM KANAN (5/12): LIVE CART SIDEBAR      -->
            <!-- ========================================== -->
            <div class="lg:col-span-5 sticky top-6 space-y-4">
                
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-5 space-y-4">
                    
                    <!-- Header Keranjang Siap Kirim -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 border border-blue-200/80 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-clipboard-list"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm text-slate-900 leading-tight">Daftar Item Siap Kirim</h3>
                                <p class="text-[11px] text-slate-500 font-normal">Periksa kembali item sebelum diterbitkan</p>
                            </div>
                        </div>

                        <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-full text-xs font-black" 
                              x-text="cartItems.length + ' Item'"></span>
                    </div>

                    <!-- STATE KOSONG (EMPTY STATE ILUSTRATIF) -->
                    <div x-show="cartItems.length === 0" class="py-12 text-center space-y-2.5">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-lg shadow-2xs">
                            <i class="fa-solid fa-inbox"></i>
                        </div>
                        <div class="text-xs font-extrabold text-slate-700">Antrean Masih Kosong</div>
                        <p class="text-[11px] text-slate-400 max-w-[220px] mx-auto leading-relaxed">
                            Pilih part dan kuantitas di formulir sebelah kiri, lalu tekan tombol <strong>"Tambahkan ke Antrean Tiket"</strong>.
                        </p>
                    </div>

                    <!-- DAFTAR ITEM KARTU DALAM KERANJANG -->
                    <div x-show="cartItems.length > 0" class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1 divide-y divide-slate-100">
                        <template x-for="(item, index) in cartItems" :key="index">
                            <div class="pt-2.5 first:pt-0 flex items-start justify-between space-x-3 group">
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono text-[10px] font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" x-text="item.part_number"></span>
                                        <template x-if="item.is_new">
                                            <span class="text-[9px] bg-amber-100 text-amber-900 font-bold px-1.5 py-0.5 rounded">Part Baru</span>
                                        </template>
                                    </div>
                                    <div class="font-bold text-xs text-slate-900 truncate" x-text="item.name"></div>
                                    <div class="flex items-center space-x-2 text-[11px]">
                                        <span class="font-semibold text-blue-700 bg-blue-50/70 border border-blue-100 px-2 py-0.5 rounded" x-text="item.department_name"></span>
                                        <span class="font-extrabold text-slate-900" x-text="item.quantity + ' ' + (item.uom || 'PCS')"></span>
                                    </div>
                                    <template x-if="item.notes">
                                        <div class="text-[10px] text-slate-400 italic truncate" x-text="'Catatan: ' + item.notes"></div>
                                    </template>
                                </div>

                                <button type="button" 
                                        @click="removeCartItem(index)" 
                                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition shrink-0" 
                                        title="Hapus item ini">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- SUMMARY STATS FOOTER & SUBMIT BUTTON -->
                    <div x-show="cartItems.length > 0" class="pt-3 border-t border-slate-100 space-y-3">
                        <div class="flex items-center justify-between text-xs text-slate-600 font-medium">
                            <span>Total Pesanan:</span>
                            <span class="font-extrabold text-slate-900" x-text="cartItems.length + ' Tiket Work Request'"></span>
                        </div>

                        <!-- TOMBOL KIRIM WORK REQUEST (HIJAU EMERALD SOLID) -->
                        <button type="button" 
                                @click="submitBulkWorkRequests()" 
                                :disabled="isSubmittingBulk" 
                                style="background-color: #16a34a !important; color: #ffffff !important;"
                                class="w-full py-3.5 hover:bg-[#15803d] active:scale-[0.99] font-extrabold text-xs sm:text-sm rounded-xl shadow-md transition flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50">
                            <i class="fa-solid fa-spinner fa-spin text-sm" x-show="isSubmittingBulk"></i>
                            <i class="fa-solid fa-paper-plane text-sm" x-show="!isSubmittingBulk"></i>
                            <span x-show="isSubmittingBulk">Menerbitkan Tiket...</span>
                            <span x-show="!isSubmittingBulk" class="inline-flex items-center gap-1.5">
                                <span>Kirim Work Request</span>
                                <span x-show="cartItems.length > 0" x-text="'(' + cartItems.length + ')'"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- FORM HIDDEN UNTUK PENGIRIMAN BULK VIA POST -->
        <form id="bulkWrForm" method="POST" action="{{ route('work-requests.bulk-store') }}" class="hidden">
            @csrf
            <input type="hidden" name="dept_pengaju" :value="selectedDeptPengaju">
            <input type="hidden" name="cart_data" :value="JSON.stringify(cartItems)">
        </form>

        <!-- 1. MODAL KONFIRMASI INTERAKTIF (PENGGANTI confirm() NATIVE BROWSER) -->
        <div x-show="confirmModal.open" 
             x-cloak 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 text-center space-y-4"
                 @click.outside="closeConfirm()">
                
                <!-- Icon Avatar Header -->
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mx-auto shadow-xs"
                     :class="{
                         'bg-emerald-50 text-emerald-600 border border-emerald-200': confirmModal.type === 'success',
                         'bg-rose-50 text-rose-600 border border-rose-200': confirmModal.type === 'danger',
                         'bg-blue-50 text-blue-600 border border-blue-200': confirmModal.type === 'primary' || !confirmModal.type
                     }">
                    <template x-if="confirmModal.type === 'success'">
                        <i class="fa-solid fa-paper-plane"></i>
                    </template>
                    <template x-if="confirmModal.type === 'danger'">
                        <i class="fa-solid fa-trash-can"></i>
                    </template>
                    <template x-if="confirmModal.type !== 'success' && confirmModal.type !== 'danger'">
                        <i class="fa-solid fa-circle-question"></i>
                    </template>
                </div>

                <!-- Teks Judul & Deskripsi -->
                <div class="space-y-1.5">
                    <h3 class="font-extrabold text-base text-slate-900 leading-snug" x-text="confirmModal.title"></h3>
                    <p class="text-xs text-slate-600 leading-relaxed" x-text="confirmModal.message"></p>
                </div>

                <!-- Tombol Aksi -->
                <div class="grid grid-cols-2 gap-2.5 pt-2">
                    <button type="button" 
                            @click="closeConfirm()" 
                            class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 active:scale-98 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer"
                            x-text="confirmModal.cancelText || 'Batal'">
                    </button>
                    <button type="button" 
                            @click="executeConfirm()" 
                            :style="confirmModal.type === 'success' ? 'background-color: #16a34a !important; color: #ffffff !important;' : (confirmModal.type === 'danger' ? 'background-color: #e11d48 !important; color: #ffffff !important;' : 'background-color: #1877F2 !important; color: #ffffff !important;')"
                            class="w-full py-2.5 px-4 active:scale-98 text-white font-extrabold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center justify-center space-x-1.5"
                            x-text="confirmModal.confirmText || 'Lanjutkan'">
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. MODAL PERINGATAN / INFORMASI INTERAKTIF (PENGGANTI alert() NATIVE BROWSER) -->
        <div x-show="alertModal.open" 
             x-cloak 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 text-center space-y-4"
                 @click.outside="closeAlert()">
                
                <!-- Icon Avatar Header -->
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mx-auto shadow-xs"
                     :class="{
                         'bg-amber-50 text-amber-600 border border-amber-200': alertModal.type === 'warning',
                         'bg-rose-50 text-rose-600 border border-rose-200': alertModal.type === 'error',
                         'bg-blue-50 text-blue-600 border border-blue-200': alertModal.type === 'info',
                         'bg-emerald-50 text-emerald-600 border border-emerald-200': alertModal.type === 'success'
                     }">
                    <template x-if="alertModal.type === 'warning'">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </template>
                    <template x-if="alertModal.type === 'error'">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </template>
                    <template x-if="alertModal.type === 'info'">
                        <i class="fa-solid fa-circle-info"></i>
                    </template>
                    <template x-if="alertModal.type === 'success'">
                        <i class="fa-solid fa-circle-check"></i>
                    </template>
                </div>

                <!-- Teks Judul & Deskripsi -->
                <div class="space-y-1.5">
                    <h3 class="font-extrabold text-base text-slate-900 leading-snug" x-text="alertModal.title"></h3>
                    <p class="text-xs text-slate-600 leading-relaxed" x-text="alertModal.message"></p>
                </div>

                <!-- Tombol Tutup -->
                <div class="pt-2">
                    <button type="button" 
                            @click="closeAlert()" 
                            style="background-color: #0a2342 !important; color: #ffffff !important;"
                            class="w-full py-2.5 px-4 active:scale-98 text-white font-extrabold text-xs rounded-xl shadow-xs transition cursor-pointer">
                        Mengerti
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. MODAL LIVE CAMERA SCANNER QR / BARCODE (html5-qrcode) -->
        <div x-show="isScannerModalOpen" 
             x-cloak 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-sm w-full p-5 shadow-2xl border border-slate-200 space-y-3" @click.outside="closeQrScannerModal()">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div class="flex items-center space-x-2 text-slate-900 font-extrabold text-sm">
                        <i class="fa-solid fa-qrcode text-blue-600"></i>
                        <span>Scan Barcode / QR Part</span>
                    </div>
                    <button type="button" @click="closeQrScannerModal()" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <div class="rounded-2xl overflow-hidden bg-black flex items-center justify-center min-h-[220px]">
                    <div id="wr-camera-reader" class="w-full"></div>
                </div>

                <p class="text-[11px] text-slate-500 text-center">
                    Arahkan kamera ke QR Code atau Barcode pada etiket komponen.
                </p>
            </div>
        </div>

    </div>

    <script>
        function createWrCartApp(initialComponents = [], availableDepts = [], defaultDept = '') {
            return {
                componentsList: initialComponents || [],
                availableDepartments: availableDepts || [],
                selectedDeptPengaju: defaultDept || 'Dept Gudang',

                // Pencarian Part
                searchQuery: '',
                isSearchOpen: false,
                selectedComponent: null,

                // Input Qty & Penerima
                itemQty: '',
                itemDepartment: '',
                isDeptDropdownOpen: false,
                itemNotes: '',

                // Keranjang Cart
                cartItems: [],
                isSubmittingBulk: false,

                // Modal Dialog Interaktif (Pengganti alert & confirm bawaan browser)
                alertModal: {
                    open: false,
                    title: '',
                    message: '',
                    type: 'warning'
                },
                confirmModal: {
                    open: false,
                    title: '',
                    message: '',
                    confirmText: 'Lanjutkan',
                    cancelText: 'Batal',
                    type: 'primary',
                    onConfirm: null
                },

                showAlert(title, message, type = 'warning') {
                    this.alertModal = {
                        open: true,
                        title: title || 'Pemberitahuan',
                        message: message || '',
                        type: type
                    };
                },

                closeAlert() {
                    this.alertModal.open = false;
                },

                showConfirm({ title, message, confirmText, cancelText, type, onConfirm }) {
                    this.confirmModal = {
                        open: true,
                        title: title || 'Konfirmasi Tindakan',
                        message: message || '',
                        confirmText: confirmText || 'Ya, Lanjutkan',
                        cancelText: cancelText || 'Batal',
                        type: type || 'primary',
                        onConfirm: onConfirm
                    };
                },

                executeConfirm() {
                    const callback = this.confirmModal.onConfirm;
                    this.confirmModal.open = false;
                    if (typeof callback === 'function') {
                        callback();
                    }
                },

                closeConfirm() {
                    this.confirmModal.open = false;
                    this.confirmModal.onConfirm = null;
                },

                // Form Komponen Baru (Non-Master)
                showNewCompForm: false,
                isSubmittingNewComp: false,
                isMaterialDropdownOpen: false,
                materialPresets: [
                    'Plat Hitam',
                    'Plat Stainless',
                    'Plat Steel Plate ST52',
                    'Plat Hardox',
                    'Plat SM490',
                    'Pipa Seamless',
                    'Besi Beton Pejal',
                    'Profil UNP'
                ],
                newComp: {
                    name: '',
                    material: 'Plat Hitam',
                    length_cm: '',
                    width_cm: '',
                    thickness_mm: '',
                    file: null
                },

                // Scanner Camera
                isScannerModalOpen: false,
                html5QrScanner: null,

                init() {
                    const preselectedId = '{{ request("component_id") }}';
                    if (preselectedId) {
                        const comp = this.componentsList.find(c => c.id == preselectedId);
                        if (comp) {
                            this.selectComponent(comp);
                        }
                    }
                },

                filteredComponents() {
                    if (!this.searchQuery || !this.searchQuery.trim()) {
                        return this.componentsList.slice(0, 25);
                    }
                    const q = this.searchQuery.toLowerCase().trim();
                    return this.componentsList.filter(c => {
                        return (c.name && c.name.toLowerCase().includes(q)) || 
                               (c.part_number && c.part_number.toLowerCase().includes(q));
                    }).slice(0, 30);
                },

                selectComponent(comp) {
                    if (!comp) return;
                    this.selectedComponent = comp;
                    this.searchQuery = comp.name;
                    this.isSearchOpen = false;
                    this.showNewCompForm = false;

                    if (!this.itemDepartment) {
                        this.smartSuggestRouting(comp.name);
                    }
                },

                clearSelectedComponent() {
                    this.selectedComponent = null;
                    this.searchQuery = '';
                    this.isSearchOpen = false;
                },

                smartSuggestRouting(name) {
                    const upper = (name || '').toUpperCase();
                    if (upper.includes('UNP')) {
                        this.itemDepartment = 'Dept UNP';
                    } else if (upper.includes('BENDING') || upper.includes('SIKU') || upper.includes('COVER') || upper.includes('BUMPER')) {
                        this.itemDepartment = 'Dept Laser Cutting';
                    } else if (upper.includes('HANDLE') || upper.includes('PIN') || upper.includes('AS ') || upper.includes('BOLT')) {
                        this.itemDepartment = 'Dept M1';
                    } else if (this.availableDepartments.length > 0) {
                        this.itemDepartment = this.availableDepartments[0];
                    }
                },

                selectDepartment(dept) {
                    if (!this.itemDepartment || !this.itemDepartment.trim()) {
                        this.itemDepartment = dept;
                    } else if (!this.itemDepartment.includes(dept)) {
                        this.itemDepartment += ' -> ' + dept;
                    }
                    this.isDeptDropdownOpen = false;
                },

                openNewComponentForm(prefillName = '') {
                    this.newComp = {
                        name: prefillName ? prefillName.trim() : (this.searchQuery ? this.searchQuery.trim() : ''),
                        material: 'Plat Hitam',
                        length_cm: '',
                        width_cm: '',
                        thickness_mm: '',
                        file: null
                    };
                    this.selectedComponent = null;
                    this.isSearchOpen = false;
                    this.showNewCompForm = true;
                },

                cancelNewComponentForm() {
                    this.showNewCompForm = false;
                },

                handleFileUpload(event) {
                    this.newComp.file = event.target.files[0] || null;
                },

                async saveAndPreviewNewComponent() {
                    if (!this.newComp.name || !this.newComp.name.trim()) {
                        this.showAlert('Nama Komponen Wajib Diisi', 'Silakan masukkan nama komponen baru terlebih dahulu sebelum menampilkan preview.', 'warning');
                        return;
                    }

                    this.isSubmittingNewComp = true;

                    try {
                        const formData = new FormData();
                        formData.append('name', this.newComp.name.trim());
                        formData.append('category', 'subframe');
                        formData.append('uom', 'PCS');
                        
                        if (this.newComp.length_cm) {
                            formData.append('length_mm', (parseFloat(this.newComp.length_cm) * 10).toString());
                        }
                        if (this.newComp.width_cm) {
                            formData.append('width_or_diameter_mm', (parseFloat(this.newComp.width_cm) * 10).toString());
                        }
                        if (this.newComp.thickness_mm) {
                            formData.append('thickness_or_height_mm', this.newComp.thickness_mm);
                        }
                        if (this.newComp.material) {
                            formData.append('specification', this.newComp.material);
                        }
                        if (this.newComp.file) {
                            formData.append('image', this.newComp.file);
                        }

                        const response = await fetch("{{ route('api.components.quick-store') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const res = await response.json();
                        if (res.success && res.component) {
                            const comp = res.component;
                            
                            let dimStr = '-';
                            if (this.newComp.length_cm || this.newComp.width_cm || this.newComp.thickness_mm) {
                                dimStr = `P: ${this.newComp.length_cm || 0}cm x L: ${this.newComp.width_cm || 0}cm x T: ${this.newComp.thickness_mm || 0}mm`;
                            }

                            const formattedComp = {
                                id: comp.id,
                                part_number: comp.part_number || 'Baru',
                                name: comp.name,
                                category: comp.category || 'subframe',
                                uom: comp.uom || 'PCS',
                                specs: comp.specification || '',
                                image_url: comp.image_url || '{{ asset('images/placeholder_part.svg') }}',
                                dimensions: dimStr,
                                routing: this.itemDepartment || '-',
                                stock: 0,
                                min_stock: 0,
                                rack: 'Belum di Rak',
                                is_new_part: true
                            };

                            this.componentsList.unshift(formattedComp);
                            this.selectedComponent = formattedComp;
                            this.searchQuery = formattedComp.name;
                            this.showNewCompForm = false;

                            if (!this.itemDepartment) {
                                this.smartSuggestRouting(formattedComp.name);
                            }
                        } else {
                            this.showAlert('Gagal Mendaftarkan Komponen', res.message || 'Gagal mendaftarkan komponen baru ke sistem.', 'error');
                        }
                    } catch (e) {
                        this.showAlert('Kesalahan Koneksi', 'Terjadi kesalahan komunikasi dengan server: ' + e.message, 'error');
                    } finally {
                        this.isSubmittingNewComp = false;
                    }
                },

                addItemToCart() {
                    if (!this.selectedComponent) {
                        this.showAlert('Komponen Belum Dipilih', 'Silakan cari dan pilih komponen dari katalog atau daftarkan komponen baru terlebih dahulu.', 'warning');
                        return;
                    }

                    const qty = parseFloat(this.itemQty);
                    if (!qty || qty <= 0) {
                        this.showAlert('Kuantitas Belum Sesuai', 'Jumlah kuantitas pesanan (Qty) wajib diisi lebih dari 0.', 'warning');
                        return;
                    }

                    const dept = this.itemDepartment ? this.itemDepartment.trim() : '';
                    if (!dept) {
                        this.showAlert('Penerima Belum Dipilih', 'Silakan tentukan departemen penerima terlebih dahulu.', 'warning');
                        return;
                    }

                    this.cartItems.push({
                        component_id: this.selectedComponent.id,
                        name: this.selectedComponent.name,
                        part_number: this.selectedComponent.part_number,
                        uom: this.selectedComponent.uom || 'PCS',
                        quantity: qty,
                        department_name: dept,
                        notes: this.itemNotes ? this.itemNotes.trim() : '',
                        is_new: this.selectedComponent.is_new_part || strStartsWith(this.selectedComponent.part_number, 'NEW-') || this.selectedComponent.part_number === 'Baru'
                    });

                    this.selectedComponent = null;
                    this.searchQuery = '';
                    this.itemQty = '';
                    this.itemNotes = '';
                },

                removeCartItem(index) {
                    const item = this.cartItems[index];
                    const itemName = item ? item.name : 'item ini';
                    this.showConfirm({
                        title: 'Hapus Item dari Antrean?',
                        message: `Apakah Anda yakin ingin menghapus "${itemName}" dari antrean siap kirim?`,
                        confirmText: 'Ya, Hapus',
                        cancelText: 'Batal',
                        type: 'danger',
                        onConfirm: () => {
                            this.cartItems.splice(index, 1);
                        }
                    });
                },

                submitBulkWorkRequests() {
                    if (this.cartItems.length === 0) {
                        this.showAlert('Antrean Masih Kosong', 'Daftar item siap kirim masih kosong. Silakan tambahkan minimal 1 komponen terlebih dahulu sebelum mengirim.', 'warning');
                        return;
                    }

                    const count = this.cartItems.length;
                    this.showConfirm({
                        title: 'Konfirmasi Terbitkan Tiket',
                        message: `Kirim dan terbitkan ${count} tiket Work Request sekarang ke stasiun departemen penerima?`,
                        confirmText: 'Ya, Terbitkan Tiket',
                        cancelText: 'Batal',
                        type: 'success',
                        onConfirm: () => {
                            this.isSubmittingBulk = true;
                            document.getElementById('bulkWrForm').submit();
                        }
                    });
                },

                // Scanner Camera
                openQrScannerModal() {
                    this.isScannerModalOpen = true;
                    this.$nextTick(() => {
                        this.startScanner();
                    });
                },

                closeQrScannerModal() {
                    this.stopScanner();
                    this.isScannerModalOpen = false;
                },

                startScanner() {
                    if (typeof Html5Qrcode === 'undefined') {
                        this.showAlert('Scanner Belum Siap', 'Pustaka kamera scan QR belum dimuat dengan sempurna.', 'warning');
                        return;
                    }

                    if (this.html5QrScanner) {
                        this.stopScanner();
                    }

                    try {
                        this.html5QrScanner = new Html5Qrcode('wr-camera-reader');
                        this.html5QrScanner.start(
                            { facingMode: 'environment' },
                            { fps: 10, qrbox: { width: 200, height: 200 } },
                            (text) => {
                                this.handleScannedCode(text);
                            },
                            (err) => {}
                        ).catch(e => {
                            console.warn('Scanner camera error:', e);
                        });
                    } catch (e) {
                        console.warn('Scanner init error:', e);
                    }
                },

                async stopScanner() {
                    if (this.html5QrScanner) {
                        const scanner = this.html5QrScanner;
                        this.html5QrScanner = null;
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

                handleScannedCode(code) {
                    if (!code) return;
                    const cleanCode = code.trim();
                    this.closeQrScannerModal();

                    const comp = this.componentsList.find(c => {
                        return c.part_number.toLowerCase() === cleanCode.toLowerCase() || 
                               cleanCode.includes(c.part_number) ||
                               (c.name && c.name.toLowerCase() === cleanCode.toLowerCase());
                    });

                    if (comp) {
                        this.selectComponent(comp);
                    } else {
                        this.searchQuery = cleanCode;
                        this.isSearchOpen = true;
                        this.showAlert('Komponen Tidak Ditemukan', `Kode "${cleanCode}" berhasil dipindai, namun tidak ditemukan di katalog master. Silakan pilih dari hasil pencarian atau daftarkan sebagai komponen baru.`, 'info');
                    }
                }
            };
        }

        function strStartsWith(str, prefix) {
            return str && typeof str === 'string' && str.startsWith(prefix);
        }
    </script>
</x-app-layout>

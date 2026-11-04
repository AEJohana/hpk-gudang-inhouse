<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-1 sm:gap-1.5">
            <div>
                <a href="{{ route('work-requests.index') }}" 
                   class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer shrink-0" 
                   title="Kembali ke Antrian WRI">
                    <i class="fa-solid fa-arrow-left text-[11px] text-slate-500"></i>
                    <span>Kembali</span>
                </a>
            </div>
            <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight leading-tight">
                Membuat WR (Work Request)
            </h2>
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
        ];
    })->values();
@endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="membuatWrForm(@js($componentsJsonData))">
        <form method="POST" action="{{ route('work-requests.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf

            @if ($components->isEmpty())
                <div class="p-4 bg-amber-50 border border-amber-300 rounded-2xl flex items-start space-x-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5"></i>
                    <div class="flex-1">
                        <h4 class="font-bold text-xs text-amber-900">Belum Ada Master Komponen di Gudang</h4>
                        <p class="text-xs text-amber-700 mt-0.5">
                            Untuk membuat Work Request (pesanan produksi ke mesin center), silakan daftarkan master komponen terlebih dahulu.
                        </p>
                        <div class="mt-2.5">
                            <button type="button" @click="showQuickAddModal = true" class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-bold transition shadow-xs">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                <span>Tambah Komponen Sekarang</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Bagian 1: Identitas Pemohon, Waktu & Tujuan Penggunaan -->
            <div class="p-5 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-id-badge text-indigo-600 text-sm"></i>
                        <h3 class="font-bold text-sm text-slate-900">1. Identitas Pemohon, Waktu & Tujuan Penggunaan</h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- 1. Dept Pengaju (Terkunci Penuh) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Departemen Pengaju (Pemohon)
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   readonly 
                                   value="{{ $userDept }}" 
                                   class="w-full px-3.5 py-2 pl-9 text-xs border border-slate-200 bg-slate-100/90 rounded-xl font-bold text-slate-800 cursor-not-allowed select-none">
                            <i class="fa-solid fa-building-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">
                            Diajukan oleh: <strong class="text-slate-600">{{ auth()->user()->name }}</strong>
                        </p>
                    </div>

                    <!-- 2. Tanggal Pengajuan (Aktual Terkunci) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Tanggal Pengajuan (Aktual)
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   readonly 
                                   value="{{ now()->translatedFormat('d F Y') }} (Hari Ini)" 
                                   class="w-full px-3.5 py-2 pl-9 text-xs border border-slate-200 bg-slate-100/90 rounded-xl font-bold text-slate-800 cursor-not-allowed select-none">
                            <i class="fa-regular fa-calendar-check absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Tanggal tercatat otomatis sesuai waktu server.</p>
                    </div>

                    <!-- 3. Tingkat Prioritas -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                Tingkat Prioritas <span class="text-rose-500">*</span>
                            </label>
                        </div>
                        <select name="priority" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-bold text-slate-800">
                            <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>Normal (Produksi Rutin)</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Tinggi (High Priority)</option>
                            <option value="urgent_line_stop" {{ old('priority') == 'urgent_line_stop' ? 'selected' : '' }}>URGENT (Line Stop / Bypass)</option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Status URGENT auto-bypass antrian otorisasi.</p>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Pemilihan Komponen & Kotak Identitas Visual -->
            <div class="p-5 bg-white rounded-xl border border-slate-200 space-y-4 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-2">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-cubes-stacked text-amber-600 text-sm"></i>
                        <h3 class="font-bold text-sm text-slate-900">2. Komponen & Kuantitas Pesanan</h3>
                    </div>

                    <!-- Tombol Cepat Tambah Komponen Baru (Modal) -->
                    <button type="button" 
                            @click="openQuickAddModal()" 
                            class="self-start sm:self-auto px-2.5 py-1 text-xs bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 font-bold rounded-lg transition shadow-2xs flex items-center space-x-1 cursor-pointer">
                        <i class="fa-solid fa-plus text-[10px] text-amber-600"></i>
                        <span>Daftarkan Komponen Baru</span>
                    </button>
                </div>

                <!-- TOAST NOTIFIKASI SUKSES PART BARU -->
                <div x-show="quickAddSuccessToast" 
                     x-transition 
                     x-cloak 
                     style="display: none;" 
                     class="p-3 bg-emerald-50 border border-emerald-300 text-emerald-800 rounded-xl text-xs flex items-center justify-between shadow-xs">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                        <span>Komponen baru berhasil didaftarkan dan langsung terpilih di formulir pesanan!</span>
                    </div>
                    <button type="button" @click="quickAddSuccessToast = false" class="text-emerald-700 hover:text-emerald-900 font-bold px-2 py-0.5">
                        &times;
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <!-- Searchable Combobox Komponen (Opsi A) -->
                    <div class="sm:col-span-8 relative" @click.outside="isSearchOpen = false">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                Cari & Pilih Komponen In-House <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[10px] text-slate-400 font-semibold" x-text="componentsList.length + ' Master Part Tersedia'"></span>
                        </div>

                        <!-- Kotak Input Pencarian Interaktif -->
                        <div class="relative">
                            <input type="text" 
                                   x-model="searchQuery" 
                                   @focus="isSearchOpen = true" 
                                   @input="isSearchOpen = true; if(!searchQuery) clearSelection()"
                                   placeholder="Cari nama atau kode part (contoh: BUMPER, ADR)..." 
                                   class="w-full px-3.5 py-2 pl-9 pr-9 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-medium text-slate-900 transition shadow-2xs">
                            
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>

                            <!-- Tombol Clear / Hapus Pilihan -->
                            <button type="button" 
                                    x-show="searchQuery || selectedComponentId" 
                                    @click="clearSelection()" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                                    title="Bersihkan pilihan">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>

                        <!-- Dropdown Panel Hasil Pencarian (Live Filter) -->
                        <div x-show="isSearchOpen" 
                             x-cloak 
                             style="display: none;"
                             class="absolute left-0 right-0 z-30 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl max-h-72 overflow-y-auto divide-y divide-slate-100">
                            
                            <!-- Daftar Hasil Filter -->
                            <template x-for="comp in filteredComponents()" :key="comp.id">
                                <div @click="selectComponent(comp)" 
                                     :class="{ 'bg-amber-50/80 font-bold': selectedComponentId == comp.id }"
                                     class="p-2.5 hover:bg-slate-50 cursor-pointer flex items-center space-x-3 transition">
                                    
                                    <!-- Thumbnail Foto Part -->
                                    <img :src="comp.image_url" 
                                         :alt="comp.name" 
                                         class="w-10 h-10 rounded-lg object-cover bg-slate-100 border border-slate-200 flex-shrink-0"
                                         x-on:error="$el.src = '{{ asset('images/placeholder_part.svg') }}'">
                                    
                                    <!-- Rincian Identitas Part -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono text-[11px] font-bold text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded border border-amber-200" x-text="comp.part_number"></span>
                                            <span class="text-[10px] text-slate-500 font-semibold uppercase" x-text="'[' + (comp.category || 'subframe') + ']'"></span>
                                        </div>
                                        <div class="font-bold text-xs text-slate-900 truncate mt-0.5" x-text="comp.name"></div>
                                        <div class="text-[10px] text-slate-400 flex items-center space-x-2 mt-0.5">
                                            <span x-text="'Dimensi: ' + comp.dimensions"></span>
                                            <span>&bull;</span>
                                            <span x-text="'Rak: ' + comp.rack"></span>
                                        </div>
                                    </div>

                                    <!-- Ketersediaan Stok -->
                                    <div class="text-right flex-shrink-0">
                                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full"
                                              :class="comp.stock > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600'"
                                              x-text="'Stok: ' + comp.stock + ' ' + comp.uom"></span>
                                    </div>
                                </div>
                            </template>

                            <!-- Kondisi: Part Tidak Ditemukan (Fallback Pendaftaran Baru Cepat) -->
                            <div x-show="filteredComponents().length === 0" class="p-4 text-center space-y-2">
                                <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-600 mx-auto flex items-center justify-center text-sm border border-amber-200">
                                    <i class="fa-solid fa-cube"></i>
                                </div>
                                <div class="text-xs font-bold text-slate-800">
                                    Part "<span x-text="searchQuery" class="text-amber-700 font-extrabold"></span>" belum terdaftar di sistem.
                                </div>
                                <p class="text-[11px] text-slate-500">
                                    Daftarkan part baru ini langsung ke sistem agar dapat segera diproses stasiun mesin.
                                </p>
                                <button type="button" 
                                        @click="openQuickAddModal(searchQuery)" 
                                        class="mt-1 inline-flex items-center space-x-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Daftarkan Part Baru Ini</span>
                                </button>
                            </div>
                        </div>

                        <!-- Hidden Native Select untuk POST Form (Menjaga Kompatibilitas) -->
                        <select name="component_id" 
                                id="componentSelect" 
                                required 
                                x-model="selectedComponentId" 
                                class="hidden">
                            <option value="">-- Cari / Pilih Komponen Karoseri --</option>
                            @foreach($components as $comp)
                                <option value="{{ $comp->id }}" 
                                        data-part="{{ $comp->part_number }}"
                                        data-name="{{ $comp->name }}"
                                        data-category="{{ $comp->category }}"
                                        data-uom="{{ $comp->uom ?: 'PCS' }}"
                                        data-specs="{{ $comp->specification }}"
                                        data-image="{{ $comp->image_url }}"
                                        data-length="{{ $comp->length_mm }}"
                                        data-width="{{ $comp->width_or_diameter_mm }}"
                                        data-thickness="{{ $comp->thickness_or_height_mm }}"
                                        data-dimensions="{{ $comp->dimension_formatted }}"
                                        data-stock="{{ $comp->total_stock }}"
                                        data-min-stock="{{ $comp->minimum_stock ?: 0 }}"
                                        data-rack="{{ $comp->default_full_location_code }}">
                                    {{ $comp->part_number }} &bull; {{ $comp->name }} ({{ $comp->uom ?: 'PCS' }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Pilih part karoseri atau daftarkan baru jika belum ada.</p>
                    </div>

                    <!-- Jumlah Permintaan & UoM (Integrated Anti-Overflow Input Group) -->
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Jumlah Permintaan <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative flex items-stretch rounded-xl border border-slate-300 focus-within:ring-2 focus-within:ring-amber-500 focus-within:border-amber-500 overflow-hidden bg-white shadow-2xs">
                            <input type="number" 
                                   name="quantity_requested" 
                                   required 
                                   min="0.1" 
                                   step="any" 
                                   value="{{ old('quantity_requested', 20) }}"
                                   placeholder="0"
                                   class="w-full min-w-0 flex-1 px-3.5 py-2 text-xs border-0 focus:ring-0 focus:outline-none font-black text-slate-900">
                            <span class="inline-flex items-center px-3.5 py-2 bg-slate-100 text-slate-700 font-extrabold text-xs border-l border-slate-200 select-none whitespace-nowrap" 
                                  x-text="selectedUom || 'PCS'"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Jumlah unit yang dipesan.</p>
                    </div>
                </div>

                <!-- KOTAK IDENTITAS KOMPONEN (Visual Card) -->
                <div x-show="selectedComponentId" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mt-4 p-4 sm:p-5 bg-gradient-to-br from-slate-50 via-white to-amber-50/30 rounded-2xl border border-amber-200/80 shadow-xs">
                    
                    <div class="flex flex-col sm:flex-row gap-4 items-start">
                        <!-- Thumbnail / Foto Komponen -->
                        <div class="w-full sm:w-40 sm:h-40 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0 relative group">
                            <img :src="selectedImage" 
                                 :alt="selectedName" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                 x-on:error="$el.src = '{{ asset('images/placeholder_part.svg') }}'">
                            
                            <a :href="selectedImage" target="_blank" x-show="selectedImage && !selectedImage.includes('placeholder')" class="absolute bottom-1.5 right-1.5 p-1 bg-black/60 hover:bg-black/80 text-white rounded text-[10px] opacity-0 group-hover:opacity-100 transition" title="Lihat Ukuran Penuh">
                                <i class="fa-solid fa-up-right-from-square"></i>
                            </a>
                        </div>

                        <!-- Data Rincian Identitas Part -->
                        <div class="flex-1 space-y-3 w-full">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono text-xs font-bold text-amber-900 bg-amber-100 px-2.5 py-0.5 rounded-lg border border-amber-200" x-text="selectedPartNumber"></span>
                                    <span class="text-[10px] text-slate-400 uppercase font-semibold">Identitas Part Karoseri</span>
                                </div>
                                <h4 class="font-extrabold text-base text-slate-900 mt-1" x-text="selectedName"></h4>
                                <p class="text-xs text-slate-500 mt-0.5" x-text="selectedSpecs || 'Spesifikasi standar fabrikasi karoseri PT Hydraxle Perkasa.'"></p>
                            </div>

                            <!-- KOTAK UKURAN & DIMENSI TEKNIS -->
                            <div class="p-3 bg-white rounded-xl border border-slate-200/90 shadow-2xs">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5 flex items-center space-x-1">
                                    <i class="fa-solid fa-ruler-combined text-indigo-500 text-[10px]"></i>
                                    <span>Dimensi / Ukuran Komponen:</span>
                                </span>
                                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                    <div class="p-1.5 bg-slate-50 rounded-lg border border-slate-100">
                                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Panjang (P)</span>
                                        <span class="font-bold text-slate-800" x-text="lengthMm ? lengthMm + ' mm' : '-'"></span>
                                    </div>
                                    <div class="p-1.5 bg-slate-50 rounded-lg border border-slate-100">
                                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Lebar / Dia. (L/D)</span>
                                        <span class="font-bold text-slate-800" x-text="widthMm ? (widthMm.includes('⌀') ? widthMm : widthMm + ' mm') : '-'"></span>
                                    </div>
                                    <div class="p-1.5 bg-slate-50 rounded-lg border border-slate-100">
                                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Tebal / Tggi (T)</span>
                                        <span class="font-bold text-slate-800" x-text="thicknessMm ? (thicknessMm.includes('⌀') ? thicknessMm : thicknessMm + ' mm') : '-'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- KOTAK STATUS SALDO & LOKASI SIMPAN GUDANG -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-1 text-xs">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Saldo Fisik Gudang</span>
                                    <span class="font-extrabold text-sm text-slate-900" x-text="totalStock + ' ' + selectedUom"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Batas Minimum Stok</span>
                                    <span class="font-bold text-slate-700" x-text="minimumStock + ' ' + selectedUom"></span>
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Lokasi Rak Standar</span>
                                    <span class="font-mono font-bold text-indigo-700" x-text="rackCode || 'Belum di Rak'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Departemen Tujuan & Alur Proses Fabrikasi (Multi-Dept) -->
            <div class="p-5 bg-slate-50 rounded-xl border border-slate-200 space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-route text-purple-600 text-sm"></i>
                        <h3 class="font-bold text-sm text-slate-900">3. Departemen Tujuan & Alur Proses Fabrikasi</h3>
                    </div>
                    <button type="button" 
                            @click="addDepartment()" 
                            class="px-2.5 py-1 text-xs bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition shadow-xs flex items-center space-x-1">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Tambah Proses</span>
                    </button>
                </div>

                <p class="text-xs text-slate-500">
                    Tentukan urutan stasiun mesin pengerjaan part.
                </p>

                <!-- LIVE ROUTING PREVIEW BADGE (SERAGAM MONOKROM SLATE) -->
                <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-2">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-diagram-project me-1 text-slate-600"></i> Alur Fabrikasi & Logistik MH:
                    </span>
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <template x-for="(dept, idx) in targetDepartments" :key="idx">
                            <div class="flex items-center">
                                <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-300 flex items-center space-x-1">
                                    <span class="w-4 h-4 rounded-full bg-slate-700 text-white text-[10px] flex items-center justify-center me-1" x-text="idx + 1"></span>
                                    <span x-text="dept.department_name ? dept.department_name.replace('Dept ', '') : 'Pilih Mesin'"></span>
                                </span>
                                <span x-show="idx < targetDepartments.length - 1" class="text-slate-400 mx-1.5 text-xs flex items-center">
                                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 me-1"></i>
                                    <span class="text-[9px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold border border-slate-200">MH Transfer</span>
                                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 ms-1"></i>
                                </span>
                            </div>
                        </template>

                        <!-- MH Pengantaran Kembali ke Pengaju / Gudang -->
                        <span class="text-slate-400 mx-1 text-xs flex items-center">
                            <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 me-1"></i>
                            <span class="text-[9px] bg-slate-100 text-slate-700 border border-slate-300 px-2 py-0.5 rounded font-bold flex items-center">
                                <i class="fa-solid fa-truck-ramp-box me-1 text-slate-500"></i> MH Antar ke Pengaju
                            </span>
                            <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 ms-1"></i>
                        </span>

                        <!-- Titik Akhir: Gudang / Selesai -->
                        <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-300 flex items-center">
                            <i class="fa-solid fa-warehouse me-1 text-slate-600"></i> Gudang (Diterima)
                        </span>
                    </div>
                </div>

                <!-- DAFTAR TAHAPAN DEPARTEMEN TUJUAN -->
                <div class="space-y-2.5">
                    <template x-for="(dept, index) in targetDepartments" :key="index">
                        <div class="p-3 rounded-xl border border-slate-200 bg-white flex flex-col sm:flex-row sm:items-center gap-2.5 shadow-2xs">
                            <!-- Step Badge -->
                            <div class="flex items-center space-x-2 sm:w-24 flex-shrink-0">
                                <span class="w-5 h-5 rounded-full bg-purple-700 text-white font-bold text-xs flex items-center justify-center flex-shrink-0" x-text="index + 1"></span>
                                <span class="font-bold text-xs text-slate-800 whitespace-nowrap" x-text="'Tahap ' + (index + 1)"></span>
                            </div>

                            <!-- Pilihan Departemen Fabrikasi (14 Stasiun Mesin) -->
                            <div class="flex-1">
                                <select :name="'target_departments[' + index + '][department_name]'" 
                                        x-model="dept.department_name" 
                                        required 
                                        class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none font-bold text-slate-800">
                                    <option value="">-- Pilih Stasiun Mesin Tujuan --</option>
                                    @foreach($fabricationDepartments as $fDept)
                                        <option value="{{ $fDept->name }}">
                                            [{{ $fDept->code }}] {{ $fDept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Catatan Instruksi per Tahap -->
                            <div class="flex-1">
                                <input type="text" 
                                       :name="'target_departments[' + index + '][notes]'" 
                                       x-model="dept.notes" 
                                       placeholder="Instruksi khusus tahapan ini (opsional)..." 
                                       class="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none text-slate-700">
                            </div>

                            <!-- Tombol Hapus Tahap -->
                            <button type="button" 
                                    @click="removeDepartment(index)" 
                                    x-show="targetDepartments.length > 1" 
                                    class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition self-end sm:self-center" 
                                    title="Hapus tahapan">
                                <i class="fa-solid fa-trash-can text-sm"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Bagian 4: Catatan Fabrikasi Keseluruhan -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Instruksi Khusus / Catatan Fabrikasi Keseluruhan (Opsional)
                </label>
                <textarea name="notes" 
                          rows="2" 
                          placeholder="Tuliskan catatan atau instruksi khusus pengerjaan (opsional)..." 
                          class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none text-slate-800"></textarea>
            </div>

            <!-- Tombol Aksi Submit -->
            <div class="pt-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2.5">
                <a href="{{ route('work-requests.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center justify-center space-x-1.5 text-center">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Kembali / Batal</span>
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black rounded-xl shadow-md transition flex items-center justify-center space-x-2 text-center cursor-pointer">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Kirim & Terbitkan WR</span>
                </button>
            </div>
        </form>

        <!-- MODAL CEPAT: DAFTARKAN KOMPONEN BARU ON-THE-FLY -->
        <div x-show="showQuickAddModal" 
             x-cloak 
             style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4" @click.outside="showQuickAddModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-cube"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base text-slate-900">Registrasi Komponen Baru</h3>
                            <p class="text-xs text-slate-500">Daftarkan part baru langsung ke sistem tanpa berpindah menu</p>
                        </div>
                    </div>
                    <button type="button" @click="showQuickAddModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitQuickAdd()" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Nama Komponen <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" x-model="newComp.name" required placeholder="Contoh: PLANGKON BUMPER BELAKANG ADR 25" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl font-bold text-slate-900">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Kode Komponen (Opsional)
                            </label>
                            <input type="text" x-model="newComp.part_number" placeholder="Otomatis jika kosong" class="w-full px-3 py-1.5 border border-slate-300 rounded-xl font-mono text-slate-800">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Kategori Part (Opsional)
                            </label>
                            <select x-model="newComp.category" class="w-full px-3 py-1.5 border border-slate-300 rounded-xl font-bold text-slate-800">
                                <option value="">-- Bawaan: Subframe --</option>
                                <option value="subframe">Subframe (Sasis Karoseri)</option>
                                <option value="karoseri">Karoseri (Panel & Bodi)</option>
                                <option value="assy">Assy (Sub-Assembly)</option>
                                <option value="accessories">Aksesoris (Engsel, Bracket)</option>
                                <option value="hydraulic">Hydraulic (Silinder/Pump)</option>
                                <option value="raw_material">Raw Material (Plat/Baja)</option>
                                <option value="fastener">Fastener (Baut, U-Bolt)</option>
                                <option value="potong_chassis">Potong Chassis</option>
                                <option value="spo">SPO (Special Purpose)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Satuan (UoM)
                            </label>
                            <input type="text" x-model="newComp.uom" placeholder="PCS / SET / BATANG" class="w-full px-3 py-1.5 border border-slate-300 rounded-xl font-bold text-slate-800">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Material / Spesifikasi
                            </label>
                            <input type="text" x-model="newComp.specification" placeholder="Contoh: Plat SPHC 6mm / Pipa Seamless..." class="w-full px-3 py-1.5 border border-slate-300 rounded-xl">
                        </div>
                    </div>

                    <!-- Dimensi P x L x T -->
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Ukuran Dimensi (Panjang x Lebar/Dia x Tebal/Tinggi) mm
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="text" x-model="newComp.length_mm" placeholder="P (mm)" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-center font-bold">
                            <input type="text" x-model="newComp.width_or_diameter_mm" placeholder="L / ⌀ (mm)" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-center font-bold">
                            <input type="text" x-model="newComp.thickness_or_height_mm" placeholder="T (mm)" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-center font-bold">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Foto Komponen (Opsional)
                        </label>
                        <input type="file" @change="handleFileUpload($event)" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                        <button type="button" @click="showQuickAddModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition">
                            Batal
                        </button>
                        <button type="submit" :disabled="isSubmittingNewComp" class="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-white font-extrabold rounded-xl shadow-xs transition flex items-center space-x-1.5">
                            <i class="fa-solid fa-spinner fa-spin" x-show="isSubmittingNewComp"></i>
                            <span x-text="isSubmittingNewComp ? 'Mendaftarkan...' : 'Simpan & Pilih Part'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function membuatWrForm(initialComponents = []) {
            return {
                componentsList: initialComponents || [],
                searchQuery: '',
                isSearchOpen: false,

                selectedComponentId: '',
                selectedPartNumber: '',
                selectedName: '',
                selectedUom: 'PCS',
                selectedSpecs: '',
                selectedImage: '',
                selectedDimensions: '',
                lengthMm: '',
                widthMm: '',
                thicknessMm: '',
                totalStock: 0,
                minimumStock: 0,
                rackCode: '',

                // Multi-Departemen Tujuan
                targetDepartments: [
                    { 
                        department_name: '{{ $fabricationDepartments->firstWhere("name", "like", "%Laser%")?->name ?: $fabricationDepartments->first()?->name ?: "Dept Laser Cutting" }}', 
                        notes: '' 
                    }
                ],

                // Modal Registrasi Komponen Baru
                showQuickAddModal: false,
                isSubmittingNewComp: false,
                quickAddSuccessToast: false,
                newComp: {
                    name: '',
                    part_number: '',
                    category: '',
                    uom: 'PCS',
                    length_mm: '',
                    width_or_diameter_mm: '',
                    thickness_or_height_mm: '',
                    specification: '',
                    file: null
                },

                filteredComponents() {
                    if (!this.searchQuery || !this.searchQuery.trim()) {
                        return this.componentsList.slice(0, 25);
                    }
                    const q = this.searchQuery.toLowerCase().trim();
                    return this.componentsList.filter(c => {
                        return (c.name && c.name.toLowerCase().includes(q)) || 
                               (c.part_number && c.part_number.toLowerCase().includes(q));
                    }).slice(0, 35);
                },

                selectComponent(comp) {
                    if (!comp) return;
                    this.selectedComponentId = comp.id;
                    this.searchQuery = comp.part_number + ' • ' + comp.name;
                    this.selectedPartNumber = comp.part_number || '';
                    this.selectedName = comp.name || '';
                    this.selectedUom = comp.uom || 'PCS';
                    this.selectedSpecs = comp.specs || '';
                    this.selectedImage = comp.image_url || '{{ asset('images/placeholder_part.svg') }}';
                    this.selectedDimensions = comp.dimensions || '-';
                    this.lengthMm = comp.length_mm || '';
                    this.widthMm = comp.width_mm || '';
                    this.thicknessMm = comp.thickness_mm || '';
                    this.totalStock = comp.stock || 0;
                    this.minimumStock = comp.min_stock || 0;
                    this.rackCode = comp.rack || 'Belum di Rak';
                    this.isSearchOpen = false;

                    const select = document.getElementById('componentSelect');
                    if (select) {
                        select.value = comp.id;
                    }

                    // Smart Suggest Routing: Sesuaikan stasiun pertama dengan nama part
                    this.smartSuggestRouting(this.selectedName);
                },

                clearSelection() {
                    this.selectedComponentId = '';
                    this.searchQuery = '';
                    this.selectedPartNumber = '';
                    this.selectedName = '';
                    this.selectedImage = '';
                    this.selectedDimensions = '';
                    this.lengthMm = '';
                    this.widthMm = '';
                    this.thicknessMm = '';
                    this.totalStock = 0;
                    this.minimumStock = 0;
                    this.rackCode = '';
                    const select = document.getElementById('componentSelect');
                    if (select) {
                        select.value = '';
                    }
                    this.isSearchOpen = true;
                },

                updateComponentDetails() {
                    const select = document.getElementById('componentSelect');
                    const opt = select?.options[select.selectedIndex];
                    if (opt && opt.value) {
                        const matched = this.componentsList.find(c => c.id == opt.value);
                        if (matched) {
                            this.selectComponent(matched);
                        }
                    } else {
                        this.clearSelection();
                    }
                },

                smartSuggestRouting(name) {
                    const upper = (name || '').toUpperCase();
                    if (upper.includes('UNP')) {
                        this.targetDepartments = [{ department_name: 'Dept Laser UNP', notes: '' }];
                    } else if (upper.includes('BENDING') || upper.includes('SIKU') || upper.includes('COVER') || upper.includes('BUMPER')) {
                        this.targetDepartments = [
                            { department_name: 'Dept Laser Cutting', notes: 'Potong pelat profil' },
                            { department_name: 'Dept Bending', notes: 'Tekuk profil karoseri' }
                        ];
                    } else if (upper.includes('HANDLE') || upper.includes('PIN') || upper.includes('AS ') || upper.includes('BOLT') || upper.includes('PIPA')) {
                        this.targetDepartments = [
                            { department_name: 'Dept Band Saw', notes: 'Potong bahan pejal/pipa' },
                            { department_name: 'Dept M1', notes: 'Machining bubut/finishing' }
                        ];
                    }
                },

                addDepartment() {
                    this.targetDepartments.push({
                        department_name: '',
                        notes: ''
                    });
                },

                removeDepartment(index) {
                    if (this.targetDepartments.length > 1) {
                        this.targetDepartments.splice(index, 1);
                    }
                },

                openQuickAddModal(prefillName = '') {
                    const queryText = (typeof prefillName === 'string' && prefillName.trim()) 
                        ? prefillName.trim() 
                        : (this.searchQuery && !this.selectedComponentId ? this.searchQuery.trim() : '');

                    this.newComp = {
                        name: queryText,
                        part_number: '',
                        category: '',
                        uom: 'PCS',
                        length_mm: '',
                        width_or_diameter_mm: '',
                        thickness_or_height_mm: '',
                        specification: '',
                        file: null
                    };
                    this.isSearchOpen = false;
                    this.showQuickAddModal = true;
                },

                handleFileUpload(event) {
                    this.newComp.file = event.target.files[0] || null;
                },

                async submitQuickAdd() {
                    if (!this.newComp.name || !this.newComp.name.trim()) {
                        alert('Nama komponen wajib diisi!');
                        return;
                    }
                    this.isSubmittingNewComp = true;

                    try {
                        const formData = new FormData();
                        formData.append('name', this.newComp.name.trim());
                        if (this.newComp.part_number) formData.append('part_number', this.newComp.part_number.trim());
                        if (this.newComp.category) formData.append('category', this.newComp.category.trim());
                        formData.append('uom', this.newComp.uom || 'PCS');
                        if (this.newComp.length_mm) formData.append('length_mm', this.newComp.length_mm);
                        if (this.newComp.width_or_diameter_mm) formData.append('width_or_diameter_mm', this.newComp.width_or_diameter_mm);
                        if (this.newComp.thickness_or_height_mm) formData.append('thickness_or_height_mm', this.newComp.thickness_or_height_mm);
                        if (this.newComp.specification) formData.append('specification', this.newComp.specification);
                        if (this.newComp.file) formData.append('image', this.newComp.file);

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
                            const formattedComp = {
                                id: comp.id,
                                part_number: comp.part_number || '-',
                                name: comp.name,
                                category: comp.category || 'subframe',
                                uom: comp.uom || 'PCS',
                                specs: comp.specification || '',
                                image_url: comp.image_url || '{{ asset('images/placeholder_part.svg') }}',
                                length_mm: comp.length_mm || '',
                                width_mm: comp.width_or_diameter_mm || '',
                                thickness_mm: comp.thickness_or_height_mm || '',
                                dimensions: comp.dimension_formatted || '-',
                                stock: comp.total_stock || 0,
                                min_stock: comp.minimum_stock || 0,
                                rack: comp.rack_code || 'Belum di Rak',
                            };

                            // Masukkan ke daftar komponen Alpine di urutan paling awal
                            this.componentsList.unshift(formattedComp);

                            // Tambahkan ke dropdown select asli secara dinamis
                            const select = document.getElementById('componentSelect');
                            if (select) {
                                const newOpt = document.createElement('option');
                                newOpt.value = comp.id;
                                newOpt.text = `${comp.part_number} • ${comp.name} (${comp.uom})`;
                                select.appendChild(newOpt);
                            }

                            // Otomatis pilih komponen baru ini
                            this.selectComponent(formattedComp);
                            this.showQuickAddModal = false;

                            // Tampilkan feedback visual
                            this.quickAddSuccessToast = true;
                            setTimeout(() => {
                                this.quickAddSuccessToast = false;
                            }, 5000);
                        } else {
                            alert(res.message || 'Gagal mendaftarkan komponen baru');
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan jaringan: ' + e.message);
                    } finally {
                        this.isSubmittingNewComp = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>

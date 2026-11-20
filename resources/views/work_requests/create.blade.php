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
            'image_url' => route('components.photo-stream', ['component' => $c->id]),
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="createWrCartApp(@js($componentsJsonData), @js($deptList), '{{ $userDept }}')">

        <!-- CARD 1: FORMULIR INPUT WORK REQUEST (SESUAI GAMBAR REFERENSI PENGGUNA) -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-5">
            
            <!-- BARIS 1: PEMOHON & TANGGAL ORDER -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Pemohon -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pemohon
                    </label>
                    @if($canAccessAll)
                        <select x-model="selectedDeptPengaju" 
                                class="w-full px-3.5 py-2.5 text-xs font-semibold bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
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
                                   class="w-full px-3.5 py-2.5 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl text-slate-700 cursor-not-allowed select-none">
                            <i class="fa-solid fa-lock text-[10px] text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2"></i>
                        </div>
                    @endif
                </div>

                <!-- Tanggal Order -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Tanggal Order
                    </label>
                    <input type="text" 
                           value="{{ now()->format('d/m/Y') }}" 
                           readonly 
                           class="w-full px-3.5 py-2.5 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl text-slate-700 cursor-not-allowed select-none">
                </div>
            </div>

            <!-- BARIS 2: CARI KOMPONEN & TOMBOL SCAN -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700">
                    Cari Komponen
                </label>

                <!-- Input Pencarian + Tombol Scan Suffix Navy -->
                <div class="relative" @click.outside="isSearchOpen = false">
                    <div class="flex items-stretch rounded-xl border border-slate-300 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 overflow-hidden bg-white shadow-2xs transition">
                        <input type="text" 
                               x-model="searchQuery" 
                               @focus="if(!showNewCompForm) isSearchOpen = true" 
                               @input="if(!showNewCompForm) isSearchOpen = true; if(!searchQuery) clearSelectedComponent()" 
                               placeholder="Ketik Kode atau Nama..." 
                               class="w-full px-4 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 border-0 focus:ring-0 focus:outline-none bg-transparent">

                        <!-- Tombol Clear jika ada teks atau part terpilih -->
                        <button type="button" 
                                x-show="searchQuery || selectedComponent" 
                                @click="clearSelectedComponent()" 
                                class="px-2.5 text-slate-400 hover:text-slate-600 transition" 
                                title="Bersihkan pilihan">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>

                        <!-- Tombol Scan Suffix (Navy Sesuai Gambar Referensi) -->
                        <button type="button" 
                                @click="openQrScannerModal()" 
                                style="background-color: #0a2342 !important; color: #ffffff !important;"
                                class="px-5 py-2.5 bg-[#0a2342] hover:bg-slate-800 active:scale-98 text-white font-bold text-xs flex items-center space-x-2 transition select-none cursor-pointer shrink-0" 
                                title="Buka Kamera Scan QR / Barcode Part">
                            <i class="fa-solid fa-qrcode text-sm"></i>
                            <span class="tracking-wide">Scan</span>
                        </button>
                    </div>

                    <!-- Dropdown Hasil Pencarian Interaktif (Sesuai Gambar 2) -->
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

                        <!-- Jika Tidak Ditemukan -->
                        <div x-show="filteredComponents().length === 0" class="p-4 text-center space-y-2">
                            <div class="text-xs font-bold text-slate-700">Komponen tidak ditemukan di katalog.</div>
                            <p class="text-[11px] text-slate-500">Anda dapat langsung mendaftarkannya sebagai komponen baru.</p>
                            <button type="button" 
                                    @click="openNewComponentForm(searchQuery)" 
                                    class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 rounded-xl text-xs font-bold transition">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                <span>Input Komponen Baru</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tombol Tambah Komponen Baru (Sesuai Gambar 1) -->
                <div x-show="!showNewCompForm && !selectedComponent" class="pt-0.5">
                    <button type="button" 
                            @click="openNewComponentForm()" 
                            class="w-full py-2.5 px-4 bg-white hover:bg-blue-50/50 active:scale-[0.99] border border-blue-300 text-blue-600 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2 cursor-pointer shadow-2xs">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Tambah Komponen Baru</span>
                    </button>
                </div>
            </div>

            <!-- INLINE FORM: INPUT KOMPONEN BARU (SESUAI GAMBAR 6, 7 & 8) -->
            <div x-show="showNewCompForm" 
                 x-cloak 
                 x-transition 
                 style="display: none;" 
                 class="border-2 border-blue-300 rounded-2xl p-4 sm:p-5 bg-white space-y-4 shadow-sm">
                
                <div class="flex items-center justify-between border-b border-blue-100 pb-2.5">
                    <div class="flex items-center space-x-2 text-blue-600 font-extrabold text-xs sm:text-sm tracking-wide">
                        <i class="fa-solid fa-circle-plus"></i>
                        <span>INPUT KOMPONEN BARU</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400">Non-Master On-The-Fly</span>
                </div>

                <!-- Field 1: Nama Komponen -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center space-x-1.5">
                        <i class="fa-solid fa-tag text-blue-500 text-[11px]"></i>
                        <span>Nama Komponen <span class="text-rose-500">*</span></span>
                    </label>
                    <input type="text" 
                           x-model="newComp.name" 
                           placeholder="Masukkan Nama Komponen..." 
                           class="w-full px-3.5 py-2 text-xs font-medium border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                </div>

                <!-- Field 2: Tipe Material (Combobox Sesuai Gambar 7 & 8) -->
                <div class="relative" @click.outside="isMaterialDropdownOpen = false">
                    <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center space-x-1.5">
                        <i class="fa-solid fa-flask text-blue-500 text-[11px]"></i>
                        <span>Tipe Material</span>
                    </label>
                    <div class="relative">
                        <input type="text" 
                               x-model="newComp.material" 
                               @focus="isMaterialDropdownOpen = true" 
                               placeholder="Pilih atau Ketik Material..." 
                               class="w-full px-3.5 py-2 pr-8 text-xs font-medium border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                        <button type="button" 
                                @click="isMaterialDropdownOpen = !isMaterialDropdownOpen" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                    </div>

                    <!-- Dropdown List Material (Gambar 7 & 8) -->
                    <div x-show="isMaterialDropdownOpen" 
                         x-cloak 
                         style="display: none;" 
                         class="absolute left-0 right-0 z-30 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100">
                        <template x-for="mat in materialPresets" :key="mat">
                            <div @click="newComp.material = mat; isMaterialDropdownOpen = false" 
                                 class="px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700 cursor-pointer transition" 
                                 x-text="mat"></div>
                        </template>
                    </div>
                </div>

                <!-- Field 3: Dimensi P, L, T (Sesuai Gambar 6) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center space-x-1.5">
                        <i class="fa-solid fa-ruler-combined text-blue-500 text-[11px]"></i>
                        <span>Dimensi</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2.5">
                        <input type="text" 
                               x-model="newComp.length_cm" 
                               placeholder="P (cm)" 
                               class="w-full px-3 py-2 text-xs text-center font-medium border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                        <input type="text" 
                               x-model="newComp.width_cm" 
                               placeholder="L (cm)" 
                               class="w-full px-3 py-2 text-xs text-center font-medium border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                        <input type="text" 
                               x-model="newComp.thickness_mm" 
                               placeholder="T (mm)" 
                               class="w-full px-3 py-2 text-xs text-center font-medium border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                </div>

                <!-- Field 4: Foto Komponen (Sesuai Gambar 6) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center space-x-1.5">
                        <i class="fa-solid fa-camera text-blue-500 text-[11px]"></i>
                        <span>Foto Komponen</span>
                    </label>
                    <div class="flex items-center space-x-3 p-2 bg-slate-50 border border-slate-200 rounded-xl">
                        <input type="file" 
                               @change="handleFileUpload($event)" 
                               accept="image/*" 
                               class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>
                </div>

                <!-- Tombol Aksi Form Komponen Baru (Sesuai Gambar 6) -->
                <div class="space-y-2 pt-2">
                    <!-- Tombol Tampilkan Preview (Cyan Cerah) -->
                    <button type="button" 
                            @click="saveAndPreviewNewComponent()" 
                            :disabled="isSubmittingNewComp" 
                            style="background-color: #00b4d8 !important; color: #ffffff !important;"
                            class="w-full py-2.5 bg-[#00b4d8] hover:bg-[#0096c7] active:scale-[0.99] text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50">
                        <i class="fa-solid fa-spinner fa-spin" x-show="isSubmittingNewComp"></i>
                        <i class="fa-solid fa-eye" x-show="!isSubmittingNewComp"></i>
                        <span x-text="isSubmittingNewComp ? 'Menyimpan & Memproses...' : 'Tampilkan Preview'"></span>
                    </button>

                    <!-- Tombol Batal / Kembali Cari (Border Merah) -->
                    <button type="button" 
                            @click="cancelNewComponentForm()" 
                            class="w-full py-2.5 bg-white hover:bg-rose-50 active:scale-[0.99] border border-rose-300 text-rose-600 font-bold text-xs rounded-xl transition flex items-center justify-center space-x-2 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Batal / Kembali Cari</span>
                    </button>
                </div>
            </div>

            <!-- KARTU PREVIEW KOMPONEN TERPILIH (SESUAI GAMBAR 3 & 4) -->
            <div x-show="selectedComponent && !showNewCompForm" 
                 x-cloak 
                 x-transition 
                 style="display: none;" 
                 class="border border-cyan-400/80 rounded-2xl p-4 bg-cyan-50/20 shadow-2xs space-y-3">
                
                <!-- Foto Komponen -->
                <div class="w-full max-w-xs mx-auto h-36 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center relative shadow-2xs">
                    <img :src="selectedComponent?.image_url" 
                         :alt="selectedComponent?.name" 
                         class="w-full h-full object-cover" 
                         x-on:error="$el.src = '{{ asset('images/placeholder_part.svg') }}'">
                </div>

                <!-- Nama Komponen Terpilih -->
                <div class="text-center">
                    <h3 class="font-extrabold text-sm sm:text-base text-slate-900 uppercase tracking-wide" x-text="selectedComponent?.name"></h3>
                </div>

                <!-- Baris Informasi Spesifikasi (Sesuai Gambar 3) -->
                <div class="space-y-1.5 text-xs pt-1 border-t border-cyan-200/50">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Kode Komponen</span>
                        <span class="font-bold text-blue-600 font-mono text-xs" x-text="selectedComponent?.part_number"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Dimensi</span>
                        <span class="font-bold text-slate-800 text-xs" x-text="selectedComponent?.dimensions || '-'"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Routing</span>
                        <span class="font-bold text-slate-800 text-xs" x-text="selectedComponent?.routing || '-'"></span>
                    </div>
                </div>
            </div>

            <!-- BARIS 3: QTY & PENERIMA (SESUAI GAMBAR 0, 1, 3 & 4) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Qty -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Qty <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           x-model.number="itemQty" 
                           min="0.1" 
                           step="any" 
                           placeholder="0" 
                           class="w-full px-3.5 py-2.5 text-xs font-bold text-slate-900 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                </div>

                <!-- Penerima (Dropdown Pilih Dept Urut Sesuai Gambar 4) -->
                <div class="relative" @click.outside="isDeptDropdownOpen = false">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Penerima <span class="text-rose-500">*</span>
                    </label>

                    <div class="flex items-stretch rounded-xl border border-slate-300 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 bg-white shadow-2xs overflow-hidden transition">
                        <input type="text" 
                               x-model="itemDepartment" 
                               @focus="isDeptDropdownOpen = true" 
                               placeholder="Pilih Dept (Urut)" 
                               class="w-full px-3.5 py-2.5 text-xs font-semibold text-slate-800 placeholder:text-slate-400 border-0 focus:ring-0 focus:outline-none bg-transparent">
                        
                        <!-- Tombol Silang Merah Reset (Sesuai Gambar 0, 1, 3 & 4) -->
                        <button type="button" 
                                @click="itemDepartment = ''; selectedDeptsList = []" 
                                class="px-3 text-rose-500 hover:text-rose-700 font-bold text-sm transition" 
                                title="Reset Penerima">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Dropdown List Departemen Penerima (Sesuai Gambar 4) -->
                    <div x-show="isDeptDropdownOpen" 
                         x-cloak 
                         style="display: none;" 
                         class="absolute left-0 right-0 z-30 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-slate-100">
                        <template x-for="dept in availableDepartments" :key="dept">
                            <div @click="selectDepartment(dept)" 
                                 class="px-3.5 py-2.5 text-xs font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700 cursor-pointer transition flex items-center justify-between">
                                <span x-text="dept"></span>
                                <i class="fa-solid fa-plus text-[10px] text-slate-400"></i>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- BARIS 4: DESKRIPSI -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Deskripsi
                </label>
                <input type="text" 
                       x-model="itemNotes" 
                       placeholder="Keterangan tambahan..." 
                       class="w-full px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
            </div>

            <!-- BARIS 5: TOMBOL TAMBAH KE LIST (SESUAI GAMBAR 0, 1, 3 & 4) -->
            <div class="pt-2">
                <button type="button" 
                        @click="addItemToCart()" 
                        style="background-color: #10707F !important; color: #ffffff !important;"
                        class="w-full py-3 bg-[#10707F] hover:bg-[#0c5965] active:scale-[0.99] text-white font-extrabold text-xs sm:text-sm rounded-xl shadow-xs transition flex items-center justify-center space-x-2 cursor-pointer select-none">
                    <i class="fa-solid fa-circle-plus"></i>
                    <span>Tambah ke List</span>
                </button>
            </div>
        </div>

        <!-- CARD 2: DAFTAR ITEM SIAP KIRIM (KERANJANG / CART) - SESUAI GAMBAR 5 -->
        <div x-show="cartItems.length > 0" 
             x-cloak 
             x-transition 
             style="display: none;" 
             class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-5">
            
            <!-- Judul Keranjang (Sesuai Gambar 5) -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-sm sm:text-base text-slate-900 leading-tight">
                    Daftar Item Siap Kirim (<span x-text="cartItems.length"></span>)
                </h3>
                <span class="text-xs text-slate-500 font-medium">Periksa kembali item sebelum diterbitkan</span>
            </div>

            <!-- Tabel Daftar Item (Sesuai Gambar 5) -->
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 font-extrabold text-slate-700">
                        <tr>
                            <th class="py-3 px-3.5">Komponen</th>
                            <th class="py-3 px-3.5 text-center">Dept Penerima</th>
                            <th class="py-3 px-3.5 text-center">Qty</th>
                            <th class="py-3 px-3.5 text-center w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="(item, index) in cartItems" :key="index">
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-3.5 align-middle">
                                    <div class="font-extrabold text-slate-900" x-text="item.name"></div>
                                    <div class="text-[11px] font-mono font-medium text-slate-500 mt-0.5 flex items-center space-x-1.5">
                                        <span x-text="item.part_number"></span>
                                        <template x-if="item.is_new">
                                            <span class="text-[9px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.5 rounded">Part Baru</span>
                                        </template>
                                    </div>
                                    <template x-if="item.notes">
                                        <div class="text-[10px] text-slate-400 italic mt-0.5" x-text="'Ket: ' + item.notes"></div>
                                    </template>
                                </td>
                                <td class="py-3 px-3.5 align-middle text-center font-medium text-slate-700" x-text="item.department_name"></td>
                                <td class="py-3 px-3.5 align-middle text-center font-extrabold text-slate-900" x-text="item.quantity + ' ' + (item.uom || 'PCS')"></td>
                                <td class="py-3 px-3.5 align-middle text-center">
                                    <button type="button" 
                                            @click="removeCartItem(index)" 
                                            class="w-7 h-7 rounded-lg bg-rose-500 hover:bg-rose-600 text-white flex items-center justify-center transition shadow-2xs mx-auto cursor-pointer" 
                                            title="Hapus dari daftar">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Tombol Kirim Work Request (Hijau Sesuai Gambar 5) -->
            <div class="pt-2">
                <button type="button" 
                        @click="submitBulkWorkRequests()" 
                        :disabled="isSubmittingBulk" 
                        style="background-color: #16a34a !important; color: #ffffff !important;"
                        class="w-full py-3.5 bg-[#16a34a] hover:bg-[#15803d] active:scale-[0.99] text-white font-extrabold text-sm sm:text-base rounded-xl shadow-md transition flex items-center justify-center space-x-2.5 cursor-pointer disabled:opacity-50">
                    <i class="fa-solid fa-spinner fa-spin text-sm" x-show="isSubmittingBulk"></i>
                    <i class="fa-solid fa-paper-plane text-sm" x-show="!isSubmittingBulk"></i>
                    <span x-text="isSubmittingBulk ? 'Menerbitkan Tiket Work Request...' : 'Kirim Work Request'"></span>
                </button>
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
                selectedDeptsList: [],
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
                    material: '',
                    length_cm: '',
                    width_cm: '',
                    thickness_mm: '',
                    file: null
                },

                // Scanner Camera
                isScannerModalOpen: false,
                html5QrScanner: null,

                init() {
                    // Cek jika ada request query component_id
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

                    // Berikan saran routing jika departemen belum dipilih
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
                        
                        // Konversi cm ke mm untuk length & width jika diisi
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
                            
                            // Format dimensi tampilan
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

                            // Masukkan ke komponen lokal
                            this.componentsList.unshift(formattedComp);
                            this.selectedComponent = formattedComp;
                            this.searchQuery = formattedComp.name;
                            this.showNewCompForm = false;

                            // Beri saran routing jika belum ada
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

                    // Tambahkan ke keranjang
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

                    // Reset form input untuk item berikutnya
                    this.selectedComponent = null;
                    this.searchQuery = '';
                    this.itemQty = '';
                    this.itemNotes = '';
                    // Biarkan itemDepartment tetap ada sebagai default atau reset jika diinginkan
                },

                removeCartItem(index) {
                    const item = this.cartItems[index];
                    const itemName = item ? item.name : 'item ini';
                    this.showConfirm({
                        title: 'Hapus Item dari Daftar?',
                        message: `Apakah Anda yakin ingin menghapus "${itemName}" dari daftar item siap kirim?`,
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
                        this.showAlert('Daftar Item Kosong', 'Daftar item siap kirim masih kosong. Silakan tambahkan minimal 1 komponen terlebih dahulu sebelum mengirim.', 'warning');
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

                    // Cari di komponen lokal
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

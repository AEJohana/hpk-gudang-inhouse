<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 uppercase tracking-wider">
                        Interactive Lego 2D Floor Plan
                    </span>
                    <span class="text-xs text-slate-500 font-medium">Multi-Gedung &bull; Sistem Rak & Pallet Pabrik Karoseri HPK</span>
                </div>
                <h2 class="font-extrabold text-2xl text-slate-900 leading-tight mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-cubes text-amber-500"></i>
                    Peta Lokasi & Tata Letak Rak & Pallet Gudang
                </h2>
                <p class="text-xs text-slate-600 mt-0.5">
                    Sesuaikan luas area gedung terlebih dahulu, susun letak balok rak bertingkat (1x1) dan area penyimpanan lantai non-rak (Pallet 1x1) secara fleksibel.
                </p>
            </div>

            <!-- KPI Pill Bar (Tema Terang) -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl shadow-xs text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Rak Bertingkat</div>
                    <div class="text-base font-extrabold text-slate-800">{{ $stats['total_racks'] }} <span class="text-xs font-normal text-slate-500">Unit</span></div>
                </div>
                <div class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl shadow-xs text-center">
                    <div class="text-[10px] uppercase font-bold text-amber-600">Area Pallet (Non-Rak)</div>
                    <div class="text-base font-extrabold text-amber-600">{{ $stats['total_pallets'] }} <span class="text-xs font-normal text-slate-500">Spot</span></div>
                </div>
                <div class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl shadow-xs text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Komponen</div>
                    <div class="text-base font-extrabold text-blue-600">{{ number_format($stats['total_stored'], 0) }} <span class="text-xs font-normal text-slate-500">Item</span></div>
                </div>
                <div class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl shadow-xs text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Rata-rata Utilitas</div>
                    <div class="text-base font-extrabold {{ $stats['occupancy_avg'] >= 80 ? 'text-amber-600' : 'text-emerald-600' }}">{{ $stats['occupancy_avg'] }}%</div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
         x-data="warehouseMap({
             initialRacks: {{ Js::from($locationsJson) }},
             warehouses: {{ Js::from($warehouses) }},
             activeWarehouse: {{ Js::from($activeWarehouseJson) }},
             saveUrl: '{{ route('warehouse-map.save-layout') }}',
             resetUrl: '{{ route('warehouse-map.reset-layout') }}',
             updateAreaUrl: '{{ $activeWarehouse ? route('warehouse-map.update-area', ['warehouse' => $activeWarehouse->id]) : '#' }}',
             createLocationUrl: '{{ route('warehouse-map.create-location') }}',
             csrfToken: '{{ csrf_token() }}'
         })" 
         x-init="initMap()">

        <!-- Floating Alert / Toast Notification -->
        <div x-show="toast.show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             style="display: none;"
             class="fixed bottom-6 right-6 z-50 max-w-md p-4 rounded-2xl shadow-2xl border flex items-center space-x-3 bg-white"
             :class="{
                 'border-emerald-500 text-slate-800 shadow-emerald-500/20': toast.type === 'success',
                 'border-rose-500 text-slate-800 shadow-rose-500/20': toast.type === 'error',
                 'border-amber-500 text-slate-800 shadow-amber-500/20': toast.type === 'info'
             }">
            <i class="text-xl" :class="{
                'fa-solid fa-circle-check text-emerald-500': toast.type === 'success',
                'fa-solid fa-triangle-exclamation text-rose-500': toast.type === 'error',
                'fa-solid fa-circle-info text-amber-500': toast.type === 'info'
            }"></i>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-slate-900" x-text="toast.title"></div>
                <div class="text-xs text-slate-600 mt-0.5" x-text="toast.message"></div>
            </div>
            <button @click="toast.show = false" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- 1. BUILDING / WAREHOUSE SELECTOR & AREA DIMENSION BAR (LIGHT THEME) -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center space-x-2 text-slate-800 font-extrabold text-xs">
                    <i class="fa-solid fa-warehouse text-blue-600 text-sm"></i>
                    <span>PILIH GEDUNG:</span>
                </div>
                <select @change="switchWarehouse($event.target.value)"
                        class="text-xs font-bold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-900 focus:ring-2 focus:ring-blue-500 shadow-xs cursor-pointer">
                    <template x-for="w in warehouses" :key="w.id">
                        <option :value="w.id" :selected="w.id === activeWarehouse.id" x-text="w.name"></option>
                    </template>
                </select>
                <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-blue-50 text-blue-800 border border-blue-200 flex items-center gap-1.5"
                      title="Ukuran Luas Area Fisik Gedung">
                    <i class="fa-solid fa-ruler-combined text-blue-600"></i>
                    <span x-text="activeWarehouse.dimension_label || (activeWarehouse.length_meters + 'm × ' + activeWarehouse.width_meters + 'm (' + activeWarehouse.total_area_sqm + ' m²)')"></span>
                </span>
                <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200 flex items-center gap-1.5"
                      title="Resolusi Grid Denah Blueprint">
                    <i class="fa-solid fa-border-all text-slate-500"></i>
                    <span x-text="activeWarehouse.grid_columns + ' × ' + activeWarehouse.grid_rows + ' GRID'"></span>
                </span>
            </div>

            <!-- Action: Atur Luas Gedung & Area -->
            <div>
                <button type="button" 
                        @click="openAreaModal()"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-300 flex items-center space-x-1.5 transition shadow-xs">
                    <i class="fa-solid fa-sliders text-blue-600"></i>
                    <span>Atur Luas Gedung & Area</span>
                </button>
            </div>
        </div>

        <!-- 2. CONTROL TOOLBAR: MODE TOGGLE, SEARCH, FILTER, SAVE, TAMBAH RAK & PALLET (TEMA TERANG) -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
            
            <!-- Left: Search and Zone Filter -->
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <!-- Search Input -->
                <div class="relative min-w-[280px] flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" 
                           x-model="searchQuery" 
                           @input="filterRacks()"
                           placeholder="Cari Rak, Pallet 1, MOUNTING HINO 2, CSFP10300250081..." 
                           class="w-full pl-9 pr-8 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none bg-slate-50 focus:bg-white transition shadow-inner">
                    <button x-show="searchQuery" @click="searchQuery = ''; filterRacks()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Storage Type Filter Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto py-1 text-xs">
                    <button type="button" 
                            @click="selectedType = 'ALL'; filterRacks()"
                            :class="selectedType === 'ALL' ? 'bg-slate-900 text-white font-bold shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium'"
                            class="px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                        Semua Lokasi
                    </button>
                    <button type="button" 
                            @click="selectedType = 'rack'; filterRacks()"
                            :class="selectedType === 'rack' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-blue-50 text-blue-800 hover:bg-blue-100 font-medium border border-blue-200'"
                            class="px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                        <i class="fa-solid fa-cubes text-[10px] mr-1"></i> Rak Bertingkat
                    </button>
                    <button type="button" 
                            @click="selectedType = 'pallet'; filterRacks()"
                            :class="selectedType === 'pallet' ? 'bg-amber-600 text-white font-bold shadow-xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 font-medium border border-amber-200'"
                            class="px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                        <i class="fa-solid fa-pallet text-[10px] mr-1"></i> Pallet (Non-Rak)
                    </button>
                </div>
            </div>

            <!-- Right: Interactive Mode & Action Buttons -->
            <div class="flex flex-wrap items-center gap-2 border-t lg:border-t-0 pt-3 lg:pt-0">
                
                <!-- Design Mode Creation Buttons (+ Rak & + Pallet) -->
                <template x-if="editMode">
                    <div class="flex items-center space-x-1.5 mr-1">
                        <button type="button" 
                                @click="addNewLocation('rack')"
                                :disabled="isCreatingLocation"
                                class="px-3 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white flex items-center space-x-1.5 transition shadow-xs">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>+ Rak (1x1)</span>
                        </button>
                        <button type="button" 
                                @click="addNewLocation('pallet')"
                                :disabled="isCreatingLocation"
                                class="px-3 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 flex items-center space-x-1.5 transition shadow-xs">
                            <i class="fa-solid fa-pallet text-[10px]"></i>
                            <span>+ Pallet (1x1)</span>
                        </button>
                    </div>
                </template>

                <!-- Mode Switcher -->
                <button type="button" 
                        @click="toggleEditMode()"
                        :class="editMode ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold ring-2 ring-amber-300' : 'bg-white hover:bg-slate-50 text-slate-800 font-semibold border border-slate-300'"
                        class="px-3.5 py-2 rounded-xl text-xs flex items-center space-x-2 transition shadow-xs">
                    <i :class="editMode ? 'fa-solid fa-arrows-up-down-left-right' : 'fa-solid fa-pen-ruler'"></i>
                    <span x-text="editMode ? 'Mode Desain Aktif' : 'Atur Tata Letak Denah'"></span>
                </button>

                <!-- Show Grid Guides Toggle -->
                <button type="button" 
                        @click="showGridGuides = !showGridGuides"
                        :class="showGridGuides ? 'text-amber-800 bg-amber-50 border-amber-300 font-bold' : 'text-slate-600 bg-white border-slate-200'"
                        class="px-3 py-2 rounded-xl border text-xs flex items-center space-x-1.5 hover:bg-slate-50 transition shadow-xs"
                        title="Tampilkan Panduan Kotak Grid">
                    <i class="fa-solid fa-border-all"></i>
                    <span class="hidden sm:inline">Grid</span>
                </button>

                <!-- Save Layout Button -->
                <button type="button" 
                        x-show="editMode || hasUnsavedChanges" 
                        @click="saveLayout()"
                        :disabled="isSaving"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white flex items-center space-x-2 transition shadow-xs disabled:opacity-50">
                    <i class="fa-solid" :class="isSaving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Posisi'"></span>
                </button>

                <!-- Reset Layout Button -->
                <button type="button" 
                        x-show="editMode"
                        @click="resetLayout()"
                        :disabled="isSaving"
                        class="px-3 py-2 rounded-xl text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 flex items-center space-x-1.5 transition">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span class="hidden sm:inline">Reset Default</span>
                </button>
            </div>
        </div>

        <!-- 3. 2D WAREHOUSE CANVAS (LIGHT MODE / TEMA TERANG INDUSTRIAL) -->
        <div class="bg-white text-slate-800 rounded-3xl p-6 border-2 border-slate-200 shadow-xl relative overflow-hidden">
            
            <!-- Canvas Header Bar with Warehouse Metadata (Light Theme) -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200 gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-3.5 h-3.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-extrabold text-base tracking-wide text-slate-900 uppercase" x-text="activeWarehouse.name"></h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-700 font-bold border border-slate-200" 
                                  x-text="activeWarehouse.grid_columns + ' x ' + activeWarehouse.grid_rows + ' GRID'"></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-blue-50 text-blue-700 font-bold border border-blue-200" 
                                  x-text="activeWarehouse.dimension_label"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="activeWarehouse.description || 'Sistem Penyimpanan Komponen Karoseri, Dump Truck & Trailer PT Hydraxle Perkasa'"></p>
                    </div>
                </div>

                <!-- Mode Indicator Banner (Light Theme) -->
                <div class="flex items-center space-x-3">
                    <template x-if="editMode">
                        <div class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs font-bold animate-pulse">
                            <i class="fa-solid fa-hand-pointer"></i>
                            <span>Mode Desain: Tarik balok Rak atau Pallet 1x1 untuk memindahkan posisi di denah</span>
                        </div>
                    </template>
                    <template x-if="!editMode">
                        <div class="flex items-center space-x-2 px-3 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600">
                            <i class="fa-solid fa-circle-info text-blue-500"></i>
                            <span>Klik salah satu balok <strong>Rak</strong> atau <strong>Pallet</strong> untuk melihat komponen & lokasi detail</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Canvas Landmarks & Boundary Notations (Light Theme) -->
            <div class="my-3 flex flex-wrap items-center justify-between text-xs text-slate-600 px-1 border-b border-slate-100 pb-2.5">
                <div class="flex items-center space-x-2 text-amber-800 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200 font-mono font-bold text-[11px]">
                    <i class="fa-solid fa-truck-moving"></i>
                    <span>GERBANG UTAMA & LOADING DOCK (CONTAINER TRUCK ACCESS)</span>
                </div>
                <div class="flex items-center space-x-4 text-[11px] text-slate-500 font-medium">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-boxes-stacked text-amber-600"></i> Area Non-Rak (Pallet Stacking)</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-cubes text-blue-600"></i> Rak Bertingkat</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-fire-extinguisher text-rose-500"></i> Jalur Evakuasi Aman</span>
                </div>
            </div>

            <!-- INTERACTIVE 2D LEGO GRID CONTAINER (DYNAMIC GRID SIZING PER BUILDING) -->
            <div class="relative w-full overflow-x-auto pb-4 pt-2">
                <div class="min-w-[980px] select-none">
                    
                    <!-- Dynamic Blueprint Grid Container -->
                    <div class="grid gap-2 relative p-4 rounded-2xl bg-slate-50 border border-slate-200 shadow-inner"
                         :style="getGridContainerStyle()">
                        
                        <!-- Background Grid Cells (Dynamic per Active Warehouse Rows & Columns) -->
                        <template x-for="r in (activeWarehouse?.grid_rows || 12)" :key="'row-'+r">
                            <template x-for="c in (activeWarehouse?.grid_columns || 16)" :key="'cell-'+c+'-'+r">
                                <div class="rounded-lg transition-colors border flex items-center justify-center pointer-events-auto aspect-square"
                                     :class="{
                                         'border-slate-200/60 bg-white/70 text-slate-400': !isCellHovered(c, r),
                                         'border-amber-400 bg-amber-100/60 text-amber-900 shadow-inner': isCellHovered(c, r) && editMode,
                                         'border-dashed border-slate-300': showGridGuides,
                                         'border-transparent': !showGridGuides && !isCellHovered(c, r)
                                     }"
                                     :style="'grid-column: ' + c + '; grid-row: ' + r + ';'"
                                     @dragover.prevent="onDragOver($event, c, r)"
                                     @drop.prevent="onDrop($event, c, r)">
                                    <span x-show="showGridGuides || editMode" class="text-[9px] font-mono text-slate-400">
                                        <span x-text="c + ',' + r"></span>
                                    </span>
                                </div>
                            </template>
                        </template>

                        <!-- Interactive Empty-State Blueprint Card (When Database Has 0 Racks/Pallets) -->
                        <template x-if="racks.length === 0">
                            <div class="col-span-full row-span-full z-20 flex flex-col items-center justify-center p-8 bg-white/95 rounded-2xl border-2 border-dashed border-blue-300 shadow-xl text-center my-6 mx-auto max-w-lg backdrop-blur-xs">
                                <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-3 shadow-inner border border-blue-200">
                                    <i class="fa-solid fa-compass-drafting"></i>
                                </div>
                                <h4 class="font-black text-base text-slate-900">Denah Gedung Baru Siap Didesain</h4>
                                <p class="text-xs text-slate-500 mt-1 max-w-sm leading-relaxed">
                                    Gedung ini baru saja disiapkan dan belum memiliki rak bertingkat atau area pallet. Silakan sesuaikan luas gedung terlebih dahulu atau langsung tambahkan rak pertama Anda.
                                </p>
                                <div class="mt-5 flex flex-wrap items-center justify-center gap-2.5">
                                    <button type="button" 
                                            @click="openAreaModal()" 
                                            class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5">
                                        <i class="fa-solid fa-sliders text-amber-400"></i>
                                        <span>Atur Luas Gedung</span>
                                    </button>
                                    <button type="button" 
                                            @click="editMode = true; addNewLocation('rack')" 
                                            class="px-3.5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                        <span>+ Tambah Rak (1x1)</span>
                                    </button>
                                    <button type="button" 
                                            @click="editMode = true; addNewLocation('pallet')" 
                                            class="px-3.5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5">
                                        <i class="fa-solid fa-pallet text-xs"></i>
                                        <span>+ Tambah Pallet (1x1)</span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- DYNAMIC RACK & PALLET LEGO BLOCKS (FLEXIBLE SHAPES: 1x1, 1x2, 2x1, 2x2, 3x1, ETC.) -->
                        <template x-for="rack in racks" :key="rack.id">
                            <div class="lego-rack-block relative rounded-xl border-2 transition-all duration-200 select-none flex flex-col items-center justify-center p-1 shadow-md cursor-pointer group text-center w-full h-full min-h-[54px]"
                                 :class="getRackClasses(rack)"
                                 :style="getRackStyle(rack)"
                                 :draggable="editMode"
                                 :title="editMode ? 'Klik untuk atur ukuran & tingkat, atau tarik untuk memindahkan posisi ' + rack.rack_number : 'Klik untuk melihat komponen & lokasi detail di ' + rack.rack_number"
                                 @dragstart="onDragStart($event, rack)"
                                 @dragend="onDragEnd($event)"
                                 @click="onRackClick(rack)">
                                
                                <!-- PALLET BLOCK VISUAL (NON-RAK FLOOR STORAGE) -->
                                <template x-if="rack.is_pallet || rack.storage_type === 'pallet'">
                                    <div class="flex flex-col items-center justify-center pointer-events-none w-full h-full p-1 relative">
                                        <div class="flex items-center gap-1 justify-center mb-0.5">
                                            <i class="fa-solid fa-pallet text-xs text-amber-950 transition-transform group-hover:scale-110"></i>
                                            <template x-if="(rack.grid_w > 1 || rack.grid_h > 1)">
                                                <span class="text-[8px] font-mono font-black px-1.5 py-0.2 bg-amber-950/20 text-amber-950 rounded leading-none" x-text="rack.grid_w + '×' + rack.grid_h"></span>
                                            </template>
                                        </div>
                                        <span class="font-black text-[11px] sm:text-xs tracking-wider uppercase leading-none text-slate-950 px-0.5 line-clamp-1"
                                              x-text="rack.rack_number.toUpperCase()"></span>
                                        <span class="text-[7px] font-mono font-extrabold uppercase tracking-tight text-amber-950/70 mt-0.5">PALLET</span>
                                    </div>
                                </template>

                                <!-- RACK BLOCK VISUAL (RAK BERTINGKAT) -->
                                <template x-if="!rack.is_pallet && rack.storage_type !== 'pallet'">
                                    <div class="flex flex-col items-center justify-center pointer-events-none w-full h-full p-1 relative">
                                        <!-- Tactile Lego Studs (Dynamically repeats horizontally if width >= 2) -->
                                        <div class="flex items-center justify-center gap-1.5 mb-1">
                                            <template x-for="s in (rack.grid_w || 1)" :key="'stud-'+rack.id+'-'+s">
                                                <div class="w-3 h-3 rounded-full border shadow-inner flex items-center justify-center shrink-0 transition-transform group-hover:scale-110"
                                                     :class="rack.color === 'amber' ? 'bg-amber-600/30 border-amber-700/50' : 'bg-white/40 border-white/60'">
                                                    <div class="w-1 h-1 rounded-full"
                                                         :class="rack.color === 'amber' ? 'bg-amber-900/60' : 'bg-white/90'"></div>
                                                </div>
                                            </template>
                                        </div>
                                        <span class="font-black text-[11px] sm:text-xs tracking-wider uppercase leading-none select-none px-0.5 line-clamp-1"
                                              :class="rack.color === 'amber' ? 'text-slate-950 font-black' : 'text-white drop-shadow-xs'"
                                              x-text="rack.rack_number.toUpperCase()"></span>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span class="text-[7px] font-mono font-extrabold uppercase tracking-tight opacity-75"
                                                  :class="rack.color === 'amber' ? 'text-slate-900' : 'text-white'">RAK</span>
                                            <template x-if="rack.total_levels && rack.total_levels !== 4">
                                                <span class="text-[7px] font-mono font-extrabold px-1 rounded bg-black/25 text-white" x-text="rack.total_levels + ' TK'"></span>
                                            </template>
                                            <template x-if="(rack.grid_w > 1 || rack.grid_h > 1)">
                                                <span class="text-[7px] font-mono font-extrabold px-1 rounded bg-black/20 text-white" x-text="rack.grid_w + '×' + rack.grid_h"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Edit Mode Action Indicator -->
                                <template x-if="editMode">
                                    <span class="absolute top-1 right-1 text-[8px] opacity-80 pointer-events-none bg-black/20 px-1 py-0.5 rounded text-white" title="Klik untuk ubah ukuran/tingkat">
                                        <i class="fa-solid fa-gear"></i>
                                    </span>
                                </template>
                            </div>
                        </template>

                    </div>
                </div>
            </div>

            <!-- Canvas Bottom Legend (Light Theme) -->
            <div class="mt-4 pt-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-600">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="font-bold text-slate-800 uppercase text-[10px] tracking-wider">Kategori Lokasi:</span>
                    <span class="flex items-center"><span class="w-3.5 h-3.5 bg-blue-600 rounded-sm me-1.5 shadow-xs"></span> Rak Bertingkat (Lantai 1-4 & Slot)</span>
                    <span class="flex items-center"><span class="w-3.5 h-3.5 bg-amber-500 rounded-sm me-1.5 shadow-xs"></span> Area Pallet Lantai (Non-Rak)</span>
                    <span class="flex items-center"><span class="w-3.5 h-3.5 bg-emerald-600 rounded-sm me-1.5 shadow-xs"></span> Rak Komponen Hidrolik</span>
                    <span class="flex items-center"><span class="w-3.5 h-3.5 bg-indigo-600 rounded-sm me-1.5 shadow-xs"></span> Rak Raw Material Baja</span>
                    <span class="flex items-center"><span class="w-3.5 h-3.5 bg-purple-600 rounded-sm me-1.5 shadow-xs"></span> Ruang B3 Cat PU</span>
                </div>
                <div class="flex items-center space-x-2 text-[11px] text-slate-500">
                    <i class="fa-solid fa-compass-drafting text-blue-600"></i>
                    <span>Sistem Denah Peta Terpadu PT Hydraxle Perkasa</span>
                </div>
            </div>
        </div>

        <!-- 4. MODAL PENGATURAN LUAS GEDUNG & AREA (LIGHT THEME) -->
        <div x-show="areaModalOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             role="dialog" 
             aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="areaModalOpen = false"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-200">
                                <i class="fa-solid fa-sliders"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-base text-slate-900">Pengaturan Luas Gedung & Area</h3>
                                <p class="text-xs text-slate-500">Sesuaikan dimensi fisik dan resolusi grid denah gedung</p>
                            </div>
                        </div>
                        <button type="button" @click="areaModalOpen = false" class="text-slate-400 hover:text-slate-600 p-2">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form @submit.prevent="saveAreaSettings()" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Gedung / Area Gudang</label>
                            <input type="text" x-model="areaForm.name" required class="w-full text-xs border border-slate-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 font-semibold">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Kolom Grid (Lebar X)</label>
                                <input type="number" min="6" max="40" x-model.number="areaForm.grid_columns" required class="w-full text-xs font-mono font-bold border border-slate-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500">
                                <p class="text-[10px] text-slate-400 mt-1">Standar: 16 kolom (min 6, max 40)</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Baris Grid (Panjang Y)</label>
                                <input type="number" min="4" max="30" x-model.number="areaForm.grid_rows" required class="w-full text-xs font-mono font-bold border border-slate-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500">
                                <p class="text-[10px] text-slate-400 mt-1">Standar: 12 baris (min 4, max 30)</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Panjang Gedung (Meter)</label>
                                <input type="number" step="0.5" min="1" max="500" x-model.number="areaForm.length_meters" required class="w-full text-xs font-mono font-bold border border-slate-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Lebar Gedung (Meter)</label>
                                <input type="number" step="0.5" min="1" max="500" x-model.number="areaForm.width_meters" required class="w-full text-xs font-mono font-bold border border-slate-300 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Live Calculated Area Badge -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-600">Estimasi Total Luas Area Fisik:</span>
                            <span class="font-mono font-extrabold text-sm text-blue-700" 
                                  x-text="((areaForm.length_meters || 0) * (areaForm.width_meters || 0)).toLocaleString() + ' m²'"></span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan / Fungsi Gedung</label>
                            <textarea x-model="areaForm.description" rows="2" class="w-full text-xs border border-slate-300 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500" placeholder="Contoh: Gudang Karoseri Utama & Perakitan Dump Truck"></textarea>
                        </div>

                        <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-2">
                            <button type="button" @click="areaModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSavingArea" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-md transition disabled:opacity-50 flex items-center space-x-1.5">
                                <i class="fa-solid" :class="isSavingArea ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                <span x-text="isSavingArea ? 'Menerapkan...' : 'Simpan & Terapkan Luas Area'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 4.1 MODAL SESUAIKAN BENTUK BALOK & TINGKAT RAK (LIGHT THEME) -->
        <div x-show="configModalOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             role="dialog" 
             aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="configModalOpen = false"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all p-6">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg border border-indigo-200 shadow-xs">
                                <i class="fa-solid fa-shapes"></i>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-extrabold text-base text-slate-900" x-text="'Pengaturan ' + configForm.rack_number"></h3>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                          :class="configForm.is_pallet ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-blue-100 text-blue-900 border border-blue-300'"
                                          x-text="configForm.is_pallet ? 'Pallet Lantai' : 'Rak Bertingkat'"></span>
                                </div>
                                <p class="text-xs text-slate-500">Sesuaikan bentuk balok denah dan jumlah tingkat rak penyimpanan</p>
                            </div>
                        </div>
                        <button type="button" @click="configModalOpen = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="saveLocationConfig()" class="space-y-5 mt-5">
                        
                        <!-- BAGIAN 1: BENTUK BALOK DENAH (SHAPE / UKURAN KOTAK) -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fa-solid fa-vector-square text-indigo-600"></i>
                                    <span>Bentuk & Ukuran Balok Denah</span>
                                </label>
                                <span class="text-xs font-mono font-black px-2 py-0.5 rounded bg-indigo-100 text-indigo-900 border border-indigo-200"
                                      x-text="configForm.grid_w + ' Kolom × ' + configForm.grid_h + ' Baris (' + (configForm.grid_w * configForm.grid_h) + ' Balok)'"></span>
                            </div>

                            <!-- Shape Quick Presets -->
                            <div>
                                <span class="text-[11px] text-slate-500 font-semibold block mb-1.5">Pilihan Cepat (Preset Bentuk):</span>
                                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                    <button type="button" 
                                            @click="setShapePreset(1, 1)"
                                            :class="configForm.grid_w === 1 && configForm.grid_h === 1 ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="py-2 px-1 rounded-xl text-xs flex flex-col items-center justify-center transition">
                                        <span class="font-extrabold text-sm">1×1</span>
                                        <span class="text-[9px] opacity-75">Standar</span>
                                    </button>
                                    <button type="button" 
                                            @click="setShapePreset(2, 1)"
                                            :class="configForm.grid_w === 2 && configForm.grid_h === 1 ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="py-2 px-1 rounded-xl text-xs flex flex-col items-center justify-center transition">
                                        <span class="font-extrabold text-sm">2×1</span>
                                        <span class="text-[9px] opacity-75">Horiz 2</span>
                                    </button>
                                    <button type="button" 
                                            @click="setShapePreset(1, 2)"
                                            :class="configForm.grid_w === 1 && configForm.grid_h === 2 ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="py-2 px-1 rounded-xl text-xs flex flex-col items-center justify-center transition">
                                        <span class="font-extrabold text-sm">1×2</span>
                                        <span class="text-[9px] opacity-75">Vertik 2</span>
                                    </button>
                                    <button type="button" 
                                            @click="setShapePreset(2, 2)"
                                            :class="configForm.grid_w === 2 && configForm.grid_h === 2 ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="py-2 px-1 rounded-xl text-xs flex flex-col items-center justify-center transition">
                                        <span class="font-extrabold text-sm">2×2</span>
                                        <span class="text-[9px] opacity-75">Kotak 4</span>
                                    </button>
                                    <button type="button" 
                                            @click="setShapePreset(3, 1)"
                                            :class="configForm.grid_w === 3 && configForm.grid_h === 1 ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="py-2 px-1 rounded-xl text-xs flex flex-col items-center justify-center transition">
                                        <span class="font-extrabold text-sm">3×1</span>
                                        <span class="text-[9px] opacity-75">Panjang</span>
                                    </button>
                                    <button type="button" 
                                            @click="setShapePreset(1, 3)"
                                            :class="configForm.grid_w === 1 && configForm.grid_h === 3 ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="py-2 px-1 rounded-xl text-xs flex flex-col items-center justify-center transition">
                                        <span class="font-extrabold text-sm">1×3</span>
                                        <span class="text-[9px] opacity-75">Tinggi</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Custom Stepper Inputs for Width and Height -->
                            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-200">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Lebar (Kolom W)</label>
                                    <div class="flex items-center space-x-1.5">
                                        <button type="button" @click="configForm.grid_w = Math.max(1, configForm.grid_w - 1)" class="w-8 h-8 rounded-lg bg-white border border-slate-300 font-bold hover:bg-slate-100 text-slate-700">-</button>
                                        <input type="number" min="1" max="8" x-model.number="configForm.grid_w" class="w-full text-center text-xs border border-slate-300 rounded-lg py-1.5 font-bold">
                                        <button type="button" @click="configForm.grid_w = Math.min(8, configForm.grid_w + 1)" class="w-8 h-8 rounded-lg bg-white border border-slate-300 font-bold hover:bg-slate-100 text-slate-700">+</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Panjang (Baris H)</label>
                                    <div class="flex items-center space-x-1.5">
                                        <button type="button" @click="configForm.grid_h = Math.max(1, configForm.grid_h - 1)" class="w-8 h-8 rounded-lg bg-white border border-slate-300 font-bold hover:bg-slate-100 text-slate-700">-</button>
                                        <input type="number" min="1" max="6" x-model.number="configForm.grid_h" class="w-full text-center text-xs border border-slate-300 rounded-lg py-1.5 font-bold">
                                        <button type="button" @click="configForm.grid_h = Math.min(6, configForm.grid_h + 1)" class="w-8 h-8 rounded-lg bg-white border border-slate-300 font-bold hover:bg-slate-100 text-slate-700">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN 2: TINGKAT LANTAI RAK & VARIASI SLOT TIAP LANTAI - KHUSUS RAK -->
                        <div x-show="!configForm.is_pallet" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fa-solid fa-layer-group text-blue-600"></i>
                                    <span>Tingkat Lantai & Variasi Slot Rak</span>
                                </label>
                                <span class="text-xs font-mono font-black px-2 py-0.5 rounded bg-blue-100 text-blue-900 border border-blue-200"
                                      x-text="configForm.total_levels + ' Lantai (' + getTotalConfiguredSlots() + ' Slot Total)'"></span>
                            </div>

                            <!-- Level Presets: 2 Tingkat, 3 Tingkat, 4 Tingkat -->
                            <div>
                                <span class="text-[11px] text-slate-500 font-semibold block mb-1.5">Pilihan Jumlah Tingkat:</span>
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" 
                                            @click="setLevelsPreset(2)"
                                            :class="configForm.total_levels === 2 ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="py-2 px-2 rounded-xl text-xs flex flex-col items-center justify-center transition">
                                        <span class="font-extrabold text-sm">2 Tingkat</span>
                                        <span class="text-[9px] opacity-75">Lantai 1 - 2</span>
                                    </button>
                                    <button type="button" 
                                            @click="setLevelsPreset(3)"
                                            :class="configForm.total_levels === 3 ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="py-2 px-2 rounded-xl text-xs flex flex-col items-center justify-center transition">
                                        <span class="font-extrabold text-sm">3 Tingkat</span>
                                        <span class="text-[9px] opacity-75">Lantai 1 - 3</span>
                                    </button>
                                    <button type="button" 
                                            @click="setLevelsPreset(4)"
                                            :class="configForm.total_levels === 4 ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="py-2 px-2 rounded-xl text-xs flex flex-col items-center justify-center transition">
                                        <span class="font-extrabold text-sm">4 Tingkat</span>
                                        <span class="text-[9px] opacity-75">Lantai 1 - 4</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Preset Variasi Slot Tiap Lantai -->
                            <div class="pt-2 border-t border-slate-200">
                                <span class="text-[11px] text-slate-500 font-semibold block mb-1.5">Preset Slot Tiap Lantai:</span>
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" 
                                            @click="setAllSlots(6)"
                                            class="p-2 rounded-xl text-[11px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-center transition">
                                        Semua 6 Slot
                                    </button>
                                    <button type="button" 
                                            @click="setAllSlots(4)"
                                            class="p-2 rounded-xl text-[11px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-center transition">
                                        Semua 4 Slot
                                    </button>
                                    <button type="button" 
                                            @click="setCustomSlotsCombo()"
                                            class="p-2 rounded-xl text-[11px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-center transition">
                                        L1:2, L2:4, L3+:6
                                    </button>
                                </div>
                            </div>

                            <!-- Detail Rincian Slot per Lantai (Bisa bermacam-macam tiap lantai) -->
                            <div class="pt-2 border-t border-slate-200 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-700">Rincian Slot per Lantai (Bisa Berbeda Tiap Lantai):</span>
                                    <span class="text-[10px] text-slate-500 font-medium">1 s/d 20 slot per lantai</span>
                                </div>

                                <div class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
                                    <template x-for="lvl in getLevelsArrayDescending()" :key="'lvl-slot-'+lvl">
                                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200 shadow-xs">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-800 font-mono font-bold text-xs flex items-center justify-center" x-text="'L' + lvl"></span>
                                                <div>
                                                    <span class="text-xs font-bold text-slate-800" x-text="'Lantai ' + lvl"></span>
                                                    <span class="text-[10px] text-slate-400 block leading-none mt-0.5" 
                                                          x-text="lvl === 1 ? 'Lantai Dasar (Part Berat / Silinder)' : (lvl === 2 ? 'Lantai Menengah' : 'Lantai Atas (Part Ringan)')"></span>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-1.5">
                                                <button type="button" 
                                                        @click="updateLevelSlot(lvl, -1)" 
                                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center transition">-</button>
                                                <span class="w-14 text-center font-mono font-black text-xs text-blue-700" 
                                                      x-text="getLevelSlotCount(lvl) + ' Slot'"></span>
                                                <button type="button" 
                                                        @click="updateLevelSlot(lvl, 1)" 
                                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center transition">+</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                            <button type="button" @click="configModalOpen = false" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSavingConfig" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white flex items-center space-x-2 transition shadow-md disabled:opacity-50">
                                <i class="fa-solid" :class="isSavingConfig ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                <span x-text="isSavingConfig ? 'Menyimpan...' : 'Simpan Bentuk & Tingkat'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 5. QUICK INSPECTOR SLIDE-OUT DRAWER (LIGHT MODE / TEMA TERANG) -->
        <div x-show="inspectorOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-hidden" 
             aria-labelledby="slide-over-title" 
             role="dialog" 
             aria-modal="true">
            <!-- Backdrop -->
            <div x-show="inspectorOpen" 
                 x-transition:enter="ease-in-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in-out duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="inspectorOpen = false" 
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>

            <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                <div x-show="inspectorOpen" 
                     x-transition:enter="transform transition ease-in-out duration-300"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="w-screen max-w-lg bg-white shadow-2xl flex flex-col border-l border-slate-200">
                    
                    <!-- Drawer Header (Light Theme) -->
                    <div class="p-5 bg-slate-900 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg border shadow-inner"
                                 :class="activeRack?.is_pallet ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 'bg-blue-500/20 text-blue-400 border-blue-500/30'">
                                <i :class="activeRack?.is_pallet ? 'fa-solid fa-pallet' : 'fa-solid fa-cubes'"></i>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-extrabold text-lg text-white" x-text="activeRack?.rack_number"></h3>
                                    <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-white/20 text-amber-300" x-text="'Kode: ' + activeRack?.full_rack_code"></span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-indigo-500/30 text-indigo-300 border border-indigo-500/40"
                                          x-text="(activeRack?.grid_w || 1) + '×' + (activeRack?.grid_h || 1)"></span>
                                </div>
                                <p class="text-xs text-slate-300 mt-0.5" x-text="(activeRack?.zone_name || 'Area 1') + ' &bull; ' + (activeRack?.type_label || '')"></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" 
                                    @click="openConfigModal(activeRack)"
                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 flex items-center gap-1.5 transition shadow-xs">
                                <i class="fa-solid fa-pen-ruler text-[11px]"></i>
                                <span class="hidden sm:inline">Ubah Bentuk/Tingkat</span>
                            </button>
                            <button type="button" @click="inspectorOpen = false" class="text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800 transition">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Drawer Content (Light Theme) -->
                    <div class="flex-1 overflow-y-auto p-5 space-y-6">
                        
                        <!-- PALLET SPECIFIC NOTICE BANNER -->
                        <template x-if="activeRack?.is_pallet">
                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center space-x-3 text-amber-950">
                                <i class="fa-solid fa-pallet text-2xl text-amber-600"></i>
                                <div>
                                    <div class="font-bold text-xs">Penyimpanan Lantai Pallet (Non-Rak)</div>
                                    <div class="text-[11px] text-amber-800 mt-0.5">Area penempatan material bulky, silinder hidrolik & komponen karoseri langsung di atas pallet lantai tanpa rangka rak.</div>
                                </div>
                            </div>
                        </template>

                        <!-- Rack/Pallet Overview Metrics (4 Columns) -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-center">
                                <div class="text-[9px] uppercase font-bold text-slate-400">Lokasi / Aisle</div>
                                <div class="text-xs font-bold text-slate-800 mt-0.5 truncate" x-text="activeRack?.aisle || 'Lorong Staging'"></div>
                            </div>
                            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-center">
                                <div class="text-[9px] uppercase font-bold text-slate-400">Ukuran Denah</div>
                                <div class="text-xs font-bold text-indigo-700 mt-0.5 font-mono" x-text="(activeRack?.grid_w || 1) + ' × ' + (activeRack?.grid_h || 1) + ' Kotak'"></div>
                            </div>
                            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-center">
                                <div class="text-[9px] uppercase font-bold text-slate-400">Tingkat Rak</div>
                                <div class="text-xs font-bold text-slate-800 mt-0.5" x-text="activeRack?.is_pallet ? 'Pallet Lantai' : (activeRack?.total_levels || 4) + ' Tingkat'"></div>
                            </div>
                            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-center">
                                <div class="text-[9px] uppercase font-bold text-slate-400">Kapasitas Maks</div>
                                <div class="text-xs font-bold text-slate-800 mt-0.5" x-text="(activeRack?.max_capacity || 60) + ' Unit'"></div>
                            </div>
                        </div>

                        <!-- ELEVATION MATRIX (FOR RACKS) OR PALLET FLOOR STAGING (FOR PALLETS) -->
                        <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                        <i class="fa-solid" :class="activeRack?.is_pallet ? 'fa-pallet text-amber-600' : 'fa-layer-group text-blue-600'"></i>
                                        <span x-text="activeRack?.is_pallet ? 'Denah Posisi Pallet Lantai' : 'Denah Elevasi Tampak Depan Rak Bertingkat'"></span>
                                    </h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5" x-text="activeRack?.is_pallet ? 'Penataan slot material di atas pallet lantai' : 'Setiap slot memiliki kode unik (Contoh: 1-R3-L3-05)'"></p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-white border border-slate-200 text-slate-700"
                                          x-text="activeRack?.is_pallet ? 'Single Floor Level' : (activeRack?.total_levels || 4) + ' Tingkat'"></span>
                                </div>
                            </div>

                            <!-- Elevation Shelf Matrix (Levels x Slots) -->
                            <div class="space-y-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
                                <template x-for="level in (activeRack?.slot_matrix || [])" :key="level.level">
                                    <div class="flex items-center space-x-2">
                                        <!-- Floor Label Badge with slot count -->
                                        <div class="w-24 shrink-0 text-right pr-2">
                                            <span class="text-[10px] font-extrabold uppercase font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200"
                                                  x-text="level.level_label + ' (' + (level.slots?.length || 0) + ')'"></span>
                                        </div>

                                        <!-- Slots Row (Dynamically adapts to THIS level's specific slots count) -->
                                        <div class="grid gap-1.5 flex-1"
                                             :style="'grid-template-columns: repeat(' + (level.slots?.length || activeRack?.slots_per_level || 6) + ', minmax(0, 1fr));'">
                                            <template x-for="slot in level.slots" :key="slot.slot_number">
                                                <div class="rounded-lg p-1.5 text-center border transition relative group/slot flex flex-col justify-center min-h-[38px]"
                                                     :class="{
                                                         'bg-amber-100 border-amber-300 text-amber-950 shadow-xs font-bold ring-1 ring-amber-400': slot.is_occupied,
                                                         'bg-slate-50 border-slate-200 text-slate-400 hover:bg-slate-100': !slot.is_occupied
                                                     }">
                                                    <span class="text-[9px] font-mono block leading-none" x-text="slot.slot_number"></span>
                                                    
                                                    <!-- Occupied Item Icon -->
                                                    <template x-if="slot.is_occupied">
                                                        <div class="mt-0.5 flex items-center justify-center">
                                                            <i class="fa-solid fa-box text-[10px] text-amber-700"></i>
                                                        </div>
                                                    </template>
                                                    <template x-if="!slot.is_occupied">
                                                        <span class="text-[8px] text-slate-300">&bull;</span>
                                                    </template>

                                                    <!-- Slot Tooltip -->
                                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover/slot:block z-30 w-44 p-2 bg-slate-900 text-white text-[10px] rounded-lg shadow-xl pointer-events-none text-left">
                                                        <div class="font-mono font-bold text-amber-300" x-text="slot.full_code"></div>
                                                        <template x-if="slot.is_occupied">
                                                            <div class="mt-1">
                                                                <div class="font-bold truncate" x-text="slot.component_name"></div>
                                                                <div class="text-[9px] text-slate-300 font-mono" x-text="slot.part_number"></div>
                                                                <div class="text-[9px] text-amber-200 font-bold mt-0.5" x-text="'Stok: ' + slot.quantity + ' ' + slot.uom"></div>
                                                            </div>
                                                        </template>
                                                        <template x-if="!slot.is_occupied">
                                                            <div class="text-slate-400 italic mt-0.5">Slot Kosong / Tersedia</div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- LIST OF STORED COMPONENTS IN THIS RACK / PALLET -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-bold text-xs uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-boxes-stacked text-blue-600"></i>
                                    <span>Komponen Tersimpan (<span x-text="activeRack?.components?.length || 0"></span> Part)</span>
                                </h4>
                                <span class="text-[10px] text-slate-500 font-medium">Klik part untuk melihat spesifikasi</span>
                            </div>

                            <div class="space-y-3">
                                <template x-if="!activeRack?.components || activeRack?.components.length === 0">
                                    <div class="p-8 text-center bg-slate-50 border border-slate-200 rounded-2xl">
                                        <i class="fa-solid fa-box-open text-2xl text-slate-300 mb-2"></i>
                                        <p class="text-xs font-semibold text-slate-600">Belum ada komponen yang tersimpan</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Lakukan transaksi Inbound untuk menyimpan part ke lokasi ini</p>
                                    </div>
                                </template>

                                <template x-for="item in (activeRack?.components || [])" :key="item.id">
                                    <div class="p-4 bg-white border border-slate-200 rounded-2xl shadow-xs hover:border-blue-400 transition space-y-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <span class="font-mono text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200"
                                                      x-text="item.part_number"></span>
                                                <h5 class="text-sm font-extrabold text-slate-900 mt-1" x-text="item.name"></h5>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-base font-extrabold text-slate-900 font-mono" x-text="item.quantity + ' ' + item.uom"></div>
                                                <div class="text-[10px] text-slate-500 font-mono" x-text="'Lot: ' + item.batch"></div>
                                            </div>
                                        </div>

                                        <!-- EXACT LOCATION DETAIL (CONTOH: 1-R3-L3-05 ATAU 1-PLT1-01) -->
                                        <div class="p-2.5 bg-amber-50/70 border border-amber-200 rounded-xl flex items-center justify-between text-xs">
                                            <div class="flex items-center space-x-1.5 text-amber-950 font-medium">
                                                <i class="fa-solid fa-location-dot text-amber-600"></i>
                                                <span x-text="item.detail_location_label"></span>
                                            </div>
                                            <span class="font-mono font-black text-xs px-2 py-0.5 rounded bg-amber-200 text-amber-950 border border-amber-300"
                                                  x-text="item.specific_location_code"></span>
                                        </div>

                                        <!-- Actions -->
                                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                            <a :href="item.url" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                                Lihat Spesifikasi Part &rarr;
                                            </a>
                                            <a :href="'/transactions/create?component_id=' + item.id" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl transition inline-flex items-center gap-1 shadow-xs">
                                                <i class="fa-solid fa-arrow-up-from-bracket text-[10px]"></i> Ambil / Pick
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                    <!-- Drawer Footer Actions (Light Theme) -->
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3">
                        <a :href="'/cycle-counts/create'" class="flex-1 py-2.5 px-3 text-center rounded-xl bg-white hover:bg-slate-100 text-slate-800 font-bold text-xs border border-slate-300 transition shadow-xs">
                            <i class="fa-solid fa-clipboard-check mr-1.5 text-blue-600"></i> Buat Stok Opname
                        </a>
                        <a :href="'/locations/' + activeRack?.id" class="flex-1 py-2.5 px-3 text-center rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition shadow-xs">
                            <i class="fa-solid fa-arrow-up-right-from-square mr-1.5 text-amber-400"></i> Halaman Lengkap
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. ZONE & LOCATION CARDS (LIST VIEW BELOW THE MAP - LIGHT THEME) -->
        <div class="space-y-6 pt-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="font-extrabold text-lg text-slate-900">Rincian Fisik Rak & Pallet Gudang</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar rak bertingkat dan area pallet lantai di <strong x-text="activeWarehouse.name"></strong></p>
                </div>
                <a href="{{ route('cycle-counts.create') }}" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-plus text-amber-400"></i>
                    <span>Buat Sesi Stok Opname</span>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6">
                @forelse ($zones as $code => $zone)
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" id="zone-section-{{ $code }}">
                        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <span class="font-mono text-sm font-black px-2.5 py-1 rounded text-white shadow-xs
                                    {{ match($code) {
                                        '1' => 'bg-blue-600',
                                        '2' => 'bg-emerald-600',
                                        'A' => 'bg-indigo-600',
                                        'B' => 'bg-emerald-600',
                                        'C' => 'bg-amber-600',
                                        'D' => 'bg-purple-600',
                                        'E' => 'bg-cyan-600',
                                        'F' => 'bg-rose-600',
                                        default => 'bg-slate-800'
                                    } }}">
                                    {{ str_starts_with($code, '1') ? 'AREA 1' : (str_starts_with($code, '2') ? 'AREA 2' : 'ZONA ' . $code) }}
                                </span>
                                <div>
                                    <h4 class="font-bold text-sm text-slate-900">{{ $zone['name'] }}</h4>
                                    <p class="text-xs text-slate-500">{{ $zone['description'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-600">
                                <span>{{ $zone['locations']->count() }} Titik Simpan</span>
                                <span>&bull;</span>
                                <span class="font-mono text-slate-800">{{ number_format($zone['locations']->sum('max_capacity'), 0) }} Max Capacity</span>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($zone['locations'] as $loc)
                                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/40 hover:bg-white hover:shadow-md transition flex flex-col justify-between space-y-3">
                                        <div>
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-black text-sm text-slate-900">{{ $loc->display_rack_name }}</span>
                                                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded {{ $loc->is_pallet ? 'bg-amber-100 text-amber-800 border border-amber-200 font-bold' : 'bg-slate-200 text-slate-700' }}">
                                                        {{ $loc->is_pallet ? 'Pallet' : $loc->bin_level }}
                                                    </span>
                                                </div>
                                                <span class="text-xs font-mono font-bold {{ $loc->occupancy_rate >= 80 ? 'text-amber-600' : 'text-emerald-600' }}">
                                                    {{ $loc->occupancy_rate }}% Terisi
                                                </span>
                                            </div>

                                            <div class="text-xs text-slate-500 mt-1 line-clamp-1">
                                                {{ $loc->description }}
                                            </div>

                                            <div class="mt-2 text-xs font-mono text-slate-600 flex items-center justify-between">
                                                <span>{{ $loc->aisle }}</span>
                                                <span class="font-bold text-slate-900">{{ (int) $loc->total_stored_quantity }} / {{ (int) $loc->max_capacity }} unit</span>
                                            </div>

                                            @if ($loc->stockBalances->count() > 0)
                                                <div class="mt-2.5 pt-2 border-t border-slate-200/60 space-y-1">
                                                    @foreach ($loc->stockBalances->take(3) as $sb)
                                                        <div class="text-[11px] flex items-center justify-between">
                                                            <span class="font-semibold text-slate-800 truncate max-w-[180px]">{{ $sb->component->name ?? '-' }}</span>
                                                            <span class="font-mono text-slate-500">{{ (int) $sb->quantity }} {{ $sb->component->uom ?? 'PCS' }}</span>
                                                        </div>
                                                    @endforeach
                                                    @if ($loc->stockBalances->count() > 3)
                                                        <div class="text-[10px] text-slate-400 italic">+ {{ $loc->stockBalances->count() - 3 }} komponen lainnya</div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                            <button type="button" 
                                                    @click="openRackQuickDetail({{ $loc->id }})" 
                                                    class="text-xs font-bold text-amber-600 hover:text-amber-700">
                                                Lihat Elevasi & Slot &rarr;
                                            </button>
                                            <a href="{{ route('locations.show', $loc) }}" class="text-xs font-semibold text-blue-600 hover:underline">
                                                Detail Lengkap
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center bg-white rounded-2xl border border-slate-200 shadow-sm">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto text-xl mb-3 border border-blue-200 shadow-xs">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <h4 class="font-bold text-sm text-slate-900">Belum Ada Rak / Pallet Terdaftar di Gedung Ini</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                            Gunakan tombol <strong>+ Tambah Rak</strong> atau <strong>+ Tambah Pallet</strong> pada denah interaktif di atas untuk mulai menyusun tata letak ruang simpan.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- ALPINE.JS COMPONENT LOGIC (TEMA TERANG & DYNAMIC GRID) -->
    <script>
        function warehouseMap(config) {
            return {
                racks: config.initialRacks || [],
                warehouses: config.warehouses || [],
                activeWarehouse: config.activeWarehouse || {
                    id: 1,
                    name: 'Gedung Utama',
                    grid_columns: 16,
                    grid_rows: 12,
                    width_meters: 24.0,
                    length_meters: 32.0,
                    total_area_sqm: 768.0,
                    dimension_label: '32m × 24m (768 m²)',
                    grid_label: '16 × 12 Grid'
                },
                saveUrl: config.saveUrl,
                resetUrl: config.resetUrl,
                updateAreaUrl: config.updateAreaUrl,
                createLocationUrl: config.createLocationUrl,
                csrfToken: config.csrfToken,
                
                editMode: false,
                showGridGuides: false,
                hasUnsavedChanges: false,
                isSaving: false,
                isSavingArea: false,
                isCreatingLocation: false,
                
                searchQuery: '',
                selectedZone: 'ALL',
                selectedType: 'ALL',
                
                draggedRack: null,
                hoverCell: null,
                
                inspectorOpen: false,
                activeRack: null,
                
                areaModalOpen: false,
                areaForm: {
                    name: '',
                    grid_columns: 16,
                    grid_rows: 12,
                    width_meters: 24.0,
                    length_meters: 32.0,
                    description: ''
                },

                configModalOpen: false,
                isSavingConfig: false,
                configForm: {
                    id: null,
                    rack_number: '',
                    storage_type: 'rack',
                    is_pallet: false,
                    grid_w: 1,
                    grid_h: 1,
                    total_levels: 4,
                    slots_per_level: 6,
                    color: 'blue',
                    description: ''
                },
                
                toast: {
                    show: false,
                    type: 'info',
                    title: '',
                    message: ''
                },

                initMap() {
                    this.racks.forEach(r => {
                        r.grid_w = r.grid_w || 1;
                        r.grid_h = r.grid_h || 1;
                    });
                },

                notify(type, title, message) {
                    this.toast = { show: true, type, title, message };
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 4000);
                },

                switchWarehouse(warehouseId) {
                    window.location.href = '/warehouse-map?warehouse_id=' + warehouseId;
                },

                openAreaModal() {
                    this.areaForm = {
                        name: this.activeWarehouse.name,
                        grid_columns: this.activeWarehouse.grid_columns || 16,
                        grid_rows: this.activeWarehouse.grid_rows || 12,
                        width_meters: this.activeWarehouse.width_meters || 24.0,
                        length_meters: this.activeWarehouse.length_meters || 32.0,
                        description: this.activeWarehouse.description || ''
                    };
                    this.areaModalOpen = true;
                },

                async saveAreaSettings() {
                    this.isSavingArea = true;
                    try {
                        const url = '/warehouse-map/warehouses/' + this.activeWarehouse.id + '/update-area';
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.areaForm)
                        });

                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            this.activeWarehouse = data.warehouse;
                            
                            // Clamp any racks that are now outside the new grid
                            const maxCols = this.activeWarehouse.grid_columns;
                            const maxRows = this.activeWarehouse.grid_rows;
                            this.racks.forEach(r => {
                                if (r.grid_x > maxCols) r.grid_x = maxCols;
                                if (r.grid_y > maxRows) r.grid_y = maxRows;
                            });

                            this.areaModalOpen = false;
                            this.notify('success', 'Luas Area Diperbarui', data.message);
                        } else {
                            this.notify('error', 'Gagal Memperbarui', data.message || 'Periksa kembali isian form.');
                        }
                    } catch (err) {
                        console.error('Error updating area:', err);
                        this.notify('error', 'Koneksi Gagal', 'Tidak dapat menghubungi server.');
                    } finally {
                        this.isSavingArea = false;
                    }
                },

                async addNewLocation(type) {
                    this.isCreatingLocation = true;

                    // Find first available cell on grid
                    const maxCols = this.activeWarehouse.grid_columns || 16;
                    const maxRows = this.activeWarehouse.grid_rows || 12;
                    let targetX = 1;
                    let targetY = 1;

                    // Look for an empty grid cell
                    for (let r = 1; r <= maxRows; r++) {
                        let found = false;
                        for (let c = 1; c <= maxCols; c++) {
                            const occupied = this.racks.some(loc => loc.grid_x === c && loc.grid_y === r);
                            if (!occupied) {
                                targetX = c;
                                targetY = r;
                                found = true;
                                break;
                            }
                        }
                        if (found) break;
                    }

                    try {
                        const res = await fetch(this.createLocationUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                warehouse_id: this.activeWarehouse.id,
                                storage_type: type,
                                grid_x: targetX,
                                grid_y: targetY
                            })
                        });

                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            this.racks.push(data.location);
                            this.notify('success', 'Lokasi Ditambahkan', data.message);
                        } else {
                            this.notify('error', 'Gagal Menambah Lokasi', data.message || 'Terjadi kendala.');
                        }
                    } catch (err) {
                        console.error('Error creating location:', err);
                        this.notify('error', 'Koneksi Gagal', 'Tidak dapat menghubungi server.');
                    } finally {
                        this.isCreatingLocation = false;
                    }
                },

                getGridContainerStyle() {
                    const cols = this.activeWarehouse?.grid_columns || 16;
                    const rows = this.activeWarehouse?.grid_rows || 12;
                    return `display: grid; grid-template-columns: repeat(${cols}, minmax(54px, 1fr)); grid-template-rows: repeat(${rows}, minmax(54px, 1fr));`;
                },

                toggleEditMode() {
                    this.editMode = !this.editMode;
                    if (this.editMode) {
                        this.showGridGuides = true;
                        this.notify('info', 'Mode Desain Aktif', 'Tarik (drag) balok Rak atau Pallet untuk mengatur posisi pada kanvas denah.');
                    }
                },

                getRackClasses(rack) {
                    const isMatched = this.isRackMatched(rack);
                    const isPallet = rack.is_pallet || rack.storage_type === 'pallet';
                    let colorClass = '';

                    if (isPallet) {
                        colorClass = 'bg-gradient-to-br from-amber-500 via-amber-600 to-amber-700 border-amber-800 text-slate-950 shadow-amber-600/30';
                    } else {
                        switch (rack.color || rack.zone_code) {
                            case 'blue':
                                colorClass = 'bg-gradient-to-br from-blue-500 to-blue-600 border-blue-700 text-white shadow-blue-500/25';
                                break;
                            case 'emerald':
                                colorClass = 'bg-gradient-to-br from-emerald-500 to-emerald-600 border-emerald-700 text-white shadow-emerald-500/25';
                                break;
                            case 'amber':
                                colorClass = 'bg-gradient-to-br from-amber-400 to-amber-500 border-amber-600 text-slate-950 shadow-amber-500/25';
                                break;
                            case 'purple':
                                colorClass = 'bg-gradient-to-br from-purple-500 to-purple-600 border-purple-700 text-white shadow-purple-500/25';
                                break;
                            case 'cyan':
                                colorClass = 'bg-gradient-to-br from-cyan-500 to-cyan-600 border-cyan-700 text-white shadow-cyan-500/25';
                                break;
                            case 'rose':
                                colorClass = 'bg-gradient-to-br from-rose-500 to-rose-600 border-rose-700 text-white shadow-rose-500/25';
                                break;
                            case 'indigo':
                            default:
                                colorClass = 'bg-gradient-to-br from-indigo-500 to-indigo-600 border-indigo-700 text-white shadow-indigo-500/25';
                        }
                    }

                    if (!isMatched) {
                        return colorClass + ' opacity-20 filter grayscale scale-95';
                    }

                    if (this.editMode) {
                        return colorClass + ' ring-4 ring-amber-400 ring-offset-2 cursor-move hover:scale-105 shadow-xl';
                    }

                    return colorClass + ' cursor-pointer hover:ring-2 hover:ring-slate-900 hover:scale-105 hover:shadow-lg';
                },

                getRackStyle(rack) {
                    const col = rack.grid_x || 1;
                    const row = rack.grid_y || 1;
                    const spanW = rack.grid_w || 1;
                    const spanH = rack.grid_h || 1;
                    return `grid-column: ${col} / span ${spanW}; grid-row: ${row} / span ${spanH}; z-index: ${this.editMode ? '20' : '10'};`;
                },

                isRackMatched(rack) {
                    // Type filter: All, Rack, Pallet
                    if (this.selectedType === 'rack' && (rack.is_pallet || rack.storage_type === 'pallet')) {
                        return false;
                    }
                    if (this.selectedType === 'pallet' && (!rack.is_pallet && rack.storage_type !== 'pallet')) {
                        return false;
                    }

                    // Zone filter
                    if (this.selectedZone !== 'ALL' && rack.zone_code !== this.selectedZone) {
                        return false;
                    }

                    // Search Query filter
                    if (!this.searchQuery || !this.searchQuery.trim()) {
                        return true;
                    }

                    const q = this.searchQuery.toLowerCase().trim();
                    if (rack.rack_number.toLowerCase().includes(q)) return true;
                    if (rack.raw_rack_number && rack.raw_rack_number.toLowerCase().includes(q)) return true;
                    if (rack.rack_code && rack.rack_code.toLowerCase().includes(q)) return true;
                    if (rack.full_rack_code && rack.full_rack_code.toLowerCase().includes(q)) return true;
                    if (rack.zone_name && rack.zone_name.toLowerCase().includes(q)) return true;
                    if (rack.aisle && rack.aisle.toLowerCase().includes(q)) return true;
                    if (rack.description && rack.description.toLowerCase().includes(q)) return true;

                    // Search within components inside rack
                    if (rack.components && rack.components.length > 0) {
                        return rack.components.some(c => 
                            c.name.toLowerCase().includes(q) || 
                            c.part_number.toLowerCase().includes(q) ||
                            (c.specific_location_code && c.specific_location_code.toLowerCase().includes(q)) ||
                            (c.slot_number && c.slot_number.toLowerCase().includes(q))
                        );
                    }

                    return false;
                },

                filterRacks() {
                    // Trigger Alpine reactivity
                },

                onRackClick(rack) {
                    if (this.editMode) {
                        this.openConfigModal(rack);
                        return;
                    }
                    this.activeRack = rack;
                    this.inspectorOpen = true;
                },

                openRackQuickDetail(locationId) {
                    const rack = this.racks.find(r => r.id === locationId);
                    if (rack) {
                        this.onRackClick(rack);
                    }
                },

                openConfigModal(rack) {
                    if (!rack) return;
                    const levels = rack.total_levels || (rack.is_pallet ? 1 : 4);
                    const slots = rack.slots_per_level || (rack.is_pallet ? 2 : 6);
                    let slotsConfig = Object.assign({}, rack.level_slots_config || {});

                    // Initialize per level if empty
                    for (let i = 1; i <= levels; i++) {
                        if (slotsConfig[i] === undefined && slotsConfig[String(i)] === undefined) {
                            slotsConfig[i] = slots;
                        }
                    }

                    this.configForm = {
                        id: rack.id,
                        rack_number: rack.rack_number,
                        storage_type: rack.storage_type || (rack.is_pallet ? 'pallet' : 'rack'),
                        is_pallet: Boolean(rack.is_pallet || rack.storage_type === 'pallet'),
                        grid_w: rack.grid_w || 1,
                        grid_h: rack.grid_h || 1,
                        total_levels: levels,
                        slots_per_level: slots,
                        level_slots_config: slotsConfig,
                        color: rack.color || (rack.is_pallet ? 'amber' : 'blue'),
                        description: rack.description || ''
                    };
                    this.configModalOpen = true;
                },

                setShapePreset(w, h) {
                    this.configForm.grid_w = w;
                    this.configForm.grid_h = h;
                },

                setLevelsPreset(lvl) {
                    this.configForm.total_levels = lvl;
                    if (!this.configForm.level_slots_config) {
                        this.configForm.level_slots_config = {};
                    }
                    for (let i = 1; i <= lvl; i++) {
                        if (!this.configForm.level_slots_config[i]) {
                            this.configForm.level_slots_config[i] = this.configForm.slots_per_level || 6;
                        }
                    }
                    this.configForm.level_slots_config = Object.assign({}, this.configForm.level_slots_config);
                },

                getLevelsArrayDescending() {
                    const total = this.configForm.total_levels || 4;
                    const arr = [];
                    for (let i = total; i >= 1; i--) {
                        arr.push(i);
                    }
                    return arr;
                },

                getLevelSlotCount(lvl) {
                    if (!this.configForm.level_slots_config) {
                        this.configForm.level_slots_config = {};
                    }
                    if (this.configForm.level_slots_config[lvl] !== undefined) {
                        return this.configForm.level_slots_config[lvl];
                    }
                    if (this.configForm.level_slots_config[String(lvl)] !== undefined) {
                        return this.configForm.level_slots_config[String(lvl)];
                    }
                    return this.configForm.slots_per_level || 6;
                },

                updateLevelSlot(lvl, delta) {
                    if (!this.configForm.level_slots_config) {
                        this.configForm.level_slots_config = {};
                    }
                    const current = this.getLevelSlotCount(lvl);
                    const next = Math.max(1, Math.min(20, current + delta));
                    this.configForm.level_slots_config[lvl] = next;
                    this.configForm.level_slots_config = Object.assign({}, this.configForm.level_slots_config);
                },

                setAllSlots(count) {
                    this.configForm.slots_per_level = count;
                    this.configForm.level_slots_config = {};
                    for (let i = 1; i <= (this.configForm.total_levels || 4); i++) {
                        this.configForm.level_slots_config[i] = count;
                    }
                    this.configForm.level_slots_config = Object.assign({}, this.configForm.level_slots_config);
                },

                setCustomSlotsCombo() {
                    this.configForm.level_slots_config = {};
                    const total = this.configForm.total_levels || 4;
                    for (let i = 1; i <= total; i++) {
                        if (i === 1) this.configForm.level_slots_config[i] = 2;
                        else if (i === 2) this.configForm.level_slots_config[i] = 4;
                        else this.configForm.level_slots_config[i] = 6;
                    }
                    this.configForm.level_slots_config = Object.assign({}, this.configForm.level_slots_config);
                },

                getTotalConfiguredSlots() {
                    const total = this.configForm.total_levels || 4;
                    let sum = 0;
                    for (let i = 1; i <= total; i++) {
                        sum += this.getLevelSlotCount(i);
                    }
                    return sum;
                },

                async saveLocationConfig() {
                    if (!this.configForm.id) return;
                    this.isSavingConfig = true;

                    try {
                        const url = `/warehouse-map/locations/${this.configForm.id}/update-config`;
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                grid_w: this.configForm.grid_w,
                                grid_h: this.configForm.grid_h,
                                total_levels: this.configForm.total_levels,
                                slots_per_level: this.configForm.slots_per_level,
                                level_slots_config: this.configForm.level_slots_config,
                                color: this.configForm.color,
                                description: this.configForm.description
                            })
                        });

                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            const idx = this.racks.findIndex(r => r.id === this.configForm.id);
                            if (idx !== -1) {
                                const prevX = this.racks[idx].grid_x;
                                const prevY = this.racks[idx].grid_y;
                                this.racks[idx] = Object.assign({}, this.racks[idx], data.location, {
                                    grid_x: prevX,
                                    grid_y: prevY
                                });
                                if (this.activeRack && this.activeRack.id === this.configForm.id) {
                                    this.activeRack = this.racks[idx];
                                }
                            }
                            this.configModalOpen = false;
                            this.notify('success', 'Konfigurasi Diperbarui', data.message);
                        } else {
                            this.notify('error', 'Gagal Memperbarui', data.message || 'Terjadi kesalahan.');
                        }
                    } catch (err) {
                        console.error('Error updating location config:', err);
                        this.notify('error', 'Koneksi Gagal', 'Tidak dapat menghubungi server.');
                    } finally {
                        this.isSavingConfig = false;
                    }
                },

                // Drag & Drop Handlers
                onDragStart(e, rack) {
                    if (!this.editMode) return;
                    this.draggedRack = rack;
                    e.dataTransfer.setData('text/plain', rack.id);
                    e.dataTransfer.effectAllowed = 'move';
                },

                onDragOver(e, col, row) {
                    if (!this.editMode || !this.draggedRack) return;
                    e.preventDefault();
                    this.hoverCell = { col, row };
                },

                onDrop(e, col, row) {
                    if (!this.editMode || !this.draggedRack) return;
                    e.preventDefault();

                    const rack = this.draggedRack;
                    const maxCols = this.activeWarehouse?.grid_columns || 16;
                    const maxRows = this.activeWarehouse?.grid_rows || 12;

                    const spanW = rack.grid_w || 1;
                    const spanH = rack.grid_h || 1;

                    // Clamp to active building grid columns x rows considering multi-cell spans
                    let targetX = Math.max(1, Math.min(col, maxCols - (spanW - 1)));
                    let targetY = Math.max(1, Math.min(row, maxRows - (spanH - 1)));

                    rack.grid_x = targetX;
                    rack.grid_y = targetY;

                    this.draggedRack = null;
                    this.hoverCell = null;
                    this.hasUnsavedChanges = true;
                    this.notify('info', 'Posisi Diperbarui', `Balok ${rack.rack_number} (${spanW}×${spanH}) dipindahkan ke kolom ${targetX}, baris ${targetY}. Klik "Simpan Posisi" untuk menyimpan.`);
                },

                onDragEnd(e) {
                    this.draggedRack = null;
                    this.hoverCell = null;
                },

                isCellHovered(col, row) {
                    if (!this.hoverCell || !this.draggedRack) return false;
                    return (col === this.hoverCell.col && row === this.hoverCell.row);
                },

                async saveLayout() {
                    this.isSaving = true;

                    const payload = {
                        layout: this.racks.map(r => ({
                            id: r.id,
                            grid_x: r.grid_x,
                            grid_y: r.grid_y,
                            grid_w: r.grid_w || 1,
                            grid_h: r.grid_h || 1
                        }))
                    };

                    try {
                        const res = await fetch(this.saveUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            this.hasUnsavedChanges = false;
                            this.notify('success', 'Berhasil Disimpan', data.message || 'Denah rak & pallet berhasil diperbarui.');
                        } else {
                            this.notify('error', 'Gagal Menyimpan', data.message || 'Terjadi kesalahan validasi.');
                        }
                    } catch (err) {
                        console.error('Save error:', err);
                        this.notify('error', 'Koneksi Gagal', 'Tidak dapat menghubungi server.');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async resetLayout() {
                    if (!confirm('Apakah Anda yakin ingin mengembalikan posisi tata letak denah rak & pallet ke standar rancangan?')) {
                        return;
                    }

                    this.isSaving = true;

                    try {
                        const res = await fetch(this.resetUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                warehouse_id: this.activeWarehouse.id
                            })
                        });

                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            if (data.locations) {
                                data.locations.forEach(loc => {
                                    const r = this.racks.find(x => x.id === loc.id);
                                    if (r) {
                                        r.grid_x = loc.grid_x;
                                        r.grid_y = loc.grid_y;
                                        r.grid_w = loc.grid_w;
                                        r.grid_h = loc.grid_h;
                                    }
                                });
                            }
                            this.hasUnsavedChanges = false;
                            this.notify('success', 'Reset Berhasil', data.message);
                        } else {
                            this.notify('error', 'Gagal Reset', data.message || 'Terjadi kendala.');
                        }
                    } catch (err) {
                        console.error('Reset error:', err);
                        this.notify('error', 'Koneksi Gagal', 'Tidak dapat menghubungi server.');
                    } finally {
                        this.isSaving = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>

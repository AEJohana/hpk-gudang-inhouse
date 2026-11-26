<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2.5 sm:gap-4 w-full">
            <!-- Left: Back Button + Icon Badge + Title (Judul Saja Tanpa Deskripsi) -->
            <div class="flex items-center space-x-2 sm:space-x-3.5 min-w-0">
                <a href="{{ route('dashboard') }}" 
                   class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200/80 flex items-center justify-center text-slate-700 transition shadow-2xs shrink-0 cursor-pointer" 
                   title="Kembali ke Beranda">
                    <i class="fa-solid fa-arrow-left text-sm text-slate-600"></i>
                </a>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-700 shadow-2xs shrink-0">
                    <i class="fa-solid fa-industry text-base sm:text-lg"></i>
                </div>
                <h2 class="font-extrabold text-base sm:text-xl text-[#0a2342] tracking-tight leading-tight truncate">
                    Work Request Mesin
                </h2>
            </div>

            <!-- Right: Action Buttons (Anchored to Far Right) -->
            <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0 ml-auto">
                <button type="button" 
                        onclick="window.location.reload()" 
                        class="px-3 py-2 sm:px-3.5 bg-white hover:bg-slate-50 text-slate-700 font-medium text-xs rounded-xl border border-slate-300 shadow-2xs transition flex items-center justify-center space-x-1.5 cursor-pointer active:scale-95"
                        title="Segarkan antrean tiket WRI">
                    <i class="fa-solid fa-arrows-rotate text-xs text-slate-500"></i>
                    <span class="hidden md:inline">Refresh</span>
                </button>
                <a href="{{ route('work-station-supplies.index') }}" class="px-3 py-2 sm:px-3.5 bg-white hover:bg-slate-50 text-slate-700 font-medium text-xs rounded-xl border border-slate-300 shadow-2xs transition flex items-center justify-center text-center space-x-1.5">
                    <i class="fa-solid fa-truck-ramp-box text-xs text-slate-500"></i>
                    <span class="hidden md:inline">Material Handling</span>
                </a>
                @can('work-request.create')
                <a href="{{ route('work-requests.create') }}" class="px-3.5 py-2 bg-[#0a2342] hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center justify-center text-center space-x-1.5 whitespace-nowrap">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Membuat WR</span>
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
         x-data="wriQueueManager()"
         @if(isset($openDetailPayload) && $openDetailPayload)
             x-init='openDetailModal(@json($openDetailPayload))'
         @endif>

        <!-- 0. INTEGRATED WRI SUB-NAVIGATION TABS (Antrean, Safety Stock, Cetak WR) -->
        @include('work_requests.partials.sub_nav')

        <!-- 1. KPI SUMMARY STATS CARDS (INTERACTIVE QUICK FILTERS - 6 STATUS UTAMA) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3" data-flip-grid>
            <a href="{{ route('work-requests.index', ['scope' => $scope]) }}" 
               @click.prevent="switchTab('all', '{{ route('work-requests.index', ['scope' => $scope]) }}')"
               data-flip-card
               class="bg-white hover:bg-slate-50/80 rounded-2xl p-3.5 xl:p-3 border border-slate-200/80 shadow-xs flex items-center space-x-2.5 transition duration-300 ease-out group active:scale-[0.99] cursor-pointer min-w-0"
               :class="activeTab === 'all' ? 'bg-slate-50/90 shadow-sm -translate-y-0.5' : ''"
               title="Klik untuk menampilkan semua tiket">
                <div class="w-10 h-10 xl:w-9 xl:h-9 rounded-xl bg-slate-100 group-hover:bg-[#0a2342] group-hover:text-amber-400 text-slate-700 flex items-center justify-center flex-shrink-0 text-base font-bold transition">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block truncate text-[10px] font-bold uppercase tracking-wider text-slate-400">Total WRI</span>
                    <h3 class="text-xl font-black text-slate-900 leading-tight mt-0.5">{{ $totalWri }}</h3>
                    <span class="block truncate text-[10px] text-slate-500" title="Semua tiket order">Semua tiket order</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'verifikasi']) }}" 
               @click.prevent="switchTab('verifikasi', '{{ route('work-requests.index', ['scope' => $scope, 'status' => 'verifikasi']) }}')"
               data-flip-card
               class="bg-white hover:bg-slate-50/80 rounded-2xl p-3.5 xl:p-3 border border-slate-200/80 shadow-xs flex items-center space-x-2.5 transition duration-300 ease-out group active:scale-[0.99] cursor-pointer min-w-0"
               :class="activeTab === 'verifikasi' ? 'bg-slate-50/90 shadow-sm -translate-y-0.5' : ''"
               title="Klik untuk menyaring tiket yang butuh verifikasi">
                <div class="w-10 h-10 xl:w-9 xl:h-9 rounded-xl {{ $needsApprovalCount > 0 ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center flex-shrink-0 text-base font-bold transition">
                    <i class="fa-solid fa-stamp"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block truncate text-[10px] font-bold uppercase tracking-wider text-amber-800">Perlu Verifikasi</span>
                    <h3 class="text-xl font-black text-amber-950 leading-tight mt-0.5">{{ $needsApprovalCount }}</h3>
                    <span class="block truncate text-[10px] text-amber-700 font-medium" title="Pengaju & Penerima">Pengaju & Penerima</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'proses']) }}" 
               @click.prevent="switchTab('proses', '{{ route('work-requests.index', ['scope' => $scope, 'status' => 'proses']) }}')"
               data-flip-card
               class="bg-white hover:bg-slate-50/80 rounded-2xl p-3.5 xl:p-3 border border-slate-200/80 shadow-xs flex items-center space-x-2.5 transition duration-300 ease-out group active:scale-[0.99] cursor-pointer min-w-0"
               :class="activeTab === 'proses' ? 'bg-slate-50/90 shadow-sm -translate-y-0.5' : ''"
               title="Klik untuk menyaring tiket yang sedang dalam proses / antrian">
                <div class="w-10 h-10 xl:w-9 xl:h-9 rounded-xl bg-blue-50 group-hover:bg-blue-600 group-hover:text-white text-blue-700 flex items-center justify-center flex-shrink-0 text-base font-bold transition">
                    <i class="fa-solid fa-gears animate-spin" style="animation-duration: 6s;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block truncate text-[10px] font-bold uppercase tracking-wider text-blue-600">Sedang Proses</span>
                    <h3 class="text-xl font-black text-blue-700 leading-tight mt-0.5">{{ $inProcessCount }}</h3>
                    <span class="block truncate text-[10px] text-blue-500" title="Antrian, Mesin, MH">Antrian, Mesin, MH</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'siap']) }}" 
               @click.prevent="switchTab('siap', '{{ route('work-requests.index', ['scope' => $scope, 'status' => 'siap']) }}')"
               data-flip-card
               class="bg-white hover:bg-slate-50/80 rounded-2xl p-3.5 xl:p-3 border border-slate-200/80 shadow-xs flex items-center space-x-2.5 transition duration-300 ease-out group active:scale-[0.99] cursor-pointer min-w-0"
               :class="activeTab === 'siap' ? 'bg-slate-50/90 shadow-sm -translate-y-0.5' : ''"
               title="Klik untuk menyaring tiket siap diambil / menuju pengaju">
                <div class="w-10 h-10 xl:w-9 xl:h-9 rounded-xl bg-teal-500 group-hover:bg-teal-600 text-white flex items-center justify-center flex-shrink-0 text-base font-bold transition">
                    <i class="fa-solid fa-dolly"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block truncate text-[10px] font-bold uppercase tracking-wider text-teal-800">Siap Diambil</span>
                    <h3 class="text-xl font-black text-teal-900 leading-tight mt-0.5">{{ $readyForWarehouseCount }}</h3>
                    <span class="block truncate text-[10px] text-teal-700 font-semibold" title="Menuju Pengaju">Menuju Pengaju</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'selesai']) }}" 
               @click.prevent="switchTab('selesai', '{{ route('work-requests.index', ['scope' => $scope, 'status' => 'selesai']) }}')"
               data-flip-card
               class="bg-white hover:bg-slate-50/80 rounded-2xl p-3.5 xl:p-3 border border-slate-200/80 shadow-xs flex items-center space-x-2.5 transition duration-300 ease-out group active:scale-[0.99] cursor-pointer min-w-0"
               :class="activeTab === 'selesai' ? 'bg-slate-50/90 shadow-sm -translate-y-0.5' : ''"
               title="Klik untuk menyaring tiket yang sudah selesai">
                <div class="w-10 h-10 xl:w-9 xl:h-9 rounded-xl bg-emerald-50 group-hover:bg-emerald-600 group-hover:text-white text-emerald-700 flex items-center justify-center flex-shrink-0 text-base font-bold transition">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block truncate text-[10px] font-bold uppercase tracking-wider text-emerald-600">Selesai</span>
                    <h3 class="text-xl font-black text-emerald-700 leading-tight mt-0.5">{{ $receivedCount }}</h3>
                    <span class="block truncate text-[10px] text-emerald-600" title="Diterima Pengaju">Diterima Pengaju</span>
                </div>
            </a>

            <!-- 6. Ditolak / Batal -->
            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'ditolak']) }}" 
               @click.prevent="switchTab('ditolak', '{{ route('work-requests.index', ['scope' => $scope, 'status' => 'ditolak']) }}')"
               data-flip-card
               class="bg-white hover:bg-slate-50/80 rounded-2xl p-3.5 xl:p-3 border border-slate-200/80 shadow-xs flex items-center space-x-2.5 transition duration-300 ease-out group active:scale-[0.99] cursor-pointer min-w-0"
               :class="activeTab === 'ditolak' ? 'bg-slate-50/90 shadow-sm -translate-y-0.5' : ''"
               title="Klik untuk menyaring tiket yang ditolak atau dibatalkan">
                <div class="w-10 h-10 xl:w-9 xl:h-9 rounded-xl {{ $rejectedCount > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-500' }} group-hover:bg-rose-600 group-hover:text-white flex items-center justify-center flex-shrink-0 text-base font-bold transition">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block truncate text-[10px] font-bold uppercase tracking-wider text-rose-600">Ditolak / Batal</span>
                    <h3 class="text-xl font-black text-rose-700 leading-tight mt-0.5">{{ $rejectedCount }}</h3>
                    <span class="block truncate text-[10px] text-rose-500 font-medium" title="Approval Ditolak">Approval Ditolak</span>
                </div>
            </a>
        </div>

        <!-- 2. UNIFIED WORK REQUEST CONTROL BAR (2 KOLOM SEJAJAR: DROPDOWN STATUS & PENCARIAN) -->
        <div class="bg-white p-2.5 sm:p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-2 w-full">
            <!-- Kolom 1: Dropdown Filter Status Kaya (Icon + Badge + Chevron) -->
            <div class="relative w-48 sm:w-60 lg:w-64 shrink-0" 
                 x-data="{ open: false }" 
                 @click.outside="open = false" 
                 @keydown.escape.window="open = false">
                
                <!-- Trigger Button: Menampilkan Status Aktif, Icon, Badge Angka & Chevron -->
                <button type="button" 
                        @click="open = !open" 
                        class="w-full py-2 px-2.5 sm:px-3.5 bg-slate-50 hover:bg-slate-100 border border-slate-300 rounded-xl text-xs font-bold transition flex items-center justify-between shadow-2xs cursor-pointer active:scale-98"
                        title="Pilih Status Work Request">
                    <div class="flex items-center space-x-1.5 sm:space-x-2 min-w-0">
                        <!-- Icon Dinamis Sesuai Active Tab -->
                        <i class="text-xs shrink-0" 
                           :class="{
                               'fa-solid fa-list text-slate-700': activeTab === 'all',
                               'fa-solid fa-stamp text-amber-600': activeTab === 'verifikasi',
                               'fa-solid fa-gears text-blue-600': activeTab === 'proses',
                               'fa-solid fa-dolly text-teal-600': activeTab === 'siap',
                               'fa-solid fa-circle-check text-emerald-600': activeTab === 'selesai',
                               'fa-solid fa-ban text-rose-600': activeTab === 'ditolak',
                               'fa-solid fa-triangle-exclamation text-rose-600': activeTab === 'overdue'
                           }"></i>
                        
                        <!-- Label Dinamis -->
                        <span class="truncate text-slate-900" 
                              x-text="{
                                  'all': 'Semua WRI',
                                  'verifikasi': 'Perlu Verifikasi',
                                  'proses': 'Sedang Proses',
                                  'siap': 'Siap Diambil',
                                  'selesai': 'Selesai',
                                  'ditolak': 'Ditolak / Batal',
                                  'overdue': 'Terlambat > 3 Hari'
                              }[activeTab] || 'Pilih Status'">Semua WRI</span>
                        
                        <!-- Badge Counter Dinamis -->
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black shrink-0"
                              :class="{
                                  'bg-slate-200 text-slate-800': activeTab === 'all',
                                  'bg-rose-600 text-white animate-pulse': activeTab === 'verifikasi',
                                  'bg-blue-100 text-blue-800': activeTab === 'proses',
                                  'bg-teal-100 text-teal-800': activeTab === 'siap',
                                  'bg-emerald-100 text-emerald-800': activeTab === 'selesai',
                                  'bg-rose-100 text-rose-800': activeTab === 'ditolak',
                                  'bg-rose-100 text-rose-800': activeTab === 'overdue'
                              }"
                              x-text="{
                                  'all': '{{ $totalWri }}',
                                  'verifikasi': '{{ $needsApprovalCount }}',
                                  'proses': '{{ $inProcessCount }}',
                                  'siap': '{{ $readyForWarehouseCount }}',
                                  'selesai': '{{ $receivedCount }}',
                                  'ditolak': '{{ $rejectedCount }}',
                                  'overdue': '{{ $overdueSlaCount }}'
                              }[activeTab] || '0'">{{ $totalWri }}</span>
                    </div>

                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 shrink-0 ml-1 transition-transform duration-200" 
                       :class="{ 'rotate-180': open }"></i>
                </button>

                <!-- Dropdown Popover Panel -->
                <div x-show="open" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                     class="absolute left-0 top-full mt-1.5 w-60 sm:w-64 bg-white rounded-2xl shadow-xl border border-slate-200 p-1.5 z-50 space-y-1">
                    
                    <!-- 1. Semua WRI -->
                    <button type="button" 
                            @click="switchTab('all', '{{ route('work-requests.index', ['scope' => $scope]) }}'); open = false;"
                            class="w-full px-3 py-2 rounded-xl text-xs font-bold transition flex items-center justify-between cursor-pointer"
                            :class="activeTab === 'all' ? 'bg-[#0a2342] text-amber-400' : 'text-slate-700 hover:bg-slate-100'">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-list text-xs"></i>
                            <span>Semua WRI</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="activeTab === 'all' ? 'bg-amber-400 text-slate-950' : 'bg-slate-200 text-slate-700'">
                            {{ $totalWri }}
                        </span>
                    </button>

                    <!-- 2. Perlu Verifikasi -->
                    <button type="button" 
                            @click="switchTab('verifikasi', '{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'verifikasi', 'overdue_sla' => null])) }}'); open = false;"
                            class="w-full px-3 py-2 rounded-xl text-xs font-bold transition flex items-center justify-between cursor-pointer"
                            :class="activeTab === 'verifikasi' ? 'bg-amber-400 text-slate-950 font-black' : 'text-slate-700 hover:bg-slate-100'">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-stamp text-xs"></i>
                            <span>Perlu Verifikasi</span>
                        </div>
                        @if($needsApprovalCount > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                  :class="activeTab === 'verifikasi' ? 'bg-slate-950 text-white' : 'bg-rose-600 text-white animate-pulse'">
                                {{ $needsApprovalCount }}
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-200 text-slate-700">0</span>
                        @endif
                    </button>

                    <!-- 3. Sedang Proses -->
                    <button type="button" 
                            @click="switchTab('proses', '{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'proses', 'overdue_sla' => null])) }}'); open = false;"
                            class="w-full px-3 py-2 rounded-xl text-xs font-bold transition flex items-center justify-between cursor-pointer"
                            :class="activeTab === 'proses' ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-100'">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-gears text-xs"></i>
                            <span>Sedang Proses</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="activeTab === 'proses' ? 'bg-blue-800 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $inProcessCount }}
                        </span>
                    </button>

                    <!-- 4. Siap Diambil -->
                    <button type="button" 
                            @click="switchTab('siap', '{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'siap', 'overdue_sla' => null])) }}'); open = false;"
                            class="w-full px-3 py-2 rounded-xl text-xs font-bold transition flex items-center justify-between cursor-pointer"
                            :class="activeTab === 'siap' ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-dolly text-xs"></i>
                            <span>Siap Diambil</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="activeTab === 'siap' ? 'bg-teal-800 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $readyForWarehouseCount }}
                        </span>
                    </button>

                    <!-- 5. Selesai -->
                    <button type="button" 
                            @click="switchTab('selesai', '{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'selesai', 'overdue_sla' => null])) }}'); open = false;"
                            class="w-full px-3 py-2 rounded-xl text-xs font-bold transition flex items-center justify-between cursor-pointer"
                            :class="activeTab === 'selesai' ? 'bg-emerald-600 text-white' : 'text-slate-700 hover:bg-slate-100'">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-circle-check text-xs"></i>
                            <span>Selesai</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="activeTab === 'selesai' ? 'bg-emerald-800 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $receivedCount }}
                        </span>
                    </button>

                    <!-- 6. Ditolak / Batal -->
                    <button type="button" 
                            @click="switchTab('ditolak', '{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'ditolak', 'overdue_sla' => null])) }}'); open = false;"
                            class="w-full px-3 py-2 rounded-xl text-xs font-bold transition flex items-center justify-between cursor-pointer"
                            :class="activeTab === 'ditolak' ? 'bg-rose-600 text-white' : 'text-slate-700 hover:bg-slate-100'">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-ban text-xs"></i>
                            <span>Ditolak / Batal</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="activeTab === 'ditolak' ? 'bg-rose-800 text-white' : 'bg-slate-200 text-slate-700'">
                            {{ $rejectedCount }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Kolom 2: Kolom Pencarian Fleksibel (Live Debounced) -->
            <div class="relative flex-1 min-w-0">
                <form method="GET" action="{{ route('work-requests.index') }}" @submit.prevent="applySearchNow()" class="relative w-full">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           @input="onSearchInput()"
                           value="{{ request()->search }}" 
                           placeholder="Cari No. WRI, komponen, SPK, departemen..." 
                           class="w-full pl-8 sm:pl-9 pr-8 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none transition shadow-2xs">
                    <span x-show="isSearching" x-cloak style="display: none;" class="absolute right-8 top-1/2 -translate-y-1/2 text-amber-600 text-xs pointer-events-none">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </span>
                    <button type="button" 
                            x-show="searchQuery.trim().length > 0" 
                            x-cloak
                            style="display: none;"
                            @click="clearSearch()" 
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600 p-0.5 transition cursor-pointer" 
                            title="Hapus pencarian">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- 2.5. PERINGATAN TERLAMBAT (> 3 HARI) BANNER (LAMPIRAN 1 STYLE) -->
        @if($overdueSlaCount > 0 || request()->boolean('overdue_sla'))
        <div class="bg-red-50/80 border border-slate-200/80 rounded-2xl p-3.5 sm:px-5 sm:py-3.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0 text-xl shadow-2xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <strong class="text-red-700 font-extrabold text-sm block">Peringatan!</strong>
                    <span class="text-slate-700 text-xs sm:text-sm">
                        Terdapat <b class="text-red-950 font-black">{{ $overdueSlaCount }}</b> Work Request yang tertunda lebih dari 3 hari.
                    </span>
                </div>
            </div>
            <div>
                @if(request()->boolean('overdue_sla'))
                    <a href="{{ route('work-requests.index', array_merge(request()->except('overdue_sla', 'page'))) }}" 
                       @click.prevent="switchTab('all', '{{ route('work-requests.index', array_merge(request()->except('overdue_sla', 'page'))) }}')"
                       class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-full shadow-sm transition inline-flex items-center space-x-1.5 whitespace-nowrap active:scale-95 cursor-pointer"
                       title="Tampilkan seluruh tiket">
                        <i class="fa-solid fa-xmark text-xs"></i>
                        <span>Tampilkan Semua</span>
                    </a>
                @else
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['overdue_sla' => 1])) }}" 
                       @click.prevent="switchTab('overdue', '{{ route('work-requests.index', array_merge(request()->query(), ['overdue_sla' => 1])) }}')"
                       class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-full shadow-sm transition inline-flex items-center space-x-1.5 whitespace-nowrap active:scale-95 cursor-pointer"
                       title="Saring tiket yang tertunda lebih dari 3 hari">
                        <i class="fa-solid fa-filter text-xs"></i>
                        <span>Lihat yang Terlambat</span>
                    </a>
                @endif
            </div>
        </div>
        @endif

        <!-- 3. WORK REQUESTS UNIFIED RESPONSIVE TABLE (DESKTOP & HP) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden relative min-h-[320px]">
            <!-- Elegant Non-Blocking Skeleton Loader -->
            <div x-show="tabLoading" 
                 x-transition.opacity.duration.150ms 
                 class="absolute inset-0 bg-white/80 backdrop-blur-2xs z-20 flex flex-col items-center justify-center space-y-3 p-8"
                 style="display: none;">
                <div class="w-9 h-9 rounded-full border-3 border-amber-400 border-t-transparent animate-spin"></div>
                <span class="text-xs font-bold text-slate-700 tracking-wide animate-pulse">Memuat data tiket...</span>
            </div>

            <div id="wri-table-container" data-lenis-prevent="true">
                @include('work_requests.partials._table_content')
            </div>
        </div>

        <!-- Include Pop-up Modals -->
        @include('work_requests.partials.assign_operator_modal')
        @include('work_requests.partials.process_modal')
        @include('work_requests.partials.process_camera_modal')
        @include('work_requests.partials.detail_modal')
        @include('work_requests.partials.image_preview_modal')
        @include('work_requests.partials.reject_modal')
        @include('work_requests.partials.qr_zoom_modal')
        @include('work_requests.partials.edit_routing_modal')

    </div>

    <script>
        function wriQueueManager() {
            return {
                isWrScanRequired: {{ ($isWrScanRequired ?? false) ? 'true' : 'false' }},
                processCameraModalOpen: false,
                html5ProcessQrCode: null,
                gunScanBuffer: '',
                gunScanStartTime: 0,
                gunLastCharTime: 0,
                gunScanHandler: null,
                activeTab: '{{ request()->boolean('overdue_sla') ? 'overdue' : (request('status') ?: 'all') }}',
                searchQuery: @js(request('search', '')),
                searchDebounceTimer: null,
                activeFetchController: null,
                isSearching: false,
                tabLoading: false,
                tabCache: {},
                departmentOperators: @json($departmentOperatorsMap ?? []),
                allFabricationDepts: @json(isset($fabricationDepartments) ? $fabricationDepartments->pluck('name') : []),
                currentUserName: @json(auth()->user()->name),
                init() {
                    const container = document.getElementById('wri-table-container');
                    if (container) {
                        container.dataset.livePagination = 'true';
                        this.tabCache[this.getCacheKey(this.activeTab)] = container.innerHTML;
                    }

                    // Intercept pagination clicks within the table container for seamless navigation
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('#wri-table-container .pagination a, #wri-table-container a[href*="page="]');
                        if (link && link.href) {
                            e.preventDefault();
                            window.history.replaceState({ tab: this.activeTab }, '', link.href);
                            this.fetchPartial(link.href, null, false);
                        }
                    });

                    // Intercept per-page selector changes within the table container
                    document.addEventListener('change', (e) => {
                        const select = e.target.closest('#wri-table-container select[data-per-page-select]');
                        if (!select) return;
                        const url = new URL(window.location.href);
                        url.searchParams.set('per_page', select.value);
                        url.searchParams.delete('page');
                        this.tabCache = {};
                        window.history.replaceState({ tab: this.activeTab }, '', url.toString());
                        this.fetchPartial(url.toString(), this.getCacheKey(this.activeTab), true);
                    });

                    // Handle browser back/forward buttons
                    window.addEventListener('popstate', (e) => {
                        if (e.state && e.state.tab) {
                            const params = new URLSearchParams(window.location.search);
                            this.searchQuery = params.get('search') || '';
                            this.switchTab(e.state.tab, window.location.href, false);
                        }
                    });
                },
                getCacheKey(tabKey) {
                    return `${tabKey}|${(this.searchQuery || '').trim()}`;
                },
                buildTabUrl(tabKey) {
                    const url = new URL(@js(route('work-requests.index')), window.location.origin);
                    url.searchParams.set('scope', @js($scope));
                    if (tabKey === 'overdue') {
                        url.searchParams.set('overdue_sla', '1');
                    } else if (tabKey && tabKey !== 'all') {
                        url.searchParams.set('status', tabKey);
                    }
                    const q = (this.searchQuery || '').trim();
                    if (q !== '') {
                        url.searchParams.set('search', q);
                    }
                    const activePerPage = new URLSearchParams(window.location.search).get('per_page');
                    if (activePerPage) {
                        url.searchParams.set('per_page', activePerPage);
                    }
                    return url.toString();
                },
                onSearchInput() {
                    clearTimeout(this.searchDebounceTimer);
                    const delay = this.searchQuery.trim() === '' ? 120 : 350;
                    this.searchDebounceTimer = setTimeout(() => this.applySearchNow(), delay);
                },
                clearSearch() {
                    this.searchQuery = '';
                    clearTimeout(this.searchDebounceTimer);
                    this.applySearchNow();
                },
                applySearchNow() {
                    const targetUrl = this.buildTabUrl(this.activeTab);
                    window.history.replaceState({ tab: this.activeTab }, '', targetUrl);
                    this.isSearching = true;
                    this.fetchPartial(targetUrl, this.getCacheKey(this.activeTab), true);
                },
                renderTableHtml(html) {
                    const container = document.getElementById('wri-table-container');
                    if (!container) return;

                    container.style.transition = 'none';
                    container.style.opacity = '0';
                    container.style.transform = 'translateY(4px)';
                    container.innerHTML = html;

                    if (window.Alpine) {
                        window.Alpine.initTree(container);
                    }

                    window.requestAnimationFrame(() => {
                        container.style.transition = 'opacity 220ms ease-out, transform 220ms ease-out';
                        container.style.opacity = '1';
                        container.style.transform = 'translateY(0)';
                    });
                },
                switchTab(tabKey, targetUrl, pushState = true) {
                    const resolvedUrl = this.buildTabUrl(tabKey);
                    const cacheKey = this.getCacheKey(tabKey);

                    if (this.activeTab === tabKey && this.tabCache[cacheKey]) {
                        return;
                    }
                    this.activeTab = tabKey;
                    if (pushState) {
                        window.history.pushState({ tab: tabKey }, '', resolvedUrl);
                    }

                    // Check in-memory cache first (0ms instant response) with smooth fade-slide
                    if (this.tabCache[cacheKey]) {
                        this.renderTableHtml(this.tabCache[cacheKey]);
                        return;
                    }

                    // Otherwise fetch partial via AJAX
                    this.fetchPartial(resolvedUrl, cacheKey, true);
                },
                fetchPartial(url, cacheKey = null, saveToCache = true) {
                    if (this.activeFetchController) {
                        this.activeFetchController.abort();
                    }
                    this.activeFetchController = new AbortController();
                    this.tabLoading = true;
                    const fetchUrl = url + (url.includes('?') ? '&' : '?') + '_partial=1';
                    
                    fetch(fetchUrl, {
                        signal: this.activeFetchController.signal,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Partial': '1'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        this.renderTableHtml(html);
                        if (saveToCache && cacheKey) {
                            this.tabCache[cacheKey] = html;
                        }
                        this.tabLoading = false;
                        this.isSearching = false;
                    })
                    .catch(err => {
                        if (err.name === 'AbortError') {
                            return;
                        }
                        console.error('Gagal memuat partial tab:', err);
                        this.tabLoading = false;
                        this.isSearching = false;
                        window.location.href = url;
                    });
                },
                processModalOpen: false,
                detailModalOpen: false,
                assignModalOpen: false,
                imageModalOpen: false,
                rejectModalOpen: false,
                qrZoomModal: false,
                editRoutingModalOpen: false,
                isSubmittingRouting: false,
                isDeptDropdownOpen: false,
                selectedDepts: [],
                deptSearch: '',
                reasonText: '',
                editRoutingData: {
                    id: null,
                    wriNumber: '',
                    actionUrl: '',
                    currentRoutingText: '',
                },
                rejectData: {
                    actionUrl: '',
                    wriNumber: '',
                    reason: '',
                },
                qrZoomData: {
                    qrUrl: '',
                    wriNumber: '',
                    componentName: '',
                },
                imageData: {
                    imageUrl: '',
                    componentName: '',
                    partNumber: '',
                    dimension: '',
                    isPdf: false,
                    zoomLevel: 1.0,
                },
                assignData: {
                    id: null,
                    wriNumber: '',
                    componentName: '',
                    partNumber: '',
                    deptPengaju: '',
                    deptPenerima: '',
                    totalQty: 0,
                    uom: 'PCS',
                    operatorName: '',
                    availableOperators: [],
                    actionUrl: '',
                },
                processData: {
                    id: null,
                    wriNumber: '',
                    componentName: '',
                    partNumber: '',
                    uom: 'PCS',
                    qrUrl: '',
                    currentStatus: '',
                    nextStatus: '',
                    nextActionLabel: '',
                    totalQty: 0,
                    remainingQty: 0,
                    processQty: 0,
                    currentBatchQty: 0,
                    targetRack: '',
                    assignedOperator: '',
                    hasDefect: false,
                    defectQty: null,
                    defectReason: '',
                    notes: '',
                    actionUrl: '',
                    isPhysicalStep: false,
                    isScanVerified: true,
                    scannedCode: '',
                    tempScanInput: '',
                    scanErrorMessage: '',
                },
                detailData: {
                    id: null,
                    wriNumber: '',
                    componentName: '',
                    partNumber: '',
                    dimension: '',
                    uom: 'PCS',
                    imageUrl: '',
                    qrUrl: '',
                    deptPengaju: '',
                    deptPenerima: '',
                    priority: '',
                    priorityClass: '',
                    currentStatus: '',
                    statusClass: '',
                    targetRack: '',
                    totalQty: 0,
                    receivedQty: 0,
                    defectQty: 0,
                    progressPercentage: 0,
                    routingText: '',
                    requestedByName: '',
                    approvalPengajuStatus: '',
                    approvalPengajuBy: '',
                    approvalPengajuAt: '',
                    approvalPenerimaStatus: '',
                    approvalPenerimaBy: '',
                    approvalPenerimaAt: '',
                    assignedOperator: '',
                    steps: [],
                    logs: [],
                    printUrl: '#',
                    isPrintable: false,
                    printValidationMessage: '',
                    printCount: 0,
                    isOverdue: false,
                },
                openAssignModal(data) {
                    const cleanDept = (data.deptPenerima || '').toLowerCase().replace(/[^a-z0-9]/g, '');
                    let operators = [];
                    for (const [key, names] of Object.entries(this.departmentOperators || {})) {
                        if (cleanDept.includes(key) || key.includes(cleanDept)) {
                            names.forEach(n => {
                                if (!operators.includes(n)) operators.push(n);
                            });
                        }
                    }

                    // Filter out current user from available operators so no duplicate name appears (current user has "Saya Sendiri")
                    const otherOperators = operators.filter(n => n.toLowerCase().trim() !== (this.currentUserName || '').toLowerCase().trim());
                    const defaultOperator = data.assignedOperator || (otherOperators.length > 0 ? otherOperators[0] : this.currentUserName);

                    this.assignData = {
                        ...data,
                        operatorName: defaultOperator,
                        availableOperators: otherOperators,
                    };
                    this.assignModalOpen = true;
                },
                closeAssignModal() {
                    this.assignModalOpen = false;
                },
                openEditRoutingModal(data) {
                    this.editRoutingData = {
                        id: data.id,
                        wriNumber: data.wriNumber || '',
                        actionUrl: data.actionUrl || '',
                        currentRoutingText: data.currentRoutingText || '',
                    };
                    
                    let initialDepts = [];
                    if (data.steps && data.steps.length > 0) {
                        initialDepts = data.steps.map(s => s.department_name).filter(Boolean);
                    } else if (data.currentRoutingText) {
                        initialDepts = data.currentRoutingText.split('->').map(s => s.trim()).filter(Boolean);
                    }
                    if (initialDepts.length === 0) {
                        initialDepts = ['Dept Laser Cutting'];
                    }
                    
                    this.selectedDepts = [...initialDepts];
                    this.deptSearch = '';
                    this.isDeptDropdownOpen = false;
                    this.reasonText = '';
                    this.isSubmittingRouting = false;
                    this.editRoutingModalOpen = true;
                },
                closeEditRoutingModal() {
                    this.editRoutingModalOpen = false;
                    this.isDeptDropdownOpen = false;
                },
                filteredDepartments() {
                    const q = (this.deptSearch || '').toLowerCase().trim();
                    const depts = (this.allFabricationDepts && this.allFabricationDepts.length > 0) 
                        ? this.allFabricationDepts 
                        : [
                            'Dept Laser Cutting',
                            'Dept M1',
                            'Dept UNP',
                            'Dept Sharing',
                            'Dept Band Saw',
                            'Dept Aksesoris',
                            'Dept Press',
                            'Dept Hydraulic',
                            'Dept CNC',
                            'Dept Sharing & Bending',
                            'Dept SPO',
                            'Dept Robot',
                            'Dept Uji 1',
                            'Dept Uji 2'
                        ];
                    if (!q) return depts;
                    return depts.filter(d => d.toLowerCase().includes(q));
                },
                addDept(name) {
                    this.selectedDepts.push(name);
                    this.deptSearch = '';
                    this.isDeptDropdownOpen = false;
                },
                removeDept(index) {
                    if (index >= 0 && index < this.selectedDepts.length) {
                        this.selectedDepts.splice(index, 1);
                    }
                },
                clearAllDepts() {
                    this.selectedDepts = [];
                    this.deptSearch = '';
                },
                openProcessModal(data) {
                    const isHandover = data.nextStatus && (data.nextStatus.includes('MH Pickup') || data.nextStatus.includes('Menuju Pengaju') || data.nextStatus.includes('Sudah Diambil'));
                    const initialQty = (isHandover && data.currentBatchQty > 0) ? Number(data.currentBatchQty) : Number(data.remainingQty || 1);
                    
                    const nextStatus = data.nextStatus || '';
                    const isPhysicalStep = (
                        nextStatus.includes('(In)') || 
                        nextStatus.includes('(Out)') || 
                        nextStatus.includes('Pickup') || 
                        nextStatus.includes('Menuju Pengaju') || 
                        nextStatus.includes('Sudah Diambil')
                    );
                    const isScanVerified = !(this.isWrScanRequired && isPhysicalStep);

                    this.processData = {
                        ...data,
                        availableQty: initialQty,
                        processQty: initialQty,
                        hasDefect: false,
                        defectQty: null,
                        defectReason: '',
                        notes: '',
                        isPhysicalStep: isPhysicalStep,
                        isScanVerified: isScanVerified,
                        scannedCode: '',
                        tempScanInput: '',
                        scanErrorMessage: '',
                    };
                    this.processModalOpen = true;

                    if (this.isWrScanRequired && isPhysicalStep) {
                        this.attachGunScannerListener();
                    }
                },
                attachGunScannerListener() {
                    if (this.gunScanHandler) return;
                    this.gunScanBuffer = '';
                    this.gunScanStartTime = 0;
                    this.gunLastCharTime = 0;

                    this.gunScanHandler = (e) => {
                        if (!this.processModalOpen || this.processData.isScanVerified) return;

                        // Jangan halangi user jika sedang fokus mengisi catatan atau defect
                        if (e.target && ['notes', 'defect_reason', 'defect_qty'].includes(e.target.name)) {
                            return;
                        }

                        const now = Date.now();

                        if (e.key === 'Enter') {
                            if (this.gunScanBuffer.length > 0) {
                                e.preventDefault();
                                const totalTime = now - this.gunScanStartTime;
                                const charCount = this.gunScanBuffer.length;
                                const avgCharSpeed = totalTime / charCount;

                                // Alat scanner gun fisik menembakkan buffer sangat cepat (rata-rata < 60ms per karakter).
                                // Pengetikan manual oleh manusia rata-rata membutuhkan 120ms - 300ms per karakter.
                                if (charCount >= 3 && avgCharSpeed <= 60) {
                                    this.verifyScannedCode(this.gunScanBuffer);
                                } else {
                                    this.processData.scanErrorMessage = 'Pengetikan manual dilarang! Wajib menggunakan pemindai kamera atau alat scanner gun fisik.';
                                }
                                this.gunScanBuffer = '';
                                this.gunScanStartTime = 0;
                            }
                            return;
                        }

                        // Tangkap karakter printable standar
                        if (e.key && e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                            if (!this.gunScanStartTime || (now - this.gunLastCharTime > 150)) {
                                this.gunScanBuffer = '';
                                this.gunScanStartTime = now;
                            }
                            this.gunLastCharTime = now;
                            this.gunScanBuffer += e.key;
                        }
                    };

                    window.addEventListener('keydown', this.gunScanHandler);
                },
                detachGunScannerListener() {
                    if (this.gunScanHandler) {
                        window.removeEventListener('keydown', this.gunScanHandler);
                        this.gunScanHandler = null;
                    }
                    this.gunScanBuffer = '';
                    this.gunScanStartTime = 0;
                    this.gunLastCharTime = 0;
                },
                verifyScannedCode(rawCode) {
                    if (!rawCode || !rawCode.trim()) return;
                    const input = rawCode.trim();
                    const wriNum = (this.processData.wriNumber || '').trim();
                    const partNum = (this.processData.partNumber || '').trim();

                    const cleanScanned = input.replace(/HPK-PART\|/gi, '').replace(/[\s\-\/]/g, '').toLowerCase();
                    const cleanWri = wriNum.replace(/[\s\-\/]/g, '').toLowerCase();
                    const cleanPart = partNum.replace(/[\s\-\/]/g, '').toLowerCase();

                    let isValid = false;
                    if (cleanScanned && cleanWri && (cleanScanned === cleanWri || cleanScanned.includes(cleanWri) || cleanWri.includes(cleanScanned))) {
                        isValid = true;
                    } else if (cleanScanned && cleanPart && (cleanScanned === cleanPart || cleanScanned.includes(cleanPart))) {
                        isValid = true;
                    }

                    if (isValid) {
                        this.processData.scannedCode = input;
                        this.processData.isScanVerified = true;
                        this.processData.scanErrorMessage = '';
                        this.detachGunScannerListener();
                        if (this.processCameraModalOpen) {
                            this.closeProcessCameraModal();
                        }
                    } else {
                        this.processData.scanErrorMessage = `Kode "${input}" tidak cocok dengan tiket ${wriNum}!`;
                    }
                },
                resetScanVerification() {
                    this.processData.isScanVerified = false;
                    this.processData.scannedCode = '';
                    this.processData.scanErrorMessage = '';
                    this.attachGunScannerListener();
                },
                async openProcessCameraModal() {
                    this.processCameraModalOpen = true;
                    this.$nextTick(() => {
                        this.startProcessCameraScanner();
                    });
                },
                async closeProcessCameraModal() {
                    await this.stopProcessCameraScanner();
                    this.processCameraModalOpen = false;
                },
                async startProcessCameraScanner() {
                    const el = document.getElementById('process-camera-reader');
                    if (!el || typeof Html5Qrcode === 'undefined') {
                        console.warn('Html5Qrcode not available or container missing');
                        return;
                    }
                    if (this.html5ProcessQrCode) {
                        await this.stopProcessCameraScanner();
                    }
                    try {
                        this.html5ProcessQrCode = new Html5Qrcode('process-camera-reader');
                        const config = { fps: 10, qrbox: { width: 220, height: 220 } };
                        await this.html5ProcessQrCode.start(
                            { facingMode: 'environment' },
                            config,
                            (decodedText) => {
                                this.verifyScannedCode(decodedText);
                            },
                            () => {}
                        );
                    } catch (err) {
                        console.error('Failed to start camera scanner:', err);
                    }
                },
                async stopProcessCameraScanner() {
                    if (this.html5ProcessQrCode) {
                        const scanner = this.html5ProcessQrCode;
                        this.html5ProcessQrCode = null;
                        try {
                            const state = typeof scanner.getState === 'function' ? scanner.getState() : null;
                            if (state === 2 || state === 3) {
                                await scanner.stop();
                            }
                        } catch (e) {
                            console.warn('Silent catch stopping process camera:', e);
                        }
                    }
                },
                getMaxProcessQty() {
                    const avail = Number(this.processData.availableQty || (this.processData.currentBatchQty > 0 ? this.processData.currentBatchQty : this.processData.remainingQty) || 0);
                    const defect = (this.processData.hasDefect && this.processData.defectQty > 0) ? Number(this.processData.defectQty) : 0;
                    return Math.max(0, avail - defect);
                },
                syncProcessQtyWithDefect() {
                    const maxGood = this.getMaxProcessQty();
                    if (Number(this.processData.processQty) > maxGood || this.processData.processQty === null || this.processData.processQty === undefined) {
                        this.processData.processQty = maxGood;
                    }
                },
                incrementProcessQty(step = 1) {
                    const max = this.getMaxProcessQty();
                    let cur = Number(this.processData.processQty || 0);
                    if (cur + step <= max) {
                        this.processData.processQty = cur + step;
                    } else {
                        this.processData.processQty = max;
                    }
                },
                decrementProcessQty(step = 1) {
                    let cur = Number(this.processData.processQty || 0);
                    if (cur - step >= 0) {
                        this.processData.processQty = cur - step;
                    } else {
                        this.processData.processQty = 0;
                    }
                },
                setMaxProcessQty() {
                    this.processData.processQty = this.getMaxProcessQty();
                },
                toggleDefect() {
                    this.processData.hasDefect = !this.processData.hasDefect;
                    if (!this.processData.hasDefect) {
                        this.processData.defectQty = null;
                        this.processData.defectReason = '';
                    }
                    this.syncProcessQtyWithDefect();
                },
                async closeProcessModal() {
                    this.detachGunScannerListener();
                    if (this.processCameraModalOpen) {
                        await this.closeProcessCameraModal();
                    }
                    this.processModalOpen = false;
                },
                openDetailModal(data) {
                    this.detailData = { ...data };
                    this.detailModalOpen = true;
                },
                closeDetailModal() {
                    this.detailModalOpen = false;
                },
                openProcessFromDetail() {
                    const payload = this.detailData.processPayload || {
                        id: this.detailData.id,
                        wriNumber: this.detailData.wriNumber,
                        componentName: this.detailData.componentName,
                        partNumber: this.detailData.partNumber,
                        uom: this.detailData.uom,
                        currentStatus: this.detailData.currentStatus,
                        nextStatus: this.detailData.nextStatus,
                        nextActionLabel: this.detailData.nextActionLabel || 'Lanjutkan Pengerjaan',
                        totalQty: this.detailData.totalQty,
                        remainingQty: this.detailData.remainingQty,
                        currentBatchQty: this.detailData.currentBatchQty || 0,
                        targetRack: this.detailData.targetRack,
                        assignedOperator: (this.detailData.assignedOperator && this.detailData.assignedOperator !== 'Belum ditentukan') ? this.detailData.assignedOperator : '',
                        actionUrl: this.detailData.actionUrl,
                    };
                    this.closeDetailModal();
                    this.openProcessModal(payload);
                },
                openImageModal(data) {
                    this.imageData = { 
                        ...data, 
                        isPdf: Boolean(data.isPdf || (data.imageUrl && data.imageUrl.includes('.pdf'))),
                        zoomLevel: 1.0 
                    };
                    this.imageModalOpen = true;
                },
                closeImageModal() {
                    this.imageModalOpen = false;
                },
                openRejectModal(actionUrl, wriNumber) {
                    this.rejectData = {
                        actionUrl: actionUrl,
                        wriNumber: wriNumber,
                        reason: '',
                    };
                    this.rejectModalOpen = true;
                },
                closeRejectModal() {
                    this.rejectModalOpen = false;
                },
                openQrZoom(qrUrl, wriNumber, componentName) {
                    this.qrZoomData = {
                        qrUrl: qrUrl,
                        wriNumber: wriNumber,
                        componentName: componentName || '',
                    };
                    this.qrZoomModal = true;
                },
                closeQrZoom() {
                    this.qrZoomModal = false;
                },
                handlePengajuActionDirect(approveUrl) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = approveUrl;
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);
                    document.body.appendChild(form);
                    form.submit();
                },
                handlePengajuAction(event, approveUrl, rejectUrl, wriNumber) {
                    const select = event.target;
                    const val = select.value;
                    if (val === 'terima') {
                        select.disabled = true;
                        select.classList.add('opacity-50', 'cursor-wait');
                        const parent = select.parentElement;
                        if (parent) {
                            parent.insertAdjacentHTML('beforeend', '<span class="inline-flex items-center text-[10px] font-bold text-amber-700 ms-1 animate-pulse"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...</span>');
                        }
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = approveUrl;
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);
                        document.body.appendChild(form);
                        form.submit();
                    } else if (val === 'tolak') {
                        select.value = '';
                        this.openRejectModal(rejectUrl, wriNumber);
                    }
                },
                handlePenerimaAction(event, assignPayload, rejectUrl, wriNumber) {
                    const select = event.target;
                    const val = select.value;
                    select.value = '';
                    if (val === 'terima') {
                        this.openAssignModal(assignPayload);
                    } else if (val === 'tolak') {
                        this.openRejectModal(rejectUrl, wriNumber);
                    }
                },
                getStepLogs(stepName, stepIndex) {
                    if (!this.detailData || !this.detailData.logs) return [];
                    const cleanName = (stepName || '').replace(/^Dept\s+/i, '').trim().toLowerCase();
                    const sLower = (stepName || '').toLowerCase();
                    const isLastStep = Boolean(this.detailData.steps && stepIndex === this.detailData.steps.length - 1);
                    return this.detailData.logs.filter(l => {
                        // Abaikan log approval / reject agar stasiun mesin dimulai bersih dari event MASUK
                        if (l.action_type === 'approval' || l.action_type === 'reject') return false;
                        const status = (l.status || '').toLowerCase();
                        const notes = (l.notes || '').toLowerCase();
                        if (status.includes('otorisasi') || notes.includes('otorisasi disetujui')) return false;

                        if (status.includes(sLower) || status.includes(cleanName) || notes.includes(sLower) || notes.includes(cleanName)) {
                            return true;
                        }
                        if (isLastStep && status.includes('siap diambil') && !status.includes('pickup')) {
                            return true;
                        }
                        return false;
                    });
                },
                formatDefectNotes(notes) {
                    if (!notes || notes === '-') return 'Cacat / reject fisik';
                    // Bersihkan pola kalimat legacy jika ada: "Reject/Defect pada tahap ...: {alasan}. Kuota total tiket dipotong..."
                    const match = notes.match(/:\s*(.+?)\.\s*Kuota\s+(?:total|order)\s+tiket\s+dipotong/i);
                    if (match && match[1]) {
                        return match[1].trim();
                    }
                    return notes;
                },
                getGudangLogs() {
                    if (!this.detailData || !this.detailData.logs) return [];
                    return this.detailData.logs.filter(l => {
                        const status = (l.status || '').toLowerCase();
                        const notes = (l.notes || '').toLowerCase();
                        return status.includes('sudah diambil') || status.includes('menuju pengaju') || notes.includes('sudah diambil') || notes.includes('menuju pengaju');
                    }).map(l => {
                        const status = (l.status || '').toLowerCase();
                        const notes = (l.notes || '').toLowerCase();
                        const isMh = status.includes('menuju pengaju') || notes.includes('menuju pengaju') || status.includes('pickup') || notes.includes('pickup');
                        const isDefect = Boolean(l.is_defect || l.action_type === 'defect' || status.includes('defect') || notes.includes('reject/defect'));
                        return {
                            ...l,
                            is_mh: isMh,
                            is_gudang: !isMh,
                            is_defect: isDefect,
                        };
                    });
                }
            };
        }
    </script>
</x-app-layout>

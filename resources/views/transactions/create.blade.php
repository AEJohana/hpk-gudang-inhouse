<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('transactions.index') }}" 
               class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-medium transition flex items-center space-x-1.5 shadow-2xs cursor-pointer"
               title="Kembali ke Riwayat Transaksi">
                <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                <span>Kembali</span>
            </a>
            <div>
                <h2 class="font-bold text-base sm:text-lg text-slate-900 leading-tight">
                    Transaksi Komponen Gudang
                </h2>
                <p class="text-[11px] text-slate-500 mt-0.5">Form mutasi barang masuk, barang keluar, dan transfer rak</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-xl mx-auto px-3 sm:px-4 py-4 sm:py-6" x-data="transactionForm()">
        <form method="POST" action="{{ route('transactions.store') }}" @submit="validateSubmit($event)" class="space-y-4">
            @csrf

            @if ($components->isEmpty())
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-start space-x-3 text-xs text-slate-700">
                    <i class="fa-solid fa-circle-info text-slate-500 text-sm mt-0.5"></i>
                    <div class="flex-1">
                        <p class="font-medium text-slate-900">Belum Ada Master Komponen</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Silakan daftarkan master komponen terlebih dahulu sebelum melakukan transaksi.
                        </p>
                        <div class="mt-2">
                            <a href="{{ route('components.create') }}" class="inline-flex items-center px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-[11px] font-medium transition">
                                Tambah Komponen
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- UNIFIED FORMAL CARD CONTAINER -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-4 sm:p-5 space-y-3.5">
                
                <!-- 1. BAR JENIS TRANSAKSI (3 CLEAN TABS - TANPA DROPDOWN GANDA) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-medium text-slate-700">Jenis Transaksi</label>
                        <span class="text-[11px] text-slate-400">Petugas: {{ auth()->user()->name }}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-1 p-1 bg-slate-100 rounded-xl">
                        <button type="button" 
                                @click="setTxType('inbound')" 
                                :class="txType === 'inbound' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-800 font-medium'"
                                class="py-2 px-2 text-xs rounded-lg transition text-center cursor-pointer">
                            Barang Masuk
                            <span class="sr-only">Inbound (Barang Masuk / Penerimaan)</span>
                        </button>
                        <button type="button" 
                                @click="setTxType('outbound')" 
                                :class="txType === 'outbound' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-800 font-medium'"
                                class="py-2 px-2 text-xs rounded-lg transition text-center cursor-pointer">
                            Barang Keluar
                            <span class="sr-only">Outbound (Barang Keluar / Perakitan)</span>
                        </button>
                        <button type="button" 
                                @click="setTxType('transfer')" 
                                :class="txType === 'transfer' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-800 font-medium'"
                                class="py-2 px-2 text-xs rounded-lg transition text-center cursor-pointer">
                            Transfer Rak
                            <span class="sr-only">Transfer Antar Rak / Zona</span>
                        </button>
                    </div>

                    <!-- Hidden Input for Form Submission -->
                    <input type="hidden" name="type" :value="txType">
                </div>

                <!-- 2. PEMINDAI KAMERA (CLEAN BUTTON) -->
                <div class="space-y-2">
                    <button type="button" 
                            @click="toggleCameraScanner()" 
                            class="w-full py-2 px-3 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-xl border border-slate-300 shadow-2xs transition flex items-center justify-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-camera text-slate-500 text-xs"></i>
                        <span x-text="scannerActive ? 'Tutup Pemindai Kamera' : 'Buka Pemindai QR / Barcode'"></span>
                    </button>

                    <!-- Camera Viewport Box -->
                    <div x-show="scannerActive" style="display: none;" class="p-3 bg-slate-950 rounded-xl border border-slate-800 transition-all">
                        <div class="max-w-[200px] mx-auto aspect-square relative flex items-center justify-center overflow-hidden rounded-lg bg-black">
                            <div id="tx-camera-reader" class="w-full"></div>
                            <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 h-0.5 bg-amber-400 shadow-xs pointer-events-none"></div>
                        </div>
                        <p class="text-center text-[11px] text-slate-400 mt-2">Arahkan kamera ke label QR / Barcode part</p>
                    </div>
                </div>

                <!-- 3. PRATINJAU FOTO KOMPONEN -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Pratinjau Komponen</label>
                    <div class="w-full min-h-[120px] rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col items-center justify-center p-3 overflow-hidden text-center transition">
                        <template x-if="selectedComp && selectedComp.image_url">
                            <div class="flex flex-col items-center space-y-1">
                                <img :src="selectedComp.image_url" :alt="selectedComp.name" class="h-24 max-w-full object-contain rounded-lg border border-slate-200 bg-white shadow-2xs">
                                <span class="text-[10px] font-mono text-slate-600 bg-slate-200/70 px-2 py-0.5 rounded" x-text="selectedComp.part_number"></span>
                            </div>
                        </template>
                        <template x-if="!selectedComp || !selectedComp.image_url">
                            <div class="text-slate-400 text-xs py-4 flex flex-col items-center space-y-1">
                                <i class="fa-regular fa-image text-xl text-slate-300"></i>
                                <span class="text-[11px]">Pratinjau fisik part akan muncul saat dipilih</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 4. COMBOBOX: SEARCHABLE AUTOCOMPLETE DROPDOWN DALAM 1 KOTAK -->
                <div class="relative" @click.away="dropdownOpen = false">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Kode / Nama Komponen</label>
                    
                    <div class="relative">
                        <input type="text" 
                               x-model="searchQuery" 
                               @focus="dropdownOpen = true" 
                               @input="onInputSearch()" 
                               @keydown.enter.prevent="onEnterKey()"
                               placeholder="Ketik kode part atau klik untuk pilih..." 
                               class="w-full pl-3 pr-10 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-slate-900 focus:outline-none font-mono text-slate-800 bg-white">
                        
                        <!-- Toggle / Clear Button -->
                        <div class="absolute right-2.5 top-2.5 flex items-center space-x-1">
                            <button type="button" 
                                    x-show="searchQuery" 
                                    @click="clearSelection()" 
                                    class="text-slate-400 hover:text-slate-600 p-0.5" 
                                    title="Bersihkan">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                            <button type="button" 
                                    @click="dropdownOpen = !dropdownOpen" 
                                    class="text-slate-400 hover:text-slate-600 p-0.5">
                                <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': dropdownOpen }"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Floating Searchable Dropdown List -->
                    <div x-show="dropdownOpen" 
                         style="display: none;" 
                         class="absolute z-30 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-lg divide-y divide-slate-100">
                        <template x-for="comp in filteredComponents" :key="comp.id">
                            <div @click="selectComponent(comp)" 
                                 class="p-2.5 hover:bg-slate-50 cursor-pointer flex items-center justify-between text-xs transition">
                                <div class="min-w-0 pr-2">
                                    <span class="font-mono font-semibold text-slate-900 block" x-text="comp.part_number"></span>
                                    <span class="text-slate-600 text-[11px] truncate block" x-text="comp.name"></span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[11px] font-semibold text-slate-700 block" x-text="comp.total_stock + ' ' + comp.uom"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="filteredComponents.length === 0" class="p-3 text-center text-xs text-slate-400">
                            Tidak ada komponen yang cocok
                        </div>
                    </div>

                    <div x-show="errorMessage" style="display: none;" class="mt-1 p-2 bg-rose-50 border border-rose-200 text-rose-700 text-[11px] rounded-lg" x-text="errorMessage"></div>
                </div>

                <!-- 5. NAMA BARANG & SALDO GUDANG (READ-ONLY) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nama Komponen</label>
                        <div class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 min-h-[38px] flex items-center">
                            <span x-text="selectedComp ? selectedComp.name : '-'"
                                  :class="selectedComp ? 'font-medium text-slate-900' : 'text-slate-400 italic text-[11px]'"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Stok Saat Ini</label>
                        <div class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 min-h-[38px] flex items-center justify-between">
                            <span x-text="selectedComp ? (selectedComp.total_stock + ' ' + (selectedComp.uom || 'Pcs')) : '-'"
                                  :class="selectedComp ? 'font-semibold text-slate-900' : 'text-slate-400 italic text-[11px]'"></span>
                            <template x-if="selectedComp">
                                <span class="text-[10px] px-2 py-0.5 rounded"
                                      :class="selectedComp.is_low_stock ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                      x-text="selectedComp.is_low_stock ? 'Perlu Restock' : 'Aman'"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 6. RAK PENYIMPANAN (ADAPTIF) -->
                <div>
                    <template x-if="txType === 'outbound' || txType === 'transfer'">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Ambil dari Rak</label>
                            <select x-model="fromLocId" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-slate-900 text-slate-800 font-normal">
                                <option value="">-- Tidak Ada Rak Terdaftar --</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->full_location_code }} ({{ $loc->zone_name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </template>

                    <template x-if="txType === 'inbound' || txType === 'transfer' || txType === 'return'">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Simpan ke Rak</label>
                            <select x-model="toLocId" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-slate-900 text-slate-800 font-normal">
                                <option value="">-- Pilih Rak Tujuan --</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->full_location_code }} ({{ $loc->zone_name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </template>
                </div>

                <!-- 7. JUMLAH BARANG (QTY) - BILANGAN BULAT MURNI (INTEGER) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-medium text-slate-700">Jumlah Barang (Qty)</label>
                        <span class="text-[11px] text-slate-500" x-text="selectedComp ? ('Satuan: ' + selectedComp.uom) : 'Satuan: Pcs'"></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="button" 
                                @click="if (itemQty > 1) itemQty--" 
                                class="w-10 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm flex items-center justify-center border border-slate-200 transition active:scale-95 cursor-pointer">
                            -
                        </button>
                        <input type="number" 
                               step="1" 
                               min="1" 
                               x-model.number="itemQty" 
                               @input="itemQty = (itemQty ? Math.max(1, Math.floor(itemQty)) : 1)"
                               inputmode="numeric" 
                               class="w-full px-3 py-2 text-sm font-semibold text-center border border-slate-300 rounded-xl focus:ring-1 focus:ring-slate-900 focus:outline-none text-slate-900">
                        <button type="button" 
                                @click="itemQty++" 
                                class="w-10 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm flex items-center justify-center border border-slate-200 transition active:scale-95 cursor-pointer">
                            +
                        </button>
                    </div>
                </div>

                <!-- 8. TANGGAL REALTIME & CATATAN OPSIONAL (COLLAPSIBLE) -->
                <div x-data="{ expanded: false }" class="pt-1 border-t border-slate-100">
                    <button type="button" @click="expanded = !expanded" class="text-[11px] font-medium text-slate-600 hover:text-slate-900 flex items-center space-x-1 cursor-pointer">
                        <i class="fa-solid fa-chevron-right text-[8px] transition-transform" :class="{ 'rotate-90': expanded }"></i>
                        <span>Tanggal & Catatan Tambahan</span>
                    </button>
                    <div x-show="expanded" style="display: none;" class="mt-2 space-y-2 pt-1">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[11px] font-medium text-slate-600">Tanggal Transaksi</label>
                                <span class="text-[10px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded">Real-time</span>
                            </div>
                            <input type="date" 
                                   name="transaction_date" 
                                   value="{{ date('Y-m-d') }}" 
                                   readonly 
                                   class="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-slate-100 text-slate-700 font-normal cursor-not-allowed select-none focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Catatan Transaksi</label>
                            <input type="text" 
                                   name="notes" 
                                   placeholder="Keterangan transaksi (opsional)..." 
                                   class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:outline-none text-slate-800">
                        </div>
                    </div>
                </div>

                <!-- 9. MULTI-ITEM STAGING LIST (MINIMALIST) -->
                <div class="pt-1 flex items-center justify-between border-t border-slate-100">
                    <button type="button" 
                            @click="addItemToTable()" 
                            :disabled="!selectedComp" 
                            class="text-xs font-medium text-slate-600 hover:text-slate-900 flex items-center space-x-1.5 py-1 px-1.5 rounded-lg hover:bg-slate-100 transition disabled:opacity-40 cursor-pointer">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Tambah ke Daftar Multi-Item</span>
                    </button>
                    <span class="text-[10px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded" 
                          x-show="items.length > 0" 
                          x-text="items.length + ' Part Siap'"></span>
                </div>

                <!-- Staged List Items -->
                <div x-show="items.length > 0" class="space-y-1.5 pt-1">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-slate-900 text-xs" x-text="item.part_number"></span>
                                <span class="text-slate-600 truncate max-w-[150px] text-[11px]" x-text="item.name"></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold text-slate-800 text-xs" x-text="item.quantity + ' ' + item.uom"></span>
                                <button type="button" @click="removeItem(idx)" class="text-slate-400 hover:text-rose-600 p-1 cursor-pointer">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- HIDDEN INPUTS FOR FORM SUBMISSION -->
                <!-- Scenario A: Single Item Instant Mode -->
                <template x-if="items.length === 0 && selectedComp">
                    <div>
                        <input type="hidden" name="items[0][component_id]" :value="selectedComp.id">
                        <input type="hidden" name="items[0][quantity]" :value="itemQty">
                        <input type="hidden" name="items[0][from_location_id]" :value="fromLocId || ''">
                        <input type="hidden" name="items[0][to_location_id]" :value="toLocId || ''">
                    </div>
                </template>

                <!-- Scenario B: Multi-Items Mode -->
                <template x-for="(item, idx) in items" :key="idx">
                    <div>
                        <input type="hidden" :name="'items[' + idx + '][component_id]'" :value="item.component_id">
                        <input type="hidden" :name="'items[' + idx + '][quantity]'" :value="item.quantity">
                        <input type="hidden" :name="'items[' + idx + '][from_location_id]'" :value="item.from_location_id || ''">
                        <input type="hidden" :name="'items[' + idx + '][to_location_id]'" :value="item.to_location_id || ''">
                    </div>
                </template>

                <!-- 10. TOMBOL KONFIRMASI TRANSAKSI (FORMAL NAVY HPK) -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full py-2.5 px-4 bg-[#0a2342] hover:bg-slate-800 active:bg-slate-950 text-white font-medium text-xs rounded-xl shadow-2xs transition flex items-center justify-center space-x-1.5 cursor-pointer">
                        <span x-text="items.length > 0 ? ('Proses Transaksi (' + items.length + ' Item)') : 'Konfirmasi Transaksi'"></span>
                    </button>
                </div>

            </div>
        </form>
    </div>

    <!-- Script for Transaction Flow and HTML5 QR Scanner -->
    <script>
        function transactionForm() {
            return {
                txType: '{{ $defaultType }}',
                searchQuery: '',
                selectedComp: null,
                itemQty: 1,
                fromLocId: '',
                toLocId: '',
                items: [],
                errorMessage: null,
                scannerActive: false,
                html5QrCode: null,
                dropdownOpen: false,

                allComponents: {!! json_encode($components->map(function($c) {
                    return [
                        'id' => $c->id,
                        'part_number' => $c->part_number,
                        'name' => $c->name,
                        'uom' => $c->uom,
                        'total_stock' => $c->total_stock,
                        'default_location_id' => $c->default_location_id,
                        'image_url' => $c->image_url,
                        'is_low_stock' => $c->is_low_stock,
                    ];
                })) !!},

                get filteredComponents() {
                    if (!this.searchQuery || !this.searchQuery.trim()) {
                        return this.allComponents.slice(0, 40);
                    }
                    const q = this.searchQuery.toLowerCase().trim();
                    return this.allComponents.filter(c => 
                        (c.part_number && c.part_number.toLowerCase().includes(q)) || 
                        (c.name && c.name.toLowerCase().includes(q))
                    ).slice(0, 40);
                },

                init() {
                    const presetId = '{{ request("component_id") }}';
                    if (presetId) {
                        this.lookupById(presetId);
                    }
                },

                setTxType(type) {
                    this.txType = type;
                },

                selectComponent(comp) {
                    this.selectedComp = comp;
                    this.searchQuery = comp.part_number + ' - ' + comp.name;
                    this.itemQty = 1;
                    this.fromLocId = comp.default_location_id ? String(comp.default_location_id) : '';
                    this.toLocId = comp.default_location_id ? String(comp.default_location_id) : '';
                    this.dropdownOpen = false;
                    this.errorMessage = null;
                },

                clearSelection() {
                    this.selectedComp = null;
                    this.searchQuery = '';
                    this.dropdownOpen = false;
                },

                onInputSearch() {
                    this.dropdownOpen = true;
                    // If user clears input, reset selected component
                    if (!this.searchQuery.trim()) {
                        this.selectedComp = null;
                    }
                },

                onEnterKey() {
                    if (!this.searchQuery || !this.searchQuery.trim()) return;
                    const q = this.searchQuery.toLowerCase().trim();
                    
                    // 1. Try exact part_number match
                    const exact = this.allComponents.find(c => c.part_number && c.part_number.toLowerCase() === q);
                    if (exact) {
                        this.selectComponent(exact);
                        return;
                    }

                    // 2. Try first match in filtered list
                    if (this.filteredComponents.length > 0) {
                        this.selectComponent(this.filteredComponents[0]);
                        return;
                    }

                    // 3. Fallback to API lookup
                    this.searchComponent();
                },

                toggleCameraScanner() {
                    this.scannerActive = !this.scannerActive;
                    if (this.scannerActive) {
                        this.$nextTick(() => {
                            this.startCamera();
                        });
                    } else {
                        this.stopCamera();
                    }
                },

                startCamera() {
                    if (typeof Html5Qrcode === 'undefined') return;
                    this.html5QrCode = new Html5Qrcode('tx-camera-reader');
                    this.html5QrCode.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 180, height: 180 } },
                        (text) => {
                            this.searchQuery = text;
                            this.onEnterKey();
                            this.stopCamera();
                            this.scannerActive = false;
                        },
                        (err) => {}
                    ).catch(e => {
                        console.warn('Tx camera start error:', e);
                    });
                },

                stopCamera() {
                    if (this.html5QrCode) {
                        this.html5QrCode.stop().then(() => this.html5QrCode.clear()).catch(e => {});
                    }
                },

                searchComponent() {
                    if (!this.searchQuery || !this.searchQuery.trim()) return;
                    this.errorMessage = null;

                    fetch(`/api/components/search?q=${encodeURIComponent(this.searchQuery.trim())}`)
                        .then(res => res.json())
                        .then(res => {
                            if (res.success && res.data) {
                                this.selectComponent(res.data);
                            } else {
                                this.selectedComp = null;
                                this.errorMessage = `Komponen "${this.searchQuery}" tidak ditemukan.`;
                            }
                        })
                        .catch(err => {
                            this.errorMessage = 'Terjadi kesalahan saat memeriksa data komponen.';
                        });
                },

                lookupById(id) {
                    const found = this.allComponents.find(c => String(c.id) === String(id));
                    if (found) {
                        this.selectComponent(found);
                        return;
                    }
                    fetch(`/api/components/search?q=${encodeURIComponent(id)}`)
                        .then(res => res.json())
                        .then(res => {
                            if (res.success && res.data) {
                                this.selectComponent(res.data);
                            }
                        });
                },

                addItemToTable() {
                    if (!this.selectedComp) return;
                    if (!this.itemQty || this.itemQty <= 0) {
                        alert('Jumlah quantity harus lebih besar dari 0');
                        return;
                    }

                    const fromLocElem = document.querySelector('select[x-model="fromLocId"]');
                    const toLocElem = document.querySelector('select[x-model="toLocId"]');

                    const fromLocText = (this.fromLocId && fromLocElem && fromLocElem.selectedIndex > 0)
                        ? fromLocElem.options[fromLocElem.selectedIndex]?.text
                        : (this.fromLocId ? null : '- (Tanpa Rak)');

                    const toLocText = (this.toLocId && toLocElem && toLocElem.selectedIndex > 0)
                        ? toLocElem.options[toLocElem.selectedIndex]?.text
                        : (this.toLocId ? null : '- (Tanpa Rak)');

                    this.items.push({
                        component_id: this.selectedComp.id,
                        part_number: this.selectedComp.part_number,
                        name: this.selectedComp.name,
                        uom: this.selectedComp.uom,
                        image_url: this.selectedComp.image_url,
                        quantity: Math.floor(this.itemQty),
                        from_location_id: this.fromLocId || null,
                        from_location_name: fromLocText,
                        to_location_id: this.toLocId || null,
                        to_location_name: toLocText,
                    });

                    // Reset staging box for next item
                    this.clearSelection();
                    this.itemQty = 1;
                },

                removeItem(idx) {
                    this.items.splice(idx, 1);
                },

                validateSubmit(e) {
                    if (this.items.length === 0 && !this.selectedComp) {
                        e.preventDefault();
                        alert('Silakan scan atau pilih komponen terlebih dahulu!');
                        return false;
                    }
                    if (this.items.length === 0 && this.selectedComp) {
                        if (!this.itemQty || this.itemQty <= 0) {
                            e.preventDefault();
                            alert('Jumlah quantity harus minimal 1');
                            return false;
                        }
                    }
                    return true;
                }
            };
        }
    </script>
</x-app-layout>

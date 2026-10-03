<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('transactions.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                    Input Transaksi Komponen Gudang
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Scan QR/Barcode atau ketik manual kode part dengan validasi visual foto komponen</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="transactionForm()">
        <form method="POST" action="{{ route('transactions.store') }}" @submit="validateSubmit($event)" class="space-y-6">
            @csrf

            <!-- Section 1: Header Information -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
                    <span>1. Informasi Umum Transaksi</span>
                    <span class="text-xs font-normal text-slate-400">Petugas: <strong>{{ auth()->user()->name }}</strong></span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <!-- Transaction Type -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Jenis Transaksi <span class="text-rose-500">*</span>
                        </label>
                        <select name="type" x-model="txType" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold">
                            <option value="outbound">Pengeluaran (Outbound ke Perakitan)</option>
                            <option value="inbound">Penerimaan (Inbound dari Supplier/PO)</option>
                            <option value="transfer">Transfer Antar Rak / Zona</option>
                            <option value="return">Retur Material dari Produksi</option>
                        </select>
                    </div>

                    <!-- SPK Number (Optional for now as requested) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            No. SPK Karoseri <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                        </label>
                        <input type="text" 
                               name="spk_number" 
                               placeholder="Contoh: SPK-2026-DT-042" 
                               class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                    </div>

                    <!-- Reference Document -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            No. Surat Jalan / PO
                        </label>
                        <input type="text" 
                               name="reference_document" 
                               placeholder="Contoh: PO-9912 / SJ-004" 
                               class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <!-- Transaction Date -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Tanggal Transaksi <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="transaction_date" 
                               value="{{ date('Y-m-d') }}" 
                               required 
                               class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Catatan Transaksi</label>
                    <input type="text" 
                           name="notes" 
                           placeholder="Keterangan perakitan unit karoseri atau penerimaan material..." 
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Section 2: Scanner & Component Photo Visual Verification Box -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">2. Identifikasi Komponen & Verifikasi Visual</h3>
                        <p class="text-xs text-slate-500">Scan QR Code label atau ketik manual. Foto fisik part akan muncul otomatis untuk dicocokkan!</p>
                    </div>
                    <!-- Camera Scanner Launcher Button -->
                    <button type="button" 
                            @click="toggleCameraScanner()" 
                            class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-sm transition flex items-center border border-slate-700">
                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span x-text="scannerActive ? 'Tutup Scanner' : 'Scan via Kamera'"></span>
                    </button>
                </div>

                <!-- Live Camera Viewport (Expandable) -->
                <div x-show="scannerActive" style="display: none;" class="p-4 bg-slate-950 rounded-xl border border-slate-800">
                    <div class="max-w-xs mx-auto aspect-square relative flex items-center justify-center overflow-hidden rounded-lg bg-black">
                        <div id="tx-camera-reader" class="w-full"></div>
                        <div class="absolute inset-x-6 top-1/2 -translate-y-1/2 h-1 bg-amber-500 shadow-[0_0_8px_#f59e0b] animate-pulse pointer-events-none"></div>
                    </div>
                    <p class="text-center text-xs text-slate-400 mt-2">Arahkan kamera ke label QR stiker komponen atau rak</p>
                </div>

                <!-- Input Selector Bar (Scan Barcode Gun / Autocomplete / Dropdown) -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-6">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Ketik / Scan Barcode Part Number:
                        </label>
                        <div class="flex space-x-2">
                            <input type="text" 
                                   x-model="searchQuery" 
                                   @keydown.enter.prevent="searchComponent()"
                                   placeholder="Contoh: HYD-CYL-160 atau scan barcode..."
                                   class="flex-1 px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                            <button type="button" 
                                    @click="searchComponent()" 
                                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs rounded-xl transition">
                                Cari Part
                            </button>
                        </div>
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Atau Pilih dari Daftar Komponen:
                        </label>
                        <select @change="onDropdownSelect($event.target.value)" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="">-- Cari Nama Komponen --</option>
                            @foreach ($components as $comp)
                                <option value="{{ $comp->part_number }}">{{ $comp->part_number }} - {{ $comp->name }} (Stok: {{ $comp->total_stock }} {{ $comp->uom }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- VISUAL COMPONENT PREVIEW CARD -->
                <template x-if="selectedComp">
                    <div class="p-4 bg-blue-50/70 border border-blue-200 rounded-2xl flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-5 transition-all">
                        <!-- High Resolution Component Photo -->
                        <div class="w-28 h-28 rounded-xl overflow-hidden bg-white border border-blue-300 shadow-md flex-shrink-0 flex items-center justify-center">
                            <img :src="selectedComp.image_url" :alt="selectedComp.name" class="w-full h-full object-cover">
                        </div>

                        <!-- Component Verification Details -->
                        <div class="flex-1 space-y-1.5 text-xs text-slate-700">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono font-bold text-xs bg-blue-900 text-white px-2 py-0.5 rounded" x-text="selectedComp.part_number"></span>
                                <span class="px-2 py-0.5 rounded font-semibold bg-blue-200 text-blue-900 text-[10px]" x-text="selectedComp.category_label"></span>
                                <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">
                                    Stok Gudang: <strong x-text="selectedComp.total_stock"></strong> <span x-text="selectedComp.uom"></span>
                                </span>
                            </div>
                            <h4 class="text-base font-bold text-slate-900" x-text="selectedComp.name"></h4>
                            <p class="text-slate-600 line-clamp-2" x-text="selectedComp.specification || 'Tidak ada spesifikasi khusus.'"></p>
                            <p class="text-slate-500 text-[11px]">
                                <strong>Lokasi Default:</strong> <span class="font-mono text-blue-900 font-semibold" x-text="selectedComp.default_location"></span>
                            </p>
                        </div>

                        <!-- Staging Input Controls -->
                        <div class="w-full sm:w-64 bg-white p-3.5 rounded-xl border border-blue-200 shadow-sm space-y-2.5">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase">Jumlah (Qty):</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <input type="number" 
                                           step="0.01" 
                                           x-model="itemQty" 
                                           min="0.01" 
                                           class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 font-bold text-slate-900">
                                    <span class="text-xs font-semibold text-slate-500" x-text="selectedComp.uom"></span>
                                </div>
                            </div>

                            <!-- Location Selectors based on Type -->
                            <template x-if="txType === 'outbound' || txType === 'transfer'">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-700 uppercase">Ambil dari Rak:</label>
                                    <select x-model="fromLocId" class="w-full px-2 py-1 text-xs border border-slate-300 rounded-lg mt-1">
                                        @foreach ($locations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->full_location_code }} ({{ $loc->zone_name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </template>

                            <template x-if="txType === 'inbound' || txType === 'transfer' || txType === 'return'">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-700 uppercase">Simpan ke Rak:</label>
                                    <select x-model="toLocId" class="w-full px-2 py-1 text-xs border border-slate-300 rounded-lg mt-1">
                                        @foreach ($locations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->full_location_code }} ({{ $loc->zone_name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </template>

                            <button type="button" 
                                    @click="addItemToTable()" 
                                    class="w-full py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg shadow-sm transition">
                                + Masukkan ke Daftar
                            </button>
                        </div>
                    </div>
                </template>

                <div x-show="errorMessage" style="display: none;" class="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl" x-text="errorMessage"></div>
            </div>

            <!-- Section 3: Staged Line Items Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">3. Rincian Komponen Siap Diproses</h3>
                        <p class="text-[11px] text-slate-400">Pastikan seluruh fisik komponen telah dicocokkan sebelum menyimpan transaksi</p>
                    </div>
                    <span class="text-xs font-bold text-blue-700 bg-blue-100 px-2.5 py-0.5 rounded-full" x-text="items.length + ' Item Terdaftar'"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Foto & Part Number</th>
                                <th class="py-3 px-4">Nama Komponen</th>
                                <th class="py-3 px-4">Rak Asal &rarr; Rak Tujuan</th>
                                <th class="py-3 px-4 text-center">Jumlah (Qty)</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, idx) in items" :key="idx">
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center space-x-3">
                                            <img :src="item.image_url" class="w-10 h-10 rounded-lg object-cover border border-slate-200 bg-white">
                                            <span class="font-mono font-bold text-slate-900" x-text="item.part_number"></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900" x-text="item.name"></td>
                                    <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                        <span x-text="item.from_location_name || 'Gudang Luar'"></span> &rarr; 
                                        <span x-text="item.to_location_name || 'Lini Perakitan'"></span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-slate-900 text-sm">
                                        <span x-text="item.quantity"></span> <span class="text-xs font-normal" x-text="item.uom"></span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <button type="button" @click="removeItem(idx)" class="text-rose-600 hover:text-rose-800 font-semibold text-xs">
                                            Hapus
                                        </button>
                                        <!-- Hidden Inputs for Form Submission -->
                                        <input type="hidden" :name="'items[' + idx + '][component_id]'" :value="item.component_id">
                                        <input type="hidden" :name="'items[' + idx + '][quantity]'" :value="item.quantity">
                                        <input type="hidden" :name="'items[' + idx + '][from_location_id]'" :value="item.from_location_id">
                                        <input type="hidden" :name="'items[' + idx + '][to_location_id]'" :value="item.to_location_id">
                                    </td>
                                </tr>
                            </template>
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400">
                                        Belum ada komponen di dalam daftar. Scan barcode atau cari komponen di atas untuk menambahkan.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end space-x-3 pt-3">
                <a href="{{ route('transactions.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="items.length === 0"
                        :class="items.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-300' : 'bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold shadow-md'"
                        class="px-6 py-2.5 text-xs rounded-xl transition">
                    Proses Transaksi & Perbarui Stok Fisik
                </button>
            </div>
        </form>
    </div>

    <!-- Script for Transaction Flow and HTML5 QR Scanner -->
    <script>
        function transactionForm() {
            return {
                txType: '{{ $defaultType }}',
                searchQuery: '{{ request("component_id") ? "" : "" }}',
                selectedComp: null,
                itemQty: 1,
                fromLocId: '',
                toLocId: '',
                items: [],
                errorMessage: null,
                scannerActive: false,
                html5QrCode: null,

                init() {
                    const presetId = '{{ request("component_id") }}';
                    if (presetId) {
                        this.lookupById(presetId);
                    }
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
                        { fps: 10, qrbox: { width: 200, height: 200 } },
                        (text) => {
                            this.searchQuery = text;
                            this.searchComponent();
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
                    if (!this.searchQuery.trim()) return;
                    this.errorMessage = null;

                    fetch(`/api/components/search?q=${encodeURIComponent(this.searchQuery.trim())}`)
                        .then(res => res.json())
                        .then(res => {
                            if (res.success && res.data) {
                                this.selectedComp = res.data;
                                this.itemQty = 1;
                                this.fromLocId = res.data.default_location_id || '';
                                this.toLocId = res.data.default_location_id || '';
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
                    fetch(`/api/components/search?q=${encodeURIComponent(id)}`)
                        .then(res => res.json())
                        .then(res => {
                            if (res.success && res.data) {
                                this.selectedComp = res.data;
                                this.fromLocId = res.data.default_location_id || '';
                                this.toLocId = res.data.default_location_id || '';
                            }
                        });
                },

                onDropdownSelect(partNumber) {
                    if (!partNumber) return;
                    this.searchQuery = partNumber;
                    this.searchComponent();
                },

                addItemToTable() {
                    if (!this.selectedComp) return;
                    if (!this.itemQty || this.itemQty <= 0) {
                        alert('Jumlah quantity harus lebih besar dari 0');
                        return;
                    }

                    // Get location label texts
                    const fromLocElem = document.querySelector('select[x-model="fromLocId"]');
                    const toLocElem = document.querySelector('select[x-model="toLocId"]');

                    this.items.push({
                        component_id: this.selectedComp.id,
                        part_number: this.selectedComp.part_number,
                        name: this.selectedComp.name,
                        uom: this.selectedComp.uom,
                        image_url: this.selectedComp.image_url,
                        quantity: this.itemQty,
                        from_location_id: this.fromLocId || null,
                        from_location_name: fromLocElem ? fromLocElem.options[fromLocElem.selectedIndex]?.text : null,
                        to_location_id: this.toLocId || null,
                        to_location_name: toLocElem ? toLocElem.options[toLocElem.selectedIndex]?.text : null,
                    });

                    // Reset staging box
                    this.selectedComp = null;
                    this.searchQuery = '';
                },

                removeItem(idx) {
                    this.items.splice(idx, 1);
                },

                validateSubmit(e) {
                    if (this.items.length === 0) {
                        e.preventDefault();
                        alert('Silakan tambahkan minimal 1 item komponen ke dalam daftar transaksi.');
                    }
                }
            };
        }
    </script>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-1 sm:gap-1.5">
            <div>
                <a href="{{ route('qr-requests.index') }}" 
                   class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer shrink-0" 
                   title="Kembali ke Permintaan Label">
                    <i class="fa-solid fa-arrow-left text-[11px] text-slate-500"></i>
                    <span>Kembali</span>
                </a>
            </div>
            <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight leading-tight">
                Buat Permintaan Label QR Baru
            </h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8" 
         x-data="qrRequestCreateForm(@js($componentsJson), '{{ $selectedComponentId }}')">

        <!-- 1. FORM INPUT PEMILIHAN KOMPONEN & JENIS LABEL -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            
            <div class="border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-full bg-cyan-100 text-cyan-800 font-black text-xs flex items-center justify-center">1</span>
                    <h3 class="font-extrabold text-base text-slate-900">Pilih Komponen & Tentukan Format Label</h3>
                </div>
                <p class="text-xs text-slate-500 mt-1">Pilih part material dan atur format label sebelum dimasukkan ke dalam keranjang pengajuan.</p>
            </div>

            <!-- PILIH KOMPONEN -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                        Pilih Komponen <span class="text-rose-500">*</span>
                    </label>
                    <span x-show="selectedComponent" 
                          x-cloak 
                          class="text-[11px] font-bold text-cyan-700 flex items-center space-x-1">
                        <i class="fa-solid fa-circle-check text-[10px] text-cyan-600"></i>
                        <span>Komponen Terpilih</span>
                    </span>
                </div>

                <select name="component_id" 
                        id="componentSelect"
                        x-model="selectedId"
                        class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:outline-none font-semibold">
                    <option value="">-- Cari & Pilih Komponen --</option>
                    @foreach ($components as $comp)
                        <option value="{{ $comp->id }}">
                            {{ $comp->part_number }} - {{ $comp->name }} (Lokasi: {{ $comp->defaultLocation ? $comp->defaultLocation->full_location_code : 'Belum diatur' }})
                        </option>
                    @endforeach
                </select>

                <!-- SLIDING COMPONENT PREVIEW PANEL -->
                <div x-show="selectedComponent" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="pt-2">
                    
                    <div class="p-4 sm:p-5 bg-gradient-to-br from-slate-50 via-white to-cyan-50/40 rounded-2xl border border-cyan-200/90 shadow-xs">
                        
                        <div class="flex flex-col sm:flex-row gap-4 items-center sm:items-start">
                            <!-- FOTO KOMPONEN / THUMBNAIL (UKURAN KOMPAK TERKUNCI) -->
                            <div class="w-32 h-32 sm:w-36 sm:h-36 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200/90 flex items-center justify-center shrink-0 relative group shadow-xs">
                                <template x-if="selectedComponent && selectedComponent.is_pdf">
                                    <div class="flex flex-col items-center justify-center p-3 text-center w-full h-full bg-rose-50/60">
                                        <i class="fa-solid fa-file-pdf text-3xl text-rose-500 mb-1"></i>
                                        <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">Gambar CAD</span>
                                        <a :href="selectedComponent ? selectedComponent.image_url : '#'" 
                                           target="_blank" 
                                           class="mt-1.5 px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold rounded-lg shadow-xs transition inline-flex items-center space-x-1">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                            <span>Buka PDF</span>
                                        </a>
                                    </div>
                                </template>

                                <template x-if="selectedComponent && !selectedComponent.is_pdf">
                                    <div class="w-full h-full relative">
                                        <img :src="selectedComponent ? selectedComponent.image_url : '{{ asset('images/placeholder_part.svg') }}'" 
                                             :alt="selectedComponent ? selectedComponent.name : 'Komponen'" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                             x-on:error="$el.src = '{{ asset('images/placeholder_part.svg') }}'">
                                        
                                        <a :href="selectedComponent ? selectedComponent.image_url : '#'" 
                                           target="_blank" 
                                           x-show="selectedComponent && selectedComponent.image_url && !selectedComponent.image_url.includes('placeholder')" 
                                           class="absolute bottom-1.5 right-1.5 px-2 py-1 bg-slate-900/80 hover:bg-slate-900 text-white rounded-lg text-[10px] font-bold opacity-0 group-hover:opacity-100 transition shadow-xs flex items-center space-x-1 backdrop-blur-xs" 
                                           title="Buka Foto Ukuran Penuh">
                                            <i class="fa-solid fa-up-right-from-square text-[9px]"></i>
                                            <span>Perbesar</span>
                                        </a>
                                    </div>
                                </template>
                            </div>

                            <!-- IDENTITAS LENGKAP PART -->
                            <div class="flex-1 min-w-0 space-y-2.5 w-full">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-black text-cyan-900 bg-cyan-100/90 px-2.5 py-0.5 rounded-lg border border-cyan-200 shadow-2xs" 
                                              x-text="selectedComponent ? selectedComponent.part_number : '-'"></span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700" 
                                              x-text="selectedComponent ? selectedComponent.category : 'Umum'"></span>
                                    </div>
                                    <h4 class="font-black text-base text-slate-900 mt-1" 
                                        x-text="selectedComponent ? selectedComponent.name : '-'"></h4>
                                    <p class="text-xs text-slate-500 line-clamp-2 mt-0.5" 
                                       x-text="selectedComponent && selectedComponent.specs ? selectedComponent.specs : 'Komponen standar fabrikasi karoseri PT Hydraxle Perkasa.'"></p>
                                </div>

                                <!-- GRID RINCIAN FISIK & GUDANG -->
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2 border-t border-slate-200/70 text-xs">
                                    <div class="p-2.5 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Lokasi Rak Simpan</span>
                                        <span class="font-mono font-bold text-cyan-800 block mt-0.5 truncate" 
                                              x-text="selectedComponent ? selectedComponent.location : 'Belum diatur'"></span>
                                    </div>
                                    <div class="p-2.5 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Dimensi (P x L x T)</span>
                                        <span class="font-semibold text-slate-700 block mt-0.5 truncate" 
                                              x-text="selectedComponent ? selectedComponent.dimension : '-'"></span>
                                    </div>
                                    <div class="col-span-2 sm:col-span-1 p-2.5 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Stok Gudang Terkini</span>
                                        <span class="font-black text-slate-900 block mt-0.5">
                                            <span x-text="selectedComponent ? selectedComponent.total_stock : 0"></span>
                                            <span class="text-[10px] font-semibold text-slate-500" x-text="selectedComponent ? selectedComponent.uom : 'Pcs'"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- FORMAT / JENIS LABEL (2 PILIHAN PILL SESUAI MOCKUP) -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                        Format / Jenis Label <span class="text-rose-500">*</span>
                    </label>
                    <span class="text-[11px] text-slate-400">Bisa pilih salah satu atau keduanya</span>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Pill 1: Label QR Rak -->
                    <label class="inline-flex items-center px-4 py-2 rounded-full border text-xs font-bold cursor-pointer transition select-none shadow-2xs"
                           :class="selectedTypeRack ? 'bg-emerald-100 border-emerald-400 text-emerald-900 ring-2 ring-emerald-300/50' : 'bg-emerald-50/60 border-emerald-200/90 text-emerald-700 hover:bg-emerald-100/60'">
                        <input type="checkbox" x-model="selectedTypeRack" class="rounded text-emerald-600 focus:ring-emerald-500 me-2.5 h-4 w-4">
                        <i class="fa-solid fa-layer-group text-emerald-600 me-1.5 text-[11px]"></i>
                        <span>Label QR Rak</span>
                    </label>

                    <!-- Pill 2: Label QR Kartu -->
                    <label class="inline-flex items-center px-4 py-2 rounded-full border text-xs font-bold cursor-pointer transition select-none shadow-2xs"
                           :class="selectedTypeCard ? 'bg-cyan-100 border-cyan-400 text-cyan-900 ring-2 ring-cyan-300/50' : 'bg-cyan-50/60 border-cyan-200/90 text-cyan-700 hover:bg-cyan-100/60'">
                        <input type="checkbox" x-model="selectedTypeCard" class="rounded text-cyan-600 focus:ring-cyan-500 me-2.5 h-4 w-4">
                        <i class="fa-solid fa-id-card text-cyan-600 me-1.5 text-[11px]"></i>
                        <span>Label QR Kartu</span>
                    </label>
                </div>

                <div x-show="!selectedTypeRack && !selectedTypeCard" class="text-rose-600 text-xs font-semibold flex items-center space-x-1 mt-1">
                    <i class="fa-solid fa-circle-exclamation text-xs"></i>
                    <span>Silakan centang minimal salah satu format label (Label Rak atau Label Kartu).</span>
                </div>
            </div>

            <!-- PENGATURAN KUANTITAS SESUAI JENIS LABEL -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <!-- Qty Rak: 1 input set = 10 label fisik (1 HVS = 20 label) -->
                <div x-show="selectedTypeRack" 
                     x-transition 
                     class="p-3.5 bg-emerald-50/50 rounded-2xl border border-emerald-200/70 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-emerald-900 uppercase tracking-wider">
                            Qty Set Rak (@10 Label | 1 HVS = 20 Label)
                        </span>
                        <span class="text-[10px] font-extrabold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md">Rak Fisik</span>
                    </div>
                    <div class="flex items-center space-x-2.5 pt-1">
                        <input type="number" 
                               min="1" 
                               max="50" 
                               x-model.number="qtyRack" 
                               class="w-24 px-3 py-1.5 text-xs font-black border border-emerald-300 rounded-xl text-emerald-900 focus:ring-2 focus:ring-emerald-500 text-center">
                        <span class="text-xs text-emerald-800 font-bold" x-text="'= ' + ((qtyRack || 0) * 10) + ' stiker label rak'"></span>
                    </div>
                    <div class="flex items-center gap-1.5 pt-0.5 text-[10px]">
                        <button type="button" @click="qtyRack = 1" class="px-2 py-0.5 bg-white border border-emerald-300 rounded-md text-emerald-800 hover:bg-emerald-100 font-bold cursor-pointer">1 Set (10 Label / 1/2 HVS)</button>
                        <button type="button" @click="qtyRack = 2" class="px-2 py-0.5 bg-white border border-emerald-300 rounded-md text-emerald-800 hover:bg-emerald-100 font-bold cursor-pointer">2 Set (20 Label / 1 HVS)</button>
                    </div>
                </div>

                <!-- Qty Kartu: 1 input = 1 label stiker kartu part -->
                <div x-show="selectedTypeCard" 
                     x-transition 
                     class="p-3.5 bg-cyan-50/50 rounded-2xl border border-cyan-200/70 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-cyan-900 uppercase tracking-wider">
                            Qty Label Kartu (1 Input = 1 Label)
                        </span>
                        <span class="text-[10px] font-extrabold text-cyan-700 bg-cyan-100/80 px-2 py-0.5 rounded-md">Stiker Part</span>
                    </div>
                    <div class="flex items-center space-x-2.5 pt-1">
                        <input type="number" 
                               min="1" 
                               max="500" 
                               x-model.number="qtyCard" 
                               class="w-24 px-3 py-1.5 text-xs font-black border border-cyan-300 rounded-xl text-cyan-900 focus:ring-2 focus:ring-cyan-500 text-center">
                        <span class="text-xs text-cyan-800 font-bold" x-text="'= ' + (qtyCard || 0) + ' stiker label kartu'"></span>
                    </div>
                    <div class="flex items-center gap-1.5 pt-0.5 text-[10px]">
                        <button type="button" @click="qtyCard = 2" class="px-2 py-0.5 bg-white border border-cyan-300 rounded-md text-cyan-800 hover:bg-cyan-100 font-bold cursor-pointer">2 pcs (1 baris)</button>
                        <button type="button" @click="qtyCard = 10" class="px-2 py-0.5 bg-white border border-cyan-300 rounded-md text-cyan-800 hover:bg-cyan-100 font-bold cursor-pointer">10 pcs (1/2 lembar)</button>
                        <button type="button" @click="qtyCard = 20" class="px-2 py-0.5 bg-white border border-cyan-300 rounded-md text-cyan-800 hover:bg-cyan-100 font-bold cursor-pointer">20 pcs (1 lembar)</button>
                    </div>
                </div>
            </div>

            <!-- CATATAN KEPERLUAN CETAK -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Catatan Keperluan Cetak (Opsional)
                </label>
                <input type="text" 
                       x-model="itemNotes"
                       placeholder="Contoh: Label stiker pengganti barcode yang rusak di lapangan..." 
                       class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>

            <!-- TOMBOL AKSI: TAMBAH KE KERANJANG -->
            <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-400 w-full sm:w-auto text-center sm:text-left">
                    <span x-show="showToast" 
                          x-transition 
                          class="inline-flex items-center text-emerald-800 font-bold text-xs bg-emerald-100/90 px-3.5 py-1.5 rounded-xl border border-emerald-300 shadow-2xs">
                        <i class="fa-solid fa-circle-check text-emerald-600 me-1.5"></i>
                        <span>Berhasil dimasukkan ke keranjang pengajuan!</span>
                    </span>
                </div>

                <button type="button" 
                        @click="addToCart()" 
                        :disabled="!selectedComponent || (!selectedTypeRack && !selectedTypeCard)"
                        class="w-full sm:w-auto px-6 py-3 bg-cyan-700 hover:bg-cyan-800 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-black rounded-xl shadow-md transition flex items-center justify-center space-x-2.5 cursor-pointer border border-cyan-800/30"
                        :style="(!selectedComponent || (!selectedTypeRack && !selectedTypeCard)) ? 'background-color: #cbd5e1; color: #64748b;' : 'background-color: #0e7490; color: #ffffff;'">
                    <i class="fa-solid fa-cart-plus text-amber-300 text-sm"></i>
                    <span class="font-black text-xs text-white" :style="(!selectedComponent || (!selectedTypeRack && !selectedTypeCard)) ? 'color: #64748b;' : 'color: #ffffff;'">+ Masukkan ke Keranjang Pengajuan</span>
                </button>
            </div>
        </div>

        <!-- 2. DAFTAR KERANJANG PERMINTAAN CETAK (MULTI-ITEM CART) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-black text-sm">
                        <i class="fa-solid fa-basket-shopping"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Keranjang Permintaan Cetak Label</h3>
                        <p class="text-xs text-slate-500">Daftar label yang akan diajukan sekaligus ke antrean cetak.</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-cyan-50 text-cyan-800 border border-cyan-200">
                        <span x-text="cartItems.length"></span> Item Pengajuan
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <span x-text="totalPrintSheets"></span> Total Lembar
                    </span>
                </div>
            </div>

            <!-- EMPTY STATE: KERANJANG KOSONG -->
            <div x-show="cartItems.length === 0" class="py-12 px-4 text-center border-2 border-dashed border-slate-200 rounded-2xl space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-lg">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-700">Keranjang Masih Kosong</h4>
                    <p class="text-xs text-slate-400 mt-0.5 max-w-sm mx-auto">
                        Pilih komponen di form atas, tentukan jenis label (Rak / Kartu), lalu klik tombol <strong>"+ Masukkan ke Keranjang Pengajuan"</strong>.
                    </p>
                </div>
            </div>

            <!-- TABEL ITEM KERANJANG -->
            <div x-show="cartItems.length > 0" class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/70 text-slate-500 text-[10px] font-bold uppercase tracking-wider">
                            <th class="py-3 px-3 w-10 text-center">#</th>
                            <th class="py-3 px-3">Komponen</th>
                            <th class="py-3 px-3 whitespace-nowrap min-w-[140px]">Format / Jenis Label</th>
                            <th class="py-3 px-3 text-center whitespace-nowrap">Qty Cetak</th>
                            <th class="py-3 px-3">Catatan</th>
                            <th class="py-3 px-3 w-16 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, idx) in cartItems" :key="idx">
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-3 text-center font-bold text-slate-400" x-text="idx + 1"></td>
                                
                                <td class="py-3 px-3">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-9 h-9 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                                            <img :src="item.image_url" class="w-full h-full object-cover">
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-mono font-bold text-slate-900 block truncate" x-text="item.part_number"></span>
                                            <span class="text-slate-600 block text-[11px] truncate max-w-xs" x-text="item.name"></span>
                                            <span class="text-[10px] text-slate-400 block" x-text="'Rak: ' + item.location"></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold border whitespace-nowrap shadow-2xs" 
                                          :class="item.badge_class" 
                                          x-text="item.label_name"></span>
                                </td>

                                <td class="py-3 px-3 text-center">
                                    <span class="font-black text-slate-900 text-sm" x-text="item.print_qty"></span>
                                    <span class="text-[10px] text-slate-400 block">Lembar</span>
                                </td>

                                <td class="py-3 px-3">
                                    <span class="text-slate-600 line-clamp-1 italic text-[11px]" x-text="item.notes || '-'"></span>
                                </td>

                                <td class="py-3 px-3 text-center">
                                    <button type="button" 
                                            @click="removeFromCart(idx)" 
                                            class="w-7 h-7 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition flex items-center justify-center cursor-pointer mx-auto" 
                                            title="Hapus dari Keranjang">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- FORM SUBMIT MASSAL KERANJANG -->
            <div x-show="cartItems.length > 0" class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <button type="button" 
                        @click="if(confirm('Kosongkan semua daftar di keranjang?')) { cartItems = []; }" 
                        class="text-xs font-bold text-slate-500 hover:text-rose-600 transition flex items-center space-x-1 cursor-pointer">
                    <i class="fa-solid fa-rotate-left text-xs"></i>
                    <span>Kosongkan Keranjang</span>
                </button>

                <form method="POST" action="{{ route('qr-requests.store') }}" class="flex items-center space-x-3 w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="cart_data" :value="JSON.stringify(cartItems)">

                    <a href="{{ route('qr-requests.index') }}" class="w-full sm:w-auto text-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </a>

                    <button type="submit" 
                            class="w-full sm:w-auto px-6 py-2.5 bg-cyan-700 hover:bg-cyan-800 text-white text-xs font-black rounded-xl shadow-md transition flex items-center justify-center space-x-2 cursor-pointer"
                            style="background-color: #0e7490; color: #ffffff;">
                        <i class="fa-solid fa-paper-plane text-amber-300"></i>
                        <span class="text-white font-black text-xs" x-text="'Kirim Semua Permintaan (' + cartItems.length + ' Tiket)'"></span>
                    </button>
                </form>
            </div>

        </div>

    </div>

    <script>
        function qrRequestCreateForm(componentsList, initialSelectedId) {
            return {
                components: componentsList || [],
                selectedId: initialSelectedId ? String(initialSelectedId) : '',
                selectedTypeRack: false,
                selectedTypeCard: true, // Default aktif label kartu
                qtyRack: 1,
                qtyCard: 2, // Default 2 label stiker kartu (1 baris pada HVS)
                itemNotes: '',
                cartItems: [],
                showToast: false,

                get selectedComponent() {
                    if (!this.selectedId) return null;
                    return this.components.find(c => String(c.id) === String(this.selectedId)) || null;
                },

                get totalPrintSheets() {
                    return this.cartItems.reduce((sum, item) => sum + (parseInt(item.print_qty) || 0), 0);
                },

                addToCart() {
                    if (!this.selectedComponent) {
                        alert('Silakan pilih komponen terlebih dahulu.');
                        return;
                    }
                    if (!this.selectedTypeRack && !this.selectedTypeCard) {
                        alert('Silakan centang minimal salah satu format label (Label QR Rak atau Label QR Kartu).');
                        return;
                    }

                    const comp = this.selectedComponent;
                    const notes = this.itemNotes ? this.itemNotes.trim() : '';

                    // 1. Jika Label QR Rak dicentang: 1 input set = 10 label fisik (1 HVS = 20 label)
                    if (this.selectedTypeRack) {
                        const qtyR = Math.max(1, parseInt(this.qtyRack) || 1);
                        this.cartItems.push({
                            component_id: comp.id,
                            part_number: comp.part_number,
                            name: comp.name,
                            image_url: comp.image_url,
                            location: comp.location,
                            label_type: 'rack',
                            label_name: 'Label QR Rak',
                            print_qty: qtyR,
                            notes: notes,
                            badge_class: 'bg-emerald-50 text-emerald-800 border-emerald-200'
                        });
                    }

                    // 2. Jika Label QR Kartu dicentang: 1 input = 1 label stiker kartu part
                    if (this.selectedTypeCard) {
                        const qtyC = Math.max(1, parseInt(this.qtyCard) || 1);
                        this.cartItems.push({
                            component_id: comp.id,
                            part_number: comp.part_number,
                            name: comp.name,
                            image_url: comp.image_url,
                            location: comp.location,
                            label_type: 'card',
                            label_name: 'Label QR Kartu',
                            print_qty: qtyC,
                            notes: notes,
                            badge_class: 'bg-cyan-50 text-cyan-800 border border-cyan-200'
                        });
                    }

                    // Reset formulir input agar siap memilih komponen berikutnya
                    this.selectedId = '';
                    this.itemNotes = '';
                    this.qtyRack = 1;
                    this.qtyCard = 2;
                    
                    // Notifikasi visual kecil
                    this.showToast = true;
                    setTimeout(() => { this.showToast = false; }, 2500);
                },

                removeFromCart(idx) {
                    this.cartItems.splice(idx, 1);
                }
            };
        }
    </script>
</x-app-layout>

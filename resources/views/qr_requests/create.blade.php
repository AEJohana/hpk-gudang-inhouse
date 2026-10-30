<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('qr-requests.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                    Buat Permintaan Label QR Baru
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Ajukan pencetakan stiker QR Code untuk identifikasi fisik material di rak gudang</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6" 
         x-data="qrRequestCreateForm(@js($componentsJson), '{{ $selectedComponentId }}')">
        <form method="POST" action="{{ route('qr-requests.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf

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
                        required 
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
                     x-transition:enter="transition-all ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-3 max-h-0"
                     x-transition:enter-end="opacity-100 translate-y-0 max-h-[800px]"
                     x-transition:leave="transition-all ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 max-h-[800px]"
                     x-transition:leave-end="opacity-0 -translate-y-3 max-h-0"
                     class="overflow-hidden pt-2">
                    
                    <div class="p-4 sm:p-5 bg-gradient-to-br from-slate-50 via-white to-cyan-50/40 rounded-2xl border border-cyan-200/90 shadow-xs space-y-4">
                        
                        <div class="flex flex-col sm:flex-row gap-4 items-start">
                            <!-- FOTO KOMPONEN / THUMBNAIL -->
                            <div class="w-full sm:w-36 sm:h-36 rounded-xl overflow-hidden bg-slate-100 border border-slate-200/80 flex items-center justify-center flex-shrink-0 relative group shadow-2xs">
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
                                           class="absolute bottom-1.5 right-1.5 px-2 py-1 bg-slate-900/75 hover:bg-slate-900 text-white rounded-lg text-[10px] font-bold opacity-0 group-hover:opacity-100 transition shadow-xs flex items-center space-x-1" 
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
                                    <h4 class="font-black text-base text-slate-900 mt-1 truncate" 
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Format / Jenis Label <span class="text-rose-500">*</span>
                    </label>
                    <select name="label_type" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <option value="item">Label Satuan Part / Item (Stiker Tempel Part)</option>
                        <option value="box">Label Master Box / Koli Kemasan</option>
                        <option value="rack">Label Identitas Rak Penyimpanan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Jumlah Lembar / Qty Cetak <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           name="print_qty" 
                           value="5" 
                           min="1" 
                           max="500" 
                           inputmode="numeric"
                           required 
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:outline-none font-bold">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Catatan Keperluan Cetak (Opsional)
                </label>
                <input type="text" 
                       name="notes" 
                       placeholder="Contoh: Label stiker pengganti barcode yang rusak di lapangan..." 
                       class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:outline-none">
            </div>

            <div class="pt-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 sm:gap-3">
                <a href="{{ route('qr-requests.index') }}" class="w-full sm:w-auto text-center px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold rounded-xl shadow transition">
                    Simpan Permintaan Cetak
                </button>
            </div>
        </form>
    </div>

    <script>
        function qrRequestCreateForm(componentsList, initialSelectedId) {
            return {
                components: componentsList || [],
                selectedId: initialSelectedId ? String(initialSelectedId) : '',
                get selectedComponent() {
                    if (!this.selectedId) return null;
                    return this.components.find(c => String(c.id) === String(this.selectedId)) || null;
                }
            };
        }
    </script>
</x-app-layout>

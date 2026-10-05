<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('work-requests.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                    Order Komponen Baru ke Machine Center (Work Request Inhouse)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Pengajuan kebutuhan komponen in-house dari Gudang melewati tahapan potong laser, bending, hingga masuk stok</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="wriForm()">
        <form method="POST" action="{{ route('work-requests.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf

            @if ($components->isEmpty())
                <div class="p-4 bg-amber-50 border border-amber-300 rounded-2xl flex items-start space-x-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5"></i>
                    <div class="flex-1">
                        <h4 class="font-bold text-xs text-amber-900">Belum Ada Master Komponen di Gudang</h4>
                        <p class="text-xs text-amber-700 mt-0.5">
                            Untuk membuat Work Request Inhouse (pesanan produksi ke mesin center), silakan daftarkan master komponen terlebih dahulu.
                        </p>
                        <div class="mt-2.5">
                            <a href="{{ route('components.create') }}" class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-bold transition shadow-xs">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                <span>Tambah Komponen Sekarang</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Section 1: Informasi Komponen & Kebutuhan Gudang -->
            <div class="p-5 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
                    <i class="fa-solid fa-boxes-stacked text-amber-600 text-sm"></i>
                    <h3 class="font-bold text-sm text-slate-900">1. Komponen yang Dipesan dari Gudang</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Component Picker -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Pilih Komponen In-House <span class="text-rose-500">*</span>
                        </label>
                        <select name="component_id" 
                                required 
                                x-model="selectedComponentId" 
                                @change="updateComponentDetails()" 
                                class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold">
                            <option value="">-- Pilih Komponen --</option>
                            @foreach($components as $comp)
                                <option value="{{ $comp->id }}" 
                                        data-name="{{ $comp->name }}"
                                        data-part="{{ $comp->part_number }}"
                                        data-uom="{{ $comp->uom }}"
                                        data-specs="{{ $comp->specification }}"
                                        data-loc-id="{{ $comp->default_location_id }}"
                                        data-loc-code="{{ $comp->defaultLocation ? $comp->defaultLocation->clean_rack_code : 'R1' }}"
                                        data-zone-code="{{ $comp->defaultLocation ? $comp->defaultLocation->zone_code : '1' }}"
                                        data-shelf="{{ $comp->default_shelf_level ?: 'L3' }}"
                                        data-slot="{{ $comp->default_slot_number ?: '05' }}">
                                    {{ $comp->part_number }} &bull; {{ $comp->name }} ({{ $comp->uom }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Komponen hasil olahan pelat sasis, mounting, bracket, dan sambungan hidrolik</p>
                    </div>

                    <!-- Jumlah Pesanan & Satuan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Jumlah Permintaan <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center space-x-2">
                            <input type="number" 
                                   name="quantity_requested" 
                                   required 
                                   min="0.1" 
                                   step="any" 
                                   value="{{ old('quantity_requested', 20) }}"
                                   class="flex-1 px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-bold text-slate-900">
                            <span class="px-3 py-2 bg-slate-200 text-slate-700 font-bold text-xs rounded-xl" x-text="selectedUom || 'Pcs'"></span>
                        </div>
                    </div>
                </div>

                <!-- Preview Selected Component -->
                <div x-show="selectedComponentId" class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-400 text-[10px] uppercase font-bold block">Spesifikasi Komponen:</span>
                        <span class="font-bold text-slate-900" x-text="selectedSpecs || 'Spesifikasi standar pabrik karoseri HPK.'"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 text-[10px] uppercase font-bold block">Lokasi Simpan Standar:</span>
                        <span class="font-mono font-bold text-blue-900" x-text="computedTargetSlot"></span>
                    </div>
                </div>
            </div>

            <!-- Section 2: Target Lokasi Simpan Gudang Saat Selesai -->
            <div class="p-5 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
                    <i class="fa-solid fa-map-location-dot text-blue-600 text-sm"></i>
                    <h3 class="font-bold text-sm text-slate-900">2. Target Penyimpanan di Gudang (Setelah Selesai Mesin)</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Rak / Pallet Tujuan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Rak / Area Simpan <span class="text-rose-500">*</span>
                        </label>
                        <select name="target_location_id" required x-model="targetLocationId" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">
                                    {{ $loc->full_location_code }} ({{ $loc->display_rack_name }} &bull; {{ $loc->zone_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Lantai Rak -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Lantai Rak
                        </label>
                        <select name="target_shelf_level" x-model="targetShelfLevel" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                            <option value="L1">Lantai 1 (Bawah - Beban Berat)</option>
                            <option value="L2">Lantai 2 (Sedang)</option>
                            <option value="L3">Lantai 3 (Atas)</option>
                            <option value="L4">Lantai 4</option>
                        </select>
                    </div>

                    <!-- Nomor Slot Kompartemen -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Nomor Slot
                        </label>
                        <input type="text" 
                               name="target_slot_number" 
                               x-model="targetSlotNumber" 
                               placeholder="Contoh: 05" 
                               class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                    </div>
                </div>

                <p class="text-[11px] text-slate-500">
                    <i class="fa-solid fa-circle-info text-blue-500 me-1"></i>
                    Begitu barang diserahterimakan dari Machine Center, stok akan langsung masuk ke slot ini dan terhubung ke Peta Gudang.
                </p>
            </div>

            <!-- Section 3: Alur Routing Mesin (Multi-Machine Routing) -->
            <div class="p-5 bg-white rounded-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-gears text-purple-600 text-sm"></i>
                        <h3 class="font-bold text-sm text-slate-900">3. Alur Routing Mesin di Machine Center</h3>
                    </div>
                    <button type="button" @click="addStep()" class="px-2.5 py-1 text-xs bg-purple-50 text-purple-700 hover:bg-purple-100 font-bold rounded-lg border border-purple-200 transition">
                        + Tambah Tahapan Mesin
                    </button>
                </div>

                <p class="text-xs text-slate-500">
                    Secara standar, komponen fabrikasi karoseri seperti Mounting melalui proses <strong>Potong Laser (Laser Cutting)</strong> terlebih dahulu, kemudian dilanjutkan ke <strong>Mesin Bending</strong> sebelum dikirim ke gudang.
                </p>

                <!-- Steps List -->
                <div class="space-y-3">
                    <template x-for="(step, index) in steps" :key="index">
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center gap-3">
                            <!-- Step Number Badge -->
                            <div class="flex items-center space-x-2">
                                <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-bold text-xs flex items-center justify-center flex-shrink-0" x-text="index + 1"></span>
                                <span class="font-bold text-xs text-slate-700 whitespace-nowrap" x-text="'Tahap ' + (index + 1)"></span>
                            </div>

                            <!-- Machine Selector -->
                            <div class="flex-1">
                                <select :name="'steps[' + index + '][machine_id]'" 
                                        x-model="step.machine_id" 
                                        required 
                                        class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    @foreach($machines as $m)
                                        <option value="{{ $m->id }}">
                                            {{ $m->code }}: {{ $m->name }} ({{ $m->type_label }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Process Name -->
                            <div class="flex-1">
                                <input type="text" 
                                       :name="'steps[' + index + '][process_name]'" 
                                       x-model="step.process_name" 
                                       required 
                                       placeholder="Nama Proses (misal: Potong Plat Laser)" 
                                       class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold">
                            </div>

                            <!-- Remove Step Button -->
                            <button type="button" 
                                    @click="removeStep(index)" 
                                    x-show="steps.length > 1" 
                                    class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition self-end sm:self-center" 
                                    title="Hapus tahapan">
                                <i class="fa-solid fa-trash-can text-sm"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Section 4: Prioritas, SPK & Catatan Tambahan -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Tingkat Prioritas <span class="text-rose-500">*</span>
                    </label>
                    <select name="priority" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="normal" selected>Normal (Produksi Rutin)</option>
                        <option value="high">Tinggi (High Priority)</option>
                        <option value="urgent_line_stop">URGENT (Line Stop Perakitan)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Tenggat Waktu (Due Date)
                    </label>
                    <input type="date" 
                           name="due_date" 
                           value="{{ old('due_date', now()->addDays(2)->format('Y-m-d')) }}" 
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Referensi SPK / Unit Karoseri
                    </label>
                    <input type="text" 
                           name="spk_reference" 
                           placeholder="Contoh: SPK-DT-2026-HINO-500" 
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Instruksi Khusus / Catatan Fabrikasi
                </label>
                <textarea name="notes" 
                          rows="2" 
                          placeholder="Contoh: Menggunakan plat baja SS400 tebal 8mm, toleransi bending sudut 90 derajat ±0.5mm..." 
                          class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('work-requests.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black rounded-xl shadow-md transition flex items-center">
                    <i class="fa-solid fa-paper-plane me-1.5"></i>
                    Kirim Work Request ke Machine Center
                </button>
            </div>
        </form>
    </div>

    <script>
        function wriForm() {
            return {
                selectedComponentId: '',
                selectedUom: 'Pcs',
                selectedSpecs: '',
                targetLocationId: '{{ $locations->first()?->id }}',
                targetShelfLevel: 'L3',
                targetSlotNumber: '05',
                targetZoneCode: '1',
                targetRackCode: 'R3',

                steps: [
                    { machine_id: '{{ $machines->firstWhere("code", "MC-LC-01")?->id ?: $machines->first()?->id }}', process_name: 'Potong Plat Baja Sasis (Laser Cutting)' },
                    { machine_id: '{{ $machines->firstWhere("code", "MC-BND-01")?->id ?: $machines->skip(1)->first()?->id }}', process_name: 'Penekukan Flange & Sudut (Bending 250T)' }
                ],

                get computedTargetSlot() {
                    const shelf = this.targetShelfLevel || 'L1';
                    const slot = (this.targetSlotNumber || '01').padStart(2, '0');
                    return (this.targetZoneCode || '1') + '-' + (this.targetRackCode || 'R1') + '-' + shelf + '-' + slot;
                },

                updateComponentDetails() {
                    const select = document.querySelector('select[name="component_id"]');
                    const option = select.options[select.selectedIndex];
                    if (option && option.value) {
                        this.selectedUom = option.dataset.uom || 'Pcs';
                        this.selectedSpecs = option.dataset.specs || '';
                        if (option.dataset.locId) {
                            this.targetLocationId = option.dataset.locId;
                        }
                        this.targetShelfLevel = option.dataset.shelf || 'L3';
                        this.targetSlotNumber = option.dataset.slot || '05';
                        this.targetRackCode = option.dataset.locCode || 'R3';
                        this.targetZoneCode = option.dataset.zoneCode || '1';
                    }
                },

                addStep() {
                    this.steps.push({
                        machine_id: '{{ $machines->first()?->id }}',
                        process_name: 'Proses Pengerjaan Tambahan'
                    });
                },

                removeStep(index) {
                    if (this.steps.length > 1) {
                        this.steps.splice(index, 1);
                    }
                }
            };
        }
    </script>
</x-app-layout>

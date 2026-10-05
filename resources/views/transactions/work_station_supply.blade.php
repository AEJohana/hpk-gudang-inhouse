<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('transactions.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                    Supply Komponen ke Stasiun Kerja (Lini Perakitan Karoseri)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Pengeluaran komponen dari rak gudang untuk diserahterimakan ke lini perakitan Dump Truck, Tangki, Mixer, dll.</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="supplyForm()">
        @if($errors->has('stock'))
            <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-2xl flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-base"></i>
                <span>{{ $errors->first('stock') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('work-station-supplies.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf

            @if ($components->isEmpty())
                <div class="p-4 bg-amber-50 border border-amber-300 rounded-2xl flex items-start space-x-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5"></i>
                    <div class="flex-1">
                        <h4 class="font-bold text-xs text-amber-900">Belum Ada Komponen di Gudang</h4>
                        <p class="text-xs text-amber-700 mt-0.5">
                            Belum ada master komponen yang terdaftar untuk disuplai ke stasiun kerja. Silakan daftarkan komponen terlebih dahulu.
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

            <!-- Section 1: Tujuan Stasiun Kerja & SPK -->
            <div class="p-5 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
                    <i class="fa-solid fa-truck-ramp-box text-blue-600 text-sm"></i>
                    <h3 class="font-bold text-sm text-slate-900">1. Stasiun Kerja & Informasi Perakitan</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Work Station Picker -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Stasiun Kerja Tujuan <span class="text-rose-500">*</span>
                        </label>
                        <select name="work_station_id" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold">
                            <option value="">-- Pilih Stasiun Kerja --</option>
                            @foreach($workStations as $ws)
                                <option value="{{ $ws->id }}" {{ $selectedWorkStationId == $ws->id ? 'selected' : '' }}>
                                    {{ $ws->code }}: {{ $ws->name }} ({{ $ws->area_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- SPK Number -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Nomor SPK / Unit Karoseri <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="spk_number" 
                               required 
                               placeholder="Contoh: SPK-DT-2026-HINO-500" 
                               value="{{ old('spk_number') }}" 
                               class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Recipient Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Nama Penerima / Mandor Lini <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="recipient_name" 
                               required 
                               placeholder="Contoh: Supriyadi (Mandor Dump Truck)" 
                               value="{{ old('recipient_name') }}" 
                               class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Tanggal Pengeluaran <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="transaction_date" 
                               required 
                               value="{{ old('transaction_date', now()->format('Y-m-d')) }}" 
                               class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Section 2: Komponen yang Disuplai dari Rak Gudang -->
            <div class="p-5 bg-white rounded-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-boxes-stacked text-amber-600 text-sm"></i>
                        <h3 class="font-bold text-sm text-slate-900">2. Komponen yang Dikeluarkan dari Rak Gudang</h3>
                    </div>
                    <button type="button" @click="addItem()" class="px-2.5 py-1 text-xs bg-amber-50 text-amber-800 hover:bg-amber-100 font-bold rounded-lg border border-amber-200 transition">
                        + Tambah Komponen
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- Component Selector -->
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">
                                        Pilih Komponen <span class="text-rose-500">*</span>
                                    </label>
                                    <select :name="'items[' + index + '][component_id]'" 
                                            x-model="item.component_id" 
                                            @change="onComponentChange(index)" 
                                            required 
                                            class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold">
                                        <option value="">-- Pilih Part --</option>
                                        @foreach($components as $c)
                                            <option value="{{ $c->id }}" 
                                                    data-uom="{{ $c->uom }}" 
                                                    data-loc-id="{{ $c->stockBalances->first()?->location_id ?: $c->default_location_id }}"
                                                    data-avail-qty="{{ $c->total_stock }}">
                                                {{ $c->part_number }} &bull; {{ $c->name }} (Tersedia: {{ number_format($c->total_stock, 0) }} {{ $c->uom }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Quantity -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">
                                        Jumlah Supply <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="number" 
                                           :name="'items[' + index + '][quantity]'" 
                                           x-model="item.quantity" 
                                           required 
                                           step="any" 
                                           min="0.1" 
                                           placeholder="Qty" 
                                           class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-bold">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                                <!-- Origin Rack / Location -->
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">
                                        Ambil dari Rak / Area Gudang <span class="text-rose-500">*</span>
                                    </label>
                                    <select :name="'items[' + index + '][from_location_id]'" 
                                            x-model="item.from_location_id" 
                                            required 
                                            class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                        @foreach($locations as $l)
                                            <option value="{{ $l->id }}">
                                                {{ $l->full_location_code }} ({{ $l->display_rack_name }} &bull; {{ $l->zone_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Remove Button & Notes -->
                                <div class="flex items-center justify-between sm:justify-end space-x-2 pt-4 sm:pt-0">
                                    <input type="text" 
                                           :name="'items[' + index + '][notes]'" 
                                           placeholder="Catatan batch/unit..." 
                                           class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <button type="button" 
                                            @click="removeItem(index)" 
                                            x-show="items.length > 1" 
                                            class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition" 
                                            title="Hapus baris">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Catatan Pengeluaran / Instruksi Perakitan
                </label>
                <textarea name="notes" 
                          rows="2" 
                          placeholder="Catatan tambahan untuk lini perakitan..." 
                          class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('transactions.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl shadow-md transition flex items-center space-x-2">
                    <i class="fa-solid fa-truck-ramp-box text-sm"></i>
                    <span>Keluarkan & Supply ke Stasiun Kerja</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        function supplyForm() {
            return {
                items: [
                    {
                        component_id: '{{ $selectedComponentId ?: ($components->first()?->id ?? "") }}',
                        from_location_id: '{{ $locations->first()?->id ?? "" }}',
                        quantity: 4
                    }
                ],

                addItem() {
                    this.items.push({
                        component_id: '{{ $components->first()?->id ?? "" }}',
                        from_location_id: '{{ $locations->first()?->id ?? "" }}',
                        quantity: 1
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },

                onComponentChange(index) {
                    const select = document.querySelector('select[name="items[' + index + '][component_id]"]');
                    const option = select?.options[select.selectedIndex];
                    if (option && option.dataset.locId) {
                        this.items[index].from_location_id = option.dataset.locId;
                    }
                }
            };
        }
    </script>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('disposals.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                    Pengajuan Disposal Material / Scrap Besi
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Form permohonan pemusnahan barang cacat atau penjualan scrap potongan pelat baja karoseri</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="POST" action="{{ route('disposals.store') }}" enctype="multipart/form-data" 
              class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf

            <!-- Section 1: General Info -->
            <div class="space-y-4">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">1. Informasi Pengajuan Disposal</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Kategori Material Disposal <span class="text-rose-500">*</span>
                        </label>
                        <select name="disposal_type" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
                            <option value="scrap_iron">Scrap Besi / Potongan Pelat Baja</option>
                            <option value="damaged_part">Komponen Rusak Fisik / Cacat Fabrikasi</option>
                            <option value="expired_chemical">Chemical / Cat / Thinner Kadaluarsa</option>
                            <option value="obsolete">Komponen Obsolete / Hasil ECR</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Estimasi Berat Timbangan (Kg)
                        </label>
                        <input type="number" 
                               step="0.01" 
                               name="estimated_weight_kg" 
                               placeholder="Contoh: 450.5" 
                               class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Taksiran Nilai Sisa (Rp)
                        </label>
                        <input type="number" 
                               name="estimated_salvage_value" 
                               placeholder="Contoh: 2500000" 
                               class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Alasan Pengajuan Disposal / Scrap <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="reason" 
                              rows="2" 
                              required 
                              placeholder="Jelaskan kondisi kerusakan atau alasan potongan material tidak dapat dimanfaatkan lagi di SPK karoseri lain..." 
                              class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
                </div>
            </div>

            <!-- Section 2: Component Items Staged for Disposal -->
            <div class="space-y-4 pt-3 border-t border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">2. Rincian Item Komponen yang Di-Afkir</h3>

                <div class="p-4 bg-rose-50/50 border border-rose-200 rounded-xl space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Komponen yang Di-Disposal:</label>
                            <select name="items[0][component_id]" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg">
                                <option value="">-- Pilih Komponen --</option>
                                @foreach ($components as $comp)
                                    <option value="{{ $comp->id }}" {{ ($selectedComponentId == $comp->id) ? 'selected' : '' }}>
                                        {{ $comp->part_number }} - {{ $comp->name }} (Stok: {{ $comp->total_stock }} {{ $comp->uom }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Diambil dari Lokasi/Rak:</label>
                            <select name="items[0][from_location_id]" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg">
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->full_location_code }} ({{ $loc->zone_name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Jumlah Di-Disposal (Qty):</label>
                            <input type="number" 
                                   step="0.01" 
                                   name="items[0][quantity]" 
                                   value="1" 
                                   min="0.01" 
                                   required 
                                   class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg font-bold">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Keterangan Kondisi Fisik:</label>
                            <input type="text" 
                                   name="items[0][condition_description]" 
                                   placeholder="Contoh: Tabung silinder bocor parah, drat rusak, karat parah..." 
                                   class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Foto Bukti Fisik Kerusakan / Timbangan Scrap:</label>
                        <input type="file" 
                               name="items[0][photo]" 
                               accept="image/*" 
                               class="block w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('disposals.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow transition">
                    Kirim Pengajuan Disposal
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

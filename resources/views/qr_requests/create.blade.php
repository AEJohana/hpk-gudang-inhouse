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

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="POST" action="{{ route('qr-requests.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Pilih Komponen <span class="text-rose-500">*</span>
                </label>
                <select name="component_id" required class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:outline-none font-semibold">
                    <option value="">-- Cari & Pilih Komponen --</option>
                    @foreach ($components as $comp)
                        <option value="{{ $comp->id }}" {{ ($selectedComponentId == $comp->id) ? 'selected' : '' }}>
                            {{ $comp->part_number }} - {{ $comp->name }} (Lokasi: {{ $comp->defaultLocation ? $comp->defaultLocation->full_location_code : 'Belum diatur' }})
                        </option>
                    @endforeach
                </select>
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

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('qr-requests.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold rounded-xl shadow transition">
                    Simpan Permintaan Cetak
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

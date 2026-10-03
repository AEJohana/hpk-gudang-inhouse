<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('cycle-counts.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                    Jadwalkan Cycle Count (Stok Opname)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Pilih zona gudang HPK yang akan dilakukan pengecekan fisik berkala</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="POST" action="{{ route('cycle-counts.store') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Zona Sasaran Stok Opname <span class="text-rose-500">*</span>
                </label>
                <select name="zone_target" required class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold">
                    <option value="">-- Pilih Zona Gudang 1 Lantai HPK --</option>
                    @foreach ($zones as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Sistem akan otomatis memuat seluruh komponen dan rak yang berada di zona ini ke dalam lembar hitung.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Tanggal Pelaksanaan Opname <span class="text-rose-500">*</span>
                </label>
                <input type="date" 
                       name="count_date" 
                       value="{{ date('Y-m-d') }}" 
                       required 
                       class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-medium">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Catatan Jadwal Opname (Opsional)
                </label>
                <textarea name="notes" 
                          rows="2" 
                          placeholder="Contoh: Stok opname rutin mingguan area hidrolik hoist dump..." 
                          class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('cycle-counts.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold rounded-xl shadow transition">
                    Buat Lembar Hitung Opname
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

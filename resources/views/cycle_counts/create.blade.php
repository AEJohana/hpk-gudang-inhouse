<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-1 sm:gap-1.5">
            <div>
                <a href="{{ route('cycle-counts.index') }}" 
                   class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer shrink-0" 
                   title="Kembali ke Daftar Cycle Count">
                    <i class="fa-solid fa-arrow-left text-[11px] text-slate-500"></i>
                    <span>Kembali</span>
                </a>
            </div>
            <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight leading-tight">
                Jadwalkan Cycle Count (Stok Opname)
            </h2>
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

            <div class="pt-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 sm:gap-3">
                <a href="{{ route('cycle-counts.index') }}" class="w-full sm:w-auto text-center px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold rounded-xl shadow transition">
                    Buat Lembar Hitung Opname
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

<x-admin-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 240px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-12 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-scale-balanced me-1 text-[9px]"></i> MASTER DATA
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Satuan Ukur (UoM)
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                            Satuan Ukur Material (UoM)
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            Standar satuan kuantitas inventaris: Pcs, Set, Batang 6 Meter, Lembar, Kg, Liter, Box, dan Meter lari.
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0 flex items-center space-x-3">
                        <a href="{{ route('admin.uoms.create') }}" 
                           class="inline-flex items-center px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs tracking-wide shadow-md hover:shadow-lg transition">
                            <i class="fa-solid fa-plus me-2 text-sm"></i>
                            Satuan Baru
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MAIN UOMS TABLE CARD -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base">Daftar Satuan Ukur Standar</h3>
                    <p class="text-xs text-slate-500">Total {{ $uoms->count() }} unit satuan terdaftar</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200/80 shadow-xs">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50 uppercase tracking-wider text-slate-600 font-extrabold border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5">Kode Satuan</th>
                            <th class="px-5 py-3.5">Nama Satuan</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($uoms as $uom)
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Kode -->
                                <td class="px-5 py-3.5">
                                    <span class="font-mono font-bold text-slate-900 bg-slate-100 px-3 py-1 rounded-lg border border-slate-200">
                                        {{ $uom->code }}
                                    </span>
                                </td>

                                <!-- Nama Satuan -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center font-bold text-xs">
                                            <i class="fa-solid fa-weight-scale"></i>
                                        </div>
                                        <span class="font-bold text-slate-900 text-sm">{{ $uom->name }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-3.5">
                                    @if($uom->is_active)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 me-1.5 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <a href="{{ route('admin.uoms.edit', $uom) }}" 
                                           class="p-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition shadow-2xs" 
                                           title="Edit Satuan">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>

                                        <form action="{{ route('admin.uoms.destroy', $uom) }}" 
                                              method="POST" 
                                              class="inline-block" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus satuan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="p-2 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition shadow-2xs" 
                                                    title="Hapus Satuan">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                                        <i class="fa-solid fa-scale-balanced"></i>
                                    </div>
                                    <p class="font-bold text-sm text-slate-700">Belum ada satuan ukur</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin-layout>

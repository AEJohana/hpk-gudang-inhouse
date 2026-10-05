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
                        <i class="fa-solid fa-tags me-1 text-[9px]"></i> MASTER DATA
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Kategori Komponen
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                            Kategori Komponen Karoseri
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            Klasifikasi material gudang: hidrolik, raw material pelat baja, fastener bodi, kelistrikan, dan cat pelindung.
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0 flex items-center space-x-3">
                        <a href="{{ route('admin.component-categories.create') }}" 
                           class="inline-flex items-center px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs tracking-wide shadow-md hover:shadow-lg transition">
                            <i class="fa-solid fa-plus me-2 text-sm"></i>
                            Kategori Baru
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MAIN CATEGORIES TABLE CARD -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base">Daftar Kategori Tersimpan</h3>
                    <p class="text-xs text-slate-500">Total {{ $categories->count() }} kelompok kategori komponen</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200/80 shadow-xs">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50 uppercase tracking-wider text-slate-600 font-extrabold border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5">Kode Kategori</th>
                            <th class="px-5 py-3.5">Nama & Deskripsi</th>
                            <th class="px-5 py-3.5">Parent / Induk</th>
                            <th class="px-5 py-3.5">Kadaluarsa (Expiry)</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($categories as $category)
                            @php
                                $catIcon = match($category->code) {
                                    'hydraulic' => 'fa-faucet-drip text-blue-600 bg-blue-50',
                                    'raw_material' => 'fa-cubes-stacked text-amber-600 bg-amber-50',
                                    'fastener' => 'fa-screwdriver-wrench text-slate-700 bg-slate-100',
                                    'accessories' => 'fa-truck-front text-teal-600 bg-teal-50',
                                    'electrical' => 'fa-bolt text-yellow-600 bg-yellow-50',
                                    'chemical_paint' => 'fa-flask-vial text-purple-600 bg-purple-50',
                                    default => 'fa-tag text-slate-600 bg-slate-100',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Kode -->
                                <td class="px-5 py-3.5">
                                    <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200">
                                        {{ $category->code }}
                                    </span>
                                </td>

                                <!-- Nama & Deskripsi -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs {{ $catIcon }}">
                                            <i class="fa-solid {{ explode(' ', $catIcon)[0] }}"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm">
                                                {{ $category->name }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 max-w-sm truncate">
                                                {{ $category->description ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Parent -->
                                <td class="px-5 py-3.5">
                                    @if($category->parent)
                                        <span class="inline-flex items-center text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                            <i class="fa-solid fa-turn-up rotate-90 text-[10px] me-1.5 text-slate-400"></i>
                                            {{ $category->parent->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs italic">Kategori Utama</span>
                                    @endif
                                </td>

                                <!-- Expiry -->
                                <td class="px-5 py-3.5">
                                    @if($category->has_expiry)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Ada Expiry
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-3.5">
                                    @if($category->is_active)
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
                                        <a href="{{ route('admin.component-categories.edit', $category) }}" 
                                           class="p-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition shadow-2xs" 
                                           title="Edit Kategori">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>

                                        <form action="{{ route('admin.component-categories.destroy', $category) }}" 
                                              method="POST" 
                                              class="inline-block" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="p-2 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition shadow-2xs" 
                                                    title="Hapus Kategori">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                                        <i class="fa-solid fa-tags"></i>
                                    </div>
                                    <p class="font-bold text-sm text-slate-700">Belum ada kategori komponen</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin-layout>

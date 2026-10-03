<x-admin-layout>
    <div class="wave-header pb-12 pt-8 relative overflow-hidden" style="min-height: 180px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="bg-hpk-orange/20 text-amber-300 border border-amber-300/50 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-widest uppercase">
                            Admin
                        </span>
                        <span class="text-teal-300 text-xs font-bold tracking-widest uppercase">Master Kategori Komponen</span>
                    </div>
                    <h1 class="text-3xl font-black text-white tracking-wide mb-1">
                        Kategori Komponen
                    </h1>
                </div>
                <a href="{{ route('admin.component-categories.create') }}" class="px-4 py-2 bg-[#0a2342] border border-amber-400/50 hover:bg-slate-800 text-amber-400 font-bold text-sm rounded-xl shadow-md transition flex items-center">
                    <i class="fa-solid fa-plus mr-2"></i> Kategori Baru
                </a>
            </div>
        </div>
        <div class="wave-shape-2">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,60 C320,120 420,0 720,60 C1020,120 1120,0 1440,60 L1440,120 L0,120 Z" fill="#f0f4f8"></path>
            </svg>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20 pb-12">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="uppercase tracking-wider text-slate-500 font-bold bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Kode</th>
                            <th class="px-6 py-4">Kategori</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Expiry</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($categories as $category)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-slate-600 font-bold">
                                {{ $category->code }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    @if($category->parent_id)
                                        <i class="fa-solid fa-level-up-alt rotate-90 text-slate-300 mr-2 ml-4"></i>
                                    @endif
                                    <span class="font-bold text-slate-800">{{ $category->name }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 {{ $category->parent_id ? 'ml-8' : '' }}">{{ $category->description ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($category->is_active)
                                    <span class="bg-emerald-100 text-emerald-700 font-bold px-2 py-0.5 rounded-full text-[11px]">Aktif</span>
                                @else
                                    <span class="bg-slate-200 text-slate-700 font-bold px-2 py-0.5 rounded-full text-[11px]">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($category->has_expiry)
                                    <span class="bg-amber-100 text-amber-700 font-bold px-2 py-0.5 rounded-full text-[11px]">Ada Expiry</span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.component-categories.edit', $category) }}" class="text-blue-600 hover:text-blue-800 px-2 py-1 bg-blue-50 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                
                                <form action="{{ route('admin.component-categories.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus kategori ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 px-2 py-1 bg-rose-50 rounded-lg transition" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                Tidak ada data kategori.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-admin-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                    Pengajuan Disposal (Scrap & Afkir Material Karoseri)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Pencatatan & persetujuan pemusnahan part cacat, potongan pelat sisa (offcut), dan besi tua</p>
            </div>
            <a href="{{ route('disposals.create') }}" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center self-start sm:self-auto touch-target">
                <i class="fa-solid fa-plus me-1.5"></i>
                Buat Pengajuan Disposal
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Status Filter Pills (Horizontal Touch Scroll) -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1 w-full -mx-1 px-1">
            <a href="{{ route('disposals.index') }}" class="super-nav-pill whitespace-nowrap {{ !request('status') ? 'active' : '' }}">
                Semua Disposal
            </a>
            <a href="{{ route('disposals.index', ['status' => 'submitted']) }}" class="super-nav-pill whitespace-nowrap {{ request('status') == 'submitted' ? 'active bg-amber-500 text-slate-950 font-bold' : '' }}">
                <i class="fa-solid fa-clock me-1 text-amber-500"></i> Menunggu Persetujuan
            </a>
            <a href="{{ route('disposals.index', ['status' => 'approved_manager']) }}" class="super-nav-pill whitespace-nowrap {{ request('status') == 'approved_manager' ? 'active bg-blue-600 text-white' : '' }}">
                <i class="fa-solid fa-thumbs-up me-1 text-blue-500"></i> Telah Disetujui
            </a>
            <a href="{{ route('disposals.index', ['status' => 'completed']) }}" class="super-nav-pill whitespace-nowrap {{ request('status') == 'completed' ? 'active bg-emerald-600 text-white' : '' }}">
                <i class="fa-solid fa-check-double me-1 text-emerald-500"></i> Selesai
            </a>
        </div>

        <!-- 1. Mobile Card List (< md screens) -->
        <div class="block md:hidden space-y-3">
            @forelse ($disposals as $dsp)
                <div class="mobile-data-card space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-mono font-bold text-rose-900 text-xs block">{{ $dsp->disposal_number }}</span>
                            <span class="text-[10px] text-slate-400 font-medium">{{ $dsp->created_at->format('d M Y') }}</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold border {{ $dsp->status_badge['class'] }}">
                            {{ $dsp->status_badge['label'] }}
                        </span>
                    </div>

                    <div class="p-2.5 bg-rose-50/50 rounded-xl border border-rose-100 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">{{ $dsp->disposal_type_label }}</span>
                            <span class="text-[10px] text-slate-500">{{ $dsp->items->count() }} Jenis Item Diproses</span>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-slate-900 text-sm block">{{ number_format($dsp->estimated_weight_kg, 1) }} Kg</span>
                            @if ($dsp->estimated_salvage_value > 0)
                                <span class="text-[10px] text-emerald-600 font-bold block">Rp {{ number_format($dsp->estimated_salvage_value, 0, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 line-clamp-2">{{ $dsp->reason }}</p>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                        <span class="text-[10px] text-slate-400">Oleh: {{ $dsp->requestedBy->name }}</span>
                        <a href="{{ route('disposals.show', $dsp) }}" class="px-3 py-1.5 bg-rose-50 text-rose-700 font-bold text-xs rounded-lg hover:bg-rose-100 transition">
                            Rincian &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-slate-400 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <i class="fa-solid fa-trash-can text-3xl mb-2 text-slate-300 block"></i>
                    Belum ada data pengajuan disposal scrap.
                </div>
            @endforelse
        </div>

        <!-- 2. Desktop & Tablet Super Table (>= md screens) -->
        <div class="hidden md:block overflow-x-auto pb-4">
            <table class="super-table">
                <thead>
                    <tr>
                        <th class="py-3 px-4">No. Disposal & Tanggal</th>
                        <th class="py-3 px-4">Kategori Scrap</th>
                        <th class="py-3 px-4">Alasan Pemusnahan</th>
                        <th class="py-3 px-4 text-center">Estimasi Berat</th>
                        <th class="py-3 px-4">Status & Approval</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($disposals as $dsp)
                        <tr class="super-row">
                            <td class="py-4 px-4">
                                <span class="font-mono font-bold text-rose-900 block text-xs">{{ $dsp->disposal_number }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $dsp->created_at->format('d M Y') }}</span>
                            </td>

                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-800 block text-xs">{{ $dsp->disposal_type_label }}</span>
                                <span class="text-[10px] text-slate-400 font-semibold">{{ $dsp->items->count() }} Jenis Item</span>
                            </td>

                            <td class="py-4 px-4 max-w-xs">
                                <p class="text-slate-700 text-xs line-clamp-1">{{ $dsp->reason }}</p>
                            </td>

                            <td class="py-4 px-4 text-center">
                                <span class="font-black text-slate-900 text-sm block">{{ number_format($dsp->estimated_weight_kg, 1) }} Kg</span>
                                @if ($dsp->estimated_salvage_value > 0)
                                    <span class="text-[10px] text-emerald-600 font-bold">Rp {{ number_format($dsp->estimated_salvage_value, 0, ',', '.') }}</span>
                                @endif
                            </td>

                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-bold border {{ $dsp->status_badge['class'] }}">
                                    {{ $dsp->status_badge['label'] }}
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Oleh: {{ $dsp->requestedBy->name }}</span>
                            </td>

                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('disposals.show', $dsp) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-700 font-bold text-xs rounded-xl transition inline-flex items-center">
                                    Rincian <i class="fa-solid fa-arrow-right ms-1.5 text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 bg-white rounded-2xl shadow-sm">
                                <i class="fa-solid fa-trash-can text-3xl mb-2 text-slate-300 block"></i>
                                Belum ada data pengajuan disposal scrap.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($disposals->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                {{ $disposals->links() }}
            </div>
        @endif

    </div>
</x-app-layout>

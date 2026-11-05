<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2.5 sm:gap-4 w-full">
            <!-- Left: Back Button + Icon Badge + Title (Judul Saja Tanpa Deskripsi) -->
            <div class="flex items-center space-x-2 sm:space-x-3.5 min-w-0">
                <a href="{{ route('dashboard') }}" 
                   class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200/80 flex items-center justify-center text-slate-700 transition shadow-2xs shrink-0 cursor-pointer" 
                   title="Kembali ke Beranda">
                    <i class="fa-solid fa-arrow-left text-sm text-slate-600"></i>
                </a>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-purple-50 border border-purple-200/80 flex items-center justify-center text-purple-700 shadow-2xs shrink-0">
                    <i class="fa-solid fa-file-pen text-base sm:text-lg"></i>
                </div>
                <h2 class="font-extrabold text-base sm:text-xl text-[#0a2342] tracking-tight leading-tight truncate">
                    ECR Revisi Part
                </h2>
            </div>

            <!-- Right: Action Buttons (Anchored to Far Right) -->
            <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0 ml-auto">
                <a href="{{ route('ecrs.create') }}" class="px-3.5 py-2 bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center touch-target whitespace-nowrap space-x-1.5">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Ajukan ECR Baru</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Status Filter Pills (Horizontal Touch Scroll) -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1 w-full -mx-1 px-1">
            <a href="{{ route('ecrs.index') }}" class="super-nav-pill whitespace-nowrap {{ !request('status') ? 'active' : '' }}">
                Semua ECR
            </a>
            <a href="{{ route('ecrs.index', ['status' => 'submitted']) }}" class="super-nav-pill whitespace-nowrap {{ request('status') == 'submitted' ? 'active bg-amber-500 text-slate-950 font-bold' : '' }}">
                <i class="fa-solid fa-clock me-1 text-amber-500"></i> Menunggu Review
            </a>
            <a href="{{ route('ecrs.index', ['status' => 'approved']) }}" class="super-nav-pill whitespace-nowrap {{ request('status') == 'approved' ? 'active bg-emerald-600 text-white' : '' }}">
                <i class="fa-solid fa-check-circle me-1 text-emerald-500"></i> Disetujui
            </a>
            <a href="{{ route('ecrs.index', ['status' => 'rejected']) }}" class="super-nav-pill whitespace-nowrap {{ request('status') == 'rejected' ? 'active bg-rose-600 text-white' : '' }}">
                <i class="fa-solid fa-circle-xmark me-1 text-rose-500"></i> Ditolak
            </a>
        </div>

        <!-- 1. Mobile Card List (< md screens) -->
        <div class="block md:hidden space-y-3">
            @forelse ($ecrs as $ecr)
                <div class="mobile-data-card space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-mono font-bold text-purple-900 text-xs block">{{ $ecr->ecr_number }}</span>
                            <span class="text-[10px] text-slate-400 font-medium">{{ $ecr->created_at->format('d M Y') }}</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold border {{ $ecr->status_badge['class'] }}">
                            {{ $ecr->status_badge['label'] }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-2.5 p-2 bg-slate-50 rounded-xl">
                        <img src="{{ $ecr->component?->image_url ?? asset('images/logo_hpk.webp') }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 bg-white flex-shrink-0">
                        <div class="min-w-0 flex-1">
                            <span class="font-mono font-bold text-slate-900 text-[11px] block">{{ $ecr->component?->part_number ?? '-' }}</span>
                            <span class="text-xs font-semibold text-slate-800 line-clamp-1">{{ $ecr->component?->name ?? 'Komponen' }}</span>
                        </div>
                    </div>

                    <div>
                        <a href="{{ route('ecrs.show', $ecr) }}" class="font-bold text-slate-900 text-xs block hover:text-purple-700">
                            {{ $ecr->title }}
                        </a>
                        <span class="text-[10px] text-purple-700 font-semibold block mt-0.5">
                            {{ $ecr->revision_type_label }} &bull; Kebijakan: {{ match($ecr->stock_policy) {
                                'run_out' => 'Habiskan Stok',
                                'immediate_scrap' => 'Scrap',
                                'rework' => 'Rework',
                                default => $ecr->stock_policy
                            } }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                        <span class="text-[10px] text-slate-400">Pengaju: {{ $ecr->requestedBy?->name ?? 'User' }}</span>
                        <a href="{{ route('ecrs.show', $ecr) }}" class="px-3 py-1.5 bg-purple-50 text-purple-700 font-bold text-xs rounded-lg hover:bg-purple-100 transition">
                            Review ECR &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-slate-400 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <i class="fa-solid fa-file-circle-question text-3xl mb-2 text-slate-300 block"></i>
                    Belum ada dokumen ECR yang diajukan.
                </div>
            @endforelse
        </div>

        <!-- 2. Desktop & Tablet Super Table (>= md screens) -->
        <div class="hidden md:block overflow-x-auto pb-4">
            <table class="super-table">
                <thead>
                    <tr>
                        <th class="py-3 px-4">No. ECR & Tanggal</th>
                        <th class="py-3 px-4">Komponen Terkait</th>
                        <th class="py-3 px-4">Judul & Jenis Perubahan</th>
                        <th class="py-3 px-4">Kebijakan Stok Lama</th>
                        <th class="py-3 px-4">Status & Pengaju</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ecrs as $ecr)
                        <tr class="super-row">
                            <td class="py-4 px-4">
                                <span class="font-mono font-bold text-purple-900 block text-xs">{{ $ecr->ecr_number }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $ecr->created_at->format('d M Y') }}</span>
                            </td>

                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ $ecr->component?->image_url ?? asset('images/logo_hpk.webp') }}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 bg-white shadow-xs">
                                    <div>
                                        <span class="font-mono font-bold text-slate-900 block text-xs">{{ $ecr->component?->part_number ?? '-' }}</span>
                                        <span class="text-[11px] text-slate-500 truncate max-w-xs block">{{ $ecr->component?->name ?? 'Komponen' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-4 px-4 max-w-sm">
                                <a href="{{ route('ecrs.show', $ecr) }}" class="font-bold text-slate-900 hover:text-purple-700 block line-clamp-1 transition">
                                    {{ $ecr->title }}
                                </a>
                                <span class="text-[10px] text-purple-700 font-semibold block mt-0.5">
                                    {{ $ecr->revision_type_label }}
                                </span>
                            </td>

                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-700">
                                    {{ match($ecr->stock_policy) {
                                        'run_out' => 'Habiskan Stok Lama',
                                        'immediate_scrap' => 'Langsung Scrap/Disposal',
                                        'rework' => 'Rework di Workshop',
                                        default => $ecr->stock_policy
                                    } }}
                                </span>
                            </td>

                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-bold border {{ $ecr->status_badge['class'] }}">
                                    {{ $ecr->status_badge['label'] }}
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Oleh: {{ $ecr->requestedBy?->name ?? 'User' }}</span>
                            </td>

                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('ecrs.show', $ecr) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-purple-50 hover:text-purple-700 text-slate-700 font-bold text-xs rounded-xl transition inline-flex items-center">
                                    Review ECR <i class="fa-solid fa-arrow-right ms-1.5 text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 bg-white rounded-2xl shadow-sm">
                                <i class="fa-solid fa-file-circle-question text-3xl mb-2 text-slate-300 block"></i>
                                Belum ada dokumen ECR yang diajukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ecrs->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                {{ $ecrs->links() }}
            </div>
        @endif

    </div>
</x-app-layout>

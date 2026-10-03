<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                    ECR (Engineering Change Request) - Revisi Part
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Pengendalian revisi spesifikasi teknis komponen karoseri, perubahan part number & persetujuan teknik</p>
            </div>
            <a href="{{ route('ecrs.create') }}" class="px-3.5 py-2 bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center">
                <i class="fa-solid fa-plus me-1.5"></i>
                Ajukan ECR Baru
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Status Filter Pills (Super Nav Pills) -->
        <div class="super-nav-pills flex-wrap">
            <a href="{{ route('ecrs.index') }}" class="super-nav-pill {{ !request('status') ? 'active' : '' }}">
                Semua ECR
            </a>
            <a href="{{ route('ecrs.index', ['status' => 'submitted']) }}" class="super-nav-pill {{ request('status') == 'submitted' ? 'active bg-amber-500 text-slate-950 font-bold' : '' }}">
                <i class="fa-solid fa-clock me-1 text-amber-500"></i> Menunggu Review
            </a>
            <a href="{{ route('ecrs.index', ['status' => 'approved']) }}" class="super-nav-pill {{ request('status') == 'approved' ? 'active bg-emerald-600 text-white' : '' }}">
                <i class="fa-solid fa-check-circle me-1 text-emerald-500"></i> Disetujui (Approved)
            </a>
            <a href="{{ route('ecrs.index', ['status' => 'rejected']) }}" class="super-nav-pill {{ request('status') == 'rejected' ? 'active bg-rose-600 text-white' : '' }}">
                <i class="fa-solid fa-circle-xmark me-1 text-rose-500"></i> Ditolak
            </a>
        </div>

        <!-- Super Table with Floating Separated Rows -->
        <div class="overflow-x-auto pb-4">
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
                                    <img src="{{ $ecr->component->image_url }}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 bg-white shadow-xs">
                                    <div>
                                        <span class="font-mono font-bold text-slate-900 block text-xs">{{ $ecr->component->part_number }}</span>
                                        <span class="text-[11px] text-slate-500 truncate max-w-xs block">{{ $ecr->component->name }}</span>
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
                                <span class="text-[10px] text-slate-400 block mt-0.5">Oleh: {{ $ecr->requestedBy->name }}</span>
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

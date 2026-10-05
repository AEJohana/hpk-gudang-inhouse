<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                    Permintaan & Pencetakan Label QR Komponen
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Generator stiker barcode & QR Code tahan oli/panas untuk penandaan part, box, dan rak gudang HPK</p>
            </div>
            <a href="{{ route('qr-requests.create') }}" class="px-3.5 py-2 bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center">
                <i class="fa-solid fa-plus me-1.5"></i>
                Buat Permintaan Label
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Status Filter Pills (Super Nav Pills) -->
        <div class="super-nav-pills flex-wrap">
            <a href="{{ route('qr-requests.index') }}" class="super-nav-pill {{ !request('status') ? 'active' : '' }}">
                Semua Antrian
            </a>
            <a href="{{ route('qr-requests.index', ['status' => 'pending']) }}" class="super-nav-pill {{ request('status') == 'pending' ? 'active bg-amber-500 text-slate-950 font-bold' : '' }}">
                <i class="fa-solid fa-clock me-1 text-amber-500"></i> Menunggu Dicetak
            </a>
            <a href="{{ route('qr-requests.index', ['status' => 'printed']) }}" class="super-nav-pill {{ request('status') == 'printed' ? 'active bg-emerald-600 text-white' : '' }}">
                <i class="fa-solid fa-check-circle me-1 text-emerald-500"></i> Selesai Dicetak
            </a>
        </div>

        <!-- Super Table with Floating Separated Rows -->
        <div class="overflow-x-auto pb-4">
            <table class="super-table">
                <thead>
                    <tr>
                        <th class="py-3 px-4">No. Request & Tanggal</th>
                        <th class="py-3 px-4">Komponen & Part Number</th>
                        <th class="py-3 px-4">Jenis Label</th>
                        <th class="py-3 px-4 text-center">Jumlah Cetak (Qty)</th>
                        <th class="py-3 px-4">Status & Pemohon</th>
                        <th class="py-3 px-4 text-right">Opsi Cetak</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($qrRequests as $qr)
                        <tr class="super-row">
                            <td class="py-4 px-4">
                                <span class="font-mono font-bold text-cyan-900 block text-xs">{{ $qr->request_number }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $qr->created_at->format('d M Y') }}</span>
                            </td>

                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ $qr->component?->image_url ?? asset('images/logo_hpk.webp') }}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 bg-white shadow-xs">
                                    <div>
                                        <span class="font-mono font-bold text-slate-900 block text-xs">{{ $qr->component?->part_number ?? '-' }}</span>
                                        <span class="text-[11px] text-slate-500 truncate max-w-xs block">{{ $qr->component?->name ?? 'Komponen' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-100 text-slate-700">
                                    {{ match($qr->label_type) {
                                        'item' => 'Label Stiker Item/Part',
                                        'box' => 'Label Master Box/Koli',
                                        'rack' => 'Label Identitas Rak',
                                        default => $qr->label_type
                                    } }}
                                </span>
                            </td>

                            <td class="py-4 px-4 text-center">
                                <span class="font-bold text-slate-900 text-sm">{{ $qr->print_qty }} Label</span>
                            </td>

                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-bold border {{ $qr->status_badge['class'] }}">
                                    {{ $qr->status_badge['label'] }}
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Oleh: {{ $qr->requestedBy->name }}</span>
                            </td>

                            <td class="py-4 px-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('qr-requests.print-thermal', $qr) }}" target="_blank" class="px-3 py-1.5 bg-[#0a2342] hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-xs transition inline-flex items-center">
                                    <i class="fa-solid fa-receipt me-1.5 text-amber-400"></i> Thermal
                                </a>
                                <a href="{{ route('qr-requests.print-sheet', $qr) }}" target="_blank" class="px-3 py-1.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-800 border border-cyan-200 font-semibold text-xs rounded-xl transition inline-flex items-center">
                                    <i class="fa-solid fa-file-pdf me-1.5"></i> Lembar A4
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 bg-white rounded-2xl shadow-sm">
                                <i class="fa-solid fa-qrcode text-3xl mb-2 text-slate-300 block"></i>
                                Belum ada permintaan pencetakan label QR.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($qrRequests->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                {{ $qrRequests->links() }}
            </div>
        @endif

    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2.5 sm:gap-4 w-full">
            <!-- Left: Back Button + Icon Badge + Title (Judul Saja Tanpa Deskripsi) -->
            <div class="flex items-center space-x-3 sm:space-x-3.5 min-w-0">
                <a href="{{ route('dashboard') }}" 
                   class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200/80 flex items-center justify-center text-slate-700 transition shadow-2xs shrink-0 cursor-pointer" 
                   title="Kembali ke Beranda">
                    <i class="fa-solid fa-arrow-left text-sm text-slate-600"></i>
                </a>
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-sky-50 border border-sky-200/80 flex items-center justify-center text-sky-700 shadow-2xs shrink-0">
                    <i class="fa-solid fa-qrcode text-base sm:text-lg"></i>
                </div>
                <h2 class="font-extrabold text-base sm:text-xl text-[#0a2342] tracking-tight leading-tight truncate">
                    Cetak Label QR Mesin
                </h2>
            </div>

            <!-- Right: Action Buttons (Anchored to Far Right) -->
            <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0 ml-auto">
                <a href="{{ route('qr-requests.create') }}" class="px-3.5 py-2 bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center whitespace-nowrap space-x-1.5">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Buat Permintaan Label</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
         x-data="{
            selectedIds: [],
            allIds: {{ json_encode($qrRequests->pluck('id')->values()->all()) }},
            toggleAll() {
                if (this.selectedIds.length === this.allIds.length) {
                    this.selectedIds = [];
                } else {
                    this.selectedIds = [...this.allIds];
                }
            },
            get isAllSelected() {
                return this.allIds.length > 0 && this.selectedIds.length === this.allIds.length;
            }
         }">

        <!-- 3 Kartu Indikator KPI Interaktif -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Kartu 1: Menunggu Dicetak -->
            <a href="{{ route('qr-requests.index', ['status' => 'pending'] + (request('q') ? ['q' => request('q')] : [])) }}"
               class="group relative overflow-hidden rounded-2xl p-5 bg-white border transition-all duration-200 shadow-xs hover:shadow-md {{ $currentStatus === 'pending' ? 'border-amber-400 ring-2 ring-amber-400/30 bg-amber-50/20' : 'border-slate-200 hover:border-amber-300' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Menunggu Dicetak</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ number_format($counts->pending ?? 0) }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Antrean stiker yang perlu segera dicetak</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110 {{ $currentStatus === 'pending' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-amber-100 text-amber-700' }}">
                        <i class="fa-solid fa-clock text-xl"></i>
                    </div>
                </div>
                @if ($currentStatus === 'pending')
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-amber-500"></div>
                @endif
            </a>

            <!-- Kartu 2: Selesai Dicetak -->
            <a href="{{ route('qr-requests.index', ['status' => 'printed'] + (request('q') ? ['q' => request('q')] : [])) }}"
               class="group relative overflow-hidden rounded-2xl p-5 bg-white border transition-all duration-200 shadow-xs hover:shadow-md {{ $currentStatus === 'printed' ? 'border-emerald-400 ring-2 ring-emerald-400/30 bg-emerald-50/20' : 'border-slate-200 hover:border-emerald-300' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Selesai Dicetak</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ number_format($counts->printed ?? 0) }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Label yang sudah dicetak thermal / A4</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110 {{ $currentStatus === 'printed' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-100 text-emerald-700' }}">
                        <i class="fa-solid fa-circle-check text-xl"></i>
                    </div>
                </div>
                @if ($currentStatus === 'printed')
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-500"></div>
                @endif
            </a>

            <!-- Kartu 3: Total Permintaan -->
            <a href="{{ route('qr-requests.index', ['status' => 'all'] + (request('q') ? ['q' => request('q')] : [])) }}"
               class="group relative overflow-hidden rounded-2xl p-5 bg-white border transition-all duration-200 shadow-xs hover:shadow-md {{ $currentStatus === 'all' ? 'border-cyan-500 ring-2 ring-cyan-500/30 bg-cyan-50/20' : 'border-slate-200 hover:border-cyan-300' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-cyan-800">Total Permintaan</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ number_format($counts->total ?? 0) }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Semua riwayat pengajuan label QR</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110 {{ $currentStatus === 'all' ? 'bg-cyan-700 text-white shadow-sm' : 'bg-cyan-100 text-cyan-800' }}">
                        <i class="fa-solid fa-tags text-xl"></i>
                    </div>
                </div>
                @if ($currentStatus === 'all')
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-cyan-600"></div>
                @endif
            </a>
        </div>

        <!-- Status Filter Pills & Quick Search -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="super-nav-pills flex-nowrap overflow-x-auto no-scrollbar sm:flex-wrap pb-1">
                <a href="{{ route('qr-requests.index', ['status' => 'pending'] + (request('q') ? ['q' => request('q')] : [])) }}" 
                   class="super-nav-pill {{ $currentStatus === 'pending' ? 'active bg-amber-500 text-slate-950 font-bold' : '' }} whitespace-nowrap flex-shrink-0 inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-clock {{ $currentStatus === 'pending' ? 'text-slate-950' : 'text-amber-500' }}"></i>
                    <span>Menunggu Dicetak</span>
                    <span class="ms-1 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $currentStatus === 'pending' ? 'bg-slate-950 text-white' : 'bg-amber-100 text-amber-800' }}">
                        {{ $counts->pending ?? 0 }}
                    </span>
                </a>
                <a href="{{ route('qr-requests.index', ['status' => 'printed'] + (request('q') ? ['q' => request('q')] : [])) }}" 
                   class="super-nav-pill {{ $currentStatus === 'printed' ? 'active bg-emerald-600 text-white font-bold' : '' }} whitespace-nowrap flex-shrink-0 inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-check-circle {{ $currentStatus === 'printed' ? 'text-white' : 'text-emerald-500' }}"></i>
                    <span>Selesai Dicetak</span>
                    <span class="ms-1 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $currentStatus === 'printed' ? 'bg-white/25 text-white' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $counts->printed ?? 0 }}
                    </span>
                </a>
                <a href="{{ route('qr-requests.index', ['status' => 'all'] + (request('q') ? ['q' => request('q')] : [])) }}" 
                   class="super-nav-pill {{ $currentStatus === 'all' ? 'active bg-cyan-700 text-white font-bold' : '' }} whitespace-nowrap flex-shrink-0 inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-layer-group {{ $currentStatus === 'all' ? 'text-white' : 'text-slate-500' }}"></i>
                    <span>Semua Antrian</span>
                    <span class="ms-1 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $currentStatus === 'all' ? 'bg-white/25 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ $counts->total ?? 0 }}
                    </span>
                </a>
            </div>

            <!-- Quick Search Form -->
            <form method="GET" action="{{ route('qr-requests.index') }}" class="w-full md:w-80 flex items-center gap-1.5">
                <input type="hidden" name="status" value="{{ $currentStatus }}">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </div>
                    <input type="text" 
                           name="q" 
                           value="{{ request('q') }}" 
                           placeholder="Cari no. request, part, atau nama..." 
                           class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-300 focus:outline-hidden focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600 bg-white">
                    @if (request('q'))
                        <a href="{{ route('qr-requests.index', ['status' => $currentStatus]) }}" 
                           class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-rose-600"
                           title="Hapus Pencarian">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </a>
                    @endif
                </div>
                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl shadow-xs transition">
                    Cari
                </button>
            </form>
        </div>

        <!-- Mobile Card View (< md) -->
        <div class="block md:hidden space-y-3">
            @forelse ($qrRequests as $qr)
                <div class="mobile-data-card" :class="selectedIds.includes({{ $qr->id }}) ? 'ring-2 ring-cyan-500 bg-cyan-50/30' : ''">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                        <div class="flex items-center space-x-2.5">
                            <input type="checkbox" 
                                   value="{{ $qr->id }}" 
                                   x-model.number="selectedIds" 
                                   class="rounded text-cyan-600 focus:ring-cyan-500 h-4 w-4 border-slate-300 cursor-pointer">
                            <div>
                                <span class="font-mono font-bold text-cyan-900 text-xs block">{{ $qr->request_number }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $qr->created_at->format('d M Y') }}</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold border {{ $qr->status_badge['class'] }}">
                            {{ $qr->status_badge['label'] }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-3 py-1">
                        <img src="{{ $qr->component?->image_url ?? asset('images/logo_hpk.webp') }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200 bg-white shadow-xs flex-shrink-0">
                        <div class="min-w-0 flex-1">
                            <span class="font-mono font-bold text-slate-900 block text-xs truncate">{{ $qr->component?->part_number ?? '-' }}</span>
                            <span class="text-xs text-slate-600 block line-clamp-1 mt-0.5">{{ $qr->component?->name ?? 'Komponen' }}</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold {{ $qr->label_type === 'rack' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-cyan-50 text-cyan-800 border border-cyan-200' }}">
                                    {{ match($qr->label_type) {
                                        'rack' => 'Label QR Rak',
                                        'card', 'item' => 'Label QR Kartu',
                                        'box' => 'Master Box',
                                        default => ucfirst($qr->label_type)
                                    } }}
                                </span>
                                <span class="font-extrabold text-slate-900 text-xs">{{ $qr->print_qty }} Label</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs gap-2">
                        <span class="text-[10px] text-slate-400 truncate">Oleh: {{ $qr->requestedBy->name }}</span>
                        <div class="flex items-center space-x-1.5 flex-shrink-0">
                            <a href="{{ route('qr-requests.print-thermal', $qr) }}" target="_blank" class="px-2.5 py-1.5 bg-[#0a2342] hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-xs transition inline-flex items-center">
                                <i class="fa-solid fa-receipt me-1 text-amber-400 text-[10px]"></i> Thermal
                            </a>
                            <a href="{{ route('qr-requests.print-sheet', $qr) }}" target="_blank" class="px-2.5 py-1.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-800 border border-cyan-200 font-semibold text-xs rounded-xl transition inline-flex items-center">
                                <i class="fa-solid fa-file-pdf me-1 text-[10px]"></i> A4
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-slate-400 bg-white rounded-2xl border border-slate-200 p-6">
                    <i class="fa-solid fa-qrcode text-3xl mb-2 text-slate-300 block"></i>
                    @if (request('q'))
                        Tidak ada permintaan label yang sesuai dengan pencarian "<span class="font-semibold text-slate-600">{{ request('q') }}</span>".
                    @elseif ($currentStatus === 'pending')
                        Tidak ada antrean label yang menunggu dicetak saat ini.
                    @elseif ($currentStatus === 'printed')
                        Belum ada riwayat label yang sudah dicetak.
                    @else
                        Belum ada permintaan pencetakan label QR.
                    @endif
                </div>
            @endforelse
        </div>

        <!-- Super Table with Floating Separated Rows (Desktop >= md) -->
        <div class="hidden md:block overflow-x-auto pb-4">
            <table class="super-table">
                <thead>
                    <tr>
                        <th class="py-3 px-3 w-10 text-center">
                            <input type="checkbox" 
                                   @change="toggleAll()" 
                                   :checked="isAllSelected" 
                                   class="rounded text-cyan-600 focus:ring-cyan-500 h-4 w-4 border-slate-300 cursor-pointer"
                                   title="Pilih Semua Tiket di Halaman Ini">
                        </th>
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
                        <tr class="super-row" :class="selectedIds.includes({{ $qr->id }}) ? 'bg-cyan-50/50 ring-1 ring-cyan-300' : ''">
                            <td class="py-4 px-3 text-center">
                                <input type="checkbox" 
                                       value="{{ $qr->id }}" 
                                       x-model.number="selectedIds" 
                                       class="rounded text-cyan-600 focus:ring-cyan-500 h-4 w-4 border-slate-300 cursor-pointer">
                            </td>

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
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold {{ $qr->label_type === 'rack' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-cyan-50 text-cyan-800 border border-cyan-200' }}">
                                    {{ match($qr->label_type) {
                                        'rack' => 'Label QR Rak',
                                        'card', 'item' => 'Label QR Kartu',
                                        'box' => 'Label Master Box',
                                        default => ucfirst($qr->label_type)
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
                            <td colspan="7" class="py-12 text-center text-slate-400 bg-white rounded-2xl shadow-sm">
                                <i class="fa-solid fa-qrcode text-3xl mb-2 text-slate-300 block"></i>
                                @if (request('q'))
                                    Tidak ada permintaan label yang sesuai dengan pencarian "<span class="font-semibold text-slate-600">{{ request('q') }}</span>".
                                @elseif ($currentStatus === 'pending')
                                    Tidak ada antrean label yang menunggu dicetak saat ini.
                                @elseif ($currentStatus === 'printed')
                                    Belum ada riwayat label yang sudah dicetak.
                                @else
                                    Belum ada permintaan pencetakan label QR.
                                @endif
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

        <!-- Floating Action Bar Cetak Bersamaan Massal -->
        <div x-show="selectedIds.length > 0"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-8"
             class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center gap-4 max-w-xl w-[92%] sm:w-auto"
             style="display: none;">
            <div class="flex items-center space-x-2.5">
                <span class="w-7 h-7 rounded-lg bg-cyan-400 text-slate-950 font-black text-xs flex items-center justify-center shadow-xs"
                      x-text="selectedIds.length"></span>
                <span class="text-xs font-bold text-slate-200">Tiket Terpilih</span>
            </div>

            <form method="POST" action="{{ route('qr-requests.bulk-print') }}" target="_blank" class="flex items-center gap-2">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit" 
                        class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-print text-amber-300"></i>
                    <span>Cetak Bersamaan (A4)</span>
                </button>
            </form>

            <button type="button" 
                    @click="selectedIds = []" 
                    class="text-xs text-slate-400 hover:text-white transition px-2 py-1 font-semibold cursor-pointer">
                Batal
            </button>
        </div>

    </div>
</x-app-layout>

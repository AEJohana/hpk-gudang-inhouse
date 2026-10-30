@php
    $componentItem = $componentItem ?? $masterComponent ?? ($component ?? null);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div class="flex items-center space-x-3">
                <a href="{{ route('components.index') }}" 
                   class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer"
                   title="Kembali ke Master Komponen">
                    <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                    <span>Kembali</span>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-amber-900 bg-amber-200 px-2 py-0.5 rounded">{{ $componentItem->part_number }}</span>
                        <span class="text-xs px-2 py-0.5 rounded font-medium bg-slate-100 text-slate-700">{{ $componentItem->category_label }}</span>
                    </div>
                    <h2 class="font-bold text-xl text-slate-900 leading-tight mt-1">
                        {{ $componentItem->name }}
                    </h2>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('components.qr-label', $componentItem) }}" target="_blank" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-sm transition flex items-center">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    Cetak Label QR
                </a>
                <a href="{{ route('transactions.create', ['component_id' => $componentItem->id]) }}" class="px-3 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-sm transition flex items-center">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Transaksi Barang
                </a>
                <a href="{{ route('components.edit', $componentItem) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                    Edit Data
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        @if (!$componentItem->default_location_id)
            <!-- Urgent Putaway Alert Banner -->
            <div class="p-4 bg-amber-50 border-2 border-amber-300 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-xs animate-pulse">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold flex-shrink-0 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-amber-950 uppercase tracking-wider">Perhatian: Komponen Belum Memiliki Posisi Rak!</h4>
                        <p class="text-[11px] text-amber-800 mt-0.5">Komponen ini belum dialokasikan ke rak penyimpanan tetap (fisik tercatat di penampungan awal). Segera tentukan posisi rak agar mudah ditemukan oleh tim karoseri.</p>
                    </div>
                </div>
                <a href="{{ route('components.edit', $componentItem) }}" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold rounded-xl whitespace-nowrap shadow transition flex items-center gap-1.5 self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Atur Lokasi Rak Sekarang
                </a>
            </div>
        @endif

        @if (!$componentItem->category)
            <!-- Uncategorized Warning Banner -->
            <div class="p-4 bg-orange-50 border-2 border-orange-200 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-xs">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center font-bold flex-shrink-0 shadow-sm">
                        <i class="fa-solid fa-tags text-base"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-orange-950 uppercase tracking-wider">Perhatian: Komponen Belum Dikategorikan!</h4>
                        <p class="text-[11px] text-orange-800 mt-0.5">Komponen ini belum dikelompokkan ke kategori resmi (Subframe, Assy, Aksesoris, PTO, Potong Chassis, atau SPO). Silakan pilih kategori yang sesuai.</p>
                    </div>
                </div>
                <a href="{{ route('components.edit', $componentItem) }}" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl whitespace-nowrap shadow transition flex items-center gap-1.5 self-start sm:self-auto">
                    <i class="fa-solid fa-pen text-xs"></i>
                    Pilih Kategori Sekarang
                </a>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Col 1: Visual Photo & QR Code Card -->
            <div class="space-y-6">
                <!-- Component Photo Card with Interactive Zoom & PDF Viewer -->
                <div x-data="{ zoomModalOpen: false, zoomLevel: 1.0 }" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col items-center text-center">
                    <div class="flex items-center justify-between w-full mb-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            Foto Fisik & Drawing
                        </h3>
                        @if($componentItem->is_pdf_drawing)
                            <span class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold rounded-md uppercase">
                                <i class="fa-solid fa-file-pdf me-1"></i> CAD PDF
                            </span>
                        @else
                            <span class="text-[10px] text-slate-400">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> Klik untuk zoom
                            </span>
                        @endif
                    </div>

                    @if($componentItem->is_pdf_drawing)
                        <div class="w-full p-6 rounded-xl bg-slate-900 border border-slate-800 flex flex-col items-center justify-center space-y-2 cursor-pointer hover:bg-slate-850 transition group"
                             @click="zoomModalOpen = true">
                            <div class="w-14 h-14 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-500 text-3xl group-hover:scale-110 transition">
                                <i class="fa-solid fa-file-pdf"></i>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-white block">Dokumen CAD (PDF)</span>
                                <span class="text-[10px] text-slate-400">Tersedia lembar kerja gambar teknik</span>
                            </div>
                            <button type="button" class="mt-2 px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-lg shadow transition flex items-center space-x-1 cursor-pointer">
                                <i class="fa-solid fa-expand text-[10px]"></i>
                                <span>Buka Drawing CAD</span>
                            </button>
                        </div>
                    @else
                        <div class="relative w-full aspect-square rounded-xl overflow-hidden border border-slate-200 bg-slate-50 flex items-center justify-center shadow-inner cursor-pointer group"
                             @click="zoomModalOpen = true"
                             title="Klik untuk memperbesar gambar">
                            <img src="{{ $componentItem->image_url }}" alt="{{ $componentItem->name }}" class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <span class="px-3 py-1.5 bg-slate-900/80 text-white rounded-lg text-xs font-bold flex items-center space-x-1.5 backdrop-blur-xs">
                                    <i class="fa-solid fa-magnifying-glass-plus text-xs"></i>
                                    <span>Perbesar Dimensi</span>
                                </span>
                            </div>
                        </div>
                    @endif
                    <p class="text-[11px] text-slate-400 mt-2">Diverifikasi untuk lini perakitan karoseri HPK</p>

                    <!-- Modal Lightbox / PDF Viewer -->
                    <div x-show="zoomModalOpen" 
                         x-cloak
                         style="display: none;"
                         class="fixed inset-0 z-50 overflow-y-auto"
                         role="dialog" aria-modal="true">
                        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" 
                                 @click="zoomModalOpen = false; zoomLevel = 1.0"></div>

                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:w-full border border-slate-200 @if($componentItem->is_pdf_drawing) sm:max-w-4xl @else sm:max-w-2xl @endif">
                                <!-- Modal Header -->
                                <div class="bg-slate-50 px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        @if($componentItem->is_pdf_drawing)
                                            <span class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold rounded-md uppercase">
                                                <i class="fa-solid fa-file-pdf me-1"></i> CAD PDF
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded-md uppercase">
                                                <i class="fa-solid fa-image me-1"></i> Gambar Teknik
                                            </span>
                                        @endif
                                        <div>
                                            <h3 class="text-sm font-bold text-slate-900">{{ $componentItem->name }}</h3>
                                            <p class="font-mono text-xs text-slate-500 font-semibold">{{ $componentItem->part_number }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-1.5">
                                        <a href="{{ $componentItem->image_url }}" target="_blank" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-xs font-semibold transition flex items-center space-x-1 shadow-2xs" title="Buka di Tab Baru">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                            <span class="hidden sm:inline">Tab Baru</span>
                                        </a>
                                        <button type="button" @click="zoomModalOpen = false; zoomLevel = 1.0" class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 transition cursor-pointer">
                                            <i class="fa-solid fa-xmark text-lg"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Modal Content -->
                                <div class="p-4 sm:p-6 text-center bg-slate-50/50">
                                    @if($componentItem->is_pdf_drawing)
                                        <div class="w-full h-[65vh] rounded-xl border border-slate-300 bg-white shadow-inner overflow-hidden">
                                            <iframe src="{{ $componentItem->image_url }}" class="w-full h-full border-0 rounded-xl" title="Drawing PDF Viewer"></iframe>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between mb-3 px-1">
                                            <span class="text-[11px] font-medium text-slate-500">
                                                Kontrol pembesaran gambar teknik:
                                            </span>
                                            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-2xs space-x-1">
                                                <button type="button" 
                                                        @click="if(zoomLevel > 0.6) zoomLevel -= 0.2"
                                                        class="px-2.5 py-1 text-slate-700 hover:bg-slate-100 rounded-lg text-xs font-bold transition cursor-pointer"
                                                        title="Perkecil (-)">
                                                    <i class="fa-solid fa-magnifying-glass-minus"></i>
                                                </button>
                                                <span class="px-2 text-xs font-mono font-bold text-slate-600" x-text="Math.round(zoomLevel * 100) + '%'"></span>
                                                <button type="button" 
                                                        @click="if(zoomLevel < 3.0) zoomLevel += 0.2"
                                                        class="px-2.5 py-1 text-slate-700 hover:bg-slate-100 rounded-lg text-xs font-bold transition cursor-pointer"
                                                        title="Perbesar (+)">
                                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                                </button>
                                                <button type="button" 
                                                        @click="zoomLevel = 1.0"
                                                        class="px-2 py-1 text-[11px] text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg font-semibold transition cursor-pointer"
                                                        title="Reset Ukuran">
                                                    Reset
                                                </button>
                                            </div>
                                        </div>

                                        <div class="max-h-[60vh] overflow-auto rounded-xl border border-slate-200 bg-white flex items-center justify-center p-3 shadow-inner">
                                            <img src="{{ $componentItem->image_url }}" 
                                                 alt="{{ $componentItem->name }}" 
                                                 :style="'transform: scale(' + zoomLevel + '); transform-origin: center center; transition: transform 0.15s ease-out;'"
                                                 class="max-h-[55vh] max-w-full object-contain mx-auto rounded-lg select-none">
                                        </div>
                                    @endif

                                    <div class="mt-3.5 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-600">
                                        <span class="px-3 py-1 bg-white border border-slate-200 rounded-lg font-mono font-medium shadow-2xs">
                                            Dimensi: {{ $componentItem->dimension_formatted ?: '-' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Modal Footer -->
                                <div class="bg-slate-50 px-5 py-3 border-t border-slate-200 flex items-center justify-between">
                                    <span class="text-[11px] text-slate-400 italic">Pratinjau gambar teknik luring (D:\Ali Magang\KOMPONEN)</span>
                                    <button type="button" @click="zoomModalOpen = false; zoomLevel = 1.0" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pure SVG QR Code Card -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col items-center text-center">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 w-full text-left">Label QR Resmi HPK</h3>
                    <div class="w-44 h-44 p-2 bg-white rounded-xl border-2 border-slate-900 shadow-sm flex items-center justify-center">
                        <div class="w-full h-full">
                            {!! $componentItem->qr_code_svg !!}
                        </div>
                    </div>
                    <span class="font-mono text-xs font-bold text-slate-900 mt-3">{{ $componentItem->qr_code_payload }}</span>
                    <p class="text-[10px] text-slate-400 mt-0.5">Dapat dibaca oleh Scanner Kamera & Barcode Scanner Gun</p>
                    <div class="mt-4 flex space-x-2 w-full">
                        <a href="{{ route('components.qr-label', $componentItem) }}" target="_blank" class="flex-1 py-2 text-center bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl transition">
                            Print Label
                        </a>
                        <a href="{{ route('qr-requests.create', ['component_id' => $componentItem->id]) }}" class="flex-1 py-2 text-center bg-cyan-50 hover:bg-cyan-100 text-cyan-800 border border-cyan-200 font-semibold text-xs rounded-xl transition">
                            Request Batch
                        </a>
                    </div>
                </div>
            </div>

            <!-- Col 2 & 3: Component Specs & Stock Balances -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Specification Card -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Informasi Teknis & Spesifikasi</h3>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 text-[11px] block uppercase font-semibold">Kode Komponen</span>
                            <span class="font-mono font-bold text-slate-900 text-sm mt-0.5 block">{{ $componentItem->part_number }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[11px] block uppercase font-semibold">Satuan (UoM)</span>
                            <span class="font-bold text-slate-900 mt-0.5 block">{{ $componentItem->uom }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[11px] block uppercase font-semibold">Ukuran Dimensi</span>
                            <span class="font-bold font-mono text-slate-900 mt-0.5 block">{{ $componentItem->dimension_formatted }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[11px] block uppercase font-semibold">Batas Minimum (Safety)</span>
                            <span class="font-bold {{ $componentItem->minimum_stock > 0 ? 'text-rose-600' : 'text-slate-500' }} mt-0.5 block">
                                {{ $componentItem->minimum_stock > 0 ? $componentItem->minimum_stock . ' ' . $componentItem->uom : 'Tidak dibatasi' }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <span class="text-slate-400 text-[11px] block uppercase font-semibold mb-1">Keterangan / Catatan Teknis:</span>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs font-mono text-slate-700 leading-relaxed whitespace-pre-line">
                            {{ $componentItem->specification ?: 'Belum ada keterangan atau catatan khusus.' }}
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-slate-400 text-[11px] block uppercase font-semibold">Lokasi Penyimpanan Default:</span>
                            @if ($componentItem->defaultLocation)
                                <span class="font-mono text-xs font-bold text-blue-900 mt-0.5 block">
                                    {{ $componentItem->defaultLocation->full_location_code }} ({{ $componentItem->defaultLocation->zone_name }})
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-lg border border-amber-200 mt-1">
                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Belum Ditempatkan ke Rak (Segera Alokasikan)
                                </span>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="text-slate-400 text-[11px] block uppercase font-semibold">Total Stok Fisik Tersedia:</span>
                            <span class="text-xl font-extrabold {{ $componentItem->is_low_stock ? 'text-rose-600' : 'text-emerald-700' }}">
                                {{ number_format($componentItem->total_stock, 0) }} {{ $componentItem->uom }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stock Balances Per Rack/Location -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Rincian Stok Fisik Berdasarkan Rak / Zona HPK</h3>
                        <span class="text-xs text-slate-500">{{ $componentItem->stockBalances->count() }} Lokasi Terdaftar</span>
                    </div>

                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/50 text-[11px] uppercase tracking-wider text-slate-400 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Zona & Kode Rak</th>
                                <th class="py-3 px-4">Deskripsi Area</th>
                                <th class="py-3 px-4">No. Batch / Lot</th>
                                <th class="py-3 px-4 text-right">Jumlah Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($componentItem->stockBalances as $sb)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                        {{ $sb->location->full_location_code }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-500">
                                        {{ $sb->location->description ?: $sb->location->zone_name }}
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                        {{ $sb->batch_lot_number ?: '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900 text-sm">
                                        {{ number_format($sb->quantity, 0) }} {{ $componentItem->uom }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-400">
                                        Belum ada catatan saldo fisik di rak gudang.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ECR (Engineering Change Request) History -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Riwayat Revisi Komponen (ECR)</h3>
                            <p class="text-[11px] text-slate-400">Catatan perubahan desain, spek dan persetujuan teknik karoseri</p>
                        </div>
                        <a href="{{ route('ecrs.create', ['component_id' => $componentItem->id]) }}" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl transition">
                            Ajukan ECR Baru
                        </a>
                    </div>

                    @if ($componentItem->ecrs->isEmpty())
                        <div class="p-6 text-center text-slate-400 text-xs">
                            Belum ada riwayat permohonan revisi komponen (ECR) untuk part ini.
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 text-xs">
                            @foreach ($componentItem->ecrs as $ecr)
                                <div class="p-4 hover:bg-slate-50/60 transition flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono font-bold text-purple-900">{{ $ecr->ecr_number }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $ecr->status_badge['class'] }}">
                                                {{ $ecr->status_badge['label'] }}
                                            </span>
                                        </div>
                                        <h4 class="font-bold text-slate-900 mt-1">{{ $ecr->title }}</h4>
                                        <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">Alasan: {{ $ecr->reason }}</p>
                                    </div>
                                    <a href="{{ route('ecrs.show', $ecr) }}" class="px-3 py-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700">
                                        Lihat ECR &rarr;
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

        </div>

    </div>
</x-app-layout>

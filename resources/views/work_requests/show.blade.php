<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('work-requests.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                            {{ $workRequest->wri_number }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] {{ $workRequest->priority_badge['class'] }}">
                            {{ $workRequest->priority_badge['label'] }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] border {{ $workRequest->status_badge['class'] }}">
                            {{ $workRequest->status_badge['label'] }}
                        </span>
                    </div>
                    <h2 class="font-extrabold text-2xl text-slate-900 leading-tight mt-1">
                        {{ $workRequest->component->name }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Diajukan oleh: <span class="font-semibold text-slate-700">{{ $workRequest->requestedBy->name }}</span> &bull; 
                        Tanggal: {{ $workRequest->created_at->format('d M Y, H:i') }}
                        @if($workRequest->due_date)
                            &bull; Target Selesai: <span class="font-bold text-amber-800">{{ $workRequest->due_date->format('d M Y') }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                @if($workRequest->status === 'ready_for_warehouse')
                    <a href="#putaway-section" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl shadow-md transition flex items-center animate-pulse">
                        <i class="fa-solid fa-boxes-packing me-1.5 text-sm"></i>
                        Terima di Gudang
                    </a>
                @endif
                @if($workRequest->status === 'received' && $workRequest->targetLocation)
                    <a href="{{ route('warehouse-map.index') }}" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center">
                        <i class="fa-solid fa-map-location-dot me-1.5"></i>
                        Lihat Slot di Peta Gudang
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- 1. READY FOR WAREHOUSE PUTAWAY HERO BANNER (When all machine steps completed) -->
        @if($workRequest->status === 'ready_for_warehouse')
        <div id="putaway-section" class="bg-gradient-to-r from-emerald-600 to-teal-700 rounded-3xl p-6 text-white shadow-lg space-y-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold mb-2">
                        <i class="fa-solid fa-check-circle me-1.5 text-amber-300"></i> Produksi Mesin Selesai & Lolos Fabrikasi!
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black">
                        Barang Telah Tiba dari Machine Center &bull; Siap Diterima di Gudang
                    </h3>
                    <p class="text-xs text-emerald-100 mt-1 max-w-2xl leading-relaxed">
                        Seluruh tahapan mesin (Potong Laser & Bending) telah tuntas. Konfirmasikan penerimaan fisik komponen ke dalam rak/pallet gudang untuk mengupdate stok secara otomatis.
                    </p>
                </div>

                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 text-center flex-shrink-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-200 block">Jumlah Siap Simpan</span>
                    <span class="text-3xl font-black text-white block mt-0.5">
                        {{ number_format($workRequest->quantity_produced, 0) }} <span class="text-sm font-normal text-emerald-100">{{ $workRequest->component->uom }}</span>
                    </span>
                    <span class="text-[11px] text-emerald-200 mt-0.5 block font-mono">
                        Slot: {{ $workRequest->target_specific_location_code }}
                    </span>
                </div>
            </div>

            <!-- Inline Putaway Form -->
            <form method="POST" action="{{ route('work-requests.receive', $workRequest) }}" class="bg-white text-slate-800 rounded-2xl p-5 shadow-inner space-y-4 border border-emerald-300">
                @csrf
                <div class="flex items-center space-x-2 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-boxes-packing text-emerald-600 text-sm"></i>
                    <h4 class="font-extrabold text-sm text-slate-900">Form Verifikasi & Putaway Stok Gudang</h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                    <!-- Qty Received -->
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                            Jumlah Aktual Diterima <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="quantity_received" 
                               required 
                               step="any" 
                               min="0.1" 
                               value="{{ $workRequest->quantity_produced ?: $workRequest->quantity_requested }}" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-black text-slate-900 text-sm">
                    </div>

                    <!-- Target Location -->
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                            Rak / Area Simpan <span class="text-rose-500">*</span>
                        </label>
                        <select name="target_location_id" required class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-semibold">
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ $workRequest->target_location_id == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->full_location_code }} ({{ $loc->display_rack_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Shelf Level -->
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                            Lantai Rak
                        </label>
                        <select name="target_shelf_level" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono">
                            <option value="L1" {{ $workRequest->target_shelf_level === 'L1' ? 'selected' : '' }}>L1 (Bawah - Beban Berat)</option>
                            <option value="L2" {{ $workRequest->target_shelf_level === 'L2' ? 'selected' : '' }}>L2</option>
                            <option value="L3" {{ ($workRequest->target_shelf_level === 'L3' || !$workRequest->target_shelf_level) ? 'selected' : '' }}>L3 (Tengah/Atas)</option>
                            <option value="L4" {{ $workRequest->target_shelf_level === 'L4' ? 'selected' : '' }}>L4</option>
                        </select>
                    </div>

                    <!-- Slot Number -->
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                            Nomor Slot Kompartemen
                        </label>
                        <input type="text" 
                               name="target_slot_number" 
                               value="{{ $workRequest->target_slot_number ?: '05' }}" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-1">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                            Nomor Batch / Lot Produksi
                        </label>
                        <input type="text" 
                               name="batch_lot_number" 
                               value="LOT-{{ date('Ym') }}-WRI{{ $workRequest->id }}" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                            Catatan Penerimaan Gudang
                        </label>
                        <input type="text" 
                               name="notes" 
                               placeholder="Kondisi komponen baik, dimensi sesuai..." 
                               class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-md transition flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check text-sm"></i>
                        <span>Konfirmasi Penerimaan Gudang & Update Stok</span>
                    </button>
                </div>
            </form>
        </div>
        @endif

        <!-- ALREADY RECEIVED BANNER -->
        @if($workRequest->status === 'received')
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start space-x-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 text-lg font-bold shadow-xs">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-emerald-950">Komponen Telah Diterima Lengkap di Gudang</h4>
                    <p class="text-xs text-emerald-700 mt-0.5">
                        Diterima oleh <span class="font-bold">{{ $workRequest->receivedBy?->name ?? 'Petugas Gudang' }}</span> pada 
                        {{ $workRequest->received_at?->format('d M Y, H:i') ?? '-' }}. Stok telah terupdate otomatis di rak penyimpanan.
                    </p>
                    <div class="mt-2 flex items-center space-x-2 text-xs">
                        <span class="px-2 py-0.5 bg-white border border-emerald-300 rounded font-mono font-bold text-emerald-900">
                            Slot: {{ $workRequest->target_specific_location_code }}
                        </span>
                        <span class="font-bold text-emerald-800">
                            +{{ number_format($workRequest->quantity_received, 0) }} {{ $workRequest->component->uom }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <a href="{{ route('warehouse-map.index') }}" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center">
                    <i class="fa-solid fa-map-location-dot me-1.5"></i>
                    Buka Peta Gudang
                </a>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- COL 1 & 2: MACHINE ROUTING INTERACTIVE STEPPER -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Stepper Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-timeline text-slate-800"></i>
                            <h3 class="font-extrabold text-base text-slate-900">Alur Routing Pengerjaan Mesin Utama (Machine Center)</h3>
                        </div>
                        <span class="text-xs font-bold text-slate-500">
                            Progres: {{ $workRequest->progress_percentage }}%
                        </span>
                    </div>

                    <!-- Visual Timeline Steps -->
                    <div class="space-y-6 relative before:absolute before:inset-0 before:left-4 before:w-0.5 before:bg-slate-200">
                        @foreach($workRequest->steps as $step)
                        <div class="relative flex items-start space-x-4">
                            <!-- Step Bullet Icon -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 z-10 shadow-xs {{ $step->status === 'completed' ? 'bg-emerald-600 text-white' : ($step->status === 'in_progress' ? 'bg-blue-600 text-white ring-4 ring-blue-100' : 'bg-white border-2 border-slate-300 text-slate-500') }}">
                                @if($step->status === 'completed')
                                    <i class="fa-solid fa-check"></i>
                                @else
                                    {{ $step->step_number }}
                                @endif
                            </div>

                            <!-- Step Content Card -->
                            <div class="flex-1 bg-slate-50 rounded-2xl p-4 border {{ $step->status === 'in_progress' ? 'border-blue-300 bg-blue-50/40 ring-1 ring-blue-300' : 'border-slate-200' }} space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono text-xs font-bold text-blue-900 bg-blue-100/70 px-2 py-0.5 rounded">
                                                {{ $step->machine->code }}
                                            </span>
                                            <span class="text-xs text-slate-500 font-semibold">{{ $step->machine->name }}</span>
                                        </div>
                                        <h4 class="font-extrabold text-sm text-slate-900 mt-1">
                                            {{ $step->process_name }}
                                        </h4>
                                    </div>

                                    <div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $step->status_badge['class'] }}">
                                            @if($step->status === 'in_progress')
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-ping me-1.5"></span>
                                            @endif
                                            {{ $step->status_badge['label'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-[11px] text-slate-500 pt-2 border-t border-slate-200/60">
                                    <div>
                                        <span class="text-slate-400 block">Operator:</span>
                                        <span class="font-semibold text-slate-700">{{ $step->operator_name ?: ($step->machine->operator_default ?: '-') }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Waktu Mulai:</span>
                                        <span class="font-mono text-slate-700">{{ $step->started_at ? $step->started_at->format('d M, H:i') : '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Waktu Selesai:</span>
                                        <span class="font-mono text-slate-700">{{ $step->completed_at ? $step->completed_at->format('d M, H:i') : '-' }}</span>
                                    </div>
                                </div>

                                @if($step->notes)
                                <p class="text-[11px] text-slate-600 italic bg-white p-2 rounded-lg border border-slate-200">
                                    Catatan: {{ $step->notes }}
                                </p>
                                @endif

                                <!-- Interactive Action Buttons for this Step (For Operator/Gudang) -->
                                @if($workRequest->status !== 'received' && $workRequest->status !== 'cancelled')
                                <div class="pt-2 flex items-center space-x-2">
                                    @if($step->status === 'pending')
                                        <form method="POST" action="{{ route('work-requests.advance-step', [$workRequest, $step]) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="start">
                                            <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5">
                                                <i class="fa-solid fa-play text-[10px]"></i>
                                                <span>Mulai Proses {{ $step->machine->type_label }}</span>
                                            </button>
                                        </form>
                                    @elseif($step->status === 'in_progress')
                                        <form method="POST" action="{{ route('work-requests.advance-step', [$workRequest, $step]) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5">
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                                <span>Selesaikan Proses & Lanjut</span>
                                            </button>
                                        </form>
                                    @elseif($step->status === 'completed')
                                        <span class="text-[11px] text-emerald-700 font-bold flex items-center">
                                            <i class="fa-solid fa-circle-check me-1"></i> Telah tuntas dikerjakan
                                        </span>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        <!-- Final Step: Gudang Putaway -->
                        <div class="relative flex items-start space-x-4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 z-10 shadow-xs {{ $workRequest->status === 'received' ? 'bg-emerald-600 text-white' : 'bg-white border-2 border-slate-300 text-slate-500' }}">
                                <i class="fa-solid fa-warehouse"></i>
                            </div>
                            <div class="flex-1 bg-slate-50 rounded-2xl p-4 border border-slate-200">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-extrabold text-sm text-slate-900">
                                        Penerimaan Gudang & Update Saldo Stok Fisik
                                    </h4>
                                    <span class="text-xs font-bold {{ $workRequest->status === 'received' ? 'text-emerald-700' : 'text-slate-400' }}">
                                        {{ $workRequest->status === 'received' ? 'Selesai' : 'Tahap Akhir' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Komponen disimpan di rak/pallet tujuan (<span class="font-mono font-bold text-blue-900">{{ $workRequest->target_specific_location_code }}</span>) dan siap disuplai ke stasiun kerja perakitan karoseri.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History Related to this WRI -->
                @if($workRequest->transactions->isNotEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
                    <h3 class="font-bold text-sm text-slate-900">Riwayat Mutasi Transaksi Terkait</h3>
                    <div class="divide-y divide-slate-100 text-xs">
                        @foreach($workRequest->transactions as $tx)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <a href="{{ route('transactions.show', $tx) }}" class="font-mono font-bold text-blue-600 hover:text-blue-800">
                                    {{ $tx->transaction_number }}
                                </a>
                                <p class="text-[11px] text-slate-500">{{ $tx->notes }}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $tx->type_badge['class'] }}">
                                    {{ $tx->type_badge['label'] }}
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ $tx->transaction_date->format('d M Y') }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>

            <!-- COL 3: COMPONENT INFO & ORDER DETAILS CARD -->
            <div class="space-y-6">

                <!-- Component Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2">
                        Rincian Komponen
                    </h3>

                    <div class="w-full aspect-video rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center">
                        <img src="{{ $workRequest->component->image_url }}" alt="{{ $workRequest->component->name }}" class="w-full h-full object-cover">
                    </div>

                    <div>
                        <span class="font-mono text-xs font-bold text-amber-900 bg-amber-100/80 px-2 py-0.5 rounded">
                            {{ $workRequest->component->part_number }}
                        </span>
                        <h4 class="font-extrabold text-base text-slate-900 mt-1">
                            {{ $workRequest->component->name }}
                        </h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            {{ $workRequest->component->specification ?: 'Spesifikasi standar karoseri HPK.' }}
                        </p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Satuan UoM:</span>
                            <span class="font-bold text-slate-900">{{ $workRequest->component->uom }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Stok Gudang Saat Ini:</span>
                            <span class="font-bold text-emerald-700">{{ number_format($workRequest->component->total_stock, 0) }} {{ $workRequest->component->uom }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('components.show', $workRequest->component) }}" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition block text-center">
                            Buka Master Komponen &rarr;
                        </a>
                    </div>
                </div>

                <!-- Order Meta Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3 text-xs">
                    <h3 class="font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2 text-[10px]">
                        Informasi Dokumen Order
                    </h3>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Nomor WRI:</span>
                            <span class="font-mono font-bold text-slate-900">{{ $workRequest->wri_number }}</span>
                        </div>
                        @if($workRequest->spk_reference)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Referensi SPK:</span>
                            <span class="font-mono font-bold text-blue-700">{{ $workRequest->spk_reference }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Jumlah Dipesan:</span>
                            <span class="font-extrabold text-slate-900">{{ number_format($workRequest->quantity_requested, 0) }} {{ $workRequest->component->uom }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Target Slot:</span>
                            <span class="font-mono font-bold text-blue-900">{{ $workRequest->target_specific_location_code }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Prioritas:</span>
                            <span class="px-2 py-0.5 rounded text-[10px] {{ $workRequest->priority_badge['class'] }}">
                                {{ $workRequest->priority_badge['label'] }}
                            </span>
                        </div>
                    </div>

                    @if($workRequest->notes)
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-slate-400 text-[10px] uppercase font-bold block mb-1">Catatan Khusus:</span>
                        <p class="text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-[11px] leading-relaxed">
                            {{ $workRequest->notes }}
                        </p>
                    </div>
                    @endif
                </div>

            </div>

        </div>

    </div>
</x-app-layout>

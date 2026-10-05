<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 text-[11px] font-bold uppercase rounded-md bg-amber-100 text-amber-900 border border-amber-300">
                        Machine Center &bull; Fabrikasi In-House
                    </span>
                    <span class="text-xs text-slate-500 font-medium">PT Hydraxle Perkasa Karoseri</span>
                </div>
                <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight mt-1">
                    Work Request Inhouse (WRI)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Permintaan komponen dari Gudang ke Mesin Utama (Laser Cutting &rarr; Bending) hingga serah terima & update stok gudang
                </p>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('work-station-supplies.create') }}" class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-bold rounded-xl shadow-xs transition flex items-center">
                    <i class="fa-solid fa-truck-ramp-box me-1.5 text-sm"></i>
                    Supply Stasiun Kerja
                </a>
                <a href="{{ route('work-requests.create') }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                    <i class="fa-solid fa-plus me-1.5"></i>
                    Buat Work Request Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- 1. KPI SUMMARY STATS CARDS -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center space-x-3">
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0 text-xl font-bold">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Work Request</span>
                    <h3 class="text-2xl font-black text-slate-900 leading-tight mt-0.5">{{ $totalWri }}</h3>
                    <span class="text-[10px] text-slate-500">Semua order mesin</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-blue-200 shadow-xs flex items-center space-x-3">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center flex-shrink-0 text-xl font-bold">
                    <i class="fa-solid fa-gears animate-spin" style="animation-duration: 6s;"></i>
                </div>
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-600">Dalam Proses Mesin</span>
                    <h3 class="text-2xl font-black text-blue-700 leading-tight mt-0.5">{{ $inProductionCount }}</h3>
                    <span class="text-[10px] text-blue-500">Laser & Bending</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-amber-300 bg-amber-50/40 shadow-xs flex items-center space-x-3">
                <div class="w-12 h-12 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center flex-shrink-0 text-xl font-bold">
                    <i class="fa-solid fa-dolly"></i>
                </div>
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800">Siap Terima di Gudang</span>
                    <h3 class="text-2xl font-black text-amber-900 leading-tight mt-0.5">{{ $readyForWarehouseCount }}</h3>
                    <span class="text-[10px] text-amber-700 font-semibold">Menunggu Putaway Stok</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-emerald-200 shadow-xs flex items-center space-x-3">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center flex-shrink-0 text-xl font-bold">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Selesai & Masuk Stok</span>
                    <h3 class="text-2xl font-black text-emerald-700 leading-tight mt-0.5">{{ $receivedCount }}</h3>
                    <span class="text-[10px] text-emerald-600">Stok Gudang Terupdate</span>
                </div>
            </div>
        </div>

        <!-- 2. MACHINE STATUS LIVE PILL STRIP -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-microchip text-slate-700"></i>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Status Mesin Utama (Machine Center)</h4>
                </div>
                <span class="text-[11px] text-slate-400 font-medium">Monitoring utilisasi mesin fabrikasi inhouse</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
                @foreach($activeMachines as $machine)
                <div class="p-2.5 rounded-xl border {{ $machine->active_steps_count > 0 ? 'bg-blue-50 border-blue-300 ring-2 ring-blue-400/30' : 'bg-slate-50 border-slate-200' }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-mono text-[10px] font-bold {{ $machine->active_steps_count > 0 ? 'text-blue-700' : 'text-slate-600' }}">
                                {{ $machine->code }}
                            </span>
                            @if($machine->active_steps_count > 0)
                                <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                            @else
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            @endif
                        </div>
                        <h5 class="text-xs font-bold text-slate-900 line-clamp-1 leading-snug">{{ $machine->name }}</h5>
                    </div>
                    <div class="mt-2 pt-1 border-t border-slate-200/60 flex items-center justify-between text-[10px]">
                        <span class="text-slate-500">{{ $machine->type_label }}</span>
                        <span class="font-bold {{ $machine->active_steps_count > 0 ? 'text-blue-700' : 'text-slate-500' }}">
                            {{ $machine->active_steps_count }} WRI
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- 3. FILTER TABS & SEARCH -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 sm:pb-0">
                <a href="{{ route('work-requests.index') }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ !request()->filled('status') || request()->status === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    Semua ({{ $totalWri }})
                </a>
                <a href="{{ route('work-requests.index', ['status' => 'in_production']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->status === 'in_production' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-gears me-1"></i> Dalam Mesin ({{ $inProductionCount }})
                </a>
                <a href="{{ route('work-requests.index', ['status' => 'ready_for_warehouse']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->status === 'ready_for_warehouse' ? 'bg-amber-500 text-slate-950 font-black shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-bell me-1 animate-bounce"></i> Siap Gudang ({{ $readyForWarehouseCount }})
                </a>
                <a href="{{ route('work-requests.index', ['status' => 'received']) }}" 
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->status === 'received' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-check-double me-1"></i> Diterima ({{ $receivedCount }})
                </a>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('work-requests.index') }}" class="relative w-full sm:w-72">
                @if(request()->filled('status'))
                    <input type="hidden" name="status" value="{{ request()->status }}">
                @endif
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request()->search }}" 
                       placeholder="Cari No. WRI, komponen, SPK..." 
                       class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
            </form>
        </div>

        <!-- 4. WORK REQUESTS TABLE -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/70 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">No. WRI & Tanggal</th>
                            <th class="py-3.5 px-4">Komponen & Spek</th>
                            <th class="py-3.5 px-4 text-center">Jumlah Order</th>
                            <th class="py-3.5 px-4">Alur Routing Mesin</th>
                            <th class="py-3.5 px-4">Target Slot Gudang</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($workRequests as $wri)
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- No WRI & Meta -->
                            <td class="py-3.5 px-4 align-top">
                                <a href="{{ route('work-requests.show', $wri) }}" class="font-mono font-bold text-slate-900 hover:text-blue-600 text-xs flex items-center">
                                    {{ $wri->wri_number }}
                                </a>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $wri->created_at->format('d M Y, H:i') }}
                                </p>
                                @if($wri->spk_reference)
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded bg-slate-100 font-mono text-[10px] text-slate-600 font-semibold">
                                        {{ $wri->spk_reference }}
                                    </span>
                                @endif
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] {{ $wri->priority_badge['class'] }}">
                                        {{ $wri->priority_badge['label'] }}
                                    </span>
                                </div>
                            </td>

                            <!-- Komponen & Spek -->
                            <td class="py-3.5 px-4 align-top max-w-xs">
                                <div class="flex items-start space-x-2.5">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                        <img src="{{ $wri->component->image_url }}" alt="{{ $wri->component->name }}" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <a href="{{ route('components.show', $wri->component) }}" class="font-bold text-slate-900 hover:text-teal-700 block line-clamp-1 leading-snug">
                                            {{ $wri->component->name }}
                                        </a>
                                        <span class="font-mono text-[11px] font-semibold text-amber-900 bg-amber-100/70 px-1.5 py-0.2 rounded mt-0.5 inline-block">
                                            {{ $wri->component->part_number }}
                                        </span>
                                        <p class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">
                                            {{ $wri->component->specification ?: '-' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Qty Info -->
                            <td class="py-3.5 px-4 align-top text-center">
                                <div class="font-extrabold text-sm text-slate-900">
                                    {{ number_format($wri->quantity_requested, 0) }} <span class="text-xs font-normal text-slate-500">{{ $wri->component->uom }}</span>
                                </div>
                                @if($wri->quantity_received > 0)
                                    <span class="text-[10px] text-emerald-700 font-bold block mt-0.5">
                                        Masuk: {{ number_format($wri->quantity_received, 0) }} {{ $wri->component->uom }}
                                    </span>
                                @elseif($wri->quantity_produced > 0)
                                    <span class="text-[10px] text-blue-700 font-semibold block mt-0.5">
                                        Selesai: {{ number_format($wri->quantity_produced, 0) }} {{ $wri->component->uom }}
                                    </span>
                                @endif
                            </td>

                            <!-- Alur Routing Mesin Steps -->
                            <td class="py-3.5 px-4 align-top">
                                <div class="space-y-1.5 min-w-[220px]">
                                    @foreach($wri->steps as $st)
                                    <div class="flex items-center space-x-1.5 text-[11px]">
                                        <span class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-bold {{ $st->status === 'completed' ? 'bg-emerald-500 text-white' : ($st->status === 'in_progress' ? 'bg-blue-600 text-white ring-2 ring-blue-300' : 'bg-slate-200 text-slate-600') }}">
                                            {{ $st->step_number }}
                                        </span>
                                        <span class="font-semibold text-slate-800 truncate max-w-[140px]">{{ $st->machine->code }}: {{ $st->process_name }}</span>
                                        <span class="text-[9px] px-1.5 py-0.2 rounded font-bold uppercase {{ $st->status_badge['class'] }} ml-auto">
                                            {{ $st->status_badge['label'] }}
                                        </span>
                                    </div>
                                    @endforeach
                                </div>

                                <!-- Progress Bar -->
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                                    <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $wri->progress_percentage }}%"></div>
                                </div>
                            </td>

                            <!-- Target Slot Gudang -->
                            <td class="py-3.5 px-4 align-top">
                                @if($wri->targetLocation)
                                    <div class="flex items-center space-x-1.5">
                                        <span class="px-2 py-0.5 bg-blue-50 border border-blue-200 rounded font-mono font-bold text-xs text-blue-900">
                                            {{ $wri->target_specific_location_code }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 block mt-0.5">
                                        {{ $wri->targetLocation->display_rack_name }} &bull; {{ $wri->targetLocation->zone_name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs italic">Belum ditentukan</span>
                                @endif
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-4 align-top text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $wri->status_badge['class'] }}">
                                    @if($wri->status === 'in_production')
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-ping me-1.5"></span>
                                    @elseif($wri->status === 'ready_for_warehouse')
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-bounce me-1.5"></span>
                                    @elseif($wri->status === 'received')
                                        <i class="fa-solid fa-check me-1 text-[10px]"></i>
                                    @endif
                                    {{ $wri->status_badge['label'] }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 align-top text-right space-y-1.5">
                                @if($wri->status === 'ready_for_warehouse')
                                    <a href="{{ route('work-requests.show', $wri) }}#putaway-modal" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow transition animate-pulse">
                                        <i class="fa-solid fa-boxes-packing me-1"></i>
                                        Terima di Gudang
                                    </a>
                                @endif
                                <a href="{{ route('work-requests.show', $wri) }}" class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                                    Lihat Detail &rarr;
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-300 flex items-center justify-center mx-auto mb-3 text-2xl">
                                    <i class="fa-solid fa-industry"></i>
                                </div>
                                <p class="text-sm font-bold text-slate-700">Belum ada data Work Request Inhouse</p>
                                <p class="text-xs text-slate-400 mt-1">Gudang dapat membuat order komponen ke Machine Center dengan tombol di atas.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($workRequests->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $workRequests->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>

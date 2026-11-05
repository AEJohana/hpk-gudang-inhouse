@php
    $isIndex = request()->routeIs('work-requests.index');
    $isSafety = request()->routeIs('work-requests.safety-stock');
    $isPrint = request()->routeIs('work-requests.print-queue');
    $isMonitoring = request()->routeIs('work-requests.monitoring');

    $printBadge = $printableCount ?? $totalPrintable ?? null;
    $criticalBadge = $totalCritical ?? null;
@endphp

<!-- WRI SEGMENTED SUB-NAVIGATION CARD (ELEVATED WHITE PILL CONTAINER - ADAPTIVE COLLAPSIBLE) -->
<div class="flex items-center justify-between gap-2.5 sm:gap-4 border-b border-slate-200/80 pb-3 sm:pb-4 w-full">
    <!-- Left: Segmented Tabs Container (w-fit shrink-0 to prevent stretching in tablet/mobile) -->
    <div class="bg-white p-1 sm:p-1.5 rounded-2xl border border-slate-200/80 shadow-xs sm:shadow-sm inline-flex items-center gap-1 sm:gap-1.5 w-fit shrink-0 max-w-full overflow-x-auto no-scrollbar">
        <!-- Tab 1: Antrean WRI -->
        <a href="{{ route('work-requests.index') }}" 
           class="{{ $isIndex 
                ? 'bg-[#0a2342] text-white shadow-xs px-3 sm:px-3.5 py-1.5 sm:py-2' 
                : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/80 px-2.5 sm:px-3 py-1.5 sm:py-2' }} 
                rounded-xl text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap group cursor-pointer"
           title="Antrean Tiket Work Request Inhouse">
            <i class="fa-solid fa-list-check text-xs {{ $isIndex ? 'text-amber-400' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
            @if($isIndex)
                <span>Antrean WRI</span>
            @endif
        </a>

        <!-- Tab 2: Safety Stock -->
        @can('work-request.safety-stock')
        <a href="{{ route('work-requests.safety-stock') }}" 
           class="{{ $isSafety 
                ? 'bg-[#0a2342] text-white shadow-xs px-3 sm:px-3.5 py-1.5 sm:py-2' 
                : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/80 px-2.5 sm:px-3 py-1.5 sm:py-2' }} 
                rounded-xl text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap group cursor-pointer"
           title="Safety Stock Buffer">
            <i class="fa-solid fa-shield-halved text-xs {{ $isSafety ? 'text-amber-400' : 'text-rose-500' }}"></i>
            @if($isSafety)
                <span>Safety Stock</span>
            @endif
            @if(isset($criticalBadge) && $criticalBadge > 0)
                <span class="min-w-[18px] h-[18px] px-1.5 inline-flex items-center justify-center rounded-full text-[10px] font-black {{ $isSafety ? 'bg-amber-400 text-slate-950 shadow-2xs' : 'bg-rose-100 text-rose-700 border border-rose-200/70' }}">
                    {{ $criticalBadge }}
                </span>
            @endif
        </a>
        @endcan

        <!-- Tab 3: Cetak WR -->
        @can('work-request.print')
        <a href="{{ route('work-requests.print-queue') }}" 
           class="{{ $isPrint 
                ? 'bg-[#0a2342] text-white shadow-xs px-3 sm:px-3.5 py-1.5 sm:py-2' 
                : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/80 px-2.5 sm:px-3 py-1.5 sm:py-2' }} 
                rounded-xl text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap group cursor-pointer"
           title="Antrean Dokumen Siap Cetak (3 Rangkap)">
            <i class="fa-solid fa-print text-xs {{ $isPrint ? 'text-amber-400' : 'text-slate-500 group-hover:text-indigo-600' }}"></i>
            @if($isPrint)
                <span>Cetak WR</span>
            @endif
            @if(isset($printBadge) && $printBadge > 0)
                <span class="min-w-[18px] h-[18px] px-1.5 inline-flex items-center justify-center rounded-full text-[10px] font-black {{ $isPrint ? 'bg-amber-400 text-slate-950 shadow-2xs' : 'bg-indigo-50 text-indigo-700 border border-indigo-200/70' }}">
                    {{ $printBadge }}
                </span>
            @endif
        </a>
        @endcan

        <!-- Tab 4: Monitoring SLA (Hanya tampil jika route aktif) -->
        @if($isMonitoring)
        <a href="{{ route('work-requests.monitoring') }}" 
           class="bg-[#0a2342] text-white shadow-xs px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer">
            <i class="fa-solid fa-chart-line text-xs text-amber-400"></i>
            <span>Monitoring SLA</span>
        </a>
        @endif
    </div>

    <!-- Right: Contextual Status / Department Pill -->
    @if($isSafety && isset($totalCritical))
        <div class="flex items-center space-x-2 text-xs shrink-0 ml-auto">
            <span class="text-slate-400 font-semibold hidden sm:inline">Status:</span>
            <span class="px-2.5 py-1.5 rounded-xl bg-white border border-rose-200 text-rose-700 font-extrabold flex items-center space-x-1.5 shadow-xs">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>{{ $totalCritical }} Komponen Perlu Restock</span>
            </span>
        </div>
    @elseif($isMonitoring)
        <div class="flex items-center space-x-2 text-xs shrink-0 ml-auto">
            <span class="text-slate-400 font-semibold hidden sm:inline">Toleransi:</span>
            <span class="px-2.5 py-1.5 rounded-xl bg-white border border-amber-200 text-amber-800 font-extrabold flex items-center space-x-1.5 shadow-xs">
                <i class="fa-solid fa-clock text-amber-600"></i>
                <span>Maksimal 72 Jam (3 Hari)</span>
            </span>
        </div>
    @else
        <div class="flex items-center space-x-2 text-xs shrink-0 ml-auto">
            <span class="text-slate-400 font-semibold hidden sm:inline">Departemen:</span>
            <span class="px-2.5 py-1.5 rounded-xl bg-white border border-slate-200/80 text-slate-800 font-extrabold flex items-center space-x-1.5 shadow-xs">
                <i class="fa-solid fa-building-user text-indigo-600"></i>
                <span>{{ Auth::user()->effective_department_name }}</span>
                @if(Auth::user()->canAccessAllDepartments())
                    <span class="text-[10px] text-teal-600 font-bold ml-1">(Akses Global *)</span>
                @endif
            </span>
        </div>
    @endif
</div>

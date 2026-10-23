<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 text-[11px] font-bold uppercase rounded-md bg-amber-100 text-amber-900 border border-amber-300">
                        WRI &bull; Alur Multi-Routing & Parsial
                    </span>
                    <span class="text-xs text-slate-500 font-medium">PT Hydraxle Perkasa Karoseri</span>
                </div>
                <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight mt-1">
                    Work Request Inhouse (WRI)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Permintaan komponen fabrikasi in-house dengan alur multi-mesin, pencatatan parsial, koreksi defect, hingga serah terima & auto-update stok gudang
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('work-station-supplies.index') }}" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center">
                    <i class="fa-solid fa-truck-ramp-box me-1.5 text-sm"></i>
                    Material Handling Center
                </a>
                @can('work-request.create')
                <a href="{{ route('work-requests.create') }}" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                    <i class="fa-solid fa-plus me-1.5"></i>
                    Membuat WR
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
         x-data="wriQueueManager()"
         @if(isset($openDetailPayload) && $openDetailPayload)
             x-init="openDetailModal(@json($openDetailPayload))"
         @endif>

        <!-- 0. INTEGRATED WRI SUB-NAVIGATION TABS (Antrian, Safety Stock, Monitoring) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-3">
            <div class="flex items-center space-x-2">
                <a href="{{ route('work-requests.index') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 {{ request()->routeIs('work-requests.index') ? 'bg-[#0a2342] text-amber-400 shadow-md' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-list-check text-xs"></i>
                    <span>Antrian & Tiket WRI</span>
                </a>
                @can('work-request.safety-stock')
                <a href="{{ route('work-requests.safety-stock') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-shield-halved text-xs text-rose-500"></i>
                    <span>Safety Stock Alert</span>
                </a>
                @endcan
            </div>

            <div class="flex items-center space-x-2 text-xs">
                <span class="text-slate-400 font-semibold">Departemen Anda:</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-800 font-extrabold flex items-center space-x-1">
                    <i class="fa-solid fa-building-user text-amber-600"></i>
                    <span>{{ Auth::user()->effective_department_name }}</span>
                    @if(Auth::user()->canAccessAllDepartments())
                        <span class="text-[10px] text-teal-600 font-bold ml-1">(Akses Global *)</span>
                    @endif
                </span>
            </div>
        </div>

        <!-- 1. KPI SUMMARY STATS CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center space-x-3">
                <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total WRI</span>
                    <h3 class="text-xl font-black text-slate-900 leading-tight mt-0.5">{{ $totalWri }}</h3>
                    <span class="text-[10px] text-slate-500">Semua tiket order</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center space-x-3">
                <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Antrian Mesin</span>
                    <h3 class="text-xl font-black text-slate-900 leading-tight mt-0.5">{{ $inAntrianCount }}</h3>
                    <span class="text-[10px] text-slate-500">Menunggu diproses</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-blue-200 shadow-xs flex items-center space-x-3">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                    <i class="fa-solid fa-gears animate-spin" style="animation-duration: 6s;"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600">Sedang Proses</span>
                    <h3 class="text-xl font-black text-blue-700 leading-tight mt-0.5">{{ $inProductionCount }}</h3>
                    <span class="text-[10px] text-blue-500">In / Out / MH Pickup</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-amber-300 bg-amber-50/40 shadow-xs flex items-center space-x-3">
                <div class="w-11 h-11 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                    <i class="fa-solid fa-dolly"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Siap Diambil</span>
                    <h3 class="text-xl font-black text-amber-900 leading-tight mt-0.5">{{ $readyForWarehouseCount }}</h3>
                    <span class="text-[10px] text-amber-700 font-semibold">Siap Diantar</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 border border-emerald-200 shadow-xs flex items-center space-x-3 col-span-2 sm:col-span-1">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Sudah Diambil</span>
                    <h3 class="text-xl font-black text-emerald-700 leading-tight mt-0.5">{{ $receivedCount }}</h3>
                    <span class="text-[10px] text-emerald-600">Stok Fisik Masuk</span>
                </div>
            </div>
        </div>

        <!-- 2. SCOPE SELECTOR & STATUS FILTER TABS -->
        <div class="space-y-3">
            <!-- Scope Switcher: Departemen Saya vs Perlu Verifikasi vs Semua Dept -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-100/90 p-1.5 rounded-2xl border border-slate-200">
                <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 sm:pb-0">
                    <a href="{{ route('work-requests.index', ['scope' => 'my_dept']) }}" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap {{ $scope === 'my_dept' ? 'bg-white text-slate-900 shadow-xs border border-slate-300' : 'text-slate-600 hover:text-slate-900' }}">
                        <i class="fa-solid fa-user-tag text-xs text-amber-500"></i>
                        <span>Tiket Departemen Saya</span>
                    </a>

                    <a href="{{ route('work-requests.index', ['scope' => 'needs_approval']) }}" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap {{ $scope === 'needs_approval' ? 'bg-amber-400 text-slate-950 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <i class="fa-solid fa-stamp text-xs"></i>
                        <span>Perlu Verifikasi Saya</span>
                        @if($needsApprovalCount > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-rose-600 text-white ml-1">
                                {{ $needsApprovalCount }}
                            </span>
                        @endif
                    </a>

                    @if(Auth::user()->canAccessAllDepartments())
                    <a href="{{ route('work-requests.index', ['scope' => 'all']) }}" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap {{ $scope === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        <i class="fa-solid fa-globe text-xs text-teal-400"></i>
                        <span>Semua 15 Departemen (*)</span>
                    </a>
                    @endif
                </div>

                @if($overdueSlaCount > 0)
                <div class="flex items-center space-x-1.5 px-3 py-1 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold self-start sm:self-auto">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                    <span>{{ $overdueSlaCount }} Tiket Melewati SLA > 72 Jam</span>
                </div>
                @endif
            </div>

            <!-- Status Tabs & Search -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 sm:pb-0">
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'all'])) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ !request()->filled('status') || request()->status === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        Semua ({{ $totalWri }})
                    </a>
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'antrian'])) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->status === 'antrian' ? 'bg-slate-700 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-clock me-1 text-[10px]"></i> Antrian ({{ $inAntrianCount }})
                    </a>
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'proses'])) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->status === 'proses' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-gears me-1"></i> Sedang Proses ({{ $inProductionCount }})
                    </a>
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'siap'])) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->status === 'siap' ? 'bg-amber-500 text-slate-950 font-black shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-dolly me-1"></i> Siap Diambil ({{ $readyForWarehouseCount }})
                    </a>
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'parsial'])) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->status === 'parsial' ? 'bg-orange-500 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-layer-group me-1"></i> Parsial ({{ $partialCount }})
                    </a>
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'selesai'])) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ request()->status === 'selesai' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-check-double me-1"></i> Selesai ({{ $receivedCount }})
                    </a>
                </div>

                <!-- Search Form -->
                <form method="GET" action="{{ route('work-requests.index') }}" class="relative w-full sm:w-72">
                    <input type="hidden" name="scope" value="{{ $scope }}">
                    @if(request()->filled('status'))
                        <input type="hidden" name="status" value="{{ request()->status }}">
                    @endif
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request()->search }}" 
                           placeholder="Cari No. WRI, komponen, SPK, Dept..." 
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                </form>
            </div>
        </div>

        <!-- 3. WORK REQUESTS (DUAL-MODE: MOBILE CARDS & DESKTOP TABLE) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <!-- Mobile Work Request Cards (< md screens) -->
            <div class="block md:hidden p-4 space-y-3">
                @forelse($workRequests as $wri)
                @php
                    $userActions = $wri->getAvailableActionsForUser(auth()->user());
                    $hasUserAction = !empty($userActions);
                    $primaryAction = $hasUserAction ? $userActions[0] : null;

                    $nextStatus = $primaryAction ? $primaryAction['target_status'] : $wri->getNextStatusLabel(auth()->user());
                    $accumReceived = (float) $wri->quantity_received;
                    $remainingQty = $primaryAction ? $primaryAction['quantity'] : max(0, (float) $wri->quantity_requested - $accumReceived);

                    $isLaserIn = stripos($nextStatus, '(In)') !== false;
                    $isLaserOut = stripos($nextStatus, '(Out)') !== false || ($primaryAction && $primaryAction['type'] === 'out');
                    $isPickup = stripos($nextStatus, 'Pickup') !== false;
                    $isMenuju = stripos($nextStatus, 'Menuju Pengaju') !== false;
                    $isSudah = stripos($nextStatus, 'Sudah Diambil') !== false;

                    $btnBg = $isSudah ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 
                             ($isMenuju ? 'bg-blue-600 hover:bg-blue-700 text-white' : 
                             ($isPickup ? 'bg-amber-500 hover:bg-amber-600 text-slate-950 font-black' : 
                             ($isLaserOut ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-teal-600 hover:bg-teal-700 text-white')));

                    $btnIcon = $primaryAction ? $primaryAction['icon'] : ($isSudah ? 'fa-warehouse' : ($isMenuju ? 'fa-truck-fast' : ($isPickup ? 'fa-truck-ramp-box' : ($isLaserOut ? 'fa-check' : 'fa-play'))));
                    $shortLabel = $primaryAction ? $primaryAction['label'] : ($isSudah ? 'Terima di Gudang' : ($isMenuju ? 'Kirim ke Pengaju' : ($isPickup ? 'MH Pickup' : ($isLaserOut ? 'Selesai: ' . $nextStatus : 'Mulai: ' . $nextStatus))));

                    $canApprovePengaju = $wri->canUserApprovePengaju(auth()->user());
                    $canApprovePenerima = $wri->canUserApprovePenerima(auth()->user());
                    $canProcessAction = $hasUserAction ? true : (auth()->user()->isAdmin() && $wri->canUserProcessAction(auth()->user(), $nextStatus));

                    $assignPayload = [
                        'id' => $wri->id,
                        'wriNumber' => $wri->wri_number,
                        'componentName' => $wri->component?->name ?? 'Komponen',
                        'partNumber' => $wri->component?->part_number ?? '-',
                        'deptPengaju' => $wri->dept_pengaju ?: 'Gudang',
                        'deptPenerima' => $wri->dept_penerima ?: 'Mesin',
                        'totalQty' => (float) $wri->quantity_requested,
                        'uom' => $wri->component?->uom ?? 'PCS',
                        'assignedOperator' => $wri->assigned_operator_name ?? '',
                        'actionUrl' => route('work-requests.approve-penerima', $wri),
                    ];

                    $processPayload = [
                        'id' => $wri->id,
                        'wriNumber' => $wri->wri_number,
                        'componentName' => $wri->component?->name ?? 'Komponen',
                        'partNumber' => $wri->component?->part_number ?? '-',
                        'uom' => $wri->component?->uom ?? 'PCS',
                        'currentStatus' => $wri->status,
                        'nextStatus' => $nextStatus,
                        'nextActionLabel' => $shortLabel,
                        'totalQty' => (float) $wri->quantity_requested,
                        'remainingQty' => $remainingQty > 0 ? $remainingQty : (float) $wri->quantity_requested,
                        'currentBatchQty' => (float) $wri->current_batch_qty,
                        'targetRack' => $wri->target_specific_location_code ?: 'Belum di Rak',
                        'assignedOperator' => $wri->assigned_operator_name ?? '',
                        'actionUrl' => route('work-requests.process-action', $wri),
                    ];

                    $appPengajuLog = $wri->approval_pengaju_log;
                    $appPenerimaLog = $wri->approval_penerima_log;

                    $approvalPengajuStatus = $wri->approval_pengaju ?: 'Menunggu';
                    $approvalPengajuBy = $appPengajuLog?->operator_name 
                        ?? ($wri->approval_pengaju === 'Diterima' ? ($wri->requestedBy?->name ?? 'Ka. ' . ($wri->dept_pengaju ?: 'Pengaju')) : 'Menunggu');
                    $approvalPengajuAt = $appPengajuLog?->created_at?->format('d/m/Y H:i') 
                        ?? ($wri->approval_pengaju === 'Diterima' ? $wri->created_at->format('d/m/Y H:i') : '-');

                    $approvalPenerimaStatus = $wri->approval_penerima ?: 'Menunggu';
                    $approvalPenerimaBy = $appPenerimaLog?->operator_name 
                        ?? ($wri->approval_penerima === 'Diterima' ? 'Ka. ' . ($wri->dept_penerima ?: 'Mesin') : 'Menunggu');
                    $approvalPenerimaAt = $appPenerimaLog?->created_at?->format('d/m/Y H:i') ?? '-';

                    $detailPayload = [
                        'id' => $wri->id,
                        'wriNumber' => $wri->wri_number,
                        'componentName' => $wri->component?->name ?? 'Komponen',
                        'partNumber' => $wri->component?->part_number ?? '-',
                        'dimension' => $wri->component?->dimension_formatted ?? '-',
                        'uom' => $wri->component?->uom ?? 'PCS',
                        'imageUrl' => $wri->component?->image_url ?? asset('images/logo_hpk.webp'),
                        'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($wri->wri_number),
                        'deptPengaju' => $wri->dept_pengaju ?: 'Gudang',
                        'deptPenerima' => $wri->dept_penerima ?: 'Mesin',
                        'priority' => $wri->priority_badge['label'],
                        'priorityClass' => $wri->priority_badge['class'],
                        'currentStatus' => $wri->status,
                        'statusClass' => $wri->status_badge['class'],
                        'targetRack' => $wri->target_specific_location_code ?: 'Belum di Rak',
                        'originalQty' => (float) $wri->original_quantity_requested,
                        'totalQty' => (float) $wri->quantity_requested,
                        'receivedQty' => (float) $wri->quantity_received,
                        'defectQty' => (float) $wri->quantity_defect,
                        'progressPercentage' => $wri->progress_percentage,
                        'routingText' => $wri->routing_text,
                        'requestedByName' => $wri->requestedBy?->name ?? 'Pemohon Gudang',
                        'approvalPengajuStatus' => $approvalPengajuStatus,
                        'approvalPengajuBy' => $approvalPengajuBy,
                        'approvalPengajuAt' => $approvalPengajuAt,
                        'approvalPenerimaStatus' => $approvalPenerimaStatus,
                        'approvalPenerimaBy' => $approvalPenerimaBy,
                        'approvalPenerimaAt' => $approvalPenerimaAt,
                        'assignedOperator' => $wri->assigned_operator_name ?: 'Belum ditentukan',
                        'steps' => $wri->steps->map(fn($s) => [
                            'step' => $s->step_number,
                            'name' => $s->process_name,
                            'status' => $s->status,
                        ])->values(),
                        'logs' => $wri->logs->map(fn($l) => [
                            'time' => $l->created_at->format('d/m/Y H:i'),
                            'status' => $l->status_label,
                            'qty' => (float) $l->quantity,
                            'operator' => $l->operator_name ?: '-',
                            'notes' => $l->notes ?: '-',
                            'is_defect' => $l->action_type === 'defect' || str_contains($l->status_label, 'Defect'),
                        ])->values(),
                        'printUrl' => route('work-requests.print-sheet', $wri),
                        'remainingQty' => $remainingQty > 0 ? $remainingQty : (float) $wri->quantity_requested,
                        'nextStatus' => $nextStatus,
                        'canProcessAction' => $canProcessAction,
                        'actionUrl' => route('work-requests.process-action', $wri),
                        'isFinal' => (bool) $wri->is_final,
                        'actionUrl' => route('work-requests.process-action', $wri),
                        'isFinal' => (bool) $wri->is_final,
                    ];
                @endphp
                <div class="mobile-data-card space-y-3 border border-slate-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <button type="button" 
                                    @click='openDetailModal(@json($detailPayload))'
                                    class="font-mono font-bold text-xs text-slate-900 hover:text-blue-600 block text-left">
                                {{ $wri->wri_number }}
                            </button>
                            <div class="flex items-center space-x-1.5 text-[10px] text-slate-500 mt-0.5">
                                <span>{{ $wri->dept_pengaju ?: 'Gudang' }} &rarr; {{ $wri->dept_penerima ?: 'Mesin' }}</span>
                                <span>&bull;</span>
                                <span>{{ $wri->created_at->format('d M, H:i') }}</span>
                            </div>
                            @if($wri->assigned_operator_name)
                                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded bg-indigo-50 border border-indigo-200 text-indigo-700 text-[9px] font-bold">
                                    <i class="fa-solid fa-user-gear text-[8px] me-1"></i> Op: {{ $wri->assigned_operator_name }}
                                </span>
                            @endif
                            @if($wri->spk_reference)
                                <span class="inline-block mt-0.5 px-2 py-0.2 rounded bg-slate-100 font-mono text-[9px] text-slate-600 font-semibold">
                                    SPK: {{ $wri->spk_reference }}
                                </span>
                            @endif
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $wri->status_badge['class'] }}">
                            {{ $wri->status_badge['label'] }}
                        </span>
                    </div>

                    <!-- Component info -->
                    <div class="flex items-center space-x-3 p-2 bg-slate-50 rounded-xl cursor-pointer" @click='openDetailModal(@json($detailPayload))'>
                        <img src="{{ $wri->component?->image_url ?? asset('images/logo_hpk.webp') }}" class="w-12 h-12 rounded-lg object-cover border border-slate-200 bg-white flex-shrink-0">
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-slate-900 text-xs block truncate">{{ $wri->component?->name ?? 'Komponen' }}</span>
                            <span class="font-mono text-[10px] font-semibold text-amber-900 bg-amber-100/70 px-1.5 py-0.2 rounded inline-block">
                                {{ $wri->component?->part_number ?? '-' }}
                            </span>
                            <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-900 mt-1">
                                <span>Target: {{ number_format($wri->quantity_requested, 0) }} {{ $wri->component?->uom ?? 'PCS' }}</span>
                                @if($wri->quantity_defect > 0)
                                    <span class="text-rose-600 font-bold text-[10px] bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200" title="Order Awal: {{ number_format($wri->original_quantity_requested, 0) }}">
                                        Defect: -{{ number_format($wri->quantity_defect, 0) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div>
                        <div class="flex items-center justify-between text-[10px] text-slate-500 font-medium mb-1">
                            <span class="truncate max-w-[200px]">Alur: {{ $wri->routing_text ?: 'Mesin Fabrikasi' }}</span>
                            <span>{{ $wri->progress_percentage }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-1.5 rounded-full" style="width: {{ $wri->progress_percentage }}%"></div>
                        </div>
                    </div>

                    <!-- Info Penerima & Operator & Actions (Direct In-Place Pop-ups) -->
                    <div class="pt-2 border-t border-slate-100 flex flex-col gap-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-[10px] text-slate-400 block font-medium">Tujuan & Operator:</span>
                            <span class="font-bold text-slate-800 text-[11px] truncate max-w-[200px]">
                                {{ $wri->dept_penerima ?: 'Mesin' }} &bull; <span class="text-indigo-700">{{ $wri->assigned_operator_name ?: 'Belum ditentukan' }}</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if(!$wri->is_final)
                                @if($wri->approval_pengaju === 'Menunggu')
                                    @if($canApprovePengaju)
                                        <form method="POST" action="{{ route('work-requests.approve-pengaju', $wri) }}" class="flex-1">
                                            @csrf
                                            <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center justify-center space-x-1">
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                                <span>Setujui Pengaju</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="flex-1 py-1.5 text-center bg-amber-50 text-amber-800 border border-amber-300 rounded-xl text-[11px] font-bold">
                                            <i class="fa-solid fa-clock text-amber-500 me-1"></i> Menunggu Verifikasi Ka. {{ $wri->dept_pengaju ?: 'Pengaju' }}
                                        </span>
                                    @endif
                                @elseif($wri->approval_penerima === 'Menunggu')
                                    @if($canApprovePenerima)
                                        <button type="button" 
                                                @click='openAssignModal(@json($assignPayload))'
                                                class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center justify-center space-x-1.5 transition">
                                            <i class="fa-solid fa-user-check text-[10px]"></i>
                                            <span>Verifikasi & Tugaskan</span>
                                        </button>
                                    @else
                                        <span class="flex-1 py-1.5 text-center bg-blue-50 text-blue-800 border border-blue-300 rounded-xl text-[11px] font-bold">
                                            <i class="fa-solid fa-clock text-blue-500 me-1"></i> Menunggu Verifikasi Ka. {{ $wri->dept_penerima ?: 'Penerima' }}
                                        </span>
                                    @endif
                                @else
                                    @if($canProcessAction)
                                        <button type="button" 
                                                @click='openProcessModal(@json($processPayload))'
                                                class="flex-1 py-2 {{ $btnBg }} font-bold text-xs rounded-xl shadow-xs flex items-center justify-center space-x-1.5 transition">
                                            <i class="fa-solid {{ $btnIcon }} text-[10px]"></i>
                                            <span>{{ $shortLabel }}</span>
                                        </button>
                                    @else
                                        @if($isSudah)
                                            <span class="flex-1 py-1.5 text-center bg-emerald-50 text-emerald-800 border border-emerald-300 rounded-xl text-[10px] font-bold truncate px-2">
                                                <i class="fa-solid fa-warehouse text-emerald-600 me-1"></i> Menunggu Penerimaan {{ $wri->dept_pengaju ?: 'Gudang' }}
                                            </span>
                                        @elseif($isMenuju)
                                            <span class="flex-1 py-1.5 text-center bg-purple-50 text-purple-800 border border-purple-300 rounded-xl text-[10px] font-bold truncate px-2">
                                                <i class="fa-solid fa-truck-fast text-purple-600 me-1"></i> Sedang Diantar ke {{ $wri->dept_pengaju ?: 'Pengaju' }}
                                            </span>
                                        @elseif($isPickup || stripos($wri->status, 'Siap Diambil') !== false)
                                            <span class="flex-1 py-1.5 text-center bg-amber-50 text-amber-900 border border-amber-300 rounded-xl text-[10px] font-bold truncate px-2">
                                                <i class="fa-solid fa-dolly text-amber-600 me-1"></i> Siap Dijemput Material Handling
                                            </span>
                                        @else
                                            <span class="flex-1 py-1.5 text-center bg-indigo-50 text-indigo-900 border border-indigo-200 rounded-xl text-[10px] font-bold truncate px-2">
                                                <i class="fa-solid fa-gear fa-spin text-indigo-500 me-1"></i> Sedang dlm proses di Line {{ $wri->dept_penerima ?: 'Penerima' }}
                                            </span>
                                        @endif
                                    @endif
                                @endif
                            @else
                                <span class="flex-1 py-1.5 text-center bg-emerald-50 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold">
                                    <i class="fa-solid fa-circle-check text-emerald-600 me-1"></i> Selesai Diterima {{ $wri->dept_pengaju ?: 'Gudang' }}
                                </span>
                            @endif

                            <button type="button" 
                                    @click='openDetailModal(@json($detailPayload))'
                                    class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center justify-center space-x-1">
                                <i class="fa-solid fa-timeline text-slate-500"></i>
                                <span>Detail</span>
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="py-10 text-center text-slate-400">
                    <i class="fa-solid fa-industry text-3xl mb-2 text-slate-300 block"></i>
                    Belum ada data Work Request Inhouse.
                </div>
                @endforelse
            </div>

            <!-- Desktop Table (>= md screens) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-3 min-w-[190px]">No WR / Status</th>
                            <th class="py-3.5 px-3 whitespace-nowrap">Tanggal</th>
                            <th class="py-3.5 px-3 min-w-[200px]">Komponen</th>
                            <th class="py-3.5 px-2 text-center whitespace-nowrap">Sts Pengaju</th>
                            <th class="py-3.5 px-2 text-center whitespace-nowrap">Sts Penerima</th>
                            <th class="py-3.5 px-2 text-center whitespace-nowrap">Foto</th>
                            <th class="py-3.5 px-3 min-w-[170px]">Routing / Dimensi / Qty</th>
                            <th class="py-3.5 px-3 min-w-[180px]">Deskripsi</th>
                            <th class="py-3.5 px-3 min-w-[170px]">Pengaju / Penerima</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($workRequests as $wri)
                        @php
                            $userActions = $wri->getAvailableActionsForUser(auth()->user());
                            $hasUserAction = !empty($userActions);
                            $primaryAction = $hasUserAction ? $userActions[0] : null;

                            $nextStatus = $primaryAction ? $primaryAction['target_status'] : $wri->getNextStatusLabel(auth()->user());
                            $accumReceived = (float) $wri->quantity_received;
                            $remainingQty = $primaryAction ? $primaryAction['quantity'] : max(0, (float) $wri->quantity_requested - $accumReceived);

                            $isLaserIn = stripos($nextStatus, '(In)') !== false;
                            $isLaserOut = stripos($nextStatus, '(Out)') !== false || ($primaryAction && $primaryAction['type'] === 'out');
                            $isPickup = stripos($nextStatus, 'Pickup') !== false;
                            $isMenuju = stripos($nextStatus, 'Menuju Pengaju') !== false;
                            $isSudah = stripos($nextStatus, 'Sudah Diambil') !== false;

                            $btnBg = $isSudah ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 
                                     ($isMenuju ? 'bg-blue-600 hover:bg-blue-700 text-white' : 
                                     ($isPickup ? 'bg-amber-500 hover:bg-amber-600 text-slate-950 font-black' : 
                                     ($isLaserOut ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-teal-600 hover:bg-teal-700 text-white')));

                            $btnIcon = $primaryAction ? $primaryAction['icon'] : ($isSudah ? 'fa-warehouse' : ($isMenuju ? 'fa-truck-fast' : ($isPickup ? 'fa-truck-ramp-box' : ($isLaserOut ? 'fa-check' : 'fa-play'))));
                            $shortLabel = $primaryAction ? $primaryAction['label'] : ($isSudah ? 'Terima di Gudang' : ($isMenuju ? 'Kirim ke Pengaju' : ($isPickup ? 'MH Pickup' : ($isLaserOut ? 'Selesai: ' . $nextStatus : 'Mulai: ' . $nextStatus))));

                            $canApprovePengaju = $wri->canUserApprovePengaju(auth()->user());
                            $canApprovePenerima = $wri->canUserApprovePenerima(auth()->user());
                            $canProcessAction = $hasUserAction ? true : (auth()->user()->isAdmin() && $wri->canUserProcessAction(auth()->user(), $nextStatus));

                            $assignPayload = [
                                'id' => $wri->id,
                                'wriNumber' => $wri->wri_number,
                                'componentName' => $wri->component?->name ?? 'Komponen',
                                'partNumber' => $wri->component?->part_number ?? '-',
                                'deptPengaju' => $wri->dept_pengaju ?: 'Gudang',
                                'deptPenerima' => $wri->dept_penerima ?: 'Mesin',
                                'totalQty' => (float) $wri->quantity_requested,
                                'uom' => $wri->component?->uom ?? 'PCS',
                                'assignedOperator' => $wri->assigned_operator_name ?? '',
                                'actionUrl' => route('work-requests.approve-penerima', $wri),
                            ];

                            $processPayload = [
                                'id' => $wri->id,
                                'wriNumber' => $wri->wri_number,
                                'componentName' => $wri->component?->name ?? 'Komponen',
                                'partNumber' => $wri->component?->part_number ?? '-',
                                'uom' => $wri->component?->uom ?? 'PCS',
                                'currentStatus' => $wri->status,
                                'nextStatus' => $nextStatus,
                                'nextActionLabel' => $shortLabel,
                                'totalQty' => (float) $wri->quantity_requested,
                                'remainingQty' => $remainingQty > 0 ? $remainingQty : (float) $wri->quantity_requested,
                                'currentBatchQty' => (float) $wri->current_batch_qty,
                                'targetRack' => $wri->target_specific_location_code ?: 'Belum di Rak',
                                'assignedOperator' => $wri->assigned_operator_name ?? '',
                                'actionUrl' => route('work-requests.process-action', $wri),
                            ];

                            $appPengajuLog = $wri->approval_pengaju_log;
                            $appPenerimaLog = $wri->approval_penerima_log;

                            $approvalPengajuStatus = $wri->approval_pengaju ?: 'Menunggu';
                            $approvalPengajuBy = $appPengajuLog?->operator_name 
                                ?? ($wri->approval_pengaju === 'Diterima' ? ($wri->requestedBy?->name ?? 'Ka. ' . ($wri->dept_pengaju ?: 'Pengaju')) : 'Menunggu');
                            $approvalPengajuAt = $appPengajuLog?->created_at?->format('d/m/Y H:i') 
                                ?? ($wri->approval_pengaju === 'Diterima' ? $wri->created_at->format('d/m/Y H:i') : '-');

                            $approvalPenerimaStatus = $wri->approval_penerima ?: 'Menunggu';
                            $approvalPenerimaBy = $appPenerimaLog?->operator_name 
                                ?? ($wri->approval_penerima === 'Diterima' ? 'Ka. ' . ($wri->dept_penerima ?: 'Mesin') : 'Menunggu');
                            $approvalPenerimaAt = $appPenerimaLog?->created_at?->format('d/m/Y H:i') ?? '-';

                            $imagePayload = [
                                'imageUrl' => $wri->component?->image_url ?? asset('images/logo_hpk.webp'),
                                'componentName' => $wri->component?->name ?? 'Komponen',
                                'partNumber' => $wri->component?->part_number ?? '-',
                                'dimension' => $wri->component?->dimension_formatted ?? ($wri->component?->specification ?? '-'),
                            ];

                            $detailPayload = [
                                'id' => $wri->id,
                                'wriNumber' => $wri->wri_number,
                                'componentName' => $wri->component?->name ?? 'Komponen',
                                'partNumber' => $wri->component?->part_number ?? '-',
                                'dimension' => $wri->component?->dimension_formatted ?? '-',
                                'uom' => $wri->component?->uom ?? 'PCS',
                                'imageUrl' => $wri->component?->image_url ?? asset('images/logo_hpk.webp'),
                                'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($wri->wri_number),
                                'deptPengaju' => $wri->dept_pengaju ?: 'Gudang',
                                'deptPenerima' => $wri->dept_penerima ?: 'Mesin',
                                'priority' => $wri->priority_badge['label'],
                                'priorityClass' => $wri->priority_badge['class'],
                                'currentStatus' => $wri->status,
                                'statusClass' => $wri->status_badge['class'],
                                'targetRack' => $wri->target_specific_location_code ?: 'Belum di Rak',
                                'originalQty' => (float) $wri->original_quantity_requested,
                                'totalQty' => (float) $wri->quantity_requested,
                                'receivedQty' => (float) $wri->quantity_received,
                                'defectQty' => (float) $wri->quantity_defect,
                                'progressPercentage' => $wri->progress_percentage,
                                'routingText' => $wri->routing_text,
                                'requestedByName' => $wri->requestedBy?->name ?? 'Pemohon Gudang',
                                'approvalPengajuStatus' => $approvalPengajuStatus,
                                'approvalPengajuBy' => $approvalPengajuBy,
                                'approvalPengajuAt' => $approvalPengajuAt,
                                'approvalPenerimaStatus' => $approvalPenerimaStatus,
                                'approvalPenerimaBy' => $approvalPenerimaBy,
                                'approvalPenerimaAt' => $approvalPenerimaAt,
                                'assignedOperator' => $wri->assigned_operator_name ?: 'Belum ditentukan',
                                'steps' => $wri->steps->map(fn($s) => [
                                    'step' => $s->step_number,
                                    'name' => $s->process_name,
                                    'status' => $s->status,
                                ])->values(),
                                'logs' => $wri->logs->map(fn($l) => [
                                    'time' => $l->created_at->format('d/m/Y H:i'),
                                    'status' => $l->status_label,
                                    'qty' => (float) $l->quantity,
                                    'operator' => $l->operator_name ?: '-',
                                    'notes' => $l->notes ?: '-',
                                    'is_defect' => $l->action_type === 'defect' || str_contains($l->status_label, 'Defect'),
                                ])->values(),
                                'printUrl' => route('work-requests.print-sheet', $wri),
                                'remainingQty' => $remainingQty > 0 ? $remainingQty : (float) $wri->quantity_requested,
                                'nextStatus' => $nextStatus,
                                'canProcessAction' => $canProcessAction,
                                'actionUrl' => route('work-requests.process-action', $wri),
                                'isFinal' => (bool) $wri->is_final,
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- 1. No WR / Status -->
                            <td class="py-3 px-3 align-top">
                                <button type="button" 
                                        @click='openDetailModal(@json($detailPayload))' 
                                        class="font-mono font-bold text-blue-600 hover:text-blue-800 text-xs flex items-center group text-left"
                                        title="Klik untuk lihat detail WRI">
                                    <span class="break-all">{{ $wri->wri_number }}</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-blue-400 group-hover:text-blue-600 ms-1 opacity-0 group-hover:opacity-100 transition shrink-0"></i>
                                </button>
                                
                                <div class="flex flex-wrap items-center gap-1 mt-1">
                                    @if($wri->spk_reference)
                                        <span class="inline-block px-1.5 py-0.2 rounded bg-slate-100 font-mono text-[9px] text-slate-600 font-semibold" title="Referensi SPK">
                                            SPK: {{ $wri->spk_reference }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] {{ $wri->priority_badge['class'] }}">
                                        {{ $wri->priority_badge['label'] }}
                                    </span>
                                </div>

                                <!-- Tombol Aksi Status / Lencana Status -->
                                <div class="mt-2">
                                    @if(!$wri->is_final)
                                        @if($wri->approval_pengaju === 'Menunggu')
                                            <span class="inline-flex items-center px-2 py-1 text-amber-800 bg-amber-50 border border-amber-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                <i class="fa-solid fa-clock text-amber-500 me-1 text-[9px]"></i> Menunggu Pengaju
                                            </span>
                                        @elseif($wri->approval_penerima === 'Menunggu')
                                            <span class="inline-flex items-center px-2 py-1 text-blue-800 bg-blue-50 border border-blue-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                <i class="fa-solid fa-clock text-blue-500 me-1 text-[9px]"></i> Menunggu Penerima
                                            </span>
                                        @else
                                            @if($canProcessAction)
                                                <button type="button" 
                                                        @click='openProcessModal(@json($processPayload))'
                                                        class="w-full inline-flex items-center justify-center px-2.5 py-1.5 {{ $btnBg }} font-bold text-xs rounded-xl shadow-xs hover:shadow-md transition space-x-1.5 text-center">
                                                    <i class="fa-solid {{ $btnIcon }} text-[10px]"></i>
                                                    <span class="truncate">{{ $shortLabel }}</span>
                                                </button>
                                            @else
                                                @if($isSudah)
                                                    <span class="inline-flex items-center px-2 py-1 text-emerald-800 bg-emerald-50 border border-emerald-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                        <i class="fa-solid fa-warehouse text-emerald-600 me-1 text-[9px]"></i> Tunggu Terima {{ $wri->dept_pengaju ?: 'Gudang' }}
                                                    </span>
                                                @elseif($isMenuju)
                                                    <span class="inline-flex items-center px-2 py-1 text-purple-800 bg-purple-50 border border-purple-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                        <i class="fa-solid fa-truck-fast text-purple-600 me-1 text-[9px]"></i> Diantar ke {{ $wri->dept_pengaju ?: 'Pengaju' }}
                                                    </span>
                                                @elseif($isPickup || stripos($wri->status, 'Siap Diambil') !== false)
                                                    <span class="inline-flex items-center px-2 py-1 text-amber-900 bg-amber-50 border border-amber-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                        <i class="fa-solid fa-dolly text-amber-600 me-1 text-[9px]"></i> Siap Dijemput MH
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 text-indigo-900 bg-indigo-50 border border-indigo-200 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                        <i class="fa-solid fa-gear fa-spin text-indigo-500 me-1 text-[9px]"></i> Proses Line {{ $wri->dept_penerima ?: 'Penerima' }}
                                                    </span>
                                                @endif
                                            @endif
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-emerald-800 bg-emerald-50 border border-emerald-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                            <i class="fa-solid fa-circle-check text-emerald-600 me-1 text-[9px]"></i> Selesai Diterima {{ $wri->dept_pengaju ?: 'Gudang' }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 2. Tanggal -->
                            <td class="py-3 px-3 align-top">
                                <div class="font-medium text-slate-800 text-xs whitespace-nowrap">
                                    {{ $wri->created_at->format('d M Y') }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 whitespace-nowrap">
                                    {{ $wri->created_at->format('H:i') }} WIB
                                </div>
                            </td>

                            <!-- 3. Komponen -->
                            <td class="py-3 px-3 align-top max-w-xs">
                                <div class="cursor-pointer" @click='openDetailModal(@json($detailPayload))'>
                                    @if($wri->component)
                                        <span class="font-bold text-slate-900 hover:text-blue-600 block line-clamp-1 leading-snug" title="{{ $wri->component->name }}">
                                            {{ $wri->component->name }}
                                        </span>
                                        <div class="font-mono text-[11px] font-semibold text-slate-500 mt-0.5">
                                            {{ $wri->component->part_number }}
                                        </div>
                                    @else
                                        <span class="font-bold text-slate-500 block text-xs">Komponen Dihapus</span>
                                        <span class="font-mono text-[11px] text-slate-400">ID #{{ $wri->component_id }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- 4. Sts Pengaju -->
                            <td class="py-3 px-2 align-top text-center">
                                @if($wri->approval_pengaju === 'Menunggu')
                                    @if($canApprovePengaju)
                                        <form method="POST" action="{{ route('work-requests.approve-pengaju', $wri) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-xs transition space-x-1">
                                                <i class="fa-solid fa-check text-[9px]"></i>
                                                <span>Setujui</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-amber-800 bg-amber-50 border border-amber-300 rounded-lg text-[11px] font-bold whitespace-nowrap">
                                            Menunggu
                                        </span>
                                    @endif
                                @elseif($wri->approval_pengaju === 'Diterima')
                                    <span class="inline-flex items-center px-2.5 py-1 text-emerald-800 bg-emerald-50 border border-emerald-300 rounded-lg text-xs font-bold whitespace-nowrap shadow-2xs" title="Disetujui oleh: {{ $approvalPengajuBy }} ({{ $approvalPengajuAt }})">
                                        <i class="fa-solid fa-check text-[9px] me-1 text-emerald-600"></i> Diterima
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-rose-800 bg-rose-50 border border-rose-300 rounded-lg text-xs font-bold whitespace-nowrap">
                                        <i class="fa-solid fa-xmark text-[9px] me-1 text-rose-600"></i> Ditolak
                                    </span>
                                @endif
                            </td>

                            <!-- 5. Sts Penerima -->
                            <td class="py-3 px-2 align-top text-center">
                                @if($wri->approval_penerima === 'Menunggu')
                                    @if($canApprovePenerima)
                                        <button type="button" 
                                                @click='openAssignModal(@json($assignPayload))'
                                                class="inline-flex items-center px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-xs transition space-x-1">
                                            <i class="fa-solid fa-user-check text-[9px]"></i>
                                            <span>Tugaskan</span>
                                        </button>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-slate-700 bg-slate-100 border border-slate-300 rounded-lg text-[11px] font-bold whitespace-nowrap">
                                            Belum Verifikasi
                                        </span>
                                    @endif
                                @elseif($wri->approval_penerima === 'Diterima')
                                    <span class="inline-flex items-center px-2.5 py-1 text-emerald-800 bg-emerald-50 border border-emerald-300 rounded-lg text-xs font-bold whitespace-nowrap shadow-2xs" title="Diverifikasi oleh: {{ $approvalPenerimaBy }} ({{ $approvalPenerimaAt }})">
                                        <i class="fa-solid fa-check text-[9px] me-1 text-emerald-600"></i> Diterima
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-rose-800 bg-rose-50 border border-rose-300 rounded-lg text-xs font-bold whitespace-nowrap">
                                        <i class="fa-solid fa-xmark text-[9px] me-1 text-rose-600"></i> Ditolak
                                    </span>
                                @endif
                            </td>

                            <!-- 6. Foto -->
                            <td class="py-3 px-2 align-top text-center">
                                <button type="button" 
                                        @click='openImageModal(@json($imagePayload))'
                                        class="inline-flex items-center px-3 py-1 bg-white hover:bg-blue-50 text-blue-600 hover:text-blue-700 border border-blue-300 hover:border-blue-400 rounded-full font-bold text-xs shadow-2xs transition space-x-1.5"
                                        title="Lihat foto komponen">
                                    <i class="fa-solid fa-image text-[10px]"></i>
                                    <span>Lihat</span>
                                </button>
                            </td>

                            <!-- 7. Routing / Dimensi / Qty -->
                            <td class="py-3 px-3 align-top">
                                <div class="text-xs text-slate-600 space-y-0.5">
                                    <div class="flex items-start space-x-1">
                                        <span class="text-slate-400 w-14 shrink-0">Routing</span>
                                        <span class="text-slate-400">:</span>
                                        <span class="font-bold text-slate-800 truncate" title="{{ $wri->dept_penerima ?: ($wri->routing_text ?: '-') }}">{{ $wri->dept_penerima ?: ($wri->routing_text ?: '-') }}</span>
                                    </div>
                                    <div class="flex items-start space-x-1">
                                        <span class="text-slate-400 w-14 shrink-0">Dimensi</span>
                                        <span class="text-slate-400">:</span>
                                        <span class="font-bold text-slate-800 truncate" title="{{ $wri->component?->dimension_formatted ?: ($wri->component?->specification ?: '-') }}">{{ $wri->component?->dimension_formatted ?: ($wri->component?->specification ?: '-') }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-slate-400 w-14 shrink-0">Qty</span>
                                        <span class="text-slate-400">:</span>
                                        <span class="font-extrabold text-slate-900">{{ number_format($wri->quantity_requested, 0) }} <span class="font-normal text-[11px] text-slate-500">{{ $wri->component?->uom ?? 'PCS' }}</span></span>
                                        @if($wri->quantity_defect > 0)
                                            <span class="text-[10px] text-rose-600 font-bold ml-1" title="Defect: {{ number_format($wri->quantity_defect, 0) }}">(-{{ number_format($wri->quantity_defect, 0) }})</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- 8. Deskripsi -->
                            <td class="py-3 px-3 align-top">
                                <div class="max-w-[220px]">
                                    <div class="text-xs text-slate-700 line-clamp-2 leading-relaxed" title="{{ $wri->notes ?: '-' }}">
                                        {{ $wri->notes ?: '-' }}
                                    </div>
                                </div>
                            </td>

                            <!-- 9. Pengaju / Penerima -->
                            <td class="py-3 px-3 align-top">
                                <div class="text-xs text-slate-600 space-y-0.5">
                                    <div class="flex items-start space-x-1">
                                        <span class="text-slate-400 w-16 shrink-0">Pengaju</span>
                                        <span class="text-slate-400">:</span>
                                        <span class="font-bold text-slate-800 truncate" title="{{ $wri->dept_pengaju ?: 'Gudang' }}">{{ $wri->dept_pengaju ?: 'Gudang' }}</span>
                                    </div>
                                    <div class="flex items-start space-x-1">
                                        <span class="text-slate-400 w-16 shrink-0">Penerima</span>
                                        <span class="text-slate-400">:</span>
                                        <span class="font-bold text-slate-800 truncate" title="{{ $wri->dept_penerima ?: 'Mesin' }}">{{ $wri->dept_penerima ?: 'Mesin' }}</span>
                                    </div>
                                    <div class="flex items-start space-x-1">
                                        <span class="text-slate-400 w-16 shrink-0">Operator</span>
                                        <span class="text-slate-400">:</span>
                                        <span class="font-bold text-indigo-700 truncate" title="{{ $wri->assigned_operator_name ?: 'Belum ditentukan' }}">{{ $wri->assigned_operator_name ?: 'Belum ditentukan' }}</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-300 flex items-center justify-center mx-auto mb-3 text-2xl">
                                    <i class="fa-solid fa-industry"></i>
                                </div>
                                <p class="text-sm font-bold text-slate-700">Belum ada data Work Request Inhouse</p>
                                <p class="text-xs text-slate-400 mt-1">Buat tiket order komponen baru dengan tombol di atas.</p>
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

        <!-- Include Pop-up Modals -->
        @include('work_requests.partials.assign_operator_modal')
        @include('work_requests.partials.process_modal')
        @include('work_requests.partials.detail_modal')
        @include('work_requests.partials.image_preview_modal')

    </div>

    <script>
        function wriQueueManager() {
            return {
                processModalOpen: false,
                detailModalOpen: false,
                assignModalOpen: false,
                imageModalOpen: false,
                imageData: {
                    imageUrl: '',
                    componentName: '',
                    partNumber: '',
                    dimension: '',
                },
                assignData: {
                    id: null,
                    wriNumber: '',
                    componentName: '',
                    partNumber: '',
                    deptPengaju: '',
                    deptPenerima: '',
                    totalQty: 0,
                    uom: 'PCS',
                    operatorName: '',
                    notes: '',
                    actionUrl: '',
                },
                processData: {
                    id: null,
                    wriNumber: '',
                    componentName: '',
                    partNumber: '',
                    uom: 'PCS',
                    currentStatus: '',
                    nextStatus: '',
                    nextActionLabel: '',
                    totalQty: 0,
                    remainingQty: 0,
                    processQty: 0,
                    currentBatchQty: 0,
                    targetRack: '',
                    assignedOperator: '',
                    hasDefect: false,
                    defectQty: null,
                    defectReason: '',
                    notes: '',
                    actionUrl: '',
                },
                detailData: {
                    id: null,
                    wriNumber: '',
                    componentName: '',
                    partNumber: '',
                    dimension: '',
                    uom: 'PCS',
                    imageUrl: '',
                    qrUrl: '',
                    deptPengaju: '',
                    deptPenerima: '',
                    priority: '',
                    priorityClass: '',
                    currentStatus: '',
                    statusClass: '',
                    targetRack: '',
                    totalQty: 0,
                    receivedQty: 0,
                    defectQty: 0,
                    progressPercentage: 0,
                    routingText: '',
                    requestedByName: '',
                    approvalPengajuStatus: '',
                    approvalPengajuBy: '',
                    approvalPengajuAt: '',
                    approvalPenerimaStatus: '',
                    approvalPenerimaBy: '',
                    approvalPenerimaAt: '',
                    assignedOperator: '',
                    steps: [],
                    logs: [],
                    printUrl: '#',
                },
                openAssignModal(data) {
                    this.assignData = {
                        ...data,
                        operatorName: data.assignedOperator || '',
                        notes: '',
                    };
                    this.assignModalOpen = true;
                },
                closeAssignModal() {
                    this.assignModalOpen = false;
                },
                openProcessModal(data) {
                    const isHandover = data.nextStatus && (data.nextStatus.includes('MH Pickup') || data.nextStatus.includes('Menuju Pengaju') || data.nextStatus.includes('Sudah Diambil'));
                    const initialQty = (isHandover && data.currentBatchQty > 0) ? Number(data.currentBatchQty) : Number(data.remainingQty || 1);
                    this.processData = {
                        ...data,
                        availableQty: initialQty,
                        processQty: initialQty,
                        hasDefect: false,
                        defectQty: null,
                        defectReason: '',
                        notes: '',
                    };
                    this.processModalOpen = true;
                },
                getMaxProcessQty() {
                    const avail = Number(this.processData.availableQty || (this.processData.currentBatchQty > 0 ? this.processData.currentBatchQty : this.processData.remainingQty) || 0);
                    const defect = (this.processData.hasDefect && this.processData.defectQty > 0) ? Number(this.processData.defectQty) : 0;
                    return Math.max(0, avail - defect);
                },
                syncProcessQtyWithDefect() {
                    const maxGood = this.getMaxProcessQty();
                    if (Number(this.processData.processQty) > maxGood || this.processData.processQty === null || this.processData.processQty === undefined) {
                        this.processData.processQty = maxGood;
                    }
                },
                incrementProcessQty(step = 1) {
                    const max = this.getMaxProcessQty();
                    let cur = Number(this.processData.processQty || 0);
                    if (cur + step <= max) {
                        this.processData.processQty = cur + step;
                    } else {
                        this.processData.processQty = max;
                    }
                },
                decrementProcessQty(step = 1) {
                    let cur = Number(this.processData.processQty || 0);
                    if (cur - step >= 0) {
                        this.processData.processQty = cur - step;
                    } else {
                        this.processData.processQty = 0;
                    }
                },
                setMaxProcessQty() {
                    this.processData.processQty = this.getMaxProcessQty();
                },
                toggleDefect() {
                    this.processData.hasDefect = !this.processData.hasDefect;
                    if (!this.processData.hasDefect) {
                        this.processData.defectQty = null;
                        this.processData.defectReason = '';
                    }
                    this.syncProcessQtyWithDefect();
                },
                closeProcessModal() {
                    this.processModalOpen = false;
                },
                openDetailModal(data) {
                    this.detailData = { ...data };
                    this.detailModalOpen = true;
                },
                closeDetailModal() {
                    this.detailModalOpen = false;
                },
                openImageModal(data) {
                    this.imageData = { ...data };
                    this.imageModalOpen = true;
                },
                closeImageModal() {
                    this.imageModalOpen = false;
                },
                getStepLogs(stepName, stepIndex) {
                    if (!this.detailData || !this.detailData.logs) return [];
                    const cleanName = (stepName || '').replace(/^Dept\s+/i, '').trim().toLowerCase();
                    const sLower = (stepName || '').toLowerCase();
                    const isLastStep = Boolean(this.detailData.steps && stepIndex === this.detailData.steps.length - 1);
                    return this.detailData.logs.filter(l => {
                        const status = (l.status || '').toLowerCase();
                        const notes = (l.notes || '').toLowerCase();
                        if (status.includes(sLower) || status.includes(cleanName) || notes.includes(sLower) || notes.includes(cleanName)) {
                            return true;
                        }
                        if (isLastStep && status.includes('siap diambil') && !status.includes('pickup')) {
                            return true;
                        }
                        return false;
                    });
                },
                getGudangLogs() {
                    if (!this.detailData || !this.detailData.logs) return [];
                    return this.detailData.logs.filter(l => {
                        const status = (l.status || '').toLowerCase();
                        return status.includes('sudah diambil') || status.includes('menuju pengaju');
                    });
                }
            };
        }
    </script>
</x-app-layout>

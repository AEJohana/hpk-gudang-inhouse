<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start space-x-3">
                <a href="{{ route('dashboard') }}" 
                   class="mt-1 px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer shrink-0" 
                   title="Kembali ke Beranda Utama">
                    <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                    <span>Kembali</span>
                </a>
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
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto self-start sm:self-auto">
                <button type="button" 
                        onclick="window.location.reload()" 
                        class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-800 font-bold text-xs rounded-xl border border-slate-300 shadow-xs transition flex items-center justify-center space-x-1.5 cursor-pointer active:scale-95"
                        title="Segarkan antrean tiket WRI">
                    <i class="fa-solid fa-arrows-rotate text-xs text-amber-600"></i>
                    <span>Refresh</span>
                </button>
                <a href="{{ route('work-station-supplies.index') }}" class="flex-1 sm:flex-initial px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center justify-center text-center">
                    <i class="fa-solid fa-truck-ramp-box me-1.5 text-sm"></i>
                    <span>Material Handling</span>
                </a>
                @can('work-request.create')
                <a href="{{ route('work-requests.create') }}" class="flex-1 sm:flex-initial px-3.5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center justify-center text-center">
                    <i class="fa-solid fa-plus me-1.5"></i>
                    <span>Membuat WR</span>
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
         x-data="wriQueueManager()"
         @if(isset($openDetailPayload) && $openDetailPayload)
             x-init='openDetailModal(@json($openDetailPayload))'
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
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100 whitespace-nowrap">
                    <i class="fa-solid fa-shield-halved text-xs text-rose-500"></i>
                    <span>Safety Stock Alert</span>
                </a>
                @endcan
                @can('work-request.print')
                <a href="{{ route('work-requests.print-queue') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100 whitespace-nowrap">
                    <i class="fa-solid fa-print text-xs text-indigo-500"></i>
                    <span>Cetak Dokumen WR</span>
                    @if(isset($printableCount) && $printableCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-indigo-600 text-white ml-1">
                            {{ $printableCount }}
                        </span>
                    @endif
                </a>
                @endcan
                @can('work-request.monitoring')
                <a href="{{ route('work-requests.monitoring') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100 whitespace-nowrap">
                    <i class="fa-solid fa-chart-line text-xs text-amber-500"></i>
                    <span>Monitoring SLA & Durasi</span>
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

        <!-- 1. KPI SUMMARY STATS CARDS (INTERACTIVE QUICK FILTERS) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <a href="{{ route('work-requests.index', ['scope' => $scope]) }}" 
               class="bg-white hover:bg-slate-50 rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer"
               title="Klik untuk menampilkan semua tiket">
                <div class="w-11 h-11 rounded-xl bg-slate-100 group-hover:bg-[#0a2342] group-hover:text-amber-400 text-slate-700 flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total WRI</span>
                    <h3 class="text-xl font-black text-slate-900 leading-tight mt-0.5">{{ $totalWri }}</h3>
                    <span class="text-[10px] text-slate-500">Semua tiket order</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'verifikasi']) }}" 
               class="bg-white hover:bg-amber-50/50 rounded-2xl p-4 border {{ $needsApprovalCount > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200' }} shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer"
               title="Klik untuk menyaring tiket yang butuh verifikasi">
                <div class="w-11 h-11 rounded-xl {{ $needsApprovalCount > 0 ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-stamp"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Perlu Verifikasi</span>
                    <h3 class="text-xl font-black text-amber-950 leading-tight mt-0.5">{{ $needsApprovalCount }}</h3>
                    <span class="text-[10px] text-amber-700 font-medium">Pengaju & Penerima</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'proses']) }}" 
               class="bg-white hover:bg-blue-50/50 rounded-2xl p-4 border border-blue-200 shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer"
               title="Klik untuk menyaring tiket yang sedang dalam proses / antrian">
                <div class="w-11 h-11 rounded-xl bg-blue-50 group-hover:bg-blue-600 group-hover:text-white text-blue-700 flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-gears animate-spin" style="animation-duration: 6s;"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600">Sedang Proses</span>
                    <h3 class="text-xl font-black text-blue-700 leading-tight mt-0.5">{{ $inProcessCount }}</h3>
                    <span class="text-[10px] text-blue-500">Antrian, Mesin, MH</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'siap']) }}" 
               class="bg-white hover:bg-teal-50/50 rounded-2xl p-4 border border-teal-200 bg-teal-50/20 shadow-xs flex items-center space-x-3 transition group active:scale-[0.99] cursor-pointer"
               title="Klik untuk menyaring tiket siap diambil / menuju pengaju">
                <div class="w-11 h-11 rounded-xl bg-teal-500 group-hover:bg-teal-600 text-white flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-dolly"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-teal-800">Siap Diambil</span>
                    <h3 class="text-xl font-black text-teal-900 leading-tight mt-0.5">{{ $readyForWarehouseCount }}</h3>
                    <span class="text-[10px] text-teal-700 font-semibold">Menuju Pengaju</span>
                </div>
            </a>

            <a href="{{ route('work-requests.index', ['scope' => $scope, 'status' => 'selesai']) }}" 
               class="bg-white hover:bg-emerald-50/50 rounded-2xl p-4 border border-emerald-200 shadow-xs flex items-center space-x-3 col-span-2 sm:col-span-1 transition group active:scale-[0.99] cursor-pointer"
               title="Klik untuk menyaring tiket yang sudah selesai">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 group-hover:bg-emerald-600 group-hover:text-white text-emerald-700 flex items-center justify-center flex-shrink-0 text-lg font-bold transition">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Selesai</span>
                    <h3 class="text-xl font-black text-emerald-700 leading-tight mt-0.5">{{ $receivedCount }}</h3>
                    <span class="text-[10px] text-emerald-600">Diterima Pengaju</span>
                </div>
            </a>
        </div>

        <!-- 2. UNIFIED WORK REQUEST CONTROL BAR (BEBAS DARI INPUT DOBEL) -->
        <div class="bg-white p-2.5 sm:p-3 rounded-2xl border border-slate-200 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <!-- Left: 5 Status Tabs & Interactive SLA Alert -->
            <div class="flex flex-wrap items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
                <!-- Tab: Semua -->
                <a href="{{ route('work-requests.index', ['scope' => $scope]) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 {{ (!request()->filled('status') || request()->status === 'all') && !request()->boolean('overdue_sla') ? 'bg-[#0a2342] text-amber-400 shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <span>Semua</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ (!request()->filled('status') || request()->status === 'all') && !request()->boolean('overdue_sla') ? 'bg-amber-400 text-slate-950' : 'bg-slate-200 text-slate-700' }}">
                        {{ $totalWri }}
                    </span>
                </a>

                <!-- Tab: Perlu Verifikasi -->
                <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'verifikasi', 'overdue_sla' => null])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 {{ request()->status === 'verifikasi' ? 'bg-amber-400 text-slate-950 font-black shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-stamp text-[11px]"></i>
                    <span>Perlu Verifikasi</span>
                    @if($needsApprovalCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ request()->status === 'verifikasi' ? 'bg-slate-950 text-white' : 'bg-rose-600 text-white animate-pulse' }}">
                            {{ $needsApprovalCount }}
                        </span>
                    @endif
                </a>

                <!-- Tab: Sedang Proses -->
                <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'proses', 'overdue_sla' => null])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 {{ request()->status === 'proses' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-gears text-[11px]"></i>
                    <span>Sedang Proses</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ request()->status === 'proses' ? 'bg-blue-800 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ $inProcessCount }}
                    </span>
                </a>

                <!-- Tab: Siap Ambil -->
                <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'siap', 'overdue_sla' => null])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 {{ request()->status === 'siap' ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-dolly text-[11px]"></i>
                    <span>Siap Ambil</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ request()->status === 'siap' ? 'bg-teal-800 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ $readyForWarehouseCount }}
                    </span>
                </a>

                <!-- Tab: Selesai -->
                <a href="{{ route('work-requests.index', array_merge(request()->query(), ['status' => 'selesai', 'overdue_sla' => null])) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 {{ request()->status === 'selesai' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-circle-check text-[11px]"></i>
                    <span>Selesai</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ request()->status === 'selesai' ? 'bg-emerald-800 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ $receivedCount }}
                    </span>
                </a>

                <!-- Separator Divider -->
                <div class="hidden sm:block h-5 w-px bg-slate-200 mx-1"></div>

                <!-- Interactive SLA Alert Filter Button -->
                @if($overdueSlaCount > 0)
                <a href="{{ route('work-requests.index', array_merge(request()->query(), ['overdue_sla' => request()->boolean('overdue_sla') ? null : 1])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap {{ request()->boolean('overdue_sla') ? 'bg-rose-600 text-white ring-2 ring-rose-400 font-black shadow-xs' : 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' }}"
                   title="{{ request()->boolean('overdue_sla') ? 'Filter aktif: Klik untuk mematikan filter SLA' : 'Klik untuk menyaring tiket terlambat > 72 jam' }}">
                    <i class="fa-solid fa-triangle-exclamation {{ request()->boolean('overdue_sla') ? 'text-white' : 'text-rose-600' }}"></i>
                    <span>{{ $overdueSlaCount }} Melewati SLA > 72 Jam</span>
                    @if(request()->boolean('overdue_sla'))
                        <i class="fa-solid fa-xmark ml-1 text-[11px] bg-rose-700 hover:bg-rose-800 rounded-full w-4 h-4 inline-flex items-center justify-center"></i>
                    @endif
                </a>
                @endif
            </div>

            <!-- Right: Global Scope Switcher (if admin) & Search Input -->
            <div class="flex flex-col sm:flex-row items-center gap-2 w-full lg:w-auto">
                @if(Auth::user()->canAccessAllDepartments())
                <div class="flex items-center bg-slate-100 p-0.5 rounded-xl border border-slate-200 text-xs w-full sm:w-auto justify-center">
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['scope' => 'my_dept'])) }}" 
                       class="px-2.5 py-1 rounded-lg font-bold transition {{ $scope === 'my_dept' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
                       title="Tampilkan hanya tiket departemen Anda">
                        <i class="fa-solid fa-user-tag text-xs text-amber-500 mr-1"></i> Dept Saya
                    </a>
                    <a href="{{ route('work-requests.index', array_merge(request()->query(), ['scope' => 'all'])) }}" 
                       class="px-2.5 py-1 rounded-lg font-bold transition {{ $scope === 'all' ? 'bg-[#0a2342] text-amber-400 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
                       title="Tampilkan tiket dari seluruh 15 departemen">
                        <i class="fa-solid fa-globe text-xs mr-1"></i> Semua Dept (*)
                    </a>
                </div>
                @endif

                <!-- Search Form -->
                <form method="GET" action="{{ route('work-requests.index') }}" class="relative w-full sm:w-64">
                    <input type="hidden" name="scope" value="{{ $scope }}">
                    @if(request()->filled('status'))
                        <input type="hidden" name="status" value="{{ request()->status }}">
                    @endif
                    @if(request()->boolean('overdue_sla'))
                        <input type="hidden" name="overdue_sla" value="1">
                    @endif
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request()->search }}" 
                           placeholder="Cari No. WRI, komponen, SPK..." 
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
                    $userActions = $wri->getAvailableActionsForUser(auth()->user(), true);
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
                    $canProcessAction = $hasUserAction ? true : (auth()->user()->isAdmin() && $wri->canUserProcessAction(auth()->user(), $nextStatus, true));

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
                        'logs' => $wri->logs->sortBy('id')->values()->map(fn($l) => [
                            'time' => $l->created_at->format('d/m/Y H:i'),
                            'status' => $l->status_label,
                            'qty' => (float) $l->quantity,
                            'operator' => $l->operator_name ?: '-',
                            'notes' => $l->notes ?: '-',
                            'action_type' => $l->action_type,
                            'is_defect' => $l->action_type === 'defect' || str_contains($l->status_label, 'Defect'),
                        ])->values(),
                        'printUrl' => route('work-requests.print-sheet', $wri),
                        'isPrintable' => $wri->isPrintable(),
                        'printValidationMessage' => $wri->getPrintValidationMessage(),
                        'printCount' => $wri->print_count,
                        'remainingQty' => $remainingQty > 0 ? $remainingQty : (float) $wri->quantity_requested,
                        'nextStatus' => $nextStatus,
                        'canProcessAction' => $canProcessAction,
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
                                        <div class="flex-1 flex items-center gap-1.5">
                                            <form method="POST" action="{{ route('work-requests.approve-pengaju', $wri) }}" class="flex-1">
                                                @csrf
                                                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center justify-center space-x-1">
                                                    <i class="fa-solid fa-check text-[10px]"></i>
                                                    <span>Diterima</span>
                                                </button>
                                            </form>
                                            <button type="button" 
                                                    @click="openRejectModal('{{ route('work-requests.reject', $wri) }}', '{{ $wri->wri_number }}')" 
                                                    class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 font-bold text-xs rounded-xl shadow-2xs flex items-center justify-center space-x-1">
                                                <i class="fa-solid fa-ban text-[10px]"></i>
                                                <span>Ditolak</span>
                                            </button>
                                        </div>
                                    @else
                                        <span class="flex-1 py-1.5 text-center bg-amber-50 text-amber-800 border border-amber-300 rounded-xl text-[11px] font-bold">
                                            <i class="fa-solid fa-clock text-amber-500 me-1"></i> Menunggu Verifikasi Ka. {{ $wri->dept_pengaju ?: 'Pengaju' }}
                                        </span>
                                    @endif
                                @elseif($wri->approval_penerima === 'Menunggu')
                                    @if($canApprovePenerima)
                                        <div class="flex-1 flex items-center gap-1.5">
                                            <button type="button" 
                                                    @click='openAssignModal(@json($assignPayload))'
                                                    class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center justify-center space-x-1.5 transition">
                                                <i class="fa-solid fa-user-check text-[10px]"></i>
                                                <span>Diterima (Tugaskan)</span>
                                            </button>
                                            <button type="button" 
                                                    @click="openRejectModal('{{ route('work-requests.reject', $wri) }}', '{{ $wri->wri_number }}')" 
                                                    class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 font-bold text-xs rounded-xl shadow-2xs flex items-center justify-center space-x-1">
                                                <i class="fa-solid fa-ban text-[10px]"></i>
                                                <span>Ditolak</span>
                                            </button>
                                        </div>
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
                                            <div class="flex-1 flex flex-col space-y-1">
                                                <span class="py-1.5 text-center bg-purple-50 text-purple-800 border border-purple-300 rounded-xl text-[10px] font-bold truncate px-2">
                                                    <i class="fa-solid fa-truck-fast text-purple-600 me-1"></i> Sedang Diantar Kurir MH
                                                </span>
                                                @can('material-handling.view')
                                                <a href="{{ route('work-station-supplies.index') }}" 
                                                   class="py-1 text-center bg-purple-100 hover:bg-purple-200 text-purple-900 font-bold text-[10px] rounded-lg border border-purple-300 transition">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] me-1"></i> Pantau di MH
                                                </a>
                                                @endcan
                                            </div>
                                        @elseif($isPickup || stripos($wri->status, 'Siap Diambil') !== false)
                                            <div class="flex-1 flex flex-col space-y-1">
                                                <span class="py-1.5 text-center bg-amber-50 text-amber-900 border border-amber-300 rounded-xl text-[10px] font-bold truncate px-2">
                                                    <i class="fa-solid fa-truck-ramp-box text-amber-600 me-1"></i> Siap Dijemput Kurir MH
                                                </span>
                                                @can('material-handling.view')
                                                <a href="{{ route('work-station-supplies.index') }}" 
                                                   class="py-1 text-center bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-[10px] rounded-lg shadow-2xs transition">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] me-1"></i> Buka Menu MH
                                                </a>
                                                @endcan
                                            </div>
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

                            @can('work-request.print')
                                @if($wri->isPrintable())
                                    <a href="{{ route('work-requests.print-sheet', $wri) }}" 
                                       target="_blank" 
                                       class="px-2.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-xl border border-indigo-200 transition flex items-center justify-center space-x-1"
                                       title="Cetak lembar kerja resmi 3 duplikat">
                                        <i class="fa-solid fa-print text-xs"></i>
                                        <span>Cetak</span>
                                    </a>
                                @endif
                            @endcan

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
                    @if(request()->filled('status') || request()->boolean('overdue_sla') || request()->filled('search'))
                        <p class="text-xs font-bold text-slate-700">Tidak ada tiket yang sesuai kriteria filter.</p>
                        <a href="{{ route('work-requests.index', ['scope' => $scope]) }}" class="inline-flex items-center space-x-1 mt-2.5 px-3 py-1 bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold rounded-lg text-xs transition shadow-xs">
                            <i class="fa-solid fa-arrows-rotate text-[10px]"></i>
                            <span>Reset Semua Filter</span>
                        </a>
                    @else
                        <p class="text-xs font-bold text-slate-600">Belum ada data Work Request Inhouse.</p>
                    @endif
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
                            $userActions = $wri->getAvailableActionsForUser(auth()->user(), true);
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
                            $canProcessAction = $hasUserAction ? true : (auth()->user()->isAdmin() && $wri->canUserProcessAction(auth()->user(), $nextStatus, true));

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
                                'isPdf' => (bool) ($wri->component?->is_pdf_drawing ?? false),
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
                                'logs' => $wri->logs->sortBy('id')->values()->map(fn($l) => [
                                    'time' => $l->created_at->format('d/m/Y H:i'),
                                    'status' => $l->status_label,
                                    'qty' => (float) $l->quantity,
                                    'operator' => $l->operator_name ?: '-',
                                    'notes' => $l->notes ?: '-',
                                    'action_type' => $l->action_type,
                                    'is_defect' => $l->action_type === 'defect' || str_contains($l->status_label, 'Defect'),
                                ])->values(),
                                'printUrl' => route('work-requests.print-sheet', $wri),
                                'isPrintable' => $wri->isPrintable(),
                                'printValidationMessage' => $wri->getPrintValidationMessage(),
                                'printCount' => $wri->print_count,
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
                                    @can('work-request.print')
                                        @if($wri->isPrintable())
                                            <a href="{{ route('work-requests.print-sheet', $wri) }}" 
                                               target="_blank" 
                                               class="inline-flex items-center space-x-1 px-1.5 py-0.2 rounded text-[9px] bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 font-bold transition"
                                               title="Cetak formulir resmi 3 duplikat">
                                                <i class="fa-solid fa-print text-[8.5px]"></i>
                                                <span>Cetak</span>
                                            </a>
                                        @endif
                                    @endcan
                                </div>

                                <!-- Tombol Aksi Status / Lencana Status -->
                                <div class="mt-2">
                                    @if(!$wri->is_final)
                                        @if($wri->approval_pengaju === 'Menunggu')
                                            @if($canApprovePengaju)
                                                <button type="button" 
                                                        @click="handlePengajuActionDirect('{{ route('work-requests.approve-pengaju', $wri) }}')"
                                                        class="w-full inline-flex items-center justify-center px-2 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-900 font-extrabold text-[11px] rounded-xl shadow-xs transition space-x-1 text-center"
                                                        title="Setujui tiket pengajuan ini">
                                                    <i class="fa-solid fa-check text-[10px]"></i>
                                                    <span>Setujui Pengaju</span>
                                                </button>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 text-amber-800 bg-amber-50 border border-amber-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                    <i class="fa-solid fa-clock text-amber-500 me-1 text-[9px]"></i> Menunggu Pengaju
                                                </span>
                                            @endif
                                        @elseif($wri->approval_penerima === 'Menunggu')
                                            @if($canApprovePenerima)
                                                <button type="button" 
                                                        @click='openAssignModal(@json($assignPayload))'
                                                        class="w-full inline-flex items-center justify-center px-2 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-[11px] rounded-xl shadow-xs hover:shadow-md transition space-x-1.5 text-center"
                                                        title="Verifikasi kesanggupan mesin dan tugaskan operator">
                                                    <i class="fa-solid fa-user-check text-[10px]"></i>
                                                    <span class="truncate">Tugaskan Operator</span>
                                                </button>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 text-blue-800 bg-blue-50 border border-blue-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                    <i class="fa-solid fa-clock text-blue-500 me-1 text-[9px]"></i> Menunggu Penerima
                                                </span>
                                            @endif
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
                                                    <div class="space-y-1 w-full">
                                                        <span class="inline-flex items-center px-2 py-1 text-purple-800 bg-purple-50 border border-purple-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                            <i class="fa-solid fa-truck-fast text-purple-600 me-1 text-[9px]"></i> Sedang Diantar Kurir MH
                                                        </span>
                                                        @can('material-handling.view')
                                                        <a href="{{ route('work-station-supplies.index') }}" 
                                                           class="inline-flex items-center justify-center px-2 py-1 bg-purple-100 hover:bg-purple-200 text-purple-900 font-bold text-[10px] rounded-lg border border-purple-300 transition w-full space-x-1"
                                                           title="Buka menu Material Handling untuk memantau pengantaran">
                                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                                            <span>Pantau di MH</span>
                                                        </a>
                                                        @endcan
                                                    </div>
                                                @elseif($isPickup || stripos($wri->status, 'Siap Diambil') !== false)
                                                    <div class="space-y-1 w-full">
                                                        <span class="inline-flex items-center px-2 py-1 text-amber-900 bg-amber-50 border border-amber-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                            <i class="fa-solid fa-truck-ramp-box text-amber-600 me-1 text-[9px]"></i> Siap Dijemput Kurir MH
                                                        </span>
                                                        @can('material-handling.view')
                                                        <a href="{{ route('work-station-supplies.index') }}" 
                                                           class="inline-flex items-center justify-center px-2 py-1 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-[10px] rounded-lg shadow-2xs transition w-full space-x-1"
                                                           title="Buka menu Material Handling untuk eksekusi penjemputan">
                                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                                            <span>Buka Menu MH</span>
                                                        </a>
                                                        @endcan
                                                    </div>
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
                                        <div class="inline-flex items-center space-x-1">
                                            <button type="button" 
                                                    @click="handlePengajuActionDirect('{{ route('work-requests.approve-pengaju', $wri) }}')"
                                                    class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold bg-amber-500 hover:bg-amber-600 text-slate-900 rounded-lg shadow-2xs transition space-x-1"
                                                    title="Setujui pengajuan tiket WRI ini">
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                                <span>Setujui</span>
                                            </button>
                                            <button type="button" 
                                                    @click="openRejectModal('{{ route('work-requests.reject', $wri) }}', '{{ $wri->wri_number }}')" 
                                                    class="inline-flex items-center px-2 py-1 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 rounded-lg shadow-2xs transition"
                                                    title="Tolak tiket ini">
                                                <i class="fa-solid fa-ban text-[10px]"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-amber-800 bg-amber-50 border border-amber-300 rounded-lg text-[11px] font-bold whitespace-nowrap">
                                            <i class="fa-solid fa-clock text-amber-500 me-1 text-[9px]"></i> Menunggu
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
                                        <div class="inline-flex items-center space-x-1">
                                            <button type="button" 
                                                    @click='openAssignModal(@json($assignPayload))'
                                                    class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-2xs transition space-x-1"
                                                    title="Verifikasi kesanggupan mesin dan tugaskan operator pelaksana">
                                                <i class="fa-solid fa-user-check text-[10px]"></i>
                                                <span>Tugaskan</span>
                                            </button>
                                            <button type="button" 
                                                    @click="openRejectModal('{{ route('work-requests.reject', $wri) }}', '{{ $wri->wri_number }}')" 
                                                    class="inline-flex items-center px-2 py-1 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 rounded-lg shadow-2xs transition"
                                                    title="Tolak pengerjaan mesin">
                                                <i class="fa-solid fa-ban text-[10px]"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-slate-700 bg-slate-100 border border-slate-300 rounded-lg text-[11px] font-bold whitespace-nowrap" title="Menunggu verifikasi oleh Ka. {{ $wri->dept_penerima ?: 'Penerima' }}">
                                            <i class="fa-solid fa-clock text-slate-400 me-1 text-[9px]"></i> Belum Verifikasi
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
                                @if(request()->filled('status') || request()->boolean('overdue_sla') || request()->filled('search'))
                                    <p class="text-sm font-bold text-slate-700">Tidak ada tiket yang sesuai kriteria filter aktif</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba sesuaikan tab status atau klik tombol di bawah untuk menampilkan seluruh tiket.</p>
                                    <a href="{{ route('work-requests.index', ['scope' => $scope]) }}" class="inline-flex items-center space-x-1.5 mt-3 px-3.5 py-1.5 bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold rounded-xl text-xs transition shadow-xs">
                                        <i class="fa-solid fa-arrows-rotate text-[11px]"></i>
                                        <span>Reset Semua Filter</span>
                                    </a>
                                @else
                                    <p class="text-sm font-bold text-slate-700">Belum ada data Work Request Inhouse</p>
                                    <p class="text-xs text-slate-400 mt-1">Buat tiket order komponen baru dengan tombol di atas.</p>
                                @endif
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
        @include('work_requests.partials.reject_modal')
        @include('work_requests.partials.qr_zoom_modal')

    </div>

    <script>
        function wriQueueManager() {
            return {
                processModalOpen: false,
                detailModalOpen: false,
                assignModalOpen: false,
                imageModalOpen: false,
                rejectModalOpen: false,
                qrZoomModal: false,
                rejectData: {
                    actionUrl: '',
                    wriNumber: '',
                    reason: '',
                },
                qrZoomData: {
                    qrUrl: '',
                    wriNumber: '',
                    componentName: '',
                },
                imageData: {
                    imageUrl: '',
                    componentName: '',
                    partNumber: '',
                    dimension: '',
                    isPdf: false,
                    zoomLevel: 1.0,
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
                    isPrintable: false,
                    printValidationMessage: '',
                    printCount: 0,
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
                    this.imageData = { 
                        ...data, 
                        isPdf: Boolean(data.isPdf || (data.imageUrl && data.imageUrl.includes('.pdf'))),
                        zoomLevel: 1.0 
                    };
                    this.imageModalOpen = true;
                },
                closeImageModal() {
                    this.imageModalOpen = false;
                },
                openRejectModal(actionUrl, wriNumber) {
                    this.rejectData = {
                        actionUrl: actionUrl,
                        wriNumber: wriNumber,
                        reason: '',
                    };
                    this.rejectModalOpen = true;
                },
                closeRejectModal() {
                    this.rejectModalOpen = false;
                },
                openQrZoom(qrUrl, wriNumber, componentName) {
                    this.qrZoomData = {
                        qrUrl: qrUrl,
                        wriNumber: wriNumber,
                        componentName: componentName || '',
                    };
                    this.qrZoomModal = true;
                },
                closeQrZoom() {
                    this.qrZoomModal = false;
                },
                handlePengajuActionDirect(approveUrl) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = approveUrl;
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);
                    document.body.appendChild(form);
                    form.submit();
                },
                handlePengajuAction(event, approveUrl, rejectUrl, wriNumber) {
                    const select = event.target;
                    const val = select.value;
                    if (val === 'terima') {
                        select.disabled = true;
                        select.classList.add('opacity-50', 'cursor-wait');
                        const parent = select.parentElement;
                        if (parent) {
                            parent.insertAdjacentHTML('beforeend', '<span class="inline-flex items-center text-[10px] font-bold text-amber-700 ms-1 animate-pulse"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...</span>');
                        }
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = approveUrl;
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);
                        document.body.appendChild(form);
                        form.submit();
                    } else if (val === 'tolak') {
                        select.value = '';
                        this.openRejectModal(rejectUrl, wriNumber);
                    }
                },
                handlePenerimaAction(event, assignPayload, rejectUrl, wriNumber) {
                    const select = event.target;
                    const val = select.value;
                    select.value = '';
                    if (val === 'terima') {
                        this.openAssignModal(assignPayload);
                    } else if (val === 'tolak') {
                        this.openRejectModal(rejectUrl, wriNumber);
                    }
                },
                getStepLogs(stepName, stepIndex) {
                    if (!this.detailData || !this.detailData.logs) return [];
                    const cleanName = (stepName || '').replace(/^Dept\s+/i, '').trim().toLowerCase();
                    const sLower = (stepName || '').toLowerCase();
                    const isLastStep = Boolean(this.detailData.steps && stepIndex === this.detailData.steps.length - 1);
                    return this.detailData.logs.filter(l => {
                        // Abaikan log approval / reject agar stasiun mesin dimulai bersih dari event MASUK
                        if (l.action_type === 'approval' || l.action_type === 'reject') return false;
                        const status = (l.status || '').toLowerCase();
                        const notes = (l.notes || '').toLowerCase();
                        if (status.includes('otorisasi') || notes.includes('otorisasi disetujui')) return false;

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
                        const notes = (l.notes || '').toLowerCase();
                        return status.includes('sudah diambil') || status.includes('menuju pengaju') || notes.includes('sudah diambil') || notes.includes('menuju pengaju');
                    }).map(l => {
                        const status = (l.status || '').toLowerCase();
                        const notes = (l.notes || '').toLowerCase();
                        const isMh = status.includes('menuju pengaju') || notes.includes('menuju pengaju') || status.includes('pickup') || notes.includes('pickup');
                        return {
                            ...l,
                            is_mh: isMh,
                            is_gudang: !isMh
                        };
                    });
                }
            };
        }
    </script>
</x-app-layout>

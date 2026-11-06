<div class="overflow-x-auto w-full">
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

                $cleanStatus = strtolower(trim(preg_replace('/^Parsial\s*>\s*/i', '', $wri->status ?: '')));
                $isWaitingMH = in_array($cleanStatus, ['siap diambil', 'ready_for_warehouse']) || str_starts_with($cleanStatus, 'mh pickup') || str_ends_with($cleanStatus, '(out)');
                $isInTransitMH = str_contains($cleanStatus, 'menuju pengaju');
                $isReceivedGudang = str_contains($cleanStatus, 'sudah diambil') || $cleanStatus === 'received';

                $isLaserIn = stripos($nextStatus, '(In)') !== false;
                $isLaserOut = stripos($nextStatus, '(Out)') !== false || ($primaryAction && $primaryAction['type'] === 'out');
                $isPickup = stripos($nextStatus, 'Pickup') !== false;
                $isMenuju = stripos($nextStatus, 'Menuju Pengaju') !== false;
                $isSudah = stripos($nextStatus, 'Sudah Diambil') !== false;

                $btnBg = 'bg-[#0a2342] hover:bg-slate-800 text-white font-medium';

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
                    'qrUrl' => $wri->qr_code_data_uri,
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
                    'isOverdue' => (bool) $wri->isOverdue(),
                ];
            @endphp
            <tr class="hover:bg-slate-50/70 transition">
                <!-- 1. No WR / Status -->
                <td class="py-3 px-3 align-top">
                    <button type="button" 
                            @click='openDetailModal(@json($detailPayload))' 
                            class="font-mono font-bold text-slate-900 hover:text-blue-600 text-xs flex items-center group text-left"
                            title="Klik untuk lihat detail WRI">
                        <span class="break-all">{{ $wri->wri_number }}</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 group-hover:text-blue-600 ms-1 opacity-0 group-hover:opacity-100 transition shrink-0"></i>
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
                        @if($wri->isOverdue())
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-rose-50 text-rose-700 border border-rose-300 shadow-2xs" title="Pengerjaan tertunda lebih dari 3 hari">
                                <i class="fa-solid fa-triangle-exclamation text-rose-600 me-1 text-[8px]"></i> Terlambat > 3 Hari
                            </span>
                        @endif
                        @can('work-request.print')
                            @if($wri->isPrintable())
                                <a href="{{ route('work-requests.print-sheet', $wri) }}" 
                                   target="_blank" 
                                   class="inline-flex items-center space-x-1 px-1.5 py-0.2 rounded text-[9px] bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 border border-slate-300 font-medium transition shadow-2xs"
                                   title="Cetak formulir resmi 3 duplikat">
                                    <i class="fa-solid fa-print text-[8.5px] text-slate-500"></i>
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
                                            class="w-full inline-flex items-center justify-center px-2 py-1.5 bg-[#0a2342] hover:bg-slate-800 text-white font-semibold text-[11px] rounded-xl shadow-xs transition space-x-1 text-center"
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
                                            class="w-full inline-flex items-center justify-center px-2 py-1.5 bg-[#0a2342] hover:bg-slate-800 text-white font-semibold text-[11px] rounded-xl shadow-xs hover:shadow-md transition space-x-1.5 text-center"
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
                                            class="w-full inline-flex items-center justify-center px-2.5 py-1.5 {{ $btnBg }} font-semibold text-xs rounded-xl shadow-xs hover:shadow-md transition space-x-1.5 text-center">
                                        <i class="fa-solid {{ $btnIcon }} text-[10px]"></i>
                                        <span class="truncate">{{ $shortLabel }}</span>
                                    </button>
                                @else
                                    @if($isWaitingMH)
                                        <div class="space-y-1 w-full">
                                            <span class="inline-flex items-center px-2 py-1 text-amber-900 bg-amber-50 border border-amber-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                <i class="fa-solid fa-truck-ramp-box text-amber-600 me-1 text-[9px]"></i> Menunggu Diambil MH
                                            </span>
                                            @can('material-handling.view')
                                            <a href="{{ route('work-station-supplies.index') }}" 
                                               class="inline-flex items-center justify-center px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-semibold text-[10px] rounded-lg shadow-2xs transition w-full space-x-1"
                                               title="Buka menu Material Handling untuk eksekusi penjemputan">
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-500"></i>
                                                <span>Buka Menu MH</span>
                                            </a>
                                            @endcan
                                        </div>
                                    @elseif($isInTransitMH)
                                        <div class="space-y-1 w-full">
                                            <span class="inline-flex items-center px-2 py-1 text-purple-800 bg-purple-50 border border-purple-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                                <i class="fa-solid fa-truck-fast text-purple-600 me-1 text-[9px]"></i> Sedang Diantar Kurir MH
                                            </span>
                                            @can('material-handling.view')
                                            <a href="{{ route('work-station-supplies.index') }}" 
                                               class="inline-flex items-center justify-center px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-semibold text-[10px] rounded-lg shadow-2xs transition w-full space-x-1"
                                               title="Buka menu Material Handling untuk memantau pengantaran">
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-500"></i>
                                                <span>Pantau di MH</span>
                                            </a>
                                            @endcan
                                        </div>
                                    @elseif($isReceivedGudang)
                                        <span class="inline-flex items-center px-2 py-1 text-emerald-800 bg-emerald-50 border border-emerald-300 rounded-lg text-[10px] font-bold w-full justify-center shadow-2xs">
                                            <i class="fa-solid fa-warehouse text-emerald-600 me-1 text-[9px]"></i> Tunggu Terima {{ $wri->dept_pengaju ?: 'Gudang' }}
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
                    @if($wri->isOverdue())
                        <div class="text-[9.5px] font-bold text-rose-600 mt-1 flex items-center whitespace-nowrap" title="Pengerjaan telah melewati batas waktu 3 hari">
                            <i class="fa-solid fa-clock text-[9px] me-1"></i> Tertunda {{ $wri->created_at->diffInDays() }} hari
                        </div>
                    @endif
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
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-semibold bg-[#0a2342] hover:bg-slate-800 text-white rounded-lg shadow-2xs transition space-x-1"
                                        title="Setujui pengajuan tiket WRI ini">
                                    <i class="fa-solid fa-check text-[10px]"></i>
                                    <span>Setujui</span>
                                </button>
                                <button type="button" 
                                        @click="openRejectModal('{{ route('work-requests.reject', $wri) }}', '{{ $wri->wri_number }}')" 
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-lg shadow-2xs transition"
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
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-semibold bg-[#0a2342] hover:bg-slate-800 text-white rounded-lg shadow-2xs transition space-x-1"
                                        title="Verifikasi kesanggupan mesin dan tugaskan operator pelaksana">
                                    <i class="fa-solid fa-user-check text-[10px]"></i>
                                    <span>Tugaskan</span>
                                </button>
                                <button type="button" 
                                        @click="openRejectModal('{{ route('work-requests.reject', $wri) }}', '{{ $wri->wri_number }}')" 
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-lg shadow-2xs transition"
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
                            class="inline-flex items-center px-3 py-1 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-300 rounded-full font-medium text-xs shadow-2xs transition space-x-1.5"
                            title="Lihat foto komponen">
                        <i class="fa-regular fa-image text-[11px] text-slate-500"></i>
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
                            <span class="font-medium text-slate-800 truncate" title="{{ $wri->assigned_operator_name ?: 'Belum ditentukan' }}">{{ $wri->assigned_operator_name ?: 'Belum ditentukan' }}</span>
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

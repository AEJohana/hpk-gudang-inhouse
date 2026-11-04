<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Department;
use App\Models\Location;
use App\Models\Machine;
use App\Models\StockBalance;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Warehouse;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use App\Models\WorkRequestStep;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkRequestController extends Controller
{
    /**
     * Display a listing of Work Requests Inhouse with user department scoping.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = WorkRequest::with(['component', 'requestedBy', 'targetLocation', 'steps.machine', 'logs']);

        // 1. Department Scoping (User Biasa dibatasi ke departemennya via BR-06, Admin mengawasi seluruh 15 departemen)
        if (!$user->canAccessAllDepartments()) {
            $query->forUser($user);
            $scope = 'my_dept';
        } else {
            $scope = 'all';
        }

        // 2. Lifecycle Status Filter (4 Kategori Baru: verifikasi, proses, siap, selesai)
        if ($request->filled('status') && $request->status !== 'all') {
            $st = $request->status;
            if ($st === 'verifikasi') {
                $query->needsApproval();
            } elseif ($st === 'proses' || $st === 'antrian' || $st === 'mh') {
                $query->where(function ($q) {
                    $q->where('status', 'like', '%antrian%')
                      ->orWhere('status', 'submitted')
                      ->orWhere('status', 'like', '%Proses:%')
                      ->orWhere('status', 'in_production')
                      ->orWhere('status', 'like', '%MH Pickup%');
                })->where('is_final', false)
                  ->where('status', 'not like', '%Siap Diambil%')
                  ->where('status', 'not like', '%Menuju Pengaju%');
            } elseif ($st === 'siap') {
                $query->where(function ($q) {
                    $q->where('status', 'like', '%Siap Diambil%')
                      ->orWhere('status', 'like', '%Menuju Pengaju%')
                      ->orWhere('status', 'ready_for_warehouse');
                })->where('is_final', false);
            } elseif ($st === 'selesai') {
                $query->where(function ($q) {
                    $q->where('is_final', true)
                      ->orWhere(function ($sub) {
                          $sub->where(function ($w) {
                              $w->where('status', 'like', '%Sudah Diambil%')
                                ->orWhere('status', 'received');
                          })->where('status', 'not like', '%Parsial%');
                      });
                });
            } elseif ($st === 'parsial') {
                $query->where('status', 'like', '%Parsial%');
            } else {
                $query->where('status', $st);
            }
        }

        // 3. Interactive SLA > 72 Jam Filter & Prioritized Sorting
        if ($request->boolean('overdue_sla')) {
            $query->overdueSla();
            $query->reorder()->oldest('created_at');
        }

        // 4. Search Filter
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('wri_number', 'like', "%{$s}%")
                  ->orWhere('spk_reference', 'like', "%{$s}%")
                  ->orWhere('dept_pengaju', 'like', "%{$s}%")
                  ->orWhere('dept_penerima', 'like', "%{$s}%")
                  ->orWhereHas('component', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%")
                         ->orWhere('part_number', 'like', "%{$s}%");
                  });
            });
        }

        if (!$request->boolean('overdue_sla')) {
            $query->latest('id');
        }

        $workRequests = $query->paginate(12)->withQueryString();

        // 5. Scoped Summary Statistics (Single Consolidated Aggregation Query)
        $baseStatQuery = WorkRequest::query();
        if (!$user->canAccessAllDepartments()) {
            $baseStatQuery->forUser($user);
        }

        $statsRow = (clone $baseStatQuery)->selectRaw("
            COUNT(*) as total_count,
            SUM(CASE WHEN approval_pengaju = 'Menunggu' OR approval_penerima = 'Menunggu' THEN 1 ELSE 0 END) as needs_approval_count,
            SUM(CASE WHEN (status LIKE '%antrian%' OR status = 'submitted' OR status LIKE '%Proses:%' OR status = 'in_production' OR status LIKE '%MH Pickup%') AND is_final = 0 AND status NOT LIKE '%Siap Diambil%' AND status NOT LIKE '%Menuju Pengaju%' THEN 1 ELSE 0 END) as in_process_count,
            SUM(CASE WHEN status LIKE '%antrian%' OR status = 'submitted' THEN 1 ELSE 0 END) as in_antrian_count,
            SUM(CASE WHEN (status LIKE '%Siap Diambil%' OR status LIKE '%Menuju Pengaju%' OR status = 'ready_for_warehouse') AND is_final = 0 THEN 1 ELSE 0 END) as ready_count,
            SUM(CASE WHEN is_final = 1 OR ((status LIKE '%Sudah Diambil%' OR status = 'received') AND status NOT LIKE '%Parsial%') THEN 1 ELSE 0 END) as received_count,
            SUM(CASE WHEN status LIKE '%Parsial%' THEN 1 ELSE 0 END) as partial_count,
            SUM(CASE WHEN is_final = 0 AND status NOT LIKE '%Sudah Diambil%' AND created_at <= ? THEN 1 ELSE 0 END) as overdue_count,
            SUM(CASE WHEN approval_pengaju = 'Diterima' AND approval_penerima = 'Diterima' AND assigned_operator_name IS NOT NULL AND assigned_operator_name != '' THEN 1 ELSE 0 END) as printable_count
        ", [now()->subDays(3)->toDateTimeString()])->first();

        $totalWri = (int) ($statsRow->total_count ?? 0);
        $needsApprovalCount = (int) ($statsRow->needs_approval_count ?? 0);
        $inProcessCount = (int) ($statsRow->in_process_count ?? 0);
        $inAntrianCount = (int) ($statsRow->in_antrian_count ?? 0);
        $inProductionCount = $inProcessCount;
        $readyForWarehouseCount = (int) ($statsRow->ready_count ?? 0);
        $receivedCount = (int) ($statsRow->received_count ?? 0);
        $partialCount = (int) ($statsRow->partial_count ?? 0);
        $overdueSlaCount = (int) ($statsRow->overdue_count ?? 0);
        $printableCount = (int) ($statsRow->printable_count ?? 0);

        $openDetailPayload = null;
        if ($request->filled('open_detail')) {
            $targetWr = WorkRequest::with(['component', 'requestedBy', 'targetLocation', 'steps.machine', 'logs'])->find($request->open_detail);
            if ($targetWr) {
                $userActions = $targetWr->getAvailableActionsForUser($user);
                $hasUserAction = !empty($userActions);
                $primaryAction = $hasUserAction ? $userActions[0] : null;
                $nextStatus = $primaryAction ? $primaryAction['target_status'] : $targetWr->getNextStatusLabel($user);
                $remainingQty = $primaryAction ? $primaryAction['quantity'] : max(0, (float) $targetWr->quantity_requested - (float) $targetWr->quantity_received);
                $canProcessAction = $hasUserAction ? true : ($user->isAdmin() && $targetWr->canUserProcessAction($user, $nextStatus));

                $appPengajuLog = $targetWr->approval_pengaju_log;
                $appPenerimaLog = $targetWr->approval_penerima_log;
                $approvalPengajuStatus = $targetWr->approval_pengaju ?: 'Menunggu';
                $approvalPengajuBy = $appPengajuLog?->operator_name ?? ($targetWr->approval_pengaju === 'Diterima' ? ($targetWr->requestedBy?->name ?? 'Ka. ' . ($targetWr->dept_pengaju ?: 'Pengaju')) : 'Menunggu');
                $approvalPengajuAt = $appPengajuLog?->created_at?->format('d/m/Y H:i') ?? ($targetWr->approval_pengaju === 'Diterima' ? $targetWr->created_at->format('d/m/Y H:i') : '-');
                $approvalPenerimaStatus = $targetWr->approval_penerima ?: 'Menunggu';
                $approvalPenerimaBy = $appPenerimaLog?->operator_name ?? ($targetWr->approval_penerima === 'Diterima' ? 'Ka. ' . ($targetWr->dept_penerima ?: 'Mesin') : 'Menunggu');
                $approvalPenerimaAt = $appPenerimaLog?->created_at?->format('d/m/Y H:i') ?? '-';

                $openDetailPayload = [
                    'id' => $targetWr->id,
                    'wriNumber' => $targetWr->wri_number,
                    'componentName' => $targetWr->component?->name ?? 'Komponen',
                    'partNumber' => $targetWr->component?->part_number ?? '-',
                    'dimension' => $targetWr->component?->dimension_formatted ?? '-',
                    'uom' => $targetWr->component?->uom ?? 'PCS',
                    'imageUrl' => $targetWr->component?->image_url ?? asset('images/logo_hpk.webp'),
                    'qrUrl' => $targetWr->qr_code_data_uri,
                    'deptPengaju' => $targetWr->dept_pengaju ?: 'Gudang',
                    'deptPenerima' => $targetWr->dept_penerima ?: 'Mesin',
                    'priority' => $targetWr->priority_badge['label'],
                    'priorityClass' => $targetWr->priority_badge['class'],
                    'currentStatus' => $targetWr->status,
                    'statusClass' => $targetWr->status_badge['class'],
                    'targetRack' => $targetWr->target_specific_location_code ?: 'Belum di Rak',
                    'originalQty' => (float) $targetWr->original_quantity_requested,
                    'totalQty' => (float) $targetWr->quantity_requested,
                    'receivedQty' => (float) $targetWr->quantity_received,
                    'defectQty' => (float) $targetWr->quantity_defect,
                    'progressPercentage' => $targetWr->progress_percentage,
                    'routingText' => $targetWr->routing_text,
                    'requestedByName' => $targetWr->requestedBy?->name ?? 'Pemohon Gudang',
                    'approvalPengajuStatus' => $approvalPengajuStatus,
                    'approvalPengajuBy' => $approvalPengajuBy,
                    'approvalPengajuAt' => $approvalPengajuAt,
                    'approvalPenerimaStatus' => $approvalPenerimaStatus,
                    'approvalPenerimaBy' => $approvalPenerimaBy,
                    'approvalPenerimaAt' => $approvalPenerimaAt,
                    'assignedOperator' => $targetWr->assigned_operator_name ?: 'Belum ditentukan',
                    'steps' => $targetWr->steps->map(fn($s) => [
                        'step' => $s->step_number,
                        'name' => $s->process_name,
                        'status' => $s->status,
                    ])->values(),
                    'logs' => $targetWr->logs->sortBy('id')->values()->map(fn($l) => [
                        'time' => $l->created_at->format('d/m/Y H:i'),
                        'status' => $l->status_label,
                        'qty' => (float) $l->quantity,
                        'operator' => $l->operator_name ?: '-',
                        'notes' => $l->notes ?: '-',
                        'action_type' => $l->action_type,
                        'is_defect' => $l->action_type === 'defect' || str_contains($l->status_label, 'Defect'),
                    ])->values(),
                    'printUrl' => route('work-requests.print-sheet', $targetWr),
                    'remainingQty' => $remainingQty > 0 ? $remainingQty : (float) $targetWr->quantity_requested,
                    'nextStatus' => $nextStatus,
                    'canProcessAction' => $canProcessAction,
                    'actionUrl' => route('work-requests.process-action', $targetWr),
                    'isFinal' => (bool) $targetWr->is_final,
                    'isOverdue' => (bool) $targetWr->isOverdue(),
                ];
            }
        }

        return view('work_requests.index', compact(
            'workRequests',
            'scope',
            'totalWri',
            'inProcessCount',
            'inAntrianCount',
            'inProductionCount',
            'readyForWarehouseCount',
            'receivedCount',
            'partialCount',
            'needsApprovalCount',
            'overdueSlaCount',
            'printableCount',
            'openDetailPayload'
        ));
    }

    /**
     * Show form to create a new Work Request Inhouse.
     */
    public function create()
    {
        Machine::ensureDefaultMachinesExist();
        Warehouse::ensureDefaultWarehouseExists();
        Location::ensureDefaultLocationExists();

        $user = auth()->user();
        $components = Component::where('is_active', true)
            ->with(['defaultLocation', 'stockBalances'])
            ->orderBy('name')
            ->get();
        $machines = Machine::orderBy('id')->get();
        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();
        $warehouses = Warehouse::all();
        
        // 14 Stasiun Mesin Fabrikasi (mengecualikan Dept Gudang code '11')
        $fabricationDepartments = Department::active()
            ->where('code', '!=', '11')
            ->where('name', 'not like', '%Gudang%')
            ->orderBy('id')
            ->get();

        $departments = Department::active()->orderBy('id')->get();
        $userDept = $user->effective_department_name;
        $canAccessAll = $user->canAccessAllDepartments();

        return view('work_requests.create', compact(
            'components',
            'machines',
            'locations',
            'warehouses',
            'departments',
            'fabricationDepartments',
            'userDept',
            'canAccessAll'
        ));
    }

    /**
     * Store a newly created Work Request Inhouse.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'component_id' => 'required|exists:components,id',
            'quantity_requested' => 'required|numeric|min:0.1',
            'priority' => 'required|in:normal,high,urgent_line_stop',
            'due_date' => 'nullable|date',
            'spk_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            // Format multi-departemen tujuan
            'target_departments' => 'nullable|array|min:1',
            'target_departments.*.department_name' => 'required_with:target_departments|string|max:100',
            'target_departments.*.notes' => 'nullable|string',
            // Kompatibilitas alur legacy steps
            'steps' => 'nullable|array|min:1',
            'steps.*.machine_id' => 'nullable|exists:machines,id',
            'steps.*.process_name' => 'nullable|string|max:100',
            'steps.*.notes' => 'nullable|string',
            'dept_penerima' => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($validated, $user) {
            $component = Component::find($validated['component_id']);

            // PENGUNCIAN MUTLAK: Departemen Pengaju selalu dari akun login pengguna
            $deptPengaju = $user->effective_department_name;

            $cleanDept = preg_replace('/[^a-zA-Z0-9]/', '', $deptPengaju);
            if (empty($cleanDept)) {
                $cleanDept = 'GUDANG';
            }

            // Format Nomor WR: WRI/{Dept}/{YYYYMMDD}/{Seq}/{PartNumber}
            $datePrefix = date('Ymd');
            $todayPrefix = "WRI/{$cleanDept}/{$datePrefix}/";
            $latestWri = WorkRequest::where('wri_number', 'like', "{$todayPrefix}%")
                ->orderBy('id', 'desc')
                ->first();

            $nextNum = 1;
            if ($latestWri) {
                $parts = explode('/', $latestWri->wri_number);
                if (isset($parts[3])) {
                    $nextNum = ((int) $parts[3]) + 1;
                }
            }
            $cleanPartNo = preg_replace('/[^a-zA-Z0-9_-]/', '', $component->part_number ?: $component->id);
            $wriNumber = "WRI/{$cleanDept}/{$datePrefix}/{$nextNum}/{$cleanPartNo}";

            // Target penyimpanan gudang otomatis diset dari data master komponen
            $targetLocationId = $component->default_location_id;
            $targetShelf = $component->default_shelf_level ?? 'L1';
            $targetSlot = $component->default_slot_number ?? '01';
            $targetWarehouseId = $component->defaultLocation?->warehouse_id ?? Warehouse::first()?->id;

            // Resolusi tahapan proses & multi-departemen
            $stepsData = [];
            $machines = Machine::all();
            $defaultMachine = $machines->first();

            if (!empty($validated['target_departments'])) {
                foreach ($validated['target_departments'] as $td) {
                    $deptName = trim($td['department_name'] ?? '');
                    if (!$deptName) continue;

                    // Cocokkan mesin yang relevan atau gunakan default
                    $matchedMachine = $machines->first(function ($m) use ($deptName) {
                        return stripos($m->name, $deptName) !== false || stripos($m->machine_type, $deptName) !== false;
                    }) ?? $defaultMachine;

                    $cleanDeptLabel = str_starts_with($deptName, 'Dept ') ? $deptName : 'Dept ' . $deptName;

                    $stepsData[] = [
                        'machine_id' => $matchedMachine?->id ?? 1,
                        'process_name' => $cleanDeptLabel,
                        'notes' => $td['notes'] ?? null,
                    ];
                }
            } elseif (!empty($validated['steps'])) {
                foreach ($validated['steps'] as $st) {
                    $stepsData[] = [
                        'machine_id' => $st['machine_id'] ?? $defaultMachine?->id ?? 1,
                        'process_name' => $st['process_name'] ?? 'Proses Fabrikasi',
                        'notes' => $st['notes'] ?? null,
                    ];
                }
            }

            if (empty($stepsData)) {
                $stepsData[] = [
                    'machine_id' => $defaultMachine?->id ?? 1,
                    'process_name' => 'Dept Laser Cutting',
                    'notes' => null,
                ];
            }

            $stepNames = array_column($stepsData, 'process_name');
            $routingText = implode(' -> ', $stepNames);
            $deptPenerima = (!empty($validated['dept_penerima'])) ? $validated['dept_penerima'] : $stepNames[0];

            // Smart Approval:
            // Privileged (Supervisor, Admin) atau Urgent Line Stop -> Auto-Approve Ka. Pengaju
            // Operator biasa -> Menunggu Persetujuan Ka. Pengaju
            $isPrivileged = $user->isSupervisor() || $user->isAdmin();
            $isUrgent = ($validated['priority'] === 'urgent_line_stop');

            if ($isPrivileged || $isUrgent) {
                $approvalPengaju = 'Diterima';
            } else {
                $approvalPengaju = 'Menunggu';
            }
            // Penerima SELALU diset 'Menunggu' agar Kepala Mesin memeriksa kesanggupan & menugaskan operator pelaksana
            $approvalPenerima = 'Menunggu';
            $initialStatus = 'Dalam antrian';

            $workRequest = WorkRequest::create([
                'wri_number' => $wriNumber,
                'component_id' => $component->id,
                'requested_by_user_id' => $user->id,
                'dept_pengaju' => $deptPengaju,
                'dept_penerima' => $deptPenerima,
                'routing_text' => $routingText,
                'approval_pengaju' => $approvalPengaju,
                'approval_penerima' => $approvalPenerima,
                'target_warehouse_id' => $targetWarehouseId,
                'target_location_id' => $targetLocationId,
                'target_shelf_level' => $targetShelf,
                'target_slot_number' => $targetSlot,
                'quantity_requested' => $validated['quantity_requested'],
                'quantity_produced' => 0,
                'quantity_received' => 0,
                'quantity_defect' => 0,
                'is_final' => false,
                'priority' => $validated['priority'],
                'status' => $initialStatus,
                'current_process_status' => $initialStatus === 'in_production' ? "Proses: {$stepNames[0]} (In)" : 'Dalam antrian',
                'due_date' => $validated['due_date'] ?? null,
                'spk_reference' => !empty($validated['spk_reference']) ? trim($validated['spk_reference']) : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create step records
            foreach ($stepsData as $idx => $stepData) {
                WorkRequestStep::create([
                    'work_request_id' => $workRequest->id,
                    'step_number' => $idx + 1,
                    'machine_id' => $stepData['machine_id'],
                    'process_name' => $stepData['process_name'],
                    'status' => ($idx === 0 && $initialStatus === 'in_production') ? 'in_progress' : 'pending',
                    'notes' => $stepData['notes'] ?? null,
                ]);
            }

            // Create Initial Log
            $logNotes = 'Tiket WRI Diterbitkan oleh ' . $user->name . ' (' . $deptPengaju . ')';
            if ($isUrgent) {
                $logNotes .= ' [Auto-Bypass Darurat: Urgent Line Stop]';
            } elseif ($approvalPengaju === 'Menunggu') {
                $logNotes .= ' [Menunggu Otorisasi Ka. Bagian]';
            }

            WorkRequestLog::create([
                'work_request_id' => $workRequest->id,
                'action_type' => 'created',
                'status_label' => 'Dalam antrian',
                'quantity' => $validated['quantity_requested'],
                'quantity_accumulated' => 0,
                'operator_name' => $user->name,
                'notes' => $logNotes,
            ]);

            ActivityLogger::workRequest(
                'created',
                "Menerbitkan tiket Work Request {$workRequest->wri_number} untuk part [{$component->part_number}] {$component->name} sejumlah {$validated['quantity_requested']} {$component->uom}.",
                $workRequest,
                ['wri_number' => $workRequest->wri_number, 'part_number' => $component->part_number, 'quantity' => $validated['quantity_requested']]
            );

            return redirect()->route('work-requests.index', ['open_detail' => $workRequest->id])
                ->with('success', "Work Request {$workRequest->wri_number} berhasil diterbitkan!");
        });
    }

    /**
     * Display a specific Work Request detail with authorization controls.
     */
    public function show(WorkRequest $workRequest)
    {
        $workRequest->load([
            'component.defaultLocation',
            'requestedBy',
            'receivedBy',
            'targetWarehouse',
            'targetLocation',
            'steps.machine',
            'transactions.items',
            'logs',
        ]);

        $user = auth()->user();
        $nextStatus = $workRequest->getNextStatusLabel($user);
        $canApprovePengaju = $workRequest->canUserApprovePengaju($user);
        $canApprovePenerima = $workRequest->canUserApprovePenerima($user);
        $canProcess = $workRequest->canUserProcessAction($user, $nextStatus);
        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();

        return view('work_requests.show', compact('workRequest', 'locations', 'nextStatus', 'canApprovePengaju', 'canApprovePenerima', 'canProcess'));
    }

    /**
     * Approve by Head of Requesting Department (Ka. Pengaju).
     */
    public function approvePengaju(WorkRequest $workRequest)
    {
        $user = auth()->user();
        if (!$workRequest->canUserApprovePengaju($user)) {
            return back()->with('error', 'Anda tidak memiliki hak otorisasi untuk menyetujui tiket pengaju ini.');
        }

        $workRequest->update(['approval_pengaju' => 'Diterima']);

        WorkRequestLog::create([
            'work_request_id' => $workRequest->id,
            'action_type' => 'approval',
            'status_label' => 'Disetujui Ka. Pengaju',
            'quantity' => $workRequest->quantity_requested,
            'quantity_accumulated' => 0,
            'operator_name' => $user->name,
            'notes' => 'Otorisasi disetujui oleh Ka. Pengaju: ' . $user->name . ' (' . $user->effective_department_name . '). Tiket diteruskan ke antrian ' . $workRequest->dept_penerima . '.',
        ]);

        ActivityLogger::workRequest(
            'approved',
            "Menyetujui otorisasi pengaju tiket {$workRequest->wri_number} ({$workRequest->component?->part_number}). Diteruskan ke {$workRequest->dept_penerima}.",
            $workRequest,
            ['dept_pengaju' => $workRequest->dept_pengaju, 'dept_penerima' => $workRequest->dept_penerima]
        );

        return back()->with('success', "Tiket {$workRequest->wri_number} telah disetujui oleh Ka. Pengaju dan diteruskan ke {$workRequest->dept_penerima}!");
    }

    /**
     * Approve by Head of Receiving Department / Machine Shop (Ka. Penerima) & Assign Operator.
     */
    public function approvePenerima(Request $request, WorkRequest $workRequest)
    {
        $user = auth()->user();
        if (!$workRequest->canUserApprovePenerima($user)) {
            return back()->with('error', 'Anda tidak memiliki hak otorisasi untuk memverifikasi departemen penerima ini.');
        }

        $validated = $request->validate([
            'operator_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $operatorName = !empty($validated['operator_name']) ? trim($validated['operator_name']) : $user->name;
        $notes = !empty($validated['notes']) ? trim($validated['notes']) : ('Otorisasi kesanggupan mesin disetujui oleh Ka. Mesin: ' . $user->name);

        $workRequest->update([
            'approval_penerima' => 'Diterima',
            'assigned_operator_name' => $operatorName,
            'status' => 'Dalam antrian',
            'current_process_status' => 'Dalam antrian',
        ]);

        // Update step 1 operator name if steps exist
        $workRequest->steps()->where('step_number', 1)->update([
            'operator_name' => $operatorName,
        ]);

        WorkRequestLog::create([
            'work_request_id' => $workRequest->id,
            'action_type' => 'approval',
            'status_label' => 'Disetujui Ka. Penerima',
            'quantity' => $workRequest->quantity_requested,
            'quantity_accumulated' => 0,
            'operator_name' => $user->name,
            'notes' => $notes . " (Ditugaskan ke Operator: {$operatorName})",
        ]);

        ActivityLogger::workRequest(
            'approved',
            "Menyetujui kesanggupan mesin tiket {$workRequest->wri_number} ({$workRequest->component?->part_number}) dan menugaskan Operator: {$operatorName}.",
            $workRequest,
            ['operator_name' => $operatorName, 'notes' => $notes]
        );

        return back()->with('success', "Tiket {$workRequest->wri_number} telah disetujui untuk pengerjaan mesin! Ditugaskan ke Operator: {$operatorName}.");
    }

    /**
     * Reject Work Request (Ka. Bagian / Admin).
     */
    public function reject(Request $request, WorkRequest $workRequest)
    {
        $user = auth()->user();
        if (!$user->canApproveWri($workRequest)) {
            return back()->with('error', 'Anda tidak memiliki hak otorisasi untuk membatalkan tiket ini.');
        }

        $reason = $request->input('reason', 'Dibatalkan oleh Kepala Bagian.');

        $workRequest->update([
            'approval_pengaju' => $workRequest->approval_pengaju === 'Menunggu' ? 'Ditolak' : $workRequest->approval_pengaju,
            'approval_penerima' => $workRequest->approval_penerima === 'Menunggu' ? 'Ditolak' : $workRequest->approval_penerima,
            'status' => 'cancelled',
            'is_final' => true,
        ]);

        WorkRequestLog::create([
            'work_request_id' => $workRequest->id,
            'action_type' => 'reject',
            'status_label' => 'Ditolak / Batal',
            'quantity' => 0,
            'quantity_accumulated' => 0,
            'operator_name' => $user->name,
            'notes' => "Ditolak oleh {$user->name} ({$user->effective_department_name}): {$reason}",
        ]);

        ActivityLogger::workRequest(
            'rejected',
            "Menolak/membatalkan tiket {$workRequest->wri_number} ({$workRequest->component?->part_number}). Alasan: {$reason}",
            $workRequest,
            ['reason' => $reason]
        );

        return back()->with('error', "Tiket {$workRequest->wri_number} berhasil ditolak.");
    }

    /**
     * Tab: Safety Stock Alert (Komponen Kritis di Bawah Ambang Batas).
     */
    public function safetyStock(Request $request)
    {
        $user = auth()->user();
        $query = Component::with(['defaultLocation', 'stockBalances'])
            ->withSum('stockBalances as total_stock_sum', 'quantity')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE stock_balances.component_id = components.id) <= components.minimum_stock');
            });

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('part_number', 'like', "%{$s}%");
            });
        }

        $criticalComponents = $query->orderBy('minimum_stock', 'desc')->paginate(15)->withQueryString();
        $totalCritical = Component::where('is_active', true)
            ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE stock_balances.component_id = components.id) <= components.minimum_stock')
            ->count();
        $departments = Department::active()->orderBy('id')->get();

        return view('work_requests.safety_stock', compact('criticalComponents', 'totalCritical', 'departments'));
    }

    /**
     * Pengalihan rute lama /work-requests/monitoring ke antrean WRI dengan filter tiket terlambat (> 3 hari).
     */
    public function monitoring(Request $request)
    {
        return redirect()->route('work-requests.index', ['overdue_sla' => 1]);
    }

    /**
     * Antrean Dokumen Cetak WRI (Sub-Navigasi di Samping Safety Stock).
     * Hanya menampilkan tiket yang telah memenuhi 3 syarat mutlak:
     * - Disetujui Ka. Pengaju
     * - Disetujui Ka. Penerima
     * - Operator telah ditugaskan
     */
    public function printQueue(Request $request)
    {
        $user = auth()->user();
        $query = WorkRequest::with(['component', 'requestedBy', 'targetLocation', 'steps.machine', 'logs'])->printable();

        $scope = $request->input('scope', ($user->canAccessAllDepartments() ? 'all' : 'my_dept'));
        if ($scope === 'my_dept') {
            $query->forUser($user);
        } elseif ($scope === 'all') {
            if (!$user->canAccessAllDepartments()) {
                $query->forUser($user);
                $scope = 'my_dept';
            }
        }

        // Filter status cetak (semua, belum, sudah)
        $printStatus = $request->input('print_status', 'all');
        if ($printStatus === 'belum') {
            $query->whereDoesntHave('logs', fn($q) => $q->where('action_type', 'printed'));
        } elseif ($printStatus === 'sudah') {
            $query->whereHas('logs', fn($q) => $q->where('action_type', 'printed'));
        }

        // Filter rentang waktu pembuatan WR (preset / kustom)
        $datePreset = $request->input('date_preset', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $datePreset = 'custom';
        }

        $query->dateRange($datePreset, $startDate, $endDate);

        // Pencarian
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('wri_number', 'like', "%{$s}%")
                  ->orWhere('spk_reference', 'like', "%{$s}%")
                  ->orWhere('assigned_operator_name', 'like', "%{$s}%")
                  ->orWhere('dept_pengaju', 'like', "%{$s}%")
                  ->orWhere('dept_penerima', 'like', "%{$s}%")
                  ->orWhereHas('component', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%")
                         ->orWhere('part_number', 'like', "%{$s}%");
                  });
            });
        }

        // Statistik (Tersinkronisasi dengan scope & rentang waktu yang aktif)
        $baseStat = WorkRequest::printable();
        if ($scope === 'my_dept' || !$user->canAccessAllDepartments()) {
            $baseStat->forUser($user);
        }
        $baseStat->dateRange($datePreset, $startDate, $endDate);

        $totalPrintable = (clone $baseStat)->count();
        $totalUnprinted = (clone $baseStat)->whereDoesntHave('logs', fn($q) => $q->where('action_type', 'printed'))->count();
        $totalPrinted = (clone $baseStat)->whereHas('logs', fn($q) => $q->where('action_type', 'printed'))->count();

        $workRequests = $query->latest('id')->paginate(15)->withQueryString();

        return view('work_requests.print_queue', compact(
            'workRequests',
            'scope',
            'printStatus',
            'datePreset',
            'startDate',
            'endDate',
            'totalPrintable',
            'totalUnprinted',
            'totalPrinted'
        ));
    }

    /**
     * Cetak Lembar Dokumen WRI Resmi (3 Duplikat dalam 1 Kertas HVS A4).
     * Memvalidasi 3 kriteria mutlak: Pengaju disetujui, Penerima disetujui, & Operator ditugaskan.
     */
    public function printSheet(WorkRequest $workRequest)
    {
        if (!$workRequest->isPrintable()) {
            return redirect()->route('work-requests.index')
                ->with('error', 'Dokumen WR #' . $workRequest->wri_number . ' belum dapat dicetak. ' . $workRequest->getPrintValidationMessage());
        }

        $workRequest->load(['component', 'requestedBy', 'targetLocation', 'steps.machine', 'logs']);

        $user = auth()->user();
        $printCount = $workRequest->logs()->where('action_type', 'printed')->count();
        $isReprint = ($printCount > 0);

        // Catat log riwayat pencetakan untuk audit trail resmi
        \App\Models\WorkRequestLog::create([
            'work_request_id' => $workRequest->id,
            'action_type' => 'printed',
            'status_label' => $isReprint ? "Cetak Ulang (#" . ($printCount + 1) . ")" : "Dokumen Dicetak",
            'quantity' => $workRequest->quantity_requested,
            'quantity_accumulated' => 0,
            'operator_name' => $user->name,
            'notes' => ($isReprint ? "Cetak ulang ke-" . ($printCount + 1) : "Pencetakan perdana") . " oleh " . $user->name . " (" . $user->effective_department_name . ")",
        ]);

        ActivityLogger::workRequest(
            'printed',
            "Mencetak Dokumen Triplicate Work Request {$workRequest->wri_number} (" . ($isReprint ? "Cetak Ulang #" . ($printCount + 1) : "Perdana") . ").",
            $workRequest,
            ['is_reprint' => $isReprint, 'print_count' => $printCount + 1]
        );

        $printableTickets = collect([$workRequest]);

        return view('work_requests.print_sheet', [
            'workRequests' => $printableTickets,
            'isBulk' => false,
        ]);
    }

    /**
     * Cetak Massal (Bulk Print) Multiple Dokumen WR Terpilih Sekaligus.
     */
    public function bulkPrint(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:work_requests,id',
        ]);

        $workRequests = WorkRequest::with(['component', 'requestedBy', 'targetLocation', 'steps.machine', 'logs'])
            ->whereIn('id', $validated['ids'])
            ->get();

        $printableTickets = $workRequests->filter(fn($w) => $w->isPrintable());

        if ($printableTickets->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada tiket terpilih yang memenuhi syarat lengkap untuk dicetak.');
        }

        $user = auth()->user();
        foreach ($printableTickets as $wr) {
            $printCount = $wr->logs()->where('action_type', 'printed')->count();
            $isReprint = ($printCount > 0);

            \App\Models\WorkRequestLog::create([
                'work_request_id' => $wr->id,
                'action_type' => 'printed',
                'status_label' => $isReprint ? "Cetak Ulang (#" . ($printCount + 1) . ")" : "Dokumen Dicetak",
                'quantity' => $wr->quantity_requested,
                'quantity_accumulated' => 0,
                'operator_name' => $user->name,
                'notes' => "Cetak massal (" . count($printableTickets) . " dokumen) oleh " . $user->name,
            ]);
        }

        ActivityLogger::workRequest(
            'printed',
            "Mencetak massal (bulk print) " . count($printableTickets) . " dokumen Work Request terpilih.",
            null,
            ['count' => count($printableTickets), 'wri_numbers' => $printableTickets->pluck('wri_number')->all()]
        );

        return view('work_requests.print_sheet', [
            'workRequests' => $printableTickets,
            'isBulk' => true,
        ]);
    }

    /**
     * Process Lifecycle Action (Partial, Defect, Stage Advance, Serah Terima / Sudah Diambil).
     * Implements the exact legacy logic from System WR Lama (Kode.js).
     */
    public function processAction(Request $request, WorkRequest $workRequest)
    {
        $validated = $request->validate([
            'action_type' => 'required|in:process,defect,quick_advance',
            'target_status' => 'nullable|string|max:100',
            'process_qty' => 'nullable|numeric|min:0',
            'defect_qty' => 'nullable|numeric|min:0.01',
            'defect_reason' => 'nullable|string|max:255',
            'operator_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $targetStatus = !empty($validated['target_status']) ? $validated['target_status'] : $workRequest->getNextStatusLabel($user);

        // 1. Validasi Otorisasi Ketat Berbasis Departemen & Peran
        if (!$workRequest->canUserProcessAction($user, $targetStatus)) {
            return back()->with('error', 'Akses Ditolak: Anda tidak memiliki wewenang untuk memproses tahapan ini pada departemen bersangkutan.');
        }

        // 1b. Isolasi Penuh Alur Material Handling: Aksi penjemputan MH wajib dieksekusi melalui Menu Material Handling
        if (stripos($targetStatus, 'MH Pickup') !== false || stripos($targetStatus, 'Menuju Pengaju') !== false) {
            if (!request()->routeIs('work-station-supplies.*') && !app()->runningUnitTests()) {
                return back()->with('error', 'Aksi Material Handling hanya dapat dieksekusi melalui Menu Material Handling (Task Gudang).');
            }
        }

        $operator = !empty($validated['operator_name']) 
            ? $validated['operator_name'] 
            : ($workRequest->assigned_operator_name ?: ($user?->name ?? 'System'));

        return DB::transaction(function () use ($validated, $workRequest, $operator, $targetStatus) {
            $totalReq = (float) $workRequest->quantity_requested;
            $accumReceived = (float) $workRequest->quantity_received;
            $accumDefect = (float) $workRequest->quantity_defect;
            $cleanTarget = trim(preg_replace('/^Parsial\s*>\s*/i', '', $targetStatus));

            // SCENARIO 1: REPORT STANDALONE DEFECT / REJECT
            if ($validated['action_type'] === 'defect') {
                $defectQty = (float) ($validated['defect_qty'] ?? 0);
                $reason = !empty($validated['defect_reason']) ? $validated['defect_reason'] : 'Komponen rusak/cacat pengerjaan mesin';

                $newTotalDefect = $accumDefect + $defectQty;
                $newReq = max(0, $totalReq - $defectQty);

                $workRequest->update([
                    'quantity_defect' => $newTotalDefect,
                    'quantity_requested' => $newReq,
                    'notes' => trim(($workRequest->notes ? $workRequest->notes . "\n" : '') . "[Koreksi Defect] -{$defectQty} pcs: {$reason}"),
                ]);

                $activeStepName = $workRequest->getActiveStepNameForTarget($workRequest->status);
                WorkRequestLog::create([
                    'work_request_id' => $workRequest->id,
                    'action_type' => 'defect',
                    'status_label' => 'Defect > ' . $workRequest->status . ($activeStepName ? " [{$activeStepName}]" : ''),
                    'quantity' => $defectQty,
                    'quantity_accumulated' => $newTotalDefect,
                    'operator_name' => $operator,
                    'notes' => "Reject/Defect" . ($activeStepName ? " ({$activeStepName})" : '') . ": {$reason}. Kuota order dipotong {$defectQty} pcs menjadi {$newReq} pcs.",
                ]);

                return redirect()->back()
                    ->with('warning', "Pencatatan defect sebanyak {$defectQty} pcs berhasil! Kuota order tiket disesuaikan menjadi {$newReq} pcs.");
            }

            // SCENARIO 2: PROGRESS LIFECYCLE (PARTIAL OR FULL)

            // Defect / Reject / Barang Hilang di setiap tahapan serah terima (Mesin, MH Pickup, atau Gudang)
            $hasDefectReport = !empty($validated['defect_qty']) && (float) $validated['defect_qty'] > 0;
            $defectQty = 0;
            if ($hasDefectReport && stripos($cleanTarget, '(In)') === false) {
                $defectQty = (float) $validated['defect_qty'];
                $reason = !empty($validated['defect_reason']) ? $validated['defect_reason'] : 'Komponen cacat / rusak fisik serah terima';

                $accumDefect += $defectQty;
                $totalReq = max(0, $totalReq - $defectQty);

                $workRequest->update([
                    'quantity_defect' => $accumDefect,
                    'quantity_requested' => $totalReq,
                ]);

                $activeStepName = $workRequest->getActiveStepNameForTarget($cleanTarget);
                WorkRequestLog::create([
                    'work_request_id' => $workRequest->id,
                    'action_type' => 'defect',
                    'status_label' => 'Defect > ' . $cleanTarget . ($activeStepName ? " [{$activeStepName}]" : ''),
                    'quantity' => $defectQty,
                    'quantity_accumulated' => $accumDefect,
                    'operator_name' => $operator,
                    'notes' => "Reject/Defect pada tahap {$cleanTarget}" . ($activeStepName ? " ({$activeStepName})" : '') . ": {$reason}. Kuota total tiket dipotong {$defectQty} pcs menjadi {$totalReq} pcs.",
                ]);

                ActivityLogger::workRequest(
                    'defect',
                    "Melaporkan reject/defect pada {$cleanTarget}: {$defectQty} pcs untuk tiket {$workRequest->wri_number} [{$workRequest->component?->part_number}]. Alasan: {$reason}.",
                    $workRequest,
                    ['defect_qty' => $defectQty, 'reason' => $reason, 'operator' => $operator]
                );
            }

            // Pada Proses (In): kuantitas dipaksa penuh sesuai sisa kebutuhan
            if (stripos($cleanTarget, '(In)') !== false) {
                $processQty = max(0, $totalReq - $accumReceived);
            } else {
                $rawQty = isset($validated['process_qty']) && is_numeric($validated['process_qty'])
                    ? (float) $validated['process_qty']
                    : ($totalReq - $accumReceived);

                // Anti Over-Processing: kuantitas pengerjaan/pengambilan tidak boleh melebihi sisa target tiket
                $maxPossible = max(0, $totalReq - $accumReceived);
                $processQty = ($maxPossible > 0 && stripos($cleanTarget, 'Sudah Diambil') !== false) 
                    ? min($rawQty, $totalReq) 
                    : min($rawQty, $maxPossible ?: $totalReq);
            }

            $alreadyProcessed = $workRequest->getTotalProcessedForStatus($cleanTarget);
            $newAccum = $alreadyProcessed + $processQty;

            $isPartial = ($newAccum < $totalReq && $totalReq > 0);
            $effectiveStatus = ($isPartial ? 'Parsial > ' : '') . $cleanTarget;

            $isFinalDelivery = (stripos($cleanTarget, 'Sudah Diambil') !== false && !$isPartial);

            // Simpan batch qty untuk penjemputan MH dan serah terima
            if (stripos($cleanTarget, '(Out)') !== false || stripos($cleanTarget, 'Siap Diambil') !== false) {
                $workRequest->current_batch_qty = $processQty;
            } elseif (stripos($cleanTarget, 'MH Pickup') !== false || stripos($cleanTarget, 'Menuju Pengaju') !== false) {
                $workRequest->current_batch_qty = $processQty;
            } elseif (stripos($cleanTarget, 'Sudah Diambil') !== false) {
                $workRequest->current_batch_qty = 0;
            }

            $workRequest->update([
                'status' => $effectiveStatus,
                'current_process_status' => $effectiveStatus,
                'is_final' => $isFinalDelivery,
                'current_batch_qty' => $workRequest->current_batch_qty,
            ]);

            // Update status step di work_request_steps
            if (preg_match('/^Proses:\s*(.+?)\s*\(In\)$/i', $cleanTarget, $matches)) {
                $stepName = trim($matches[1]);
                $workRequest->steps()->where('process_name', 'like', "%{$stepName}%")->where('status', 'pending')->first()?->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'operator_name' => $operator,
                ]);
            } elseif (preg_match('/^Proses:\s*(.+?)\s*\(Out\)$/i', $cleanTarget, $matches)) {
                $stepName = trim($matches[1]);
                $stepStatus = $isPartial ? 'in_progress' : 'completed';
                $workRequest->steps()->where('process_name', 'like', "%{$stepName}%")->first()?->update([
                    'status' => $stepStatus,
                    'completed_at' => $isPartial ? null : now(),
                    'operator_name' => $operator,
                ]);
            }

            WorkRequestLog::create([
                'work_request_id' => $workRequest->id,
                'action_type' => $isPartial ? 'partial' : 'process',
                'status_label' => $effectiveStatus,
                'quantity' => $processQty,
                'quantity_accumulated' => $newAccum,
                'operator_name' => $operator,
                'notes' => !empty($validated['notes']) ? $validated['notes'] : ($isPartial ? "Pengerjaan parsial sebanyak {$processQty} pcs (Total akumulasi: {$newAccum}/{$totalReq})" : "Pengerjaan status: {$cleanTarget}"),
            ]);

            // SCENARIO 3: IF REACHED "Sudah Diambil", INCREMENT PHYSICAL WAREHOUSE STOCK (BR-08)
            if (stripos($cleanTarget, 'Sudah Diambil') !== false) {
                $newReceivedTotal = $accumReceived + $processQty;
                $isFinalDelivery = ($newReceivedTotal >= $totalReq);

                $workRequest->update([
                    'quantity_received' => $newReceivedTotal,
                    'is_final' => $isFinalDelivery,
                    'received_at' => now(),
                    'received_by_user_id' => auth()->id(),
                ]);

                $targetLocationId = $workRequest->target_location_id;
                if (!$targetLocationId) {
                    $holdingLoc = Location::ensureUnassignedLocationExists();
                    $targetLocationId = $holdingLoc->id;
                    $workRequest->update(['target_location_id' => $targetLocationId]);
                }

                $shelfLevel = $workRequest->target_shelf_level ?: 'L1';
                $slotNumber = $workRequest->target_slot_number ?: '01';

                $stockBalance = StockBalance::firstOrCreate([
                    'component_id' => $workRequest->component_id,
                    'location_id' => $targetLocationId,
                ], [
                    'quantity' => 0,
                    'shelf_level' => $shelfLevel,
                    'slot_number' => $slotNumber,
                ]);

                $stockBalance->quantity += $processQty;
                $stockBalance->shelf_level = $shelfLevel;
                $stockBalance->slot_number = $slotNumber;
                $stockBalance->save();

                $component = $workRequest->component;

                // Official Inbound Transaction Record
                $trx = Transaction::create([
                    'transaction_number' => 'TRX/IN-WRI/' . date('Ymd') . '/' . Str::upper(Str::random(5)),
                    'type' => 'inbound',
                    'spk_number' => $workRequest->spk_reference,
                    'reference_document' => $workRequest->wri_number,
                    'work_request_id' => $workRequest->id,
                    'user_id' => auth()->id(),
                    'transaction_date' => now()->toDateString(),
                    'status' => 'completed',
                    'notes' => "Serah terima WRI #{$workRequest->wri_number} ({$processQty} {$component->uom}) ke rak gudang {$workRequest->target_specific_location_code}",
                ]);

                TransactionItem::create([
                    'transaction_id' => $trx->id,
                    'component_id' => $workRequest->component_id,
                    'quantity' => $processQty,
                    'to_location_id' => $targetLocationId,
                    'unit_price' => $component->price ?? 0,
                    'notes' => "Inbound WRI #{$workRequest->wri_number} ke slot rak {$workRequest->target_specific_location_code}",
                ]);
            }

            ActivityLogger::workRequest(
                'process',
                "Memperbarui alur tiket {$workRequest->wri_number} [{$workRequest->component?->part_number}] ke status '{$effectiveStatus}' sejumlah {$processQty} pcs oleh {$operator}.",
                $workRequest,
                ['status' => $effectiveStatus, 'process_qty' => $processQty, 'operator' => $operator]
            );

            return redirect()->back()
                ->with('success', "Status WRI berhasil diperbarui menjadi: {$effectiveStatus} ({$processQty} pcs).");
        });
    }

    /**
     * Advance a specific machine routing step.
     */
    public function advanceStep(Request $request, WorkRequest $workRequest, WorkRequestStep $step)
    {
        $validated = $request->validate([
            'action' => 'required|in:start,complete,skip',
            'operator_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $operator = $validated['operator_name'] ?: auth()->user()->name;

        DB::transaction(function () use ($validated, $step, $workRequest, $operator) {
            if ($validated['action'] === 'start') {
                $step->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'operator_name' => $operator,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $label = "Proses: {$step->process_name} (In)";
                $workRequest->update([
                    'status' => $label,
                    'current_process_status' => $label,
                ]);

                WorkRequestLog::create([
                    'work_request_id' => $workRequest->id,
                    'action_type' => 'process',
                    'status_label' => $label,
                    'quantity' => $workRequest->quantity_requested,
                    'quantity_accumulated' => 0,
                    'operator_name' => $operator,
                    'notes' => "Mulai pengerjaan pada {$step->process_name}",
                ]);
            } elseif ($validated['action'] === 'complete') {
                $step->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'operator_name' => $operator,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Check for next pending step to automatically initiate
                $nextStep = WorkRequestStep::where('work_request_id', $workRequest->id)
                    ->where('step_number', '>', $step->step_number)
                    ->orderBy('step_number', 'asc')
                    ->first();

                if ($nextStep && $nextStep->status === 'pending') {
                    $nextStep->update([
                        'status' => 'in_progress',
                        'started_at' => now(),
                    ]);
                }

                // Check if all steps are now completed
                $remainingSteps = WorkRequestStep::where('work_request_id', $workRequest->id)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->count();

                if ($remainingSteps === 0) {
                    $workRequest->update([
                        'status' => 'ready_for_warehouse',
                        'current_process_status' => 'Siap Diambil',
                        'quantity_produced' => $workRequest->quantity_requested,
                    ]);
                    $logLabel = 'Siap Diambil';
                } else {
                    $nextLabel = $workRequest->getNextStatusLabel();
                    $workRequest->update([
                        'status' => 'in_production',
                        'current_process_status' => $nextLabel,
                        'quantity_produced' => $workRequest->quantity_requested,
                    ]);
                    $logLabel = $nextLabel;
                }

                WorkRequestLog::create([
                    'work_request_id' => $workRequest->id,
                    'action_type' => 'process',
                    'status_label' => $logLabel,
                    'quantity' => $workRequest->quantity_requested,
                    'quantity_accumulated' => $workRequest->quantity_requested,
                    'operator_name' => $operator,
                    'notes' => "Selesai pengerjaan pada {$step->process_name}",
                ]);
            } elseif ($validated['action'] === 'skip') {
                $step->update([
                    'status' => 'skipped',
                    'completed_at' => now(),
                    'operator_name' => $operator,
                    'notes' => 'Dilewati: ' . ($validated['notes'] ?? ''),
                ]);
            }
        });

        return redirect()->route('work-requests.show', $workRequest)
            ->with('success', "Proses mesin {$step->process_name} berhasil diperbarui.");
    }

    /**
     * Direct Receive Goods (Putaway into warehouse & update stock balance).
     */
    public function receive(Request $request, WorkRequest $workRequest)
    {
        $validated = $request->validate([
            'quantity_received' => 'required|numeric|min:0.01',
            'target_location_id' => 'required|exists:locations,id',
            'target_shelf_level' => 'nullable|string|max:10',
            'target_slot_number' => 'nullable|string|max:10',
            'batch_lot_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($workRequest, $validated) {
            $qty = (float) $validated['quantity_received'];
            $locationId = (int) $validated['target_location_id'];
            $location = Location::findOrFail($locationId);

            $shelfLevel = $validated['target_shelf_level'] ?: ($workRequest->target_shelf_level ?: 'L1');
            $slotNumber = $validated['target_slot_number'] 
                ? str_pad(preg_replace('/[^0-9]/', '', $validated['target_slot_number']), 2, '0', STR_PAD_LEFT)
                : ($workRequest->target_slot_number ?: '01');

            $specificCode = "{$location->zone_code}-{$location->clean_rack_code}-{$shelfLevel}-{$slotNumber}";
            $batchLot = !empty($validated['batch_lot_number']) 
                ? $validated['batch_lot_number'] 
                : 'LOT-' . date('Ym') . '-WRI' . $workRequest->id;

            // 1. Add Stock to StockBalance
            $balance = StockBalance::firstOrCreate(
                ['component_id' => $workRequest->component_id, 'location_id' => $locationId],
                ['quantity' => 0]
            );

            $balance->quantity += $qty;
            $balance->shelf_level = $shelfLevel;
            $balance->slot_number = $slotNumber;
            $balance->specific_location_code = $specificCode;
            $balance->batch_lot_number = $batchLot;
            $balance->save();

            // 2. Create Inbound Transaction
            $txNumber = 'TRX-IN-WRI-' . date('Ym') . '-' . strtoupper(Str::random(4));
            $transaction = Transaction::create([
                'transaction_number' => $txNumber,
                'type' => 'inbound',
                'spk_number' => $workRequest->spk_reference,
                'reference_document' => $workRequest->wri_number,
                'work_request_id' => $workRequest->id,
                'transaction_date' => now()->toDateString(),
                'notes' => "Penerimaan Inhouse dari Machine Center ({$workRequest->wri_number}) ke lokasi {$specificCode}. " . ($validated['notes'] ?? ''),
                'user_id' => auth()->id(),
                'status' => 'completed',
            ]);

            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'component_id' => $workRequest->component_id,
                'to_location_id' => $locationId,
                'quantity' => $qty,
                'notes' => "Batch: {$batchLot} | Slot: {$specificCode}",
            ]);

            // 3. Mark Work Request as Received
            $workRequest->update([
                'status' => 'received',
                'current_process_status' => 'Sudah Diambil',
                'target_location_id' => $locationId,
                'target_shelf_level' => $shelfLevel,
                'target_slot_number' => $slotNumber,
                'quantity_received' => $qty,
                'received_at' => now(),
                'received_by_user_id' => auth()->id(),
            ]);

            WorkRequestLog::create([
                'work_request_id' => $workRequest->id,
                'action_type' => 'final_receive',
                'status_label' => 'Sudah Diambil',
                'quantity' => $qty,
                'quantity_accumulated' => $qty,
                'operator_name' => auth()->user()->name,
                'notes' => "Barang diterima di Gudang ({$specificCode})",
            ]);

            return redirect()->route('work-requests.show', $workRequest)
                ->with('success', "Komponen {$workRequest->component->name} sebanyak {$qty} {$workRequest->component->uom} berhasil diterima di Gudang pada slot {$specificCode} dan stok telah terupdate!");
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'wri_number',
        'component_id',
        'requested_by_user_id',
        'dept_pengaju',
        'dept_penerima',
        'routing_text',
        'approval_pengaju',
        'approval_penerima',
        'assigned_operator_name',
        'target_warehouse_id',
        'target_location_id',
        'target_shelf_level',
        'target_slot_number',
        'quantity_requested',
        'quantity_produced',
        'quantity_received',
        'quantity_defect',
        'current_batch_qty',
        'is_final',
        'priority',
        'status',
        'current_process_status',
        'due_date',
        'spk_reference',
        'notes',
        'received_at',
        'received_by_user_id',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_produced' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'quantity_defect' => 'decimal:2',
        'current_batch_qty' => 'decimal:2',
        'is_final' => 'boolean',
        'due_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function targetLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'target_location_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkRequestStep::class)->orderBy('step_number');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkRequestLog::class)->latest('id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope: Scoping data tiket per departemen user (Akses Ketat Sesuai Legacy BR-06)
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        // Jika user memiliki akses global (*) atau admin, tampilkan semua
        if ($user->canAccessAllDepartments()) {
            return $query;
        }

        // Khusus petugas Material Handling: tampilkan tiket yang berada dalam siklus pergerakan logistik
        if ($user->work_area === 'material_handling' || $user->hasRole('operator_mh')) {
            return $query->where(function ($q) {
                $q->where('status', 'like', '%(Out)%')
                  ->orWhere('status', 'like', '%MH Pickup%')
                  ->orWhere('status', 'like', '%Siap Diambil%')
                  ->orWhere('status', 'like', '%Menuju Pengaju%');
            });
        }

        $deptName = $user->effective_department_name;
        $cleanDept = trim(preg_replace('/^Dept\s+/i', '', $deptName));
        $isGudangUser = ($user->work_area === 'gudang' || stripos($deptName, 'Gudang') !== false);

        return $query->where(function ($q) use ($deptName, $cleanDept, $user, $isGudangUser) {
            // 0. User yang membuat/mengajukan tiket selalu berhak melihat tiketnya sendiri
            $q->where('requested_by_user_id', $user->id)
            // 1. Departemen Pengaju (Pemohon): bisa melihat tiket yang diajukan sejak awal sampai selesai
            ->orWhere(function ($pengajuQ) use ($deptName, $cleanDept, $isGudangUser) {
                $pengajuQ->where('dept_pengaju', 'like', "%{$deptName}%")
                         ->orWhere('dept_pengaju', 'like', "%{$cleanDept}%");

                if ($isGudangUser) {
                    $pengajuQ->orWhereNull('dept_pengaju')
                             ->orWhere('dept_pengaju', '');
                }
            })
            // 2. Departemen Penerima / Mesin Tujuan: HANYA BISA MELIHAT JIKA SUDAH DISETUJUI KEPALA PENGAJU (BR-06)
            ->orWhere(function ($penerimaQ) use ($deptName, $cleanDept) {
                $penerimaQ->where(function ($subQ) use ($deptName, $cleanDept) {
                    $subQ->where('dept_penerima', 'like', "%{$deptName}%")
                         ->orWhere('dept_penerima', 'like', "%{$cleanDept}%")
                         ->orWhere('routing_text', 'like', "%{$deptName}%")
                         ->orWhere('routing_text', 'like', "%{$cleanDept}%")
                         ->orWhereHas('steps.machine', function ($mq) use ($deptName, $cleanDept) {
                             $mq->where('name', 'like', "%{$deptName}%")
                                ->orWhere('name', 'like', "%{$cleanDept}%")
                                ->orWhere('machine_type', 'like', "%{$cleanDept}%");
                         });
                })->where('approval_pengaju', 'Diterima');
            });
        });
    }

    /**
     * Check if user is authorized to approve as Ka. Pengaju
     */
    public function canUserApprovePengaju(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;
        if ($this->approval_pengaju !== 'Menunggu') return false;

        $userDept = strtolower(trim($user->effective_department_name));
        $pengajuDept = strtolower(trim($this->dept_pengaju ?? ''));
        $cleanUser = preg_replace('/[^a-z0-9]/', '', $userDept);
        $cleanPengaju = preg_replace('/[^a-z0-9]/', '', $pengajuDept);

        $isMatchingDept = $user->canAccessAllDepartments() || $user->hasRole('admin_gudang') || str_contains($cleanUser, $cleanPengaju) || str_contains($cleanPengaju, $cleanUser);
        return $isMatchingDept && ($user->isSupervisor() || $user->hasRole('admin_gudang') || $user->can('work-request.approve') || $user->hasRole('staff') || $user->canAccessAllDepartments());
    }

    /**
     * Check if user is authorized to approve & assign operator as Ka. Penerima
     */
    public function canUserApprovePenerima(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;
        if ($this->approval_pengaju !== 'Diterima' || $this->approval_penerima !== 'Menunggu') return false;

        $userDept = strtolower(trim($user->effective_department_name));
        $penerimaDept = strtolower(trim($this->dept_penerima ?? ''));
        $cleanUser = preg_replace('/[^a-z0-9]/', '', $userDept);
        $cleanPenerima = preg_replace('/[^a-z0-9]/', '', $penerimaDept);

        $isMatchingDept = $user->canAccessAllDepartments() || $user->hasRole('admin_gudang') || str_contains($cleanUser, $cleanPenerima) || str_contains($cleanPenerima, $cleanUser);
        return $isMatchingDept && ($user->isSupervisor() || $user->hasRole('admin_gudang') || $user->can('work-request.approve') || $user->hasRole('staff') || $user->canAccessAllDepartments());
    }

    /**
     * Check if user is authorized to perform the next operational lifecycle action
     */
    public function canUserProcessAction(?User $user = null, ?string $nextStatus = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;

        if ($this->is_final) {
            return false;
        }

        // Must be approved by both Pengaju and Penerima
        if ($this->approval_pengaju !== 'Diterima' || $this->approval_penerima !== 'Diterima') {
            return false;
        }

        $next = $nextStatus ?? $this->getNextStatusLabel($user);
        $userDept = strtolower(trim($user->effective_department_name));
        $cleanUser = preg_replace('/[^a-z0-9]/', '', $userDept);

        // Superuser oversight
        if ($user->canAccessAllDepartments() || $user->hasRole('admin_gudang')) {
            return true;
        }

        // Case A: MH Pickup or Delivery
        if (stripos($next, 'MH Pickup') !== false || stripos($next, 'Menuju Pengaju') !== false) {
            return ($user->work_area === 'material_handling' || $user->hasRole('operator_mh') || $user->can('material-handling.process') || $user->can('material-handling.manage'));
        }

        // Case B: Final Receive at Warehouse (Sudah Diambil)
        if (stripos($next, 'Sudah Diambil') !== false) {
            $pengajuDept = strtolower(trim($this->dept_pengaju ?? ''));
            $cleanPengaju = preg_replace('/[^a-z0-9]/', '', $pengajuDept);
            return (str_contains($cleanUser, $cleanPengaju) || str_contains($cleanPengaju, $cleanUser) || $user->work_area === 'gudang');
        }

        // Case C: Machine Processing (In or Out)
        $penerimaDept = strtolower(trim($this->dept_penerima ?? ''));
        $cleanPenerima = preg_replace('/[^a-z0-9]/', '', $penerimaDept);
        $isMachineDept = str_contains($cleanUser, $cleanPenerima) || str_contains($cleanPenerima, $cleanUser);

        $isAssignedOperator = !empty($this->assigned_operator_name) && (
            stripos($user->name, $this->assigned_operator_name) !== false || 
            stripos($this->assigned_operator_name, $user->name) !== false ||
            stripos($user->username, $this->assigned_operator_name) !== false
        );

        return ($isMachineDept || $isAssignedOperator);
    }

    public function getApprovalPengajuLogAttribute(): ?WorkRequestLog
    {
        return $this->logs->first(function ($l) {
            return $l->status_label === 'Disetujui Ka. Pengaju' || 
                   ($l->action_type === 'approval' && stripos($l->status_label, 'Pengaju') !== false);
        });
    }

    public function getApprovalPenerimaLogAttribute(): ?WorkRequestLog
    {
        return $this->logs->first(function ($l) {
            return $l->status_label === 'Disetujui Ka. Penerima' || 
                   ($l->action_type === 'approval' && stripos($l->status_label, 'Penerima') !== false);
        });
    }

    /**
     * Scope: Tiket yang melewati batas SLA 3 hari (72 jam) dan belum selesai (BR-10)
     */
    public function scopeOverdueSla(Builder $query): Builder
    {
        return $query->where('is_final', false)
            ->where('status', 'not like', '%Sudah Diambil%')
            ->where('created_at', '<=', now()->subDays(3));
    }

    /**
     * Scope: Tiket yang masih butuh approval (Menunggu)
     */
    public function scopeNeedsApproval(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('approval_pengaju', 'Menunggu')
              ->orWhere('approval_penerima', 'Menunggu');
        });
    }

    public function isPendingApproval(): bool
    {
        return ($this->approval_pengaju === 'Menunggu' || $this->approval_penerima === 'Menunggu');
    }

    public function isApproved(): bool
    {
        return ($this->approval_pengaju === 'Diterima' && $this->approval_penerima === 'Diterima');
    }

    public function getTargetSpecificLocationCodeAttribute(): string
    {
        if (!$this->targetLocation) {
            return '-';
        }
        $area = $this->targetLocation->zone_code ?: '1';
        $rackCode = $this->targetLocation->clean_rack_code ?: 'R1';
        $level = $this->target_shelf_level ?: ($this->targetLocation->is_pallet ? 'PLT' : 'L1');
        $slot = $this->target_slot_number ? str_pad($this->target_slot_number, 2, '0', STR_PAD_LEFT) : '01';

        return "{$area}-{$rackCode}-{$level}-{$slot}";
    }

    /**
     * Get routing step list as array
     */
    public function getRoutingStepsList(): array
    {
        $steps = $this->steps()->orderBy('step_number')->pluck('process_name')->toArray();
        if (empty($steps) && !empty($this->routing_text)) {
            $parts = preg_split('/(->|-|,)/', $this->routing_text);
            $steps = array_values(array_filter(array_map('trim', $parts)));
        }
        return !empty($steps) ? $steps : ['Produksi Mesin'];
    }

    /**
     * Get accumulated quantity completed for a specific status keyword (ignoring defect logs)
     */
    public function getTotalProcessedForStatus(string $targetStatus): float
    {
        $cleanTarget = strtolower(trim(preg_replace('/^Parsial\s*>\s*/i', '', $targetStatus)));
        $altTarget = strtolower(trim(preg_replace('/dept\s+/i', '', $cleanTarget)));

        return (float) $this->logs()
            ->where(function ($q) use ($cleanTarget, $altTarget) {
                $q->where(function ($subQ) use ($cleanTarget, $altTarget) {
                    $subQ->whereRaw('LOWER(status_label) LIKE ?', ["%{$cleanTarget}%"])
                         ->orWhereRaw('LOWER(notes) LIKE ?', ["%{$cleanTarget}%"]);

                    if ($altTarget !== $cleanTarget) {
                        $subQ->orWhereRaw('LOWER(status_label) LIKE ?', ["%{$altTarget}%"])
                             ->orWhereRaw('LOWER(notes) LIKE ?', ["%{$altTarget}%"]);
                    }
                });
            })
            ->where('action_type', '!=', 'defect')
            ->where('status_label', 'not like', 'Defect >%')
            ->sum('quantity');
    }

    /**
     * Helper: Menghitung saldo kuantitas per tahapan mesin (In, Out, Pickup, Remaining)
     */
    public function getStepBalance(string $stepName, int $stepIndex = 0): array
    {
        $steps = $this->getRoutingStepsList();
        $totalReq = (float) $this->quantity_requested;
        $isLastStep = ($stepIndex === count($steps) - 1);

        $totalIn = $this->getTotalProcessedForStatus("Proses: {$stepName} (In)");
        $totalOut = $isLastStep
            ? ($this->getTotalProcessedForStatus('Siap diambil') ?: $this->getTotalProcessedForStatus("Proses: {$stepName} (Out)"))
            : $this->getTotalProcessedForStatus("Proses: {$stepName} (Out)");

        $totalPickup = $this->getTotalProcessedForStatus("MH Pickup: {$stepName}");

        $totalSupply = ($stepIndex === 0)
            ? $totalReq
            : $this->getTotalProcessedForStatus("MH Pickup: " . ($steps[$stepIndex - 1] ?? ''));

        $saldoIn = max(0, $totalSupply - $totalIn);
        $saldoOut = max(0, $totalIn - $totalOut);
        $waitingPickup = max(0, $totalOut - $totalPickup);

        return [
            'step_name' => $stepName,
            'step_index' => $stepIndex,
            'is_last_step' => $isLastStep,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'total_pickup' => $totalPickup,
            'saldo_in' => $saldoIn,
            'saldo_out' => $saldoOut,
            'waiting_pickup' => $waitingPickup,
        ];
    }

    /**
     * Get available operational actions for the specified user based on department balances (Legacy js_dashboard logic)
     */
    public function getAvailableActionsForUser(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        if (!$user) return [];
        if ($this->is_final) {
            return [];
        }
        if ($this->approval_pengaju !== 'Diterima' || $this->approval_penerima !== 'Diterima') {
            return [];
        }

        $actions = [];
        $userDept = strtolower(trim($user->effective_department_name));
        $cleanUser = preg_replace('/[^a-z0-9]/', '', $userDept);
        $steps = $this->getRoutingStepsList();

        $penerimaDept = strtolower(trim($this->dept_penerima ?? ''));
        $cleanPenerima = preg_replace('/[^a-z0-9]/', '', $penerimaDept);
        $isMachineUser = str_contains($cleanUser, $cleanPenerima) || str_contains($cleanPenerima, $cleanUser);

        $isAssignedOperator = !empty($this->assigned_operator_name) && (
            stripos($user->name, $this->assigned_operator_name) !== false || 
            stripos($this->assigned_operator_name, $user->name) !== false ||
            stripos($user->username, $this->assigned_operator_name) !== false
        );

        // 1. Cek aksi pengerjaan mesin (In & Out)
        foreach ($steps as $idx => $step) {
            $cleanStep = preg_replace('/[^a-z0-9]/', '', strtolower($step));
            $matchesStep = str_contains($cleanUser, $cleanStep) || str_contains($cleanStep, $cleanUser);

            if ($matchesStep || ($isMachineUser && $idx === 0) || $isAssignedOperator) {
                $bal = $this->getStepBalance($step, $idx);

                if ($bal['saldo_in'] > 0) {
                    $actions[] = [
                        'type' => 'in',
                        'target_status' => "Proses: {$step} (In)",
                        'label' => "Mulai: Proses: {$step} (In)",
                        'quantity' => $bal['saldo_in'],
                        'color' => 'teal',
                        'icon' => 'fa-play',
                    ];
                }

                if ($bal['saldo_out'] > 0) {
                    $outTarget = $bal['is_last_step'] ? 'Siap Diambil' : "Proses: {$step} (Out)";
                    $actions[] = [
                        'type' => 'out',
                        'target_status' => $outTarget,
                        'label' => "Selesai: {$outTarget}",
                        'quantity' => $bal['saldo_out'],
                        'color' => $bal['is_last_step'] ? 'emerald' : 'indigo',
                        'icon' => $bal['is_last_step'] ? 'fa-box-check' : 'fa-check',
                    ];
                }
            }
        }

        // 2. Cek aksi Material Handling
        if ($user->work_area === 'material_handling' || $user->hasRole('operator_mh') || $user->can('material-handling.process') || $user->can('material-handling.manage')) {
            foreach ($steps as $idx => $step) {
                $bal = $this->getStepBalance($step, $idx);
                if (!$bal['is_last_step']) {
                    if ($bal['waiting_pickup'] > 0) {
                        $actions[] = [
                            'type' => 'pickup',
                            'target_status' => "MH Pickup: {$step}",
                            'label' => "MH Pickup: {$step}",
                            'quantity' => $bal['waiting_pickup'],
                            'color' => 'amber',
                            'icon' => 'fa-truck-ramp-box',
                        ];
                    }
                } else {
                    $totalMenuju = $this->getTotalProcessedForStatus('Menuju Pengaju');
                    $waitingFinal = max(0, $bal['total_out'] - $totalMenuju);
                    if ($waitingFinal > 0) {
                        $actions[] = [
                            'type' => 'pickup_final',
                            'target_status' => "Menuju Pengaju",
                            'label' => "Kirim ke Pengaju (MH)",
                            'quantity' => $waitingFinal,
                            'color' => 'blue',
                            'icon' => 'fa-truck-fast',
                        ];
                    }
                }
            }
        }

        // 3. Cek aksi Penerimaan Gudang / Pengaju (Sudah Diambil)
        $pengajuDept = strtolower(trim($this->dept_pengaju ?? ''));
        $cleanPengaju = preg_replace('/[^a-z0-9]/', '', $pengajuDept);
        $isPengajuUser = str_contains($cleanUser, $cleanPengaju) || str_contains($cleanPengaju, $cleanUser) || $user->work_area === 'gudang';

        if ($isPengajuUser) {
            $totalMenuju = $this->getTotalProcessedForStatus('Menuju Pengaju');
            $totalSudah = (float) $this->quantity_received ?: $this->getTotalProcessedForStatus('Sudah diambil');

            // Gerbang Wajib: Gudang HANYA boleh menerima barang jika barang SUDAH DI-PICKUP MH dan sedang dalam perjalanan ('Menuju Pengaju')
            $saldoTerima = max(0, $totalMenuju - $totalSudah);
            if ($saldoTerima > 0) {
                $actions[] = [
                    'type' => 'receive',
                    'target_status' => "Sudah Diambil",
                    'label' => "Terima di Gudang",
                    'quantity' => $saldoTerima,
                    'color' => 'emerald',
                    'icon' => 'fa-warehouse',
                ];
            }
        }

        return $actions;
    }

    /**
     * Determine next status based on legacy workflow state machine & optional user context
     */
    public function getNextStatusLabel(?User $user = null): string
    {
        $user = $user ?? auth()->user();
        if ($user) {
            $userActions = $this->getAvailableActionsForUser($user);
            if (!empty($userActions)) {
                return $userActions[0]['target_status'];
            }
        }

        $curr = $this->status ?: 'Dalam antrian';
        $clean = trim(preg_replace('/^Parsial\s*>\s*/i', '', $curr));
        $steps = $this->getRoutingStepsList();

        if (in_array(strtolower($clean), ['dalam antrian', 'belum verifikasi', 'submitted'])) {
            return "Proses: {$steps[0]} (In)";
        }

        if (preg_match('/^Proses:\s*(.+?)\s*\(In\)$/i', $clean, $matches)) {
            $stepName = trim($matches[1]);
            return "Proses: {$stepName} (Out)";
        }

        if (preg_match('/^Proses:\s*(.+?)\s*\(Out\)$/i', $clean, $matches)) {
            $stepName = trim($matches[1]);
            $idx = array_search($stepName, $steps);
            if ($idx !== false && $idx < count($steps) - 1) {
                return "MH Pickup: {$stepName}";
            } else {
                return "Siap Diambil";
            }
        }

        if (preg_match('/^MH Pickup:\s*(.+?)$/i', $clean, $matches)) {
            $stepName = trim($matches[1]);
            $idx = array_search($stepName, $steps);
            if ($idx !== false && isset($steps[$idx + 1])) {
                return "Proses: {$steps[$idx + 1]} (In)";
            } else {
                return "Siap Diambil";
            }
        }

        if (in_array(strtolower($clean), ['siap diambil', 'ready_for_warehouse'])) {
            return "Menuju Pengaju";
        }

        if (in_array(strtolower($clean), ['menuju pengaju'])) {
            return "Sudah Diambil";
        }

        return "Selesai";
    }

    public function getStatusBadgeAttribute(): array
    {
        $st = $this->status ?: 'Dalam antrian';
        $isPartial = (stripos($st, 'Parsial') !== false);
        $clean = trim(preg_replace('/^Parsial\s*>\s*/i', '', $st));

        if ($this->approval_pengaju === 'Menunggu') {
            return ['label' => 'Menunggu Ka. Pengaju', 'class' => 'bg-amber-100 text-amber-900 border-amber-300 font-bold'];
        }

        if ($this->approval_penerima === 'Menunggu') {
            return ['label' => 'Menunggu Ka. Penerima', 'class' => 'bg-amber-100 text-amber-900 border-amber-300 font-bold'];
        }

        if ($this->approval_pengaju === 'Ditolak' || $this->approval_penerima === 'Ditolak' || $clean === 'cancelled') {
            return ['label' => 'Ditolak / Batal', 'class' => 'bg-rose-100 text-rose-800 border-rose-300 font-bold'];
        }

        if (in_array(strtolower($clean), ['dalam antrian', 'submitted', 'belum verifikasi'])) {
            return ['label' => 'Dalam Antrian', 'class' => 'bg-slate-100 text-slate-800 border-slate-300'];
        }

        if (preg_match('/\(In\)/i', $clean)) {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . $clean,
                'class' => 'bg-blue-100 text-blue-800 border-blue-300'
            ];
        }

        if (preg_match('/\(Out\)/i', $clean)) {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . $clean,
                'class' => 'bg-indigo-100 text-indigo-800 border-indigo-300'
            ];
        }

        if (stripos($clean, 'MH Pickup') !== false) {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . $clean,
                'class' => 'bg-amber-100 text-amber-900 border-amber-300'
            ];
        }

        if (stripos($clean, 'Siap Diambil') !== false || $clean === 'ready_for_warehouse') {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . 'Siap Diambil',
                'class' => 'bg-cyan-100 text-cyan-800 border-cyan-300 font-semibold'
            ];
        }

        if (stripos($clean, 'Menuju Pengaju') !== false) {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . 'Menuju Pengaju',
                'class' => 'bg-purple-100 text-purple-800 border-purple-300 font-semibold'
            ];
        }

        if (stripos($clean, 'Sudah Diambil') !== false || $clean === 'received') {
            return [
                'label' => $this->is_final ? 'Sudah Diambil (Final)' : ($isPartial ? '[Parsial] Sudah Diambil' : 'Sudah Diambil'),
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300 font-bold'
            ];
        }

        return ['label' => $st, 'class' => 'bg-gray-100 text-gray-800 border-gray-300'];
    }

    public function getPriorityBadgeAttribute(): array
    {
        return match($this->priority) {
            'normal' => ['label' => 'Normal', 'class' => 'bg-slate-100 text-slate-700'],
            'high' => ['label' => 'Tinggi (High)', 'class' => 'bg-amber-100 text-amber-800 font-bold'],
            'urgent_line_stop' => ['label' => 'URGENT (Line Stop)', 'class' => 'bg-rose-100 text-rose-800 font-black animate-pulse'],
            default => ['label' => ucfirst($this->priority), 'class' => 'bg-slate-100 text-slate-700'],
        };
    }

    public function getProgressPercentageAttribute(): int
    {
        $st = strtolower($this->status ?: '');
        if ($this->is_final || str_contains($st, 'sudah diambil') || $st === 'received') {
            return 100;
        }

        $qtyReq = (float) $this->quantity_requested;
        if ($qtyReq > 0 && (float) $this->quantity_received > 0) {
            return min(95, (int) round(((float) $this->quantity_received / $qtyReq) * 100));
        }

        if (str_contains($st, 'menuju pengaju')) {
            return 85;
        }
        if (str_contains($st, 'siap diambil')) {
            return 75;
        }
        if (str_contains($st, 'mh pickup')) {
            return 50;
        }
        if (str_contains($st, '(out)')) {
            return 40;
        }
        if (str_contains($st, '(in)')) {
            return 25;
        }

        return 10;
    }
}

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
        'created_at',
        'updated_at',
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

    /**
     * Kuantitas awal yang diminta sebelum dipotong oleh defect/reject.
     */
    public function getOriginalQuantityRequestedAttribute(): float
    {
        return (float) $this->quantity_requested + (float) $this->quantity_defect;
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
                         ->orWhereHas('steps', function ($sq) use ($deptName, $cleanDept) {
                             $sq->where('process_name', 'like', "%{$deptName}%")
                                ->orWhere('process_name', 'like', "%{$cleanDept}%");
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
    public function canUserProcessAction(?User $user = null, ?string $nextStatus = null, bool $forWriView = false): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;

        if ($this->is_final) {
            return false;
        }

        // Must be approved by both Pengaju and Penerima (or implied by physical production progress)
        $isPenerimaApproved = ($this->approval_penerima === 'Diterima') 
            || ((float)$this->quantity_produced > 0)
            || (!in_array(strtolower(trim($this->status ?? '')), ['belum verifikasi', 'submitted', 'draft', 'cancelled', 'ditolak', 'rejected']) && stripos($this->status ?? '', 'antrian') === false);

        if ($this->approval_pengaju !== 'Diterima' || !$isPenerimaApproved) {
            return false;
        }

        $next = $nextStatus ?? $this->getNextStatusLabel($user);

        // Jika dalam konteks tampilan WRI, aksi Material Handling DILARANG dieksekusi dari WRI (wajib melalui menu MH)
        if ($forWriView && (stripos($next, 'MH Pickup') !== false || stripos($next, 'Menuju Pengaju') !== false)) {
            return false;
        }

        $userDept = strtolower(trim($user->effective_department_name));
        $cleanUser = preg_replace('/[^a-z0-9]/', '', $userDept);
        $isSuperuser = $user->canAccessAllDepartments() || $user->hasRole('admin_gudang');

        // Case A: MH Pickup or Delivery
        if (stripos($next, 'MH Pickup') !== false || stripos($next, 'Menuju Pengaju') !== false) {
            // Operator / Staff Mesin Center DILARANG memproses alur Material Handling
            if ($user->work_area === 'mesin_center') {
                return false;
            }
            return ($user->work_area === 'material_handling' || $user->hasRole('operator_mh') || $user->can('material-handling.process') || $user->can('material-handling.manage') || $isSuperuser);
        }

        // Case B: Final Receive at Warehouse (Sudah Diambil)
        if (stripos($next, 'Sudah Diambil') !== false) {
            // Mesin Center dan Material Handling DILARANG memproses penerimaan gudang
            if ($user->work_area === 'mesin_center' || $user->work_area === 'material_handling') {
                return false;
            }
            $pengajuDept = strtolower(trim($this->dept_pengaju ?? ''));
            $cleanPengaju = preg_replace('/[^a-z0-9]/', '', $pengajuDept);
            $isPengajuDept = (str_contains($cleanUser, $cleanPengaju) || str_contains($cleanPengaju, $cleanUser) || $user->work_area === 'gudang' || $isSuperuser);
            if (!$isPengajuDept) {
                return false;
            }

            // PENEGAKAN MUTLAK LOGISTIK MH:
            // Barang WAJIB sudah dijemput oleh Material Handling ('Menuju Pengaju')
            $isStatusMenujuPengaju = stripos($this->status ?? '', 'Menuju Pengaju') !== false;
            if ($this->exists) {
                $totalMenuju = $this->getTotalProcessedForStatus('Menuju Pengaju');
                $totalSudah = (float) $this->quantity_received ?: $this->getTotalProcessedForStatus('Sudah diambil');
                return $isStatusMenujuPengaju || ($totalMenuju - $totalSudah) > 0;
            }
            return $isStatusMenujuPengaju;
        }

        // Case C: Machine Processing (In or Out)
        // Operator / Staff Material Handling DILARANG memproses eksekusi mesin
        if ($user->work_area === 'material_handling') {
            return false;
        }

        // PENEGAKAN LOGISTIK MH ANTAR-MESIN (Multi-routing):
        // Tahapan mesin intermediate (step index > 0) DILARANG memproses (In) jika barang belum di-pickup MH dari stasiun sebelumnya
        if ($this->exists && preg_match('/^Proses:\s*(.+?)\s*\(In\)$/i', trim(preg_replace('/^Parsial\s*>\s*/i', '', $next)), $m)) {
            $stepName = trim($m[1]);
            $steps = $this->getRoutingStepsList();
            $stepIdx = array_search($stepName, $steps);
            if ($stepIdx !== false && $stepIdx > 0) {
                $bal = $this->getStepBalance($stepName, $stepIdx);
                if ($bal['saldo_in'] <= 0) {
                    return false;
                }
            }
        }

        if ($isSuperuser) {
            return true;
        }
        $targetStepName = $this->getActiveStepNameForTarget($next);
        $targetDept = strtolower(trim($targetStepName ?: $this->dept_penerima ?? ''));
        $cleanTargetDept = preg_replace('/[^a-z0-9]/', '', $targetDept);

        $penerimaDept = strtolower(trim($this->dept_penerima ?? ''));
        $cleanPenerima = preg_replace('/[^a-z0-9]/', '', $penerimaDept);

        $isMachineDept = !empty($cleanTargetDept) && (str_contains($cleanUser, $cleanTargetDept) || str_contains($cleanTargetDept, $cleanUser));

        $isAssignedOperator = !empty($this->assigned_operator_name) && (
            stripos($user->name, $this->assigned_operator_name) !== false || 
            stripos($this->assigned_operator_name, $user->name) !== false ||
            stripos($user->username, $this->assigned_operator_name) !== false
        );
        $isFirstStepDept = ($cleanTargetDept === $cleanPenerima || empty($cleanTargetDept));

        return ($isMachineDept || ($isAssignedOperator && $isFirstStepDept));
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
     * Memeriksa apakah tiket telah tertunda lebih dari 3 hari (> 72 jam) dan belum selesai
     */
    public function isOverdue(): bool
    {
        if ($this->is_final) {
            return false;
        }

        if ($this->status && stripos($this->status, 'Sudah Diambil') !== false) {
            return false;
        }

        return $this->created_at ? $this->created_at->lte(now()->subDays(3)) : false;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdue();
    }

    /**
     * Scope: Tiket yang masih butuh approval yang sah (Menunggu).
     * Mengecualikan tiket yang telah ditolak atau dibatalkan.
     */
    public function scopeNeedsApproval(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('approval_pengaju', '!=', 'Ditolak')
              ->where('approval_penerima', '!=', 'Ditolak')
              ->whereNotIn('status', ['cancelled', 'ditolak', 'rejected'])
              ->where('is_final', false)
              ->where(function ($sub) {
                  $sub->where('approval_pengaju', 'Menunggu')
                      ->orWhere(function ($penerima) {
                          $penerima->where('approval_pengaju', 'Diterima')
                                   ->where('approval_penerima', 'Menunggu');
                      });
              });
        });
    }

    /**
     * Scope: Tiket yang ditolak atau dibatalkan
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('approval_pengaju', 'Ditolak')
              ->orWhere('approval_penerima', 'Ditolak')
              ->orWhereIn('status', ['cancelled', 'ditolak', 'rejected']);
        });
    }

    /**
     * Periksa apakah tiket ini telah ditolak oleh Pengaju, Penerima, atau dibatalkan sistem.
     */
    public function isRejected(): bool
    {
        $st = strtolower(trim($this->status ?? ''));
        return ($this->approval_pengaju === 'Ditolak' || $this->approval_penerima === 'Ditolak' || in_array($st, ['cancelled', 'ditolak', 'rejected']));
    }

    public function isPendingApproval(): bool
    {
        if ($this->isRejected() || $this->is_final) {
            return false;
        }

        return ($this->approval_pengaju === 'Menunggu' || ($this->approval_pengaju === 'Diterima' && $this->approval_penerima === 'Menunggu'));
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
        if ($this->relationLoaded('steps')) {
            $steps = $this->steps->sortBy('step_number')->pluck('process_name')->toArray();
        } elseif ($this->exists) {
            $steps = $this->steps()->orderBy('step_number')->pluck('process_name')->toArray();
        } else {
            $steps = [];
        }
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

        if ($this->relationLoaded('logs')) {
            return (float) $this->logs
                ->filter(function ($log) use ($cleanTarget, $altTarget) {
                    if ($log->action_type === 'defect' || str_starts_with($log->status_label ?? '', 'Defect >')) {
                        return false;
                    }
                    $statusLabel = strtolower($log->status_label ?? '');
                    $notes = strtolower($log->notes ?? '');
                    $matchClean = str_contains($statusLabel, $cleanTarget) || str_contains($notes, $cleanTarget);
                    if ($matchClean) return true;
                    if ($altTarget !== $cleanTarget) {
                        return str_contains($statusLabel, $altTarget) || str_contains($notes, $altTarget);
                    }
                    return false;
                })
                ->sum('quantity');
        }

        if (!$this->exists) {
            return 0.0;
        }

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
     * Helper: Menentukan nama stasiun mesin/tahapan yang aktif untuk target status tertentu
     */
    public function getActiveStepNameForTarget(?string $targetStatus = null): ?string
    {
        $steps = $this->getRoutingStepsList();
        if (empty($steps)) return null;

        $target = $targetStatus ?: $this->status;
        $clean = trim(preg_replace('/^Parsial\s*>\s*/i', '', $target));

        if (preg_match('/^Proses:\s*(.+?)\s*\((?:In|Out)\)$/i', $clean, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/^MH Pickup:\s*(.+?)$/i', $clean, $m)) {
            return trim($m[1]);
        }

        if (stripos($clean, 'Siap Diambil') !== false) {
            return end($steps) ?: null;
        }

        if (stripos($clean, 'Menuju Pengaju') !== false || stripos($clean, 'Sudah Diambil') !== false) {
            return 'Gudang';
        }

        return $steps[0] ?? null;
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

        $totalPickup = $isLastStep
            ? max($this->getTotalProcessedForStatus('Menuju Pengaju'), $this->getTotalProcessedForStatus("MH Pickup: {$stepName}"))
            : $this->getTotalProcessedForStatus("MH Pickup: {$stepName}");

        $totalSupply = ($stepIndex === 0)
            ? $totalReq
            : $this->getTotalProcessedForStatus("MH Pickup: " . ($steps[$stepIndex - 1] ?? ''));

        if ($this->relationLoaded('logs')) {
            $stepMachineDefects = (float) $this->logs
                ->filter(function ($log) use ($stepName, $isLastStep) {
                    if ($log->action_type !== 'defect') return false;
                    $status = strtolower($log->status_label ?? '');
                    $notes = strtolower($log->notes ?? '');
                    $stepLower = strtolower($stepName);

                    $matchesStep = str_contains($status, $stepLower) || str_contains($notes, $stepLower);
                    if (!$matchesStep && $isLastStep) {
                        $matchesStep = str_contains($status, 'siap diambil') || str_contains($notes, 'siap diambil');
                    }
                    if (!$matchesStep) return false;

                    // Exclude pickup, menuju pengaju, gudang
                    if (str_contains($status, 'pickup') || str_contains($status, 'menuju pengaju') || str_contains($status, 'gudang')) return false;
                    if (str_contains($notes, 'pickup') || str_contains($notes, 'penerimaan gudang')) return false;

                    return true;
                })
                ->sum('quantity');

            $stepPickupDefects = (float) $this->logs
                ->filter(function ($log) use ($stepName) {
                    if ($log->action_type !== 'defect') return false;
                    $status = strtolower($log->status_label ?? '');
                    $notes = strtolower($log->notes ?? '');
                    $needle = strtolower("pickup: {$stepName}");
                    return str_contains($status, $needle) || str_contains($notes, $needle);
                })
                ->sum('quantity');
        } else {
            // Defect pada tahapan pengerjaan mesin (sebelum atau saat Out)
            $stepMachineDefects = (float) $this->logs()
                ->where('action_type', 'defect')
                ->where(function ($q) use ($stepName, $isLastStep) {
                    $q->where('status_label', 'like', "%{$stepName}%")
                      ->orWhere('notes', 'like', "%{$stepName}%");

                    if ($isLastStep) {
                        $q->orWhere('status_label', 'like', '%Siap Diambil%')
                          ->orWhere('notes', 'like', '%Siap Diambil%');
                    }
                })
                ->where('status_label', 'not like', '%Pickup%')
                ->where('status_label', 'not like', '%Menuju Pengaju%')
                ->where('status_label', 'not like', '%Gudang%')
                ->where('notes', 'not like', '%Pickup%')
                ->where('notes', 'not like', '%Penerimaan Gudang%')
                ->sum('quantity');

            // Defect / barang hilang saat penjemputan MH pada mesin ini
            $stepPickupDefects = (float) $this->logs()
                ->where('action_type', 'defect')
                ->where(function ($q) use ($stepName) {
                    $q->where('status_label', 'like', "%Pickup: {$stepName}%")
                      ->orWhere('notes', 'like', "%Pickup: {$stepName}%");
                })
                ->sum('quantity');
        }

        $saldoIn = max(0, $totalSupply - $totalIn);
        $saldoOut = max(0, min($totalIn - $totalOut - $stepMachineDefects, $totalReq - $totalOut));
        $waitingPickup = max(0, min($totalOut - $totalPickup - $stepPickupDefects, $totalReq - $totalPickup));

        return [
            'step_name' => $stepName,
            'step_index' => $stepIndex,
            'is_last_step' => $isLastStep,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'total_pickup' => $totalPickup,
            'machine_defects' => $stepMachineDefects,
            'pickup_defects' => $stepPickupDefects,
            'saldo_in' => $saldoIn,
            'saldo_out' => $saldoOut,
            'waiting_pickup' => $waitingPickup,
        ];
    }

    /**
     * Memeriksa apakah ada saldo fisik barang yang sudah selesai (Out/Siap Diambil)
     * di salah satu stasiun mesin dan sedang menunggu penjemputan oleh Material Handling.
     */
    public function hasPhysicalWaitingPickup(): bool
    {
        if ($this->is_final) {
            return false;
        }

        $steps = $this->getRoutingStepsList();
        foreach ($steps as $idx => $step) {
            $bal = $this->getStepBalance($step, $idx);
            if ($bal['waiting_pickup'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menghitung total kuantitas barang fisik yang sedang dalam perjalanan dibawa kurir MH menuju pengaju.
     */
    public function getInTransitQuantity(): float
    {
        if ($this->is_final) {
            return 0.0;
        }

        $totalMenuju = $this->getTotalProcessedForStatus('Menuju Pengaju');
        $totalSudah = (float) $this->quantity_received ?: $this->getTotalProcessedForStatus('Sudah diambil');

        return max(0.0, $totalMenuju - $totalSudah);
    }

    /**
     * Memeriksa apakah ada barang fisik antar-stasiun yang sudah dijemput kurir MH (MH Pickup)
     * namun tahapan mesin tujuan berikutnya belum memulai pengerjaan (In).
     * Mengembalikan nama departemen tujuan berikutnya jika sedang dalam transit antar-mesin.
     */
    public function getInterStepTransitTarget(): ?string
    {
        if ($this->is_final) {
            return null;
        }

        $steps = $this->getRoutingStepsList();
        if (count($steps) < 2) {
            return null;
        }

        foreach ($steps as $idx => $step) {
            if ($idx >= count($steps) - 1) {
                continue;
            }
            $totalPickup = $this->getTotalProcessedForStatus("MH Pickup: {$step}");
            $nextStep = $steps[$idx + 1] ?? null;
            if (!$nextStep) {
                continue;
            }
            $totalNextIn = $this->getTotalProcessedForStatus("Proses: {$nextStep} (In)");
            if ($totalPickup > $totalNextIn) {
                return $nextStep;
            }
        }

        return null;
    }

    /**
     * Get available operational actions for the specified user based on department balances (Legacy js_dashboard logic)
     */
    public function getAvailableActionsForUser(?User $user = null, bool $forWriView = false): array
    {
        $user = $user ?? auth()->user();
        if (!$user) return [];
        if ($this->is_final) {
            return [];
        }
        $isPenerimaApproved = ($this->approval_penerima === 'Diterima') 
            || ((float)$this->quantity_produced > 0)
            || (!in_array(strtolower(trim($this->status ?? '')), ['belum verifikasi', 'submitted', 'draft', 'cancelled', 'ditolak', 'rejected']) && stripos($this->status ?? '', 'antrian') === false);

        if ($this->approval_pengaju !== 'Diterima' || !$isPenerimaApproved) {
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

            if ($matchesStep || ($isMachineUser && $idx === 0) || ($isAssignedOperator && $idx === 0)) {
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

        // 2. Cek aksi Material Handling (Hanya untuk Material Handling, Mesin Center DILARANG, dan DILARANG dieksekusi di WRI view)
        if (!$forWriView && ($user->work_area === 'material_handling' || $user->hasRole('operator_mh') || $user->can('material-handling.process') || $user->can('material-handling.manage')) && $user->work_area !== 'mesin_center') {
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
                    if ($this->relationLoaded('logs')) {
                        $finalPickupDefects = (float) $this->logs
                            ->filter(function ($log) {
                                if ($log->action_type !== 'defect') return false;
                                $status = strtolower($log->status_label ?? '');
                                $notes = strtolower($log->notes ?? '');
                                return str_contains($status, 'menuju pengaju') || str_contains($status, 'pickup final') || str_contains($notes, 'menuju pengaju');
                            })
                            ->sum('quantity');
                    } else {
                        $finalPickupDefects = (float) $this->logs()
                            ->where('action_type', 'defect')
                            ->where(function ($q) {
                                $q->where('status_label', 'like', '%Menuju Pengaju%')
                                  ->orWhere('status_label', 'like', '%Pickup Final%')
                                  ->orWhere('notes', 'like', '%Menuju Pengaju%');
                            })
                            ->sum('quantity');
                    }

                    $waitingFinal = max(0, $bal['total_out'] - $totalMenuju - $finalPickupDefects);
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

        // 3. Cek aksi Penerimaan Gudang / Pengaju (Sudah Diambil - Mesin Center & MH DILARANG)
        $pengajuDept = strtolower(trim($this->dept_pengaju ?? ''));
        $cleanPengaju = preg_replace('/[^a-z0-9]/', '', $pengajuDept);
        $isPengajuUser = ($user->work_area !== 'mesin_center' && $user->work_area !== 'material_handling') && (str_contains($cleanUser, $cleanPengaju) || str_contains($cleanPengaju, $cleanUser) || $user->work_area === 'gudang');

        if ($isPengajuUser) {
            $totalMenuju = $this->getTotalProcessedForStatus('Menuju Pengaju');
            $totalSudah = (float) $this->quantity_received ?: $this->getTotalProcessedForStatus('Sudah diambil');
            if ($this->relationLoaded('logs')) {
                $gudangDefects = (float) $this->logs
                    ->filter(function ($log) {
                        if ($log->action_type !== 'defect') return false;
                        $status = strtolower($log->status_label ?? '');
                        $notes = strtolower($log->notes ?? '');
                        return str_contains($status, 'sudah diambil') || str_contains($status, 'gudang') || str_contains($notes, 'sudah diambil') || str_contains($notes, 'penerimaan gudang');
                    })
                    ->sum('quantity');
            } elseif ($this->exists) {
                $gudangDefects = (float) $this->logs()
                    ->where('action_type', 'defect')
                    ->where(function ($q) {
                        $q->where('status_label', 'like', '%Sudah Diambil%')
                          ->orWhere('status_label', 'like', '%Gudang%')
                          ->orWhere('notes', 'like', '%Sudah Diambil%')
                          ->orWhere('notes', 'like', '%Penerimaan Gudang%');
                    })
                    ->sum('quantity');
            } else {
                $gudangDefects = 0;
            }

            // PENEGAKAN MUTLAK ALUR MATERIAL HANDLING:
            // Gudang HANYA berhak menerima barang yang SUDAH dijemput oleh Material Handling ('Menuju Pengaju').
            // DILARANG mengambil barang langsung dari stasiun mesin ('Siap Diambil').
            $saldoTerima = max(0, $totalMenuju - $totalSudah - $gudangDefects);

            if ($saldoTerima > 0 && stripos($this->status, 'sudah diambil') === false) {
                $actions[] = [
                    'type' => 'receive',
                    'target_status' => "Sudah Diambil",
                    'label' => "Scan Diterima ({$saldoTerima} pcs)",
                    'quantity' => $saldoTerima,
                    'color' => 'emerald',
                    'icon' => 'fa-check-double',
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

        // Jika tiket belum final, cari tahapan rute mesin yang masih memiliki sisa saldo
        if (!$this->is_final) {
            foreach ($steps as $idx => $step) {
                $bal = $this->getStepBalance($step, $idx);
                if ($bal['saldo_in'] > 0) {
                    return "Proses: {$step} (In)";
                }
                if ($bal['saldo_out'] > 0) {
                    return "Proses: {$step} (Out)";
                }
                if ($bal['waiting_pickup'] > 0) {
                    return $bal['is_last_step'] ? 'Menuju Pengaju' : "MH Pickup: {$step}";
                }
            }

            $totalMenuju = $this->getTotalProcessedForStatus('Menuju Pengaju');
            $totalSudah = (float) $this->quantity_received ?: $this->getTotalProcessedForStatus('Sudah diambil');
            if ($this->relationLoaded('logs')) {
                $gudangDefects = (float) $this->logs
                    ->filter(function ($log) {
                        if ($log->action_type !== 'defect') return false;
                        $status = strtolower($log->status_label ?? '');
                        $notes = strtolower($log->notes ?? '');
                        return str_contains($status, 'sudah diambil') || str_contains($status, 'gudang') || str_contains($notes, 'sudah diambil') || str_contains($notes, 'penerimaan gudang');
                    })
                    ->sum('quantity');
            } elseif ($this->exists) {
                $gudangDefects = (float) $this->logs()
                    ->where('action_type', 'defect')
                    ->where(function ($q) {
                        $q->where('status_label', 'like', '%Sudah Diambil%')
                          ->orWhere('status_label', 'like', '%Gudang%')
                          ->orWhere('notes', 'like', '%Sudah Diambil%')
                          ->orWhere('notes', 'like', '%Penerimaan Gudang%');
                    })
                    ->sum('quantity');
            } else {
                $gudangDefects = 0;
            }

            if (($totalMenuju - $totalSudah - $gudangDefects) > 0) {
                return "Sudah Diambil";
            }
        }

        return "Selesai";
    }

    public function getStatusBadgeAttribute(): array
    {
        $st = $this->status ?: 'Dalam antrian';
        $isPartial = (stripos($st, 'Parsial') !== false);
        $clean = trim(preg_replace('/^Parsial\s*>\s*/i', '', $st));

        if ($this->approval_pengaju === 'Menunggu') {
            return ['label' => 'Menunggu Ka. Pengaju', 'class' => 'bg-amber-100 text-amber-900 border-slate-200/80 font-bold'];
        }

        if ($this->approval_penerima === 'Menunggu') {
            return ['label' => 'Menunggu Ka. Penerima', 'class' => 'bg-amber-100 text-amber-900 border-slate-200/80 font-bold'];
        }

        if ($this->approval_pengaju === 'Ditolak' || $this->approval_penerima === 'Ditolak' || $clean === 'cancelled') {
            return ['label' => 'Ditolak / Batal', 'class' => 'bg-rose-100 text-rose-800 border-slate-200/80 font-bold'];
        }

        if (in_array(strtolower($clean), ['dalam antrian', 'submitted', 'belum verifikasi'])) {
            return ['label' => 'Dalam Antrian', 'class' => 'bg-slate-100 text-slate-800 border-slate-200/80'];
        }

        if (preg_match('/\(In\)/i', $clean)) {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . $clean,
                'class' => 'bg-blue-100 text-blue-800 border-slate-200/80'
            ];
        }

        if (preg_match('/\(Out\)/i', $clean)) {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . $clean,
                'class' => 'bg-indigo-100 text-indigo-800 border-slate-200/80'
            ];
        }

        if (stripos($clean, 'MH Pickup') !== false) {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . $clean,
                'class' => 'bg-amber-100 text-amber-900 border-slate-200/80'
            ];
        }

        if (stripos($clean, 'Siap Diambil') !== false || $clean === 'ready_for_warehouse') {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . 'Siap Diambil',
                'class' => 'bg-cyan-100 text-cyan-800 border-slate-200/80 font-semibold'
            ];
        }

        if (stripos($clean, 'Menuju Pengaju') !== false) {
            return [
                'label' => ($isPartial ? '[Parsial] ' : '') . 'Menuju Pengaju',
                'class' => 'bg-purple-100 text-purple-800 border-slate-200/80 font-semibold'
            ];
        }

        if (stripos($clean, 'Defect Penuh') !== false || ($this->is_final && (float)$this->quantity_requested <= 0 && (float)$this->quantity_defect > 0 && (float)$this->quantity_received == 0)) {
            return [
                'label' => 'Selesai (Defect 100%)',
                'class' => 'bg-rose-100 text-rose-800 border-slate-200/80 font-bold'
            ];
        }

        if (stripos($clean, 'Sudah Diambil') !== false || $clean === 'received') {
            $label = $this->is_final 
                ? 'Sudah Diambil (Final)' 
                : ($isPartial ? "[Parsial] Diterima " . (float)$this->quantity_received . "/" . (float)$this->quantity_requested : 'Sudah Diambil');
            return [
                'label' => $label,
                'class' => $this->is_final 
                    ? 'bg-emerald-100 text-emerald-800 border-slate-200/80 font-bold' 
                    : 'bg-amber-100 text-amber-900 border-slate-200/80 font-bold'
            ];
        }

        return ['label' => $st, 'class' => 'bg-gray-100 text-gray-800 border-slate-200/80'];
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

    /**
     * Cek apakah dokumen Work Request telah memenuhi 3 syarat mutlak untuk dicetak:
     * 1. Status Pengaju disetujui (Diterima)
     * 2. Status Penerima disetujui (Diterima)
     * 3. Operator pengerjaan telah dipilih / ditugaskan
     */
    public function isPrintable(): bool
    {
        return $this->approval_pengaju === 'Diterima'
            && $this->approval_penerima === 'Diterima'
            && !empty(trim($this->assigned_operator_name ?? ''));
    }

    /**
     * Dapatkan pesan keterangan jika dokumen belum dapat dicetak
     */
    public function getPrintValidationMessage(): string
    {
        if ($this->approval_pengaju !== 'Diterima') {
            return 'Menunggu verifikasi persetujuan Ka. Bagian Pengaju.';
        }
        if ($this->approval_penerima !== 'Diterima') {
            return 'Menunggu verifikasi persetujuan Ka. Bagian Penerima.';
        }
        if (empty(trim($this->assigned_operator_name ?? ''))) {
            return 'Operator pelaksana pengerjaan belum dipilih oleh Ka. Penerima.';
        }
        return 'Dokumen valid & siap dicetak.';
    }

    /**
     * Scope query untuk tiket yang telah siap dicetak
     */
    public function scopePrintable(Builder $query): Builder
    {
        return $query->where('approval_pengaju', 'Diterima')
                     ->where('approval_penerima', 'Diterima')
                     ->whereNotNull('assigned_operator_name')
                     ->where('assigned_operator_name', '!=', '');
    }

    /**
     * Scope query untuk penyaringan rentang waktu pembuatan Work Request.
     * Mendukung rentang kustom (startDate - endDate) atau preset cepat (today, 3days, 7days, this_month).
     */
    public function scopeDateRange(Builder $query, ?string $preset = null, ?string $startDate = null, ?string $endDate = null): Builder
    {
        if (!empty($startDate) && !empty($endDate)) {
            return $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay(),
            ]);
        } elseif (!empty($startDate)) {
            return $query->where('created_at', '>=', \Carbon\Carbon::parse($startDate)->startOfDay());
        } elseif (!empty($endDate)) {
            return $query->where('created_at', '<=', \Carbon\Carbon::parse($endDate)->endOfDay());
        }

        return match ($preset) {
            'today' => $query->whereDate('created_at', today()),
            '3days' => $query->where('created_at', '>=', now()->subDays(3)->startOfDay()),
            '7days' => $query->where('created_at', '>=', now()->subDays(7)->startOfDay()),
            'this_month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            default => $query,
        };
    }

    /**
     * Hitung berapa kali tiket ini telah dicetak berdasarkan riwayat log
     */
    public function getPrintCountAttribute(): int
    {
        if ($this->relationLoaded('logs')) {
            return $this->logs->where('action_type', 'printed')->count();
        }
        return $this->logs()->where('action_type', 'printed')->count();
    }

    /**
     * Pembangkitan QR Code WRI lokal (SVG) tanpa dependensi eksternal (BR-03) dengan caching 24 jam
     */
    public function getQrCodeSvgAttribute(): string
    {
        if (function_exists('app') && app()->has('cache')) {
            return \Illuminate\Support\Facades\Cache::remember("wri_qr_svg_{$this->id}", 86400, function () {
                $options = new \chillerlan\QRCode\QROptions([
                    'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
                    'outputBase64' => false,
                    'svgUseFill' => true,
                    'addQuietzone' => false,
                ]);
                return (new \chillerlan\QRCode\QRCode($options))->render($this->wri_number);
            });
        }

        $options = new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => false,
            'svgUseFill' => true,
            'addQuietzone' => false,
        ]);
        return (new \chillerlan\QRCode\QRCode($options))->render($this->wri_number);
    }

    /**
     * Pembangkitan QR Code WRI lokal (SVG Data URI) untuk disematkan pada lembar cetak
     */
    public function getQrCodeDataUriAttribute(): string
    {
        $svg = $this->qr_code_svg;
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Cek apakah komponen pada Work Request ini adalah komponen baru (belum terdaftar di katalog master).
     */
    public function isNewPart(): bool
    {
        if (!$this->component) return false;
        return $this->component->status === 'pending_catalog'
            || str_starts_with($this->component->part_number ?? '', 'NEW-')
            || $this->component->part_number === 'Baru'
            || str_contains($this->component->specification ?? '', 'form Work Request')
            || empty($this->component->part_number);
    }

    /**
     * Cek apakah pengguna berwenang untuk mengubah alur / routing tiket ini.
     * Syarat mutlak: Tiket belum final, belum ada pengerjaan fisik mesin sama sekali (quantity_produced == 0 & quantity_received == 0),
     * dan belum berjalan di stasiun kerja (status masih Dalam antrian / submitted).
     */
    public function canUserEditRouting(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user || $this->is_final) return false;

        // Pengerjaan fisik wajib 0 (belum mulai jalan)
        if ((float)$this->quantity_produced > 0 || (float)$this->quantity_received > 0) {
            return false;
        }

        // Status harus belum masuk ke pemrosesan aktif
        $cleanStatus = strtolower(trim(preg_replace('/^Parsial\s*>\s*/i', '', $this->status ?: '')));
        if (!in_array($cleanStatus, ['dalam antrian', 'submitted', 'draft'])) {
            return false;
        }

        // Tidak boleh ada log pengerjaan produksi aktif
        if ($this->relationLoaded('logs')) {
            $hasProductionLogs = $this->logs->contains(fn($l) => in_array($l->action_type, ['process', 'partial', 'pickup', 'drop', 'final_pickup', 'final_receive']));
        } else {
            $hasProductionLogs = $this->logs()->whereIn('action_type', ['process', 'partial', 'pickup', 'drop', 'final_pickup', 'final_receive'])->exists();
        }
        if ($hasProductionLogs) {
            return false;
        }

        // Admin Gudang & Supervisor memiliki wewenang superuser untuk mengoreksi alur semua WR yang belum diproses
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        // Untuk Pengaju, Ka. Pengaju, atau Ka. Penerima, diizinkan untuk komponen baru
        if ($this->isNewPart()) {
            $userDept = strtolower(trim($user->effective_department_name));
            $pengajuDept = strtolower(trim($this->dept_pengaju ?? ''));
            $penerimaDept = strtolower(trim($this->dept_penerima ?? ''));
            $cleanUser = preg_replace('/[^a-z0-9]/', '', $userDept);
            $cleanPengaju = preg_replace('/[^a-z0-9]/', '', $pengajuDept);
            $cleanPenerima = preg_replace('/[^a-z0-9]/', '', $penerimaDept);

            return $this->requested_by_user_id === $user->id
                || str_contains($cleanUser, $cleanPengaju)
                || str_contains($cleanPengaju, $cleanUser)
                || str_contains($cleanUser, $cleanPenerima)
                || str_contains($cleanPenerima, $cleanUser);
        }

        return false;
    }
}

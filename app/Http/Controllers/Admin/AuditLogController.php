<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        // 1. Calculate KPI Metrics
        $totalToday = Activity::whereDate('created_at', $today)->count();
        $usersActiveToday = Activity::whereDate('created_at', $today)
            ->whereNotNull('causer_id')
            ->distinct('causer_id')
            ->count('causer_id');
        $criticalToday = Activity::whereDate('created_at', $today)
            ->whereIn('event', ['defect', 'reject', 'rejected', 'deleted', 'delete'])
            ->count();

        $topModuleRecord = Activity::selectRaw('log_name, COUNT(*) as cnt')
            ->groupBy('log_name')
            ->orderByDesc('cnt')
            ->first();
        $topModule = $topModuleRecord ? $this->getModuleName($topModuleRecord->log_name) : 'Belum Ada';

        $kpis = [
            'total_today' => $totalToday,
            'users_active_today' => $usersActiveToday,
            'critical_today' => $criticalToday,
            'top_module' => $topModule,
        ];

        // 2. Build Query with Multi-Criteria Filters
        $query = Activity::with(['causer', 'subject']);

        // Period filter
        $period = $request->get('period', 'all');
        if ($period === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($period === '7_days') {
            $query->where('created_at', '>=', Carbon::now()->subDays(7));
        } elseif ($period === '30_days') {
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        } elseif ($period === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        // Module filter
        if ($request->filled('module') && $request->module !== 'all') {
            $query->where('log_name', $request->module);
        }

        // User filter
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('causer_id', $request->user_id);
        }

        // Event filter
        if ($request->filled('event') && $request->event !== 'all') {
            $query->where('event', $request->event);
        }

        // Free-text Search filter
        if ($request->filled('search')) {
            $searchTerm = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', $searchTerm)
                    ->orWhere('event', 'like', $searchTerm)
                    ->orWhere('log_name', 'like', $searchTerm)
                    ->orWhereHas('causer', function ($uq) use ($searchTerm) {
                        $uq->where('name', 'like', $searchTerm)
                           ->orWhere('email', 'like', $searchTerm);
                    });
            });
        }

        $logs = $query->latest('id')->paginate(25)->withQueryString();

        // 3. Reference Data for Dropdowns
        $usersList = User::orderBy('name')->get(['id', 'name', 'department']);
        $modulesList = [
            'auth' => 'Keamanan & Autentikasi',
            'work_request' => 'Work Request & Fabrikasi',
            'material_handling' => 'Material Handling',
            'transaction' => 'Transaksi Stok Gudang',
            'component' => 'Master Komponen & CAD',
            'qr_label' => 'Permintaan Label QR',
            'qc' => 'QC & Stok Opname',
            'system' => 'Administrasi Sistem',
        ];

        return view('admin.audit_logs.index', compact('logs', 'kpis', 'usersList', 'modulesList'));
    }

    private function getModuleName(string $logName): string
    {
        return match ($logName) {
            'auth' => 'Autentikasi (Login)',
            'work_request' => 'Work Request (WRI)',
            'material_handling' => 'Material Handling',
            'transaction' => 'Transaksi Stok',
            'component' => 'Master Komponen',
            'qr_label' => 'Label QR',
            'qc' => 'QC & Opname',
            'system' => 'Admin & Sistem',
            default => ucfirst(str_replace('_', ' ', $logName)),
        };
    }
}

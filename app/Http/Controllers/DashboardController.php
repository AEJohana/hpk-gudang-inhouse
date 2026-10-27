<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Transaction;
use App\Models\Ecr;
use App\Models\Disposal;
use App\Models\QrRequest;
use App\Models\WorkRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. KPI & Summary Metrics
        $totalComponents = Component::count();
        
        $lowStockComponents = Component::all()->filter(function ($c) {
            return $c->is_low_stock;
        });
        $lowStockCount = $lowStockComponents->count();

        $todayTransactionsCount = Transaction::whereDate('transaction_date', now())->count();
        
        $pendingEcrsCount = Ecr::whereIn('status', ['submitted', 'reviewed_qc'])->count();
        $pendingDisposalsCount = Disposal::whereIn('status', ['submitted', 'approved_qc'])->count();
        $pendingQrRequestsCount = QrRequest::where('status', 'pending')->count();
        
        $pendingApprovalsCount = $pendingEcrsCount + $pendingDisposalsCount;

        // 2. Work Request Badge Counters
        $inProductionWriCount = WorkRequest::where('status', 'in_production')->count();
        $readyForWarehouseWriCount = WorkRequest::where('status', 'ready_for_warehouse')->count();
        $unallocatedComponentsCount = Component::whereNull('default_location_id')->count();

        return view('dashboard', compact(
            'totalComponents',
            'lowStockCount',
            'lowStockComponents',
            'unallocatedComponentsCount',
            'todayTransactionsCount',
            'pendingApprovalsCount',
            'pendingEcrsCount',
            'pendingDisposalsCount',
            'pendingQrRequestsCount',
            'inProductionWriCount',
            'readyForWarehouseWriCount'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Location;
use App\Models\Transaction;
use App\Models\Ecr;
use App\Models\Disposal;
use App\Models\QrRequest;
use App\Models\CycleCount;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. KPI Calculations
        $totalComponents = Component::count();
        
        $lowStockComponents = Component::all()->filter(function ($c) {
            return $c->is_low_stock;
        });
        $lowStockCount = $lowStockComponents->count();

        $todayTransactionsCount = Transaction::whereDate('transaction_date', now())->count();
        
        $pendingApprovalsCount = Ecr::whereIn('status', ['submitted', 'reviewed_qc'])->count()
            + Disposal::whereIn('status', ['submitted', 'approved_qc'])->count();

        // 2. Warehouse Zones data (1-Building HPK Warehouse)
        $zones = [
            'A' => [
                'code' => 'A',
                'name' => 'Raw Material Baja',
                'color' => 'blue',
                'description' => 'Pelat Hitam/Bordes, UNP, WF, Pipa (Area Crane Overhead)',
                'locations' => Location::where('zone_code', 'A')->with('stockBalances.component')->get(),
            ],
            'B' => [
                'code' => 'B',
                'name' => 'Komponen Hidrolik & Presisi',
                'color' => 'indigo',
                'description' => 'Silinder Hidrolik Hoist, Pompa, Valve, PTO Transmisi',
                'locations' => Location::where('zone_code', 'B')->with('stockBalances.component')->get(),
            ],
            'C' => [
                'code' => 'C',
                'name' => 'Hardware, Fastener & Aksesoris',
                'color' => 'emerald',
                'description' => 'Multi-Tier Rack Baut HT, Engsel Dump, Twist Lock, Lampu LED',
                'locations' => Location::where('zone_code', 'C')->with('stockBalances.component')->get(),
            ],
            'D' => [
                'code' => 'D',
                'name' => 'Chemical & Cat',
                'color' => 'amber',
                'description' => 'Ruang Berventilasi Khusus Drum Cat PU, Epoxy Primer, Thinner',
                'locations' => Location::where('zone_code', 'D')->with('stockBalances.component')->get(),
            ],
            'E' => [
                'code' => 'E',
                'name' => 'Buffer Staging Lini Perakitan',
                'color' => 'cyan',
                'description' => 'Area Transit Material Siap Ambil Divisi Assembly Karoseri',
                'locations' => Location::where('zone_code', 'E')->with('stockBalances.component')->get(),
            ],
            'F' => [
                'code' => 'F',
                'name' => 'Karantina & Scrap Yard',
                'color' => 'rose',
                'description' => 'Penampungan Material Rusak / Afkir & Potongan Besi Tua',
                'locations' => Location::where('zone_code', 'F')->with('stockBalances.component')->get(),
            ],
        ];

        // Calculate occupancy percentage per zone
        foreach ($zones as $code => &$zone) {
            $totalCapacity = $zone['locations']->sum('max_capacity');
            $currentItemsCount = 0;
            foreach ($zone['locations'] as $loc) {
                $currentItemsCount += $loc->stockBalances->sum('quantity');
            }
            $zone['total_capacity'] = $totalCapacity ?: 100;
            $zone['current_quantity'] = $currentItemsCount;
            $zone['occupancy_rate'] = $totalCapacity > 0 ? min(100, round(($currentItemsCount / $totalCapacity) * 100)) : 0;
        }

        // 3. Recent activities & tasks
        $recentTransactions = Transaction::with(['user', 'items.component'])
            ->latest()
            ->take(5)
            ->get();

        $pendingEcrs = Ecr::with(['component', 'requestedBy'])
            ->whereIn('status', ['submitted', 'reviewed_qc'])
            ->latest()
            ->take(3)
            ->get();

        $pendingDisposals = Disposal::with(['requestedBy', 'items.component'])
            ->whereIn('status', ['submitted', 'approved_qc'])
            ->latest()
            ->take(3)
            ->get();

        $pendingQrRequests = QrRequest::with(['component', 'requestedBy'])
            ->where('status', 'pending')
            ->latest()
            ->take(3)
            ->get();

        return view('dashboard', compact(
            'totalComponents',
            'lowStockCount',
            'lowStockComponents',
            'todayTransactionsCount',
            'pendingApprovalsCount',
            'zones',
            'recentTransactions',
            'pendingEcrs',
            'pendingDisposals',
            'pendingQrRequests'
        ));
    }
}

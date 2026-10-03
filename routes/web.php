<?php

use App\Http\Controllers\ComponentController;
use App\Http\Controllers\CycleCountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisposalController;
use App\Http\Controllers\EcrController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrRequestController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root redirect to dashboard if authenticated, or login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authenticated WMS Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // 1. Dashboard Command Center
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Master Data Komponen
    Route::resource('components', ComponentController::class);
    Route::get('/components/{component}/qr-label', [ComponentController::class, 'printQrLabel'])->name('components.qr-label');
    Route::get('/api/components/search', [ComponentController::class, 'searchApi'])->name('api.components.search');

    // 3. Transaksi Komponen (Inbound, Outbound, Transfer, Return)
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'show']);

    // 4. ECR (Engineering Change Request) - Revisi Komponen
    Route::resource('ecrs', EcrController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/ecrs/{ecr}/approve', [EcrController::class, 'approve'])->name('ecrs.approve');
    Route::post('/ecrs/{ecr}/reject', [EcrController::class, 'reject'])->name('ecrs.reject');

    // 5. Pengajuan Disposal (Scrap & Afkir Material)
    Route::resource('disposals', DisposalController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/disposals/{disposal}/approve', [DisposalController::class, 'approve'])->name('disposals.approve');
    Route::post('/disposals/{disposal}/complete', [DisposalController::class, 'complete'])->name('disposals.complete');

    // 6. Permintaan Label QR (Barcode / QR Generator)
    Route::resource('qr-requests', QrRequestController::class)->only(['index', 'create', 'store']);
    Route::get('/qr-requests/{qrRequest}/print-thermal', [QrRequestController::class, 'printThermal'])->name('qr-requests.print-thermal');
    Route::get('/qr-requests/{qrRequest}/print-sheet', [QrRequestController::class, 'printSheet'])->name('qr-requests.print-sheet');

    // 7. Cycle Count (Stok Opname)
    Route::resource('cycle-counts', CycleCountController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/cycle-counts/{cycleCount}/submit-count', [CycleCountController::class, 'submitCount'])->name('cycle-counts.submit-count');
    Route::post('/cycle-counts/{cycleCount}/reconcile', [CycleCountController::class, 'reconcile'])->name('cycle-counts.reconcile');

    // 8. Peta Gudang 1 Gedung (Warehouse Layout Map)
    Route::get('/warehouse-map', [LocationController::class, 'index'])->name('warehouse-map.index');
    Route::get('/locations/{location}', [LocationController::class, 'show'])->name('locations.show');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

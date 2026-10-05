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

    // Peta Gudang Multi-Gedung & Denah Interaktif Rak / Pallet
    Route::get('/warehouse-map', [LocationController::class, 'index'])->name('warehouse-map.index');
    Route::post('/warehouse-map/save-layout', [LocationController::class, 'saveLayout'])->name('warehouse-map.save-layout');
    Route::post('/warehouse-map/reset-layout', [LocationController::class, 'resetLayout'])->name('warehouse-map.reset-layout');
    Route::post('/warehouse-map/warehouses/{warehouse}/update-area', [LocationController::class, 'updateWarehouseArea'])->name('warehouse-map.update-area');
    Route::post('/warehouse-map/create-location', [LocationController::class, 'createLocation'])->name('warehouse-map.create-location');
    Route::post('/warehouse-map/locations/{location}/update-config', [LocationController::class, 'updateLocationConfig'])->name('warehouse-map.locations.update-config');
    Route::get('/api/locations/{location}/quick-detail', [LocationController::class, 'quickDetail'])->name('warehouse-map.quick-detail');
    Route::get('/locations/{location}', [LocationController::class, 'show'])->name('locations.show');

    // 8. Work Request Inhouse (Order ke Machine Center & Routing Multi-Mesin)
    Route::resource('work-requests', \App\Http\Controllers\WorkRequestController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/work-requests/{workRequest}/steps/{step}/advance', [\App\Http\Controllers\WorkRequestController::class, 'advanceStep'])->name('work-requests.advance-step');
    Route::post('/work-requests/{workRequest}/receive', [\App\Http\Controllers\WorkRequestController::class, 'receive'])->name('work-requests.receive');

    // 9. Supply Komponen ke Stasiun Kerja / Lini Perakitan Karoseri
    Route::get('/work-station-supplies/create', [\App\Http\Controllers\WorkStationSupplyController::class, 'create'])->name('work-station-supplies.create');
    Route::post('/work-station-supplies', [\App\Http\Controllers\WorkStationSupplyController::class, 'store'])->name('work-station-supplies.store');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Panel Routes
    Route::prefix('admin')->name('admin.')->middleware(['role:admin_gudang'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');
        
        // Manajemen User & Roles
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class)->except(['create', 'store', 'destroy', 'show']);

        // Master Data
        Route::resource('component-categories', \App\Http\Controllers\Admin\ComponentCategoryController::class);
        Route::resource('uoms', \App\Http\Controllers\Admin\UomController::class);
        Route::resource('locations-master', \App\Http\Controllers\Admin\LocationMasterController::class);

        // Konfigurasi Sistem
        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
        Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});

require __DIR__.'/auth.php';

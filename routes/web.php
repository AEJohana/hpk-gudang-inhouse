<?php

use App\Http\Controllers\ComponentController;
use App\Http\Controllers\ComponentImportController;
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
    Route::post('/api/components/quick-store', [ComponentController::class, 'quickStore'])->name('api.components.quick-store');
    Route::get('/components/{component}/photo-stream', [ComponentController::class, 'photoStream'])->name('components.photo-stream');

    Route::middleware(['can:master-komponen.view'])->group(function () {
        Route::get('/components', [ComponentController::class, 'index'])->name('components.index');
        Route::get('/components/import/template', [ComponentImportController::class, 'downloadTemplate'])
            ->middleware('can:master-komponen.create')
            ->name('components.import.template');
        Route::post('/components/import/preview', [ComponentImportController::class, 'preview'])
            ->middleware('can:master-komponen.create')
            ->name('components.import.preview');
        Route::post('/components/import/confirm', [ComponentImportController::class, 'confirm'])
            ->middleware('can:master-komponen.create')
            ->name('components.import.confirm');
        Route::get('/components/create', [ComponentController::class, 'create'])->middleware('can:master-komponen.create')->name('components.create');
        Route::post('/components', [ComponentController::class, 'store'])->middleware('can:master-komponen.create')->name('components.store');
        Route::get('/components/{component}', [ComponentController::class, 'show'])->name('components.show');
        Route::get('/components/{component}/edit', [ComponentController::class, 'edit'])->middleware('can:master-komponen.edit')->name('components.edit');
        Route::match(['put', 'patch'], '/components/{component}', [ComponentController::class, 'update'])->middleware('can:master-komponen.edit')->name('components.update');
        Route::delete('/components/{component}', [ComponentController::class, 'destroy'])->middleware('can:master-komponen.delete')->name('components.destroy');
        Route::get('/components/{component}/qr-label', [ComponentController::class, 'printQrLabel'])->name('components.qr-label');
        Route::get('/api/components/search', [ComponentController::class, 'searchApi'])->name('api.components.search');
    });

    // 3. Transaksi Komponen (Inbound, Outbound, Transfer, Return)
    Route::middleware(['can:transaksi-komponen.view'])->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::match(['get', 'post'], '/transactions/bulk-print', [TransactionController::class, 'bulkPrint'])->name('transactions.bulk-print');
        Route::get('/transactions/create', [TransactionController::class, 'create'])->middleware('can:transaksi-komponen.create')->name('transactions.create');
        Route::post('/transactions', [TransactionController::class, 'store'])->middleware('can:transaksi-komponen.create')->name('transactions.store');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    });

    // 4. ECR (Engineering Change Request) - Revisi Komponen
    Route::middleware(['can:ecr.view'])->group(function () {
        Route::get('/ecrs', [EcrController::class, 'index'])->name('ecrs.index');
        Route::get('/ecrs/create', [EcrController::class, 'create'])->middleware('can:ecr.create')->name('ecrs.create');
        Route::post('/ecrs', [EcrController::class, 'store'])->middleware('can:ecr.create')->name('ecrs.store');
        Route::get('/ecrs/{ecr}', [EcrController::class, 'show'])->name('ecrs.show');
        Route::post('/ecrs/{ecr}/approve', [EcrController::class, 'approve'])->middleware('can:ecr.approve')->name('ecrs.approve');
        Route::post('/ecrs/{ecr}/reject', [EcrController::class, 'reject'])->middleware('can:ecr.approve')->name('ecrs.reject');
    });

    // 5. Pengajuan Disposal (Scrap & Afkir Material)
    Route::middleware(['can:disposal.view'])->group(function () {
        Route::get('/disposals', [DisposalController::class, 'index'])->name('disposals.index');
        Route::get('/disposals/create', [DisposalController::class, 'create'])->middleware('can:disposal.create')->name('disposals.create');
        Route::post('/disposals', [DisposalController::class, 'store'])->middleware('can:disposal.create')->name('disposals.store');
        Route::get('/disposals/{disposal}', [DisposalController::class, 'show'])->name('disposals.show');
        Route::post('/disposals/{disposal}/approve', [DisposalController::class, 'approve'])->middleware('can:disposal.approve')->name('disposals.approve');
        Route::post('/disposals/{disposal}/complete', [DisposalController::class, 'complete'])->middleware('can:disposal.approve')->name('disposals.complete');
    });

    // 6. Permintaan Label QR (Barcode / QR Generator)
    Route::middleware(['can:label-qr.view'])->group(function () {
        Route::get('/qr-requests', [QrRequestController::class, 'index'])->name('qr-requests.index');
        Route::get('/qr-requests/create', [QrRequestController::class, 'create'])->middleware('can:label-qr.create')->name('qr-requests.create');
        Route::post('/qr-requests', [QrRequestController::class, 'store'])->middleware('can:label-qr.create')->name('qr-requests.store');
        Route::get('/qr-requests/{qrRequest}/print-thermal', [QrRequestController::class, 'printThermal'])->middleware('can:label-qr.print')->name('qr-requests.print-thermal');
        Route::get('/qr-requests/{qrRequest}/print-sheet', [QrRequestController::class, 'printSheet'])->middleware('can:label-qr.print')->name('qr-requests.print-sheet');
        Route::post('/qr-requests/bulk-print', [QrRequestController::class, 'bulkPrint'])->middleware('can:label-qr.print')->name('qr-requests.bulk-print');
    });

    // 7. Cycle Count (Stok Opname)
    Route::middleware(['can:cycle-count.view'])->group(function () {
        Route::get('/cycle-counts', [CycleCountController::class, 'index'])->name('cycle-counts.index');
        Route::get('/cycle-counts/create', [CycleCountController::class, 'create'])->middleware('can:cycle-count.create')->name('cycle-counts.create');
        Route::post('/cycle-counts', [CycleCountController::class, 'store'])->middleware('can:cycle-count.create')->name('cycle-counts.store');
        Route::get('/cycle-counts/{cycleCount}', [CycleCountController::class, 'show'])->name('cycle-counts.show');
        Route::post('/cycle-counts/{cycleCount}/submit-count', [CycleCountController::class, 'submitCount'])->middleware('can:cycle-count.create')->name('cycle-counts.submit-count');
        Route::post('/cycle-counts/{cycleCount}/reconcile', [CycleCountController::class, 'reconcile'])->middleware('can:cycle-count.approve')->name('cycle-counts.reconcile');
    });

    // Peta Gudang Multi-Gedung & Denah Interaktif Rak / Pallet
    Route::middleware(['can:peta-gudang.view'])->group(function () {
        Route::get('/warehouse-map', [LocationController::class, 'index'])->name('warehouse-map.index');
        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/warehouse-map/save-layout', [LocationController::class, 'saveLayout'])->middleware('can:peta-gudang.edit')->name('warehouse-map.save-layout');
        Route::post('/warehouse-map/reset-layout', [LocationController::class, 'resetLayout'])->middleware('can:peta-gudang.edit')->name('warehouse-map.reset-layout');
        Route::post('/warehouse-map/warehouses/{warehouse}/update-area', [LocationController::class, 'updateWarehouseArea'])->middleware('can:peta-gudang.edit')->name('warehouse-map.update-area');
        Route::post('/warehouse-map/create-location', [LocationController::class, 'createLocation'])->middleware('can:peta-gudang.create')->name('warehouse-map.create-location');
        Route::post('/warehouse-map/locations/{location}/update-config', [LocationController::class, 'updateLocationConfig'])->middleware('can:peta-gudang.edit')->name('warehouse-map.locations.update-config');
        Route::get('/api/locations/{location}/quick-detail', [LocationController::class, 'quickDetail'])->name('warehouse-map.quick-detail');
        Route::get('/locations/{location}', [LocationController::class, 'show'])->name('locations.show');
    });

    // 8. Work Request Inhouse (Order ke Machine Center & Routing Multi-Mesin)
    Route::middleware(['can:work-request.view'])->group(function () {
        // Tab Khusus Safety Stock, Monitoring SLA, dan Antrean Cetak Dokumen WR
        Route::get('/work-requests/safety-stock', [\App\Http\Controllers\WorkRequestController::class, 'safetyStock'])
            ->middleware('can:work-request.safety-stock')
            ->name('work-requests.safety-stock');
        Route::get('/work-requests/monitoring', function () {
            return redirect()->route('work-requests.index', ['overdue_sla' => 1]);
        })->name('work-requests.monitoring');
        Route::get('/work-requests/print-queue', [\App\Http\Controllers\WorkRequestController::class, 'printQueue'])
            ->middleware('can:work-request.print')
            ->name('work-requests.print-queue');
        Route::post('/work-requests/bulk-print', [\App\Http\Controllers\WorkRequestController::class, 'bulkPrint'])
            ->middleware('can:work-request.print')
            ->name('work-requests.bulk-print');
        Route::post('/work-requests/bulk-store', [\App\Http\Controllers\WorkRequestController::class, 'bulkStore'])
            ->middleware('can:work-request.create')
            ->name('work-requests.bulk-store');

        // CRUD Work Requests
        Route::get('/work-requests', [\App\Http\Controllers\WorkRequestController::class, 'index'])->name('work-requests.index');
        Route::get('/work-requests/create', [\App\Http\Controllers\WorkRequestController::class, 'create'])
            ->middleware('can:work-request.create')
            ->name('work-requests.create');
        Route::post('/work-requests', [\App\Http\Controllers\WorkRequestController::class, 'store'])
            ->middleware('can:work-request.create')
            ->name('work-requests.store');
        Route::get('/work-requests/{workRequest}', [\App\Http\Controllers\WorkRequestController::class, 'show'])->name('work-requests.show');
        Route::get('/work-requests/{workRequest}/print-sheet', [\App\Http\Controllers\WorkRequestController::class, 'printSheet'])
            ->middleware('can:work-request.print')
            ->name('work-requests.print-sheet');

        // Approval Workflow Ka. Bagian
        Route::post('/work-requests/{workRequest}/approve-pengaju', [\App\Http\Controllers\WorkRequestController::class, 'approvePengaju'])
            ->name('work-requests.approve-pengaju');
        Route::post('/work-requests/{workRequest}/approve-penerima', [\App\Http\Controllers\WorkRequestController::class, 'approvePenerima'])
            ->name('work-requests.approve-penerima');
        Route::post('/work-requests/{workRequest}/reject', [\App\Http\Controllers\WorkRequestController::class, 'reject'])
            ->name('work-requests.reject');

        // Eksekusi Pengerjaan Mesin & Defect
        Route::post('/work-requests/{workRequest}/steps/{step}/advance', [\App\Http\Controllers\WorkRequestController::class, 'advanceStep'])
            ->name('work-requests.advance-step');
        Route::post('/work-requests/{workRequest}/process-action', [\App\Http\Controllers\WorkRequestController::class, 'processAction'])
            ->name('work-requests.process-action');
        Route::post('/work-requests/{workRequest}/receive', [\App\Http\Controllers\WorkRequestController::class, 'receive'])
            ->name('work-requests.receive');
    });

    // 9. Material Handling & Supply Komponen Stasiun Kerja (Pusat Logistik Lapangan)
    Route::middleware(['can:material-handling.view'])->group(function () {
        Route::get('/work-station-supplies', [\App\Http\Controllers\WorkStationSupplyController::class, 'index'])->name('work-station-supplies.index');
        Route::get('/work-station-supplies/scan-lookup', [\App\Http\Controllers\WorkStationSupplyController::class, 'scanLookup'])->name('work-station-supplies.scan-lookup');
        Route::post('/work-station-supplies/quick-action', [\App\Http\Controllers\WorkStationSupplyController::class, 'quickAction'])
            ->middleware('can:material-handling.manage')
            ->name('work-station-supplies.quick-action');
        Route::get('/work-station-supplies/create', [\App\Http\Controllers\WorkStationSupplyController::class, 'create'])
            ->middleware('can:material-handling.manage')
            ->name('work-station-supplies.create');
        Route::post('/work-station-supplies', [\App\Http\Controllers\WorkStationSupplyController::class, 'store'])
            ->middleware('can:material-handling.manage')
            ->name('work-station-supplies.store');
    });

    // 10. Menu Aktivitas & Riwayat Audit Sistem (Pusat Jejak Aktivitas Pengguna)
    Route::get('/activities', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])
        ->middleware(['role_or_permission:admin_gudang|supervisor|qc|engineering|audit-log.view'])
        ->name('activities.index');

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

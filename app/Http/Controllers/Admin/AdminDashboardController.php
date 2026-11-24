<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\ComponentPhotoStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function index(ComponentPhotoStorageService $storageService)
    {
        $usersCount = \App\Models\User::count();
        $rolesCount = \Spatie\Permission\Models\Role::count();
        $categoriesCount = \App\Models\ComponentCategory::count();
        $logsCount = \Spatie\Activitylog\Models\Activity::count();

        $pendingEcrsCount = \App\Models\Ecr::where('status', 'submitted')->count() ?? 0;
        $pendingDisposalsCount = \App\Models\Disposal::whereIn('status', ['submitted', 'approved_qc'])->count() ?? 0;

        $storageStatus = $storageService->getStorageStatus();

        return view('admin.dashboard', compact(
            'usersCount', 'rolesCount', 'categoriesCount', 'logsCount',
            'pendingEcrsCount', 'pendingDisposalsCount', 'storageStatus'
        ));
    }

    /**
     * Sinkronkan foto dari Server NAS ke webroot mirror.
     */
    public function syncPhotos(ComponentPhotoStorageService $storageService)
    {
        $res = $storageService->syncNasToMirror();

        try {
            ActivityLogger::system(
                'nas_photo_synced',
                "Administrator " . auth()->user()->name . " memicu sinkronisasi foto dari Server NAS: " . ($res['message'] ?? 'Selesai'),
                ['synced_count' => $res['synced_count'] ?? 0]
            );
        } catch (\Throwable $e) {}

        if ($res['success']) {
            return redirect()->route('admin.dashboard')->with('success', $res['message']);
        }

        return redirect()->route('admin.dashboard')->with('error', $res['message']);
    }

    /**
     * Bersihkan cache resolusi foto komponen.
     */
    public function clearPhotoCache()
    {
        ComponentPhotoStorageService::clearDirectoryIndexCache();

        if (function_exists('app') && app()->has('cache')) {
            try {
                Cache::flush();
            } catch (\Throwable $e) {}
        }

        try {
            ActivityLogger::system(
                'photo_cache_cleared',
                "Administrator " . auth()->user()->name . " membersihkan cache resolusi foto komponen."
            );
        } catch (\Throwable $e) {}

        return redirect()->route('admin.dashboard')->with('success', 'Cache resolusi foto komponen berhasil dibersihkan! Sistem akan membaca data fisik langsung dari folder Z: / NAS.');
    }
}

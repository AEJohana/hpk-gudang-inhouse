<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $usersCount = \App\Models\User::count();
        $rolesCount = \Spatie\Permission\Models\Role::count();
        $categoriesCount = \App\Models\ComponentCategory::count();
        $logsCount = \Spatie\Activitylog\Models\Activity::count();

        $pendingEcrsCount = \App\Models\Ecr::where('status', 'submitted')->count() ?? 0;
        $pendingDisposalsCount = \App\Models\Disposal::whereIn('status', ['submitted', 'approved_qc'])->count() ?? 0;

        return view('admin.dashboard', compact(
            'usersCount', 'rolesCount', 'categoriesCount', 'logsCount',
            'pendingEcrsCount', 'pendingDisposalsCount'
        ));
    }
}

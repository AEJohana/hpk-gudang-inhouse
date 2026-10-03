<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = \Spatie\Activitylog\Models\Activity::with('causer');

        if ($request->filled('search')) {
            $query->where('description', 'like', "%{$request->search}%")
                  ->orWhere('event', 'like', "%{$request->search}%")
                  ->orWhereHas('causer', function($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        return view('admin.audit_logs.index', compact('logs'));
    }
}

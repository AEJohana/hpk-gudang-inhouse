<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password recovery helpdesk view.
     */
    public function create(): View
    {
        $admins = User::where(function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin_gudang', 'supervisor']);
            })->orWhereIn('role', ['admin_gudang', 'supervisor']);
        })
        ->where('is_active', true)
        ->orderBy('id')
        ->get(['id', 'name', 'username', 'phone', 'role', 'work_area', 'department']);

        return view('auth.forgot-password', compact('admins'));
    }

    /**
     * Handle an incoming request for password help.
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('password.request')
            ->with('info', 'Demi keamanan data operasional pabrik, pemulihan kata sandi dilakukan secara langsung melalui Administrator Gudang atau Supervisor yang bertugas.');
    }
}

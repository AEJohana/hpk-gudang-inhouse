<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = \Spatie\Permission\Models\Role::with('permissions')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function edit(\Spatie\Permission\Models\Role $role)
    {
        $permissions = \Spatie\Permission\Models\Permission::all()->groupBy(function($perm) {
            return explode('.', $perm->name)[0] ?? 'Lainnya';
        });
        
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, \Spatie\Permission\Models\Role $role)
    {
        $role->syncPermissions($request->permissions ?? []);
        return redirect()->route('admin.roles.index')->with('success', 'Permissions untuk role berhasil diperbarui.');
    }
}

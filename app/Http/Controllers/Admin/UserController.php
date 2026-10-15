<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Define the 4 standard display roles and their system role names.
     */
    protected array $standardRoles = [
        'supervisor' => 'Supervisor',
        'admin_gudang' => 'Admin',
        'staff' => 'Staff',
        'operator' => 'Operator',
    ];

    /**
     * Define the 3 operational work areas.
     */
    protected array $workAreas = [
        'gudang' => 'Gudang',
        'mesin_center' => 'Mesin Center',
        'material_handling' => 'Material Handling',
    ];

    public function index(Request $request)
    {
        $query = User::with(['roles', 'permissions', 'departmentRel']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleFilter = $request->role === 'admin' ? 'admin_gudang' : $request->role;
            $query->role($roleFilter);
        }

        if ($request->filled('work_area')) {
            $query->where('work_area', $request->work_area);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = $this->standardRoles;
        $workAreas = $this->workAreas;
        $departments = Department::active()->orderBy('id')->get();

        return view('admin.users.index', compact('users', 'roles', 'workAreas', 'departments'));
    }

    public function create()
    {
        $roles = $this->standardRoles;
        $workAreas = $this->workAreas;
        $departments = Department::active()->orderBy('id')->get();
        $gudangDept = Department::where('code', '11')->orWhere('name', 'like', '%Gudang%')->first();
        $machineDepartments = Department::active()
            ->where('code', '!=', '11')
            ->where('name', 'not like', '%Gudang%')
            ->orderBy('id')
            ->get();

        $permissions = Permission::all()->groupBy(function($perm) {
            return explode('.', $perm->name)[0] ?? 'lainnya';
        });

        $presets = $this->getPermissionPresets();

        return view('admin.users.create', compact('roles', 'workAreas', 'departments', 'gudangDept', 'machineDepartments', 'permissions', 'presets'));
    }

    public function store(Request $request)
    {
        $isGlobalRole = in_array($request->role, ['supervisor', 'admin_gudang', 'admin']);

        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:supervisor,admin_gudang,admin,staff,operator',
            'can_access_all_departments' => 'nullable|boolean',
            'signature_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];

        if ($isGlobalRole) {
            $rules['work_area'] = 'nullable|string';
            $rules['department_id'] = 'nullable';
        } else {
            $rules['work_area'] = 'required|string|in:gudang,mesin_center,material_handling';
            if ($request->work_area === 'mesin_center') {
                $rules['department_id'] = 'required|exists:departments,id';
            } else {
                $rules['department_id'] = 'nullable';
            }
        }

        $validated = $request->validate($rules);

        $roleName = $validated['role'] === 'admin' ? 'admin_gudang' : $validated['role'];
        $gudangDept = Department::where('code', '11')->orWhere('name', 'like', '%Gudang%')->first();

        if ($isGlobalRole) {
            $finalWorkArea = 'semua';
            $finalDeptId = $gudangDept?->id;
            $deptName = 'Seluruh Departemen';
            $canAccessAll = true;
        } else {
            $finalWorkArea = $validated['work_area'];
            if ($finalWorkArea === 'mesin_center') {
                $finalDeptId = $validated['department_id'];
                $deptModel = Department::find($finalDeptId);
                $deptName = $deptModel?->name ?? 'Dept Mesin Center';
                $canAccessAll = $request->boolean('can_access_all_departments');
            } else {
                $finalDeptId = $gudangDept?->id;
                $deptName = $gudangDept?->name ?? 'Dept Gudang';
                $canAccessAll = $request->boolean('can_access_all_departments');
            }
        }

        $signaturePath = null;
        if ($request->hasFile('signature_file')) {
            $signaturePath = $request->file('signature_file')->store('signatures', 'public');
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password' => bcrypt($validated['password']),
            'work_area' => $finalWorkArea,
            'department_id' => $finalDeptId,
            'department' => $deptName,
            'can_access_all_departments' => $canAccessAll,
            'signature_path' => $signaturePath,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        // Sync Spatie role
        $user->syncRoles([$roleName]);

        // Sync Direct Permissions selected in checklist
        $user->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.users.index')->with('success', 'User dan hak akses otorisasi berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $roles = $this->standardRoles;
        $workAreas = $this->workAreas;
        $departments = Department::active()->orderBy('id')->get();
        $gudangDept = Department::where('code', '11')->orWhere('name', 'like', '%Gudang%')->first();
        $machineDepartments = Department::active()
            ->where('code', '!=', '11')
            ->where('name', 'not like', '%Gudang%')
            ->orderBy('id')
            ->get();

        $permissions = Permission::all()->groupBy(function($perm) {
            return explode('.', $perm->name)[0] ?? 'lainnya';
        });

        // Current assigned permissions (direct + inherited)
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        $presets = $this->getPermissionPresets();

        return view('admin.users.edit', compact('user', 'roles', 'workAreas', 'departments', 'gudangDept', 'machineDepartments', 'permissions', 'userPermissions', 'presets'));
    }

    public function update(Request $request, User $user)
    {
        $isGlobalRole = in_array($request->role, ['supervisor', 'admin_gudang', 'admin']);

        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:supervisor,admin_gudang,admin,staff,operator',
            'can_access_all_departments' => 'nullable|boolean',
            'signature_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];

        if ($isGlobalRole) {
            $rules['work_area'] = 'nullable|string';
            $rules['department_id'] = 'nullable';
        } else {
            $rules['work_area'] = 'required|string|in:gudang,mesin_center,material_handling';
            if ($request->work_area === 'mesin_center') {
                $rules['department_id'] = 'required|exists:departments,id';
            } else {
                $rules['department_id'] = 'nullable';
            }
        }

        $validated = $request->validate($rules);

        if ($user->id === auth()->id() && !$request->has('is_active')) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $roleName = $validated['role'] === 'admin' ? 'admin_gudang' : $validated['role'];
        $gudangDept = Department::where('code', '11')->orWhere('name', 'like', '%Gudang%')->first();

        if ($isGlobalRole) {
            $finalWorkArea = 'semua';
            $finalDeptId = $gudangDept?->id;
            $deptName = 'Seluruh Departemen';
            $canAccessAll = true;
        } else {
            $finalWorkArea = $validated['work_area'];
            if ($finalWorkArea === 'mesin_center') {
                $finalDeptId = $validated['department_id'];
                $deptModel = Department::find($finalDeptId);
                $deptName = $deptModel?->name ?? 'Dept Mesin Center';
                $canAccessAll = $request->boolean('can_access_all_departments');
            } else {
                $finalDeptId = $gudangDept?->id;
                $deptName = $gudangDept?->name ?? 'Dept Gudang';
                $canAccessAll = $request->boolean('can_access_all_departments');
            }
        }

        $updateData = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'work_area' => $finalWorkArea,
            'department_id' => $finalDeptId,
            'department' => $deptName,
            'can_access_all_departments' => $canAccessAll,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->has('is_active'),
        ];

        if ($request->hasFile('signature_file')) {
            if ($user->signature_path) {
                Storage::disk('public')->delete($user->signature_path);
            }
            $updateData['signature_path'] = $request->file('signature_file')->store('signatures', 'public');
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = bcrypt($validated['password']);
        }

        $user->update($updateData);

        // Sync Spatie role
        $user->syncRoles([$roleName]);

        // Sync Direct Permissions selected in checklist
        $user->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.users.index')->with('success', 'User dan hak akses otorisasi berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Prevent deleting the last admin_gudang
        if ($user->hasRole('admin_gudang') && User::role('admin_gudang')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya Admin Gudang.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }

    /**
     * Returns preset permission lists mapped to role + work_area.
     */
    protected function getPermissionPresets(): array
    {
        $allPermissions = Permission::pluck('name')->toArray();

        return [
            'all' => $allPermissions,
            'supervisor' => $allPermissions,
            'admin_gudang' => $allPermissions,
            'staff' => [
                'gudang' => array_values(array_filter($allPermissions, function($p) {
                    return str_starts_with($p, 'master-komponen.') ||
                           str_starts_with($p, 'transaksi-komponen.') ||
                           str_starts_with($p, 'peta-gudang.') ||
                           str_starts_with($p, 'cycle-count.') ||
                           str_starts_with($p, 'label-qr.') ||
                           str_starts_with($p, 'laporan.') ||
                           $p === 'material-handling.view' ||
                           in_array($p, ['work-request.view', 'work-request.create', 'work-request.print', 'work-request.monitoring', 'work-request.safety-stock']);
                })),
                'mesin_center' => [
                    'work-request.view', 'work-request.process', 'work-request.print', 'work-request.monitoring', 'work-request.safety-stock',
                    'master-komponen.view',
                    'peta-gudang.view',
                    'laporan.view', 'laporan.export',
                ],
                'material_handling' => [
                    'material-handling.view', 'material-handling.manage',
                    'work-request.view', 'work-request.print',
                    'peta-gudang.view',
                    'master-komponen.view',
                    'laporan.view',
                ],
            ],
            'operator' => [
                'gudang' => [
                    'peta-gudang.view',
                    'master-komponen.view',
                    'transaksi-komponen.view', 'transaksi-komponen.create',
                    'cycle-count.view', 'cycle-count.create',
                    'label-qr.view', 'label-qr.create',
                    'work-request.view', 'work-request.create', 'work-request.print',
                ],
                'mesin_center' => [
                    'work-request.view', 'work-request.process', 'work-request.print',
                    'master-komponen.view',
                    'peta-gudang.view',
                ],
                'material_handling' => [
                    'material-handling.view', 'material-handling.manage',
                    'work-request.view', 'work-request.print',
                    'peta-gudang.view',
                    'master-komponen.view',
                ],
            ],
        ];
    }
}


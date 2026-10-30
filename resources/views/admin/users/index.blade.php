<x-admin-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header !h-[240px] sm:!h-[270px]">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-12 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-users-gear me-1 text-[9px]"></i> KONTROL PENGGUNA TERPUSAT
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Sistem, Role & Hak Akses
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                    <div class="flex items-start space-x-3">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="mt-1 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer shrink-0" 
                           title="Kembali ke Pusat Kendali Administrator">
                            <i class="fa-solid fa-arrow-left text-xs text-amber-300"></i>
                            <span>Kembali</span>
                        </a>
                        <div>
                            <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                                Manajemen Pengguna & Otorisasi
                            </h1>
                            <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                                Pusat kendali akun staf karoseri: konfigurasi Role (Supervisor, Admin, Staff, Operator), penempatan Bagian Operasional, unit Departemen Pabrik, dan izin modul sistem.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('admin.users.create') }}" 
                           class="inline-flex items-center px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs tracking-wide shadow-md hover:shadow-lg transition">
                            <i class="fa-solid fa-user-plus me-2 text-sm"></i>
                            Tambah User Baru
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center">
                    <i class="fa-solid fa-circle-check me-2.5 text-emerald-600 text-base"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center">
                    <i class="fa-solid fa-triangle-exclamation me-2.5 text-rose-600 text-base"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        <!-- 2. MAIN CARD: FILTER & USER TABLE -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            
            <!-- Filter Toolbar -->
            <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                    <!-- Search Input -->
                    <div class="lg:col-span-1">
                        <label for="search" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Cari User
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" 
                                   id="search" 
                                   name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Nama, username, email..." 
                                   class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                        </div>
                    </div>

                    <!-- Role Dropdown -->
                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Filter Role
                        </label>
                        <select name="role" 
                                id="role" 
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                            <option value="">Semua Role</option>
                            @foreach($roles as $roleKey => $roleLabel)
                                <option value="{{ $roleKey }}" {{ request('role') == $roleKey ? 'selected' : '' }}>
                                    {{ $roleLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Bagian Dropdown -->
                    <div>
                        <label for="work_area" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Filter Bagian
                        </label>
                        <select name="work_area" 
                                id="work_area" 
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                            <option value="">Semua Bagian</option>
                            @foreach($workAreas as $areaKey => $areaLabel)
                                <option value="{{ $areaKey }}" {{ request('work_area') == $areaKey ? 'selected' : '' }}>
                                    {{ $areaLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Departemen Dropdown -->
                    <div>
                        <label for="department_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Departemen Pabrik
                        </label>
                        <select name="department_id" 
                                id="department_id" 
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                            <option value="">Semua Departemen ({{ $departments->count() }})</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                    [{{ $dept->code }}] {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex items-center space-x-2">
                        <button type="submit" 
                                class="flex-1 py-2 px-3 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center">
                            <i class="fa-solid fa-filter me-1.5"></i> Terapkan
                        </button>
                        @if(request()->hasAny(['search', 'role', 'work_area', 'department_id', 'status']))
                        <a href="{{ route('admin.users.index') }}" 
                           class="py-2 px-3 bg-white hover:bg-slate-100 text-slate-600 border border-slate-300 rounded-xl text-xs font-semibold transition" 
                           title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>

            <!-- Mobile Card View (< md) -->
            <div class="block md:hidden space-y-3">
                @forelse($users as $user)
                    @php
                        $roleSlug = $user->roles->first()->name ?? 'operator';
                        $avatarBg = match($roleSlug) {
                            'supervisor' => 'bg-purple-600 text-white font-black',
                            'admin_gudang', 'admin' => 'bg-amber-500 text-slate-950 font-black',
                            'staff' => 'bg-blue-600 text-white font-black',
                            'operator' => 'bg-emerald-600 text-white font-black',
                            default => 'bg-slate-700 text-white font-black',
                        };
                        $roleBadgeClass = match($roleSlug) {
                            'supervisor' => 'bg-purple-100 text-purple-800 border-purple-300/60',
                            'admin_gudang', 'admin' => 'bg-amber-100 text-amber-800 border-amber-300/60',
                            'staff' => 'bg-blue-100 text-blue-800 border-blue-300/60',
                            'operator' => 'bg-emerald-100 text-emerald-800 border-emerald-300/60',
                            default => 'bg-slate-100 text-slate-800 border-slate-300/60',
                        };
                        $areaBadgeClass = match($user->work_area) {
                            'gudang' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'mesin_center' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'material_handling' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'semua', 'global' => 'bg-purple-50 text-purple-700 border-purple-200',
                            default => 'bg-slate-50 text-slate-700 border-slate-200',
                        };
                        $permCount = $user->getAllPermissions()->count();
                    @endphp
                    <div class="mobile-data-card p-4 rounded-xl border border-slate-200 bg-white space-y-3 shadow-xs">
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-full {{ $avatarBg }} flex items-center justify-center text-xs shadow-xs shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900 text-xs flex items-center space-x-1 truncate">
                                        <span class="truncate">{{ $user->name }}</span>
                                        @if($user->id === auth()->id())
                                            <span class="bg-amber-100 text-amber-800 text-[9px] font-bold px-1.5 py-0.2 rounded-full uppercase shrink-0">Anda</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-slate-500 font-mono block truncate">{{ '@' . $user->username }}</span>
                                </div>
                            </div>
                            @if($user->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 me-1 animate-pulse"></span>
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-300 shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 me-1"></span>
                                    Nonaktif
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-semibold">Role & Bagian</span>
                                <div class="flex flex-wrap gap-1 mt-0.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $roleBadgeClass }}">
                                        {{ $user->role_badge }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $areaBadgeClass }}">
                                        {{ $user->work_area_label }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-semibold">Departemen</span>
                                <div class="mt-0.5">
                                    @if($user->can_access_all_departments)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 font-mono">* SEMUA DEPT</span>
                                    @else
                                        <span class="font-semibold text-slate-700 text-xs">{{ $user->departmentRel?->name ?? $user->department ?? '-' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                            <span class="text-[11px] font-mono text-slate-500">
                                <i class="fa-solid fa-shield-halved text-[10px] me-1 text-amber-500"></i>
                                <span class="font-bold text-slate-800">{{ $permCount }}</span> Izin
                            </span>
                            <div class="inline-flex items-center space-x-1.5">
                                <a href="{{ route('admin.users.edit', $user) }}" 
                                   class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-xs transition shadow-2xs inline-flex items-center">
                                    <i class="fa-solid fa-pen-to-square me-1 text-[10px]"></i> Edit
                                </a>

                                @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" 
                                      method="POST" 
                                      class="inline-block" 
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus user {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition shadow-2xs" 
                                            title="Hapus User">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-lg">
                            <i class="fa-solid fa-user-slash"></i>
                        </div>
                        <p class="font-bold text-xs text-slate-700">Tidak ada pengguna yang cocok</p>
                    </div>
                @endforelse
            </div>

            <!-- Table Section (Desktop >= md) -->
            <div class="hidden md:block overflow-x-auto rounded-xl border border-slate-200/80 shadow-xs">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50 uppercase tracking-wider text-slate-600 font-extrabold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3.5">Nama & Profil</th>
                            <th class="px-4 py-3.5">ID Masuk (Username & Email)</th>
                            <th class="px-4 py-3.5">Role & Bagian</th>
                            <th class="px-4 py-3.5">Departemen Pabrik</th>
                            <th class="px-4 py-3.5 text-center">Izin Sistem</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($users as $user)
                        @php
                            $roleSlug = $user->roles->first()->name ?? 'operator';
                            $avatarBg = match($roleSlug) {
                                'supervisor' => 'bg-purple-600 text-white font-black',
                                'admin_gudang', 'admin' => 'bg-amber-500 text-slate-950 font-black',
                                'staff' => 'bg-blue-600 text-white font-black',
                                'operator' => 'bg-emerald-600 text-white font-black',
                                default => 'bg-slate-700 text-white font-black',
                            };
                            $roleBadgeClass = match($roleSlug) {
                                'supervisor' => 'bg-purple-100 text-purple-800 border-purple-300/60',
                                'admin_gudang', 'admin' => 'bg-amber-100 text-amber-800 border-amber-300/60',
                                'staff' => 'bg-blue-100 text-blue-800 border-blue-300/60',
                                'operator' => 'bg-emerald-100 text-emerald-800 border-emerald-300/60',
                                default => 'bg-slate-100 text-slate-800 border-slate-300/60',
                            };
                            $areaBadgeClass = match($user->work_area) {
                                'gudang' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'mesin_center' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'material_handling' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'semua', 'global' => 'bg-purple-50 text-purple-700 border-purple-200',
                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };
                            $permCount = $user->getAllPermissions()->count();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Name & Avatar -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full {{ $avatarBg }} flex items-center justify-center text-xs shadow-xs shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm flex items-center space-x-1.5">
                                            <span>{{ $user->name }}</span>
                                            @if($user->id === auth()->id())
                                                <span class="bg-amber-100 text-amber-800 text-[9px] font-bold px-1.5 py-0.2 rounded-full uppercase">Anda</span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-slate-500 flex items-center mt-0.5">
                                            <i class="fa-solid fa-phone text-[9px] me-1 text-slate-400"></i>
                                            {{ $user->phone ?? 'Tidak ada no. telp' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Username & Email -->
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-slate-900 font-mono text-[11px] flex items-center">
                                    <span class="text-amber-600 font-bold me-0.5">@</span>{{ $user->username }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $user->email ?? '—' }}
                                </div>
                            </td>

                            <!-- Role & Bagian -->
                            <td class="px-4 py-3.5">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $roleBadgeClass }}">
                                        <i class="fa-solid fa-shield-halved me-1 text-[8px]"></i>
                                        {{ $user->role_badge }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $areaBadgeClass }}">
                                        <i class="fa-solid fa-location-dot me-1 text-[8px]"></i>
                                        {{ $user->work_area_label }}
                                    </span>
                                </div>
                            </td>

                            <!-- Departemen Pabrik -->
                            <td class="px-4 py-3.5">
                                @if($user->can_access_all_departments)
                                    <div class="flex items-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 font-mono border border-amber-300/60 flex items-center">
                                            <i class="fa-solid fa-globe me-1 text-[9px]"></i> * SEMUA DEPT
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Akses Lintas 15 Departemen</span>
                                @else
                                    <span class="font-bold text-slate-800 text-xs block">
                                        {{ $user->departmentRel?->name ?? $user->department ?? '-' }}
                                    </span>
                                    @if($user->departmentRel)
                                        <span class="text-[10px] text-slate-400 font-mono block">[{{ $user->departmentRel->code }}] {{ $user->departmentRel->alias }}</span>
                                    @endif
                                @endif
                            </td>

                            <!-- Total Izin -->
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-mono font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                    <i class="fa-solid fa-list-check me-1.5 text-indigo-500"></i>
                                    {{ $permCount }} <span class="text-slate-400 font-normal ms-1">/ 64</span>
                                </span>
                            </td>

                            <!-- Status Akun -->
                            <td class="px-4 py-3.5 text-center">
                                @if($user->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 me-1.5 animate-pulse"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 me-1.5"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="p-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition shadow-2xs" 
                                       title="Edit Data User & Izin">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </a>

                                    @if($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $user) }}" 
                                          method="POST" 
                                          class="inline-block" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus user {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-2 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition shadow-2xs" 
                                                title="Hapus User">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-xl">
                                    <i class="fa-solid fa-user-slash"></i>
                                </div>
                                <p class="font-bold text-sm text-slate-700">Tidak ada pengguna yang cocok</p>
                                <p class="text-xs text-slate-400 mt-1">Coba ubah kata kunci pencarian atau reset filter.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(method_exists($users, 'links'))
            <div class="mt-5">
                {{ $users->links() }}
            </div>
            @endif

        </div>

    </div>
</x-admin-layout>

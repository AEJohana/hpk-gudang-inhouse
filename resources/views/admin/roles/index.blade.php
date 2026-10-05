<x-admin-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 240px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-12 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-user-shield me-1 text-[9px]"></i> KEAMANAN SISTEM
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Role & Hak Akses
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                            Matriks Peran & Hak Akses (RBAC)
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            Konfigurasi matriks permission berbasis modul (Spatie Laravel Permission) untuk 5 divisi pergudangan HPK Karoseri.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. ROLES GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($roles as $role)
                @php
                    $roleSlug = $role->name;
                    $badgeStyle = match($roleSlug) {
                        'admin_gudang' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'border' => 'border-amber-300/80', 'icon' => 'fa-crown'],
                        'supervisor' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'border' => 'border-blue-300/80', 'icon' => 'fa-user-tie'],
                        'operator' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'border' => 'border-emerald-300/80', 'icon' => 'fa-dolly'],
                        'engineering' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'border' => 'border-purple-300/80', 'icon' => 'fa-drafting-compass'],
                        'qc' => ['bg' => 'bg-teal-100', 'text' => 'text-teal-800', 'border' => 'border-teal-300/80', 'icon' => 'fa-clipboard-check'],
                        default => ['bg' => 'bg-slate-100', 'text' => 'text-slate-800', 'border' => 'border-slate-300/80', 'icon' => 'fa-user'],
                    };
                    $usersCount = $role->users()->count();
                    $permsCount = $role->permissions->count();
                    $totalPerms = \Spatie\Permission\Models\Permission::count();
                    $pct = $totalPerms > 0 ? round(($permsCount / $totalPerms) * 100) : 0;
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        <!-- Header Card -->
                        <div class="flex items-start justify-between pb-4 border-b border-slate-100 mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl {{ $badgeStyle['bg'] }} {{ $badgeStyle['text'] }} flex items-center justify-center font-bold text-sm shadow-xs">
                                    <i class="fa-solid {{ $badgeStyle['icon'] }}"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">
                                        {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                    </h3>
                                    <span class="text-[10px] font-mono text-slate-400">role: {{ $role->name }}</span>
                                </div>
                            </div>
                            <a href="{{ route('admin.roles.edit', $role) }}" 
                               class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition shadow-2xs">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Atur
                            </a>
                        </div>

                        <!-- Stats Row -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Pengguna</span>
                                <span class="text-lg font-black text-slate-900">{{ $usersCount }}</span>
                                <span class="text-[10px] text-slate-400 block">akun aktif</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Kewenangan</span>
                                <span class="text-lg font-black text-slate-900">{{ $permsCount }}</span>
                                <span class="text-[10px] text-slate-400 block">dari {{ $totalPerms }} izin ({{ $pct }}%)</span>
                            </div>
                        </div>

                        <!-- Permission Coverage Bar -->
                        <div class="mb-4">
                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $roleSlug == 'admin_gudang' ? 'bg-amber-500' : 'bg-blue-600' }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>

                        <!-- Sample Permissions Badges -->
                        <div class="space-y-1.5">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Cakupan Modul Utama:</span>
                            <div class="flex flex-wrap gap-1">
                                @forelse($role->permissions->take(6) as $perm)
                                    <span class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium px-2 py-0.5 rounded-md font-mono transition">
                                        {{ $perm->name }}
                                    </span>
                                @empty
                                    <span class="text-slate-400 text-xs italic">Belum ada izin khusus diberikan</span>
                                @endforelse

                                @if($permsCount > 6)
                                    <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-md">
                                        +{{ $permsCount - 6 }} izin lainnya
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-400 text-[11px]">Guard: {{ $role->guard_name }}</span>
                        <a href="{{ route('admin.roles.edit', $role) }}" class="font-bold text-blue-600 hover:text-blue-800 flex items-center">
                            <span>Ubah Matriks</span>
                            <i class="fa-solid fa-chevron-right ms-1 text-[10px]"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</x-admin-layout>

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
                        <i class="fa-solid fa-users-gear me-1 text-[9px]"></i> KONTROL PENGGUNA
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Sistem & Otorisasi
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                            Manajemen Pengguna WMS
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            Kelola data akun pengguna, penugasan role per divisi (Admin, SPV, Operator, Engineering, QC), dan status akses aktif.
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0 flex items-center space-x-3">
                        <a href="{{ route('admin.users.create') }}" 
                           class="inline-flex items-center px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs tracking-wide shadow-md hover:shadow-lg transition">
                            <i class="fa-solid fa-user-plus me-2 text-sm"></i>
                            Tambah User Baru
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MAIN CARD: FILTER & USER TABLE -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            
            <!-- Filter Toolbar -->
            <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <!-- Search Input -->
                    <div>
                        <label for="search" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Cari User
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" 
                                   id="search" 
                                   name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Nama, email, username..." 
                                   class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
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
                            <option value="">Semua Role ({{ $roles->count() }})</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Dropdown -->
                    <div>
                        <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Status Akun
                        </label>
                        <select name="status" 
                                id="status" 
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif (Dapat Login)</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif (Terkunci)</option>
                        </select>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex items-center space-x-2">
                        <button type="submit" 
                                class="flex-1 py-2 px-4 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center">
                            <i class="fa-solid fa-filter me-1.5"></i> Terapkan
                        </button>
                        @if(request()->hasAny(['search', 'role', 'status']))
                        <a href="{{ route('admin.users.index') }}" 
                           class="py-2 px-3 bg-white hover:bg-slate-100 text-slate-600 border border-slate-300 rounded-xl text-xs font-semibold transition" 
                           title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>

            <!-- Table Section -->
            <div class="overflow-x-auto rounded-xl border border-slate-200/80 shadow-xs">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50 uppercase tracking-wider text-slate-600 font-extrabold border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5">Nama & Profil</th>
                            <th class="px-5 py-3.5">Username / Email</th>
                            <th class="px-5 py-3.5">Departemen</th>
                            <th class="px-5 py-3.5">Role Akses</th>
                            <th class="px-5 py-3.5">Status Akun</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($users as $user)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Name & Avatar -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center space-x-3">
                                    @php
                                        $roleSlug = $user->roles->first()->name ?? $user->role;
                                        $avatarBg = match($roleSlug) {
                                            'admin_gudang' => 'bg-amber-500 text-slate-950 font-black',
                                            'supervisor' => 'bg-blue-600 text-white font-black',
                                            'operator' => 'bg-emerald-600 text-white font-black',
                                            'engineering' => 'bg-purple-600 text-white font-black',
                                            'qc' => 'bg-teal-600 text-white font-black',
                                            default => 'bg-slate-700 text-white font-black',
                                        };
                                    @endphp
                                    <div class="w-9 h-9 rounded-full {{ $avatarBg }} flex items-center justify-center text-xs shadow-xs flex-shrink-0">
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

                            <!-- Username / Email -->
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-900 font-mono text-[11px]">
                                    {{ $user->username ? '@'.$user->username : '-' }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $user->email }}
                                </div>
                            </td>

                            <!-- Departemen -->
                            <td class="px-5 py-3.5 text-slate-600 font-semibold">
                                {{ $user->department ?? '-' }}
                            </td>

                            <!-- Role Badge -->
                            <td class="px-5 py-3.5">
                                @php
                                    $roleBadgeClass = match($roleSlug) {
                                        'admin_gudang' => 'bg-amber-100 text-amber-800 border-amber-300/60',
                                        'supervisor' => 'bg-blue-100 text-blue-800 border-blue-300/60',
                                        'operator' => 'bg-emerald-100 text-emerald-800 border-emerald-300/60',
                                        'engineering' => 'bg-purple-100 text-purple-800 border-purple-300/60',
                                        'qc' => 'bg-teal-100 text-teal-800 border-teal-300/60',
                                        default => 'bg-slate-100 text-slate-800 border-slate-300/60',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $roleBadgeClass }}">
                                    <i class="fa-solid fa-shield-halved me-1 text-[9px]"></i>
                                    {{ $user->role_badge }}
                                </span>
                            </td>

                            <!-- Status Akun -->
                            <td class="px-5 py-3.5">
                                @if($user->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 me-1.5 animate-pulse"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 me-1.5"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="px-5 py-3.5 text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="p-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition shadow-2xs" 
                                       title="Edit Data User">
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
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-xl">
                                    <i class="fa-solid fa-user-slash"></i>
                                </div>
                                <p class="font-bold text-sm text-slate-700">Tidak ada pengguna yang cocok</p>
                                <p class="text-xs text-slate-400 mt-1">Coba ubah kata kunci pencarian atau reset filter role.</p>
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

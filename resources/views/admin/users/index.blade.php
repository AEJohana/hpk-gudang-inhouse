<x-admin-layout>
    <div class="wave-header pb-12 pt-8 relative overflow-hidden" style="min-height: 180px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="bg-hpk-orange/20 text-amber-300 border border-amber-300/50 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-widest uppercase">
                            Admin
                        </span>
                        <span class="text-teal-300 text-xs font-bold tracking-widest uppercase">Manajemen User</span>
                    </div>
                    <h1 class="text-3xl font-black text-white tracking-wide mb-1">
                        Daftar Pengguna Sistem
                    </h1>
                </div>
                <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-[#0a2342] border border-amber-400/50 hover:bg-slate-800 text-amber-400 font-bold text-sm rounded-xl shadow-md transition flex items-center">
                    <i class="fa-solid fa-user-plus mr-2"></i> Tambah User Baru
                </a>
            </div>
        </div>
        <div class="wave-shape-2">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,60 C320,120 420,0 720,60 C1020,120 1120,0 1440,60 L1440,120 L0,120 Z" fill="#f0f4f8"></path>
            </svg>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20 pb-12">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            
            <!-- Filter Section -->
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4 mb-6">
                <div class="flex-1">
                    <x-input-label for="search" value="Cari (Nama / Email / Username)" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Ketik kata kunci..." />
                </div>
                <div class="w-full md:w-48">
                    <x-input-label for="role" value="Filter Role" />
                    <select name="role" id="role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">Semua Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <x-input-label for="status" value="Filter Status" />
                    <select name="status" id="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="w-full md:w-auto">
                    <x-primary-button type="submit" class="w-full justify-center">Terapkan Filter</x-primary-button>
                </div>
            </form>

            <!-- Table Data -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="uppercase tracking-wider text-slate-500 font-bold bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Username & Email</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Last Login</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-hpk-blue text-white flex items-center justify-center font-bold shadow-sm">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $user->department ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800">{{ $user->username ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $user->email }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-indigo-100 text-indigo-700 font-bold px-3 py-1 rounded-full text-xs">
                                    {{ $user->roles->first()->name ?? $user->role ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->is_active)
                                    <span class="bg-emerald-100 text-emerald-700 font-bold px-2 py-0.5 rounded-full text-[11px]">Aktif</span>
                                @else
                                    <span class="bg-slate-200 text-slate-700 font-bold px-2 py-0.5 rounded-full text-[11px]">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : 'Belum pernah' }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:text-blue-800 px-2 py-1 bg-blue-50 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                
                                @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus/menonaktifkan pengguna ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 px-2 py-1 bg-rose-50 rounded-lg transition" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-users-slash text-4xl mb-3 text-slate-300"></i>
                                    <p>Tidak ada data pengguna ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $users->links() }}
            </div>
            
        </div>
    </div>
</x-admin-layout>

<x-admin-layout>
    <div class="wave-header pb-12 pt-8 relative overflow-hidden" style="min-height: 180px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="bg-hpk-orange/20 text-amber-300 border border-amber-300/50 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-widest uppercase">
                            Admin
                        </span>
                        <span class="text-teal-300 text-xs font-bold tracking-widest uppercase">Matriks Permission</span>
                    </div>
                    <h1 class="text-3xl font-black text-white tracking-wide mb-1">
                        Edit Role: {{ ucwords(str_replace('_', ' ', $role->name)) }}
                    </h1>
                </div>
            </div>
        </div>
        <div class="wave-shape-2">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,60 C320,120 420,0 720,60 C1020,120 1120,0 1440,60 L1440,120 L0,120 Z" fill="#f0f4f8"></path>
            </svg>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20 pb-12">
        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-6">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-slate-800">Daftar Hak Akses (Permissions)</h2>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mr-2">
                        <span class="text-sm font-semibold text-slate-700">Pilih Semua</span>
                    </label>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php $rolePermissions = $role->permissions->pluck('name')->toArray(); @endphp
                    
                    @foreach($permissions as $group => $perms)
                    <div class="border border-slate-200 rounded-xl p-4">
                        <h3 class="font-bold text-slate-800 uppercase tracking-widest text-xs mb-3 border-b border-slate-100 pb-2">Modul: {{ strtoupper($group) }}</h3>
                        <div class="space-y-2">
                            @foreach($perms as $perm)
                            <label class="flex items-start cursor-pointer hover:bg-slate-50 p-1.5 rounded transition">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 permission-checkbox" {{ in_array($perm->name, $rolePermissions) ? 'checked' : '' }}>
                                <div class="ml-2">
                                    <span class="text-sm font-semibold text-slate-700 block leading-none mt-1">{{ explode('.', $perm->name)[1] ?? $perm->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $perm->name }}</span>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <div class="flex items-center justify-end">
                <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm rounded-xl mr-3 transition">Batal</a>
                <button type="submit" class="px-6 py-2 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-sm rounded-xl shadow-md transition">
                    Simpan Matriks Permission
                </button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            let checkboxes = document.querySelectorAll('.permission-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</x-admin-layout>

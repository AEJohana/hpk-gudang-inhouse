<x-admin-layout>
    <div class="wave-header pb-12 pt-8 relative overflow-hidden" style="min-height: 180px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center space-x-3 mb-2">
                <span class="bg-hpk-orange/20 text-amber-300 border border-amber-300/50 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-widest uppercase">
                    Admin
                </span>
                <span class="text-teal-300 text-xs font-bold tracking-widest uppercase">Role Sistem</span>
            </div>
            <h1 class="text-3xl font-black text-white tracking-wide mb-1">
                Matriks Hak Akses
            </h1>
        </div>
        <div class="wave-shape-2">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,60 C320,120 420,0 720,60 C1020,120 1120,0 1440,60 L1440,120 L0,120 Z" fill="#f0f4f8"></path>
            </svg>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20 pb-12">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($roles as $role)
                <div class="border border-slate-200 rounded-xl p-5 hover:shadow-md transition">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <h3 class="font-bold text-slate-800 text-lg">{{ ucwords(str_replace('_', ' ', $role->name)) }}</h3>
                        <a href="{{ route('admin.roles.edit', $role) }}" class="text-blue-600 hover:text-blue-800 bg-blue-50 px-3 py-1 rounded-lg text-xs font-semibold transition">
                            <i class="fa-solid fa-pen mr-1"></i> Edit
                        </a>
                    </div>
                    <div class="text-sm text-slate-600 mb-2">
                        <span class="font-bold">{{ $role->users()->count() }}</span> User dengan role ini
                    </div>
                    <div class="text-sm text-slate-600">
                        <span class="font-bold">{{ $role->permissions->count() }}</span> Izin khusus (Permissions)
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="flex flex-wrap gap-1">
                            @foreach($role->permissions->take(5) as $perm)
                                <span class="bg-slate-100 text-slate-600 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $perm->name }}</span>
                            @endforeach
                            @if($role->permissions->count() > 5)
                                <span class="bg-slate-100 text-slate-500 text-[10px] px-2 py-0.5 rounded-full">+ {{ $role->permissions->count() - 5 }} lainnya</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
        </div>
    </div>
</x-admin-layout>

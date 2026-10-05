<nav class="bg-[#0a2342] border-b border-white/10 text-slate-200 sticky top-0 z-40 shadow-lg">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left: Sidebar Toggle, Brand & Desktop Links -->
            <div class="flex items-center space-x-3">
                <!-- Hamburger Drawer Toggle Button -->
                <button type="button" 
                        @click="sidebarOpen = true" 
                        title="Buka Menu Navigasi"
                        class="p-2 text-white hover:text-amber-400 rounded-xl hover:bg-white/10 transition flex items-center justify-center focus:outline-none">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                <!-- Brand Logo & Title -->
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 p-1 bg-white rounded-xl shadow-sm border border-slate-700/50 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                        <img src="{{ asset('images/logo_hpk.webp') }}" alt="HPK Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center space-x-2">
                            <span class="font-extrabold text-sm tracking-wide text-white leading-tight">HYDRAXLE PERKASA</span>
                            <span class="bg-amber-400 text-slate-950 text-[9px] font-black px-1.5 py-0.5 rounded tracking-wider uppercase">ADMIN</span>
                        </div>
                        <span class="text-[10px] font-semibold text-amber-300 tracking-wider uppercase">Pusat Kendali Sistem</span>
                    </div>
                </a>

                <!-- Desktop Shortcut Navigation Links -->
                <div class="hidden xl:flex items-center space-x-1 ms-6">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('admin.dashboard') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <i class="fa-solid fa-gauge-high me-1.5 text-[11px]"></i>Dashboard
                    </a>
                    <a href="{{ route('admin.users.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('admin.users.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <i class="fa-solid fa-users me-1.5 text-[11px]"></i>User
                    </a>
                    <a href="{{ route('admin.roles.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('admin.roles.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <i class="fa-solid fa-user-shield me-1.5 text-[11px]"></i>Role
                    </a>
                    
                    <!-- Master Data Dropdown -->
                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button class="px-3 py-1.5 text-xs font-semibold rounded-lg transition flex items-center {{ request()->routeIs('admin.component-categories.*', 'admin.uoms.*', 'admin.locations-master.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <i class="fa-solid fa-database me-1.5 text-[11px]"></i>
                                <span>Master Data</span>
                                <i class="fa-solid fa-chevron-down text-[9px] ms-1.5 opacity-70"></i>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('admin.component-categories.index')" class="text-xs">
                                <i class="fa-solid fa-tags me-2 text-teal-600"></i> Kategori Komponen
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('admin.uoms.index')" class="text-xs">
                                <i class="fa-solid fa-scale-balanced me-2 text-teal-600"></i> Satuan Ukur (UoM)
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('admin.locations-master.index')" class="text-xs">
                                <i class="fa-solid fa-boxes-stacked me-2 text-teal-600"></i> Gudang & Lokasi Rak
                            </x-dropdown-link>
                        </x-slot>
                    </x-dropdown>

                    <a href="{{ route('admin.settings.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('admin.settings.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <i class="fa-solid fa-gears me-1.5 text-[11px]"></i>Pengaturan
                    </a>
                    <a href="{{ route('admin.audit-logs.index') }}" 
                       class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <i class="fa-solid fa-clipboard-list me-1.5 text-[11px]"></i>Audit Log
                    </a>
                </div>
            </div>

            <!-- Right: Return to App & User Glass Pill -->
            <div class="flex items-center space-x-3">
                <!-- Return to Main App Button -->
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center px-3.5 py-1.5 rounded-xl bg-teal-500/20 hover:bg-teal-500/30 text-teal-300 hover:text-teal-200 border border-teal-400/30 text-xs font-bold transition shadow-xs">
                    <i class="fa-solid fa-arrow-left me-1.5 text-xs"></i>
                    <span class="hidden sm:inline">Aplikasi Utama</span>
                </a>

                <!-- User Profile Glass Pill -->
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="user-glass-pill focus:outline-none">
                            <div class="w-8 h-8 rounded-full bg-amber-500/30 border border-amber-400/50 text-amber-200 font-extrabold text-xs flex items-center justify-center shadow-xs">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <div class="flex flex-col text-left leading-tight hidden md:flex">
                                <span class="font-bold text-xs text-white tracking-wide truncate max-w-[130px]">{{ Auth::user()->name }}</span>
                                <span class="text-[10px] text-amber-300 font-medium truncate max-w-[130px]">{{ Auth::user()->role_badge }}</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-300 ml-0.5"></i>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2.5 border-b border-slate-100 bg-slate-50 rounded-t-lg">
                            <p class="text-xs text-slate-400">Login sebagai:</p>
                            <p class="text-xs font-bold text-slate-800">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] font-semibold text-amber-600 uppercase">{{ Auth::user()->role_badge }} &bull; {{ Auth::user()->department ?? 'Gudang HPK' }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')" class="text-xs">
                            <i class="fa-solid fa-user-gear me-2 text-slate-400"></i> {{ __('Profil Akun') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('dashboard')" class="text-xs">
                            <i class="fa-solid fa-boxes-stacked me-2 text-teal-600"></i> {{ __('Aplikasi Gudang WMS') }}
                        </x-dropdown-link>

                        <!-- Authentication Sign Out -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="text-rose-600 font-semibold text-xs border-t border-slate-100">
                                <i class="fa-solid fa-arrow-right-from-bracket me-2 text-rose-500"></i> {{ __('Keluar (Log Out)') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>

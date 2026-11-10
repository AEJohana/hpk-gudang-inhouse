@php
    $currentNavbarMenu = match(true) {
        request()->routeIs('work-requests.*') => 'Work Request Mesin',
        request()->routeIs('components.*') => 'Master Komponen',
        request()->routeIs('transactions.*') => 'Transaksi Komponen',
        request()->routeIs('work-station-supplies.*') => 'Material Handling',
        request()->routeIs('ecrs.*') => 'ECR Revisi Part',
        request()->routeIs('cycle-counts.*') => 'Cycle Count',
        request()->routeIs('qr-requests.*') => 'Cetak Label QR',
        request()->routeIs('disposals.*') => 'Pengajuan Disposal',
        request()->routeIs('locations.*') => 'Peta Denah Gudang',
        request()->routeIs('activities.*') => 'Log Aktivitas Sistem',
        request()->routeIs('admin.*') => 'Panel Administrasi',
        default => null,
    };
@endphp
<nav class="bg-[#0a2342] border-b border-white/10 text-slate-200 sticky top-0 z-40 shadow-lg w-full max-w-full overflow-hidden">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 gap-2">
            <!-- Left: Sidebar Toggle & Brand Logo -->
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                <!-- Hamburger Drawer Toggle Button (Visible all screens) -->
                <button type="button" 
                        @click="sidebarOpen = true" 
                        title="Buka Menu Navigasi"
                        class="p-2 text-white hover:text-teal-300 rounded-xl hover:bg-white/10 transition flex items-center justify-center focus:outline-none shrink-0">
                    <i class="fa-solid fa-bars text-lg sm:text-xl"></i>
                </button>

                <!-- Brand Logo & Title -->
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 sm:space-x-3 group min-w-0 shrink-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 p-1 bg-white rounded-xl shadow-sm border border-slate-700/50 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                        <img src="{{ asset('images/logo_hpk.webp') }}" alt="HPK Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="flex-col min-w-0 {{ $currentNavbarMenu ? 'hidden sm:flex' : 'flex' }}">
                        <span class="font-extrabold text-xs sm:text-sm tracking-wide text-white leading-tight truncate max-w-[125px] sm:max-w-none">HYDRAXLE PERKASA</span>
                        <span class="text-[9px] sm:text-[10px] font-semibold text-teal-400 tracking-wider uppercase truncate max-w-[125px] sm:max-w-none">WMS In-House</span>
                    </div>
                </a>

                @if($currentNavbarMenu)
                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 pl-1 sm:pl-2">
                    <div class="h-4 sm:h-6 w-px bg-white/30 shrink-0"></div>
                    <span class="font-bold text-xs sm:text-sm text-white tracking-wide truncate max-w-[130px] sm:max-w-none">
                        {{ $currentNavbarMenu }}
                    </span>
                </div>
                @endif
            </div>

            <!-- Right: User Glass Pill -->
            <div class="flex items-center space-x-2 sm:space-x-3 shrink-0">
                <!-- User Profile Glass Pill (Reference Design) -->
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="user-glass-pill focus:outline-none">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-teal-500/30 border border-teal-400/50 text-teal-200 font-extrabold text-xs flex items-center justify-center shadow-xs shrink-0">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <div class="flex flex-col text-left leading-tight hidden md:flex">
                                <span class="font-bold text-xs text-white tracking-wide truncate max-w-[130px]">{{ Auth::user()->name }}</span>
                                <span class="text-[10px] text-teal-300 font-medium truncate max-w-[130px]">{{ Auth::user()->department ?? Auth::user()->role_badge }}</span>
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
                            <i class="fa-solid fa-gauge-high me-2 text-slate-400"></i> {{ __('Dashboard WMS') }}
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

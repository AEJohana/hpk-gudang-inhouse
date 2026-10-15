<nav class="bg-[#0a2342] border-b border-white/10 text-slate-200 sticky top-0 z-40 shadow-lg">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left: Sidebar Toggle & Brand Logo -->
            <div class="flex items-center space-x-3">
                <!-- Hamburger Drawer Toggle Button (Visible all screens) -->
                <button type="button" 
                        @click="sidebarOpen = true" 
                        title="Buka Menu Navigasi"
                        class="p-2 text-white hover:text-teal-300 rounded-xl hover:bg-white/10 transition flex items-center justify-center focus:outline-none">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                <!-- Brand Logo & Title -->
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 p-1 bg-white rounded-xl shadow-sm border border-slate-700/50 flex items-center justify-center group-hover:scale-105 transition-transform flex-shrink-0">
                        <img src="{{ asset('images/logo_hpk.webp') }}" alt="HPK Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="flex flex-col">
                        <span class="font-extrabold text-sm tracking-wide text-white leading-tight">HYDRAXLE PERKASA</span>
                        <span class="text-[10px] font-semibold text-teal-400 tracking-wider uppercase">WMS In-House Karoseri</span>
                    </div>
                </a>

                <!-- Desktop Shortcut Navigation Links (Visible on Laptop & Desktop from xl: 1280px) -->
                <div class="hidden xl:flex items-center space-x-1 ms-4 lg:ms-6">
                    <a href="{{ route('dashboard') }}" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('components.index') }}" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('components.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        Komponen
                    </a>
                    <a href="{{ route('transactions.index') }}" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('transactions.*') && !request()->routeIs('work-station-supplies.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        Transaksi
                    </a>
                    @can('work-request.view')
                    <a href="{{ route('work-requests.index') }}" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('work-requests.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <i class="fa-solid fa-industry me-1 text-teal-300 text-[10px]"></i> Work Request (WRI)
                    </a>
                    @endcan
                    <a href="{{ route('warehouse-map.index') }}" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg transition {{ request()->routeIs('warehouse-map.*', 'locations.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        Peta Gudang
                    </a>

                    <!-- Dropdown: Kualitas & Audit -->
                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button class="px-2.5 py-1.5 text-xs font-semibold rounded-lg transition flex items-center {{ request()->routeIs('ecrs.*', 'disposals.*', 'qr-requests.*', 'cycle-counts.*') ? 'bg-white/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span>Kontrol & Audit</span>
                                <i class="fa-solid fa-chevron-down text-[9px] ms-1.5 opacity-70"></i>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('ecrs.index')" class="text-xs">
                                <i class="fa-solid fa-file-pen me-2 text-purple-600"></i> ECR Revisi Part
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('disposals.index')" class="text-xs">
                                <i class="fa-solid fa-trash-can me-2 text-rose-600"></i> Pengajuan Disposal
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('qr-requests.index')" class="text-xs">
                                <i class="fa-solid fa-qrcode me-2 text-cyan-600"></i> Permintaan Label QR
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('cycle-counts.index')" class="text-xs">
                                <i class="fa-solid fa-clipboard-check me-2 text-amber-600"></i> Cycle Count Opname
                            </x-dropdown-link>
                            @can('material-handling.view')
                            <x-dropdown-link :href="route('work-station-supplies.index')" class="text-xs border-t border-slate-100">
                                <i class="fa-solid fa-truck-ramp-box me-2 text-blue-600"></i> Material Handling Center
                            </x-dropdown-link>
                            @endcan
                        </x-slot>
                    </x-dropdown>
                    
                    @if(Auth::user()->hasRole('admin_gudang'))
                    <a href="{{ route('admin.dashboard') }}" class="px-2.5 py-1.5 text-xs font-bold rounded-lg transition bg-amber-500/20 text-amber-300 border border-amber-300/30 hover:bg-amber-500/30 ml-1">
                        <i class="fa-solid fa-gear me-1 text-[10px]"></i> Admin
                    </a>
                    @endif
                </div>
            </div>

            <!-- Right: Quick Camera Scanner Button & User Glass Pill -->
            <div class="flex items-center space-x-3">
                <!-- Global QR & Barcode Scanner Button -->
                <button type="button" 
                        @click="$dispatch('open-global-scanner')" 
                        class="inline-flex items-center px-3 py-1.5 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold tracking-wide transition shadow-sm hover:shadow-md">
                    <i class="fa-solid fa-qrcode me-1.5 text-xs"></i>
                    <span>Scan Part</span>
                </button>

                <!-- User Profile Glass Pill (Reference Design) -->
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="user-glass-pill focus:outline-none">
                            <div class="w-8 h-8 rounded-full bg-teal-500/30 border border-teal-400/50 text-teal-200 font-extrabold text-xs flex items-center justify-center shadow-xs">
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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'WMS HPK') }} - PT Hydraxle Perkasa Karoseri</title>
        <link rel="icon" type="image/webp" href="{{ asset('images/logo_hpk.webp') }}">

        <!-- Fonts & Icons -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-800 min-h-screen flex flex-col" 
          x-data="globalApp()" 
          @open-global-scanner.window="openScanner()">

        <!-- BACKDROP OVERLAY FOR SIDEBAR -->
        <div x-show="sidebarOpen" 
             style="display: none;" 
             @click="sidebarOpen = false" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-950/70 z-50 backdrop-blur-xs"></div>

        <!-- SLIDING HPK NAVY SIDEBAR DRAWER (FROM REFERENS) -->
        <aside class="hpk-sidebar" :class="{ 'active': sidebarOpen }">
            <!-- Sidebar Header with Brand & Close Button -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-white p-1.5 flex items-center justify-center shadow-md">
                        <img src="{{ asset('images/logo_hpk.webp') }}" alt="Logo HPK" class="w-full h-full object-contain">
                    </div>
                    <div class="flex flex-col">
                        <span class="font-extrabold text-xs tracking-wider text-white">PT. HYDRAXLE PERKASA</span>
                        <span class="text-[9px] font-semibold text-amber-400 tracking-widest uppercase">WMS In-House Karoseri</span>
                    </div>
                </div>
                <button type="button" @click="sidebarOpen = false" class="text-white/70 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Sidebar User Profile Card -->
            @auth
            <div class="sidebar-user-card">
                <div class="w-14 h-14 rounded-full bg-white/10 border-2 border-amber-400 text-white flex items-center justify-center mx-auto mb-2 text-xl font-bold shadow-inner">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <div class="font-bold text-white text-sm truncate">{{ Auth::user()->name }}</div>
                <div class="text-[11px] text-amber-400 font-semibold tracking-wider uppercase mt-0.5">{{ Auth::user()->role_badge }}</div>
                <div class="text-[10px] text-slate-300 mt-0.5">{{ Auth::user()->department ?? 'Operasional Gudang' }}</div>
            </div>
            @endauth

            <!-- Sidebar Navigation Links -->
            <nav class="space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard Admin</span>
                </a>
                
                <div class="px-4 py-2 mt-2 mb-1">
                    <span class="text-[10px] font-bold text-amber-500/80 uppercase tracking-widest">Sistem & Pengguna</span>
                </div>
                
                <a href="{{ route('admin.users.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i>
                    <span>Manajemen User</span>
                </a>
                <a href="{{ route('admin.roles.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Role & Permission</span>
                </a>

                <div class="px-4 py-2 mt-2 mb-1">
                    <span class="text-[10px] font-bold text-teal-500/80 uppercase tracking-widest">Master Data</span>
                </div>

                <a href="{{ route('admin.component-categories.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.component-categories.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-tags"></i>
                    <span>Kategori Komponen</span>
                </a>
                <a href="{{ route('admin.uoms.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.uoms.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <span>Satuan (UoM)</span>
                </a>
                <a href="{{ route('admin.locations-master.index') }}" class="hpk-sidebar-item {{ request()->requestIs('admin.locations-master.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Gudang & Lokasi</span>
                </a>

                <div class="px-4 py-2 mt-2 mb-1">
                    <span class="text-[10px] font-bold text-rose-500/80 uppercase tracking-widest">Konfigurasi</span>
                </div>

                <a href="{{ route('admin.settings.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gears"></i>
                    <span>Pengaturan Sistem</span>
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Audit Log</span>
                </a>

                <div class="my-4 border-t border-white/10"></div>
                <a href="{{ route('dashboard') }}" class="hpk-sidebar-item text-teal-300 hover:text-teal-200">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali ke Aplikasi Utama</span>
                </a>
            </nav>

            <!-- Quick Scanner Action -->
            <div class="mt-5 pt-4 border-t border-white/10">
                <button type="button" @click="sidebarOpen = false; openScanner();" class="w-full py-2.5 px-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs flex items-center justify-center space-x-2 transition shadow-md">
                    <i class="fa-solid fa-camera"></i>
                    <span>Buka Scanner Kamera</span>
                </button>
            </div>

            <!-- Footer Signout & Profile -->
            @auth
            <div class="mt-5 pt-4 border-t border-white/10 flex items-center justify-between">
                <a href="{{ route('profile.edit') }}" class="text-xs text-slate-300 hover:text-white flex items-center space-x-1.5 transition">
                    <i class="fa-solid fa-user-gear"></i>
                    <span>Profil</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 font-semibold flex items-center space-x-1.5 transition">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
            @endauth
        </aside>

        <!-- Navigation Bar -->
        @include('layouts.navigation')

        <!-- Flash Messages -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            @if (session('success'))
                <div class="p-4 mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Page Heading (Optional) -->
        @if (isset($header))
            <header class="bg-white border-b border-slate-200 shadow-sm">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Main Content -->
        <main class="flex-1 pb-12">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between">
                <span>&copy; {{ date('Y') }} PT Hydraxle Perkasa Karoseri &bull; Warehouse Management System</span>
                <span class="text-[11px] text-slate-400 mt-1 sm:mt-0">Sistem Pergudangan Karoseri & Komponen Hidrolik In-House</span>
            </div>
        </footer>

        <!-- GLOBAL CAMERA SCANNER MODAL -->
        <div x-show="scannerOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            
            <div @click.away="closeScanner()" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 transition-all">
                <!-- Modal Header -->
                <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="p-1.5 bg-amber-500/20 text-amber-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Scanner Barcode & QR Code HPK</h3>
                            <p class="text-[11px] text-slate-400">Arahkan kamera ke label atau ketik manual kode part</p>
                        </div>
                    </div>
                    <button type="button" @click="closeScanner()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 space-y-4">
                    <!-- Camera Viewport -->
                    <div class="relative bg-slate-950 rounded-xl overflow-hidden min-h-[220px] flex items-center justify-center">
                        <div id="global-scanner-reader" class="w-full"></div>
                        <div x-show="isScanning" class="absolute inset-x-8 top-1/2 -translate-y-1/2 h-1 bg-amber-500 shadow-[0_0_8px_#f59e0b] animate-pulse pointer-events-none"></div>
                    </div>

                    <!-- Manual Input / Barcode Gun field -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Input Manual / Barcode Scanner Gun:</label>
                        <div class="flex space-x-2">
                            <input type="text" 
                                   x-model="manualCode" 
                                   @keydown.enter.prevent="lookupCode(manualCode)"
                                   placeholder="Contoh: HYD-CYL-160 atau scan barcode..." 
                                   class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <button type="button" 
                                    @click="lookupCode(manualCode)" 
                                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs rounded-lg transition">
                                Cari
                            </button>
                        </div>
                    </div>

                    <!-- Result Preview Card (Loaded via AJAX) -->
                    <div x-show="scannedComponent" style="display: none;" class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl">
                        <div class="flex space-x-4">
                            <!-- Component Photo Thumbnail -->
                            <img :src="scannedComponent?.image_url" 
                                 :alt="scannedComponent?.name" 
                                 class="w-20 h-20 rounded-lg object-cover border border-amber-300 bg-white shadow-sm flex-shrink-0">
                            
                            <!-- Component Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-mono font-bold text-amber-900 bg-amber-200 px-2 py-0.5 rounded" x-text="scannedComponent?.part_number"></span>
                                    <span class="text-xs font-bold text-slate-700">Stok: <span class="text-emerald-700 font-extrabold text-sm" x-text="scannedComponent?.total_stock + ' ' + (scannedComponent?.uom || '')"></span></span>
                                </div>
                                <h4 class="text-sm font-bold text-slate-900 mt-1 truncate" x-text="scannedComponent?.name"></h4>
                                <p class="text-xs text-slate-600 mt-0.5" x-text="'Lokasi: ' + scannedComponent?.default_location"></p>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1" x-text="scannedComponent?.specification"></p>
                            </div>
                        </div>

                        <!-- Actions for Scanned Item -->
                        <div class="mt-3 pt-3 border-t border-amber-200/80 flex items-center justify-end space-x-2">
                            <a :href="'/components/' + scannedComponent?.id" class="px-3 py-1.5 text-xs font-semibold bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg shadow-sm">
                                Detail Part
                            </a>
                            <a :href="'/transactions/create?component_id=' + scannedComponent?.id" class="px-3 py-1.5 text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-lg shadow-sm">
                                Buat Transaksi
                            </a>
                        </div>
                    </div>

                    <!-- Not Found Warning -->
                    <div x-show="scanError" style="display: none;" class="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-lg font-medium" x-text="scanError"></div>
                </div>
            </div>
        </div>

        <script>
            function globalApp() {
                return {
                    sidebarOpen: false,
                    scannerOpen: false,
                    isScanning: false,
                    manualCode: '',
                    scannedComponent: null,
                    scanError: null,
                    html5QrCode: null,

                    openScanner() {
                        this.scannerOpen = true;
                        this.scannedComponent = null;
                        this.scanError = null;
                        this.manualCode = '';
                        this.$nextTick(() => {
                            this.startCameraScanner();
                        });
                    },

                    closeScanner() {
                        this.stopCameraScanner();
                        this.scannerOpen = false;
                    },

                    startCameraScanner() {
                        const readerElem = document.getElementById('global-scanner-reader');
                        if (!readerElem) return;

                        if (typeof Html5Qrcode === 'undefined') {
                            console.error('Html5Qrcode library not loaded');
                            return;
                        }

                        this.html5QrCode = new Html5Qrcode('global-scanner-reader');
                        const config = { fps: 10, qrbox: { width: 220, height: 220 } };

                        this.html5QrCode.start(
                            { facingMode: 'environment' },
                            config,
                            (decodedText, decodedResult) => {
                                // Scanned successfully
                                this.manualCode = decodedText;
                                this.lookupCode(decodedText);
                            },
                            (errorMessage) => {
                                // QR code parsing error - ignore background noise
                            }
                        ).then(() => {
                            this.isScanning = true;
                        }).catch(err => {
                            console.warn('Camera scan could not start:', err);
                            this.isScanning = false;
                        });
                    },

                    stopCameraScanner() {
                        if (this.html5QrCode) {
                            this.html5QrCode.stop().then(() => {
                                this.html5QrCode.clear();
                                this.isScanning = false;
                            }).catch(err => {
                                console.warn('Error stopping scanner:', err);
                            });
                        }
                    },

                    lookupCode(code) {
                        if (!code || !code.trim()) return;
                        this.scanError = null;

                        fetch(`/api/components/search?q=${encodeURIComponent(code.trim())}`)
                            .then(res => res.json())
                            .then(res => {
                                if (res.success && res.data) {
                                    this.scannedComponent = res.data;
                                    // Play feedback sound if possible
                                } else {
                                    this.scannedComponent = null;
                                    this.scanError = `Komponen dengan kode "${code}" tidak ditemukan di database.`;
                                }
                            })
                            .catch(err => {
                                console.error('Lookup error:', err);
                                this.scanError = 'Terjadi kesalahan saat memeriksa komponen ke server.';
                            });
                    }
                };
            }
        </script>
    </body>
</html>

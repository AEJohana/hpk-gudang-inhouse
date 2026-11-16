<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'WMS HPK') }} - PT Hydraxle Perkasa Karoseri</title>
        <link rel="icon" type="image/webp" href="{{ asset('images/logo_hpk.webp') }}">

        <!-- Offline Local Fonts & Icons -->
        <link rel="stylesheet" href="{{ asset('fonts/poppins.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

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
             :class="{ 'pointer-events-none': !sidebarOpen }"
             class="fixed inset-0 bg-slate-950/70 z-50 backdrop-blur-xs"></div>

        <!-- SLIDING ELEGANT GLASS SIDEBAR DRAWER (STYLE 5: ELEGANT GLASS) -->
        <aside class="hpk-sidebar" :class="{ 'active': sidebarOpen }">
            <!-- Sidebar Header with Brand & Close Button -->
            <div class="flex items-center justify-between mb-5 pb-3.5 border-b border-slate-200/60">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-11 h-11 rounded-2xl bg-white p-2 flex items-center justify-center shadow-xs border border-slate-200/80 shrink-0">
                        <img src="{{ asset('images/logo_hpk.webp') }}" alt="Logo HPK" class="w-full h-full object-contain">
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-black text-sm tracking-tight text-slate-900 leading-tight truncate">PT. HYDRAXLE PERKASA</span>
                        <span class="text-[10px] font-bold text-blue-700 tracking-wider uppercase truncate mt-0.5">WMS In-House Karoseri</span>
                    </div>
                </div>
                <button type="button" @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-xl hover:bg-white/80 transition cursor-pointer shrink-0" title="Tutup Menu">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard Admin</span>
                </a>
                
                <div class="px-3 py-1.5 mt-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sistem & Pengguna</span>
                </div>
                
                <a href="{{ route('admin.users.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Manajemen User & Akses</span>
                </a>

                <div class="px-3 py-1.5 mt-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Master Data</span>
                </div>

                <a href="{{ route('admin.component-categories.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.component-categories.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-tags"></i>
                    <span>Kategori Komponen</span>
                </a>
                <a href="{{ route('admin.uoms.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.uoms.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <span>Satuan (UoM)</span>
                </a>
                <a href="{{ route('admin.locations-master.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.locations-master.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Gudang & Lokasi</span>
                </a>

                <div class="px-3 py-1.5 mt-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Konfigurasi</span>
                </div>

                <a href="{{ route('admin.settings.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gears"></i>
                    <span>Pengaturan Sistem</span>
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="hpk-sidebar-item {{ request()->routeIs('admin.audit-logs.*', 'activities.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Audit Log</span>
                </a>

                <div class="my-3 border-t border-slate-200/60"></div>
                <a href="{{ route('dashboard') }}" class="hpk-sidebar-item text-blue-700 hover:text-blue-900 hover:bg-blue-50/80 font-bold">
                    <i class="fa-solid fa-arrow-left text-blue-600"></i>
                    <span>Kembali ke Aplikasi Utama</span>
                </a>
            </nav>

            <!-- Quick Scanner Action (Glass Floating Button) -->
            <div class="mt-4 pt-3 border-t border-slate-200/60">
                <button type="button" 
                        @click="sidebarOpen = false; openScanner();" 
                        class="w-full py-3 px-4 rounded-2xl bg-white/90 hover:bg-white border border-blue-200/70 text-slate-800 hover:text-blue-600 font-bold text-xs flex items-center justify-center space-x-2.5 transition shadow-xs hover:shadow-sm cursor-pointer active:scale-98">
                    <i class="fa-solid fa-camera text-blue-600 text-sm"></i>
                    <span>Buka Scanner Kamera</span>
                </button>
            </div>

            <!-- Executive User Profile Dock (Elegant Glass Style) -->
            @auth
            <div class="mt-4 pt-3 border-t border-slate-200/60 space-y-2">
                <div class="p-3 bg-white/80 hover:bg-white/95 rounded-2xl border border-white shadow-xs transition-all duration-200">
                    <!-- Top Row: Avatar + Info + Profile Gear -->
                    <div class="flex items-center space-x-2.5">
                        <!-- Avatar with Live Active Pulse Dot -->
                        <div class="relative shrink-0">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 font-black text-xs flex items-center justify-center shadow-2xs">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full shadow-2xs" title="Pengguna Aktif (Online)"></span>
                        </div>

                        <!-- User Info -->
                        <div class="flex-1 min-w-0">
                            <span class="font-black text-slate-900 text-xs truncate block tracking-wide uppercase leading-tight" title="{{ Auth::user()->name }}">
                                {{ Auth::user()->name }}
                            </span>
                            <div class="text-[11px] text-slate-500 font-medium truncate mt-0.5">
                                {{ Auth::user()->role_badge }}
                            </div>
                        </div>

                        <!-- Quick Profile Settings Button -->
                        <a href="{{ route('profile.edit') }}" 
                           class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-500 hover:text-blue-600 flex items-center justify-center transition shadow-2xs shrink-0 cursor-pointer"
                           title="Pengaturan Profil Akun">
                            <i class="fa-solid fa-gear text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- Minimalist Logout Link (Matching Mockup with Elegant Confirmation) -->
                <button type="button" 
                        @click="$dispatch('open-confirm-modal', {
                            title: 'Konfirmasi Keluar',
                            message: 'Apakah Anda yakin ingin keluar dari sistem? Sesi aktif Anda di perangkat ini akan diakhiri.',
                            confirmText: 'Ya, Keluar',
                            cancelText: 'Batal',
                            type: 'danger',
                            icon: 'fa-solid fa-arrow-right-from-bracket',
                            actionUrl: '{{ route('logout') }}',
                            method: 'POST'
                        })"
                        class="w-full py-2 flex items-center justify-center space-x-2 text-xs font-bold text-slate-600 hover:text-rose-600 transition cursor-pointer group">
                    <i class="fa-solid fa-arrow-right-from-bracket text-xs text-slate-400 group-hover:text-rose-500 transition"></i>
                    <span>Keluar</span>
                </button>
                <div class="text-[9px] text-slate-400/80 text-center font-medium tracking-wider uppercase pt-1">
                    PT. Hydraxle Perkasa
                </div>
            </div>
            @endauth
        </aside>

        <!-- Navigation Bar -->
        @include('layouts.admin-navigation')

        <!-- Global Notification & Modal Feedback System -->
        <x-toast-notification />
        <x-confirm-modal />
        <x-milestone-modal />

        <!-- Page Heading (Optional) -->
        @if (isset($header))
            <header class="bg-white border-b border-slate-200 shadow-sm">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Main Content -->
        <main class="flex-1 pb-24 md:pb-12">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500 mt-auto mb-14 md:mb-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between">
                <span>&copy; {{ date('Y') }} PT Hydraxle Perkasa Karoseri &bull; Warehouse Management System</span>
                <span class="text-[11px] text-slate-400 mt-1 sm:mt-0">Sistem Pergudangan Karoseri & Komponen Hidrolik In-House</span>
            </div>
        </footer>

        <!-- MOBILE BOTTOM NAVIGATION BAR (< md screens) -->
        <div class="mobile-bottom-nav md:hidden flex items-center justify-around px-2">
            <!-- 1. Admin Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="mobile-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Admin</span>
            </a>

            <!-- 2. User Management -->
            <a href="{{ route('admin.users.index') }}" class="mobile-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i>
                <span>User</span>
            </a>

            <!-- 3. Central Scanner FAB Button -->
            <button type="button" @click="openScanner()" class="mobile-scanner-fab" title="Scan QR Code / Barcode">
                <i class="fa-solid fa-qrcode"></i>
                <span class="text-[8px] font-black tracking-tighter uppercase mt-0.5">SCAN</span>
            </button>

            <!-- 4. Main WMS App Return -->
            <a href="{{ route('dashboard') }}" class="mobile-nav-item text-teal-300">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>WMS Utama</span>
            </a>

            <!-- 5. Menu Drawer Toggle -->
            <button type="button" @click="sidebarOpen = true" class="mobile-nav-item focus:outline-none">
                <i class="fa-solid fa-bars"></i>
                <span>Menu</span>
            </button>
        </div>

        <!-- GLOBAL CAMERA SCANNER MODAL -->
        <div x-show="scannerOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
            
            <div @click.away="closeScanner()" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[92vh] overflow-y-auto border border-slate-200 transition-all">
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

                    <!-- Camera Error / Permission Notice -->
                    <div x-show="cameraErrorMessage" style="display: none;" class="p-3 bg-amber-500/10 border border-amber-500/30 text-amber-200 text-xs rounded-xl flex items-start space-x-2">
                        <svg class="w-4 h-4 text-amber-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span x-text="cameraErrorMessage"></span>
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
                    cameraErrorMessage: null,
                    html5QrCode: null,

                    openScanner() {
                        this.scannerOpen = true;
                        this.scannedComponent = null;
                        this.scanError = null;
                        this.cameraErrorMessage = null;
                        this.manualCode = '';
                        this.$nextTick(() => {
                            this.startCameraScanner();
                        });
                    },

                    closeScanner() {
                        this.scannerOpen = false;
                        this.cameraErrorMessage = null;
                        this.stopCameraScanner();
                    },

                    startCameraScanner() {
                        const readerElem = document.getElementById('global-scanner-reader');
                        if (!readerElem) return;

                        if (typeof Html5Qrcode === 'undefined') {
                            console.error('Html5Qrcode library not loaded');
                            this.cameraErrorMessage = 'Library pemindai barcode belum dimuat.';
                            return;
                        }

                        if (this.html5QrCode) {
                            this.stopCameraScanner();
                        }

                        try {
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
                                if (!this.scannerOpen) {
                                    this.stopCameraScanner();
                                    return;
                                }
                                this.isScanning = true;
                                this.cameraErrorMessage = null;
                            }).catch(err => {
                                console.warn('Camera scan could not start:', err);
                                this.isScanning = false;
                                if (this.scannerOpen) {
                                    this.cameraErrorMessage = 'Kamera tidak dapat diakses (izin kamera belum diberikan atau diblokir browser). Silakan masukkan kode part atau gunakan barcode scanner gun.';
                                }
                            });
                        } catch (err) {
                            console.warn('Failed to start scanner:', err);
                            this.isScanning = false;
                            this.cameraErrorMessage = 'Kamera tidak dapat diinisialisasi.';
                        }
                    },

                    async stopCameraScanner() {
                        this.isScanning = false;
                        if (this.html5QrCode) {
                            const scanner = this.html5QrCode;
                            this.html5QrCode = null;
                            try {
                                const state = typeof scanner.getState === 'function' ? scanner.getState() : null;
                                // Html5QrcodeScannerState: 2 = SCANNING, 3 = PAUSED
                                if (state === 2 || state === 3) {
                                    await scanner.stop();
                                }
                            } catch (err) {
                                console.warn('Silently handled scanner stop:', err);
                            }
                            try {
                                scanner.clear();
                            } catch (err) {
                                // Ignore clear error
                            }
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

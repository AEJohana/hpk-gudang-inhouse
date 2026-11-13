<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-200/80 shrink-0"
                   title="Kembali ke Dashboard">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Dashboard</span>
                </a>
                <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>
                <div>
                    <h2 class="font-extrabold text-lg text-slate-900 leading-tight">
                        Pengaturan Akun & Profil
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola data identitas, preferensi, dan keamanan akun operasional Anda
                    </p>
                </div>
            </div>

            <!-- Operational Status Pill -->
            <div class="flex items-center space-x-2 self-start sm:self-auto">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                    <span class="w-2 h-2 me-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    Sesi Terhubung
                </span>
            </div>
        </div>
    </x-slot>

    @php
        $words = explode(' ', trim($user->name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= mb_strtoupper(mb_substr($w, 0, 1));
        }
        $initials = $initials ?: 'U';
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- LEFT COLUMN: Executive Profile ID & Quick Navigation -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Profile Identity Card -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 text-center relative overflow-hidden">
                        <!-- Decorative Top Gradient Accent -->
                        <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-[#0a2342] via-teal-500 to-amber-500"></div>

                        <!-- Squircle Avatar with Pulse Indicator -->
                        <div class="relative inline-block mt-1">
                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#0a2342] to-[#1e4976] text-white font-black text-2xl flex items-center justify-center shadow-md border-2 border-white">
                                {{ $initials }}
                            </div>
                            <span class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full shadow-xs" title="Pengguna Aktif (Online)"></span>
                        </div>

                        <!-- User Names & Email -->
                        <h3 class="text-base font-extrabold text-slate-900 mt-3 truncate" title="{{ $user->name }}">
                            {{ $user->name }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5 truncate" title="{{ $user->email }}">
                            {{ $user->email }}
                        </p>

                        <!-- Role & Dept Badges -->
                        <div class="mt-4 flex flex-wrap items-center justify-center gap-1.5">
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200/80">
                                <i class="fa-solid fa-user-shield me-1 text-amber-600"></i>{{ $user->role_badge }}
                            </span>
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-teal-50 text-teal-800 border border-teal-200/80">
                                <i class="fa-solid fa-building me-1 text-teal-600"></i>{{ $user->effective_department_name }}
                            </span>
                            @if($user->work_area)
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                <i class="fa-solid fa-location-dot me-1 text-slate-400"></i>{{ $user->work_area_label }}
                            </span>
                            @endif
                        </div>

                        <!-- Account Metadata Grid -->
                        <div class="mt-5 pt-5 border-t border-slate-100 grid grid-cols-2 gap-2.5 text-left">
                            <div class="bg-slate-50/80 rounded-xl p-2.5 border border-slate-100">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Terdaftar</div>
                                <div class="text-xs font-bold text-slate-700 mt-0.5">
                                    {{ $user->created_at ? $user->created_at->translatedFormat('d M Y') : '-' }}
                                </div>
                            </div>
                            <div class="bg-slate-50/80 rounded-xl p-2.5 border border-slate-100">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</div>
                                <div class="text-xs font-bold text-emerald-700 mt-0.5 flex items-center space-x-1">
                                    <i class="fa-solid fa-circle-check text-[10px]"></i>
                                    <span>Aktif</span>
                                </div>
                            </div>
                        </div>

                        <!-- Organization Footnote -->
                        <div class="mt-4 pt-3 border-t border-slate-100 text-[10px] text-slate-400 font-medium tracking-wide">
                            PT. Hydraxle Perkasa Karoseri
                        </div>
                    </div>

                    <!-- Quick Navigation Shortcuts (Desktop) -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 hidden lg:block">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-2">
                            Pintas Pengaturan
                        </div>
                        <nav class="space-y-1">
                            <a href="#profil-info" 
                               class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-teal-700 transition">
                                <i class="fa-regular fa-user text-teal-600 w-4 text-center"></i>
                                <span>Informasi Profil</span>
                            </a>
                            <a href="#keamanan-sandi" 
                               class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-700 transition">
                                <i class="fa-solid fa-shield-halved text-blue-600 w-4 text-center"></i>
                                <span>Keamanan & Kata Sandi</span>
                            </a>
                            <a href="#zona-bahaya" 
                               class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition">
                                <i class="fa-solid fa-triangle-exclamation text-rose-500 w-4 text-center"></i>
                                <span>Zona Bahaya (Hapus Akun)</span>
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Settings Forms -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Section 1: Profile Information -->
                    <div id="profil-info" class="scroll-mt-6">
                        @include('profile.partials.update-profile-information-form')
                    </div>

                    <!-- Section 2: Update Password -->
                    <div id="keamanan-sandi" class="scroll-mt-6">
                        @include('profile.partials.update-password-form')
                    </div>

                    <!-- Section 3: Danger Zone / Delete Account -->
                    <div id="zona-bahaya" class="scroll-mt-6">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>

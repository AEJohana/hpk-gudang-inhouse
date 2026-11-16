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
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- SECTION PINTAS NAVIGASI CEPAT -->
            <div class="flex items-center justify-between gap-2 overflow-x-auto pb-1 text-xs">
                <div class="flex items-center space-x-2 shrink-0">
                    <a href="#profil-info" 
                       class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full bg-white hover:bg-slate-50 text-slate-700 font-semibold border border-slate-200/80 shadow-2xs transition">
                        <i class="fa-regular fa-user text-blue-600 text-xs"></i>
                        <span>Informasi Profil</span>
                    </a>
                    <a href="#keamanan-sandi" 
                       class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full bg-white hover:bg-slate-50 text-slate-700 font-semibold border border-slate-200/80 shadow-2xs transition">
                        <i class="fa-solid fa-lock text-blue-600 text-xs"></i>
                        <span>Keamanan & Kata Sandi</span>
                    </a>
                    <a href="#zona-bahaya" 
                       class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full bg-white hover:bg-rose-50 text-slate-500 hover:text-rose-700 font-semibold border border-slate-200/80 shadow-2xs transition">
                        <i class="fa-solid fa-shield-halved text-slate-400 text-xs"></i>
                        <span>Zona Bahaya</span>
                    </a>
                </div>
                <div class="text-[11px] text-slate-400 font-medium hidden sm:block">
                    PT. Hydraxle Perkasa Karoseri
                </div>
            </div>

            <!-- CARD 1: EXECUTIVE PROFILE HERO IDENTITY CARD (MATCHING KONSEP) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 relative overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                    
                    <!-- Left: Squircle Avatar + Name + Email + Role Badge -->
                    <div class="flex items-center space-x-4 sm:space-x-5">
                        <!-- Squircle Avatar with Soft Blue Gradient & Active Dot -->
                        <div class="relative shrink-0">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-blue-500 text-white font-bold text-2xl sm:text-3xl flex items-center justify-center border-2 border-white select-none"
                                 style="background: linear-gradient(180deg, #6ba1f7 0%, #3a7bf0 100%); box-shadow: 0 10px 25px -5px rgba(58, 123, 240, 0.35);">
                                {{ $initials }}
                            </div>
                            <span class="absolute bottom-0 right-0 sm:bottom-0.5 sm:right-0.5 w-4 h-4 sm:w-5 sm:h-5 bg-emerald-500 border-2 border-white rounded-full shadow-xs" title="Pengguna Aktif (Online)"></span>
                        </div>

                        <!-- User Titles -->
                        <div class="min-w-0">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight truncate" title="{{ $user->name }}">
                                {{ $user->name }}
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5 truncate font-medium flex items-center space-x-1.5" title="{{ $user->email }}">
                                <span>{{ $user->email }}</span>
                                @if($user->username)
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-blue-600 font-semibold">&#64;{{ $user->username }}</span>
                                @endif
                            </p>
                            
                            <!-- Role & Dept Pills -->
                            <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200/80 shadow-2xs">
                                    <i class="fa-solid fa-user text-[11px] me-1.5 text-amber-600"></i>{{ $user->role_badge }}
                                </span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200/80 shadow-2xs">
                                    <i class="fa-solid fa-building text-[11px] me-1.5 text-teal-600"></i>{{ $user->effective_department_name }}
                                </span>
                                @if($user->work_area)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    <i class="fa-solid fa-location-dot text-[10px] me-1 text-slate-400"></i>{{ $user->work_area_label }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right: Active Status Badge & Registration Date -->
                    <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center border-t sm:border-t-0 pt-4 sm:pt-0 border-slate-100 gap-2 shrink-0">
                        <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                            <span class="w-2 h-2 me-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                            Aktif
                        </span>
                        <div class="text-right sm:mt-2">
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center sm:justify-end gap-1.5">
                                <i class="fa-regular fa-calendar text-slate-400"></i>
                                <span>Terdaftar</span>
                            </div>
                            <div class="text-xs sm:text-sm font-bold text-slate-700 mt-0.5">
                                {{ $user->created_at ? $user->created_at->translatedFormat('d M Y') : '-' }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- CARD 2: INFORMASI PROFIL AKUN -->
            <div id="profil-info" class="scroll-mt-6">
                @include('profile.partials.update-profile-information-form')
            </div>

            <!-- CARD 3: KEAMANAN & KATA SANDI -->
            <div id="keamanan-sandi" class="scroll-mt-6">
                @include('profile.partials.update-password-form')
            </div>

            <!-- CARD 4: ZONA BAHAYA (HAPUS AKUN) -->
            <div id="zona-bahaya" class="scroll-mt-6">
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>

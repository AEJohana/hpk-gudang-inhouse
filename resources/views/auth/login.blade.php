<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="glass-card text-center p-5 sm:p-8" x-data="{
        fillUser(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'password';
        }
    }">
        <!-- Floating Logo Badge from referens -->
        <div class="mb-4">
            <div class="floating-logo-badge">
                <img src="{{ asset('images/logo_hpk.webp') }}" alt="PT Hydraxle Perkasa" class="w-full h-full object-contain">
            </div>
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mt-2">PT. HYDRAXLE PERKASA</div>
            <div class="text-xs font-bold text-teal-600 tracking-wide uppercase">WMS IN-HOUSE KAROSERI</div>
        </div>

        <h4 class="font-extrabold text-xl text-slate-900 mb-1">Welcome Back!</h4>
        <p class="text-xs text-slate-500 mb-6">Silahkan login untuk mengakses sistem gudang</p>

        <form method="POST" action="{{ route('login') }}" class="text-left space-y-4">
            @csrf

            <!-- Username / Email with icon -->
            <div>
                <label for="email" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Email / Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <input id="email" 
                           class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-xs font-medium transition" 
                           type="text" 
                           name="email" 
                           value="{{ old('email', 'admin') }}" 
                           required 
                           autofocus 
                           autocomplete="username" 
                           placeholder="Username atau email" />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <!-- Password with Precision Countdown Pill (Persis Lampiran 3 & 4 recent.design) -->
            <div>
                <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Password</label>
                <div class="relative flex items-center bg-slate-50 focus-within:bg-white border border-slate-200 rounded-xl focus-within:ring-2 focus-within:ring-teal-500 focus-within:border-teal-500 transition-all p-1"
                     x-data="{ 
                        showPassword: false, 
                        totalDuration: 5000,
                        remainingTime: 0,
                        timerInterval: null,
                        togglePassword() {
                            if (this.showPassword) {
                                this.hidePassword();
                            } else {
                                this.showPassword = true;
                                this.remainingTime = this.totalDuration;
                                if (this.timerInterval) clearInterval(this.timerInterval);
                                const stepMs = 40;
                                this.timerInterval = setInterval(() => {
                                    this.remainingTime -= stepMs;
                                    if (this.remainingTime <= 0) {
                                        this.hidePassword();
                                    }
                                }, stepMs);
                            }
                        },
                        hidePassword() {
                            this.showPassword = false;
                            this.remainingTime = 0;
                            if (this.timerInterval) {
                                clearInterval(this.timerInterval);
                                this.timerInterval = null;
                            }
                        }
                     }">
                    <div class="pl-2.5 flex items-center pointer-events-none text-slate-400 shrink-0">
                        <i class="fas fa-lock text-xs"></i>
                    </div>
                    <input id="password" 
                           class="w-full pl-2.5 pr-2 py-1.5 bg-transparent border-0 focus:ring-0 text-slate-800 placeholder-slate-400 text-xs sm:text-sm font-medium tracking-wide focus:outline-none"
                           :type="showPassword ? 'text' : 'password'"
                           type="password"
                           name="password"
                           value="password"
                           required 
                           autocomplete="current-password" 
                           placeholder="••••••••" />
                    <!-- Action Button (Identik Lampiran 3 & Lampiran 4) -->
                    <div class="shrink-0 pr-0.5">
                        <button type="button" 
                                @click="togglePassword()"
                                :title="showPassword ? 'Sembunyikan password' : 'Lihat password'"
                                class="relative w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 cursor-pointer focus:outline-none select-none active:scale-95"
                                :class="showPassword ? 'bg-[#E8FAF0] text-[#16A34A]' : 'bg-[#EEF0FE] hover:bg-[#E0E4FD] text-[#4F46E5]'"
                                tabindex="-1">
                            
                            <!-- Countdown Line Indicator (Hanya saat Show Aktif - Lampiran 4) -->
                            <template x-if="showPassword">
                                <svg class="absolute inset-0 w-full h-full -rotate-90 pointer-events-none" viewBox="0 0 32 32">
                                    <rect x="1.5" y="1.5" width="29" height="29" rx="7" 
                                          fill="none" 
                                          stroke="#22c55e" 
                                          stroke-width="2.5" 
                                          stroke-linecap="round" 
                                          pathLength="100" 
                                          stroke-dasharray="100" 
                                          :style="'stroke-dashoffset: ' + (100 - (remainingTime / totalDuration * 100))"
                                          class="transition-all duration-75 ease-linear" />
                                </svg>
                            </template>

                            <!-- Icon: Mata Lembut (Lampiran 3) vs Mata Tercoret (Lampiran 4) -->
                            <i class="text-xs transition-transform duration-200" 
                               :class="showPassword ? 'fa-solid fa-eye-slash text-[#16A34A]' : 'fa-solid fa-eye text-[#4F46E5]'"></i>
                        </button>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between text-xs pt-1">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500" name="remember">
                    <span class="ms-2 text-xs text-slate-600 font-medium">Ingat saya</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-xs text-teal-600 hover:text-teal-700 font-semibold" href="{{ route('password.request') }}">
                        Lupa password?
                    </a>
                @endif
            </div>

            <button type="submit" class="w-full btn-signin-hpk flex items-center justify-center gap-2 mt-4 cursor-pointer">
                <span>Sign In</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </button>
        </form>

        <!-- 1-Click Demo Login Switcher from our previous polish -->
        <div class="mt-6 pt-4 border-t border-slate-200/80 text-left">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Akses Cepat Uji Coba (1-Click Fill):</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                <button type="button" @click="fillUser('admin@hydraxle.com')" class="p-2.5 rounded-xl bg-slate-50 hover:bg-teal-50 hover:border-teal-200 border border-slate-200 text-slate-700 transition text-left">
                    <span class="font-bold text-blue-900 block text-[11px]"><i class="fas fa-user-shield text-[10px] me-1 text-teal-600"></i> Admin Gudang</span>
                    <span class="text-[10px] text-slate-400 font-mono">admin@hydraxle.com</span>
                </button>
                <button type="button" @click="fillUser('operator@hydraxle.com')" class="p-2.5 rounded-xl bg-slate-50 hover:bg-teal-50 hover:border-teal-200 border border-slate-200 text-slate-700 transition text-left">
                    <span class="font-bold text-blue-900 block text-[11px]"><i class="fas fa-dolly text-[10px] me-1 text-teal-600"></i> Operator Picker</span>
                    <span class="text-[10px] text-slate-400 font-mono">operator@hydraxle.com</span>
                </button>
                <button type="button" @click="fillUser('spv.gudang@hydraxle.com')" class="p-2.5 rounded-xl bg-slate-50 hover:bg-teal-50 hover:border-teal-200 border border-slate-200 text-slate-700 transition text-left">
                    <span class="font-bold text-blue-900 block text-[11px]"><i class="fas fa-user-tie text-[10px] me-1 text-teal-600"></i> Kepala Gudang</span>
                    <span class="text-[10px] text-slate-400 font-mono">spv.gudang@hydraxle.com</span>
                </button>
                <button type="button" @click="fillUser('engineer@hydraxle.com')" class="p-2.5 rounded-xl bg-slate-50 hover:bg-teal-50 hover:border-teal-200 border border-slate-200 text-slate-700 transition text-left">
                    <span class="font-bold text-blue-900 block text-[11px]"><i class="fas fa-drafting-compass text-[10px] me-1 text-teal-600"></i> Engineering (ECR)</span>
                    <span class="text-[10px] text-slate-400 font-mono">engineer@hydraxle.com</span>
                </button>
            </div>
        </div>

        <div class="mt-5 pt-3 border-t border-slate-100">
            <small class="text-slate-400 font-bold text-[10px] tracking-wider uppercase">PT. HYDRAXLE PERKASA &copy; 2026</small>
        </div>
    </div>
</x-guest-layout>

<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="glass-card text-center p-7 sm:p-9" x-data="{
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
                           type="email" 
                           name="email" 
                           value="{{ old('email', 'admin@hydraxle.com') }}" 
                           required 
                           autofocus 
                           autocomplete="username" 
                           placeholder="nama@hydraxle.com" />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <!-- Password with icon -->
            <div>
                <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-lock text-sm"></i>
                    </div>
                    <input id="password" 
                           class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-xs font-medium transition"
                           type="password"
                           name="password"
                           value="password"
                           required 
                           autocomplete="current-password" 
                           placeholder="••••••••" />
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
            <div class="grid grid-cols-2 gap-2 text-xs">
                <button type="button" @click="fillUser('admin@hydraxle.com')" class="p-2 rounded-xl bg-slate-50 hover:bg-teal-50 hover:border-teal-200 border border-slate-200 text-slate-700 transition text-left">
                    <span class="font-bold text-blue-900 block text-[11px]"><i class="fas fa-user-shield text-[10px] me-1 text-teal-600"></i> Admin Gudang</span>
                    <span class="text-[10px] text-slate-400">admin@hydraxle.com</span>
                </button>
                <button type="button" @click="fillUser('operator@hydraxle.com')" class="p-2 rounded-xl bg-slate-50 hover:bg-teal-50 hover:border-teal-200 border border-slate-200 text-slate-700 transition text-left">
                    <span class="font-bold text-blue-900 block text-[11px]"><i class="fas fa-dolly text-[10px] me-1 text-teal-600"></i> Operator Picker</span>
                    <span class="text-[10px] text-slate-400">operator@hydraxle.com</span>
                </button>
                <button type="button" @click="fillUser('spv.gudang@hydraxle.com')" class="p-2 rounded-xl bg-slate-50 hover:bg-teal-50 hover:border-teal-200 border border-slate-200 text-slate-700 transition text-left">
                    <span class="font-bold text-blue-900 block text-[11px]"><i class="fas fa-user-tie text-[10px] me-1 text-teal-600"></i> Kepala Gudang</span>
                    <span class="text-[10px] text-slate-400">spv.gudang@hydraxle.com</span>
                </button>
                <button type="button" @click="fillUser('engineer@hydraxle.com')" class="p-2 rounded-xl bg-slate-50 hover:bg-teal-50 hover:border-teal-200 border border-slate-200 text-slate-700 transition text-left">
                    <span class="font-bold text-blue-900 block text-[11px]"><i class="fas fa-drafting-compass text-[10px] me-1 text-teal-600"></i> Engineering (ECR)</span>
                    <span class="text-[10px] text-slate-400">engineer@hydraxle.com</span>
                </button>
            </div>
        </div>

        <div class="mt-5 pt-3 border-t border-slate-100">
            <small class="text-slate-400 font-bold text-[10px] tracking-wider uppercase">PT. HYDRAXLE PERKASA &copy; 2026</small>
        </div>
    </div>
</x-guest-layout>

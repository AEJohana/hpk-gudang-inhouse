<section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-7" 
         x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
    <!-- Header with Icon Badge -->
    <div class="flex items-center space-x-3 pb-5 border-b border-slate-100">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0a2342] flex items-center justify-center text-sm shrink-0 shadow-2xs">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div>
            <h2 class="text-base font-extrabold text-slate-900 leading-tight">
                Keamanan & Kata Sandi
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Pastikan akun Anda menggunakan kata sandi yang kuat dan aman untuk menjaga data operasional.
            </p>
        </div>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <!-- Current Password Field with Eye Toggle -->
        <div>
            <label for="update_password_current_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                Kata Sandi Saat Ini <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-lock text-sm"></i>
                </div>
                <input id="update_password_current_password" 
                       name="current_password" 
                       :type="showCurrent ? 'text' : 'password'" 
                       class="w-full pl-10 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 text-slate-800 placeholder-slate-400 transition" 
                       placeholder="Masukkan kata sandi saat ini"
                       autocomplete="current-password" />
                <button type="button" 
                        @click="showCurrent = !showCurrent" 
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                        title="Tampilkan / Sembunyikan sandi">
                    <i :class="showCurrent ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5 text-xs" />
        </div>

        <!-- New Password Field with Eye Toggle -->
        <div>
            <label for="update_password_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                Kata Sandi Baru <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-key text-sm"></i>
                </div>
                <input id="update_password_password" 
                       name="password" 
                       :type="showNew ? 'text' : 'password'" 
                       class="w-full pl-10 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 text-slate-800 placeholder-slate-400 transition" 
                       placeholder="Minimal 8 karakter"
                       autocomplete="new-password" />
                <button type="button" 
                        @click="showNew = !showNew" 
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                        title="Tampilkan / Sembunyikan sandi">
                    <i :class="showNew ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5 text-xs" />
        </div>

        <!-- Confirm Password Field with Eye Toggle -->
        <div>
            <label for="update_password_password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">
                Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-check-double text-sm"></i>
                </div>
                <input id="update_password_password_confirmation" 
                       name="password_confirmation" 
                       :type="showConfirm ? 'text' : 'password'" 
                       class="w-full pl-10 pr-10 py-2.5 text-sm bg-white border border-slate-300 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 text-slate-800 placeholder-slate-400 transition" 
                       placeholder="Ulangi kata sandi baru"
                       autocomplete="new-password" />
                <button type="button" 
                        @click="showConfirm = !showConfirm" 
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                        title="Tampilkan / Sembunyikan sandi">
                    <i :class="showConfirm ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5 text-xs" />
        </div>

        <!-- Security Best-Practice Tip -->
        <div class="text-[11px] text-slate-500 flex items-center space-x-2 bg-slate-50 p-3 rounded-xl border border-slate-200/70">
            <i class="fa-solid fa-circle-info text-teal-600 text-xs shrink-0"></i>
            <span>Sandi minimal 8 karakter. Disarankan menggunakan kombinasi huruf besar, angka, dan karakter khusus.</span>
        </div>

        <!-- Action Button & Status -->
        <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
            <button type="submit" 
                    class="px-5 py-2.5 rounded-xl bg-[#0a2342] hover:bg-[#13335a] active:scale-98 text-white font-bold text-xs shadow-xs transition flex items-center space-x-2 cursor-pointer">
                <i class="fa-solid fa-shield-halved text-xs"></i>
                <span>Perbarui Kata Sandi</span>
            </button>

            @if (session('status') === 'password-updated')
                <div x-data="{ show: true }"
                     x-show="show"
                     x-transition
                     x-init="setTimeout(() => show = false, 3000)"
                     class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-semibold text-xs flex items-center space-x-1.5 shadow-2xs">
                    <i class="fa-solid fa-circle-check text-xs"></i>
                    <span>Kata sandi berhasil diperbarui.</span>
                </div>
            @endif
        </div>
    </form>
</section>

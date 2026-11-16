<div x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <!-- Card Container -->
        <section class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 space-y-5">
            <!-- Header with Squircle Icon Badge -->
            <div class="flex items-center space-x-3.5 pb-2">
                <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-base shrink-0 shadow-2xs">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 leading-tight">
                        Keamanan & Kata Sandi
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium">
                        Pastikan akun Anda tetap aman dengan kata sandi yang kuat.
                    </p>
                </div>
            </div>

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
                           class="w-full pl-10 pr-10 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 placeholder-slate-400 shadow-2xs transition" 
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
                        <i class="fa-solid fa-lock text-sm"></i>
                    </div>
                    <input id="update_password_password" 
                           name="password" 
                           :type="showNew ? 'text' : 'password'" 
                           class="w-full pl-10 pr-10 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 placeholder-slate-400 shadow-2xs transition" 
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
                        <i class="fa-solid fa-lock text-sm"></i>
                    </div>
                    <input id="update_password_password_confirmation" 
                           name="password_confirmation" 
                           :type="showConfirm ? 'text' : 'password'" 
                           class="w-full pl-10 pr-10 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 placeholder-slate-400 shadow-2xs transition" 
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
            <div class="text-[11px] text-slate-500 flex items-center space-x-2 bg-slate-50 p-3 rounded-2xl border border-slate-200/70">
                <i class="fa-solid fa-circle-info text-blue-600 text-xs shrink-0"></i>
                <span>Sandi minimal 8 karakter. Disarankan menggunakan kombinasi huruf besar, angka, dan karakter khusus.</span>
            </div>
        </section>

        <!-- Action Button & Status (Outside Card, Matching Konsep Mockup) -->
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" 
                    class="px-8 py-3.5 rounded-full bg-blue-600 hover:bg-blue-700 active:scale-98 text-white font-bold text-sm transition inline-flex items-center space-x-3 cursor-pointer"
                    style="background-color: #1a56db; box-shadow: 0 10px 25px -5px rgba(26, 86, 219, 0.4);">
                <i class="fa-solid fa-arrows-rotate text-sm shrink-0"></i>
                <span>Perbarui Kata Sandi</span>
                <i class="fa-solid fa-chevron-right text-xs shrink-0 ml-1"></i>
            </button>

            @if (session('status') === 'password-updated')
                <div x-data="{ show: true }"
                     x-show="show"
                     x-transition
                     x-init="setTimeout(() => show = false, 3000)"
                     class="px-4 py-2 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 font-semibold text-xs flex items-center space-x-1.5 shadow-2xs">
                    <i class="fa-solid fa-circle-check text-xs"></i>
                    <span>Kata sandi berhasil diperbarui.</span>
                </div>
            @endif
        </div>
    </form>
</div>

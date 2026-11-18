<section class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7">
    <!-- Header with Squircle Icon Badge -->
    <div class="flex items-center space-x-3.5 pb-5 border-b border-slate-100">
        <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-base shrink-0 shadow-2xs">
            <i class="fa-solid fa-user"></i>
        </div>
        <div>
            <h2 class="text-base sm:text-lg font-black text-slate-900 leading-tight">
                Informasi Profil Akun
            </h2>
            <p class="text-xs text-slate-500 mt-0.5 font-medium">
                Perbarui nama lengkap dan username operasional Anda.
            </p>
        </div>
    </div>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <!-- 2-Column Responsive Form Grid (Nama Lengkap, Username Akun) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Full Name Field with Icon -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-id-badge text-sm"></i>
                    </div>
                    <input id="name" 
                           name="name" 
                           type="text" 
                           class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 placeholder-slate-400 shadow-2xs transition" 
                           value="{{ old('name', $user->name) }}" 
                           required autofocus autocomplete="name" />
                </div>
                <x-input-error class="mt-1.5 text-xs" :messages="$errors->get('name')" />
            </div>

            <!-- Username Field with Icon -->
            <div>
                <label for="username" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Username Akun <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold">
                        <i class="fa-solid fa-at text-xs"></i>
                    </div>
                    <input id="username" 
                           name="username" 
                           type="text" 
                           class="w-full pl-9 pr-3.5 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 placeholder-slate-400 shadow-2xs transition lowercase" 
                           value="{{ old('username', $user->username) }}" 
                           placeholder="username"
                           required autocomplete="username" />
                </div>
                <p class="text-[10px] text-slate-400 mt-1 font-medium">ID untuk login ke sistem.</p>
                <x-input-error class="mt-1.5 text-xs" :messages="$errors->get('username')" />
            </div>
        </div>

        <!-- System Access Info Row (Matching Konsep) -->
        <div class="p-4 rounded-2xl bg-blue-50/40 border border-blue-100 flex flex-wrap items-center gap-2.5">
            <div class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] shrink-0 shadow-2xs">
                <i class="fa-solid fa-check"></i>
            </div>
            <span class="text-xs font-bold text-slate-800">Hak Akses Sistem :</span>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100/80 text-blue-800 border border-blue-200 shadow-2xs">
                {{ $user->role_badge }}
            </span>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-teal-100/80 text-teal-800 border border-teal-200 shadow-2xs">
                <i class="fa-solid fa-building me-1 text-teal-600"></i>{{ $user->effective_department_name }}
            </span>
            @if($user->work_area)
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                <i class="fa-solid fa-location-dot me-1 text-slate-400"></i>{{ $user->work_area_label }}
            </span>
            @endif
        </div>

        <!-- Action Button & Status -->
        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="submit" 
                    class="px-7 py-3 rounded-full bg-blue-600 hover:bg-blue-700 active:scale-98 text-white font-bold text-xs transition inline-flex items-center space-x-2.5 cursor-pointer"
                    style="background-color: #1a56db; box-shadow: 0 10px 20px -5px rgba(26, 86, 219, 0.35);">
                <i class="fa-solid fa-floppy-disk text-xs shrink-0"></i>
                <span>Simpan Perubahan</span>
                <i class="fa-solid fa-chevron-right text-[10px] shrink-0 ml-1"></i>
            </button>

            @if (session('status') === 'profile-updated')
                <div x-data="{ show: true }"
                     x-show="show"
                     x-transition
                     x-init="setTimeout(() => show = false, 3000)"
                     class="px-4 py-2 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 font-semibold text-xs flex items-center space-x-1.5 shadow-2xs">
                    <i class="fa-solid fa-circle-check text-xs"></i>
                    <span>Profil berhasil diperbarui.</span>
                </div>
            @endif
        </div>
    </form>
</section>

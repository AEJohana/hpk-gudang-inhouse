<section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-7">
    <!-- Header with Icon Badge -->
    <div class="flex items-center space-x-3 pb-5 border-b border-slate-100">
        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-sm shrink-0 shadow-2xs">
            <i class="fa-regular fa-user"></i>
        </div>
        <div>
            <h2 class="text-base font-extrabold text-slate-900 leading-tight">
                Informasi Profil Akun
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Perbarui nama lengkap dan alamat email operasional akun Anda.
            </p>
        </div>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

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
                       class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 text-slate-800 placeholder-slate-400 transition" 
                       value="{{ old('name', $user->name) }}" 
                       required autofocus autocomplete="name" />
            </div>
            <x-input-error class="mt-1.5 text-xs" :messages="$errors->get('name')" />
        </div>

        <!-- Email Field with Icon -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">
                Alamat Email <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-regular fa-envelope text-sm"></i>
                </div>
                <input id="email" 
                       name="email" 
                       type="email" 
                       class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 text-slate-800 placeholder-slate-400 transition" 
                       value="{{ old('email', $user->email) }}" 
                       required autocomplete="username" />
            </div>
            <x-input-error class="mt-1.5 text-xs" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs">
                    <p class="font-medium">
                        Alamat email Anda belum diverifikasi.
                        <button form="send-verification" class="underline text-amber-700 hover:text-amber-900 font-bold ml-1 focus:outline-none">
                            Klik di sini untuk mengirim ulang tautan verifikasi.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-bold text-emerald-700 flex items-center space-x-1">
                            <i class="fa-solid fa-circle-check text-xs"></i>
                            <span>Tautan verifikasi baru telah dikirimkan ke alamat email Anda.</span>
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Readonly WMS System Access Info Pill -->
        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="text-xs text-slate-600">
                <span class="font-bold text-slate-800 block sm:inline">Hak Akses Sistem:</span>
                <span class="text-slate-500 text-[11px] sm:ml-1">Peran & Departemen dikonfigurasi oleh Administrator.</span>
            </div>
            <div class="flex items-center space-x-1.5 shrink-0">
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100/80 text-amber-800 border border-amber-200/80">
                    <i class="fa-solid fa-user-shield me-1 text-amber-600"></i>{{ $user->role_badge }}
                </span>
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-teal-100/80 text-teal-800 border border-teal-200/80">
                    <i class="fa-solid fa-building me-1 text-teal-600"></i>{{ $user->effective_department_name }}
                </span>
            </div>
        </div>

        <!-- Action Button & Status -->
        <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
            <button type="submit" 
                    class="px-5 py-2.5 rounded-xl bg-[#0a2342] hover:bg-[#13335a] active:scale-98 text-white font-bold text-xs shadow-xs transition flex items-center space-x-2 cursor-pointer">
                <i class="fa-solid fa-floppy-disk text-xs"></i>
                <span>Simpan Perubahan</span>
            </button>

            @if (session('status') === 'profile-updated')
                <div x-data="{ show: true }"
                     x-show="show"
                     x-transition
                     x-init="setTimeout(() => show = false, 3000)"
                     class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-semibold text-xs flex items-center space-x-1.5 shadow-2xs">
                    <i class="fa-solid fa-circle-check text-xs"></i>
                    <span>Profil berhasil diperbarui.</span>
                </div>
            @endif
        </div>
    </form>
</section>

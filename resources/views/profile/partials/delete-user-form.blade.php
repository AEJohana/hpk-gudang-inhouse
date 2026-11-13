<section class="bg-rose-50/25 border border-rose-200/70 rounded-2xl p-6 sm:p-7">
    <!-- Header with Danger Icon Badge -->
    <div class="flex items-center space-x-3 pb-5 border-b border-rose-100">
        <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-sm shrink-0 shadow-2xs">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <h2 class="text-base font-extrabold text-slate-900 leading-tight">
                Zona Bahaya: Hapus Akun
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Penghapusan akun bersifat permanen. Seluruh kredensial autentikasi sistem Anda akan dinonaktifkan.
            </p>
        </div>
    </div>

    <!-- Trigger Area -->
    <div class="pt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="text-xs text-slate-600 max-w-lg leading-relaxed">
            Setelah akun Anda dihapus, seluruh sumber daya dan sesi login akan ditutup secara permanen. Riwayat transaksi dan log audit tetap tersimpan untuk integritas data kepatuhan pergudangan.
        </div>
        <button type="button"
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                class="px-4 py-2.5 rounded-xl bg-white hover:bg-rose-600 hover:text-white text-rose-700 border border-rose-300 font-bold text-xs shadow-2xs transition flex items-center space-x-2 cursor-pointer group shrink-0 self-start sm:self-auto">
            <i class="fa-regular fa-trash-can text-xs text-rose-600 group-hover:text-white transition"></i>
            <span>Hapus Akun Saya</span>
        </button>
    </div>

    <!-- Confirmation Modal -->
    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-7">
            @csrf
            @method('delete')

            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-base shrink-0 shadow-2xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 leading-tight">
                        Konfirmasi Hapus Akun Permanen
                    </h2>
                    <p class="text-xs text-rose-600 font-medium mt-0.5">
                        Tindakan ini permanen dan tidak dapat dibatalkan.
                    </p>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed bg-rose-50/60 p-3.5 rounded-xl border border-rose-200/60">
                Apakah Anda benar-benar yakin ingin menghapus akun Anda? Seluruh hak akses Anda ke aplikasi HPK WMS akan dicabut seketika. Silakan masukkan kata sandi Anda untuk memverifikasi tindakan ini.
            </p>

            <div class="mt-4">
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Kata Sandi Konfirmasi <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </div>
                    <input id="password"
                           name="password"
                           type="password"
                           class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-white border border-slate-300 rounded-xl focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 text-slate-800 placeholder-slate-400 transition"
                           placeholder="Masukkan kata sandi untuk konfirmasi" />
                </div>
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1.5 text-xs" />
            </div>

            <div class="mt-6 flex justify-end items-center space-x-2.5 pt-4 border-t border-slate-100">
                <button type="button"
                        x-on:click="$dispatch('close')"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer">
                    Batal
                </button>

                <button type="submit"
                        class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition flex items-center space-x-2 cursor-pointer">
                    <i class="fa-regular fa-trash-can text-xs"></i>
                    <span>Ya, Hapus Akun</span>
                </button>
            </div>
        </form>
    </x-modal>
</section>

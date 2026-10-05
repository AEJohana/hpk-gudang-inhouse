<x-admin-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 220px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-10 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-2">
                    <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-amber-300 hover:text-amber-200 transition flex items-center">
                        <i class="fa-solid fa-arrow-left me-1.5"></i> Kembali ke Daftar User
                    </a>
                    <span class="text-white/40">&bull;</span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">Perbarui Profil</span>
                </div>

                <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                    Edit Akun: {{ $user->name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 mt-1">
                    Sesuaikan detail akun, mutasi departemen, ubah hak akses role, atau reset password.
                </p>
            </div>
        </div>

        <!-- 2. FORM CARD -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Section 1: Profil & Identitas -->
                <div class="mb-8">
                    <div class="flex items-center space-x-2.5 pb-3 border-b border-slate-100 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Informasi Pribadi & Kontak</h3>
                            <p class="text-[11px] text-slate-500">Data identitas pengguna dan informasi kontak</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Nama Lengkap -->
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Lengkap <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}" 
                                   required 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('name') border-rose-400 @enderror">
                            @error('name')
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Alamat Email Perusahaan <span class="text-rose-500">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   required 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('email') border-rose-400 @enderror">
                            @error('email')
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Username (Opsional)
                            </label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   value="{{ old('username', $user->username) }}" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('username') border-rose-400 @enderror">
                            @error('username')
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- No HP -->
                        <div>
                            <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nomor Handphone / WhatsApp
                            </label>
                            <input type="text" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $user->phone) }}" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('phone') border-rose-400 @enderror">
                            @error('phone')
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Departemen -->
                        <div class="md:col-span-2">
                            <label for="department" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Departemen / Area Kerja
                            </label>
                            <input type="text" 
                                   id="department" 
                                   name="department" 
                                   value="{{ old('department', $user->department) }}" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Role & Hak Akses -->
                <div class="mb-8">
                    <div class="flex items-center space-x-2.5 pb-3 border-b border-slate-100 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Peran & Otorisasi Sistem</h3>
                            <p class="text-[11px] text-slate-500">Tentukan kewenangan akses modul</p>
                        </div>
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Pilih Role Pengguna <span class="text-rose-500">*</span>
                        </label>
                        @php $currentRole = old('role', $user->roles->first()->name ?? $user->role); @endphp
                        <select id="role" 
                                name="role" 
                                required 
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('role') border-rose-400 @enderror">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $currentRole == $role->name ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Section 3: Reset Kata Sandi (Opsional) -->
                <div class="mb-8 p-5 bg-slate-50 border border-slate-200/80 rounded-xl">
                    <div class="flex items-center space-x-2 mb-2">
                        <i class="fa-solid fa-lock text-slate-700 text-xs"></i>
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Reset Password Pengguna</h4>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-4">Kosongkan kedua kolom di bawah jika pengguna tetap menggunakan password lama.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Password Baru -->
                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Password Baru
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Minimal 8 karakter baru" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('password') border-rose-400 @enderror">
                            @error('password')
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Konfirmasi Password Baru -->
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Konfirmasi Password Baru
                            </label>
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Ketik ulang password baru" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                        </div>
                    </div>
                </div>

                <!-- Section 4: Status Akun -->
                <div class="mb-8 p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1" 
                               {{ old('is_active', $user->is_active) ? 'checked' : '' }} 
                               {{ $user->id === auth()->id() ? 'disabled' : '' }}
                               class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 border-slate-300">
                        <div class="ms-3">
                            <span class="text-xs font-bold text-slate-900 block">Akun Aktif (Dapat Login)</span>
                            <span class="text-[11px] text-slate-500 block">Jika dinonaktifkan, user tidak akan dapat login ke dalam sistem</span>
                            @if($user->id === auth()->id())
                                <input type="hidden" name="is_active" value="1">
                                <span class="text-[10px] text-amber-600 font-semibold italic mt-0.5 block">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Anda tidak dapat menonaktifkan akun sendiri
                                </span>
                            @endif
                        </div>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('admin.users.index') }}" 
                       class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-6 py-2.5 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                        <i class="fa-solid fa-check me-2"></i>
                        Perbarui Data Pengguna
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-admin-layout>

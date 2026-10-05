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
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">Form Pengguna</span>
                </div>

                <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                    Tambah Pengguna Sistem Baru
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 mt-1">
                    Buat akun staf atau pimpinan untuk mengakses modul WMS sesuai peran operasional.
                </p>
            </div>
        </div>

        <!-- 2. FORM CARD -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <!-- Section 1: Profil & Identitas -->
                <div class="mb-8">
                    <div class="flex items-center space-x-2.5 pb-3 border-b border-slate-100 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Informasi Pribadi & Kontak</h3>
                            <p class="text-[11px] text-slate-500">Nama lengkap dan kontak staf yang bertugas</p>
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
                                   value="{{ old('name') }}" 
                                   placeholder="Contoh: Budi Santoso" 
                                   required 
                                   autofocus
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
                                   value="{{ old('email') }}" 
                                   placeholder="nama@hydraxle.com" 
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
                                   value="{{ old('username') }}" 
                                   placeholder="Contoh: budi_gudang" 
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
                                   value="{{ old('phone') }}" 
                                   placeholder="0812-xxxx-xxxx" 
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
                                   value="{{ old('department') }}" 
                                   placeholder="Contoh: Gudang & Logistik / Lini Perakitan Dump" 
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
                            <p class="text-[11px] text-slate-500">Tentukan tingkat kewenangan modul yang dapat diakses pengguna</p>
                        </div>
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Pilih Role Pengguna <span class="text-rose-500">*</span>
                        </label>
                        <select id="role" 
                                name="role" 
                                required 
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('role') border-rose-400 @enderror">
                            <option value="">-- Pilih Salah Satu Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Section 3: Kata Sandi -->
                <div class="mb-8">
                    <div class="flex items-center space-x-2.5 pb-3 border-b border-slate-100 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Keamanan & Password</h3>
                            <p class="text-[11px] text-slate-500">Password sementara untuk login pertama kali</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Password Sementara <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   placeholder="Minimal 8 karakter" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('password') border-rose-400 @enderror">
                            @error('password')
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Konfirmasi Password -->
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Ulangi Password <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required 
                                   placeholder="Ketik ulang password" 
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
                               {{ old('is_active', true) ? 'checked' : '' }} 
                               class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 border-slate-300">
                        <div class="ms-3">
                            <span class="text-xs font-bold text-slate-900 block">Akun Aktif Langsung</span>
                            <span class="text-[11px] text-slate-500 block">Pengguna dapat langsung masuk ke sistem setelah didaftarkan</span>
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
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-admin-layout>

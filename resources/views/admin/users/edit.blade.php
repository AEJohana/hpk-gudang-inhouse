<x-admin-layout>
    <div class="wave-header pb-12 pt-8 relative overflow-hidden" style="min-height: 180px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center space-x-3 mb-2">
                <span class="bg-hpk-orange/20 text-amber-300 border border-amber-300/50 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-widest uppercase">
                    Admin
                </span>
                <span class="text-teal-300 text-xs font-bold tracking-widest uppercase">Edit User</span>
            </div>
            <h1 class="text-3xl font-black text-white tracking-wide mb-1">
                Edit Profil: {{ $user->name }}
            </h1>
        </div>
        <div class="wave-shape-2">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,60 C320,120 420,0 720,60 C1020,120 1120,0 1440,60 L1440,120 L0,120 Z" fill="#f0f4f8"></path>
            </svg>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20 pb-12">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-8">
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <x-input-label for="name" value="Nama Lengkap *" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email *" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="username" value="Username (Opsional)" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $user->username)" />
                        <x-input-error class="mt-2" :messages="$errors->get('username')" />
                    </div>
                    
                    <div>
                        <x-input-label for="phone" value="No. Handphone (Opsional)" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>

                    <div>
                        <x-input-label for="department" value="Departemen (Opsional)" />
                        <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $user->department)" />
                        <x-input-error class="mt-2" :messages="$errors->get('department')" />
                    </div>
                    
                    <div>
                        <x-input-label for="role" value="Role Akses Sistem *" />
                        <select id="role" name="role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">Pilih Role...</option>
                            @php $currentRole = old('role', $user->roles->first()->name ?? $user->role); @endphp
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $currentRole == $role->name ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('role')" />
                    </div>
                </div>

                <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                    <h3 class="text-sm font-bold text-slate-800 mb-2">Reset Password</h3>
                    <p class="text-xs text-slate-500 mb-4">Kosongkan kolom ini jika tidak ingin mengubah password.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="password" value="Password Baru" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="is_active" class="inline-flex items-center">
                        <input id="is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        <span class="ms-2 text-sm text-gray-600">Akun Aktif (Dapat Login)</span>
                        @if($user->id === auth()->id())
                            <input type="hidden" name="is_active" value="1">
                            <span class="ms-2 text-xs text-rose-500 italic">(Anda tidak dapat menonaktifkan akun sendiri)</span>
                        @endif
                    </label>
                </div>

                <div class="flex items-center justify-end border-t border-slate-100 pt-6">
                    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-sm rounded-xl mr-3 transition">Batal</a>
                    <button type="submit" class="px-6 py-2 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-sm rounded-xl shadow-md transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>

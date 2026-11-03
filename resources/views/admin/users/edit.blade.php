<x-admin-layout>
    @php
        $initialRole = old('role', $user->roles->first()->name ?? 'operator');
        if ($initialRole === 'admin') $initialRole = 'admin_gudang';
        $isGlobal = in_array($initialRole, ['supervisor', 'admin_gudang', 'admin']);
        $initialWorkArea = $isGlobal ? 'semua' : old('work_area', $user->work_area ?? 'gudang');
        $initialDeptId = old('department_id', $user->department_id);
        $initialPermissions = old('permissions', $userPermissions);
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8" 
         x-data="{
            role: '{{ $initialRole }}',
            workArea: '{{ $initialWorkArea }}',
            departmentId: '{{ $initialDeptId }}',
            gudangDeptId: '{{ $gudangDept->id }}',
            gudangDeptName: '{{ $gudangDept->name }}',
            selectedPermissions: {{ json_encode($initialPermissions) }},
            presets: {{ json_encode($presets) }},

            isGlobalRole() {
                return this.role === 'supervisor' || this.role === 'admin_gudang' || this.role === 'admin';
            },

            onRoleChange() {
                if (this.isGlobalRole()) {
                    this.workArea = 'semua';
                    this.departmentId = this.gudangDeptId;
                } else {
                    if (this.workArea === 'semua') {
                        this.workArea = 'gudang';
                    }
                    if (this.workArea === 'gudang' || this.workArea === 'material_handling') {
                        this.departmentId = this.gudangDeptId;
                    }
                }
                this.applyPreset();
            },

            onWorkAreaChange() {
                if (this.workArea === 'gudang' || this.workArea === 'material_handling') {
                    this.departmentId = this.gudangDeptId;
                } else if (this.workArea === 'mesin_center' && this.departmentId == this.gudangDeptId) {
                    this.departmentId = '';
                }
                this.applyPreset();
            },

            applyPreset() {
                if (this.isGlobalRole()) {
                    this.selectedPermissions = [...this.presets.all];
                } else if (this.role === 'staff') {
                    const areaPerms = this.presets.staff[this.workArea] || [];
                    this.selectedPermissions = [...areaPerms];
                } else if (this.role === 'operator') {
                    const areaPerms = this.presets.operator[this.workArea] || [];
                    this.selectedPermissions = [...areaPerms];
                }
            },

            selectAll() {
                this.selectedPermissions = [...this.presets.all];
            },

            deselectAll() {
                this.selectedPermissions = [];
            },

            toggleModule(modulePerms) {
                const hasAll = modulePerms.every(p => this.selectedPermissions.includes(p));
                if (hasAll) {
                    this.selectedPermissions = this.selectedPermissions.filter(p => !modulePerms.includes(p));
                } else {
                    const combined = new Set([...this.selectedPermissions, ...modulePerms]);
                    this.selectedPermissions = Array.from(combined);
                }
            },

            isModuleChecked(modulePerms) {
                return modulePerms.length > 0 && modulePerms.every(p => this.selectedPermissions.includes(p));
            }
         }">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 230px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-10 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-2">
                    <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-amber-300 hover:text-amber-200 transition flex items-center">
                        <i class="fa-solid fa-arrow-left me-1.5"></i> Kembali ke Manajemen Pengguna
                    </a>
                    <span class="text-white/40">&bull;</span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">Perbarui Akun & Izin</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                            Edit Pengguna: {{ $user->name }}
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1">
                            Username: <span class="font-mono text-amber-300 font-bold me-2">{{ '@' . $user->username }}</span> | 
                            Area: <span class="font-bold text-white uppercase">{{ $user->work_area_label }}</span>
                        </p>
                    </div>

                    <div class="inline-flex items-center space-x-2 px-3.5 py-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-white text-xs">
                        <i class="fa-solid fa-shield-halved text-amber-400"></i>
                        <span>Total Izin Aktif:</span>
                        <span class="font-mono font-bold text-amber-300" x-text="selectedPermissions.length"></span>
                        <span class="text-white/60">/ {{ count($presets['all']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-xs text-rose-700 space-y-1">
                <div class="font-bold flex items-center mb-1">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Terdapat kesalahan pengisian formulir:
                </div>
                <ul class="list-disc list-inside space-y-0.5 ms-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- 2. MAIN FORM -->
        <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- SECTION 1: IDENTITAS & KREDENSIAL AKUN -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-7">
                <div class="flex items-center space-x-3 pb-3 border-b border-slate-100 mb-6">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">1. Identitas Akun & Kredensial Masuk</h3>
                        <p class="text-[11px] text-slate-500">Nama tampilan, ID login unik, kontak, dan kata sandi</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Lengkap (Nama Tampilan) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $user->name) }}" 
                               placeholder="Contoh: Budi Santoso" 
                               required 
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('name') border-rose-400 @enderror">
                        @error('name')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Username (ID Masuk) -->
                    <div>
                        <label for="username" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Username (ID Masuk Akun) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs">
                                <i class="fa-solid fa-at"></i>
                            </span>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   value="{{ old('username', $user->username) }}" 
                                   placeholder="Contoh: budi_gudang" 
                                   required
                                   class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('username') border-rose-400 @enderror">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Digunakan sebagai identitas login utama.</p>
                        @error('username')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email (Opsional) -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Alamat Email Perusahaan <span class="text-slate-400 font-normal lowercase">(opsional / tidak wajib)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   placeholder="budi@hydraxle.com (opsional)" 
                                   class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('email') border-rose-400 @enderror">
                        </div>
                        @error('email')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- No HP (Opsional) -->
                    <div>
                        <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nomor Handphone / WhatsApp <span class="text-slate-400 font-normal lowercase">(opsional / tidak wajib)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <input type="text" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $user->phone) }}" 
                                   placeholder="0812-xxxx-xxxx" 
                                   class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('phone') border-rose-400 @enderror">
                        </div>
                        @error('phone')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reset Password (Opsional saat Edit) -->
                    <div class="md:col-span-2 p-4 bg-slate-50 border border-slate-200/80 rounded-xl space-y-3">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-key text-slate-700 text-xs"></i>
                            <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Ganti Kata Sandi (Kosongkan bila tidak diubah)</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Password Baru</label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Minimal 8 karakter baru" 
                                       class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('password') border-rose-400 @enderror">
                                @error('password')
                                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Ulangi Password Baru</label>
                                <input type="password" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       placeholder="Ketik ulang password baru" 
                                       class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                            </div>
                        </div>
                    </div>

                    <!-- Tanda Tangan Digital -->
                    <div class="md:col-span-2">
                        <label for="signature_file" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Berkas Tanda Tangan Digital (PNG/JPG transparan) <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                        </label>
                        @if($user->signature_path)
                            <div class="mb-3 p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center space-x-4">
                                <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Tanda Tangan" class="h-12 w-auto object-contain border border-slate-200 bg-white p-1 rounded-lg">
                                <span class="text-xs text-slate-600 font-medium">Tanda tangan digital saat ini aktif. Unggah berkas baru untuk mengganti.</span>
                            </div>
                        @endif
                        <input type="file" 
                               id="signature_file" 
                               name="signature_file" 
                               accept="image/*"
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- SECTION 2: ROLE DAN BAGIAN OPERASIONAL -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-7">
                <div class="flex items-center space-x-3 pb-3 border-b border-slate-100 mb-6">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">2. Pemilihan Role & Bagian Operasional</h3>
                        <p class="text-[11px] text-slate-500">Tentukan jabatan dan area kerja fisik pengguna</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    <!-- PILIH ROLE (KIRI) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Pilih Tingkatan Role <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach($roles as $key => $label)
                                @php
                                    $roleIcon = match($key) {
                                        'supervisor' => 'fa-user-tie text-purple-600',
                                        'admin_gudang' => 'fa-user-shield text-amber-600',
                                        'staff' => 'fa-clipboard-user text-blue-600',
                                        'operator' => 'fa-helmet-safety text-emerald-600',
                                        default => 'fa-user text-slate-600',
                                    };
                                @endphp
                                <label class="flex items-center p-3 rounded-xl border cursor-pointer transition select-none"
                                       :class="role === '{{ $key }}' ? 'border-amber-500 bg-amber-50/50 ring-2 ring-amber-400/40' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50'">
                                    <input type="radio" 
                                           name="role" 
                                           value="{{ $key }}" 
                                           x-model="role" 
                                           @change="onRoleChange()"
                                           class="sr-only">
                                    <i class="fa-solid {{ $roleIcon }} text-base me-2.5"></i>
                                    <div>
                                        <div class="text-xs font-extrabold text-slate-900">{{ $label }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">role:{{ $key }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('role')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- PILIH BAGIAN OPERASIONAL (KANAN) -->
                    <div>
                        <!-- JIKA ROLE ADALAH SUPERVISOR ATAU ADMIN (GLOBAL/MENYELURUH) -->
                        <div x-show="isGlobalRole()" class="p-4 rounded-xl bg-purple-50/60 border border-purple-200 text-xs space-y-2">
                            <div class="flex items-center space-x-2 text-purple-900 font-bold">
                                <i class="fa-solid fa-earth-asia text-sm text-purple-600"></i>
                                <span>Cakupan Pengawasan Menyeluruh</span>
                            </div>
                            <p class="text-[11px] text-purple-800 leading-relaxed">
                                Role <strong class="uppercase" x-text="role === 'admin_gudang' ? 'Admin' : role"></strong> memiliki wewenang lintas seluruh area operasional pabrik (<strong>Gudang, Mesin Center, dan Material Handling</strong>) sehingga tidak dibatasi pada satu stasiun fisik.
                            </p>
                            <div class="inline-flex items-center px-2 py-0.5 rounded bg-purple-200/70 text-purple-900 text-[10px] font-mono font-bold">
                                Area Kerja: Seluruh Area Pabrik (Global)
                            </div>
                            <input type="hidden" name="work_area" value="semua">
                        </div>

                        <!-- JIKA ROLE ADALAH STAFF ATAU OPERATOR (WAJIB PILIH BAGIAN) -->
                        <div x-show="!isGlobalRole()">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Pilih Bagian Stasiun / Area Kerja <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 gap-2.5">
                                @foreach($workAreas as $areaKey => $areaLabel)
                                    @continue($areaKey === 'semua')
                                    @php
                                        $areaIcon = match($areaKey) {
                                            'gudang' => 'fa-warehouse text-amber-600',
                                            'mesin_center' => 'fa-gears text-blue-600',
                                            'material_handling' => 'fa-truck-ramp-box text-emerald-600',
                                            default => 'fa-industry text-slate-600',
                                        };
                                        $areaDesc = match($areaKey) {
                                            'gudang' => 'Gudang Pusat Karoseri, rak penyimpanan, stok & pengeluaran part',
                                            'mesin_center' => 'Pusat Mesin (Laser, M1, UNP, Sharing, Saw, Press, CNC, Robot, dll)',
                                            'material_handling' => 'Armada forklift, pemindahan barang antar-stasiun & transit',
                                            default => '',
                                        };
                                    @endphp
                                    <label class="flex items-center p-3 rounded-xl border cursor-pointer transition select-none"
                                           :class="workArea === '{{ $areaKey }}' ? 'border-blue-500 bg-blue-50/50 ring-2 ring-blue-400/40' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50'">
                                        <input type="radio" 
                                               name="work_area" 
                                               value="{{ $areaKey }}" 
                                               x-model="workArea" 
                                               @change="onWorkAreaChange()" 
                                               class="sr-only">
                                        <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center me-3 shrink-0">
                                            <i class="fa-solid {{ $areaIcon }} text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-xs font-extrabold text-slate-900">{{ $areaLabel }}</div>
                                            <div class="text-[10px] text-slate-500">{{ $areaDesc }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('work_area')
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: MASTER DEPARTEMEN PABRIK -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-7">
                <div class="flex items-center space-x-3 pb-3 border-b border-slate-100 mb-6">
                    <div class="w-9 h-9 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-building-user"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">3. Unit Stasiun Kerja / Departemen Pabrik</h3>
                        <p class="text-[11px] text-slate-500">Unit kerja pengguna untuk filter WRI dan pelaporan</p>
                    </div>
                </div>

                <!-- JIKA SUPERVISOR / ADMIN (GLOBAL AKSES SEMUA DEPARTEMEN) -->
                <div x-show="isGlobalRole()" class="p-4 rounded-xl bg-amber-50/60 border border-amber-200 text-xs space-y-2">
                    <div class="flex items-center space-x-2 text-amber-900 font-bold">
                        <i class="fa-solid fa-globe text-amber-600"></i>
                        <span>Akses Global Seluruh 15 Departemen Pabrik (* ALL DEPT)</span>
                    </div>
                    <p class="text-[11px] text-amber-800 leading-relaxed">
                        Pengguna ini secara otomatis memiliki otorisasi penuh untuk melihat, menyetujui, dan memproses tiket Work Request Inhouse (WRI) dari seluruh 15 departemen pabrik tanpa terikat pada satu departemen saja.
                    </p>
                    <input type="hidden" name="can_access_all_departments" value="1" :disabled="!isGlobalRole()">
                    <input type="hidden" name="department_id" :value="gudangDeptId" :disabled="!isGlobalRole()">
                </div>

                <!-- JIKA STAFF / OPERATOR GUDANG (TERKUNCI KE DEPT GUDANG) -->
                <div x-show="!isGlobalRole() && workArea === 'gudang'" class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-200 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 text-emerald-900 font-bold">
                            <i class="fa-solid fa-warehouse text-emerald-600"></i>
                            <span>Stasiun Kerja: Dept Gudang & Logistik</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-200 text-emerald-900">TERKUNCI OTOMATIS</span>
                    </div>
                    <p class="text-[11px] text-emerald-800">
                        Pengguna bertugas di <strong>Dept Gudang & Logistik Inhouse</strong>. Formulir tiket WRI yang dibuat oleh staf/operator ini akan terkunci otomatis dengan asal Dept Gudang.
                    </p>
                    <input type="hidden" name="department_id" :value="gudangDeptId">
                </div>

                <!-- JIKA STAFF / OPERATOR MATERIAL HANDLING (TERHUBUNG KE LOGISTIK MH) -->
                <div x-show="!isGlobalRole() && workArea === 'material_handling'" class="p-4 rounded-xl bg-blue-50/60 border border-blue-200 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 text-blue-900 font-bold">
                            <i class="fa-solid fa-truck-ramp-box text-blue-600"></i>
                            <span>Armada Logistik: Material Handling & Transit Gudang</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-200 text-blue-900">TERHUBUNG LOGISTIK</span>
                    </div>
                    <p class="text-[11px] text-blue-800">
                        Pengguna bertugas sebagai armada operasional pemindahan barang (MH) yang menghubungkan Gudang dan seluruh Pusat Mesin Pabrik.
                    </p>
                    <input type="hidden" name="department_id" :value="gudangDeptId">
                </div>

                <!-- JIKA STAFF / OPERATOR MESIN CENTER (TAMPILKAN DROPDOWN 14 MESIN FABRIKASI) -->
                <div x-show="!isGlobalRole() && workArea === 'mesin_center'" class="space-y-4">
                    <div>
                        <label for="department_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Pilih Stasiun Mesin Fabrikasi <span class="text-rose-500">*</span>
                        </label>
                        <select id="department_id" 
                                name="department_id" 
                                x-model="departmentId"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('department_id') border-rose-400 @enderror">
                            <option value="">-- Pilih Salah Satu Stasiun Mesin Pabrik (14 Mesin) --</option>
                            @foreach($machineDepartments as $dept)
                                <option value="{{ $dept->id }}">
                                    [{{ $dept->code }}] {{ $dept->name }} &bull; {{ $dept->alias }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Menentukan mesin kerja tempat staf/operator bertugas memproses dan menerima tiket WRI.</p>
                        @error('department_id')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- SECTION 4: PERAN & OTORISASI SISTEM (MATRIKS CHECKLIST BOX PERMISSION) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-7">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 mb-6 gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">4. Peran & Otorisasi Sistem (Matriks Checklist Box)</h3>
                            <p class="text-[11px] text-slate-500">Izin sistem terintegrasi: centang manual atau sesuaikan dengan preset</p>
                        </div>
                    </div>

                    <!-- PRESET QUICK CONTROLS -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" 
                                @click="applyPreset()" 
                                class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-bold transition flex items-center border border-amber-200">
                            <i class="fa-solid fa-rotate-left me-1.5 text-amber-600"></i> Rekomendasi Role
                        </button>
                        <button type="button" 
                                @click="selectAll()" 
                                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition flex items-center">
                            <i class="fa-solid fa-check-double me-1.5 text-emerald-600"></i> Pilih Semua
                        </button>
                        <button type="button" 
                                @click="deselectAll()" 
                                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition flex items-center">
                            <i class="fa-solid fa-xmark me-1.5 text-rose-600"></i> Hapus Semua
                        </button>
                    </div>
                </div>

                <!-- LIVE PRESET NOTIFICATION BANNER -->
                <div class="p-3.5 rounded-xl mb-6 flex items-start space-x-3 border text-xs"
                     :class="{
                        'bg-purple-50 border-purple-200 text-purple-900': role === 'supervisor',
                        'bg-amber-50 border-amber-200 text-amber-900': role === 'admin_gudang' || role === 'admin',
                        'bg-blue-50 border-blue-200 text-blue-900': role === 'staff',
                        'bg-emerald-50 border-emerald-200 text-emerald-900': role === 'operator'
                     }">
                    <i class="fa-solid fa-circle-info text-base mt-0.5"></i>
                    <div>
                        <template x-if="isGlobalRole()">
                            <div>
                                <span class="font-bold">Mode Penuh (All Permissions):</span>
                                Seluruh 31 hak akses modul WMS diaktifkan secara otomatis untuk pimpinan / admin sistem.
                            </div>
                        </template>
                        <template x-if="role === 'staff'">
                            <div>
                                <span class="font-bold">Mode Rekomendasi Staff:</span>
                                Menyesuaikan izin administratif, pencatatan transaksi, dan monitoring untuk stasiun <span class="font-bold uppercase" x-text="workArea.replace('_', ' ')"></span>.
                            </div>
                        </template>
                        <template x-if="role === 'operator'">
                            <div>
                                <span class="font-bold">Mode Rekomendasi Operator:</span>
                                Menyesuaikan izin eksekusi kerja, cetak tiket, dan pelaporan operasional untuk bagian <span class="font-bold uppercase" x-text="workArea.replace('_', ' ')"></span>.
                            </div>
                        </template>
                        <div class="text-[10px] opacity-75 mt-0.5">
                            * Anda dapat mencentang atau menghapus centang pada setiap kotak di bawah ini secara bebas.
                        </div>
                    </div>
                </div>

                <!-- MODULES CHECKLIST GRID -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($permissions as $group => $perms)
                        @php
                            $modulePermNames = $perms->pluck('name')->toArray();
                            $moduleIcon = match($group) {
                                'work-request' => 'fa-industry text-amber-500',
                                'material-handling' => 'fa-truck-ramp-box text-blue-500',
                                'peta-gudang' => 'fa-map-location-dot text-emerald-600',
                                'master-komponen' => 'fa-boxes-stacked text-teal-600',
                                'transaksi-komponen' => 'fa-right-left text-blue-600',
                                'ecr' => 'fa-file-pen text-purple-600',
                                'disposal' => 'fa-trash-can text-rose-600',
                                'label-qr' => 'fa-qrcode text-amber-600',
                                'cycle-count' => 'fa-clipboard-check text-indigo-600',
                                'audit-log' => 'fa-clock-rotate-left text-cyan-600',
                                'laporan' => 'fa-chart-pie text-cyan-600',
                                'admin-panel' => 'fa-shield-halved text-amber-600',
                                default => 'fa-folder text-slate-600',
                            };
                            $moduleLabel = match($group) {
                                'work-request' => 'Work Request Inhouse (WRI)',
                                'material-handling' => 'Material Handling (MH)',
                                'peta-gudang' => 'Peta & Layout Gudang',
                                'master-komponen' => 'Master Komponen',
                                'transaksi-komponen' => 'Transaksi Stok Komponen',
                                'ecr' => 'Engineering Change Request (ECR)',
                                'disposal' => 'Disposal / Scrap',
                                'label-qr' => 'Label & Cetak QR Code',
                                'cycle-count' => 'Stock Opname / Cycle Count',
                                'audit-log' => 'Log Aktivitas & Audit Sistem',
                                'laporan' => 'Log Aktivitas & Laporan',
                                'admin-panel' => 'Pengaturan & Admin Panel',
                                default => ucwords(str_replace('-', ' ', $group)),
                            };
                        @endphp
                        <div class="bg-slate-50/70 rounded-2xl border border-slate-200/90 p-4 flex flex-col justify-between {{ $group === 'work-request' ? 'ring-2 ring-amber-400/40 bg-amber-50/20' : '' }}">
                            <div>
                                <!-- Header Modul -->
                                <div class="flex items-center justify-between pb-2.5 border-b border-slate-200/80 mb-3">
                                    <div class="flex items-center space-x-2">
                                        <i class="fa-solid {{ $moduleIcon }} text-sm"></i>
                                        <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">
                                            {{ $moduleLabel }}
                                        </h4>
                                    </div>
                                    <button type="button" 
                                            @click="toggleModule({{ json_encode($modulePermNames) }})" 
                                            class="text-[10px] font-bold text-blue-600 hover:text-blue-800 bg-white border border-slate-200 px-2 py-0.5 rounded transition">
                                        Pilih Modul
                                    </button>
                                </div>

                                <!-- Checklist Izin -->
                                <div class="space-y-1.5">
                                    @foreach($perms as $perm)
                                        @php
                                            $actionRaw = explode('.', $perm->name)[1] ?? $perm->name;
                                            $friendlyTitle = match($perm->name) {
                                                // WRI
                                                'work-request.view' => 'Akses Menu WRI (Parent)',
                                                'work-request.create' => 'Sub: Buat Tiket WRI Baru',
                                                'work-request.process' => 'Sub: Eksekusi Pengerjaan Mesin',
                                                'work-request.approve' => 'Sub: Approval (Ka. Pengaju & Penerima / Tugaskan Operator)',
                                                'work-request.print' => 'Sub: Cetak Lembar WRI & QR',
                                                'work-request.safety-stock' => 'Sub: Pantau Safety Stock',

                                                // MH
                                                'material-handling.view' => 'Lihat Dashboard MH & Task',
                                                'material-handling.manage' => 'Kelola Task & Scan QR Lookup',
                                                'material-handling.process' => 'Sub: Eksekusi Pickup & Drop',

                                                // Peta Gudang
                                                'peta-gudang.view' => 'Lihat Peta Denah 2D Rak/Pallet',
                                                'peta-gudang.create' => 'Sub: Tambah Titik Rak Baru',
                                                'peta-gudang.edit' => 'Sub: Drag-and-Drop & Konfigurasi Rak',

                                                // Master Komponen
                                                'master-komponen.view' => 'Lihat Katalog & Data Komponen',
                                                'master-komponen.create' => 'Sub: Tambah Komponen & Import',
                                                'master-komponen.edit' => 'Sub: Edit Komponen & Foto',
                                                'master-komponen.delete' => 'Sub: Hapus Master Komponen',

                                                // Transaksi Komponen
                                                'transaksi-komponen.view' => 'Lihat Kartu Stok & Mutasi',
                                                'transaksi-komponen.create' => 'Sub: Catat Mutasi In/Out/Retur',

                                                // Label QR
                                                'label-qr.view' => 'Lihat Antrean & Riwayat Label',
                                                'label-qr.create' => 'Sub: Ajukan Cetak Label Baru',
                                                'label-qr.print' => 'Sub: Cetak Label (Thermal/A4/Bulk)',

                                                // ECR
                                                'ecr.view' => 'Lihat Daftar Pengajuan ECR',
                                                'ecr.create' => 'Sub: Ajukan Revisi Komponen',
                                                'ecr.approve' => 'Sub: Otorisasi Approval/Reject ECR',

                                                // Disposal
                                                'disposal.view' => 'Lihat Pengajuan Disposal Scrap',
                                                'disposal.create' => 'Sub: Ajukan Afkir Material Rusak',
                                                'disposal.approve' => 'Sub: Otorisasi QC & Eksekusi Scrap',

                                                // Cycle Count
                                                'cycle-count.view' => 'Lihat Sesi & Riwayat Opname',
                                                'cycle-count.create' => 'Sub: Buka Opname & Input Hitung',
                                                'cycle-count.approve' => 'Sub: Rekonsiliasi Selisih Stok',

                                                // Audit Log
                                                'audit-log.view' => 'Lihat Rekam Jejak Audit Sistem',

                                                default => ucfirst(str_replace('-', ' ', $actionRaw)),
                                            };
                                            $actionBadge = match($actionRaw) {
                                                'view' => 'text-slate-700 bg-slate-100',
                                                'create' => 'text-emerald-700 bg-emerald-100/70',
                                                'edit' => 'text-blue-700 bg-blue-100/70',
                                                'delete' => 'text-rose-700 bg-rose-100/70',
                                                'approve' => 'text-purple-700 bg-purple-100/70',
                                                'export' => 'text-amber-700 bg-amber-100/70',
                                                'process' => 'text-indigo-700 bg-indigo-100/70',
                                                'print' => 'text-amber-700 bg-amber-100/70',
                                                'monitoring' => 'text-cyan-700 bg-cyan-100/70',
                                                'safety-stock' => 'text-rose-700 bg-rose-100/70',
                                                'view-all' => 'text-teal-700 bg-teal-100/70',
                                                'users' => 'text-purple-700 bg-purple-100/70',
                                                'settings' => 'text-blue-700 bg-blue-100/70',
                                                'manage' => 'text-blue-700 bg-blue-100/70',
                                                default => 'text-slate-700 bg-slate-100',
                                            };
                                        @endphp
                                        <label class="flex items-center p-2 rounded-xl bg-white hover:bg-slate-100/80 border border-slate-200/60 cursor-pointer transition">
                                            <input type="checkbox" 
                                                   name="permissions[]" 
                                                   value="{{ $perm->name }}" 
                                                   x-model="selectedPermissions" 
                                                   class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 border-slate-300">
                                            <div class="ms-2.5 flex-1 flex items-center justify-between overflow-hidden">
                                                <span class="text-xs font-bold text-slate-800 truncate" title="{{ $friendlyTitle }}">{{ $friendlyTitle }}</span>
                                                <span class="text-[9px] font-mono px-1.5 py-0.5 rounded font-semibold shrink-0 {{ $actionBadge }}">
                                                    {{ $actionRaw }}
                                                </span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- SECTION 5: STATUS AKUN AKTIF -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1" 
                           {{ old('is_active', $user->is_active) ? 'checked' : '' }} 
                           {{ $user->id === auth()->id() ? 'disabled' : '' }}
                           class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 border-slate-300">
                    <div class="ms-3">
                        <span class="text-xs font-bold text-slate-900 block">Status Akun Aktif</span>
                        <span class="text-[11px] text-slate-500 block">Jika dinonaktifkan, user tidak akan dapat login ke dalam sistem WMS.</span>
                        @if($user->id === auth()->id())
                            <input type="hidden" name="is_active" value="1">
                            <span class="text-[10px] text-amber-600 font-semibold italic mt-0.5 block">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Anda tidak dapat menonaktifkan akun sendiri
                            </span>
                        @endif
                    </div>
                </label>
            </div>

            <!-- STICKY BOTTOM ACTION BAR -->
            <div class="sticky bottom-4 z-30 bg-white/95 backdrop-blur-md rounded-2xl border border-slate-200/90 shadow-xl p-4 flex items-center justify-between">
                <div class="flex items-center space-x-2 text-xs text-slate-500">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>Hak akses dan role akan langsung tersinkronisasi saat disimpan</span>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.users.index') }}" 
                       class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-6 py-2.5 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                        <i class="fa-solid fa-check me-2"></i>
                        Perbarui Akun & Matriks Izin
                    </button>
                </div>
            </div>
        </form>

    </div>
</x-admin-layout>

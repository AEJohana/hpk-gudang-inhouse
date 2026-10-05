<x-admin-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 230px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-10 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-2">
                    <a href="{{ route('admin.roles.index') }}" class="text-xs font-semibold text-amber-300 hover:text-amber-200 transition flex items-center">
                        <i class="fa-solid fa-arrow-left me-1.5"></i> Kembali ke Daftar Role
                    </a>
                    <span class="text-white/40">&bull;</span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">Matriks Permission</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                            Konfigurasi Izin: {{ ucwords(str_replace('_', ' ', $role->name)) }}
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1">
                            Centang tindakan atau fitur yang diperbolehkan untuk divisi ini di seluruh modul WMS.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. PERMISSIONS MATRIX FORM -->
        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Global Master Controls Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Kontrol Cepat Matriks</h3>
                        <p class="text-[11px] text-slate-500">Pilih atau batalkan semua izin secara instan</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="button" 
                            id="btnSelectAll" 
                            class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition flex items-center">
                        <i class="fa-solid fa-check-double me-1.5 text-emerald-600"></i> Pilih Semua
                    </button>
                    <button type="button" 
                            id="btnDeselectAll" 
                            class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition flex items-center">
                        <i class="fa-solid fa-xmark me-1.5 text-rose-600"></i> Hapus Semua
                    </button>
                </div>
            </div>

            <!-- Modules Grid -->
            @php $rolePermissions = $role->permissions->pluck('name')->toArray(); @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($permissions as $group => $perms)
                    @php
                        $moduleIcon = match($group) {
                            'peta-gudang' => 'fa-map-location-dot text-emerald-600',
                            'master-komponen' => 'fa-boxes-stacked text-teal-600',
                            'transaksi-komponen' => 'fa-right-left text-blue-600',
                            'ecr' => 'fa-file-pen text-purple-600',
                            'disposal' => 'fa-trash-can text-rose-600',
                            'label-qr' => 'fa-qrcode text-amber-600',
                            'cycle-count' => 'fa-clipboard-check text-indigo-600',
                            'laporan' => 'fa-chart-pie text-cyan-600',
                            'admin-panel' => 'fa-shield-halved text-amber-600',
                            default => 'fa-folder text-slate-600',
                        };
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex flex-col justify-between module-card">
                        <div>
                            <!-- Module Header -->
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3.5">
                                <div class="flex items-center space-x-2.5">
                                    <i class="fa-solid {{ $moduleIcon }} text-base"></i>
                                    <h4 class="font-extrabold text-slate-900 text-xs uppercase tracking-wider">
                                        {{ str_replace('-', ' ', $group) }}
                                    </h4>
                                </div>
                                <button type="button" 
                                        onclick="toggleModulePerms(this)" 
                                        class="text-[10px] font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-2 py-0.5 rounded transition">
                                    Pilih Modul
                                </button>
                            </div>

                            <!-- Permission Checkboxes List -->
                            <div class="space-y-2">
                                @foreach($perms as $perm)
                                    @php
                                        $actionName = explode('.', $perm->name)[1] ?? $perm->name;
                                        $actionBadge = match($actionName) {
                                            'view' => 'text-slate-700 bg-slate-100',
                                            'create' => 'text-emerald-700 bg-emerald-50',
                                            'edit' => 'text-blue-700 bg-blue-50',
                                            'delete' => 'text-rose-700 bg-rose-50',
                                            'approve' => 'text-purple-700 bg-purple-50',
                                            'export' => 'text-amber-700 bg-amber-50',
                                            default => 'text-slate-700 bg-slate-100',
                                        };
                                    @endphp
                                    <label class="flex items-center p-2 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200/80 cursor-pointer transition">
                                        <input type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $perm->name }}" 
                                               {{ in_array($perm->name, $rolePermissions) ? 'checked' : '' }} 
                                               class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 border-slate-300 permission-checkbox">
                                        <div class="ms-3 flex-1 flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-800">{{ ucfirst($actionName) }}</span>
                                            <span class="text-[9px] font-mono px-1.5 py-0.5 rounded font-semibold {{ $actionBadge }}">
                                                {{ $perm->name }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Sticky Bottom Action Bar -->
            <div class="sticky bottom-4 z-30 bg-white/95 backdrop-blur-md rounded-2xl border border-slate-200/90 shadow-xl p-4 flex items-center justify-between">
                <div class="flex items-center space-x-2 text-xs text-slate-500">
                    <i class="fa-solid fa-circle-info text-amber-500"></i>
                    <span>Perubahan hak akses berlaku segera setelah disimpan</span>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.roles.index') }}" 
                       class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Simpan Matriks Permission
                    </button>
                </div>
            </div>
        </form>

    </div>

    <!-- Script for Select All / Deselect All -->
    <script>
        document.getElementById('btnSelectAll').addEventListener('click', function() {
            document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
        });

        document.getElementById('btnDeselectAll').addEventListener('click', function() {
            document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
        });

        function toggleModulePerms(btn) {
            const card = btn.closest('.module-card');
            const checkboxes = card.querySelectorAll('.permission-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        }
    </script>
</x-admin-layout>

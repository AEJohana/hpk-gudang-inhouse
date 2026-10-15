<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('work-requests.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded border border-slate-200">
                            {{ $workRequest->wri_number }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] {{ $workRequest->priority_badge['class'] }}">
                            {{ $workRequest->priority_badge['label'] }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] border {{ $workRequest->status_badge['class'] }}">
                            {{ $workRequest->status_badge['label'] }}
                        </span>
                        @if($workRequest->quantity_defect > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Defect: {{ number_format($workRequest->quantity_defect, 0) }}
                        </span>
                        @endif
                        @if($workRequest->spk_reference)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-900 border border-purple-300">
                            <i class="fa-solid fa-file-contract me-1 text-purple-600"></i> SPK: {{ $workRequest->spk_reference }}
                        </span>
                        @endif
                    </div>
                    <h2 class="font-extrabold text-2xl text-slate-900 leading-tight mt-1">
                        {{ $workRequest->component->name }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <span class="font-semibold text-slate-700">{{ $workRequest->dept_pengaju ?: 'Gudang' }}</span> &rarr; 
                        <span class="font-semibold text-slate-700">{{ $workRequest->dept_penerima ?: 'Mesin Shop' }}</span> &bull; 
                        Pemohon: <span class="font-semibold text-slate-700">{{ $workRequest->requestedBy->name }}</span> &bull; 
                        Tgl: {{ $workRequest->created_at->format('d M Y, H:i') }}
                        @if($workRequest->due_date)
                            &bull; Target: <span class="font-bold text-amber-800">{{ $workRequest->due_date->format('d M Y') }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                @can('work-request.print')
                <a href="{{ route('work-requests.print-sheet', $workRequest) }}" target="_blank" class="px-3 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition flex items-center">
                    <i class="fa-solid fa-print me-1.5"></i>
                    Cetak Kartu WRI
                </a>
                @endcan
                <a href="{{ route('work-station-supplies.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center">
                    <i class="fa-solid fa-truck-ramp-box me-1.5 text-blue-600"></i>
                    Material Handling
                </a>
                @if($workRequest->targetLocation)
                    <a href="{{ route('warehouse-map.index') }}" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center">
                        <i class="fa-solid fa-map-location-dot me-1.5"></i>
                        Slot Rak Gudang
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ showDefectForm: false, showAdvancedForm: false, actionModal: false }">

        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-xmark text-rose-600 text-base"></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="p-4 bg-amber-50 border border-amber-300 text-amber-900 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            </div>
        @endif

        <!-- KARTU OTORISASI & APPROVAL KEPALA BAGIAN (BR-06) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-stamp"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-900">Otorisasi & Approval Kepala Bagian</h4>
                        <p class="text-[11px] text-slate-500">Verifikasi pesanan pengaju dan kesanggupan kapasitas mesin tujuan</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    @if($workRequest->isApproved())
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                            <i class="fa-solid fa-check me-1"></i> Disetujui Penuh &bull; Masuk Jalur Mesin
                        </span>
                    @elseif($workRequest->approval_pengaju === 'Ditolak' || $workRequest->approval_penerima === 'Ditolak')
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">
                            <i class="fa-solid fa-xmark me-1"></i> Tiket Dibatalkan
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 animate-pulse">
                            <i class="fa-solid fa-clock me-1"></i> Menunggu Otorisasi Ka. Bagian
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Status Approval Pengaju -->
                <div class="p-4 rounded-xl border {{ $workRequest->approval_pengaju === 'Diterima' ? 'bg-emerald-50/50 border-emerald-200' : ($workRequest->approval_pengaju === 'Ditolak' ? 'bg-rose-50/50 border-rose-200' : 'bg-amber-50/50 border-amber-200') }} flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">1. Verifikasi Ka. Bagian Pengaju</span>
                        <div class="font-bold text-xs text-slate-900 mt-0.5">
                            Departemen: <span class="text-indigo-900 font-extrabold">{{ $workRequest->dept_pengaju ?: 'Gudang' }}</span>
                        </div>
                        <div class="text-[11px] mt-1 font-semibold flex items-center space-x-1.5">
                            <span>Status:</span>
                            @if($workRequest->approval_pengaju === 'Diterima')
                                <span class="text-emerald-700 font-extrabold"><i class="fa-solid fa-circle-check me-1"></i> Disetujui</span>
                            @elseif($workRequest->approval_pengaju === 'Ditolak')
                                <span class="text-rose-700 font-extrabold"><i class="fa-solid fa-circle-xmark me-1"></i> Ditolak</span>
                            @else
                                <span class="text-amber-800 font-extrabold"><i class="fa-solid fa-clock me-1"></i> Menunggu Approval</span>
                            @endif
                        </div>
                    </div>

                    @if($workRequest->approval_pengaju === 'Menunggu' && $canApprove)
                    <div class="flex items-center space-x-2">
                        <form method="POST" action="{{ route('work-requests.approve-pengaju', $workRequest) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center space-x-1">
                                <i class="fa-solid fa-check"></i>
                                <span>Setujui</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('work-requests.reject', $workRequest) }}" onsubmit="return confirm('Yakin ingin menolak tiket WRI ini?');">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center space-x-1">
                                <i class="fa-solid fa-xmark"></i>
                                <span>Tolak</span>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                <!-- Status Approval Penerima -->
                <div class="p-4 rounded-xl border {{ $workRequest->approval_penerima === 'Diterima' ? 'bg-emerald-50/50 border-emerald-200' : ($workRequest->approval_penerima === 'Ditolak' ? 'bg-rose-50/50 border-rose-200' : 'bg-amber-50/50 border-amber-200') }} flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">2. Verifikasi Ka. Bagian Penerima (Mesin)</span>
                        <div class="font-bold text-xs text-slate-900 mt-0.5">
                            Departemen: <span class="text-indigo-900 font-extrabold">{{ $workRequest->dept_penerima ?: 'Mesin Shop' }}</span>
                        </div>
                        <div class="text-[11px] mt-1 font-semibold flex items-center space-x-1.5">
                            <span>Status:</span>
                            @if($workRequest->approval_penerima === 'Diterima')
                                <span class="text-emerald-700 font-extrabold"><i class="fa-solid fa-circle-check me-1"></i> Disetujui</span>
                            @elseif($workRequest->approval_penerima === 'Ditolak')
                                <span class="text-rose-700 font-extrabold"><i class="fa-solid fa-circle-xmark me-1"></i> Ditolak</span>
                            @else
                                <span class="text-amber-800 font-extrabold"><i class="fa-solid fa-clock me-1"></i> Menunggu Verifikasi Mesin</span>
                            @endif
                        </div>
                    </div>

                    @if($workRequest->approval_penerima === 'Menunggu' && $canApprove && $workRequest->approval_pengaju === 'Diterima')
                    <div class="flex items-center space-x-2">
                        <form method="POST" action="{{ route('work-requests.approve-penerima', $workRequest) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center space-x-1">
                                <i class="fa-solid fa-check"></i>
                                <span>Setujui Mesin</span>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($workRequest->isPendingApproval())
        <div class="p-4 bg-amber-50 border border-amber-300 rounded-2xl flex items-center space-x-3 text-amber-900">
            <i class="fa-solid fa-circle-exclamation text-amber-600 text-lg"></i>
            <div>
                <h4 class="font-bold text-xs">Pengerjaan Mesin Tertahan (Menunggu Otorisasi)</h4>
                <p class="text-xs text-amber-800 mt-0.5">Sesuai aturan operasional pabrik karoseri, proses mesin dan serah terima material handling baru dapat dimulai setelah tiket disetujui oleh Kepala Bagian.</p>
            </div>
        </div>
        @endif

        <!-- OPERATIONAL ACTION HERO BOX (Siklus Kerja Operasional) -->
        @if(!$workRequest->is_final && stripos($workRequest->status, 'Sudah Diambil') === false)
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 text-white shadow-xl border border-indigo-900/50 space-y-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/10 text-teal-300 text-xs font-bold mb-2">
                        <i class="fa-solid fa-circle-play me-1.5 text-amber-400"></i> Kontrol Tahap Operasional & Logistik
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-white">
                        Status Saat Ini: <span class="text-amber-400">{{ $workRequest->status }}</span>
                    </h3>
                    <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                        Langkah berikutnya yang disarankan: <span class="font-extrabold text-teal-300">{{ $nextStatus }}</span>.
                        Anda dapat mengeksekusi pengerjaan kuantitas penuh atau parsial (cicilan), serta mencatat reject/defect fisik.
                    </p>
                </div>

                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 text-center flex-shrink-0 min-w-[200px]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-300 block">Sisa Kuota Tiket</span>
                    <span class="text-3xl font-black text-white block mt-0.5">
                        {{ number_format($workRequest->quantity_requested, 0) }} <span class="text-sm font-normal text-teal-200">{{ $workRequest->component->uom }}</span>
                    </span>
                    @if($workRequest->quantity_received > 0)
                    <span class="text-[11px] text-emerald-300 mt-0.5 block font-semibold">
                        Telah Diterima: {{ number_format($workRequest->quantity_received, 0) }} pcs
                    </span>
                    @endif
                </div>
            </div>

            <!-- ONE-CLICK ADAPTIVE ACTION BUTTON -->
            <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/15 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-amber-300 uppercase tracking-wider block flex items-center space-x-1.5">
                        <i class="fa-solid fa-forward-step text-amber-400"></i>
                        <span>Aksi Langkah Selanjutnya (Rekomendasi Alur):</span>
                    </span>
                    <div class="text-sm font-black text-white flex items-center space-x-2">
                        <span>&rarr; {{ $nextStatus }}</span>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <form method="POST" action="{{ route('work-requests.process-action', $workRequest) }}">
                        @csrf
                        <input type="hidden" name="action_type" value="process">
                        <input type="hidden" name="target_status" value="{{ $nextStatus }}">
                        <input type="hidden" name="process_qty" value="{{ $workRequest->quantity_requested }}">
                        <button type="submit" 
                                class="px-5 py-2.5 font-black text-xs rounded-xl shadow-lg transition flex items-center space-x-2 {{ stripos($nextStatus, 'Sudah Diambil') !== false ? 'bg-emerald-500 hover:bg-emerald-400 text-slate-950' : (stripos($nextStatus, 'Pickup') !== false ? 'bg-amber-400 hover:bg-amber-300 text-slate-950' : (stripos($nextStatus, '(Out)') !== false ? 'bg-indigo-500 hover:bg-indigo-400 text-white' : 'bg-teal-500 hover:bg-teal-400 text-slate-950')) }}">
                            @if(stripos($nextStatus, 'Sudah Diambil') !== false)
                                <i class="fa-solid fa-box-open text-base"></i>
                                <span>Konfirmasi Serah Terima & Masuk Stok</span>
                            @elseif(stripos($nextStatus, 'Pickup') !== false)
                                <i class="fa-solid fa-truck-ramp-box text-base"></i>
                                <span>Angkut Material Handling (Pickup)</span>
                            @elseif(stripos($nextStatus, '(Out)') !== false)
                                <i class="fa-solid fa-check text-base"></i>
                                <span>Selesaikan Mesin: {{ $nextStatus }}</span>
                            @elseif(stripos($nextStatus, 'Menuju Pengaju') !== false)
                                <i class="fa-solid fa-truck-fast text-base"></i>
                                <span>Kirim ke Pengaju / Gudang</span>
                            @else
                                <i class="fa-solid fa-play text-base"></i>
                                <span>Mulai: {{ $nextStatus }}</span>
                            @endif
                        </button>
                    </form>

                    <button type="button" 
                            @click="showAdvancedForm = !showAdvancedForm" 
                            class="px-3.5 py-2.5 bg-white/10 hover:bg-white/20 text-slate-200 text-xs font-bold rounded-xl transition border border-white/20 flex items-center space-x-1" 
                            title="Buka opsi parsial / defect / ubah status manual">
                        <i class="fa-solid fa-sliders"></i>
                        <span x-text="showAdvancedForm ? 'Tutup Opsi' : 'Opsi Parsial / Defect'"></span>
                    </button>
                </div>
            </div>

            <!-- Form Eksekusi Lanjutan (Parsial, Defect, Manual Status) -->
            <div x-show="showAdvancedForm" x-transition class="bg-white text-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
                <form method="POST" action="{{ route('work-requests.process-action', $workRequest) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action_type" value="process">

                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-sliders text-indigo-600"></i>
                            <h4 class="font-extrabold text-sm text-slate-900">Form Pengerjaan Khusus (Parsial & Defect)</h4>
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="inline-flex items-center cursor-pointer text-xs font-bold text-rose-600 hover:text-rose-700">
                                <input type="checkbox" x-model="showDefectForm" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 me-1.5">
                                <span>Ada Barang Defect / Reject?</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <!-- Target Status -->
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                                Langkah Status Tujuan <span class="text-rose-500">*</span>
                            </label>
                            <select name="target_status" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-bold text-slate-900">
                                <option value="{{ $nextStatus }}" selected>&rarr; Rekomendasi: {{ $nextStatus }}</option>
                                <option value="Proses: Dept Laser Cutting (In)">Proses: Dept Laser Cutting (In)</option>
                                <option value="Proses: Dept Laser Cutting (Out)">Proses: Dept Laser Cutting (Out)</option>
                                <option value="MH Pickup: Dept Laser Cutting">MH Pickup: Dept Laser Cutting</option>
                                <option value="Proses: Dept Bending (In)">Proses: Dept Bending (In)</option>
                                <option value="Proses: Dept Bending (Out)">Proses: Dept Bending (Out)</option>
                                <option value="MH Pickup: Dept Bending">MH Pickup: Dept Bending</option>
                                <option value="Siap Diambil">Siap Diambil</option>
                                <option value="Menuju Pengaju">Menuju Pengaju</option>
                                <option value="Sudah Diambil">Sudah Diambil (Selesai & Masuk Stok)</option>
                            </select>
                        </div>

                        <!-- Process Qty (Parsial / Full) -->
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                                Qty Pengerjaan / Kirim (Parsial / Penuh) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   name="process_qty" 
                                   required 
                                   step="any" 
                                   min="0.01" 
                                   max="{{ $workRequest->quantity_requested }}"
                                   value="{{ $workRequest->quantity_requested }}" 
                                   class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-black text-slate-900 text-sm">
                            <span class="text-[10px] text-slate-400 block mt-0.5">Jika input &lt; {{ $workRequest->quantity_requested }}, tiket otomatis tercatat <b>Parsial</b>.</span>
                        </div>

                        <!-- Operator Name -->
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                                Petugas / Operator
                            </label>
                            <input type="text" 
                                   name="operator_name" 
                                   value="{{ auth()->user()->name }}" 
                                   class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-semibold">
                        </div>
                    </div>

                    <!-- Defect Sub-Form (Conditional AlpineJS) -->
                    <div x-show="showDefectForm" x-transition class="p-4 bg-rose-50 border border-rose-200 rounded-2xl space-y-3">
                        <div class="flex items-center space-x-2 text-rose-800 text-xs font-bold">
                            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                            <span>Pencatatan Barang Defect / Reject (Akan Memotong Kuantitas Tiket WRI)</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="block font-bold text-rose-900 uppercase tracking-wider mb-1 text-[10px]">
                                    Jumlah Defect (PCS) <span class="text-rose-600">*</span>
                                </label>
                                <input type="number" 
                                       name="defect_qty" 
                                       step="any" 
                                       min="0.01" 
                                       max="{{ $workRequest->quantity_requested }}"
                                       placeholder="Contoh: 12" 
                                       class="w-full px-3 py-2 border border-rose-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none font-bold text-rose-900 bg-white">
                            </div>
                            <div>
                                <label class="block font-bold text-rose-900 uppercase tracking-wider mb-1 text-[10px]">
                                    Alasan Defect / Reject <span class="text-rose-600">*</span>
                                </label>
                                <input type="text" 
                                       name="defect_reason" 
                                       placeholder="Contoh: Salah tekuk, material bengkok, plat kurang..." 
                                       class="w-full px-3 py-2 border border-rose-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none bg-white">
                            </div>
                        </div>
                    </div>

                    <!-- Notes & Submit -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                        <div class="flex-1">
                            <input type="text" 
                                   name="notes" 
                                   placeholder="Catatan tambahan operasional (opsional)..." 
                                   class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs rounded-xl shadow-md transition flex items-center justify-center space-x-2">
                            <i class="fa-solid fa-check text-sm"></i>
                            <span>Simpan & Eksekusi Status Khusus</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @else
        <!-- ALREADY FINAL BANNER -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-xs">
            <div class="flex items-start space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 text-xl font-bold shadow-xs">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-base text-emerald-950">Tiket WRI Selesai Sepenuhnya &bull; Sudah Diambil (Final)</h4>
                    <p class="text-xs text-emerald-700 mt-0.5 leading-relaxed">
                        Seluruh komponen telah diserahterimakan dan saldo stok fisik gudang telah otomatis diperbarui. 
                        Diterima oleh <span class="font-bold">{{ $workRequest->receivedBy?->name ?? 'Gudang' }}</span> pada 
                        {{ $workRequest->received_at?->format('d M Y, H:i') ?? '-' }}.
                    </p>
                    <div class="mt-2 flex items-center space-x-2 text-xs">
                        <span class="px-2.5 py-0.5 bg-white border border-emerald-300 rounded font-mono font-bold text-emerald-900">
                            Slot Rak: {{ $workRequest->target_specific_location_code }}
                        </span>
                        <span class="font-bold text-emerald-800">
                            Total Masuk: {{ number_format($workRequest->quantity_received, 0) }} {{ $workRequest->component->uom }}
                        </span>
                    </div>
                </div>
            </div>

            @if($workRequest->targetLocation)
            <div class="flex items-center space-x-2">
                <a href="{{ route('warehouse-map.index') }}" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center">
                    <i class="fa-solid fa-map-location-dot me-1.5"></i>
                    Lihat di Peta Denah Gudang
                </a>
            </div>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- COL 1 & 2: ROUTING TIMELINE & AUDIT LOGS -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Stepper Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-timeline text-slate-800"></i>
                            <h3 class="font-extrabold text-base text-slate-900">Alur Routing Proses Mesin & Material Handling</h3>
                        </div>
                        <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full">
                            Progres: {{ $workRequest->progress_percentage }}%
                        </span>
                    </div>

                    <!-- Steps Timeline -->
                    <div class="space-y-6 relative before:absolute before:inset-0 before:left-4 before:w-0.5 before:bg-slate-200">
                        @foreach($workRequest->steps as $step)
                        <div class="relative flex items-start space-x-4">
                            <!-- Bullet Icon -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 z-10 shadow-xs {{ $step->status === 'completed' ? 'bg-emerald-600 text-white' : ($step->status === 'in_progress' ? 'bg-blue-600 text-white ring-4 ring-blue-100' : 'bg-white border-2 border-slate-300 text-slate-500') }}">
                                @if($step->status === 'completed')
                                    <i class="fa-solid fa-check"></i>
                                @else
                                    {{ $step->step_number }}
                                @endif
                            </div>

                            <!-- Step Content Card -->
                            <div class="flex-1 bg-slate-50 rounded-2xl p-4 border {{ $step->status === 'in_progress' ? 'border-blue-300 bg-blue-50/40 ring-1 ring-blue-300' : 'border-slate-200' }} space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono text-xs font-bold text-blue-900 bg-blue-100/70 px-2 py-0.5 rounded">
                                                {{ $step->machine->code }}
                                            </span>
                                            <span class="text-xs text-slate-500 font-semibold">{{ $step->machine->name }}</span>
                                        </div>
                                        <h4 class="font-extrabold text-sm text-slate-900 mt-1">
                                            {{ $step->process_name }}
                                        </h4>
                                    </div>

                                    <div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $step->status_badge['class'] }}">
                                            @if($step->status === 'in_progress')
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-ping me-1.5"></span>
                                            @endif
                                            {{ $step->status_badge['label'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-[11px] text-slate-500 pt-2 border-t border-slate-200/60">
                                    <div>
                                        <span class="text-slate-400 block">Operator:</span>
                                        <span class="font-semibold text-slate-700">{{ $step->operator_name ?: ($step->machine->operator_default ?: '-') }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Waktu Mulai:</span>
                                        <span class="font-mono text-slate-700">{{ $step->started_at ? $step->started_at->format('d M, H:i') : '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Waktu Selesai:</span>
                                        <span class="font-mono text-slate-700">{{ $step->completed_at ? $step->completed_at->format('d M, H:i') : '-' }}</span>
                                    </div>
                                </div>

                                @if($step->notes)
                                <p class="text-[11px] text-slate-600 italic bg-white p-2 rounded-lg border border-slate-200">
                                    Catatan: {{ $step->notes }}
                                </p>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        <!-- Final Step: Serah Terima / Gudang -->
                        <div class="relative flex items-start space-x-4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 z-10 shadow-xs {{ $workRequest->is_final || stripos($workRequest->status, 'Sudah Diambil') !== false ? 'bg-emerald-600 text-white' : 'bg-white border-2 border-slate-300 text-slate-500' }}">
                                <i class="fa-solid fa-warehouse"></i>
                            </div>
                            <div class="flex-1 bg-slate-50 rounded-2xl p-4 border border-slate-200">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-extrabold text-sm text-slate-900">
                                        Penerimaan Pengaju / Gudang & Auto Update Saldo Stok Fisik
                                    </h4>
                                    <span class="text-xs font-bold {{ $workRequest->is_final ? 'text-emerald-700' : 'text-slate-400' }}">
                                        {{ $workRequest->is_final ? 'Sudah Diambil (Final)' : 'Tahap Akhir' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Begitu status diset menjadi <b>Sudah Diambil</b>, sistem otomatis menambahkan kuantitas ke rak <span class="font-mono font-bold text-blue-900">{{ $workRequest->target_specific_location_code }}</span>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AUDIT TRAIL LOG REAL-TIME (Tabel Riwayat Lengkap) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-clock-rotate-left text-slate-800"></i>
                            <h3 class="font-extrabold text-base text-slate-900">Riwayat Log Transaksi & Defect (Audit Trail)</h3>
                        </div>
                        <span class="text-xs text-slate-400 font-semibold">{{ $workRequest->logs->count() }} Entri Log</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">Waktu</th>
                                    <th class="py-2.5 px-3">Status / Pergerakan</th>
                                    <th class="py-2.5 px-3 text-center">Jumlah (Qty)</th>
                                    <th class="py-2.5 px-3">Operator</th>
                                    <th class="py-2.5 px-3">Keterangan / Alasan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($workRequest->logs as $log)
                                <tr class="hover:bg-slate-50/70 transition {{ $log->action_type === 'defect' ? 'bg-rose-50/50' : '' }}">
                                    <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500">
                                        {{ $log->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if($log->action_type === 'defect')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $log->status_label }}
                                            </span>
                                        @else
                                            <span class="font-semibold text-slate-800">{{ $log->status_label }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-extrabold {{ $log->action_type === 'defect' ? 'text-rose-600' : 'text-slate-900' }}">
                                        {{ number_format($log->quantity, 0) }} pcs
                                    </td>
                                    <td class="py-2.5 px-3 font-medium text-slate-700">
                                        {{ $log->operator_name ?: '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-600 text-[11px]">
                                        {{ $log->notes ?: '-' }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400 italic">
                                        Belum ada riwayat aktivitas pada tiket ini.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- COL 3: COMPONENT INFO & ORDER DETAILS CARD -->
            <div class="space-y-6">

                <!-- Component Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2">
                        Rincian Komponen
                    </h3>

                    <div class="w-full aspect-video rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center">
                        <img src="{{ $workRequest->component->image_url }}" alt="{{ $workRequest->component->name }}" class="w-full h-full object-cover">
                    </div>

                    <div>
                        <span class="font-mono text-xs font-bold text-amber-900 bg-amber-100/80 px-2 py-0.5 rounded">
                            {{ $workRequest->component->part_number }}
                        </span>
                        <h4 class="font-extrabold text-base text-slate-900 mt-1">
                            {{ $workRequest->component->name }}
                        </h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            {{ $workRequest->component->specification ?: 'Spesifikasi standar karoseri HPK.' }}
                        </p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Satuan UoM:</span>
                            <span class="font-bold text-slate-900">{{ $workRequest->component->uom }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Saldo Fisik Gudang:</span>
                            <span class="font-bold text-emerald-700">{{ number_format($workRequest->component->total_stock, 0) }} {{ $workRequest->component->uom }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('components.show', $workRequest->component) }}" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition block text-center">
                            Buka Master Komponen &rarr;
                        </a>
                    </div>
                </div>

                <!-- Order Meta Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3 text-xs">
                    <h3 class="font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2 text-[10px]">
                        Informasi Dokumen Order
                    </h3>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Nomor WRI:</span>
                            <span class="font-mono font-bold text-slate-900">{{ $workRequest->wri_number }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Departemen Asal:</span>
                            <span class="font-semibold text-slate-800">{{ $workRequest->dept_pengaju ?: 'Gudang' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Departemen Tujuan:</span>
                            <span class="font-semibold text-slate-800">{{ $workRequest->dept_penerima ?: 'Mesin' }}</span>
                        </div>
                        @if($workRequest->spk_reference)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Referensi SPK:</span>
                            <span class="font-mono font-bold text-blue-700">{{ $workRequest->spk_reference }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Jumlah Diminta:</span>
                            <span class="font-extrabold text-slate-900">{{ number_format($workRequest->quantity_requested, 0) }} {{ $workRequest->component->uom }}</span>
                        </div>
                        @if($workRequest->quantity_defect > 0)
                        <div class="flex items-center justify-between text-rose-600">
                            <span>Akumulasi Defect:</span>
                            <span class="font-bold">{{ number_format($workRequest->quantity_defect, 0) }} {{ $workRequest->component->uom }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Target Slot:</span>
                            <span class="font-mono font-bold text-blue-900">{{ $workRequest->target_specific_location_code }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Prioritas:</span>
                            <span class="px-2 py-0.5 rounded text-[10px] {{ $workRequest->priority_badge['class'] }}">
                                {{ $workRequest->priority_badge['label'] }}
                            </span>
                        </div>
                    </div>

                    @if($workRequest->notes)
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-slate-400 text-[10px] uppercase font-bold block mb-1">Catatan Khusus:</span>
                        <p class="text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-[11px] leading-relaxed">
                            {{ $workRequest->notes }}
                        </p>
                    </div>
                    @endif
                </div>

            </div>

        </div>

    </div>
</x-app-layout>

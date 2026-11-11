<!-- MODAL 3: VERIFIKASI PENERIMA & PENUGASAN OPERATOR MESIN (POP-UP MINIMALIS & ERGONOMIS) -->
<div x-show="assignModalOpen" 
     x-cloak 
     style="display: none;"
     :class="{ 'pointer-events-none': !assignModalOpen }"
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-assign-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur Lembut -->
    <div x-show="assignModalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         :class="{ 'pointer-events-none': !assignModalOpen }"
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" 
         @click="closeAssignModal()"></div>

    <div class="flex min-h-full items-center justify-center p-3 text-center sm:p-0">
        <div x-show="assignModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-md border border-slate-200/80">
            
            <!-- Modal Header Bersih & Kompak -->
            <div class="p-5 pb-3 border-b border-slate-100 relative">
                <button type="button" 
                        @click="closeAssignModal()" 
                        class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>

                <div class="flex items-center space-x-3 pr-8">
                    <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200/70 flex items-center justify-center text-lg flex-shrink-0 shadow-2xs">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-extrabold text-slate-900 leading-tight truncate">Tugaskan Operator Mesin</h3>
                        <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">Verifikasi kesanggupan & penentuan pelaksana</p>
                    </div>
                </div>

                <!-- Tiket & Info Komponen Ringkas -->
                <div class="mt-3 p-2.5 bg-slate-50 border border-slate-200/70 rounded-2xl flex items-center justify-between text-xs">
                    <div class="min-w-0 flex-1 pr-2">
                        <div>
                            <span class="text-[10px] font-mono font-black uppercase text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200/60 inline-block truncate max-w-full" x-text="assignData.wriNumber"></span>
                        </div>
                        <div class="font-bold text-slate-800 text-xs truncate mt-1" x-text="assignData.componentName"></div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-[10px] text-slate-400 font-mono block" x-text="assignData.partNumber"></span>
                        <span class="text-[10px] font-extrabold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200 inline-block mt-0.5" x-text="assignData.totalQty + ' ' + assignData.uom"></span>
                    </div>
                </div>

                <!-- Route Strip Alur Departemen -->
                <div class="mt-2.5 p-2 bg-slate-50/90 border border-slate-200/70 rounded-xl flex items-center justify-between gap-1.5">
                    <!-- Sisi Asal (Pengaju) -->
                    <div class="min-w-0 flex-1 flex items-center space-x-1.5 px-2.5 py-1 bg-white border border-slate-200/80 rounded-lg shadow-2xs">
                        <i class="fa-solid fa-location-dot text-slate-400 text-[10px] flex-shrink-0"></i>
                        <div class="min-w-0 flex-1">
                            <span class="text-[9px] text-slate-400 uppercase font-bold block leading-none">Pengaju</span>
                            <span class="text-[11px] font-bold text-slate-800 truncate block mt-0.5" x-text="assignData.deptPengaju || 'Gudang'"></span>
                        </div>
                    </div>

                    <!-- Indikator Transisi -->
                    <div class="w-5 h-5 rounded-full bg-slate-200/70 text-slate-500 flex items-center justify-center flex-shrink-0 text-[9px]">
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>

                    <!-- Sisi Tujuan (Penerima) -->
                    <div class="min-w-0 flex-1 flex items-center space-x-1.5 px-2.5 py-1 bg-blue-50/90 border border-blue-200/80 rounded-lg shadow-2xs">
                        <i class="fa-solid fa-bullseye text-blue-600 text-[10px] flex-shrink-0"></i>
                        <div class="min-w-0 flex-1">
                            <span class="text-[9px] text-blue-500 uppercase font-bold block leading-none">Tujuan</span>
                            <span class="text-[11px] font-extrabold text-blue-900 truncate block mt-0.5" x-text="assignData.deptPenerima || 'Mesin'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Approval & Operator Assignment (Minimalis Tanpa Notes) -->
            <form method="POST" :action="assignData.actionUrl" class="p-5 space-y-3.5 text-xs">
                @csrf

                <!-- Input Nama Operator Mesin Pelaksana (Combobox: Dropdown Pilihan + Ketik Bebas) -->
                <div>
                    <label class="block font-extrabold text-slate-700 uppercase tracking-wider text-[11px] mb-1.5">
                        Operator Pelaksana Mesin <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative" x-data="{ openDropdown: false }" @click.outside="openDropdown = false">
                        <div class="relative rounded-2xl border-2 border-slate-200 focus-within:border-blue-600 focus-within:bg-white focus-within:ring-4 focus-within:ring-blue-500/10 transition-all bg-slate-50/70 overflow-hidden cursor-pointer"
                             @click="openDropdown = !openDropdown">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-user text-xs"></i>
                            </div>
                            <input type="text" 
                                   name="operator_name" 
                                   x-model="assignData.operatorName" 
                                   required 
                                   @focus="openDropdown = true"
                                   @click.stop="openDropdown = true"
                                   placeholder="Pilih dari dropdown atau ketik nama..." 
                                   class="w-full pl-9 pr-9 py-2.5 text-xs font-bold text-slate-900 border-0 bg-transparent focus:ring-0 focus:outline-none placeholder:text-slate-400 font-sans">
                            <button type="button" 
                                    @click.stop="openDropdown = !openDropdown"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer transition-transform duration-200"
                                    :class="{ 'rotate-180': openDropdown }">
                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </button>
                        </div>

                        <!-- Dropdown Menu List -->
                        <div x-show="openDropdown" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute z-30 left-0 right-0 mt-1.5 bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden max-h-56 overflow-y-auto p-1.5 space-y-1 text-xs"
                             style="display: none;">
                            
                            <!-- Header Keterangan Bagian -->
                            <div class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                <span>Pilihan Operator (<span x-text="assignData.deptPenerima || 'Mesin'"></span>):</span>
                            </div>

                            <!-- Daftar Operator yang Terdaftar di Bagian Terkait -->
                            <template x-for="op in assignData.availableOperators" :key="op">
                                <button type="button" 
                                        @click="assignData.operatorName = op; openDropdown = false" 
                                        class="w-full text-left px-3 py-2 rounded-xl flex items-center justify-between transition cursor-pointer"
                                        :class="assignData.operatorName === op ? 'bg-blue-50 text-blue-900 font-extrabold' : 'hover:bg-slate-50 text-slate-700 font-bold'">
                                    <div class="flex items-center space-x-2 truncate">
                                        <i class="fa-solid fa-user-check text-[11px]" :class="assignData.operatorName === op ? 'text-blue-600' : 'text-slate-400'"></i>
                                        <span x-text="op" class="truncate"></span>
                                    </div>
                                    <template x-if="assignData.operatorName === op">
                                        <i class="fa-solid fa-check text-xs text-blue-600 shrink-0"></i>
                                    </template>
                                </button>
                            </template>

                            <!-- Jika Belum Ada Operator Terdaftar di Bagian Ini -->
                            <template x-if="!assignData.availableOperators || assignData.availableOperators.length === 0">
                                <div class="px-3 py-2 text-[11px] text-amber-700 bg-amber-50 rounded-xl font-medium">
                                    <i class="fa-solid fa-circle-info me-1"></i> Tidak ada operator terdaftar di bagian ini
                                </div>
                            </template>

                            <!-- Opsi Diri Sendiri (User Login) -->
                            <button type="button" 
                                    @click="assignData.operatorName = currentUserName; openDropdown = false" 
                                    class="w-full text-left px-3 py-2 rounded-xl flex items-center justify-between transition cursor-pointer border-t border-slate-100"
                                    :class="assignData.operatorName === currentUserName ? 'bg-emerald-50 text-emerald-900 font-extrabold' : 'hover:bg-slate-50 text-slate-700 font-bold'">
                                <div class="flex items-center space-x-2 truncate">
                                    <i class="fa-solid fa-circle-user text-[11px]" :class="assignData.operatorName === currentUserName ? 'text-emerald-600' : 'text-slate-400'"></i>
                                    <span class="truncate">Saya Sendiri (<strong x-text="currentUserName"></strong>)</span>
                                </div>
                                <template x-if="assignData.operatorName === currentUserName">
                                    <i class="fa-solid fa-check text-xs text-emerald-600 shrink-0"></i>
                                </template>
                            </button>
                        </div>
                    </div>

                    <p class="text-[10px] text-slate-400 mt-2">
                        <i class="fa-solid fa-circle-info me-1 text-blue-500"></i> Pilih nama dari dropdown di atas atau ketik manual jika ada operator pengganti.
                    </p>
                </div>

                <!-- Footer Tombol Aksi Simetris & Rapi -->
                <div class="pt-3 border-t border-slate-100 flex items-center gap-2">
                    <button type="button" 
                            @click="closeAssignModal()" 
                            class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition cursor-pointer shrink-0">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 py-2.5 px-4 bg-[#0a2342] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-md shadow-slate-900/10 transition inline-flex items-center justify-center gap-2 cursor-pointer active:scale-95 whitespace-nowrap">
                        <i class="fa-solid fa-check text-xs text-white shrink-0"></i>
                        <span>Verifikasi & Masukkan Antrian</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

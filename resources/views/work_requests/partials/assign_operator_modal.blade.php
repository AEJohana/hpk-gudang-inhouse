<!-- MODAL 3: VERIFIKASI PENERIMA & PENUGASAN OPERATOR MESIN (POP-UP) -->
<div x-show="assignModalOpen" 
     x-cloak 
     style="display: none;"
     :class="{ 'pointer-events-none': !assignModalOpen }"
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-assign-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur -->
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

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="assignModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
            
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 p-5 text-white flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-blue-400 text-lg flex-shrink-0">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-[10px] font-mono font-black uppercase text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20" x-text="assignData.wriNumber"></span>
                            <span class="text-[10px] font-bold text-blue-300 bg-blue-500/20 px-2 py-0.5 rounded">Verifikasi Ka. Mesin</span>
                        </div>
                        <h3 class="text-base font-extrabold text-white mt-1 leading-snug truncate max-w-xs sm:max-w-sm" x-text="assignData.componentName"></h3>
                        <p class="text-xs text-slate-300 font-mono" x-text="assignData.partNumber"></p>
                    </div>
                </div>
                <button type="button" 
                        @click="closeAssignModal()" 
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Tiket Summary Card -->
            <div class="bg-blue-50/70 border-b border-blue-100 p-3.5 flex items-center justify-between text-xs">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Dari Departemen</span>
                    <span class="font-bold text-slate-800" x-text="assignData.deptPengaju"></span>
                </div>
                <div class="text-blue-400 text-sm px-2">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-blue-600 uppercase block">Tujuan Pengerjaan</span>
                    <span class="font-bold text-blue-950" x-text="assignData.deptPenerima"></span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Kebutuhan Order</span>
                    <span class="font-extrabold text-slate-900" x-text="assignData.totalQty + ' ' + assignData.uom"></span>
                </div>
            </div>

            <!-- Form Approval & Operator Assignment -->
            <form method="POST" :action="assignData.actionUrl" class="p-5 space-y-4 text-xs">
                @csrf

                <!-- Input Nama Operator Mesin Pelaksana (Ketik Manual) -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px] mb-1.5">
                        Tugaskan Operator Pelaksana Mesin <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative rounded-2xl border-2 border-slate-300 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-500/20 overflow-hidden bg-white shadow-2xs">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-id-badge text-sm"></i>
                        </div>
                        <input type="text" 
                               name="operator_name" 
                               x-model="assignData.operatorName" 
                               required 
                               placeholder="Ketik nama operator (contoh: Fajar, Budi, Hendra...)" 
                               class="w-full pl-10 pr-4 py-2.5 text-sm font-bold text-slate-900 border-0 focus:ring-0 focus:outline-none">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">
                        <i class="fa-solid fa-circle-info me-1 text-blue-500"></i> Operator yang ditugaskan akan tercatat resmi pada tiket WRI dan stasiun kerja mesin.
                    </p>
                </div>

                <!-- Catatan / Instruksi Mesin (Opsional) -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1">
                        Catatan Kesanggupan / Jadwal Mesin <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                    </label>
                    <textarea name="notes" 
                              x-model="assignData.notes" 
                              rows="2" 
                              placeholder="Instruksi pengerjaan shift, jadwal pisau/dies, atau ketersediaan mesin..." 
                              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-medium"></textarea>
                </div>

                <!-- Footer Buttons -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <!-- Reject Form Trigger if needed -->
                    <div>
                        <button type="button" 
                                @click="closeAssignModal()" 
                                class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs transition flex items-center space-x-1.5 cursor-pointer">
                            <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                            <span>Kembali / Batal</span>
                        </button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="submit" 
                                class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md transition flex items-center space-x-1.5">
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>Verifikasi & Masukkan Antrian</span>
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

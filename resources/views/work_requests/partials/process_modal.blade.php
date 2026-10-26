<!-- MODAL POP-UP QUANTITY & STATUS WORK REQUEST (IN-PLACE POP-UP MINIMALIS & ERGONOMIS) -->
<div x-show="processModalOpen" 
     x-cloak 
     style="display: none;"
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur Lembut -->
    <div x-show="processModalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" 
         @click="closeProcessModal()"></div>

    <div class="flex min-h-full items-center justify-center p-3 text-center sm:p-0">
        <div x-show="processModalOpen" 
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
                        @click="closeProcessModal()" 
                        class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>

                <div class="flex items-center space-x-3 pr-8">
                    <!-- Soft Dynamic Circle Icon Badge -->
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg flex-shrink-0 transition-colors shadow-2xs"
                         :class="processData.nextStatus.includes('Sudah Diambil') ? 'bg-emerald-50 text-emerald-600 border border-emerald-200/70' : 
                                (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'bg-amber-50 text-amber-600 border border-amber-200/70' : 
                                (processData.nextStatus.includes('(In)') ? 'bg-teal-50 text-teal-600 border border-teal-200/70' : 'bg-indigo-50 text-indigo-600 border border-indigo-200/70'))">
                        <i :class="processData.nextStatus.includes('Sudah Diambil') ? 'fa-solid fa-warehouse' : 
                                  (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'fa-solid fa-truck-ramp-box' : 
                                  (processData.nextStatus.includes('(In)') ? 'fa-solid fa-play' : 'fa-solid fa-calculator'))"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-extrabold text-slate-900 leading-tight truncate"
                            x-text="processData.nextStatus.includes('Sudah Diambil') ? 'Penerimaan di Rak Gudang' : 
                                   (processData.nextStatus.includes('MH Pickup') ? 'Verifikasi Ambil Material (MH)' : 
                                   (processData.nextStatus.includes('Menuju Pengaju') ? 'Pengantaran ke Pengaju (MH)' : 
                                   (processData.nextStatus.includes('(In)') ? 'Mulai Pengerjaan Mesin' : 'Input Kuantitas Pengerjaan')))"></h3>
                        <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5"
                           x-text="processData.nextStatus.includes('Sudah Diambil') ? 'Konfirmasi stok fisik masuk ke rak gudang' : 
                                  (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'Serah terima barang oleh kurir Material Handling' : 
                                  (processData.nextStatus.includes('(In)') ? 'Kuantitas mesin dimulai secara penuh' : 'Catat hasil unit pengerjaan dan defect'))"></p>
                    </div>
                </div>

                <!-- Tiket & Info Komponen Ringkas -->
                <div class="mt-3 p-2.5 bg-slate-50 border border-slate-200/70 rounded-2xl flex items-center justify-between text-xs">
                    <div class="min-w-0 flex-1 pr-2">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-[10px] font-mono font-black uppercase text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200/60" x-text="processData.wriNumber"></span>
                            <template x-if="processData.assignedOperator">
                                <span class="text-[10px] text-slate-500 font-medium truncate">
                                    Op: <strong class="text-slate-800" x-text="processData.assignedOperator"></strong>
                                </span>
                            </template>
                        </div>
                        <div class="font-bold text-slate-800 text-xs truncate mt-1" x-text="processData.componentName"></div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-[10px] text-slate-400 font-mono block" x-text="processData.partNumber"></span>
                        <template x-if="processData.nextStatus.includes('Sudah Diambil') && processData.targetRack">
                            <span class="text-[10px] font-extrabold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                Rak: <span x-text="processData.targetRack"></span>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Breadcrumb Alur Mini Satu Baris -->
                <div class="mt-2 flex items-center justify-between text-[11px] px-1 text-slate-500">
                    <span class="truncate max-w-[45%]" x-text="processData.currentStatus || 'Dalam antrian'"></span>
                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 mx-1"></i>
                    <span class="font-extrabold truncate max-w-[50%]"
                          :class="processData.nextStatus.includes('Sudah Diambil') ? 'text-emerald-700' : 
                                 (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'text-amber-700' : 
                                 (processData.nextStatus.includes('(In)') ? 'text-teal-700' : 'text-indigo-700'))"
                          x-text="processData.nextStatus"></span>
                </div>
            </div>

            <!-- Form Body Kompak & Fungsional -->
            <form method="POST" :action="processData.actionUrl" class="p-5 pt-4 space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="action_type" value="process">
                <input type="hidden" name="target_status" :value="processData.nextStatus">
                <input type="hidden" name="operator_name" :value="processData.assignedOperator || '{{ auth()->user()->name }}'">

                <!-- KASUS A: PROSES MASUK / (In) - KUANTITAS TERKUNCI & BERSIH -->
                <template x-if="processData.nextStatus.includes('(In)')">
                    <div class="space-y-3">
                        <div class="p-4 bg-teal-50/70 border border-teal-200 rounded-2xl text-center space-y-1.5">
                            <span class="text-[10px] font-extrabold text-teal-800 uppercase tracking-wider block">Kuantitas Masuk Mesin</span>
                            <div class="flex items-center justify-center space-x-2">
                                <span class="text-3xl font-black text-teal-950 font-mono" x-text="processData.remainingQty"></span>
                                <span class="text-xs font-extrabold text-teal-700" x-text="processData.uom"></span>
                            </div>
                            <div class="inline-flex items-center space-x-1.5 px-3 py-1 bg-teal-100/90 text-teal-800 text-[10px] font-bold rounded-full">
                                <i class="fa-solid fa-lock text-[9px]"></i>
                                <span>Terkunci Penuh (Tahap Awal Pengerjaan)</span>
                            </div>
                            <input type="hidden" name="process_qty" :value="processData.remainingQty">
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed text-center px-2">
                            <i class="fa-solid fa-circle-info text-teal-600 me-0.5"></i> Seluruh unit diproses mesin. Input kuantitas hasil dan defect/reject diisi saat proses selesai (Out).
                        </p>
                    </div>
                </template>

                <!-- KASUS B/C/D: PROSES KELUAR (Out), MH PICKUP, PENERIMAAN GUDANG (HERO STEPPER & DEFECT) -->
                <template x-if="!processData.nextStatus.includes('(In)')">
                    <div class="space-y-3">
                        <!-- Hero Numeric Stepper -->
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <label class="font-extrabold text-slate-700 uppercase tracking-wider text-[11px]">
                                    <span x-text="processData.nextStatus.includes('Sudah Diambil') ? 'Unit Diterima Masuk Rak' : 
                                                 (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'Unit Diambil Kurir MH' : 'Unit Selesai Dikerjakan (Bagus)')"></span>
                                    <span class="text-rose-500">*</span>
                                </label>
                                
                                <!-- Shortcut Button 'Max' -->
                                <button type="button" 
                                        @click="setMaxProcessQty()" 
                                        class="px-2 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-[10px] border border-indigo-200/80 transition flex items-center space-x-1">
                                    <span>Semua:</span>
                                    <strong x-text="getMaxProcessQty()"></strong>
                                    <span x-text="processData.uom"></span>
                                </button>
                            </div>

                            <!-- Stepper Container (Touch-Friendly) -->
                            <div class="flex items-center rounded-2xl border-2 border-slate-200 bg-slate-50/70 focus-within:border-indigo-500 focus-within:bg-white focus-within:ring-4 focus-within:ring-indigo-500/10 transition-all p-1">
                                <!-- Stepper Minus Button -->
                                <button type="button" 
                                        @click="decrementProcessQty(1)" 
                                        :disabled="Number(processData.processQty) <= 0"
                                        class="w-11 h-11 rounded-xl bg-white hover:bg-slate-100 active:bg-slate-200 border border-slate-200 text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-sm font-bold shadow-2xs transition">
                                    <i class="fa-solid fa-minus"></i>
                                </button>

                                <!-- Direct Numeric Input (Hero) -->
                                <div class="flex-1 relative flex items-center justify-center">
                                    <input type="number" 
                                           name="process_qty" 
                                           x-model.number="processData.processQty" 
                                           required 
                                           min="0" 
                                           :max="getMaxProcessQty()"
                                           @input="if(Number(processData.processQty) > getMaxProcessQty()) processData.processQty = getMaxProcessQty()"
                                           step="any" 
                                           class="w-full text-center text-2xl sm:text-3xl font-black text-slate-900 border-0 bg-transparent focus:ring-0 focus:outline-none p-0 py-1 font-mono">
                                </div>

                                <span class="text-xs font-extrabold text-slate-400 font-mono pr-2 select-none" x-text="processData.uom"></span>

                                <!-- Stepper Plus Button -->
                                <button type="button" 
                                        @click="incrementProcessQty(1)" 
                                        :disabled="Number(processData.processQty) >= getMaxProcessQty()"
                                        class="w-11 h-11 rounded-xl bg-white hover:bg-slate-100 active:bg-slate-200 border border-slate-200 text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-sm font-bold shadow-2xs transition">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>

                            <!-- Micro Status & Calculation (Single Clean Line) -->
                            <div class="mt-1.5 flex items-center justify-between px-1 text-[11px] text-slate-500">
                                <span>Tersedia: <strong class="text-slate-800" x-text="(processData.availableQty || (processData.currentBatchQty > 0 ? processData.currentBatchQty : processData.remainingQty)) + ' ' + processData.uom"></strong></span>
                                <template x-if="processData.hasDefect && processData.defectQty > 0">
                                    <span class="text-rose-600 font-bold">
                                        Defect: <strong x-text="processData.defectQty"></strong>
                                    </span>
                                </template>
                                <template x-if="Number(processData.processQty) < getMaxProcessQty()">
                                    <span class="text-amber-600 font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-clock-rotate-left text-[10px]"></i> Parsial (Cicilan)
                                    </span>
                                </template>
                                <template x-if="Number(processData.processQty) >= getMaxProcessQty() && getMaxProcessQty() > 0">
                                    <span class="text-emerald-600 font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-check text-[10px]"></i> Kuantitas Penuh
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Smart Defect Toggle (Ramping & Bebas Rame) -->
                        <div class="pt-1 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <button type="button" 
                                        @click="toggleDefect()" 
                                        class="inline-flex items-center space-x-1.5 text-[11px] font-bold transition px-2.5 py-1 rounded-xl"
                                        :class="processData.hasDefect ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'text-slate-600 hover:text-rose-600 hover:bg-rose-50 border border-dashed border-slate-300'">
                                    <i :class="processData.hasDefect ? 'fa-solid fa-circle-xmark text-rose-600' : 'fa-solid fa-triangle-exclamation text-amber-500'"></i>
                                    <span x-text="processData.hasDefect ? 'Tutup Laporan Defect' : '+ Ada Defect / Rusak? (Opsional)'"></span>
                                </button>
                            </div>

                            <!-- Expandable Defect Form -->
                            <div x-show="processData.hasDefect" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-2"
                                 class="mt-2 p-3 bg-rose-50/70 border border-rose-200 rounded-2xl space-y-2">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label class="block font-bold text-rose-900 text-[10px] uppercase tracking-wider mb-1">
                                            Jumlah Defect (<span x-text="processData.uom"></span>) <span class="text-rose-600">*</span>
                                        </label>
                                        <input type="number" 
                                               name="defect_qty" 
                                               x-model.number="processData.defectQty" 
                                               @input="syncProcessQtyWithDefect()"
                                               min="0.01" 
                                               :max="processData.availableQty || (processData.currentBatchQty > 0 ? processData.currentBatchQty : processData.remainingQty)"
                                               step="any" 
                                               placeholder="0" 
                                               class="w-full px-2.5 py-1.5 text-xs bg-white border border-rose-300 rounded-xl font-bold text-rose-950 focus:ring-2 focus:ring-rose-500 text-center font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-rose-900 text-[10px] uppercase tracking-wider mb-1">
                                            Alasan Defect <span class="text-rose-600">*</span>
                                        </label>
                                        <input type="text" 
                                               name="defect_reason" 
                                               x-model="processData.defectReason" 
                                               placeholder="Contoh: Salah potong / retak" 
                                               class="w-full px-2.5 py-1.5 text-xs bg-white border border-rose-300 rounded-xl font-medium text-slate-800 focus:ring-2 focus:ring-rose-500">
                                    </div>
                                </div>
                                <p class="text-[10px] text-rose-700 leading-tight">
                                    <i class="fa-solid fa-info-circle me-1"></i> Defect akan otomatis memotong kuota total tiket dan dicatat di log audit.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Catatan Proses / Shift (Opsional & Bersih) -->
                <div>
                    <label class="block font-bold text-slate-500 uppercase tracking-wider text-[10px] mb-1">
                        Catatan Proses <span class="font-normal text-slate-400 lowercase">(opsional)</span>
                    </label>
                    <input type="text" 
                           name="notes" 
                           x-model="processData.notes" 
                           placeholder="Catatan pengerjaan mesin, shift, atau kondisi material..." 
                           class="w-full px-3 py-2 text-xs border border-slate-200 bg-slate-50 focus:bg-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium text-slate-800 transition">
                </div>

                <!-- Footer Tombol Aksi Simetris -->
                <div class="pt-2 border-t border-slate-100 flex items-center space-x-2">
                    <button type="button" 
                            @click="closeProcessModal()" 
                            class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition flex items-center justify-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                        <span>Kembali / Batal</span>
                    </button>
                    <button type="submit" 
                            class="flex-1 py-2.5 rounded-xl text-white font-extrabold text-xs shadow-md transition flex items-center justify-center space-x-1.5"
                            :class="processData.nextStatus.includes('Sudah Diambil') ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20' : 
                                   (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'bg-amber-500 hover:bg-amber-600 text-slate-950 font-black shadow-amber-500/20' : 
                                   (processData.nextStatus.includes('(In)') ? 'bg-teal-600 hover:bg-teal-700 shadow-teal-500/20' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-500/20'))">
                        <i :class="processData.nextStatus.includes('Sudah Diambil') ? 'fa-solid fa-warehouse' : 
                                  (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'fa-solid fa-truck-ramp-box' : 
                                  (processData.nextStatus.includes('(In)') ? 'fa-solid fa-play' : 'fa-solid fa-check'))"></i>
                        <span x-text="processData.nextStatus.includes('Sudah Diambil') ? 'Terima di Gudang' : 
                                     (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'Konfirmasi Ambil MH' : 
                                     (processData.nextStatus.includes('(In)') ? 'Mulai Mesin' : 'Simpan & Lanjutkan'))"></span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

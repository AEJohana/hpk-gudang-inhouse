<!-- MODAL 1: UPDATE PROSES WORK REQUEST (IN-PLACE POP-UP ADAPTIF) -->
<div x-show="processModalOpen" 
     x-cloak 
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur -->
    <div x-show="processModalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" 
         @click="closeProcessModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="processModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
            
            <!-- Modal Header (Dynamic Accent Based on Action) -->
            <div class="p-5 text-white flex items-start justify-between"
                 :class="processData.nextStatus.includes('Sudah Diambil') ? 'bg-gradient-to-r from-emerald-950 via-slate-900 to-emerald-950' : 
                        (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'bg-gradient-to-r from-amber-950 via-slate-900 to-amber-950' : 
                        (processData.nextStatus.includes('(In)') ? 'bg-gradient-to-r from-teal-950 via-slate-900 to-teal-950' : 'bg-gradient-to-r from-indigo-950 via-slate-900 to-indigo-950'))">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-lg flex-shrink-0"
                         :class="processData.nextStatus.includes('Sudah Diambil') ? 'text-emerald-400' : 
                                (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'text-amber-400' : 
                                (processData.nextStatus.includes('(In)') ? 'text-teal-400' : 'text-indigo-400'))">
                        <i :class="processData.nextStatus.includes('Sudah Diambil') ? 'fa-solid fa-warehouse' : 
                                  (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'fa-solid fa-truck-ramp-box' : 
                                  (processData.nextStatus.includes('(In)') ? 'fa-solid fa-play' : 'fa-solid fa-check'))"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-[10px] font-mono font-black uppercase text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20" x-text="processData.wriNumber"></span>
                            <template x-if="processData.assignedOperator">
                                <span class="text-[10px] font-semibold text-slate-300">
                                    Operator: <strong class="text-white" x-text="processData.assignedOperator"></strong>
                                </span>
                            </template>
                        </div>
                        <h3 class="text-base font-extrabold text-white mt-1 leading-snug truncate max-w-xs sm:max-w-sm" x-text="processData.componentName"></h3>
                        <p class="text-xs text-slate-300 font-mono" x-text="processData.partNumber"></p>
                    </div>
                </div>
                <button type="button" 
                        @click="closeProcessModal()" 
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Status Transition Banner -->
            <div class="border-b p-3.5 flex items-center justify-between text-xs"
                 :class="processData.nextStatus.includes('Sudah Diambil') ? 'bg-emerald-50/70 border-emerald-100' : 
                        (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'bg-amber-50/70 border-amber-100' : 
                        (processData.nextStatus.includes('(In)') ? 'bg-teal-50/70 border-teal-100' : 'bg-indigo-50/70 border-indigo-100'))">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Status Saat Ini</span>
                    <span class="font-bold text-slate-800" x-text="processData.currentStatus || 'Dalam antrian'"></span>
                </div>
                <div class="text-slate-400 text-sm px-2">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold uppercase block"
                          :class="processData.nextStatus.includes('Sudah Diambil') ? 'text-emerald-600' : 
                                 (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'text-amber-600' : 
                                 (processData.nextStatus.includes('(In)') ? 'text-teal-600' : 'text-indigo-600'))">Langkah Tujuan</span>
                    <span class="font-extrabold px-2 py-0.5 rounded-lg border text-xs"
                          :class="processData.nextStatus.includes('Sudah Diambil') ? 'text-emerald-950 bg-emerald-200/60 border-emerald-300' : 
                                 (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'text-amber-950 bg-amber-200/60 border-amber-300' : 
                                 (processData.nextStatus.includes('(In)') ? 'text-teal-950 bg-teal-200/60 border-teal-300' : 'text-indigo-950 bg-indigo-200/60 border-indigo-300'))"
                          x-text="processData.nextStatus"></span>
                </div>
            </div>

            <!-- Form Process Action -->
            <form method="POST" :action="processData.actionUrl" class="p-5 space-y-4 text-xs">
                @csrf
                <input type="hidden" name="action_type" value="process">
                <input type="hidden" name="target_status" :value="processData.nextStatus">
                <input type="hidden" name="operator_name" :value="processData.assignedOperator || '{{ auth()->user()->name }}'">

                <!-- KASUS A: PROSES MASUK / IN (KUANTITAS TERKUNCI & TANPA DEFECT) -->
                <template x-if="processData.nextStatus.includes('(In)')">
                    <div class="space-y-3">
                        <div class="p-3 bg-teal-50 border border-teal-200 rounded-2xl">
                            <div class="flex items-center justify-between text-xs font-bold text-teal-900 mb-1">
                                <span>Kuantitas yang Diproses Mesin (Terkunci)</span>
                                <span>Tahap: Mulai Pengerjaan</span>
                            </div>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-teal-200/60">
                                <span class="text-2xl font-black text-teal-950" x-text="processData.remainingQty + ' ' + processData.uom"></span>
                                <span class="px-2.5 py-1 bg-teal-100 text-teal-800 rounded-lg text-[10px] font-bold">
                                    <i class="fa-solid fa-lock me-1"></i> Terkunci Penuh
                                </span>
                            </div>
                            <input type="hidden" name="process_qty" :value="processData.remainingQty">
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            <i class="fa-solid fa-circle-info text-teal-600 me-1"></i> Proses awal mesin akan memproses seluruh kuantitas yang dibutuhkan. Input kuantitas hasil dan defect/reject dilakukan saat mesin selesai (Out).
                        </p>
                    </div>
                </template>

                <!-- KASUS B: PROSES KELUAR / OUT & SELESAI MESIN (INPUT QTY KELUAR, PARSIAL & DEFECT) -->
                <template x-if="processData.nextStatus.includes('(Out)') || processData.nextStatus.includes('Siap Diambil') || (!processData.nextStatus.includes('(In)') && !processData.nextStatus.includes('MH Pickup') && !processData.nextStatus.includes('Menuju Pengaju') && !processData.nextStatus.includes('Sudah Diambil'))">
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px]">
                                    Jumlah Unit yang Selesai Dikerjakan <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] text-slate-500">
                                    Sisa di Mesin: <strong class="text-slate-800" x-text="processData.remainingQty + ' ' + processData.uom"></strong>
                                </span>
                            </div>

                            <div class="relative flex items-stretch rounded-2xl border-2 border-slate-300 focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-500/20 overflow-hidden bg-white shadow-2xs">
                                <input type="number" 
                                       name="process_qty" 
                                       x-model.number="processData.processQty" 
                                       required 
                                       min="0" 
                                       :max="getMaxProcessQty()"
                                       @input="if(Number(processData.processQty) > getMaxProcessQty()) processData.processQty = getMaxProcessQty()"
                                       step="any" 
                                       class="w-full min-w-0 flex-1 px-4 py-2.5 text-lg font-black text-slate-900 border-0 focus:ring-0 focus:outline-none text-center">
                                <span class="inline-flex items-center px-4 py-2.5 bg-slate-100 text-slate-700 font-extrabold text-xs border-l border-slate-200 select-none whitespace-nowrap" 
                                      x-text="processData.uom"></span>
                            </div>

                            <!-- Reactive Allocation Card (Mesin) -->
                            <div class="mt-2.5 p-3 bg-indigo-50/80 border border-indigo-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                <div>
                                    <span class="text-[10px] text-indigo-700 font-extrabold uppercase tracking-wider block">Kalkulasi Fisik Mesin:</span>
                                    <span class="font-bold text-slate-800">
                                        Sisa di Mesin: <b class="text-indigo-950 font-black" x-text="processData.remainingQty"></b> | 
                                        Defect: <b class="text-rose-600 font-black" x-text="processData.hasDefect && processData.defectQty > 0 ? processData.defectQty : 0"></b>
                                    </span>
                                </div>
                                <div class="sm:text-right">
                                    <span class="text-[10px] text-indigo-700 font-extrabold uppercase tracking-wider block">Batas Maksimal Unit Bagus:</span>
                                    <span class="text-sm font-black text-indigo-700" x-text="getMaxProcessQty() + ' ' + processData.uom"></span>
                                </div>
                            </div>

                            <!-- Dynamic Helper Notes -->
                            <div class="mt-2 text-[11px]">
                                <template x-if="((Number(processData.processQty) || 0) + (processData.hasDefect && processData.defectQty > 0 ? Number(processData.defectQty) : 0)) < processData.remainingQty">
                                    <div class="p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 flex items-start space-x-2">
                                        <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                                        <span>Pengerjaan parsial (cicilan): Total diproses <strong x-text="((Number(processData.processQty) || 0) + (processData.hasDefect && processData.defectQty > 0 ? Number(processData.defectQty) : 0))"></strong> dari <strong x-text="processData.remainingQty"></strong> PCS. Sisa unit (<strong x-text="Math.max(0, processData.remainingQty - (Number(processData.processQty) || 0) - (processData.hasDefect && processData.defectQty > 0 ? Number(processData.defectQty) : 0))"></strong> PCS) tetap dalam proses mesin.</span>
                                    </div>
                                </template>
                                <template x-if="((Number(processData.processQty) || 0) + (processData.hasDefect && processData.defectQty > 0 ? Number(processData.defectQty) : 0)) >= processData.remainingQty">
                                    <div class="p-2 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center space-x-2">
                                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                        <span>Kuantitas penuh. Seluruh unit (<strong x-text="processData.remainingQty"></strong>) tuntas diproses (<strong x-text="processData.processQty || 0"></strong> unit bagus + <strong x-text="processData.hasDefect && processData.defectQty > 0 ? processData.defectQty : 0"></strong> reject). Tahapan mesin akan selesai dan berhenti.</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Toggle Defect / Reject -->
                        <div class="pt-2 border-t border-slate-100">
                            <label class="flex items-center space-x-2.5 cursor-pointer p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
                                <input type="checkbox" 
                                       x-model="processData.hasDefect" 
                                       @change="syncProcessQtyWithDefect()"
                                       class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <div class="flex items-center space-x-1.5 text-xs font-bold text-slate-700">
                                    <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                                    <span>Terdapat Komponen Rusak / Defect pada mesin?</span>
                                </div>
                            </label>

                            <!-- Sub-form Defect -->
                            <div x-show="processData.hasDefect" 
                                 x-transition 
                                 class="mt-2.5 p-3.5 bg-rose-50/70 border border-rose-200 rounded-2xl space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-bold text-rose-900 uppercase tracking-wider text-[10px] mb-1">
                                            Jumlah Defect (<span x-text="processData.uom"></span>) <span class="text-rose-600">*</span>
                                        </label>
                                        <input type="number" 
                                               name="defect_qty" 
                                               x-model.number="processData.defectQty" 
                                               @input="syncProcessQtyWithDefect()"
                                               min="0.01" 
                                               :max="processData.remainingQty"
                                               step="any" 
                                               placeholder="0"
                                               class="w-full px-3 py-1.5 text-xs border border-rose-300 rounded-xl focus:ring-2 focus:ring-rose-500 font-bold text-rose-950">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-rose-900 uppercase tracking-wider text-[10px] mb-1">
                                            Alasan Defect <span class="text-rose-600">*</span>
                                        </label>
                                        <input type="text" 
                                               name="defect_reason" 
                                               x-model="processData.defectReason" 
                                               placeholder="Contoh: Plat gompal / salah potong" 
                                               class="w-full px-3 py-1.5 text-xs border border-rose-300 rounded-xl focus:ring-2 focus:ring-rose-500 font-semibold text-slate-800">
                                    </div>
                                </div>
                                <p class="text-[10px] text-rose-700 leading-relaxed">
                                    <i class="fa-solid fa-info-circle me-1"></i> Defect akan memotong kuota total order tiket dan dicatat permanen pada log audit.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- KASUS C: LOGISTIK MATERIAL HANDLING (PICKUP & PENGANTARAN) -->
                <template x-if="processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju')">
                    <div class="space-y-4">
                        <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl">
                            <div class="flex items-center justify-between text-xs font-bold text-amber-900 mb-1">
                                <span>Verifikasi Fisik Penjemputan MH</span>
                                <span>Logistik Karoseri</span>
                            </div>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-amber-200/60">
                                <div>
                                    <span class="text-[10px] text-amber-800 block">Tercatat Siap di Mesin:</span>
                                    <span class="text-xl font-black text-amber-950" x-text="(processData.currentBatchQty > 0 ? processData.currentBatchQty : processData.remainingQty) + ' ' + processData.uom"></span>
                                </div>
                                <span class="px-2.5 py-1 bg-amber-200/80 text-amber-950 rounded-lg text-[10px] font-black">
                                    <i class="fa-solid fa-truck-ramp-box me-1"></i> Verifikasi MH
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px]">
                                    Jumlah Fisik Bagus yang Diambil Kurir MH <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] text-slate-500">
                                    Batas Maksimal: <strong class="text-slate-800" x-text="getMaxProcessQty() + ' ' + processData.uom"></strong>
                                </span>
                            </div>

                            <div class="relative flex items-stretch rounded-2xl border-2 border-slate-300 focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-400/20 overflow-hidden bg-white shadow-2xs">
                                <input type="number" 
                                       name="process_qty" 
                                       x-model.number="processData.processQty" 
                                       required 
                                       min="0" 
                                       :max="getMaxProcessQty()"
                                       @input="if(Number(processData.processQty) > getMaxProcessQty()) processData.processQty = getMaxProcessQty()"
                                       step="any" 
                                       class="w-full min-w-0 flex-1 px-4 py-2.5 text-lg font-black text-slate-900 border-0 focus:ring-0 focus:outline-none text-center">
                                <span class="inline-flex items-center px-4 py-2.5 bg-slate-100 text-slate-700 font-extrabold text-xs border-l border-slate-200 select-none whitespace-nowrap" 
                                      x-text="processData.uom"></span>
                            </div>

                            <!-- Reactive Allocation Card (MH) -->
                            <div class="mt-2.5 p-3 bg-amber-50/80 border border-amber-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                <div>
                                    <span class="text-[10px] text-amber-800 font-extrabold uppercase tracking-wider block">Kalkulasi Fisik Serah Terima MH:</span>
                                    <span class="font-bold text-slate-800">
                                        Siap di Mesin: <b class="text-amber-950 font-black" x-text="processData.currentBatchQty > 0 ? processData.currentBatchQty : processData.remainingQty"></b> | 
                                        Defect/Hilang: <b class="text-rose-600 font-black" x-text="processData.hasDefect && processData.defectQty > 0 ? processData.defectQty : 0"></b>
                                    </span>
                                </div>
                                <div class="sm:text-right">
                                    <span class="text-[10px] text-amber-800 font-extrabold uppercase tracking-wider block">Batas Maksimal Diangkut:</span>
                                    <span class="text-sm font-black text-amber-700" x-text="getMaxProcessQty() + ' ' + processData.uom"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Toggle Defect / Barang Hilang saat Pickup MH -->
                        <div class="pt-2 border-t border-slate-100">
                            <label class="flex items-center space-x-2.5 cursor-pointer p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
                                <input type="checkbox" 
                                       x-model="processData.hasDefect" 
                                       @change="syncProcessQtyWithDefect()"
                                       class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <div class="flex items-center space-x-1.5 text-xs font-bold text-slate-700">
                                    <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                                    <span>Ada Barang Cacat / Hilang / Selisih saat Penjemputan?</span>
                                </div>
                            </label>

                            <!-- Sub-form Defect MH -->
                            <div x-show="processData.hasDefect" 
                                 x-transition 
                                 class="mt-2.5 p-3.5 bg-rose-50/70 border border-rose-200 rounded-2xl space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-bold text-rose-900 uppercase tracking-wider text-[10px] mb-1">
                                            Jumlah Defect / Hilang (<span x-text="processData.uom"></span>) <span class="text-rose-600">*</span>
                                        </label>
                                        <input type="number" 
                                               name="defect_qty" 
                                               x-model.number="processData.defectQty" 
                                               @input="syncProcessQtyWithDefect()"
                                               min="0.01" 
                                               :max="processData.currentBatchQty > 0 ? processData.currentBatchQty : processData.remainingQty"
                                               step="any" 
                                               placeholder="0"
                                               class="w-full px-3 py-1.5 text-xs border border-rose-300 rounded-xl focus:ring-2 focus:ring-rose-500 font-bold text-rose-950">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-rose-900 uppercase tracking-wider text-[10px] mb-1">
                                            Alasan Selisih / Defect <span class="text-rose-600">*</span>
                                        </label>
                                        <input type="text" 
                                               name="defect_reason" 
                                               x-model="processData.defectReason" 
                                               placeholder="Contoh: 1 unit hilang / rusak saat serah terima mesin" 
                                               class="w-full px-3 py-1.5 text-xs border border-rose-300 rounded-xl focus:ring-2 focus:ring-rose-500 font-semibold text-slate-800">
                                    </div>
                                </div>
                                <p class="text-[10px] text-rose-700 leading-relaxed">
                                    <i class="fa-solid fa-info-circle me-1"></i> Barang hilang/defect akan otomatis memotong kuota order tiket dan tercatat dalam audit log serah terima.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- KASUS D: PENERIMAAN FISIK DI GUDANG (SUDAH DIAMBIL) -->
                <template x-if="processData.nextStatus.includes('Sudah Diambil')">
                    <div class="space-y-4">
                        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl">
                            <div class="flex items-center justify-between text-xs font-bold text-emerald-900 mb-1">
                                <span>Verifikasi Penerimaan Fisik di Rak Gudang</span>
                                <span class="text-emerald-700 font-mono font-extrabold" x-text="processData.targetRack"></span>
                            </div>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-emerald-200/60">
                                <div>
                                    <span class="text-[10px] text-emerald-800 block">Kuantitas Dikirim oleh MH:</span>
                                    <span class="text-xl font-black text-emerald-950" x-text="(processData.currentBatchQty > 0 ? processData.currentBatchQty : processData.remainingQty) + ' ' + processData.uom"></span>
                                </div>
                                <span class="px-2.5 py-1 bg-emerald-200/80 text-emerald-950 rounded-lg text-[10px] font-black">
                                    <i class="fa-solid fa-warehouse me-1"></i> Masuk Rak
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px]">
                                    Jumlah Unit Bagus yang Diterima Masuk Rak <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] text-slate-500">
                                    Batas Maksimal: <strong class="text-slate-800" x-text="getMaxProcessQty() + ' ' + processData.uom"></strong>
                                </span>
                            </div>

                            <div class="relative flex items-stretch rounded-2xl border-2 border-slate-300 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-500/20 overflow-hidden bg-white shadow-2xs">
                                <input type="number" 
                                       name="process_qty" 
                                       x-model.number="processData.processQty" 
                                       required 
                                       min="0" 
                                       :max="getMaxProcessQty()"
                                       @input="if(Number(processData.processQty) > getMaxProcessQty()) processData.processQty = getMaxProcessQty()"
                                       step="any" 
                                       class="w-full min-w-0 flex-1 px-4 py-2.5 text-lg font-black text-slate-900 border-0 focus:ring-0 focus:outline-none text-center">
                                <span class="inline-flex items-center px-4 py-2.5 bg-slate-100 text-slate-700 font-extrabold text-xs border-l border-slate-200 select-none whitespace-nowrap" 
                                      x-text="processData.uom"></span>
                            </div>

                            <!-- Reactive Allocation Card (Gudang) -->
                            <div class="mt-2.5 p-3 bg-emerald-50/80 border border-emerald-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                <div>
                                    <span class="text-[10px] text-emerald-800 font-extrabold uppercase tracking-wider block">Kalkulasi Masuk Rak Gudang:</span>
                                    <span class="font-bold text-slate-800">
                                        Dikirim Kurir MH: <b class="text-emerald-950 font-black" x-text="processData.currentBatchQty > 0 ? processData.currentBatchQty : processData.remainingQty"></b> | 
                                        Defect Transit: <b class="text-rose-600 font-black" x-text="processData.hasDefect && processData.defectQty > 0 ? processData.defectQty : 0"></b>
                                    </span>
                                </div>
                                <div class="sm:text-right">
                                    <span class="text-[10px] text-emerald-800 font-extrabold uppercase tracking-wider block">Batas Maksimal Masuk Rak:</span>
                                    <span class="text-sm font-black text-emerald-700" x-text="getMaxProcessQty() + ' ' + processData.uom"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Toggle Defect / Barang Rusak saat Tiba di Gudang -->
                        <div class="pt-2 border-t border-slate-100">
                            <label class="flex items-center space-x-2.5 cursor-pointer p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
                                <input type="checkbox" 
                                       x-model="processData.hasDefect" 
                                       @change="syncProcessQtyWithDefect()"
                                       class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <div class="flex items-center space-x-1.5 text-xs font-bold text-slate-700">
                                    <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                                    <span>Terdapat Komponen Rusak / Defect saat Tiba di Gudang?</span>
                                </div>
                            </label>

                            <!-- Sub-form Defect Gudang -->
                            <div x-show="processData.hasDefect" 
                                 x-transition 
                                 class="mt-2.5 p-3.5 bg-rose-50/70 border border-rose-200 rounded-2xl space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-bold text-rose-900 uppercase tracking-wider text-[10px] mb-1">
                                            Jumlah Defect (<span x-text="processData.uom"></span>) <span class="text-rose-600">*</span>
                                        </label>
                                        <input type="number" 
                                               name="defect_qty" 
                                               x-model.number="processData.defectQty" 
                                               @input="syncProcessQtyWithDefect()"
                                               min="0.01" 
                                               :max="processData.currentBatchQty > 0 ? processData.currentBatchQty : processData.remainingQty"
                                               step="any" 
                                               placeholder="0"
                                               class="w-full px-3 py-1.5 text-xs border border-rose-300 rounded-xl focus:ring-2 focus:ring-rose-500 font-bold text-rose-950">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-rose-900 uppercase tracking-wider text-[10px] mb-1">
                                            Alasan Kerusakan / Defect <span class="text-rose-600">*</span>
                                        </label>
                                        <input type="text" 
                                               name="defect_reason" 
                                               x-model="processData.defectReason" 
                                               placeholder="Contoh: Terbentur saat pengangkutan troli MH" 
                                               class="w-full px-3 py-1.5 text-xs border border-rose-300 rounded-xl focus:ring-2 focus:ring-rose-500 font-semibold text-slate-800">
                                    </div>
                                </div>
                                <p class="text-[10px] text-rose-700 leading-relaxed">
                                    <i class="fa-solid fa-info-circle me-1"></i> Hanya unit bagus yang akan menambah stok rak. Unit defect akan memotong kuota tiket secara permanen.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Catatan Pengerjaan (Opsional) -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1">
                        Catatan Proses <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                    </label>
                    <textarea name="notes" 
                              x-model="processData.notes" 
                              rows="2" 
                              placeholder="Catatan pengerjaan mesin, shift operator, atau kondisi barang..." 
                              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium"></textarea>
                </div>

                <!-- Footer Buttons -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" 
                            @click="closeProcessModal()" 
                            class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 rounded-xl text-white font-extrabold text-xs shadow-md transition flex items-center space-x-1.5"
                            :class="processData.nextStatus.includes('Sudah Diambil') ? 'bg-emerald-600 hover:bg-emerald-700' : 
                                   (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'bg-amber-500 hover:bg-amber-600 text-slate-950 font-black' : 
                                   (processData.nextStatus.includes('(In)') ? 'bg-teal-600 hover:bg-teal-700' : 'bg-indigo-600 hover:bg-indigo-700'))">
                        <i :class="processData.nextStatus.includes('Sudah Diambil') ? 'fa-solid fa-warehouse text-xs' : 
                                  (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'fa-solid fa-truck-ramp-box text-xs' : 
                                  (processData.nextStatus.includes('(In)') ? 'fa-solid fa-play text-xs' : 'fa-solid fa-check text-xs'))"></i>
                        <span x-text="processData.nextStatus.includes('Sudah Diambil') ? 'Terima Fisik di Gudang' : 
                                     (processData.nextStatus.includes('MH Pickup') || processData.nextStatus.includes('Menuju Pengaju') ? 'Konfirmasi Ambil & Antar MH' : 
                                     (processData.nextStatus.includes('(In)') ? 'Mulai Pengerjaan Mesin' : 'Selesai & Lanjutkan Langkah'))"></span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

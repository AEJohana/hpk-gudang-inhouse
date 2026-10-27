<!-- MODAL 2: DETAIL TRACKING TIMELINE & RIWAYAT (IN-PLACE POP-UP) -->
<div x-show="detailModalOpen" 
     x-cloak 
     style="display: none;"
     :class="{ 'pointer-events-none': !detailModalOpen }"
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur -->
    <div x-show="detailModalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         :class="{ 'pointer-events-none': !detailModalOpen }"
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" 
         @click="closeDetailModal()"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
        <div x-show="detailModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-3xl bg-slate-100 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200 flex flex-col max-h-[92vh]">
            
            <!-- Top Header Action Bar -->
            <div class="px-5 py-3 bg-white border-b border-slate-200/80 flex items-center justify-between flex-shrink-0 z-20">
                <button type="button" 
                        @click="closeDetailModal()" 
                        class="px-2.5 py-1 text-xs font-bold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl border border-slate-200 transition flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                    <span>Kembali</span>
                </button>
                <div class="flex items-center space-x-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Detail Pelacakan Tiket</span>
                    <button type="button" 
                            @click="closeDetailModal()" 
                            class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition"
                            title="Tutup">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Scrollable Content Body -->
            <div class="p-5 overflow-y-auto space-y-4 text-xs text-slate-700">
                
                <!-- 1. HERO STATUS BADGE (Top Center - Lampiran 1-3 Style) -->
                <div class="pt-2 pb-1 text-center">
                    <!-- Icon Bulat Status -->
                    <template x-if="detailData.approvalPengajuStatus === 'Menunggu' || detailData.approvalPenerimaStatus === 'Menunggu' || (detailData.currentStatus && detailData.currentStatus.toLowerCase().includes('antrian'))">
                        <div class="inline-block">
                            <div class="w-16 h-16 rounded-full bg-amber-100 border-4 border-amber-50 text-amber-500 flex items-center justify-center text-2xl shadow-sm mx-auto">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <h3 class="font-extrabold text-base sm:text-lg text-slate-900 mt-2">Belum Verifikasi</h3>
                            <p class="text-[11px] text-slate-400 font-medium">Status saat ini</p>
                        </div>
                    </template>

                    <template x-if="detailData.currentStatus && detailData.currentStatus.toLowerCase().includes('siap diambil')">
                        <div class="inline-block">
                            <div class="w-16 h-16 rounded-full bg-emerald-100 border-4 border-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shadow-sm mx-auto">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <h3 class="font-extrabold text-base sm:text-lg text-slate-900 mt-2">Siap diambil</h3>
                            <p class="text-[11px] text-slate-400 font-medium">Selesai & Siap diambil</p>
                        </div>
                    </template>

                    <template x-if="detailData.isFinal || (detailData.currentStatus && detailData.currentStatus.toLowerCase().includes('sudah diambil'))">
                        <div class="inline-block">
                            <div class="w-16 h-16 rounded-full bg-emerald-600 border-4 border-emerald-100 text-white flex items-center justify-center text-2xl shadow-sm mx-auto">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <h3 class="font-extrabold text-base sm:text-lg text-slate-900 mt-2">Sudah diambil</h3>
                            <p class="text-[11px] text-slate-400 font-medium">Telah diambil oleh Pengaju / Diterima Gudang</p>
                        </div>
                    </template>

                    <template x-if="detailData.currentStatus && detailData.currentStatus.toLowerCase().includes('menuju pengaju')">
                        <div class="inline-block">
                            <div class="w-16 h-16 rounded-full bg-purple-100 border-4 border-purple-50 text-purple-600 flex items-center justify-center text-2xl shadow-sm mx-auto">
                                <i class="fa-solid fa-truck-fast"></i>
                            </div>
                            <h3 class="font-extrabold text-base sm:text-lg text-slate-900 mt-2">Sedang Diantar MH</h3>
                            <p class="text-[11px] text-slate-400 font-medium">Dalam perjalanan menuju Pengaju / Gudang</p>
                        </div>
                    </template>

                    <template x-if="detailData.currentStatus && (detailData.currentStatus.toLowerCase().includes('proses:') || detailData.currentStatus.toLowerCase().includes('in_production')) && !detailData.currentStatus.toLowerCase().includes('siap diambil') && !detailData.currentStatus.toLowerCase().includes('sudah diambil')">
                        <div class="inline-block">
                            <div class="w-16 h-16 rounded-full bg-blue-100 border-4 border-blue-50 text-blue-600 flex items-center justify-center text-2xl shadow-sm mx-auto">
                                <i class="fa-solid fa-gear fa-spin"></i>
                            </div>
                            <h3 class="font-extrabold text-base sm:text-lg text-slate-900 mt-2" x-text="detailData.currentStatus"></h3>
                            <p class="text-[11px] text-slate-400 font-medium">Sedang dalam proses pengerjaan mesin</p>
                        </div>
                    </template>
                </div>

                <!-- 2. IDENTITAS TIKET, QR CODE & DUAL VERIFIKATOR (Lampiran 1-3 Style) -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs space-y-3">
                    <div class="flex items-start gap-3.5">
                        <!-- QR Code Section (Left) -->
                        <div class="bg-slate-50 p-1.5 rounded-xl border border-slate-200 flex items-center justify-center shadow-2xs shrink-0 w-24 h-24 sm:w-28 sm:h-28 cursor-pointer hover:border-amber-400 hover:shadow-md transition group relative"
                             @click="openQrZoom(detailData.qrUrl, detailData.wriNumber, detailData.componentName)"
                             title="Klik untuk memperbesar QR Code">
                            <img :src="detailData.qrUrl" alt="QR Tiket" class="w-full h-full object-contain group-hover:scale-105 transition">
                            <div class="absolute inset-0 bg-slate-900/10 opacity-0 group-hover:opacity-100 rounded-xl transition flex items-center justify-center">
                                <i class="fa-solid fa-magnifying-glass-plus text-slate-800 text-xs bg-white/90 p-1 rounded-full shadow-xs"></i>
                            </div>
                        </div>

                        <!-- Details Section (Right) -->
                        <div class="flex-grow overflow-hidden">
                            <div class="border-b border-slate-100 pb-2 mb-2">
                                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-400">No Work Request</div>
                                <div class="font-mono font-bold text-slate-900 text-xs sm:text-sm break-all leading-snug" x-text="detailData.wriNumber"></div>
                            </div>
                            <div>
                                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Komponen</div>
                                <div class="font-black text-slate-900 text-xs sm:text-sm leading-tight uppercase truncate" :title="detailData.componentName" x-text="detailData.componentName"></div>
                                <div class="font-mono text-[11px] font-semibold text-slate-500 mt-0.5" x-text="detailData.partNumber"></div>
                                <div class="text-[10px] text-slate-400 mt-0.5" x-show="detailData.dimension && detailData.dimension !== '-'">
                                    Dimensi: <span class="font-medium text-slate-600" x-text="detailData.dimension"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DUAL APPROVAL / VERIFIKATOR SECTION (Menjawab Permintaan Pengguna) -->
                    <div class="border-t border-slate-100 pt-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <!-- 1. Verifikator Pengaju -->
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80">
                                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 flex items-center">
                                    <i class="fa-solid fa-user-check text-emerald-600 me-1.5"></i>
                                    <span>Verifikator Pengaju</span>
                                </div>
                                <div class="font-bold text-slate-900 text-xs mt-1" x-text="detailData.approvalPengajuBy"></div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                          :class="detailData.approvalPengajuStatus === 'Diterima' ? 'bg-emerald-100 text-emerald-800' : (detailData.approvalPengajuStatus === 'Ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-900')"
                                          x-text="detailData.approvalPengajuStatus === 'Diterima' ? 'Disetujui' : (detailData.approvalPengajuStatus === 'Ditolak' ? 'Ditolak' : 'Menunggu Approval')"></span>
                                    <span class="text-[10px] text-slate-400 font-mono" x-show="detailData.approvalPengajuAt && detailData.approvalPengajuAt !== '-'" x-text="detailData.approvalPengajuAt"></span>
                                </div>
                            </div>

                            <!-- 2. Verifikator Penerima -->
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80">
                                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 flex items-center">
                                    <i class="fa-solid fa-user-gear text-blue-600 me-1.5"></i>
                                    <span>Verifikator Penerima</span>
                                </div>
                                <div class="font-bold text-slate-900 text-xs mt-1" x-text="detailData.approvalPenerimaBy"></div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                          :class="detailData.approvalPenerimaStatus === 'Diterima' ? 'bg-blue-100 text-blue-800' : (detailData.approvalPenerimaStatus === 'Ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-slate-200 text-slate-700')"
                                          x-text="detailData.approvalPenerimaStatus === 'Diterima' ? 'Diverifikasi' : (detailData.approvalPenerimaStatus === 'Ditolak' ? 'Ditolak' : 'Belum Verifikasi')"></span>
                                    <template x-if="detailData.assignedOperator && detailData.assignedOperator !== 'Belum ditentukan'">
                                        <span class="px-2 py-0.5 rounded bg-indigo-50 border border-indigo-200 text-indigo-700 text-[10px] font-bold" x-text="'Op: ' + detailData.assignedOperator"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. STRIP KUOTA ORDER & DEFECT TRANSPARAN -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center bg-white p-2.5 rounded-2xl border border-slate-200 shadow-2xs">
                    <div class="px-2">
                        <span class="text-[9px] uppercase font-bold text-slate-500 block">Order Awal</span>
                        <span class="font-black text-xs sm:text-sm text-slate-900" x-text="(detailData.originalQty || detailData.totalQty) + ' ' + detailData.uom"></span>
                    </div>
                    <div class="px-2 border-l border-slate-100">
                        <span class="text-[9px] uppercase font-bold text-rose-500 block">Defect / Reject</span>
                        <span class="font-black text-xs sm:text-sm" :class="detailData.defectQty > 0 ? 'text-rose-600' : 'text-slate-400'" x-text="'-' + detailData.defectQty + ' ' + detailData.uom"></span>
                    </div>
                    <div class="px-2 border-l border-slate-100">
                        <span class="text-[9px] uppercase font-bold text-slate-500 block">Target Aktif</span>
                        <span class="font-black text-xs sm:text-sm text-slate-900" x-text="detailData.totalQty + ' ' + detailData.uom"></span>
                    </div>
                    <div class="px-2 border-l border-slate-100">
                        <span class="text-[9px] uppercase font-bold text-slate-500 block">Diterima Gudang</span>
                        <span class="font-black text-xs sm:text-sm text-slate-900" x-text="detailData.receivedQty + ' ' + detailData.uom"></span>
                    </div>
                </div>

                <!-- 4. TRACKING PROSES (Station Timeline with Event Boxes - Lampiran 1-3 Style) -->
                <div class="space-y-3">
                    <h6 class="font-bold text-slate-400 text-xs tracking-wider uppercase ps-1">TRACKING PROSES</h6>

                    <!-- Timeline Cards per Station -->
                    <div class="space-y-3">
                        <template x-for="(st, sIdx) in detailData.steps" :key="sIdx">
                            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-2.5">
                                <!-- Station Title Header -->
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold"
                                             :class="st.status === 'completed' ? 'bg-emerald-600 text-white' : (st.status === 'in_progress' ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-200 text-slate-600')"
                                             x-text="sIdx + 1"></div>
                                        <h4 class="font-black text-xs text-slate-900" x-text="st.name"></h4>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                                          :class="st.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : (st.status === 'in_progress' ? 'bg-amber-100 text-amber-900 font-extrabold' : 'bg-slate-100 text-slate-500')"
                                          x-text="st.status === 'completed' ? 'Selesai' : (st.status === 'in_progress' ? 'Sedang Diproses' : 'Menunggu')"></span>
                                </div>

                                <!-- Station Events Boxes (Biru MASUK, Kuning PARSIAL, Merah DEFECT, Hijau SELESAI) -->
                                <div class="space-y-2">
                                    <template x-for="(log, lIdx) in getStepLogs(st.name, sIdx)" :key="lIdx">
                                        <div>
                                            <!-- A. EVENT BOX MERAH: DEFECT / REJECT (FITUR BARU) -->
                                            <template x-if="log.is_defect">
                                                <div class="p-3 rounded-2xl border-2 border-rose-300 bg-rose-50 shadow-xs">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center space-x-2.5">
                                                            <div class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                            </div>
                                                            <div>
                                                                <span class="font-black text-xs uppercase tracking-wide text-rose-800">DEFECT / RUSAK</span>
                                                                <span class="block text-[10px] text-rose-600 font-medium" x-text="log.time"></span>
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="font-extrabold text-sm sm:text-base text-rose-600 leading-tight" x-text="'-' + log.qty + ' ' + detailData.uom"></div>
                                                            <div class="text-[10px] text-rose-800 font-semibold" x-text="log.operator ? 'Op: ' + log.operator : ''"></div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2 pt-2 border-t border-rose-200 text-[11px] text-rose-900 font-medium flex items-start space-x-1">
                                                        <span class="font-bold shrink-0">Alasan:</span>
                                                        <span class="break-words" x-text="log.notes || 'Komponen cacat / reject pengerjaan'"></span>
                                                    </div>
                                                    <span class="block text-[9px] text-rose-500 font-medium italic mt-1">* Kuota pesanan tiket otomatis dikurangi</span>
                                                </div>
                                            </template>

                                            <!-- B. EVENT BOX BIRU: MASUK -->
                                            <template x-if="!log.is_defect && log.status.includes('(In)')">
                                                <div class="p-3 rounded-2xl border border-blue-200 bg-blue-50/70 shadow-2xs">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center space-x-2.5">
                                                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                                                                <i class="fa-solid fa-arrow-right"></i>
                                                            </div>
                                                            <div>
                                                                <span class="font-black text-xs uppercase tracking-wide text-blue-900">MASUK</span>
                                                                <span class="block text-[10px] text-blue-600 font-medium" x-text="log.time"></span>
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="font-extrabold text-sm sm:text-base text-blue-950 leading-tight" x-text="log.qty + ' ' + detailData.uom"></div>
                                                            <div class="text-[10px] text-blue-800 font-semibold" x-text="log.operator ? 'Op: ' + log.operator : ''"></div>
                                                        </div>
                                                    </div>
                                                    <div x-show="log.notes && log.notes !== '-'" class="mt-2 pt-2 border-t border-blue-200/60 text-[10px] text-blue-800" x-text="log.notes"></div>
                                                </div>
                                            </template>

                                            <!-- C. EVENT BOX KUNING: PARSIAL (SELESAI) -->
                                            <template x-if="!log.is_defect && !log.status.includes('(In)') && log.status.toLowerCase().includes('parsial')">
                                                <div class="p-3 rounded-2xl border border-amber-300 bg-amber-50/80 shadow-2xs">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center space-x-2.5">
                                                            <div class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                                                                <i class="fa-solid fa-chart-pie"></i>
                                                            </div>
                                                            <div>
                                                                <span class="font-black text-xs uppercase tracking-wide text-amber-950">PARSIAL (SELESAI)</span>
                                                                <span class="block text-[10px] text-amber-700 font-medium" x-text="log.time + ' • Bertahap'"></span>
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="font-extrabold text-sm sm:text-base text-amber-950 leading-tight" x-text="log.qty + ' ' + detailData.uom"></div>
                                                            <div class="text-[10px] text-amber-900 font-semibold" x-text="log.operator ? 'Op: ' + log.operator : ''"></div>
                                                        </div>
                                                    </div>
                                                    <div x-show="log.notes && log.notes !== '-'" class="mt-2 pt-2 border-t border-amber-200/60 text-[10px] text-amber-900" x-text="log.notes"></div>
                                                </div>
                                            </template>

                                            <!-- D. EVENT BOX HIJAU: SELESAI (FINAL) -->
                                            <template x-if="!log.is_defect && !log.status.includes('(In)') && !log.status.toLowerCase().includes('parsial') && !log.status.toLowerCase().includes('pickup') && !log.status.toLowerCase().includes('sudah diambil') && !log.status.toLowerCase().includes('menuju pengaju') && !log.status.toLowerCase().includes('otorisasi') && log.action_type !== 'approval' && log.action_type !== 'reject'">
                                                <div class="p-3 rounded-2xl border border-emerald-300 bg-emerald-50/80 shadow-2xs">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center space-x-2.5">
                                                            <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                                                                <i class="fa-solid fa-check"></i>
                                                            </div>
                                                            <div>
                                                                <span class="font-black text-xs uppercase tracking-wide text-emerald-950">SELESAI (FINAL)</span>
                                                                <span class="block text-[10px] text-emerald-700 font-medium" x-text="log.time + ' • Parsial Terakhir Selesai'"></span>
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="font-extrabold text-sm sm:text-base text-emerald-950 leading-tight" x-text="log.qty + ' ' + detailData.uom"></div>
                                                            <div class="text-[10px] text-emerald-900 font-semibold" x-text="log.operator ? 'Op: ' + log.operator : ''"></div>
                                                        </div>
                                                    </div>
                                                    <div x-show="log.notes && log.notes !== '-'" class="mt-2 pt-2 border-t border-emerald-200/60 text-[10px] text-emerald-900" x-text="log.notes"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Placeholder jika stasiun belum ada log -->
                                    <template x-if="getStepLogs(st.name, sIdx).length === 0">
                                        <div class="p-3 text-center text-slate-400 text-xs italic bg-slate-50/60 rounded-xl border border-slate-100">
                                            Menunggu antrian pengerjaan
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Final Destination Card: Pengiriman ke Dept Gudang -->
                        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-2.5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold"
                                         :class="detailData.receivedQty >= detailData.totalQty && detailData.totalQty > 0 ? 'bg-emerald-600 text-white' : (detailData.currentStatus.toLowerCase().includes('menuju pengaju') ? 'bg-purple-600 text-white animate-bounce' : 'bg-slate-200 text-slate-600')">
                                        <i class="fa-solid fa-warehouse text-[9px]"></i>
                                    </div>
                                    <h4 class="font-black text-xs text-slate-900" x-text="'Pengiriman ke ' + (detailData.deptPengaju || 'Dept Gudang')"></h4>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                                      :class="detailData.receivedQty >= detailData.totalQty && detailData.totalQty > 0 ? 'bg-emerald-100 text-emerald-800' : (detailData.receivedQty > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (detailData.currentStatus.toLowerCase().includes('menuju pengaju') ? 'bg-purple-100 text-purple-900 font-extrabold' : 'bg-slate-100 text-slate-500'))"
                                      x-text="detailData.receivedQty >= detailData.totalQty && detailData.totalQty > 0 ? 'Selesai Diterima' : (detailData.receivedQty > 0 ? 'Diterima Parsial (' + detailData.receivedQty + '/' + detailData.totalQty + ')' : (detailData.currentStatus.toLowerCase().includes('menuju pengaju') ? 'Sedang Diantar MH' : 'Menunggu Selesai'))"></span>
                            </div>

                            <!-- Final Logs / Status -->
                            <div class="space-y-2">
                                <template x-for="(gLog, gIdx) in getGudangLogs()" :key="gIdx">
                                    <div>
                                        <!-- A. LOG BIRU: MATERIAL HANDLING (MENUJU PENGAJU / TRANSIT) -->
                                        <template x-if="gLog.is_mh">
                                            <div class="p-3 rounded-2xl border border-blue-300 bg-blue-50/80 shadow-2xs">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                                                            <i class="fa-solid fa-truck-ramp-box"></i>
                                                        </div>
                                                        <div>
                                                            <span class="font-black text-xs uppercase tracking-wide text-blue-950">MATERIAL HANDLING (MENUJU PENGAJU)</span>
                                                            <span class="block text-[10px] text-blue-600 font-medium" x-text="gLog.time + ' • Pengantaran Logistik'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="font-extrabold text-sm sm:text-base text-blue-950 leading-tight" x-text="gLog.qty + ' ' + detailData.uom"></div>
                                                        <div class="text-[10px] text-blue-800 font-semibold" x-text="gLog.operator ? 'Kurir MH: ' + gLog.operator : 'Kurir MH'"></div>
                                                    </div>
                                                </div>
                                                <div x-show="gLog.notes && gLog.notes !== '-'" class="mt-2 pt-2 border-t border-blue-200/60 text-[10px] text-blue-900" x-text="gLog.notes"></div>
                                            </div>
                                        </template>

                                        <!-- B. LOG HIJAU: DITERIMA GUDANG (SUDAH DIAMBIL) -->
                                        <template x-if="!gLog.is_mh">
                                            <div class="p-3 rounded-2xl border border-emerald-300 bg-emerald-50/80 shadow-2xs">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2.5">
                                                        <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                                                            <i class="fa-solid fa-warehouse"></i>
                                                        </div>
                                                        <div>
                                                            <span class="font-black text-xs uppercase tracking-wide text-emerald-950">DITERIMA GUDANG</span>
                                                            <span class="block text-[10px] text-emerald-700 font-medium" x-text="gLog.time + ' • Fisik Diterima'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="font-extrabold text-sm sm:text-base text-emerald-950 leading-tight" x-text="gLog.qty + ' ' + detailData.uom"></div>
                                                        <div class="text-[10px] text-emerald-900 font-semibold" x-text="gLog.operator ? 'Penerima: ' + gLog.operator : ''"></div>
                                                    </div>
                                                </div>
                                                <div x-show="gLog.notes && gLog.notes !== '-'" class="mt-2 pt-2 border-t border-emerald-200/60 text-[10px] text-emerald-900" x-text="gLog.notes"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="getGudangLogs().length === 0">
                                    <div>
                                        <template x-if="detailData.currentStatus.toLowerCase().includes('menuju pengaju')">
                                            <div class="p-3 rounded-2xl border border-purple-200 bg-purple-50 text-purple-900 flex items-center space-x-2.5">
                                                <i class="fa-solid fa-truck-fast text-purple-600 text-base"></i>
                                                <span class="font-bold text-xs">Sedang Diantar Kurir Material Handling ke Gudang</span>
                                            </div>
                                        </template>
                                        <template x-if="!detailData.currentStatus.toLowerCase().includes('menuju pengaju')">
                                            <div class="p-3 text-center text-slate-400 text-xs italic bg-slate-50/60 rounded-xl border border-slate-100">
                                                Menunggu tahapan pengerjaan mesin selesai
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. FORM EKSEKUSI PENGERJAAN LANGSUNG DI MODAL -->
                <template x-if="detailData.canProcessAction && !detailData.isFinal">
                    <div class="p-4 bg-gradient-to-br from-indigo-50/90 via-slate-50 to-indigo-50/70 border-2 border-indigo-200 rounded-3xl space-y-3.5 shadow-xs" 
                         x-data="{ 
                             hasDefectModal: false, 
                             defectQty: 0, 
                             processQty: detailData.remainingQty,
                             getMax() { return Math.max(0, Number(detailData.remainingQty) - (this.hasDefectModal && this.defectQty > 0 ? Number(this.defectQty) : 0)); },
                             sync() { if (Number(this.processQty) > this.getMax()) this.processQty = this.getMax(); }
                         }"
                         x-init="processQty = detailData.remainingQty">
                        <div class="flex items-center justify-between border-b border-indigo-100 pb-2.5">
                            <div class="flex items-center space-x-2.5">
                                <span class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xs font-black shadow-xs">
                                    <i class="fa-solid fa-play"></i>
                                </span>
                                <div>
                                    <h4 class="font-black text-xs text-indigo-950 uppercase tracking-wider">Eksekusi Langkah Selanjutnya</h4>
                                    <p class="text-[11px] text-indigo-700 font-mono font-extrabold" x-text="'&rarr; ' + detailData.nextStatus"></p>
                                </div>
                            </div>
                            <span class="text-[10px] px-2.5 py-1 rounded-full font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                                Sisa Order: <b x-text="detailData.remainingQty + ' ' + detailData.uom"></b>
                            </span>
                        </div>

                        <form method="POST" :action="detailData.actionUrl" class="space-y-3">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="action_type" value="process">
                            <input type="hidden" name="target_status" :value="detailData.nextStatus">

                            <!-- Input Qty Pengerjaan / Keluar -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block font-bold text-slate-700 uppercase tracking-wider text-[10px]">
                                        Jumlah Komponen yang Diproses / Dikeluarkan: <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] text-slate-500 font-medium">Batas Maksimal: <b x-text="getMax() + ' ' + detailData.uom"></b></span>
                                </div>
                                <div class="relative">
                                    <input type="number" 
                                           name="process_qty" 
                                           x-model.number="processQty"
                                           @input="if(Number(processQty) > getMax()) processQty = getMax()"
                                           required 
                                           step="any" 
                                           min="0" 
                                           :max="getMax()" 
                                           class="w-full px-3.5 py-2 text-sm font-black border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400" x-text="detailData.uom"></span>
                                </div>

                                <!-- Allocation Card -->
                                <div class="p-2 bg-indigo-100/70 border border-indigo-200 rounded-xl flex items-center justify-between text-[11px] font-bold text-indigo-950 mt-1.5">
                                    <span>Sedia: <b x-text="detailData.remainingQty"></b> | Defect: <b class="text-rose-600" x-text="hasDefectModal && defectQty > 0 ? defectQty : 0"></b></span>
                                    <span>Batas Unit Bagus: <b class="text-indigo-700" x-text="getMax() + ' ' + detailData.uom"></b></span>
                                </div>
                            </div>

                            <!-- Checkbox Defect / Reject -->
                            <div class="pt-1">
                                <label class="inline-flex items-center cursor-pointer text-xs font-bold text-rose-600 hover:text-rose-700 p-2 bg-rose-50 border border-rose-200 rounded-xl w-full">
                                    <input type="checkbox" x-model="hasDefectModal" @change="sync()" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 me-2">
                                    <span>Terdapat Komponen Rusak / Defect saat Pengerjaan?</span>
                                </label>

                                <div x-show="hasDefectModal" x-transition class="mt-2 p-3 bg-rose-50/80 border border-rose-200 rounded-xl space-y-2 text-xs">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block font-bold text-rose-900 uppercase text-[9px] mb-0.5">Jumlah Defect (PCS)</label>
                                            <input type="number" name="defect_qty" x-model.number="defectQty" @input="sync()" step="any" min="0.01" :max="detailData.remainingQty" placeholder="Contoh: 5" class="w-full px-2.5 py-1.5 text-xs border border-rose-300 rounded-lg font-bold text-rose-900 bg-white">
                                        </div>
                                        <div>
                                            <label class="block font-bold text-rose-900 uppercase text-[9px] mb-0.5">Alasan Defect</label>
                                            <input type="text" name="defect_reason" placeholder="Contoh: Salah tekuk, material bengkok..." class="w-full px-2.5 py-1.5 text-xs border border-rose-300 rounded-lg bg-white">
                                        </div>
                                    </div>
                                    <p class="text-[9px] text-rose-700">Defect akan otomatis memotong kuota total tiket WRI & dicatat pada audit log.</p>
                                </div>
                            </div>

                            <!-- Petugas & Catatan -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[10px]">
                                <div>
                                    <label class="block font-bold text-slate-700 uppercase mb-0.5">Petugas / Operator:</label>
                                    <input type="text" name="operator_name" value="{{ auth()->user()->name }}" class="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg font-semibold">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 uppercase mb-0.5">Catatan Operasional:</label>
                                    <input type="text" name="notes" placeholder="Catatan shift / nomor pallet..." class="w-full px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg">
                                </div>
                            </div>

                            <!-- Tombol Konfirmasi -->
                            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs rounded-xl shadow-md transition flex items-center justify-center space-x-1.5">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Konfirmasi & Simpan Langkah Pengerjaan</span>
                            </button>
                        </form>
                    </div>
                </template>

            </div>

            <!-- Sticky Footer -->
            <div class="p-4 bg-white border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <template x-if="detailData.isPrintable">
                    <a :href="detailData.printUrl" 
                       target="_blank" 
                       class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition flex items-center space-x-1.5 shadow-xs">
                        <i class="fa-solid fa-print"></i>
                        <span>Cetak Dokumen WR (3 Rangkap)</span>
                    </a>
                </template>
                <template x-if="!detailData.isPrintable">
                    <div class="px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-400 font-semibold text-xs flex items-center space-x-1.5 cursor-not-allowed"
                         :title="detailData.printValidationMessage || 'Dokumen belum dapat dicetak: Butuh verifikasi pengaju, penerima, dan penugasan operator'">
                        <i class="fa-solid fa-lock text-slate-400"></i>
                        <span>Cetak Terkunci (Belum Lengkap)</span>
                    </div>
                </template>
                <button type="button" 
                        @click="closeDetailModal()" 
                        class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs transition flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Kembali / Tutup</span>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL 2: DETAIL TRACKING TIMELINE & RIWAYAT (IN-PLACE POP-UP) -->
<div x-show="detailModalOpen" 
     x-cloak 
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
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" 
         @click="closeDetailModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="detailModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200 flex flex-col max-h-[90vh]">
            
            <!-- Sticky Header -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-5 text-white flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-teal-300 text-lg">
                        <i class="fa-solid fa-diagram-project"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-mono text-xs font-black text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20" x-text="detailData.wriNumber"></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" :class="detailData.statusClass" x-text="detailData.currentStatus"></span>
                        </div>
                        <h3 class="text-base font-extrabold text-white mt-1 leading-snug truncate" x-text="detailData.componentName"></h3>
                    </div>
                </div>
                <button type="button" 
                        @click="closeDetailModal()" 
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Scrollable Content Body -->
            <div class="p-5 overflow-y-auto space-y-5 text-xs text-slate-700">
                
                <!-- Identitas Komponen & QR Card -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center space-x-3.5">
                        <img :src="detailData.imageUrl" 
                             class="w-16 h-16 rounded-xl object-cover bg-white border border-slate-200 flex-shrink-0"
                             x-on:error="$el.src = '{{ asset('images/logo_hpk.webp') }}'">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Kode / Part Number:</span>
                            <span class="font-mono font-extrabold text-sm text-slate-900" x-text="detailData.partNumber"></span>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Dimensi: <strong class="text-slate-700" x-text="detailData.dimension"></strong> &bull; Slot Rak: <strong class="text-indigo-800 font-mono" x-text="detailData.targetRack"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Kuota Qty Strip -->
                    <div class="grid grid-cols-3 gap-2 text-center bg-white p-2.5 rounded-xl border border-slate-200/80 w-full sm:w-auto">
                        <div class="px-2">
                            <span class="text-[9px] uppercase font-bold text-slate-400 block">Order</span>
                            <span class="font-black text-sm text-slate-900" x-text="detailData.totalQty + ' ' + detailData.uom"></span>
                        </div>
                        <div class="px-2 border-x border-slate-100">
                            <span class="text-[9px] uppercase font-bold text-emerald-600 block">Masuk</span>
                            <span class="font-black text-sm text-emerald-700" x-text="detailData.receivedQty + ' ' + detailData.uom"></span>
                        </div>
                        <div class="px-2">
                            <span class="text-[9px] uppercase font-bold text-rose-500 block">Defect</span>
                            <span class="font-black text-sm text-rose-600" x-text="detailData.defectQty + ' ' + detailData.uom"></span>
                        </div>
                    </div>
                </div>

                <!-- STATUS OTORISASI & VERIFIKASI DEPARTEMEN -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-stamp text-amber-500"></i>
                            <h4 class="font-bold text-xs uppercase tracking-wider text-slate-800">Status Otorisasi & Verifikasi Alur</h4>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Kartu 1: Verifikasi Departemen Pengaju -->
                        <div class="p-3.5 rounded-2xl border" 
                             :class="detailData.approvalPengajuStatus === 'Diterima' ? 'bg-emerald-50/60 border-emerald-200' : (detailData.approvalPengajuStatus === 'Ditolak' ? 'bg-rose-50 border-rose-200' : 'bg-amber-50/60 border-amber-200')">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">1. Otorisasi Pengaju</span>
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full uppercase"
                                      :class="detailData.approvalPengajuStatus === 'Diterima' ? 'bg-emerald-100 text-emerald-800' : (detailData.approvalPengajuStatus === 'Ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-900 animate-pulse')"
                                      x-text="detailData.approvalPengajuStatus === 'Diterima' ? 'Disetujui' : (detailData.approvalPengajuStatus === 'Ditolak' ? 'Ditolak' : 'Menunggu Ka. Pengaju')"></span>
                            </div>
                            <div class="space-y-1.5 text-[11px]">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Departemen:</span>
                                    <strong class="text-slate-800 font-bold" x-text="detailData.deptPengaju"></strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Pemohon / Dibuat:</span>
                                    <strong class="text-slate-800" x-text="detailData.requestedByName"></strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Diverifikasi Ka.:</span>
                                    <strong class="text-emerald-700 font-bold" x-text="detailData.approvalPengajuBy"></strong>
                                </div>
                                <div class="flex justify-between items-center text-[10px] text-slate-400 pt-1.5 border-t border-slate-200/60">
                                    <span>Waktu Otorisasi:</span>
                                    <span class="font-mono" x-text="detailData.approvalPengajuAt"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Kartu 2: Verifikasi Departemen Penerima (Mesin) -->
                        <div class="p-3.5 rounded-2xl border" 
                             :class="detailData.approvalPenerimaStatus === 'Diterima' ? 'bg-blue-50/60 border-blue-200' : (detailData.approvalPenerimaStatus === 'Ditolak' ? 'bg-rose-50 border-rose-200' : 'bg-amber-50/60 border-amber-200')">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">2. Otorisasi Penerima</span>
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full uppercase"
                                      :class="detailData.approvalPenerimaStatus === 'Diterima' ? 'bg-blue-100 text-blue-800' : (detailData.approvalPenerimaStatus === 'Ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-900 animate-pulse')"
                                      x-text="detailData.approvalPenerimaStatus === 'Diterima' ? 'Disetujui & Siap' : (detailData.approvalPenerimaStatus === 'Ditolak' ? 'Ditolak' : 'Menunggu Ka. ' + detailData.deptPenerima)"></span>
                            </div>
                            <div class="space-y-1.5 text-[11px]">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Departemen:</span>
                                    <strong class="text-slate-800 font-bold" x-text="detailData.deptPenerima"></strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Diverifikasi Ka.:</span>
                                    <strong class="text-blue-800 font-bold" x-text="detailData.approvalPenerimaBy"></strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Operator Pelaksana:</span>
                                    <strong class="text-indigo-900 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200 font-bold" x-text="detailData.assignedOperator"></strong>
                                </div>
                                <div class="flex justify-between items-center text-[10px] text-slate-400 pt-1.5 border-t border-slate-200/60">
                                    <span>Waktu Otorisasi:</span>
                                    <span class="font-mono" x-text="detailData.approvalPenerimaAt"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TRACKING PROSES FABRIKASI & LOGISTIK MH -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-route text-indigo-600"></i>
                            <h4 class="font-bold text-xs uppercase tracking-wider text-slate-800">Alur Fabrikasi & Material Handling</h4>
                        </div>
                        <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-lg border border-indigo-200" x-text="detailData.progressPercentage + '% Selesai'"></span>
                    </div>

                    <!-- Modern Timeline Card -->
                    <div class="p-4 bg-white rounded-2xl border border-slate-200 space-y-3 shadow-2xs">
                        <div class="space-y-2">
                            <template x-for="(st, sIdx) in detailData.steps" :key="sIdx">
                                <div class="flex items-start space-x-3">
                                    <!-- Step Indicator Dot -->
                                    <div class="flex flex-col items-center">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px]"
                                             :class="st.status === 'completed' ? 'bg-emerald-600 text-white' : (st.status === 'in_progress' ? 'bg-amber-500 text-slate-950 animate-pulse' : 'bg-slate-200 text-slate-600')"
                                             x-text="sIdx + 1"></div>
                                        <div x-show="sIdx < detailData.steps.length - 1" class="w-0.5 h-6 bg-slate-200 my-1"></div>
                                    </div>

                                    <!-- Step Content -->
                                    <div class="flex-1 pb-2">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-xs text-slate-800" x-text="st.name"></span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                                                  :class="st.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : (st.status === 'in_progress' ? 'bg-amber-100 text-amber-900 font-extrabold' : 'bg-slate-100 text-slate-500')"
                                                  x-text="st.status === 'completed' ? 'Selesai' : (st.status === 'in_progress' ? 'Sedang Diproses' : 'Antrian')"></span>
                                        </div>

                                        <!-- MH Transfer indicator between steps -->
                                        <div x-show="sIdx < detailData.steps.length - 1" class="mt-1 text-[10px] text-blue-700 bg-blue-50/60 px-2 py-0.5 rounded border border-blue-200 flex items-center space-x-1.5 w-fit">
                                            <i class="fa-solid fa-truck-fast text-blue-500"></i>
                                            <span>Transfer Material Handling (MH) ke stasiun berikutnya</span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Final Delivery Step: MH Kirim ke Pengaju / Gudang -->
                            <div class="flex items-start space-x-3 pt-1 border-t border-slate-100">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px]"
                                     :class="detailData.receivedQty >= detailData.totalQty && detailData.totalQty > 0 ? 'bg-emerald-600 text-white' : (detailData.currentStatus.includes('Menuju Pengaju') ? 'bg-blue-600 text-white animate-bounce' : 'bg-slate-200 text-slate-600')">
                                    <i class="fa-solid fa-warehouse text-[9px]"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs text-slate-800">Serah Terima ke Pengaju / Gudang</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                                              :class="detailData.receivedQty >= detailData.totalQty && detailData.totalQty > 0 ? 'bg-emerald-100 text-emerald-800' : (detailData.currentStatus.includes('Menuju Pengaju') ? 'bg-blue-100 text-blue-900 font-extrabold' : 'bg-slate-100 text-slate-500')"
                                              x-text="detailData.receivedQty >= detailData.totalQty && detailData.totalQty > 0 ? 'Diterima Fisik (Closed)' : (detailData.currentStatus.includes('Menuju Pengaju') ? 'Sedang Diantar MH' : 'Menunggu Selesai')"></span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Barang jadi diantar kembali ke Gudang untuk penambahan stok fisik.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIWAYAT AUDIT LOGS -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                        <div class="flex items-center space-x-1.5">
                            <i class="fa-solid fa-clock-rotate-left text-slate-500"></i>
                            <h4 class="font-bold text-xs uppercase tracking-wider text-slate-800">Riwayat Log Transaksi & Defect</h4>
                        </div>
                        <span class="text-[10px] text-slate-400" x-text="detailData.logs ? detailData.logs.length + ' Entri' : '0 Entri'"></span>
                    </div>

                    <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100 bg-slate-50/50">
                        <template x-for="(log, lIdx) in detailData.logs" :key="lIdx">
                            <div class="p-2.5 flex items-start justify-between text-[11px]" :class="log.is_defect ? 'bg-rose-50/80' : 'bg-white'">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-slate-900" x-text="log.status"></span>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded font-bold"
                                              :class="log.is_defect ? 'bg-rose-200 text-rose-900' : 'bg-slate-100 text-slate-600'"
                                              x-text="log.qty + ' ' + detailData.uom"></span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-0.5" x-text="log.notes"></p>
                                </div>
                                <div class="text-right text-[10px] text-slate-400 font-mono flex-shrink-0 ml-3">
                                    <span x-text="log.time"></span>
                                    <span class="block text-slate-600 font-semibold font-sans" x-text="log.operator"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="!detailData.logs || detailData.logs.length === 0" class="p-4 text-center text-slate-400 text-xs italic">
                            Belum ada riwayat aktivitas pengerjaan.
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sticky Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <a :href="detailData.printUrl" 
                   target="_blank" 
                   class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-300 text-slate-800 font-bold text-xs transition flex items-center space-x-1.5 shadow-2xs">
                    <i class="fa-solid fa-print text-slate-600"></i>
                    <span>Cetak Lembar WR / Kanban</span>
                </a>
                <button type="button" 
                        @click="closeDetailModal()" 
                        class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs transition">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>

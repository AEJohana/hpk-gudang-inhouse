<!-- MODAL KAMERA SCANNER QR WORK REQUEST (VERIFIKASI FISIK OPERASIONAL) -->
<div x-show="processCameraModalOpen" 
     x-cloak 
     style="display: none; z-index: 99999 !important;"
     :class="{ 'pointer-events-none': !processCameraModalOpen }"
     class="fixed inset-0 z-[99999] overflow-y-auto" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur Gelap (Z-Index 99998 Menutupi Modal Proses di Belakangnya) -->
    <div x-show="processCameraModalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         style="z-index: 99998 !important;"
         class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm transition-opacity" 
         @click="closeProcessCameraModal()"></div>

    <div class="flex min-h-full items-center justify-center p-3 text-center sm:p-0 relative"
         style="z-index: 99999 !important;">
        <div x-show="processCameraModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 scale-95" 
             x-transition:enter-end="opacity-100 scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 scale-100" 
             x-transition:leave-end="opacity-0 scale-95" 
             style="z-index: 99999 !important;"
             class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-md border border-slate-200">
            
            <!-- Header Modal Scanner -->
            <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#0a2342] text-white flex items-center justify-center font-bold text-xs shadow-2xs">
                        <i class="fa-solid fa-camera text-amber-300"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-extrabold text-slate-900 leading-tight">Pindai QR Lembar Tiket WRI</h4>
                        <p class="text-[10px] text-slate-500 font-medium">Arahkan kamera ke kode QR lembar kerja cetak</p>
                    </div>
                </div>

                <button type="button" 
                        @click="closeProcessCameraModal()" 
                        class="w-7 h-7 rounded-full bg-white hover:bg-slate-200 border border-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Area Viewfinder Kamera Video (html5-qrcode) -->
            <div class="p-4 space-y-3 bg-slate-950/90 text-white">
                <div class="relative w-full rounded-2xl overflow-hidden bg-black border border-slate-800 min-h-[260px] flex items-center justify-center">
                    <div id="process-camera-reader" class="w-full h-full min-h-[260px]"></div>
                </div>

                <!-- Info Tiket yang Diharapkan -->
                <div class="p-2.5 bg-slate-900/90 border border-slate-800 rounded-xl flex items-center justify-between text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Tiket Target:</span>
                        <span class="font-mono font-black text-amber-400 text-xs" x-text="processData.wriNumber"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Part:</span>
                        <span class="font-bold text-white text-xs truncate max-w-[140px] block" x-text="processData.componentName"></span>
                    </div>
                </div>
            </div>

            <!-- Footer Tombol Tutup -->
            <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
                <button type="button" 
                        @click="closeProcessCameraModal()" 
                        class="w-full py-2 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                    Tutup Kamera
                </button>
            </div>

        </div>
    </div>
</div>

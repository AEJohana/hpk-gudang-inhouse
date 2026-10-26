<!-- MODAL ZOOM QR CODE RESOLUSI TINGGI -->
<div x-show="qrZoomModal" 
     x-cloak 
     style="display: none;"
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-qr-zoom-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur -->
    <div x-show="qrZoomModal" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" 
         @click="closeQrZoom()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="qrZoomModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-3xl bg-white text-center shadow-2xl transition-all p-6 sm:p-7 max-w-sm w-full border border-slate-200">
            
            <!-- Close Button Top Right -->
            <button type="button" 
                    @click="closeQrZoom()" 
                    class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Header Info -->
            <div class="mb-4 text-center">
                <div class="inline-flex items-center space-x-1.5 px-3 py-1 bg-amber-50 border border-amber-300 text-amber-900 rounded-full text-xs font-bold mb-2">
                    <i class="fa-solid fa-qrcode text-amber-600"></i>
                    <span>Scan QR Code WRI</span>
                </div>
                <h3 class="font-mono font-black text-sm text-slate-900 break-all leading-snug" x-text="qrZoomData.wriNumber"></h3>
                <p class="font-extrabold text-xs text-slate-700 mt-1 uppercase truncate" x-text="qrZoomData.componentName"></p>
            </div>

            <!-- Big QR Image Container -->
            <div class="bg-white p-3 rounded-2xl border-2 border-dashed border-slate-300 shadow-inner inline-block mx-auto mb-3">
                <img :src="qrZoomData.qrUrl" 
                     alt="QR Tiket Zoom" 
                     class="w-64 h-64 sm:w-72 sm:h-72 object-contain rounded-xl mx-auto">
            </div>

            <p class="text-[11px] text-slate-500 font-medium">
                Arahkan barcode scanner gun atau kamera smartphone langsung ke QR Code di atas.
            </p>

            <button type="button" 
                    @click="closeQrZoom()" 
                    class="mt-4 w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center space-x-1.5 cursor-pointer">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali / Tutup Pembesar QR</span>
            </button>
        </div>
    </div>
</div>

<div x-show="imageModalOpen" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="modal-image-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div x-show="imageModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
             @click="imageModalOpen = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <!-- Modal Panel -->
        <div x-show="imageModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200">
            
            <!-- Header -->
            <div class="bg-slate-50 px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900" x-text="imageData.componentName" id="modal-image-title"></h3>
                    <p class="font-mono text-xs text-slate-500 font-semibold mt-0.5" x-text="imageData.partNumber"></p>
                </div>
                <button type="button" @click="imageModalOpen = false" class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Body / Image Container -->
            <div class="p-6 text-center bg-slate-50/50">
                <div class="max-h-[60vh] overflow-hidden rounded-xl border border-slate-200 bg-white flex items-center justify-center p-3 shadow-inner">
                    <img :src="imageData.imageUrl" :alt="imageData.componentName" class="max-h-[55vh] max-w-full object-contain mx-auto rounded-lg">
                </div>
                <div class="mt-3.5 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-600">
                    <span class="px-3 py-1 bg-white border border-slate-200 rounded-lg font-mono font-medium shadow-2xs" x-show="imageData.dimension && imageData.dimension !== '-'" x-text="'Dimensi: ' + imageData.dimension"></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 px-5 py-3 border-t border-slate-200 flex items-center justify-between">
                <span class="text-[11px] text-slate-400 italic">Pratinjau gambar komponen</span>
                <button type="button" @click="imageModalOpen = false" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="imageModalOpen" 
     x-cloak
     style="display: none;"
     :class="{ 'pointer-events-none': !imageModalOpen }"
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
             :class="{ 'pointer-events-none': !imageModalOpen }"
             class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" 
             @click="closeImageModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <!-- Modal Panel -->
        <div x-show="imageModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             :class="imageData.isPdf ? 'sm:max-w-4xl' : 'sm:max-w-2xl'"
             class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:w-full border border-slate-200">
            
            <!-- Header -->
            <div class="bg-slate-50 px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span x-show="imageData.isPdf" class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold rounded-md uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-file-pdf me-1"></i> CAD PDF
                    </span>
                    <span x-show="!imageData.isPdf" class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded-md uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-image me-1"></i> Gambar Teknik
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900" x-text="imageData.componentName" id="modal-image-title"></h3>
                        <p class="font-mono text-xs text-slate-500 font-semibold" x-text="imageData.partNumber"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-1.5">
                    <a :href="imageData.imageUrl" target="_blank" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-xs font-semibold transition flex items-center space-x-1 shadow-2xs" title="Buka di Tab Baru">
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        <span class="hidden sm:inline">Tab Baru</span>
                    </a>
                    <button type="button" @click="closeImageModal()" class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Body / Image & PDF Container -->
            <div class="p-4 sm:p-6 text-center bg-slate-50/50">
                <!-- PDF Native Full Viewer -->
                <template x-if="imageData.isPdf">
                    <div class="w-full h-[65vh] rounded-xl border border-slate-300 bg-white shadow-inner overflow-hidden">
                        <iframe :src="imageData.imageUrl" class="w-full h-full border-0 rounded-xl" title="Drawing PDF Viewer"></iframe>
                    </div>
                </template>

                <!-- Image with Zoom & Pan Controls -->
                <template x-if="!imageData.isPdf">
                    <div>
                        <!-- Zoom Control Toolbar -->
                        <div class="flex items-center justify-between mb-3 px-1">
                            <span class="text-[11px] font-medium text-slate-500">
                                Gunakan tombol kontrol untuk memperbesar dimensi gambar:
                            </span>
                            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-2xs space-x-1">
                                <button type="button" 
                                        @click="if(imageData.zoomLevel > 0.6) imageData.zoomLevel -= 0.2"
                                        class="px-2.5 py-1 text-slate-700 hover:bg-slate-100 rounded-lg text-xs font-bold transition cursor-pointer"
                                        title="Perkecil (-)">
                                    <i class="fa-solid fa-magnifying-glass-minus"></i>
                                </button>
                                <span class="px-2 text-xs font-mono font-bold text-slate-600" x-text="Math.round(imageData.zoomLevel * 100) + '%'"></span>
                                <button type="button" 
                                        @click="if(imageData.zoomLevel < 3.0) imageData.zoomLevel += 0.2"
                                        class="px-2.5 py-1 text-slate-700 hover:bg-slate-100 rounded-lg text-xs font-bold transition cursor-pointer"
                                        title="Perbesar (+)">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </button>
                                <button type="button" 
                                        @click="imageData.zoomLevel = 1.0"
                                        class="px-2 py-1 text-[11px] text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg font-semibold transition cursor-pointer"
                                        title="Reset Ukuran Asli">
                                    Reset
                                </button>
                            </div>
                        </div>

                        <!-- Image Viewing Area -->
                        <div class="max-h-[60vh] overflow-auto rounded-xl border border-slate-200 bg-white flex items-center justify-center p-3 shadow-inner">
                            <img :src="imageData.imageUrl" 
                                 :alt="imageData.componentName" 
                                 :style="'transform: scale(' + imageData.zoomLevel + '); transform-origin: center center; transition: transform 0.15s ease-out;'"
                                 class="max-h-[55vh] max-w-full object-contain mx-auto rounded-lg select-none">
                        </div>
                    </div>
                </template>

                <div class="mt-3.5 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-600">
                    <span class="px-3 py-1 bg-white border border-slate-200 rounded-lg font-mono font-medium shadow-2xs" 
                          x-show="imageData.dimension && imageData.dimension !== '-'" 
                          x-text="'Dimensi: ' + imageData.dimension"></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 px-5 py-3 border-t border-slate-200 flex items-center justify-between">
                <span class="text-[11px] text-slate-400 italic">Pratinjau gambar teknik luring (D:\Ali Magang\KOMPONEN)</span>
                <button type="button" @click="closeImageModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl transition flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Tutup</span>
                </button>
            </div>
        </div>
    </div>
</div>

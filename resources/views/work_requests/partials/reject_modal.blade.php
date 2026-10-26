<!-- MODAL KONFIRMASI PENOLAKAN TIKET WRI -->
<div x-show="rejectModalOpen" 
     x-cloak 
     style="display: none;"
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-reject-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur -->
    <div x-show="rejectModalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" 
         @click="closeRejectModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="rejectModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200">
            
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-rose-900 via-rose-950 to-slate-900 p-5 text-white flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-rose-400 text-lg flex-shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-[10px] font-mono font-black uppercase text-rose-300 bg-rose-400/20 px-2 py-0.5 rounded border border-rose-400/30" x-text="rejectData.wriNumber"></span>
                            <span class="text-[10px] font-bold text-rose-200 bg-rose-500/20 px-2 py-0.5 rounded">Penolakan Tiket</span>
                        </div>
                        <h3 class="text-base font-extrabold text-white mt-1 leading-snug">Konfirmasi Tolak Tiket WRI</h3>
                    </div>
                </div>
                <button type="button" 
                        @click="closeRejectModal()" 
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form :action="rejectData.actionUrl" method="POST" class="p-5 space-y-4">
                @csrf
                <div class="p-3 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-900 space-y-1">
                    <p class="font-bold flex items-center">
                        <i class="fa-solid fa-circle-exclamation me-1.5 text-rose-600"></i>
                        Perhatian: Tindakan ini akan membatalkan tiket WRI secara permanen.
                    </p>
                    <p class="text-[11px] text-rose-700">
                        Tiket yang ditolak tidak dapat diproses lebih lanjut oleh stasiun mesin maupun gudang.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">
                        Alasan Penolakan <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="reason" 
                              required 
                              rows="3" 
                              x-model="rejectData.reason"
                              placeholder="Tuliskan alasan penolakan tiket secara jelas (contoh: Kapasitas mesin penuh / material baku tidak tersedia)..."
                              class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500 focus:outline-none transition"></textarea>
                </div>

                <!-- Footer Buttons -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" 
                            @click="closeRejectModal()" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                        <span>Kembali / Batal</span>
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-ban text-xs"></i>
                        <span>Tolak & Batalkan Tiket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

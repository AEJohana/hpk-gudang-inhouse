<!-- MODAL REVISI ROUTING (DESAIN MINIMALIS SESUAI REFERENSI LAMPIRAN) -->
<div x-show="editRoutingModalOpen" 
     x-cloak 
     style="display: none;"
     :class="{ 'pointer-events-none': !editRoutingModalOpen }"
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-edit-routing-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop Blur -->
    <div x-show="editRoutingModalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         :class="{ 'pointer-events-none': !editRoutingModalOpen }"
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" 
         @click="closeEditRoutingModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="editRoutingModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-visible rounded-3xl bg-white text-left shadow-2xl transition-all my-6 mx-auto w-full sm:max-w-md border border-slate-100"
             style="max-width: 420px;">
            
            <!-- Modal Header (Persis Lampiran 0) -->
            <div class="px-5 pt-5 pb-3 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2 text-blue-600" style="color: #1877F2;">
                    <i class="fa-regular fa-pen-to-square text-base"></i>
                    <h3 class="text-base font-extrabold tracking-tight" style="color: #1877F2;">Revisi Routing</h3>
                </div>
                <button type="button" 
                        @click="closeEditRoutingModal()" 
                        class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition cursor-pointer text-xs"
                        title="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Form Body (Persis Lampiran 0 & Lampiran 1) -->
            <form :action="editRoutingData.actionUrl" method="POST" class="p-5 space-y-4" @submit="isSubmittingRouting = true">
                @csrf
                @method('PUT')

                <!-- Hidden inputs agar format data target_departments sesuai dengan Controller -->
                <template x-for="(dept, idx) in selectedDepts" :key="idx">
                    <input type="hidden" :name="'target_departments[' + idx + '][department_name]'" :value="dept">
                </template>

                <!-- 1. No Work Request -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">No Work Request</label>
                    <input type="text" 
                           readonly 
                           :value="editRoutingData.wriNumber" 
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none cursor-not-allowed select-none">
                </div>

                <!-- 2. Routing / Penerima Baru * -->
                <div class="relative" @click.outside="isDeptDropdownOpen = false">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Routing / Penerima Baru <span class="text-rose-500 font-bold">*</span>
                    </label>
                    
                    <!-- Kotak Input Tag dengan Tombol Red X di Sisi Kanan -->
                    <div class="relative flex items-stretch rounded-xl border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition">
                        
                        <!-- Pills/Badges + Cursor Input -->
                        <div class="flex-1 min-w-0 p-1.5 flex items-center flex-wrap gap-1.5">
                            <template x-for="(dept, idx) in selectedDepts" :key="idx">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold shrink-0"
                                      style="background-color: #E8F2FE; color: #1877F2; border: 1px solid #CDE2FC;">
                                    <span x-text="dept"></span>
                                    <button type="button" 
                                            @click.stop="removeDept(idx)" 
                                            class="hover:opacity-75 focus:outline-none cursor-pointer"
                                            style="color: #1877F2;"
                                            title="Hapus stasiun ini">
                                        <i class="fa-solid fa-circle-xmark text-[11px]"></i>
                                    </button>
                                </span>
                            </template>

                            <input type="text" 
                                   x-model="deptSearch" 
                                   @focus="isDeptDropdownOpen = true" 
                                   @click="isDeptDropdownOpen = true" 
                                   placeholder="Ketik & Pilih..." 
                                   class="flex-1 min-w-[90px] border-0 focus:ring-0 p-1 text-xs font-medium text-slate-800 placeholder-slate-400 bg-transparent focus:outline-none">
                        </div>

                        <!-- Tombol Hapus Semua (Red X Button Suffix) -->
                        <div class="border-l border-slate-200 flex items-center justify-center px-2.5 shrink-0">
                            <button type="button" 
                                    x-show="selectedDepts.length > 0" 
                                    @click="clearAllDepts()" 
                                    class="text-rose-500 hover:text-rose-700 font-bold text-xs cursor-pointer p-1"
                                    title="Hapus semua stasiun">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Keterangan Urutan Sesuai Referensi Lampiran 0 -->
                    <p class="text-[11px] text-slate-500 mt-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-slate-400 text-xs"></i>
                        <span>Urutan routing sesuai urutan input.</span>
                    </p>

                    <!-- Dropdown Pilihan Stasiun Mesin (Persis Lampiran 1) -->
                    <div x-show="isDeptDropdownOpen" 
                         x-cloak 
                         style="display: none;"
                         class="absolute left-0 right-0 z-50 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-slate-100">
                        
                        <template x-for="dept in filteredDepartments()" :key="dept">
                            <div @click="addDept(dept)" 
                                 class="px-3.5 py-2 text-xs font-medium text-slate-800 hover:bg-slate-50 hover:text-blue-600 cursor-pointer transition flex items-center justify-between">
                                <span x-text="dept"></span>
                                <span x-show="selectedDepts.includes(dept)" class="text-[10px] text-blue-600 font-bold bg-blue-50 px-1.5 py-0.5 rounded">Terpilih</span>
                            </div>
                        </template>

                        <div x-show="filteredDepartments().length === 0" class="px-3.5 py-2 text-xs text-slate-400 italic">
                            Tidak ada stasiun mesin yang cocok.
                        </div>
                    </div>
                </div>

                <!-- 3. Alasan Revisi * (Persis Lampiran 0) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Alasan Revisi <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <textarea name="reason" 
                              required 
                              rows="3" 
                              x-model="reasonText" 
                              placeholder="Wajib diisi..." 
                              class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition resize-none"></textarea>
                </div>

                <!-- 4. Tombol Utama (Full-Width Blue Button - Persis Lampiran 0) -->
                <button type="submit" 
                        :disabled="isSubmittingRouting || selectedDepts.length === 0"
                        class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] font-bold text-xs sm:text-sm rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                        style="background-color: #1877F2 !important; color: #ffffff !important;">
                    <i class="fa-solid fa-spinner fa-spin text-xs" x-show="isSubmittingRouting"></i>
                    <span x-text="isSubmittingRouting ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </form>
        </div>
    </div>
</div>

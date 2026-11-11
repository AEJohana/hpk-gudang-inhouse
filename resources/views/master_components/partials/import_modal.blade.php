{{-- Modal Bulk Import Master Komponen Karoseri --}}
<div x-data="componentImportModal()" 
     x-cloak 
     @open-import-modal.window="openModal()" 
     @keydown.escape.window="closeModal()" 
     class="relative z-50">

    {{-- Backdrop --}}
    <div x-show="isOpen" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity"
         @click="if(!isProcessing) closeModal()">
    </div>

    {{-- Modal Box --}}
    <div x-show="isOpen" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-3 sm:p-6"
         :class="{ 'pointer-events-none': isProcessing }">

        <div class="relative bg-white rounded-3xl shadow-2xl border border-slate-200/80 w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden text-left pointer-events-auto">

            {{-- Modal Header --}}
            <div class="px-4 sm:px-6 py-3.5 sm:py-4.5 bg-gradient-to-r from-[#0a2342] to-[#123966] text-white flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/10 flex items-center justify-center text-teal-400 text-base sm:text-lg border border-white/10 shrink-0">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-black tracking-wide text-white truncate">Import Massal Komponen</h3>
                        <p class="text-[11px] sm:text-xs text-slate-300 font-medium truncate">Tambah atau perbarui katalog via Excel</p>
                    </div>
                </div>
                <button type="button" 
                        @click="closeModal()" 
                        :disabled="isProcessing"
                        class="text-slate-300 hover:text-white p-2 rounded-xl hover:bg-white/10 transition cursor-pointer disabled:opacity-40 shrink-0">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            {{-- Stepper Progress Bar --}}
            <div class="px-3 sm:px-6 py-2 sm:py-2.5 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between text-[11px] sm:text-xs font-semibold shrink-0">
                <div class="flex items-center space-x-1.5 sm:space-x-2" :class="step >= 1 ? 'text-[#0a2342]' : 'text-slate-400'">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] sm:text-[11px] font-bold"
                          :class="step > 1 ? 'bg-teal-600 text-white' : (step === 1 ? 'bg-[#0a2342] text-white' : 'bg-slate-200 text-slate-600')">
                        <template x-if="step > 1">
                            <i class="fa-solid fa-check text-[9px]"></i>
                        </template>
                        <template x-if="step <= 1">
                            <span>1</span>
                        </template>
                    </span>
                    <span>Upload</span>
                </div>
                <div class="h-0.5 w-6 sm:w-16 bg-slate-200" :class="{ 'bg-teal-600': step > 1 }"></div>
                <div class="flex items-center space-x-1.5 sm:space-x-2" :class="step >= 2 ? 'text-[#0a2342]' : 'text-slate-400'">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] sm:text-[11px] font-bold"
                          :class="step > 2 ? 'bg-teal-600 text-white' : (step === 2 ? 'bg-[#0a2342] text-white' : 'bg-slate-200 text-slate-600')">
                        <template x-if="step > 2">
                            <i class="fa-solid fa-check text-[9px]"></i>
                        </template>
                        <template x-if="step <= 2">
                            <span>2</span>
                        </template>
                    </span>
                    <span>Preview</span>
                </div>
                <div class="h-0.5 w-6 sm:w-16 bg-slate-200" :class="{ 'bg-teal-600': step > 2 }"></div>
                <div class="flex items-center space-x-1.5 sm:space-x-2" :class="step >= 3 ? 'text-[#0a2342]' : 'text-slate-400'">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] sm:text-[11px] font-bold"
                          :class="step === 3 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'">
                        <span>3</span>
                    </span>
                    <span>Selesai</span>
                </div>
            </div>

            {{-- Modal Body (Scrollable) --}}
            <div class="p-6 overflow-y-auto space-y-5 flex-1">

                {{-- Alert Notification --}}
                <div x-show="errorMessage" 
                     x-transition 
                     class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start space-x-3">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <p class="font-bold text-rose-900">Perhatian</p>
                        <p x-text="errorMessage" class="mt-0.5 leading-relaxed"></p>
                    </div>
                </div>

                {{-- STEP 1: UPLOAD & OPSI --}}
                <div x-show="step === 1" class="space-y-5">
                    
                    {{-- Download Format Excel Card --}}
                    <div class="bg-gradient-to-r from-teal-50 to-emerald-50 rounded-2xl p-4 sm:p-5 border border-teal-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center text-lg shrink-0 shadow-sm">
                                <i class="fa-solid fa-file-arrow-down"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide">Format Resmi Excel (2 Sheet)</h4>
                                <p class="text-xs text-slate-600 mt-1">
                                    Unduh template resmi: <strong>Sheet 1</strong> formulir input, <strong>Sheet 2</strong> kamus kategori, satuan resmi & kode rak fisik aktif.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('components.import.template') }}" 
                           class="inline-flex items-center justify-center px-4 py-2.5 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-xl shadow-md transition shrink-0 whitespace-nowrap">
                            <i class="fa-solid fa-download me-2"></i>
                            Download Template Excel
                        </a>
                    </div>

                    {{-- Dropzone / File Picker --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-2">
                            Pilih File Excel / CSV (<span class="text-rose-500 font-bold">*</span>)
                        </label>
                        <div class="relative border-2 border-dashed rounded-2xl p-6 text-center transition cursor-pointer hover:border-teal-500 hover:bg-teal-50/20"
                             :class="file ? 'border-teal-500 bg-teal-50/30' : 'border-slate-300 bg-slate-50/50'"
                             @dragover.prevent=""
                             @drop.prevent="handleFileDrop($event)">
                            
                            <input type="file" 
                                   id="bulkImportFileInput" 
                                   accept=".xlsx, .xls, .csv" 
                                   @change="handleFileSelect($event)" 
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">

                            <template x-if="!file">
                                <div class="space-y-2 pointer-events-none">
                                    <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                    </div>
                                    <div class="text-xs font-bold text-slate-700">
                                        Seret file Excel ke sini atau <span class="text-teal-700 underline">klik untuk mencari file</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400">Mendukung format .xlsx, .xls, .csv (Maksimal 10 MB)</p>
                                </div>
                            </template>

                            <template x-if="file">
                                <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-teal-200 shadow-sm pointer-events-none">
                                    <div class="flex items-center space-x-3 text-left">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-base">
                                            <i class="fa-solid fa-file-excel"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-900" x-text="file.name"></p>
                                            <p class="text-[10px] text-slate-400" x-text="formatFileSize(file.size)"></p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-teal-700 bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-200">
                                        Siap Diproses
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Duplicate Handling Options --}}
                    <div class="bg-slate-50 p-4.5 rounded-2xl border border-slate-200/80 space-y-3">
                        <label class="block text-xs font-bold text-slate-800">
                            Kebijakan Data Duplikat (Jika Kode Part Sudah Ada di Database):
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="relative flex items-start p-3 bg-white rounded-xl border cursor-pointer transition"
                                   :class="duplicateAction === 'skip' ? 'border-teal-600 ring-2 ring-teal-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" name="duplicate_action" value="skip" x-model="duplicateAction" class="mt-0.5 text-teal-600 focus:ring-teal-500">
                                <div class="ms-3 text-left">
                                    <span class="block text-xs font-extrabold text-slate-900">Lewati Duplikat (Rekomendasi)</span>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">Pertahankan data lama. Hanya entri part baru yang disimpan.</span>
                                </div>
                            </label>

                            <label class="relative flex items-start p-3 bg-white rounded-xl border cursor-pointer transition"
                                   :class="duplicateAction === 'update' ? 'border-teal-600 ring-2 ring-teal-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" name="duplicate_action" value="update" x-model="duplicateAction" class="mt-0.5 text-teal-600 focus:ring-teal-500">
                                <div class="ms-3 text-left">
                                    <span class="block text-xs font-extrabold text-slate-900">Perbarui (Update) Duplikat</span>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">Timpa spesifikasi, kategori, rak & stok dengan data Excel terbaru.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Quick Tips Box --}}
                    <div class="p-3.5 bg-amber-50/80 rounded-2xl border border-amber-200 text-amber-900 text-xs flex items-start space-x-2.5">
                        <i class="fa-solid fa-lightbulb text-amber-600 text-sm shrink-0 mt-0.5"></i>
                        <div class="leading-relaxed text-[11px]">
                            <strong>Tips Cepat:</strong> Kolom wajib hanya <strong>Kode Part</strong> dan <strong>Nama Komponen</strong>. Kolom lain (kategori, dimensi, foto) boleh dikosongkan. Foto komponen bisa diisi tautan Google Drive / Web, atau dikosongkan terlebih dahulu.
                        </div>
                    </div>
                </div>

                {{-- STEP 2: PREVIEW & REVIEW --}}
                <div x-show="step === 2" class="space-y-4">

                    {{-- KPI Cards Summary --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" x-show="previewData">
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Baris</span>
                            <span class="text-xl font-black text-slate-800" x-text="previewData?.summary?.total_rows ?? 0"></span>
                        </div>
                        <div class="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200">
                            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Part Baru</span>
                            <span class="text-xl font-black text-emerald-800" x-text="previewData?.summary?.new_items ?? 0"></span>
                        </div>
                        <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200">
                            <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block"
                                  x-text="duplicateAction === 'update' ? 'Duplikat (Diupdate)' : 'Duplikat (Dilewati)'"></span>
                            <span class="text-xl font-black text-amber-800" 
                                  x-text="duplicateAction === 'update' ? (previewData?.summary?.duplicate_update ?? 0) : (previewData?.summary?.duplicate_skip ?? 0)"></span>
                        </div>
                        <div class="p-3.5 bg-rose-50 rounded-2xl border border-rose-200">
                            <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">Error / Tidak Valid</span>
                            <span class="text-xl font-black text-rose-800" x-text="previewData?.summary?.errors ?? 0"></span>
                        </div>
                    </div>

                    {{-- Table Container --}}
                    <div>
                        <div class="sm:hidden flex items-center justify-between text-[10px] text-slate-500 mb-1.5 px-1">
                            <span class="inline-flex items-center gap-1 font-medium text-slate-600">
                                <i class="fa-solid fa-arrows-left-right text-teal-600"></i> Geser tabel ke samping
                            </span>
                            <span class="font-mono font-bold text-slate-400" x-text="(previewData?.rows?.length || 0) + ' Baris'"></span>
                        </div>
                        <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
                            <div class="max-h-72 sm:max-h-80 overflow-x-auto overflow-y-auto">
                                <table class="w-full text-left text-xs min-w-[720px]">
                                <thead class="bg-slate-100 text-slate-600 font-bold border-b border-slate-200 sticky top-0 z-10 text-[11px]">
                                    <tr>
                                        <th class="py-2.5 px-3">No</th>
                                        <th class="py-2.5 px-3">Kode Part</th>
                                        <th class="py-2.5 px-3">Nama Komponen</th>
                                        <th class="py-2.5 px-3">Kategori</th>
                                        <th class="py-2.5 px-3">Satuan</th>
                                        <th class="py-2.5 px-3">Dimensi</th>
                                        <th class="py-2.5 px-3">Rak</th>
                                        <th class="py-2.5 px-3 text-center">Stok Awal</th>
                                        <th class="py-2.5 px-3">Foto</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="(row, idx) in (previewData?.rows || [])" :key="idx">
                                        <tr :class="row.status === 'error' ? 'bg-rose-50/50' : (row.status === 'duplicate_skip' ? 'bg-amber-50/30' : '')">
                                            <td class="py-2 px-3 text-slate-400 font-mono text-[10px]" x-text="row.row_number"></td>
                                            <td class="py-2 px-3 font-mono font-bold text-slate-900" x-text="row.part_number || '-'"></td>
                                            <td class="py-2 px-3 font-medium text-slate-800" x-text="row.name || '-'"></td>
                                            <td class="py-2 px-3 text-slate-600 text-[11px]" x-text="row.category || '-'"></td>
                                            <td class="py-2 px-3 font-semibold text-slate-700" x-text="row.uom || 'PCS'"></td>
                                            <td class="py-2 px-3 text-slate-500 font-mono text-[10px]">
                                                <span x-text="(row.length_mm || row.width_or_diameter_mm || row.thickness_or_height_mm) ? (row.length_mm || 0) + '×' + (row.width_or_diameter_mm || 0) + '×' + (row.thickness_or_height_mm || 0) : '-'"></span>
                                            </td>
                                            <td class="py-2 px-3">
                                                <template x-if="row.location_code">
                                                    <span class="px-1.5 py-0.5 rounded bg-teal-50 text-teal-800 font-mono text-[10px] font-bold" x-text="row.location_code"></span>
                                                </template>
                                                <template x-if="!row.location_code">
                                                    <span class="text-slate-400 text-[10px]">-</span>
                                                </template>
                                            </td>
                                            <td class="py-2 px-3 text-center font-bold text-slate-800" x-text="row.initial_stock || 0"></td>
                                            <td class="py-2 px-3">
                                                <template x-if="row.image_url">
                                                    <span class="text-teal-700 font-semibold text-[10px] inline-flex items-center gap-1" title="Tautan Gambar Terdeteksi">
                                                        <i class="fa-solid fa-image"></i> Ada
                                                    </span>
                                                </template>
                                                <template x-if="!row.image_url">
                                                    <span class="text-slate-400 text-[10px]">-</span>
                                                </template>
                                            </td>
                                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                                {{-- Status Badges --}}
                                                <template x-if="row.status === 'new'">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                        <i class="fa-solid fa-plus me-1 text-[9px]"></i> Baru
                                                    </span>
                                                </template>
                                                <template x-if="row.status === 'duplicate_skip'">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                        <i class="fa-solid fa-forward me-1 text-[9px]"></i> Lewati
                                                    </span>
                                                </template>
                                                <template x-if="row.status === 'duplicate_update'">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                        <i class="fa-solid fa-pen-to-square me-1 text-[9px]"></i> Update
                                                    </span>
                                                </template>
                                                <template x-if="row.status === 'error'">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800"
                                                          :title="row.error_message">
                                                        <i class="fa-solid fa-triangle-exclamation me-1 text-[9px]"></i>
                                                        <span x-text="row.error_message || 'Error'"></span>
                                                    </span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Notice info --}}
                    <p class="text-[11px] text-slate-500 italic">
                        * Baris dengan status <strong>Error</strong> otomatis diabaikan saat penyimpanan dan tidak akan merusak data lain.
                    </p>
                </div>

                {{-- STEP 3: RESULT SUMMARY --}}
                <div x-show="step === 3" class="text-center py-6 space-y-4">
                    <div class="w-16 h-16 rounded-3xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-3xl mx-auto shadow-md">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>
                        <h4 class="text-lg font-black text-slate-900">Import Komponen Selesai!</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto" x-text="successMessage"></p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-lg mx-auto pt-2" x-show="resultData">
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-400 block uppercase">Total Masuk</span>
                            <span class="text-lg font-black text-slate-800" x-text="resultData?.total_processed ?? 0"></span>
                        </div>
                        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200">
                            <span class="text-[10px] font-bold text-emerald-700 block uppercase">Baru</span>
                            <span class="text-lg font-black text-emerald-800" x-text="resultData?.inserted ?? 0"></span>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-2xl border border-blue-200">
                            <span class="text-[10px] font-bold text-blue-700 block uppercase">Diupdate</span>
                            <span class="text-lg font-black text-blue-800" x-text="resultData?.updated ?? 0"></span>
                        </div>
                        <div class="p-3 bg-amber-50 rounded-2xl border border-amber-200">
                            <span class="text-[10px] font-bold text-amber-700 block uppercase">Dilewati</span>
                            <span class="text-lg font-black text-amber-800" x-text="resultData?.skipped ?? 0"></span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-3 sm:px-6 sm:py-4 bg-slate-50 border-t border-slate-200/80 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-2.5 shrink-0">
                
                {{-- Left Actions --}}
                <div>
                    <template x-if="step === 2">
                        <button type="button" 
                                @click="step = 1; errorMessage = ''" 
                                :disabled="isProcessing"
                                class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            <span>Ganti File / Opsi</span>
                        </button>
                    </template>
                </div>

                {{-- Right Actions --}}
                <div>
                    {{-- Step 1 Buttons --}}
                    <template x-if="step === 1">
                        <div class="flex items-center space-x-2">
                            <button type="button" 
                                    @click="closeModal()" 
                                    :disabled="isProcessing"
                                    class="flex-1 sm:flex-initial px-4 py-2.5 sm:py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition cursor-pointer disabled:opacity-50 text-center">
                                Batal
                            </button>
                            <button type="button" 
                                    @click="submitPreview()" 
                                    :disabled="!file || isProcessing"
                                    class="flex-1 sm:flex-initial inline-flex items-center justify-center px-5 py-2.5 sm:py-2 bg-[#0a2342] hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-md transition cursor-pointer disabled:opacity-50">
                                <span x-show="!isProcessing" class="inline-flex items-center gap-1.5">
                                    <span>Tampilkan Preview</span>
                                    <i class="fa-solid fa-arrow-right text-teal-400"></i>
                                </span>
                                <span x-show="isProcessing" class="inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                                    <span>Membaca File...</span>
                                </span>
                            </button>
                        </div>
                    </template>

                    {{-- Step 2 Buttons --}}
                    <template x-if="step === 2">
                        <div class="flex items-center space-x-2">
                            <button type="button" 
                                    @click="closeModal()" 
                                    :disabled="isProcessing"
                                    class="px-3.5 sm:px-4 py-2.5 sm:py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition cursor-pointer disabled:opacity-50 text-center">
                                Batal
                            </button>
                            <button type="button" 
                                    @click="submitConfirm()" 
                                    :disabled="isProcessing || !previewData || previewData.summary.valid_rows === 0"
                                    class="flex-1 sm:flex-initial inline-flex items-center justify-center px-4 sm:px-5 py-2.5 sm:py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md transition cursor-pointer disabled:opacity-50">
                                <span x-show="!isProcessing" class="inline-flex items-center gap-1.5 text-center">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span>Konfirmasi & Simpan</span>
                                </span>
                                <span x-show="isProcessing" class="inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                                    <span>Menyimpan...</span>
                                </span>
                            </button>
                        </div>
                    </template>

                    {{-- Step 3 Buttons --}}
                    <template x-if="step === 3">
                        <button type="button" 
                                @click="finishAndReload()" 
                                class="w-full sm:w-auto px-5 py-2.5 sm:py-2 bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs rounded-xl shadow-md transition cursor-pointer text-center">
                            <i class="fa-solid fa-rotate-right me-1.5"></i>
                            Selesai & Muat Ulang Halaman
                        </button>
                    </template>
                </div>
            </div>

        </div>
    </div>
</div>
</div>

<script>
function componentImportModal() {
    return {
        isOpen: false,
        step: 1,
        isProcessing: false,
        file: null,
        duplicateAction: 'skip',
        previewData: null,
        resultData: null,
        errorMessage: '',
        successMessage: '',

        openModal() {
            this.isOpen = true;
            this.step = 1;
            this.file = null;
            this.duplicateAction = 'skip';
            this.previewData = null;
            this.resultData = null;
            this.errorMessage = '';
            this.successMessage = '';
            const fileInput = document.getElementById('bulkImportFileInput');
            if (fileInput) fileInput.value = '';
        },

        closeModal() {
            if (this.isProcessing) return;
            this.isOpen = false;
        },

        handleFileSelect(event) {
            const files = event.target.files;
            if (files && files.length > 0) {
                this.file = files[0];
                this.errorMessage = '';
            }
        },

        handleFileDrop(event) {
            const files = event.dataTransfer.files;
            if (files && files.length > 0) {
                this.file = files[0];
                this.errorMessage = '';
            }
        },

        formatFileSize(bytes) {
            if (!bytes || bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        async submitPreview() {
            if (!this.file) {
                this.errorMessage = 'Silakan pilih file Excel (.xlsx / .csv) terlebih dahulu.';
                return;
            }

            this.isProcessing = true;
            this.errorMessage = '';

            const formData = new FormData();
            formData.append('file', this.file);
            formData.append('duplicate_action', this.duplicateAction);

            try {
                const response = await fetch('{{ route("components.import.preview") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Gagal membaca isi file Excel.');
                }

                this.previewData = result.data;
                this.step = 2;
            } catch (err) {
                this.errorMessage = err.message || 'Terjadi kesalahan saat memproses file Excel.';
            } finally {
                this.isProcessing = false;
            }
        },

        async submitConfirm() {
            if (!this.previewData || !this.previewData.preview_token) {
                this.errorMessage = 'Data preview tidak ditemukan atau telah kedaluwarsa. Silakan upload ulang file.';
                this.step = 1;
                return;
            }

            this.isProcessing = true;
            this.errorMessage = '';

            try {
                const response = await fetch('{{ route("components.import.confirm") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        preview_token: this.previewData.preview_token,
                        duplicate_action: this.duplicateAction
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Gagal menyimpan komponen ke database.');
                }

                this.resultData = result.data;
                this.successMessage = result.message;
                this.step = 3;
            } catch (err) {
                this.errorMessage = err.message || 'Terjadi kesalahan saat menyimpan data.';
            } finally {
                this.isProcessing = false;
            }
        },

        finishAndReload() {
            window.location.reload();
        }
    };
}
</script>

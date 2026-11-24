<x-admin-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 240px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-12 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-sliders me-1 text-[9px]"></i> OPERASIONAL
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Kendali Proses Lapangan
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div class="flex flex-col items-start gap-1 sm:gap-1.5">
                        <div>
                            <a href="{{ route('admin.dashboard') }}" 
                               class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer shrink-0" 
                               title="Kembali ke Pusat Kendali Administrator">
                                <i class="fa-solid fa-arrow-left text-[11px] text-amber-300"></i>
                                <span>Kembali</span>
                            </a>
                        </div>
                        <h1 class="font-black text-xl sm:text-3xl text-white tracking-wide leading-tight">
                            Pengaturan Operasional Lapangan
                        </h1>
                        <p class="text-xs text-slate-200 font-normal">
                            Konfigurasi dinamis alur pengerjaan mesin, pemindaian QR code fisik, dan logistik pabrik
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center space-x-3 text-emerald-800 text-xs font-bold shadow-xs">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-check text-sm"></i>
                </div>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        <!-- 2. FORM PENGATURAN OPERASIONAL -->
        <form action="{{ route('admin.operational-settings.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- KARTU 1: ALUR WORK REQUEST & MATERIAL HANDLING (SCAN QR KENDALI) -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden" 
                 x-data="{ isScanActive: {{ $wrScanSetting->value == '1' ? 'true' : 'false' }} }">
                
                <!-- Header Kartu -->
                <div class="p-6 sm:p-7 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/40">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-[#0a2342] text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0">
                                <i class="fa-solid fa-qrcode"></i>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-extrabold text-slate-900 text-base sm:text-lg">Alur Work Request (WR) & Material Handling</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider transition"
                                          :class="isScanActive ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-600 border border-slate-300'"
                                          x-text="isScanActive ? 'SCAN AKTIF (WAJIB)' : 'MODE KLIK (NONAKTIF)'">
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Kendali pemindaian QR code lembar kerja untuk validasi fisik pengerjaan mesin dan perpindahan logistik
                                </p>
                            </div>
                        </div>

                        <!-- Interactive iOS Style Switch Toggle (Zero CSS-Hack, Rock-Solid Alpine.js Knob) -->
                        <div class="flex items-center space-x-3 bg-white p-2 sm:p-2.5 rounded-2xl border border-slate-200 shadow-2xs self-start sm:self-auto cursor-pointer"
                             @click="isScanActive = !isScanActive">
                            <span class="text-xs font-extrabold select-none transition-colors"
                                  :class="isScanActive ? 'text-emerald-700' : 'text-slate-600'"
                                  x-text="isScanActive ? 'Wajib Scan: ON' : 'Wajib Scan: OFF'"></span>

                            <input type="hidden" name="settings[wr_scan_required]" :value="isScanActive ? '1' : '0'">

                            <!-- Tombol Switch Nyata dengan Knob Bulat Bergerak (Alpine.js) -->
                            <button type="button" 
                                    @click.stop="isScanActive = !isScanActive" 
                                    role="switch" 
                                    :aria-checked="isScanActive"
                                    class="relative inline-flex items-center shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none shadow-inner"
                                    :class="isScanActive ? 'bg-emerald-500' : 'bg-slate-300'"
                                    :style="isScanActive ? 'width: 60px; height: 32px; padding: 3px; background-color: #10b981;' : 'width: 60px; height: 32px; padding: 3px; background-color: #cbd5e1;'">
                                <span class="sr-only">Toggle Wajib Scan</span>
                                <!-- Knob / Bulat-bulat putih dengan icon dan bayangan timbul -->
                                <span class="pointer-events-none inline-flex items-center justify-center rounded-full bg-white shadow-md transition-transform duration-200 ease-in-out border border-black/5"
                                      :style="isScanActive ? 'width: 26px; height: 26px; transform: translateX(28px);' : 'width: 26px; height: 26px; transform: translateX(0px);'">
                                    <i class="fa-solid text-[10px] transition-colors"
                                       :class="isScanActive ? 'fa-check text-emerald-600 font-black' : 'fa-xmark text-slate-400 font-bold'"></i>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Detail Alur & Rincian Perilaku Sistem -->
                <div class="p-6 sm:p-7 space-y-6">
                    
                    <!-- Dynamic Banner Informasi Mode Aktif/Nonaktif -->
                    <div class="p-4 rounded-2xl border transition-all"
                         :class="isScanActive ? 'bg-emerald-50/70 border-emerald-200 text-emerald-950' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-sm shrink-0"
                                 :class="isScanActive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'">
                                <i :class="isScanActive ? 'fa-solid fa-camera' : 'fa-solid fa-hand-pointer'"></i>
                            </div>
                            <div class="text-xs space-y-1">
                                <div class="font-extrabold text-sm"
                                     x-text="isScanActive ? 'Fitur Wajib Scan QR Sedang AKTIF' : 'Fitur Scan QR Sedang NONAKTIF (Mode Bebas / Klik Tombol)'"></div>
                                <p class="leading-relaxed text-slate-600" x-show="isScanActive">
                                    Setiap operator atau staf gudang yang memproses tahapan fisik <strong>wajib memindai kode QR</strong> pada lembar fisik tiket pengerjaan sebelum tombol eksekusi terbuka. Ini memastikan barang fisik benar-benar ada di stasiun mesin atau rak gudang.
                                </p>
                                <p class="leading-relaxed text-slate-600" x-show="!isScanActive">
                                    Operator dan staf gudang dapat memajukan status tiket secara leluasa hanya dengan <strong>menekan tombol aksi di antarmuka</strong> tanpa perlu menyalakan kamera scanner barcode/QR (cocok untuk pengujian cepat atau saat perangkat scanner belum tersedia).
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Matriks Tahapan yang Terdampak -->
                    <div>
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <i class="fa-solid fa-list-check text-slate-400"></i>
                            <span>Daftar Tahapan Operasional yang Terpengaruh</span>
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            
                            <!-- 1. Approval Pengaju & Penerima (Selalu Manual) -->
                            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-700 font-extrabold text-[11px] flex items-center justify-center">1</span>
                                        <span>Approval Pengaju & Penerima</span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Selalu Klik Tombol
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-normal">
                                    Persetujuan otorisasi kepala bagian tetap berupa klik tombol biasa tanpa scanner demi kemudahan administratif.
                                </p>
                            </div>

                            <!-- 2. Mulai Pengerjaan Mesin (Proses In) -->
                            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-1.5"
                                 :class="isScanActive ? 'border-teal-200 bg-teal-50/20' : ''">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 font-extrabold text-[11px] flex items-center justify-center">2</span>
                                        <span>Mulai Proses Mesin (In)</span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold transition"
                                          :class="isScanActive ? 'bg-teal-100 text-teal-800 border border-teal-300' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                          x-text="isScanActive ? 'Wajib Scan Lembar WR' : 'Klik Tombol Mulai'">
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-normal">
                                    Operator mesin memindai QR sheet sebelum menyalakan mesin untuk verifikasi part fisik yang diterima dari antrean.
                                </p>
                            </div>

                            <!-- 3. Selesai Pengerjaan Mesin (Proses Out) -->
                            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-1.5"
                                 :class="isScanActive ? 'border-indigo-200 bg-indigo-50/20' : ''">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-700 font-extrabold text-[11px] flex items-center justify-center">3</span>
                                        <span>Selesai Proses Mesin (Out)</span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold transition"
                                          :class="isScanActive ? 'bg-indigo-100 text-indigo-800 border border-indigo-300' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                          x-text="isScanActive ? 'Wajib Scan Lembar WR' : 'Klik Input Qty/Defect'">
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-normal">
                                    Mencatat hasil pengerjaan bagus dan reject mesin dengan memvalidasi QR tiket fisik pengerjaan.
                                </p>
                            </div>

                            <!-- 4. Penjemputan Logistik Material Handling -->
                            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-1.5"
                                 :class="isScanActive ? 'border-amber-200 bg-amber-50/20' : ''">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-700 font-extrabold text-[11px] flex items-center justify-center">4</span>
                                        <span>Material Handling Pickup (MH)</span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold transition"
                                          :class="isScanActive ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                          x-text="isScanActive ? 'Wajib Scan QR Penjemputan' : 'Klik Ambil Barang'">
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-normal">
                                    Petugas kurir logistik MH memindai QR lembar tiket sebelum membawa komponen ke stasiun mesin lanjutan atau gudang.
                                </p>
                            </div>

                            <!-- 5. Penerimaan di Rak Gudang (Sudah Diambil) -->
                            <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-1.5 md:col-span-2"
                                 :class="isScanActive ? 'border-emerald-200 bg-emerald-50/20' : ''">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 font-extrabold text-[11px] flex items-center justify-center">5</span>
                                        <span>Penerimaan Barang Jadi di Gudang (Sudah Diambil)</span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold transition"
                                          :class="isScanActive ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                          x-text="isScanActive ? 'Wajib Scan QR Serah Terima' : 'Klik Selesai Masuk Rak'">
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-normal">
                                    Petugas gudang memindai QR etiket tiket untuk serah terima fisik barang masuk rak, yang otomatis mengupdate saldo stok fisik gudang.
                                </p>
                            </div>

                        </div>
                    </div>

                </div>

            </div>

            <!-- KARTU 2: SLOT PENGATURAN OPERASIONAL LANJUTAN (MODULAR) -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 sm:p-7 space-y-4">
                <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Toleransi Keterlambatan Pengerjaan (SLA)</h3>
                        <p class="text-xs text-slate-500">Ambang batas waktu maksimal pengerjaan tiket Work Request sebelum memicu peringatan</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center pt-2">
                    <div class="md:col-span-1">
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">Batas Waktu Toleransi</label>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Memicu banner merah jika belum selesai</span>
                    </div>
                    <div class="md:col-span-2 flex items-center space-x-2">
                        <input type="number" 
                               value="72" 
                               readonly 
                               class="w-28 px-3.5 py-2 text-xs font-mono font-bold rounded-xl border border-slate-200 bg-slate-50 text-slate-600 cursor-not-allowed">
                        <span class="text-xs font-semibold text-slate-500">Jam (Standar 3 Hari Kerja HPK)</span>
                    </div>
                </div>
            </div>

            <!-- TOMBOL SIMPAN PENGATURAN -->
            <div class="flex items-center justify-end space-x-3 pt-2">
                <a href="{{ route('admin.dashboard') }}" 
                   class="px-5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                    Batal
                </a>
                <button type="submit" 
                        style="background-color: #0a2342 !important; color: #ffffff !important;"
                        class="px-6 py-2.5 hover:bg-slate-800 active:scale-98 font-extrabold text-xs rounded-xl shadow-xs transition flex items-center space-x-2 cursor-pointer select-none">
                    <i class="fa-solid fa-floppy-disk text-xs text-amber-300"></i>
                    <span>Simpan Pengaturan Operasional</span>
                </button>
            </div>

        </form>

    </div>
</x-admin-layout>

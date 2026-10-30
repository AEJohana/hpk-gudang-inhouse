<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('components.index') }}" 
               class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer"
               title="Kembali ke Master Komponen">
                <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                <span>Kembali</span>
            </a>
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                    Input Master Komponen Karoseri
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftarkan komponen baru beserta spesifikasi & foto fisik untuk memudahkan pengenalan barang</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="POST" action="{{ route('components.store') }}" enctype="multipart/form-data" 
              x-data="cameraCapture()" 
              class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf

            <!-- Section 1: Photo & Visual Recognition (Upload or Live Camera Snapshot) -->
            <div class="p-5 bg-slate-50 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900">Foto Fisik Komponen</h3>
                        <p class="text-xs text-slate-500">Penting untuk membedakan part mirip di gudang karoseri (Upload file atau ambil dari kamera)</p>
                    </div>
                    <!-- Toggle Buttons -->
                    <div class="flex space-x-2">
                        <button type="button" 
                                @click="mode = 'upload'; stopCamera();" 
                                :class="mode === 'upload' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-300'"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition">
                            Upload File
                        </button>
                        <button type="button" 
                                @click="mode = 'camera'; startCamera();" 
                                :class="mode === 'camera' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-white text-slate-700 border border-slate-300'"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition flex items-center">
                            <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Ambil Kamera
                        </button>
                    </div>
                </div>

                <!-- Mode 1: File Upload -->
                <div x-show="mode === 'upload'" class="flex items-center space-x-4">
                    <div class="w-24 h-24 rounded-xl border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden flex-shrink-0 p-1">
                        <template x-if="isPdf">
                            <div class="text-center">
                                <i class="fa-solid fa-file-pdf text-rose-500 text-2xl"></i>
                                <span class="text-[9px] font-bold text-slate-600 block truncate max-w-[80px]" x-text="pdfFileName"></span>
                            </div>
                        </template>
                        <template x-if="!isPdf && imagePreview">
                            <img :src="imagePreview" class="w-full h-full object-contain">
                        </template>
                        <template x-if="!isPdf && !imagePreview">
                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </template>
                    </div>
                    <div class="flex-1">
                        <input type="file" 
                               name="image" 
                               id="fileInput"
                               accept="image/*,application/pdf" 
                               @change="previewFile($event)"
                               class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                        <p class="text-[11px] text-slate-400 mt-1">Mendukung format gambar (JPEG, PNG, WEBP, SVG) atau Dokumen CAD (PDF) hingga 15MB</p>
                    </div>
                </div>

                <!-- Mode 2: Live Camera Snapshot -->
                <div x-show="mode === 'camera'" style="display: none;" class="space-y-3">
                    <div class="relative bg-slate-950 rounded-xl overflow-hidden max-w-sm mx-auto aspect-video flex items-center justify-center">
                        <video x-ref="videoElem" class="w-full h-full object-cover" autoplay playsinline></video>
                        <canvas x-ref="canvasElem" style="display: none;"></canvas>

                        <!-- Live badge -->
                        <div class="absolute top-2 left-2 bg-red-600/90 text-white text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center shadow">
                            <span class="w-1.5 h-1.5 rounded-full bg-white me-1 animate-ping"></span>
                            KAMERA LIVE
                        </div>
                    </div>

                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" @click="takeSnapshot()" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow transition flex items-center">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Jepret Foto (Snapshot)
                        </button>
                    </div>

                    <!-- Snapshot Preview Thumbnail -->
                    <template x-if="cameraSnapshot">
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center space-x-3">
                            <img :src="cameraSnapshot" class="w-16 h-16 rounded-lg object-cover border border-emerald-300">
                            <div>
                                <span class="text-xs font-bold text-emerald-800 block">Foto Berhasil Dijepret!</span>
                                <span class="text-[11px] text-emerald-600">Foto ini akan otomatis disimpan sebagai gambar komponen.</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Hidden field for camera base64 -->
                <input type="hidden" name="camera_snapshot" :value="cameraSnapshot">
            </div>

            <!-- Section 2: Component Identifiers -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Kode Komponen <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="part_number" 
                           value="{{ old('part_number') }}" 
                           required
                           placeholder="Contoh: CSFP10300250081 atau HYD-CYL-160"
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono font-bold">
                    <p class="text-[11px] text-slate-500 mt-1">Kode komponen wajib diisi sebagai kunci pengesahan barang masuk ke gudang.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Kategori Komponen <span class="text-slate-400 font-normal">(Opsional / Perlu Diisi)</span>
                    </label>
                    <select name="category" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-medium">
                        <option value="">-- Pilih Kategori Resmi --</option>
                        <option value="subframe" {{ old('category') == 'subframe' ? 'selected' : '' }}>1. Subframe</option>
                        <option value="assy" {{ old('category') == 'assy' ? 'selected' : '' }}>2. Assy</option>
                        <option value="accessories" {{ old('category') == 'accessories' ? 'selected' : '' }}>3. Aksesoris</option>
                        <option value="pto" {{ old('category') == 'pto' ? 'selected' : '' }}>4. PTO</option>
                        <option value="potong_chassis" {{ old('category') == 'potong_chassis' ? 'selected' : '' }}>5. Potong Chassis</option>
                        <option value="spo" {{ old('category') == 'spo' ? 'selected' : '' }}>6. SPO</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Nama Lengkap Komponen <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       value="{{ old('name') }}" 
                       required 
                       placeholder="Contoh: Subframe Dump Truck Hino 500 FM260 / Silinder Hidrolik 5-Stage"
                       class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold">
                <p class="text-[11px] text-amber-700 mt-1 flex items-center gap-1.5 font-medium">
                    <i class="fa-solid fa-circle-info text-amber-600"></i>
                    Nama harus disesuaikan dengan standarisasi Engineering HPK.
                </p>
            </div>

            <!-- Section 3: Physical Dimensions (Satuan mm) -->
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                        Ukuran Fisik Komponen (Satuan mm)
                    </label>
                    <span class="text-[11px] text-slate-400 font-medium">Opsional - Isi angka, simbol ⌀, atau teks (seperti PEJAL)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Panjang (mm)</label>
                        <div class="relative">
                            <input type="text" 
                                   name="length_mm" 
                                   value="{{ old('length_mm') }}" 
                                   placeholder="Contoh: 6000 atau 845" 
                                   class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none pr-10 font-mono">
                            <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-bold">mm</span>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-semibold text-slate-600">Diameter / Lebar (mm)</label>
                            <button type="button" onclick="const f = this.closest('div').parentElement.querySelector('input'); f.value = (f.value ? '⌀ ' + f.value : '⌀ '); f.focus();" class="text-[10px] font-bold text-amber-700 bg-amber-100 hover:bg-amber-200 px-1.5 py-0.5 rounded transition">
                                + ⌀
                            </button>
                        </div>
                        <div class="relative">
                            <input type="text" 
                                   name="width_or_diameter_mm" 
                                   value="{{ old('width_or_diameter_mm') }}" 
                                   placeholder="Contoh: 1500, ⌀ 25, 34,72" 
                                   class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none pr-10 font-mono">
                            <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-bold">mm</span>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-semibold text-slate-600">Tebal / Tinggi (mm)</label>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="const f = this.closest('div').parentElement.parentElement.querySelector('input'); f.value = (f.value ? '⌀ ' + f.value : '⌀ '); f.focus();" class="text-[10px] font-bold text-amber-700 bg-amber-100 hover:bg-amber-200 px-1.5 py-0.5 rounded transition">
                                    + ⌀
                                </button>
                                <button type="button" onclick="const f = this.closest('div').parentElement.parentElement.querySelector('input'); f.value = 'PEJAL'; f.focus();" class="text-[10px] font-bold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 px-1.5 py-0.5 rounded transition">
                                    PEJAL
                                </button>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="text" 
                                   name="thickness_or_height_mm" 
                                   value="{{ old('thickness_or_height_mm') }}" 
                                   placeholder="Contoh: 6.5, PEJAL, 3/4&quot;" 
                                   class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none pr-10 font-mono">
                            <span class="absolute right-3 top-2 text-[10px] text-slate-400 font-bold">mm</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: UoM & Safety Stock -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Satuan (UoM) <span class="text-rose-500">*</span>
                    </label>
                    <select name="uom" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="Pcs" {{ old('uom') == 'Pcs' ? 'selected' : '' }}>Pcs (Buah / Unit)</option>
                        <option value="Set" {{ old('uom') == 'Set' ? 'selected' : '' }}>Set (Kumpulan Komponen)</option>
                        <option value="Batang (6m)" {{ old('uom') == 'Batang (6m)' ? 'selected' : '' }}>Batang (Profil 6m)</option>
                        <option value="Lembar" {{ old('uom') == 'Lembar' ? 'selected' : '' }}>Lembar (Pelat Baja)</option>
                        <option value="Kg" {{ old('uom') == 'Kg' ? 'selected' : '' }}>Kg (Kilogram)</option>
                        <option value="Liter" {{ old('uom') == 'Liter' ? 'selected' : '' }}>Liter (Cat / Oli / Thinner)</option>
                        <option value="Box" {{ old('uom') == 'Box' ? 'selected' : '' }}>Box / Kotak</option>
                        <option value="Meter" {{ old('uom') == 'Meter' ? 'selected' : '' }}>Meter</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Safety Stock (Batas Minimum)
                    </label>
                    <input type="number" 
                           name="minimum_stock" 
                           value="{{ old('minimum_stock') }}" 
                           min="0" 
                           placeholder="0 (Biarkan kosong jika tidak dibatasi)"
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Opsional - Sistem memicu peringatan stok kritis jika stok berada di bawah batas ini.</p>
                </div>
            </div>

            <!-- Section 5: Keterangan / Catatan Tambahan -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Keterangan / Catatan Tambahan (Opsional)
                </label>
                <textarea name="specification" 
                          rows="3" 
                          placeholder="Contoh: Standar material JIS G3101 SS400, toleransi presisi ±0.2mm, catatan perakitan karoseri dump truck, dsb."
                          class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">{{ old('specification') }}</textarea>
            </div>

            <!-- Section 6: Location & Initial Balance -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Posisi Rak / Lokasi Default (Opsional)
                    </label>
                    <select name="default_location_id" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="">-- Belum Ditempatkan ke Rak (Segera Alokasikan) --</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('default_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->full_location_code }} ({{ $loc->zone_name }})
                            </option>
                        @endforeach
                    </select>
                    <div class="flex items-center justify-between mt-1 text-[11px] text-slate-500">
                        <span>Jika belum ada rak, sistem menandai <b>Belum Ditempatkan</b>.</span>
                        <a href="{{ route('locations.index') }}" target="_blank" class="text-blue-600 hover:underline font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-map text-[10px]"></i> Buka Denah Gudang
                        </a>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Stok Fisik Awal Saat Input
                    </label>
                    <input type="number" 
                           step="0.01" 
                           name="initial_stock" 
                           value="{{ old('initial_stock', 0) }}" 
                           min="0" 
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Stok fisik awal akan langsung dicatat saat komponen disimpan.</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('components.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold rounded-xl shadow transition">
                    Simpan Master Komponen
                </button>
            </div>
        </form>
    </div>

    <!-- Script for Live Camera Snapshot -->
    <script>
        function cameraCapture() {
            return {
                mode: 'upload', // 'upload' or 'camera'
                imagePreview: null,
                isPdf: false,
                pdfFileName: '',
                cameraSnapshot: '',
                mediaStream: null,

                previewFile(e) {
                    const file = e.target.files[0];
                    if (file) {
                        this.isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
                        this.pdfFileName = this.isPdf ? file.name : '';
                        this.imagePreview = this.isPdf ? null : URL.createObjectURL(file);
                        this.cameraSnapshot = '';
                    }
                },

                startCamera() {
                    const video = this.$refs.videoElem;
                    if (!video) return;

                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                            .then(stream => {
                                this.mediaStream = stream;
                                video.srcObject = stream;
                            })
                            .catch(err => {
                                alert('Tidak dapat mengakses kamera: ' + err.message);
                                this.mode = 'upload';
                            });
                    } else {
                        alert('Browser Anda tidak mendukung akses kamera.');
                        this.mode = 'upload';
                    }
                },

                stopCamera() {
                    if (this.mediaStream) {
                        this.mediaStream.getTracks().forEach(track => track.stop());
                        this.mediaStream = null;
                    }
                },

                takeSnapshot() {
                    const video = this.$refs.videoElem;
                    const canvas = this.$refs.canvasElem;
                    if (!video || !canvas) return;

                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    this.cameraSnapshot = canvas.toDataURL('image/jpeg', 0.85);
                    this.stopCamera();
                }
            };
        }
    </script>
</x-app-layout>

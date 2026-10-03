<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('components.show', $component) }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                    Edit Data Komponen: {{ $component->part_number }}
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui spesifikasi teknis, batas stok, atau foto fisik komponen</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="POST" action="{{ route('components.update', $component) }}" enctype="multipart/form-data" 
              x-data="cameraCaptureEdit()" 
              class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Photo & Visual Recognition -->
            <div class="p-5 bg-slate-50 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900">Foto Fisik Komponen</h3>
                        <p class="text-xs text-slate-500">Perbarui foto komponen (Upload file baru atau ambil langsung dari kamera)</p>
                    </div>
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

                <!-- Mode 1: Upload -->
                <div x-show="mode === 'upload'" class="flex items-center space-x-4">
                    <div class="w-24 h-24 rounded-xl border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden flex-shrink-0">
                        <img :src="imagePreview || '{{ $component->image_url }}'" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <input type="file" 
                               name="image" 
                               accept="image/*" 
                               @change="previewFile($event)"
                               class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                        <p class="text-[11px] text-slate-400 mt-1">Biarkan kosong jika tidak ingin mengubah foto yang ada saat ini</p>
                    </div>
                </div>

                <!-- Mode 2: Camera -->
                <div x-show="mode === 'camera'" style="display: none;" class="space-y-3">
                    <div class="relative bg-slate-950 rounded-xl overflow-hidden max-w-sm mx-auto aspect-video flex items-center justify-center">
                        <video x-ref="videoElem" class="w-full h-full object-cover" autoplay playsinline></video>
                        <canvas x-ref="canvasElem" style="display: none;"></canvas>
                        <div class="absolute top-2 left-2 bg-red-600/90 text-white text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center shadow">
                            <span class="w-1.5 h-1.5 rounded-full bg-white me-1 animate-ping"></span>
                            KAMERA LIVE
                        </div>
                    </div>

                    <div class="flex items-center justify-center">
                        <button type="button" @click="takeSnapshot()" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow transition flex items-center">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Jepret Foto Baru
                        </button>
                    </div>

                    <template x-if="cameraSnapshot">
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center space-x-3">
                            <img :src="cameraSnapshot" class="w-16 h-16 rounded-lg object-cover border border-emerald-300">
                            <div>
                                <span class="text-xs font-bold text-emerald-800 block">Foto Baru Berhasil Dijepret!</span>
                                <span class="text-[11px] text-emerald-600">Klik "Perbarui Data Komponen" di bawah untuk menyimpan.</span>
                            </div>
                        </div>
                    </template>
                </div>

                <input type="hidden" name="camera_snapshot" :value="cameraSnapshot">
            </div>

            <!-- Identifiers -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Part Number (Kode Unik)
                    </label>
                    <input type="text" 
                           value="{{ $component->part_number }}" 
                           disabled 
                           class="w-full px-3.5 py-2 text-xs border border-slate-200 bg-slate-100 rounded-xl text-slate-500 font-mono cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Kategori Komponen <span class="text-rose-500">*</span>
                    </label>
                    <select name="category" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="hydraulic" {{ $component->category == 'hydraulic' ? 'selected' : '' }}>Komponen Hidrolik & Presisi</option>
                        <option value="raw_material" {{ $component->category == 'raw_material' ? 'selected' : '' }}>Raw Material Baja (Pelat, UNP, WF, Pipa)</option>
                        <option value="fastener" {{ $component->category == 'fastener' ? 'selected' : '' }}>Hardware & Fastener (Baut, Mur, Ring)</option>
                        <option value="accessories" {{ $component->category == 'accessories' ? 'selected' : '' }}>Aksesoris Karoseri (Engsel, Twist Lock)</option>
                        <option value="electrical" {{ $component->category == 'electrical' ? 'selected' : '' }}>Electrical & Lighting (Lampu LED, Harness)</option>
                        <option value="chemical_paint" {{ $component->category == 'chemical_paint' ? 'selected' : '' }}>Chemical & Cat (Cat PU, Thinner, Dempul)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Nama Lengkap Komponen <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       value="{{ old('name', $component->name) }}" 
                       required 
                       class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none font-semibold">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Satuan (UoM) <span class="text-rose-500">*</span>
                    </label>
                    <select name="uom" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        @foreach(['Pcs', 'Set', 'Batang (6m)', 'Lembar', 'Kg', 'Liter', 'Box'] as $uom)
                            <option value="{{ $uom }}" {{ $component->uom == $uom ? 'selected' : '' }}>{{ $uom }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Safety Stock (Batas Minimum) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           name="minimum_stock" 
                           value="{{ old('minimum_stock', $component->minimum_stock) }}" 
                           min="0" 
                           required 
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Maximum Stock <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           name="maximum_stock" 
                           value="{{ old('maximum_stock', $component->maximum_stock) }}" 
                           min="1" 
                           required 
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Spesifikasi Teknis & Dimensi
                </label>
                <textarea name="specification" 
                          rows="3" 
                          class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">{{ old('specification', $component->specification) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    Posisi Rak / Lokasi Default <span class="text-rose-500">*</span>
                </label>
                <select name="default_location_id" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}" {{ $component->default_location_id == $loc->id ? 'selected' : '' }}>
                            {{ $loc->full_location_code }} ({{ $loc->zone_name }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('components.show', $component) }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold rounded-xl shadow transition">
                    Perbarui Data Komponen
                </button>
            </div>
        </form>
    </div>

    <script>
        function cameraCaptureEdit() {
            return {
                mode: 'upload',
                imagePreview: null,
                cameraSnapshot: '',
                mediaStream: null,

                previewFile(e) {
                    const file = e.target.files[0];
                    if (file) {
                        this.imagePreview = URL.createObjectURL(file);
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

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('ecrs.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                    Pengajuan ECR (Engineering Change Request)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Form permohonan revisi desain, spesifikasi material, atau substitusi komponen karoseri</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="POST" action="{{ route('ecrs.store') }}" enctype="multipart/form-data" 
              x-data="ecrForm()" 
              class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            @csrf

            <!-- Section 1: Target Component -->
            <div class="p-5 bg-purple-50/60 rounded-xl border border-purple-200 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-purple-900">1. Komponen yang Diajukan Revisi</h3>
                        <p class="text-xs text-slate-500">Pilih komponen karoseri yang akan diubah spesifikasinya</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-8">
                        <select name="component_id" 
                                required 
                                x-model="selectedCompId" 
                                @change="onSelectComp($event.target.value)" 
                                class="w-full px-3.5 py-2.5 text-xs border border-purple-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none font-semibold">
                            <option value="">-- Pilih Komponen dari Master Data --</option>
                            @foreach ($components as $comp)
                                <option value="{{ $comp->id }}" data-spec="{{ $comp->specification }}" {{ ($selectedComponentId == $comp->id) ? 'selected' : '' }}>
                                    {{ $comp->part_number }} - {{ $comp->name }} (Stok: {{ $comp->total_stock }} {{ $comp->uom }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Revision Details -->
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Judul Permohonan Revisi ECR <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           required 
                           placeholder="Contoh: Penggantian Grade Seal Kit Silinder Telescopic Menjadi Viton High Temp" 
                           class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none font-semibold">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Jenis Revisi <span class="text-rose-500">*</span>
                        </label>
                        <select name="revision_type" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="spec_change">Perubahan Spesifikasi Teknis / Dimensi</option>
                            <option value="part_replacement">Pergantian Part Number Alternatif / Substitusi</option>
                            <option value="discontinue">Penghentian Pemakaian Komponen (Discontinue)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Kebijakan Terhadap Stok Lama <span class="text-rose-500">*</span>
                        </label>
                        <select name="stock_policy" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="run_out">Habiskan Stok Lama Terlebih Dahulu (Run-Out)</option>
                            <option value="immediate_scrap">Langsung Ganti Baru & Ajukan Disposal/Scrap</option>
                            <option value="rework">Modifikasi / Rework di Workshop Karoseri</option>
                        </select>
                    </div>
                </div>

                <!-- Side by Side Specification Diff -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                            Spesifikasi Lama (Current Specs)
                        </label>
                        <textarea name="old_specification" 
                                  x-model="oldSpec"
                                  rows="4" 
                                  placeholder="Spesifikasi saat ini..."
                                  class="w-full px-3.5 py-2 text-xs border border-slate-200 bg-slate-50 rounded-xl text-slate-700 font-mono"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-purple-700 uppercase tracking-wider mb-1">
                            Spesifikasi Baru yang Diusulkan <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="new_specification" 
                                  rows="4" 
                                  required
                                  placeholder="Tuliskan perubahan dimensi, grade material, merk, atau toleransi baru..."
                                  class="w-full px-3.5 py-2 text-xs border border-purple-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none font-mono"></textarea>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Alasan & Latar Belakang Perubahan (Engineering Justification) <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="reason" 
                              rows="3" 
                              required 
                              placeholder="Jelaskan mengapa komponen ini perlu direvisi (masalah kualitas di lapangan, efisiensi biaya perakitan, kelangkaan part di pasar, atau desain karoseri baru)..."
                              class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none"></textarea>
                </div>

                <!-- Attachment -->
                <div class="pt-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Dokumen Pendukung (Gambar CAD / PDF Gambar Teknik)
                    </label>
                    <input type="file" 
                           name="document" 
                           accept=".pdf,.jpg,.png,.zip" 
                           class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">Mendukung format PDF, gambar CAD atau ZIP gambar teknik hingga 10MB</p>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('ecrs.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow transition">
                    Kirim Permohonan ECR
                </button>
            </div>
        </form>
    </div>

    <script>
        function ecrForm() {
            return {
                selectedCompId: '{{ $selectedComponentId ?? "" }}',
                oldSpec: '',

                init() {
                    if (this.selectedCompId) {
                        this.onSelectComp(this.selectedCompId);
                    }
                },

                onSelectComp(id) {
                    if (!id) {
                        this.oldSpec = '';
                        return;
                    }
                    const select = document.querySelector('select[name="component_id"]');
                    const option = select.querySelector(`option[value="${id}"]`);
                    if (option) {
                        this.oldSpec = option.getAttribute('data-spec') || 'Tidak ada spesifikasi tercatat.';
                    }
                }
            };
        }
    </script>
</x-app-layout>

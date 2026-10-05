<x-admin-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 220px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-10 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-2">
                    <a href="{{ route('admin.component-categories.index') }}" class="text-xs font-semibold text-amber-300 hover:text-amber-200 transition flex items-center">
                        <i class="fa-solid fa-arrow-left me-1.5"></i> Kembali ke Kategori
                    </a>
                    <span class="text-white/40">&bull;</span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">Form Kategori</span>
                </div>

                <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                    Tambah Kategori Komponen Baru
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 mt-1">
                    Buat klasifikasi komponen baru untuk pengelompokan material gudang karoseri.
                </p>
            </div>
        </div>

        <!-- 2. FORM CARD -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
            <form action="{{ route('admin.component-categories.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <!-- Kode Kategori -->
                    <div>
                        <label for="code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kode Kategori (Slug Unik) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="code" 
                               name="code" 
                               value="{{ old('code') }}" 
                               placeholder="Contoh: hydraulic_seal" 
                               required 
                               autofocus
                               class="w-full px-3.5 py-2.5 text-xs font-mono rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('code') border-rose-400 @enderror">
                        @error('code')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nama Kategori -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Kategori <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               placeholder="Contoh: Seal & O-Ring Hidrolik" 
                               required 
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition @error('name') border-rose-400 @enderror">
                        @error('name')
                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kategori Induk (Parent) -->
                    <div>
                        <label for="parent_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kategori Induk (Parent)
                        </label>
                        <select id="parent_id" 
                                name="parent_id" 
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                            <option value="">-- Kategori Utama (Tanpa Induk) --</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->name }} ({{ $parent->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Default Minimum Stock -->
                    <div>
                        <label for="default_min_stock" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Default Safety Stock (Batas Minimum)
                        </label>
                        <input type="number" 
                               id="default_min_stock" 
                               name="default_min_stock" 
                               value="{{ old('default_min_stock', 5) }}" 
                               min="0"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                    </div>

                    <!-- Deskripsi -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Deskripsi Kategori
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="3" 
                                  placeholder="Tuliskan keterangan komponen apa saja yang termasuk dalam kelompok ini..."
                                  class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- Opsi Tambahan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }} 
                                   class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 border-slate-300">
                            <div class="ms-3">
                                <span class="text-xs font-bold text-slate-900 block">Kategori Aktif</span>
                                <span class="text-[11px] text-slate-500 block">Dapat dipilih pada saat input master komponen</span>
                            </div>
                        </label>
                    </div>

                    <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   name="has_expiry" 
                                   value="1" 
                                   {{ old('has_expiry') ? 'checked' : '' }} 
                                   class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 border-slate-300">
                            <div class="ms-3">
                                <span class="text-xs font-bold text-slate-900 block">Memiliki Masa Kadaluarsa</span>
                                <span class="text-[11px] text-slate-500 block">Wajib mencatat tanggal expiry (contoh: cat PU, hardener, thinner)</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('admin.component-categories.index') }}" 
                       class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-6 py-2.5 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-admin-layout>

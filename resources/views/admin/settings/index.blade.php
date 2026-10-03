<x-admin-layout>
    <div class="wave-header pb-12 pt-8 relative overflow-hidden" style="min-height: 180px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center space-x-3 mb-2">
                <span class="bg-hpk-orange/20 text-amber-300 border border-amber-300/50 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-widest uppercase">
                    Admin
                </span>
                <span class="text-teal-300 text-xs font-bold tracking-widest uppercase">Konfigurasi Sistem</span>
            </div>
            <h1 class="text-3xl font-black text-white tracking-wide mb-1">
                Pengaturan Global
            </h1>
        </div>
        <div class="wave-shape-2">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,60 C320,120 420,0 720,60 C1020,120 1120,0 1440,60 L1440,120 L0,120 Z" fill="#f0f4f8"></path>
            </svg>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20 pb-12">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                
                @foreach($settings as $group => $groupSettings)
                    <div class="px-8 py-6 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                        <h2 class="text-lg font-black text-slate-800 uppercase tracking-wide mb-6">
                            Pengaturan {{ ucfirst($group) }}
                        </h2>
                        
                        <div class="space-y-6">
                            @foreach($groupSettings as $setting)
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:items-center">
                                    <div class="md:col-span-1">
                                        <label for="setting_{{ $setting->key }}" class="block text-sm font-bold text-slate-700">
                                            {{ $setting->description ?? ucwords(str_replace('_', ' ', $setting->key)) }}
                                        </label>
                                        <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $setting->key }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        @if($setting->type == 'boolean')
                                            <select name="settings[{{ $setting->key }}]" id="setting_{{ $setting->key }}" class="mt-1 block w-full md:w-1/2 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                <option value="1" {{ $setting->value == '1' ? 'selected' : '' }}>Aktif (Ya)</option>
                                                <option value="0" {{ $setting->value == '0' ? 'selected' : '' }}>Nonaktif (Tidak)</option>
                                            </select>
                                        @elseif($setting->type == 'integer')
                                            <x-text-input name="settings[{{ $setting->key }}]" id="setting_{{ $setting->key }}" type="number" class="mt-1 block w-full md:w-1/2" :value="$setting->value" />
                                        @else
                                            <x-text-input name="settings[{{ $setting->key }}]" id="setting_{{ $setting->key }}" type="text" class="mt-1 block w-full" :value="$setting->value" />
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="bg-slate-50 px-8 py-4 flex items-center justify-end border-t border-slate-200">
                    <button type="submit" class="px-6 py-2 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-sm rounded-xl shadow-md transition flex items-center">
                        <i class="fa-solid fa-save mr-2"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>

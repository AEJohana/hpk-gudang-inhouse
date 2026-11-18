<x-guest-layout>
    <div class="glass-card text-center p-5 sm:p-7">
        <!-- Floating Logo Badge -->
        <div class="mb-4">
            <div class="floating-logo-badge">
                <img src="{{ asset('images/logo_hpk.webp') }}" alt="PT Hydraxle Perkasa" class="w-full h-full object-contain">
            </div>
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mt-2">PT. HYDRAXLE PERKASA</div>
            <div class="text-xs font-bold text-teal-600 tracking-wide uppercase">WMS IN-HOUSE KAROSERI</div>
        </div>

        <div class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300/70 uppercase tracking-wider mb-2">
            <i class="fa-solid fa-shield-halved me-1.5 text-amber-600"></i> BANTUAN PEMULIHAN AKUN
        </div>

        <h4 class="font-extrabold text-xl text-slate-900 mb-1.5">Lupa Kata Sandi?</h4>
        <p class="text-xs text-slate-600 leading-relaxed mb-4">
            Demi menjaga keamanan akun dan integritas data operasional pabrik, pemulihan kata sandi dilakukan secara <strong>langsung & terverifikasi</strong> melalui Administrator Gudang atau Supervisor yang bertugas.
        </p>

        @if (session('info'))
            <div class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs font-medium text-amber-900 text-left flex items-start space-x-2">
                <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0"></i>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        <!-- Daftar Kontak Admin & Supervisor Bertugas -->
        <div class="space-y-2.5 text-left mb-5">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                <i class="fa-solid fa-headset me-1 text-teal-600"></i> Kontak Administrator / SPV Bertugas:
            </p>

            @forelse($admins as $admin)
                @php
                    $cleanPhone = preg_replace('/[^0-9]/', '', $admin->phone ?? '');
                    if (str_starts_with($cleanPhone, '0')) {
                        $waPhone = '62' . substr($cleanPhone, 1);
                    } else {
                        $waPhone = $cleanPhone;
                    }
                    $waMsg = urlencode("Halo {$admin->name}, saya memerlukan bantuan reset kata sandi akun sistem saya di HPK Gudang In-House.");
                    $isSpv = in_array($admin->role, ['supervisor']) || (method_exists($admin, 'hasRole') && $admin->hasRole('supervisor'));
                @endphp
                <div class="p-3 rounded-xl bg-slate-50 hover:bg-slate-100/90 border border-slate-200 transition">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <div class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
                                <span>{{ $admin->name }}</span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $isSpv ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $isSpv ? 'Supervisor' : 'Admin Gudang' }}
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-500 font-mono block mt-0.5">
                                <i class="fa-solid fa-phone text-[9px] me-1 text-slate-400"></i>
                                {{ $admin->phone ?? 'Pos Kantor Gudang' }}
                            </span>
                        </div>

                        @if(!empty($waPhone))
                        <div class="shrink-0 flex items-center space-x-1.5">
                            <a href="https://wa.me/{{ $waPhone }}?text={{ $waMsg }}" 
                               target="_blank" 
                               class="inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-2xs cursor-pointer"
                               title="Kirim pesan WhatsApp">
                                <i class="fa-brands fa-whatsapp text-sm"></i>
                                <span class="hidden sm:inline text-[11px]">WhatsApp</span>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                    Silakan hubungi Administrator Gudang di Pos Kantor Gudang Lantai 1.
                </div>
            @endforelse
        </div>

        <!-- Pos Fisik Bantuan -->
        <div class="p-3 rounded-xl bg-teal-50/80 border border-teal-200 text-left text-xs text-teal-900 mb-5 flex items-start space-x-2.5">
            <i class="fa-solid fa-building-user text-teal-600 text-sm mt-0.5 shrink-0"></i>
            <div>
                <span class="font-bold block text-[11px] uppercase tracking-wider text-teal-800">Bantuan Langsung / Tatap Muka:</span>
                <p class="text-[11px] text-teal-700 mt-0.5">
                    Kunjungi <strong>Pos Ruang Admin Gudang (Lantai 1)</strong> pabrik untuk verifikasi identitas dan permohonan reset kata sandi instan.
                </p>
            </div>
        </div>

        <!-- Back to Login Button -->
        <a href="{{ route('login') }}" 
           class="w-full inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition shadow-md cursor-pointer">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Kembali ke Halaman Login</span>
        </a>
    </div>
</x-guest-layout>

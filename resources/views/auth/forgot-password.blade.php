<x-guest-layout>
    <style>
        .hpk-copy-box {
            position: relative;
            overflow: hidden;
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 12px;
            padding: 5px 6px 5px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            user-select: none;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        .hpk-copy-left {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e2e8f0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.025em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 8px;
        }
        .hpk-copy-left i {
            color: #2dd4bf;
            font-size: 11px;
            flex-shrink: 0;
        }
        .hpk-copy-btn {
            position: relative;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff !important;
            font-weight: 700;
            font-size: 11px;
            padding: 5px 12px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3);
            flex-shrink: 0;
        }
        .hpk-copy-btn:hover {
            background: linear-gradient(135deg, #4338ca 0%, #2563eb 100%);
            box-shadow: 0 3px 8px rgba(59, 130, 246, 0.45);
        }
        .hpk-copy-btn:active {
            transform: scale(0.95);
        }
        .hpk-copied-overlay {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #0d9488 0%, #059669 50%, #10b981 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
            z-index: 25;
            transform-origin: right center;
            transform: scaleX(0);
            opacity: 0;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
            pointer-events: none;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.15);
        }
        .hpk-copied-overlay.active {
            transform: scaleX(1);
            opacity: 1;
        }
    </style>

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
            Sistem beroperasi pada jaringan lokal pabrik (intranet). Demi menjaga integritas data logistik, silakan hubungi <strong>Administrator Gudang</strong> atau <strong>Supervisor</strong> yang bertugas dengan menyalin nomor telepon di bawah ini, atau datang langsung ke ruang pos admin.
        </p>

        @if (session('info'))
            <div class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs font-medium text-amber-900 text-left flex items-start space-x-2">
                <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0"></i>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        <!-- Daftar Kontak Admin & Supervisor Bertugas -->
        <div class="space-y-3 text-left mb-5">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                <i class="fa-solid fa-headset me-1 text-teal-600"></i> Kontak Administrator / SPV Bertugas:
            </p>

            @forelse($admins as $admin)
                @php
                    $cleanPhone = preg_replace('/[^0-9]/', '', $admin->phone ?? '');
                    $isSpv = in_array($admin->role, ['supervisor']) || (method_exists($admin, 'hasRole') && $admin->hasRole('supervisor'));
                @endphp
                <div class="p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:shadow-xs transition">
                    <!-- Baris Atas: Profil Admin & Tombol Aksi Dialer Telepon -->
                    <div class="flex items-center justify-between gap-2 mb-2.5">
                        <div class="flex items-center space-x-2 min-w-0">
                            <div class="w-8 h-8 rounded-xl {{ $isSpv ? 'bg-purple-600' : 'bg-amber-500' }} text-white flex items-center justify-center text-[11px] font-black shadow-xs shrink-0">
                                {{ strtoupper(substr($admin->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-slate-900 truncate leading-tight">
                                    {{ $admin->name }}
                                </div>
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold {{ $isSpv ? 'bg-purple-50 text-purple-700 border border-purple-200/60' : 'bg-amber-50 text-amber-800 border border-amber-200/60' }} mt-0.5">
                                    {{ $isSpv ? 'Supervisor' : 'Admin Gudang' }}
                                </span>
                            </div>
                        </div>

                        @if(!empty($cleanPhone))
                        <a href="tel:{{ $cleanPhone }}" 
                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 font-bold text-[10px] border border-teal-200/80 transition shadow-2xs cursor-pointer"
                           title="Panggil via Telepon (Perangkat Ponsel/Tablet)">
                            <i class="fa-solid fa-phone text-[9px] text-teal-600"></i>
                            <span>Panggil</span>
                        </a>
                        @endif
                    </div>

                    <!-- Baris Bawah: Interaksi Salin Nomor Telepon Smooth Morphing -->
                    @if(!empty($admin->phone))
                    <div class="hpk-copy-box"
                         x-data="{
                             copied: false,
                             copyPhone(number) {
                                 if (this.copied) return;
                                 if (navigator.clipboard && window.isSecureContext) {
                                     navigator.clipboard.writeText(number);
                                 } else {
                                     const ta = document.createElement('textarea');
                                     ta.value = number;
                                     ta.style.position = 'fixed';
                                     ta.style.opacity = '0';
                                     document.body.appendChild(ta);
                                     ta.select();
                                     try { document.execCommand('copy'); } catch(e) {}
                                     document.body.removeChild(ta);
                                 }
                                 this.copied = true;
                                 setTimeout(() => {
                                     this.copied = false;
                                 }, 2400);
                             }
                         }">
                         
                         <!-- Kiri: Nomor Telepon Tampil -->
                         <div class="hpk-copy-left">
                             <i class="fa-solid fa-phone"></i>
                             <span>{{ $admin->phone }}</span>
                         </div>

                         <!-- Kanan: Tombol Salin Biru-Indigo -->
                         <button type="button" 
                                 @click="copyPhone('{{ $cleanPhone }}')"
                                 class="hpk-copy-btn"
                                 title="Salin nomor telepon">
                             <i class="fa-regular fa-copy text-xs"></i>
                             <span>Copy</span>
                         </button>

                         <!-- Efek Meluncur Penuh Hijau Zamrud dari Kanan ke Kiri -->
                         <div class="hpk-copied-overlay" :class="{ 'active': copied }">
                             <i class="fa-solid fa-check text-xs"></i>
                             <span>Nomor tersalin!</span>
                         </div>
                    </div>
                    @else
                    <div class="rounded-xl bg-slate-100 border border-slate-200 px-3 py-1.5 text-[11px] text-slate-500 flex items-center space-x-2">
                        <i class="fa-solid fa-location-dot text-[10px] text-slate-400"></i>
                        <span>Pos Ruang Admin Gudang Lantai 1</span>
                    </div>
                    @endif
                </div>
            @empty
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                    Silakan hubungi Administrator Gudang di Pos Kantor Gudang Lantai 1.
                </div>
            @endforelse
        </div>

        <!-- Pos Fisik Bantuan -->
        <div class="p-3.5 rounded-2xl bg-teal-50/80 border border-teal-200 text-left text-xs text-teal-900 mb-5 flex items-start space-x-2.5 shadow-2xs">
            <i class="fa-solid fa-building-user text-teal-600 text-sm mt-0.5 shrink-0"></i>
            <div>
                <span class="font-bold block text-[11px] uppercase tracking-wider text-teal-800">Bantuan Langsung / Tatap Muka:</span>
                <p class="text-[11px] text-teal-700 mt-0.5 leading-relaxed">
                    Kunjungi <strong>Pos Ruang Admin Gudang (Lantai 1)</strong> pabrik untuk <strong>verifikasi identitas fisik</strong> dan permohonan reset kata sandi instan.
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

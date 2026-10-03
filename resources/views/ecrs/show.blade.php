<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center space-x-3">
                <a href="{{ route('ecrs.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-purple-900 bg-purple-100 px-2 py-0.5 rounded">{{ $ecr->ecr_number }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $ecr->status_badge['class'] }}">
                            {{ $ecr->status_badge['label'] }}
                        </span>
                    </div>
                    <h2 class="font-bold text-xl text-slate-900 leading-tight mt-1">
                        {{ $ecr->title }}
                    </h2>
                </div>
            </div>

            <!-- Approval Actions (If user has supervisor/engineering authority & ECR is pending) -->
            @if ($ecr->status === 'submitted' || $ecr->status === 'reviewed_qc')
                <div class="flex items-center space-x-2" x-data="{ approveModal: false, rejectModal: false }">
                    <button type="button" @click="rejectModal = true" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition">
                        Tolak ECR
                    </button>
                    <button type="button" @click="approveModal = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center">
                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Setujui ECR (Approve)
                    </button>

                    <!-- Approve Modal -->
                    <div x-show="approveModal" style="display: none;" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
                        <div @click.away="approveModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
                            <h3 class="font-bold text-base text-slate-900">Setujui Permohonan ECR?</h3>
                            <p class="text-xs text-slate-500">Persetujuan ECR akan memperbarui spesifikasi teknis komponen secara resmi di sistem gudang HPK.</p>
                            
                            <form method="POST" action="{{ route('ecrs.approve', $ecr) }}">
                                @csrf
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Persetujuan (Opsional):</label>
                                <textarea name="approval_notes" rows="2" class="w-full text-xs p-2.5 border border-slate-300 rounded-xl mb-4" placeholder="Contoh: Disetujui sesuai rapat desain karoseri dump..."></textarea>
                                
                                <div class="flex justify-end space-x-2">
                                    <button type="button" @click="approveModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 rounded-lg">Batal</button>
                                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-lg">Konfirmasi Setujui</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div x-show="rejectModal" style="display: none;" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
                        <div @click.away="rejectModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
                            <h3 class="font-bold text-base text-slate-900">Tolak Permohonan ECR</h3>
                            <p class="text-xs text-slate-500">Silakan masukkan alasan penolakan untuk catatan tim engineering.</p>
                            
                            <form method="POST" action="{{ route('ecrs.reject', $ecr) }}">
                                @csrf
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan <span class="text-rose-500">*</span>:</label>
                                <textarea name="approval_notes" required rows="3" class="w-full text-xs p-2.5 border border-slate-300 rounded-xl mb-4" placeholder="Tuliskan alasan penolakan..."></textarea>
                                
                                <div class="flex justify-end space-x-2">
                                    <button type="button" @click="rejectModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 rounded-lg">Batal</button>
                                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 rounded-lg">Konfirmasi Tolak</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Target Component Preview Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="{{ $ecr->component->image_url }}" class="w-16 h-16 rounded-xl object-cover border border-slate-200 bg-slate-50">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Komponen Target Revisi:</span>
                    <h3 class="font-bold text-base text-slate-900">{{ $ecr->component->name }}</h3>
                    <div class="flex items-center space-x-2 mt-0.5 text-xs text-slate-500">
                        <span class="font-mono font-bold text-slate-700">{{ $ecr->component->part_number }}</span>
                        <span>&bull;</span>
                        <span>Stok Saat Ini: <strong>{{ $ecr->component->total_stock }} {{ $ecr->component->uom }}</strong></span>
                    </div>
                </div>
            </div>
            <a href="{{ route('components.show', $ecr->component) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition">
                Lihat Master Part &rarr;
            </a>
        </div>

        <!-- Specification Comparison Section (Diff View) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Perbandingan Spesifikasi Teknis</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Old Spec -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Spesifikasi Lama (Sebelum Revisi)</span>
                    <div class="text-xs font-mono text-slate-700 whitespace-pre-line leading-relaxed">
                        {{ $ecr->old_specification ?: 'Tidak ada spesifikasi lama.' }}
                    </div>
                </div>

                <!-- New Spec -->
                <div class="p-4 rounded-xl bg-purple-50/70 border border-purple-200">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700 block mb-2">Spesifikasi Baru yang Diusulkan</span>
                    <div class="text-xs font-mono text-purple-950 whitespace-pre-line leading-relaxed font-semibold">
                        {{ $ecr->new_specification }}
                    </div>
                </div>
            </div>

            <!-- Justification -->
            <div class="pt-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Alasan & Rekomendasi Engineering:</span>
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-800 leading-relaxed">
                    {{ $ecr->reason }}
                </div>
            </div>

            <!-- ECR Details Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-3 border-t border-slate-100 text-xs">
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Jenis Revisi</span>
                    <span class="font-bold text-slate-900 mt-0.5 block">{{ $ecr->revision_type_label }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Kebijakan Stok Lama</span>
                    <span class="font-bold text-slate-900 mt-0.5 block">
                        {{ match($ecr->stock_policy) {
                            'run_out' => 'Habiskan Stok Lama (Run-Out)',
                            'immediate_scrap' => 'Langsung Scrap/Disposal',
                            'rework' => 'Rework Workshop',
                            default => $ecr->stock_policy
                        } }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Diajukan Oleh</span>
                    <span class="font-bold text-slate-900 mt-0.5 block">{{ $ecr->requestedBy->name }}</span>
                    <span class="text-[10px] text-slate-400">{{ $ecr->created_at->format('d M Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Dokumen Pendukung</span>
                    @if ($ecr->document_path)
                        <a href="{{ asset($ecr->document_path) }}" target="_blank" class="font-bold text-purple-700 hover:underline mt-0.5 block">
                            Unduh File Lampiran &rarr;
                        </a>
                    @else
                        <span class="text-slate-400 italic mt-0.5 block">Tidak ada lampiran</span>
                    @endif
                </div>
            </div>

            <!-- Approval Audit Status -->
            @if ($ecr->approved_by)
                <div class="p-4 rounded-xl {{ $ecr->status === 'approved' ? 'bg-emerald-50 border border-emerald-200 text-emerald-900' : 'bg-rose-50 border border-rose-200 text-rose-900' }} text-xs">
                    <div class="flex items-center justify-between font-bold">
                        <span>Status: {{ $ecr->status === 'approved' ? 'Telah Disetujui' : 'Telah Ditolak' }} oleh {{ $ecr->approvedBy->name }}</span>
                        <span>{{ $ecr->approved_at ? $ecr->approved_at->format('d M Y H:i') : '' }}</span>
                    </div>
                    @if ($ecr->approval_notes)
                        <p class="mt-1 text-[11px] {{ $ecr->status === 'approved' ? 'text-emerald-700' : 'text-rose-700' }}">
                            Catatan: {{ $ecr->approval_notes }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

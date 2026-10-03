<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center space-x-3">
                <a href="{{ route('disposals.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-rose-900 bg-rose-100 px-2 py-0.5 rounded">{{ $disposal->disposal_number }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $disposal->status_badge['class'] }}">
                            {{ $disposal->status_badge['label'] }}
                        </span>
                    </div>
                    <h2 class="font-bold text-xl text-slate-900 leading-tight mt-1">
                        Rincian Pengajuan Disposal Material
                    </h2>
                </div>
            </div>

            <!-- Approval / Execution Actions -->
            <div class="flex items-center space-x-2">
                @if ($disposal->status === 'submitted')
                    <form method="POST" action="{{ route('disposals.approve', $disposal) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Setujui Pengajuan (Approve)
                        </button>
                    </form>
                @elseif ($disposal->status === 'approved_manager')
                    <form method="POST" action="{{ route('disposals.complete', $disposal) }}" onsubmit="return confirm('Apakah fisik barang telah dimusnahkan/dibuang? Stok di rak gudang akan otomatis dipotong!')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Eksekusi Pemusnahan & Potong Stok
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Disposal Overview Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Informasi Disposal & Verifikasi Afkir</h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Kategori Scrap</span>
                    <span class="font-bold text-slate-900 mt-0.5 block">{{ $disposal->disposal_type_label }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Estimasi Berat</span>
                    <span class="font-bold text-rose-700 mt-0.5 block text-sm">{{ number_format($disposal->estimated_weight_kg, 1) }} Kg</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Taksiran Nilai Sisa</span>
                    <span class="font-bold text-emerald-700 mt-0.5 block text-sm">Rp {{ number_format($disposal->estimated_salvage_value, 0, ',', '.') }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Diajukan Oleh</span>
                    <span class="font-bold text-slate-900 mt-0.5 block">{{ $disposal->requestedBy->name }}</span>
                    <span class="text-[10px] text-slate-400">{{ $disposal->created_at->format('d M Y') }}</span>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100">
                <span class="text-slate-400 text-[10px] uppercase font-bold block mb-1">Alasan Pengajuan Pemusnahan / Scrap:</span>
                <p class="text-xs text-slate-800 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-200">
                    {{ $disposal->reason }}
                </p>
            </div>
        </div>

        <!-- Items & Proof Photos -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Item Komponen & Bukti Fisik Kerusakan</h3>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach ($disposal->items as $item)
                    <div class="p-6 flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6">
                        <!-- Proof Photo -->
                        <div class="w-32 h-32 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex-shrink-0 flex items-center justify-center shadow-inner">
                            <img src="{{ $item->proof_photo_url }}" alt="Bukti Afkir" class="w-full h-full object-cover">
                        </div>

                        <!-- Item Details -->
                        <div class="flex-1 space-y-2 text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono font-bold text-slate-900 text-xs bg-slate-100 px-2 py-0.5 rounded">{{ $item->component->part_number }}</span>
                                <span class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded">
                                    Jumlah Di-Afkir: {{ number_format($item->quantity, 0) }} {{ $item->component->uom }}
                                </span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900">{{ $item->component->name }}</h4>
                            <p class="text-slate-600">
                                <strong>Lokasi Pengambilan:</strong> <span class="font-mono text-blue-900">{{ $item->fromLocation ? $item->fromLocation->full_location_code : 'Zona Scrap/Karantina' }}</span>
                            </p>
                            @if ($item->condition_description)
                                <p class="text-slate-500 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                    <strong>Catatan Kerusakan:</strong> {{ $item->condition_description }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-app-layout>

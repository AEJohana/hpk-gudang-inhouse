<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight">
                    Dashboard Monitoring SLA Pabrik
                </h2>
            </div>

            <div class="flex items-center space-x-2 self-start sm:self-auto">
                <a href="{{ route('work-requests.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center">
                    <i class="fa-solid fa-arrow-left me-1.5"></i>
                    Kembali ke Antrian WRI
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- 0. INTEGRATED WRI SUB-NAVIGATION TABS -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-3">
            <div class="flex items-center space-x-2">
                <a href="{{ route('work-requests.index') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-list-check text-xs"></i>
                    <span>Antrian & Tiket WRI</span>
                </a>
                @can('work-request.safety-stock')
                <a href="{{ route('work-requests.safety-stock') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100 whitespace-nowrap">
                    <i class="fa-solid fa-shield-halved text-xs text-rose-500"></i>
                    <span>Safety Stock Alert</span>
                </a>
                @endcan
                @can('work-request.print')
                <a href="{{ route('work-requests.print-queue') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100 whitespace-nowrap">
                    <i class="fa-solid fa-print text-xs text-indigo-500"></i>
                    <span>Cetak Dokumen WR</span>
                </a>
                @endcan
                <a href="{{ route('work-requests.monitoring') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 bg-[#0a2342] text-amber-400 shadow-md">
                    <i class="fa-solid fa-chart-line text-xs text-amber-400"></i>
                    <span>Monitoring SLA & Durasi</span>
                </a>
            </div>

            <div class="flex items-center space-x-2 text-xs">
                <span class="text-slate-400 font-semibold">Toleransi SLA Baku:</span>
                <span class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 font-extrabold flex items-center space-x-1">
                    <i class="fa-solid fa-clock text-amber-600"></i>
                    <span>Maksimal 72 Jam (3 Hari)</span>
                </span>
            </div>
        </div>

        <!-- 1. KPI SUMMARY CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Tiket Dalam Lingkup</span>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $totalOrders }}</h3>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Akumulasi pesanan</span>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Pesanan Selesai (Final)</span>
                <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ $completedOrders }}</h3>
                <span class="text-[11px] text-emerald-600 mt-0.5 block font-semibold">{{ $successRate }}% Tingkat Penyelesaian</span>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Peringatan Overdue (> 72 Jam)</span>
                <h3 class="text-2xl font-black {{ $overdueTickets->count() > 0 ? 'text-rose-600' : 'text-slate-900' }} mt-1">
                    {{ $overdueTickets->count() }} Tiket
                </h3>
                <span class="text-[11px] {{ $overdueTickets->count() > 0 ? 'text-rose-600 font-bold animate-pulse' : 'text-slate-500' }} mt-0.5 block">
                    {{ $overdueTickets->count() > 0 ? 'Melewati ambang batas SLA' : 'Semua pengerjaan tepat waktu' }}
                </span>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Mesin Terdaftar</span>
                <h3 class="text-2xl font-black text-blue-600 mt-1">{{ $machines->count() }} Stasiun</h3>
                <span class="text-[11px] text-blue-600 mt-0.5 block font-semibold">Pusat fabrikasi karoseri</span>
            </div>
        </div>

        <!-- 2. SLA OVERDUE TICKETS (RED ALERT SECTION) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                    <h4 class="font-extrabold text-xs uppercase tracking-wider text-slate-900">
                        Tiket Tertahan Melewati Batas Toleransi SLA (> 3 Hari)
                    </h4>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full {{ $overdueTickets->count() > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $overdueTickets->count() }} Tiket Terdeteksi
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">No. WRI</th>
                            <th class="py-3 px-3">Komponen</th>
                            <th class="py-3 px-3">Pengaju &rarr; Penerima</th>
                            <th class="py-3 px-3">Status Saat Ini</th>
                            <th class="py-3 px-3 text-center">Durasi Tertahan</th>
                            <th class="py-3 px-4 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($overdueTickets as $ov)
                        @php
                            $hours = (int) now()->diffInHours($ov->created_at);
                            $days = floor($hours / 24);
                        @endphp
                        <tr class="bg-rose-50/40 hover:bg-rose-100/50 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                {{ $ov->wri_number }}
                                <div class="text-[10px] text-slate-500 font-sans">
                                    Diterbitkan: {{ $ov->created_at->format('d/m/Y H:i') }}
                                </div>
                            </td>
                            <td class="py-3 px-3 font-semibold text-slate-900">
                                {{ $ov->component->name }}
                                <span class="font-mono text-[10px] text-amber-900 block font-normal">
                                    {{ $ov->component->part_number }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-slate-700">
                                <span class="font-bold">{{ $ov->dept_pengaju ?: 'Gudang' }}</span> &rarr; 
                                <span class="font-bold text-indigo-900">{{ $ov->dept_penerima ?: 'Mesin' }}</span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $ov->status_badge['class'] }}">
                                    {{ $ov->status_badge['label'] }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-rose-600 text-white">
                                    {{ $days }} Hari ({{ $hours }} Jam)
                                </span>
                            </td>
                            <td class="py-3 px-4 text-end">
                                <a href="{{ route('work-requests.show', $ov) }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition">
                                    Buka Tiket &rarr;
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 italic">
                                Luar biasa! Tidak ada tiket yang tertahan melebihi batas waktu SLA 72 jam.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. MACHINE CENTER LIVE LOAD MONITORING -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-microchip text-slate-800"></i>
                    <h4 class="font-extrabold text-xs uppercase tracking-wider text-slate-900">
                        Beban Kerja Mesin Fabrikasi (Machine Center)
                    </h4>
                </div>
                <span class="text-xs text-slate-400 font-semibold">Monitoring antrian aktif</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
                @foreach($machines as $m)
                <div class="p-3.5 rounded-xl border {{ $m->active_count > 0 ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200' }}">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-slate-900">{{ $m->name }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $m->active_count > 0 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                            {{ $m->active_count }} Aktif
                        </span>
                    </div>
                    <div class="text-[10px] text-slate-500 mt-1">
                        Tipe: {{ $m->type_label }} &bull; Stasiun #{{ $m->id }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</x-app-layout>

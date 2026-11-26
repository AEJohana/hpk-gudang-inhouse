@php
    $isTransfer = $mode === 'transfer';
    $gridColsClass = 'grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,0.9fr)_minmax(0,0.75fr)]';

    $statusBadgeClass = match (true) {
        $subTab === 'waiting' && $isTransfer => 'bg-rose-50 text-rose-700 border border-rose-200/80',
        $subTab === 'waiting' && ! $isTransfer => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80',
        $subTab === 'transit' => 'bg-sky-50 text-sky-700 border border-sky-200/80',
        default => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80',
    };

    $splitWrNumber = static function (string $wr): array {
        $parts = explode('/', $wr);
        if (count($parts) >= 4) {
            return [
                'prefix' => implode('/', array_slice($parts, 0, 3)) . '/',
                'suffix' => implode('/', array_slice($parts, 3)),
            ];
        }
        return ['prefix' => $wr, 'suffix' => ''];
    };

    $formatDisplayDate = static function (array $row): string {
        if (! empty($row['item']?->created_at)) {
            return $row['item']->created_at->format('d M Y');
        }
        if (! empty($row['tgl_raw'])) {
            try {
                return \Carbon\Carbon::parse($row['tgl_raw'])->format('d M Y');
            } catch (\Throwable $e) {
            }
        }
        return $row['tgl'] ?? '-';
    };
@endphp

<!-- Unified Responsive Floating Card Table (Horizontal Swipe pada HP, Tanpa Card Vertikal Terpisah) -->
<div class="overflow-x-auto pb-1.5 -mx-1 px-1" data-lenis-prevent="true">
    <div class="min-w-[780px] space-y-2.5">
        <!-- Column Headers Strip -->
        <div class="grid {{ $gridColsClass }} bg-slate-50/70 rounded-xl border border-slate-200/75 text-[10px] uppercase tracking-wider font-bold text-slate-400 divide-x divide-slate-200/60">
            <div class="py-3 px-4">No WR / Tanggal</div>
            <div class="py-3 px-4">Komponen</div>
            <div class="py-3 px-4">{{ $routeHeader }}</div>
            <div class="py-3 px-4">Status</div>
            <div class="py-3 px-4 text-right">Aksi</div>
        </div>

        <!-- Floating Card Rows -->
        @forelse($items as $t)
            @php
                $wrParts = $splitWrNumber($t['wri_number']);
                $displayDate = $formatDisplayDate($t);
                $searchHaystack = strtolower($t['wri_number'] . ' ' . $t['component_name'] . ' ' . ($t['part_number'] ?? ''));
                $xShowExpr = $isTransfer
                    ? "matchesFilter('{$t['tgl_raw']}', '{$t['origin']}', '{$t['destination']}', " . \Illuminate\Support\Js::from($searchHaystack) . ")"
                    : "matchesFilterFinal('{$t['tgl_raw']}', '{$t['location']}', " . \Illuminate\Support\Js::from($searchHaystack) . ")";
                $statusLabel = match (true) {
                    $subTab === 'waiting' && $isTransfer => 'Menunggu Ambil',
                    $subTab === 'waiting' && ! $isTransfer => 'Siap Diambil',
                    $subTab === 'transit' => 'Dalam Perjalanan',
                    default => $t['status'],
                };
            @endphp
            <div class="grid {{ $gridColsClass }} items-center bg-white rounded-2xl border border-slate-200/80 shadow-[0_2px_10px_rgba(15,23,42,0.03)] hover:shadow-md transition divide-x divide-slate-100"
                 x-show="{{ $xShowExpr }}">
                <!-- 1. No WR / Tanggal -->
                <div class="py-3.5 px-4 min-w-0">
                    <div class="font-extrabold text-xs leading-snug break-words">
                        <span class="text-slate-900">{{ $wrParts['prefix'] }}</span><span class="text-teal-700">{{ $wrParts['suffix'] }}</span>
                    </div>
                    <div class="text-[11px] text-slate-400 font-medium mt-1">
                        <i class="fa-regular fa-calendar me-1"></i>{{ $displayDate }}
                    </div>
                </div>

                <!-- 2. Komponen -->
                <div class="py-3.5 px-4 min-w-0">
                    <div class="font-bold text-teal-700 text-xs leading-snug break-words">{{ $t['component_name'] }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">Kode: {{ $t['part_number'] }}</div>
                </div>

                <!-- 3. Rute / Lokasi -->
                <div class="py-3.5 px-4 min-w-0">
                    @if($isTransfer)
                        <div class="font-bold text-slate-900 text-xs flex items-start gap-1.5 leading-snug">
                            <i class="fa-solid fa-location-dot text-rose-500 mt-0.5 shrink-0"></i>
                            <span>Asal: {{ $t['origin'] }}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 flex items-start gap-1.5 leading-snug">
                            <i class="fa-solid fa-bullseye text-teal-600 mt-0.5 shrink-0"></i>
                            <span>Tujuan: {{ $t['destination'] }}</span>
                        </div>
                    @elseif($subTab === 'history')
                        <div class="font-bold text-slate-900 text-xs flex items-start gap-1.5 leading-snug">
                            <i class="fa-solid fa-warehouse text-emerald-600 mt-0.5 shrink-0"></i>
                            <span>{{ $t['destination'] }}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">Diterima: {{ $t['received_time'] }}</div>
                    @else
                        <div class="font-bold text-slate-900 text-xs flex items-start gap-1.5 leading-snug">
                            <i class="fa-solid fa-location-dot text-rose-500 mt-0.5 shrink-0"></i>
                            <span>Selesai di: {{ $t['location'] }}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 flex items-start gap-1.5 leading-snug">
                            <i class="fa-solid fa-bullseye text-teal-600 mt-0.5 shrink-0"></i>
                            <span>Tujuan: {{ $t['destination'] }}</span>
                        </div>
                    @endif
                </div>

                <!-- 4. Status & Qty -->
                <div class="py-3.5 px-4 min-w-0">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-bold {{ $statusBadgeClass }}">
                        {{ $statusLabel }}
                    </span>
                    <div class="text-xs font-extrabold text-slate-800 mt-1.5">
                        Qty: {{ $t['qty'] }} {{ $t['uom'] ?? 'pcs' }}
                    </div>
                </div>

                <!-- 5. Aksi -->
                <div class="py-3.5 px-4 flex items-center justify-end">
                    @if($subTab === 'waiting')
                        <button type="button"
                                @click="openActionModal({{ json_encode($t) }})"
                                class="inline-flex items-center justify-center px-4 py-2 {{ $isTransfer ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white font-bold text-xs rounded-full shadow-xs transition cursor-pointer whitespace-nowrap">
                            <i class="fa-solid {{ $isTransfer ? 'fa-qrcode' : 'fa-truck-fast' }} me-1.5 text-[11px]"></i> Ambil
                        </button>
                    @elseif($subTab === 'transit')
                        <div class="text-right">
                            <div class="text-[11px] font-bold text-slate-600 leading-snug">
                                {{ $isTransfer ? 'Diantar Kurir MH' : 'Menunggu ' . $t['destination'] }}
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5 whitespace-nowrap">
                                <i class="fa-regular fa-clock me-1"></i>{{ $t['pickup_time'] }}
                            </div>
                        </div>
                    @else
                        <button type="button"
                                @click="openDetailModal({{ json_encode($t) }})"
                                class="inline-flex items-center justify-center px-3.5 py-1.5 bg-white hover:bg-slate-50 border border-slate-200/90 text-slate-700 font-bold text-xs rounded-full shadow-2xs transition cursor-pointer whitespace-nowrap">
                            <i class="fa-solid fa-circle-info me-1 text-teal-600"></i> Detail
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="py-12 px-4 text-center text-slate-400 italic bg-white rounded-2xl border border-slate-200/80 shadow-2xs">
                <div class="w-12 h-12 rounded-full {{ $emptyIconBg }} flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                    <i class="fa-solid {{ $emptyIcon }}"></i>
                </div>
                <p class="font-bold text-slate-600 text-xs">{{ $emptyTitle }}</p>
            </div>
        @endforelse
    </div>
</div>

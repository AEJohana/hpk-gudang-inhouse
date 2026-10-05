<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('warehouse-map.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="font-mono text-xs font-bold text-white bg-slate-900 px-2 py-0.5 rounded">ZONA {{ $location->zone_code }}</span>
                    <span class="text-xs text-slate-500 font-semibold">{{ $location->zone_name }}</span>
                </div>
                <h2 class="font-bold text-xl text-slate-900 leading-tight mt-1">
                    Rak Penyimpanan: {{ $location->rack_number }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Rack Overview Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Kode Rak Lengkap</span>
                    <span class="font-mono font-bold text-slate-900 text-sm mt-0.5 block">{{ $location->full_location_code }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Tingkat / Level Bin</span>
                    <span class="font-bold text-slate-900 mt-0.5 block">{{ $location->bin_level ?: 'Floor Level' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Lorong (Aisle)</span>
                    <span class="font-semibold text-slate-700 mt-0.5 block">{{ $location->aisle ?: '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] uppercase font-bold">Kapasitas Maksimal</span>
                    <span class="font-bold text-slate-900 mt-0.5 block">{{ $location->max_capacity }} Unit</span>
                </div>
            </div>

            @if ($location->description)
                <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600">
                    <strong>Deskripsi Area:</strong> {{ $location->description }}
                </div>
            @endif
        </div>

        <!-- Stored Components Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Komponen Karoseri yang Tersimpan di Rak Ini</h3>
                <span class="text-xs text-slate-500 font-semibold">{{ $location->stockBalances->count() }} Jenis Komponen</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/50 text-[10px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Foto & Part Number</th>
                            <th class="py-3 px-4">Nama Komponen</th>
                            <th class="py-3 px-4">Lokasi Slot</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4">No. Batch / Lot</th>
                            <th class="py-3 px-4 text-center">Stok di Rak</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($location->stockBalances as $sb)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2.5">
                                        <img src="{{ $sb->component->image_url }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 bg-white">
                                        <span class="font-mono font-bold text-slate-900 block">{{ $sb->component->part_number }}</span>
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <a href="{{ route('components.show', $sb->component) }}" class="font-bold text-slate-900 hover:text-blue-600 block">
                                        {{ $sb->component->name }}
                                    </a>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="space-y-0.5">
                                        <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-300">
                                            {{ $sb->computed_location_code }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 block">
                                            Lantai {{ preg_replace('/[^0-9]/', '', $sb->shelf_level ?? '1') ?: '1' }}, Slot {{ $sb->slot_number ?: '01' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ $sb->component->category_label }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                    {{ $sb->batch_lot_number ?: '-' }}
                                </td>

                                <td class="py-3 px-4 text-center font-bold text-slate-900 text-sm">
                                    {{ number_format($sb->quantity, 0) }} {{ $sb->component->uom }}
                                </td>

                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <a href="{{ route('transactions.create', ['component_id' => $sb->component->id]) }}" class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-lg transition inline-flex items-center">
                                        Keluarkan (Pick)
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    Tidak ada komponen yang tersimpan di rak ini saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>

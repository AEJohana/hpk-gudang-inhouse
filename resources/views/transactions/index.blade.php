<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div class="flex items-start space-x-3">
                <a href="{{ route('dashboard') }}" 
                   class="mt-1 px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer shrink-0" 
                   title="Kembali ke Beranda Utama">
                    <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                    <span>Kembali</span>
                </a>
                <div>
                    <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                        Transaksi Komponen Gudang HPK
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Penerimaan (Inbound), Pengeluaran (Outbound Karoseri), dan Transfer Antar Rak</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 self-start sm:self-auto">
                <a href="{{ route('transactions.create', ['type' => 'inbound']) }}" class="px-3 py-1.5 sm:px-3.5 sm:py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center">
                    <i class="fa-solid fa-arrow-down me-1.5"></i>
                    Inbound
                </a>
                <a href="{{ route('transactions.create', ['type' => 'outbound']) }}" class="px-3 py-1.5 sm:px-3.5 sm:py-2 bg-[#0a2342] hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center">
                    <i class="fa-solid fa-arrow-up me-1.5 text-teal-400"></i>
                    Outbound
                </a>
                <a href="{{ route('transactions.create', ['type' => 'transfer']) }}" class="px-3 py-1.5 sm:px-3.5 sm:py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-sm transition flex items-center">
                    <i class="fa-solid fa-right-left me-1.5"></i>
                    Transfer Rak
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Search & Filter Bar -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-6 relative">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari No. Transaksi, No. SPK Unit, atau Surat Jalan..." 
                           class="w-full pl-10 pr-4 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-3.5 top-3 text-xs"></i>
                </div>

                <div class="sm:col-span-4">
                    <select name="type" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:outline-none text-slate-700">
                        <option value="">-- Semua Jenis Transaksi --</option>
                        <option value="inbound" {{ request('type') == 'inbound' ? 'selected' : '' }}>Penerimaan (Inbound)</option>
                        <option value="outbound" {{ request('type') == 'outbound' ? 'selected' : '' }}>Pengeluaran (Outbound Karoseri)</option>
                        <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Transfer Antar Rak</option>
                        <option value="return" {{ request('type') == 'return' ? 'selected' : '' }}>Retur Material</option>
                    </select>
                </div>

                <div class="sm:col-span-2 flex space-x-2">
                    <button type="submit" class="flex-1 py-2 px-4 bg-[#0a2342] hover:bg-slate-800 text-white font-semibold text-xs rounded-xl transition">
                        <i class="fa-solid fa-filter me-1 text-teal-400"></i> Cari
                    </button>
                    @if (request()->hasAny(['search', 'type']))
                        <a href="{{ route('transactions.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs rounded-xl flex items-center justify-center font-semibold">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- 1. Mobile Card List View (< md screens) -->
        <div class="block md:hidden space-y-3">
            @forelse ($transactions as $tx)
                <div class="mobile-data-card space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-mono font-bold text-xs text-slate-900 block">{{ $tx->transaction_number }}</span>
                            <span class="text-[10px] text-slate-400 font-medium">{{ $tx->transaction_date->format('d M Y') }}</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold border {{ $tx->type_badge['class'] }}">
                            {{ $tx->type_badge['label'] }}
                        </span>
                    </div>

                    @if ($tx->spk_number || $tx->reference_document)
                    <div class="p-2 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-[10px] text-slate-400">Referensi:</span>
                        <span class="font-mono font-bold text-slate-700 text-[11px] truncate max-w-[200px]">
                            {{ $tx->spk_number ?: ($tx->reference_document ?: 'Internal Memo') }}
                        </span>
                    </div>
                    @endif

                    <div class="text-xs">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block mb-1">Komponen ({{ $tx->items->count() }} item):</span>
                        <p class="font-semibold text-slate-800 truncate">
                            {{ $tx->items->first()?->component?->name }}
                            <span class="text-slate-500 font-normal">({{ number_format($tx->items->first()?->quantity ?? 0, 0) }} {{ $tx->items->first()?->component?->uom }})</span>
                        </p>
                        @if ($tx->items->count() > 1)
                            <span class="text-[10px] text-amber-600 font-bold block mt-0.5">+{{ $tx->items->count() - 1 }} komponen lainnya</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 block">Petugas:</span>
                            <span class="font-semibold text-slate-700 text-[11px]">{{ $tx->user->name }}</span>
                        </div>
                        <a href="{{ route('transactions.show', $tx) }}" class="px-3.5 py-1.5 bg-[#0a2342] text-white text-xs font-bold rounded-lg flex items-center">
                            Detail Slip <i class="fa-solid fa-arrow-right ms-1 text-[10px]"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-slate-400 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-300 block"></i>
                    Belum ada catatan transaksi yang sesuai dengan filter pencarian.
                </div>
            @endforelse
        </div>

        <!-- 2. Desktop & Tablet Super Table (>= md screens) -->
        <div class="hidden md:block overflow-x-auto pb-4">
            <table class="super-table">
                <thead>
                    <tr>
                        <th class="py-3 px-4">No. Transaksi & Tgl</th>
                        <th class="py-3 px-4">Tipe Mutasi</th>
                        <th class="py-3 px-4">No. SPK / Referensi</th>
                        <th class="py-3 px-4">Item Komponen</th>
                        <th class="py-3 px-4">Petugas / Operator</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr class="super-row">
                            <!-- Transaction Number & Date -->
                            <td class="py-4 px-4">
                                <span class="font-mono font-bold text-slate-900 block text-xs">{{ $tx->transaction_number }}</span>
                                <span class="text-[11px] text-slate-400 font-medium">{{ $tx->transaction_date->format('d M Y') }}</span>
                            </td>

                            <!-- Type Badge -->
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold border {{ $tx->type_badge['class'] }}">
                                    {{ $tx->type_badge['label'] }}
                                </span>
                            </td>

                            <!-- SPK / Reference Doc -->
                            <td class="py-4 px-4">
                                @if ($tx->spk_number)
                                    <span class="font-mono text-xs font-bold text-blue-900 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200 block w-max">
                                        {{ $tx->spk_number }}
                                    </span>
                                @endif
                                <span class="text-[11px] text-slate-500 block {{ $tx->spk_number ? 'mt-1' : '' }}">
                                    Ref: {{ $tx->reference_document ?: 'Internal Memo' }}
                                </span>
                            </td>

                            <!-- Items summary -->
                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-800">{{ $tx->items->count() }} Komponen</span>
                                <p class="text-[11px] text-slate-500 truncate max-w-xs mt-0.5">
                                    {{ $tx->items->first()?->component?->name }} 
                                    ({{ number_format($tx->items->first()?->quantity ?? 0, 0) }} {{ $tx->items->first()?->component?->uom }})
                                    @if ($tx->items->count() > 1)
                                        <span class="text-amber-600 font-bold">+{{ $tx->items->count() - 1 }} lainnya</span>
                                    @endif
                                </p>
                            </td>

                            <!-- User -->
                            <td class="py-4 px-4">
                                <span class="font-semibold text-slate-800 block">{{ $tx->user->name }}</span>
                                <span class="text-[10px] text-teal-700 font-medium">{{ $tx->user->role_badge }}</span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('transactions.show', $tx) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-teal-50 hover:text-teal-700 text-slate-700 font-bold text-xs rounded-xl transition inline-flex items-center">
                                    Detail Slip <i class="fa-solid fa-arrow-right ms-1.5 text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 bg-white rounded-2xl shadow-sm">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-300 block"></i>
                                Belum ada catatan transaksi yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                {{ $transactions->links() }}
            </div>
        @endif

    </div>
</x-app-layout>

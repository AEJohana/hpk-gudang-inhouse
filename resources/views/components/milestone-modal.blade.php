{{-- Global Milestone Success Modal (Pencapaian Besar Operasional) --}}
<div x-data="globalMilestoneModal()"
     x-cloak
     x-show="isOpen"
     x-on:open-milestone-modal.window="openModal($event.detail)"
     x-on:keydown.escape.window="closeModal()"
     class="fixed inset-0 z-[125] flex items-center justify-center p-4 sm:p-6"
     style="display: none;"
     role="dialog"
     aria-modal="true">

    {{-- Frosted Glass Backdrop --}}
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeModal()"
         class="fixed inset-0 bg-slate-950/65 backdrop-blur-xs transition-opacity">
    </div>

    {{-- Dialog Card --}}
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-90 translate-y-4"
         class="relative w-full max-w-sm sm:max-w-md bg-white rounded-3xl border border-slate-100 shadow-2xl p-6 sm:p-7 text-center overflow-hidden transform transition-all z-10"
         style="box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);">

        {{-- Top Celebration Ambient Glow --}}
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-24 rounded-full blur-2xl pointer-events-none opacity-40 bg-emerald-400"></div>

        {{-- Squircle Milestone Badge --}}
        <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white flex items-center justify-center relative mb-4 shadow-lg shadow-emerald-500/25">
            <div class="absolute inset-0 rounded-2xl bg-emerald-400 animate-ping opacity-25 pointer-events-none"></div>
            <i :class="icon" class="text-2xl sm:text-3xl relative z-10"></i>
        </div>

        {{-- Title & Subtitle --}}
        <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight" x-text="title"></h3>
        <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed" x-text="subtitle"></p>

        {{-- Details Card Box --}}
        <div x-show="details && details.length > 0" class="mt-4 p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-left space-y-2">
            <template x-for="(item, idx) in details" :key="idx">
                <div class="flex items-center justify-between text-xs sm:text-sm py-0.5 border-b border-slate-200/50 last:border-b-0">
                    <span class="text-slate-500 font-medium" x-text="item.label"></span>
                    <span class="text-slate-900 font-bold text-right ml-2 truncate" x-text="item.value"></span>
                </div>
            </template>
        </div>

        {{-- Actions --}}
        <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-center gap-2.5 sm:gap-3">
            <button type="button"
                    @click="closeModal()"
                    class="w-full sm:w-1/2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs sm:text-sm transition cursor-pointer">
                <span x-text="secondaryBtnText"></span>
            </button>
            <template x-if="primaryBtnUrl">
                <a :href="primaryBtnUrl"
                   class="w-full sm:w-1/2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i :class="primaryBtnIcon" x-show="primaryBtnIcon"></i>
                    <span x-text="primaryBtnText"></span>
                </a>
            </template>
            <template x-if="!primaryBtnUrl && primaryBtnAction">
                <button type="button"
                        @click="executePrimaryAction()"
                        class="w-full sm:w-1/2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i :class="primaryBtnIcon" x-show="primaryBtnIcon"></i>
                    <span x-text="primaryBtnText"></span>
                </button>
            </template>
        </div>
    </div>
</div>

<script>
    function globalMilestoneModal() {
        return {
            isOpen: false,
            title: 'Proses Berhasil Diselesaikan!',
            subtitle: 'Seluruh data transaksi dan stok telah disinkronkan.',
            icon: 'fa-solid fa-boxes-stacked',
            details: [],
            primaryBtnText: 'Lihat Detail',
            primaryBtnIcon: null,
            primaryBtnUrl: null,
            primaryBtnAction: null,
            secondaryBtnText: 'Selesai & Tutup',

            init() {
                @if (session('milestone'))
                    this.openModal(@js(session('milestone')));
                @endif
            },

            openModal(data) {
                if (!data) return;
                this.title = data.title || 'Proses Berhasil!';
                this.subtitle = data.subtitle || '';
                this.icon = data.icon || 'fa-solid fa-boxes-stacked';
                this.details = data.details || [];
                
                this.primaryBtnText = data.primaryAction?.text || 'Lihat Detail';
                this.primaryBtnIcon = data.primaryAction?.icon || null;
                this.primaryBtnUrl = data.primaryAction?.url || null;
                this.primaryBtnAction = typeof data.primaryAction?.action === 'function' ? data.primaryAction.action : null;

                this.secondaryBtnText = data.secondaryAction?.text || 'Selesai & Tutup';
                this.isOpen = true;
            },

            closeModal() {
                this.isOpen = false;
            },

            executePrimaryAction() {
                if (this.primaryBtnAction && typeof this.primaryBtnAction === 'function') {
                    this.primaryBtnAction();
                }
                this.isOpen = false;
            }
        };
    }

    // Helper Global Window Function
    window.showMilestoneModal = function(options) {
        window.dispatchEvent(new CustomEvent('open-milestone-modal', { detail: options }));
    };
</script>

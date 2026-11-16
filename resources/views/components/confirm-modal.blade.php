{{-- Global Elegant Confirmation Modal --}}
<div x-data="globalConfirmModal()"
     x-cloak
     x-show="isOpen"
     x-on:open-confirm-modal.window="openModal($event.detail)"
     x-on:keydown.escape.window="closeModal()"
     class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6"
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
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity">
    </div>

    {{-- Dialog Card --}}
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90 translate-y-3"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-90 translate-y-3"
         class="relative w-full max-w-sm sm:max-w-md bg-white rounded-3xl border border-slate-100 shadow-2xl p-6 sm:p-7 text-center overflow-hidden transform transition-all z-10"
         style="box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);">

        {{-- Top Subtle Ambient Glow matching modal type --}}
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-44 h-24 rounded-full blur-2xl pointer-events-none opacity-40"
             :class="{
                 'bg-rose-500': type === 'danger',
                 'bg-amber-400': type === 'warning',
                 'bg-blue-500': type === 'info' || type === 'primary'
             }">
        </div>

        {{-- Squircle Icon Container with subtle pulse --}}
        <div class="mx-auto w-16 h-16 sm:w-18 sm:h-18 rounded-2xl flex items-center justify-center relative mb-4 transition-colors"
             :class="{
                 'bg-rose-50 text-rose-600 border border-rose-200/80': type === 'danger',
                 'bg-amber-50 text-amber-600 border border-amber-200/80': type === 'warning',
                 'bg-blue-50 text-blue-600 border border-blue-200/80': type === 'info' || type === 'primary'
             }">
            <div class="absolute inset-0 rounded-2xl animate-ping opacity-20 pointer-events-none"
                 :class="{
                     'bg-rose-400': type === 'danger',
                     'bg-amber-400': type === 'warning',
                     'bg-blue-400': type === 'info' || type === 'primary'
                 }">
            </div>
            <i :class="icon" class="text-2xl sm:text-3xl relative z-10"></i>
        </div>

        {{-- Title --}}
        <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight" x-text="title"></h3>

        {{-- Message / Description --}}
        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed break-words px-2" x-text="message"></p>

        {{-- Action Buttons --}}
        <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-center gap-2.5 sm:gap-3">
            <button type="button"
                    @click="closeModal()"
                    class="w-full sm:w-1/2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs sm:text-sm transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-slate-300">
                <span x-text="cancelText"></span>
            </button>
            <button type="button"
                    @click="executeConfirm()"
                    class="w-full sm:w-1/2 px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm text-white shadow-md transition cursor-pointer focus:outline-none focus:ring-2"
                    :class="{
                        'bg-rose-600 hover:bg-rose-700 focus:ring-rose-400 shadow-rose-500/20': type === 'danger',
                        'bg-amber-500 hover:bg-amber-600 text-slate-950 focus:ring-amber-300 shadow-amber-500/20': type === 'warning',
                        'bg-blue-600 hover:bg-blue-700 focus:ring-blue-400 shadow-blue-500/20': type === 'info' || type === 'primary'
                    }"
                    :style="type === 'danger' ? 'background-color: #e11d48;' : (type === 'warning' ? 'background-color: #f59e0b; color: #020617;' : 'background-color: #1a56db;')">
                <span x-text="confirmText"></span>
            </button>
        </div>

        {{-- Internal Dynamic Form for POST / DELETE / GET Submissions --}}
        <form x-ref="dynamicForm" method="POST" action="" style="display: none;">
            @csrf
            <input type="hidden" name="_method" x-ref="methodInput" value="POST">
        </form>
    </div>
</div>

<script>
    function globalConfirmModal() {
        return {
            isOpen: false,
            title: 'Konfirmasi Aksi',
            message: 'Apakah Anda yakin ingin melanjutkan aksi ini?',
            confirmText: 'Ya, Lanjutkan',
            cancelText: 'Batal',
            type: 'danger', // danger, warning, info
            icon: 'fa-solid fa-triangle-exclamation',
            actionUrl: null,
            method: 'POST',
            formId: null,
            callback: null,

            openModal(data) {
                this.title = data.title || 'Konfirmasi Aksi';
                this.message = data.message || 'Apakah Anda yakin ingin melanjutkan aksi ini?';
                this.confirmText = data.confirmText || 'Ya, Lanjutkan';
                this.cancelText = data.cancelText || 'Batal';
                this.type = data.type || 'danger';
                
                // Fallback icon based on type
                if (data.icon) {
                    this.icon = data.icon;
                } else if (this.type === 'danger') {
                    this.icon = 'fa-solid fa-triangle-exclamation';
                } else if (this.type === 'warning') {
                    this.icon = 'fa-solid fa-circle-exclamation';
                } else {
                    this.icon = 'fa-solid fa-circle-question';
                }

                this.actionUrl = data.actionUrl || null;
                this.method = (data.method || 'POST').toUpperCase();
                this.formId = data.formId || null;
                this.callback = typeof data.onConfirm === 'function' ? data.onConfirm : null;

                this.isOpen = true;
            },

            closeModal() {
                this.isOpen = false;
                this.callback = null;
            },

            executeConfirm() {
                const cb = this.callback;
                const formId = this.formId;
                const actionUrl = this.actionUrl;
                const method = this.method;

                this.isOpen = false;

                // Priority 1: JavaScript Callback
                if (cb && typeof cb === 'function') {
                    cb();
                    return;
                }

                // Priority 2: Existing Form ID
                if (formId) {
                    const targetForm = document.getElementById(formId);
                    if (targetForm) {
                        targetForm.submit();
                        return;
                    }
                }

                // Priority 3: Dynamic Form Action URL
                if (actionUrl) {
                    const dynForm = this.$refs.dynamicForm;
                    const methodInput = this.$refs.methodInput;
                    
                    dynForm.action = actionUrl;
                    if (method === 'GET') {
                        dynForm.method = 'GET';
                        methodInput.value = 'GET';
                    } else {
                        dynForm.method = 'POST';
                        methodInput.value = method;
                    }
                    dynForm.submit();
                }
            }
        };
    }

    // Helper Global Window Function for Non-Alpine JS Calls
    window.showConfirmDialog = function(options) {
        window.dispatchEvent(new CustomEvent('open-confirm-modal', { detail: options }));
    };
</script>

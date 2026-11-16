{{-- Global Floating Glass Toast Notification Container --}}
<div x-data="globalToastManager()"
     x-cloak
     x-on:toast.window="addToast($event.detail)"
     class="fixed top-4 right-4 sm:top-6 sm:right-6 z-[130] flex flex-col space-y-3 pointer-events-none max-w-sm sm:max-w-md w-full px-4 sm:px-0"
     style="display: none;"
     x-show="toasts.length > 0">

    <template x-for="(toast, index) in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-[-12px] scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 translate-x-[20px] scale-95"
             @mouseenter="pauseToast(toast)"
             @mouseleave="resumeToast(toast)"
             class="pointer-events-auto relative w-full bg-white/95 backdrop-blur-md rounded-2xl p-4 shadow-xl border flex items-start space-x-3.5 overflow-hidden transition-all select-none"
             :class="{
                 'border-emerald-200/90 shadow-emerald-500/10': toast.type === 'success',
                 'border-rose-200/90 shadow-rose-500/10': toast.type === 'error',
                 'border-amber-200/90 shadow-amber-500/10': toast.type === 'warning',
                 'border-blue-200/90 shadow-blue-500/10': toast.type === 'info'
             }"
             :style="toast.type === 'success' ? 'box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.15);' : (toast.type === 'error' ? 'box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.15);' : 'box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.1);')">

            {{-- Squircle Icon Container with subtle pulse --}}
            <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center relative mt-0.5"
                 :class="{
                     'bg-emerald-50 text-emerald-600 border border-emerald-200': toast.type === 'success',
                     'bg-rose-50 text-rose-600 border border-rose-200': toast.type === 'error',
                     'bg-amber-50 text-amber-600 border border-amber-200': toast.type === 'warning',
                     'bg-blue-50 text-blue-600 border border-blue-200': toast.type === 'info'
                 }">
                <i :class="toast.icon" class="text-base"></i>
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0 pr-2">
                <h4 class="text-xs sm:text-sm font-bold tracking-tight text-slate-900" x-text="toast.title"></h4>
                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed break-words" x-text="toast.message"></p>
            </div>

            {{-- Close Button --}}
            <button type="button"
                    @click="removeToast(toast.id)"
                    class="shrink-0 w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition cursor-pointer"
                    title="Tutup notifikasi">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>

            {{-- Countdown Progress Bar at Bottom --}}
            <div class="absolute bottom-0 left-0 h-1 transition-all ease-linear"
                 :style="`width: ${toast.progress}%; background-color: ${toast.progressColor};`">
            </div>
        </div>
    </template>
</div>

<script>
    function globalToastManager() {
        return {
            toasts: [],
            counter: 0,

            init() {
                // Read Server-side Flash Sessions automatically
                @if (session('success'))
                    this.addToast({
                        type: 'success',
                        title: 'Berhasil!',
                        message: @js(session('success')),
                        duration: 4000
                    });
                @endif

                @if (session('error'))
                    this.addToast({
                        type: 'error',
                        title: 'Terjadi Kesalahan / Perhatian',
                        message: @js(session('error')),
                        duration: 5500
                    });
                @endif

                @if (session('warning'))
                    this.addToast({
                        type: 'warning',
                        title: 'Peringatan',
                        message: @js(session('warning')),
                        duration: 4500
                    });
                @endif

                @if (session('status') && !session('success'))
                    this.addToast({
                        type: 'info',
                        title: 'Status Operasional',
                        message: @js(session('status')),
                        duration: 4000
                    });
                @endif
            },

            addToast(data) {
                this.counter++;
                const id = 'toast_' + this.counter + '_' + Date.now();
                const type = data.type || 'info';
                
                let icon = 'fa-solid fa-circle-info';
                let progressColor = '#3b82f6';
                if (type === 'success') {
                    icon = 'fa-solid fa-circle-check';
                    progressColor = '#10b981';
                } else if (type === 'error') {
                    icon = 'fa-solid fa-circle-xmark';
                    progressColor = '#f43f5e';
                } else if (type === 'warning') {
                    icon = 'fa-solid fa-triangle-exclamation';
                    progressColor = '#f59e0b';
                }

                const toast = {
                    id: id,
                    type: type,
                    title: data.title || (type === 'success' ? 'Berhasil!' : (type === 'error' ? 'Kesalahan' : 'Pemberitahuan')),
                    message: data.message || '',
                    icon: data.icon || icon,
                    progressColor: progressColor,
                    duration: data.duration || (type === 'error' ? 5500 : 3800),
                    progress: 100,
                    visible: true,
                    isPaused: false,
                    startTime: Date.now(),
                    remainingTime: data.duration || (type === 'error' ? 5500 : 3800),
                    intervalId: null
                };

                this.toasts.unshift(toast);
                this.startTimer(toast);
            },

            startTimer(toast) {
                const stepMs = 50;
                const totalDuration = toast.duration;

                toast.intervalId = setInterval(() => {
                    if (toast.isPaused) return;

                    toast.remainingTime -= stepMs;
                    toast.progress = Math.max(0, (toast.remainingTime / totalDuration) * 100);

                    if (toast.remainingTime <= 0) {
                        this.removeToast(toast.id);
                    }
                }, stepMs);
            },

            pauseToast(toast) {
                toast.isPaused = true;
            },

            resumeToast(toast) {
                toast.isPaused = false;
            },

            removeToast(id) {
                const target = this.toasts.find(t => t.id === id);
                if (target) {
                    if (target.intervalId) clearInterval(target.intervalId);
                    target.visible = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 250);
                }
            }
        };
    }

    // Helper Global Window Function for Non-Alpine JS Calls
    window.showToast = function(options) {
        window.dispatchEvent(new CustomEvent('toast', { detail: options }));
    };
</script>

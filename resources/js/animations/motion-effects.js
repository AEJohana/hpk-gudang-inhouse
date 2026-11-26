import { gsap } from 'gsap';
import { animate, spring } from 'motion';

/**
 * 1. Animated Number Counter (GSAP)
 * Menjalankan animasi rolling counter angka metrik dari 0 ke nilai aktual saat halaman dimuat.
 * Murni memanipulasi teks angka, TIDAK PERNAH menyentuh opacity atau menyembunyikan elemen.
 */
export function initNumberCounters() {
    const counters = document.querySelectorAll('.counter-value');

    counters.forEach((el) => {
        if (el.dataset.counterDone === 'true') return;

        const targetText = el.getAttribute('data-target') || el.innerText.trim();
        const targetValue = parseInt(targetText.replace(/[^\d]/g, ''), 10);

        if (isNaN(targetValue) || targetValue === 0) return;

        el.dataset.counterDone = 'true';
        const obj = { val: 0 };

        gsap.to(obj, {
            val: targetValue,
            duration: 0.9,
            ease: 'power2.out',
            onUpdate: () => {
                el.innerText = Math.round(obj.val).toLocaleString('id-ID');
            }
        });
    });
}

/**
 * 2. Motion Spring Micro-Interactions
 * Menghubungkan elemen interaktif berkelas .motion-spring ke spring physics modern
 */
export function initMotionSpring() {
    const springElements = document.querySelectorAll('.motion-spring');

    springElements.forEach((el) => {
        if (el.dataset.motionAttached === 'true') return;
        el.dataset.motionAttached = 'true';

        el.addEventListener('mouseenter', () => {
            animate(el, { scale: 1.02 }, { easing: spring({ stiffness: 400, damping: 25 }) });
        });

        el.addEventListener('mouseleave', () => {
            animate(el, { scale: 1.0 }, { easing: spring({ stiffness: 400, damping: 25 }) });
        });
    });
}

/**
 * 3. Responsive Grid FLIP Transition (GSAP)
 * Menganimasikan perpindahan posisi kartu secara halus (GPU transform) ketika jumlah kolom
 * CSS Grid berubah (misal 6x1 <-> 3x2 <-> 2x3 saat layar/jendela berubah ukuran).
 */
const activeGridObservers = [];

function getGridColumnCount(gridEl) {
    const cols = window.getComputedStyle(gridEl).gridTemplateColumns;
    if (!cols || cols === 'none') return 1;
    return cols.trim().split(/\s+/).filter(Boolean).length;
}

export function initResponsiveGridFlip() {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const grids = document.querySelectorAll('[data-flip-grid]');

    grids.forEach((grid) => {
        if (grid.dataset.flipAttached === 'true') return;
        grid.dataset.flipAttached = 'true';

        const cards = Array.from(grid.querySelectorAll('[data-flip-card]'));
        if (cards.length === 0) return;

        let prevCols = getGridColumnCount(grid);
        let prevRects = new Map();
        const captureRects = () => {
            const map = new Map();
            cards.forEach((card) => map.set(card, card.getBoundingClientRect()));
            return map;
        };
        prevRects = captureRects();

        const observer = new ResizeObserver(() => {
            const nextCols = getGridColumnCount(grid);

            // Clear active transforms before measuring true target layout rects
            gsap.killTweensOf(cards);
            gsap.set(cards, { clearProps: 'transform' });

            const nextRects = captureRects();

            if (nextCols !== prevCols) {
                cards.forEach((card, idx) => {
                    const oldRect = prevRects.get(card);
                    const newRect = nextRects.get(card);
                    if (!oldRect || !newRect) return;

                    const dx = oldRect.left - newRect.left;
                    const dy = oldRect.top - newRect.top;
                    if (Math.abs(dx) < 1 && Math.abs(dy) < 1) return;

                    gsap.fromTo(
                        card,
                        { x: dx, y: dy },
                        {
                            x: 0,
                            y: 0,
                            duration: 0.34,
                            delay: idx * 0.02,
                            ease: 'power2.out',
                            clearProps: 'transform',
                            overwrite: 'auto',
                        }
                    );
                });
                prevCols = nextCols;
            }

            prevRects = nextRects;
        });

        observer.observe(grid);
        activeGridObservers.push({ observer, grid });
    });
}

/**
 * 4. Pembersihan Aman saat Turbo Unloads
 */
export function cleanupMotionEffects() {
    while (activeGridObservers.length > 0) {
        const entry = activeGridObservers.pop();
        try {
            entry.observer.disconnect();
            if (entry.grid) {
                delete entry.grid.dataset.flipAttached;
            }
        } catch (e) {}
    }

    try {
        gsap.killTweensOf('*');
    } catch (e) {}
}

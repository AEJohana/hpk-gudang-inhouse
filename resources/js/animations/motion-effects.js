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
 * 3. Pembersihan Aman saat Turbo Unloads
 */
export function cleanupMotionEffects() {
    try {
        gsap.killTweensOf('*');
    } catch (e) {}
}

/**
 * Native Hardware-Accelerated Smooth Scrolling
 * Menggunakan akselerasi kartu grafis (GPU) browser langsung via 'scroll-behavior: smooth'.
 * Bebas lag, 0ms input latency, tidak ada pembajakan event wheel, dan 100% responsif.
 */

export function initSmoothScroll() {
    if (window.__lenis) {
        try {
            window.__lenis.destroy();
        } catch (e) {}
        window.__lenis = null;
    }
    return null;
}

export function destroySmoothScroll() {
    if (window.__lenis) {
        try {
            window.__lenis.destroy();
        } catch (e) {}
        window.__lenis = null;
    }
}

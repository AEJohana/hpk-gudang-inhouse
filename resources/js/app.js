import './bootstrap';
import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';
import { initSmoothScroll, destroySmoothScroll } from './animations/smooth-scroll';
import { initNumberCounters, initMotionSpring, initResponsiveGridFlip, cleanupMotionEffects } from './animations/motion-effects';
import { initLiveFilters, cleanupLiveFilters } from './components/live-filter';
import { initSilentFileDrops, cleanupSilentFileDrops } from './components/silent-file-drop';

window.Alpine = Alpine;
window.Turbo = Turbo;

// Inisialisasi komponen interaktif pada setiap navigasi Turbo
function initPageInteractions() {
    initSmoothScroll();
    initNumberCounters();
    initMotionSpring();
    initResponsiveGridFlip();
    initLiveFilters();
    initSilentFileDrops();
}

// Lifecycle listener: Turbo load menangani pemuatan awal dan setiap navigasi berikutnya
document.addEventListener('turbo:load', initPageInteractions);

// Ensure Alpine initializes properly on Turbo renders
document.addEventListener('turbo:render', () => {
    if (window.Alpine) {
        window.Alpine.initTree(document.body);
    }
});

// Lifecycle listener: Bersihkan sebelum pergantian halaman Turbo
document.addEventListener('turbo:before-render', () => {
    destroySmoothScroll();
    cleanupMotionEffects();
    cleanupLiveFilters();
    cleanupSilentFileDrops();
});

// Safely close camera scanner before leaving page
document.addEventListener('turbo:before-visit', () => {
    if (window._stopGlobalScanner) {
        window._stopGlobalScanner();
    }
    destroySmoothScroll();
    cleanupMotionEffects();
    cleanupLiveFilters();
    cleanupSilentFileDrops();
});

Alpine.start();
Turbo.start();

import './bootstrap';
import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.Turbo = Turbo;

// Ensure Alpine initializes properly on Turbo renders
document.addEventListener('turbo:render', () => {
    if (window.Alpine) {
        window.Alpine.initTree(document.body);
    }
});

// Safely close camera scanner before leaving page
document.addEventListener('turbo:before-visit', () => {
    if (window._stopGlobalScanner) {
        window._stopGlobalScanner();
    }
});

Alpine.start();
Turbo.start();

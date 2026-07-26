/*!
 * VNL Theme 1.0 — davranış katmanı (vanilla JS, jQuery'siz)
 * Kaynak: theme/js — çıktı: assets/vnl/js/vnl.min.js (IIFE)
 * Bootstrap davranışları (dropdown/collapse/offcanvas) data API ile çalışır;
 * burada yalnız temaya özgü olanlar var: tema modu, sidebar mini,
 * tooltip init ve lightbox.
 */

import { initThemeMode } from './modules/theme-mode.js';
import { initSidebar } from './modules/sidebar.js';
import { initFlyout } from './modules/flyout.js';
import { initTooltips } from './modules/tooltips.js';
import { initLightbox } from './modules/lightbox.js';

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

function boot() {
    initThemeMode();
    initSidebar();
    initFlyout();
    initTooltips();
    initLightbox();
}

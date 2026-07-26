/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Davranış katmanı (vanilla JS, jQuery'siz)
 * Kaynak: theme/js — çıktı: assets/vnl/js/vnl.min.js (IIFE)
 *  Authors: Voxnup Authors
 * ============================================================
 */

import { initThemeMode } from './modules/theme-mode.js';
import { initSidebar } from './modules/sidebar.js';
import { initFlyout } from './modules/flyout.js';
import { initTooltips } from './modules/tooltips.js';
import { initLightbox } from './modules/lightbox.js';
import { initSpinner } from './modules/spinner.js';
import { initPassword } from './modules/password.js';
import { initSelect } from './modules/select.js';
import { initLoadingButton } from './modules/loading-button.js';

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
    initSpinner();
    initPassword();
    initSelect();
    initLoadingButton();
}

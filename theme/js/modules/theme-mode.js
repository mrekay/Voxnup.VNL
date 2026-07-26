/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Tema modu (light/dark/system) davranisi
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * Tema modu (light / dark / system).
 *
 * Pre-paint attribute ataması head'deki inline script'in işidir (FOUC'suz);
 * burası yalnız etkileşimi yönetir. localStorage anahtarı `data-bs-theme`
 * Metronic döneminden beri aynıdır — eski kullanıcı tercihleri korunur.
 *
 * Markup sözleşmesi: <button data-vnl-theme-value="light|dark|system">
 */

const STORAGE_KEY = 'data-bs-theme';
const media = window.matchMedia('(prefers-color-scheme: dark)');

function storedMode() {
    return localStorage.getItem(STORAGE_KEY) || 'system';
}

function resolve(mode) {
    if (mode === 'system') {
        return media.matches ? 'dark' : 'light';
    }
    return mode;
}

function apply(mode) {
    document.documentElement.setAttribute('data-bs-theme', resolve(mode));
    markActive(mode);
    document.dispatchEvent(new CustomEvent('vnl.theme.changed', {
        detail: { mode: mode, resolved: resolve(mode) },
    }));
}

function markActive(mode) {
    document.querySelectorAll('[data-vnl-theme-value]').forEach(function (el) {
        el.classList.toggle('active', el.getAttribute('data-vnl-theme-value') === mode);
    });
}

export function initThemeMode() {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-vnl-theme-value]');
        if (!btn) return;

        const mode = btn.getAttribute('data-vnl-theme-value');
        localStorage.setItem(STORAGE_KEY, mode);
        apply(mode);
    });

    media.addEventListener('change', function () {
        if (storedMode() === 'system') {
            apply('system');
        }
    });

    markActive(storedMode());
}

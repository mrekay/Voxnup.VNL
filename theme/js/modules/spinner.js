/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Tam sayfa yükleniyor katmanı (#loadingSpinner)
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * vnl.js'ten temaya taşındı — dört tarihsel global isim de korunur:
 * spinnerShow(text)/spinnerHide() ve showSpinner()/hideSpinner().
 * jQuery gerektirmez; açılış yumuşaklığını CSS animasyonu verir.
 */

function overlay() {
    return document.getElementById('loadingSpinner');
}

function spinnerShow(text) {
    const el = overlay();
    if (!el) return;

    const label = el.querySelector('.spinner-text');
    if (label) label.innerHTML = text || '';
    el.style.display = 'block';
}

function spinnerHide() {
    const el = overlay();
    if (el) el.style.display = 'none';
}

export function initSpinner() {
    window.spinnerShow = spinnerShow;
    window.spinnerHide = spinnerHide;
    window.showSpinner = spinnerShow;
    window.hideSpinner = spinnerHide;
}

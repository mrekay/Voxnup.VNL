/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Sidebar daraltma modlari ve offcanvas davranisi
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * Sidebar davranışları.
 *
 * Daraltılmış durumun iki modu vardır (html[data-vnl-sidebar] değeri):
 *   "mini"  → sabit ikon rayı
 *   "hover" → ikon rayı; üzerine gelince/tıklayınca üstüne doğru genişler
 *
 * Daraltma düğmesi hangi moda daraltacağını <body data-vnl-sidebar-mode>
 * üzerinden okur (header.php, $page_config['leftsidebar'] değerinden basar;
 * varsayılan "hover"). Tercih localStorage('vnl-sidebar-mini') ile kalıcıdır
 * ve pre-paint restore head'deki inline script'tedir; eski kayıtlardaki
 * "1" değeri "mini" olarak okunur.
 *
 * Mobil offcanvas'ta bir menü linkine tıklanınca çekmece kapanır.
 */

const STORAGE_KEY = 'vnl-sidebar-mini';

function collapseMode() {
    return document.body.getAttribute('data-vnl-sidebar-mode') || 'hover';
}

export function initSidebar() {
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-vnl-toggle="sidebar-mini"]');
        if (toggle) {
            const html = document.documentElement;
            if (html.hasAttribute('data-vnl-sidebar')) {
                html.removeAttribute('data-vnl-sidebar');
                localStorage.removeItem(STORAGE_KEY);
            } else {
                const mode = collapseMode();
                html.setAttribute('data-vnl-sidebar', mode);
                localStorage.setItem(STORAGE_KEY, mode);
            }
            // Focus butonda kalırsa hover modundaki sidebar açık yapışıyor
            toggle.blur();
            return;
        }

        // Offcanvas modunda gezinme: linke tıklayınca çekmeceyi kapat
        const link = e.target.closest('.vnl-sidebar .vnl-menu-link[href]');
        if (link && window.bootstrap) {
            const sidebar = link.closest('.vnl-sidebar');
            const instance = window.bootstrap.Offcanvas.getInstance(sidebar);
            if (instance) {
                instance.hide();
            }
        }
    });
}

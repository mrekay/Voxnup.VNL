/**
 * Sidebar davranışları.
 *
 * - Mini/daraltılmış durum (yalnız desktop): html[data-vnl-sidebar="mini"]
 *   attribute'u + localStorage('vnl-sidebar-mini'). Pre-paint restore
 *   head'deki inline script'te — burada yalnız toggle.
 * - Mobil offcanvas'ta bir menü linkine tıklanınca çekmece kapanır
 *   (Bootstrap Offcanvas instance'ı üzerinden).
 *
 * Markup sözleşmesi:
 *   <button data-vnl-toggle="sidebar-mini">  — daraltma düğmesi
 *   <aside id="vnlSidebar" class="vnl-sidebar offcanvas-lg offcanvas-start">
 */

const STORAGE_KEY = 'vnl-sidebar-mini';

export function initSidebar() {
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-vnl-toggle="sidebar-mini"]');
        if (toggle) {
            const mini = document.documentElement.getAttribute('data-vnl-sidebar') !== 'mini';
            if (mini) {
                document.documentElement.setAttribute('data-vnl-sidebar', 'mini');
                localStorage.setItem(STORAGE_KEY, '1');
            } else {
                document.documentElement.removeAttribute('data-vnl-sidebar');
                localStorage.removeItem(STORAGE_KEY);
            }
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

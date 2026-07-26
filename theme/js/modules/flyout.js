/**
 * Sidebar alt menüleri: sağa açılan flyout paneller.
 *
 * Desktop (lg+): .vnl-has-sub öğesinin üzerine gelince ya da başlığına
 * tıklayınca alt menü sidebar'ın sağ kenarından fixed panel olarak açılır —
 * normal, mini ve hover-genişleme modlarının hepsinde. Fare ayrılınca kısa
 * bir gecikmeyle kapanır; ESC, dış tıklama ve menü kaydırması da kapatır.
 *
 * Mobil (offcanvas): panel yerine yerinde açılım (CSS static), tıklama toggle.
 */

const OPEN_CLASS = 'show';
const CLOSE_DELAY = 160;

let openSub = null;
let closeTimer = null;

function isDesktop() {
    return window.matchMedia('(min-width: 992px)').matches;
}

function subOf(item) {
    return item.querySelector(':scope > .vnl-menu-sub');
}

function btnOf(item) {
    return item.querySelector(':scope > .vnl-menu-link');
}

function open(item) {
    const sub = subOf(item);
    if (!sub) return;

    clearTimeout(closeTimer);
    if (openSub && openSub !== sub) close();

    sub.classList.add(OPEN_CLASS);
    btnOf(item)?.setAttribute('aria-expanded', 'true');
    openSub = sub;

    if (isDesktop()) {
        const sidebar = item.closest('.vnl-sidebar');
        const btnRect = btnOf(item).getBoundingClientRect();

        sub.style.left = Math.round(sidebar.getBoundingClientRect().right) + 'px';
        sub.style.top = '0px';

        // Panel yüksekliği belli olduktan sonra dikey hizala ve ekrana sığdır
        const height = sub.offsetHeight;
        const top = Math.max(8, Math.min(btnRect.top, window.innerHeight - height - 8));
        sub.style.top = Math.round(top) + 'px';
    }
}

function close() {
    if (!openSub) return;

    openSub.classList.remove(OPEN_CLASS);
    openSub.removeAttribute('style');
    const item = openSub.closest('.vnl-has-sub');
    btnOf(item)?.setAttribute('aria-expanded', 'false');
    openSub = null;
}

function scheduleClose() {
    clearTimeout(closeTimer);
    closeTimer = setTimeout(close, CLOSE_DELAY);
}

export function initFlyout() {
    document.querySelectorAll('.vnl-sidebar .vnl-has-sub').forEach(function (item) {
        item.addEventListener('mouseenter', function () {
            if (isDesktop()) open(item);
        });
        item.addEventListener('mouseleave', function () {
            if (isDesktop()) scheduleClose();
        });

        btnOf(item)?.addEventListener('click', function () {
            const sub = subOf(item);
            if (sub && sub.classList.contains(OPEN_CLASS)) {
                close();
            } else {
                open(item);
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });

    document.addEventListener('click', function (e) {
        if (openSub && !e.target.closest('.vnl-has-sub')) close();
    });

    // Menü kaydırılırsa fixed konum bozulur — kapat
    document.querySelector('.vnl-sidebar-nav')?.addEventListener('scroll', function () {
        if (isDesktop()) close();
    }, { passive: true });
}

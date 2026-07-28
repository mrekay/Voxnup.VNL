/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Sidebar alt menuleri: saga acilan flyout paneller
 *  Authors: Voxnup Authors
 * ============================================================
 */
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

// Panel (flyout) modu mu? Accordion modunda bile daraltılmış rayda
// (html[data-vnl-sidebar]) liste sığmayacağı için panele düşülür.
function isPanelMode() {
    return isDesktop() && (
        document.body.getAttribute('data-vnl-submenu') !== 'accordion' ||
        document.documentElement.hasAttribute('data-vnl-sidebar')
    );
}

function subOf(item) {
    return item.querySelector(':scope > .vnl-menu-sub');
}

function btnOf(item) {
    return item.querySelector(':scope > .vnl-menu-link');
}

// Panel açık olduğu sürece sidebar'ın CANLI sağ kenarına yapışık kalır:
// hover-genişleme/daralma animasyonu sürerken de birlikte kayarlar
// (kapanana kadar takip). rAF döngüsü yalnız panel açıkken çalışır.
let trackFrame = null;

function track() {
    if (!openSub || !isPanelMode()) {
        trackFrame = null;
        return;
    }

    const sidebar = openSub.closest('.vnl-sidebar');
    const right = Math.round(sidebar.getBoundingClientRect().right);
    if (parseInt(openSub.style.left, 10) !== right) {
        openSub.style.left = right + 'px';
    }

    trackFrame = requestAnimationFrame(track);
}

function startTracking() {
    if (trackFrame === null) {
        trackFrame = requestAnimationFrame(track);
    }
}

function stopTracking() {
    if (trackFrame !== null) {
        cancelAnimationFrame(trackFrame);
        trackFrame = null;
    }
}

function open(item) {
    const sub = subOf(item);
    if (!sub) return;

    // Yerinde açılım (accordion / mobil offcanvas): bağımsız toggle,
    // panel yaşam döngüsüne (openSub, dış tıklama, ESC) karışmaz.
    if (!isPanelMode()) {
        sub.classList.add(OPEN_CLASS);
        btnOf(item)?.setAttribute('aria-expanded', 'true');
        return;
    }

    clearTimeout(closeTimer);
    if (openSub && openSub !== sub) close();

    btnOf(item)?.setAttribute('aria-expanded', 'true');
    openSub = sub;

    const sidebar = item.closest('.vnl-sidebar');
    const btnRect = btnOf(item).getBoundingClientRect();

    // Görünmez aç → ölç → hizala → göster: panel hiçbir karede yanlış
    // konumda boyanmaz, animasyon doğru yerde başlar.
    sub.style.visibility = 'hidden';
    sub.style.left = Math.round(sidebar.getBoundingClientRect().right) + 'px';
    sub.style.top = '0px';
    sub.classList.add(OPEN_CLASS);

    layoutColumns(sub);

    const height = sub.offsetHeight;
    const top = Math.max(8, Math.min(btnRect.top, window.innerHeight - height - 8));
    sub.style.top = Math.round(top) + 'px';
    sub.style.visibility = '';

    startTracking();
}

/**
 * Uzun menüler kaydırılmaz: dikey dolar, yükseklik sınırına gelince yana
 * yeni sütun açar. Satır sayısı görünür yüksekliğe göre hesaplanır ve
 * sütunlar dengeli dağıtılır (13 öğe → 7+6; 10+3 değil).
 */
function layoutColumns(sub) {
    const items = sub.querySelectorAll(':scope > .vnl-menu-item');
    const first = items[0];
    if (!first) return;

    const rowGap = 2;
    const maxHeight = Math.min(window.innerHeight * .78, 560) - 16; // panel padding payı
    const rowHeight = first.offsetHeight + rowGap;
    const capacity = Math.max(1, Math.floor(maxHeight / rowHeight));

    let rows = Math.min(items.length, capacity);
    const cols = Math.ceil(items.length / rows);
    rows = Math.ceil(items.length / cols);

    sub.style.gridTemplateRows = 'repeat(' + rows + ', auto)';
}

function close() {
    if (!openSub) return;

    stopTracking();
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
        // Hover yalnız panel modunda açar; liste (accordion) modu tıklamayladır
        item.addEventListener('mouseenter', function () {
            if (isPanelMode()) open(item);
        });
        item.addEventListener('mouseleave', function () {
            if (isPanelMode()) scheduleClose();
        });

        btnOf(item)?.addEventListener('click', function () {
            const sub = subOf(item);
            if (!sub) return;

            if (sub.classList.contains(OPEN_CLASS)) {
                if (openSub === sub) {
                    close();
                } else {
                    // Yerinde açılım (accordion / mobil): bağımsız kapatma
                    sub.classList.remove(OPEN_CLASS);
                    btnOf(item)?.setAttribute('aria-expanded', 'false');
                }
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

    // Menü kaydırılır ya da pencere boyutlanırsa fixed konum bozulur — kapat
    document.querySelector('.vnl-sidebar-nav')?.addEventListener('scroll', function () {
        if (isDesktop()) close();
    }, { passive: true });

    window.addEventListener('resize', function () {
        if (isDesktop()) close();
    }, { passive: true });
}

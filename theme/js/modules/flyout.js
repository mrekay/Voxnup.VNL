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

// Panel açık olduğu sürece sidebar'ın CANLI sağ kenarına yapışık kalır:
// hover-genişleme/daralma animasyonu sürerken de birlikte kayarlar
// (kapanana kadar takip). rAF döngüsü yalnız panel açıkken çalışır.
let trackFrame = null;

function track() {
    if (!openSub || !isDesktop()) {
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

    clearTimeout(closeTimer);
    if (openSub && openSub !== sub) close();

    btnOf(item)?.setAttribute('aria-expanded', 'true');
    openSub = sub;

    if (isDesktop()) {
        const sidebar = item.closest('.vnl-sidebar');
        const btnRect = btnOf(item).getBoundingClientRect();

        // Görünmez aç → ölç → hizala → göster: panel hiçbir karede yanlış
        // konumda boyanmaz, animasyon doğru yerde başlar.
        sub.style.visibility = 'hidden';
        sub.style.left = Math.round(sidebar.getBoundingClientRect().right) + 'px';
        sub.style.top = '0px';
        sub.classList.add(OPEN_CLASS);

        const height = sub.offsetHeight;
        const top = Math.max(8, Math.min(btnRect.top, window.innerHeight - height - 8));
        sub.style.top = Math.round(top) + 'px';
        sub.style.visibility = '';

        startTracking();
    } else {
        sub.classList.add(OPEN_CLASS);
    }
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

    // Menü kaydırılır ya da pencere boyutlanırsa fixed konum bozulur — kapat
    document.querySelector('.vnl-sidebar-nav')?.addEventListener('scroll', function () {
        if (isDesktop()) close();
    }, { passive: true });

    window.addEventListener('resize', function () {
        if (isDesktop()) close();
    }, { passive: true });
}

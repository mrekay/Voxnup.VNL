/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Yükleniyor düğmeleri (vnlBtnLoading / vnlBtnReset)
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * Tek yapı, iki kullanım:
 *
 * 1) Deklaratif — "yenile" tarzı AJAX tetikleyen düğmeler:
 *      <button class="btn btn-soft-primary" data-vnl-loading>Yenile</button>
 *    Tıklanınca kendini yükleniyor durumuna alır; sayfadaki jQuery AJAX
 *    trafiği bitince (ajaxStop) kendini sıfırlar.
 *    Opsiyonel: data-loading-text="Yenileniyor…"
 *
 * 2) Programatik — akışı kendin yönetiyorsan:
 *      vnlBtnLoading(btn [, metin]);   // Element, jQuery ya da seçici
 *      vnlBtnReset(btn);
 *
 * Düğmede .indicator-label/.indicator-progress span'ları varsa içerik
 * takası yerine o desen kullanılır (data-vnl-indicator="on").
 * İçerik takasında genişlik sabitlenir — düğme zıplamaz.
 * (Eski loadingButton()/loadingButtonDisable() bu yapının lehine kaldırıldı.)
 */

const BUSY = 'data-vnl-busy';
const RESTORE_KEY = '__vnlRestore';

function resolve(btn) {
    if (!btn) return null;
    if (btn.jquery) return btn[0] || null;
    if (typeof btn === 'string') return document.querySelector(btn);
    return btn;
}

function vnlBtnLoading(btn, text) {
    btn = resolve(btn);
    if (!btn || btn.hasAttribute(BUSY)) return;

    btn.setAttribute(BUSY, '1');
    btn.disabled = true;

    if (btn.querySelector('.indicator-progress')) {
        btn.setAttribute('data-vnl-indicator', 'on');
        return;
    }

    const label = text
        || btn.getAttribute('data-loading-text')
        || 'Lütfen Bekleyin';

    btn[RESTORE_KEY] = btn.innerHTML;
    btn.style.minWidth = btn.offsetWidth + 'px';
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + label;
}

function vnlBtnReset(btn) {
    btn = resolve(btn);
    if (!btn || !btn.hasAttribute(BUSY)) return;

    btn.removeAttribute(BUSY);
    btn.disabled = false;

    if (btn.hasAttribute('data-vnl-indicator')) {
        btn.removeAttribute('data-vnl-indicator');
        return;
    }

    if (RESTORE_KEY in btn) {
        btn.innerHTML = btn[RESTORE_KEY];
        delete btn[RESTORE_KEY];
    }
    btn.style.minWidth = '';
}

export function initLoadingButton() {
    window.vnlBtnLoading = vnlBtnLoading;
    window.vnlBtnReset = vnlBtnReset;

    // Deklaratif tetikleme. setTimeout(0): düğme type="submit" ise form
    // gönderimi (click'in default action'ı) disabled'dan ÖNCE tetiklensin.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-vnl-loading]');
        if (btn) {
            setTimeout(function () {
                vnlBtnLoading(btn);
            }, 0);
        }
    });

    // Deklaratif düğmeler AJAX trafiği durunca kendini sıfırlar
    if (window.jQuery) {
        window.jQuery(document).ajaxStop(function () {
            document.querySelectorAll('[data-vnl-loading][' + BUSY + ']').forEach(function (btn) {
                vnlBtnReset(btn);
            });
        });
    }
}

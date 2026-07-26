/**
 * Tooltip init.
 *
 * Hem Bootstrap 5 attribute'u (data-bs-toggle="tooltip") hem de projede
 * hâlâ yaşayan BS4 kalıntısı (data-toggle="tooltip") desteklenir.
 * AJAX ile gelen içerik için window.vnlTooltips(scope) yeniden çağrılabilir
 * (vnl.js elementReady() köprüsü bunu kullanır).
 */

const SELECTOR = '[data-bs-toggle="tooltip"], [data-toggle="tooltip"]';

export function initTooltips() {
    window.vnlTooltips = vnlTooltips;
    vnlTooltips(document);
}

function vnlTooltips(scope) {
    if (!window.bootstrap) return;

    (scope || document).querySelectorAll(SELECTOR).forEach(function (el) {
        if (!window.bootstrap.Tooltip.getInstance(el)) {
            new window.bootstrap.Tooltip(el);
        }
    });
}

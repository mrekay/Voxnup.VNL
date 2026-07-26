/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Parola göster/gizle (.show-password)
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * vnl.js'ten temaya taşındı. Markup sözleşmesi aynıdır: `.show-password`
 * sarmalayıcısındaki input, `.pass-input-wrapper` içine alınır ve yanına
 * `.spass-btn` göz düğmesi eklenir (stil: theme/scss/components/_forms.scss).
 * İkonlar Bootstrap Icons'a geçti.
 */

function setup(root) {
    (root || document).querySelectorAll('.show-password input').forEach(function (input) {
        if (input.closest('.pass-input-wrapper')) return; // çifte kurulum yok

        const wrapper = document.createElement('div');
        wrapper.className = 'pass-input-wrapper';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const btn = document.createElement('span');
        btn.className = 'spass-btn';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
        wrapper.appendChild(btn);
    });
}

export function initPassword() {
    setup(document);
    window.vnlPasswordToggles = setup; // AJAX ile gelen formlar için

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.show-password .spass-btn');
        if (!btn) return;

        const icon = btn.querySelector('i');
        const input = btn.closest('.pass-input-wrapper')?.querySelector('input');
        if (!input) return;

        const hidden = input.getAttribute('type') === 'password';
        input.setAttribute('type', hidden ? 'text' : 'password');
        if (icon) {
            icon.classList.toggle('bi-eye', !hidden);
            icon.classList.toggle('bi-eye-slash', hidden);
        }
    });
}

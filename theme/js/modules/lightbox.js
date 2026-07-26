/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Tam ekran gorsel onizleme (vnlLightbox)
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * Görsel lightbox.
 *
 * window.vnlLightbox(src, caption) — tam ekran görsel önizleme.
 * ESC, backdrop tıklaması ve kapatma düğmesiyle kapanır.
 * Katman ilk kullanımda oluşturulur; markup'a önceden eklenmesi gerekmez.
 */

let overlay = null;

function build() {
    overlay = document.createElement('div');
    overlay.className = 'vnl-lightbox';
    overlay.innerHTML =
        '<button type="button" class="btn-close btn-close-white vnl-lightbox-close" aria-label="Kapat"></button>' +
        '<img alt="">' +
        '<div class="vnl-lightbox-caption"></div>';
    document.body.appendChild(overlay);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay || e.target.closest('.vnl-lightbox-close')) {
            close();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('show')) {
            close();
        }
    });
}

function open(src, caption) {
    if (!overlay) build();

    overlay.querySelector('img').src = src;
    overlay.querySelector('img').alt = caption || '';
    overlay.querySelector('.vnl-lightbox-caption').textContent = caption || '';
    overlay.classList.add('show');
}

function close() {
    if (overlay) {
        overlay.classList.remove('show');
    }
}

export function initLightbox() {
    window.vnlLightbox = open;

    // Eski sözleşme: .modal-image sınıflı görsele tıklayınca büyüt
    document.addEventListener('click', function (e) {
        const img = e.target.closest('img.modal-image');
        if (img) {
            open(img.getAttribute('src'), img.getAttribute('alt'));
        }
    });
}

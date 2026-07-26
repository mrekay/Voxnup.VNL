/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — VNL Theme 1.0
 *  Select altyapısı: SlimSelect köprüsü + AJAX doldurma
 *  Authors: Voxnup Authors
 * ============================================================
 */
/**
 * Global API: selectReload, vnlSelectInit, elementReady.
 * (VNL 3.0: select2Reload adı kaldırıldı — çağrılar selectReload'a çevrilir.)
 *
 * Sözleşme: `select.vnl-select[action]` seçicisi (eski `.select2` sınıfı da
 * tanınır), `prm_*` attribute'ları POST parametresi olur, `no-option` /
 * `multiple` yoksa value="0" "Seçiniz" seçeneği eklenir (ClearForm .val(0)
 * buna dayanır), `async` attribute'u senkron/asenkron davranışı korur.
 *
 * NOT: Bu modül bilinçli olarak jQuery kullanır (AJAX doldurma ve
 * ajaxStop yaşam döngüsü) — vendors.min.js her zaman önce yüklenir.
 */

const SELECT_SELECTOR = 'select.vnl-select, select.select2';

function vnlSelectInit(el) {
    if (typeof SlimSelect === 'undefined') return;

    if (el.slim) {
        el.slim.destroy();
        el.slim = null;
    }

    var settings = {
        showSearch: true,
        allowDeselect: false,
        searchPlaceholder: 'Ara…',
        searchText: 'Sonuç yok',
    };

    if (el.hasAttribute('tags')) {
        settings.addable = function (value) {
            return value;
        };
    }

    new SlimSelect({select: el, settings: settings});
}

function selectReload(input) {
    var $ = window.jQuery;

    $.each(input, function () {
        var parameters = {};
        var this_input = $(this);
        var el = this;
        var async_mode = true;

        $.each(this.attributes, function () {
            if (this.name.startsWith('prm_')) {
                parameters[(this.name).substring(4)] = this.value;
            }
        });

        if (el.slim) {
            el.slim.destroy();
            el.slim = null;
        }

        this_input.empty();
        if (!this_input.attr('no-option') && !this_input.attr('multiple')) {
            this_input.append(new Option('Seçiniz', '0', false, false)).trigger('change');
        }

        if (this_input.attr('action')) {
            async_mode = !this_input.attr('async');
            $.ajax({
                dataType: 'json',
                type: 'POST',
                async: async_mode,
                url: this_input.attr('action'),
                data: parameters,
                success: function (response) {
                    $.each(response, function (i, item) {
                        this_input.append(new Option(item.text, item.id, false, false));
                    });
                    vnlSelectInit(el);
                }
            });
        } else {
            vnlSelectInit(el);
        }
    });
}

/**
 * AJAX sonrası tazeleme: tooltip'ler + instance'sız select'ler.
 * ajaxStop her istekte buraya düşer; el.slim işareti çifte init'i engeller.
 */
function elementReady() {
    if (window.vnlTooltips) {
        window.vnlTooltips(document);
    }

    document.querySelectorAll(SELECT_SELECTOR).forEach(function (el) {
        if (!el.slim) {
            vnlSelectInit(el);
        }
    });
}

export function initSelect() {
    window.vnlSelectInit = vnlSelectInit;
    window.selectReload = selectReload;
    window.elementReady = elementReady;

    elementReady();

    var $ = window.jQuery;
    if ($) {
        selectReload($('select.vnl-select[action], select.select2[action]'));
        $(document).ajaxStop(function () {
            elementReady();
        });
    }
}

/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Standart Script Dosyası
 *  Global AJAX yanıt yönetimi, toast/modal yardımcıları ve
 *  ortak istemci fonksiyonları.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * CSRF jetonu (3.4.0) — <meta name="csrf-token"> (head-standard.php).
 *
 * Aynı kökene giden her jQuery isteğine X-CSRF-Token başlığı eklenir:
 * $.ajax, $.post, selectReload, deleteItemFromDB. Başlık istek ANINDA
 * okunur (ajaxSetup'taki sabit değer değil): çıkış/giriş jetonu döndürse
 * de sayfa yenilenmeden sonraki istek doğru jetonu taşır. Başka kökene
 * giden isteğe jeton hiç eklenmez.
 *
 * VNL Table fetch kullanır; o da aynı fonksiyonu okur (vnltable.js).
 */
window.vnlCsrfToken = function () {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
};

/** Sunucu yeni jeton döndürdüğünde (ör. yanıt başlığı) meta'yı günceller. */
window.vnlSetCsrfToken = function (token) {
    var m = document.querySelector('meta[name="csrf-token"]');
    if (m && token) m.setAttribute('content', token);
};

if (window.jQuery) {
    jQuery.ajaxPrefilter(function (options, originalOptions, jqXHR) {
        if (!options.crossDomain) {
            var token = vnlCsrfToken();
            if (token) jqXHR.setRequestHeader('X-CSRF-Token', token);
        }
    });
}

$(document).on('click', '#previewImg', function () {
    Swal.fire({
        title: 'İşlem Seçiniz',
        showDenyButton: true,
        showCancelButton: false,
        confirmButtonText: '<i class="bi bi-arrow-left-right"></i> Fotoğrafı Değiştir',
        denyButtonText: '<i class="bi bi-eye"></i> Görüntüle',
    }).then((result) => {
        if (result.isConfirmed) {
            $('#techizat_image_input').click();
        } else if (result.isDenied) {
            modalImageShowImageModal($('#previewImg').attr('src'), $('#previewImg').attr('alt'));
        }
    });
});


const Toast = Swal.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

function showModalMessage(message, mode = 1) {

    var icon = "error";
    switch (mode) {
        case true:
        case 1:
            icon = "error";
            break;
        case false:
        case 0:
            icon = "success";
            break;
        case 2:
            icon = "warning";
            break;
        case 3:
            icon = "info";
            break;

    }

    Swal.fire({
        icon: icon,
        html: message,
        confirmButtonText: 'Ok',
    })
}

function showMessage(message, is_err) {
    Toast.fire(
        {
            title: message,
            icon: is_err ? 'error' : 'success',
        }
    )
}

/**
 * Onaylı silme akışı: SweetAlert2 onayı + AJAX.
 *
 * /docs/theme-js bu fonksiyonu VNL 3.0'dan beri anlatıyordu ama hiçbir
 * dosyada tanımlı değildi; VNL 3.2'de yazıldı. Sözleşme dokümandaki
 * biçimin kendisidir: deleteItemFromDB({url, data, ...}).
 *
 *   deleteItemFromDB({
 *       url:   '/app/kayit/sil',
 *       data:  { id: 42 },
 *       table: 'kayitlar'          // silme sonrası VNL Table'ı tazele
 *   });
 *
 * Seçenekler:
 *   url            (zorunlu) isteğin gideceği action
 *   data           gönderilecek gövde
 *   method         varsayılan POST
 *   title/text     onay kutusunun başlığı ve metni
 *   confirmText    onay düğmesi yazısı
 *   cancelText     vazgeç düğmesi yazısı
 *   successMessage sunucu bir message döndürmezse basılacak metin
 *   table          VNL Table kimliği; verilirse başarıda reload() edilir
 *   reload         true ise sayfa yeniden yüklenir
 *   onSuccess      başarı geri çağrısı; yanıtı alır
 *   customErrMessage  true ise hata modalını vnl.js basmaz, çağıran basar
 *
 * Hata yolu bilinçli olarak BOŞ: 400/401/406/410/500 yanıtları zaten
 * yukarıdaki genel $(document).ajaxComplete kancasından geçip mesajı
 * basıyor. Burada ikinci bir modal açmak aynı hatayı iki kez gösterirdi.
 *
 * Swal'ın vaadini döndürür: çağıran zincirlemek isterse zincirleyebilir.
 */
function deleteItemFromDB(options) {
    options = options || {};

    if (!options.url) {
        console.error('[VNL] deleteItemFromDB: url zorunludur.');

        return;
    }

    return Swal.fire({
        icon: 'warning',
        title: options.title || 'Emin misiniz?',
        text: options.text || 'Kayıt silinecek. Bu işlem geri alınamaz.',
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'Sil',
        cancelButtonText: options.cancelText || 'Vazgeç',
    }).then(function (result) {
        if (!result.isConfirmed) return result;

        return $.ajax({
            url: options.url,
            type: options.method || 'POST',
            dataType: 'json',
            data: options.data || {},
            customErrMessage: options.customErrMessage === true
        }).done(function (response) {
            var message = (response && response.message)
                ? response.message
                : (options.successMessage || 'Kayıt silindi.');

            showMessage(message, false);

            if (typeof options.onSuccess === 'function') options.onSuccess(response);

            /* Listeyi tazelemek çağıranın işi; iki kısayol var. */
            if (options.table && typeof window.vnlTable === 'function' && vnlTable(options.table)) {
                vnlTable(options.table).reload();
            }

            if (options.reload) window.location.reload();
        });
    });
}

// Görsel önizleme: eski .image-modal (hiç çalışmayan .modal('show') çağrısı)
// yerine tema lightbox'ı. Çağrı imzası korunur.
$(document).on('click', '.modal-image', function () {
    modalImageShowImageModal($(this).attr('src'), $(this).attr('alt'));
});

function modalImageShowImageModal($src, $alt) {
    if (window.vnlLightbox) vnlLightbox($src, $alt);
}


/**
 * Mobil tarayıcı tespiti. VNL 2.x'te PHP tarafında checkMobileBrowser() ile
 * üretilip script-standard.php'ye gömülüyordu; VNL 3.0'da istemciye taşındı.
 *
 * Sıra: User-Agent Client Hints (Chromium — UA ayrıştırmadan kesin bilgi) →
 * UA deseni → çok dokunmalı Macintosh (iPadOS 13+ masaüstü UA'sı gönderir).
 * Eski PHP'deki 2010 dönemi cihaz öneki tablosu bilinçli olarak taşınmadı.
 */
function isMobile() {
    if (navigator.userAgentData && typeof navigator.userAgentData.mobile === 'boolean') {
        return navigator.userAgentData.mobile;
    }

    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Silk/i.test(navigator.userAgent)) {
        return true;
    }

    return navigator.maxTouchPoints > 1 && /Macintosh/.test(navigator.userAgent);
}

$(document).on('input', '.length-counter', function () {
    var len = $(this).val().length;
    var maxlen = $(this).attr('maxlength');
    var targetObject = $($(this).attr('length-target-class'));

    if (!targetObject.is(":visible")) targetObject.fadeIn();

    $($(this).attr('length-target-class')).html(`${len}/${maxlen}`);

    if (len <= 0) targetObject.fadeOut();
})


/**
 * Global AJAX hata kancası (3.4.0).
 *
 * Kurallar:
 *  - Gövde JSON değilse (nginx'in HTML 502'si gibi) konsol hatası yok;
 *    genel bir metin gösterilir.
 *  - Sunucu metni HER ZAMAN düz metin olarak basılır (HTML kaçışlı):
 *    showModalMessage HTML çizer, mesajda kullanıcı girdisi olabilir.
 *  - settings.customErrMessage === true ise mesajı çağıran gösterir.
 *
 *   406 uyarı · 410 bilgi · 400/403/404/405/419/422/429 hata
 *   401 oturum yok: girişliyken sayfa yenilenir, girişsizken /login'e gidilir
 *   500+ beklenmeyen hata (varsa başvuru kodu ile)
 */
var VNL_GENERIC_ERROR = 'Beklenmeyen bir hata oluştu.';

function vnlParseJson(text) {
    if (!text || typeof text !== 'string') return null;
    try {
        var parsed = JSON.parse(text);
        return (parsed && typeof parsed === 'object') ? parsed : null;
    } catch (e) {
        return null;
    }
}

function vnlEscape(text) {
    return $('<div>').text(text == null ? '' : String(text)).html();
}

/**
 * Bir hata yanıtını kullanıcıya gösterir. jQuery kancası ve VNL Table
 * (fetch) aynı yolu kullanır. json: çözülmüş gövde ya da null.
 */
window.vnlHandleAjaxError = function (status, json, settings) {
    settings = settings || {};
    json = json || {};

    var custom = settings.customErrMessage === true;
    var message = (json && json.message) ? String(json.message) : VNL_GENERIC_ERROR;

    if (status >= 400 && typeof spinnerHide === 'function') spinnerHide();

    if (status == 406) {
        if (!custom) showModalMessage(vnlEscape(message), 2);
    } else if (status == 410) {
        if (!custom) showModalMessage(vnlEscape(message), 3);
    } else if (status == 401) {
        // 401 = oturum yok/süresi doldu.
        if (!custom) showModalMessage(vnlEscape(message), true);
        setTimeout(function () {
            if (typeof VNL_USER_ID !== 'undefined' && VNL_USER_ID > 0) {
                window.location.reload();
            } else if (!/\/login(\/|$)/.test(window.location.pathname)) {
                window.location.href = '/login';
            }
        }, 1500);
    } else if ([400, 403, 404, 405, 419, 422, 429].indexOf(status) !== -1) {
        if (!custom) showModalMessage(vnlEscape(message), true);
    } else if (status >= 500) {
        if (json && json.error) {
            console.error('[VNL] ' + json.error.type + ': ' + json.error.text +
                ' (' + json.error.file + ':' + json.error.line + ')', json.error.trace);
        }
        if (json && json.ref) message += ' (ref: ' + json.ref + ')';
        if (!custom) showModalMessage(vnlEscape(message), true);
    }
};

$(document).ajaxComplete(function (event, request, settings) {
    if (!request || request.status < 400) return;

    var json = request.responseJSON || vnlParseJson(request.responseText);
    vnlHandleAjaxError(request.status, json, settings);
});

/**
 * Çıkış (3.4.0): POST /app/login/logout — CSRF başlığı prefilter'dan gelir.
 * data-vnl-logout taşıyan her öğe bunu çağırır (üst çubuk, /logout sayfası).
 */
window.vnlLogout = function () {
    return $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/login/logout',
        customErrMessage: true
    }).always(function (response) {
        var url = (response && response.data && response.data.url) ? response.data.url : '/login';
        window.location.href = url;
    });
};

$(document).on('click', '[data-vnl-logout]', function (e) {
    e.preventDefault();
    vnlLogout();
});

function getOS() {
    var OSName = '';
    if (navigator.appVersion.indexOf("Win") != -1) OSName = "Windows";
    else if (navigator.appVersion.indexOf("Mac") != -1) OSName = "MacOS";
    else if (navigator.appVersion.indexOf("X11") != -1) OSName = "UNIX";
    else if (navigator.appVersion.indexOf("Linux") != -1) OSName = "Linux";
    return OSName;
}

function convertFileSize(size) {
    if (size < 1024) return size + ' Bytes';
    else if (size >= 1024 && size < 1048576) return (size / 1024).toFixed(2) + ' KB';
    else if (size >= 1048576 && size < 1073741824) return (size / 1048576).toFixed(2) + ' MB';
    else return (size / 1073741824).toFixed(2) + ' GB';
}


function clearForm($form) {
    $form.find("input[type!='checkbox'], textarea").not('.no-clear').val("");
    // VNL Date: val("") yalnız metni siler, seçili tarih örnekte kalır ve
    // takvim yeniden açılınca eski günü gösterirdi. Örnek de sıfırlanır.
    $form.find('input[data-vnl-date]').not('.no-clear').each(function () {
        if (this._flatpickr) this._flatpickr.clear();
    });
    $form.find("select").not('.no-clear').each(function () {
        // SlimSelect'li select'lerde UI'ın da sıfırlanması için instance API'si
        if (this.slim) {
            this.slim.setSelected('0');
        }
        $(this).val(0).trigger('change');
    });
    $form.find("input[type='checkbox']").not('.no-clear').prop('checked', false);
}

/**
 * Zorunlu alanları doğrular, geçersizleri .is-invalid ile işaretler ve
 * formun geçerli olup olmadığını döner.
 *
 * Ad VNL 3.0'da checkValidity → validateForm oldu: tarayıcının yerleşik
 * form.checkValidity() metodu farklı bir şey yapar (HTML5 constraint
 * validation), aynı ad iki farklı davranışı karıştırıyordu.
 */
function validateForm($form) {
    $is_valid = true;
    $form.find('input').each(function () {
        if ($(this).attr('required')) {
            if ($(this).val() == '') {
                $(this).addClass('is-invalid');
                $is_valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        }
    });

    $form.find('select').each(function () {
        if ($(this).attr('required')) {
            if ($(this).val() == '' || $(this).val() == '0') {
                $(this).addClass('is-invalid');
                $is_valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        }
    });

    $form.find('textarea').each(function () {
        if ($(this).attr('required')) {
            if ($(this).val() == '') {
                $(this).addClass('is-invalid');
                $is_valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        }
    });

    return $is_valid;
}

/**
 * Metni panoya kopyalar.
 *
 * Önce asenkron Clipboard API'si denenir; güvenli bağlam (HTTPS/localhost)
 * yoksa ekran dışı textarea + execCommand'e düşülür. Bildirim yalnızca
 * kopyalama gerçekten başarılıysa gösterilir.
 *
 * Not: showMessage parametresi aynı adlı global fonksiyonu gölgeler, bu
 * yüzden bildirim için showModalMessage kullanılır.
 */
function copyToClipboard(text, showMessage = true) {

    function legacyCopy() {
        // Ekran dışına konumlanır: eski sürüm görünür textarea ekleyip
        // select() ile sayfayı oraya kaydırıyordu.
        var $temp = $('<textarea>')
            .val(text)
            .attr('readonly', true)
            .css({position: 'fixed', top: '-9999px', left: '-9999px', opacity: 0});

        // Modal açıkken body'ye eklenen eleman odak tuzağının dışında kalır.
        var $host = $('.modal.show').first();
        ($host.length ? $host : $('body')).append($temp);

        $temp[0].focus();
        $temp[0].setSelectionRange(0, String(text).length);

        var ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }

        $temp.remove();
        return ok;
    }

    function done(ok) {
        if (!showMessage) return;
        showModalMessage(ok ? 'Panoya kopyalandı.' : 'Kopyalanamadı, metni elle seçin.', !ok);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(
            function () { done(true); },
            function () { done(legacyCopy()); }
        );
        return;
    }

    done(legacyCopy());
}

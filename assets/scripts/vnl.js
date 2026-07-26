/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 3.0.0 — Standart Script Dosyası
 *  Global AJAX yanıt yönetimi, toast/modal yardımcıları ve
 *  ortak istemci fonksiyonları.
 *  Authors: Voxnup Authors
 * ============================================================
 */
/*
 * Select altyapısı (vnlSelectInit / selectReload / elementReady + ajaxStop
 * tazelemesi), tooltip tıklama davranışı ve parola göster/gizle VNL Theme'e
 * taşındı: theme/js/modules/select.js, tooltips.js, password.js (derlenmiş
 * hali assets/vnl/js/vnl.min.js). VNL 3.0: select2Reload adı kaldırıldı.
 */

$(document).ajaxComplete(function (event, request, settings) {
    if (request.status == 406) {
        showModalMessage(request.responseJSON.message, 2);
        return;
    }else if (request.status == 410) {
        showModalMessage(request.responseJSON.message, 3);
        return;
    }else if (request.status == 400) {
        spinnerHide();
        if (!settings.customErrMessage) showModalMessage(request.responseJSON.message, true);
    } else if (request.status == 401) {
        spinnerHide();
        // 401 = oturum yok/expired. Yalnızca GİRİŞ YAPMIŞ kullanıcıda anlamlıdır (oturumu düşmüş →
        // uyar + yeniden yükle). Public sayfalarda ($page_config['public'] veya login_except_pages)
        // login gerektiren arka-plan AJAX'ları da 401 döner; koşulsuz reload sonsuz yenileme yaratır.
        if (typeof VNL_USER_ID !== 'undefined' && VNL_USER_ID > 0) {
            if (!settings.customErrMessage) showModalMessage(request.responseJSON.message, true);
            setTimeout(function () {
                window.location.reload();
            }, 1500);
        }
    } else if (request.status == 500) {
        // Sunucu tarafı fatal. Spinner'ı mutlaka kapat; aksi halde arayüz sonsuza kadar dönüyor.
        spinnerHide();

        var vnlErr = request.responseJSON;
        var vnlMsg = (vnlErr && vnlErr.message) ? vnlErr.message : 'Sunucu tarafında beklenmeyen bir hata oluştu.';

        if (vnlErr && vnlErr.error) {
            console.error('[VNL] ' + vnlErr.error.type + ': ' + vnlErr.error.text +
                ' (' + vnlErr.error.file + ':' + vnlErr.error.line + ')', vnlErr.error.trace);
        }

        // showModalMessage içeriği html olarak basar; hata metnini kaçışlayarak ver.
        if (!settings.customErrMessage) showModalMessage($('<div>').text(vnlMsg).html(), true);
    }
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


function ClearForm($form) {
    $form.find("input[type!='checkbox'], textarea").not('.no-clear').val("");
    $form.find("select").not('.no-clear').each(function () {
        // SlimSelect'li select'lerde UI'ın da sıfırlanması için instance API'si
        if (this.slim) {
            this.slim.setSelected('0');
        }
        $(this).val(0).trigger('change');
    });
    $form.find("input[type='checkbox']").not('.no-clear').prop('checked', false);

}

function checkValidity($form) {
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

function TopMessageShow($isErr, $message) {
    if ($message == 'undefined') return;
    showModalMessage($message, $isErr);
}

function ErrorMessage(msg) {
    TopMessageShow(true, msg);
}

/* spinnerShow()/spinnerHide() VNL Theme'e taşındı (theme/js/modules/spinner.js);
   showSpinner()/hideSpinner() takma adlarıyla birlikte global olarak oradan gelir. */

function copyToClipboard(text, showMessage = true) {
    var $temp = $("<textarea>");
    //geçici çözüm
    if ($('.modal.show').length > 0) {
        $('.modal.show').first().append($temp);
    } else {
        $("body").append($temp);
    }
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();

    if (showMessage)
        showModalMessage(LBL_COPIED, false);
}

function openLink(url, target) {
    target = target || '_self';

    if (isMobile()) {
        target = '_self';
    }
    window.open(url, target);
}

$(document).on('click', 'a', function (e) {
    if (typeof $(this).attr('download') == 'undefined') {

        openLink($(this).attr('href'), $(this).attr('target'));
        e.stopPropagation();
        e.preventDefault();
        return false;
    }
});


$(document).ready(function () {
    // openLink özelliğine sahip olan herhangi bir öğeye tıklandığında
    $('[openLink]').click(function () {
        // Öğenin openLink değerini al
        var link = $(this).attr('openLink');
        // Bağlantıyı aç
        window.open(link, "_self");
    });
});

/* silme için onay alma 20.02.2024 ertugrul*/
function deleteItemFromDB(opt) {

    var opt_default = {
        async:false,
        url: "",
        data: {},
        dataType: "JSON",
        type: "POST",
        success: function (response) {
            showModalMessage(response.message, false);
        },
        error: function (response) {
            showModalMessage(response.message, true);
        },
        complete: function(){},
    }

    // opt ve opt_default'ı birleştirme
    opt = Object.assign({}, opt_default, opt);

    if (Object.keys(opt).length <= 0 || opt.url.length <= 0 || Object.keys(opt.data).length <= 0) {
        showModalMessage("İç JS Hatası:  deleteItemFromDB için geçerli değerler gelmedi", true);
        return;
    }

    Swal.fire({
        title: "Emin misiniz?",
        text: "Kayıt silinecektir, bu işlem geri alınamaz.",
        icon: "warning",
        buttons: true,
        dangerMode: true,
        showCancelButton: true,
        confirmButtonText: "Sil",
        CancelButtonText: `İptal`
    })
        .then((willDelete) => {
            if (willDelete.isConfirmed) {
                $.ajax({
                    async:opt.async,
                    dataType: opt.dataType,
                    type: opt.type,
                    url: opt.url,
                    data: opt.data,
                    success: opt.success,
                    error: opt.error,
                });
                opt.complete();

            }
        });
}
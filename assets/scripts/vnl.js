/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) 2.4.0 — Standart Script Dosyası
 *  Global AJAX yanıt yönetimi, toast/modal yardımcıları ve
 *  ortak istemci fonksiyonları.
 *  Authors: Voxnup Authors
 * ============================================================
 */
var expo_token = '';

$(document).ready(function () {
    elementReady();
    $('input.dt-filter-td:not(.no-clear)').val('');
    select2Reload($('select.select2[action]'));
    // $('.fixed-panel-title').fixedPanelTitle();

    // DataTables vendor'u VNL Theme 1.0 ile söküldü; datatable.js yüklü olmayan sayfalarda sessizce atla.
    if (typeof InitalizeDatatableFilters === 'function') InitalizeDatatableFilters();
});

function replaceAll(text, old_text, new_text) {
    return text.split(old_text).join(new_text);
}

function htmlspecialchars(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return text.replace(/[&<>"']/g, function (m) {
        return map[m];
    });
}

function url_encode(text) {
    var map = {
        '/': '&slash;',
        '\\': '&rslash;',
        '=': '&equal;',
    };

    return text.replace(/[/\\=]/g, function (m) {
        return map[m];
    });
}

// 2021/07/08 ***************
// ÖLÜ BÖLGE — moment/daterangepicker vendor'u VNL Theme 1.0 ile söküldü (VNL 3.0 madde 3'te kaldırılacak).
// Initializer top-level moment() çağırıyor; guard'sız bırakılırsa moment yokken ReferenceError
// fırlar ve dosyanın bu satırdan sonraki tamamı (spinner, ajaxComplete, 401 yönetimi) ölür.
var dateRangePickerSettings = (typeof moment === 'undefined') ? null : {
    autoUpdateInput: false,
    ranges: {
        'Bugün': [moment(), moment()],
        'Dün': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Son 7 gün': [moment().subtract(6, 'days'), moment()],
        'Son 30 gün': [moment().subtract(29, 'days'), moment()],
        'Bu Ay': [moment().startOf('month'), moment().endOf('month')],
        'Geçen Ay': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    },
    "locale": {
        "format": "DD/MM/YYYY",
        "separator": " - ",
        "applyLabel": "Uygula",
        "cancelLabel": "Vazgeç",
        "fromLabel": "Dan",
        "toLabel": "a",
        "customRangeLabel": "Gün Aralığı Seç",
        "daysOfWeek": ["Pzt", "Sal", "Çar", "Per", "Cum", "Cmt", "Paz"],
        "monthNames": ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"],
        "firstDay": 1
    }
};

var dateRangePickerSettings2 = (typeof moment === 'undefined') ? null : {
    autoUpdateInput: false,
    "locale": {
        "format": "DD/MM/YYYY",
        "separator": " - ",
        "applyLabel": "Uygula",
        "cancelLabel": "Vazgeç",
        "fromLabel": "Dan",
        "toLabel": "a",
        "customRangeLabel": "Gün Aralığı Seç",
        "daysOfWeek": ["Pzt", "Sal", "Çar", "Per", "Cum", "Cmt", "Paz"],
        "monthNames": ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"],
        "firstDay": 1
    }
};

/**
 * Select altyapısı: select2 söküldü, SlimSelect'e köprü.
 * Sözleşme VNL 2.x ile aynıdır: `select.select2[action]` seçicisi, `prm_*`
 * attribute'ları POST parametresi olur, `no-option`/`multiple` yoksa
 * value="0" "Seçiniz" seçeneği eklenir (ClearForm .val(0) buna dayanır),
 * `async` attribute'u senkron/asenkron davranışı korur. Fonksiyon adı
 * dış projelerdeki çağrılar için değişmedi.
 */
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

function select2Reload(input) {

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

        this_input.empty()
        if (!this_input.attr('no-option') && !this_input.attr('multiple')) {
            var newOption = new Option('Seçiniz', '0', false, false);
            this_input.append(newOption).trigger('change');
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

// Eski adın yanına nötr takma ad
var selectReload = select2Reload;


function elementReady() {
    if (window.vnlTooltips) {
        vnlTooltips(document);
    }

    // ajaxStop her AJAX sonrasında buraya düşer; instance'lı select'e
    // yeniden init UI çoğaltır — el.slim işareti çifte init'i engeller.
    $('select.select2').each(function () {
        if (!this.slim) {
            vnlSelectInit(this);
        }
    });
}

$(document).on('click', '[data-toggle="tooltip"]', function (e) {
    e.stopPropagation();
});

$(document).ajaxStop(function () {
    elementReady();
});


$(document).ajaxComplete(function (event, request, settings) {
    const default_settings={
        customErrMessage: true
    };
    if (request.status == 200) {
        // datatableExcel*/datatableSelect* fonksiyonları ölü datatable.js'te tanımlı; yüklü değilse atla.
        if (typeof (request.responseJSON) != 'undefined' && typeof (request.responseJSON.excel_result) != 'undefined') {
            if (typeof datatableExcelSuccess !== 'function') { /* DataTables söküldü */ }
            else if (request.responseJSON.excel_result === 'ok' && !(request.responseJSON.excel_url === '')) {
                datatableExcelSuccess(request.responseJSON);
            } else {
                datatableExcelError((request.responseJSON.excel_message ? request.responseJSON.excel_message : 'Excel Response Error'));
            }
        } else if (typeof (request.responseJSON) != 'undefined' && typeof (request.responseJSON.select_result) != 'undefined') {
            if (typeof datatableSelectAjaxResponse === 'function')
                datatableSelectAjaxResponse(request.responseJSON.table_id, request.responseJSON.select_result, request.responseJSON.message, request.responseJSON);
            //datatableSelectAjaxResponseError
        }
    }else if (request.status == 406) {
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


function isMobileScreen() {
    return $(window).width() < 992;
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

function preloaderShow() {
    $('.preloader img').show();
    $('.preloader').css('height', '100%');

}

function preloaderHide() {

    $('.preloader .fa-cog').hide().parent().removeClass('rotate');
    $('.preloader .fa-tools').fadeIn();

    setTimeout(function () {
        $('.preloader img').hide();
        $('.preloader').css('height', '0');
        $('.preloader .fa-tools').hide();

        setTimeout(function () {
            $('.preloader').hide();
        }, 200);
    }, 250);


}

function spinnerShow(text = "") {
    $("#loadingSpinner").fadeIn();
    $('#loadingSpinner .spinner-text').html(text);
}

function spinnerHide() {
    $("#loadingSpinner").fadeOut();
}

function AjaxError() {
    TopMessageShow(true, 'Bir hata oluştu, lütfen daha sonra tekrar deneyin.');
    spinnerHide()
}


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

$(document).on("touchstart", '*', function () {
});

function searchOnFocusOut() {
    setTimeout(function () {
        $('#searchStudent').val('');
        $('#searchStudent').closest('.search-topbar').find('.AutoCompleteContent').html('').hide();
    }, 500);
}

function loadingButton($showText, $item) {
    $showText = $showText || 0;
    $item = $item || $("body");
    $item.find('.loadingbutton').each(function () {
        var istyle = $(this).find('i').attr('style');
        var fa4x = '';
        if ($(this).find('i').hasClass('fa-4x')) {
            fa4x = ' fa-4x'
        }
        $text = '';
        if ($showText)
            $text = '<span>Lütfen Bekleyin</span>';
        $(this).html('<i style="' + istyle + '" class="fa fa-spinner fa-spin' + fa4x + '"></i>' + $text).attr('disabled', 'disabled');
    });
}

function loadingButtonDisable($item) {
    $item = $item || $("body");
    $item.find('.loadingbutton').each(function () {
        var istyle = $(this).find('i').attr('style');
        var fa4x = '';
        if ($(this).find('i').hasClass('fa-4x')) {
            fa4x = ' fa-4x'
        }
        $(this).html('<i style="' + istyle + '" class="' + $(this).attr('data-icon') + fa4x + '"></i><span>' + $(this).attr('data-text') + '</span>').removeAttr('disabled');
    });
}

function IsNumeric(number) {
    if (number === parseInt(number, 10))
        return true;
    else
        return false;
}

function clearFileInput(fileElement) {
    //klon oluşturarak file inputu sıfırlar.
    fileElement.val('');
    var FileElementClone = fileElement.clone();
    fileElement.after(FileElementClone);
    fileElement.remove();
    fileElement = FileElementClone;
    fileElement.change(function () {
        var files = $(this)[0].files;
        var filenames = "";
        $.each(files, function (key, val) {
            filenames += val.name;
            if ((key + 1) < files.length)
                filenames += ",";
        });
        $(this).parent().find('label[for="' + $(this).attr('id') + '"]').html(filenames);
    });
    fileElement.change();
    fileElement.parent().find('label').text('Dosya Seçiniz...');
    return fileElement;
}

// $(document).on('change', '.custom-file-input');


function sleep(milliseconds) {
    var start = new Date().getTime();
    for (var i = 0; i < 1e7; i++) {
        if ((new Date().getTime() - start) > milliseconds) {
            break;
        }
    }
}


$(document).ready(function () {

    $('.show-password input').after('<div class="pass-input-wrapper"></div>');
    $('.show-password input').appendTo($('.show-password .pass-input-wrapper'));
    $('.show-password .pass-input-wrapper').append('<span class="spass-btn"><i class="far fa-eye"></i></span>');
    $(document).on('click', '.show-password .spass-btn', function (event) {

        if ($(this).find('i').hasClass('fa-eye')) {
            $(this).find('i').removeClass('fa-eye');
            $(this).find('i').addClass('fa-eye-slash');
        } else {
            $(this).find('i').addClass('fa-eye');
            $(this).find('i').removeClass('fa-eye-slash');
        }


        $(this).prev().attr('type', function (index, attr) {
            return attr == 'password' ? 'text' : 'password';
        });
    });
});

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
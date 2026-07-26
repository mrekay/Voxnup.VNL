<script>
    $SITE_URL = '<?php echo SITE_URL; ?>';
    // vnl.js 401 yanıtında yalnızca oturumu düşmüş kullanıcıyı yeniden yükler; girişsizde 0 kalır.
    var VNL_USER_ID = <?= (int)$CurrentUser->id ?>;

    // JS'e aktarılan dil sözlüğü: yalnız istemcinin kullandığı anahtarlar
    // (Language::all() bilinçli kullanılmıyor — tüm sözlük her sayfaya gömülmesin).
    const VNL_LANG = <?= json_encode([
        'BTN_LOADING' => lang('BTN_LOADING'),
        'LBL_COPIED'  => lang('LBL_COPIED'),
        'LBL_CLOSE'   => lang('LBL_CLOSE'),
        'ERR_GENERAL' => lang('ERR_GENERAL'),
    ], JSON_UNESCAPED_UNICODE) ?>;

    function vnlLang(key) {
        return Object.prototype.hasOwnProperty.call(VNL_LANG, key) ? VNL_LANG[key] : key;
    }

    // Geriye uyumlu global değişkenler (vnl.js ve proje script'leri kullanıyor)
    var BTN_LOADING = VNL_LANG.BTN_LOADING;
    var LBL_COPIED = VNL_LANG.LBL_COPIED;
</script>

<!-- VNL Theme: vendor paketi (jquery + bootstrap + sweetalert2 + slim-select),
     tema davranışları ve VNL çekirdek script'i. Script'ler head'de kalır:
     view/modal dosyaları body içinde satır arası $(...) çalıştırıyor. -->
<script src="<?= $assets_address ?>/vnl/js/vendors.min.js"></script>
<script src="<?= $assets_address ?>/vnl/js/vnl.min.js"></script>
<script src="<?= $assets_address ?>/scripts/vnl.js"></script>

<script>

    $(document).on('click', '#previewImg', function () {
        Swal.fire({
            title: 'İşlem Seçiniz',
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: '<i class="fa-solid fa-arrow-right-arrow-left"></i> Fotoğrafı Değiştir',
            denyButtonText: '<i class="fa-solid fa-eye"></i> Görüntüle',
        }).then((result) => {
            if (result.isConfirmed) {
                $('#techizat_image_input').click();
            } else if (result.isDenied) {
                modalImageShowImageModal($('#previewImg').attr('src'), $('#previewImg').attr('alt'));
            }
        });
    });

    function previewFile(input) {
        var file = $("input[type=file]").get(0).files[0];

        if (file) {
            var reader = new FileReader();

            reader.onload = function () {
                $("#previewImg").attr("src", reader.result);
            }

            reader.readAsDataURL(file);
        }
    }


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

    // Görsel önizleme: eski .image-modal (hiç çalışmayan .modal('show') çağrısı)
    // yerine tema lightbox'ı. Çağrı imzası korunur.
    $(document).on('click', '.modal-image', function () {
        modalImageShowImageModal($(this).attr('src'), $(this).attr('alt'));
    });

    function modalImageShowImageModal($src, $alt) {
        if (window.vnlLightbox) vnlLightbox($src, $alt);
    }


    function isMobile() {
        return <?=checkMobileBrowser(); ?>;
    }

    $(document).on('input', '.length-counter', function () {
        var len = $(this).val().length;
        var maxlen = $(this).attr('maxlength');
        var targetObject = $($(this).attr('length-target-class'));

        if (!targetObject.is(":visible")) targetObject.fadeIn();

        $($(this).attr('length-target-class')).html(`${len}/${maxlen}`);

        if (len <= 0) targetObject.fadeOut();
    })


    function showSpinner(){
        $('#loadingSpinner').fadeIn();
    }

    function hideSpinner(){
        $('#loadingSpinner').fadeOut();
    }



</script>

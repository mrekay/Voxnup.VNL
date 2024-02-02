<script>
    $SITE_URL = '<?php echo SITE_URL; ?>';
    var BTN_LOADING = 'Please Wait';
    var LBL_COPIED = 'Copied to clipboard';

    var DATATABLES_PROCESSING = 'Processing...';
    var DATATABLES_LENGTHMENU = 'Show _MENU_ Entries per Page';
    var DATATABLES_ZERORECORDS = 'No Matching Records Found';
    var DATATABLES_INFO = 'Showing _START_ to _END_ of _TOTAL_ Entries';
    var DATATABLES_INFOEMPTY = 'No Entries';
    var DATATABLES_INFOFILTERED = '(filtered from _MAX_ total entries)';
    var DATATABLES_INFOPOSTFIX = '';
    var DATATABLES_SEARCH = 'Search:';
    var DATATABLES_URL = '';
    var DATATABLES_PAGINATE_FIRST = 'First';
    var DATATABLES_PAGINATE_PREVIOUS = 'Previous';
    var DATATABLES_PAGINATE_NEXT = 'Next';
    var DATATABLES_PAGINATE_LAST = 'Last';

    var DATATABLES_BTN_SEARCH = ' Filters';
    var DATATABLES_BTN_CLEAR = ' Clear Filters';
    var DATATABLES_BTN_EXCEL_OUTPUT = ' Excel Export';
    var DATATABLES_LBL_PLEASE_WRITE_FILTER = ' Please Enter Filter';

</script>


<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="<?= $theme_path ?>plugins/global/plugins.bundle.js"></script>
<script src="<?= $theme_path ?>js/scripts.bundle.js"></script>
<script src="<?= $theme_path ?>plugins/custom/draggable/draggable.bundle.js"></script>
<!--end::Global Javascript Bundle-->
<!--begin::Vendors Javascript(used for this page only)-->
<script src="<?= $theme_path ?>plugins/custom/datatables/datatables.bundle.js"></script>

<script src="<?= $assets_address ?>/scripts/_standard.js"></script>

<script>

    $(document).on('click', '#previewImg', function () {
        Swal.fire({
            title: 'İşlem Seçiniz',
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: '<i class="fas fa-exchange-alt"></i> Fotoğrafı Değiştir',
            denyButtonText: `<i class="fas fa-eye"></i> Görüntüle`,
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


    const Toast = swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        onOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    function showModalMessage(message, is_err) {
        Swal.fire({
            icon: is_err ? 'error' : 'success',
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

    $(document).on('click', '.modal-image', function () {
        modalImageShowImageModal($(this).attr('src'), $(this).attr('alt'));
    });

    function modalImageShowImageModal($src, $alt) {
        $('.image-modal').modal('show');
        $('.image-modal-content').attr('src', $src);
        $('.image-caption').html($alt);
    }

    $('.image-modal-close').click(function () {
        $('.image-modal').modal('hide');
    });


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

    $(document).ready(function () {
        function reloadProjectSideBar(){
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: 'app/project/getProjectList',
                async: false,
                data: {
                    dID: $(this).attr('projectID'),
                },
                success: function (response) {
                    $('.projects-list-menu-item').remove();
                    $('#kt_app_sidebar_menu').append(
                        $('<div class="menu-item pt-5 projects-list-menu-item">').append(
                            $('<div class="menu-content">').append(
                                $('<div class="menu-heading fw-bold text-uppercase fs-7">').text("Projects")
                            )
                        )
                    );
                    if (response.data.length > 0){
                        $.each(response.data,function (k,v){
                            $('#kt_app_sidebar_menu').append(
                                $('<div class="menu-item projects-list-menu-item">').append(
                                    $(`<a target="_self" href="${v.url}" class="menu-link project-list-move-to-up">`).append(
                                        $('<div class="menu-icon">').append($(`<i class="fas fa-${v.icon}">`)),
                                        $('<span class="menu-title">').append(v.text)
                                    )
                                )
                            );
                        })
                    }else{
                        $('#kt_app_sidebar_menu').append(
                            $('<div class="menu-item projects-list-menu-item">').append(
                                $(`<a target="_self" href="project-manager?action=create-project" class="menu-link">`).append(
                                    $('<div class="menu-icon">').append($(`<i class="fas fa-plus">`)),
                                    $('<span class="menu-title">').append("Create Project")
                                )
                            )
                        );
                    }
                }
            });
        }
        setTimeout(reloadProjectSideBar,300);
        setInterval(reloadProjectSideBar, 30000);
    });

    function showSpinner(){
        $('#loadingSpinner').fadeIn();
    }

    function hideSpinner(){
        $('#loadingSpinner').fadeOut();
    }



</script>

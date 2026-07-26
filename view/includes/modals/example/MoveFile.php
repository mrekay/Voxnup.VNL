<!--begin::Modal - Move file-->
<div class="modal fade" id="kt_modal_move_to_folder" tabindex="-1" aria-hidden="true">
    <input type="hidden" class="mvf-file-id">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <form class="form" action="#" id="kt_modal_move_to_folder_form">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Move to folder</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body pt-10 pb-15 px-lg-17">
                    <!--begin::Input group-->
                    <div class="form-group fv-row mf-dialog-folder">

                    </div>
                    <!--end::Input group-->
                    <!--begin::Action buttons-->
                    <div class="d-flex flex-center mt-12">
                        <!--begin::Button-->
                        <button type="button" class="btn btn-primary" id="kt_modal_move_to_folder_submit">
                            <span class="indicator-label">Move File</span>
                            <span class="indicator-progress">Please wait...
																<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                        <!--end::Button-->
                    </div>
                    <!--begin::Action buttons-->
                </div>
                <!--end::Modal body-->
            </form>
            <!--end::Form-->
        </div>
    </div>
</div>
<!--end::Modal - Move file-->
<script>

    $('#kt_modal_move_to_folder').on('click', '#kt_modal_move_to_folder_submit', function () {
        $('#kt_modal_move_to_folder_submit .indicator-label').hide();
        $('#kt_modal_move_to_folder_submit .indicator-progress').fadeIn();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: 'app/file_manager/moveFile',
            data: {
                pKey: $('#project-key').val(),
                fKey: $('.mf-dialog-folder input[name="move_to_folder"]:checked').val(),
                fileID: $('#kt_modal_move_to_folder .mvf-file-id').val(),
                revID: $('#revision-id').val(),
            },
            success: function (response) {
                $('#kt_modal_move_to_folder').modal('hide');
                showModalMessage(response.message, false);
                $('#kt_modal_move_to_folder_submit .indicator-label').show();
                $('#kt_modal_move_to_folder_submit .indicator-progress').hide();
                reloadFiles();
            }, error: function (err) {
                $('#kt_modal_move_to_folder').modal('hide');
            }
        });

    });

    function loadMoveFileFolderDatas(fileID, fldID = null) {

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: 'app/file_manager/getFolderData',
            data: {
                pKey: $('#project-key').val(),
                fKey: fldID ?? $('#folder-key').val(),
                revID: $('#revision-id').val(),
                folders: 1,
            },
            success: function (response) {
                $('#kt_modal_move_to_folder').modal('show');
                $('#kt_modal_move_to_folder .mvf-file-id').val(fileID);
                $('#kt_modal_move_to_folder_submit .indicator-label').show();
                $('#kt_modal_move_to_folder_submit .indicator-progress').hide();
                $('.mf-dialog-folder').html("");

                $('#kt_modal_move_to_folder_submit').addClass("disabled");

                var upper_folder_id = response.data.folder_info.upper_folder_key ?? "";
                var i = 0;
                $.each(response.data.files, function (k, v) {
                    $('.mf-dialog-folder').append($('<div class="d-flex">').append(
                        $('<div class="form-check form-check-custom form-check-solid">').append(
                            $(`<input class="form-check-input me-3" type="radio" name="move_to_folder" value="${v.UUID}"/>`).attr('checked', i == 0),
                            $(`<label class="form-check-label" style="cursor:pointer" onclick="loadMoveFileFolderDatas('${fileID}','${v.UUID}')">`).append(
                                $('<div class="fw-bold">').append(
                                    $('<i class="fa-solid fa-folder fs-2 text-primary me-2">').append(
                                        $(''),
                                        $('')
                                    ),
                                    v.ff_name
                                )
                            )
                        )
                    ));
                    $('.mf-dialog-folder').append($("<div class='separator separator-dashed my-5'>"))
                    i++;
                })

                if (upper_folder_id.length > 0 || response.data.folder_info.path != "/") {


                    $('.mf-dialog-folder').prepend($("<div class='separator separator-dashed my-5'>"))

                    $('.mf-dialog-folder').prepend($(`<div class="d-flex mf_upper_folder_load" upID="${upper_folder_id}">`).append(
                        $('<div class="form-check form-check-custom form-check-solid">').append(
                            $(`<label class="form-check-label" style="cursor: pointer" onclick="loadMoveFileFolderDatas('${fileID}','${upper_folder_id}')">`).append(
                                $('<div class="fw-bold">').append(
                                    $('<i class="fas fa-level-up-alt fs-2 text-primary me-2">'),
                                    "Upper folder"
                                )
                            )
                        )
                    ));

                }

                if ($('input[name="move_to_folder"]:checked').length > 0){

                    $('#kt_modal_move_to_folder_submit').removeClass("disabled");
                }

            }, error: function (err) {

                $('#kt_modal_move_to_folder').modal('hide');
            }
        });
    }

    $('.mf-dialog-folder').on('change','input[name="move_to_folder"]',function(){
        if ($('input[name="move_to_folder"]:checked').length > 0){
            $('#kt_modal_move_to_folder_submit').removeClass("disabled");
        }else{
            $('#kt_modal_move_to_folder_submit').addClass("disabled");
        }
    })

</script>
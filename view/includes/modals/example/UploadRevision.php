<style>
    .upload-new-rev-file-area {
        width: 100%;
        height: 64px;
        border: 2px dotted lightgrey;

        display: flex;
        justify-content: center;
        align-items: center;

        font-size: 18px;

        cursor: pointer;

    }
</style>
<!--begin::Modal - Upload File-->
<div class="modal fade" id="kt_modal_newRev_upload" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <div>
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Upload Revision File</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <button type="button" class="btn-close" onclick="clearRevisionFileModal()" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body pt-10 pb-15 px-lg-17">
                    <div class="row g-6 g-xl-9 mb-6 mb-xl-9 d-flex justify-content-center">
                        <div class="col-md-6 col-lg-4 col-xl-4">
                            <!--begin::Card-->
                            <div class="card h-100 ">
                                <!--begin::Card body-->
                                <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                                    <!--begin::Name-->
                                    <a href="javascript:void(0)"
                                       class="text-body text-hover-primary d-flex flex-column">
                                        <!--begin::Image-->
                                        <div class="symbol symbol-60px mb-5">
                                            <img id="new-rev-file-symbol-file-ico"
                                                 src="/assets/images/files/file.svg"
                                                 onerror="loadDefaultFileImage(this)"
                                                 alt="">

                                        </div>
                                        <!--end::Image-->

                                        <!--begin::Title-->
                                        <div class="fs-5 fw-bold mb-2" id="new-rev-file-name">
                                        </div>
                                        <!--end::Title-->
                                    </a>
                                    <!--end::Name-->

                                    <!--begin::Description-->
                                    <div class="fs-7 fw-semibold text-body-secondary" id="new-rev-file-description">
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Card-->
                        </div>
                    </div>
                    <!--begin::Input group-->
                    <div class="form-group">
                        <div class="upload-new-rev-file-area mb-3">
                            <span id="new-rev-file-temp-name"><i class="fas fa-plus me-2" style="font-size: 18px"></i> Add File</span>
                        </div>
                        <!--begin::Hint-->
                        <span class="form-text fs-6 text-muted">Max file size is 1GB per file.</span>
                        <!--end::Hint-->
                    </div>
                    <div class="form-group mt-3 border-top pt-3">
                        <table class="table table-bordered">
                            <tr>
                                <th>Visible File Name</th>
                            </tr>
                            <tr>
                                <td><input class="form-control revision-file-name" placeholder="File Name"></td>
                            </tr>
                            <tr>
                                <th>File Description</th>
                            </tr>
                            <tr>
                                <td>
                                    <textarea class="form-control revision-file-description"
                                              placeholder="File Description"></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <input style="display: none" type="file" id="input-upload-new-rev-file-area">
                </div>
                <!--end::Modal body-->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            onclick="clearRevisionFileModal()">Close
                    </button>
                    <button type="button" class="btn btn-primary btn-upload-new-revision">Upload File</button>
                </div>
            </div>
            <!--end::Form-->
        </div>
    </div>
</div>
<script>

    var nrfiID = 0;

    function uploadNewRevision(newRevFileID,asyncStatus=true) {
        clearRevisionFileModal();
        nrfiID = newRevFileID;

        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: 'app/file_manager/getFileInfo', // Sunucu tarafındaki dosya yükleme script'inin URL'si
            async:asyncStatus, // bu drag and drop yapıldığı zaman visible file name değiştirmeden önce bu işlemin bitmesini beklemesi için
            data: {
                pKey: $('#project-key').val(),
                revID: $('#revision-id').val(),
                fileID: newRevFileID,
            },
            success: function (response) {
                $('#new-rev-file-symbol-file-ico').attr('src', `/assets/images/files/${response.data.file_extention}.svg`)
                $('#new-rev-file-name').html(response.data.file_name);
                $('.revision-file-name').val(response.data.file_name);
                $('.revision-file-description').html(response.data.file_description);
                $('#kt_modal_newRev_upload').modal("show");
            },
            error: function (response) {
                $('#kt_modal_newRev_upload').hide();
            },
        });


    }

    $('.upload-new-rev-file-area').click(function () {

        $('#input-upload-new-rev-file-area').click();
    })


    function clearRevisionFileModal() {
        nrfiID = "";
        $('#input-upload-new-rev-file-area')[0].files[0] = null;
    }

    $('#input-upload-new-rev-file-area').change(function () {
        console.log($(this)[0].files[0])
        if ($(this)[0].files.length > 0) {
            $('#new-rev-file-temp-name').html("<i class='fas fa-file me-2' style='font-size: 18px'></i> " + $(this)[0].files[0].name);
            $('.revision-file-name').val($(this)[0].files[0].name);
        } else
            $('#new-rev-file-temp-name').html('<i class="fas fa-plus me-2" style="font-size: 18px"></i> Add File');
    })

    $('.btn-upload-new-revision').click(function () {

        var file = $('#input-upload-new-rev-file-area')[0].files[0];

        var formData = new FormData();
        formData.append('file', file);
        formData.append('fileID', nrfiID);
        formData.append('pKey', $('#project-key').val());
        formData.append('revID', $('#revision-id').val());
        formData.append('newVisibleName', $('.revision-file-name').val());
        formData.append('newVisibleDescription', $('.revision-file-description').val());

        $.ajax({
            type: 'POST',
            url: 'app/file_manager/uploadRevisionFile', // Sunucu tarafındaki dosya yükleme script'inin URL'si
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function (response) {
                clearRevisionFileModal();
                $('#kt_modal_newRev_upload').modal("hide");
                showModalMessage(response.message, false);
            },
            error: function (response) {
                clearRevisionFileModal();
                $('#kt_modal_newRev_upload').modal("hide");
            }
        });
    });


    $("#kt_modal_newRev_upload").on("hidden.bs.modal", function () {
        reloadFiles();
    });
</script>
<!--end::Modal - Upload File-->

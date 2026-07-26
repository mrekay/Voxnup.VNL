<style>
    .upload-file-area {
        width: 100%;
        height: 64px;
        border: 2px dotted lightgrey;

        display: flex;
        justify-content: center;
        align-items: center;

        font-size: 18px;

        cursor: pointer;

    }

    .upload-file-input {
        display: none;
    }

    .upload-file-area-files {
        display: none;
    }
</style>
<!--begin::Modal - Upload File-->
<div class="modal fade" id="kt_modal_upload" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <div>
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Upload files</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark fs-1" onclick="clearUploadFileModal()">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body pt-10 pb-15 px-lg-17">
                    <!--begin::Input group-->
                    <div class="form-group">
                        <div class="upload-file-area mb-3">
                            <i class="fas fa-plus me-3"></i> Add Files
                        </div>
                        <!--begin::Hint-->
                        <span class="form-text fs-6 text-muted">Max file size is 1GB per file.</span>
                        <!--end::Hint-->
                    </div>
                    <div class="form-group mt-5">
                        <table class="table table-striped table-bordered table-rounded" id="upload-file-list-table"
                               style="display: none">
                            <thead>
                            <tr>
                                <th>File name</th>
                                <th>File Description</th>
                                <th>File Size</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody id="upload-file-list-table-tbody"></tbody>
                        </table>
                    </div>
                    <div class="upload-file-area-files"></div>
                    <!--end::Input group-->
                </div>
                <!--end::Modal body-->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            onclick="clearUploadFileModal()">Close
                    </button>
                    <button type="button" class="btn btn-primary btn-upload-all-files">Upload Files</button>
                </div>
            </div>
            <!--end::Form-->
        </div>
    </div>
</div>
<!--end::Modal - Upload File-->
<script>

    var ufiID = 0;

    $('.upload-file-area').click(function () {
        var inp = createUploadFileInput();
        inp.click();
        $('#upload-file-list-table').show();

    })

    function createUploadFileInput() {
        ufiID++;
        var inp = $(`<input ufiid="${ufiID}" class="upload-file-input" type="file">`);
        $(inp).appendTo($('.upload-file-area-files'));
        $('#upload-file-list-table').show();
        return inp;
    }

    $('.upload-file-area-files').on('change', '.upload-file-input', function () {
        // Dosya seçildiğinde bu blok çalışacak

        if (this.files && this.files.length > 0) {
            $('#upload-file-list-table-tbody').append(
                $(`<tr ufiid="${ufiID}">`)
                    .append($('<td>').append($('<input class="form-control form-control-sm file-upload-visible-name" placeholder="File Name">').val(this.files[0].name)))
                    .append($('<td>').append($('<input class="form-control form-control-sm file-upload-desc" placeholder="File Description">')))
                    .append($('<td class="align-middle text-center">').html(convertFileSize(this.files[0].size)))
                    .append($('<td class="upload-status align-middle text-center">').html("Not Uploaded"))
            )


        } else {
            // Dosya seçilmedi
            $(this).remove();
        }
    });

    function clearUploadFileModal() {
        $('.upload-file-area-files').html("");
        $('#upload-file-list-table-tbody').html("");
        $('#upload-file-list-table').hide();
    }

    $('.btn-upload-all-files').click(function () {
        $.each($('.upload-file-input'), function (k, v) {

            var file = v.files[0];
            var ufiid = $(v).attr("ufiid");
            var formData = new FormData();
            formData.append('file', file);
            formData.append('file_name', $(`tr[ufiid=${ufiid}] .file-upload-visible-name`).val());
            formData.append('file_desc', $(`tr[ufiid=${ufiid}] .file-upload-desc`).val());
            formData.append('pKey', $('#project-key').val());
            formData.append('fKey', $('#folder-key').val());
            formData.append('revID', $('#revision-id').val());

            $.ajax({
                type: 'POST',
                url: 'app/file_manager/uploadFile', // Sunucu tarafındaki dosya yükleme script'inin URL'si
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    $(v).remove();
                    $(`#upload-file-list-table-tbody tr[ufiid="${$(v).attr("ufiid")}"] .upload-status`).html("<span class='text-success'>Upload Successful</span>");

                },
                error: function (response) {
                    $(`#upload-file-list-table-tbody tr[ufiid="${$(v).attr("ufiid")}"] .upload-status`).html("<span class='text-danger'>Upload Fail</span>");
                }
            });
        });

    });

    $("#kt_modal_upload").on("hidden.bs.modal", function () {
        reloadFiles();
    });

    $('#kt_modal_upload').on('drop', ".upload-file-area", function (e) {
        e.preventDefault();
        e.stopPropagation();

        $('.dosya-birak').hide();

        $(this).find("td").removeClass("d-none")
        $(this).find(".remove-after-drop").remove();

        var _files = e.originalEvent.dataTransfer.files;
        if (_files.length > 0) {

            $.each(_files, function (k, v) {
                var inp = createUploadFileInput();

                /* dosya için ayrı transfer oluştur*/
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(v);

                $(inp)[0].files = dataTransfer.files;
                $(inp).trigger('change');
            })
        }

    });
</script>
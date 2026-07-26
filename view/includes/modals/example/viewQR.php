<style>
    #project_qr_view_modal .file-info {
        width: 100%;
        height: auto;

        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #project_qr_view_modal .file-qr-data-card {
        width: auto;
        display: flex;
        justify-content: center;

        align-items: center;
        justify-content: center;

        background-color: white;
        border-radius: 8px;
    }

    #project_qr_view_modal .file-qr-data {
        width: 100%;
        height: auto;
        padding: 10px;

        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }

    #project_qr_view_modal .file-qr-data-options {
        width: 100%;
        height: auto;
        padding: 10px;

        display: flex;
        justify-content: center;
        align-items: center;
    }

    #project_qr_view_modal #file-qr {
        border-radius: 8px;
    }
</style>
<!--begin::Modal - Upload File-->
<div class="modal fade" id="project_qr_view_modal" tabindex="-1" aria-hidden="true">
    <input type="hidden" name="fUUID" value="">
    <input type="hidden" name="rID" value="">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <div>
                <div class="loading-overlay">
                    <i class="fas fa-2x fa-sync fa-spin"></i>
                </div>
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">View QR</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <button type="button" class="btn-close" onclick="clearUploadFileModal()" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body pt-5 pb-15 px-7">
                    <div class="file-info">
                        <div class="file-name">
                            -
                        </div>
                        <div class="file-desc" style="color: #666">
                            -
                        </div>
                        <div class="file-revision-info">
                            <select class="form-control s-file-revision-info">
                                <option>Revision 0</option>
                            </select>
                        </div>
                    </div>
                    <div class="file-qr-data">
                        <div class="file-qr-data-card shadow-sm">
                            <div class="shadow-sm" id="file-qr" svgdata="">
                                <div class="badge badge-lg badge-soft-primary mt-3 mb-7">
                                    <div class="d-flex align-items-center flex-wrap ">
                                        <i class="fas fa-qrcode fs-2 text-primary me-2"></i>
                                        <span id="file-qr-text" class="h3 text-primary mb-0"></span>
                                        <i class="fas fa-copy text-primary ms-2 text-sm qr-has-tooltip"
                                           title="Copy QR Code Text" data-bs-toggle="tooltip" data-bs-placement="top"
                                           style="cursor: pointer;" id="file-qr-text-copy"></i>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="file-qr-data-options">
                            <div class="btn btn-primary btn-icon btn-sm me-2" onclick="QRCopyLink()"
                                 data-toggle="tooltip" title="Copy QR Code Link">
                                <i class="fas fa-link"></i>
                            </div>
                            <div class="btn btn-info btn-icon btn-sm me-2" onclick="QRCopyAccessCode()"
                                 data-toggle="tooltip" title="Copy Access Code">
                                <i class="fas fa-copy"></i>
                            </div>
                            <div class="btn btn-warning btn-icon btn-sm me-2" onclick="openQRInNewTab()"
                                 data-toggle="tooltip" title="Open link in new tab">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                            <div class="btn btn-success btn-icon btn-sm me-2" onclick="downloadQRImage()"
                                 data-toggle="tooltip" title="Download QR Image">
                                <i class="fas fa-download"></i>
                            </div>
                        </div>
                    </div>
                    <!--end::Modal body-->
                </div>
                <!--end::Form-->
            </div>
        </div>
    </div>
    <!--end::Modal - Upload File-->
    <script>

        function viewQRData($pKey, $revID, $fUUID) {
            var shownAllready = false;
            if ($('#project_qr_view_modal input[name=fUUID]').val() == $fUUID && $('#project_qr_view_modal input[name=rID]').val() == $revID) {
                $('#project_qr_view_modal').modal("show");
                return;
            }
            $('#project_qr_view_modal .loading-overlay').fadeIn();
            shownAllready = $('#project_qr_view_modal:visible').length > 0;
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: 'app/project/viewQR', // Sunucu tarafındaki dosya yükleme script'inin URL'si
                data: {
                    pKey: $pKey,
                    revisionID: $revID == ""  ? "last" : $revID,
                    projectRevID: $('#revision-id').val(),
                    fKey: $fUUID,
                },
                success: function (response) {
                    $('#project_qr_view_modal input[name=fUUID]').val($fUUID);
                    $('#project_qr_view_modal input[name=rID]').val($revID);
                    $('#project_qr_view_modal').modal("show");
                    $('.qr-has-tooltip').tooltip();
                    $('#project_qr_view_modal #file-qr-text-copy').attr("text", response.data.qr_access_code);
                    $('#project_qr_view_modal #file-qr-text').html(response.data.qr_access_code);
                    $('#project_qr_view_modal .file-name').html(response.data.file_name);
                    $('#project_qr_view_modal .file-desc').html(response.data.file_description);

                    $('#project_qr_view_modal .s-file-revision-info').html("");
                    var selected = false;
                    var shownAllready = false;
                    response.data.revision_id_list.forEach(function(rev_id) {
                        var _text = "Revision " + rev_id;
                        selected = (response.data.selected_revision_id == rev_id);
                        shownAllready = selected ? true : shownAllready;
                        $('#project_qr_view_modal .s-file-revision-info').append(`<option value="${rev_id}" ${selected ? "selected" : ""}>${_text}</option>`);
                    });

                    $('#project_qr_view_modal .s-file-revision-info').append(`<option value="" ${shownAllready ? "" : "selected"}>Latest Revision</option>`);
                    if (!shownAllready) {
                        $('#project_qr_view_modal .s-file-revision-info option:last').attr("selected", 1);
                    }

                    $('#project_qr_view_modal #file-qr').html(response.data.qr_image);
                    $('#project_qr_view_modal #file-qr').attr("svgdata", response.data.qr_image);
                    $('#project_qr_view_modal #file-qr').attr("qr-access-code", response.data.qr_access_code);
                    $('#project_qr_view_modal #file-qr').attr("qr-link", response.data.qr_link);
                    // $('#project_qr_view_modal #file-qr').attr("src",response.data.qr_image);
                    $('#project_qr_view_modal .loading-overlay').fadeOut();
                },
                error: function (response) {
                    $('#project_qr_view_modal').modal("hide");
                    $('#project_qr_view_modal .loading-overlay').fadeOut();

                }
            });
        }

        function downloadQRImage() {
            var svgData = $('#file-qr').attr('svgdata'); // SVG stringini buraya yaz
            var base64Data = 'data:image/svg+xml;base64,' + window.btoa(unescape(encodeURIComponent(svgData)));
            var canvas = document.createElement("canvas");
            var context = canvas.getContext("2d");
            var img = new Image();

            img.onload = function () {
                canvas.width = img.width;
                canvas.height = img.height;
                context.drawImage(img, 0, 0);
                var pngUrl = canvas.toDataURL("image/png");
                var dlLink = document.createElement("a");
                dlLink.href = pngUrl;
                dlLink.download = `latestset_com_qr_code.png`;
                document.body.appendChild(dlLink);
                dlLink.click();
                document.body.removeChild(dlLink);
            };
            img.src = base64Data;

        }

        function QRCopyAccessCode() {
            copyToClipboard($('#file-qr').attr("qr-access-code"));
        }

        function QRCopyLink() {
            copyToClipboard($('#file-qr').attr("qr-link"));
        }

        function openQRInNewTab() {
            // openLink() VNL 3.0'da kaldırıldı; doğrudan window.open kullanılıyor.
            window.open($('#file-qr').attr("qr-link"), '_blank');
        }

        $('.s-file-revision-info').change(function () {
            viewQRData($("#project-key").val(), $(this).val(), $('#project_qr_view_modal input[name=fUUID]').val())
        })


    </script>
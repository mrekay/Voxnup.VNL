<script>

    var project_name = "";
    var project_latest_revision = 0;
    var upperFolderID = "";
    var navHistory = [];

    $('#kt_app_content').parent().ready(function () {
        if ($('#project-key').val())
            loadProjectData($('#project-key').val());
    })

    /* Navigation */
    function navGoBack() {
        if (navHistory.length > 0) {
            var sonEleman = navHistory[navHistory.length - 1];
            navHistory.pop();
            loadFilesData($('#project-key').val(), sonEleman, true);
        }
    }

    function loadProjectData(prKey) {
        $('#project-key').val(prKey);
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: 'app/project/getData',
            data: {
                pKey: prKey,
            },
            success: function (response) {
                $('.revisionDDB-ddmenu').html("");
                $('#project-name').html(response.project_name);
                $('#project-desc').html(response.project_description);
                $('#project-revision-count').html(response.revision_id + " Revisions");
                project_latest_revision = response.revision_id
                $('#project-size').html(convertFileSize(response.project_size));
                $('#revision-id').val(<?= $req->postHas("revID") && is_int($req->post('revID')) ? $req->post('revID') : "null" ?> ?? response.revision_id);
                for ($i = 0; $i <= response.revision_id; $i++) {
                    $('.revisionDDB-ddmenu').append(`<li class="btn-changeRev" revID="${$i}"><div class="dropdown-item ">Revision ${$i}</div></li>`);
                }
                $('.rev-numb').html($('#revision-id').val());
                loadFilesData(prKey);
            }, error: function (err) {
            }
        });
    }

    function loadFilesData(prKey, fldKey, cf_navHistory = false) {
        $('#project-key').val(prKey);
        $('#folder-key').val(fldKey);

        if (fldKey) $('.load-upper-folder').show();
        else $('.load-upper-folder').hide();

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: 'app/file_manager/getFolderData',
            data: {
                pKey: prKey,
                fKey: fldKey,
                revID: $('#revision-id').val(),
                search: $('#search-on-files').val(),
            },
            success: function (response) {
                if (response.data.folder_info.length <= 0) loadFilesData(prKey, "");

                if (!cf_navHistory && navHistory[navHistory.length - 1] != (upperFolderID ?? ""))
                    navHistory.push((upperFolderID ?? ""));

                $('#kt_file_manager_new_folder_row').prependTo('#fm_new_folder_row_table_hidden_template');

                $('#kt_file_manager_list tbody').html("");
                $('.dt-no-items-found').hide();


                drawPath(response.data.folder_info.path);
                $("#kt_file_manager_items_counter").html(response.data.item_count + " Items");

                if (response.data.files.length > 0) {
                    upperFolderID = response.data.folder_info.upper_folder_key;
                    $.each(response.data.files, function (k, v) {

                        switch (v.ff_type) {
                            case "FOLDER":
                                createDynamicFolderRow(v);
                                break;
                            case "FILE":
                                createDynamicFileRow(v);
                                break;
                        }

                    })
                } else {
                    $('.dt-no-items-found').fadeIn();
                }
            }, error: function (err) {
            }
        });
    }

    function loadUpperFolder() {

        loadFilesData($('#project-key').val(), upperFolderID);

    }

    function returnProjectList() {
        loadApps({
            url: "project-list",
            cache_key: "project_list"
        })
    }

    function reloadFiles() {
        deSelectSelection();
        loadFilesData($('#project-key').val(), $('#folder-key').val());
    }

    /* Actions */

    function deSelectSelection() {
        $('#dt-selected-files').hide();
        $('.btn-hide-in-modify-mode').css("display", "flex")
            .hide()
            .fadeIn();
    }

    function createFolder() {
        var prKey = $('#project-key').val();
        var folder_name = $('input[name="new_folder_name"]').val();
        var upper_key = $('#folder-key').val();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: 'app/file_manager/createFolder',
            async: false,
            data: {
                prKey: prKey,
                folder_name: folder_name,
                upper_key: upper_key,
                revID: $('#revision-id').val()
            },
            success: function (response) {
                showModalMessage(response.message, false)
                $('#kt_file_manager_new_folder_row').prependTo('#fm_new_folder_row_table_hidden_template');
                loadFilesData(prKey, upper_key);
            }
        });
        $('#kt_file_manager_new_folder_row').prependTo('#fm_new_folder_row_table_hidden_template');

        $('#kt_file_manager_add_folder').children('.indicator-label').show();
        $('#kt_file_manager_add_folder').children('.indicator-progress').hide();
    }

    function createDynamicFolderRow(v) {
        var tr = createDynamicRow(v);
        $(tr).addClass("dynamicFolderRow");
        $(tr).find("a").addClass("navigate-folder").attr("fkey", v.UUID);
        $(tr).find(".row-name").prepend(
            $('<span>').addClass('icon-wrapper').append(
                $('<i>').addClass('ki-duotone ki-folder fs-2x text-primary me-4')
                    .append($('<span>').addClass('path1'))
                    .append($('<span>').addClass('path2'))
            )
        );

    }

    function createDynamicFileRow(v) {
        var tr = createDynamicRow(v);
        tr.find("td").first().append($('<div>').addClass('td-dropfile-zone').html("Drop here to update the file.").hide());

        tr.find(".ff-name").attr("ac", v.access_key).addClass("fm-fl-row-openFile").attr("revID",v.revID);
    }

    function createDynamicRow(v) {
        // Ana <tr> elementi
        var $tr = $(`<tr class="dynamicrow" row-uuid="${v.UUID}">`);

        // İlk <td> ve içeriği
        var $td1 = $('<td class="w-10px">').append(
            $('<div>').addClass('form-check form-check-sm form-check-custom form-check-solid').append(
                $('<input>').addClass('form-check-input select-row-check-input').attr('type', 'checkbox').val('1').attr('fm-data', JSON.stringify({
                    UUID: v.UUID,
                    type: v.ff_type,
                    name: v.ff_name,
                    revID: v.revID
                }))
            )
        );

        // İkinci <td> ve içeriği
        var $td2 = $('<td class="min-w-250px">').append(
            $('<div>').addClass('d-flex align-items-center row-name').append(
                $('<a>').attr('href', 'javascript:void(0)').addClass('text-gray-800 text-hover-primary ff-name').text(v.ff_name).append( v.revID != null ? `<span data-bs-toggle="tooltip" title="${v.revID != null && v.revID < $('#revision-id').val() ? 'This file/folder has no record in the revision you are viewing.': ''}" class='nr-tooltip ms-3 badge badge-sm badge-light fst-italic' >Revision : ${v.revID}</span>` :'')
            )
        );

        if (v.ff_type != "FOLDER") {
            var rowName = $td2.find('.row-name');
            rowName.prepend($('<i class="fas fa-caret-down me-2 fs-6" onclick="showOlderRevisionsOfFile(this)" style="cursor: pointer">'));
        }

        // Diğer <td> elemanları
        var $td3 = $('<td class="min-w-10px">').text(v.ff_type != "FOLDER" ? v.description : "");
        var $td4 = $('<td class="min-w-10px">').text(v.ff_type != "FOLDER" ? v.file_size : "-");
        var $td5 = $('<td class="min-w-125px">').text(v.last_modify_date);

        // Altıncı <td> ve iç içe yapıları
        var $td6 = $('<td class="w-125px">').addClass('btn-group text-end');
        if (v.ff_type != "FOLDER") {

            if (v.revID < $('#revision-id').val()) {
                var $td6_n_revision = $(`<div class=" btn btn-sm btn-icon btn-light btn-active-light-primary" onclick="uploadNewRevision('${v.UUID}')">`).append($('<i class="fas fa-upload">'))
                $td6.append($td6_n_revision);
            }

            var $td6_qr_button = $(`<div class="btn btn-sm btn-icon btn-light btn-active-light-primary" onclick="viewQRData('${$('#project-key').val()}','${($('#revision-id').val()!=project_latest_revision ? $('#revision-id').val() : "")}','${v.UUID}')">`).append($('<i class="fas fa-qrcode fs-5 m-0">'))
            $td6.append($td6_qr_button);

            var $td6_qr_button = $(`<div class="btn btn-sm btn-icon btn-light btn-active-light-primary dropdown-toggle" data-bs-toggle="dropdown">`).append($('<i class="fas fa-ellipsis-h">'))
            $td6.append($td6_qr_button);

            var tddrpdwn = $('<ul class="dropdown-menu">');
            tddrpdwn.append($(`<li><a class="dropdown-item di-move-file" onclick="loadMoveFileFolderDatas('${v.UUID}')" href="#">Move File</a></li>`));
            tddrpdwn.append($(`<li><a class="dropdown-item di-move-file" onclick="editFile('${v.UUID}')" href="#">Edit File</a></li>`));
            $td6.append(tddrpdwn);


        }
        // <tr> içine tüm <td> elemanlarını ekleyin
        $tr.append($td1, $td2, $td3, $td4, $td5, $td6);

        // Son olarak oluşturulan <tr> elemanını tabloya ekleyin
        $('#kt_file_manager_list tbody').append($tr);
        $('.nr-tooltip ').tooltip();
        return $tr;
    }

    function drawPath($path_string) {
        $('.project-folder-path-info').html("");
        var seperator = $('<i class="ki-duotone ki-right fs-2 text-primary mx-1">');

        if (typeof $path_string === "undefined") return;

        var i = 0;
        var path_info = $path_string.split("/");
        $.each(path_info, function (k, v) {
            if (i > 0 && v) $('.project-folder-path-info').append(seperator.clone())
            $('.project-folder-path-info').append((i == 0) ? "Root Folder" : v)
            i++;
        });
    }

    $(document).on('click', '.navigate-folder', function () {
        loadFilesData($('#project-key').val(), $(this).attr('fkey'));
    });
    $(document).on('click', '.fm-fl-row-openFile', function () {
        window.open("/view-file?ac=" + $(this).attr("ac") + ($(this).attr('revID') != project_latest_revision ? $(this).attr('revID') : "" ))
    });
    $('.load-upper-folder').click(function () {
        loadUpperFolder();
    })

    $('#kt_file_manager_new_folder').click(function () {
        if ($('#kt_file_manager_list tbody #kt_file_manager_new_folder_row').length <= 0)
            $('#kt_file_manager_new_folder_row').prependTo('#kt_file_manager_list tbody');
    })
    $('#kt_file_manager_add_folder').click(function () {
        $(this).children('.indicator-label').hide();
        $(this).children('.indicator-progress').show();
        if ($('input[name="new_folder_name"]').val())
            createFolder();
        else {
            $(this).children('.indicator-label').show();
            $(this).children('.indicator-progress').hide();
            showModalMessage("Folder name is required", true);
        }
    })

    $(document).on('change', '.form-check-input-all', function () {
        rowCheckChange();
    })
    $(document).on('change', '.select-row-check-input', function () {
        rowCheckChange();
    })

    function rowCheckChange() {
        var l = $('.select-row-check-input:checked').length;
        if ($('.select-row-check-input:checked').length > 0) {
            $('#dt-selected-files').css("display", "flex")
                .hide()
                .fadeIn();
            $('.btn-hide-in-modify-mode').hide();
            $('.filemanager-table-select').html(l);
        } else {
            $('#dt-selected-files').hide();
            $('.btn-hide-in-modify-mode').css("display", "flex")
                .hide()
                .fadeIn();
        }
    }

    $('.delete_selected').click(function () {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {

                var deletion_list = [];
                $.each($('.select-row-check-input:checked'), function (k, v) {
                    deletion_list.push(JSON.parse($(v).attr("fm-data")));
                })
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: 'app/file_manager/deleteFile',
                    async: false,
                    data: {
                        prKey: $('#project-key').val(),
                        deletion_list: JSON.stringify(deletion_list),
                        revID: $('#revision-id').val(),
                    },
                    success: function (response) {
                        Swal.fire({
                            title: "Deleted!",
                            text: "Your file has been deleted.",
                            icon: "success"
                        });
                        reloadFiles();
                        deSelectSelection();
                    }
                });

            }
        });
    })

    $('#search-on-files').keyup(function () {
        reloadFiles();
    })

    $(document).on('click', '.btn-changeRev', function () {
        var rID = $(this).attr("revID");
        if ($('#revision-id').val() != rID) {
            $('#revision-id').val(rID);
            $('.rev-numb').html($('#revision-id').val());
            reloadFiles();
        }
    })

    function createNewRevision() {
        Swal.fire({
            title: "Are you sure?",
            text: "Are you sure you want to create a new revision?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes"
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: 'app/project/createRevision',
                    async: false,
                    data: {
                        prKey: $('#project-key').val(),
                    },
                    success: function (response) {
                        Swal.fire({
                            title: "Successful!",
                            text: "New revision created successfully.",
                            icon: "success"
                        });
                        loadProjectData($('#project-key').val());
                        deSelectSelection();
                    }
                });

            }
        });
    }

    $("body").on('dragover', function (e) {
        // e.preventDefault();
        // e.stopPropagation();
        var dt = e.originalEvent.dataTransfer;
        if (dt.types && (dt.types.indexOf ? dt.types.indexOf('Files') != -1 : dt.types.contains('Files'))) {
            // $('.dosya-birak').appendTo($(this));
            $('.dosya-birak').show();
        }
    });

    $(".files-list-wrapper").on('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var _files = e.originalEvent.dataTransfer.files;

        $('.dosya-birak').hide();

        $(this).find("td").removeClass("d-none")
        $(this).find(".remove-after-drop").remove();

        if (_files.length > 0) {

            clearUploadFileModal();
            $('#kt_modal_upload').modal("show");

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
    $(document).on('drop dragleave','body' , function (e) {

         $('.dosya-birak').hide();
    });


    $(document).on('dragover', '.dynamicrow', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var dt = e.originalEvent.dataTransfer;
        // $(this).parent().find(".td-dropfile-zone").remove();
        // if (dt.types && (dt.types.indexOf ? dt.types.indexOf('Files') != -1 : dt.types.contains('Files'))) {

        $(this).find('.td-dropfile-zone').show();

        // $(this).append($("<td colspan='3' class='remove-after-drop'>").html($(this).hasClass("dynamicFolderRow") ? "Cannot Drop Here" : "Drop here to update the file."));
        // }
    });

    $(document).on('drop dragleave', '.dynamicrow', function (e) {
        // $('.dosya-birak').hide();

        $(this).find('.td-dropfile-zone').hide();

        // if($(this).hasClass('on-drag-dr')){
        //     console.log('4444');
        //     $(this).find(".td-dropfile-zone").remove();
        // }
    });

    $(document).on('drop', ".dynamicrow", function (e) {
        e.preventDefault();
        e.stopPropagation();

        $('.dosya-birak').hide();

        $(this).find("td").removeClass("d-none")
        $(this).find(".remove-after-drop").remove();

        if ($(this).hasClass("dynamicFolderRow")) return;

        var _files = e.originalEvent.dataTransfer.files;
        var fileInfo = JSON.parse($(this).find(".select-row-check-input").attr("fm-data"));
        clearUploadFileModal();
        uploadNewRevision(fileInfo.UUID, false);
        $('#input-upload-new-rev-file-area')[0].files = _files;
        $('#input-upload-new-rev-file-area').trigger("change");
    });

    function showOlderRevisionsOfFile(elem) {

        var file_info = $(elem).parents("tr").first().find(".select-row-check-input");
        var file_json = JSON.parse(file_info.attr("fm-data"));

        if ($(`.dynamic-row-rev-info-${file_json.UUID}`).length > 0) {
            $(`.dynamic-row-rev-info-${file_json.UUID}`).remove();
            $(elem).removeClass("fa-caret-up").addClass("fa-caret-down");
            return;
        } else {
            $(elem).addClass("fa-caret-up").removeClass("fa-caret-down")
        }

        var projID = $('#project-key').val();

        if (file_json.type != "FOLDER") {
            console.log(file_json.revID)
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: 'app/file_manager/getAllFileRevisions',
                async: false,
                data: {
                    prKey: projID,
                    revID: file_json.revID,
                    fileKey: file_json.UUID,
                },
                success: function (response) {
                    if (response.data.length <= 0) {
                        showTD.append("No past revision record found.");
                    } else {
                        $.each(response.data, function (k, v) {
                            var showTR = $(`<tr class="dynamic-row-rev-info-${file_json.UUID}">`);
                            showTR.append(
                                $('<td>'),
                                $('<td>').append(
                                    $('<a href="javascript:void(0)" class="text-gray-800 text-hover-primary fm-fl-row-openFile" ac="'+v.qr_code+'" revid="'+v.file_revision+'">').text(v.file_name).append(
                                    $('<div class="badge badge-light ms-2">').append("<i class='fas fa-info-circle me-2'>").append(`Revision ${v.file_revision}`))),
                                $('<td>').text(v.file_description),
                                $('<td>').text(convertFileSize(v.file_size)),
                                $('<td>').text(v.last_modify_date),
                            );

                            if ($(`.dynamic-row-rev-info-${file_json.UUID}`).length <= 0)
                                $(elem).parents("tr").first().after(showTR);
                            else
                                $(`.dynamic-row-rev-info-${file_json.UUID}`).after(showTR);
                        });
                    }

                }
            });
        }

    }

    function editFile($file){
        uploadNewRevision($file);
    }

</script>
/**************************************************
 * Özel Datatable Kütüphanesi
 * TÜM FONKSİYONLAR BİR ARAYA TOPLANMIŞTIR
 * 20.02.2024 ERTUĞRUL
 * V2.0
 **************************************************/
let $first_datatable;

$(document).ready(function () {
    setDatatable();
});

function setDatatable() {

    // Datatable Yükle
    $datatable_i = 0;
    $('.datatable:not(.datatable-initialized)').each(function () {

        /* Aynı sayfada birden çok datatable varsa ilkini almak için */
        if ($datatable_i === 0) {
            $first_datatable = $(this);
        }
        $datatable_i++;

        $datatable_obj = null;
        $datatable = $(this);


        $datatableColsName = [];
        $datatableCols = [];
        $datatableColsSearch = [];
        $col_i = 0;

        $datatable.find("thead tr th").each(function () {

            $objStr = '{' + '"data" : "' + $(this).data('name') + '"';

            if ($(this).data('orderable') == '0')
                $objStr += ', "orderable": false';
            if ($(this).data('visibility') == "0" || $(this).data('visibility') == "-1") {
                $objStr += ', "visible": false';

            }
            $objStr += ', "searchable": true';
            $objStr += '}';
//				alert($objStr);

            var ob = jQuery.parseJSON($objStr);
            $datatableCols.push(ob);
            $datatableColsSearch.push(null);
        });
        $.each($datatableCols, function (key, value) {
            $t_data = $datatable.find('.trFilter td[data-colindex="' + key + '"]').find('.dt-filter-td').val();
            if ($t_data)
                $datatableColsSearch[key] = {search: $t_data};
            else
                $datatableColsSearch[key] = null;
        });

        // console.log($datatableCols);

        $datatable_id = $datatable.attr('id');


        var bPaginate = ($datatable.attr('data-bPaginate') !== undefined && $datatable.attr('data-bPaginate') !== null && $datatable.attr('data-bPaginate') !== '' && ($datatable.attr('data-bPaginate') === 'false' || $datatable.attr('data-bPaginate') === '0')) ? false : true;
        var bInfo = ($datatable.attr('data-bInfo') !== undefined && $datatable.attr('data-bInfo') !== null && $datatable.attr('data-bInfo') !== '' && ($datatable.attr('data-bInfo') === 'false' || $datatable.attr('data-bInfo') === '0')) ? false : true;
        var bFilter = ($datatable.attr('data-bFilter') !== undefined && $datatable.attr('data-bFilter') !== null && $datatable.attr('data-bFilter') !== '' && ($datatable.attr('data-bFilter') === 'false' || $datatable.attr('data-bFilter') === '0')) ? false : true;
        var bExcel = ($datatable.attr('data-bExcel') !== undefined && $datatable.attr('data-bExcel') !== null && $datatable.attr('data-bExcel') !== '' && ($datatable.attr('data-bExcel') === 'false' || $datatable.attr('data-bExcel') === '0')) ? false : true;

        /* DT-COLLAPSE 23.09.2022 */
        var bCollapse = ($datatable.attr('data-bCollapse') !== undefined && $datatable.attr('data-bCollapse') !== null && $datatable.attr('data-bCollapse') !== '' && ($datatable.attr('data-bCollapse') === 'true' || $datatable.attr('data-bCollapse') === '1')) ? true : false;

        if (bCollapse) {
            $datatableCols[0] = {
                className: 'dt-control',
                orderable: false,
                data: $datatableCols[0]['data'],
                defaultContent: '',
            };
            // $datatableCols.push({data:"bCollapse",visible:false});
        }

        var isMultiSelectable = ($datatable.attr('data-multiSelectable') !== undefined && $datatable.attr('data-multiSelectable') !== null && $datatable.attr('data-multiSelectable') !== '') ? ($datatable.attr('data-multiSelectable') === 'true' ? true : false) : false;
        var isMultiSelectAll = ($datatable.attr('data-multiSelectAll') !== undefined && $datatable.attr('data-multiSelectAll') !== null && $datatable.attr('data-multiSelectAll') !== '') ? ($datatable.attr('data-multiSelectAll') === 'true' ? true : false) : true;
        var isMultiSelectCancel = ($datatable.attr('data-multiSelectCancel') !== undefined && $datatable.attr('data-multiSelectCancel') !== null && $datatable.attr('data-multiSelectCancel') !== '') ? ($datatable.attr('data-multiSelectCancel') === 'true' ? true : false) : true;
        // data-multiSelectable='true'
        // saklanmesı ve listelenmesi istenilen stunların header larına --- data-mselect-head="1" --- eklenmelidir


        var sortAction = ($datatable.attr('data-sortAction') !== undefined && $datatable.attr('data-sortAction') !== null && $datatable.attr('data-sortAction') !== '') ? $datatable.attr('data-sortAction') : false;
        var isSortable = (sortAction ? true : false);

        $datatable.before('<div class="dt-toprightpanel"></div>');

        if (isMultiSelectable) {
            $datatable.find('thead tr.trFilter td:first-child').append('<input type="hidden" name="dt-allItemSelected"  class="datatable-pass-data dt-allItemSelected" value="0">');
            $datatable.parent().find('.dt-toprightpanel').html(
                '<div class="datatable-multiselectable">' +
                '<div class="datatable-selectedlist" ' + (isMultiSelectCancel ? '' : 'style="display: block;"') + '>' +
                '<span class="datatable-selectedlist-head badge badge-info"><span class="ms-count">0</span> satır seçildi</span></div>' +
                (isMultiSelectAll ? '<button type="button" class="btn btn-success btn-sm btn-dt-select-all" ' + (isMultiSelectCancel ? 'style="display: none;"' : '') + '><span><i class="fa fa-angle-choose" aria-hidden="true"></i>Tümünü Seç</span></button>' : '') +
                (isMultiSelectCancel ? '<button type="button" class="btn btn-secondary btn-sm btn-dt-multiselectable"><span><i class="fa fa-angle-choose" aria-hidden="true"></i>Seç</span></button>' : '') +
                '</div>');

            if (!isMultiSelectCancel) {
                $datatable.addClass('multiselectable-active');
                multiSelectableControl(true, $datatable.attr('id'));
            }
        } else { // select tanımı yoksa false değerini bas diğer select ile alakalı tanımları yok et.
            $datatable.attr('data-multiSelectable', 'false');
            $datatable.removeAttr('data-multiselectcancel');
        }

        if (sortAction) {
            $datatable.parent().find('.dt-toprightpanel').append(
                '<div class="datatable-sortable disabledbutton">' +
                '<div class="btn-group">' +
                '<button type="button" class="btn btn-default order_up"><span><i class="fa fa-angle-up" aria-hidden="true"></i></span></button>' +
                '<button type="button" class="btn btn-default order_down"><span><i class="fa fa-angle-down" aria-hidden="true"></i></span></button></div> ' +
                '</div>');
        }

        $datatable_pass_data = {};

        $datatable.find('thead tr.trFilter td:first-child input.datatable-pass-data').each(function () {
            $pass_data = $(this);
            $pass_data_name = $pass_data.attr('name');
            $datatable_pass_data[$pass_data_name] = $pass_data.val();
        });

        $datatable_pass_data['default_action'] = $datatable.data("action");
        $datatable_pass_data['table_id'] = $datatable.attr("id");
        $datatable_pass_data['table_colums'] = {};

        let datatable_options = {};
        /* datatable "data-action" sahip değilse ya da boş ise ilk requesti atmayacak */
        if (typeof $datatable.data("action") === 'undefined' || $datatable.data("action") == '') {
            datatable_options = {
                "deferLoading": 0
            };
        }

        datatable_options_main = {
            "processing": true,
            "serverSide": true,
            "stateSave": true,
            "bPaginate": bPaginate,
            "bInfo": bInfo,
            colReorder: {
                order: [0, 1, 2]
            },
            "stateLoadParams": function (settings, data) {

                $_dt_tmp = $(this[0]);
                $.each(data.columns, function (key, value) {
                    value.search.search = $_dt_tmp.find('.trFilter td[data-colindex="' + key + '"]').find('.dt-filter-td').val();
                });

            },
            "language": {
                "processing": DATATABLES_PROCESSING,
                "lengthMenu": DATATABLES_LENGTHMENU,
                "zeroRecords": DATATABLES_ZERORECORDS,
                "info": DATATABLES_INFO,
                "infoEmpty": DATATABLES_INFOEMPTY,
                "infoFiltered": DATATABLES_INFOFILTERED,
                "infoPostFix": DATATABLES_INFOPOSTFIX,
                "search": DATATABLES_SEARCH,
                "url": DATATABLES_URL,
                "paginate": {
                    "first": DATATABLES_PAGINATE_FIRST,
                    "previous": DATATABLES_PAGINATE_PREVIOUS,
                    "next": DATATABLES_PAGINATE_NEXT,
                    "last": DATATABLES_PAGINATE_LAST
                }
            },

            "ajax": {
                "url": $datatable.data("action"),
                "type": "POST",
                "data": $datatable_pass_data,
//					"async" : false,
                complete: function (e) {
                    //HideColControl($datatable_id,$datatableCols);
                    if (typeof onDatatableComplete == "function") {
                        onDatatableComplete($datatable);
                    }
                }
            },
            drawCallback: function () {

                var api = this.api();
                DatatableSumFooter(api);

                if ($(this).hasClass('multiselectable-active')) {
                    // seçme butonuna tıklanılımış
                    datatableCheckSelect($(this), false);
                }

            },
            "columns": $datatableCols,
            "searchCols": $datatableColsSearch,
        }

        datatable_options = Object.assign(datatable_options, datatable_options_main);
        // console.log(datatable_options);

        /* mobile-filter güncellemesi scroll 01.02.2022 ertuğrul*/
        if ($datatable.attr('scroll-enabled')) {
            datatable_options.scrollX = true;
            // datatable_options.scrollY = "200"; 03.02.2022 ertuğrul
            datatable_options.scrollY = $datatable.attr("scroll-size") ? $datatable.attr('scroll-size') : "18rem";
        }
        /* UYARI! mobile-filter ekli değilse scroll-enabled modunda filtreler çalışmaz!!!! UYARI! */

        $datatable_obj = $datatable.DataTable(datatable_options);

        $datatable_obj.on('preXhr', function (e, settings, data) {


            $(this).find('thead tr.trFilter td:first-child input.datatable-pass-data').each(function () {
                data[$(this).attr('name')] = $(this).val();
            });

            if ($(this).DataTable().ajax.url() === "actions/datatableToExcel.php") {
                $col_i = 0;
                $(this).find("thead tr th").each(function () {
                    data['table_colums'][$col_i] = {};
                    data['table_colums'][$col_i]['name'] = $(this).data('name');
                    data['table_colums'][$col_i]['html'] = $(this).html();

                    $col_i++;

                });
            }


        });

        $datatable_obj.on('draw', function () {
            elementReady();
            if (typeof selectedChanged === "function") {
                selectedChanged(false);
                if (isSortable) {
                    $(this).parents('.datatable-div').find('.datatable-sortable').addClass('disabledbutton');
                }
            }
            if (typeof DataTablesDraw === "function") {
                DataTablesDraw($(this).attr('id'));
            }
            if ($(this).data("scrolltop") != undefined && $(this).data("scrolltop") == 1 && $(window).scrollTop() > $(".page-content").offset().top) {
                $('html, body').animate({
                    scrollTop: $(".page-content").offset().top
                }, 1000);
            }
        });

        /* 23.09.2022 Collapse */
        $datatable.on('click', 'td.dt-control', function () {
            var tr = $(this).closest('tr');
            var row = $(this).closest('.datatable').DataTable().row(tr);

            if (row.child.isShown()) {

                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
                tr.removeClass('dt-hasChild');
            } else {

                var _rData = row.data();
                if (_rData && _rData.bCollapse) {
                    var _format = _rData.bCollapse;
                    row.child(_format).show();
                } else {
                    row.child.show();
                }

                row.child().addClass('not-select');
                tr.addClass('shown');
                tr.addClass('dt-hasChild');

            }
        });


        //$datatable_obj.search('').columns().search('').draw();
        $($datatable_obj.table().container()).on('change', '.dt-filter-td', function () {

            //var table = $datatable_obj.table();
            var table = $(this).parents('.datatable').DataTable().table();
            var visIdx = $(this).parent().index();
            var dataIdx = table.column.index('fromVisible', visIdx);

            var search_val = this.value;
            if ($(this).hasClass('dt-multi-filter')) {
                visIdx = $(this).closest('td').index();
                dataIdx = table.column.index('fromVisible', visIdx);
                var search_val_temp = {};
                $(this).closest('td').find('.dt-multi-filter').each(function () {
                    search_val_temp[$(this).attr('name')] = $(this).val();
                });
                search_val = JSON.stringify(search_val_temp);
            }

            if ($(this).hasClass('dt-select2-filter')) {
                search_val = $(this).val();
                search_val = search_val.join('|');
            }

            if ($(this).hasClass('date-input')) {
                var exp1 = search_val.split(" ");
                var res = exp1[0].split(".");
                res = res.length <= 1 ? search_val.split("/") : res;
                res = res.length <= 1 ? search_val.split("-") : res;
                if (res.length <= 1) {
                    console.log('date format bulunamadı');
                } else {
                    if (res[0].length === 4) {
                        search_val = res[0] + '-' + res[1] + '-' + res[2] + (exp1[1] ? ' ' + exp1[1] : '');
                    } else {
                        search_val = res[2] + '-' + res[1] + '-' + res[0] + (exp1[1] ? ' ' + exp1[1] : '');
                    }
                }

            }

            $(this).parents('.datatable').DataTable().column(dataIdx).search(search_val).draw();
        });
        if (bFilter) {
            /* 01.02.2022 mobile-filter güncellemesi (mobile filter yazarak aratın.) */
            if (!$datatable.attr('mobile-filter')) {
                if ($datatable.parents().hasClass('table-responsive')) {
                    $datatable.parents('.table-responsive').before('<div id="" class="datatableFilterButton fa fa-trash" style=""></div>');
                } else {
                    $datatable.before('<div id="" class="datatableFilterButton fa fa-search" style=""></div>');
                }
            }
        }
        if (bExcel) {
            /* 01.02.2022 mobile-filter güncellemesi (mobile filter yazarak aratın.) */
            if (!$datatable.attr('mobile-filter')) {
                if ($datatable.parents().hasClass('table-responsive')) {
                    $datatable.parents('.table-responsive').before('<div id="" class="datatableExcelButton far fa-file-excel" style=""></div>');
                } else {
                    $datatable.before('<div id="" class="datatableExcelButton fa fa-file-excel-o" style=""></div>');
                }
            }
        }
        $(this).addClass('datatable-initialized');
    });
    $datatable = $first_datatable;
}


function datatableRefreshAll() {
    $('.datatable').DataTable().draw();
}

// function datatableAddSelect($datatable, $tr) {
// 	var datatableOBJ = $datatable.DataTable();
// 	var index = $datatable.find('tbody tr').index($tr);
// 	var number_col_index = datatableOBJ.column('.table-col-box').index();
// 	var row_id = $tr.find('.row_id').val();
// 	if (row_id > 0) {
// 		$tr.addClass('multiselectable-selected');
// 		$tr.find('td').eq(number_col_index).prepend('<i class="dt-select-check fa fa-check-circle"></i>');
//
//
// 		// Tümünü seç aktif değilse seçme bilgisini kullan.
// 		if (!isDatatableRowSelected($datatable, row_id)) {
// 			$datatable.find('.trFilter td').first().append('<input type="hidden" class="datatable-pass-data datatable-delete-data dt-selectItem" name="dt-selectItem-' + row_id + '" value="' + row_id + '"/>');
// 		}
//
// 		multiSelectableSelectChange(true, $datatable.attr('id'), $tr);
//
// 	}
// 	datatableMultiSelectCountRefresh($datatable);
// }
function datatableAddSelect($datatable, row_id) {
    var datatableOBJ = $datatable.DataTable();
    var number_col_index = datatableOBJ.column('.table-col-box').index();
    $tr = $datatable.find('.row_id[value="' + row_id + '"]').closest('tr');
    if ($tr.length > 0) {
        $tr.addClass('multiselectable-selected');
        $tr.find('td').eq(number_col_index).prepend('<i class="dt-select-check fa fa-check-circle"></i>');

    }
    // Tümünü seç aktif değilse seçme bilgisini kullan.
    if (!isDatatableRowSelected($datatable, row_id)) {
        $datatable.find('.trFilter td').first().append('<input type="hidden" class="datatable-pass-data datatable-delete-data dt-selectItem" name="dt-selectItem[]" value="' + row_id + '"/>');
    }

    multiSelectableSelectChange(true, $datatable.attr('id'), $tr);
    datatableMultiSelectCountRefresh($datatable);
}

function datatableRemoveSelect($datatable, $tr) {
    // seçileni sil.
    var datatableOBJ = $datatable.DataTable();
    var number_col_index = datatableOBJ.column('.table-col-box').index();
    var row_id = $tr.find('.row_id').val();
    $tr.removeClass('multiselectable-selected');

    $tr.find('td').eq(number_col_index).find('.dt-select-check').remove();
    if (isDatatableRowSelected($datatable, row_id)) {
        // Seçili satırı kaldır.
        $datatable.find('input.dt-selectItem[value=' + row_id + ']').remove();
    }

    multiSelectableSelectChange(false, $datatable.attr('id'), $tr);
    datatableMultiSelectCountRefresh($datatable);
}

function datatableRemoveAllSelect($datatable) {
    $datatable.find('tr').removeClass('multiselectable-selected');
    $datatable.find('.dt-select-check').remove();
    $datatable.find('input.dt-selectItem').remove();
    multiSelectableSelectChange(false, $datatable.attr('id'), null);
    datatableMultiSelectCountRefresh($datatable);
}

function getdatatableMultiSelectCount($datatable) {

    return $datatable.find('input.dt-selectItem').length;
}

function getdatatableMultiSelectList($datatable) {
    var selectedlist = [];
    $datatable.find('input.dt-selectItem').each(function () {
        selectedlist.push($(this).val());
    });
    return selectedlist;
}

function datatableMultiSelectCountRefresh($datatable) {
    // count kontrol eder.
    var count_elem = $datatable.parents('.datatable-div').find('.datatable-selectedlist-head .ms-count');
    var selectedCount = getdatatableMultiSelectCount($datatable);
    count_elem.html(selectedCount);
}


function datatableCheckSelect($datatable, status) {
    status = status || true;
    // Seçili itemleri kontrol ederek tr leri tik ile  işaretle.
    // status:  tümünü seç ya da seç butonuna tıklanılarak aktif edilen tr leri döndürmek için.   sayfa değişikliklerinde kullanılmasın.

    var datatable_id = $datatable.attr('id');
    var datatableOBJ = $datatable.DataTable();
    var number_col_index = datatableOBJ.column('.table-col-box').index();

    datatableOBJ.rows().eq(0).each(function (index) {

        $row_id = $.trim($(datatableOBJ.row(index).node()).find('.row_id').val());
        $row_input = $(datatableOBJ.row(index).node()).find('.row_id');
        $new_select = ($datatable.find('input.dt-selectItem[value="' + $row_id + '"]').length == 1 ? true : false);
        // Default
        if ($new_select) {
            $(datatableOBJ.row(index).node()).addClass('multiselectable-selected');
            $(datatableOBJ.row(index).node()).find('td').eq(number_col_index).prepend('<i class="dt-select-check fa fa-check-circle"></i>');
            /*if (status) {
                    multiSelectableSelectChange(true, datatable_id, $(datatableOBJ.row(index).node()));
                }*/
        }

    });
    datatableMultiSelectCountRefresh($datatable);
}

function datatableReset($datatable) {
    datatableRemoveDeleteData($datatable);
    selectAllReset($datatable);
}

function datatableRemoveDeleteData($datatable) { // datatable-delete-data   ları ototmatik sil.
    $datatable.find('input.datatable-delete-data').remove();
}

function isDatatableRowSelected($datatable, $row_id) {
    // seçilen yada seçimi kaldırılan satırın değişiklik bilgisi oldu mu ? yoksa eski haline mi döndü.
    $row_id = $row_id || 0;
    $datatable = $datatable || false;
    var status = false;

    $row_input = $datatable.find('.row_id[value="' + $row_id + '"]');

    $new_select = ($datatable.find('input.dt-selectItem[value="' + $row_id + '"]').length == 1 ? true : false);

    return $new_select;
}

$(document).on("click", '.selected-table  tbody  tr:not(.not-select)', function (e) {

    /* 23.09.2022 Collapse */
    if ($(e.target).hasClass('dt-control')) return;


    if ($(this).attr("data-selectable") != undefined && $(this).attr("data-selectable") == 'false')
        return;

    var selectedTable = $(this).parents('.selected-table');
    if (selectedTable.hasClass('multiselectable-active')) {
        if ($(this).hasClass('multiselectable-selected')) {
            datatableRemoveSelect(selectedTable, $(this));
        } else {
            datatableAddSelect(selectedTable, $(this).find('.row_id').val());
        }
        return;
    }


    if (!$(this).hasClass("selected")) {
        $(this).closest('tbody').children('.selected').removeClass("selected");
        $(this).addClass("selected");
        selectedChanged(true, selectedTable.attr("id"));
        if (selectedTable.hasClass("datatable-initialized")) {
            if (selectedTable.parents('.datatable-div').find('.datatable-sortable').length === 1) {
                selectedTable.parents('.datatable-div').find('.datatable-sortable').removeClass('disabledbutton');
            }
        }
    } else {
        $(this).removeClass("selected");
        selectedChanged(false, selectedTable.attr("id"));
        if (selectedTable.hasClass("datatable-initialized")) {
            if (selectedTable.parents('.datatable-div').find('.datatable-sortable').length === 1) {
                selectedTable.parents('.datatable-div').find('.datatable-sortable').addClass('disabledbutton');
            }
        }
    }
});
// Tr çift tıklanırsa selectedChanged ve open calıstır.
$(document).on("dblclick", '.selected-table  tbody  tr:not(.not-select)', function (e) {

    /* 23.09.2022 Collapse */
    if ($(e.target).hasClass('dt-control')) return;

    $dt_id = $(this).parent().parent().attr("id");
    var selectedTable = $(this).parents('.selected-table');
    if (selectedTable.hasClass('multiselectable-active')) {
        return;
    }
    $(this).closest('tbody').children('.selected').removeClass("selected");
    $(this).addClass("selected");

    selectedChanged(true, $dt_id);
    selectedOpen($dt_id);

});

function DatatableSumFooter(api) {
    $(api.table().footer()).find("td").each(function () {

        if ($(this).data('sum') == 1) {
            $dtSum = (api.column($(this).data('colindex'), {page: 'current'}).data().sum()).toFixed($(this).data('tofixed') == undefined ? 2 : $(this).data('tofixed'));
            $dtUnit = $(this).data('unit') == undefined ? '' : $(this).data('unit');
            $(this).html(
                $dtSum + $dtUnit
            );
        }
    });
}

$(document).on("click", '.datatable tr td', function () {
    $(this).parents('.dataTables_wrapper').find('.col-panel').remove();
});
$(document).on('click', '.table-col-box', function () {

    $datatable_obj = $(this).parents('.datatable').DataTable();
    $checkBoxsCols = "";
    var dt_div = $(this).parents('.datatable-div');
    var dt_wrapper = $(this).parents('.dataTables_wrapper');
    var thead = $(this).parents('thead');

    if (thead.find('.col-panel').length) {
        thead.find('.col-panel').remove();
        dt_div.css('height', '');
        dt_wrapper.css('height', '');
    } else {
        $taleId = $(this).parents('.datatable').attr("id");
        //alert($taleId);

        $col_array = [];

        $datatable_obj.columns().every(function () {
            // Column data
            if (this.visible()) {
                $checked = "checked";
            } else {
                $checked = "";
            }
            if ($(this.header()).data('visibility') != undefined && $(this.header()).data('visibility') != '-1' && $(this.header()).data('visibility') != '2') {

                // $name = '<input data-tableid="' + $taleId + '" class="toggle-col-vis buttons-columnVisibility" type="checkbox" data-colname="' + $(this.header()).data('name') + '" data-colindex="' + this.index() + '" ' + $checked + '>&nbsp;' + $(this.header()).text();
                $dtCheckbox = '<div class="dtbl-control dtbl-checkbox"><input type="checkbox" id="dtCheck' + this.index() + '" data-tableid="' + $taleId + '" class="toggle-col-vis buttons-columnVisibility dtbl-control-input" type="checkbox" data-colname="' + $(this.header()).data('name') + '" data-colindex="' + this.index() + '" ' + $checked + '><label class="dtbl-control-label" for="dtCheck' + this.index() + '">' + $(this.header()).text() + '</label></div>';
                $col_array.push({
                    'name': $(this.header()).text(),
                    'html': "<div class='col-md-6'>" + $dtCheckbox + "</div>",
                });
            }
        });
        $col_array = $col_array.sort(function (a, b) {
            return a.name.localeCompare(b.name);
        });

        $.each($col_array, function (key, value) {
            $checkBoxsCols += value.html;
        });

        thead.append('<div class="col-panel">' + $checkBoxsCols + '</div>');
        elementReady();


        var total_height = thead.height() + dt_wrapper.find('.dataTables_length').height() + thead.find('.col-panel').height() + 40;


        if (total_height > dt_div.height()) {
            dt_div.css('height', total_height + 'px');
        }
        if (total_height > dt_wrapper.height()) {
            dt_wrapper.css('height', total_height + 'px');
        }

    }
});
$(document).on('change', '.toggle-col-vis', function (event) {
// $('.toggle-col-vis').on('ifChanged', function (event) {

    var checked = event.target.checked ? 1 : 0;
    var index = $(this).data('colindex');

    if (checked) {
        showDatatableColumn($datatable_obj, index);
    } else {
        $datatable_obj.column(index).visible(false);
    }

    var dt_div = $(this).parents('datatable-div');
    dt_div.css('height', '');

    $(this).parents('.col-panel').remove();

});

function showDatatableColumn($datatable_obj, index) {
    $datatable_obj.column(index).visible(true);
    DatatableSumFooter($datatable_obj);
}

$(document).on('mouseover', '.datatableFilterButton', function () {
    $datatable = $(this).parent().find('.datatable');

    if (!$datatable.find('thead .trFilter').is(':visible')) {
        $(this).text(DATATABLES_BTN_SEARCH);
    } else {
        $(this).text(DATATABLES_BTN_CLEAR);
    }
});
$(document).on('mouseout', '.datatableFilterButton', function () {
    $(this).text('');
});
$(document).on('click', '.datatableFilterButton', function () {
    /*$datatable = $(this).parent().find('.datatable');
    if ($datatable.find('thead .trFilter').hasClass('d-none')) {
        //HideColControl($datatable_obj,$datatableCols);
        $datatable.find('thead .trFilter').removeClass('d-none');
        $(this).text(' Filtre Temizle');

    }
    else {
        $(this).text(' Filtrele');*/
    $datatable_obj = $datatable.DataTable();
    $datatable_obj.search('').columns().search('').draw();
    $datatable.find('thead .trFilter input:not(.datatable-pass-data)').val('');
    //$datatable.find('thead .trFilter select:not(.datatable-pass-data)').val($datatable.find('thead .trFilter select:not(.datatable-pass-data) option').eq(0).val());
    $datatable.find('thead .trFilter select:not(.datatable-pass-data)').val('');
    // $datatable.find('thead .trFilter').addClass('d-none');
    /*}*/
});
$(document).on('click', '.datatable-sortable button', function () {
    $datatable = $(this).parents('.datatable-div').find('.datatable');
    var order_type = ($(this).hasClass('order_up') ? '-1' : '1');
    var id = 0;
    var sortAction = ($datatable.attr('data-sortAction') !== undefined && $datatable.attr('data-sortAction') !== null && $datatable.attr('data-sortAction') !== '') ? $datatable.attr('data-sortAction') : false;
    var sortColumn = ($datatable.attr('data-sortColumn') !== undefined && $datatable.attr('data-sortColumn') !== null && $datatable.attr('data-sortColumn') !== '') ? $datatable.attr('data-sortColumn') : false;


    if ($datatable.find('tr.selected td').first().find('.row_id').length === 1) {
        id = $datatable.find('tr.selected td').first().find('.row_id').val();
    }

    if (sortAction && id > 0) {

        $datatable_drawed = $datatable.DataTable();
        $.ajax({
            dataType: 'json',
            type: 'POST',
            url: sortAction,
            data: {
                id: id,
                order_type: order_type
            },
            success: function (response) {
                if (response.result == 'ok' || response.result == 'reload') {
                    if (sortColumn) {
                        $datatable.find('.trFilter td').first().append('<input type="hidden" class="datatable-pass-data" name="datatable_order" value="' + sortColumn + '"/>');
                    }
                    $datatable_drawed.draw();
                    if (sortColumn) {
                        $datatable.find('.trFilter td input[name="datatable_order"]').remove();
                    }
                } else {
                    TopMessageShow(true, response.message);
                }
            }
        });
    }
});

// Tümünü Seç butonu
$(document).on('click', '.btn-dt-select-all', function () {
    $datatable = $(this).parents('.datatable-div').find('.datatable');
    var selectStatus = $datatable.find('input.dt-allItemSelected').val() == "1";

    selectAllSetStatus($datatable, !selectStatus);
    if (isMultiSelectableMode($datatable)) {
        var def_action = $datatable.attr('data-action');
        $.fn.dataTable.ext.errMode = 'throw';
        $datatable.DataTable().ajax.url('app/datatableMultipleSelect/selectAllChange');
        $datatable.DataTable().ajax.reload();
        $.fn.dataTable.ext.errMode = '';
        $datatable.DataTable().ajax.url(def_action);
        $datatable.DataTable().ajax.reload();
    }
});


function selectAllSetStatus($datatable, status) {
    var buttonDtSAll = $datatable.parents('.datatable-div').find('.btn-dt-select-all');
    if (status != 1) {
        // Tümünü seç iptal edildi.
        buttonDtSAll.html('Tümünü Seç');
        buttonDtSAll.removeClass('allItemSelected');
        buttonDtSAll.removeClass('btn-danger').addClass('btn-success');
        $datatable.find('input.dt-allItemSelected').val(0);
        // multiSelectableSelectChange(false, $datatable.attr('id'), null);
    } else {
        // Tümünü seç  yapıldı
        buttonDtSAll.html('Tümünü Bırak');
        buttonDtSAll.addClass('allItemSelected');
        buttonDtSAll.removeClass('btn-success').addClass('btn-danger');
        $datatable.find('input.dt-allItemSelected').val(1);
    }

}

function selectAllReset($datatable) {
    selectAllSetStatus($datatable, 0);
    datatableMultiSelectCountRefresh($datatable);
}

function isMultiSelectableMode($datatable) {
    var status = false;
    if ($datatable !== '' && $datatable !== null && $datatable.attr('data-multiselectable') == 'true') {
        status = true;
    }
    return status;
}


function isMultiSelectableStatus($datatable) {
    var $multiSelectButton = $datatable.parents('.datatable-div').find('.btn-dt-multiselectable');
    var status = false;
    if ($multiSelectButton !== '' && $multiSelectButton !== null && $multiSelectButton != "undefined" && $multiSelectButton.hasClass('active')) {
        status = true;
    }
    return status;
}

// Seçmek için tr ye tıklanırsa fonksiyona veri gönder.
function multiSelectableSelectChange($status, $datatable_id, $tr) {
    if (typeof multiSelectableSelectChanged === "function") {
        multiSelectableSelectChanged($status, $datatable_id, $tr);
    }
}

// Select Modu aktif/pasiflik
function multiSelectableControl(status, datatable_id) {
    var status = status || false;
    if (typeof multiSelectableActive === "function") {
        multiSelectableActive(status, datatable_id);
    }
}

// Seç/iptal butonu
$(document).on('click', '.btn-dt-multiselectable', function () {
    $datatable = $(this).parents('.datatable-div').find('.datatable');
    if ($datatable.hasClass('multiselectable-active')) {
        cancelDatatableMultiSelect($datatable);

    } else {
        $datatable.addClass('multiselectable-active');
        multiSelectableControl(true, $datatable.attr('id'));
        $(this).addClass('active');
        $(this).html('İptal');
        $datatable.parents('.datatable-div').find('.datatable-selectedlist').show();
        $datatable.parents('.datatable-div').find('.btn-dt-select-all').show();
        datatableCheckSelect($datatable);
        $datatable.find('.selected').removeClass('selected');
        selectedChanged(false, $datatable.attr("id"));
    }
});

function cancelDatatableMultiSelect($datatable) {
    $datatable.removeClass('multiselectable-active');
    multiSelectableControl(false, $datatable.attr('id'));
    $datatable.parents('.datatable-div').find('.btn-dt-multiselectable').html('Seç');
    $datatable.parents('.datatable-div').find('.btn-dt-multiselectable').removeClass('active');
    $datatable.parents('.datatable-div').find('.datatable-selectedlist').hide();
    $datatable.parents('.datatable-div').find('.btn-dt-select-all').hide();
    $datatable.find('input.dt-allItemSelected').val(0);
    $datatable.parents('.datatable-div').find('.btn-dt-multiselectable').parents('.datatable-div').find('.btn-dt-select-all').html('Tümünü Seç');
    $datatable.parents('.datatable-div').find('.btn-dt-multiselectable').parents('.datatable-div').find('.btn-dt-select-all').removeClass('btn-red').removeClass('allItemSelected').addClass('btn-green');
    datatableRemoveAllSelect($datatable);
    datatableMultiSelectCountRefresh($datatable);
}

$(document).on('mouseover', '.datatableExcelButton', function () {
    $(this).text(DATATABLES_BTN_EXCEL_OUTPUT);
});
$(document).on('mouseout', '.datatableExcelButton', function () {
    $(this).text('');
});
$(document).on('click', '.datatableExcelButton', function () {
    datatableExcelButtonLoadingEnable();
    var datatable = $(this).parent().find('.datatable');
    var def_action = datatable.attr('data-action');

    $.fn.dataTable.ext.errMode = 'throw';
    datatable.DataTable().ajax.url('actions/datatableToExcel.php');

    datatable.DataTable().ajax.reload();


    $.fn.dataTable.ext.errMode = '';
    datatable.DataTable().ajax.url(def_action);
    datatable.DataTable().ajax.reload();
});

function datatableExcelButtonLoadingEnable() {
    $('.datatableExcelButton').addClass('disabledbutton');
    setTimeout(function () {
        $('.datatableExcelButton').html('<span id="excelSpinner" class="fa fa-spinner fa-spin"></span>');
        $('.datatableExcelButton').removeClass('fa fa-file-excel-o');
        spinnerHide();
    }, 100);

}

function datatableExcelButtonLoadingDisable() {

    $('.datatableExcelButton').removeClass('disabledbutton');

    $('#excelSpinner').remove();
    $('.datatableExcelButton').addClass('fa fa-file-excel-o');

}

function datatableExcelSuccess(json) {
    datatableExcelButtonLoadingDisable();
    setTimeout(function () {
        window.location.href = json.excel_url;
    }, 20);
    showModalMessage("İşlem başarılı lütfen bekleyin", false);
}

function datatableExcelError(error_text) {
    datatableExcelButtonLoadingDisable();
    showModalMessage(error_text, true)
}

function datatableSelectAjaxResponse($datatable_id, $result, $message, $json) {
    $result = ($result === true || $result === 'ok' ? true : false);
    $message = $message || '';
    $json = $json || null;
    if ($result) {
        $datatable_id = $datatable_id || false;
        $datatable = $('#' + $datatable_id);
        if ($json.data && $json.data.selected_list) {
            datatableSetSelectList($datatable, $json.data.selected_list);
        } else {
            showMessage('Veri bulunamadı', true);
        }
    } else {
        showMessage('Seçim sırasında tanmlanmamış hata oluştu', true);
    }
    return false;
}

function datatableSetSelectList($datatable, _selected_list) {
    datatableRemoveAllSelect($datatable);

    $.each(_selected_list, function (key, val) {

        if (val) {
            // $datatable.find('.trFilter td').first().append('<input type="hidden" class="datatable-pass-data datatable-delete-data dt-selectItem" name="dt-selectItem-' + val + '" value="' + val + '"/>');
            datatableAddSelect($datatable, val);
        }
    });
    multiSelectableSelectChange(null, $datatable.attr('id'), null);
}


function multipleSelectionsCreateSession($datatable) {
    var result_id = false;
    $.ajax({
        dataType: 'json',
        type: 'POST',
        async: false,
        url: 'app/datatableMultipleSelect/createSession',
        data: {
            selected_list: getdatatableMultiSelectList($datatable),
        },
        success: function (response) {
            if (response.result == 'ok') {
                result_id = response.data.id;
            } else {
                TopMessageShow(true, response.message);
            }
        }
    });
    return result_id;
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
* DATATABLE MOBILE FILTER
* İLK DÜZENLEME : 01.02.2022
* SON DÜZENLEME : 20.02.2024 bootstrap 5 için güncellendi
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * **/

function InitalizeDatatableFilters(table = undefined) {

    if (typeof table === 'undefined') {
        $.each($('.dt-filter-td'), function (key, val) {
            if (!$(val).closest('.datatable').attr('mobile-filter')) return;
            if (key == 0)
                $(val).closest('tr.trFilter').find('td').eq(0).append(`
                    <div class='d-flex mobile-mini-bar'>
                    <div class='me-1 dt-mobile-filter btn btn-icon btn-sm btn-secondary' data-toggle="tooltip" title="${DATATABLES_BTN_SEARCH}"><i class="fas fa-search"></i></div>
                    <div class='me-1 dt-datatableFilterButton btn btn-icon btn-sm btn-info' data-toggle="tooltip" title="${DATATABLES_BTN_CLEAR}"><i class="fas fa-trash"></i></div>
                    <div class='me-1 dt-datatableExcelButton btn btn-icon btn-sm btn-success' data-toggle="tooltip" title="${DATATABLES_BTN_EXCEL_OUTPUT}"><i class="fas fa-file-excel"></i></div>
                    </div>`);
            $(val).addClass('d-none');
        });
    } else {

        $.each($(`${table} .dt-filter-td`), function (key, val) {
            if (!$(val).closest('.datatable').attr('mobile-filter')) return;

            var bExcel = ($(val).closest('.datatable').attr('data-bExcel') !== undefined &&
                $(val).closest('.datatable').attr('data-bExcel') !== null &&
                $(val).closest('.datatable').attr('data-bExcel') !== '' &&
                ($(val).closest('.datatable').attr('data-bExcel') === 'false' || $(val).closest('.datatable').attr('data-bExcel') === '0')) ? false : true;
            var excelButtonHtml = ``;
            if (bExcel) {
                excelButtonHtml += `<div class='mr-1 dt-datatableExcelButton btn btn-sm btn-success' data-toggle="tooltip" title="${DATATABLES_BTN_EXCEL_OUTPUT}"><i class="fas fa-file-excel"></i></div>`;
            }
            if (key == 0) {
                if ($(val).closest('tr.trFilter').find('td').eq(0).find('.mobile-mini-bar')) {
                    $(val).closest('tr.trFilter').find('td').eq(0).find('.mobile-mini-bar').remove();
                }
                $(val).closest('tr.trFilter').find('td').eq(0).append(`
                    <div class='d-flex mobile-mini-bar'>
                    <div class='mr-1 dt-mobile-filter btn btn-sm btn-secondary' data-toggle="tooltip" title="${DATATABLES_BTN_SEARCH}"><i class="fas fa-search"></i></div>
                    <div class='mr-1 dt-datatableFilterButton btn btn-sm btn-info' data-toggle="tooltip" title="${DATATABLES_BTN_CLEAR}"><i class="fas fa-trash"></i></div>
                    ${excelButtonHtml}
                    </div>`);
            }
            $(val).addClass('d-none');
        });
    }
    $('body').append(`
    <div class="modal fade" id="datatable-search" style="display: none;" aria-hidden="true">
    <input id="dt-search-dt-id" value="" type="hidden">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">${DATATABLES_BTN_SEARCH}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <thead><tr><th>#</th><th>${DATATABLES_BTN_SEARCH}</th></tr></thead>
                    <tbody id="dt-search-body"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="filterSelectedButton" onclick="filterSelectedTable()">${DATATABLES_BTN_SEARCH}</button>
            </div>
        </div>
        <!-- /.modal-content -->
        </div>
            <!-- /.modal-dialog -->
        </div>
        `);

}

$(document).on('click', '.dt-mobile-filter', function () {
    var dataTable = $(this).closest('table').DataTable().table().header();
    if (dataTable == null) dataTable = $(this).closest('table').parents().find('.dataTables_scroll').find('.dataTables_scrollBody').find('.table').DataTable().table().header();
    var theads = $(dataTable).find('tr').eq(1);

    var table_id = $(dataTable).parent().attr('id');
    if (table_id == null) table_id = $($(dataTable).parents().find('.dataTables_scroll').find('.dataTables_scrollBody').find('.table')).attr('id');
    $('#dt-search-dt-id').val(table_id);
    $('#datatable-search').modal('show');
    var modal_content = $('#dt-search-body');
    modal_content.html('');
    var last_index = 1;
    $.each($('.dt-filter-td', $(dataTable).find('tr').eq(0)), function (key, val) {
        var tr = $("<tr>");
        var td_name = $("<td>");
        var td_content = $("<td class='dt-filter-content' filter-id=''>");


        tr.append(td_name);
        tr.append(td_content);

        var text_index = $(val).parent().attr("data-colindex"); //2
        if ($(val).parent().attr("data-colindex") - last_index > 1) text_index = ++last_index;
        $(td_name).html($(theads).find('th').eq(text_index).text());
        if ($(val).parent().attr("data-colindex") - last_index <= 1) last_index = $(val).parent().attr("data-colindex");
        $(td_content).attr('filter-id', text_index);

        $(val).appendTo(td_content);
        $(val).removeClass('d-none');
        if ($(val).hasClass('dt-select2-filter')) {
            $(val).select2();
        }
        modal_content.append(tr);
    });
});

$(document).keypress(function (e) {
    if ($('#datatable-search').hasClass('show') && (e.keyCode == 13 || e.which == 13)) {
        $('#filterSelectedButton').click();
    }
});

$(document).on('click', '.dt-datatableFilterButton', function () {
    $this_datatable = $(this).closest('.datatable');
    $this_datatable.DataTable().search('').columns().search('').draw();
    // $datatable_obj = $datatable.DataTable();
    // $datatable_obj.search('').columns().search('').draw();
    $this_datatable.find('thead .trFilter input:not(.datatable-pass-data)').val('');
    $this_datatable.find('thead .trFilter select:not(.datatable-pass-data)').val('');
});


$(document).on('click', '.dt-datatableExcelButton', function () {
    datatableExcelButtonLoadingEnable();
    spinnerShow();
    var datatable = $(this).parents().eq(7);
    var def_action = datatable.find('.datatable-initialized').attr('data-action');
    datatable = datatable.find('.datatable-initialized');
    $.fn.dataTable.ext.errMode = 'throw';
    datatable.DataTable().ajax.url('actions/datatableToExcel.php');

    datatable.DataTable().ajax.reload();

    $.fn.dataTable.ext.errMode = '';
    datatable.DataTable().ajax.url(def_action);
    datatable.DataTable().ajax.reload();
});

function filterSelectedTable() {

    var datatable = $(`#${$('#dt-search-dt-id').val()}`).DataTable();
    var table = $(`#${$('#dt-search-dt-id').val()}`).DataTable().table();

    $.each($('.dt-filter-content'), function (key, val) {
        var dataItem = $(val).find('.dt-filter-td');
        var dataIdx = table.column.index('fromVisible', key + 1);
        datatable.column(dataIdx).search($(dataItem).val());
    });
    datatable.draw();
    $('#datatable-search').modal('hide');
}

$(document).on('hidden.bs.modal', '#datatable-search', function () {
    var datatable = $($(`#${$('#dt-search-dt-id').val()}`).DataTable().table().header());
    var filters = datatable.find('tr').eq(0);
    $.each($('.dt-filter-content'), function (key, val) {
        var dataItem = $(val).find('.dt-filter-td');
        dataItem.appendTo(filters.find('td').eq($(val).attr('filter-id')));
        if (!$(dataItem).hasClass('d-none')) {
            $(dataItem).addClass('d-none');
        }
    });

});


/* DATATABLE MOBILE FILTER END */

/*
* İŞE YARAR FONKİSYONLAR
* */

function DTGetSelectedRows(datatable) {
    return $(datatable).find('tr.selected');
}

function DTReload(datatable) {
    $(datatable).DataTable().ajax.reload();
}
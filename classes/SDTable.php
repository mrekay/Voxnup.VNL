<?php
/* SDTable v1.0.1*/
/* created by Ertuğrul*/
/* update date 27.02.2024*/
class SDTable
{
    public static function CreateDatable($url, $columns, $filters, $options = [], $passdata = [])
    {

        if (empty($options['dt-id'])) throwErrorMessage("option['dt-id'] (tablo html id'sini) doldurun");

        return Self::CreateDTHTML($url, $columns, $filters, $options, $passdata);
    }

    private static function CreateDTHTML($url, $columns, $filters, $options, $passdata)
    {

        $default_options = [
            'width' => '100%',
            'cellspacing' => 0,
            'dt-class' => 'selected-table'
        ];
        $options = array_merge($default_options, $options);

        $datatable_html = '                
                <!-- Datatable start -->
                <div class="datatable-div">
                    <div class="table-responsive">
                        <table id="' . $options["dt-id"] . '" class="' . $options['dt-class'] . ' table display datatable"
                               ' . self::CreateDTOptions($options) . '
                               data-action="' . $url . '">
                            <thead>
                            <tr class="trFilter">
                                ' . self::CreateDTFilters($filters, $passdata) . '
                            </tr>
                            <tr>
                                ' . self::CreateDTColumns($columns) . '
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <!-- Datatable End -->';

        return $datatable_html;
    }

    private static function CreateDTOptions($options)
    {
        $returnHTML = "";
        foreach ($options as $key => $option) {
            $returnHTML .= $key . "='" . $option . "' ";
        }
        return $returnHTML;
    }

    private static function CreateDTFilters($filters, $passdata)
    {
        $filter_id = 0;
        $returnHTML = "";

        $filter_default = [
            'class' => '',
            'id' => '',
            'name' => '',
            'placeholder' => '',
            'value' => '',
            'type' => '',
        ];


        /* pass data oluşturma */
        $passdata_html = "";
        if (!empty($passdata)) {
            foreach ($passdata as $passdatum) {
                $passdata_html .= '<input type="hidden" class="datatable-pass-data" id="' . $passdatum['id'] . '"
                                           name="' . $passdatum['name'] . '" value="' . $passdatum['value'] . '"/>';
            }
        }
        $returnHTML .= "<td data-colindex='" . $filter_id++ . "'>" . $passdata_html . "</td>";


        foreach ($filters as $filter) {
            $filter_html = "";

            $filter = array_merge($filter_default, $filter);
            if (empty($filter['filter_type'])) throwErrorMessage("filters['filter_type'] alanını doldurun");


            if ($filter["filter_type"] == "input") {
                $filter_html .= "<input class='" . $filter["class"] . " dt-filter-td form-control form-control-sm' id='" . $filter['id'] . "' name='" . $filter['name'] . "-filter' placeholder='" . ($filter['placeholder'] ?? 'Filtre yazınız') . "' value='" . $filter['value'] . "' type='" . $filter['type'] . "'>";
            } else if ($filter["filter_type"] == "select") {
                $filter_html .= "<select class='" . $filter["class"] . " dt-filter-td form-control form-control-sm' id='" . $filter['id'] . "' name='" . $filter['name'] . "-filter' value='" . $filter['value'] . "' type='" . $filter['type'] . "'>";
                if (!empty($filter['options'])) {
                    foreach ($filter['options'] as $option) {
                        $filter_html .= "<option value='" . $option['value'] . "' >" . $option['text'] . "</option>";
                    }
                }
                $filter_html .= "</select>";
            }

            $returnHTML .= "<td data-colindex='" . $filter_id++ . "'>" . $filter_html . "</td>";
        }

        return $returnHTML;
    }

    private static function CreateDTColumns($columns)
    {
        $returnHTML = "";

        $columns_default = [
            ['data-visibility' => 1, 'data-orderable' => 0, 'data-name' => 'id', 'data-visible-name' => '<i class="fas fa-cog"></i>']
        ];

        $columns = array_merge($columns_default,$columns);

        $column_default = [
            'data-orderable' => 1,
            'data-visibilty' => 1,
            'data-name' => '',
            'data-visible-name' => ''
        ];

        foreach ($columns as $column) {

            $column = array_merge($column_default, $column);

            $returnHTML .= "<th data-visibility='" . $column['data-visibilty'] . "' data-orderable='" . $column['data-orderable'] . "' data-name='" . $column['data-name'] . "'>" . $column['data-visible-name'] . "</th>";
        }

        return $returnHTML;
    }
}
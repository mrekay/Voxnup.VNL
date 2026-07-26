<?php

require_once('../libs/config.php');
// sabit olarak php excel modunda olduğunu php'ye belirtiyoruz
define('PHPEXCELMODE',true);
//if (Session::checkSession() != 1) {
//	returnAjaxError('redirect', '', array('url' => 'login'));
//	die();
//}


$default_action = trim($req->post('default_action'));

$action_temp = explode('/', $default_action);
//$action_temp = end($action_temp);
$action_end = explode('?', end($action_temp));
$get_temp = $action_end[0];
$get = null;

if (str_contains("&", $get_temp)) {
    foreach (explode('&', $get_temp) as $item) {
        $key = explode('=', $item)[0];
        $value = explode('=', $item)[1];
        $get[$key] = $value;
    }
}

$action_path = $action_temp[1];
$action = $action_end[0];


$tableCols = $req->post('table_colums');

$post = $_POST;

$post['length'] = -1;


ob_start();

$_GET = $get;
$_POST = $post;

include 'action_' . $action_path . '.php';
$class_name = 'action_' . $action_path;
$action_class = new $class_name;
require getDocumentRoot()."actions/Auth.php";
if (method_exists($action_class, $action)) {
    call_user_func([$action_class, $action],$req);
}
$content = ob_get_clean();

$jsonObj = (array)json_decode($content, true);
$jsonArray = (array)$jsonObj['data'];

require_once getDocumentRoot() . 'libs/excel/Classes/PHPExcel/IOFactory.php';
$objPHPExcel = PHPExcel_IOFactory::load(getDocumentRoot() . 'files/template/GenelDataTable.xlsx');
PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
$objPHPExcel->setActiveSheetIndex(0);

$key = 1;
$harf = 'A';
$addedHead = false;
$Page1Style1 = 'A1';
$Page1Style2 = 'A2';

foreach ($jsonArray as $item) {
    foreach ($tableCols as $t_col) {

        if (!isset($item[$t_col['name']]))
            $item[$t_col['name']] = '';
        $item[$t_col['name']] = strip_tags($item[$t_col['name']]);
        $t_col['html'] = trim(strip_tags(html_entity_decode($t_col['html'])));
        if (!$addedHead) {
            $objPHPExcel->getActiveSheet()->duplicateStyle($objPHPExcel->getActiveSheet()->getStyle($Page1Style1), $harf . $key);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit($harf . $key, $t_col['html'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->duplicateStyle($objPHPExcel->getActiveSheet()->getStyle($Page1Style2), $harf . ($key + 1));
            $objPHPExcel->getActiveSheet()->setCellValueExplicit($harf . ($key + 1), html_entity_decode($item[$t_col['name']]), PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->getColumnDimension($harf)->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getStyle($harf . ($key + 1))->getAlignment()->setWrapText(true);
        } else {
            $objPHPExcel->getActiveSheet()->duplicateStyle($objPHPExcel->getActiveSheet()->getStyle($Page1Style2), $harf . $key);
            $objPHPExcel->getActiveSheet()->setCellValueExplicit($harf . $key, html_entity_decode($item[$t_col['name']]), PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->getStyle($harf . $key)->getAlignment()->setWrapText(true);
        }


        $harf++;
    }

    if (!$addedHead) {
        $key++;
        $addedHead = true;
    }

    $harf = 'A';
    $key++;
}

if (!file_exists('../files/datatable')) {
    mkdir('../files/datatable', 0777, true);
}


$file_folder = '/files/datatable/' . $CurrentUser->unique_text;
if (!file_exists('..' . $file_folder)) {
    mkdir('..' . $file_folder, 0777, true);
}

$full_file_path = '';

do {
    $file_i = rand(1000, 10000);
    $file_i2 = rand(1000, 10000);
    $file_path = TextGeneralModule::SafeURL($CurrentUser->name . $CurrentUser->surname) . '_' . $file_i . $file_i2 . '.xlsx';

    $full_file_path = $file_folder . '/' . $file_path;
    $file_i++;
} while (file_exists('..' . $full_file_path));

$directory = '..' . $file_folder . "/";
$filecount = 0;
$files = glob($directory . "*");
if ($files) {
    $filecount = count($files);

    //print_r( );
}

foreach ($files as $item) {

    if (date("Y-m-d H:i:s", (filemtime($item) + 30)) < date("Y-m-d H:i:s"))
        unlink($item);
}


$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
// Write the Excel file to filename some_excel_file.xlsx in the current directory
$objWriter->save('../' . $full_file_path);

$objPHPExcel->disconnectWorksheets();
unset($objWriter, $objPHPExcel);

$message = lang('SUC_EXPORT');
$cclass = 'success';
$result = 'ok';
$url = SITE_URL . $full_file_path;


if (1) {
    $message = lang('SUC_GENERAL');
    $cclass = 'success';
    $result = 'ok';
} else {
    $message = lang('ERR_GENERAL');
    $cclass = 'danger';
    $result = 'fail';
}

$json_data = array(
    //"draw"            => 2,
    "excel_message" => $message,
    "excel_cclass" => $cclass,
    "excel_result" => $result,
    "excel_url" => $url,
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => [],

);
$json_data = array_merge($json_data, $jsonObj);

$json = json_encode($json_data);
echo $json;






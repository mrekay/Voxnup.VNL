<?php
error_reporting(E_ALL);//tüm hata gösterimleri açılıyor.
set_time_limit(0);//max_execution_time değeri olabilecek en üst değere getirliyor
function ExcelRead($file_name)
{
    require_once 'Classes/PHPExcel/IOFactory.php';//kullandığımız kütüphane
    $inputFileName = $file_name;//işlenecek dosya
    $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
    $excel_satirlar = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);//excel dosyasındaki aktif sekme kullanılıyor
    return $excel_satirlar;
}

function ExcelWrite($file_name,$array,$started_index)
{
    require_once 'Classes/PHPExcel.php';
    require_once 'Classes/PHPExcel/IOFactory.php';

    $objPHPExcel = PHPExcel_IOFactory::load($file_name);
    $objPHPExcel->setActiveSheetIndex(0);
    if($started_index)
    $row = $started_index;
    //echo $row;
    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Serhat');
    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, 'İbrahim');
    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, 'ğüışöçĞÖÜİŞÇ');
    $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
    $objWriter->save($file_name);
}

function ExcelLastRow($file_name){
    require_once 'Classes/PHPExcel.php';
    require_once 'Classes/PHPExcel/IOFactory.php';

    $objPHPExcel = PHPExcel_IOFactory::load($file_name);
    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getActiveSheet()->getHighestRow();
}
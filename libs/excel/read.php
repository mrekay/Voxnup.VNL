<?php
error_reporting(E_ALL);//tüm hata gösterimleri açılıyor.
set_time_limit(0);//max_execution_time değeri olabilecek en üst değere getirliyor
 
include 'Classes/PHPExcel/IOFactory.php';//kullandığımız kütüphane
$inputFileName = 'test2.xlsx';//işlenecek dosya
$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
$excel_satirlar = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);//excel dosyasındaki aktif sekme kullanılıyor
 
$i=0;
foreach($excel_satirlar as $excel_satir){
    $i++;
 
    $veri_1 = $excel_satir['A'];
    $veri_2 = $excel_satir['B'];
    $veri_3 = $excel_satir['C'];
    $veri_4 = $excel_satir['D'];
    $veri_5 = $excel_satir['E'];
 
    echo $veri_1;
    }


?>
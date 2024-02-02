
<?php 
/** Include PHPExcel */
require_once 'Classes/PHPExcel.php';
require_once 'Classes/PHPExcel/IOFactory.php';

$objPHPExcel = PHPExcel_IOFactory::load("test2.xlsx");
$objPHPExcel->setActiveSheetIndex(0);
$row = $objPHPExcel->getActiveSheet()->getHighestRow()+1;
//echo $row;
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, 'Serhat');
$objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, 'İbrahim');
$objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, 'ğüışöçĞÖÜİŞÇ');
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save('test2.xlsx');
header('location:test2.xlsx');
?>


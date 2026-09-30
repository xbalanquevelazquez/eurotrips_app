<?php
session_start();
/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');//Sólo para browser

define("VIEWABLE",true);

require_once('../cnf/cnfg.app.php');
include_once(FUNCT_PATH."funcionalidad.php");
require LIB_PATH."/Excel_PHPSpreadsheet/vendor/autoload.php";


$config_file = '../cnf/cnfg.app.php';
	
if(file_exists($config_file)){
	require_once($config_file);
}else{
	echo '<div class="error">Hay un problema al adjuntar el archivo: '.$config_file.'</div>';
}
/**
 * PHPExcel
 *
 * Copyright (C) 2006 - 2014 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2014 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.8.0, 2014-03-02
 */


if (PHP_SAPI == 'cli')
	die('This example should only be run from a Web Browser');

date_default_timezone_set('America/Mexico_City');

/** Include PHPExcel */
#require_once( $CFG->dirroot."/".EVAL_URL . 'libs/PHPExcel-1.8/PHPExcel.php' );
#require_once( $CFG->dirroot."/".EVAL_URL . 'libs/PHPExcel-1.8/PHPExcel/IOFactory.php' );

$filepath = APP_PATH .'sys_reports/';
$templatepath = $filepath.'template/';
$fechareporte = date("Y-m-d_H-i-s");

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

$year = '';
$trip_id = '';

if(isset($_GET['trip_id']) && !empty($_GET['trip_id'])){
		
	$trip_id = $_GET['trip_id'];
	$trip_data = $Admin->getTrip($trip_id);

	if(isset($trip_data['message'])){
		die('Trip data error: '.$trip_data['message']);
	}
	
	$filenameReporte = 'Reporte-Eurotrips_travelers_'.$trip_data['trip_name'].'_'.$fechareporte.'.xlsx';
	
	$inputFileType = 'Xlsx';
	$inputFileName = $templatepath.'_template-Eurotrips_travelers.xlsx';
	//CARGA
	//$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
	//OR
	$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
	$spreadsheet = $reader->load($inputFileName);
	//Cuando quiera uno NUEVO:
	//$spreadsheet = new Spreadsheet();
	$spreadsheet->getProperties()->setCreator("Xbalanqué Velázquez")
								 ->setLastModifiedBy("Xbalanqué Velázquez")
								 ->setTitle("Reporte Eurotrips travelers")
								 ->setSubject("Reporte")
								 ->setDescription("Reporte")
								 ->setKeywords("reporte excel")
								 ->setCategory("Reporte");

	$sheet = $spreadsheet->getActiveSheet();

	include_once("excel-generator.inc.php");

	/*$sheet->setCellValue('A3', 'Hello World !');
	*/
	/*
	$writer = new Xlsx($spreadsheet);
	$writer->save('hello world.xlsx');
	*/

	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="'.$filenameReporte.'"');
	header('Cache-Control: max-age=0');
	// If you're serving to IE 9, then the following may be needed
	header('Cache-Control: max-age=1');

	// If you're serving to IE over SSL, then the following may be needed
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
	header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header('Pragma: public'); // HTTP/1.0

	$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
	$writer->save($filenameReporte);
	$writer->save('php://output');

	/*
	$reader = IOFactory::createReader($inputFileType);
	$spreadsheet = $reader->load($inputFileName);

	#$objPHPExcel = new PHPExcel();

	include_once("excel-generator.inc.php");

	$fechareporte = date("Y-m-d_H-i-s");
	$filenameReporte = 'Reporte-biossmann_'.$fechareporte.'.xlsx';//__FILE__



	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="01simple.xls"');
	header('Cache-Control: max-age=0');
	// If you're serving to IE 9, then the following may be needed
	header('Cache-Control: max-age=1');

	// If you're serving to IE over SSL, then the following may be needed
	header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header ('Pragma: public'); // HTTP/1.0

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');
	exit;



	#header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	#header('Content-Disposition: attachment;filename="'.$filenameReporte.'"');

	// If you're serving to IE 9, then the following may be needed
	#header('Cache-Control: max-age=1');

	// If you're serving to IE over SSL, then the following may be needed

	#$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	#$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

	#header('Content-type: application/vnd.ms-excel');
	#header('Content-Disposition: attachment; filename="' . $filenameReporte . '"');
	/*
	header('Cache-Control: max-age=0');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header('Pragma: public'); // HTTP/1.0

	$objWriter->save('php://output');
	*/
	     /*   $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="filename'.date('d-m-y_H-i-s').'.xls"');
	header('Cache-Control: max-age=0');

	$objWriter->save('php://output');
			#header('Content-Type: application/vnd.ms-excel');
	       
	       # header('Cache-Control: max-age=0');

	        //$objWriter->save('php://output');
	exit;*/


}else{//NO $_GET['group']
	die('Error: no se especificó correctamente el grupo.');
}


<?php

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
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2014 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.8.0, 2014-03-02
 */

/** Error reporting */
//error_reporting(E_ALL);
//define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');//Sólo para browser

date_default_timezone_set('America/Mexico_City');

$data = [];
$registros = [];

if(!empty($trip_id)){
	$registros = $Admin->getReportTravelers($trip_id);
}else if(!empty($year) && $fullYear){
	$registros = $Admin->getTravelersByYear($year);
}else{
	die('Faltan datos para generar el reporte.');
}

$data = [];
if(isset($registros) && count($registros) > 0){
	$data = $registros;
}


#print_pre($data);
#die();
/*echo "<pre>";
print_r($data);
echo "</pre>";*/

$labelRegistros = count($data)." registro(s)";
//$objPHPExcel->setActiveSheetIndex(0);

$sheet->setCellValue('A3',$labelRegistros);// PHPExcel_Shared_Date::PHPToExcel(time())

$baseRow = 5;//inicio en la fila 7
$num_rows = 5;

foreach($data as $r => $dataRow) {
	$row = $baseRow + $r;
	
	$tieneTripAsignado = FALSE;
	$trip_id = $dataRow['trip_id'];
	if(!empty($trip_id)){
		$trip_data = $Admin->getTrip($trip_id);
		$tieneTripAsignado = TRUE;
	}

	$getCurrency = $tieneTripAsignado?getCurrency($trip_data['currency']):'';

	$traveler_id = $dataRow['traveler_id'];

	$pagado_validado = 0;
	if($tieneTripAsignado){
		$pagado_validado = $Admin->getPaymentValidated($traveler_id);
	}
	#$X - trip_id = $dataRow['X - trip_id'];
	$trip_name = $tieneTripAsignado?$trip_data['trip_name']:'';#$dataRow['trip_name'];
	$trip_cost = $tieneTripAsignado?$trip_data['cost']:'';#$dataRow['trip_cost'];
	$trip_currency = $tieneTripAsignado?$getCurrency['moneda']:'';#$dataRow['trip_currency'];
	$email = $dataRow['email'];
	$name = $dataRow['name'];
	$birth_date = convertirFecha($dataRow['birth_date']);
	$age = $dataRow['age'];
	$passport_number = $dataRow['passport_number'];
	$phone_number = $dataRow['phone_number'];
	$parent_name = $dataRow['parent_name'];
	$parent_email = $dataRow['parent_email'];
	$parent_phone_number = $dataRow['parent_phone_number'];
	$accepted_cancellation_policy = $dataRow['accepted_cancellation_policy'];
	$accepted_rules = $dataRow['accepted_rules'];
	$accepted_first_payment = $dataRow['accepted_first_payment'];
	$accepted_privacy_policy = $dataRow['accepted_privacy_policy'];
	$accepted_traveler = $dataRow['accepted_traveler'];
	$accepted_parent = $dataRow['accepted_parent'];
	$created_at = convertirFecha($dataRow['created_at']);
	$support_number = $dataRow['support_number'];
	$year = $dataRow['year'];
	$language = $dataRow['language'];
	$pagado_validado = $pagado_validado;#$dataRow['pagado_validado'];

	$sheet->setCellValue('A'.$row, $r+1)
	      ->setCellValue('B'.$row, $traveler_id)
	      ->setCellValue('C'.$row, $trip_name)
	      ->setCellValue('D'.$row, $trip_cost)//'=C'.$row.'*D'.$row);
	      ->setCellValue('E'.$row, $trip_currency)
	      ->setCellValue('F'.$row, $email)
	      ->setCellValue('G'.$row, $name)
	      ->setCellValue('H'.$row, $birth_date)
	      ->setCellValue('I'.$row, $age)
	      ->setCellValue('J'.$row, $passport_number)
	      ->setCellValueExplicit('K'.$row, $phone_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING)
	      ->setCellValue('L'.$row, $parent_name)
	      ->setCellValue('M'.$row, $parent_email)
	      ->setCellValueExplicit('N'.$row, $parent_phone_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING)
	      ->setCellValue('O'.$row, $accepted_cancellation_policy)
	      ->setCellValue('P'.$row, $accepted_rules)
	      ->setCellValue('Q'.$row, $accepted_first_payment)
	      ->setCellValue('R'.$row, $accepted_privacy_policy)
	      ->setCellValue('S'.$row, $accepted_traveler)
	      ->setCellValue('T'.$row, $accepted_parent)
	      ->setCellValue('U'.$row, $created_at)
	      ->setCellValue('V'.$row, $support_number)
	      ->setCellValue('W'.$row, $year)
	      ->setCellValue('X'.$row, $language)
	      ->setCellValue('Y'.$row, $pagado_validado)
	      ;
	$sheet->getRowDimension($row)->setRowHeight(65);//ALTURA DE RENGLON
	$num_rows++;
}

$sheet->getStyle('A'.$baseRow.':Y'.$num_rows-1)->applyFromArray(
	array('fill' 	=> array(
								'type'		=> \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
								'color'		=> array('argb' => 'FFFFFFFF')
							),
		  'borders' => array(
								'outline'	=> array(
														'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
														'color' => array('argb' => '55A5A5A5')
													),
								'inside'		=> array(
														'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
														'color' => array('argb' => '55A5A5A5')
													)
							)
		 )
	);


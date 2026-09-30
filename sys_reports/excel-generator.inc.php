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

$data = array();

#$query = "SELECT * FROM [BiossmannData].[dbo].[dp_descriptivos] ORDER BY id_descriptivo DESC";
#$stmt = $pdo->prepare($query);
#$stmt->execute();
$registros = array();
#if($stmt){
#	while ($row = $stmt->fetch()) {
#		$registros[] = $row;
#	}
#}else{
	//die("Error:".$stmt->error);
#}

$registros = $Admin->getReportTravelers($trip_id);
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
	
	$getCurrency = getCurrency($trip_data['currency']);

	$traveler_id = $dataRow['traveler_id'];

	$pagado_validado = $Admin->getPaymentValidated($traveler_id);

	#$X - trip_id = $dataRow['X - trip_id'];
	$trip_name = $trip_data['trip_name'];#$dataRow['trip_name'];
	$trip_cost = $trip_data['cost'];#$dataRow['trip_cost'];
	$trip_currency = $getCurrency['moneda'];#$dataRow['trip_currency'];
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


	/*$ponderacionFinal = ($ponderacionArr[$dataRow['competencia1']] + $ponderacionArr[$dataRow['competencia2']] + $ponderacionArr[$dataRow['competencia3']] + $ponderacionArr[$dataRow['competencia4']]) / $numCompetencias;
	$ponderacionFinal =  number_format($ponderacionFinal, 2, '.', '');*/
	//$sheet->insertNewRowBefore($row,1);

   /* $id_descriptivo 		= $dataRow["id_descriptivo"];
    $fid_dp 				= $dataRow["fid_dp"];
    $puesto_snapshot 		= $dataRow["puesto_snapshot"];
    	$estatus 				= $dataRow["estatus"];
	$numEstatus				= showEstatus($estatus);
	$descEstatus			= showEstatus($estatus,'desc');
	$nombre_realizador		= $dataRow['nombre_realizador'];
	$fecha_registro			= $dataRow['fecha_registro'];
	$nombre_jefe			= $dataRow['nombre_jefe'];
	$autorizado_por_jefe	= $dataRow['autorizado_por_jefe'];
	$comentarios_jefe		= $dataRow['comentarios_jefe'];
	$firma_jefe				= $dataRow['firma_jefe'];
	$fecha_respuesta_jefe	= $dataRow['fecha_respuesta_jefe'];*/
	    /*if($firma_jefe != ''){

	    	$firma_jefe_img_src = resampleImageFromBase64($firma_jefe,'firma_'.$nombre_jefe.' '.$fecha_respuesta_jefe);

	    	$sheetimg = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
			$sheetimg->setName('firma');
			$sheetimg->setDescription('firma');
			$sheetimg->setPath($firma_jefe_img_src);
			$sheetimg->setHeight(60);
			$sheetimg->setCoordinates("K".$row);
			$sheetimg->setWorksheet($sheet);

	    }*/
	/*$nombre_rh				= $dataRow['nombre_rh'];
	$autorizado_por_rh		= $dataRow['autorizado_por_rh'];
	$comentarios_rh			= $dataRow['comentarios_rh'];
	$firma_rh				= $dataRow['firma_rh'];
	$fecha_respuesta_rh	= $dataRow['fecha_respuesta_rh'];*/
	    /*if($firma_rh != ''){
	    	$firma_rh_img_src = resampleImageFromBase64($firma_rh,'firma_'.$nombre_rh.' '.$fecha_respuesta_rh);

	    	$sheetimg = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
			$sheetimg->setName('firma');
			$sheetimg->setDescription('firma');
			$sheetimg->setPath($firma_rh_img_src);
			$sheetimg->setHeight(60);
			$sheetimg->setCoordinates("P".$row);
			$sheetimg->setWorksheet($sheet);
	    }*/

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

/*function getABC($numero,$iteracion){
	$ABC = array('-','A','B','C');
	if($numero > 0){
		return $ABC[($numero-($iteracion*3))];
	}else{
		return '-';
	}
}*/

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

//$sheet->removeRow($num_rows,1);
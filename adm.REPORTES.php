<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
if($Admin->obtenerUsr('perfil') != 'admin'){ echo makeTemplate('403.html', [], 'admin'); exit; }
#$DEBUG = TRUE;
$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $Admin->obtenerUsr('usr');
$data['data1'] = $data1;



$data['YEAR_FILTER'] = YEAR_FILTER;

$reportePagosFull = $Admin->getReportPayments(YEAR_FILTER);

$costo_total_EUR = 0; 
$pago_total_EUR  = 0;
$porcentaje_EUR = 0;
$viajes_count_EUR = 0;
$viajeros_count_EUR = 0;

$data['COSTO_TOTAL_EUR'] = $costo_total_EUR;
$data['PAGO_TOTAL_EUR'] = $pago_total_EUR;
$data['PORCENTAJE_EUR'] = $porcentaje_EUR;
$data['COUNT_VIAJES_EUR'] = $viajes_count_EUR;
$data['COUNT_VIAJEROS_EUR'] = $viajeros_count_EUR;


if(isset($reportePagosFull['CostoTotal']['EUR'])){
	//EUR
	$costo_total_EUR = $reportePagosFull['CostoTotal']['EUR']['total_trip_cost']; 
	$pago_total_EUR  = $reportePagosFull['CostoTotal']['EUR']['total_paid'];
	$viajes_count_EUR  = $reportePagosFull['CostoTotal']['EUR']['trips_count'];
	$viajeros_count_EUR  = $reportePagosFull['CostoTotal']['EUR']['travelers_count'];

	// Calcular porcentaje en formato decimal para que Google Charts lo entienda como %
	$porcentaje_EUR  = $costo_total_EUR > 0 ? (($pago_total_EUR * 100) / $costo_total_EUR) : 0; 

	$data['COSTO_TOTAL_EUR'] = $costo_total_EUR;
	$data['PAGO_TOTAL_EUR'] = $pago_total_EUR;
	$data['PORCENTAJE_EUR'] = number_format($porcentaje_EUR,2);

	$data['COUNT_VIAJES_EUR'] = $viajes_count_EUR;
	$data['COUNT_VIAJEROS_EUR'] = $viajeros_count_EUR;
}


$costo_total_USD = 0; 
$pago_total_USD  = 0;
$porcentaje_USD = 0;
$viajes_count_USD = 0;
$viajeros_count_USD = 0;

$data['COSTO_TOTAL_USD'] = $costo_total_USD;
$data['PAGO_TOTAL_USD'] = $pago_total_USD;
$data['PORCENTAJE_USD'] = $porcentaje_USD;
$data['COUNT_VIAJES_USD'] = $viajes_count_USD;
$data['COUNT_VIAJEROS_USD'] = $viajeros_count_USD;

if(isset($reportePagosFull['CostoTotal']['USD'])){

	//_USD
	$costo_total_USD = $reportePagosFull['CostoTotal']['USD']['total_trip_cost']; 
	$pago_total_USD  = $reportePagosFull['CostoTotal']['USD']['total_paid'];
	$viajes_count_USD  = $reportePagosFull['CostoTotal']['USD']['trips_count'];
	$viajeros_count_USD  = $reportePagosFull['CostoTotal']['USD']['travelers_count'];
	
	// Calcular porcentaje en formato decimal para que Google Charts lo entienda como %
	$porcentaje_USD  = $costo_total_USD > 0 ? (($pago_total_USD * 100) / $costo_total_USD) : 0; 

	$data['COSTO_TOTAL_USD'] = $costo_total_USD;
	$data['PAGO_TOTAL_USD'] = $pago_total_USD;
	$data['PORCENTAJE_USD'] = number_format($porcentaje_USD,2);
	 
	$data['COUNT_VIAJES_USD'] = $viajes_count_USD;
	$data['COUNT_VIAJEROS_USD'] = $viajeros_count_USD;

}

/*--------------------*/

$data['TABLA_GRUPOS_PAGOS'] = '';

if(isset($reportePagosFull['ReporteXGrupo'])){
	$reporteXGrupo = $reportePagosFull['ReporteXGrupo'];
	$data['TABLA_GRUPOS_PAGOS'] = $reporteXGrupo;
}

/*--------------------*/

$optionsBuffer = '';
for($i = INIT_YEAR_OPTIONS;$i <= YEAR_FILTER;$i++){
	$selectedYear = '';
	if($i == YEAR_FILTER){
		$selectedYear = ' selected="selected"';
	}
	$optionsBuffer .= "<option value='$i'$selectedYear>$i</option>";
	
}
$data['ANIOS_OPTIONS'] = $optionsBuffer;


$gruposBuffer = '';
$resTrips = $Admin->getTrips(YEAR_FILTER);
for($i = INIT_YEAR_OPTIONS;$i <= YEAR_FILTER;$i++){
	$selectedYear = '';
	if($i == YEAR_FILTER){
		$selectedYear = ' selected="selected"';
	}
	$gruposBuffer .= "<option value='$i'$selectedYear>$i</option>";
	
}
$data['ANIOS_OPTIONS'] = $gruposBuffer;

/*--------------------*/

echo makeTemplate('admin_reportes.html', $data, 'admin');

?>
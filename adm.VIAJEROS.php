<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;
$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $Admin->obtenerUsr('usr');
$data['data1'] = $data1;

if(			$data2=='detalle'){
	if($data3=='doc'){
		include_once("adm.$data1.detalle.doc.php");
	}else{
		include_once("adm.$data1.detalle.php");
	}
}else if(	$data2=='pago'){ 	include_once("adm.$data1.pago.php");
}else{
	$data['linkNuevo'] = WEB_URL.$data1.'/new';

	$data['YEAR_FILTER'] = YEAR_FILTER;

	echo makeTemplate('admin_viajeros.html', $data, 'admin');
}
?>
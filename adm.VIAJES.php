<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;

if(		$data2=='new'){ 	include_once("adm.$data1.new.php");}
else if($data2=='detalle'){ 	include_once("adm.$data1.detalle.php");}
else {

	$data = array();
	$data['siteURL'] = WEB_URL;
	$data['appName'] = APP_NAME;

	$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
	$data['EMAIL'] = $Admin->obtenerUsr('usr');
	$data['linkNuevo'] = WEB_URL.$data1.'/new';
	$data['data1'] = $data1;

	$data['YEAR_FILTER'] = YEAR_FILTER;

	$data['consultorHidden'] = '';
	if($Admin->esConsultor()){
		$data['consultorHidden'] = ' hidden';
	}


	echo makeTemplate('admin_viajes.html', $data, 'admin');
}
?>
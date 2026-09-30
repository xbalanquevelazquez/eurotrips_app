<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$DEBUG = TRUE;

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $Admin->obtenerUsr('usr');

if($data2 !== '' && esUUID($data2)){
	$notification_id = $data2;
	$notification = $Admin->getNotification($notification_id);
	if(count($notification) > 0){
		$updateNotification = $notification;
		unset($updateNotification['created_at']);
		$updateNotification['is_read'] = TRUE;
		$updateNotification['read_at'] = date('Y-m-d H:i:s');

		$resultado = $Admin->setNotificationRead($notification_id,$updateNotification);

		$link = WEB_URL.'VIAJEROS/detalle/'.$notification['traveler_id'];
		header('Location:'.$link);
		exit;		
	}else{
		header('Location:'.WEB_URL.'SALIR');
		exit;				
	}
}else{
	header('Location:'.WEB_URL);
	exit;
}
?>
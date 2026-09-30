<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

//$DEBUG = TRUE;

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;
$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $email = $Admin->obtenerUsr('usr');

if($data2 !== '' && esUUID($data2)){
	$notification_id = $data2;
	$notification = $Admin->getNotification($notification_id);

	$updateNotification = $notification;
	unset($updateNotification['created_at']);
	$updateNotification['is_read'] = TRUE;
	$updateNotification['read_at'] = date('Y-m-d H:i:s');

	$resultado = $Admin->setNotificationRead($notification_id,$updateNotification);
	$link = WEB_URL;

	switch ($notification['message']) {
		case 'Un pago fue creado.':
		case 'El pago cambió a: '.PAYMENT_VALIDATED_STATUS:
		case 'El pago cambió a: '.PAYMENT_REJECTED_STATUS:
			$link = WEB_URL.'PAGOS';
			
			break;
		case 'Un documento fue creado.':
		case 'El documento cambió a: ':
		case 'El documento cambió a: rejected':
			$link = WEB_URL.'DOCS';
			
			break;
		
		default:
			break;
	}

	header('Location:'.$link);
	exit;
}else{
	header('Location:'.WEB_URL);
	exit;
}
?>
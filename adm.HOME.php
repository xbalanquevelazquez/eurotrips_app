<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$DEBUG = TRUE;

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;
$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $Admin->obtenerUsr('usr');


### NOTIFICACIONS
$callNotifications = $Admin->getNotificationsAdmin();
$notifications = $callNotifications['notifications'];

$data['count_alertas'] = $callNotifications['totalRecords'];
$data['alertas'] = formatAdminNotifications($notifications);

echo makeTemplate('admin_inicio.html', $data, 'admin');
?>
<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['errorMsg'] = $GLOBALS['errorMsg'] ?? '';
$data['alerta_envio'] = '';

echo makeTemplate('formulario_registro_correcto.html', $data, 'site');
?>
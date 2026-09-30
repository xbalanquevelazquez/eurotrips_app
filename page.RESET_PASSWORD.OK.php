<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['TURNSTILE_SITE_KEY'] = TURNSTILE_SITE_KEY;

$data['CURRENT_YEAR_REGISTER'] = CURRENT_YEAR_REGISTER;

$data['errorMsg'] = $GLOBALS['errorMsg'] ?? '';
$data['mensaje'] = '<div class="alerta verde p1">Se reestableció su contraseña. Firmese en la aplicación.</div>';

echo makeTemplate('panel_reset_password_ok.html', $data, 'site');
?>
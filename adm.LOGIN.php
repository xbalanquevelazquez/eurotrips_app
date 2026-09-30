<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$loginData = array();
$loginData['siteURL'] = WEB_URL;
$loginData['appName'] = APP_NAME;
$loginData['TURNSTILE_SITE_KEY'] = TURNSTILE_SITE_KEY;

$loginData['errorMsg'] = $_SESSION['errorMsg'];
$_SESSION['errorMsg'] = '';

echo makeTemplate('panel_login.html', $loginData, 'site');
?>
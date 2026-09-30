<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header('Content-Type: text/html; charset=UTF-8');
date_default_timezone_set('America/Mexico_City');
define('VIEWABLE',TRUE);

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("cnf/cnfg.app.php");
include_once(CLASS_PATH."cron.class.php");

$cron = new Cronjob($Admin,$admRegistro,$mail);

$prefijo = PREFIJO;
$cron->setNumPerfilParaEnvioCorreos(2);//ID del perfil de usuarios a los que se enviarán los correos (deberían correcponder a CC)
$cron->debug = TRUE;//Como lo ejecuta el server, se puede dejar habilitado para ver que este funcionando
$cron->setBandera = TRUE;//Esto cambia la bandera en la BD para que una vez incluida en la alerta no siga mandando más correos / se puede poner en FALSE y seguirá mandando correos con los registros que cumplan los parámetros
echo $cron->exec();
?>
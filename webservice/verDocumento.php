<?php
header("Access-Control-Allow-Origin: *");
session_start();

#header("Content-Type: application/json", true);
define('VIEWABLE',TRUE);
include_once("../cnf/cnfg.app.php");
include_once("../funct/funcionalidad.php");

	error_reporting(E_ALL);
	ini_set('display_errors', 1);

$continue = TRUE;
$error = '';

if(isset($_GET['id']) && $_GET['id'] !== ''){
	$id = $_GET['id'];
}else{
	$error .= 'Falta el ID';
	$continue = FALSE;
}
if(isset($_GET['trav']) && $_GET['trav'] !== ''){
	$traveler_id = $_GET['trav'];
}else{
	$error .= 'Falta el Viajero';
	$continue = FALSE;
}		
if(isset($_GET['tipo']) && $_GET['tipo'] !== ''){
	$document_type = $_GET['tipo'];
}else{
	$error .= 'Falta el tipo';
	$continue = FALSE;
}		

if($continue){

	if($Admin->comprobarSesion()){

	    $current_traveler_id = $Admin->obtenerUsr('user_id');
	    
	    if($current_traveler_id != $traveler_id && !$Admin->esAdmin()){
	        http_response_code(403);
	        exit('No autorizado');
	    }
		// Consultar API
		$document = $Admin->getDocumentByType($traveler_id,$document_type);
		$traveler = $Admin->getTraveler($traveler_id);
		if(isset($document[0])){
			$document = $document[0];
		}
		$mime = $document['mime_type'];
		// Obtener document_path
		$ruta = normalizePath(STORAGE_PATH.$document['document_path']);
		$data = Document::decryptFile($ruta);

		if(isset($_GET['descargar']) && $_GET['descargar'] == '1'){
			$ext = mimeToExtension($mime);
		    // Headers de descarga
		    header('Content-Type: application/octet-stream');
		    header('Content-Disposition: attachment; filename="'.$traveler['name'].'_'.$document['document_type'].$ext.'"');
		    header('Content-Length: ' . strlen($data));
		} else {
		    // Headers para mostrar (actual)
		    header('Content-Type: '.$mime);
		}

		echo $data;

		exit;
	}else{
		header('Location:'.WEB_URL);
	}

}else{
	echo $error;
	exit;
}
?>
<?php
header("Access-Control-Allow-Origin: *");
session_start();


header("Content-Type: application/json", true);
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
		// Consultar API
		$document = $Admin->getDocumentByType($traveler_id,$document_type);
		if(isset($document[0])){
			$document = $document[0];
		}
		$mime = $document['mime_type'];
		// Obtener document_path
		$ruta = normalizePath(STORAGE_PATH.$document['document_path']);
		$data = Document::decryptFile($ruta);

		header('Content-Type: '.$mime);

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
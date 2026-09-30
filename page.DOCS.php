<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $Admin->obtenerUsr('usr');
$data['mensajePassport'] = '';
$data['mensajePassport2'] = '';
$traveler_id = $Admin->obtenerUsrData('traveler_id');
$data['alertFaltaPasaporte'] = __('Selecciona un archivo (el Pasaporte 1 es el obligatorio)');
$data['alertFormatoNoPermitido'] = __('Formato no permitido.');
$data['alertExcedeTamanio'] = __('El archivo excede el tamaño permitido (2 MB).');

$data['mensaje'] = '';
$mensaje = $Admin->getMensaje();
if($mensaje != ''){
	$data['mensaje'] = $mensaje;
	$Admin->cleanMensaje();
}

if(		$data2=='detalle'){ 	include_once("page.$data1.detalle.php");}
else {

	if(isset($_FILES) && count($_FILES) >= 1){
		if(isset($_FILES['passport']) && $_FILES['passport']['error'] == 0){
			$passport = $_FILES['passport'];
			$folder = STORAGE_PATH.$Admin->obtenerUsrData('traveler_id');
			$folder_relative = $Admin->obtenerUsrData('traveler_id');
			if(!is_dir($folder)){
			    mkdir($folder,0755,true);
			}

			if (!function_exists('finfo_open')) {
			    die('La extensión Fileinfo no está habilitada.');
			}

			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $passport['tmp_name']);
			#finfo_close($finfo);

			$extension = strtolower(pathinfo($passport['name'], PATHINFO_EXTENSION));
	
			$tiposPermitidos = [
			    'pdf'  => 'application/pdf',
			    'jpg'  => 'image/jpeg',
			    'jpeg' => 'image/jpeg',
			    'png'  => 'image/png'
			];

			if (!isset($tiposPermitidos[$extension]) || $tiposPermitidos[$extension] !== $mime) {
			    die('Tipo de archivo no válido.');
			}

			$encryptedFile = $folder.DIRECTORY_SEPARATOR.'first_passport.enc';
			$doc_path = $folder_relative.DIRECTORY_SEPARATOR.'first_passport.enc';
			Document::encryptFile(
			    $passport['tmp_name'],
			    $encryptedFile
			);

			if(file_exists($encryptedFile)){
				$documento = $Admin->getDocumentByType($traveler_id,$document_type='first_passport');
				$docDataCreate = [
				  "traveler_id" => $traveler_id,
				  "document_type" => 'first_passport',
				  "mime_type" => $passport['type'],
				  "notes" => "",
				  "document_path" => $doc_path
				];
				if(!isset($documento[0])){
					$createDoc = $Admin->API->post('/api/documents',$docDataCreate);

					$tipo_documento_convertido = convertirTipoDocumento('first_passport');
					$message = "<span class='strong'>{$data['NOMBRE']}</span> cargó un documento del tipo <span class='strong'>$tipo_documento_convertido</span> que debe ser validado";
					$resNotif = $Admin->addNotification($traveler_id,'document_uploaded','admin',$title = 'Documento subido',$message);
				}else{
					$createDoc = $Admin->API->put('/api/documents/'.$documento[0]['document_id'],$docDataCreate);
				}

				$Admin->setMensaje("<div class='pm5 mb1 alerta verde'>Se guardo el pasaporte correctamente.</div>");
			}else{
				$Admin->setMensaje("<div class='pm5 mb1 alerta'>Error al guardar el pasaporte.</div>");
			}
		}
		###    Passport 2
		if(isset($_FILES['passport2']) && $_FILES['passport2']['error'] == 0){
			$passport2 = $_FILES['passport2'];
			$folder = STORAGE_PATH.$Admin->obtenerUsrData('traveler_id');
			$folder_relative = $Admin->obtenerUsrData('traveler_id');
			if(!is_dir($folder)){
			    mkdir($folder,0755,true);
			}

			if (!function_exists('finfo_open')) {
			    die('La extensión Fileinfo no está habilitada.');
			}

			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $passport2['tmp_name']);
			#finfo_close($finfo);

			$extension = strtolower(pathinfo($passport2['name'], PATHINFO_EXTENSION));
	
			$tiposPermitidos = [
			    'pdf'  => 'application/pdf',
			    'jpg'  => 'image/jpeg',
			    'jpeg' => 'image/jpeg',
			    'png'  => 'image/png'
			];

			if (!isset($tiposPermitidos[$extension]) || $tiposPermitidos[$extension] !== $mime) {
			    die('Tipo de archivo no válido.');
			}

			$encryptedFile = $folder.DIRECTORY_SEPARATOR.'second_passport.enc';
			$doc_path = $folder_relative.DIRECTORY_SEPARATOR.'second_passport.enc';
			Document::encryptFile(
			    $passport2['tmp_name'],
			    $encryptedFile
			);

			if(file_exists($encryptedFile)){
				$documento2 = $Admin->getDocumentByType($traveler_id,$document_type='second_passport');
				$docDataCreate2 = [
				  "traveler_id" => $traveler_id,
				  "document_type" => 'second_passport',
				  "mime_type" => $passport2['type'],
				  "notes" => "",
				  "document_path" => $doc_path
				];
				if(!isset($documento2[0])){
					$createDoc = $Admin->API->post('/api/documents',$docDataCreate2);
					
					$tipo_documento_convertido = convertirTipoDocumento('second_passport');
					$message = "<span class='strong'>{$data['NOMBRE']}</span> cargó un documento del tipo <span class='strong'>$tipo_documento_convertido</span> que debe ser validado";
					$resNotif = $Admin->addNotification($traveler_id,'document_uploaded','admin',$title = 'Documento subido',$message);
				}else{
					$createDoc = $Admin->API->put('/api/documents/'.$documento2[0]['document_id'],$docDataCreate2);
				}
				$mensaje = $Admin->getMensaje();
				$Admin->setMensaje($mensaje."<div class='pm5 mb1 alerta verde'>Se guardo el segundo pasaporte correctamente.</div>");

			}else{
				$mensaje = $Admin->getMensaje();
				$Admin->setMensaje($mensaje."<div class='pm5 mb1 alerta'>Error al guardar el segundo pasaporte.</div>");
			}

		}

		header("Location:".WEB_URL.$data1);
		exit;
	}

	//faltante, en revisión, validado o rechazado

	$contadorDocumentos = 0;

	###   PASSPORT 1
	$documento = $Admin->getDocumentByType($traveler_id,$document_type='first_passport');
	if(isset($documento[0])){
		$documento = $documento[0];
	}

	$data['input'] = '';
	$data['btnEnviar'] = '';

	if(count($documento) > 0){ 
		$contadorDocumentos++;
		$document_id = $documento['document_id'];
		switch($documento['status']){
			case 'pending_review':
				$data['btnPassportColor'] = 'texto-naranja';
				$data['passportCSS'] = 'fa-solid fa-clock '.$data['btnPassportColor'];
				$data['passportStatus'] = 'En revisión';
				break;
			case 'approved':
				$data['btnPassportColor'] = 'texto-aqua';
				$data['passportCSS'] = 'fa-solid fa-check '.$data['btnPassportColor'];
				$data['passportStatus'] = 'Validado';
				break;
			case 'rejected':
				$data['btnPassportColor'] = 'texto-alerta';
				$data['passportCSS'] = 'fa-solid fa-times '.$data['btnPassportColor'];
				$data['passportStatus'] = 'Rechazado';
				break;
		}
		$data['btnDoc'] = '<a href="'.WEB_URL.$data1.'/detalle/'.$document_id.'" class="btn primario"><i class="fa-solid fa-eye"></i></a>';

	}else{
		$data['btnPassportColor'] = 'texto-amarillo';
		$data['passportCSS'] = 'fa-solid fa-exclamation-triangle '.$data['btnPassportColor'];
		$data['passportStatus'] = 'Faltante';
		$data['input'] = '<input type="file" id="passport" name="passport"  class="document-upload" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">';
		$data['btnDoc'] = '<a class="btn primario btnUpload" data-input="passport"><i class="fa-solid fa-upload"></i></a>';
	}

	##### ----
	###   PASSPORT 2
	$documento_2 = $Admin->getDocumentByType($traveler_id,$document_type='second_passport');
	if(isset($documento_2[0])){
		$documento_2 = $documento_2[0];
	}

	$data['input2'] = '';

	if(count($documento_2) > 0){ 
		$contadorDocumentos++;
		$document_2_id = $documento_2['document_id'];
		switch($documento_2['status']){
			case 'pending_review':
				$data['btnPassport2Color'] = 'texto-naranja';
				$data['passport2CSS'] = 'fa-solid fa-clock '.$data['btnPassport2Color'];
				$data['passport2Status'] = 'En revisión';
				break;
			case 'approved':
				$data['btnPassport2Color'] = 'texto-aqua';
				$data['passport2CSS'] = 'fa-solid fa-check '.$data['btnPassport2Color'];
				$data['passport2Status'] = 'Validado';
							break;
			case 'rejected':
				$data['btnPassport2Color'] = 'texto-alerta';
				$data['passport2CSS'] = 'fa-solid fa-times '.$data['btnPassport2Color'];
				$data['passport2Status'] = 'Rechazado';
				break;
		}
		$data['btnDoc2'] = '<a href="'.WEB_URL.$data1.'/detalle/'.$document_2_id.'" class="btn primario"><i class="fa-solid fa-eye"></i></a>';

	}else{
		$data['btnPassport2Color'] = 'texto-amarillo';
		$data['passport2CSS'] = 'fa-solid fa-exclamation-triangle '.$data['btnPassport2Color'];
		$data['passport2Status'] = 'Opcional';
		$data['input2'] = '<input type="file" id="passport2" name="passport2"  class="document-upload2" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">';
		$data['btnDoc2'] = '<a class="btn primario btnUpload2" data-input="passport2"><i class="fa-solid fa-upload"></i></a>';
	}

	if($contadorDocumentos <= 1){//falta por lo menos 1 documento
		$data['btnEnviar'] = '<button type="submit" class="btn primario" id="sendForm"><i class="fa-solid fa-paper-plane"></i> &nbsp; '.__('Enviar').'</button>';
	}
	##### ----
	$data['passportStatus'] = __($data['passportStatus']);
	$data['passport2Status'] = __($data['passport2Status']);
	echo makeTemplate('panel_documentos.html', $data, 'site');
} ?>
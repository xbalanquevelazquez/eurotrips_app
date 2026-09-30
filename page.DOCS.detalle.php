<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;
$data = [];
$data['returnLink'] = WEB_URL.$data1;

$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $Admin->obtenerUsr('usr');
$data['mensajeDocument'] = '';
$traveler_id = $Admin->obtenerUsrData('traveler_id');

$data['alertFaltaArchivo'] = __('Selecciona un archivo');
$data['alertFormatoNoPermitido'] = __('Formato no permitido.');
$data['alertExcedeTamanio'] = __('El archivo excede el tamaño permitido (2 MB).');

$data['mensaje'] = '';
$mensaje = $Admin->getMensaje();
if($mensaje != ''){
	$data['mensaje'] = $mensaje;
	$Admin->cleanMensaje();
}

if(!empty($data3) && $data3 !== ''){
	$document_id = $data3;

	//-----UPDATE
	if(isset($_FILES) && count($_FILES) >= 1){

		$document_type_post = (isset($_POST['document_type']) && !empty($_POST['document_type']))? $_POST['document_type']: 'first_passport';
		if(isset($_FILES['documento'])){

			$documento = $_FILES['documento'];
			$folder = STORAGE_PATH.$Admin->obtenerUsrData('traveler_id');
			$folder_relative = $Admin->obtenerUsrData('traveler_id');
			if(!is_dir($folder)){
			    mkdir($folder,0755,true);
			}

			if (!function_exists('finfo_open')) {
			    die('La extensión Fileinfo no está habilitada.');
			}

			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $documento['tmp_name']);
			#finfo_close($finfo);

			$extension = strtolower(pathinfo($documento['name'], PATHINFO_EXTENSION));
	
			$tiposPermitidos = [
			    'pdf'  => 'application/pdf',
			    'jpg'  => 'image/jpeg',
			    'jpeg' => 'image/jpeg',
			    'png'  => 'image/png'
			];

			if (!isset($tiposPermitidos[$extension]) || $tiposPermitidos[$extension] !== $mime) {
			    die('Tipo de archivo no válido.');
			}
			
			$encryptedFile = $folder.DIRECTORY_SEPARATOR.$document_type_post.'.enc';
			$doc_path = $folder_relative.DIRECTORY_SEPARATOR.$document_type_post.'.enc';

			Document::encryptFile(
			    $documento['tmp_name'],
			    $encryptedFile
			);

			if(file_exists($encryptedFile)){
				$notes = '';
				if(isset($_POST['notes']) && $_POST['notes'] !== ''){
					$notes = __('Se actualizó el documento, notas originales:').' '.$_POST['notes'];
				}
				$docDataUpdate = [
				  "traveler_id" => $traveler_id,
				  "document_type" => $document_type_post,
				  "mime_type" => $documento['type'],
				  "status" => "pending_review",
				  "notes" => $notes,
				  "document_path" => $doc_path
				];

				$updateDoc = $Admin->API->put('/api/documents/'.$document_id,$docDataUpdate);

				$tipo_documento_convertido = convertirTipoDocumento($document_type_post);
				#'general', 'payment_reminder', 'document_uploaded', 'document_rejected', 'document_approved', 'trip_update', 'traveler_update'
				$message = "<span class='strong'>{$data['NOMBRE']}</span> cargó un documento del tipo <span class='strong'>$tipo_documento_convertido</span> que debe ser validado";
				$resNotif = $Admin->addNotification($traveler_id,'document_uploaded','admin',$title = 'Documento subido',$message);

				$Admin->setMensaje("<div class='pm5 mb1 alerta verde'>Se guardo el archivo correctamente.</div>");
			}else{
				$Admin->setMensaje("<div class='pm5 mb1 alerta'>Error al guardar el archivo.</div>");
			}
			header("location:".WEB_URL.$data1.'/detalle/'.$data3);
			exit;
		}
	}

	$documento = $Admin->getDocumentByID($document_id);

	$data['mensaje'] = '';

	$data['input'] = '';
	$data['subirDoc'] = '';
	$data['btnEnviar'] = '';
	$data['labelSubir'] = '';
	$data['visorDocumento'] = '';

	if(isset($documento['document_id'])){
		$data = array_merge($data,$documento);
		if(is_null($data['document_id'])){
			$data['document_id'] = '';
		}
		$data['tipo_documento'] = convertirTipoDocumento($documento['document_type']);

		$data['tipo_documento'] = __($data['tipo_documento']);

		$document_id = $documento['document_id'];
		$mime = $documento['mime_type'];
		$document_type = $documento['document_type'];
		switch($documento['status']){
			case 'pending_review':
				$data['btnDocumentColor'] = 'texto-naranja';
				$data['documentCSS'] = 'fa-solid fa-clock '.$data['btnDocumentColor'];
				$data['documentStatus'] = 'En revisión';
				$data['subirDoc'] = '';
				break;
			case 'approved':
				$data['btnDocumentColor'] = 'texto-aqua';
				$data['documentCSS'] = 'fa-solid fa-check '.$data['btnDocumentColor'];
				$data['documentStatus'] = 'Validado';
				$data['subirDoc'] = '';
				break;
			case 'ejected':
			case 'rejected':
				$data['btnDocumentColor'] = 'texto-alerta';
				$data['documentCSS'] = 'fa-solid fa-times '.$data['btnDocumentColor'];
				$data['documentStatus'] = 'Rechazado';
				$data['labelSubir'] = 'Subir de nuevo:';
				$data['subirDoc'] = '<input type="file" id="documento" name="documento"  class="document-upload" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">'.
					'<a class="btn primario btnUpload" data-input="documento"><i class="fa-solid fa-upload"></i></a>';
				$data['btnEnviar'] = '<button type="submit" class="btn primario" id="sendForm"><i class="fa-solid fa-paper-plane"></i> &nbsp; Enviar</button>';
				break;
		}

		$data['visorDocumento'] = $Admin->createVisorDoc($documento,$traveler_id);

	}else{
		$data['btnDocumentColor'] = 'texto-amarillo';
		$data['documentCSS'] = 'fa-regular fa-triangle-exclamation '.$data['btnDocumentColor'];
		$data['documentStatus'] = 'Faltante';
		$data['input'] = '<input type="file" id="documento" name="documento"  class="document-upload" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">';
		$data['btnDoc'] = '<a class="btn primario btnUpload" data-input="documento"><i class="fa-solid fa-upload"></i></a>';
		$data['btnEnviar'] = '<button type="submit" class="btn primario" id="sendForm"><i class="fa-solid fa-paper-plane"></i> &nbsp; Enviar</button>';
	}
	$data['documentStatus'] = __($data['documentStatus']);
}

echo makeTemplate('panel_documento_detalle.html', $data, 'site');
?>
<script type="text/javascript">
	$(document).ready(function(){
		console.log('Init...');
	});
</script>
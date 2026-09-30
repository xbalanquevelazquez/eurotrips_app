<?php
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

date_default_timezone_set('America/Mexico_City');
$fechaHoy = date('Y-m-d');

$data['consultorHidden'] = '';
$data['consultorShow'] = ' hidden';
if($Admin->esConsultor()){
	$data['consultorHidden'] = ' hidden';
	$data['consultorShow'] = '';
}

if(!empty($data3) && $data3 !== ''){
	$mensaje = $Admin->getMensaje();
	$data['mensaje'] = '';
	if($mensaje != ''){
		$data['mensaje'] = $mensaje;
		$Admin->cleanMensaje();
	}
	$UUID = $data3;

	$travelerData = $Admin->getTraveler($UUID);
	if(isset($travelerData['traveler_id'])){

		#$travelerData = $travelerData['data'];
		$userConsolidado = $call = $Admin->getTravelerConsolidado($UUID);
		$userData = $Admin->getTraveler($UUID);
		
		$userConsolidado['language'] = $userData['language'] ?? '';

		$data = array_merge($data,$userConsolidado);
		# CORRECCION DE AGE
		$userConsolidado['age'] = $travelerData['age'] ?? '';
		$data['age'] = $travelerData['age'] ?? '';

	    $trip_data = [];
		$trip_id = $userConsolidado['trip_id'] ?? '';
	    $trip_name = '-';
	    if(!is_null($trip_id)){
	        $trip_data = $userConsolidado['trip_data'][0] ?? '';
	        $current_trip_name =  $trip_data['trip_name'] ?? '';
	    }else{
	        $data['trip_id'] = '';
	        $data['trip_name'] = '';
	    }

		$data['returnLink'] = WEB_URL.$data1;

		$phoneArr = getLADAFromPhone($data['phone_number'] ?? '');
		$parentPhoneArr = getLADAFromPhone($data['parent_phone_number'] ?? '');

		$data['phone_number'] = $phoneArr['TEL'];
		$data['lada'] = $phoneArr['LADA'];
		$data['lada_icon'] = $phoneArr['ICON'];

		$data['parent_phone_number'] = $parentPhoneArr['TEL'];
		$data['parent_lada'] = $parentPhoneArr['LADA'];
		$data['parent_lada_icon'] = $parentPhoneArr['ICON'];

		$resViajes = $Admin->getTrips(YEAR_FILTER);

		if(isset($resViajes['data'])){
			$data['viajesArr'] = json_encode($resViajes['data']);
		}else{
			$data['viajesArr'] = json_encode([]);
		}

		$documents_data = $userConsolidado['documents_data'] ?? [];
		$payments_data = $userConsolidado['payments_data'] ?? [];

		if(!is_null($trip_id) && !empty($trip_id)){
			$cost = formatearCurrency($trip_data['cost'],$trip_data['currency']);
			
			## SI EL VIAJERO TIENE PAYMENTS, NO PUEDO QUITAR EL GRUPO
			$data['trip_selected'] = '<div class="trip-chip">';
			$data['trip_selected'] .=     '<span><a href="'.WEB_URL.'VIAJES/detalle/'.$trip_data['trip_id'].'">' . $trip_data['trip_name'] .'</a> <span class="small texto-grismedio">['.$trip_data['status'].'] [costo: '.$cost.']</span> '. '</span>';

			$totalPagado = calcularTotalPagado($payments_data);
			if(($totalPagado <= 0 && count($payments_data) == 0) && !$Admin->esConsultor()){
				$data['trip_selected'] .=     '<button type="button" class="trip-remove">&times;</button>' ;
			}
			$data['trip_selected'] .= '</div>';
		}

		$data['traveler_data'] = $travelerData;
		$traveler_id = $data['traveler_data']['traveler_id'] ?? '';
		unset($data['traveler_data']['traveler_id']);
		unset($data['traveler_data']['trip_id']);
		unset($data['traveler_data']['email']);
		unset($data['traveler_data']['accepted_cancellation_policy']);
		unset($data['traveler_data']['accepted_rules']);
		unset($data['traveler_data']['accepted_first_payment']);
		unset($data['traveler_data']['accepted_privacy_policy']);
		unset($data['traveler_data']['accepted_traveler']);
		unset($data['traveler_data']['accepted_parent']);
		unset($data['traveler_data']['created_at']);

		$data['traveler_data'] = json_encode($data['traveler_data']);

		//UPDATE support_number
		if(isset($_POST['sendArray']) && trim($_POST['sendArray']['support_number']) != '') {
			$support_number = trim($_POST['sendArray']['support_number']);
			$updateData = [];
			$updateData['trip_id'] 				= (is_null($data['trip_id'])||$data['trip_id']=='')?NULL:$data['trip_id'];
			$updateData['name'] 				= $data['name'];
			$updateData['birth_date'] 			= convertirFecha($data['birth_date']);
			$updateData['age'] 					= $travelerData['age'];
			$updateData['passport_number'] 		= $data['passport_number'];
			$updateData['phone_number'] 		= $data['lada'].$data['phone_number'];
			$updateData['parent_name'] 			= $data['parent_name'];
			$updateData['parent_email'] 		= $data['parent_email'];
			$updateData['parent_phone_number'] 	= $data['parent_lada'].$data['parent_phone_number'];
			$updateData['support_number'] 		= $support_number;
			$updateData['year'] 				= $data['year'];
			$updateData['language'] 			= $data['language'];

			$updateRes = $Admin->API->put('/api/travelers/'.$traveler_id, $updateData);

			if($updateRes['success']){
				//CREAR UN DOCUMENTO PASSPORT
				$data['support_number'] = $support_number;
				$Admin->addMensaje('<div class="pm5 alerta verde">Se guardaron los datos del usuario.</div>');
			}else{
				$Admin->addMensaje('<div class="warning">Error: httpCode '.$updateRes['httpCode'].' : '.$updateRes['data']['message'].'</div>');
			}
		}
		if(isset($_FILES['assistanceFile']) && $_FILES['assistanceFile']['size'] > 0) {
			$assistanceFile = $_FILES['assistanceFile'];
			$document_type = 'assistance_file';
			$folder = STORAGE_PATH.$traveler_id;
			$folder_relative = $traveler_id;
			if(!is_dir($folder)){
			    mkdir($folder,0755,true);
			}

			if (!function_exists('finfo_open')) {
			    die('La extensión Fileinfo no está habilitada.');
			}
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $assistanceFile['tmp_name']);
			#finfo_close($finfo);

			$extension = strtolower(pathinfo($assistanceFile['name'], PATHINFO_EXTENSION));

			$tiposPermitidos = [
			    'pdf'  => 'application/pdf',
			    'jpg'  => 'image/jpeg',
			    'jpeg' => 'image/jpeg',
			    'png'  => 'image/png'
			];

			if (!isset($tiposPermitidos[$extension]) || $tiposPermitidos[$extension] !== $mime) {
			    die('Tipo de archivo no válido.');
			}

			$encryptedFile = $folder.DIRECTORY_SEPARATOR.$document_type.'.enc';
			$doc_path = $folder_relative.DIRECTORY_SEPARATOR.$document_type.'.enc';
			Document::encryptFile(
			    $assistanceFile['tmp_name'],
			    $encryptedFile
			);

			if(file_exists($encryptedFile)){
				$assistance_file = $Admin->searchDocumentByType($documents_data,'assistance_file');
				$docDataCreate = [
				  "traveler_id" => $traveler_id,
				  "document_type" => $document_type,
				  "status" => 'approved',
				  "mime_type" => $assistanceFile['type'],
				  "notes" => "",
				  "document_path" => $doc_path
				];
				if(!isset($assistance_file[0])){
					//LO CREO SÓLO SI NO EXISTE	
					$createDoc = $Admin->API->post('/api/documents',$docDataCreate);
				}else{
					$createDoc = $Admin->API->put('/api/documents/'.$assistance_file[0]['document_id'],$docDataCreate);
				}
				$Admin->addMensaje('<div class="pm5 alerta verde">Se actualizo el documento de asistencia.</div>');
			}else{
				$Admin->addMensaje("<div class='pm5 mb1 alerta roja'>Error al guardar el archivo.</div>");
			}
		}

		if((isset($_POST['sendArray']) && trim($_POST['sendArray']['support_number']) != '') || (isset($_FILES['assistanceFile']) && $_FILES['assistanceFile']['size'] > 0)) {
			header('Location:'.WEB_URL.$data1.'/detalle/'.$traveler_id);
			exit;
		}
		//ASSISTANCE FILE
		$data['btnUploadShow'] = '';
		$data['assistanceChip'] = '';
		$assistance_file = $Admin->searchDocumentByType($documents_data,'assistance_file');
		if(isset($assistance_file[0])){
			$assistance_file = $assistance_file[0];
			$data['btnUploadShow'] = 'hidden';
			$data['assistanceChip'] = '<span class="chip mt1"><a href="'.WEB_URL.'webservice/verDocumento.php?id='.$assistance_file['document_id'].'&trav='.$traveler_id.'&tipo=assistance_file" target="_blank">Assistance file</a> ';
			if(!$Admin->esConsultor()){
			        $data['assistanceChip'] .=    ' <a href="#" class="removeFile" data-input="assistanceFile">✕</a>';
			}
	        $data['assistanceChip'] .='</span>';
		}
		$first_passport = $Admin->searchDocumentByType($documents_data,'first_passport');
		$second_passport = $Admin->searchDocumentByType($documents_data,'second_passport');

		$documentos = array_merge($first_passport,$second_passport);

		$data['tablaDocumentos'] = '<tr><td class="acenter" colspan="3">No hay documentos cargados</td></tr>';
		$bufferDocumentos = '';
		if(count($documentos)>0){
			foreach ($documentos as $documento){
				switch($documento['document_type']){
					case 'first_passport':
						$documento['tipo_documento'] = 'Primer pasaporte';
						break;
					case 'second_passport':
						$documento['tipo_documento'] = 'Segundo pasaporte';
						break;
				}
				switch($documento['status']){
					case 'pending_review':
						$documento['docStatusColor'] = 'texto-naranja';
						$documento['passportStatus'] = 'En revisión';
						break;
					case 'approved':
						$documento['docStatusColor'] = 'texto-aqua';
						$documento['passportStatus'] = 'Validado';
						break;
					case 'rejected':
						$documento['docStatusColor'] = 'texto-alerta';
						$documento['passportStatus'] = 'Rechazado';
						break;
				}

				$documento['uploaded_at'] = convertirFecha($documento['uploaded_at']);

				$documento['link_archivo'] = WEB_URL.$data1.'/'.$data2.'/doc/'.$documento['document_id'];
				$bufferDocumentos .= makeTemplate('admin_documento_renglon.html', $documento, 'admin');
			}
			$data['tablaDocumentos'] = $bufferDocumentos;

		}

		$travelerData = $userConsolidado;
		$dataPagos = [];
		$dataPayment = [
				"trip_id" => $travelerData['trip_id'],
				"traveler_id" => $travelerData['traveler_id'],
				"fechaHoy" => $fechaHoy,
				"cost" => 0,
				"currency" => 1,
				"limit_date" => $fechaHoy,
				"btnDetalle" => TRUE, //GENERAR EL BOTÓN DE DETALLES
				"year" => $userConsolidado['year']
			];
		$data['birth_date'] = convertirFecha($travelerData['birth_date']);
		if(!empty($trip_id)){
			$dataPayment = [
				"trip_id" => $travelerData['trip_id'],
				"traveler_id" => $travelerData['traveler_id'],
				"fechaHoy" => $fechaHoy,
				"cost" => $trip_data['cost'],
				"currency" => $trip_data['currency'],
				"limit_date" => $trip_data['limit_date'],
				"btnDetalle" => TRUE, //GENERAR EL BOTÓN DE DETALLES
				"year" => $userConsolidado['year']
			];
		}
		
		$dataPagos = $Admin->getPaymentsProcessed($dataPayment,$payments_data);
		$data = array_merge($data,$dataPagos);

		$plantilla = 'admin_viajero_detalle.html';	

	}else{
		if(isset($travelerData['error']) && $travelerData['error'] == 'SESSION_ENDED'){
			header("Location:".WEB_URL."SALIR");
			exit;
		}else if(isset($travelerData['error']) && stripos($travelerData['error'], 'Could not connect to server') !== false){
			die('API error: '. $travelerData['error']);
		}else{
			$plantilla = '403.html';
		}
	}
	

}else{
	$plantilla = '403.html';
}
echo makeTemplate($plantilla, $data, 'admin');
?>
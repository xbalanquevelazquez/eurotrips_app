<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

#$DEBUG = TRUE;
$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

date_default_timezone_set('America/Mexico_City');
$fechaHoy = date('Y-m-d');

$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $email = $Admin->obtenerUsr('usr');
$userData = $Admin->getUserData($email);

$data['fechaHoy'] = $fechaHoy;
$data['mensaje'] = '';

if(count($userData)>0){
	$data['year'] = $traveler_year_asoc = $Admin->obtenerUsr('year');
	$traveler_id = $userData['traveler_id'];
	$data['traveler_id'] = $traveler_id;
	$trip_id = $userData['trip_id'];

	if(!is_null($trip_id) && $trip_id!==''){
		$trip = $Admin->getTrip($trip_id);
		if($trip){
			$data = array_merge($data,$trip);
		}
	}else{
		$data['trip_status'] = 'No asignado';
		$data['trip_name'] = '-';
		$data['itinerary_url'] = '';
	}

	$data['colorTripStatus'] = '';
	if($data['trip_status'] !== ''){
		switch($data['trip_status']){
			case 'Pendiente':
			case 'preparing':
				$data['colorTripStatus'] = 'texto-grismedio';
				$data['trip_status'] = 'Pendiente';
				break;
			case 'Confirmado':
			case 'confirmed':
				$data['colorTripStatus'] = 'texto-aqua';
				$data['trip_status'] = 'Confirmado';
				break;
			default://sin asignar
				$data['colorTripStatus'] = 'texto-grismedio';
				$data['trip_status'] = 'No asignado';
				break;
		}
	}

	$data['btn_itinerario'] = '';
	if($data['itinerary_url'] !== '' && $data['itinerary_url'] !== '#'){
		$data['btn_itinerario'] = '<a href="'.$data['itinerary_url'].'" class="btn primario" target="_blank">'.__('Consultar itinerario').'</a>';
	}

	$data['support_number'] = 'No asignado aún';
	$data['CSSbtnAssistance'] = 'hidden';
	$data['assistance_documents'] = '';

	if(isset($userData['support_number']) && !empty($userData['support_number'])){
		$data['support_number'] = $userData['support_number'];

	}
	$assistance_file = $Admin->getDocumentByType($traveler_id,$document_type='assistance_file');
	if(isset($assistance_file[0])){
		$assistance_file = $assistance_file[0];
		$data['assistance_file'] = WEB_URL.'webservice/verDocumento.php?id='.$assistance_file['document_id'].'&trav='.$traveler_id.'&tipo=assistance_file';
		$data['CSSbtnAssistance'] = '';
		$data['assistance_documents'] = makeTemplate('panel_home_assistance_documents.html',$data,'site');
	}

	####   PASSPORT 1
	$passport = $Admin->getDocumentByType($traveler_id,$document_type='first_passport');
	if(isset($passport[0])){
		$passport = $passport[0];
	}

	if(count($passport) > 0){ 
		
		switch($passport['status']){
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

	}else{
		$data['btnPassportColor'] = 'texto-amarillo';
		$data['passportCSS'] = 'fa-solid fa-exclamation-triangle '.$data['btnPassportColor'];
		$data['passportStatus'] = 'Faltante';
	}

	### ---- 
	####   PASSPORT 2
	$passport_2 = $Admin->getDocumentByType($traveler_id,$document_type='second_passport');
	if(isset($passport_2[0])){
		$passport_2 = $passport_2[0];
	}

	if(count($passport_2) > 0){ 
		
		switch($passport_2['status']){
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

	}else{
		$data['btnPassport2Color'] = 'texto-amarillo';
		$data['passport2CSS'] = 'fa-solid fa-exclamation-triangle '.$data['btnPassport2Color'];
		$data['passport2Status'] = 'Opcional';
	}

	### ---- 
	$data['panelPagos'] = '<span class="capsula hueca texto-grismedio acenter mt1">'.__('Podrá realizar pagos del viaje cuando este se asigne.').'</span>';
	if($data['trip_status'] !== 'No asignado'){
		$data['btnDetalle'] = FALSE;
		$payments_data = $Admin->getPayments($traveler_id);
		$dataPagos = $Admin->getPaymentsProcessed($data,$payments_data);
		$data['panelPagos'] = makeTemplate('panel_pagos_home.html',$dataPagos,'site');
	}

	### NOTIFICACIONS

	$notifications = $Admin->getNotifications($traveler_id);

	$data['alertas'] = formatNotifications($notifications['notifications']);
	$data['totalRecords'] = $notifications['totalRecords'];


	$data['trip_status'] = __($data['trip_status']);
	$data['passportStatus'] = __($data['passportStatus']);
	$data['passport2Status'] = __($data['passport2Status']);

	$plantilla = 'panel_home.html';
}else{
	$plantilla = '403.html';

}


echo makeTemplate($plantilla, $data, 'site');
?>
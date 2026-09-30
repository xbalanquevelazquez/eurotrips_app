<?php
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $Admin->obtenerUsr('usr');

$data['mensaje'] = '';
$data['tipoRegistro'] = 'PARCIALIDADES';

if(!empty($data3) && $data3 !== ''){
	$data['trip_id'] = $trip_id = $UUID = $data3;

	if(isset($_POST['sendArr'])) {
		$sendArr = $_POST['sendArr'];

		$continue = TRUE;
		if($sendArr['trip_name'] == ''){
			$continue = FALSE;
			$data['alerta_trip_name'] = '<div class="warning">Este campo no puede estar vacío.</div>';
		}

		if($sendArr['itinerary_url'] == ''){
			$sendArr['itinerary_url'] = '#';
		}

		if($sendArr['cost'] == ''){
			$sendArr['cost'] = 0;
		}

		if($continue){
			$res = $Admin->API->put('/api/trips/'.$UUID, $sendArr);
			if($res['success']){
				$Admin->setMensaje('<div class="pm5 alerta verde">Se guardaron los datos del viaje.</div>');
				header('Location:'.WEB_URL.$data1.'/'.$data2.'/'.$data3);
				exit;
			}else{
				$data['alerta_envio'] = '<div class="warning">Error: httpCode '.$res['httpCode'].' : '.$res['data']['message'].'</div>';
			}
		}

	}

	/*
	::
	::
	CAMBIO
	::
	::
	*/
	$trip = $Admin->getTrip($UUID);

	if(!isset($trip['error'])){		
		if($trip['limit_date'] == ''){
			$fechaOriginal = $trip['created_at'];
			$trip['limit_date'] = $fechaMasUnAnio = date('Y-m-d', strtotime($fechaOriginal . ' + 1 year'));; 
		}
		$trip['limit_date'] = convertirFecha($trip['limit_date']);
		
		$data = array_merge($data,$trip);
	}else{
		if($trip['error'] == 'SESSION_ENDED'){
			header("Location:".WEB_URL."SALIR");
			exit;	
		}else if($trip['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $trip['message'].'</div>');
		}
	}

	$data['returnLink'] = WEB_URL.$data1;
	$data['departure_date'] = '';#esta NULL por eso la pongo vacía

	$data['label_status'] = '';
	if(isset($data['status'])){
		switch($data['status']){
			case 'preparing':
				$data['status_selected_1'] = ' selected="selected"';//preparing
				$data['status_selected_2'] = '';//confirmed
				$data['label_status'] = 'Pendiente';
				break;
			case 'confirmed':
				$data['status_selected_1'] = '';//preparing
				$data['status_selected_2'] = ' selected="selected"';//confirmed
				$data['label_status'] = 'Confirmado';
				break;
		}
	}

	if(isset($data['currency'])){
		$getCurrency = getCurrency($data['currency']);
		$data['monedaYSimbolo'] = $getCurrency['monedaYSimbolo'];
	}

	$costoTotal = 0;
	$subtotalParcialidades = 0;
	$diferenciaTotalParcialidades = 0;

	if(isset($data['cost']) && !is_null($data['cost'])){
		$costoTotal = $data['cost'];
	}else{
		$data['cost'] = '';
	}

	$calendario = $Admin->getCalendarioPago($trip_id);
	if(count($calendario)>0){
		foreach($calendario as $parcialidad){
			$subtotalParcialidades += $parcialidad['amount'];
		}
	}

	$diferenciaTotalParcialidades = $costoTotal - $subtotalParcialidades;

	$data['costoTotal'] = $costoTotal;
	$data['subtotalParcialidades'] = $subtotalParcialidades;
	$data['diferenciaTotalParcialidades'] = $diferenciaTotalParcialidades;

	$mensaje = $Admin->getMensaje();
	if($mensaje != ''){
		$data['mensaje'] = $mensaje;
		$Admin->cleanMensaje();
	}

	$data['alerta_envio'] = '';
	$data['alerta_trip_name'] = '';
	$data['alerta_itinerary_url'] = '';
	$data['alerta_cost'] = '';
}

$bufferOptions = '';
if(!isset($data['year']) || empty($data['year'])){
	$data['year'] = date("Y");
}
for($a = CURRENT_YEAR_REGISTER;$a >= INIT_YEAR_OPTIONS;$a--){
	$selected = '';
	if($a == $data['year']) $selected = 'selected="selected"';
	$bufferOptions .= '<option value="'.$a.'" '.$selected.'>'.$a.'</option>';
}
$data['yearOptions'] = $bufferOptions;

$plantilla = 'admin_viaje_detalle.html';
if($Admin->esConsultor()){
	$data['consultorHidden'] = ' hidden';
	$data['consultorShow'] = '';
	$plantilla = 'admin_viaje_detalle_consulta.html';
}else if($Admin->esEditor()){
	$plantilla = 'admin_viaje_detalle_editor.html';
}

echo makeTemplate($plantilla, $data, 'admin');
?>
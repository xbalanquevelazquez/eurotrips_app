<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;
$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;
$data['ONLINE_MINIMUM_PAY'] = ONLINE_MINIMUM_PAY;

date_default_timezone_set('America/Mexico_City');
$fechaHoy = date('Y-m-d');

$data['NOMBRE'] = $name = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $email = $Admin->obtenerUsr('usr');
$userData = $Admin->getUserData($email);

$trip_id = '';

$data['montoMayor'] = __('El monto es mayor a lo que falta por pagar');
$data['montoMenor'] = __('El monto es menor al pago mínimo necesario');
$data['montoCero'] = __('El monto no puede ser cero');

$data['alertFaltaArchivo'] = __('Selecciona un archivo');
$data['alertFormatoNoPermitido'] = __('Formato no permitido.');
$data['alertExcedeTamanio'] = __('El archivo excede el tamaño permitido (2 MB).');


if(count($userData)>0){
	$traveler_id = $userData['traveler_id'];
	$data['traveler_id'] = $traveler_id;
	$trip_id = $userData['trip_id'];
	$data['year'] = $userData['year'];
}

$data['fechaHoy'] = $fechaHoy;
$currency = '';
$data['mensaje'] = '';
$mensaje = $Admin->getMensaje();
if($mensaje != ''){
	$data['mensaje'] = $mensaje;
	$Admin->cleanMensaje();
}


$data['returnLink'] = WEB_URL.'HOME/';

if(!is_null($trip_id) && $trip_id!==''){
	$trip = $Admin->getTrip($trip_id);
	$trip['costoTotal'] = $costoTotal = $trip['cost'];
	$currency = $trip['currency'];
	if($trip){
		$data = array_merge($data,$trip);
	}
}else{
	$data['trip_status'] = 'No asignado';
	$data['trip_name'] = '-';
	$data['itinerary_url'] = '';
}

if(			$data2=='detalle'){ include_once("page.$data1.detalle.php");}
else if(	$data2=='stripe'){ 	include_once("page.$data1.stripe.php");}
else {

	if(	$data2=='success'){ 
		$data['mensaje'] .= "<div class='pm5 mb1 alerta aqua'>".__('Gracias. Estamos verificando su pago.')."</div>";
	}else if(	$data2=='canceled'){ 
		$data['mensaje'] .= "<div class='pm5 mb1 alerta roja'>".__('El usuario canceló el pago.')."</div>";
		if(isset($data3) && esUUID($data3)){
			$UUID=$data3;
			$payment_data = $Admin->getPaymentByID($UUID);
			unset($payment_data['payment_id']);
			unset($payment_data['traveler_id']);
			
			$payment_data['payment_status'] = 'canceled';
			$res = $Admin->API->put('/api/payments/'.$UUID,$payment_data);
			header('Location:'.WEB_URL.'PAGOS/canceled');
		}
	}

	if(isset($_FILES) && count($_FILES) >= 1 && isset($_POST['tipo'])){
		$document_type = $_POST['tipo'];
		
		### DOCUMENTO PAGO VIAJE
		if(isset($_FILES['pago'])){
			$pagoDoc = $_FILES['pago'];
			$folder = STORAGE_PATH.$traveler_id;
			$folder_relative = $traveler_id;

			if($currency == ''){
				$Admin->setMensaje( "<div class='pm5 mb1 alerta roja'>Se debe tener asignado un viaje y un tipo  de cambio.</div>" );
			}else{
				$currentRate = $Admin->getCurrentRateByCurrency($currency); 

				if(!is_dir($folder)){
				    mkdir($folder,0755,true);
				}

				if (!function_exists('finfo_open')) {
					$Admin->setMensaje( "<div class='pm5 mb1 alerta roja'>La extensión Fileinfo no está habilitada.</div>" );
				    
				}else{
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mime = finfo_file($finfo, $pagoDoc['tmp_name']);
					#finfo_close($finfo);

					$extension = strtolower(pathinfo($pagoDoc['name'], PATHINFO_EXTENSION));
			
					$tiposPermitidos = [
					    'pdf'  => 'application/pdf',
					    'jpg'  => 'image/jpeg',
					    'jpeg' => 'image/jpeg',
					    'png'  => 'image/png'
					];

					if (!isset($tiposPermitidos[$extension]) || $tiposPermitidos[$extension] !== $mime) {
					    $Admin->setMensaje('Tipo de archivo no válido.');
					}else{
						$basePath = $folder_relative.DIRECTORY_SEPARATOR;
						$ext = '.enc';
						$fileName = validarNombreArchivo($basePath,$document_type,$fechaHoy,$ext);
						
						Document::encryptFile(
						    $pagoDoc['tmp_name'],
						    STORAGE_PATH.$fileName
						);

						if(file_exists(STORAGE_PATH.$fileName)){

							$docDataCreate = [
							  "traveler_id" 	=> $traveler_id,
							  "document_type" 	=> $document_type,
							  "mime_type" 		=> $pagoDoc['type'],
							  "notes" 			=> "",
							  "document_path" 	=> $fileName
							];
							
							$createDoc = $Admin->API->post('/api/documents',$docDataCreate);

							if($createDoc['success'] && isset($createDoc['data']['document_id'])){
								$document_id = $createDoc['data']['document_id'];
								$dataPayment = [
									"traveler_id" => $traveler_id,
								    "amount_original" => "0",
								    "currency_original" => $currency,
								    "exchange_rate" => $currentRate,
								    "amount_mxn" => "0",
								    "payment_type" => "$document_type",
								    "payment_status" => "pending",
								    "stripe_payment_intent_id" => "",
								    "document_id" => "$document_id",
								    "paid_at" => NULL,
								    "comments" => "",
								    "document_type" => NULL
								];

								$createPayment = $Admin->API->post('/api/payments',$dataPayment);

								if($createPayment['success'] && isset($createPayment['data']['payment_id'])){
									$payment_id = $createPayment['data']['payment_id'];


									$tipo_pago_convertido = convertirTipoPago($document_type);
									#'general', 'payment_reminder', 'document_uploaded', 'document_rejected', 'document_approved', 'trip_update', 'traveler_update'
									$message = "<span class='strong'>{$data['NOMBRE']}</span> cargó un documento de pago (<span class='strong'>$tipo_pago_convertido</span>) que debe ser validado";
									$resNotif = $Admin->addNotification($traveler_id,'document_uploaded','admin',$title = 'Documento subido',$message);

									$Admin->setMensaje( "<div class='pm5 mb1 alerta verde'>".__('Se guardo el archivo correctamente.')."</div>" );
								}else{
									$Admin->setMensaje( "<div class='pm5 mb1 alerta roja'>Error al registrar el pago.</div>" );
								}
							}else{
								$Admin->setMensaje( "<div class='pm5 mb1 alerta roja'>No se guardó el archivo correctamente.</div>" );
							}
						}else{
							$Admin->setMensaje( "<div class='pm5 mb1 alerta roja'>El archivo no se subió al servidor.</div>" );
						}
					}
				}
			}
			header('Location:'.WEB_URL.$data1);
		}
	}

	$plantilla = 'panel_pagos.html';
	$plantillaPagos = 'panel_pagos_blocked.html';

	if($data['trip_status'] !== 'No asignado'){

		$plantillaPagos = 'panel_pagos_activado.html';

		$data['btnDetalle'] = TRUE;
		$payments_data = $Admin->getPayments($traveler_id);
		$dataPagos = $Admin->getPaymentsProcessed($data,$payments_data);
		$data = array_merge($data,$dataPagos);
		$data['pagoMinimoRaw'] = $data['minimoAPagar'];//$data['totalAdeudo']

		if($dataPagos['totalPagado'] >= $dataPagos['trip_cost']){//PAGO DEL VIAJE COMPLETO
			$moduloPagoHabilitado = 'panel_pago_deshabilitado.html';
		}else{
			$moduloPagoHabilitado = 'panel_pago_habilitado.html';			
		}

		if($data['minimoAPagar'] > 0){//$data['totalAdeudo']
			#STRIPE DESHABILITADO
			#PAGO MINIMO DESHABILITADO
			$data['pagoMinimo'] = "<div class='alerta amarilla pm5 mb1'>".__('Tu pago mínimo en línea debe ser').": <span class='nowrap'>".formatearCurrency($data['minimoAPagar'],$data['currency'])."</span></div>";//$data['totalAdeudo']
		}else{
			$data['pagoMinimo'] = '';
		}
		$data['restante'] = $costoTotal - $data['totalPagado'];

		$pantillaModuloPago = 'panel_pago_no_disponible.html';
		$lastRate = $Admin->getLastRates();
		$data['fechaTipoCambio'] = '-';
		if(isset($lastRate['data']['data'][0])){
			$lastRate = $lastRate['data']['data'][0];
			$data['fechaTipoCambio'] = convertirFecha($lastRate['date']);
		}
		$data['rate'] = $Admin->getCurrentRateByCurrency($data['currency']);

		if($data['rate'] > 0){
			#STRIPE DESHABILITADO
			#$pantillaModuloPago = 'panel_pago_stripe.html';
			$pantillaModuloPago = '_blank.html';
		}

		$data['moduloPagoHabilitado'] = makeTemplate($moduloPagoHabilitado, $data, 'site');

		$data['moduloPago'] = makeTemplate($pantillaModuloPago, $data, 'site');

		$currencyData = getCurrency($data['currency']);
		$data['simboloMoneda'] = $currencyData['simboloMoneda'];
		$data['moneda'] = $currencyData['moneda'];
	}
	$data['plantillaPagos'] = makeTemplate($plantillaPagos, $data, 'site');

	echo makeTemplate($plantilla, $data, 'site');
}
?>
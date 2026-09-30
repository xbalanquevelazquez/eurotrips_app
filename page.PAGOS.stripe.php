<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$DEBUG = TRUE;
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

date_default_timezone_set('America/Mexico_City');
$fechaHoy = date('Y-m-d');
$fechaAhora = date('Y-m-d H:i:s');

$data['NOMBRE'] = $name = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $email = $Admin->obtenerUsr('usr');
$userData = $Admin->getUserData($email);

if(count($userData)>0){
	$traveler_id = $userData['traveler_id'];
	$traveler_year = $userData['year'];
	$data['traveler_id'] = $traveler_id;
	$trip_id = $userData['trip_id'];

}
$data['fechaHoy'] = $fechaHoy;
$data['returnLink'] = WEB_URL.'/PAGOS/';
$data['mensaje'] = '<span class="capsula hueca texto-grismedio acenter">Falta configuración del módulo.</span>';
$continuar = FALSE;

$montoPagoEnLinea = 0;

if(!empty($_POST['montoPagoEnLinea']) && $_POST['montoPagoEnLinea'] !== ''){

	$montoPagoEnLinea = $_POST['montoPagoEnLinea'] ?? '';
	$montoPagoEnLinea = trim($montoPagoEnLinea);

	// Convierte comas a punto por si acaso
	$montoPagoEnLinea = str_replace(',', '.', $montoPagoEnLinea);

	if (!is_numeric($montoPagoEnLinea)) {
	   	$data['mensaje'] = '<span class="capsula hueca texto-alerta acenter">Monto inválido.</span>';
	   	$continuar = FALSE;
	}else{
		$montoPagoEnLinea = (float)$montoPagoEnLinea;

		if ($montoPagoEnLinea <= 0) {
		   	$data['mensaje'] = '<span class="capsula hueca texto-alerta acenter">El monto debe ser mayor a cero.</span>';
		   	$continuar = FALSE;
		}else{
			if (!preg_match('/^\d+(\.\d{1,2})?$/', $montoPagoEnLinea)) {
			   	$data['mensaje'] = '<span class="capsula hueca texto-alerta acenter">El monto debe tener máximo dos decimales.</span>';
			   	$continuar = FALSE;
			}

			$montoPagoEnLinea = (float)$montoPagoEnLinea;

			if(is_null($trip_id) || empty($trip_id)){
			   	$data['mensaje'] = '<span class="capsula hueca texto-alerta acenter">Debe tener un viaje asignado para hacer pagos.</span>';
			   	$continuar = FALSE;
			}else{
				$trip = $Admin->getTrip($trip_id);
				$dataPayment = [
					"trip_id" => $trip_id,
					"traveler_id" => $traveler_id,
					"fechaHoy" => $fechaHoy,
					"cost" => $trip['cost'],
					"currency" => $trip['currency'],
					"limit_date" => $trip['limit_date'],
					"btnDetalle" => FALSE //GENERAR EL BOTÓN DE DETALLES
				];
				
				$dataPagos = $Admin->getPaymentsProcessed($dataPayment);
				$costoTotal = $dataPagos['trip_cost'];
				$totalPagado = $dataPagos['totalPagado'];
				$totalAdeudo = $dataPagos['totalAdeudo'];
				$totalPagadoLabel = $dataPagos['totalPagadoLabel'];
				$totalAdeudoLabel = $dataPagos['totalAdeudoLabel'];
				$totalAdeudoParcialidades = $dataPagos['totalAdeudoParcialidades'];

				$minimo = $dataPagos['minimoAPagar'];//$totalAdeudo;
				$maximo = $costoTotal - $totalPagado;

				$montoCentavos = (int) round($montoPagoEnLinea * 100);
				$minimoCentavos = (int) round($minimo * 100);
				$maximoCentavos = (int) round($maximo * 100);

				$currency = $trip['currency'];

				if ($montoCentavos < $minimoCentavos) {
				   	$data['mensaje'] = '<span class="capsula hueca texto-alerta acenter">El monto mínimo es '.formatearCurrency($minimo,$currency).'.</span>';
				   	$continuar = FALSE;
				}

				if ($montoCentavos > $maximoCentavos) {
				   	$data['mensaje'] = '<span class="capsula hueca texto-alerta acenter">El monto máximo es '.formatearCurrency($maximo,$currency).' (costo total del viaje menos los pagos validados).</span>';
				   	$continuar = FALSE;
				}

				if($montoCentavos >= $minimoCentavos && $montoCentavos <= $maximoCentavos){

					$todayRate = $Admin->getCurrentRateByCurrency($currency);
					$lastRate = $Admin->getLastRates();
					if(isset($lastRate['data']['data'][0])){
						$lastRate = $lastRate['data']['data'][0];
						$lastRateDate = convertirFecha($lastRate['date']);
					}
					if(empty($todayRate)){
					   	$data['mensaje'] = '<span class="capsula hueca texto-alerta acenter">Se podrá pagar en línea cuando se registre el tipo de cambio para el día de hoy en el sistema.</span>';
					   	$continuar = FALSE;
					}else{
						$tipoCambio = $todayRate;
						$montoMXN = round($montoPagoEnLinea * $tipoCambio, 2);
						$montoStripe = (int) round($montoMXN * 100);

						//GENERAR DOCUMENT
						$docDataCreate = [
							  "traveler_id" 	=> $traveler_id,
							  "document_type" 	=> "empty",
							  "mime_type" 		=> "",
							  "notes" 			=> "",
							  "document_path" 	=> ""
							];
							
						$createDoc = $Admin->API->post('/api/documents',$docDataCreate);

						if($createDoc['success'] && isset($createDoc['data']['document_id'])){
							$document_id = $createDoc['data']['document_id'];
							//GENERAR PAYMENT
							$dataPayment = [
										"traveler_id" => $traveler_id,
									    "amount_original" => $montoPagoEnLinea,
									    "currency_original" => $currency,
									    "exchange_rate" => $todayRate,
									    "amount_mxn" => $montoMXN,
									    "payment_type" => "online",
									    "payment_status" => "pending",
									    "stripe_payment_intent_id" => "",
									    "document_id" => $document_id,
									    "paid_at" => NULL,
									    "comments" => __('Pago en Stripe de').' '.$name.' ('.$trip['trip_name'].'), '.__('fecha de tipo de cambio').': '.$lastRateDate,
									    "document_type" => NULL
									];

							$createPayment = $Admin->API->post('/api/payments',$dataPayment);
							if($createPayment['success'] && isset($createPayment['data']['payment_id'])){
								$payment_id = $createPayment['data']['payment_id'];
								$continuar = TRUE;
							}else{
								$data['mensaje'] = "<div class='pm5 mb1 alerta roja'>Error al registrar el pago.</div>";
								$continuar = FALSE;
							}
						}else{
							$data['mensaje'] = "<div class='pm5 mb1 alerta roja'>No se guardó el archivo correctamente.</div>" ;
							$continuar = FALSE;
						}
					}
				}
			}
		}		
	}

	if($continuar){
		require_once LIB_PATH.'stripe/init.php';

		\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

		try{
		    $checkout = \Stripe\Checkout\Session::create([
		        'mode' => 'payment',
		        'success_url' => WEB_URL.'PAGOS/success',
		        'cancel_url' => WEB_URL.'PAGOS/canceled/'.$payment_id,
		        'line_items' => [[
		            'price_data' => [
		                'currency' => 'mxn',
		                'product_data' => [
		                    'name' => 'Pago de '.$name.' ('.$trip['trip_name'].')'
		                ],
		                'unit_amount' => $montoStripe
		            ],
		            'quantity' => 1
		        ]],
		        'metadata' => [
		            'traveler_id' => $traveler_id,
		            'payment_id' => $payment_id,
		            'document_id' => $document_id,
		            'trip_id' => $trip_id,
		            'amount_original' => $montoPagoEnLinea,
		            'amount_mxn' => $montoMXN,
		            'currency_original' => $currency,
		            'exchange_rate' => $tipoCambio,
		            'payment_type' => 'online'
		        ]

		    ]);

		    header("Location: ".$checkout->url);
		    exit;

		}catch(Exception $e){
		    $data['mensaje'] = '<span class="capsula hueca texto-alerta acenter">'.$e->getMessage().'</span>';
		}
	}

}else{
	$data['mensaje'] = '<span class="capsula hueca texto-alerta acenter">No se envió el dato de monto o es incorrecto.</span>';
}

echo makeTemplate('panel_pago_stripe_mensaje.html',$data,'site');
?>
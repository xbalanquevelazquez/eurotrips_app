<?php
header("Access-Control-Allow-Origin: *");
session_start();

#header("Content-Type: application/json", true);
define('VIEWABLE',TRUE);
include_once("../cnf/cnfg.app.php");
include_once("../funct/funcionalidad.php");

$continue = TRUE;
$error = '';
$fechaHoy = date('Y-m-d');
$fechaAhora = date('Y-m-d H:i:s');

if (empty($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
    http_response_code(400);
    exit('Invalid request');
}

file_put_contents(
    LOGS_PATH . 'stripe_debug.log',
    date('Y-m-d H:i:s') . " WEBHOOK RECIBIDO\n",
    FILE_APPEND
);


require_once LIB_PATH.'stripe/init.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

//====================================================
// Leer petición
//====================================================

$payload = @file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

//====================================================
// Validar firma
//====================================================

$event = null;

try{

    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $signature,
        STRIPE_WEBHOOK_SECRET
    );

}catch(\UnexpectedValueException $e){
	$log = [
	    'fecha' => date('Y-m-d H:i:s'),
	    'tipo_evento' => $event->type,
	    'datos' => json_decode('{"http_response_code":400,"mensaje":"Payload inválido."}', true)
	];	
	registroLog($log);
    http_response_code(400);
    exit('Payload inválido.');

}catch(\Stripe\Exception\SignatureVerificationException $e){
	$log = [
	    'fecha' => date('Y-m-d H:i:s'),
	    'tipo_evento' => $event->type,
	    'datos' => json_decode('{"http_response_code":400,"mensaje":"Firma inválida."}', true)
	];	
	registroLog($log);
    http_response_code(400);
    exit('Firma inválida.');

}

//====================================================
// Guardar log
//====================================================
switch($event->type){

    case 'checkout.session.completed':
    case 'payment_intent.succeeded':
		$log = [
		    'fecha' => date('Y-m-d H:i:s'),
		    'tipo_evento' => $event->type,
		    'datos' => json_decode($payload, true)
		];
		registroLog($log);
        break;
    default:

        // Evento ignorado

        break;

}

//====================================================
// Procesar eventos
//====================================================

switch($event->type){

    case 'checkout.session.completed':

        $session = $event->data->object;

        if($session->payment_status !== 'paid'){
		    http_response_code(200);
		    exit('Pago no completado.');
		}

        //------------------------------------------------
        // Metadata enviada al Checkout
        //------------------------------------------------

        $traveler_id = $session->metadata->traveler_id ?? '';
        $payment_id = $session->metadata->payment_id ?? '';
        $document_id = $session->metadata->document_id ?? '';
        $trip_id = $session->metadata->trip_id ?? '';
        $amount_original = $session->metadata->amount_original ?? '';
        $amount_mxn = $session->metadata->amount_mxn ?? '';
        $currency_original = $session->metadata->currency_original ?? '';
        $exchange_rate = $session->metadata->exchange_rate ?? '';
        $payment_type = $session->metadata->payment_type ?? '';

        //------------------------------------------------
        // Datos propios de Stripe
        //------------------------------------------------

        $stripe_session = $session->id;
        $payment_intent = $session->payment_intent;
        $payment_status = $session->payment_status;
        $currency = strtoupper($session->currency);
        $amount_total = $session->amount_total / 100;

        $paymentIntent = \Stripe\PaymentIntent::retrieve(
		    $session->payment_intent
		);

        $apiAuth = $Admin->API->login(ADMIN_USR, ADMIN_PSW);
		$token = $apiAuth['data']['token'];
		$Admin->API->setToken($token);
		$log = [
		    'fecha' => date('Y-m-d H:i:s'),
		    'tipo_evento' => 'Login',
		    'datos' => $apiAuth
		];	
		registroLog($log);

        $payment = $Admin->getPaymentByID($payment_id);

		$log = [
		    'fecha' => date('Y-m-d H:i:s'),
		    'tipo_evento' => 'getPayment('.$payment_id.')',
		    'datos' => $payment
		];	
		registroLog($log);

        //------------------------------------------------
        // Aquí actualizarás tu API
        //------------------------------------------------
        
        $peticion = [
		    "amount_original" => $amount_original,
		    "currency_original" => $currency_original,
		    "exchange_rate" => $exchange_rate,
		    "amount_mxn" => $amount_total,
		    "payment_type" => $payment_type,
		    "payment_status" => "validated",
		    "stripe_payment_intent_id" => $payment_intent,
		    "document_id" => $document_id,
		    "comments" => $payment['comments'],
		    "paid_at" => $fechaAhora
        ];

        $updatePayment = $Admin->API->put('/api/payments/'.$payment_id,$peticion);
		$log = [
		    'fecha' => date('Y-m-d H:i:s'),
		    'tipo_evento' => 'updatePayment('.$payment_id.')',
		    'datos' => $updatePayment
		];	
		registroLog($log);

		$pay_type = convertirTipoPago($payment_type);
		$traveler = $Admin->getTraveler($traveler_id);
		$language = $traveler['language'];

		if($payment_type == 'online'){
			$montoOriginalFormateado = formatearCurrency($amount_original,$currency_original);
			$montoMXNFormateado = formatearCurrency($amount_total,0);

			$tripData = $Admin->getTrip($trip_id);
			$trip_name = $tripData['trip_name'];

			switch($language){
				case 'es':
					$contenido = "Hemos recibido tu pago de <span style='font-weight:bold;'>$montoOriginalFormateado</span> (cobrado como <span style='font-weight:bold;'>$montoMXNFormateado</span> al tipo de cambio <span style='font-weight:bold;'>$exchange_rate</span>).<br/><br/>";
					$contenido .= "Pago aplicado a tu viaje <span style='font-weight:bold;'>$trip_name</span>.<br/><br/>";
					break;
				case 'en':
					$contenido = "We have received your payment of <span style='font-weight:bold;'>$montoOriginalFormateado</span> (charged as <span style='font-weight:bold;'>$montoMXNFormateado</span> at an exchange rate of <span style='font-weight:bold;'>$exchange_rate</span>).<br/><br/>";
					$contenido .= "Payment applied to your trip <span style='font-weight:bold;'>$trip_name</span>.<br/><br/>";
					break;
			}

		}

		$tipo_pago_convertido = convertirTipoPago($payment_type);
		$traveler_name = $traveler['name'];
		#'general', 'payment_reminder', 'document_uploaded', 'document_rejected', 'document_approved', 'trip_update', 'traveler_update'
		$message = "Se validó un pago (<span class='strong'>$tipo_pago_convertido</span>) de <span class='strong'>{$traveler_name}</span>";
		$resNotif = $Admin->addNotification($traveler_id,'payment_reminder','admin',$title = 'Pago validado',$message);

		$estatus_mail = __('validado',$language);        
		$datosMensaje = [
			"siteURL" => WEB_URL,
			"contenido" => 	$contenido
		];
		$mailResponse = enviarMensaje(
							$destinatario = $traveler['email'],
							$tituloMensaje='Eurotrips - '.__($pay_type,$language).' - '.$estatus_mail, 
							$templateName='correo_aviso.html', 
							$datosMensaje
						);

        break;
    default:
        // Evento ignorado
        break;
}

//====================================================
// Respuesta a Stripe
//====================================================

http_response_code(200);

echo 'OK';

function registroLog($log){
	$archivoLog = LOGS_PATH.'stripe.log';

	file_put_contents(

	    $archivoLog,

	    json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
	    .PHP_EOL
	    ."========================================================"
	    .PHP_EOL,

	    FILE_APPEND
	);
}
?>
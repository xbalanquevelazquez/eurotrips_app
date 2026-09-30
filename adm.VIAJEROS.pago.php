<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;
$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;
$data['PAYMENT_REJECTED_STATUS'] = PAYMENT_REJECTED_STATUS;
$data['PAYMENT_VALIDATED_STATUS'] = PAYMENT_VALIDATED_STATUS;

date_default_timezone_set('America/Mexico_City');
$fechaHoy = date('Y-m-d');

if(!empty($data3) && $data3 !== ''){
	$payment_id = $data3;
	$data['payment_id'] = $payment_id;

	$payment = $Admin->getPaymentByID($payment_id);

	if(count($payment)>0){

		$data['currency_original'] = $payment['currency_original'] ?? '';

		$data['exchange_rate'] = number_format($payment['exchange_rate'] ?? 0,2);
		
		$currencyData = getCurrency($payment['currency_original'] ?? 1);

		$data['exchange_rate_chip'] = '';
		if($payment['payment_status'] !== 'pending'){
			$data['exchange_rate_chip'] = '<div class="tipoCambio trip-chip mt1">' . $currencyData['simboloMoneda'] . '1 ' . $currencyData['moneda'] . ' = ' . number_format($payment['exchange_rate'],2) . ' MXN</div>';
		}
		
		$data['currentPaymentSaldoAFavor'] = 0;
		$data['nota_rechazado'] = '';
		if($payment['payment_status'] == 'validated' || $payment['payment_status'] == PAYMENT_VALIDATED_STATUS){
			switch($payment['payment_type']){
				case 'transfer':
				case 'cash':
				case 'online':
					$data['currentPaymentSaldoAFavor'] = $payment['amount_original'];
					break;
			}
		}else if($payment['payment_status'] == 'rejected' || $payment['payment_status'] == 'ejected' || $payment['payment_status'] == PAYMENT_REJECTED_STATUS){
			$data['nota_rechazado'] = '<div class="col bg-post-it texto-post-it pm5 mb1"><div class="smallNote">Las cantidades mostradas sólo se aplicarán si este pago es validado (este pago está rechazado)</div></div>';
		}

		$traveler_id = $payment['traveler_id'];
		$traveler = $Admin->getUserDataByTravelerID($traveler_id);

		$document_id = $payment['document_id'];
		$data['document_id'] = $document_id;

		$document = $Admin->getDocumentByID($document_id);

		$trip = $Admin->gettrip($traveler['trip_id']);
		if(count($trip)>0){
			$data['cost'] = $trip['cost'];
		}

		$dataPayment = [
			"trip_id" => $traveler['trip_id'],
			"traveler_id" => $traveler['traveler_id'],
			"fechaHoy" => $fechaHoy,
			"cost" => 0,
			"currency" => 1,
			"limit_date" => $fechaHoy,
			"btnDetalle" => FALSE //GENERAR EL BOTÓN DE DETALLES
		];

		if(!empty($traveler['trip_id'])){
			$dataPayment = [
				"trip_id" => $traveler['trip_id'],
				"traveler_id" => $traveler['traveler_id'],
				"fechaHoy" => $fechaHoy,
				"cost" => $trip['cost'],
				"currency" => $trip['currency'],
				"limit_date" => $trip['limit_date'],
				"btnDetalle" => FALSE //GENERAR EL BOTÓN DE DETALLES
			];
		}
		$dataPagos = $Admin->getPaymentsProcessed($dataPayment);

		$data['totalPagado'] = $dataPagos['totalPagado'];

		$data['comments'] = $payment['comments'];
		$data['payment_status'] = convertirEstatusPago($payment['payment_status']);
		$data['payment_status_color'] = convertirEstatusColor($payment['payment_status']);
		$data['paid_at'] = '';
		if(!empty($payment['paid_at'])){
			$data['paid_at'] = convertirFechaHora($payment['paid_at']);
		}
		$data['amount_mxn'] = $payment['amount_mxn'];
		$data['amount_mxn_formated'] = formatearCurrency($payment['amount_mxn'],0);
		$data['stripe_payment_intent_id'] = $payment['stripe_payment_intent_id'];

		$data['created_at'] = ajustarFechaHoraTimezone($payment['created_at']);
		$data['payment_type'] = $payment['payment_type'];
		$data['tipoPago'] = convertirTipoPago($payment['payment_type']);
		$data['amount_original'] = $payment['amount_original'];
		$data['visorDocumento'] = $Admin->createVisorDoc($document,$traveler_id);

		$data['returnLink'] = WEB_URL.'/VIAJEROS/detalle/'.$traveler_id;

		$data['name'] = $traveler['name'];
		$data['language'] = $traveler['language'];
		$data['traveler_id'] = $traveler_id;
		$data['fechaHoy'] = $fechaHoy;
		
		$data['simboloMoneda'] = $currencyData['simboloMoneda'];
		$data['moneda'] = $currencyData['moneda'];

		$rates = $Admin->getRates(1,365);
		$ratesJSON = [];
		$fechasDisponibles = [];
		$fechaUltimoRate = $fechaHoy;
		$counter = 0;

		foreach($rates['data'] as $rate){
			if($counter < 1) $fechaUltimoRate = convertirFecha($rate['date']);
			$counter++;
			$ratesJSON[convertirFecha($rate['date'])] = $rate;
			$fechasDisponibles[] = convertirFecha($rate['date']);
		}
		$data['fechaUltimoRate'] = $fechaUltimoRate;

		if(!empty($payment['paid_at'])){
			$data['fechaUltimoRate'] = convertirFecha($payment['paid_at']);
		}

		$data['ratesJSON'] = json_encode($ratesJSON);
		$data['fechasDisponibles'] = json_encode($fechasDisponibles);
		$data['currency'] = $payment['currency_original'];
		
		$currencyID = 'rate_eur';
		switch($data['currency']){
			case 1:
				$currencyID = 'rate_eur';
				break;
			case 2:
				$currencyID = 'rate_usd';
				break;
		}
		$data['currencyID'] = $currencyID;

		if($Admin->esConsultor() || $Admin->esEditor()){
			$plantilla = 'admin_viajero_pago_consulta.html';
		}else{
			$plantilla = 'admin_viajero_pago.html';
		}

		switch($payment['payment_type']){
			case 'online':
				$plantilla = 'admin_viajero_pago_online.html';
				$data['visorDocumento'] = '';
				break;
		}
	}else{
		$plantilla = '403.html';
	}
	echo makeTemplate($plantilla, $data, 'admin');
}
?>
<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;
$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

date_default_timezone_set('America/Mexico_City');
$fechaHoy = date('Y-m-d');

$data['NOMBRE'] = $name = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $email = $Admin->obtenerUsr('usr');
$userData = $Admin->getUserData($email);
if(count($userData)>0){
	$traveler_id = $userData['traveler_id'];
	$data['traveler_id'] = $traveler_id;
	$trip_id = $userData['trip_id'];
}
$data['fechaHoy'] = $fechaHoy;
$data['visorDocumento'] = '';


if(!empty($data3) && $data3 !== ''){
	$payment_id = $data3;

	$payment = $Admin->getPaymentByID($payment_id);
	if(count($payment)>0){
		$data['currency_original'] = $payment['currency_original'];
		$data['exchange_rate'] = number_format($payment['exchange_rate'],2);
		$document_id = $payment['document_id'];

		$document = $Admin->getDocumentByID($document_id);

		$data['comments'] = $payment['comments'];
		$data['payment_status'] = convertirEstatusPago($payment['payment_status']);
		$data['payment_status_color'] = convertirEstatusColor($payment['payment_status']);

		$data['created_at'] = convertirFechaHora(ajustarFechaHoraTimezone($payment['created_at']));
		$data['payment_type'] = convertirTipoPago($payment['payment_type']);
		$data['amount'] = $payment['amount_original']==0?'-':formatearCurrency($payment['amount_original'],$payment['currency_original']);
		$data['amount_mxn'] = formatearCurrency($payment['amount_mxn'],0);

		$arrCurrency = getCurrency($payment['currency_original']);

		$data['rateChip'] = '';
		switch ($payment['payment_type']) {
			case 'online':
				$data['rateChip'] = '<div class="chip mt1">' . $arrCurrency['simboloMoneda'] . '1 ' . $arrCurrency['moneda'] . ' = ' . number_format($payment['exchange_rate'],2) . ' MXN</div>';
				break;
			case 'transfer':
			case 'cash':
				if($payment['amount_mxn'] > 0){
					$data['rateChip'] = '<div class="chip mt1">' . $arrCurrency['simboloMoneda'] . '1 ' . $arrCurrency['moneda'] . ' = ' . number_format($payment['exchange_rate'],2) . ' MXN</div>';
				}
				break;
			default:
				break;
		}

		$data['visorDocumento'] = '';
		$data['mostrarVisor'] = 'hidden';

		$data['paid_at'] = '';
		if(!empty($payment['paid_at'])){
			$data['paid_at'] = convertirFechaHora($payment['paid_at']);
		}

		$plantilla = 'panel_pago_detalle.html';
		$data['mostrarVisor'] = '';
		if($payment['payment_type'] == 'online'){
			$plantilla = 'panel_pago_detalle_online.html';
		}else{
			if(!empty($payment['paid_at'])){
				$data['paid_at'] = convertirFecha($payment['paid_at']);
			}
			$data['visorDocumento'] = $Admin->createVisorDoc($document,$traveler_id);
		}
		$data['returnLink'] = WEB_URL.'/PAGOS/';

		$currencyData = getCurrency($payment['currency_original']);
		$data['simboloMoneda'] = $currencyData['simboloMoneda'];
		$data['moneda'] = $currencyData['moneda'];

		$data['payment_status'] = __($data['payment_status']);
		$data['payment_type'] = __($data['payment_type']);
		
	}else{
		$plantilla = '403.html';
	}
	echo makeTemplate($plantilla, $data, 'site');
}
?>
<?php
session_start();

#header("Content-Type: application/json", true);
define('VIEWABLE',TRUE);
include_once("../cnf/cnfg.app.php");
include_once("../funct/funcionalidad.php");

$continue = TRUE;
$error = '';
$fechaHoy = date('Y-m-d');
$fechaAhora = date('Y-m-d H:i:s');
$notifLog = ['Ejecucion'=>$fechaAhora];

define('DIAS_ANTES',[5,1]);


$log = [
    'fecha' => date('Y-m-d H:i:s'),
    'tipo_evento' => "CRON EJECUTADO",
    'datos' => ["YEAR_FILTER"=>YEAR_FILTER]
];
registroLog($log);


$apiAuth = $Admin->API->login(ADMIN_USR, ADMIN_PSW);
$token = $apiAuth['data']['token'];
$Admin->API->setToken($token);
$log = [
    'fecha' => date('Y-m-d H:i:s'),
    'tipo_evento' => 'Login',
    'datos' => $apiAuth
];	
registroLog($log);


$trips = $Admin->getTrips(YEAR_FILTER);

echo "<pre>";
echo "EJECUCIÓN: $fechaAhora <br /><hr /><br />";

if(isset($trips['data'])){

	$notifLog['countTrips'] = count($trips['data']);
	foreach($trips['data'] as $trip) {


		echo "TRIP :: <br />";
		echo "\t name: ".$trip['trip_name']."<br />";
		echo "\t id: ".$trip['trip_id']."<br />";
		echo "\t cost: ".$trip['cost']."<br />";

		$notifLog[$trip['trip_id']] = [
						'trip_name'=>$trip['trip_name'],
						'trip_id'=>$trip['trip_id']
						];
		$parcialidades = $Admin->getCalendarioPago($trip['trip_id']);
		
		$totalCostoViaje = $trip['cost'];
		$totalAdeudoParcialidades = 0;
		$sigPago = 0;
		$sigFechaPago = '';
		$sigFechaPagoLabel = '';
		$adeudoALaFecha = 0;


		//SI NO HAY PARCIALIDADES ESTABLEZCO EL MONTO DEL PAGO FINAL COMO EL COSTO TOTAL DEL VIAJE
		if(count($parcialidades)<=0){
			$amountFinalPayment = $totalCostoViaje;
		}else{//HAY PARCIALIDADES
			$subtotalParcialidades = 0;
			for($i = (count($parcialidades) - 1);$i >= 0; $i--){
		        $parcialidad = $parcialidades[$i];
				$subtotalParcialidades += $parcialidad['amount'];
			}
			$amountFinalPayment = $totalCostoViaje - $subtotalParcialidades;
		}

		//PARCIALIDADES CALCULOS
		$finalPayment = [
			'amount' => $amountFinalPayment,
			'payment_date' => $trip['limit_date']
		];

		array_unshift($parcialidades,$finalPayment);//AGREGA última parcialidad AL PRINCIPIO

		$bandera = TRUE;
		for($i = (count($parcialidades) - 1);$i >= 0; $i--){//sumo los adeudos de fechas vencidas (pasadas)
	        $parcialidad = $parcialidades[$i];
	        #echo "{$parcialidad['payment_date']} :: {$parcialidad['amount']} <br />";
	        if($bandera){
    	        $sigPago = $parcialidad['amount'];
    	        $sigFechaPago = $parcialidad['payment_date'];
    	    }
			if(strtotime(convertirFecha($parcialidad['payment_date'])) < strtotime($fechaHoy)){//VENCIDO
				$totalAdeudoParcialidades += $parcialidad['amount'];
			}else{
				$bandera = FALSE;
			}
			$notifLog[$trip['trip_id']]['parcialidades'][] = [
						'payment_date' => convertirFecha($parcialidad['payment_date'],'es'),
						'amount' => $parcialidad['amount'],
						'vencido' => $bandera
					];
		}

		echo "\t totalAdeudoParcialidades: $totalAdeudoParcialidades <br />";
		echo "\t sigPago: $sigPago <br />\t sigFechaPago: ".convertirFecha($sigFechaPago,'es')."<br />";

		$timeStampSigfechaPago = strtotime(date("Y-m-d",strtotime($sigFechaPago)));
		$timeStampHoy = strtotime($fechaHoy);
		#echo "<br />";
		#echo strtotime(date("Y-m-d",strtotime($sigFechaPago))) - strtotime($fechaHoy);

		$diferencia_segundos = $timeStampSigfechaPago - $timeStampHoy;
		#echo "<br />";
		$diferencia_dias = floor($diferencia_segundos / 86400);
		echo "\t > $diferencia_dias días faltantes<br /><br />";

		#$sigFechaPago = '';


		#print_pre($parcialidades);


		if(in_array($diferencia_dias,DIAS_ANTES)){//ESTA EN EL RANGO ENVIAR UNA ALERTA, PERO VALIDAR PAGO DE CADA VIAJERO
			echo "\t > ALERTA<br /><br />";

			$notifLog[$trip['trip_id']]['alerta'] = TRUE;
			$travelers = $Admin->getTravelersInTravel($trip['trip_id']);
			#print_pre($travelers);
			#echo "<hr />";

			if(count($travelers) > 0){
				$notifLog['countTravelers'] = count($travelers);
				echo "\t TRAVELERS :: <br />";
				foreach($travelers as $traveler){
					$porPagar = 0;
					$totalPagado = 0;
					$currentLang = $traveler['language'];
					$pagos = $Admin->getPayments($traveler['traveler_id']);

					echo "\t\t name: {$traveler['name']}<br />";
					echo "\t\t email: {$traveler['email']}<br />";
					#print_pre($pagos);

					if(count($pagos) > 0){
						//PAGOS REALIZADOS
						foreach($pagos as $pago){
							if($pago['payment_type'] !== 'fee_online' && $pago['payment_type'] !== 'fee_document'){//NO SUMO EL PAGO SI PERTENECE A FEE
								$pagosFiltrados[] = $pago;
								if($pago['payment_status'] == PAYMENT_VALIDATED_STATUS){//VERIFICAR CUAL ES EL ESTATUS FINAL QUE SE USUARA
									$totalPagado += $pago['amount_original'];//SOLO SUMA SI YA ESTA VALIDADO
								}
							}
						}

					}//TIENE PAGOS

					#echo "totalPagado: $totalPagado<br />";


					$adeudoSigFechaTravel = $totalAdeudoParcialidades + $sigPago;
					$adeudoSigFechaCurrentTraveler = $adeudoSigFechaTravel - $totalPagado;

					echo "\t\t adeudoSigFechaTravel: $adeudoSigFechaTravel<br />";
					echo "\t\t totalPagado: $totalPagado<br />";

					$tieneAdeudo = FALSE;
					$contenido = '';

					if($adeudoSigFechaTravel > $totalPagado){

						$tieneAdeudo = TRUE;
						#echo "{$traveler['name']} correo: {$traveler['email']} <br />";
						$contenido = __("El próximo pago sugerido es el",$currentLang)." <span style='font-weight:bold;'>".fechaFormato($sigFechaPago,$currentLang)."</span>, ".__("y deberá pagar mínimo",$currentLang)." <span style='font-weight:bold;'>".formatearCurrency($adeudoSigFechaCurrentTraveler,$trip['currency'])."</span><br />";
						switch($currentLang){
							case 'es':
								$contenido .= "<p>Vaya al <a href=\"".WEB_URL."\" target='_blank'>panel del viajero</a> de Eurotrips para consultar la información.</p>"; 
								break;
							case 'en':
								$contenido .= "<p>Go to the Eurotrips <a href=\"".WEB_URL."\" target='_blank'>traveler dashboard</a> to review the information.</p>"; 
								break;
						}
						

						echo "\t\t contenido: $contenido <br />";
						$datosMensaje = [
							"siteURL" => WEB_URL,
							"contenido" => 	$contenido
						];
						$mailResponse = enviarMensaje(
											$destinatario = $traveler['email'],#$traveler['email'],
											$tituloMensaje='Eurotrips - '.__('Recordatorio de pago',$currentLang), 
											$templateName='correo_aviso.html', 
											$datosMensaje
										);
					}


					echo "\t\t ------------------<br /><br />";

					$notifLog[$trip['trip_id']]['travelers'][] = [
						'name' => $traveler['name'],
						'email' => $traveler['email'],
						'lang' => $traveler['language'],
						'adeudoSigFechaTravel' => $adeudoSigFechaTravel,
						'totalPagado' => $totalPagado,
						'fechaProximoPago' => fechaFormato($sigFechaPago,$currentLang),
						'montoProximoPago' => formatearCurrency($adeudoSigFechaCurrentTraveler,$trip['currency']),
						'tieneAdeudo' => $tieneAdeudo,
						'contenido' => $contenido
					];

				}
			}//HAY TRAVELERS
		}

	echo "\t --------------<br/><br/>";
	}//FOREACH TRIP

	registroLog($notifLog);
}
echo "<hr /></pre>";

print_pre($notifLog);


function registroLog($log){
	$archivoLog = LOGS_PATH.'cron_debug.log';

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
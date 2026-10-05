<?php 
header("Access-Control-Allow-Origin: *");
session_start();


header("Content-Type: application/json", true);
define('VIEWABLE',TRUE);
include_once("../cnf/cnfg.app.php");
include_once(FUNCT_PATH."funcionalidad.php");
if(DEBUG){
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

$success 		= FALSE;
$error   		= 'No especificado';
$data    		= array();
$action  		= '';
$now	 		= date("Y-m-d H:i:s");
$prefijo 		= '';

if(isset($_POST['action']) && $_POST['action']!=''){
	$action = $_POST['action'];
	switch ($action) {
		/*****************************************/
		/******** getRegistros  **********/
		/*****************************************/
			case 'getRegistros':
				$filter = isset($_POST['filter']) && trim($_POST['filter']) != ''?urlencode(trim($_POST['filter'])):'Sin especificar';
				$page = isset($_POST['page']) && trim($_POST['page']) != ''?trim($_POST['page']):1;
				$type = isset($_POST['type']) && trim($_POST['type']) != ''?trim($_POST['type']):'Sin especificar';
				$continuar = FALSE;
				$endpoint = '';
				$regXPag = 10;
				$filterString = '';
				$registros = [];
				$buffer = '';
				$totalRegistros = 0;
				$paginacion = Array('HTML'=>'');

				switch($type){
					case 'VIAJES':
						$continuar = TRUE;
						$endpoint = 'trips/year/'.YEAR_FILTER;
						break;
					case 'VIAJEROS':
						$continuar = TRUE;
						$endpoint = 'travelers/year/'.YEAR_FILTER;
						break;
					case 'USERS':
					case 'CONFIG':
						$continuar = TRUE;
						$endpoint = '----';
						break;
					case 'PARCIALIDADES':
						$continuar = TRUE;
						$endpoint = 'installments';
						$regXPag = 100;
						break;
					deafult:
					break;
				}
				if ($filter == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el valor a buscar correctamente</div>";
					$data    = array();	
				} else {
					if(!$continuar){
						$success = FALSE;
						$error   = 'No hay un endpoint configurado';
						$data    = array("mensaje"=>'Error');
						
					}else{//$continuar
						if($filter !== '%'){
							$filterString = "&search=$filter";
						}
						
						switch ($type) {
							case 'USERS':
								$call = $Admin->API->get("/api/auth/users/admin");

								$registros = [];

								if(!$call['success'] && $call['httpCode'] != 200){
									$success = FALSE;
									$error   = 'Error: '.$call['data']['message'];
									$data    = array("mensaje"=>'Error',"httpCode"=>$call['httpCode']);
									exit;
								}

								if($call['success'] && count($call['data']) > 0){
									$registros = $call['data'];
								}
									
								$call_2 = $Admin->API->get("/api/auth/users/concierge");
								if($call_2['success'] && count($call_2['data']) > 0){
									$registros_2 = $call_2['data'];
									$registros = array_merge($registros,$registros_2);
								}

								$call_3 = $Admin->API->get("/api/auth/users/editor");
								if($call_3['success'] && count($call_3['data']) > 0){
									$registros_3 = $call_3['data'];
									$registros = array_merge($registros,$registros_3);
								}

								$totalRegistros = count($registros);

								$buffer = generarTablaDatos($registros, $type, $Admin);

								$paginacion['HTML'] = '';

								$success = TRUE;
								$error   = '';
								$data    = array("mensaje"=>'Ok',"codigo"=>$buffer,"paginacion"=>$paginacion['HTML'],"totalRegistros"=>$totalRegistros);
								break;
							default:
								$call = $Admin->API->get("/api/".$endpoint."?limit=".$regXPag.'&page='.$page.$filterString);

								if($call['success'] && isset($call['data']['data'])){
									$registros = $call['data']['data'];

									if($type == 'VIAJES'){
										usort($registros, fn($a,$b)=>strcmp($a['trip_name'], $b['trip_name']));
									}

									$totalRegistros = $call["data"]["totalRecords"];
									$totalPaginas = $call["data"]["totalPages"];
									$paginaActual = $call["data"]["currentPage"];

									$buffer = generarTablaDatos($registros, $type, $Admin);

									$paginacion = $Admin->paginador->paginar($totalRegistros,$regXPag,10,$totalPaginas,$page,"$data1");
									
									$success = TRUE;
									$error   = '';
									$data    = array("mensaje"=>'Ok',"codigo"=>$buffer,"paginacion"=>$paginacion['HTML'],"totalRegistros"=>$totalRegistros);
								}else{
									$success = FALSE;
									$error   = 'Error: '.$call['data']['message'];
									$data    = array("mensaje"=>'Error',"httpCode"=>$call['httpCode']);
								}
								break;
						}
					}
				}
			break;
		/*****************************************/
		/******** getParcialidades  **********/
		/*****************************************/
			case 'getParcialidades':
				$trip_id = isset($_POST['trip_id']) && trim($_POST['trip_id']) != ''?trim($_POST['trip_id']):'Sin especificar';
				$type = isset($_POST['type']) && trim($_POST['type']) != ''?trim($_POST['type']):'Sin especificar';
				$continuar = FALSE;
				$endpoint = '';
				$regXPag = 10;
				$filterString = '';
				
				$continuar = TRUE;
				$endpoint = 'installments';
				$regXPag = 100;
						
				if ($trip_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el valor a buscar correctamente</div>";
					$data    = array();	
				} else {
					
					if(!$continuar){
						$success = FALSE;
						$error   = 'No hay un endpoint configurado';
						$data    = array("mensaje"=>'Error');
						
					}else{//$continuar
						
						$filterString = "&trip=$trip_id";
						
						$call = $Admin->API->get("/api/".$endpoint."?limit=".$regXPag.$filterString);

						if(!isset($call['data']['error'])){

							$registros = $call['data']['data'];
							$subtotalParcialidades = 0;

							foreach($registros as $reg){
								$subtotalParcialidades += $reg['amount'];
							}

							$totalRegistros = $call["data"]["totalRecords"];
							$totalPaginas = $call["data"]["totalPages"];#ceil($totalRegistros/$regXPag);
							$paginaActual = $call["data"]["currentPage"];#$page;

							$trip = $Admin->getTrip($trip_id);

							if(is_null($trip['limit_date'])){
								$fechaOriginal = $trip['created_at'];
								$trip['limit_date'] = $fechaMasUnAnio = date('Y-m-d', strtotime($fechaOriginal . ' + 1 year'));; 
							}
							$buffer = generarTablaDatos($registros, $type, $Admin, $trip);

							$success = TRUE;
							$error   = '';
							$data    = array("mensaje"=>'Ok',"codigo"=>$buffer,"totalRegistros"=>$totalRegistros,"subtotalParcialidades"=>$subtotalParcialidades);
						}else{
							$success = FALSE;
							$error   = 'Error: '.$call['data']['message'];
							$data    = array("mensaje"=>'Error',"httpCode"=>$call['httpCode']);
						}
					}
				}
			break;
		/*****************************************/
		/******** getComboGrupos  **********/
		/*****************************************/
			case 'getComboGrupos':
				$filter = isset($_POST['filter']) && trim($_POST['filter']) != ''?trim($_POST['filter']):'Sin especificar';
						
				if ($filter == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el valor del filtro correctamente</div>";
					$data    = array();	
				} else {
									
					$call = $Admin->getTrips($filter);
					$dataset = [];
					$counter = 0;
					$buffer = '';

					if(isset($call['data']) && count($call['data']) > 0){
						$dataset = $call['data'];
						$counter = $call['totalRecords'];

						#print_pre($dataset);

						$buffer .= '<select name="reporte_grupo" id="reporte_grupo">';
						for($i=0;$i < count($dataset);$i++){
							$buffer .= "<option value='{$dataset[$i]['trip_id']}'>{$dataset[$i]['trip_name']}</option>";
						}
						$buffer .= '</select>';
					}else{
						$buffer = '<div class="warning">No hay grupos para mostrar</div>';
					}

					$success = TRUE;
					$error   = '';
					$data    = array("mensaje"=>'Ok',"codigo"=>$buffer,"totalRegistros"=>$counter);
				}
			break;
		/*****************************************/
		/******** setGrupo  **********/
		/*****************************************/
			case 'setGrupo':
				$trip_id = isset($_POST['id']) && trim($_POST['id']) != ''?trim($_POST['id']):'Sin especificar';
				$traveler_id = isset($_POST['traveler_id']) && trim($_POST['traveler_id']) != ''?trim($_POST['traveler_id']):'Sin especificar';
				$traveler_data = isset($_POST['traveler_data']) && trim($_POST['traveler_data']) != ''?trim($_POST['traveler_data']):[];
				$traveler_data = json_decode($traveler_data, true);
				$continuar = FALSE;
				$endpoint = 'travelers';
				$buffer = '';

				
				if ($trip_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
					$data    = array();	
				}else if($traveler_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el traveler_id correctamente</div>";
					$data    = array();	
				}else if(count($traveler_data) < 1){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita enviar la traveler_data</div>";
					$data    = array();	
				} else {
						
					$traveler_data['trip_id'] = $trip_id;

					$call = $Admin->API->put("/api/".$endpoint."/".$traveler_id,$traveler_data);

					if(!isset($call['data']['error'])){

						$response = $call['data'];

						if($response){
							$success = TRUE;
							$error   = '';
							$data    = array("mensaje"=>'Ok',"codigo"=>$buffer);
						}else{
							$success = FALSE;
							$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
							$data    = $call;	
						}
					}else{
						$success = FALSE;
						$error   = 'Error: '.$call['data']['message'];
						$data    = array("mensaje"=>'Error',"httpCode"=>$call['httpCode']);
					}
				}
			break;
		/*****************************************/
		/******** setConfig  **********/
		/*****************************************/
			case 'setConfig':
				$param = isset($_POST['param']) && trim($_POST['param']) != ''?trim($_POST['param']):'Sin especificar';
				$valor = isset($_POST['valor']) && trim($_POST['valor']) != ''?trim($_POST['valor']):'Sin especificar';
				
				$endpoint = 'configurations';
				
				if ($param == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el parámetro correctamente</div>";
					$data    = array();	
				}else if($valor == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el valor correctamente</div>";
					$data    = array();	
				} else {
						
					$configuration_id = $Admin->getConfig($param);

					$dataUpdate = [
						"configuration_name" => $param,
						"value" => $valor
					];

					$call = $Admin->API->put("/api/".$endpoint."/".$configuration_id,$dataUpdate);

					if(!isset($call['data']['error'])){

						$response = $call['data'];

						if($response){
							$success = TRUE;
							$error   = '';
							$data    = array("mensaje"=>'Ok',"codigo"=>'Se actualizó el parámetro');
						}else{
							$success = FALSE;
							$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
							$data    = $call;	
						}
					}else{
						$success = FALSE;
						$error   = 'Error: '.$call['data']['message'];
						$data    = array("mensaje"=>'Error',"httpCode"=>$call['httpCode']);
					}
				}
			break;
		/*****************************************/
		/******** setUserStatus  **********/
		/*****************************************/
			case 'setUserStatus':
				$email = isset($_POST['email']) && trim($_POST['email']) != ''?trim($_POST['email']):'Sin especificar';
				$change = isset($_POST['change']) && trim($_POST['change']) != ''?trim($_POST['change']):'Sin especificar';
				
				$endpoint = 'auth/usersData';
				
				if ($email == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el email</div>";
					$data    = array();	
				}else if($change == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el valor correctamente</div>";
					$data    = array();	
				} else {
						
					$userData = $Admin->getUsuarioAdminByEmail($email);

					switch($change){
						case 'activar':
							$valor = TRUE;
							break;
						case 'desactivar':
							$valor = FALSE;
							break;
					}

					$user_id = $userData['user_id'];
					$dataUpdate = [
						"email" => $email,
						"name" => $userData['name'],
						"role" => $userData['role'],
						"is_active" => $valor
					];

					$call = $Admin->API->put("/api/".$endpoint."/".$user_id,$dataUpdate);
					$response = $call['data'];

					if($response){
						$success = TRUE;
						$error   = '';
						$data    = array("mensaje"=>'Ok',"codigo"=>'Se actualizó el parámetro');
					}else{
						$success = FALSE;
						$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
						$data    = $call;	
					}
				}
			break;
		/*****************************************/
		/******** setLanguage  **********/
		/*****************************************/
			case 'setLanguage':
				$email = isset($_POST['email']) && trim($_POST['email']) != ''?trim($_POST['email']):'Sin especificar';
				$lang = isset($_POST['lang']) && trim($_POST['lang']) != ''?trim($_POST['lang']):'Sin especificar';
				
				#$endpoint = 'auth/usersData';
				
				if ($email == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el email</div>";
					$data    = array();	
				}else if($lang == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el valor correctamente</div>";
					$data    = array();	
				} else {
						
					$response = $Admin->setLanguage($lang,TRUE);
					/*$user_id = $userData['user_id'];
					$dataUpdate = [
						"email" => $email,
						"name" => $userData['name'],
						"role" => $userData['role'],
						"is_active" => $valor
					];

					$call = $Admin->API->put("/api/".$endpoint."/".$user_id,$dataUpdate);
					$response = $call['data'];*/

					if($response){
						$success = TRUE;
						$error   = '';
						$data    = array("mensaje"=>'Ok',"codigo"=>'Se actualizó el parámetro');
					}else{
						$success = FALSE;
						$error   = 'Error: Sesión expirada';
						$data    = array("mensaje"=>'Error',"httpCode"=>'403');
					}
				}
			break;
		/*****************************************/
		/******** markAlertAsRead  **********/
		/*****************************************/
			case 'markAlertAsRead':
				$notification_id = isset($_POST['notification_id']) && trim($_POST['notification_id']) != ''?trim($_POST['notification_id']):'Sin especificar';
								
				if ($notification_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Existe un problema con el notification_id</div>";
					$data    = array();	
				} else {
						
					if($notification_id !== '' && esUUID($notification_id)){

						$notification = $Admin->getNotification($notification_id);
						if(count($notification)>0){
							$updateNotification = $notification;
							unset($updateNotification['created_at']);
							$updateNotification['is_read'] = TRUE;
							$updateNotification['read_at'] = date('Y-m-d H:i:s');

							$call = $Admin->setNotificationRead($notification_id,$updateNotification);
							$response = $call['data'];

							if(!isset($response['error'])){
								$success = TRUE;
								$error   = '';
								$data    = array("mensaje"=>'Ok',"codigo"=>'Se actualizó la alerta');
							}else{
								$success = FALSE;
								$error   = 'Error: '.$call['data']['message'];
								$data    = array("mensaje"=>'Error',"httpCode"=>$call['httpCode']);
							}

						}else{
							$success = FALSE;
							$error   = 'Error: Sin datos';
							$data    = array("mensaje"=>'Error',"httpCode"=>'403');
						}

					}else{
						$success = FALSE;
						$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
						$data    = $call;	
					}
				}
			break;
		/*****************************************/
		/******** getAlertasUser  **********/
		/*****************************************/
			case 'getAlertasUser':
				$traveler_id = isset($_POST['traveler_id']) && trim($_POST['traveler_id']) != ''?trim($_POST['traveler_id']):'Sin especificar';
								
				if ($traveler_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Existe un problema con el traveler_id</div>";
					$data    = array();	
				} else {
						
					if($traveler_id !== '' && esUUID($traveler_id)){

						$notifications = $Admin->getNotifications($traveler_id);

						$codigo = formatNotifications($notifications['notifications']);
						$totalRecords = $notifications['totalRecords'];
						
						$success = TRUE;
						$error   = '';
						$data    = array("mensaje"=>'Ok',"codigo"=>$codigo, "totalRecords"=>$totalRecords);						
					}else{
						$success = FALSE;
						$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
						$data    = $call;	
					}
				}
			break;
		/*****************************************/
		/******** updatePayment  **********/
		/*****************************************/
			case 'updatePayment':
				$payment_id = isset($_POST['payment_id']) && trim($_POST['payment_id']) != ''?trim($_POST['payment_id']):'Sin especificar';
				$amount_original = isset($_POST['amount_original']) && trim($_POST['amount_original']) != ''?trim($_POST['amount_original']):'Sin especificar';
				$currency_original = isset($_POST['currency_original']) && trim($_POST['currency_original']) != ''?trim($_POST['currency_original']):'';
				$exchange_rate = isset($_POST['exchange_rate']) && trim($_POST['exchange_rate']) != ''?trim($_POST['exchange_rate']):'';
				$amount_mxn = isset($_POST['amount_mxn']) && trim($_POST['amount_mxn']) != ''?trim($_POST['amount_mxn']):'';
				$payment_type = isset($_POST['payment_type']) && trim($_POST['payment_type']) != ''?trim($_POST['payment_type']):'';
				$payment_status = isset($_POST['payment_status']) && trim($_POST['payment_status']) != ''?trim($_POST['payment_status']):'';
				$stripe_payment_intent_id = isset($_POST['stripe_payment_intent_id']) && trim($_POST['stripe_payment_intent_id']) != ''?trim($_POST['stripe_payment_intent_id']):'';
				$document_id = isset($_POST['document_id']) && trim($_POST['document_id']) != ''?trim($_POST['document_id']):'';
				$comments = isset($_POST['comments']) && trim($_POST['comments']) != ''?trim($_POST['comments']):'';
				$paid_at = isset($_POST['paid_at']) && trim($_POST['paid_at']) != ''?trim($_POST['paid_at']):'';
				$language = isset($_POST['language']) && trim($_POST['language']) != ''?trim($_POST['language']):'';
				
				$continuar = FALSE;
				$endpoint = 'payments';
				$buffer = '';
				
				if ($payment_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el payment_id correctamente</div>";
					$data    = array();	
				}else if($amount_original == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el amount_original correctamente</div>";
					$data    = array();	
				}else if($payment_status == ''){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita especificar el payment_status</div>";
					$data    = array();	
				} else {
					$payment_data = [
						"amount_original" => number_format($amount_original,2),
					    "currency_original" => $currency_original,
					    "exchange_rate" => $exchange_rate,
					    "amount_mxn" => $amount_mxn,
					    "payment_type" => $payment_type,
					    "payment_status" => $payment_status,
					    "stripe_payment_intent_id" => $stripe_payment_intent_id,
					    "document_id" => $document_id,
					    "comments" => $comments,
					    "paid_at" => $paid_at
					];
					#print_pre($payment_data);
						
					$call = $Admin->API->put("/api/".$endpoint."/".$payment_id,$payment_data);
					#print_pre($call);
					if(!isset($call['data']['error'])){

						$response = $call['data'];

						switch ($payment_type) {
							case 'cash':
								$pay_type = __('Depósito en efectivo',$language);
								break;
							case 'transfer':
								$pay_type = __('Transferencia',$language);
								break;
							case 'online':
								$pay_type = __('Pago en línea',$language);
								break;
							
							default:
								$pay_type = __('Pago',$language);
								break;
						}

						switch($payment_status){
							case 'validated':
								switch($language){
									case 'es':
										$contenido = "<p>$pay_type fue <span style='font-weight:bold;'>validado</span>. Vaya al <a href=\"".WEB_URL."\" target='_blank'>panel del viajero</a> de Eurotrips para consultarlo.</p>";
										break;
									case 'en':
										$contenido = "<p>$pay_type was <span style='font-weight:bold;'>validated</span>. Go to the Eurotrips <a href=\"".WEB_URL."\" target='_blank'>traveler dashboard</a> to review.</p>";
										break;
								}
								$estatus_mail = __('validado',$language);
								break;
							case 'rejected':
							case PAYMENT_REJECTED_STATUS:
								switch($language){
									case 'es':
										$contenido = "<p>$pay_type fue <span style='font-weight:bold;'>rechazado</span>. Vaya al <a href=\"".WEB_URL."\" target='_blank'>panel del viajero</a> de Eurotrips para revisar.</p>";
										$contenido .= "<p>Las observaciones son: $comments</p>";
										break;
									case 'en':
										$contenido = "<p>$pay_type was <span style='font-weight:bold;'>rejected</span>. Go to the Eurotrips <a href=\"".WEB_URL."\" target='_blank'>traveler dashboard</a> to review.</p>";
										$contenido .= "<p>Comments: $comments</p>";
										break;
								}
								$estatus_mail = __('rechazado',$language);
								break;
							default:
						}

						$datosMensaje = [
							"siteURL" => WEB_URL,
							"contenido" => 	$contenido
						];
						$traveler_id = $response['traveler_id'];
						$traveler = $Admin->getTraveler($traveler_id);

						$mailResponse = enviarMensaje(
											$destinatario = $traveler['email'],
											$tituloMensaje='Eurotrips - '.$pay_type.' - '.$estatus_mail, 
											$templateName='correo_aviso.html', 
											$datosMensaje
										);


						if($response){
							$success = TRUE;
							$error   = '';
							$data    = array("mensaje"=>'Ok',"codigo"=>$buffer);
						}else{
							$success = FALSE;
							$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
							$data    = $call;	
						}
					}else{
						$success = FALSE;
						$error   = 'Error: '.$call['data']['message'];
						$data    = array("mensaje"=>'Error',"httpCode"=>$call['httpCode']);
					}
				}
			break;
		/*****************************************/
		/******** setFechaPago  **********/
		/*****************************************/
			case 'setFechaPago':
				$trip_id = isset($_POST['trip_id']) && trim($_POST['trip_id']) != ''?trim($_POST['trip_id']):'Sin especificar';
				$payment_date = isset($_POST['payment_date']) && trim($_POST['payment_date']) != ''?trim($_POST['payment_date']):'Sin especificar';
				$amount = isset($_POST['amount']) && trim($_POST['amount']) != ''?trim($_POST['amount']):'Sin especificar';
				
				$continuar = FALSE;
				$endpoint = 'installments';
				$buffer = '';
				
				if ($trip_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
					$data    = array();	
				}else if($payment_date == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar la fecha correctamente</div>";
					$data    = array();	
				}else if($amount == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el monto correctamente</div>";
					$data    = array();	
				} else {
					$errores = '';

					if($amount <= 0){
						$errores .= '<div class="alerta roja">La cantidad no puede ser igual o menor a cero.</div>';
					}

					$currentTrip = $Admin->getTrip($trip_id,$redirect=FALSE);
					#print_pre($currentTrip);
					#die();
					if(!isset($currentTrip['error'])){

						if(is_null($currentTrip['limit_date'])){
							$fechaOriginal = $currentTrip['created_at'];
							$currentTrip['limit_date'] = $fechaMasUnAnio = date('Y-m-d', strtotime($fechaOriginal . ' + 1 year'));; 
						}
						$currentTripLimitDate = $currentTrip['limit_date'];
						$currentCalendarioPagos = $Admin->getCalendarioPago($trip_id);
						$montoTotalParcialidades = 0;

						foreach($currentCalendarioPagos as $fechaPago){
							$montoTotalParcialidades += $fechaPago['amount'];
							if(strtotime($payment_date) == strtotime(convertirFecha($fechaPago['payment_date']))){
								$errores .= '<div class="alerta roja">Ya hay una fecha del calendario registrada en este día.</div>';
							}
						}
						if(strtotime($payment_date) >= strtotime(convertirFecha($currentTripLimitDate))){
							$errores .= '<div class="alerta roja">La fecha no puede ser igual o mayor que la fecha límite de liquidación.</div>';
						}

						$subtotal = $montoTotalParcialidades + $amount;
						if($subtotal > $currentTrip['cost']){
							$errores .= '<div class="alerta roja">La suma de los montos ('.formatearCurrency($subtotal,$currentTrip['currency']).') no puede ser superior a la totalidad del costo del viaje.</div>';
						}


						if($errores == ''){
							$fecha_pago_data = [
								"trip_id" => $trip_id,
								"payment_date" => $payment_date,
								"amount" => $amount
							];

							$call = $Admin->API->post("/api/".$endpoint,$fecha_pago_data);
							$response = $call['data'];

							if($call['success']){
								$success = TRUE;
								$error   = '';
								$data    = array("mensaje"=>'Ok',"codigo"=>$call);
							}else{
								$success = FALSE;
								$error   = "<div class='bg-warning'>".$response['message']."</div>";
								$data    = $buffer;	
							}
						}else{
							$success = FALSE;
							$error   = $errores;
							$data    = [];
						}
					}else{
						$success = FALSE;
						$error   = 'Error: '.$currentTrip['error'];
						$data    = array("mensaje"=>$currentTrip['message'],"httpCode"=>$currentTrip['httpCode']);
					}
				}
			break;
		/*****************************************/
		/******** deleteParcialidad  **********/
		/*****************************************/
			case 'deleteParcialidad':
				$trip_id = isset($_POST['trip_id']) && trim($_POST['trip_id']) != ''?trim($_POST['trip_id']):'Sin especificar';
				$installement_id = isset($_POST['installement_id']) && trim($_POST['installement_id']) != ''?trim($_POST['installement_id']):'Sin especificar';
				$continuar = FALSE;
				$endpoint = 'installments';
				$buffer = '';
				
				if ($trip_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
					$data    = array();	

				}else if($installement_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el id correctamente</div>";
					$data    = array();	
				} else {

					$call = $Admin->API->delete("/api/".$endpoint.'/'.$installement_id);
					if(!isset($call['data']['error'])){
						$currentCalendarioPagos = $Admin->getCalendarioPago($trip_id);

						if($call['success']){
							$success = TRUE;
							$error   = '';
							$data    = array("mensaje"=>'Ok',"codigo"=>$currentCalendarioPagos);
						}else{
							$success = FALSE;
							$error   = "<div class='bg-warning'>Ocurrio un error</div>";
							$data    = $buffer;	
						}
					}else{
						$success = FALSE;
						$error   = 'Error: '.$call['data']['message'];
						$data    = array("mensaje"=>'Error',"httpCode"=>$call['httpCode']);
					}
				}
			break;
		/*****************************************/
		/******** setDocumentStatus  **********/
		/*****************************************/
			case 'setDocumentStatus':
				$traveler_id = isset($_POST['traveler_id']) && trim($_POST['traveler_id']) != ''?trim($_POST['traveler_id']):'Sin especificar';
				$document_id = isset($_POST['document_id']) && trim($_POST['document_id']) != ''?trim($_POST['document_id']):'Sin especificar';
				$document_data = isset($_POST['document_data']) && trim($_POST['document_data']) != ''?trim($_POST['document_data']):[];
				$valor = isset($_POST['valor']) && trim($_POST['valor']) != ''?trim($_POST['valor']):'Sin especificar';
				$notes = isset($_POST['notes']) && trim($_POST['notes']) != ''?trim($_POST['notes']):'';
				$language = isset($_POST['language']) && trim($_POST['language']) != ''?trim($_POST['language']):'';

				$document_data = json_decode($document_data, true);
				$continuar = FALSE;
				$endpoint = 'documents';
				$buffer = '';
				
				if ($document_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el document_id correctamente</div>";
					$data    = array();	
				}else if($traveler_id == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el traveler_id correctamente</div>";
					$data    = array();	
				}else if(count($document_data) < 1){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita enviar la document_data</div>";
					$data    = array();	
				}else if($valor == 'Sin especificar'){
					$success = FALSE;
					$error   = "<div class='bg-warning'>Necesita indicar el valor correctamente</div>";
					$data    = array();	
				} else {
						
					unset($document_data['document_id']);
					switch($valor){
						case 'Validar':
							$estatus = 'approved';
							$estatus_mail = __('validado',$language);
							break;
						case 'Rechazar':
							$estatus = 'rejected';
							$estatus_mail = __('rechazado',$language);
							break;
						default:
					}
					$document_data['status'] = $estatus;
					$document_data['notes'] = $notes;

					$call = $Admin->API->put("/api/".$endpoint."/".$document_id,$document_data);

					if(!isset($call['data']['error'])){
						$response = $call['data'];

						switch ($document_data['document_type']) {
							case 'first_passport':
								$doc_type = __('Primer pasaporte',$language);
								break;
							case 'second_passport':
								$doc_type = __('Segundo pasaporte',$language);
								break;
							
							default:
								$doc_type = __('Documento',$language);
								break;
						}

						switch($valor){
							case 'Validar':
								$contenido = "<p>'$doc_type' ".__('fue',$language)." {$estatus_mail}.</p>";
								switch($language){
									case 'es':
										$contenido .= "<p>Vaya al <a href=\"".WEB_URL."\" target='_blank'>panel del viajero</a> de Eurotrips para revisar.</p>";
										break;
									case 'en':
										$contenido .= "<p>Go to the Eurotrips <a href=\"".WEB_URL."\" target='_blank'>traveler dashboard</a> to review.</p>";
										break;
								}
								break;
							case 'Rechazar':
								$contenido = "<p>'$doc_type' ".__('fue',$language)." {$estatus_mail}.</p>";
								switch($language){
									case 'es':
										$contenido .= "<p>Vaya al <a href=\"".WEB_URL."\" target='_blank'>panel del viajero</a> de Eurotrips para revisar.</p>";
										$contenido .= "<p>Las observaciones son: $notes</p>";
										break;
									case 'en':
										$contenido .= "<p>Go to the Eurotrips <a href=\"".WEB_URL."\" target='_blank'>traveler dashboard</a> to review.</p>";
										$contenido .= "<p>Comments: $notes</p>";
										break;
								}
								break;
							default:
						}

						$datosMensaje = [
							"siteURL" => WEB_URL,
							"contenido" => 	$contenido
						];

						$traveler = $Admin->getTraveler($traveler_id);

						$mailResponse = enviarMensaje(
											$destinatario = $traveler['email'],
											$tituloMensaje='Eurotrips - '.$doc_type.' '.__('fue',$language).' '.$estatus_mail, 
											$templateName='correo_aviso.html', 
											$datosMensaje
										);
						if($mailResponse['success']){
							### 
						}

						if($response){
							$success = TRUE;
							$error   = '';
							$data    = array("mensaje"=>'Ok',"codigo"=>$buffer);
						}else{
							$success = FALSE;
							$error   = "<div class='bg-warning'>".$call['error']."</div>";
							$data    = $call;	
						}
					}else{
						$success = FALSE;
						$error   = 'Error: '.$call['data']['message'];
						$data    = array("mensaje"=>'Error',"httpCode"=>$call['httpCode']);
					}
				}
			break;
		/*****************************************/
		/******** cambiarNombreArchivo  **********/
		/*****************************************/
		case 'cambiarNombreArchivo':
			$referer = isset($_POST['referer']) && trim($_POST['referer']) != ''?trim($_POST['referer']):'Sin-especificar';
			$edit_filename = isset($_POST['edit_filename']) && trim($_POST['edit_filename']) != ''?trim($_POST['edit_filename']):'Sin-especificar';
			$extension = isset($_POST['extension']) && trim($_POST['extension']) != ''?trim($_POST['extension']):'sin-especificar';
			$es_detalle = isset($_POST['es_detalle']) && trim($_POST['es_detalle']) != '' && is_numeric($_POST['es_detalle'])?TRUE:FALSE;

			$data = array();
			$nombre_original = "{$edit_filename}{$extension}";

			if($referer == 'Sin-especificar' || $edit_filename == 'Sin-especificar' || $extension == 'sin-especificar'){
				$success = FALSE;
				$error .= 'Faltan datos de referencia del proyecto/archivo';
				$data    = array();	
			}else{
				if(!$editable){
					$success = FALSE;
					$error .= 'No tiene permisos para editar archivos';
					$data    = array();
				}else{					    
					$datos['nombre_original'] = $nombre_original;
					if($es_detalle){
					    $tabla = 'documentos_detalle';
					}else{
						$tabla = 'documentos';
					}
					if($Admin->conexion->update(PREFIJO.$tabla,$datos," WHERE code='$referer'",'TEXT')){
						$buffer = "<div class='bg-success'>Datos guardados </div>";
						$success = TRUE;
						$error   = '';
						$data    = array("mensaje"=>'Ok',"codigo"=>$buffer,"nombre_original"=>$nombre_original,"edit_filename"=>$edit_filename,"code"=>$referer);
					}else{
						$success = FALSE;
						$error   = "<div class='bg-warning'>Error:".$Admin->conexion->error."</div>";
						$data    = array();	
					}
				}//$editable
			}//variables $fid_proyecto, $referer, $tipo_documento
		break;
		/*****************************************/
		/******** borrarArchivo  **********/
		/*****************************************/
		case 'borrarArchivo':
			$referer = isset($_POST['referer']) && trim($_POST['referer']) != ''?trim($_POST['referer']):'Sin-especificar';
			$es_detalle = isset($_POST['es_detalle']) && trim($_POST['es_detalle']) != '' && is_numeric($_POST['es_detalle'])?TRUE:FALSE;

			$data = array();

			if($referer == 'Sin-especificar'){
				$success = FALSE;
				$error .= 'Faltan datos de referencia del proyecto/archivo';
				$data    = array();	
			}else{
				if(!$editable){
					$success = FALSE;
					$error .= 'No tiene permisos para borrar archivos';
					$data    = array();
				}else{					    
					$datos['doc_activo'] = 0;
					if($es_detalle){
					    $tabla = 'documentos_detalle';
					}else{
						$tabla = 'documentos';
					}

					if($Admin->conexion->update(PREFIJO.$tabla,$datos," WHERE code='$referer'",'TEXT')){
						$buffer = "<div class='bg-success'>Datos guardados </div>";
						$success = TRUE;
						$error   = '';
						$data    = array("mensaje"=>'Ok',"codigo"=>$buffer,"code"=>$referer);
					}else{
						$success = FALSE;
						$error   = "<div class='bg-warning'>Error:".$Admin->conexion->error."</div>";
						$data    = array();	
					}
				}//$editable
			}//variables $fid_proyecto, $referer, $tipo_documento
		break;
		/*****************************************/
		/******** guardarArchivo  **********/
		/*****************************************/
		case 'guardarArchivo':
			$id = isset($_POST['id']) && trim($_POST['id']) != '' && is_numeric($_POST['id'])?trim($_POST['id']):'Sin especificar';
			$tipo = isset($_POST['tipo']) && trim($_POST['tipo']) != ''?trim($_POST['tipo']):'Sin especificar';
			$data = array();
			$now = date("Y-m-d H:i:s");
			$fechahora = date("Y-m-d_H-i-s");
			$nombreArchivo = "{$id}_{$tipo}_{$fechahora}";

			if(isset($_FILES)){  
			    $files = array();

			    $uploaddir = FILE_PATH;
			    foreach($_FILES as $file){
					$extension = explode('.', $file['name']);
					$extension = end($extension);
					
			        if(move_uploaded_file($file['tmp_name'], $uploaddir.$nombreArchivo.'.'.$extension)){
			            $files[] = $uploaddir . $file['name'];

			            $datos['fecha_ultima_actualizacion'] = $now;
			            $datos[$tipo] = $nombreArchivo.'.'.$extension;//GUARDADO EN LA CATEGORIA CORRECTA
			            
						if($Admin->conexion->update(PREFIJO.'registros',$datos,$condicion=" WHERE nip='$id'",'TEXT',FALSE)){
								$estatusARegistrar = 'NO SE IDENTIFICO';
								switch($tipo){
									case 'comprobante_pago':
										$estatusARegistrar = 'REGISTRO DE COMPROBANTE DE PAGO';
										break;
									case 'respaldo_envio':
										$estatusARegistrar = 'REGISTRO DE RESPALDO DE ENVIO';
										break;
									case 'respaldo_entrega':
										$estatusARegistrar = 'REGISTRO DE RESPALDO DE ENTREGA';
										break;
								}
								$admRegistro->cambiarEstatus($id,$admRegistro->estatus_registro,$estatusARegistrar,'');
								$buffer = "<div class='bg-success'>Datos guardados </div>";
								$success = TRUE;
								$error   = '';
								$linkArchivo = '<a href="'.WEB_FILE_PATH.$nombreArchivo.'.'.$extension.'" target="_blank">Archivo</a>';
								$data    = array("mensaje"=>'Ok',"codigo"=>$buffer,"archivo"=>$linkArchivo,"estatus"=>$estatusARegistrar);
						}else{
							$success = FALSE;
							$error   = "<div class='bg-warning'>Error:".$Admin->conexion->error."</div>";
							$data    = array();	
						}
			        }else{
			            $success = FALSE;
						$error .= 'Problema al subir la imagen';
						$data    = array();	
			        }
			    }
			}else{
				$success = FALSE;
				$error   = 'No se indicaron archivos: '.count($_FILES);
				$data    = array();	
			}

			break;
	case 'testguarda':
		$data = array();
		$now = date("Y-m-d H:i:s");
		$fechahora = date("Y-m-d_H-i-s");
		$nombreArchivo = "temporal".$fechahora;#{$id}_{$tipo}_{$fechahora}

		if(isset($_FILES)){  
		    $files = array();

		    $uploaddir = FILE_PATH;
		    foreach($_FILES as $file){
				$extension = explode('.', $file['name']);
				$extension = end($extension);
				
		        if(move_uploaded_file($file['tmp_name'], $uploaddir.$nombreArchivo.'.pdf')){
		            $files[] = $uploaddir . $file['name'];
		            
		            $query = "INSERT INTO testxvm(data) VALUES('{$uploaddir}{$nombreArchivo}.pdf')";
					$res = $mysqli->query($query);
					if($res){
						$success = TRUE;
						$error   = '';
						$data    = array('resultado'=>'OK','file_data'=>"{$uploaddir}{$nombreArchivo}.pdf");
						
					}else{
						$success = FALSE;
						$error   = $mysqli->error;
						$data    = array('Error');
					}
		        }else{
		            $success = FALSE;
					$error .= 'Problema al subir archivo';
					$data    = array();	
		        }
		    }
		}else{
			$success = FALSE;
			$error   = 'No se indicaron archivos: '.count($_FILES);
			$data    = array();	
		}
		break;
	case 'hacerEditable':
		$fid_dp = isset($_POST['fid_dp']) && trim($_POST['fid_dp']) != '' && is_numeric($_POST['fid_dp'])?trim($_POST['fid_dp']):'Sin especificar';
		$fid_uqid = isset($_POST['fid_uqid']) && trim($_POST['fid_uqid']) != '' && is_numeric($_POST['fid_uqid'])?trim($_POST['fid_uqid']):'Sin especificar';

		if($fid_dp != 'Sin especificar' && $fid_uqid != 'Sin especificar'){
			$query = "UPDATE [BiossmannData].[dbo].[dp_descriptivos] SET estatus=6,firma_jefe='',comentarios_jefe='',fecha_respuesta_jefe=NULL,autorizado_por_jefe='',firma_rh='',comentarios_rh='',fecha_respuesta_rh=NULL,autorizado_por_rh='' WHERE fid_dp={$fid_dp} AND  fid_uqid={$fid_uqid}";
			$stmt = $pdo->prepare($query);
  			if($stmt->execute()){
  				$success = TRUE;
				$error   = '';
				$data    = array('query'=>$query);
  			}else{
  				$success = FALSE;
				$error   = 'Error ejecutar SQL';
				$data    = array();
  			}		
		}else{	

		}
		break;
	/*****************************************/
	/******** DEFAULT  **********/
	/*****************************************/
	default:
		$success = FALSE;
		$error   = 'Error, no existe la action';
		$data    = array();
		break;
	}
}else{
	$success = FALSE;
	$error   = 'Error al especificar accion';
	$data    = array();
}

$respuesta = array('action'=>$action,'success'=>$success,'error'=>$error,'data'=>$data);
echo json_encode($respuesta);
?>
<?php
#include_once("mysql.class.php");
include_once("paginacion.class.php");

class Admin{
	var $debug = 0;
	var $conexion = '';
	var $API = '';
	var $resultado = '';
	var $error = '';
	var $confpath = 'cnf/';
	var $libfpath = '';
	var $paginador = '';
	function __construct($apiObj){//$connObj
		$this->confpath = CONF_PATH;
		$this->libfpath = LIB_PATH;
		$this->API = $apiObj;
		$paginador = new Paginacion();
		$this->paginador = $paginador;
	}
	
	function comprobarUsuario($usrlogin,$pswlogin){

		$res = $this->API->login($usrlogin, $pswlogin);
		#print_pre($res);
		#die();
		if($res['success']) {

			$userData = $res['data'];

			$userID = $userData['user']['user_id'];
			$data = [];
    		$this->API->setToken($userData['token']);

			if($userData['user']['role'] == 'traveler'){
				$user_email = $userData['user']['email'];
				$userMeta = $this->getUserData($user_email);
				$userID = $userMeta['traveler_id'];
				$data = array_merge($data,$userMeta);
			}
			$role = $userData['user']['role'];
			#HARDCODE FORZAR ROLE EDITOR
			/*if($userData['user']['email'] == 'editor@correo.com'){
				$role = 'editor';
			}*/

    		//($usr,$nombre,$perfil,$seccionesAcceso,$firstSecc,$user_id,$token)
    		$this->iniciarSesion($userData['user']['email'],$userData['user']['name'],$role,'','HOME',$userID,$userData['token'],$data);

			
			return TRUE;

		}else{
			return $res;
		}
	}
	function obtenerPermisos($fid_perfil){
		$query = "SELECT *,(SELECT acronimo_accion FROM ".PREFIJO."acciones WHERE fid_accion=kid_accion) AS acronimo FROM ".PREFIJO."permisos WHERE fid_perfil='$fid_perfil'";
		$res = $this->conexion->query($query);
		$permisos = $this->conexion->fetch($res);
		$arrPermisos = array();
		foreach ($permisos as $key => $value) {
			$arrPermisos[] = $value['acronimo'];
			echo ' | ' . $value['acronimo'];
		}
		return $arrPermisos;
	}
	function iniciarSesion($usr,$nombre,$perfil,$seccionesAcceso,$firstSecc,$user_id,$token,$data=[]){//$bit,$seccIni,$arrSecc
		if($perfil == 'admin' || $perfil == 'concierge' || $perfil == 'editor'){
			$admin = TRUE;
		}else{
			$admin = FALSE;
		}

		if($admin){
			$seccionesLOAD = 	require 'cnf/secciones.ADM.php';
		}else{
			$seccionesLOAD = 	require 'cnf/secciones.php';
		}

		$seccionesPorAcronimo = Array();
		$secciones = Array();
		foreach ($seccionesLOAD as $seccion) {
			switch ($seccion['acronimo']) {
				case 'REPORTES':
					if($perfil == 'admin'){
						$seccionesPorAcronimo[$seccion['acronimo']] = $seccion['nombre'];
						array_push($secciones, $seccion);
					}
					break;
				default:
					$seccionesPorAcronimo[$seccion['acronimo']] = $seccion['nombre'];
					array_push($secciones, $seccion);
					break;
			}
		}

		$arrDatos = array(
				'usr' => $usr, 
				'nombre' => $nombre, 
				'perfil' => $perfil, 
				'permisos' => $seccionesAcceso,#$arrSecc['bits'],
				'firstSecc' => $firstSecc,
				'secciones' => $secciones,
				'admin' => $admin,
				'user_id' => $user_id,
				'seccionesPorAcronimo' => $seccionesPorAcronimo,
				'token' => $token,
				'data' => $data,
				'mensaje' => '',
				'year' => $data['year'] ?? ''
			);

		if($admin){
			$lang = 'es';
		}else{
			$lang = (isset($data['language']) && !empty($data['language']))?$data['language']:'es';
			$arrDatos['language'] = $lang;
		}
		
		$_SESSION['site'][APP_NAME] = $arrDatos;

		$this->initLanguage($lang);
	}
	function comprobarSesion(){
		if(isset($_SESSION['site'][APP_NAME]) && is_array($_SESSION['site'][APP_NAME])) return TRUE;else return FALSE;
	}
	function salirSesion($pag){
		unset($_SESSION['site'][APP_NAME]);
		header("Location:".WEB_URL);
		exit;
	}
	function obtenerUsr($dato){
		if(isset($_SESSION['site'])){
			return $_SESSION['site'][APP_NAME][$dato];
		}else{
			return FALSE;
		}
	}
	function obtenerUsrData($dato){
		return $_SESSION['site'][APP_NAME]['data'][$dato];
	}
	function permisosUsuario(){
		return $_SESSION['site'][APP_NAME]['permisos'];
	}
	function esAdmin(){
		return $_SESSION['site'][APP_NAME]['admin'];
	}
	function esPerfilAdmin(){
		return ($_SESSION['site'][APP_NAME]['perfil'] == 'admin');
	}
	function esConsultor(){
		return ($_SESSION['site'][APP_NAME]['perfil'] == 'concierge');
	}
	function esEditor(){
		return ($_SESSION['site'][APP_NAME]['perfil'] == 'editor');
	}
	function setMensaje($texto){
		$_SESSION['site'][APP_NAME]['mensaje'] = $texto;
	}
	function addMensaje($texto){
		$buffer = $this->getMensaje();
		$_SESSION['site'][APP_NAME]['mensaje'] = $buffer.$texto;
	}
	function getMensaje(){
		return $_SESSION['site'][APP_NAME]['mensaje'];
	}
	function cleanMensaje(){
		$_SESSION['site'][APP_NAME]['mensaje'] = '';
	}
	function getUserData($user_email,$redirect=TRUE){
		$res = $this->API->get('/api/travelers?search='.$user_email);
		$getData = [];

		if(isset($res['data']['error']) && $res['data']['error'] == 'SESSION_ENDED'){
			if($redirect){
				header('Location:'.WEB_URL.'SALIR');
				exit;
			}else{
				return $res;
			}
		}else if(isset($res['data']['error']) && $res['data']['error'] == 'API_UNAVAILABLE'){
			if($redirect){
				die('<div class="alerta amarilla pm5 mb1">API error: '. $res['data']['message'].'</div>');
			}else{
				return $res;
			}
		}

		if(isset($res['data']['data'][0])){
			$userID = $res['data']['data'][0]['traveler_id'];
			$getData = $res['data']['data'][0];
		}

		return $getData;
	}
	function getUserDataByTravelerID($traveler_id){
		$res = $this->API->get('/api/travelers/'.$traveler_id);
		$getData = $res['data'];

		if(isset($getData['error']) && $getData['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($getData['error']) && $getData['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $getData['message'].'</div>');
		}

		$getData['trip_id'] = $getData['trip_id']!==NULL?$getData['trip_id']:'';
		$getData['birth_date'] = date_format(date_create($getData['birth_date']),"Y-m-d");

		return $getData;
	}
	function getTrips($yearFilter = ''){
		$filter = '';
		if($yearFilter !== ''){
			$filter = '/year/'.$yearFilter;
		}
		$resTrips = $this->API->get('/api/trips'.$filter.'?limit=300');
		
		if(isset($resTrips['data']['error']) && $resTrips['data']['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($resTrips['data']['error']) && $resTrips['data']['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $resTrips['data']['message'].'</div>');
		}

		$trips = [];
		if(isset($resTrips['data']['totalRecords']) && $resTrips['data']['totalRecords'] > 0){
			$trips = $resTrips['data'];

			$tripsOrdenados = $resTrips['data']['data'];
			//ORDENAR ALFABETICAMENTE LOS GRUPOS
			usort($tripsOrdenados, fn($a, $b) => strcmp($a['trip_name'], $b['trip_name']));
			$trips['data'] = $tripsOrdenados;
		} 

		return $trips;
	}
	/*
	::
	::
	CAMBIO
	::
	::
	*/
	function getTrip($trip_id,$redirect=TRUE){
		if(!is_null($trip_id) && $trip_id!==''){
			$resTrip = $this->API->get('/api/trips/'.$trip_id);
			#print_pre($resTrip);
			$trip = [];
			$trip = $resTrip['data'];
			if(isset($trip['error']) && $trip['error'] == 'SESSION_ENDED'){
				if($redirect){
					header('Location:'.WEB_URL.'SALIR');
					exit;
				}else{
					return ["error"=>'SESSION_ENDED',"message"=>$trip['message'],"httpCode"=>$resTrip['httpCode']];
				}
			}else if(isset($trip['error']) && $trip['error'] == 'API_UNAVAILABLE'){
				if($redirect){
					die('<div class="alerta amarilla pm5 mb1">API error: '. $trip['message'].'</div>');
				}else{
					return ["error"=>'API_UNAVAILABLE',"message"=>$trip['message'],"httpCode"=>$resTrip['httpCode']];
				}
			}
			if(isset($trip['currency'])){
				$trip['currency_text'] = $trip['currency']==1?'EUR':'USD';
			}
			$trip['trip_status'] = $trip['status'] ?? '';
			return $trip;
		}else{
			return [];
		}
	}
	function getTraveler($taveler_id){
		if(!is_null($taveler_id) && $taveler_id!==''){
			$resCall = $this->API->get('/api/travelers/'.$taveler_id);
			$traveler = [];
			$traveler = $resCall['data'];
			if(isset($traveler['error']) && $traveler['error'] == 'SESSION_ENDED'){
				header('Location:'.WEB_URL.'SALIR');
				exit;
			}else if(isset($traveler['error']) && $traveler['error'] == 'API_UNAVAILABLE'){
				die('<div class="alerta amarilla pm5 mb1">API error: '. $traveler['message'].'</div>');
			}
			return $traveler;
		}else{
			return false;
		}
	}
	function getTravelerConsolidado($traveler_id){
		if(!is_null($traveler_id) && $traveler_id!=='' && esUUID($traveler_id)){
			$res = $this->API->get('/api/utils/consolidated/'.$traveler_id);
			$traveler = [];
			
			if(isset($res['data']['error']) && $res['data']['error'] == 'SESSION_ENDED'){
				header('Location:'.WEB_URL.'SALIR');
				exit;
			}else if(isset($res['data']['error']) && $res['data']['error'] == 'API_UNAVAILABLE'){
				die('<div class="alerta amarilla pm5 mb1">API error: '. $res['data']['message'].'</div>');
			}
			if(isset($res['data']['data'])){
				$traveler = $res['data']['data'];
			}
			return $traveler;
		}else{
			return false;
		}
	}
	function getTravelersByYear($year){
		if(!is_null($year) && $year!=='' && is_numeric($year)){
			$getLimit = $this->API->get('/api/travelers/year/'.$year.'/?limit=1');
			if(isset($getLimit['data']['error']) && $getLimit['data']['error'] == 'SESSION_ENDED'){
				header('Location:'.WEB_URL.'SALIR');
				exit;
			}else if(isset($getLimit['data']['error']) && $getLimit['data']['error'] == 'API_UNAVAILABLE'){
				die('<div class="alerta amarilla pm5 mb1">API error: '. $getLimit['data']['message'].'</div>');
			}

			$limit = 0;
			if(isset($getLimit['data']['totalRecords'])){
				$limit = $getLimit['data']['totalRecords'];
			}
			
			$travelers = [];

			if($limit > 0){
				$res = $this->API->get('/api/travelers/year/'.$year.'/?limit='.$limit);
				
				if(isset($res['data']['error']) && $res['data']['error'] == 'SESSION_ENDED'){
					header('Location:'.WEB_URL.'SALIR');
					exit;
				}else if(isset($res['data']['error']) && $res['data']['error'] == 'API_UNAVAILABLE'){
					die('<div class="alerta amarilla pm5 mb1">API error: '. $res['data']['message'].'</div>');
				}
				if(isset($res['data']['data'])){
					$travelers = $res['data']['data'];
				}
			}
			return $travelers;
		}else{
			return false;
		}
	}
	function getTravelersInTravel($trip_id){
		if(!is_null($trip_id) && $trip_id!=='' && esUUID($trip_id)){
			$res = $this->API->get('/api/travelers/travel/'.$trip_id);
			$trip = [];
			
			if(isset($res['data']['error']) && $res['data']['error'] == 'SESSION_ENDED'){
				header('Location:'.WEB_URL.'SALIR');
				exit;
			}else if(isset($res['data']['error']) && $res['data']['error'] == 'API_UNAVAILABLE'){
				die('<div class="alerta amarilla pm5 mb1">API error: '. $res['data']['message'].'</div>');
			}
			if(isset($res['data']['data'])){
				$trip = $res['data']['data'];
			}
			return $trip;
		}else{
			return false;
		}
	}
	function getUsuarioAdmin($user_id){
		if(!is_null($user_id) && $user_id!==''){
			$call_1 		= $this->API->get("/api/auth/users/admin");
			$registros 		= $call_1['data'];

			$call_2 		= $this->API->get("/api/auth/users/concierge");
			$registros_2 	= $call_2['data'];
			$registros 		= array_merge($registros,$registros_2);

			$call_3 		= $this->API->get("/api/auth/users/editor");
			$registros_3 	= $call_3['data'];
			$registros 		= array_merge($registros,$registros_3);
			
			$user = [];
			foreach($registros as $reg){
				if($reg['user_id'] == $user_id){
					$user = $reg;		
				}
			}
			
			return $user;
		}else{
			return false;
		}
	}
	function getUsuarioAdminByEmail($email){
		if(!is_null($email) && $email!==''){
			$call = $this->API->get("/api/auth/users?search=".$email);
			$registro = $call['data'][0];
			
			return $registro;
		}else{
			return false;
		}
	}
	function getInstallment($installement_id){
		if(!is_null($installement_id) && $installement_id!==''){
			$resInstallement = $this->API->get('/api/installments/'.$installement_id);
			$installement = [];
			$installement = $resInstallement['data'];
			return $installement;
		}else{
			return false;
		}
	}
	function getDocs($traveler_id){
		if(!is_null($traveler_id) && $traveler_id!==''){
			$resDocs = $this->API->get('/api/documents?traveler='.$traveler_id);
			$docs = $resDocs['data'];

			return $docs;
		}else{
			return false;
		}
	}
	function getRates($page=1,$limit=5){
		$resRates = $this->API->get('/api/rates?page='.$page.'&limit='.$limit);
		$rates = $resRates['data'];

		if(isset($rates['error']) && $rates['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($rates['error']) && $rates['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $rates['message'].'</div>');
		}

		return $rates;
	}
	function getTodayRates(){
		$fechaHoy = date('Y-m-d');
		$resRates = $this->API->get('/api/rates?limit=1&search='.$fechaHoy);
		$todayRate = $resRates;

		return $todayRate;
	}
	function getLastRates(){
		$resRates = $this->API->get('/api/rates?limit=1');
		$todayRate = $resRates;

		return $todayRate;
	}
	function getCurrentRateByCurrency($currency){
		$currentRate = 0;
		$todayRate = $this->getLastRates();
		if($todayRate['success']){
			$todayRateData = $todayRate['data'];
			
			if($todayRateData['totalRecords']>0){
				switch($currency){
					case 1:
						$currencyDef = 'rate_eur';
						break;
					case 2:
						$currencyDef = 'rate_usd';
						break;
				}
				$currentRate = number_format($todayRateData['data'][0][$currencyDef],2);
			}
		}
		return $currentRate;
	}
	function getPayments($traveler_id,$page=1,$limit=1000){
		$payments = [];
		$resPayments = $this->API->get('/api/payments?page='.$page.'&limit='.$limit);
		if(isset($resPayments['data']['error']) && $resPayments['data']['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($resPayments['data']['error']) && $resPayments['data']['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $resPayments['data']['message'].'</div>');
		}
		if($resPayments['success'] && count($resPayments['data']['data'])){
			foreach($resPayments['data']['data'] as $payment){
				if($payment['traveler_id'] == $traveler_id){
					$payments[] = $payment;
				}
			}
		}

		return $payments;
	}
	function getPaymentValidated($traveler_id,$page=1,$limit=1000){
		$payments = $this->getPayments($traveler_id);
		$total = 0;
		foreach($payments as $payment){
			if($payment['payment_status'] == PAYMENT_VALIDATED_STATUS){
				$total += $payment['amount_original'];
			}
		}
		return $total;
	}
	function getPaymentByID($payment_id){
		$payments = [];
		$resPayment = $this->API->get('/api/payments/'.$payment_id);
		if(isset($resPayment['data']['error']) && $resPayment['data']['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($resPayment['data']['error']) && $resPayment['data']['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $resPayment['data']['message'].'</div>');
		}
		if($resPayment['success'] && count($resPayment['data'])>0){
			$payments = $resPayment['data'];
		}
		return $payments;
	}
	function getPaymentsProcessed($data,$payments_data = []){
		$dataPagos = [];
		$trip_id = $data['trip_id'];
		$traveler_id = $data['traveler_id'];
		$fechaHoy = $data['fechaHoy'];
		$pagosFiltrados = [];
		
		$dataPagos['trip_cost'] = $data['cost'];
		$dataPagos['cost'] = formatearCurrency($data['cost'],$data['currency']);
		
		$dataPagos['linkPagos'] = WEB_URL.'PAGOS/';

		$parcialidades = $this->getCalendarioPago($trip_id);
		$pagos = $payments_data;
		
		if(count($payments_data) < 1){
			$pagos = $this->getPayments($traveler_id);
		}

		$totalCostoViaje = $data['cost'];
		$totalPagado = 0;
		$porcentajePagado = 0;
		$totalAdeudoParcialidades = 0;
		$porcentajeAdeudo = 0;
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

		//PAGOS REALIZADOS
		foreach($pagos as $pago){
			if($pago['payment_type'] !== 'fee_online' && $pago['payment_type'] !== 'fee_document'){//NO SUMO EL PAGO SI PERTENECE A FEE
				$pagosFiltrados[] = $pago;
				if($pago['payment_status'] == PAYMENT_VALIDATED_STATUS){//VERIFICAR CUAL ES EL ESTATUS FINAL QUE SE USUARA
					$totalPagado += $pago['amount_original'];//SOLO SUMA SI YA ESTA VALIDADO
				}
			}
		}

		//PARCIALIDADES CALCULOS
		$finalPayment = [
			'amount' => $amountFinalPayment,
			'payment_date' => $data['limit_date']
		];

		array_unshift($parcialidades,$finalPayment);//AGREGA última parcialidad AL PRINCIPIO
		#array_push($parcialidades,$finalPayment);//AGREGA AL FINAL

		for($i = (count($parcialidades) - 1);$i >= 0; $i--){//sumo los adeudos de fechas vencidas (pasadas)
	        $parcialidad = $parcialidades[$i];
			if(strtotime(convertirFecha($parcialidad['payment_date'])) < strtotime($fechaHoy)){//VENCIDO
				$totalAdeudoParcialidades += $parcialidad['amount'];
			}
		}

		#$subtotalCalculo = $totalCostoViaje;
		$porPagar = 0;
		$sigFechaPago = '';
		#foreach($parcialidades as $parcialidad){//recorro el array para ver cual es la siguiente fecha
		#echo $fechaHoy."<br />";
		for($i = (count($parcialidades) - 1);$i >= 0; $i--){
			$parcialidad = $parcialidades[$i];
			$porPagar += $parcialidad['amount'];
			#echo convertirFecha($parcialidad['payment_date'])." (porPagar: {$porPagar} ) ::  <br /> ";
			#echo "Monto: {$parcialidad['amount']} <br />";
			
			#echo "totalPagado: $totalPagado < porPagar: $porPagar <br/>";
			$sigFechaPagoLabel = fechaFormato(convertirFecha($parcialidad['payment_date']));
			$sigMontoPago = $porPagar - $totalPagado;
			$sigMontoPagoLabel = formatearCurrency($sigMontoPago,$data['currency']);
			$sigFechaPago = '<div class="col bg-post-it texto-post-it pm5 ps2"><div class="proximoPago">'.__('Próximo pago sugerido el').' <span class="strong">'.$sigFechaPagoLabel.'</span> '.__('por una cantidad de').' <span class="strong">'.$sigMontoPagoLabel.'</span> ('.__('calculado dinámicamente').')</div></div>';
			#echo "<hr />";
			
			if(strtotime(convertirFecha($parcialidad['payment_date'])) > strtotime($fechaHoy)){//FECHA VENCIDA
				if($totalPagado < $porPagar){//se ha pagado mas que lo adeudado
					break;
				}
			}

		}

		//ADEUDOS
		$adeudoALaFecha = $totalAdeudoParcialidades - $totalPagado;
		if($adeudoALaFecha < 0){//NO MOSTRAR SI ES NEGATIVO
			$adeudoALaFecha = 0;
		}

		$porcentajePagado = 0;
		$porcentajeAdeudo = 0;

		if($totalCostoViaje > 0){
			$porcentajePagado = ($totalPagado * 100)/$totalCostoViaje;
			$porcentajeAdeudo = ($totalAdeudoParcialidades * 100)/$totalCostoViaje;
		}

		$tablaPagos = generarTablaDatos($pagosFiltrados,'PAGOS_SIMPLE',$this, $data);

		$dataPagos['totalPagado'] = $totalPagado;
		$adeudoCalculo = $totalAdeudoParcialidades - $totalPagado;
		if($adeudoCalculo < 0) $adeudoCalculo = 0;
		$dataPagos['totalAdeudo'] = $adeudoCalculo;

		//CALCULOS DEL MINIMO A PAGAR
		$restantePorPagar = $totalCostoViaje - $totalPagado;
		$minimoAPagar = ONLINE_MINIMUM_PAY;

		if($restantePorPagar < ONLINE_MINIMUM_PAY){//lo restante es menor que el pago mínimo, usar el restante como mínimo
			$minimoAPagar = $restantePorPagar;
		}else{
			if($adeudoALaFecha < ONLINE_MINIMUM_PAY){//el adeudo es menor que el pago mínimo, usar el pago mínimo
				$minimoAPagar = ONLINE_MINIMUM_PAY;
			}else{
				$minimoAPagar = $adeudoALaFecha;
			}
		}

		$dataPagos['minimoAPagar'] = $minimoAPagar;

		$dataPagos['totalPagadoLabel'] = formatearCurrency($totalPagado,$data['currency']);
		$dataPagos['totalAdeudoLabel'] = formatearCurrency($adeudoCalculo,$data['currency']);

		$dataPagos['totalAdeudoParcialidades'] = $totalAdeudoParcialidades;
		$dataPagos['porcentajePagado'] = number_format($porcentajePagado,1);
		$dataPagos['porcentajeAdeudo'] = number_format($porcentajeAdeudo,1);
		$dataPagos['sigFechaPago'] = $sigFechaPago;
		$dataPagos['sigFechaPagoLabel'] = $sigFechaPagoLabel;

		$dataPagos['tablaPagos'] = $tablaPagos;

		return $dataPagos;
	}

	function tienePagosPendientesValidar($payments_data){
		$contador = 0;
		foreach($payments_data as $pago){
			if(($pago['payment_type'] == 'transfer' || $pago['payment_type'] == 'cash' || $pago['payment_type'] == 'fee_document') && $pago['payment_status'] == 'pending'){//Tomo pagos de documentos, pendientes
				$contador++;
			}
		}
		return $contador;
	}
	function getCalendarioPago($trip_id){
		$calendario = [];
		$resCalendario = $this->API->get('/api/installments?trip='.$trip_id);
		if($resCalendario['success'] && $resCalendario['data']['totalRecords']>0){
			$calendario = $resCalendario['data']['data'];
		}

		return $calendario;
	}
	function getNotification($notification_id){
		$notification = [];
		$resCall = $this->API->get('/api/notifications/'.$notification_id);
		if(isset($resCall['data']['error']) && $resCall['data']['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($resCall['data']['error']) && $resCall['data']['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $resCall['data']['message'].'</div>');
		}
		if($resCall['success'] && count($resCall['data']) >0){
			$notification = $resCall['data'];
		}
		return $notification;
	}
	function addNotification($traveler_id,$type,$recipient_role,$title,$message){
		$createNotification = [
			"traveler_id" => $traveler_id,
			"recipient_role" => $recipient_role,
			"type" => $type,
			"title" => $title,
			"message" => $message
		];
		$resCall = $this->API->post('/api/notifications/',$createNotification);
		if($resCall['success'] && count($resCall['data']) >0){
			$notification = $resCall['data'];
		}
		return $notification;
	}
	function setNotificationRead($notification_id,$updateNotification){
		$resCall = $this->API->put('/api/notifications/'.$notification_id,$updateNotification);
		return $resCall;
	}
	function getNotifications($traveler_id){
		$notifications = [];
		$resCall = $this->API->get('/api/notifications/traveler/'.$traveler_id.'?page=1&limit=10&is_read=false');
		$totalRecords = 0;
		if(isset($resCall['data']['error']) && $resCall['data']['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($resCall['data']['error']) && $resCall['data']['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $resCall['data']['message'].'</div>');
		}
		if($resCall['success'] && $resCall['data']['totalRecords']>0){			
			$notificationsTemp = $resCall['data']['data'];
			$totalRecords = $resCall['data']['totalRecords'];
			foreach ($notificationsTemp as $notification) {
				if($notification['traveler_id'] == $traveler_id && $notification['recipient_role'] == 'traveler'){
						$notifications[] = $notification;
				}
			}
		}

		return ['notifications' => $notifications,'totalRecords' => $totalRecords];
	}
	function getNotificationsAdmin(){
		$notifications = [];
		$totalRecords = 0;
		$resCall = $this->API->get('/api/notifications?page=1&limit=10&type=admin');
		if(isset($resCall['data']['error']) && $resCall['data']['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($resCall['data']['error']) && $resCall['data']['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $resCall['data']['message'].'</div>');
		}
		if($resCall['success'] && $resCall['data']['totalRecords']>0){
			$notificationsTemp = $resCall['data']['data'];
			foreach ($notificationsTemp as $notification) {
				if(!$notification['is_read']){
					$notifications[] = $notification;
				}
			}
			$totalRecords = $resCall['data']['totalRecords'];
		}
		return ['notifications' => $notifications,'totalRecords' => $totalRecords];
	}
	function getDocumentByType($traveler_id,$document_type=''){
		$documentos = [];
		if(!is_null($traveler_id) && $traveler_id!==''){
			$filtroTipo = '';
			if($document_type !== '') $filtroTipo = '&search='.$document_type;
			$resDocs = $this->API->get('/api/documents?traveler='.$traveler_id.$filtroTipo);
			$documentos = [];
			if(isset($resDocs['data']['error']) && $resDocs['data']['error'] == 'SESSION_ENDED'){
				header('Location:'.WEB_URL.'SALIR');
				exit;
			}else if(isset($resDocs['data']['error']) && $resDocs['data']['error'] == 'API_UNAVAILABLE'){
				die('<div class="alerta amarilla pm5 mb1">API error: '. $resDocs['data']['message'].'</div>');
			}
			if(isset($resDocs['data']['data'])){
				$documentos = $resDocs['data']['data'];
			}			
		}
		return $documentos;
	}
	function searchDocumentByType($documents_data,$document_type=''){
		$documentos = [];
		if(count($documents_data) > 0){
			foreach ($documents_data as $doc) {
			    if ($doc['document_type'] === $document_type) {
			        $documentos[] = $doc;
			    }
			}
		}
		return $documentos;
	}
	function getDocumentByID($document_id){
		$documento = [];
		if(!is_null($document_id) && $document_id!==''){
			$resDocs = $this->API->get('/api/documents/'.$document_id);
			$documento = $resDocs['data'];
			if(isset($documento['error']) && $documento['error'] == 'SESSION_ENDED'){
				header('Location:'.WEB_URL.'SALIR');
				exit;
			}else if(isset($documento['error']) && $documento['error'] == 'API_UNAVAILABLE'){
				die('<div class="alerta amarilla pm5 mb1">API error: '. $documento['message'].'</div>');
			}
		}
		return $documento;
	}
	function initLanguage($lang){
		if($this->esAdmin()){//Si es Admin sólo en español
			$this->setLanguage('es');
		}else{
			#LOGICA PARA OBTENER LANG
			if(!empty($lang)){
				$this->setLanguage($lang);
			}else{
				$this->setLanguage('es');
			}
		}
	}
	function getLanguage(){
		return $_SESSION['language'];
	}
	function setLanguage($valor,$persistencia=FALSE){
		$_SESSION['language'] = $valor;
		//LOGICA PARA GUARDAR EL LANG -> API
		if($persistencia){
			$email = $this->obtenerUsrData('email');
			$traveler_data = $this->getUserData($email,FALSE);
			if(isset($traveler_data['data']['error']) && $traveler_data['data']['error']=='SESSION_ENDED'){
				return FALSE;
			}else if(isset($traveler_data['data']['error']) && $traveler_data['data']['error']=='API_UNAVAILABLE'){
				die('<div class="alerta amarilla pm5 mb1">API error: '. $traveler_data['data']['message'].'</div>');
			}
			$traveler_id = $traveler_data['traveler_id'];
			$traveler_data['language'] = $valor;
			$res = $this->API->put('/api/travelers/'.$traveler_id, $traveler_data);
			if($res['success']){
				return TRUE;
			}else{
				return FALSE;
			}
		}
	}
	function getReportPayments($year){
		if(empty($year) || !isset($year)){
			$year = YEAR_FILTER;
		}
		
		$res = $this->API->get('/api/utils/reportPayments/'.$year);
		$report = [];
		$reportData = [];
		if(isset($res['error']) && $res['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($res['data']['error']) && $res['data']['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $res['data']['message'].'</div>');
		}

		if(isset($res['data']['data'])){
			$reportData = $res['data']['data'];
			$report['CostoTotal'] = $this->procesarReportePagos($reportData);
			$report['ReporteXGrupo'] = $this->procesarReporteXGrupo($reportData);
		}
		return $report;
	}
	function procesarReportePagos($data=[]){
		$report = [];
		if(count($data)>0){
			foreach($data as $trip){
				$monedaArr = getCurrency($trip['currency']);
				$moneda = $monedaArr['moneda'];
				if(!isset($report[$moneda])){ 
					$report[$moneda]['total_trip_cost'] = 0;
					$report[$moneda]['total_paid'] = 0;
					$report[$moneda]['travelers_count'] = 0;
					$report[$moneda]['remaining_amount'] = 0;
					$report[$moneda]['trips_count'] = 0;
				}

				$report[$moneda]['total_trip_cost'] += $trip['total_trip_cost'];
				$report[$moneda]['total_paid'] += $trip['total_paid'];
				$report[$moneda]['travelers_count'] += $trip['travelers_count'];
				$report[$moneda]['remaining_amount'] += $trip['remaining_amount'];
				$report[$moneda]['trips_count']++;

			}
		}
		return $report;
	}
	function procesarReporteXGrupo($data=[]){
		$buffer = '';
		$renglones = '';
		if(count($data)>0){
			foreach($data as $trip){
				#echo ':::TRIP:::';
				#print_pre($trip);
				$monedaArr = getCurrency($trip['currency']);
				$moneda = $monedaArr['moneda'];
				$simboloMoneda = $monedaArr['simboloMoneda'];
				$trip['moneda'] = $moneda;
				$trip['simboloMoneda'] = $simboloMoneda;
				
				$renglones .= makeTemplate('row_grupos_pagos.html',$trip,'admin');
			}
		}else{
			$renglones = '<tr><td colspan="7" class="acenter">No hay registros para mostrar</td></tr>';
		}
		$buffer = makeTemplate('tabla_grupos_pagos.html',['CELDAS'=>$renglones],'admin');
		return $buffer;
	}
	function getReportTravelers($trip){
		if(empty($trip) || !isset($trip)){
			return FALSE;
		}
		
		$res = $this->API->get('/api/travelers/travel/'.$trip);
		#print_pre($res);
		$report = [];
		#$reportData = [];
		if(isset($res['error']) && $res['error'] == 'SESSION_ENDED'){
			header('Location:'.WEB_URL.'SALIR');
			exit;
		}else if(isset($res['data']['error']) && $res['data']['error'] == 'API_UNAVAILABLE'){
			die('<div class="alerta amarilla pm5 mb1">API error: '. $res['data']['message'].'</div>');
		}

		if(isset($res['data']['data'])){
			$report = $res['data']['data'];
			#$report['CostoTotal'] = $this->procesarReportePagos($reportData);
			#$report['ReporteXGrupo'] = $this->procesarReporteXGrupo($reportData);
		}
		return $report;
	}
	function calcularMontosCalendario($costoTotal,$subtotalParcialidades,$currency){
		$numero = ($costoTotal - $subtotalParcialidades);
		$diferenciaTotalParcialidades = number_format($numero, 2);//, '.', ','   <---- separador de decimales y de miles
		return formatCurrency($diferenciaTotalParcialidades,$currency);
	}
	function createVisorDoc($documento,$traveler_id){
		$document_id = $documento['document_id'];
		$mime = $documento['mime_type'];
		$document_type = $documento['document_type'];
		$bufferVisor = '';

		if ($mime == 'application/pdf') {
			$bufferVisor .= '    <object';
			$bufferVisor .= '        data="'.WEB_URL.'webservice/verDocumento.php?id='.$document_id.'&trav='.$traveler_id.'&tipo='.$document_type.'"';
			$bufferVisor .= '        type="application/pdf"';
			$bufferVisor .= '        width="100%"';
			$bufferVisor .= '        height="500">';

			$bufferVisor .= '        <p>';
			$bufferVisor .= '            El navegador no puede mostrar el PDF.';
			$bufferVisor .= '            <a href="'.WEB_URL.'webservice/verDocumento.php?id='.$document_id.'&trav='.$traveler_id.'&tipo='.$document_type.'">Descargar</a>';
			$bufferVisor .= '        </p>';

			$bufferVisor .= '    </object>';
		}else{
			$bufferVisor .= '    <a href="'.WEB_URL.'webservice/verDocumento.php?id='.$document_id.'&trav='.$traveler_id.'&tipo='.$document_type.'" target="_blank">';
			$bufferVisor .= '    <img';
			$bufferVisor .= '        src="'.WEB_URL.'webservice/verDocumento.php?id='.$document_id.'&trav='.$traveler_id.'&tipo='.$document_type.'"';
			$bufferVisor .= '        style="max-width:100%;max-height:500px;">';
			$bufferVisor .= '    </a>';
		}
		return $bufferVisor;
	}
	function createDownloadDocLink($documento,$traveler_id){
		$document_id = $documento['document_id'];
		$document_type = $documento['document_type'];
		$link = WEB_URL.'webservice/verDocumento.php?id='.$document_id.'&trav='.$traveler_id.'&tipo='.$document_type.'&descargar=1';

		return $link;
	}
	function getCurrentYearRegister(){
		$resConfig = $this->API->get('/api/configurations?search=CURRENT_YEAR_REGISTER');
		$config = INIT_YEAR_OPTIONS;
		if(isset($resConfig['data']['data'][0])){//SI HAY DATO
			$config = $resConfig['data']['data'][0]['value'];
		}else{//NO HAY DATO LO GENERO
			$createData = ['configuration_name'=>'CURRENT_YEAR_REGISTER','value'=>INIT_YEAR_OPTIONS];
			$createConfig = $this->API->post('/api/configurations',$createData);
		}
		return $config;
	}
	function getYearFilter(){
		$resConfig = $this->API->get('/api/configurations?search=YEAR_FILTER');
		$config = INIT_YEAR_OPTIONS;
		if(isset($resConfig['data']['data'][0])){//SI HAY DATO
			$config = $resConfig['data']['data'][0]['value'];
		}else{//NO HAY DATO LO GENERO
			$createData = ['configuration_name'=>'YEAR_FILTER','value'=>INIT_YEAR_OPTIONS];
			$createConfig = $this->API->post('/api/configurations',$createData);
		}
		return $config;
	}
	function getConfig($param){
		$resConfig = $this->API->get('/api/configurations?search='.$param);
		$configuration_id = '';
		if(isset($resConfig['data']['data'][0])){//SI HAY DATO
			$configuration_id = $resConfig['data']['data'][0]['configuration_id'];
		}	
		return $configuration_id;
	}
}
?>
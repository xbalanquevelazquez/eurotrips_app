<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;
$data['data1'] = $data1;

$data['NOMBRE'] = $name = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $email = $Admin->obtenerUsr('usr');
$data['mensaje'] = '';

$data['CURRENT_YEAR_REGISTER'] = CURRENT_YEAR_REGISTER;
$data['YEAR_FILTER'] = YEAR_FILTER;

$data['consultorHidden'] = '';
$data['consultorShow'] = ' hidden';
if($Admin->esConsultor() || $Admin->esEditor()){
	$data['consultorHidden'] = ' hidden';
	$data['consultorShow'] = '';
}


if(			$data2=='USER' && $data3 !='new'){ include_once("adm.$data1.USER.edit.php");}
else if(	$data2=='USER' && $data3=='new') { include_once("adm.$data1.USER.new.php");}
else {
	date_default_timezone_set('America/Mexico_City');
	$fechaHoy = date('Y-m-d');

	$ratesRes = $Admin->getRates();
	#print_pre($ratesRes);

	if(isset($ratesRes['error']) && ($ratesRes['error'] == 'SESSION_ENDED')){
		header('Location:'.WEB_URL.'SALIR');
		exit;
	}else if(isset($ratesRes['error']) && ($ratesRes['error'] == 'API_UNAVAILABLE')){
		die('<div class="alerta amarilla pm5 mb1">API error: '. $ratesRes['message'].'</div>');
	}
	$rates = [];
	$ultimoRate = []; 
	$existeRegistroHoy = FALSE;
	$permitirRegistroHoy = TRUE;

	if(isset($ratesRes['totalRecords']) && $ratesRes['totalRecords']>0){//hay rates
		$rates = $ratesRes['data'];
		$ultimoRate = $rates[0];

		$fechaUltimoRate = convertirFecha($ultimoRate['date']);
		if ($fechaUltimoRate === $fechaHoy) {
			$existeRegistroHoy = TRUE;
			$permitirRegistroHoy = FALSE;
			if($Admin->esPerfilAdmin()){//lo quito sólo si soy admin
				array_shift($rates);//quito el registro reciente sólo si es el de hoy 
			}
		}
	}


	if(isset($_POST['sendArr'])) {

		$sendArr = $_POST['sendArr'];

		$errores = validarTiposDeCambio($sendArr);
		$showErrores = '';

		if (!empty($errores)) {
		    foreach ($errores as $error) {
		        $data['mensaje'] .= '<div class="pm5 alerta">'.$error . '</div>';
		    }
		}else{
			//VALIDAR QUE NO EXISTE YA UN REGISTRO PARA HOY
			if($permitirRegistroHoy){

				$sendArr['date'] = $fechaHoy;
				#print_r($sendArr);
				$res = $Admin->API->post('/api/rates/', $sendArr);
				if($res['success']){
					//CREAR UN DOCUMENTO PASSPORT
					$Admin->setMensaje('<div class="pm5 alerta verde">Se guardaron los datos del usuario.</div>');
					header('Location:'.WEB_URL.$data1);
					exit;
				}else{
					if($res['httpCode'] == 403 || $res['httpCode'] == 401){
						header('Location:'.WEB_URL.'SALIR');
						exit;
					}
					$data['mensaje'] = '<div class="warning">Error: httpCode '.$res['httpCode'].' : '.$res['data']['message'].'</div>';
				}
			}else{
				$data['mensaje'] = '<div class="warning">Ya existe un registro para el día de hoy.</div>';
			}
		}
	}

	$data['registroTipoCambio'] = '';
	if(isset($ultimoRate['rate_eur'])){
		$data['inputEuros'] = number_format($ultimoRate['rate_eur'],2);
	}else{
		$data['inputEuros'] = '-';
	}
	if(isset($ultimoRate['rate_usd'])){
		$data['inputDolares'] = number_format($ultimoRate['rate_usd'],2);
	}else{
		$data['inputDolares'] = '-';
	}

	$data['btnGuardar'] = '';

	if($permitirRegistroHoy){
		$data['inputEuros'] = '<input id="euros" name="sendArr[rate_eur]" type="text" min="0" step="0.01" inputmode="decimal" autocomplete="off" class="requerido" />';
		$data['inputDolares'] = '<input id="dolares" name="sendArr[rate_usd]" type="text" min="0" step="0.01" inputmode="decimal" autocomplete="off" class="requerido" />';
		$data['btnGuardar'] = '<button type="submit" class="btn primario" id="sendForm"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>';
	}
	if(!$Admin->esConsultor() && !$Admin->esEditor()){
		$data['registroTipoCambio'] = makeTemplate('admin_registro_tipo_cambio.html', $data, 'admin');
	}
	$data['linkNuevo'] = WEB_URL.$data1.'/USER/new';

	$data['fechaHoy'] = fechaFormato('');//ej. 17 de julio de 2026
	$data['tablaEUR'] = generarTablaDatos($rates,'RATES_EUR',$Admin);
	$data['tablaUSD'] = generarTablaDatos($rates,'RATES_USD',$Admin);
	$data['moduloUsuarios'] = '';
	$data['config_variables'] = '';

	if(!$Admin->esConsultor() && !$Admin->esEditor()){
		$data['moduloUsuarios'] = makeTemplate('admin_usuarios.html', $data, 'admin');
		$data['config_variables'] = makeTemplate('admin_config_variables.html', $data, 'admin');
	}

	echo makeTemplate('admin_config.html', $data, 'admin');

} ?>
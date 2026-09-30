<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;
$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['NOMBRE'] = $name = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $email = $Admin->obtenerUsr('usr');
$data['USER_ID'] = $traveler_id = $Admin->obtenerUsr('user_id');
$userData = $Admin->getUserData($email);
$data['year'] = $traveler_year_asoc = $Admin->obtenerUsr('year');

//PHONES
$phoneArr = getLADAFromPhone($userData['phone_number'] ?? '');
$parentPhoneArr = getLADAFromPhone($userData['parent_phone_number'] ?? '');

$userData['phone_number'] = $phoneArr['TEL'];
$userData['lada'] = $phoneArr['LADA'];
$userData['lada_icon'] = $phoneArr['ICON'];

$userData['parent_phone_number'] = $parentPhoneArr['TEL'];
$userData['parent_lada'] = $parentPhoneArr['LADA'];
$userData['parent_lada_icon'] = $parentPhoneArr['ICON'];

$data['op_LADA_MX'] = '';
$data['op_LADA_US'] = '';
$data['op_LADA_ES'] = '';
switch($userData['lada']){
	case '+1':
		$data['op_LADA_US'] = ' selected="selected"';
		break;
	case '+34':
		$data['op_LADA_ES'] = ' selected="selected"';
		break;
	case '+52':
	default:
		$data['op_LADA_MX'] = ' selected="selected"';
		break;
}

$data['op_parent_LADA_MX'] = '';
$data['op_parent_LADA_US'] = '';
$data['op_parent_LADA_ES'] = '';
switch($userData['parent_lada']){
	case '+1':
		$data['op_parent_LADA_US'] = ' selected="selected"';
		break;
	case '+34':
		$data['op_parent_LADA_ES'] = ' selected="selected"';
		break;
	case '+52':
	default:
		$data['op_parent_LADA_MX'] = ' selected="selected"';
		break;
}

$data = array_merge($data,$userData);

$data['birth_date'] = convertirFecha($data['birth_date'] ?? '');
$data['mensaje'] = '';

$trip_id = $userData['trip_id'] ?? '';

if(isset($_POST['sendArr'])) {

	$sendArr = $_POST['sendArr'];
	$errores = validarRegistroEdicion($sendArr);
	$showErrores = '';
	if (!empty($errores)) {
	    foreach ($errores as $error) {
	        $data['mensaje'] .= $error;
	    }
	    exit;
	}else{

		$confirmUserData = $Admin->getTraveler($traveler_id);

		$trip_id = is_null($confirmUserData['trip_id'])?NULL:$confirmUserData['trip_id'];
		$sendArr['trip_id'] = $trip_id;
		$sendArr['name'] = $name;
		$sendArr['year'] = $confirmUserData['year'];
		$sendArr['language'] = $confirmUserData['language'];
		$sendArr['support_number'] = $confirmUserData['support_number'];

		$sendArr['phone_number'] = $sendArr['lada'].$sendArr['phone_number'];
		$sendArr['parent_phone_number'] = $sendArr['parent_lada'].$sendArr['parent_phone_number'];
		unset($sendArr['lada']);
		unset($sendArr['parent_lada']);
		echo $traveler_id;
		#print_pre($sendArr);
		#die();
		$res = $Admin->API->put('/api/travelers/'.$traveler_id, $sendArr);
		if($res['success']){
			$Admin->setMensaje('<div class="pm5 alerta verde">'.__('Se guardaron los datos del usuario').'.</div>');
			header('Location:'.WEB_URL.$data1);
			exit;
		}else{
			$data['mensaje'] = '<div class="warning">Error: httpCode '.$res['httpCode'].' : '.$res['data']['message'].'</div>';
		}
	}
}

$res = $Admin->API->get('/api/travelers/'.$data['USER_ID']);
#print_pre($res);
if($res['success']){
	$mensaje = $Admin->getMensaje();
	if($mensaje != ''){
		$data['mensaje'] = $mensaje;
		$Admin->cleanMensaje();
	}
}else{
	$data['mensaje'] = '<div class="warning">Error: httpCode '.$res['httpCode'].' : '.$res['data']['message'].'</div>';
	$data['mensaje'] .= '<div class="warning">/api/travelers/'.$data['USER_ID'].'</div>';
	if($res['httpCode'] == 403 || $res['httpCode'] == 401){
		header('Location:'.WEB_URL.'SALIR');
		exit;
	}
}

echo makeTemplate('panel_perfil.html', $data, 'site');
?>
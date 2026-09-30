<?php
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
$data = array();
$data['returnLink'] = WEB_URL.$data1;
$data['trip_name'] = '';
$data['status'] = '';

$data['status_selected_1'] = ' selected="selected"';//preparing
$data['status_selected_2'] = '';

$data['itinerary_url'] = '';
$data['currency'] = '';

$data['currency_selected_1'] = ' selected="selected"';//EUR
$data['currency_selected_2'] = '';

$data['cost'] = '0.00';

$data['alerta_envio'] = '';
$data['alerta_trip_name'] = '';
$data['alerta_itinerary_url'] = '';
$data['alerta_cost'] = '';
$data['alerta_fecha'] = '';

$bufferOptions = '';
for($a = CURRENT_YEAR_REGISTER;$a >= INIT_YEAR_OPTIONS;$a--){
	$selected = '';
	if($a == CURRENT_YEAR_REGISTER) $selected = 'selected="selected"';
	$bufferOptions .= '<option value="'.$a.'" '.$selected.'>'.$a.'</option>';
}
$data['yearOptions'] = $bufferOptions;

if(isset($_POST['sendArr'])) {
	$sendArr = $_POST['sendArr'];

	$continue = TRUE;
	if($sendArr['trip_name'] == ''){
		$continue = FALSE;
		$data['alerta_trip_name'] = '<div class="warning">Este campo no puede estar vacío.</div>';
	}

	if($sendArr['itinerary_url'] == ''){
		$sendArr['itinerary_url'] = '#';
	}

	if($continue){
		#print_r($sendArr);
		$res = $Admin->API->post('/api/trips', $sendArr);
		#print_r($res);
		if($res['success']){
			header('Location:'.WEB_URL.$data1);
			exit;
		}else{
			if($res['httpCode'] == 403 || $res['httpCode'] == 401){
				header('Location:'.WEB_URL.'SALIR');
				exit;
			}
			$data['alerta_envio'] = '<div class="warning">Error: httpCode '.$res['httpCode'].' : '.$res['data']['message'].'</div>';
		}
	}
}

echo makeTemplate('admin_viaje_new.html', $data, 'admin');
?>
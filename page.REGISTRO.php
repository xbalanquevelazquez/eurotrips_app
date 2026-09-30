<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['TURNSTILE_SITE_KEY'] = TURNSTILE_SITE_KEY;

$data['FEE_TEXT_MXN'] = FEE_TEXT_MXN;
$data['FEE_TEXT_USD'] = FEE_TEXT_USD;
$data['CURRENT_YEAR_REGISTER'] = CURRENT_YEAR_REGISTER;

$data['errorMsg'] = $GLOBALS['errorMsg'] ?? '';
$data['mensaje'] = '';
$data['lang'] = $data2;

if(		$data2=='OK'){ 	include_once("page.$data1.OK.php");}
else{
	if(isset($_POST['sendArr'])) {

		$tokenTurnstile = $_POST['cf-turnstile-response'] ?? '';
		$validacion = validarTokenTurnstile($tokenTurnstile);
		if($validacion['success']){
			$sendArr = $_POST['sendArr'];
			$errores = validarRegistro($sendArr);
			$showErrores = '';

			$checkboxes = [
			    'accepted_cancellation_policy',
			    'accepted_rules',
			    'accepted_first_payment',
			    'accepted_privacy_policy',
			    'accepted_traveler',
			    'accepted_parent'
			];

			foreach ($checkboxes as $campo) {
			    if (empty($sendArr[$campo])) {
			        $errores[] = "Debe aceptar {$campo}.";
			    }
			}

			if (!empty($errores)) {
			    foreach ($errores as $error) {
			        $data['mensaje'] .= $error . '<br>';
			    }
			}else{
				unset($sendArr['confirmPassword']);
				$sendArr['name'] = normalizarNombre($sendArr['name']);
				$sendArr['email'] = strtolower($sendArr['email']);
				$sendArr['phone_number'] = $sendArr['lada'].$sendArr['phone_number'];
				$sendArr['parent_phone_number'] = $sendArr['parent_lada'].$sendArr['parent_phone_number'];
				$sendArr['year'] = CURRENT_YEAR_REGISTER;

				$res = $Admin->API->post('/api/signin', $sendArr);

				//CAMBIO PARA REGISTRO DE LANG
				/*if($sendArr['language'] != 'es'){
					$traveler_id = $res['data']['traveler_id'];
					$resLang = $Admin->API->put('/api/travelers/'.$traveler_id, $sendArr);
					print_pre($resLang);
					die();

				}*/

				if($res['success']){
					$contenido = "El usuario <span style='font-weight:bold'>{$sendArr['name']}</span>, con correo electrónico <span style='font-weight:bold'>{$sendArr['email']}</span>, se acaba de registrar en el Panel del Viajero de Eurotrips.";
					
					$datosMensaje = [
						"siteURL" => WEB_URL,
						"contenido" => 	$contenido
					];
					
					$mailResponse = enviarMensaje(
										$destinatario = ADMIN_NOTIF_MAIL,
										$tituloMensaje='Eurotrips - se registró un usuario', 
										$templateName='correo_aviso.html', 
										$datosMensaje
									);

					header('Location:'.WEB_URL.$data1.'/OK');
					exit;
				}else{
					if(isset($res['data']['error']) && $res['data']['error']=='API_UNAVAILABLE'){
						$data['mensaje'] = '<div class="warning">La API no esta disponible.</div>';

					}else{
						$data['mensaje'] = '<div class="warning">El correo que está intentando registrar ya se encuentra registrado.</div>';

					}
				}
			}
		}else{
			$data['mensaje'] = $validacion['error'];
		}
	}
	$selectedTemplate = 'formulario_registro.html';
	if($data2 == 'EN'){
		$selectedTemplate = 'formulario_registro_EN.html';
	}

	echo makeTemplate($selectedTemplate, $data, 'site');
}
?>
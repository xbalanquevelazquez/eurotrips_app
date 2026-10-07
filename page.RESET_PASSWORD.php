<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['TURNSTILE_SITE_KEY'] = TURNSTILE_SITE_KEY;
$data['CURRENT_YEAR_REGISTER'] = CURRENT_YEAR_REGISTER;

$data['errorMsg'] = $GLOBALS['errorMsg'] ?? '';
$data['mensaje'] = '';

if(		$data2=='OK'){ 	include_once("page.$data1.OK.php");}
else{
	$token = $data2;
	$tokenHash = hash('sha256',$token);

	$tokenHash_escaped = $conn->real_escape_string($tokenHash);
	$resultado = $conn->query("SELECT * FROM password_resets WHERE token_hash='$tokenHash_escaped'");

	$num = $conn->num_rows($resultado);
	$registros = $conn->fetch($resultado);
	$now = date('Y-m-d H:i:s');

	if(isset($_POST['sendArr']) && $num > 0){
		$tokenTurnstile = $_POST['cf-turnstile-response'] ?? '';
		$validacion = validarTokenTurnstile($tokenTurnstile);

		if($validacion['success']){
			$sendArr = $_POST['sendArr'];
			$registro = $registros[0];

			$errores = validarReestablecerPasswords($sendArr);
			#print_pre($errores);

			$showErrores = '';
			if (count($errores)>0) {
			    foreach ($errores as $error) {
			        $data['mensaje'] .= $error . '<br>';
			    }
			}else{
				unset($sendArr['confirmPassword']);
				$user = $Admin->API->get('/api/auth/users/?search='.$registro['email']);

				if(count($user['data']) > 0 && isset($user['data'][0]['user_id'])){
					$user = $user['data'][0];
					$updateData = [
						"email" => $user['email'],
						"name" => $user['name'],
					    "password" => $sendArr['password'],
					    "role" => $user['role'],
					    "is_active" => $user['is_active']
					];
					$res = $Admin->API->put('/api/auth/users/'.$user['user_id'], $updateData);					

					if($res['success']){
						### BORRAR REGISTRO PARA CAMBIO PASS
						$conn->delete('password_resets',"token_hash='$tokenHash'");

						header('Location:'.WEB_URL.$data1.'/OK');
						exit;
					}else{
						$data['reestablecerForm'] = '<div class="warning">Error: httpCode '.$res['httpCode'].' : '.$res['data']['message'].'</div>';
						$data['reestablecerForm'] .= '<div class="warning">El correo que está intentando registrar ya se encuentra registrado.</div>';
					}
				}else{
					$data['reestablecerForm'] = '<div class="warning">Usuario no encontrado.</div>';
				}
			}
		}
	}

	if($num > 0){
		$registro = $registros[0];
		if(strtotime($now) < strtotime($registro['expires_at'])){
			#CONTINUAR
			$data['reestablecerForm'] = makeTemplate('panel_reset_password_fields.html', $data, 'site');
		}else{
			###VENCIO EL TIEMPO
			$data['reestablecerForm'] = '<div class="p2"><div class="warning">El link perdió vigencia, por favor genere otro.</div></div>';
		}
	}else{
		$data['reestablecerForm'] = '<div class="p2"><div class="warning">Token inválido.</div></div>';
	}
	echo makeTemplate('panel_reset_password.html', $data, 'site');
}
?>
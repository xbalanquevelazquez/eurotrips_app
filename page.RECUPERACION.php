<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;

$data['TURNSTILE_SITE_KEY'] = TURNSTILE_SITE_KEY;

$data['CURRENT_YEAR_REGISTER'] = CURRENT_YEAR_REGISTER;

$data['errorMsg'] = $GLOBALS['errorMsg'] ?? '';
$data['mensaje'] = '';
$data['lang'] = $data2;

if(		$data2=='OK'){ 	include_once("page.$data1.OK.php");}
else{
	if(isset($_POST['email'])) {

		$tokenTurnstile = $_POST['cf-turnstile-response'] ?? '';
		$validacion = validarTokenTurnstile($tokenTurnstile);

		if($validacion['success']){

			$email = strtolower(trim($_POST['email']));

			if(validar($email,'email')){
				###   VALIDAR QUE EXISTE EL MAIL DEL USUARIO
				$emailSearch = $Admin->API->get("/api/auth/users/?search=".$email);

				if($emailSearch['success'] && count($emailSearch['data']) > 0){
					###   HAY CORREO, CONTINUAR
					###   GENERO TOKEN
					$token = generarTokenRecuperacion();
					$tokenHash = hash('sha256',$token);
					$expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora
					$now = date('Y-m-d H:i:s'); // 1 hora

					$link = WEB_URL.'RESET_PASSWORD/'.$token;

					$datosRegistro = [
						'email' => $email,
						'token_hash' => $tokenHash,
						'expires_at' => $expires,
						'created_at' => $now
					];

					$registro = $conn->replace('password_resets',$datosRegistro);

					$contenido = "<h3>Recuperación de cuenta</h3>";
					$contenido .= "<p>De clic en el siguiente enlace para reestablecer su contraseña:</p>";
					$contenido .= "<p><a href=\"$link\">Reestablecer contraseña</a></p>";
					$contenido .= "<p>Este enlace expira en 1 hora</p>";

					$datosMensaje = [
						"siteURL" => WEB_URL,
						"contenido" => 	$contenido
					];
					$mailResponse = enviarMensaje(
										$destinatario = $email,
										$tituloMensaje='Eurotrips - reestablecer contraseña', 
										$templateName='correo_aviso.html', 
										$datosMensaje
									);
					if($mailResponse['success']){
						### 
					}
				}
				$data['mensaje'] = "<div class='alerta amarilla p1'>Si existe su correo en la base de datos, recibirá un correo con instrucciones de recuperación de contraseña.</div>";
			}else{
				$data['mensaje'] = "<div class='warning'>Hay un problema con el correo que ingreso.</div>";
			}
		}else{
			$data['mensaje'] = $validacion['error'];
		}
	}
	echo makeTemplate('panel_recuperacion_contrasena.html', $data, 'site');
}
?>
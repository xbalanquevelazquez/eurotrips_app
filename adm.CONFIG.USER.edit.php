<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;
$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;
$data['data1'] = $data1;

$data['NOMBRE'] = $name = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $email = $Admin->obtenerUsr('usr');

$mensaje = $Admin->getMensaje();
$data['mensaje'] = '';
if($mensaje != ''){
	$data['mensaje'] = $mensaje;
	$Admin->cleanMensaje();
}

$data['returnLink'] = WEB_URL.$data1;
$continuar = TRUE;

if(isset($_POST['sendArr'])) {

	$sendArr = $_POST['sendArr'];

	if(isset($data3) && !empty($data3) && esUUID($data3)){
		$UUID = $data3;
		$updateData = [];

		if(isset($sendArr['activo'])){
			$updateData['is_active'] = TRUE;
		}else{
			$updateData['is_active'] = FALSE;
		}

		if (!isset($sendArr['name']) || trim($sendArr['name']) === '') {
           	$Admin->addMensaje("<div class='warning'>El Nombre es obligatorio.</div>");
           	$continuar = FALSE;
        }
		if (!isset($sendArr['email']) || empty($sendArr['email'])) {
           	$Admin->addMensaje("<div class='warning'>Falta el correo electrónico.</div>");
           	$continuar = FALSE;
        }

        $updateData['name'] = $sendArr['name'];
        $updateData['email'] = $sendArr['email'];
        $updateData['role'] = $sendArr['role'];
        
        if($continuar){
    		if(!empty($sendArr['password']) && !empty($sendArr['confirmPassword'])){//SI HAY PASSWORD
    		 	// Contraseñas
    		    if (($sendArr['password']) !== ($sendArr['confirmPassword'] ?? '')) {
    		        $Admin->addMensaje("<div class='warning'>Las contraseñas no coinciden.</div>");
    		    }else{
            		$updateData['password'] = $sendArr['password'];
    				$res = $Admin->API->put('/api/auth/users/'.$UUID, $updateData);
    		    }
    		}else{
    			$res = $Admin->API->put('/api/auth/usersData/'.$UUID, $updateData);
    		}
			if(isset($res['success']) && $res['success']){
				$Admin->addMensaje('<div class="pm5 alerta verde">Se guardaron los datos del usuario.</div>');
				header('Location:'.WEB_URL.$data1.'/'.$data2.'/'.$data3);
				exit;
			}else{
				$Admin->addMensaje('<div class="warning">Error: httpCode '.$res['httpCode'].' : '.($res['data']['message'] ?? '').'</div>');
			}
        }



		
	}
}

if(isset($data3) && !empty($data3) && esUUID($data3) && $Admin->esAdmin()){
	$UUID = $data3;
	$user = $Admin->getUsuarioAdmin($UUID);
	$data = array_merge($data,$user);

	$data['optionAdmin'] = '';
	$data['optionConcierge'] = '';
	switch($user['role']){
		case 'admin':
			$data['optionAdmin'] = 'selected="selected"';
			break;
		case 'concierge':
			$data['optionConcierge'] = 'selected="selected"';
			break;
		case 'editor':
			$data['optionEditor'] = 'selected="selected"';
			break;
	}

	$data['checkActivo'] = '';
	if($user['is_active']){
		$data['checkActivo'] = 'checked="checked"';
	}

	echo makeTemplate('admin_usuario_detalle.html', $data, 'admin');
}

?>
<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;
$data = array();
$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;
$data['data1'] = $data1;

$data['NOMBRE'] = $name = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $email = $Admin->obtenerUsr('usr');
$data['mensaje'] = '';

$data['returnLink'] = WEB_URL.$data1;
$continuar = TRUE;

date_default_timezone_set('America/Mexico_City');
$fechaHoy = date('Y-m-d');

if(isset($_POST['sendArr'])) {
	$sendArr = $_POST['sendArr'];

	if(isset($sendArr['email']) && !empty($sendArr['email'])){
		$UUID = $data3;
		$createData = [];

		if(isset($sendArr['activo'])){
			$createData['is_active'] = TRUE;
		}else{
			$createData['is_active'] = FALSE;
		}

		if (!isset($sendArr['name']) || trim($sendArr['name']) === '') {
           	$data['mensaje'] .= "<div class='warning'>El Nombre es obligatorio.</div>";
           	$continuar = FALSE;
        }
		if (!isset($sendArr['email']) || empty($sendArr['email'])) {
           	$data['mensaje'] .= "<div class='warning'>Falta el correo electrónico.</div>";
           	$continuar = FALSE;
        }

        $createData['name'] = $sendArr['name'];
        $createData['email'] = strtolower($sendArr['email']);
        $createData['role'] = $sendArr['role'];
        
        if($continuar){
			if(!empty($sendArr['password']) && !empty($sendArr['confirmPassword'])){//SI HAY PASSWORD
			 	// Contraseñas
			    if (($sendArr['password']) !== ($sendArr['confirmPassword'] ?? '')) {
			        $data['mensaje'] .= "<div class='warning'>Las contraseñas no coinciden.</div>";
			    }else{
		    		$createData['password'] = $sendArr['password'];
					$res = $Admin->API->post('/api/auth/users/', $createData);

					if(isset($res['success']) && $res['success']){
						header('Location:'.WEB_URL.$data1);
						exit;
					}else{
						$data['mensaje'] .= '<div class="warning">Error: httpCode '.$res['httpCode'].' : '.($res['data']['message'] ?? '').'</div>';
					}
			    }
			}
		}
	}
}

$data['email'] = '';
$data['name'] = '';
$data['optionAdmin'] = 'selected="selected"';
$data['checkActivo'] = 'checked="checked"';

echo makeTemplate('admin_usuario_new.html', $data, 'admin');
?>
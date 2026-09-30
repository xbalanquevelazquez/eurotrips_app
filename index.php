<?php
session_start();

define('VIEWABLE',TRUE);


$config_file = "cnf/cnfg.app.php";
if(file($config_file)){
	include_once($config_file);
}else{
	die("No se encontró el archivo de config");
}


$includeHeader = TRUE;
$paginaDespliegue = "home.php";
$tituloDePagina = 'Inicio';
$mostrarMenu = TRUE;
$modulo = 'adm';

if(!isset($_SESSION['errorMsg'])){
	$_SESSION['errorMsg'] = '';
}

if(PRIVADO){//Son paginas privadas = tienen LOGIN
	ob_start();
	//:::::::::::::NO HAY SESSION:::::::::::::::::::::
	if(!$Admin->comprobarSesion()){
		if(isset($_POST['cmp']) && is_array($_POST['cmp']) && !empty($_POST['cmp'])){//vienen datos de form
			
			$tokenTurnstile = $_POST['cf-turnstile-response'] ?? '';
			$validacion = validarTokenTurnstile($tokenTurnstile);
			if($validacion['success']){

				$email = strtolower($_POST['cmp']['usrlogin']);

				if($LoginAttempts->estaBloqueado($email)){//ESTA BLOQUEADO
					#echo "BLOQUEADO<br >";
					$userData = $LoginAttempts->obtenerDatos($email);
					$segundos = $LoginAttempts->obtenerTiempoRestante($userData['locked_until']);
					$minutos = ceil($segundos/60);	
					$_SESSION['errorMsg'] = "<div class='alerta roja pm5'>".__('Su cuenta estará bloqueada')." $minutos ".__('mins')."</div>";

					if($data1 != '' && $data1 != 'LOGIN'){//ES OTRA PAGINA REGRESAR AL LOGIN
						header("Location: ".WEB_URL);
					}
					 
				}else{//NO BLOQUEADO
					#echo "NO BLOQUEADO<br >";
					$comprobarUsr = $Admin->comprobarUsuario($email,$_POST['cmp']['pswlogin']);
					if($comprobarUsr === TRUE){
						#echo "usr comprobado<br>";
						$LoginAttempts->reiniciarIntentos($email);
						if(!isset($data1) || $data1=='' || $data1='LOGIN') { $data1 = $Admin->obtenerUsr('firstSecc'); } 

						$extra = '';
						if(isset($_POST['code']) && $_POST['code']!=''){
							$extra = '/code='.$_POST['code'];
						}
						header("Location: ".WEB_URL.$data1);//.$extra
					}else{
						#print_pre($comprobarUsr);
						#die('x');
						if(isset($comprobarUsr['data']['error']) && $comprobarUsr['data']['error']=='API_UNAVAILABLE'){
							$_SESSION['errorMsg'] = '<div class="alerta roja pm5">El servicio no está disponible temporalmente.</div>';
							if($data1 != '' && $data1 != 'LOGIN'){//ES OTRA PAGINA REGRESAR AL LOGIN
								header("Location: ".WEB_URL);
							}
						}else{
							$LoginAttempts->registrarIntentoFallido($_POST['cmp']['usrlogin']);

							$_SESSION['errorMsg'] = '<div class="alerta roja pm5">'.__('El usuario o contraseña no son correctos.').'</div>';
							#$data1='LOGIN';
							if($data1 != '' && $data1 != 'LOGIN'){//ES OTRA PAGINA REGRESAR AL LOGIN
								header("Location: ".WEB_URL);
							}
						}
					}
				}
			}else{
				$_SESSION['errorMsg'] = $validacion['error'];
				$data1='LOGIN';
			}
		//:::::::::::::NO HAY SESSION pero es otra página que no requiere sesion abierta:::::::::::::::::::::
		}else if($data1 == 'REGISTRO' || $data1 == 'RECUPERACION' || $data1 == 'RESET_PASSWORD'){
			//NO CAMBIO LA PAGINA POR EL LOGIN
			$index = FALSE;
			$modulo = 'page';
		}else{//NO HAY SESSION NI DATOS DE LOGIN
			$data1='LOGIN';
			#header('Location:'.WEB_URL);
		}
	//:::::::::::::HAY SESSION:::::::::::::::::::::
	} else {
		if($data1=='' || $data1=='LOGIN' || $data1=='RECUPERACION' || $data1=='index') {
			$data1 = $Admin->obtenerUsr('firstSecc');
		}else if($data1 == 'VISOR'){
		//PASA
		}else{
			$current_usr = $Admin->obtenerUsr('usr');
			$current_usr_id = $Admin->obtenerUsr('user_id');
			$permisos = $Admin->permisosUsuario();
		}

		$isAdmin = $Admin->esAdmin();
		$modulo = ($isAdmin)?'adm':'page';
		if(!file_exists($modulo.'.'.$data1.'.php')){
			$data1 = '404';
			$tituloDePagina = 'Error';
		}
	}

	if($data1 == 'LOGIN' || $data1 == 'SALIR' || $data1 == 'EXCEL' || $data1 == 'PDF' || $data1 == 'VISOR' || $data1 == 'REGISTRO' || $data1 == 'RECUPERACION' || $data1 == 'RESET_PASSWORD' || $data1 == 'NOTIFICATIONS'){
		$includeHeader = FALSE;
	}else{
		$seccionesPorAcronimo = $Admin->obtenerUsr('seccionesPorAcronimo');
		if($data1 !== '404'){
			$currentSecc = strtoupper($data1);
			if(isset($seccionesPorAcronimo[$currentSecc])){
				$tituloDePagina = $seccionesPorAcronimo[$currentSecc];
			}else{
				$tituloDePagina = '...';
			}
		}
	}

	//CARGA PAGINA
	if($includeHeader){	include_once("header.php"); }

	include($modulo.'.'.$data1.'.php');

	if($includeHeader){	include_once("footer.php"); }

	ob_end_flush();
//PÚBLICO
}else{
	ob_start();
	if($data1 != 'HOME'){
		$page = "page.{$data1}.php";
		if(file_exists(APP_PATH.$page)){
			$includeHeader = TRUE;
		}else{
			$includeHeader = FALSE;
			$page = "adm.404.php";
		}
	}else{//acceso sin funcion a ejecutar
		$includeHeader = TRUE;
		$page =  "page.{$data1}.php";
	}
	//CARGA PAGINA
	if($includeHeader){
		include_once("header.php");
	}

	include_once($page);

	if($includeHeader){
		include_once("footer.php");
	}
	ob_end_flush();
}
?>
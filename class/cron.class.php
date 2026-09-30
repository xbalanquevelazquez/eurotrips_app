<?php
/**
 * 
 */
class Cronjob
{
	var $Admin 			= '';
	var $conexion 			= '';
	var $registro			= '';
	var $mailsender			= '';
	var $prefijo 			= '';
	var $numPerfil 			= 2;
	var $setBandera			= TRUE;
	var $error 				= '';
	var $debug 				= TRUE;
	
	function __construct($admin,$registro,$mailsender)
	{
		self::setAdmin($admin);
		self::setConexion($admin->conexion);
		self::setRegistro($registro);
		self::setMailsender($mailsender);
		$this->prefijo = PREFIJO;
	}
	function setAdmin($obj){
		$this->myAdmin = $obj;
	}
	function setConexion($obj){
		$this->conexion = $obj;
	}
	function setRegistro($obj){
		$this->registro = $obj;
	}
	function setMailsender($obj){
		$this->mailsender = $obj;
	}
	function exec(){
		if($this->debug) echo "[DEBUG MODE ON] <br><hr>";
		self::verificarRegistrosSinComprobantePago_48hrs();
		self::verificarRegistrosEntregados_14diasDespues();
		if($this->error != ''){
			return $this->error;
		}else{
			return 'OK';
		}
	}
	function setNumPerfilParaEnvioCorreos($num){
		$this->numPerfil = $num;
	}
	function verificarRegistrosSinComprobantePago_48hrs(){
		$now = date("Y-m-d H:i:s");
		//OBTIENE TODOS LOS REGISTROS ESTATUS 3 (en crs_registros) y con fecha de inicio de estatus mayor a 48 hrs y que no tengan bandera -no se haya enviado aviso-
		$query = "SELECT nip,fid_registro,nombre,apellido_paterno,estatus_registro,fid_estatus,fecha_inicio_estatus,texto,bandera,fecha_bandera,CURRENT_TIMESTAMP,(fecha_inicio_estatus + interval 2 day) as mas48hrs, CURRENT_TIMESTAMP > (fecha_inicio_estatus + interval 2 day) as compare FROM {$this->prefijo}registros LEFT JOIN {$this->prefijo}estatus ON fid_estatus=estatus_registro AND fid_registro=nip WHERE estatus_registro=3 AND CURRENT_TIMESTAMP > (fecha_inicio_estatus + interval 2 day)>0 AND bandera=0 ORDER BY nip ASC";
		
		if($this->debug) echo "$query <br><hr>";
		$registros = $this->conexion->fetch($this->conexion->query($query));
		$buffer = '';
		$total = count($registros);
		if($total>0){
			$buffer .= "Total de registros: ".count($registros)."<br />";
			$buffer .= "<table><tr><th>FOLIO</th><th>Horas a partir de que se envió correo</th></tr>";
			foreach ($registros as $reg) {
				$buffer .= "<tr><td>{$reg['nip']}</td><td>".$this->horasDiferencia($reg['fecha_inicio_estatus'],$now,48)." hrs.</td></tr>";
				if($this->setBandera){
					$update = array("bandera"=>1,"fecha_bandera"=>$now);
					$this->conexion->update($this->prefijo.'estatus',$update," WHERE fid_registro={$reg['nip']} AND fid_estatus=3",'HTML');
				}
			}
			$buffer .= "</table>";

			$arrCorreos = self::obtenerCorreosElectronicos();
			if(count($arrCorreos)>0){
				$dataMail = array(
						'sender_mail'=>SENDER_MAIL,
						'sender_name'=>SENDER_MAIL_NAME,
						'destinatarios' => $arrCorreos,
						'isHTML'=>TRUE,
						'titulo'=>'Alertas',
						'mensaje'=>'<style>table{border-collapse:collapse;}table td{border:1px solid #000;}</style><p>Ingrese al <a href="'.WEB_URL.'"">sistema</a> para dar seguimiento a los siguientes registros (la cliente debió haber terminado de llenar la dirección de envío, consentimiento informado y comprobante de pago en el link que se le envió por correo electrónico):</p>'.$buffer.'',
						'alt_mensaje'=>''
					);
				if($this->debug) $this->mailsender->PHPMailer->SMTPDebug = 2;
				if(!$this->mailsender->send_mail($dataMail)){ 
					$this->error .= 'Error de envío:'.$this->mailsender->error;
				}
			}else{
				$this->error .= "No hay correos para enviar los mensajes. Perfil utilizado: ".$this->numPerfil;
			}

		}
		return $buffer;
	}
	function verificarRegistrosEntregados_14diasDespues(){
		$now = date("Y-m-d H:i:s");
		//OBTIENE TODOS LOS REGISTROS ESTATUS 7 (en crs_registros) y con fecha de fin de estatus mayor despues de 14 días y que no tengan bandera -no se haya enviado aviso-
		$query = "SELECT nip,fid_registro,nombre,apellido_paterno,estatus_registro,fid_estatus,fecha_inicio_estatus,texto,bandera,fecha_bandera,CURRENT_TIMESTAMP,(fecha_inicio_estatus + interval 14 day) as mas14dias, CURRENT_TIMESTAMP > (fecha_inicio_estatus + interval 14 day) as compare FROM {$this->prefijo}registros LEFT JOIN {$this->prefijo}estatus ON fid_estatus=estatus_registro AND fid_registro=nip WHERE estatus_registro=7 AND CURRENT_TIMESTAMP > (fecha_inicio_estatus + interval 14 day)>0 AND bandera=0 ORDER BY nip ASC";
				
		if($this->debug) echo "$query <br><hr>";
		$registros = $this->conexion->fetch($this->conexion->query($query));
		$buffer = '';
		$total = count($registros);
		if($total>0){
			$buffer .= "Total de registros: ".count($registros)."<br />";
			$buffer .= "<table><tr><th>FOLIO</th><th>Días a partir de que se registro la entrega del paquete</th></tr>";
			foreach ($registros as $reg) {
				$buffer .= "<tr><td>{$reg['nip']}</td><td>".$this->diasDiferencia($reg['fecha_inicio_estatus'],$now,48)." días.</td></tr>";
				if($this->setBandera){
					$update = array("bandera"=>1,"fecha_bandera"=>$now);
					$this->conexion->update($this->prefijo.'estatus',$update," WHERE fid_registro={$reg['nip']} AND fid_estatus=7",'HTML');
				}
			}
			$buffer .= "</table>";

			$arrCorreos = self::obtenerCorreosElectronicos();
			if(count($arrCorreos)>0){
				$dataMail = array(
						'sender_mail'=>SENDER_MAIL,
						'sender_name'=>SENDER_MAIL_NAME,
						'destinatarios' => $arrCorreos,
						'isHTML'=>TRUE,
						'titulo'=>'Alertas',
						'mensaje'=>'<style>table{border-collapse:collapse;}table td{border:1px solid #000;}</style><p>Ingrese al <a href="'.WEB_URL.'"">sistema</a> para dar seguimiento a los siguientes registros (la cliente debió haber recibido el envío hace 14 días, se debe dar seguimiento al resultado):</p>'.$buffer.'',
						'alt_mensaje'=>''
					);
				if($this->debug) $this->mailsender->PHPMailer->SMTPDebug = 2;
				if(!$this->mailsender->send_mail($dataMail)){ 
					$this->error .= 'Error de envío:'.$this->mailsender->error;
				}
			}else{
				$this->error .= "No hay correos para enviar los mensajes. Perfil utilizado: ".$this->numPerfil;
			}

		}
		return $buffer;
	}	
	function horasDiferencia($primeraFecha,$segundaFecha){
		$fechaA = strtotime($primeraFecha);
		$fechaB = strtotime($segundaFecha);
		$buffer = floor((($fechaB-$fechaA)/60/60));//resultado en horas
		return $buffer;
	}
	function diasDiferencia($primeraFecha,$segundaFecha){
		$fechaA = strtotime($primeraFecha);
		$fechaB = strtotime($segundaFecha);
		$buffer = floor((($fechaB-$fechaA)/60/60/24));//resultado en dias
		return $buffer;
	}
	function obtenerCorreosElectronicos(){
		$query = "SELECT usr_correo from {$this->prefijo}usuarios WHERE fid_perfil={$this->numPerfil} and usr_activo=1";
		$resultados = array();
		$correos = $this->conexion->fetch($this->conexion->query($query));
		foreach($correos as $correo){
			$resultados[] = $correo['usr_correo'];
		}
		return $resultados;
	}
}
?>
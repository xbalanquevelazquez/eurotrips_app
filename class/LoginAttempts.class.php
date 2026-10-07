<?php
class LoginAttempts
{
	var $Admin;
	var $conn;
	var $table = 'login_attempts';

	function __construct($Admin,$conn){
		$this->Admin = $Admin;
		$this->conn = $conn;
	}

	function obtenerDatos($email){
		$userData = [];
		#echo "SELECT * FROM {$this->table} WHERE email='$email'";
		$email_escaped = $this->conn->real_escape_string($email);
		$resultado = $this->conn->query("SELECT * FROM {$this->table} WHERE email='$email_escaped'");
		#print_pre($resultado);
		$num = $this->conn->num_rows($resultado);

		#echo "num_rows: ".$num;
		if($num > 0){
			$userData = $this->conn->fetch($resultado);
			$userData = $userData[0];
		}
		return $userData;
	}

	function estaBloqueado($email){
		$userData = $this->obtenerDatos($email);
		#print_pre($userData);
		if(count($userData) > 0){//existe registro de attempts
			$failedAttempts = $userData['failed_attempts'];
			if($failedAttempts >= LOGIN_MAX_ATTEMPTS){
				$tiempoRestante = $this->obtenerTiempoRestante($userData['locked_until']);
				if($tiempoRestante > 0){//AUN BLOQUEADO
					return TRUE;
				}else{
					$this->reiniciarIntentos($email);
					return FALSE;
				}
			}else{
				return FALSE;
			}
		}else{//no existe registro de attempts
			return FALSE;
		}
	}

	function registrarIntentoFallido($email){
		$userData = $this->obtenerDatos($email);
		$failedAttempts = 1;
		if(count($userData) > 0){//existe registro de attempts
			$failedAttempts = $userData['failed_attempts'] + 1;
		}else{//no existe registro de attempts

		}
		$locked_until = date('Y-m-d H:i:s', strtotime('+'.LOGIN_LOCK_MINUTES.' minutes'));
		$last_attempt = date('Y-m-d H:i:s');
		$datos = [
			"email" => "$email",
			"failed_attempts" => "$failedAttempts",
			"locked_until" => $locked_until,
			"last_attempt" => $last_attempt,
			"ip_address" => $_SERVER['REMOTE_ADDR']
		];
		$this->conn->replace($this->table,$datos);
		
	}

	function reiniciarIntentos($email){
		$failedAttempts = 0;
		$last_attempt = date('Y-m-d H:i:s');
		$datos = [
			"email" => "$email",
			"failed_attempts" => "$failedAttempts",
			"locked_until" => NULL,
			"last_attempt" => $last_attempt,
			"ip_address" => $_SERVER['REMOTE_ADDR']
		];
		$this->conn->replace($this->table,$datos);
	}

	function obtenerTiempoRestante($locked_until){		
		return $segundos = strtotime($locked_until) - time();
	}

}
?>
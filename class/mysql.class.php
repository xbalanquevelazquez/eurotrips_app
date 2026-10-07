<?php
class MySQL{
	var $host			= 'localhost';
	var $usr			= 'root';
	var $psw			= '';
	var $dbname			= '';
	var $mysqli				= false;
	var $resultado		= '';
	var $resultadoData	= '';
	var $error			= '';
	var $debug			= false;
	var $columnas		= array();
	var $datos			= array();
	var $numcols		= 0;
	var $numfilas		= 0;
	var $lastQuery 		= '';
	
	function __construct($host='',$usr='',$psw='',$dbname=''){
		if(!$this->conectar($host,$usr,$psw,$dbname)){
			die($this->error);
		}
	}
	function conectar($host='',$usr='',$psw='',$dbname=''){
		#asignar los datos de la función, o los default de la clase.
		$host 	= empty($host)	?$this->host	:$host;
		$usr 	= empty($usr)	?$this->usr		:$usr;
		$psw 	= empty($psw)	?$this->psw		:$psw;
		$dbname	= empty($dbname)?$this->dbname	:$dbname;

		$this->host		= $host;
		$this->usr		= $usr;
		$this->psw		= $psw;
		$this->dbname	= $dbname;
		#verificar que se asigne un usuario.
		if($usr == '') { $this->error = 'Debe indicar un usuario de conexión'; return false; }
		if($dbname == '') { $this->error = 'Debe indicar la BD'; return false; }
		#realizo la conexión, que PHP no despliegue error, se imprimirá en caso de que suceda.
		$this->mysqli = new mysqli($host,$usr,$psw,$dbname);
		if($this->mysqli->connect_errno){
			$this->error="[Error al conectar BD:]".$this->mysqli->connect_error;
			#si está activado el debug, mostrar error
			if($this->debug) echo $this->error;
            return false;
		}else{
			return $this->mysqli;
		}
	}
	function query($sql='',$debug=FALSE){
		$this->set_charset();
		$this->lastQuery = $sql;
		
		if($this->mysqli){
			if($sql==''){
				$this->error = 'No especificó el sql a ejecutar';
				return false;
			}else{
				/*
				$this->resultado = $this->mysqli->query($sql);
				return $this->resultado;
				*/
				
				if(!$this->resultado = $this->mysqli->query($sql)){
					#echo mysqli_real_escape_string($sql);
					#echo "[Error en BD:".$this->dbname."]".$sql;
					error_log("[Error en BD:".$this->dbname."]".$sql);
					$this->error = "Error en la consulta";
					#$this->error=mysqli_errno($this->mysqli).": ".mysqli_error($this->mysqli); 
					#si está activado el debug, mostrar error
					if($this->debug) die("[Error en BD | ".$this->dbname."] ".$this->error);
					return false;
				}else{
					return $this->resultado;
				}
			}
		}else{
			die("Falta el objeto de conexión.");
		}
	}
	function set_charset($charset = 'utf8'){
		$this->mysqli->set_charset($charset);
	}
	function num_rows($resultado){
		return $resultado->num_rows;
	}
	function num_fields($resultado){
		return $resultado->num_fields;
	}
	function last_id(){
		return $this->mysqli->insert_id;
	}
	function fetch($resultado,$opcion = 'ASSOC'){
		$this->set_charset();
		#if(empty($resultado)) die("El identificador no tiene datos. (".$this->lastQuery.")");
		if(empty($resultado)){
		    error_log("Consulta sin resultados: ".$this->lastQuery);
		    die("Error en la consulta");
		}
		$this->numfilas = $resultado->num_rows;
		$this->numcols 	= $resultado->field_count;
		$arr = array();
		$arrKeys = array();
		$defCampos = $resultado->fetch_fields();
		foreach ($defCampos as $campo){
			$arrKeys[] = $campo->name;
		}

		switch($opcion){
			case 'NUM':#guarda en array num
				while($result = mysqli_fetch_array($resultado,mysqli_NUM)){
					$arr[] = $result;
				}
				break;
			case 'ARRAY':#guarda tanto key como num
				while($result = mysqli_fetch_array($resultado)){
					$arr[] = $result;
				}
				break;
			case 'ASSOC':#guarda en assoc
			default:
				while($result = mysqli_fetch_assoc($resultado)){
					$arr[] = $result;
				}
				break;
		}
		$this->columnas	= $arrKeys;
		$this->datos	= $arr;
		$this->free();#se limpia la memoria de la consulta
		return $arr;
	}
	function insert($tabla,$datos,$type='HTML',$debug=FALSE){
		$llaves=NULL;
		$valores=NULL;
		$this->set_charset();
		foreach($datos as $key => $value){
			@$llaves .= (isset($llaves)?',':'').$key;
			@$valores .= (isset($valores)?',':'')."'".(strtoupper($type)=='HTML'?htmlentities($value,ENT_QUOTES,'ISO-8859-1'):addslashes($value))."'";
		}

		return $this->query("INSERT INTO $tabla ($llaves) VALUES ($valores)");
	}
	function replace($tabla,$datos,$type='HTML',$debug=FALSE){
		$llaves=NULL;
		$valores=NULL;
		$this->set_charset();
		foreach($datos as $key => $value){
			@$llaves .= (isset($llaves)?',':'').$key;
			 if($value === NULL){
	            $valorSQL = 'NULL';
	        }else{
	            $valor = strtoupper($type) == 'HTML'
	                ? htmlentities($value, ENT_QUOTES, 'ISO-8859-1')
	                : addslashes($value);

	            $valorSQL = "'" . $valor . "'";
        	}
			@$valores .= (isset($valores)?',':'').$valorSQL;
		}

		return $this->query("REPLACE INTO $tabla ($llaves) VALUES ($valores)");
	}
	function update($tabla,$datos,$condicion='',$type='HTML',$debug=FALSE){
		#echo "$tabla,$datos,$condicion='',$type='HTML'";
		/*echo "<pre>";
		print_r($datos);
		echo "</pre>";*/
		$this->set_charset();
		if(!is_array($datos)) die('falta un array de datos adecuado');
		foreach($datos as $key => $value){
			if($value === NULL){
				@$valores .= (isset($valores)?',':'')."$key=NULL";
			}else{
				@$valores .= (isset($valores)?',':'')."$key='".(strtoupper($type)=='HTML'?htmlentities($value,ENT_QUOTES,'ISO-8859-1'):addslashes($value))."'";
			}
		}
		#echo "UPDATE $tabla SET $valores $condicion";
		return $this->query("UPDATE $tabla SET $valores $condicion",$debug);
	}
	function delete($tabla,$condicion){
		return $this->query("DELETE FROM $tabla WHERE $condicion");
	}
	function real_escape_string($string){
	    return $this->mysqli->real_escape_string($string);
	}
	function free(){
		return mysqli_free_result($this->resultado);
	}
	function close(){
		return mysqli_close($this->mysqli);
	}
}
?>
<?php
class Registro{
	var $registro 			= array();
	var $Admin 			= '';
	var $conexion 			= '';
	var $mailsender			= '';
	var $alerta 			= '';
	var $alertaPlain		= '';
	var $diasTranscurridos 	= '';
	var $infoFaltante 		= 0;
	var $detalleFaltante 	= '';
	var $avisoInfo			= '';
	var $estadosRestringidos = array();
	var $municipiosRestringidos = array();
	var	$nip 				= 0;
	var	$nombre 			= '';
	var	$apellido_paterno 	= '';
	var	$apellido_materno	= '';
	var	$cp 				= '';
	var	$fid_entidad		= '99';
	var $nombre_entidad 	= '';
	var	$fid_municipio		= '999';
	var $nombre_municipio	= '';
	var	$fid_localidad		= '0';
	var $nombre_localidad	= '';
	var	$fecha_nacimiento	= '';
	var	$fum				= '';
	var	$correo				= '';
	var $lada 				= '';
	var $telefono			= '';
	var $estatus_registro	= '';
	var $proposito			= 0;
	var $resultado			= 0;
	var $edad 				= '';
	var $puntosNSE			= 0;
	var $code				= '';
	var $fid_acompanante	= 0;
	var $es_referido		= 0;
	var $tipo_registro		= '';
	var $solicita_subsidio	= 0;
	var $motivo_subsidio	= '';
	var $subsidio_autorizado = FALSE;
	var $num_kits			= 0;
	var $tipo_kits			= '';
	var $nombre_red			= '';
	var $costo 				= 0;
	var $envio_nombre		= '';
	var $envio_calle		= '';
	var $envio_num_exterior	= '';
	var $envio_num_interior	= '';
	var $envio_cp			= '';
	var $envio_fid_entidad	= '99';
	var $envio_nombre_entidad = '';
	var $envio_fid_municipio	= '999';
	var $envio_fid_localidad	= '0';
	var $nota_envio			= '';
	var $comprobante_pago	= '';
	var $fecha_pago	= '';
	var $proveedor_logistico	= '';
	var $num_guia			= '';
	var $fecha_envio		= '';
	var $fecha_estimada_entrega	= '';
	var $fecha_ultima_actualizacion		= '';
	var $respaldo_envio		= '';
	var $respaldo_entrega	= '';
	var $fecha_entrega		= '';
	var $observaciones		= '';
	var $current_error = '';

	var $acompanante_data = array();
	function Registro(){
	}
	function setRegistro($data){
		$this->resetValidacion();
		$this->alerta = '';
		$this->diasTranscurridos = '';
		$this->registro = $data;
		$this->getEstadosRestringidos();
		$this->getMunicipiosRestringidos();

		$this->nip 				= $data['nip'];
		$this->tipo_registro	= $data['tipo_registro'];
		
		$this->nombre 			= $data['nombre'];
		$this->apellido_paterno = $data['apellido_paterno'];
		$this->apellido_materno	= $data['apellido_materno'];
		$this->cp 				= $data['cp'];
		$this->fid_entidad		= $data['fid_entidad'];
		$this->fid_municipio	= $data['fid_municipio'];
		$this->fid_localidad	= $data['fid_localidad'];
		$this->nombre_entidad	= $data['nombre_entidad'];
		$this->nombre_municipio	= $data['nombre_municipio'];
		$this->nombre_localidad	= $data['nombre_localidad'];
		$this->fecha_nacimiento	= $data['fecha_nacimiento'];

		$this->fum				= $data['fum'];
		$this->correo			= $data['correo'];
		$this->lada				= $data['lada'];
		$this->telefono			= $data['telefono'];
		$this->aviso_contacto	= $data['aviso_contacto'];
		$this->estatus_registro	= $data['estatus_registro'];
		$this->fid_acompanante	= $data['fid_acompanante'];
		$this->solicita_subsidio	= $data['solicita_subsidio'];
		$this->motivo_subsidio		= $data['motivo_subsidio'];
		$this->subsidio_autorizado	= $data['subsidio_autorizado'];
		$this->nota_subsidio	= $data['nota_subsidio'];

		$this->num_kits			= $data['num_kits'];
		$this->tipo_kits		= $data['tipo_kits'];
		$this->es_referido		= $data['es_referido'];
		$this->nombre_red		= $data['nombre_red'];
		$this->costo 			= $data['costo'];

		$this->envio_nombre 		= $data['envio_nombre'];
		$this->envio_calle 			= $data['envio_calle'];
		$this->envio_num_exterior 	= $data['envio_num_exterior'];
		$this->envio_num_interior 	= $data['envio_num_interior'];
		$this->envio_cp 			= $data['envio_cp'];
		$this->envio_fid_entidad 	= $data['envio_fid_entidad'];
		$this->envio_fid_municipio 	= $data['envio_fid_municipio'];
		$this->envio_fid_localidad 	= $data['envio_fid_localidad'];
		$this->nota_envio 			= $data['nota_envio'];
		$this->envio_nombre_entidad		= $data['envio_nombre_entidad'];

		$this->comprobante_pago		= $data['comprobante_pago'];
		$this->fecha_pago			= $data['fecha_pago'];
		$this->proveedor_logistico	= $data['proveedor_logistico'];
		$this->num_guia				= $data['num_guia'];
		$this->fecha_envio			= $data['fecha_envio'];

		$this->fecha_estimada_entrega		= $data['fecha_estimada_entrega'];
		$this->fecha_ultima_actualizacion	= $data['fecha_ultima_actualizacion'];
		$this->respaldo_envio				= $data['respaldo_envio'];
		$this->respaldo_entrega				= $data['respaldo_entrega'];
		$this->fecha_entrega				= $data['fecha_entrega'];
		$this->recibido_por 				= $data['recibido_por'];

		$this->code				= $data['code'];
		$this->observaciones	= $data['observaciones'];


		if(($this->tipo_registro == 'ES_ACOMPAÑANTE' || $this->tipo_registro == 'ES_REFERIDA') && $this->fid_acompanante!=0){//si se eligió acompañante
			$prefijo = PREFIJO;
			$queryAcompanante = "SELECT *,
									(SELECT nombre_entidad FROM {$prefijo}cat_entidades_federativas WHERE fid_entidad_acompanante=kid_entidad) AS nombre_entidad, 
									(SELECT nombre_municipio FROM {$prefijo}cat_municipios WHERE {$prefijo}cat_municipios.fid_entidad=fid_entidad_acompanante AND fid_municipio_acompanante=cat_key) AS nombre_municipio,
									(SELECT CONCAT(tipo_asentamiento,' ',asentamiento) AS nombre_localidad FROM {$prefijo}cat_cp WHERE fid_localidad_acompanante=kid_cp) AS nombre_localidad
									 FROM {$prefijo}acompanantes 
									 WHERE kid_acompanante={$this->fid_acompanante}";

			
			$laAcompanante = $this->myAdmin->conexion->fetch($this->myAdmin->conexion->query($queryAcompanante));
			$laAcompanante = $laAcompanante[0];
			$this->acompanante_data = $laAcompanante;
			$this->nombre = $laAcompanante['alias'];
			$this->apellido_paterno = '';
		}else{
			$this->acompanante_data = array('nombre_acompanante'=>'',
											'nombre_entidad'=>'',
											);
		}

		if($this->nip == 11) {
				/*echo "<pre>";
				print_r($this->fid_acompanante);
				print_r($this->acompanante_data);*/
			}
		if($this->fum != ''){
			$diasTranscurridosCalc = calculaDiasTranscurridos($this->fum);

			$diasFUM = $diasTranscurridosCalc['d'];
			$semanasFUM = $diasTranscurridosCalc['w'];
			$this->diasTranscurridos = "Días transcurridos $diasFUM <br/>Semanas $semanasFUM";
			$this->registro['diasTranscurridos'] = "Días transcurridos $diasFUM <br/>Semanas $semanasFUM";
			/*if($diasTranscurridosCalc['d'] > 56){
				$this->alerta .= "<span class=restriccion>Han transcurrido más de 56 días desde la FUM</span>";
			}*/
		}

		if($this->fecha_nacimiento != ''){
			$aniosCalc = calculaEdad($this->fecha_nacimiento);
			$this->registro['edad'] = "$aniosCalc años <br/>";
		}else{
			$this->registro['edad'] = '';
		}

	}
	function setAdmin($admin){
		$this->myAdmin = $admin;
	}
	function setConexion($conn){
		$this->conexion = $conn;
	}
	function resetValidacion(){
		$this->infoFaltante = 0;
		$this->alerta = '';
		$this->alertaPlain = '';
		$this->avisoInfo = '';
	}
	function validarDatos(){
		if($this->nombre == ''){ $this->infoFaltante++; $this->addDetalle('Nombre'); }
		if($this->apellido_paterno == ''){ $this->infoFaltante++; $this->addDetalle('Apellido paterno'); }
		#if($apellido_materno == ''){ $this->infoFaltante++; }
		//if($cp == '' || strlen($cp)<=4){ $this->infoFaltante++; }
		if($this->fid_entidad == '99'){ $this->infoFaltante++; $this->addDetalle('Entidad'); }
		if($this->fid_municipio == '999'){ $this->infoFaltante++; $this->addDetalle('Municipio'); }
		if($this->fid_localidad == '0'){ $this->infoFaltante++; $this->addDetalle('Localidad'); }
		if($this->tipo_registro == 'ES_REFERIDA' || $this->tipo_registro == 'ES_USUARIA_FINAL'){//VALIDAR ESTOS CAMPOS SI NO ES REFERIDA
			//if($fecha_nacimiento == ''){ $this->infoFaltante++; }
			if($this->fum == ''){ $this->infoFaltante++; $this->addDetalle('FUM'); }	
		}
		if($this->correo == ''){ $this->infoFaltante++; $this->addDetalle('Correo'); }
		if($this->lada == ''){ $this->infoFaltante++; $this->addDetalle('Lada'); }
		if($this->telefono == ''){ $this->infoFaltante++; $this->addDetalle('Teléfono'); }

		//VALIDA QUE NO FALTE INFORMACIÓN
		//VALIDAR ESTADO Y MUNICIPIO RESTRINGIDO
		if($this->fid_entidad != '99'){
			if(in_array($this->fid_entidad, $this->estadosRestringidos)){
				$this->alerta .= "<span class=restriccion>Entidad Federativa con alerta</span>";
				$this->alertaPlain .= "Entidad Federativa con alerta";
			}
			if($this->fid_municipio != '999'){
				if(in_array($this->fid_entidad.'-'.$this->fid_municipio, $this->municipiosRestringidos)){
					$this->alerta .= "<span class=restriccion>Municipio con alerta</span><br/>";
					$this->alertaPlain .= "Municipio con alerta";
				}
			}
		}
		//VALIDAR DIAS TRANSCURRIDOS
		if($this->fum != ''){
			$diasTranscurridosCalc = calculaDiasTranscurridos($this->fum);

			$diasFUM = $diasTranscurridosCalc['d'];
			$semanasFUM = $diasTranscurridosCalc['w'];
			$this->diasTranscurridos = "Días transcurridos $diasFUM <br/>Semanas $semanasFUM";
			if($diasTranscurridosCalc['d'] > 56){
				$this->alerta .= "<span class=restriccion>Han transcurrido más de 56 días desde la FUM</span>";
			}
		}
		//CALCULA LA EDAD
		if($this->fecha_nacimiento != ''){
			$aniosCalc = calculaEdad($this->fecha_nacimiento);
			$this->edad = "$aniosCalc años <br/>";
		}
		return $this->alerta;
	}
	function validarDatosAcompanante(){
		if($this->fid_acompanante == 0){
			$this->alerta .= "<span class=restriccion>Indique la Acompañante</span><br/>";
		}
		return $this->alerta;
	}
	function validarDatosBase(){
		if($this->tipo_registro == ''){
			$this->alerta .="<span class=restriccion>Indique que tipo de registro es.</span>";
			$this->infoFaltante++;
			$this->addDetalle('Tipo de registro');
		}else{

			if($this->nombre_red == ''){ $this->infoFaltante++; $this->addDetalle('Red'); }

		}

		if($this->solicita_subsidio == 'SI'){
			if($this->motivo_subsidio == ''){
				$this->infoFaltante++; $this->addDetalle('Motivo del subsidio');
			}
		}


		if($this->infoFaltante > 0){
			$s = $this->infoFaltante>1?'s':'';
			$this->alerta .="<span class=restriccion>Faltan $this->infoFaltante dato$s que completar <span class='small'>($this->detalleFaltante)</span></span>";
			$this->alertaPlain .= "Faltan $this->infoFaltante dato$s que completar";
		}
		//VALIDAR COSTO
		if($this->costo == 0){
			$this->avisoInfo .= "<span class='restriccion bg-warning'>El costo es $0, verifique que sea correcto</span>";
		}

	}
	function getEstadosRestringidos(){
		$queryEstadosRestringidos = "SELECT * FROM ".PREFIJO."cat_entidades_federativas WHERE estatus_entidad=2";
		$resEstadosRestringidos = $this->conexion->fetch($this->conexion->query($queryEstadosRestringidos));
		$estadosRestringidos = array();
		foreach($resEstadosRestringidos as $estRes){
			$estadosRestringidos[] = $estRes['kid_entidad'];
		}
		$this->estadosRestringidos = $estadosRestringidos;
	}
	function getMunicipiosRestringidos(){
		$queryMunicipiosRestringidos = "SELECT * FROM ".PREFIJO."cat_municipios WHERE estatus_municipio=2";
		$resMunicipiosRestringidos = $this->conexion->fetch($this->conexion->query($queryMunicipiosRestringidos));
		$municipiosRestringidos = array();
		foreach($resMunicipiosRestringidos as $munRes){
			$municipiosRestringidos[] = $munRes['fid_entidad'].'-'.$munRes['cat_key'];
		}
		$this->municipiosRestringidos = $municipiosRestringidos;
	}
	function validarInteraccion(){
		$proposito = $this->proposito;
		$resultado = $this->resultado;
		//VALIDAR PROPOSITO Y RESULTADO
		if($proposito == 0){
			$this->alerta .= "<span class=restriccion>No ha seleccionado un propósito de la interacción</span>";
		}
		if($resultado == 0){
			$this->alerta .= "<span class=restriccion>No ha seleccionado un resultado de la interacción</span>";
		}
	}
		// Las funciones que utilizo con los NSE

	// Obtiene los datos del POST del formulario de arriba, cuantifica los puntos por respuesta y el resultado determina el NSE
	function setNSE($inputDatos = array('r1'=>0,'r2'=>0,'r3'=>0,'r4'=>0,'r5'=>0,'r6'=>0)){
		global $page;
		#self::getCall();
		$resp = 'nel';
		$datos = '';
		$info = 'No se pudo registrar el dato';
		$puntos = 0;
		$nse = 0;
		$q1 = array(
			array('n'=>1, 'val'=>0),
			array('n'=>2, 'val'=>0),
			array('n'=>3, 'val'=>10),
			array('n'=>4, 'val'=>22),
			array('n'=>5, 'val'=>23),
			array('n'=>6, 'val'=>31),
			array('n'=>7, 'val'=>35),
			array('n'=>8, 'val'=>43),
			array('n'=>9, 'val'=>59),
			array('n'=>10, 'val'=>73),
			array('n'=>11, 'val'=>101)
			);
		$q2 = array(
			array('n'=>1, 'val'=>0),
			array('n'=>2, 'val'=>24),
			array('n'=>3, 'val'=>47)
			);
		$q3 = array(
			array('n'=>1, 'val'=>0),
			array('n'=>2, 'val'=>18),
			array('n'=>3, 'val'=>37)
			);
		$q4 = array(
			array('n'=>1, 'val'=>0),
			array('n'=>2, 'val'=>31)
			);
		$q5 = array(
			array('n'=>1, 'val'=>0),
			array('n'=>2, 'val'=>15),
			array('n'=>3, 'val'=>31),
			array('n'=>4, 'val'=>46),
			array('n'=>5, 'val'=>61)
			);
		$q6 = array(
			array('n'=>1, 'val'=>0),
			array('n'=>2, 'val'=>6),
			array('n'=>3, 'val'=>12),
			array('n'=>4, 'val'=>17),
			array('n'=>5, 'val'=>23)
			);
		//print_r($inputDatos);
		$puntos += self::getNsePuntos($q1, $inputDatos['pregunta_1']);
		$puntos += self::getNsePuntos($q2, $inputDatos['pregunta_2']);
		$puntos += self::getNsePuntos($q3, $inputDatos['pregunta_3']);
		$puntos += self::getNsePuntos($q4, $inputDatos['pregunta_4']);
		$puntos += self::getNsePuntos($q5, $inputDatos['pregunta_5']);
		$puntos += self::getNsePuntos($q6, $inputDatos['pregunta_6']);
		if ($puntos >= 205){
			// 1 => A/B
			$nse = 1;
		} else if ($puntos >= 166 && $puntos <= 204){
			// 2 => C+
			$nse = 2;
		} else if ($puntos >= 136 && $puntos <= 165){
			// 3 => C
			$nse = 3;
		} else if ($puntos >= 112 && $puntos <= 135){
			// 4 => C-
			$nse = 4;
		} else if ($puntos >= 90 && $puntos <= 111){
			// 5 => D+
			$nse = 5;
		} else if ($puntos >= 48 && $puntos <= 89){
			// 6 => D
			$nse = 6;
		} else if ($puntos <= 47){
			// 7 => 
			$nse = 7;
		}
		$this->puntosNSE = $puntos;
		
		return $nse;
		}
	// Para simplificar el buscar el puntaje correspindente a cada pregunta, mejor hice esta función
	function getNsePuntos($lista, $valor){
		$puntos = 0;
		foreach ($lista as $item) {
			if ($valor == $item['n']){
				$puntos = $item['val'];
				break;
			}
		}
		return $puntos;
		}
	// Estos son los NSE que utilizamos, o el valor numérico (identificador) que le corresponde a cada uno dentro en la base de datos
	// Ej.: los níveles A y B tienen el mismo valor numérico o identificador
	function getNSEvalues(){
		return array(
			array('val'=>1, 'name'=>'A/B'),
			array('val'=>2, 'name'=>'C+'),
			array('val'=>3, 'name'=>'C'),
			array('val'=>4, 'name'=>'C-'),
			array('val'=>5, 'name'=>'D+'),
			array('val'=>6, 'name'=>'D'),
			array('val'=>7, 'name'=>'E')
		);
		}
	// Y esta la uso sólo para obtener rápido el NSE a partir de un valor númerico
	function getNSE($n, $default = ''){
		$nse = $default;
		$lista = self::getNSEvalues();
		foreach ($lista as $item) {
			if ($item['val'] == $n){
				$nse = $item['name'];
			}
		}

		return $nse;
		}
	function getNSEfromDB(){
		$query = "SELECT * FROM ".PREFIJO."subsidio WHERE fid_registro={$this->nip}";
		$respuestasNSE = $this->myAdmin->conexion->fetch($this->myAdmin->conexion->query($query));
		$respuestasNSE = $respuestasNSE[0];
		$data = array();
		$this->validaNSE($data = array(
			'pregunta_1'=>$respuestasNSE['pregunta_1'],
			'pregunta_2'=>$respuestasNSE['pregunta_2'],
			'pregunta_3'=>$respuestasNSE['pregunta_3'],
			'pregunta_4'=>$respuestasNSE['pregunta_4'],
			'pregunta_5'=>$respuestasNSE['pregunta_5'],
			'pregunta_6'=>$respuestasNSE['pregunta_6'])
		);
		return $data;
	}
	function validaNSE($datos = array('q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0,'q5'=>0,'q6'=>0)){
		$i = 1;
		$alerta = '';
		$nse = '';
		#print_r($datos);
		foreach ($datos as $dato => $value) {
			if(strpos($dato,'pregunta') === FALSE){

			}else{
				if($value == 0){
					$alerta .= "<span class=restriccion>Falta que complete la pregunta $i del NSE.</span>";
				}
				$i++;
			}
		}
		if($alerta == ''){
			$nse =  self::setNSE($datos);
		}

		return $datos = array('NSE'=>$nse,'alerta'=>$alerta);
	}
	function setNSElevels($nameOfMax){
		$value = 0;
		switch($nameOfMax){
			case 'A/B':
				$value = 500;
				break;
			case 'C+':
				$value = 204;
				break;
			case 'C':
				$value = 165;
				break;
			case 'C-':
				$value = 135;
				break;
			case 'D+':
				$value = 111;
				break;
			case 'D':
				$value = 89;
				break;
			case 'E':
				$value = 47;
				break;
			default:
				$value = 0;
				break;
		}
		return $value;
	}
	function aplicaSubsidio(){//regresa alerta
		$resultado = '';
		$maxPuntosPSubsidio = $this->setNSElevels($nameOfMax=NSE_MINIMO_SUBSIDIO);
		if($this->puntosNSE <= $maxPuntosPSubsidio){
			$resultado = '<span class="bg-success text-white setpadding5 redondear">Aplicable para subsidio</span>';
		}else{
			$resultado = '<span class="restriccion setpadding5">No aplicable para subsidio</span>';
		}
		return $resultado;
	}
	function esAplicableParaSubsidio(){//regresa booleano
		$resultado = '';
		$maxPuntosPSubsidio = $this->setNSElevels($nameOfMax=NSE_MINIMO_SUBSIDIO);
		if($this->puntosNSE <= $maxPuntosPSubsidio){
			$resultado = TRUE;
		}else{
			$resultado = FALSE;
		}
		return $resultado;
	}
	function cierraExpedienteMedico($nip, $correo, $deAcompanante = false){
		$data = array("success"=>false,"error"=>'');
		//Genera código
		$codigo = generarCodigo();
		//Cambia estatus
		$datos = array("estatus_registro"=>3,"code"=>$codigo);
		if($this->myAdmin->conexion->update(PREFIJO.'registros',$datos,$condicion=" WHERE nip='{$nip}'",'TEXT',FALSE)){
			if (!$deAcompanante){
				$this->cambiarEstatus($nip,2,3);
			}
			//Envía correo
			$sendMensaje = notificarClienta($nip, 1, $deAcompanante);
			$data["success"] 	= $sendMensaje['success'];
			$data["error"]		= $sendMensaje['error'];
		}else{
			$data["success"] 	= false;
			$data["error"]		= $this->myAdmin->conexion->error;
		}
		return $data;
	}
	function cierraPedidoAcompanante($nip, $correo){
		return $this->cierraExpedienteMedico($nip, $correo, true);
	}
	function avisoRechazoComprobante($razones){
		$data = array("success"=>false,"error"=>'');
		$enlace = EXTERNAL_WEB_URL."VALIDA/{$this->nip}/{$this->code}/";
		$dataMail = array(
						'sender_mail'=>SENDER_MAIL,
						'sender_name'=>SENDER_MAIL_NAME,
						'destinatarios' => array($this->correo),
						'isHTML'=>TRUE,
						'titulo'=>'Información importante',
						'mensaje'=>'<p>Su comprobante de pago fue rechazado por las siguientes razones:<br>'.$razones.'<br>Por favor vuelva a subir el comprobante con la corrección necesaria, debe hacerlo a través del siguiente enlace:</p><p><a href="'.$enlace.'">'.$enlace.'</a></p>',
						'alt_mensaje'=>''
					);
		#die("$razones | $enlace | {$this->correo}");
			if($this->mailsender->send_mail($dataMail)){ 
				$data["success"] 	= true;
				$data["error"]		= '';
			}else{ 
				$data["success"] 	= false;
				$data["error"]		= 'Error de envío:'.$this->mailsender->error;
			}
		return $data;
	}
	function getEstatus(){
		global $data1, $Admin, $prefijo;
		$estatus = $this->estatus_registro;
		//$nip = $this->nip;
		/*switch ($this->estatus_registro) {
			case '1':
				$estatus = "Se generó registro, no se han completado los datos básicos.";
				break;
			case '2':
				$estatus = "Se completaron los datos básicos, pendiente de ser llenado el expediente médico.";
				break;
			case '3':
				$msg = 1;
				//VERIFICA SI SE RECHAZÓ EL PAGO
				$qEstatusActual = "SELECT * FROM {$prefijo}estatus WHERE fid_registro=$nip AND fid_estatus=3 ORDER BY fecha_inicio_estatus DESC LIMIT 1";//Obtengo sólo el último y más reciente estatus 3
				$estatusActual = $Admin->conexion->fetch($Admin->conexion->query($qEstatusActual));
				// if(count($estatusActual)>0){
				// 	$estatusActual = $estatusActual[0];
				// 	#$estatus .= $estatusActual['fecha_inicio_estatus'];
				// 	$queryExisteEstatus4 = "SELECT * FROM {$prefijo}estatus WHERE fid_registro=$nip AND fid_estatus=4 AND fecha_termino_estatus='{$estatusActual['fecha_inicio_estatus']}'";
				// 	$existeRechazo = $Admin->conexion->fetch($Admin->conexion->query($qEstatusActual));
				// 	$existeRechazo = $existeRechazo[0];
				// 	// edi(' - ' . $existeRechazo['kid_estatus']);
				// }

				// if(isset($existeRechazo['kid_estatus'])){
				// 	$estatus = "<span class='bg-danger text-white setpadding5 redondear'>Se rechazó el comprobante de pago, se debe revisar el registro</span>";
				// 	$msg = 4;
				// } else{
					$estatus = "Se completó el expediente médico, pendientes de ser llenados los datos de envío, consentimiento informado y comprobante de pago.";
				// }
				if ($this->fid_acompanante > 0){//EXISTE ACOMPAÑANTE
					$estatus = "Pendientes de ser llenados los datos de envío y comprobante de pago.";
				}
				if ($data1 == 'LOG'){
					$msg = 1;
					$prefijo = PREFIJO;
					$nip = $this->nip;
					$condicion = " WHERE nip = $nip";
					$query = "SELECT * FROM {$prefijo}registros $condicion";
					$registros 	= $Admin->conexion->fetch($Admin->conexion->query($query));
					$registro = $registros[0];
					$codigo = $registro['code'];
					$enlace = EXTERNAL_WEB_URL."VALIDA/{$nip}/{$codigo}/";
					$urlLink = generaBoton($enlace, '<i class="fa fa-link"></i>', 'btn btn-primary', '', ' target="_blank"');
					if ($this->fid_acompanante > 0 && $this->es_referido == 0){
						$estatus .= ' ' . $urlLink;
					}
				}
				$urlSend = WEB_URL . 'webservice/acciones.php';
				$estatus .= ' <a href="' . $urlSend . '" class="enviar-correo-prueba btn btn-primary" id="' . $this->nip . '" msg="'.$msg.'"><i class="fa fa-at"></i></a>';
				break;
			case '4':
				$estatus = "Completados datos de envío, aceptado consentimiento informado y se subió un comprobante de pago, pendiente de ser validado el comprobante de pago.";
				if ($this->fid_acompanante > 0){
					$estatus = "Completados datos de envío, pendiente de ser validado el comprobante de pago.";
				}
				break;
			case '5':
				$estatus = "Validado el comprobante de pago, pendiente de ser procesado el envío.";
				break;
			case '6':
				$estatus = "En proceso de envío, pendiente de ser entregado.";
				break;
			case '7':
				$estatus = "Concluido, el envío ya fue entregado.";
				break;
			case '8':
				$estatus = "Cancelado.";
				break;
			case '9':
				$estatus = "Rechazado.";
				break;
			
			default:
				
				break;
		}*/
		return $estatus;
	}
	function cambiarEstatus($id_registro,$estatus_actual,$estatus_nuevo,$desc=''){
		global $Admin;
		if($this->nip == 0){
			self::getRegistroDeBD($id_registro);
		}
		$now = date("Y-m-d H:i:s");

		$datosEstatus = array();
		$datosEstatus['fid_registro'] = $id_registro;
		$datosEstatus['fid_estatus'] = $estatus_nuevo;
		$datosEstatus['fecha_cambio_estatus'] = $now;
		$id_usr_actual = $Admin->obtenerUsr('user_id');
		$datosEstatus['fid_usr'] = $id_usr_actual;
		if($desc != ''){
			$datosEstatus['texto'] = $desc;
		}
		if(!$this->conexion->insert(PREFIJO.'estatus',$datosEstatus,'TEXT',FALSE)){
			die("Error al generar registro de estatus. ID:$id_registro | $estatus_actual -> $estatus_nuevo");
		}
		
		$datosRegistro = array('estatus_registro'=>$estatus_nuevo);
		if($estatus_nuevo == 'SOLICITUD DE SUBSIDIO'){
			$codigo = generarCodigo();
			$this->code = $codigo;
			$datosRegistro['code'] = $codigo;
		}

		if(!$this->conexion->update(PREFIJO.'registros',$datosRegistro,"WHERE nip='$id_registro'",'TEXT')){
			die("Error al cambiar estatus en registro.");
		}

		//ENVIO DE MENSAJES AUTOMATICOS AL CAMBIAR ESTATUS -> cuando pasa a MED no puede ser automático, porque cuando se cambia el estatus aún no sabemos si estaba disponible el médico
		switch ($estatus_nuevo) {
			case 'SOLICITUD DE SUBSIDIO':
				//5 -> registro pasa a LOG concluido pasa a en envio -> 6  ||  concluido  -> 7

				self::enviarMensajeInterno($estatus_nuevo);

				break;

			case 'REGISTRO CERRADO':
				self::enviarMensajeInterno($estatus_nuevo);
		}

	}
	function getFechaRegistro(){
		$fecha = '--';
		$query = "SELECT * FROM ".PREFIJO."estatus WHERE fid_registro=".$this->nip." AND fid_estatus='REGISTRO ABIERTO' LIMIT 1";
		$q = $this->conexion->query($query);
		
		if($this->conexion->num_rows($q) > 0){
			$r = $resultado = $this->conexion->fetch($q);
			$r = $r[0];
			$fechaR = explode(' ', $r['fecha_cambio_estatus']);
			$fecha = $fechaR[0];
		}else{
			$fecha = 'Sin información';
		}
		/*if ($this->fecha_conclusion != NULL){
			$fechaR = separaFecha($this->fecha_conclusion);
			$fecha = $fechaR['fechamini'] . ' ' . $fechaR['hora'];
		}*/
		return $fecha;
	}
	function enviarMensajeInterno($estatus_nuevo){
		$now = date("Y-m-d H:i:s");
		$datosMensaje = array();
		$datosMensaje['folio'] = self::folioCompleto();
		$datosMensaje['link'] = WEB_URL;
		switch ($estatus_nuevo) {
			/*case 2:	//2 -> registro MED - pero se envía mensaje sólo cuando no se encontró al médico
				$perfil = 3;//MED
				$mensaje = 'Usuaria en espera de llenar Expediente';
				break;
			case 4: //4 -> registro pasa a FIN
				$perfil = 6;//FIN
				$mensaje = 'Usuaria ha depositado, hay que verificar depósito';
				break;
			case 5:	//5 -> registro pasa a LOG
				$perfil = 8;//LOG
				$mensaje = 'El pedido está listo para ser programado';
				break;*/
			case 'SOLICITUD DE SUBSIDIO':
				$getPerfilAutorizador = $this->conexion->fetch($this->conexion->query("SELECT kid_perfil FROM ".PREFIJO."perfil WHERE nombre_perfil='".PERFIL_SUPERVISOR."'"));
				$perfil = $getPerfilAutorizador[0]['kid_perfil'];
				$mensaje = 'Se solicita autorización de subsidio <br />';
				#$mensaje .= 'Ingrese para revisar la solicitud y aprovarla/rechazarla: <a href="'.WEB_URL.'webservices/revision.php?nip='.$this->nip.'-'.$this->code.'">Revisar solicitud</a>';
				$mensaje .= 'Ingrese para revisar la solicitud y aprovarla/rechazarla: <a href="'.WEB_URL.'/SUB/code='.$this->nip.'-'.$this->code.'">Revisar solicitud</a>';
				break;
			case 'REGISTRO CERRADO':
				$getPerfilAutorizador = $this->conexion->fetch($this->conexion->query("SELECT kid_perfil FROM ".PREFIJO."perfil WHERE nombre_perfil='".PERFIL_SUPERVISOR."'"));
				$perfil = $getPerfilAutorizador[0]['kid_perfil'];
				$mensaje = 'El folio se ha cerrado.  <br />';
				#$mensaje .= 'Ingrese para revisar la solicitud y aprovarla/rechazarla: <a href="'.WEB_URL.'webservices/revision.php?nip='.$this->nip.'-'.$this->code.'">Revisar solicitud</a>';
				#$mensaje .= 'Ingrese para revisar la solicitud y aprovarla/rechazarla: <a href="'.WEB_URL.'/SUB/code='.$this->nip.'-'.$this->code.'">Revisar solicitud</a>';
				break;
		}
		$datosMensaje['textoEstatus'] = $mensaje;
		//OBTENER PLANTILLA DE CORREO
		$mensaje = makeTemplate('_mensaje_interno_1.html', $datosMensaje, 'mensajes_internos');
		//OBTENER CORREOS SEGÚN PERFIL
		$correos = self::getCorreosPorPerfil($perfil);
		$envio = self::enviaCorreoInterno($mensaje,$correos);

		if($envio['success']){
			return TRUE;
		}else{
			die($envio['error']);
		}
	}
	function folioCompleto(){
		$folio = '';
		if($this->fid_acompanante != 0){
			$acompanante = self::getAcompanante();
			$folio .= $acompanante['siglasAcompanante'];
		}
		$folio .= $this->nip;
		return $folio;
	}
	function getAcompanante(){
		$prefijo = PREFIJO;
		$acompanante = array('alias'=>'-','correo_acompanante'=>FALSE,'siglasAcompanante'=>'');
		//OBTENGO DATOS DE ACOMPAÑANTE
		$queryAcompanante = "SELECT * FROM {$prefijo}acompanantes WHERE kid_acompanante={$this->fid_acompanante}";
		if($this->fid_acompanante != 0){
			$acompanante = $this->conexion->fetch($this->conexion->query($queryAcompanante));
			$acompanante = $acompanante[0];
			$alias = $acompanante['alias'];
			$acompanante['siglasAcompanante'] = strtoupper(substr($alias,0,3));
		}
		return $acompanante;
	}
	function getCorreosPorPerfil($perfil){
		$correos = array();
		$prefijo = PREFIJO;
		$queryAcompanante = "SELECT * FROM {$prefijo}usuarios WHERE fid_perfil={$perfil} AND usr_activo=1";//SOLO USUARIOS ACTIVOS Y CON EL PERFIL
		$correosObtenidos = $this->conexion->fetch($this->conexion->query($queryAcompanante));
		foreach($correosObtenidos as $correo){
			$correos[] = $correo['usr_correo'];
		}
		return $correos;
	}
	function enviaCorreoInterno($mensaje,$correos){
		$data = array("success"=>false,"error"=>'');
		$dataMail = array(
						'sender_mail'=>SENDER_MAIL,
						'sender_name'=>SENDER_MAIL_NAME,
						'destinatarios' => $correos,
						'isHTML'=>TRUE,
						'titulo'=>'Seguimiento',
						'mensaje'=>$mensaje,
						'alt_mensaje'=>''
					);
			if($this->mailsender->send_mail($dataMail)){ 
				$data["success"] 	= true;
				$data["error"]		= '';
			}else{ 
				$data["success"] 	= false;
				$data["error"]		= 'Error de envío:'.$this->mailsender->error;
			}
		return $data;
	}
	function getRegistroDeBD($nip){
		$prefijo = PREFIJO;
		$queryRegistro = "SELECT *,
							(SELECT nombre_entidad FROM {$prefijo}cat_entidades_federativas WHERE fid_entidad=kid_entidad) AS nombre_entidad,
							(SELECT nombre_municipio FROM {$prefijo}cat_municipios WHERE {$prefijo}registros.fid_municipio=cat_key AND {$prefijo}cat_municipios.fid_entidad={$prefijo}registros.fid_entidad) AS nombre_municipio,
							(SELECT CONCAT(codigo_postal,' | ',asentamiento,' (',tipo_asentamiento,')') FROM {$prefijo}cat_cp WHERE fid_localidad=kid_cp) AS nombre_localidad,
							(SELECT nombre_entidad FROM {$prefijo}cat_entidades_federativas WHERE envio_fid_entidad=kid_entidad) AS envio_nombre_entidad
							FROM {$prefijo}registros WHERE nip='{$nip}' LIMIT 1";
		$data = $this->conexion->query($queryRegistro);
		$num_rows = $this->conexion->num_rows($data);
		if($num_rows > 0){
			$registro = $this->conexion->fetch($data);
			/*if($nip == 11){
				echo "<pre>";print_r($registro);
			}*/
			$this->setRegistro($registro[0]);
			return true;
		}else{
			return false;
		}
		
	}
	function addDetalle($detalle){
		if($this->detalleFaltante == ''){
			$this->detalleFaltante = $detalle;
		}else{
			$this->detalleFaltante .= ", $detalle";
		}
	}
	function getEstatusArray(){
		$arr = array();
		if($this->nip != 0){//EXISTE
			$query = "SELECT fid_estatus FROM ".PREFIJO."estatus WHERE fid_registro={$this->nip}";
			$res = $this->conexion->fetch($this->conexion->query($query));
			foreach($res as $row){
				$arr[] = $row['fid_estatus'];
			}
		}
		return $arr;
	}
	function estatusEngine($vista_solicitada){
		$estatus_registrados = $this->getEstatusArray();
		if($this->nip != 0){
			switch ($vista_solicitada) {
				//:::::::::VISTA::::::://
				case 'mostrar_carga_comprobante_pago':
					switch ($this->estatus_registro) {
						case 'REGISTRO COMPLETADO':
						case 'SUBSIDIO AUTORIZADO'://con permisos directos
						case 'REGISTRO DE COMPROBANTE DE PAGO'://si aún estoy editando tengo permisos
						case 'REGISTRO DE FECHA DE PAGO':
							return true;
							break;

						default://Si no hay un estatus especifico, no tiene permisos
							return false;
							break;
					}
					break;
				//:::::::END 'mostrar_carga_comprobante_pago':::::://

				//:::::::::VISTA::::::://
				case 'mostrar_carga_respaldo_envio':
					switch ($this->estatus_registro) {
						case 'REGISTRO DE RESPALDO DE ENVIO'://si aún estoy editando tengo permisos
						case 'REGISTRO DE FECHA DE ENVIO'://
						case 'REGISTRO DE NUM GUIA':
						case 'REGISTRO DE FECHA ESTIMADA DE ENTREGA':
							return true;
							break;

						case 'REGISTRO DE COMPROBANTE DE PAGO'://solo tengo acceso hasta que haya registrado tanto comprobante como fecha de pago
						case 'REGISTRO DE FECHA DE PAGO':
							if(in_array('REGISTRO DE COMPROBANTE DE PAGO', $estatus_registrados) && in_array('REGISTRO DE FECHA DE PAGO', $estatus_registrados)){
								return true;
							}else{
								return false;
							}
							break;

						default://Si no hay un estatus especifico, no tiene permisos
							return false;
							break;
					}
					break;
				//:::::::END 'mostrar_carga_respaldo_envio':::::://

				//:::::::::VISTA::::::://
				case 'mostrar_carga_respaldo_entrega':
					switch ($this->estatus_registro) {
						case 'REGISTRO DE RESPALDO DE ENTREGA'://si aún estoy editando tengo permisos
						case 'REGISTRO DE FECHA DE ENTREGA'://
						case 'REGISTRO DE RECIBIDO POR':
							return true;
							break;

						case 'REGISTRO DE RESPALDO DE ENVIO'://solo tengo acceso hasta que haya registrado todo lo del envio
						case 'REGISTRO DE FECHA DE ENVIO':
						case 'REGISTRO DE NUM GUIA':
						case 'REGISTRO DE FECHA ESTIMADA DE ENTREGA':
							if(		in_array('REGISTRO DE RESPALDO DE ENVIO', $estatus_registrados) 
								&& 	in_array('REGISTRO DE FECHA DE ENVIO', $estatus_registrados) 
								&& 	in_array('REGISTRO DE NUM GUIA', $estatus_registrados) 
								&& 	in_array('REGISTRO DE FECHA ESTIMADA DE ENTREGA', $estatus_registrados)){
								return true;
							}else{
								return false;
							}
							break;

						default://Si no hay un estatus especifico, no tiene permisos
							return false;
							break;
					}
					break;
				//:::::::END 'mostrar_carga_respaldo_entrega':::::://

				default://TODAS LAS DEMAS VISTAS NO REGISTRADAS
					return false;
					break;
			}

		}else{
			echo "No hay un registro válido";
			return false;
		}
	}
	function validateClosure(){
		$this->current_error = '';
		if($this->respaldo_entrega == ''){
			$this->current_error .= 'Falta el respaldo de entrega.<br>';
		}
		if($this->fecha_entrega == ''){
			$this->current_error .= 'Falta la fecha de entrega.<br>';
		}
		if($this->recibido_por == ''){
			$this->current_error .= 'Falta el nombre de quien recibió.<br>';
		}
		
		return $this->current_error == ''?true:false;
	}
	function getTodosRegistros($filter=array()){
		$prefijo = PREFIJO;
		$registros = array();
		
		$cond = 1;
		$condicion = "";
		

		if(count($filter) > 0){
			if(isset($filter['filter'])){
				$precondicion = explode(' ', $filter['filter']);
				foreach($precondicion as $a_condition){
					if($cond == 1){
						$condicion .= " WHERE ";
					}else{
						$condicion .= " OR ";
					}
					$condicion .= " (nip LIKE '%$a_condition%' OR nombre LIKE '%$a_condition%' OR apellido_paterno LIKE '%$a_condition%' OR (SELECT alias FROM {$prefijo}acompanantes WHERE kid_acompanante=fid_acompanante) LIKE '%$a_condition%') ";
					$cond++;
				}
				
			}
		}

		$query = "SELECT nip, 
					(SELECT fecha_cambio_estatus FROM {$prefijo}estatus WHERE fid_estatus='REGISTRO ABIERTO' AND fid_registro=nip) AS fecha_registro,
					(SELECT nombre_entidad FROM {$prefijo}cat_entidades_federativas WHERE fid_entidad=kid_entidad) AS nombre_entidad 
					FROM {$prefijo}registros $condicion ORDER BY nip DESC";


		$registros = $this->conexion->fetch($this->conexion->query($query));
		return $registros;
	}
}
$admRegistro = new Registro();
$admRegistro->setAdmin($Admin);
$admRegistro->setConexion($Admin->conexion);
#global $mail;//viene del archiv ocnfg.crs.php
$admRegistro->mailsender = $mail;
?>
<?php
#define("INCLUDE_ANY",true);
function conectDBp(){
	include_once("cnf/configuracion.cnf.php");
	$myCon = new Conexion();
	$myCon->debug=true;
	$myCon->conectar(HOST,USR,PSW,DB);
	return $myCon;
}
function closeCon($myCon){
	$myCon->close();
}
function generarPermisos(){
	$permisos = array();
	$myCon = conectDBp();
	$secciones = $myCon->fetch($myCon->query("SELECT * FROM ".PREFIJO."seccion WHERE visible=1"));
	$arrSecciones = array();
	foreach($secciones as $secc){
		$arrSecciones[] = $secc;
	}
	for($i = 0;$i < count($arrSecciones); $i++){
		$permisos[] = pow(2,$arrSecciones[$i]['kid_seccion']);
	}
	return $permisos;
}
function mostrarSecciones(){
	$permisos = array();
	$myCon = conectDBp();
	$secciones = $myCon->fetch($myCon->query("SELECT * FROM ".PREFIJO."seccion WHERE visible=1"));
	$arrSecciones = array();
	foreach($secciones as $secc){
		$arrSecciones[] = $secc;
	}
	return $arrSecciones;
}
function obtenerPermisos($bit){
	$permisos = generarPermisos();
	if($bit == 0){/*Se asignan todos los permisos, es administrador*/
		$arrPermisos = array();
		for($j = (count($permisos)-1);$j >=0; $j--){
			$arrPermisos[] = reversePow(2,$permisos[$j]);
		}
	}
	$tempBIT=$bit;
	$cuantos = count($permisos)-1;
	
	for($i=$cuantos;$i >= 0;$i--){
		if($tempBIT >= $permisos[$i]){
			$arrPermisos[] = reversePow(2,$permisos[$i]);
			$tempBIT = $tempBIT-$permisos[$i];
		} 
	}
	
	return $arrPermisos;
}
function validarPermiso($secc,$userArrPermisos){
	return in_array($secc,$userArrPermisos)?true:false;
}
function reversePow($base,$num){
	$counter=0;
	while($num > 1){
		$num = $num/$base;
		$counter++;
	}
	return $counter;
}
function mostrarAccesosGraf($arrSecciones){
	$myCon = conectDBp();
	$seccs = implode(",",$arrSecciones);
	$secciones = $myCon->fetch($myCon->query("SELECT * FROM ".PREFIJO."seccion WHERE kid_seccion in ($seccs)"));
	$arrSecciones = array();
	foreach($secciones as $secc){
		$arrSecciones[] = $secc;
	}
	$result = '';
	foreach($arrSecciones as $sec){
		$result .= "<div class='imgAcceso {$sec['image']}' title='{$sec['nombre']}'></div>";
	}
	return $result;
}
#$permisosUsuario = obtenerPermisos(9);/*indicar bit del perfil del usuario*/
#print_r($permisosUsuario);
#echo "<br />acceso a secc: ",2,"<br />";
#echo validarPermiso(2,$permisosUsuario)?'si':'no';
#print_r(mostrarAccesosGraf(array(1,2,3)));
?>
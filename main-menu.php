<?php
$secciones = $Admin->obtenerUsr('secciones');
for($s=0; $s < count($secciones); $s++){
	$activo = '';
	$acron = $secciones[$s]['acronimo'];
	$icon = $secciones[$s]['icon'];
	$seccName = mb_convert_encoding($secciones[$s]['nombre'], 'UTF-8', 'ISO-8859-1');
	if($acron != 'ADMIN'){//Saltamos la opción de ADMIN porque es un permiso global del sistema
		if($data1 == $acron){
			$activo = ' activo';
			$tituloDePagina = $seccName;
		}
		?>
			<li class="<?php echo $activo; ?>"><a href="<?php echo WEB_URL.$acron; ?>"><i class="fa-solid <?php echo $icon; ?>"></i><?php echo __($seccName); ?></a></li>
		<?php
	}
}
?>
	<li class="red"><a class="btn aleft" id="btnSalir"><i class="fa-solid fa-times"></i><?php echo __('Salir'); ?></a></li>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo utf8_encode_($tituloDePagina); ?> | <?php echo APP_NAME; ?></title>
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,shrink-to-fit=no" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
	<meta name="description" content="<?php echo utf8_encode_($tituloDePagina); ?>" />
	<link rel="shortcut icon" href="<?php echo WEB_URL; ?>img/favicon.ico"/>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <?php if($modulo == 'adm'){ ?>
    <link type="text/css" rel="stylesheet" href="<?php echo WEB_URL; ?>css/admin_basico.css" />
    <link type="text/css" rel="stylesheet" href="<?php echo WEB_URL; ?>css/admin_eurotrips_admin.css" />
    <?php }else{ ?>
    <link type="text/css" rel="stylesheet" href="<?php echo WEB_URL; ?>css/basico.css" />
    <link type="text/css" rel="stylesheet" href="<?php echo WEB_URL; ?>css/eurotrips_panel.css" />
    <?php } ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
	<script type="text/javascript">
		var siteURL = '<?php echo WEB_URL; ?>';
		var seccActual = '<?php echo $data1; ?>';
	</script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>
	<script src="https://kit.fontawesome.com/609e2e7fda.js" crossorigin="anonymous"></script>
	<!-- <script type="text/javascript" src="https://gstatic.com"></script> -->
	<script type="text/javascript" src="<?php echo WEB_URL; ?>js/app.js?<?php echo rand(100,10000); ?>"></script>
    <script type="text/javascript" src="<?php echo WEB_URL; ?>js/io.component.js?<?php echo rand(10, 150000); ?>"></script>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@7.3.2/css/flag-icons.min.css">
</head>
<body class="">
	<div id="mainContainer" class="container row">
		<div class="col">
			
			<header class="row sombrear">
				<div class="col logo flex">
					<label for="menuControl" class="controlBtnDespliegue">
						<div class="btnDespliegue">
							<span></span>
							<span></span>
							<span></span>
						</div>
					</label>
					<a href="<?php echo WEB_URL; ?>HOME"><img src="<?php echo WEB_URL; ?>img/logo-eurotrips-negro.png" alt="Eurotrips" /></a>
				</div>
				<div class="col tituloSeccion"><?php echo __(utf8_encode_($tituloDePagina)); ?></div>
				<div class="col usuario aright">
					<div class="bloqueUsuario"><i class="icon fa-solid fa-circle-user" title="<?php echo htmlspecialchars($Admin->obtenerUsr('nombre'), ENT_QUOTES, 'UTF-8') ?>"></i><span><?php echo htmlspecialchars($Admin->obtenerUsr('nombre'), ENT_QUOTES, 'UTF-8'); ?></span></div>
					<div class="small texto-grisclaro"><?php echo __(convertirPerfil($Admin->obtenerUsr('perfil'))); ?></div>
				</div>
				<?php if(!$Admin->esAdmin()){ ?>
				<div class="col col-2 langSelector desk">
					<div class="btn-group btnsIdioma" role="group">
						<a class="btn <?php echo $Admin->getLanguage()=='es'?'activo':''; ?> btnEstablecerES">ES</a>
						<a class="btn <?php echo $Admin->getLanguage()=='en'?'activo':''; ?> btnEstablecerEN">EN</a>
					</div>
				</div>
				<?php } ?>
			</header>
			<script type="text/javascript">
				$(document).ready(function(){
					$('.btnEstablecerES').click(function(){
						setLanguage('es');
					});
					$('.btnEstablecerEN').click(function(){
						setLanguage('en');
					});
				});
				function setLanguage(lang){
					var envioData = new FormData();
					envioData.append("action",'setLanguage');													
					envioData.append("lang",lang);
					envioData.append("email",'<?php echo $Admin->obtenerUsr('usr'); ?>');

					$.ajax({
						url: "<?php echo WEB_URL; ?>webservice/acciones.php",
						type:"POST",
						processData: false,//tanto processData como contentType deben estar en false para que funcione FormData
						contentType: false,
						data:envioData,
						cache:false,
						dataType:"json",
						success: function(respuesta){
							var texto = '';
							console.log(respuesta);
							if(respuesta.success){
								document.location.reload();
							}else{
								texto = "<div class='bg-warning'>"+respuesta.error+"</div>";
								$(".langSelector").prepend(texto).addClass('small');
								if(respuesta.data.httpCode == 401 || respuesta.data.httpCode == 403){
									document.location.href='<?php echo WEB_URL; ?>SALIR';
								}
							}
						},
						error: function(){
							if (typeof respuesta !== 'undefined') {
								$(".langSelector").prepend(respuesta.data.error);
								console.log(respuesta.data.error);
							}
						}
					});
				}

			</script>
			<input type="checkbox" id="menuControl" name="menuControl" />
			<nav id="optionsNav">
				<?php if(!$Admin->esAdmin()){ ?>
				<div class="langSelector mobile pm5">
					<div class="btn-group btnsIdioma" role="group">
						<a class="btn <?php echo $Admin->getLanguage()=='es'?'activo':''; ?> btnEstablecerES">ES</a>
						<a class="btn <?php echo $Admin->getLanguage()=='en'?'activo':''; ?> btnEstablecerEN">EN</a>
					</div>
				</div>
				<?php } ?>
				<ul>
					<?php 
					if($Admin->comprobarSesion() && $mostrarMenu){ 
						include_once(APP_PATH."main-menu.php");
					}
					?>
				</ul>
			</nav>
			<section class="mainSection home row">
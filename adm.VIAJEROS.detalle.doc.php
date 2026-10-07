<?php 
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }
#$DEBUG = TRUE;
$data = [];

$data['siteURL'] = WEB_URL;
$data['appName'] = APP_NAME;
$data['NOMBRE'] = $Admin->obtenerUsr('nombre');
$data['EMAIL'] = $Admin->obtenerUsr('usr');

$data['returnLink'] = WEB_URL.$data1;

$data['consultorHidden'] = '';
$data['consultorShow'] = ' hidden';
if($Admin->esConsultor()){
	$data['consultorHidden'] = ' hidden';
	$data['consultorShow'] = '';
}

$language = 'es';

if(!empty($data4) && $data4 !== ''){
	$UUID = $document_id = $data4;
	$document_data = $Admin->getDocumentByID($document_id);

	if(isset($document_data['document_id'])){

		$traveler_id = $document_data['traveler_id'];

		$data['returnLink'] = WEB_URL.$data1.'/detalle/'.$traveler_id;
		$call = $Admin->API->get("/api/travelers/".$traveler_id);

		$traveler = $call['data'];
		$data['traveler_name'] = $traveler['name'];
		$data['traveler_email'] = $traveler['email'];
		$data['traveler_birthdate'] = convertirFecha($traveler['birth_date']);
		$data['traveler_age'] = $traveler['age'];
		$data['traveler_passport_number'] = $traveler['passport_number'];
		$language = $traveler['language'] ?? 'es';
		
		if(is_null($traveler['trip_id'])){
			$data['trip_id'] = '';
			$data['trip_name'] = '-';
		}else{
			$trip = $Admin->getTrip($traveler['trip_id']);
			$data['trip_name'] = $trip['trip_name'];
		}
		$data['mensaje'] = '';
		
		$data['tipo_documento'] = 'Documento';

		switch($document_data['document_type']){
			case 'passport':
			case 'first_passport':
				$data['tipo_documento'] = 'Primer pasaporte';
				break;
			case 'second_passport':
				$data['tipo_documento'] = 'Segundo pasaporte';
				break;
		}

		$document_data_json = json_encode($document_data);
	}

}


$data['input'] = '';
$data['subirDoc'] = '';
$data['btnEnviar'] = '';
$data['labelSubir'] = '';
$data['visorDocumento'] = '';
$data['observacionesState'] = '';
$data['observaciones'] = '';
$data['btnsAcciones'] = '';

if(isset($document_data['document_id'])){
	$document_id = $document_data['document_id'];
	$mime = $document_data['mime_type'];
	$document_type = $document_data['document_type'];
	switch($document_data['status']){
		case 'pending_review':
			$data['btnPassportColor'] = 'texto-naranja';
			$data['passportCSS'] = 'fa-solid fa-clock '.$data['btnPassportColor'];
			$data['passportStatus'] = 'En revisión';
			break;
		case 'approved':
			$data['btnPassportColor'] = 'texto-aqua';
			$data['passportCSS'] = 'fa-solid fa-check '.$data['btnPassportColor'];
			$data['passportStatus'] = 'Validado';
			$data['observacionesState'] = ' disabled="disabled"';
			$data['btnsAcciones'] = ' hidden';
			
			$data['subirDoc'] = '';
			break;
		case 'rejected':
			$data['btnPassportColor'] = 'texto-alerta';
			$data['passportCSS'] = 'fa-solid fa-times '.$data['btnPassportColor'];
			$data['passportStatus'] = 'Rechazado';
			$data['observacionesState'] = ' disabled="disabled"';
			$data['btnsAcciones'] = ' hidden';
			
			$data['labelSubir'] = 'Subir de nuevo:';
			$data['subirDoc'] = '<input type="file" id="passport" name="passport"  class="document-upload" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">'.
				'<a class="btn primario btnUpload" data-input="passport"><i class="fa-solid fa-upload"></i></a>';
			$data['btnEnviar'] = '<button type="submit" class="btn primario" id="sendForm"><i class="fa-solid fa-paper-plane"></i> &nbsp; Enviar</button>';
			break;
	}

	$data['visorDocumento'] = $Admin->createVisorDoc($document_data,$traveler_id);
	$data['downloadDocLink'] = $Admin->createDownloadDocLink($document_data,$traveler_id);


	if($document_data['notes'] != ''){
		$data['observaciones'] = $document_data['notes'];
	}

	$plantilla = 'admin_viajero_detalle_documento.html';

}else{
	$data['btnPassportColor'] = 'texto-amarillo';
	$data['passportCSS'] = 'fa-regular fa-triangle-exclamation '.$data['btnPassportColor'];
	$data['passportStatus'] = 'Faltante';
	$data['input'] = '<input type="file" id="passport" name="passport"  class="document-upload" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">';
	$data['btnDoc'] = '<a class="btn primario btnUpload" data-input="passport"><i class="fa-solid fa-upload"></i></a>';
	$data['btnEnviar'] = '<button type="submit" class="btn primario" id="sendForm"><i class="fa-solid fa-paper-plane"></i> &nbsp; Enviar</button>';

	$plantilla = '403.html';
}
echo makeTemplate($plantilla, $data, 'admin');
?>
<script type="text/javascript">
	const traveler_id = <?php echo json_encode($traveler_id); ?>;
	const document_id = <?php echo json_encode($document_id); ?>;
	const document_data = <?php echo $document_data_json; ?>;
	$(document).ready(function(){
		console.log('Init...');
		$('#btnValidar').click(function(){
			var notes = $('#notes').val().trim();
			setDocumentStatus(traveler_id,document_id,document_data,'setDocumentStatus','Validar',notes,'.msgBox','<?php echo $language; ?>');
		});
		$('#btnRechazar').click(function(){
			var notes = $('#notes').val().trim();
			if(notes == ''){
				alert('Debe capturar las observaciones');
			}else{
				setDocumentStatus(traveler_id,document_id,document_data,'setDocumentStatus','Rechazar',notes,'.msgBox','<?php echo $language; ?>');
			}
		});

	});

function setDocumentStatus(traveler_id,document_id,document_data,accion,valor,notes,msgBox,language){
	var envioData = new FormData();
	envioData.append("action",accion);													
	envioData.append("traveler_id",traveler_id);
	envioData.append("document_id",document_id);
	envioData.append("valor",valor);
	envioData.append("notes",notes);
	envioData.append("language",language);
	envioData.append("document_data",JSON.stringify(document_data));
	$.ajax({
		url: siteURL+"webservice/acciones.php",
		type:"POST",
		processData: false,//tanto processData como contentType deben estar en false para que funcione FormData
		contentType: false,
		data:envioData,
		cache:false,
		dataType:"json",
		success: function(respuesta){
			var texto = '';
			if(respuesta.success){
				texto = '<span class="alerta verde">Se guardó el dato</span>';
			}else{
				texto = "<div class='alerta bg-warning'>"+respuesta.error+"</div>";
				if(respuesta.data.httpCode == 401 || respuesta.data.httpCode == 403){
					document.location.href='<?php echo WEB_URL; ?>SALIR';
				}
			}
			$(msgBox).html(texto);
			$(".alerta").delay(3000).fadeOut();
			if(respuesta.success){
				document.location.reload();
			}

		}
	});
}
</script>
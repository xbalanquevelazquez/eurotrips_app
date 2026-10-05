<?php 
function makeTemplate($plantilla, $datos, $dir = ''){
    $html = '';
    if(!is_array($datos)){
      die("No proporciono un array para el template: ".$plantilla);
    }
    if ($dir != '') $dir = $dir . '/';
    
    $file = TEMPLATE_PATH . $dir . $plantilla;
    
    if (file_exists($file)){
        $itModel = file_get_contents($file);

        // 2. Reemplazar variables {$...}
        foreach ($datos as $key => $value) {
            $re_str = '{$' . $key . '}';
            $re_value = $value ?? '';
            if (is_array($re_value)) $re_value = '';
            $itModel = str_replace($re_str, $re_value, $itModel);
        }

        // 1. Traducir textos del template
        $itModel = traducirTemplate($itModel);
        
        $html .= $itModel;
    } else $html .= 'No existe el archivo: ' . $file;

    return $html;
}
function traducirTemplate($html){
    global $translations;

    $idioma = $_SESSION['language'] ?? 'es';

    if($idioma === 'es'){
        return $html;
    }

    if(!isset($translations[$idioma])){
        return $html;
    }

    /*
     * ---------------------------------------------------------
     * Proteger SCRIPT y STYLE antes de procesar con DOMDocument
     * ---------------------------------------------------------
     */
    $bloquesProtegidos = [];

    $html = preg_replace_callback(
        '/<(script|style)\b[^>]*>.*?<\/\1>/is',
        function($match) use (&$bloquesProtegidos){
            $id = '___BLOQUE_PROTEGIDO_' . count($bloquesProtegidos) . '___';
            $bloquesProtegidos[$id] = $match[0];

            return $id;
        },
        $html
    );

    /*
     * ---------------------------------------------------------
     * Procesar HTML
     * ---------------------------------------------------------
     */

    libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $contenido = '<?xml encoding="UTF-8">' . $html;

    $dom->loadHTML(
        $contenido,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    $xpath = new DOMXPath($dom);

    /*
     * ---------------------------------------------------------
     * 1. Traducir nodos de texto
     * ---------------------------------------------------------
     */

    $textNodes = $xpath->query('//text()');

    foreach($textNodes as $textNode){

        $textoOriginal = $textNode->nodeValue;

        if(trim($textoOriginal) === ''){
            continue;
        }

        $inicio = '';
        $final = '';

        if(preg_match('/^\s+/', $textoOriginal, $match)){
            $inicio = $match[0];
        }

        if(preg_match('/\s+$/', $textoOriginal, $match)){
            $final = $match[0];
        }

        $texto = trim($textoOriginal);

        if(isset($translations[$idioma][$texto])){
            $traduccion = $translations[$idioma][$texto];

            if($traduccion !== ''){
                $textNode->nodeValue =
                    $inicio . $traduccion . $final;
            }
        }
    }

    /*
     * ---------------------------------------------------------
     * 2. Traducir atributos visibles
     * ---------------------------------------------------------
     */

    $atributosTraducibles = [
        'placeholder',
        'title',
        'alt',
        'value'
    ];

    foreach($atributosTraducibles as $atributo){
        $nodes = $xpath->query(
            '//*[@' . $atributo . ']'
        );

        foreach($nodes as $node){
            $textoOriginal = $node->getAttribute($atributo);

            if(isset($translations[$idioma][$textoOriginal])){
                $traduccion = $translations[$idioma][$textoOriginal];

                if($traduccion !== ''){
                    $node->setAttribute(
                        $atributo,
                        $traduccion
                    );
                }
            }
        }
    }

    /*
     * ---------------------------------------------------------
     * Obtener HTML
     * ---------------------------------------------------------
     */

    $htmlTraducido = $dom->saveHTML();
    libxml_clear_errors();

    /*
     * ---------------------------------------------------------
     * Restaurar SCRIPT y STYLE originales
     * ---------------------------------------------------------
     */

    foreach($bloquesProtegidos as $id => $contenidoOriginal){
        $htmlTraducido = str_replace(
            $id,
            $contenidoOriginal,
            $htmlTraducido
        );
    }

    return $htmlTraducido;
}
function resampleImageFromBase64($data,$nombre){
  $arrSearch = array(':','Á','É','Í','Ó','Ú','á','é','í','ó','ú','Ñ','ñ','"',"'");
  $arrReplace = array('-','A','E','I','O','U','a','e','i','o','u','N','n','','');
  $nombre = str_replace($arrSearch, $arrReplace, $nombre);
  $data = str_replace('data:image/png;base64,', '', $data);
  $data = str_replace(' ', '+', $data);
  $data = base64_decode($data);
  $im = imageCreateFromString($data);
  $img_file = APP_IMG_PATH.'firmas/'.$nombre.'.png';
  $web_img_file = WEB_IMG_PATH.'firmas/'.$nombre.'.png';
  imagealphablending($im, true);
  imagesavealpha($im, true);
  imagepng($im, $img_file, 0);
  return $web_img_file;
  //header('Content-Type: image/png');
  //echo  $data;
  //echo "<img src='../webfiles/firmas/$nombre.png'' />";
}
function enviarMensaje($destinatario, $tituloMensaje, $templateName, $datosMensaje = []){
    global $phpMailer;
    $mensaje = makeTemplate($templateName, $datosMensaje, 'correos');

    // Envía el correo
        /*$dataMail = array(
            'sender_mail'=>SENDER_MAIL,
            'sender_name'=>SENDER_MAIL_NAME,
            'destinatarios' => array($destinatario),
#           'destinatarios_bcc' =>array('alfredo.zamarripa@mariestopes.org.mx', 'info@proteccionysalud.com'),
            'isHTML'=>TRUE,
            'titulo'=>$tituloMensaje,
            'mensaje'=>$mensaje,
            'alt_mensaje'=>'',
        );*/
        #$phpMailer->debug(FALSE);

        if(MailFactory::enviar( $destinatario, $tituloMensaje, $mensaje , $debug=FALSE)){ 
            $success = TRUE;
            $error = '';
        } else { 
            $success = FALSE;
            $error = 'Error de envío:' . $phpMailer->error;
        }
    return array('success'=>$success,'error'=>$error);
}
function make_safe($char) {
    $ban = 1;
    $hack_sql = array("'", "*", "?", '/', "%", '"', ";", ":", "\\", "HAVING", "GROUP", "INSERT", "UNION", "DROP", "TABLE", "SET", "DELETE", "UPDATE", "SELECT", "MEMB_INFO", "MEMB__PWD", "MEMB___ID", "ALTER", "JAVASCRIPT", "ALERT", "SCRIPT", "FRAME", "ONMOUSE", "ONCLICK", "PROMPT");
    //$hack_xxs = array("JAVASCRIPT", "SCRIPT");
    $char1 = explode(" ", strtoupper($char));
    for ($i = 0; $i < count($char1); $i++) {
        if (in_array($char1[$i], $hack_sql)) {
            $ban = 0;
        }
    }
    return $ban;
}
function normaliza($ch){
    $originales = array("á","é","í","ó","ú","ñ","Á","É","Í","Ó","Ú","Ñ","ä","ë","ï","ö","ü","Ä","Ë","Ï","Ö","Ü");
    $modificada = array("a","e","i","o","u","n","A","E","I","O","U","N","a","e","i","o","u","A","E","I","O","U");
    return strtoupper(str_replace($originales, $modificada, $ch));
}
function validar($dato,$tipoValidacion){
    //,$campoReferencia=''
  /*if(!isset($campoReferencia) || $campoReferencia == ''){
    $campoReferencia = 'No especificó campo referencia';
  }*/
  /*if(!isset($dato)){
    die("No especificó dato a validar (tipo: $tipoValidacion en $campoReferencia)");
  }*/
  switch($tipoValidacion){
    case 'notEmpty':
    default:
        return !is_null($dato) && isset($dato) && trim($dato) != ''?$dato:'Error';
      break;
    case 'numeric':
        return !is_null($dato) && isset($dato) && trim($dato) != '' && is_numeric($dato) && $dato > 0 ?$dato:'Error';
      break;
    case 'binario':
        return !is_null($dato) && isset($dato) && trim($dato) != '' && is_numeric($dato) && $dato >= 0 && $dato <= 1?$dato:'Error';
      break;
    case 'onlyGet':
        return !is_null($dato) && isset($dato)?$dato:'';
      break;
    case 'email':
        return filter_var($dato, FILTER_VALIDATE_EMAIL)?$dato:'Error';
      break;
  }
}
function generarCodigo(){
    return $codigo = chr(rand(65,90)) . chr(rand(65,90)) . rand(1,9) . chr(rand(65,90)) . chr(rand(65,90)).date("Y").date("m").date("d");
}
/*
function filtrarRegistros(array $registros, string $busqueda, array $campos): array
{
    $busqueda = trim(mb_strtolower($busqueda));

    if ($busqueda === '') {
        return $registros;
    }

    $palabras = preg_split('/\s+/', $busqueda);

    $resultado = [];

    foreach ($registros as $registro) {

        $texto = '';

        foreach ($campos as $campo) {
            if (isset($registro[$campo])) {
                $texto .= ' ' . mb_strtolower((string)$registro[$campo]);
            }
        }

        foreach ($palabras as $palabra) {

            if (mb_strpos($texto, $palabra) !== false) {
                $resultado[] = $registro;
                break;
            }

        }

    }

    return ['registros' => $resultado];
}*/
function generarTablaDatos($registros,$tipo,$Admin,$data=Array()){
    $buffer = '';
    $fechaHoy = date('Y-m-d');
    switch($tipo){
        case 'VIAJES':
            $buffer .= '<table class="dataTable fullWidth">
                <thead>
                    <tr>
                        <th></th>
                        <th class="aleft">Grupo/Viaje</th>
                        <th>Año de viaje</th>
                        <th>Estatus</th>
                        <th>Enlace de itinerario</th>
                        <th>Costo total</th>
                        <th>Fecha límite liquidación</th>
                    </tr>
                </thead>
                <tbody>';

            if(count($registros) < 1){
                $buffer .= '<tr><td colspan="7">No hay registros para mostrar</td></tr>';
            }else{
                #$init = ($page - 1) * $regXPag;
                #$fin = ($page * $regXPag) - 1;
                #if($fin > ($totalRegistros - 1)){ $fin = $totalRegistros - 1; }

                #echo "init: $init | fin: $fin";
                //$regXPag,$totalRegistros,$page

                foreach($registros as $reg){
                    switch($reg['status']){
                        case 'preparing':
                            $colorCSS = 'texto-naranja';
                            break;
                        case 'confirmed':
                            $colorCSS = 'texto-verde';
                            break;
                    }

                    $costo = formatearCurrency($reg['cost'],$reg['currency']);

                    $marca = '';
                    if($reg['limit_date'] == ''){
                        $fechaOriginal = $reg['created_at'];
                        $reg['limit_date'] = $fechaMasUnAnio = date('Y-m-d', strtotime($fechaOriginal . ' + 1 year'));
                        $marca = ' *'; 
                    }
                    $limit_date = convertirFecha($reg['limit_date']);
                    
                    $buffer .= '
                    <tr>
                        <td><a href="'.WEB_URL.$tipo.'/detalle/'.$reg['trip_id'].'" class="btn small primario"><i class="fa-solid fa-eye"></i></a></td>
                        <td>'.$reg['trip_name'].'</td>
                        <td>'.$reg['year'].'</td>
                        <td class="strong"><span class="'.$colorCSS.'">'.$reg['status'].'</span></td>
                        <td>'.$reg['itinerary_url'].'</td>
                        <td>'.$costo.'</td>
                        <td class=""><span class="">'.$limit_date.$marca.'</span></td>
                    </tr>';
                }

            }
            $buffer .= '</tbody></table>';


            break;
        case 'VIAJEROS':
            $buffer .= '<table class="dataTable fullWidth">
                <thead>
                    <tr>
                        <th class="sticky first"></th>
                        <th class="aleft sticky name">Nombre</th>
                        <th>Correo</th>
                        <th>Año de viaje</th>
                        <th>Pasaporte</th>
                        <th>Grupo/Viaje</th>
                        <th>Documentación</th>
                        <th>Número de asistencia de viaje</th>
                        <th>Pagos pendientes de validar</th>
                        <th>Costo del viaje</th>
                        <th>Total pagado (validado)</th>
                        <th>Total adeudo (a la fecha)</th>
                    </tr>
                </thead>
                <tbody>';

            if(count($registros) < 1){
                $buffer .= '<tr><td></td><td></td><td colspan="10">No hay registros para mostrar</td></tr>';
            }else{
                
                foreach($registros as $reg){

                    $traveler_id = $reg['traveler_id'];
                    $userConsolidado = $Admin->getTravelerConsolidado($traveler_id);
                    $trip_data = [];

                    #print_pre($userConsolidado);

                    $trip_id = $userConsolidado['trip_id'];
                    $trip_name = '-';
                    if(!empty($trip_id)){
                        $trip_data = $userConsolidado['trip_data'][0];
                        $current_trip_name =  $trip_data['trip_name'];
                        $trip_name = '<a href="'.WEB_URL.'VIAJES/detalle/'.$trip_id.'">'.$current_trip_name.'</a>';
                    }

                    $documents_data = $userConsolidado['documents_data'];

                    $documentsStatus = '';
                    ###    FIRST PASSPORT
                    $first_passport = $Admin->searchDocumentByType($documents_data,'first_passport');
                    
                    $passportStatus = 'Faltante';
                    $btnPassportColor = 'texto-amarillo';
                    $passportCSS = 'fa-solid fa-exclamation-triangle '.$btnPassportColor;

                    if(count($first_passport) > 0){ 
                        $first_passport = $first_passport[0];
                        #print_pre($passport);
                        switch($first_passport['status']){
                            case 'pending_review':
                                $btnPassportColor   = 'texto-naranja';
                                $passportCSS        = 'fa-solid fa-clock '.$btnPassportColor;
                                $passportStatus     = 'En revisión';
                                break;
                            case 'approved':
                                $btnPassportColor   = 'texto-aqua';
                                $passportCSS        = 'fa-solid fa-check '.$btnPassportColor;
                                $passportStatus     = 'Validado';
                                break;
                            case 'ejected':
                            case 'rejected':
                                $btnPassportColor   = 'texto-alerta';
                                $passportCSS        = 'fa-solid fa-times '.$btnPassportColor;
                                $passportStatus     = 'Rechazado';
                                break;
                        }
                    }
                    $documentsStatus .= '<span class="chip small texto-gris nowrap">Pasaporte 1: <i class="'.$passportCSS.'"></i> '.$passportStatus.'</span>';
                    ###    SECOND PASSPORT
                    $second_passport = $Admin->searchDocumentByType($documents_data,'second_passport');
                    
                    $passport2Status = 'Faltante';
                    $btnPassport2Color = 'texto-amarillo';
                    $passport2CSS = 'fa-solid fa-exclamation-triangle '.$btnPassport2Color;


                    if(count($second_passport) > 0){ 
                        $second_passport = $second_passport[0];
                        #print_pre($passport);
                        switch($second_passport['status']){
                            case 'pending_review':
                                $btnPassport2Color   = 'texto-naranja';
                                $passport2CSS        = 'fa-solid fa-clock '.$btnPassport2Color;
                                $passport2Status     = 'En revisión';
                                break;
                            case 'approved':
                                $btnPassport2Color   = 'texto-aqua';
                                $passport2CSS        = 'fa-solid fa-check '.$btnPassport2Color;
                                $passport2Status     = 'Validado';
                                break;
                            case 'ejected':
                            case 'rejected':
                                $btnPassport2Color   = 'texto-alerta';
                                $passport2CSS        = 'fa-solid fa-times '.$btnPassport2Color;
                                $passport2Status     = 'Rechazado';
                                break;
                        }
                    }
                    if(count($second_passport) > 0){ 
                        $documentsStatus .= '<span class="chip small texto-gris nowrap">Pasaporte 2: <i class="'.$passport2CSS.'"></i> '.$passport2Status.'</span>';
                    }
                    ### ---

                    $costoTotalViaje = '-';
                    $totalPagadoLabel = '-';
                    $totalAdeudoLabel = '-';
                    $payments_data = $userConsolidado['payments_data'];
                    
                    if(!empty($trip_id)){
                        $dataPayment = [
                            "trip_id" => $trip_id,
                            "traveler_id" => $reg['traveler_id'],
                            "fechaHoy" => $fechaHoy,
                            "cost" => $trip_data['cost'],
                            "currency" => $trip_data['currency'],
                            "limit_date" => $trip_data['limit_date'],
                            "btnDetalle" => TRUE //GENERAR EL BOTÓN DE DETALLES
                        ];
                        $dataPagos = $Admin->getPaymentsProcessed($dataPayment,$payments_data);
                        $costoTotalViaje = $dataPagos['cost'];
                        $totalPagadoLabel = $dataPagos['totalPagadoLabel'];
                        $totalAdeudoLabel = $dataPagos['totalAdeudoLabel'];
                    }

                    $tienePagosPendientesValidar = $Admin->tienePagosPendientesValidar($payments_data);
                    $pagosPendientesValidar = '-';
                    $colorPagosPenidentesValidar = 'texto-grismedio';
                    if($tienePagosPendientesValidar > 0){
                        $pagosPendientesValidar = 'Sí';
                        $colorPagosPenidentesValidar = 'strong texto-alerta';
                    }

                    $buffer .= '
                    <tr>
                        <td class="sticky first"><a href="'.WEB_URL.$tipo.'/detalle/'.$reg['traveler_id'].'" class="btn small primario"><i class="fa-solid fa-eye"></i></a></td>
                        <td class="aleft sticky name">'.$reg['name'].'</td>
                        <td class="aleft">'.$reg['email'].'</td>
                        <td class="acenter">'.$reg['year'].'</td>
                        <td class="aleft">'.$reg['passport_number'].'</td>
                        <td>'.$trip_name.'</td>
                        <td class="acenter">'.$documentsStatus.'</td>
                        <td class="aright">'.$reg['support_number'].'</td>
                        <td class="'.$colorPagosPenidentesValidar.'">'.$pagosPendientesValidar.'</td>
                        <td class="strong nowrap">'.$costoTotalViaje.'</td>
                        <td class="strong nowrap">'.$totalPagadoLabel.'</td>
                        <td class="strong nowrap">'.$totalAdeudoLabel.'</td>
                    </tr>
                    ';
                }
            }
            $buffer .= '</tbody></table>';

            break;
        case 'PARCIALIDADES':
            #print_r($data);
            $costoTotal = 0;
            $subtotalParcialidades = 0;
            $diferenciaTotalParcialidades = 0;
            if(!is_null($data['cost'])){
                $costoTotal = $data['cost'];
            }
            
            $buffer .= '<table class="dataTable fullWidth">
                <thead>
                    <tr>
                        <th></th>
                        <th class="aleft">Fecha</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>';


            if(count($registros) < 1){
                $buffer .= '<tr><td colspan="3">No hay registros para mostrar</td></tr>';
            }else{

                for($i = (count($registros) - 1);$i >= 0; $i--){
                    $reg = $registros[$i];
                    $subtotalParcialidades += $reg['amount'];
                    $buffer .= '
                    <tr>
                        <td>';

                        if(!$Admin->esConsultor() && !$Admin->esEditor()){
                        $buffer .= '<a class="btn small primario btnDelete" data-id="'.$reg['installment_id'].'"><i class="fa-solid fa-times"></i></a>';    
                        }

                    $buffer .= '    
                        </td>

                        <td class="aleft">'.convertirFecha($reg['payment_date']).'</td>
                        <td class="strong aright">'.formatearCurrency($reg['amount'],$data['currency']).'</td>
                    </tr>
                    ';
                }

            }

            $diferenciaTotalParcialidades = $costoTotal - $subtotalParcialidades;
            $total = $subtotalParcialidades + $diferenciaTotalParcialidades;

            $buffer .= '
                <tr class="bg-postit">
                    <td class="strong">
                        Límite de liquidación:
                    </td>
                    <td class="aleft">'.convertirFecha($data['limit_date']).'</td>
                    <td class="strong aright">'.formatearCurrency($diferenciaTotalParcialidades,$data['currency']).'</td>
                </tr>
            ';

            $buffer .= '
                <tr class="bg-aqua">
                    <td></td>
                    <td class="strong aright">
                        Total:
                    </td>
                    <td class="strong aright">'.formatearCurrency($total,$data['currency']).'</td>
                </tr>
            ';
            $buffer .= '</tbody></table>';

            break;
        case 'RATES_EUR':
            $buffer .= '<table class="dataTable fullWidth">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo de cambio</th>
                    </tr>
                </thead>
                <tbody>';

            if(count($registros) < 1){
                $buffer .= '<tr><td colspan="2">No hay registros</td></tr>';
            }else{
                
                foreach($registros as $reg){
                   
                    
                    $buffer .= '
                    <tr>
                        <td>'.convertirFecha($reg['date']).'</td>
                        <td class="aright">'.number_format($reg['rate_eur'],2).'</td>
                    </tr>
                    ';
                }
            }
            $buffer .= '</tbody></table>';
            break;
        case 'RATES_USD':
            $buffer .= '<table class="dataTable fullWidth">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo de cambio</th>
                    </tr>
                </thead>
                <tbody>';

            if(count($registros) < 1){
                $buffer .= '<tr><td colspan="2">No hay registros</td></tr>';
            }else{
                
                foreach($registros as $reg){
                   
                    
                    $buffer .= '
                    <tr>
                        <td>'.convertirFecha($reg['date']).'</td>
                        <td class="aright">'.number_format($reg['rate_usd'],2).'</td>
                    </tr>
                    ';
                }
            }
            $buffer .= '</tbody></table>';
            break;
        case 'PAGOS_SIMPLE':
            $btnDetalle = FALSE;
            if(isset($data['btnDetalle'])){
                $btnDetalle = $data['btnDetalle'];
            }
            $buffer .= '<table class="dataTable fullWidth">
                <thead>
                    <tr>
                        <th>'.__('Fecha').'</th>
                        <th>'.__('Monto').'</th>';
            if($btnDetalle){
                $buffer .= '        <th>'.__('Tipo').'</th>';
            }
            $buffer .= '            <th>'.__('Estado').'</th>';
            if($btnDetalle){
                $buffer .= '        <th></th>';
            }
            $buffer .= '
                    </tr>
                </thead>
                <tbody>';

            if(count($registros) < 1){
                $buffer .= '<tr><td colspan="4">'.__('No hay registros').'</td></tr>';
            }else{
                
                $rutaDetallePago = WEB_URL.'PAGOS/detalle/';
                if($Admin->esAdmin()) $rutaDetallePago = WEB_URL.'VIAJEROS/pago/';

                foreach($registros as $reg){
                    
                    $fechaPago = '-';
                    $estatus = '-';
                    $monto = formatearCurrency($reg['amount_original'],$reg['currency_original']);
                    $estatus = convertirEstatusPago($reg['payment_status']);
                    $cssTexto = convertirEstatusColor($reg['payment_status']);

                    switch($reg['payment_status']){
                        case 'pending':
                        case 'canceled':
                        default:
                            $fechaPago = convertirFecha($reg['created_at']);
                            break;
                        case 'validated':
                        case 'rejected':
                        case 'ejected':
                            $fechaPago = convertirFecha($reg['paid_at']);
                            break;
                    }

                    $tipoPago = convertirTipoPago($reg['payment_type']);
                    
                    $buffer .= '
                    <tr>
                        <td>'.$fechaPago.'</td>
                        <td>'.$monto.'</td>';
                    if($btnDetalle){
                        $buffer .= '<td>'.__($tipoPago).'</td>';
                    }
                        $buffer .= '<td class="texto-'.$cssTexto.'">'.__($estatus).'</td>';
                    if($btnDetalle){
                        $buffer .= '<td><a href="'.$rutaDetallePago.$reg['payment_id'].'" class="btn primario"><i class="fa-solid fa-eye"></i></a></td>';
                    }
                    $buffer .= '</tr>';
                }
            }
            $buffer .= '</tbody></table>';
            break;
        case 'USERS':
            $buffer .= '<table class="dataTable fullWidth">
                <thead>
                    <tr>
                        <th></th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Activo</th>
                        <th>Rol</th>
                    </tr>
                </thead>
                <tbody>';

            if(count($registros) < 1){
                $buffer .= '<tr><td colspan="5">No hay registros para mostrar</td></tr>';
            }else{
                
                foreach($registros as $reg){
                    $colorActivoCSS = '';
                    $btnAction = 'desactivar';
                    $status = '<span class="texto-verde">Sí</span>';
                    $btnIconActivo = '-slash';
                    $actionStatus = 'desactivar';
                    if(!$reg['is_active']){
                        $colorActivoCSS = 'inactivo';
                        $btnAction = 'activar';
                        $status = '<span class="texto-alerta">No</span>';
                        $btnIconActivo = '';
                        $actionStatus = 'activar';
                    }
                    switch($reg['role']){
                        case 'admin':
                            $role = 'Admin';
                            break;
                        case 'concierge':
                            $role = 'Sólo consulta';
                            break;
                        case 'editor':
                            $role = 'Editor';
                            break;
                    }

                    $buffer .= '
                    <tr class="'.$colorActivoCSS.'">
                        <td>
                            <a href="'.WEB_URL.'CONFIG/USER/'.$reg['user_id'].'" class="btn small primario"><i class="fa-solid fa-pencil"></i></a> 
                            <a mail-refer="'.$reg['email'].'" change="'.$actionStatus.'" class="btn small primario btnActivar"><i class="fa-solid fa-user'.$btnIconActivo.'"></i></a>
                        </td>
                        <td class="strong">'.$reg['email'].'</td>
                        <td>'.$reg['name'].'</td>
                        <td class="strong">'.$status.'</td>
                        <td>'.$role.'</td>
                    </tr>';
                }

            }
            $buffer .= '</tbody></table>';


            break;
        default:
            $buffer = 'No se ha configurado la visualización de tabla de registros (generarTablaDatos).';
            break;
    }
    
    return $buffer;

}
function calcularTotalPagado($payments_data){
    $totalPagado = 0;
    foreach ($payments_data as $payment) {
        if($payment['payment_type'] !== 'fee_online' && $payment['payment_type'] !== 'fee_document'){//NO SUMO EL PAGO SI PERTENECE A FEE
            if($payment['payment_status'] == 'validated'){//VERIFICAR CUAL ES EL ESTATUS FINAL QUE SE USUARA
                $totalPagado += $payment['amount_original'];//SOLO SUMA SI YA ESTA VERIFICADO
            }
        }
    }
    return $totalPagado;
}
function convertirTipoPago($payment_type){
    switch($payment_type){//actualizar el catálogo
        case 'transfer':
            $tipoPago = 'Transferencia';
            break;
        case 'cash':
            $tipoPago = 'Depósito en efectivo';
            break;
        case 'online':
            $tipoPago = 'Pago en línea';
            break;
        default:
            $tipoPago = '-';
            break;
    }
    return $tipoPago;
}
function convertirTipoDocumento($tipo_documento){
    $tipoConvertido = 'Documento';

    switch($tipo_documento){
        case 'first_passport':
            $tipoConvertido = 'Primer pasaporte';
            break;
        case 'second_passport':
            $tipoConvertido = 'Segundo pasaporte';
            break;
        case 'cash':
            $tipoConvertido = 'Depósito en efectivo';
            break;
        case 'transfer':
            $tipoConvertido = 'Transferencia';
            break;
    }

    return $tipoConvertido;
}
function convertirEstatusPago($payment_status){
    $estatus = '-';
    switch($payment_status){
        case 'pending':
            $estatus = 'Pendiente';
            break;
        case 'confirmed':
        case 'confirmed ':
        case 'validated':
        case 'validated ':
            $estatus = 'Validado';
            break;
        case 'ejected':
        case 'rejected':
        case 'rejected ':
        case 'ejected ':
            $estatus = 'Rechazado';
            break;
        case 'canceled':
            $estatus = 'Cancelado';
            break;
    }
    return $estatus;
}
function convertirEstatusColor($payment_status){
    $color = 'grismedio';
    switch($payment_status){
        case 'pending':
            $color = 'naranja';
            break;
        case 'confirmed':
        case 'confirmed ':
        case 'validated':
        case 'validated ':
            $color = 'verde';
            break;
        case 'ejected':
        case 'rejected':
        case 'rejected ':
        case 'ejected ':
            $color = 'alerta';
            break;
    }
    return $color;
}
function convertirPerfil($role){
    $roleConverted = '';
    switch ($role) {
        case 'traveler':
            $roleConverted = 'Viajero';
            break;
        case 'admin':
            $roleConverted = 'Admin';
            break;
        case 'editor':
            $roleConverted = 'Editor';
            break;
        case 'concierge':
            $roleConverted = 'Consulta';
            break;
        default:
            $roleConverted = 'No definido';
            break;
    }
    return $roleConverted;
}
function homologarRegistros($registros,$tipo){
    $registrosHomologados = [];
    switch($tipo){
        case 'VIAJES':
            foreach($registros as $reg){
                switch($reg['status']){
                    case 'preparing':
                        $estatus = 'Pendiente';
                        break;
                    case 'confirmed':
                        $estatus = 'Confirmado';
                        break;
                }
                $reg['status'] = $estatus;
                array_push($registrosHomologados, $reg);
            }
        break;
    default:
        $registrosHomologados = $registros;
        break;
    }
    
    return $registrosHomologados;
}
function validarRegistro(array $data): array
{
    $errores = [];

    // Campos obligatorios
    $requeridos = [
        'name',
        'email',
        'password',
        'confirmPassword',
        'birth_date',
        'age',
        'passport_number',
        'phone_number',
        'parent_name',
        'parent_email',
        'parent_phone_number'
    ];

    foreach ($requeridos as $campo) {

        if (!isset($data[$campo]) || trim($data[$campo]) === '') {
            $errores[] = "El campo {$campo} es obligatorio.";
        }

    }

    // Correos
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido.";
    }

    if (!empty($data['parent_email']) && !filter_var($data['parent_email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo del responsable no es válido.";
    }

    // Contraseñas
    if (($data['password'] ?? '') !== ($data['confirmPassword'] ?? '')) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    // Edad
    if (!empty($data['age']) && (!is_numeric($data['age']) || $data['age'] < 15 || $data['age'] > 99)) {
        $errores[] = "La edad no es válida.";
    }

    // Fecha
    if (!empty($data['birth_date'])) {

        $fecha = DateTime::createFromFormat('Y-m-d', $data['birth_date']);

        if (!$fecha || $fecha->format('Y-m-d') !== $data['birth_date']) {
            $errores[] = "La fecha de nacimiento no es válida.";
        }

    }

    return $errores;
}
function validarReestablecerPasswords(array $data): array
{
    $errores = [];

    // Campos obligatorios
    $requeridos = [
        'password',
        'confirmPassword'
    ];

    foreach ($requeridos as $campo) {

        if (!isset($data[$campo]) || trim($data[$campo]) === '') {
            $errores[] = "El campo {$campo} es obligatorio.";
        }

    }

    // Contraseñas
    if (($data['password'] ?? '') !== ($data['confirmPassword'] ?? '')) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    return $errores;
}
function validarRegistroEdicion(array $data): array
{
    $errores = [];

    // Campos obligatorios
    $requeridos = [
        //'name',
        //'email',
        //'password',
        //'confirmPassword',
        'birth_date',
        'age',
        'passport_number',
        'phone_number',
        'parent_name',
        'parent_email',
        'parent_phone_number'
    ];

    foreach ($requeridos as $campo) {

        if (!isset($data[$campo]) || trim($data[$campo]) === '') {
            $errores[] = "El campo {$campo} es obligatorio.";
        }

    }

    // Correos
    /*if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido.";
    }*/

    if (!empty($data['parent_email']) && !filter_var($data['parent_email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo del responsable no es válido.";
    }

    // Contraseñas
    /*if (($data['password'] ?? '') !== ($data['confirmPassword'] ?? '')) {
        $errores[] = "Las contraseñas no coinciden.";
    }*/

    // Edad
    if (!empty($data['age']) && (!is_numeric($data['age']) || $data['age'] < 15 || $data['age'] > 99)) {
        $errores[] = "La edad no es válida.";
    }

    // Fecha
    if (!empty($data['birth_date'])) {

        $fecha = DateTime::createFromFormat('Y-m-d', $data['birth_date']);

        if (!$fecha || $fecha->format('Y-m-d') !== $data['birth_date']) {
            $errores[] = "La fecha de nacimiento no es válida.";
        }

    }

    return $errores;
}
function validarTiposDeCambio(array $data): array
{
    $errores = [];

    // Campos obligatorios
    $requeridos = [
        'rate_eur',
        'rate_usd'
    ];

    foreach ($requeridos as $campo) {
        if (!isset($data[$campo]) || trim($data[$campo]) === '') {
            $errores[] = "El campo {$campo} es obligatorio.";
        }
    }

    return $errores;
}
function formatNotifications($notifications){
    $buffer = '';
    foreach ($notifications as $notif) {
        if(!$notif['is_read']){//NOTIFICACIONES NO LEIDAS

            $color = 'amarilla';
            $link = WEB_URL.'NOTIFICATIONS/'.$notif['notification_id'];
            $icon = 'fa-file-lines';
            $message = $notif['message'];
            if($message == 'El pago cambió a: ejected'){
                $message = 'El pago cambió a: rejected';
            }
            $notification_id = $notif['notification_id'];

            switch ($notif['type']) {
                case 'document_uploaded':
                    $color = 'amarilla';//roja
                    $icon = 'fa-file-lines';
                    break;
                case 'payment_reminder':
                    $color = 'verde';
                    $icon = 'fa-credit-card';
                    break;
                case 'general':
                    $color = 'gris';
                    $icon = 'fa-message';
                    break;
            }
            switch ($message) {
                case 'El pago cambió a: '.PAYMENT_VALIDATED_STATUS:
                case 'El pago cambió a: '.PAYMENT_VALIDATED_STATUS.' ':
                case 'El documento cambió a: approved':
                case 'El documento cambió a: confirmed':
                    $color = 'verde';
                    break;
                case 'El pago cambió a: '.PAYMENT_REJECTED_STATUS:
                case 'El pago cambió a: rejected':
                case 'El documento cambió a: rejected':
                    $color = 'roja';
                    break;
            }


            $buffer .= '
                        <div class="row mb1 gap" notif-container="'.$notification_id.'">
                            <div class="col col-10">
                                <div class="alerta '.$color.'">
                                    <a href="'.$link.'">
                                        <div class="icono"><i class="fa-solid '.$icon.'"></i></div>
                                        <div class="texto">'.__($message).'</div>
                                    </a>
                                </div>
                            </div>
                            <div class="col col-1">
                                <div><a class="btn primario btnMarkRead" notif-ref="'.$notification_id.'" title="Marcar como leído"><i class="fa fa-check"></i> </a></div>
                            </div>
                        </div>
                        ';

        }
    }
    return $buffer;
}
function formatAdminNotifications($notifications){
    $buffer = '';
    foreach ($notifications as $notif) {
        if(!$notif['is_read']){//NOTIFICACIONES NO LEIDAS

            $color = 'amarilla';
            $link = WEB_URL.'NOTIFICATIONS/'.$notif['notification_id'];
            $icon = 'fa-file-lines';
            $message = $notif['message'];
            $notification_id = $notif['notification_id'];

            switch ($notif['type']) {
                case 'document_uploaded':
                    $color = 'amarilla';//roja
                    $icon = 'fa-file-lines';
                    break;
                case 'payment_reminder':
                    $color = 'verde';
                    $icon = 'fa-credit-card';
                    break;
                case 'general':
                    $color = 'verde';
                    $icon = 'fa-users';
                    break;
            }

            $buffer .= '<div class="alerta '.$color.'">
                            <a href="'.$link.'">
                                <div class="icono"><i class="fa-solid '.$icon.'"></i></div>
                                <div class="texto">'.$message.'</div>
                            </a>
                        </div>';

        }
    }
    return $buffer;
}
function convertirFecha($fechaOriginal,$selectLanguage=''){
    if(empty($selectLanguage)){
        $idioma = $_SESSION['language'] ?? 'es';
    }else{
        $idioma = $selectLanguage;
    }

    if($idioma == 'en'){
        $fechaConvertida = (new DateTime($fechaOriginal))->format('m/d/Y');
    }else{
        $fechaConvertida = (new DateTime($fechaOriginal))->format('Y-m-d');
    }
    return $fechaConvertida;
}
function convertirFechaHora($fechaOriginal,$selectLanguage=''){
    if(empty($selectLanguage)){
        $idioma = $_SESSION['language'] ?? 'es';
    }else{
        $idioma = $selectLanguage;
    }

    if($idioma == 'en'){
        $fechaConvertida = (new DateTime($fechaOriginal))->format('m/d/Y H:i:s');
    }else{
        $fechaConvertida = (new DateTime($fechaOriginal))->format('Y-m-d H:i:s');
    }
    return $fechaConvertida;
}
function ajustarFechaHoraTimezone($fechaOriginal){
    $fecha = new DateTime($fechaOriginal, new DateTimeZone('UTC'));
    $fecha->setTimezone(new DateTimeZone('America/Mexico_City'));

    return $fecha->format('Y-m-d H:i:s');
}
function formatearCurrency($monto,$currency){
    $simboloMoneda = '';
    $moneda = '';
    $buffer = '';
    if($monto == 0 || is_null($monto)){
        $buffer = '-';
    }else{
        $monto = number_format($monto,2);
        switch($currency){
            case 0:
            default:
                $simboloMoneda = '$';
                $moneda = 'MXN';
                break;
            case 1:
                $simboloMoneda = '€';
                $moneda = 'EUR';
                break;
            case 2:
                $simboloMoneda = '$';
                $moneda = 'USD';
                break;
        }
        $buffer = $simboloMoneda.' '.$monto.' <span class="small texto-grisclaro">'.$moneda.'</span>';
    }
    return $buffer;
}
function getCurrency($currency){
    $simboloMoneda = '';
    $moneda = '';
    $monedaYSimbolo = '';
    $resultados = [];
    switch($currency){
        case 1:
            $simboloMoneda = '€';
            $moneda = 'EUR';
            $monedaYSimbolo = 'Euros (€)';
            break;
        case 2:
            $simboloMoneda = '$';
            $moneda = 'USD';
            $monedaYSimbolo = 'Dólares ($)';
            break;
    }
    $resultados = ['simboloMoneda'=>$simboloMoneda,'moneda'=>$moneda,'monedaYSimbolo'=>$monedaYSimbolo];

    return $resultados;
}
function utf8_encode_($texto){
    return mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
}
function latin_encode_($texto){
    return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
}
function fechaFormato($fecha,$selectLanguage=''){
    date_default_timezone_set('America/Mexico_City');

    if(empty($selectLanguage)){
        $idioma = $_SESSION['language'] ?? 'es';
    }else{
        $idioma = $selectLanguage;
    }

    if($idioma === 'en'){
        $locale = 'en_US';
        $patron = "MMMM dd, yyyy";
    }else{
        $locale = 'es_MX';
        $patron = "dd 'de' MMMM 'de' yyyy";
    }

    $formateador = new IntlDateFormatter(
        $locale,
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        null,
        null,
        $patron
    );

    if (empty($fecha)) {
        $fecha = new DateTime();
    } elseif (is_string($fecha)) {
        $fecha = new DateTime($fecha);
    }

    return $formateador->format($fecha);
}
function validarNombreArchivo($basePath,$document_type,$fechaHoy,$ext,$counter=0){
    $counter++;
    $fileName = $basePath.$document_type.'_'.$fechaHoy.'_'.$counter.$ext;
    if (file_exists(STORAGE_PATH.$fileName)) {
        return validarNombreArchivo($basePath,$document_type,$fechaHoy,$ext,$counter);
    }else{
        return $fileName;
    }
}
function normalizePath($path){
    return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
}
function print_pre($arr){
    echo "<br />".obtenerNombreVariable($arr)." :<br />";
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}
// Función para buscar el nombre de la variable
function obtenerNombreVariable($variable) {
    // 1. Obtiene todas las variables definidas en el scope global
    foreach ($GLOBALS as $nombre => $valor) {
        // 2. Compara si la variable actual es idéntica a la que buscas
        if ($valor === $variable) {
            return '$' . $nombre;
        }
    }
    return null;
}
function getLADAFromPhone($telefonoCompleto){
    $ladas = ['+52', '+34', '+1'];
    $iconos = ['mx','es','us'];

    $lada = '';
    $icono = '';
    $telefono = $telefonoCompleto;

    for($i = 0;$i < count($ladas);$i++) {
        $codigo = $ladas[$i];

        if (str_starts_with($telefonoCompleto, $codigo)) {

            $lada = $codigo;
            $icono = "<span class='fi fi-{$iconos[$i]}'></span>";
            $telefono = substr($telefonoCompleto, strlen($codigo));

            break;
        }

    }
    return ['LADA' => $lada,'TEL' => $telefono,'ICON' => $icono];
}
function validarTokenTurnstile($tokenTurnstile){
    $response = [
        "success" => FALSE,
        "error" => ''
    ];
    $debugFile = LOGS_PATH . '/turnstile_debug.log';


    if($tokenTurnstile == ''){
        $response['success'] = FALSE;
        $response['error'] = '<div class="warning">Captcha no enviado.</div>';
        return $response;
        #die('Captcha no enviado.');
    }
    $datos = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $tokenTurnstile,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $respuesta = curl_exec($ch);

    #error_log('Turnstile response: '.$respuesta);
    error_log(
        date('Y-m-d H:i:s') .
        ' | Respuesta: ' .
        $respuesta .
        PHP_EOL,
        3,
        $debugFile
    );
    #curl_close($ch);
    /*
     * Comprobar error de conexión
     */
    if($respuesta === false){
        $errorCurl = curl_error($ch);
        $response['error'] = '<div class="warning">No se pudo validar el captcha (verificar config).</div>';
        #error_log('Turnstile cURL error: '.$errorCurl);
        error_log(
            date('Y-m-d H:i:s') .
            ' | Turnstile cURL error: ' .
            $errorCurl .
            PHP_EOL,
            3,
            $debugFile
        );
        return $response;
    }

    /*
     * Convertir respuesta JSON
     */
    $resultado = json_decode($respuesta, true);

    /*
     * Comprobar que realmente recibimos un JSON válido
     */
    if(!is_array($resultado)){
        $response['error'] = 'Respuesta inválida de Turnstile.';
        #error_log('Turnstile respuesta inválida: '.$respuesta);
        error_log(
            date('Y-m-d H:i:s') .
            ' | Turnstile respuesta inválida: ' .
            $respuesta .
            PHP_EOL,
            3,
            $debugFile
        );
        return $response;
    }

    /*
     * Comprobar resultado del captcha
     */
    if(empty($resultado['success'])){
        $response['success'] = FALSE;
        $response['error'] = '<div class="warning">Captcha inválido.</div>';
        return $response;
    }

    /*
     * Captcha correcto
     */
    $response['success'] = TRUE;
    $response['error'] = '';
    return $response;
}
function generarTokenRecuperacion(){
    return bin2hex(random_bytes(32));
}
function esUUIDestricto($uuid){

    return preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        $uuid
    ) === 1;

}
function esUUID($uuid){

    return preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
        trim($uuid)
    ) === 1;

}
function mimeToExtension($mime) {
    $map = [
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'application/pdf' => '.pdf',
        'image/jpg' => '.jpg',
    ];
    return $map[$mime] ?? '.unk';
}
function normalizarNombre($valor) {
    $valor = trim($valor);

    $valor = strtr($valor, [
        'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
        'á'=>'A', 'é'=>'E', 'í'=>'I', 'ó'=>'O', 'ú'=>'U',
        'Ü'=>'U', 'ü'=>'U', 'ñ'=>'N', 'Ñ'=>'N'
    ]);

    return mb_strtoupper($valor, 'UTF-8');
}
function __($texto,$selectLanguage=''){

    global $translations;

    if(empty($selectLanguage)){
        $idioma = $_SESSION['language'] ?? 'es';
    }else{
        $idioma = $selectLanguage;
    }

    if($idioma === 'en' && !empty($translations['en'][$texto])){
        return $translations['en'][$texto];
    }

    return $texto;
}
?>
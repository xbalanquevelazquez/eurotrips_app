<?php 
class Mailer365{
    /****************************************************
     * VARIABLES PRIVADAS
     ****************************************************/
    private static $graphUrl = 'https://graph.microsoft.com/v1.0';
    private static $accessToken = '';
    private static $lastError = '';
    private static $initialized = false;
	private static $tokenFile = CONF_PATH.'mail365_token.json';
    /****************************************************
     * ÚLTIMO ERROR
     ****************************************************/
    public static function getLastError(){

        return self::$lastError;

    }

    /****************************************************
     * OBTENER ACCESS TOKEN
     ****************************************************/
	private static function getAccessToken(){
	    /*
	     * ¿Existe token en memoria?
	     */
	    if(self::$accessToken != ''){
	        return self::$accessToken;
	    }
	    /*
	     * ¿Existe archivo de caché?
	     */
	    if(file_exists(self::$tokenFile)){
	        $json = json_decode(file_get_contents(self::$tokenFile),true);

	        if(!empty($json['access_token']) && !empty($json['expires']) && time() < $json['expires']){
	            self::$accessToken = $json['access_token'];
	            return self::$accessToken;
	        }
	    }
	    /*
	     * Pedir nuevo token
	     */
	    $url = 'https://login.microsoftonline.com/'.MAIL365_TENANT_ID.'/oauth2/v2.0/token';
	    $post = http_build_query([
	        'client_id'=>MAIL365_CLIENT_ID,
	        'client_secret'=>MAIL365_CLIENT_SECRET,
	        'scope'=>'https://graph.microsoft.com/.default',
	        'grant_type'=>'client_credentials'
	    ]);

	    $ch = curl_init($url);

	    curl_setopt_array($ch,[
	        CURLOPT_RETURNTRANSFER=>true,
	        CURLOPT_POST=>true,
	        CURLOPT_POSTFIELDS=>$post,
	        CURLOPT_HTTPHEADER=>[
	            'Content-Type: application/x-www-form-urlencoded'
	        ]
	    ]);

	    $response = curl_exec($ch);

	    if(curl_errno($ch)){
	        self::$lastError = curl_error($ch);
	        #curl_close($ch);
	        return false;
	    }

	    #curl_close($ch);

	    $json = json_decode($response,true);

	    if(empty($json['access_token'])){
	        self::$lastError = $response;
	        return false;
	    }

	    self::$accessToken = $json['access_token'];
	    /*
	     * Guardar caché
	     */
	    file_put_contents(
	        self::$tokenFile,
	        json_encode([
	            'access_token'=>$json['access_token'],
	            'expires'=>time()+$json['expires_in']-60
	        ]),
    		LOCK_EX
	    );

	    return self::$accessToken;
	}
    /****************************************************
     * PETICIÓN A MICROSOFT GRAPH
     ****************************************************/
    private static function graphRequest(
        $method,
        $endpoint,
        $body = null
    ){
    	
        $token = self::getAccessToken();

        if(!$token){
            return false;
        }

        $url = self::$graphUrl.$endpoint;

        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        /*  También devolver los headers */
		curl_setopt($ch, CURLOPT_HEADER, true);

        switch(strtoupper($method)){
            case 'POST':
            	curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'POST');
                break;

            case 'PATCH':
                curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'PATCH');
                break;

            case 'DELETE':
                curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'DELETE');
                break;

            case 'GET':
            default:
                break;
        }

        if($body != null){
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode($body)
            );
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


        $response = curl_exec($ch);

        if(curl_errno($ch)){
            self::$lastError = curl_error($ch);
            #curl_close($ch);
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		/* Tamaño de los headers */
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

		/* Separar headers y body */
		$headers = substr($response, 0, $headerSize);
		$body = substr($response, $headerSize);
		#curl_close($ch);

		/* Convertir únicamente el body a JSON */
		$json = json_decode($body, true);

        return [
            'httpCode'=>$httpCode,
            'headers' => $headers,
            'response'=>$json,
            'raw' => $body
        ];
    }

     /****************************************************
     * ENVIAR CORREO
     ****************************************************/
    public static function enviar(
        $to,
        $subject,
        $html
    ){
    	
        self::$lastError = '';

        #echo "FROM EMAIL: ".self::$fromEmail."<br />";
        /*
         * Si recibimos un string,
         * lo convertimos a arreglo.
         */
        if(!is_array($to)){
            $to = [$to];
        }

        /*
         * Construimos los destinatarios.
         */

        $destinatarios = [];
        foreach($to as $correo){
            $destinatarios[] = [
                'emailAddress'=>[
                    'address'=>$correo
                ]
            ];
        }

        /*
         * Construcción del mensaje.
         */
        $body = [
            'message'=>[
                'subject'=>$subject,
                'body'=>[
                    'contentType'=>'HTML',
                    'content'=>$html
                ],
                'toRecipients'=>$destinatarios
            ],
            'saveToSentItems'=>true
        ];

        /*
         * Enviar.
         */
        $respuesta = self::graphRequest(
            'POST',
            '/users/'.MAIL365_FROM.'/sendMail',
            $body
        );

		#print_pre($respuesta);

        if($respuesta===false){
            return false;
        }

        /*
         * Microsoft responde 202 cuando
         * acepta el correo.
         */
        self::log(
		    'INFO',
		    'Respuesta Microsoft Graph',
		    [
		        'httpCode'=>$respuesta['httpCode'],
		        'headers'=>$respuesta['headers'],
		        'body'=>$respuesta['raw']
		    ]
		);
        if($respuesta['httpCode']==202){
        	self::log(
			    'INFO',
			    'Correo enviado',
			    [
			        'to'=>$to,
			        'subject'=>$subject,
			        'http'=>$respuesta['httpCode']
			    ]
			);
        }else if($respuesta['httpCode']!=202){
        	self::log(
			    'ERROR',
			    self::$lastError,
			    [
			        'to'=>$to,
			        'subject'=>$subject,
			        'respuesta'=>$respuesta
			    ]
			);
            self::$lastError = 'Error Graph ('.$respuesta['httpCode'].') '.$respuesta['raw'];
            return false;
        }

        return true;
    }
    private static function log(
	    $nivel,
	    $mensaje,
	    $extra=[]
	){

	    $archivo = LOGS_PATH.'mail365.log';

	    $linea=[
	        'fecha'=>date('Y-m-d H:i:s'),
	        'nivel'=>$nivel,
	        'mensaje'=>$mensaje,
	        'extra'=>$extra
	    ];

	    file_put_contents(
	        $archivo,
	        json_encode(
	            $linea,
	            JSON_UNESCAPED_UNICODE
	        ).
	        PHP_EOL,
	        FILE_APPEND
	    );
	}
	public static function probarUsuario($correo){
	    $respuesta = self::graphRequest('GET','/users/'.rawurlencode($correo));
	    return $respuesta;
	}
}

?>
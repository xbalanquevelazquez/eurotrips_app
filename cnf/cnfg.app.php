<?php
date_default_timezone_set('America/Mexico_City');
header('Content-Type: text/html; charset=utf-8');
define('DEBUG',	TRUE);
if(DEBUG){
	ini_set('error_reporting', E_ALL);
	error_reporting(-1);
	ini_set('display_errors', '1');

	set_error_handler(function ($severity, $message, $file, $line) {

	    if (!(error_reporting() & $severity)) {
	        return false;
	    }

	    echo "<div style='font-size:12px;font-family:Consolas;'>";
	    echo "PHP ERROR\t";
	    echo "Severity [" . $severity . "]\t";
	    echo $message . "\t";
	    echo "in " . $file . "\t";
	    echo "on line " . $line . "";
	    echo "</div>";
	});
}

if(!defined('VIEWABLE'))
{ 	
	header('HTTP/1.0 404 Not Found');
	die("Error 404. No se encontró el contenido.");
	exit;
}else{
	$server = $_SERVER['SERVER_NAME'];
	
	$mainPath = $_SERVER['DOCUMENT_ROOT'];
	define('APP_NAME', 		'Eurotrips APP');
	define('APP_PATH',	dirname(__FILE__,2).DIRECTORY_SEPARATOR);//Un nivel arriba
	define('CONF_PATH',		APP_PATH.'cnf/');
	define('CLASS_PATH',	APP_PATH.'class/');
	define('LOGS_PATH',		APP_PATH.'logs/');
	define('LIB_PATH',		APP_PATH.'libs/');
	define('FILE_PATH',		APP_PATH.'webfiles/pdf/');
	define('APP_IMG_PATH',	APP_PATH.'webfiles/');
	define('TEMPLATE_PATH',	APP_PATH.'templates/');
	define('FUNCT_PATH',	APP_PATH.'funct/');
	
	define('PRIVADO',TRUE);

	// Cargar credenciales
	require_once CONF_PATH . 'credentials.php'; 

	// Aplicar credenciales del servidor
	foreach ($credentials[$server] as $key => $value) {
	    define($key, $value);
	}

	// Aplicar credenciales globales
	foreach ($credentials['global'] as $key => $value) {
	    define($key, $value);
	}


	define('WEB_FILE_PATH',	WEB_URL.'webfiles/pdf/');
	define('WEB_IMG_PATH',	WEB_URL.'webfiles/');



	define('LOGIN_LOCK_MINUTES',15);
	define('LOGIN_MAX_ATTEMPTS',3);


	require_once CONF_PATH . 'languages.php';
	include_once(FUNCT_PATH."funcionalidad.php");
	
	switch (MAIL_PROVIDER) {
		case 'MICROSOFT365':
			require_once CLASS_PATH.'Mailer365.class.php';
			break;
		
		case 'GMAIL':
		default:
			require_once CLASS_PATH.'Mailer.class.php';
			break;
	}
	require_once CLASS_PATH.'MailFactory.class.php';


	define('FEE_TEXT_MXN',	'$10,000.00 MXN, (diez mil pesos 00/100 MXN)');
	define('FEE_TEXT_USD',	'$500.00 USD, (five hundred dollars 00/100 USD)');
	define('ONLINE_MINIMUM_PAY',	100);

	include_once(CLASS_PATH."api.class.php");
	$API = new API(API_URL);

	include_once(CLASS_PATH."document.class.php");

	include_once(CLASS_PATH."admin.class.php");
	$Admin = new Admin($API);

	//MYSQL CONN CLASS
	include_once(CLASS_PATH."mysql.class.php");
	$conn = new MySQL(LOCAL_DB_HOST, LOCAL_DB_USER, LOCAL_DB_PSW, LOCAL_DB_NAME);

	include_once(CLASS_PATH.'LoginAttempts.class.php');
	$LoginAttempts = new LoginAttempts($Admin,$conn);

	define('INIT_YEAR_OPTIONS',	'2027');//Año desde el que iniciarán los OPTIONS de SELECTS
	define('CURRENT_YEAR_REGISTER',	$Admin->getCurrentYearRegister());//Año que se estará registrando para registros nuevos
	define('YEAR_FILTER',		$Admin->getYearFilter());//Año que se filtrará para las vistas

	$datos = array(
				'siteURL' => WEB_URL
				);

	$data1 = NULL;
	if(isset($_GET['data1']) && $_GET['data1'] != ''){ 
		$data1 = $_GET['data1']; 
	}else{
		if(PRIVADO){
			$data1 = 'LOGIN'; 
		}else{
			$data1 = 'HOME';
		}
	}
	$data2 = NULL;
	if(isset($_GET['data2']) && $_GET['data2'] != ''){ $data2 = $_GET['data2']; }
	$data3 = NULL;
	if(isset($_GET['data3']) && $_GET['data3'] != ''){ $data3 = $_GET['data3']; }
	$data4 = NULL;
	if(isset($_GET['data4']) && $_GET['data4'] != ''){ $data4 = $_GET['data4']; }
	$data5 = NULL;
	if(isset($_GET['data5']) && $_GET['data5'] != ''){ $data5 = $_GET['data5']; }
	$data6 = NULL;
	if(isset($_GET['data6']) && $_GET['data6'] != ''){ $data6 = $_GET['data6']; }

}
?>
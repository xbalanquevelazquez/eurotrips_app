<?php
require_once LIB_PATH.'PHPMailer/PHPMailer.php';
require_once LIB_PATH.'PHPMailer/SMTP.php';
require_once LIB_PATH.'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer{
	public static function enviar($to,$subject,$html,$debug = FALSE){
		$mail = new PHPMailer(true);

		if($debug){
			$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
			$mail->Debugoutput = 'html';
		}

		try{
			$mail->isSMTP();
			$mail->Host = SMTP_HOST;
			$mail->SMTPAuth = true;
			$mail->Username = SMTP_USER;
			$mail->Password = SMTP_PASS;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			#$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = SMTP_PORT;
			$mail->CharSet = 'UTF-8';
			$mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
			$mail->addAddress($to);
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $html;
			return $mail->send();
		}catch(Exception $e){
			echo '<pre>';
		    echo "Error PHPMailer:\n";
		    echo $mail->ErrorInfo;
		    echo '</pre>';
			return false;
		}
	}
}
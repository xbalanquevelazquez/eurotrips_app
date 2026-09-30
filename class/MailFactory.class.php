<?php 
class MailFactory{
    public static function enviar($to,$subject,$html,$debug=FALSE){
    	switch (MAIL_PROVIDER) {
			case 'MICROSOFT365':
                return Mailer365::enviar($to,$subject,$html,$debug);
				break;
			
			case 'GMAIL':
			default:
                return Mailer::enviar($to,$subject,$html,$debug);
				break;
		}
    }
}
?>
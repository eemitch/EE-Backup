<?php // Mitchell Bennis (mitch@elementengage.com)
// Version 1.1 -- Rev 12.19.23
// PHP 8 Approved

// Use this script to test the email sending functionality

// Messaging
$eeLog = array(
	'Notice' => array('Running Email Test...'),
	'Errors' => array()
);
	
// PHPMailer - https://github.com/PHPMailer/PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Email Notices and Backup Files
function mailAttached() {
	
	global $eeLog;
	
	$eeTo = 'mail@elementengage.net';
	$eeFrom = 'mail@elementengage.net';
	$eeSubject = 'Backup Mail Test';
	$eeBody = 'Testing the backup email sending.';
	$eeFile = 'backup-files/eeTestFile.zip';
	
	if(is_file($eeFile)) {
			
		// Get PHPMailer
		require_once( __DIR__ . '/PHPMailer/src/PHPMailer.php' );
		require_once( __DIR__ . '/PHPMailer/src/SMTP.php' );
		require_once( __DIR__ . '/PHPMailer/src/Exception.php' );
		
		$eeMail = new PHPMailer(TRUE);
		
		try {
/*	    
			// SMTP Server Settings
			$mail->SMTPDebug = 2; // Enable verbose debug output
			$mail->isSMTP(); // Send using SMTP
			$mail->Host = 'my.server.com'; // Set the SMTP server to send through
			$mail->SMTPAuth = true; // Enable SMTP authentication
			$mail->Username = 'mail@emailaddress.com'; // SMTP username
			$mail->Password = ''; // SMTP password
			$mail->SMTPSecure = 'ssl'; // Enable TLS encryption; PHPMailer::ENCRYPTION_SMTPS, PHPMailer::ENCRYPTION_STARTTLS also accepted
			$mail->Port = 465; // TCP port to connect to
*/		
			// Recipients
			$eeMail->setFrom($eeFrom, 'EE Backup Test');
			$eeMail->addAddress($eeTo, 'EE Backup Recipient'); // Add a recipient
			$eeMail->addReplyTo($eeFrom, 'EE Backup Test');
			// $mail->addCC('cc@example.com');
			// $mail->addBCC('bcc@example.com');
		
			// Attachments
			$eeMail->addAttachment($eeFile); // Add attachments
		
			// Content
			// $mail->isHTML(true); // Set email format to HTML
			$eeMail->Subject .= $eeSubject . ' -- ' . $_SERVER['SERVER_NAME'];
			$eeMail->Body    = $eeBody;
			// $eeMail->AltBody = $eeBody;
		
			$eeMail->send();
			
			$eeLog['Notice'][] = 'Message has been sent to ' . $eeTo;
			
			return TRUE;
		
		} catch (Exception $e) {
			
			$eeLog['Errors'][] = "Message could not be sent. Mailer Error: {$eeMail->ErrorInfo}";
			
			return FALSE;
		}
		
	} else {
		
		$eeLog['Errors'][] = "FILE NOT FOUND" . PHP_EOL . PHP_EOL . basename($eeFile);
		
		return FALSE;
	}
	
}
	
mailAttached();

echo print_r($eeLog);
	
	
?>
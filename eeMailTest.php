<?php // Mitchell Bennis (mitch@elementengage.com)
// Version 1.1 -- Rev 12.19.23
// PHP 8 Approved

// Use the script to test the email sending functionality

// Messaging
$log = array('Running script...');
$errors = array();
	
$to = 'mail@elementengage.net';
$from = 'mail@elementengage.net';
$subject = 'Backup Mail Test';
$body = 'Testing 123';
$file = 'backup_files/eeTestFile.zip';
	
mailAttached($to, $from, $subject, $body, $file);	
	
// PHPMailer - https://github.com/PHPMailer/PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Email Notices and Backup Files
function mailAttached($to, $from, $subject, $body, $file) {
	
	global $log, $errors;
	
	if(is_file($file)) {
			
		// Get PHPMailer
		require_once( __DIR__ . '/PHPMailer/src/PHPMailer.php' );
		require_once( __DIR__ . '/PHPMailer/src/SMTP.php' );
		require_once( __DIR__ . '/PHPMailer/src/Exception.php' );
		
		$mail = new PHPMailer(TRUE);
		
		try {
			
/*	    
			//Server settings
			$mail->SMTPDebug = 2; // Enable verbose debug output
			$mail->isSMTP(); // Send using SMTP
			$mail->Host = 'my.server.com'; // Set the SMTP server to send through
			$mail->SMTPAuth = true; // Enable SMTP authentication
			$mail->Username = 'mail@emailaddress.com'; // SMTP username
			$mail->Password = ''; // SMTP password
			$mail->SMTPSecure = 'ssl'; // Enable TLS encryption; PHPMailer::ENCRYPTION_SMTPS, PHPMailer::ENCRYPTION_STARTTLS also accepted
			$mail->Port = 465; // TCP port to connect to
*/		
			//Recipients
			$mail->setFrom($from, 'EE Backup');
			$mail->addAddress($to, 'EE Backups'); // Add a recipient
			// $mail->addAddress('ellen@example.com'); // Name is optional
			$mail->addReplyTo($from, 'EE Backups');
			// $mail->addCC('cc@example.com');
			// $mail->addBCC('bcc@example.com');
		
			// Attachments
			$mail->addAttachment($file);         // Add attachments
			// $mail->addAttachment($file, basename($file));    // Optional name
		
			// Content
			// $mail->isHTML(true); // Set email format to HTML
			$mail->Subject = $subject;
			$mail->Body    = $body;
			// $mail->AltBody = $body;
		
			$mail->send();
			
			$log[] = 'Message has been sent';
			
			return TRUE;
		
		} catch (Exception $e) {
			
			$errors[] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
			
			return FALSE;
		}
		
	} else {
		
		$errors[] = "FILE NOT FOUND" . PHP_EOL . PHP_EOL . basename($filename);
		
		return FALSE;
	}
	
}
	
mailAttached($to, $from, $subject, $body, $file);

$log[] = $errors;

echo print_r($log);
	
	
?>
<?php // Author: Mitchell Bennis - mitch@elementengage.com
	// Version 1.5.1 - 12.17.23
	// PHP 8 Approved

// Backup all the SQL databases for the user and then email and/or FTP away an archive file.

// Configuration ===============================
$eeBackup = TRUE; // Back up all databases
$eeFTPResult = TRUE; // Put the backup file on another server
$eeEmailResult = TRUE; // Send and email with the attached backups
$eeDeleteLocalBackup = TRUE; // Keep a local backup file.

DEFINE('SERVER', 'eeDotNet'); // The name of this server
DEFINE('BACKUP_FOLDER', __DIR__ . '/backup_files/'); // Local Backup Files

// Email Configuration
DEFINE('EMAIL_TO', 'me@my-address.com');
DEFINE('EMAIL_FROM', 'mail@this-server.com');
DEFINE('EMAIL_MaxAttachSize', 20971520); // Files over 20 MB will not be sent
$eeSubject = SERVER . ' Backup Job: ' . date('m-d-Y');

// DB & FTP Connection Info 
if(is_readable('../eeConnect.php')) { // External Connection Info - (This script is run via URI)
	
	require_once('../eeConnect.php'); // Contains connection info below

} else { // This script is run via command line or CRON (not in a public dir)
	
	// USAGE: Use Command Line or CRON
	// cd /home/elementengage/
	// php eeBackup.php
	
	// Database Configuration
	DEFINE('DB_USER', 'eeBackupUser');
	DEFINE('DB_PASSWORD', 'paSSworD'); // Best not contain special chars on command line
	DEFINE('DB_HOST', 'localhost');
	$eeExcluded = array('mysql', 'information_schema', 'performance_schema', 'sys'); // Don't back up these databases
	
	// FTP Configuration
	DEFINE('FTP_SERVER', '1.22.33.444'); // Destination FTP Server
	DEFINE('FTP_USER', 'backup@my-other-server.com'); // FTP Username
	DEFINE('FTP_PASSWORD', 'paSSworD'); // Password
	DEFINE('FTP_REMOTE', 'backup_files/'); // Use trailing slash
	
	// Amazon S3 Configuration- Coming Soon
}

// Error Reporting
ini_set("log_errors", TRUE);
error_reporting(E_ALL);
ini_set ('display_errors', TRUE);

// Script Setup ===================================

$eeCleanUp = array(); // Delete local working backup files after successful save.

// Messaging
$eeLog = array(
	'Notices' => array($eeSubject),
	'Errors' => array()
);

// Load our Class, contained far below.
$eeClass = new eeBackupClass();

// PHPMailer - https://github.com/PHPMailer/PHPMailer
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// Begin the script ============================================================

if(is_object($eeClass)) { // Check
	
	$eeDatabases = $eeClass->eeDB_ListAllDatabases(); // Get a list of the databases
	
	// Uncomment to test your connection first
	// print_r($eeDatabases); exit;
	
	if( !empty($eeDatabases) ) {
		
		$eeFileList = array();
		
		// Loop through each database and back it up
		foreach ($eeDatabases as $database) {
					
			$sqlFile = $eeClass->eeDB_DumpDatabase($database); // Dump the database to a SQL text file
			
			$eeCleanup[] = $sqlFile; // SQL File
			
			if(empty($eeLog['Errors'])) {
		
				$tarFile = $eeClass->eeFILE_ArchiveFile($sqlFile); // Make a tar.gz file
				
				if( is_readable($tarFile) ) {
					
					$eeFileList[] = $tarFile;
					$eeCleanup[] = $tarFile;
					
				} else {
					
					$eeLog['Errors'][] = 'ERROR: Bad Backup File: ' . $sqlFile;
				}
			}
		}
		
		
		// Dump database files
		if( count($eeFileList) ) {
			
			// Create a master ZIP file
			$zip = new ZipArchive();
			
			$zipName = SERVER . '-' . DB_USER . '_DB_Backups_' . date('Y-m-d') . '.zip';
			$zipFile = BACKUP_FOLDER . $zipName;
			
			// Create and open the archive 
			if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
			    
			    $eeLog['Errors'][] = "ERROR: Could Not Create ZIP: $zipFileName";
			
			} else {
				
				$eeLog['Notices'][] = "New Zip File Created: " . $zipFile;
				
				// Add each file in the file list to the archive
				foreach ($eeFileList as $eeFile) {
					
					if(is_readable($eeFile)) {
					
						if($zip->addFile($eeFile, basename($eeFile))) {
							
						} else {
							$eeLog['Errors'][] = "ERROR: Could Not Archive the File: " . $eeFile;
						}
						
					} else {
						$eeLog['Errors'][] = "ERROR: Could Not Read the Source File: " . $eeFile;
					}
				}
			}
			
			$zip->close();
			
			$eeBackupFile = $zipFile; // Full Path
		
		}
		
		
		// FTP, Email and Cleanup
		if(empty($eeLog['Errors'])) {
		
			$eeLog['Notices'][] = count($eeFileList) . ' Databases Backed Up';
			
			// FTP Mode?
			if($eeFTPResult) { // FTP it away
				try {
					$eeFTP = new eeFtpHandler(FTP_SERVER, FTP_USER, FTP_PASSWORD);
					
					if( $eeFTP->eePutFile( $eeBackupFile, FTP_REMOTE . basename($eeBackupFile) ) ) { // $eeBackupFile contains the full path
						$eeLog['Notices'][] = "FTP PUT SUCCESS";
					} else {
						$eeLog['Errors'][] = "ERROR - FTP PUT FAILED!";
					}
				} catch (Exception $eeEx) {
					$eeLog['Errors'][] = $eeEx->getMessage();
				}
			}
			
			
			// Email Mode?
			if($eeEmailResult) {
				
				// Check file size
				if(filesize($eeBackupFile) <= EMAIL_MaxAttachSize) {
					
					$eeLog['Notices'][] = "Sending Email with Zip File Attached";
					
					$eeBody = $eeSubject . PHP_EOL . PHP_EOL . print_r($eeLog, TRUE);
					
					if( !$eeClass->eeEmail_Mail($eeSubject, $eeBody, $eeBackupFile) ) {
					
						$eeLog['Errors'][] = 'Mail Send FAILED!';
					}
				
				} else {
					
					$eeLog['Errors'][] = "ERROR - ZIP File Too Large to Attach!" . PHP_EOL . PHP_EOL . $eeBody;
					$eeClass->eeEmail_Mail($eeSubject, $eeBody);
				}
			}
			
			// Clean Up - Delete the sql backup files
			if( count($eeCleanup) AND empty($eeLog['Errors']) ) {
				
				$eeLog['Notices'][] = 'Cleaning Up...';
				
				foreach($eeCleanup as $eeFile) {
					if(unlink($eeFile)) {
						$eeLog['Notices'][] = 'SQL File Deleted: ' . $eeFile;
					}
				}
			} else {
				$eeLog['Errors'][] = 'Not Cleaning Up!';
			}
			
			if($eeBackupFile AND $eeDeleteLocalBackup) {
				
				$eeLog['Notices'][] = 'Removing Local Backup...';
				
				if(unlink($eeBackupFile)) {
					$eeLog['Notices'][] = 'File Deleted: ' . $eeBackupFile;
				} else {
					$eeLog['Errors'][] = 'Cannot Delete Local Backup File';
				}
			}
		}
		
	} else {
		$eeLog['Errors'][] = 'No Databases Found';
	}
	
} else {
	$eeLog['Errors'][] = 'Class Not Found';
}

echo print_r($eeLog); // Output the log to the console

// End the Script, Define the Class ...
	
class eeBackupClass {
	
	// DATABASE METHODS ======================
	
	// Connect to the Database
	function eeDB_Connect() {
		
		$eeDBCx = new mysqli(DB_HOST, DB_USER, DB_PASSWORD);
		
		// Check Connection
		if ($eeDBCx->connect_error) {
			echo "DB Connection Failed: " . $eeDBCx->connect_error;
			die();
		} else {
			return $eeDBCx;
		}
	}
	
	// List all the Databases
	function eeDB_ListAllDatabases() {
		
		global $eeLog, $eeExcluded;
		
		$eeDBCx = $this->eeDB_Connect();
	
		$set = mysqli_query($eeDBCx, 'SHOW DATABASES;');
		$eeDatabases = array();
		
		while($db = mysqli_fetch_row($set)) {
		   
			if(strpos($db[0], '_')) { // Only backup account databases
			   
				   if(!in_array($db[0], $eeExcluded)) {
				   
					   $eeDatabases[] = $db[0];
					   $eeLog['Notices'][] = 'Database Added: ' . $db[0];
		   
				   } else {
			   
					   $eeLog['Notices'][] = 'Database Excluded: ' . $db[0];
				}
			}
		}
		return $eeDatabases;
	}
	
	
	// DB Check
	function eeDB_HasData($value) {
		if (is_array($value)) return (sizeof($value) > 0)? true : false;
		else return (($value != '') && (strtolower($value) != 'null') && (strlen(trim($value)) > 0)) ? true : false;
	}
	
	
	// Get The Database Data
	public function eeDB_DumpDatabase($database) {
		
		global $eeLog;
		
		if($this->eeDB_HasData($database)) {
		
			$dumpFile = BACKUP_FOLDER . $database . '_DB_BACKUP_' . date('Y-m-d') . '.sql';
			
			$command = 'mysqldump --single-transaction -u ' . DB_USER . ' --password=\'' . DB_PASSWORD . '\' --host=' . DB_HOST . ' ' . $database . ' > ' . $dumpFile;
			shell_exec($command);
			
			$command .= ' 2>&1'; // Redirect standard error to standard output
			$output = shell_exec($command);
			if ($output !== null && strpos($output, 'error') !== false) {
				$eeLog['Errors'][] = 'mysqldump error: ' . $output;
			}
			if(!is_readable($dumpFile)) {
				$eeLog['Errors'][]  = 'Cannot read the database dump file: ' . $dumpFile;
				return FALSE;
			} else {
				$eeLog['Notices'][] = "Database Dumped: " . basename($dumpFile) . ' (' . $this->eeFILE_BytesToSize(filesize($dumpFile)) . ')';
				return $dumpFile;
			}
		
		} else {
			return FALSE;
		}
		
	}
	
	// FILE METHODS =============
	
	// Write to GZ File 
	public function eeFILE_ArchiveFile($eeFile, $eeType = 'tar.gz') {
		
		$eePathParts = pathinfo($eeFile);
		
		$zipFile = BACKUP_FOLDER . $eePathParts['basename'] . '.' . $eeType;
		$command = 'tar -czf ' . $zipFile . ' ' . $eeFile;
		
		exec($command);
		
		if(!is_readable($zipFile)) {
			$errors[]  = 'Cannot read the compressed file: ' . $zipFile;
			return FALSE;
		} else {
			$log[] = "File Zipped: [ $zipFile ] <<< " . $this->eeFILE_BytesToSize(filesize($zipFile));
			return $zipFile;
		}
	}
	
	public function eeFILE_BytesToSize($bytes, $precision = 2) { // @ChatGPT
		$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		$factor = floor((strlen($bytes) - 1) / 3);
		
		if ($factor > count($units) - 1) {
			$factor = count($units) - 1;
		}
	
		return round($bytes / pow(1024, $factor), $precision) . ' ' . $units[$factor];
	}
	
	
	
	// Email Notices and Backup Files
	public function eeEmail_Mail($eeSubject, $eeBody, $eeFile = FALSE) {
		
		global $eeLog;
		
		if( $eeSubject && $eeBody ) {
			
			// Get PHPMailer
			require_once( __DIR__ . '/PHPMailer/src/PHPMailer.php' );
			require_once( __DIR__ . '/PHPMailer/src/Exception.php' );
			
			$mail = new PHPMailer(TRUE);
			
			try {
				
				//Recipients
				$mail->setFrom(EMAIL_FROM, 'EE Backups');
				$mail->addAddress(EMAIL_TO, 'EE Backups');
				$mail->addReplyTo(EMAIL_FROM, 'EE Backups');
				
				// Check if $eeFile is provided and exists, then attach
				if ($eeFile && file_exists($eeFile)) {
					$mail->addAttachment($eeFile, basename($eeFile));
				} else {
					throw new Exception("Attachment file not found: " . basename($eeFile));
				}
				
				// Content
				$mail->isHTML(false); // Set email format to plain text
				$mail->Subject = $eeSubject;
				$mail->Body    = $eeBody;
				
				$mail->send();
				
				$eeLog['Notices'][] = 'Message has been sent';
				
				return TRUE;
			
			} catch (Exception $e) {
				
				$eeLog['Errors'][] = "Message could not be sent. Mailer Error: " . $e->getMessage();
				
				return FALSE;
			}
			
		} else {
			
			$eeLog['Errors'][] = "Subject or Body not provided.";
			
			return FALSE;
		}
	}

}

class eeFtpHandler {
	
	private $eeConnection;
	
	public function __construct($eeServer, $eeUser, $eePassword, $eePort = 21, $eeTimeout = 90) {
		$this->eeConnection = ftp_connect($eeServer, $eePort, $eeTimeout);
		
		if (!$this->eeConnection) {
			throw new Exception('ERROR: Could not connect to ' . $eeServer);
		}
		
		$eeLoginResult = ftp_login($this->eeConnection, $eeUser, $eePassword);
		
		if (!$eeLoginResult) {
			throw new Exception('ERROR: Could not log in with provided credentials.');
		}
		
		ftp_pasv($this->eeConnection, true); // Passive mode
	}

	public function eePutFile($eeLocalFile, $eeRemoteFile) {
		
		global $eeLog;
		
		if (!file_exists($eeLocalFile)) {
			throw new Exception("ERROR: Local file $eeLocalFile does not exist.");
		}

		$eeUpload = ftp_put($this->eeConnection, $eeRemoteFile, $eeLocalFile, FTP_BINARY);
		
		if (!$eeUpload) {
			throw new Exception("ERROR: Could not upload $eeLocalFile to $eeRemoteFile.");
		}
		
		return true;
	}

	public function __destruct() {
		if ($this->eeConnection) {
			ftp_close($this->eeConnection);
		}
	}
}


?>
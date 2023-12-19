<?php // Mitchell Bennis (mitch@elementengage.com)
// Version 1.2 -- Rev 12.19.23
// PHP 8 Approved

// Modify the values below as needed.

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

// Local Configuration
DEFINE('SERVER', 'eeDotNet'); // The name of this server
DEFINE('BACKUP_FOLDER', __DIR__ . '/backup_files/'); // Local Backup Directory

// Email Configuration
DEFINE('EMAIL_TO', 'me@my-address.com');
DEFINE('EMAIL_FROM', 'mail@this-server.com');
DEFINE('EMAIL_MaxAttachSize', 20971520); // Files over 20 MB will not be sent
$eeSubject = SERVER . ' Backup Job: ' . date('m-d-Y');

// Amazon S3 Configuration- Coming Soon


?>
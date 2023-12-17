<?php // Mitchell Bennis (mitch@elementengage.com)
// Version 1.1 -- Rev 12.17.23
// PHP 8 Approved

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


?>
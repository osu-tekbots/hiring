<?php
/**
 * This page contains bootstraping logic required on all pages. It should be included at the very top of every file in
 *  the `/pages` directory.
 */


define('PUBLIC_FILES', __DIR__);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include PUBLIC_FILES . '/lib/autoload.php';     /* Auto-load our custom classes */
include PUBLIC_FILES . '/vendor/autoload.php';  /* Auto-load libraries from Composer */

// Load configuration
$configManager = new Util\ConfigManager(PUBLIC_FILES);

// Create logger
try {
    $logFileName = $configManager->getLogFilePath() . date('MY') . ".log";
    $logger = new Util\Logger($logFileName, $configManager->getLogLevel());
} catch (\Exception $e) {
    $logger = null;
}

// Add handlers for uncaught errors/exceptions
include PUBLIC_FILES . '/lib/handleUncaught.php';

// Connect to database
try {
    $dbConn = DataAccess\DatabaseConnection::FromConfig($configManager->getDatabaseConfig());
} catch (\Exception $e) {
    echo 'There is an irresolvable issue with our database connection right now. Please try again later.';
    $logger->info('Sending HTTP response: 500: Database connection failed');
    exit(0);
}

// Set $_SESSION variables to be for this site
include PUBLIC_FILES . '/lib/authenticate.php';

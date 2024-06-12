<?php
/**
 * This script handles client requests to modify or fetch MODEL_NAME-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\UserDao;
use Api\UserActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$userDao = new UserDao($dbConn, $logger);
$handler = new UserActionHandler($userDao, $configManager, $logger);

// Request authentication happens in ActionHandler methods
$handler->handleRequest();

?>
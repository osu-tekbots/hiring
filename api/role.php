<?php
/**
 * This script handles client requests to modify or fetch Role-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\PositionDao;
use DataAccess\RoleDao;
use DataAccess\UserDao;
use DataAccess\MessageDao;
use Api\RoleActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$positionDao = new PositionDao($dbConn, $logger);
$roleDao = new RoleDao($dbConn, $logger);
$userDao = new UserDao($dbConn, $logger);
$messageDao = new MessageDao($dbConn, $logger);
$handler = new RoleActionHandler($positionDao, $roleDao, $userDao, $messageDao, $configManager, $logger);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
<?php
/**
 * This script handles client requests to modify or fetch Position-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Set up our data access and handler classes
use DataAccess\PositionDao;
use DataAccess\RoleDao;
use Api\PositionActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$positionDao = new PositionDao($dbConn, $logger);
$roleDao = new RoleDao($dbConn, $logger);
$handler = new PositionActionHandler($positionDao, $roleDao, $logger);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
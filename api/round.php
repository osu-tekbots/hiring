<?php
/**
 * This script handles client requests to modify or fetch Round-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\RoundDao;
use DataAccess\FeedbackFileDao;
use Api\RoundActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$roundDao = new RoundDao($dbConn, $logger);
$feedbackFileDao = new FeedbackFileDao($dbConn, $logger);
$handler = new RoundActionHandler($roundDao, $feedbackFileDao, $configManager, $logger);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
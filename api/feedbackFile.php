<?php
/**
 * This script handles client requests to modify or fetch FeedbackFile-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\DatabaseConnection;
use DataAccess\FeedbackFileDao;
use DataAccess\FeedbackDao;
use DataAccess\RoundDao;
use Api\FeedbackFileActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$feedbackFileDao = new FeedbackFileDao($dbConn, $logger);
$feedbackDao = new FeedbackDao($dbConn, $logger);
$roundDao = new RoundDao($dbConn, $logger);
$handler = new FeedbackFileActionHandler($feedbackFileDao, $feedbackDao, $roundDao, $logger, $configManager);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
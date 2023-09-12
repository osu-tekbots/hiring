<?php
/**
 * This script handles client requests to modify or fetch Feedback-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\FeedbackDao;
use DataAccess\FeedbackForQualDao;
use DataAccess\RoundDao;
use Api\FeedbackActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$feedbackDao = new FeedbackDao($dbConn, $logger);
$ffqDao = new FeedbackForQualDao($dbConn, $logger);
$roundDao = new RoundDao($dbConn, $logger);
$handler = new FeedbackActionHandler($feedbackDao, $ffqDao, $roundDao, $logger);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
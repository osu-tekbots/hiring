<?php
/**
 * This script handles client requests to send emails. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\CandidateDao;
use DataAccess\PositionDao;
use Api\EmailActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$candidateDao = new CandidateDao($dbConn, $logger);
$positionDao = new PositionDao($dbConn, $logger);
$handler = new EmailActionHandler($candidateDao, $positionDao, $logger);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
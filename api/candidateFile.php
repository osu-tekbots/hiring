<?php
/**
 * This script handles client requests to modify or fetch Candidate File-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\DatabaseConnection;
use DataAccess\CandidateFileDao;
use DataAccess\CandidateDao;
use Api\CandidateFileActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$candidateFileDao = new CandidateFileDao($dbConn, $logger);
$candidateDao = new CandidateDao($dbConn, $logger);
$handler = new CandidateFileActionHandler($candidateFileDao, $candidateDao, $logger, $configManager);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
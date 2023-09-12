<?php
/**
 * This script handles client requests to modify or fetch QualificationForRound-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\QualificationForRoundDao;
use DataAccess\QualificationDao;
use DataAccess\PositionDao;
use Api\QualificationForRoundActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$qualificationForRoundDao = new QualificationForRoundDao($dbConn, $logger);
$qualificationDao = new QualificationDao($dbConn, $logger);
$positionDao = new PositionDao($dbConn, $logger);
$handler = new QualificationForRoundActionHandler($qualificationForRoundDao, $qualificationDao, $positionDao, $logger);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
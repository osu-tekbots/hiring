<?php
/**
 * This script handles client requests to modify or fetch Message-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\DatabaseConnection;
use DataAccess\MessageDao;
use Api\MessageActionHandler;
use Api\Response;
use Email\HiringMailer;

if(!session_id()) {
    session_start();
}

$messageDao = new MessageDao($dbConn, $logger);
$hiringMailer = new HiringMailer($configManager->getAdminEmail(), $configManager->getAdminEmail(), $configManager->getAdminEmailTag(), $logger);
$handler = new MessageActionHandler($messageDao, $hiringMailer, $logger);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    // Tell the user they're not logged in
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
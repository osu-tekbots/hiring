<?php
// REMOVE BELOW
/**
 * This is a template for our API endpoint logic. Duplicate this script and replace __ModelName__ and __modelName__ with 
 * the name of the model you are creating an API endpoint for.
 * NOTE: Use a case-sensitive replacement to preserve proper casing for variables and classes.
 */
die(); 
// REMOVE UNTIL HERE

/**
 * This script handles client requests to modify or fetch __ModelName__-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Setup our data access and handler classes
use DataAccess\DatabaseConnection;
use DataAccess\__ModelName__Dao;
use Api\__ModelName__ActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$__modelName__Dao = new __ModelName__Dao($dbConn, $logger);
$handler = new __ModelName__ActionHandler($__modelName__Dao, $logger);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    // Tell the user they're not signed in
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
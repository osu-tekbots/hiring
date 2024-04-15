<?php
/**
 * This script handles client requests to modify or fetch Position-related data. All requests made to this script should be a 
 * POST request with a corresponding `action` field in the request body.
 */

include_once '../bootstrapApi.php';

// Set up our data access and handler classes
use DataAccess\PositionDao;
use DataAccess\CandidateDao;
use DataAccess\CandidateFileDao;
use DataAccess\CandidateRoundNoteDao;
use DataAccess\QualificationDao;
use DataAccess\QualificationForRoundDao;
use DataAccess\RoundDao;
use DataAccess\RoleDao;
use DataAccess\FeedbackDao;
use DataAccess\FeedbackForQualDao;
use DataAccess\FeedbackFileDao;
use DataAccess\UserDao;
use DataAccess\MessageDao;
use Email\HiringMailer;
use Api\PositionActionHandler;
use Api\Response;

if(!session_id()) {
    session_start();
}

$positionDao = new PositionDao($dbConn, $logger);
$candidateDao = new CandidateDao($dbConn, $logger);
$candidateFileDao = new CandidateFileDao($dbConn, $logger);
$candidateRoundNoteDao = new CandidateRoundNoteDao($dbConn, $logger);
$qualificationDao = new QualificationDao($dbConn, $logger);
$qualificationForRoundDao = new QualificationForRoundDao($dbConn, $logger);
$roundDao = new RoundDao($dbConn, $logger);
$roleDao = new RoleDao($dbConn, $logger);
$feedbackDao = new FeedbackDao($dbConn, $logger);
$ffqDao = new FeedbackForQualDao($dbConn, $logger);
$feedbackFileDao = new FeedbackFileDao($dbConn, $logger);
$userDao = new UserDao($dbConn, $logger);
$messageDao = new MessageDao($dbConn, $logger);
$hiringMailer = new HiringMailer($configManager->getAdminEmail(), $configManager->getBounceEmail(), $configManager->getAdminEmailTag(), $logger);
$handler = new PositionActionHandler($positionDao, $candidateDao, $candidateFileDao, $candidateRoundNoteDao, $qualificationDao, $qualificationForRoundDao, $roundDao, $roleDao, $feedbackDao, $ffqDao, $feedbackFileDao, $userDao, $messageDao, $hiringMailer, $configManager, $logger);

// Ensure the user is logged in
if (verifyPermissions(['user', 'admin'])) {
	$handler->handleRequest();
} else {
    $handler->respond(new Response(Response::UNAUTHORIZED, 'You do not have permission to access this resource. Do you need to re-login?'));
}

?>
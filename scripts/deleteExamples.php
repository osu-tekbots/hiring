<?php

include_once "../bootstrap.php";

use Api\PositionActionHandler;
use DataAccess\CandidateDao;
use DataAccess\CandidateFileDao;
use DataAccess\FeedbackFileDao;
use DataAccess\PositionDao;
use DataAccess\QualificationDao;
use DataAccess\RoundDao;

$candidateDao = new CandidateDao($dbConn, $logger);
$candidateFileDao = new CandidateFileDao($dbConn, $logger);
$feedbackFileDao = new FeedbackFileDao($dbConn, $logger);
$positionDao = new PositionDao($dbConn, $logger);$positionDao = new PositionDao($dbConn, $logger);
$qualificationDao = new QualificationDao($dbConn, $logger);
$roundDao = new RoundDao($dbConn, $logger);


$positions = $positionDao->getExamplePositions();

function deletePosition(
    \Model\Position              $position, 
    \DataAccess\CandidateDao     $candidateDao,
    \DataAccess\CandidateFileDao $candidateFileDao,
    \DataAccess\FeedbackFileDao  $feedbackFileDao,
    \DataAccess\PositionDao      $positionDao,
    \DataAccess\QualificationDao $qualificationDao,
    \DataAccess\RoundDao         $roundDao,
    \Util\ConfigManager          $configManager,
    \Util\Logger                 $logger
): bool {
    // Verify that the position's an example position & can be deleted on a whim (doesn't legally need to persist)
    if(!$position->getIsExample()) {
        $logger->error('Tried to delete a non-example position: '.$position->getID());
        return false;
    }


    // Delete all candidates
    $candidates = $candidateDao->getCandidatesByPositionId($position->getID());
    foreach($candidates as $candidate) {
        // Delete all files from the server
        $files = $feedbackFileDao->getAllFilesForCandidate($candidate->getID());
        foreach($files as $file) {
            try {
                $ok = unlink($configManager->getPrivateFilesDirectory()."/uploads/feedback/".$file->getFileName());
                if(!$ok) {
                    throw new \Exception('feedback unlink() failed');
                }
            } catch (\Exception $e) {
                $logger->error('Failed to remove FeedbackFile from server: ' . $e->getMessage());
                return false;
            }
        }
        $files = $candidateFileDao->getAllFilesForCandidate($candidate->getID());
        foreach($files as $file) {
            try {
                $ok = unlink($configManager->getPrivateFilesDirectory()."/uploads/candidate/".$file->getFileName());
                if(!$ok) {
                    throw new \Exception('candidate unlink() failed');
                }
            } catch (\Exception $e) {
                $logger->error('Failed to remove CandidateFile from server: ' . $e->getMessage());
                return false;
            }
        }

        // Delete the candidate and all associated data
        $ok = $candidateDao->deleteCandidate($candidate->getID());
        if(!$ok) {
            $logger->error('Failed to Delete a Candidate');
            return false;
        }
    }

    // Delete all qualifications
    $qualifications = $qualificationDao->getQualificationsForPosition($position->getID());
    foreach($qualifications as $qualification) {
        // Delete everything tied to the qualification
        $ok = $qualificationDao->deleteQualification($qualification->getID());
        
        // Verify the deletion succeeded
        if(!$ok) {
            $logger->error('Failed to Delete a Qualification');
            return false;
        }
    }

    // Delete all rounds
    $rounds = $roundDao->getAllRoundsByPositionId($position->getID());
    foreach($rounds as $round) {
        // Delete all files from the server
        $files = $feedbackFileDao->getAllFilesForRound($round->getID());
        foreach($files as $file) {
            try {
                $ok = unlink($configManager->getPrivateFilesDirectory()."/uploads/feedback/".$file->getFileName());
                if(!$ok) {
                    throw new \Exception('feedback unlink() failed');
                }
            } catch (\Exception $e) {
                $logger->error('Failed to remove FeedbackFile from server: ' . $e->getMessage());
                return false;
            }
        }

        // Delete everything associated with the round
        $ok = $roundDao->deleteRound($round->getID());
        
        if(!$ok) {
            $logger->error('Failed to Delete a Round');
        }
    }

    // Delete the position itself
    $ok = $positionDao->deletePosition($position->getID());
    if(!$ok) {
        $logger->error('Position Not Deleted');
    }

    return true;
}

foreach($positions as $position) {
    $twoWeeks = new DateTime("2 weeks ago");

    if($position->getDateCreated() < $twoWeeks) {
        $result = deletePosition($position, $candidateDao, $candidateFileDao, $feedbackFileDao, $positionDao, $qualificationDao, $roundDao, $configManager, $logger);

        if($result)
            $logger->trace('Deleted '.$position->getID().' ('.$position->getTitle().')');
    }
}  
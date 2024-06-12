<?php
namespace Api;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Model\User;
use Model\Position;
use Model\QualificationForRound;
use DataAccess\PositionDao;
use DataAccess\RoleDao;
use Email\HiringMailer;
use Email\Mailer;
use Util\IdGenerator;

/**
 * Defines the logic for how to handle API requests made to modify Position information.
 */
class PositionActionHandler extends ActionHandler {

    /** @var \DataAccess\PositionDao */
    private $positionDao;

    /** @var \DataAccess\CandidateDao */
    private $candidateDao;

    /** @var \DataAccess\CandidateFileDao */
    private $candidateFileDao;

    /** @var \DataAccess\CandidateRoundNoteDao */
    private $candidateRoundNoteDao;

    /** @var \DataAccess\QualificationDao */
    private $qualificationDao;

    /** @var \DataAccess\QualificationForRoundDao */
    private $qualForRoundDao;

    /** @var \DataAccess\RoundDao */
    private $roundDao;

    /** @var \DataAccess\RoleDao */
    private $roleDao;

    /** @var \DataAccess\FeedbackDao */
    private $feedbackDao;

    /** @var \DataAccess\FeedbackForQualDao */
    private $ffqDao;

    /** @var \DataAccess\FeedbackFileDao */
    private $feedbackFileDao;

    /** @var \DataAccess\UserDao */
    private $userDao;

    /** @var \DataAccess\MessageDao */
    private $messageDao;

    /** @var \Email\HiringMailer */
    private $hiringMailer;

    /** @var \Util\ConfigManager */
    private $configManager;
	
    /**
     * Constructs a new instance of the Position action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\PositionDao $positionDao The class for accessing the Position database table
     * @param \DataAccess\CandidateDao $candidateDao The class for accessing the Candidate database table
     * @param \DataAccess\CandidateFileDao $candidateFileDao The class for accessing the CandidateFile database table
     * @param \DataAccess\CandidateRoundNoteDao $candidateRoundNoteDao The class for accessing the CandidateRoundNote database table
     * @param \DataAccess\QualificationDao $qualificationDao The class for accessing the Qualification database table
     * @param \DataAccess\RoundDao $roundDao The class for accessing the Round database table
     * @param \DataAccess\RoleDao $roleDao The class for accessing the Role database table
     * @param \DataAccess\FeedbackDao $feedbackDao The class for accessing the Feedback database table
     * @param \DataAccess\FeedbackForQualDao $ffqDao The class for accessing the FeedbackForQualification database table
     * @param \DataAccess\FeedbackFileDao $feedbackFileDao The class for accessing the FeedbackFile database table
     * @param \DataAccess\UserDao $userDao The class for accessing the User database table
     * @param \Util\ConfigManager $configManager The class for accessing information in `config.ini`
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct(
        \DataAccess\PositionDao              $positionDao, 
        \DataAccess\CandidateDao             $candidateDao, 
        \DataAccess\CandidateFileDao         $candidateFileDao, 
        \DataAccess\CandidateRoundNoteDao    $candidateRoundNoteDao, 
        \DataAccess\QualificationDao         $qualificationDao, 
        \DataAccess\QualificationForRoundDao $qualForRoundDao, 
        \DataAccess\RoundDao                 $roundDao, 
        \DataAccess\RoleDao                  $roleDao, 
        \DataAccess\FeedbackDao              $feedbackDao, 
        \DataAccess\FeedbackForQualDao       $ffqDao, 
        \DataAccess\FeedbackFileDao          $feedbackFileDao, 
        \DataAccess\UserDao                  $userDao, 
        \DataAccess\MessageDao               $messageDao,
        \Email\HiringMailer                  $hiringMailer,
        \Util\ConfigManager                  $configManager, 
        \Util\Logger                         $logger
    ) {
        parent::__construct($logger); 

		$this->positionDao = $positionDao;
        $this->candidateDao = $candidateDao;
        $this->candidateFileDao = $candidateFileDao;
        $this->candidateRoundNoteDao = $candidateRoundNoteDao;
        $this->qualificationDao = $qualificationDao;
        $this->qualForRoundDao = $qualForRoundDao;
        $this->roundDao = $roundDao;
        $this->roleDao = $roleDao;
        $this->feedbackDao = $feedbackDao;
        $this->ffqDao = $ffqDao;
        $this->feedbackFileDao = $feedbackFileDao;
        $this->userDao = $userDao;
        $this->messageDao = $messageDao;
        $this->hiringMailer = $hiringMailer;
        $this->configManager = $configManager;
    }

    /**
     * Creates the Position state.
     * 
     * @param string title Must exist in the POST request body.
     * @param string postingLink May exist in the POST request body.
     * @param string email May exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleCreatePosition() {
        // Ensure the required parameters exist
        $this->requireParam('title');

        $body = $this->requestBody;

        // Create a new position object with the given information
        $position = new Position();
        $position->setTitle($body['title']);
        $position->setPostingLink($body['postingLink']);
        $position->setDateCreated(new \DateTime());
        $position->setCommitteeEmail($body['email']);
        $position->setStatus('Requested');
        $position->setIsExample(false);

        // Store the position object in the database
		$ok = $this->positionDao->createPosition($position, $_SESSION['userID']);
        if($ok===false) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not created'));
        }

        // Get the Search Chair role and assign it for the person who created the position
        $role = $this->roleDao->getRoleByName('Search Chair');
        if(!$role) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Search Chair role not found'));
        }
        $ok = $this->roleDao->addUserRoleForPosition($role->getID(), $_SESSION['userID'], $position->getID());
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Search Chair role not set'));
        }

        // Notify the admins that a position has been created
        $user = $this->userDao->getUserByID($_SESSION['userID']);
        $message = $this->messageDao->getMessageByID(6);
        $ok = $this->hiringMailer->sendPositionCreatedEmail($message, $user->getFirstName().' '.$user->getLastName(), $body['title'], $this->configManager);

        // Use Response object to send success
        $this->respond(new Response(Response::OK, 'Position created', $position->getID()));
    }


	/**
     * Updates the Position state.
     * 
     * @param string id Must exist in the POST request body.
     * @param string title Must exist in the POST request body.
     * @param string postingLink May exist in the POST request body.
     * @param string email May exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdatePosition() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $this->requireParam('title');

        $body = $this->requestBody;

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $body['id']);

        // Get and update the position
        $position = $this->positionDao->getPosition($body['id']);
        if(!$position) {
            $this->respond(new Response(Response::BAD_REQUEST, 'Position not found'));
        }
        $position->setTitle($body['title']);
        $position->setPostingLink($body['postingLink']);
        $position->setCommitteeEmail($body['email']);

        // Save the updated version in the database
		$ok = $this->positionDao->updatePosition($position);
        
        // Use Response object to send DAO action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not updated'));
        }
		$this->respond(new Response(Response::OK, 'Position updated'));
    }


	/**
     * Approves the Position state.
     * 
     * @param string id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleApprovePosition() {
        // Ensure the required parameters exist
        $this->requireParam('id');

        $body = $this->requestBody;

        // Check if the user is allowed to approve an instance
        $this->verifyUserRole('Admin', $body['id']); // 'Admin' isn't a valid option, so it'll only succeed if the user is an admin
        
        // Get and update the position
        $position = $this->positionDao->getPosition($body['id']);
        if(!$position) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not found'));
        }
        $position->setStatus('Open');
        $this->logger->info('Position '.$position->getID().' approved');

        // Store the updated version in the database
		$ok = $this->positionDao->updatePosition($position);
        
        // Use Response object to send DAO action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not approved'));
        }

        // Send an email to the Search Chair so they know their position is approved
        $message = $this->messageDao->getMessageByID(4);
        $searchChairRole = $this->roleDao->getRoleByName('Search Chair');
        $searchChairs = $this->roleDao->getUsersByPositionRole($position->getID(), $searchChairRole);
        if(!$searchChairs) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Search Chair not found'));
        }
        foreach($searchChairs as $searchChair) {
            $ok = $this->hiringMailer->sendPositionApprovedEmail($searchChair->getUser(), $message, 'https://eecs.engineering.oregonstate.edu/education/hiring/user/updatePosition.php?id='.$position->getID());
            
            if(!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to email Search Chair'));
            }
        }

		$this->respond(new Response(Response::OK, 'Position approved'));
    }

	/**
     * Changes the Position state to start interviewing.
     * 
     * @param string id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleStartInterviewing() {
        // Ensure the required parameters exist
        $this->requireParam('id');

        $body = $this->requestBody;

        // Check if the user is allowed to start interviewing
        $this->verifyUserRole('Search Chair', $body['id']);
        
        // Get the position & make sure it's at a valid state
        $position = $this->positionDao->getPosition($body['id']);
        if(!$position) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not found'));
        }
        if($position->getStatus() != 'Open' && $position->getStatus() != 'Completed') {
            $this->respond(new Response(Response::UNAUTHORIZED, 'Access Denied'));
        }
        
        // Update the position
        $position->setStatus('Interviewing');
        $this->logger->info(var_export($position, true));

        // Save the updated version in the database
		$ok = $this->positionDao->updatePosition($position);
        
        // Use Response object to send DAO action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not updated'));
        }
		$this->respond(new Response(Response::OK, 'Position marked as Interviewing'));
    }

	/**
     * Changes the Position state to completed after a candidate has been hired.
     * 
     * @param string id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handlePositionCompleted() {
        // Ensure the required parameters exist
        $this->requireParam('id');

        $body = $this->requestBody;

        // Check if the user is allowed to mark the position as completed
        $this->verifyUserRole('Search Chair', $body['id']);
        
        // Get the position & make sure it's at a valid state
        $position = $this->positionDao->getPosition($body['id']);
        if(!$position) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not found'));
        }
        if($position->getStatus() != 'Interviewing') {
            $this->respond(new Response(Response::UNAUTHORIZED, 'Access Denied'));
        }
        
        // Update the position
        $position->setStatus('Completed');
        $this->logger->info(var_export($position, true));

        // Save the updated version in the database
		$ok = $this->positionDao->updatePosition($position);
        
        // Use Response object to send DAO action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not updated'));
        }
		$this->respond(new Response(Response::OK, 'Position marked as Completed'));
    }

    /**
     * Duplicates the example position for the user to learn the tool's capabilities
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleGetExample() {
        $examplePosition = $this->positionDao->getPosition('examplePosition');
        $exampleCands = $this->candidateDao->getCandidatesByPositionId($examplePosition->getID());
        $exampleQuals = $this->qualificationDao->getQualificationsForPosition($examplePosition->getID());
        $exampleRounds = $this->roundDao->getAllRoundsByPositionId($examplePosition->getID());
        $exampleQualForRounds = $this->qualForRoundDao->getAllQualForRoundsForPosition($examplePosition->getID());

        $newID = IdGenerator::generateSecureUniqueId();

        $examplePosition->setID($newID);
        $examplePosition->setIsExample(true);
        $examplePosition->setDateCreated(new \DateTime());
        
        /* Create a new position with the same data */
        $this->positionDao->createPosition($examplePosition, $_SESSION['userID']);

        /* Set the current user as the new position's Search Chair */
        $role = $this->roleDao->getRoleByName('Search Chair');
        if(!$role) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Search Chair role not found'));
        }
        $ok = $this->roleDao->addUserRoleForPosition($role->getID(), $_SESSION['userID'], $newID);
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Search Chair role not set'));
        }

        $qualificationIDs = [];
        $roundIDs = [];

        /* Duplicate candidates, qualifications, and rounds for the new position */
        foreach($exampleCands as $candidate) {
            $candidate->setPositionID($newID);
            $candidate->setID(IdGenerator::generateSecureUniqueId());
            $this->candidateDao->createCandidate($candidate);
        }
        foreach($exampleQuals as $qualification) {
            $oldID = $qualification->getID();
            $qualification->setPositionID($newID);
            $qualification->setID(IdGenerator::generateSecureUniqueId());
            $this->qualificationDao->createQualification($qualification);

            $qualificationIDs[$oldID] = $qualification->getID();
        }
        foreach($exampleRounds as $round) {
            $oldID = $round->getID();
            $round->setPositionID($newID);
            $round->setID(IdGenerator::generateSecureUniqueId());
            $this->roundDao->createRound($round);

            $roundIDs[$oldID] = $round->getID();
        }

        /* Link qualifications and rounds for the new position */
        foreach($exampleQualForRounds as $qualForRound) {
            $newQualForRound = new QualificationForRound($roundIDs[$qualForRound->getRoundID()], $qualificationIDs[$qualForRound->getQualificationID()]);
            $this->qualForRoundDao->createQualificationForRound($newQualForRound);
        }

        if(!$newID) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Example Position Not Created'));
        }
        $this->respond(new Response(Response::OK, 'Example Position Created', $newID));
    }
    
    /**
     * Verifies that the position is an example and deletes it
     * 
     * @param string id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleDeleteExample() {
        $this->requireParam('id');

        $body = $this->requestBody;

        // Check if the user is allowed to delete the position
        $this->verifyUserRole('Search Chair', $body['id']);

        // Get the position
        $position = $this->positionDao->getPosition($body['id']);
        if(!$position) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not found'));
        }

        // Verify that the position's an example position & can be deleted on a whim (doesn't legally need to persist)
        if(!$position->getIsExample()) {
            $this->respond(new Response(Response::UNAUTHORIZED, 'Access Denied'));
        }

        // Delete all candidates
        $candidates = $this->candidateDao->getCandidatesByPositionId($body['id']);
        foreach($candidates as $candidate) {
            // Delete all files from the server
            $files = $this->feedbackFileDao->getAllFilesForCandidate($candidate->getID());
            foreach($files as $file) {
                try {
                    $ok = unlink($this->configManager->getPrivateFilesDirectory()."/uploads/feedback/".$file->getFileName());
                    if(!$ok) {
                        throw new \Exception('feedback unlink() failed');
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Failed to remove FeedbackFile from server: ' . $e->getMessage());
                    $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Files Not Deleted'));
                }
            }
            $files = $this->candidateFileDao->getAllFilesForCandidate($candidate->getID());
            foreach($files as $file) {
                try {
                    $ok = unlink($this->configManager->getPrivateFilesDirectory()."/uploads/candidate/".$file->getFileName());
                    if(!$ok) {
                        throw new \Exception('candidate unlink() failed');
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Failed to remove CandidateFile from server: ' . $e->getMessage());
                    $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Files Not Deleted'));
                }
            }
    
            // Delete the candidate and all associated data
            $ok = $this->candidateDao->deleteCandidate($candidate->getID());
            if(!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to Delete a Candidate'));
            }
        }

        // Delete all qualifications
        $qualifications = $this->qualificationDao->getQualificationsForPosition($body['id']);
        foreach($qualifications as $qualification) {            
            // Check if the user is allowed to delete an instance
            $this->verifyUserRole('Search Chair', $qualification->getPositionID());

            // Delete everything tied to the qualification
            $ok = $this->qualificationDao->deleteQualification($qualification->getID());
            
            // Use Response object to send DAO action results
            if(!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to Delete a Qualification'));
            }
        }

        // Delete all rounds
        $rounds = $this->roundDao->getAllRoundsByPositionId($body['id']);
        foreach($rounds as $round) {
            // Delete all files from the server
            $files = $this->feedbackFileDao->getAllFilesForRound($round->getID());
            foreach($files as $file) {
                try {
                    $ok = unlink($this->configManager->getPrivateFilesDirectory()."/uploads/feedback/".$file->getFileName());
                    if(!$ok) {
                        throw new \Exception('feedback unlink() failed');
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Failed to remove FeedbackFile from server: ' . $e->getMessage());
                    $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'File Not Deleted'));
                }
            }
    
            // Delete everything associated with the round
            $ok = $this->roundDao->deleteRound($round->getID());
            
            if(!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to Delete a Round'));
            }
        }

        // Delete the position itself
        $ok = $this->positionDao->deletePosition($body['id']);
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position Not Deleted'));
        }

        $this->respond(new Response(Response::OK, 'Example Position Deleted'));
    }

    /**
     * Sends all Position information in an email to the current user.
     * 
     * @param string id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleEmailPosition() {
        // Ensure the required parameters exist
        $this->requireParam('id');

        $body = $this->requestBody;

        // Check if the user is allowed to save all the data
        $this->verifyUserRole('Search Chair', $body['id']);
        
        // Get the position
        $position = $this->positionDao->getPosition($body['id']);
        if(!$position) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not found'));
        }
        // Get the user
        $user = $this->userDao->getUserByID($_SESSION['userID']);
        // Get the info tied to the position
        $candidates = $this->candidateDao->getCandidatesByPositionId($body['id']);
        $qualifications = $this->qualificationDao->getQualificationsForPosition($body['id']);
        $rounds = $this->roundDao->getAllRoundsByPositionId($body['id']);
        $members = $this->roleDao->getAllPositionMembers($body['id']);

        $attachments = array();

        // Generate the email data & add attachments as we go
        $message = '
            Position Information:<br>
            &emsp;Position Title: '.$position->getTitle().'<br>
            &emsp;Current Status: '.$position->getStatus().'<br>
            &emsp;Internal Posting Link: '.$position->getPostingLink().'<br>
            &emsp;Committee Email Address: '.$position->getCommitteeEmail().'<br>
            &emsp;Date Created: '.$position->getDateCreated()?->format('l, m/d/Y \a\t H:i:s T').'<br>
            <br>
            <hr>
            <br>
            Candidates:<br>
        ';
        // Candidate data
        foreach($candidates as $candidate) {
            $candidateStatus = $candidate->getCandidateStatus();
            $candidateFiles = $this->candidateFileDao->getAllFilesForCandidate($candidate->getID());
            $message .= '
                &emsp;Name: '.$candidate->getFirstName().' '.$candidate->getLastName().'<br>
                &emsp;Email: '.$candidate->getEmail().'<br>
                &emsp;Phone: '.$candidate->getPhoneNumber().'<br>
                &emsp;Location: '.$candidate->getLocation().'<br>
                &emsp;Application Date: '.$candidate->getDateApplied()?->format('l, m/d/Y').'<br>
                &emsp;Files:<br>
                ';
            foreach($candidateFiles as $candidateFile) {
                $message .= '
                    &emsp;&emsp;'.$candidate->getLastName().'_'.$candidateFile->getPurpose().' (Added on: '.$candidateFile->getDateCreated()->format('l, m/d/Y \a\t H:i:s T').')<br>
                ';
                $attachments[] = array(
                    'address' => PUBLIC_FILES.'/uploads/candidate/'.$candidateFile->getFileName(),
                    'name' => $candidate->getLastName() . '_' . $candidateFile->getPurpose()
                );
            }
            if($candidateStatus) {
                $deciderUser = $this->userDao->getUserByID($candidateStatus->getUserID());
                $deciderName = $deciderUser->getFirstName() . ' ' . $deciderUser->getLastName();
                $message .= '
                    &emsp;Status: '.$candidateStatus->getName().'<br>
                    &emsp;&emsp;Decided by: '.$deciderName.' '.'('.$candidateStatus->getResponsiblePartyDescription().')<br>
                    &emsp;&emsp;Specific Disposition Reason: '.$candidateStatus->getSpecificDispositionReason().'<br>
                    &emsp;&emsp;Comments: '.$candidateStatus->getComments().'<br>
                    &emsp;&emsp;Notified Via: '.$candidateStatus->getHowNotified().'<br>
                    &emsp;&emsp;Date Decided: '.$candidateStatus->getDateDecided()->format('l, m/d/Y \a\t H:i:s T').'<br>
                ';
            }

            foreach($rounds as $index=>$round) {
                $roundQuals = $this->qualificationDao->getQualificationsForRound($round->getID());
                $msgComponent = '';
                $hasData = false;
                $msgComponent .= '&emsp;<table style="border: 1px solid; width: 80%; margin-left: 20px; border-collapse: collapse">
                    <tr>
                        <th colspan="100%" style="border: 1px solid;">'.($index + 1).': '.$round->getName().'</th>
                    </tr>
                    <tr">
                        <th style="border: 1px solid;">Qualification</th>
                ';
                foreach($members as $member) {
                    $msgComponent .= '<th style="border: 1px solid;">'.$member->getUser()->getFirstName().' '.$member->getUser()->getLastName().'</th>'."\n";
                }
                $msgComponent .= '</tr>'."\n";

                // Output each qualification row in table
                foreach($roundQuals as $qualification) {
                    $msgComponent .= '<tr">'."\n";

                    $msgComponent .= '<td style="border: 1px solid;">
                        <span>
                            '.$qualification->getDescription().'
                        </span></td>
                    ';
                    foreach($members as $member) {
                        $rating = $this->ffqDao->getQualStatusName($member->getUser()->getID(), $candidate->getID(), $round->getID(), $qualification->getID());
                        $msgComponent .= '<td style="border: 1px solid;">'.($rating ? $rating : '--').'</td>'."\n";
                        if($rating) $hasData = true;
                    }

                    $msgComponent .= '</tr>'."\n";
                }
                
                // Output notes row in table
                $msgComponent .= '<tr>
                                <td style="border: 1px solid;">Notes</td>
                ';
                foreach($members as $member) {
                    $feedback = $this->feedbackDao->getFeedbackForUser($member->getUser()->getID(), $candidate->getID(), $round->getID());
                    if($feedback) {
                        $feedbackFiles = $this->feedbackFileDao->getAllFilesForFeedback($feedback->getID());
                        $msgComponent .= '<td style="border: 1px solid;">'.$feedback->getNotes()."\n";
                        foreach($feedbackFiles as $feedbackFile) {
                            $msgComponent .= '<br><a target="_blank" href="uploads/feedback/'.$feedbackFile->getFileName().'">'.$feedbackFile->getFileName().'</a>'."\n";
                            $attachments[] = array(
                                'address' => PUBLIC_FILES.'/uploads/feedback/'.$feedbackFile->getFileName(),
                                'name' => $feedbackFile->getFileName()
                            );
                            $hasData = true;
                        }
                        $msgComponent .= '</td>'."\n";
                    } else {
                        $msgComponent .= '<td style="border: 1px solid;"></td>'."\n";
                    }
                }
                $msgComponent .= '</tr>'."\n";

                // Output group notes row in table
                $roundNote = $this->candidateRoundNoteDao->getCandidateNotesForRound($candidate->getID(), $round->getID());
                $msgComponent .= '<tr>
                                <td style="border: 1px solid;">Group Notes</td>
                                <td colspan="100%" style="border: 1px solid;">'.($roundNote ? $roundNote->getNotes() : '').'</td>
                            </tr>'."\n";
                if($roundNote) $hasData = true;

                $msgComponent .= '</table>'."\n";
                
                /* Only add the table if there's actual data in it */
                if($hasData) {
                    $message .= $msgComponent;
                }
            }

            $message .= '
                <br>
            ';
        }
        $message .= '
            <hr>
            <br>
            Qualifications:<br>
        ';
        // Qualification data
        foreach($qualifications as $qualification) {
            $message .= '
                &emsp;Description: '.$qualification->getDescription().'<br>
                &emsp;Screening Criteria: '.$qualification->getScreeningCriteria().'<br>
                &emsp;Strength Indicators: '.$qualification->getStrengthIndicators().'<br>
                &emsp;Level: '.$qualification->getLevel().'<br>
                &emsp;Priority: '.$qualification->getPriority().'<br>
                &emsp;Tranferable: '.($qualification->getTransferable() ? 'Yes' : 'No').'<br>
                &emsp;Date Created: '.$qualification->getDateCreated()->format('l, m/d/Y \a\t H:i:s T').'<br>
                <br>
            ';
        }
        $message .= '
            <hr>
            <br>
            Rounds:<br>
        ';
        // Round data
        foreach($rounds as $round) {
            $message .= '
                &emsp;Name: '.$round->getName().'<br>
                &emsp;Interview Questions: <a href="'.$round->getInterviewQuestionLink().'">'.$round->getInterviewQuestionLink().'<a><br>
                &emsp;Date Created: '.$round->getDateCreated()->format('l, m/d/Y \a\t H:i:s T').'<br>
                <br>';
        }
        $message .= '
            <hr>
            <br>
            Committee Members:<br>
        ';
        // Member data
        foreach($members as $member) {
            $message .= '
                &emsp;'.$member->getUser()->getFirstName().' '.$member->getUser()->getLastName().' ('.$member->getRole()->getName().')<br>
            ';
        }
        $message .= '
            <br>
            <hr>
        ';
        

        $mailer = new \Email\NewMailer($this->configManager->getAdminEmail(), $this->configManager->getBounceEmail(), null, $this->logger);

        $ok = $mailer->sendEmail($user->getEmail(), 'Search Committee Export', $message, true, null, $attachments);
        
        // Use Response object to send email action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Data Not Exported'));
        }
		$this->respond(new Response(Response::OK, 'Data Emailed To You'));
    }

    /**
     * Generates a .xlsx sheet with the information needed for the HR Disposition Worksheet
     * 
     * @param string id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleExportDisposition() {
        // Ensure the required parameters exist
        $this->requireParam('id');

        $body = $this->requestBody;

        // Check if the user is allowed to export the disposition info
        $this->verifyUserRole('Search Chair', $body['id']);
        $position = $this->positionDao->getPosition($body['id']);


        // Get the spreadsheet to generate
        $spreadsheet = IOFactory::load(PUBLIC_FILES . '/uploads/xlsx/Template-Disposition.xlsx');
        $activeWorksheet = $spreadsheet->getActiveSheet();

        // Get the info tied to the position
        $candidates = $this->candidateDao->getCandidatesByPositionId($body['id']);

        foreach($candidates as $index=>$candidate) {
            $row = $index + 2;

            $activeWorksheet->setCellValue('A'.$row, $candidate->getLastName());
            $activeWorksheet->setCellValue('B'.$row, $candidate->getFirstName());
            
            $candidateStatus = $candidate->getCandidateStatus();
            if($candidateStatus) {
                $deciderRole = $this->roleDao->getUserRoleForPosition($candidateStatus->getUserID(), $body['id'])->getRole();
                $deciderRoleName = $deciderRole->getName();
                $sheetRoleName = '';
                switch($deciderRoleName) {
                    case('Search Chair'):
                        $sheetRoleName = 'SC - Search chair/committee';
                        break;
                    default:
                        $sheetRoleName = $deciderRoleName;
                }

                $activeWorksheet->setCellValue('D'.$row, $candidateStatus->getName());
                $activeWorksheet->setCellValue('E'.$row, $candidateStatus->getSpecificDispositionReason());
                $activeWorksheet->setCellValue('F'.$row, $sheetRoleName);
                $activeWorksheet->setCellValue('G'.$row, $candidateStatus->getResponsiblePartyDescription());
                $activeWorksheet->setCellValue('H'.$row, $candidateStatus->getHowNotified());    
                $activeWorksheet->setCellValue('I'.$row, $candidateStatus->getComments());    
            }   
        }

        $escapedTitle = str_replace("/", "-", $position->getTitle());
        $filename = $escapedTitle.'-'.(new \DateTime())->format('m-d-H-i-s').'.xlsx';
        // Save the spreadsheet
        $writer = new Xlsx($spreadsheet);
        /* Make the client's browser download the sheet */
        $writer->save(PUBLIC_FILES . '/uploads/xlsx/'.$filename);
        // ob_end_clean();
        // header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment; filename="'. urlencode($escapedTitle).'"');
        // exit($writer->save('php://output'));

        /* Cyclical references can cause memory leaks unless cleared */
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $this->respond(new Response(Response::OK, 'File Created', 'uploads/xlsx/'.$filename));
    }

    /**
     * Sends an email to committee members that haven't started providing feedback for the 
     * given round
     * 
     * @param string roundID Must exist in the POST request body.
     * @param string candidateID Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleRemindCommittee() {
        // Ensure the required parameters exist
        $this->requireParam('roundID');
        $this->requireParam('candidateID');
        
        $body = $this->requestBody;

        // Get round from database
        $round = $this->roundDao->getRound($body['roundID']);
        if(!$round) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Round Not Found'));
        }

        // Check if the user is allowed to send a reminder
        $this->verifyUserRole('Search Chair', $round->getPositionID());

        // Get the committee members to remind
        $members = $this->roleDao->getAllPositionMembers($round->getPositionID());

        // Check who needs a reminder
        $membersToRemind = [];
        foreach($members as $member) {
            $feedback = $this->feedbackDao->getFeedbackForUser($_SESSION['userID'], $body['candidateID'], $round->getID());

            if(!$feedback) {
                $membersToRemind[] = $member;
            } else {
                $filledQuals = $this->ffqDao->getFeedbackForQualByFeedbackID($feedback->getID());
                if(!$filledQuals)
                    $membersToRemind[] = $member;
                // Enable this to check if the member has also completed ALL feedback, instead of just started
                /* $totalQuals = $qualForRoundDao->getAllQualificationsForRound($round->getID());
                if(count($totalQuals) != count($filledQuals)) {
                    $membersToRemind[] = $member;
                */
            }
            
        }

        // Send different response if no-one to remind
        if(!count($membersToRemind)) {
            $this->respond(new Response(Response::BAD_REQUEST, 'All members have provided feedback'));
        }
        
        // Get message to send
        $message = $this->messageDao->getMessageByID(5);
        if(!$message) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Message not found'));
        }

        // Get details for the email
        $searchChair = $this->userDao->getUserByID($_SESSION['userID']);
        $candidate = $this->candidateDao->getCandidateById($body['candidateID']);
        $position = $this->positionDao->getPosition($round->getPositionID());
        if(!$searchChair) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Search Chair not found'));
        }
        if(!$candidate) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate not found'));
        }
        if(!$position) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not found'));
        }
        $searchChairName = $searchChair->getFirstName().' '.$searchChair->getLastName();
        $candidateName = $candidate->getFirstName().' '.$candidate->getLastName();
        $positionName = $position->getTitle();

        // Send the emails
        $emailsSent = 0;
        foreach($membersToRemind as $member) {
            $ok = $this->hiringMailer->sendFeedbackReminderEmail($member->getUser(), $message, $searchChairName, $candidateName, $positionName);
            if(!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, "Email send failure: $emailsSent sent"));
            }
            $emailsSent++;
        }
        
        $this->respond(new Response(Response::OK, "$emailsSent emails sent"));
    }

	/**
     * Handles the HTTP request on the API resource. 
     * 
     * This effectively will invoke the correct action based on the `action` parameter value in the request body. If
     * the `action` parameter is not in the body, the request will be rejected. The assumption is that the request
     * has already been authorized before this function is called.
     *
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleRequest() {
        // Make sure the action parameter exists
        $this->requireParam('action');
		
		// Call the correct handler based on the action
        switch($this->requestBody['action']) {
            case 'createPosition':
                $this->handleCreatePosition();
                break;
            case 'updatePosition':
                $this->handleUpdatePosition();
                break;
            case 'approvePosition':
                $this->handleApprovePosition();
                break;
            case 'startInterviewing':
                $this->handleStartInterviewing();
                break;
            case 'markCompleted':
                $this->handlePositionCompleted();
                break;
            case 'getExample':
                $this->handleGetExample();
                break;
            case 'deleteExamplePosition':
                $this->handleDeleteExample();
                break;
            case 'emailPosition':
                $this->handleEmailPosition();
                break;
            case 'exportPositionDisposition':
                $this->handleExportDisposition();
                break;
            case 'remindCommittee':
                $this->handleRemindCommittee();
                break;
            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on Position resource'));
        }
    }
}
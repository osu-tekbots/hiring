<?php
namespace Api;

use Model\User;
use Model\Candidate;
use Model\CandidateStatus;
use Model\CandidateRoundNote;
use DataAccess\CandidateDao;

/**
 * Defines the logic for how to handle API requests made to modify Candidate information.
 */
class CandidateActionHandler extends ActionHandler {

    /** @var \DataAccess\CandidateDao */
    private $candidateDao;

    /** @var \DataAccess\CandidateRoundNoteDao */
    private $candidateRoundNoteDao;

    /** @var \DataAccess\CandidateFileDao */
    private $candidateFileDao;

    /** @var \DataAccess\FeedbackFileDao */
    private $feedbackFileDao;

    /** @var \Util\ConfigManager */
    private $configManager;
	
    /**
     * Constructs a new instance of the Candidate action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\CandidateDao $candidateDao The class for accessing the Candidate database table
     * @param \DataAccess\CandidateRoundNoteDao $candidateRoundNoteDao The class for accessing the CandidateRoundNote database table
     * @param \DataAccess\CandidateFileDao $candidateFileDao The class for accessing the CandidateFile database table
     * @param \DataAccess\FeedbackFileDao $feedbackFileDao The class for accessing the FeedbackFile database table
     * @param \Util\ConfigManager $configManager The class for reading data from /config.ini
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($candidateDao, $candidateRoundNoteDao, $candidateFileDao, $feedbackFileDao, $configManager, $logger)
    {
        parent::__construct($logger);
		$this->candidateDao = $candidateDao;
        $this->candidateRoundNoteDao = $candidateRoundNoteDao;
        $this->candidateFileDao = $candidateFileDao;
        $this->feedbackFileDao = $feedbackFileDao;
        $this->configManager = $configManager;
    }

    /**
     * Creates a Candidate object.
     * 
     * @param string positionID Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleCreateCandidate() {
        // Ensure the required parameters exist
        $this->requireParam('positionID');
        
        $body = $this->requestBody;

        // Check if the user is allowed to create an instance
        $this->verifyUserRole('Search Chair', $body['positionID']);

        // Create a new candidate with the given information
        $newCandidate = new Candidate();
        $newCandidate->setPositionID($body['positionID']);
        $newCandidate->setFirstName('');
        $newCandidate->setLastName('');
        $newCandidate->setEmail('');
        $newCandidate->setPhoneNumber('');
        $newCandidate->setLocation('');
        $newCandidate->setCandidateStatus('');

        // Create the candidate in the database
		$ok = $this->candidateDao->createCandidate($newCandidate);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate not Created', var_export($newCandidate, true)));
        }
		$this->respond(new Response(Response::OK, 'Candidate Successfully Created', $newCandidate->getID()));
    }

	/**
     * Updates the candidate state.
     * 
     * @param string id Must exist in the POST request body.
     * @param string firstname Must exist in the POST request body.
     * @param string lastname Must exist in the POST request body.
     * @param string email Must exist in the POST request body.
     * @param string phone Must exist in the POST request body.
     * @param string location Must exist in the POST request body.
     * @param string dateApplied Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateCandidate() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $this->requireParam('firstname');
        $this->requireParam('lastname');
        $this->requireParam('email');
        $this->requireParam('phone');
        $this->requireParam('location');
        $this->requireParam('dateApplied');
        
        $body = $this->requestBody;
        
        // Get Candidate from database
        $candidate = $this->candidateDao->getCandidateById($body['id']);
        if(!$candidate) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Not Found'));
        }

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $candidate->getPositionID());

        //  Update the candidate with the given information
		$ok = $this->candidateDao->updateCandidate($body);
        
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Not Updated'));
        }
		$this->respond(new Response(Response::OK, 'Candidate Updated'));
    }

	/**
     * Deletes a candidate and all associated feedback.
     * 
     * @param string id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleDeleteCandidate() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        
        $body = $this->requestBody;
        
        // Get Candidate from database
        $candidate = $this->candidateDao->getCandidateById($body['id']);
        if(!$candidate) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Not Found'));
        }

        // Check if the user is allowed to delete an instance
        $this->verifyUserRole('Search Chair', $candidate->getPositionID());

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
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Not Deleted'));
        }
		$this->respond(new Response(Response::OK, 'Candidate Deleted'));
    }

	/**
     * Updates (or creates if the candidate has no linked state) the candidate status state.
     * 
     * @param string candidateID Must exist in the POST request body.
     * @param integer status Must exist in the POST request body.
     * @param string disposition Must exist in the POST request body.
     * @param string reason Must exist in the POST request body.
     * @param string notificationMethod Must exist in the POST request body.
     * @param string responsiblePartyDesc May exist in the POST request body.
     * @param string comments May exist in the POST request body.
     * 
     * @return Api/Response object
     */
    public function handleCreateOrUpdateStatus() {
        // Ensure the required parameters exist
        $this->requireParam('candidateID');
        $this->requireParam('status');
        $this->requireParam('disposition');
        $this->requireParam('reason');
        $this->requireParam('notificationMethod');
        
        $body = $this->requestBody;
        
        // Get Candidate from database
        $candidate = $this->candidateDao->getCandidateById($body['candidateID']);
        if(!$candidate) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Not Found', $body));
        }

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $candidate->getPositionID());

        // Update or create the candidate status
        $candidateStatus = $candidate->getCandidateStatus();
        $statusExists = true;
        if(!$candidateStatus) {
            $statusExists = false;
            $candidateStatus = new CandidateStatus();
        }
        $candidateStatus->setStatusID($body['status']);
        $candidateStatus->setSpecificDispositionReason($body['reason']);
        $candidateStatus->setUserID($_SESSION['userID']);
        $candidateStatus->setResponsiblePartyDescription($body['responsiblePartyDesc']);
        $candidateStatus->setComments($body['comments']);
        $candidateStatus->setHowNotified($body['notificationMethod']);
        $candidateStatus->setDateDecided(new \DateTime());

        // Update the candidate status or create a new one if the candidate doesn't have one yet
        if($statusExists) {
            $ok = $this->candidateDao->updateCandidateStatus($body['candidateID'], $candidateStatus);
        } else {
            $ok = $this->candidateDao->setCandidateStatus($body['candidateID'], $candidateStatus);
        }
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Status Not Updated', $body));
        }
		$this->respond(new Response(Response::OK, 'Candidate Status Updated'));
    }

    /**
     * Updates (or creates if the candidate has no linked notes for this round) the candidate round note.
     * 
     * @param string candidateID Must exist in the POST request body.
     * @param string roundID Must exist in the POST request body.
     * @param string notes May exist in the POST request body.
     * 
     * @return Api/Response object
     */
    public function handleCreateOrUpdateRoundNotes() {
        // Ensure the required parameters exist
        $this->requireParam('candidateID');
        $this->requireParam('roundID');
        $this->requireParam('notes');
        
        $body = $this->requestBody;
        
        // Get Candidate from database
        $candidate = $this->candidateDao->getCandidateById($body['candidateID']);
        if(!$candidate) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Not Found', $body));
        }

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $candidate->getPositionID());

        // Update or create the candidate round note
        $candidateRoundNote = $this->candidateRoundNoteDao->getCandidateNotesForRound($body['candidateID'], $body['roundID']);
        $noteExists = true;
        if(!$candidateRoundNote) {
            $noteExists = false;
            $candidateRoundNote = new CandidateRoundNote();
            $candidateRoundNote->setCandidateID($body['candidateID']);
            $candidateRoundNote->setRoundID($body['roundID']);
        }
        $candidateRoundNote->setNotes($body['notes']);

        // Update the candidate round note or create a new one if the candidate doesn't have one for this round yet
        if($noteExists) {
            $ok = $this->candidateRoundNoteDao->updateCandidateRoundNote($candidateRoundNote);
        } else {
            $ok = $this->candidateRoundNoteDao->createCandidateRoundNote($candidateRoundNote);
        }
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Group Note Not Updated', $body));
        }
		$this->respond(new Response(Response::OK, 'Group Note Updated'));
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
            case 'createCandidate':
                $this->handleCreateCandidate();
                break;
            case 'updateCandidate':
                $this->handleUpdateCandidate();
                break;
            case 'deleteCandidate':
                $this->handleDeleteCandidate();
                break;
            case 'createOrUpdateStatus':
                $this->handleCreateOrUpdateStatus();
                break;
            case 'createOrUpdateRoundNotes':
                $this->handleCreateOrUpdateRoundNotes();
                break;
            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on Candidate resource'));
        }
    }
}
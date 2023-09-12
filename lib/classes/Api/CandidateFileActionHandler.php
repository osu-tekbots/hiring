<?php
namespace Api;

use Model\User;
use Model\CandidateFile;
use DataAccess\CandidateFileDao;
use DataAccess\CandidateDao;

/**
 * Defines the logic for how to handle API requests made to modify CandidateFile information.
 */
class CandidateFileActionHandler extends ActionHandler {

    /** @var \DataAccess\CandidateFileDao */
    private $candidateFileDao;

    /** @var \DataAccess\CandidateDao */
    private $candidateDao;

    /** @var \Util\ConfigManager */
    private $configManager;
	
    /**
     * Constructs a new instance of the CandidateFile action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\CandidateFileDao $candidateFileDao The class for accessing the CandidateFile database table
     * @param \DataAccess\CandidateDao $candidateDao The class for accessing the Candidate database table
     * @param \Util\Logger $logger The class for logging execution details
     * @param \Util\ConfigManager $configManager The class for accessing the /config.ini information
     */
	public function __construct($candidateFileDao, $candidateDao, $logger, $configManager)
    {
        parent::__construct($logger);
		$this->candidateFileDao = $candidateFileDao;
		$this->candidateDao = $candidateDao;
		$this->configManager = $configManager;
    }

	/**
     * Links a new CandidateFile in the database.
     * 
     * @param string candidateID Must exist in the POST request body.
     * @param string filename Must exist in the POST request body.
     * @param string purpose Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleCreateCandidateFile() {
        // Ensure the required parameters exist
        $this->requireParam('candidateID');
        $this->requireParam('filename');
        $this->requireParam('purpose');

        $body = $this->requestBody;

        // Get Candidate from database
        $candidate = $this->candidateDao->getCandidateById($body['candidateID']);
        if(!$candidate) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Not Found'));
        }

        // Check if the user is allowed to update the candidate
        $this->verifyUserRole('Search Chair', $candidate->getPositionID());

        // Create new candidate file
        $newCandidateFile = new CandidateFile();
        $newCandidateFile->setCandidateId($body['candidateID']);
        $newCandidateFile->setFileName($body['filename']);
        $newCandidateFile->setPurpose($body['purpose']);

        // Add the CandidateFile to database
        $fileID = $this->candidateFileDao->createCandidateFile($newCandidateFile);
        
        
        // Use Response object to send DAO action results 
        if($fileID === false) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate File Not Linked'));
        }
		$this->respond(new Response(Response::OK, 'Candidate File Successfully Uploaded', $fileID));
    }

    /**
     * Updates the CandidateFile state.
     * 
     * @param integer fileID Must exist in the POST request body.
     * @param string purpose Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateCandidateFile() {
        // Ensure the required parameters exist
        $this->requireParam('fileID');
        $this->requireParam('purpose');

        $body = $this->requestBody;

        // Get info from database
        $candidateFile = $this->candidateFileDao->getFile($body['fileID']);
        $candidate = $this->candidateDao->getCandidateById($candidateFile->getCandidateID());
        if(!$candidate) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Not Found'));
        }

        // Check if the user is allowed to update the candidate
        $this->verifyUserRole('Search Chair', $candidate->getPositionID());

        // Update the candidate file
        $candidateFile->setPurpose($body['purpose']);
        $this->logger->info(gettype($body['purpose']));
        $this->logger->info(var_export($body['purpose'], true));

        // Update the CandidateFile in the database
        $ok = $this->candidateFileDao->updateCandidateFile($candidateFile);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate File Not Updated'));
        }
		$this->respond(new Response(Response::OK, 'Candidate File Successfully Updated'));
    }

    /**
     * Removes a CandidateFile (from the database AND the server).
     * 
     * @param integer fileID Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleRemoveCandidateFile() {
        // Ensure the required parameters exist
        $this->requireParam('fileID');

        $body = $this->requestBody;

        // Get info from database
        $candidateFile = $this->candidateFileDao->getFile($body['fileID']);
        $candidate = $this->candidateDao->getCandidateById($candidateFile->getCandidateID());
        if(!$candidate) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate Not Found'));
        }

        // Check if the user is allowed to remove the candidate file
        $this->verifyUserRole('Search Chair', $candidate->getPositionID());

        // Delete the file from the server
        try {
            $ok = unlink($this->configManager->getPrivateFilesDirectory()."/uploads/candidate/".$candidateFile->getFileName());
            if(!$ok) {
                throw new \Exception('unlink() returned false');
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove CandidateFile from server: ' . $e->getMessage());
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate File Not Deleted'));
        }

        // Delete the CandidateFile from the database
        $ok = $this->candidateFileDao->removeCandidateFile($candidateFile);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Candidate File Not Deleted'));
        }
		$this->respond(new Response(Response::OK, 'Candidate File Successfully Deleted'));
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
            case 'createCandidateFile':
                $this->handleCreateCandidateFile();
                break;
            case 'updateCandidateFile':
                $this->handleUpdateCandidateFile();
                break;
            case 'removeCandidateFile':
                $this->handleRemoveCandidateFile();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on CandidateFile resource'));
        }
    }
}
 
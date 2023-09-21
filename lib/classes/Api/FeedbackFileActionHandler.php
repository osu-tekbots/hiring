<?php

namespace Api;

use Model\FeedbackFile;
use DataAccess\FeedbackFileDao;

/**
 * Defines the logic for how to handle API requests made to modify FeedbackFile information.
 */
class FeedbackFileActionHandler extends ActionHandler {

    /** @var \DataAccess\FeedbackFileDao */
    private $feedbackFileDao;
    /** @var \DataAccess\FeedbackDao */
    private $feedbackDao;
    /** @var \DataAccess\RoundDao */
    private $roundDao;
    /** @var \Util\ConfigManager */
    private $configManager;
	
    /**
     * Constructs a new instance of the FeedbackFile action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\FeedbackFileDao $feedbackFileDao The class for accessing the FeedbackFile database table
     * @param \DataAccess\FeedbackDao $feedbackDao The class for accessing the Feedback database table
     * @param \DataAccess\RoundDao $roundDao The class for accessing the Round database table
     * @param \Util\Logger $logger The class for logging execution details
     * @param \Util\ConfigManager $configManager The class for accessing the /config.ini information
     * 
     */
	public function __construct($feedbackFileDao, $feedbackDao, $roundDao, $logger, $configManager)
    {
        parent::__construct($logger);
		$this->feedbackFileDao = $feedbackFileDao;
		$this->feedbackDao = $feedbackDao;
		$this->roundDao = $roundDao;
        $this->configManager = $configManager;
    }


	/**
     * Create new FeedbackFile in the database.
     * 
     * @param integer feedbackID Must exist in the POST request body.
     * @param string filename Must exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleCreateFeedbackFile() {
        // Ensure the required parameters exist
        $this->requireParam('feedbackID');
        $this->requireParam('filename');

        $body = $this->requestBody;

        // Get Feedback from database
        $feedback = $this->feedbackDao->getFeedbackById($body['feedbackID']);
        $round = $this->roundDao->getRound($feedback->getRoundID());
        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Any', $round->getPositionID());

        $feedbackFile = new FeedbackFile();
        $feedbackFile->setFeedbackId($body['feedbackID']);
        $feedbackFile->setFileName($body['filename']);
        // Save the file info in the database
		$fileID = $this->feedbackFileDao->createFeedbackFile($feedbackFile);
        $this->logger->info("Action Handler fileID: ".$fileID);
        
        // Use Response object to send DAO action results 
        if($fileID === false) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Feedback File Not Linked'));
        }
        $this->logger->info("Action Handler fileID after check: ".$fileID);
		$this->respond(new Response(Response::OK, 'Feedback File Saved', $fileID));
    }

    /**
     * Remove the FeedbackFile (from the database AND the server).
     * 
     * @param integer fileID Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleRemoveFeedbackFile() {
        // Ensure the required parameters exist
        $this->requireParam('fileID');

        $body = $this->requestBody;

        // Get Feedback from database
        $feedbackFile = $this->feedbackFileDao->getFeedbackFileById($body['fileID']);
        if(!$feedbackFile) {
            $this->respond(new Response(Response::BAD_REQUEST, 'Feedback File Not Found'));
        }
        $feedback = $this->feedbackDao->getFeedbackById($feedbackFile->getFeedbackID());
        $round = $this->roundDao->getRound($feedback->getRoundID());
        
        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Any', $round->getPositionID());

        // Delete the file from the server
        try {
            $ok = unlink($this->configManager->getPrivateFilesDirectory()."/uploads/feedback/".$feedbackFile->getFileName());
            if(!$ok) {
                throw new \Exception('feedback unlink() failed');
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove FeedbackFile from server: ' . $e->getMessage());
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Feedback File Not Deleted'));
        }
        
        //  Delete the file information from the database
		$ok = $this->feedbackFileDao->removeFeedbackFile($body['fileID']);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Feedback File Not Removed'));
        }
		$this->respond(new Response(Response::OK, 'Feedback File Removed'));
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
            case 'createFeedbackFile':
                $this->handleCreateFeedbackFile();
                break;
            case 'removeFeedbackFile':
                $this->handleRemoveFeedbackFile();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on FeedbackFile resource'));
        }
    }
}
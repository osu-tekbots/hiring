<?php
namespace Api;

use Model\User;
use Model\Round;
use DataAccess\RoundDao;

/**
 * Defines the logic for how to handle API requests made to modify Round information.
 */
class RoundActionHandler extends ActionHandler {

    /** @var \DataAccess\RoundDao */
    private $roundDao;

    /** @var \DataAccess\FeedbackFileDao */
    private $feedbackFileDao;

    /** @var \Util\ConfigManger */
    private $configManager;
    
    /**
     * Constructs a new instance of the Round action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\RoundDao $roundDao The class for accessing the Round database table
     * @param \DataAccess\FeedbackFileDao $feedbackFileDao The class for accessing the FeedbackFile database table
     * @param \Util\ConfigManager $configManager The class for reading data from /config.ini
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($roundDao, $feedbackFileDao, $configManager, $logger)
    {
        parent::__construct($logger);
		$this->roundDao = $roundDao;
        $this->feedbackFileDao = $feedbackFileDao;
        $this->configManager = $configManager;
    }

    /**
     * Creates a Round in the database.
     * 
     * @param positionID Must exist in the POST request body.
     * @param name May exist in the POST request body.
     * @param link May exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleCreateRound() {
        // Ensure the required parameters exist
        $this->requireParam('positionID');

        $body = $this->requestBody;

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $body['positionID']);

        // Create a new round
        $newRound = new Round();
        $newRound->setPositionID($body['positionID']);
        $newRound->setName($body['name'] ?? NULL);
        $newRound->setInterviewQuestionLink($body['link'] ?? NULL);

        // Save the round in the database
		$ok = $this->roundDao->createRound($newRound);
        
        // Use Response object to send DAO action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Round not Created'));
        }
		$this->respond(new Response(Response::OK, 'Round Successfully updated', $newRound->getID()));
    }


	/**
     * Updates the Round state.
     * 
     * @param id Must exist in the POST request body.
     * @param name Must exist in the POST request body.
     * @param link May exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateRound() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $this->requireParam('name');
        
        $body = $this->requestBody;

        // Get round from database
        $round = $this->roundDao->getRound($body['id']);
        if(!$round) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Round not found'));
        }

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $round->getPositionID());

        // Update the round with the given information
		$ok = $this->roundDao->updateRound($body['id'], $body['name'], $body['link'] ?? '');
        
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Round not updated'));
        }
		$this->respond(new Response(Response::OK, 'Round Successfully Updated'));
    }

    /**
     * Updates the Round link's state.
     * 
     * @param id Must exist in the POST request body.
     * @param name Must exist in the POST request body.
     * @param link Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateRoundLink() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $this->requireParam('name');
        $this->requireParam('link');
        
        $body = $this->requestBody;

        // Get round from database
        $round = $this->roundDao->getRound($body['id']);
        if(!$round) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Round not found'));
        }

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $round->getPositionID());

        // Update the round with the given information
		$ok = $this->roundDao->updateRound($body['id'], $body['name'], $body['link']);
        
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Round not updated'));
        }
		$this->respond(new Response(Response::OK, 'Round Successfully Updated'));
    }

    
	/**
     * Deletes the Round and all associated.
     * 
     * @param id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleDeleteRound() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        
        $body = $this->requestBody;

        // Get round from database
        $round = $this->roundDao->getRound($body['id']);
        if(!$round) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Round Not Found'));
        }

        // Check if the user is allowed to delete an instance
        $this->verifyUserRole('Search Chair', $round->getPositionID());

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
		$ok = $this->roundDao->deleteRound($body['id']);
        
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Round Not Deleted'));
        }
		$this->respond(new Response(Response::OK, 'Round Deleted'));
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
            case 'createRound':
                $this->handleCreateRound();
                break;
            case 'updateRound':
                $this->handleUpdateRound();
                break;
            case 'updateRoundLink':
                $this->handleUpdateRoundLink();
                break;
            case 'deleteRound':
                $this->handleDeleteRound();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on Round resource'));
        }
    }
}
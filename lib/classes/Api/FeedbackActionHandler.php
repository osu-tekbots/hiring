<?php
namespace Api;

use Model\User;
use Model\Feedback;
use DataAccess\FeedbackDao;
use DataAccess\FeedbackForQualDao;
use DataAccess\RoundDao;

/**
 * Defines the logic for how to handle API requests made to modify Feedback information.
 */
class FeedbackActionHandler extends ActionHandler {

    /** @var \DataAccess\FeedbackForQualificationDao */
    private $feedbackDao;
    
    /** @var \DataAccess\FeedbackForQualificationDao */
    private $ffqDao;

    /** @var \DataAccess\RoundDao */
    private $roundDao;
	
    /**
     * Constructs a new instance of the Feedback action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\FeedbackDao $feedbackDao The class for accessing the Feedback database table
     * @param \DataAccess\FeedbackForQualDao $feedbackForQualDao The class for accessing the FeedbackForQual database table
     * @param \DataAccess\RoundDao $roundDao The class for accessing the Round database table
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($feedbackDao, $feedbackForQualDao, $roundDao, $logger)
    {
        parent::__construct($logger);
        $this->ffqDao = $feedbackForQualDao;
		$this->feedbackDao = $feedbackDao;
        $this->roundDao = $roundDao;
    }

    /**
     * Handles storing a user's score for a feedback and qualification
     * 
     * @param integer feedbackID Must exist in the POST request body
     * @param string qualificationID Must exist in the POST request body
     * @param string qualificationStatus Must exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleAddFFQ() {
        // Ensure the required parameters exist
        $this->requireParam('feedbackID');
        $this->requireParam('qualificationID');
        $this->requireParam('qualificationStatus');

        $body = $this->requestBody;

        // Get Feedback from database
        $feedback = $this->feedbackDao->getFeedbackById($body['feedbackID']);
        $round = $this->roundDao->getRound($feedback->getRoundID());

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Any', $round->getPositionID());

        // Store FFQ in database
        $ok = $this->ffqDao->addFeedbackForQual($body['feedbackID'], $body['qualificationID'], $body['qualificationStatus']);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Score not saved'));
        }
		$this->respond(new Response(Response::OK, 'Score saved'));
    }

    /**
     * Handles updating a user's score for a feedback and qualification
     * 
     * @param integer feedbackID Must exist in the POST request body
     * @param string qualificationID Must exist in the POST request body
     * @param string qualificationStatus Must exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateFFQ() {
        // Ensure the required parameters exist
        $this->requireParam('feedbackID');
        $this->requireParam('qualificationID');
        $this->requireParam('qualificationStatus');

        $body = $this->requestBody;

        // Get Feedback from database
        $feedback = $this->feedbackDao->getFeedbackById($body['feedbackID']);
        $round = $this->roundDao->getRound($feedback->getRoundID());

        // Check if the user is allowed to update the instance
        $this->verifyUserRole('Any', $round->getPositionID());

        // Update FFQ in database
        $ok = $this->ffqDao->updateFeedbackForQual($body['feedbackID'], $body['qualificationID'], $body['qualificationStatus']);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Score not updated'));
        }
		$this->respond(new Response(Response::OK, 'Score updated'));
    }

	/**
     * Handles updating a user's notes for a feedback
     * 
     * @param integer feedbackID Must exist in the POST request body
     * @param string note Must exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateNotes() {
        // Ensure the required parameters exist
        $this->requireParam('feedbackID');
        $this->requireParam('note');

        $body = $this->requestBody;

        // Get Feedback from database
        $feedback = $this->feedbackDao->getFeedbackById($body['feedbackID']);
        $round = $this->roundDao->getRound($feedback->getRoundID());

        // Check if the user is allowed to update the instance
        $this->verifyUserRole('Any', $round->getPositionID());
        
        // Update feedback object
        $feedback->setNotes($body['note']);
        $feedback->setLastUpdated(new \DateTime('now'));

        // Store updated version in database
        $ok = $this->feedbackDao->updateFeedback($feedback);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Notes not updated'));
        }
		$this->respond(new Response(Response::OK, 'Notes updated'));
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
            case 'addFFQ':
                $this->handleAddFFQ();
                break;
            case 'updateFFQ':
                $this->handleUpdateFFQ();
                break;
            case 'updateNotes':
                $this->handleUpdateNotes();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on Feedback resource'));
        }
    }
}
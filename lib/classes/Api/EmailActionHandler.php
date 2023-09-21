<?php
namespace Api;

use Email\Mailer;

/**
 * Defines the logic for how to handle API requests made to modify MODEL_NAME information.
 */
class EmailActionHandler extends ActionHandler {

    /** @var \DataAccess\CandidateDao */
    private $candidateDao;

    /** @var \DataAccess\PositionDao */
    private $positionDao;
	
    /**
     * Constructs a new instance of the email action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\CandidateDao $candidateDao The class for accessing the Candidate database table
     * @param \DataAccess\PositionDao $positionDao The class for accessing the Position database table
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($candidateDao, $positionDao, $logger)
    {
        parent::__construct($logger);
		$this->candidateDao = $candidateDao;
		$this->positionDao = $positionDao;
    }


	/**
     * Sends an email.
     * 
     * @param string candidateID Must exist in the POST request body.
     * @param string subject Must exist in the POST request body.
     * @param string body Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleSendEmail() {
        // Ensure the required parameters exist
        $this->requireParam('candidateID');
        $this->requireParam('subject');
        $this->requireParam('body');
        $body = $this->requestBody;

        // Get the candidate
        $candidate = $this->candidateDao->getCandidateById($body['candidateID']);
        
        // Check if the user is allowed to send an email
        $this->verifyUserRole('Any', $candidate->getPositionID());
        
        // Get FROM email address
        $position = $this->positionDao->getPosition($candidate->getPositionID());

        // Create corresponding mailer
        $mailer = new Mailer($position->getCommitteeEmail(), NULL, $this->logger);

        //  Send the email
		$ok = $mailer->sendEmail($candidate->getEmail(), $body['subject'], $body['body'], NULL, $position->getCommitteeEmail());
        
        // Use Response object to send email action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Email not sent'));
        }
		$this->respond(new Response(Response::OK, 'Email sent'));
    }
    
	/**
     * Sends an error email with diagnostic information.
     * 
     * @param string body Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleErrorEmail() {
        // Ensure the required parameters exist
        $this->requireParam('body');
        $body = $this->requestBody;

        // Create mailer
        $mailer = new Mailer($this->configManager->get('email.admin_address'), $this->configManager->get('email.admin_subject_tag'), $this->logger);

        //  Send the email
		$ok = $mailer->sendEmail('bairdn@oregonstate.edu', 'Uncaught Error', str_replace("\n", "<br>", $body['body']), true);
        
        // Use Response object to send email action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Email not sent'));
        }
		$this->respond(new Response(Response::OK, 'Email sent'));
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
            case 'sendEmail':
                $this->handleSendEmail();
                break;

            case 'errorEmail':
                $this->handleErrorEmail();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on email resource'));
        }
    }
}
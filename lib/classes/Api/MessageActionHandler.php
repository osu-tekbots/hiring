<?php
namespace Api;

use Model\Message;
use DataAccess\MessageDao;
// Below for filling test email templates
use Model\User;
use Model\Position;

/**
 * Defines the logic for how to handle API requests made to modify Message information.
 */
class MessageActionHandler extends ActionHandler {

    /** @var \DataAccess\MessageDao */
    private $messageDao;

    /** @var \Mailer\HiringMailer */
    private $hiringMailer;

    /** @var string The string to fill test email templates with */
    private $testString;
	
    /**
     * Constructs a new instance of the Message action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\MessageDao $messageDao The class for accessing the Message database table
     * @param \Mailer\HiringMailer $hiringMailer The class for sending Hiring emails
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($messageDao, $hiringMailer, $logger)
    {
        parent::__construct($logger);
		$this->messageDao = $messageDao;
        $this->hiringMailer = $hiringMailer;
        $this->testString = '<i>&lt;test&gt;</i>';
    }

	/**
     * Updates the Message state.
     * 
     * @param id Must exist in the POST request body
     * @param subject Must exist in the POST request body
     * @param body May exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateMessage() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $this->requireParam('subject');
        $this->requireParam('body');

        $body = $this->requestBody;

        // Check if the user is allowed to update an instance ('Admin' will not match any, so it'll only succeed if the user is an admin since that overrides the checks)
        $this->verifyUserRole('Admin', null);

        // Get the message
        $message = $this->messageDao->getMessageByID($body['id']);

        // Update via setters
        $message->setSubject($body['subject']);
        $message->setBody($body['body']);

        //  Update the message using its DAO
		$ok = $this->messageDao->updateMessage($message);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Message not updated'));
        }
		$this->respond(new Response(Response::OK, 'Message updated'));
    }

    public function handleSendTestMessage() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $this->requireParam('email');
        $body = $this->requestBody;
		
        // Get the message
		$message = $this->messageDao->getMessageByID($body['id']);

        // Check if the user is allowed to send a test email ('Admin' will not match any, so it'll only succeed if the user is an admin since that overrides the checks)
        $this->verifyUserRole('Admin', null);

        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail($body['email']);

        $ok = false;

        switch($body['id']) {
            case 1: // Added to committee
                $position = new Position();
                $this->fillModel($position);
                $ok = $this->hiringMailer->sendAddedToCommitteeEmail($user, $message, $position, $this->testString);
                break;
            default:
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Message not Found'));
        }

        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Failed to Send Email'));
        }

        $this->respond(new Response(Response::OK, 'Successfully Sent Email'));
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
            case 'updateMessage':
                $this->handleUpdateMessage();
                break;
            case 'sendTestMessage':
                $this->handleSendTestMessage();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on Message resource'));
        }
    }

    /**
     * Fills the given model with the HTML markup for an italicized string "<test>". Operates directly on the object
     * instead of creating a duplicate, and uses `set__()` methods to fill the object.
     * 
     * Used for sending test emails.
     * 
     * @param \Model\* $model The model to fill with the string "<test>"
     * @param string[] $exceptions Setter methods to ignore when filling the object (eg "setPurpose")
     */
    private function fillModel(&$model, $exceptions=[]) {
        foreach(get_class_methods($model) as $method) {
            if(str_contains(strtolower($method), 'set') && !in_array($method, $exceptions)) {
                $model->$method($this->testString);
            }
        }
    }
}
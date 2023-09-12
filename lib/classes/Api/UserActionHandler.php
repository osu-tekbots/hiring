<?php
namespace Api;

use Model\User;
use DataAccess\UserDao;

/**
 * Defines the logic for how to handle API requests made to modify User information.
 */
class UserActionHandler extends ActionHandler {

    /** @var \DataAccess\UserDao */
    private $userDao;
	
    /**
     * Constructs a new instance of the User action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\UserDao $userDao The class for accessing the User database table
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($userDao, $logger)
    {
        parent::__construct($logger);
		$this->userDao = $userDao;
    }


	/**
     * Updates the User's access level.
     * 
     * @param id Must exist in the POST request body.
     * @param level May exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleChangeAccessLevel() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $this->requireParam('level');

        $body = $this->requestBody;

        // Get User from database
        $user = $this->userDao->getUserByID($body['id']);

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Admin', NULL);

        // Update via setters
        $user->setAccessLevel($body['level']);
        $user->setDateUpdated(new \DateTime());

        // Update the user in the database
		$ok = $this->userDao->updateUser($user);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Access level not updated'));
        }
		$this->respond(new Response(Response::OK, 'Access level updated'));
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
            case 'changeAccessLevel':
                $this->handleChangeAccessLevel();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on User resource'));
        }
    }
}
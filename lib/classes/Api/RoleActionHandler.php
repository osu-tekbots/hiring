<?php
namespace Api;

use Model\User;
use Model\Role;
use Model\RoleForPosition;
use DataAccess\RoleDao;
use Email\HiringMailer;

/**
 * Defines the logic for how to handle API requests made to modify Role information.
 */
class RoleActionHandler extends ActionHandler {

    /** @var \DataAccess\PositionDao */
    private $positionDao;

    /** @var \DataAccess\RoleDao */
    private $roleDao;

    /** @var \DataAccess\UserDao */
    private $userDao;

    /** @var \DataAccess\MessageDao */
    private $messageDao;
	
    /**
     * Constructs a new instance of the Role action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\RoleDao $roleDao The class for accessing the Role database table
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($positionDao, $roleDao, $userDao, $messageDao, $configManager, $logger)
    {
        parent::__construct($logger);
        $this->positionDao = $positionDao;
		$this->roleDao = $roleDao;
		$this->userDao = $userDao;
		$this->messageDao = $messageDao;
        $this->configManager = $configManager;
    }

    /**
     * Adds a Role for a user for a position.
     * 
     * @param string positionID Must exist in the POST request body.
     * @param integer roleID Must exist in the POST request body.
     * @param string userID Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleAddRoleForPosition() {
        // Ensure the required parameters exist
        $this->requireParam('positionID');
        $this->requireParam('roleID');
        $this->requireParam('userID');
        
        // Get request body
        $body = $this->requestBody;

        // Get the position
        $position = $this->positionDao->getPosition($body['positionID']);
        if(!$position) {
            $this->respond(new Response(Response::BAD_REQUEST, 'Position Not Found'));
        }
        if($position->getIsExample()) {
            $this->respond(new Response(Response::UNAUTHORIZED, 'Access Denied'));
        }

        // Check if the user is allowed to add an instance
        $this->verifyUserRole('Search Chair', $body['positionID']);
        
        // Get the role
        $role = $this->roleDao->getRoleByID($body['roleID']);

        // Get the user
        $user = $this->userDao->getUserByID($body['userID']);

        // Make sure user doesn't already have a role for this position
        $alreadyExists = $this->roleDao->getUserRoleForPosition($body['userID'], $body['positionID']);
        if($alreadyExists) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'User has duplicate role for this position'));
        }

        // Assign the given role to the user
		$roleForPositionID = $this->roleDao->addUserRoleForPosition($body['roleID'], $body['userID'], $body['positionID']);
        
        // Use Response object to send DAO action results
        if($roleForPositionID===false) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Role not added'));
        }

        // Email the new member to tell them they've been added
        $hiringMailer = new HiringMailer($position->getCommitteeEmail(), $this->configManager->getBounceEmail(), null, $this->logger);
        $message = $this->messageDao->getMessageByID(1);
        $ok = $hiringMailer->sendAddedToCommitteeEmail($user, $message, $position, $role->getName());
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'New User Not Notified of Addition'));
        }

		$this->respond(new Response(Response::OK, 'Role added', $roleForPositionID));
    }


	/**
     * Updates the Role state for a position committee member.
     * 
     * @param integer roleForPositionID Must exist in the POST request body.
     * @param string positionID Must exist in the POST request body.
     * @param integer roleID Must exist in the POST request body.
     * @param string userID Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateRoleForPosition() {
        // Ensure the required parameters exist
        $this->requireParam('roleForPositionID');
        $this->requireParam('positionID');
        $this->requireParam('roleID');
        $this->requireParam('userID');
        
        $body = $this->requestBody;

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $body['positionID']);

        // Check that the user doesn't have a different role for this position
        $currentRoleForPosition = $this->roleDao->getUserRoleForPosition($body['userID'], $body['positionID']);
        if($currentRoleForPosition && $currentRoleForPosition->getID() != $body['roleForPositionID']) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'User has duplicate role for this position'));
        }

        // Update the user's role
		$ok = $this->roleDao->updateUserRoleForPosition($body['roleForPositionID'], $body['roleID']);
        
        // Use Response object to send DAO action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Member not updated'));
        }
		$this->respond(new Response(Response::OK, 'Member updated'));
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
            case 'addRoleForPosition':
                $this->handleAddRoleForPosition();
                break;
            case 'updateRoleForPosition':
                $this->handleUpdateRoleForPosition();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on Role resource'));
        }
    }
}
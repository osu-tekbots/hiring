<?php
namespace Api;

use Model\User;
use Model\Position;
use DataAccess\PositionDao;
use DataAccess\RoleDao;
use Email\HiringMailer;

/**
 * Defines the logic for how to handle API requests made to modify Position information.
 */
class PositionActionHandler extends ActionHandler {

    /** @var \DataAccess\PositionDao */
    private $positionDao;

    /** @var \DataAccess\RoleDao */
    private $roleDao;
	
    /**
     * Constructs a new instance of the Position action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\PositionDao $positionDao The class for accessing the Position database table
     * @param \DataAccess\RoleDao $roleDao The class for accessing the Role database table
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($positionDao, $roleDao, $logger)
    {
        parent::__construct($logger);
		$this->positionDao = $positionDao;
        $this->roleDao = $roleDao;
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
        $this->logger->info(var_export($position, true));

        // Store the updated version in the database
		$ok = $this->positionDao->updatePosition($position);
        
        // Use Response object to send DAO action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not approved'));
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
        if($position->getStatus() != 'Open') {
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
     * Changes the Position state to closed after a candidate has been hired.
     * 
     * @param string id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleClosePosition() {
        // Ensure the required parameters exist
        $this->requireParam('id');

        $body = $this->requestBody;

        // Check if the user is allowed to approve an instance
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
        $position->setStatus('Closed');
        $this->logger->info(var_export($position, true));

        // Save the updated version in the database
		$ok = $this->positionDao->updatePosition($position);
        
        // Use Response object to send DAO action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not updated'));
        }
		$this->respond(new Response(Response::OK, 'Position marked as Closed'));
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

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on Position resource'));
        }
    }
}
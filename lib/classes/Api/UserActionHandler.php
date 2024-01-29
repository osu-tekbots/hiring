<?php
namespace Api;

use Model\User;
use Model\UserAuth;
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
     * @param string id Must exist in the POST request body.
     * @param string level May exist in the POST request body
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
     * Adds a new User for search chairs' convinience.
     * 
     * @param string onid Must exist in the POST request body.
     * @param string firstName Must exist in the POST request body.
     * @param string lastName Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleAddUser() {
        // Ensure the required parameters exist
        $this->requireParam('onid');
        $this->requireParam('firstName');
        $this->requireParam('lastName');
        
        $body = $this->requestBody;

        if($body['onid'] == '') {
            $this->respond(new Response(Response::BAD_REQUEST, 'ONID must be provided'));
        }
        if($body['firstName'] == '' || $body['lastName'] == '') {
            $this->respond(new Response(Response::BAD_REQUEST, 'Full name must be provided'));
        }

        $onidProvider = $this->userDao->getAuthProviderByName('ONID');
        $user = $this->userDao->getUserFromAuth($onidProvider, $body['onid']);
        if($user) {
            $this->respond(new Response(Response::BAD_REQUEST, 'User Already Exists'));
        }

        // Create a new user with the given info
        $user = new User();
        $user->setFirstName($body['firstName']);
        $user->setLastName($body['lastName']);
        $user->setEmail($body['onid'].'@oregonstate.edu');
        $user->setAccessLevel('User');
        $user->setDateUpdated(new \DateTime());
        
        // Add the user to the database
        $ok = $this->userDao->addNewUser($user);
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'User Not Added'));
        }

        $ua = new UserAuth();
        $ua->setUserID($user->getID());
        $ua->setAuthProvider($onidProvider);
        $ua->setProviderID($body['onid']);
        $ok = $this->userDao->addNewUserAuth($ua);

        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'User Credentials Not Added'));
        }
		$this->respond(new Response(Response::OK, 'User Added', $user->getID()));
    }

    /**
     * Starts masquerading as the provided user, allowing admins to see things from the viewpoint of a normal user.
     *
     * @param string id Must exist in the POST request body -- the user to masquerade as.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleStartMasquerade() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $body = $this->requestBody;

        // Check if the user is allowed to masquerade
        $this->verifyUserRole('Admin', NULL);

        $user = $this->userDao->getUserByID($body['id']);

        if(!$user) {
            $this->respond(new Response(Response::BAD_REQUEST, 'User Not Found'));
        }

        $this->logger->warn('User '.$_SESSION['userID'].' began masquerading as '.$user->getID());

        if(isset($_SESSION['masq'])) $this->endMasquerade();

        $_SESSION['masq'] = array(
            'active' => true,
            'savedPreviousUser' => true,
            'userID' => $_SESSION['userID'],
            'userAccessLevel' => $_SESSION['userAccessLevel'],
            'newUser' => $_SESSION['newUser'],
        );
        $_SESSION['userID'] = $user->getID();
        $_SESSION['userAccessLevel'] = $user->getAccessLevel();
        $_SESSION['newUser'] = false;

        $this->respond(new Response(Response::OK, 'Masquerading as '.$user->getFirstName().' '.$user->getLastName()));
    }
    
    /**
     * Stops masquerading (if the user currently is) and restores the original user session variables.
     *
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleStopMasquerade() {
        if (isset($_SESSION['masq'])) {
            $this->endMasquerade();
        } else {
            $this->respond(new Response(Response::BAD_REQUEST, 'Not Masquerading'));    
        }

        $this->respond(new Response(Response::OK, 'Masquerading Ended'));
    }

    /**
     * Restores the session data for the normal user. Assumes that $_SESSION['masq] is confirmed to exist
     */
    private function endMasquerade() {
        unset($_SESSION['userID']);
        unset($_SESSION['userAccessLevel']);
        unset($_SESSION['newUser']);
        if (isset($_SESSION['masq']['savedPreviousUser'])) {
            $_SESSION['userID'] = $_SESSION['masq']['userID'];
            $_SESSION['userAccessLevel'] = $_SESSION['masq']['userAccessLevel'];
            $_SESSION['newUser'] = $_SESSION['masq']['newUser'];
        }
        unset($_SESSION['masq']);
    }
    
    /**
     * Updates a user's information.
     * 
     * @param string id Must exist in the POST request body.
     * @param string firstName May exist in the POST request body.
     * @param string lastName May exist in the POST request body.
     * @param string phone May exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateUser() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        
        $body = $this->requestBody;
        
        if(!isset($body['firstName']) && !isset($body['lastName']) 
            && !isset($body['email']) && !isset($body['phone'])
        ) {
            $this->respond(new Response(Response::BAD_REQUEST, 'Must specify a field to update'));
        }
        if((isset($body['firstName']) && $body['firstName'] == '')
            || (isset($body['lastName']) && $body['lastName'] == '')
        ) {
            $this->respond(new Response(Response::BAD_REQUEST, 'Name Cannot Be Empty'));
        }
        if(isset($body['email']) && $body['email'] == '') {
            $this->respond(new Response(Response::BAD_REQUEST, 'Email Cannot Be Empty'));
        }

        $user = $this->userDao->getUserByID($body['id']);
        if(!$user) {
            $this->respond(new Response(Response::BAD_REQUEST, 'User Not Found'));
        }

        if(isset($body['firstName']))
            $user->setFirstName($body['firstName']);
        if(isset($body['lastName']))
            $user->setLastName($body['lastName']);
        if(isset($body['email']) && $this->verifyUserRole('Admin', NULL))
            $user->setEmail($body['email']);
        if(isset($body['phone']))
            $user->setPhone($body['phone']);

        $user->setDateUpdated(new \DateTime());
        
        // Add the user to the database
        $ok = $this->userDao->updateUser($user);
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'User Not Updated'));
        }
        
		$this->respond(new Response(Response::OK, 'User Updated'));
    }
    
    /**
     * Updates a User's ONID.
     * 
     * @param string id Must exist in the POST request body.
     * @param string onid Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateOnid() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $this->requireParam('onid');
        
        $body = $this->requestBody;

        $user = $this->userDao->getUserByID($body['id']);
        if(!$user) {
            $this->respond(new Response(Response::BAD_REQUEST, 'User Not Found'));
        }

        $onidProvider = $this->userDao->getAuthProviderByName('onid');
        if(!$onidProvider) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Internal Server Error'));
        }
        
        // Add the user to the database
        $ok = $this->userDao->updateProviderUserID($user->getID(), $body['onid'], $onidProvider);
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'User Not Updated'));
        }
        
		$this->respond(new Response(Response::OK, 'User Updated'));
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
            case 'addUser':
                $this->handleAddUser();
                break;

            case 'startMasquerade':
                $this->handleStartMasquerade();
                break;
            case 'stopMasquerade':
                $this->handleStopMasquerade();
                break;
            case 'updateUser':
                $this->handleUpdateUser();
                break;
            case 'updateONID':
                $this->handleUpdateONID();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on User resource'));
        }
    }
}
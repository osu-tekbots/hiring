<?php
// REMOVE BELOW
/**
 * This is a template for our ActionHandler class logic. These classes do the actual processing for our APIs. Duplicate 
 * this page and replace __ModelName__ and __modelName__ with the name of the model you are creating an ActionHandler for.
 * NOTE: Use a case-sensitive replacement to preserve proper casing for variables and classes.
 */
die(); 
// REMOVE UNTIL HERE
namespace Api;

use Model\__ModelName__;
use DataAccess\__ModelName__Dao;

/**
 * Defines the logic for how to handle API requests made to modify __ModelName__ information.
 */
class __ModelName__ActionHandler extends ActionHandler {

    /** @var \DataAccess\__ModelName__Dao */
    private $__modelName__Dao;
	
    /**
     * Constructs a new instance of the __ModelName__ action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\__ModelName__Dao $__modelName__Dao The class for accessing the __ModelName__ database table
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($__modelName__Dao, $logger)
    {
        parent::__construct($logger);
		$this->__modelName__Dao = $__modelName__Dao;
    }


	/**
     * Updates the __ModelName__ state.
     * 
     * @param type id Must exist in the POST request body.
     * @param type __example__ May exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdate__ModelName__() {
        // Ensure the required parameters exist
        $this->requireParam('id');

        $body = $this->requestBody;

        // Get __ModelName__ from database
        $__modelName__ = $this->__modelName__Dao->get__ModelName__($body['id']);

        // Check if the user is allowed to update an instance
        /**
         * 
         * [NOTE: Be sure to update the array of allowed positions when creating an ActionHandler from this template.] 
         * 
         */
        $this->verifyUserRole(['Search Chair', 'Search Advocate', 'Member'], $__modelName__->getPositionID());

        // Update via setters
        $__modelName__->set__Example__($body['__example__']);

        //  Do some DAO action
		$ok = $this->__modelName__Dao->update__ModelName__($__modelName__);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, '__ModelName__ not updated'));
        }
		$this->respond(new Response(Response::OK, '__ModelName__ updated'));
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
            case 'update__ModelName__':
                $this->handleUpdate__ModelName__();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on __ModelName__ resource'));
        }
    }
}
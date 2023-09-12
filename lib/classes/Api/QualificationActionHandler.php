<?php
namespace Api;

use Model\User;
use Model\Qualification;
use DataAccess\QualificationDao;

/**
 * Defines the logic for how to handle API requests made to modify Qualification information.
 */
class QualificationActionHandler extends ActionHandler {

    /** @var \DataAccess\QualificationDao */
    private $qualificationDao;
	
    /**
     * Constructs a new instance of the Qualification action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\QualificationDao $qualificationDao The class for accessing the Qualification database table
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($qualificationDao, $logger)
    {
        parent::__construct($logger);
		$this->qualificationDao = $qualificationDao;
    }

    /**
     * Creates the Qualification state.
     * 
     * @param positionID Must exist in the POST request body.
     * @param level May exist in the POST request body.
     * @param description May exist in the POST request body.
     * @param transferable May exist in the POST request body.
     * @param screeningCriteria May exist in the POST request body.
     * @param priority May exist in the POST request body.
     * @param strengthIndicators May exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleCreateQualification() {
        // Ensure the required parameters exist
        $this->requireParam('positionID');
        
        $body = $this->requestBody;

        // Check if the user is allowed to create an instance
        $this->verifyUserRole('Search Chair', $body['positionID']);

        // Create a new qualification object with the given info
        $qualification = new Qualification();
        $qualification->setPositionID($body['positionID']);
        $qualification->setLevel($body['level'] ?? 'Preferred');
        $qualification->setDescription($body['description'] ?? NULL);
        $qualification->setTransferable($body['transferable'] ?? false);
        $qualification->setScreeningCriteria($body['screeningCriteria'] ?? NULL);
        $qualification->setPriority($body['priority'] ?? 'Low');
        $qualification->setStrengthIndicators($body['strengthIndicators'] ?? NULL);
        $qualification->setDateCreated(new \DateTime());

        // Save the new qualification in the database
		$ok = $this->qualificationDao->createQualification($qualification);
        
        // Use Response object to send DAO action results
        if($ok===false) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification not created'));
        }
		$this->respond(new Response(Response::OK, 'Qualification created', $qualification->getID()));
    }

    /**
     * Updates the Qualification state.
     * 
     * @param id Must exist in the POST request body.
     * @param level Must exist in the POST request body.
     * @param transferable Must exist in the POST request body.
     * @param priority Must exist in the POST request body.
     * @param description May exist in the POST request body.
     * @param screeningCriteria May exist in the POST request body.
     * @param strengthIndicators May exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateQualification() {
        // Ensure the required parameters exist
        $this->requireParam('id');
        $this->requireParam('level');
        $this->requireParam('transferable');
        $this->requireParam('priority');

        $body = $this->requestBody;

        // Get the current qualification
        $qualification = $this->qualificationDao->getQualification($body['id']);
        if(!$qualification) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification not found'));
        }
        
        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $qualification->getPositionID());

        // Update the qualification
        $qualification->setDescription($body['description']);
        $qualification->setScreeningCriteria($body['screeningCriteria']);
        $qualification->setStrengthIndicators($body['strengthIndicators']);
        $qualification->setLevel($body['level']);
        $qualification->setTransferable($body['transferable'] == "Yes");
        $qualification->setPriority($body['priority']);

        // Save the updated version
		$ok = $this->qualificationDao->updateQualification($qualification);
        
        // Use Response object to send DAO action results
        if($ok===false) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification not updated'));
        }
		$this->respond(new Response(Response::OK, 'Qualification updated', $qualification->getID()));
    }

    /**
     * Deletes a Qualification and all data tied to it.
     * 
     * @param id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleDeleteQualification() {
        // Ensure the required parameters exist
        $this->requireParam('id');

        $body = $this->requestBody;

        // Get the current qualification
        $qualification = $this->qualificationDao->getQualification($body['id']);
        if(!$qualification) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification not found'));
        }
        
        // Check if the user is allowed to delete an instance
        $this->verifyUserRole('Search Chair', $qualification->getPositionID());

        // Delete everything tied to the qualification
		$ok = $this->qualificationDao->deleteQualification($qualification->getID());
        
        // Use Response object to send DAO action results
        if($ok===false) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification not deleted'));
        }
		$this->respond(new Response(Response::OK, 'Qualification deleted'));
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
            case 'createQualification':
                $this->handleCreateQualification();
                break;
            case 'updateQualification':
                $this->handleUpdateQualification();
                break;
            case 'deleteQualification':
                $this->handleDeleteQualification();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on Qualification resource'));
        }
    }
}
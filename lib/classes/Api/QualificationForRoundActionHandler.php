<?php
namespace Api;

use Model\User;
use Model\QualificationForRound;
use DataAccess\QualificationDao;
use DataAccess\QualificationForRoundDao;

/**
 * Defines the logic for how to handle API requests made to modify QualificationForRound information.
 */
class QualificationForRoundActionHandler extends ActionHandler {

    /** @var \DataAccess\QualificationForRoundDao */
    private $qualificationForRoundDao;

    /** @var \DataAccess\QualificationDao */
    private $qualificationDao;

    /** @var \DataAccess\PositionDao */
    private $positionDao;
	
    /**
     * Constructs a new instance of the QualificationForRound action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\QualificationForRoundDao $qualificationForRoundDao The class for accessing the QualificationForRound database table
     * @param \DataAccess\QualificationDao $qualificationDao The class for accessing the Qualification database table
     * @param \DataAccess\PositionDao $positionDao The class for accessing the Position database table
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($qualificationForRoundDao, $qualificationDao, $positionDao, $logger)
    {
        parent::__construct($logger);
		$this->qualificationForRoundDao = $qualificationForRoundDao;
		$this->qualificationDao = $qualificationDao;
        $this->positionDao = $positionDao;
    }


	/**
     * Creates the QualificationForRound state.
     * 
     * @param string roundID Must exist in the POST request body.
     * @param string qualificationID Must exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleCreateQualificationForRound() {
        // Ensure the required parameters exist
        $this->requireParam('roundID');
        $this->requireParam('qualificationID');

        $body = $this->requestBody;

        // Get QualificationForRound from database
        $qualification = $this->qualificationDao->getQualification($body['qualificationID']);

        if(!$qualification) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification Not Found'));
        }

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $qualification->getPositionID());

        // Update via setters
        $qfr = new QualificationForRound($body['roundID'], $body['qualificationID']);

        // Store the new qualForRound in the database
		$ok = $this->qualificationForRoundDao->createQualificationForRound($qfr);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification Not Added to Round'));
        }
		$this->respond(new Response(Response::OK, 'Added Qualification To Round'));
    }

    /**
     * Deletes the QualificationForRound state.
     * 
     * @param string roundID Must exist in the POST request body.
     * @param string qualificationID Must exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleRemoveQualificationForRound() {
        // Ensure the required parameters exist
        $this->requireParam('roundID');
        $this->requireParam('qualificationID');

        $body = $this->requestBody;

        // Get QualificationForRound from database
        $qualification = $this->qualificationDao->getQualification($body['qualificationID']);

        if(!$qualification) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification Not Found'));
        }

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $qualification->getPositionID());

        // Check that interviewing hasn't begun yet
        $position = $this->positionDao->getPosition($qualification->getPositionID());
        if(!$position) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position Not Found'));
        }
        if($position->getStatus() != 'Open' && $position->getStatus() != 'Requested') {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Interviewing has already begun'));
        }

        // Remove qualForRound from the database
		$ok = $this->qualificationForRoundDao->removeQualificationForRound($body['roundID'], $body['qualificationID']);
        
        // Use Response object to send DAO action results 
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification Not Removed from Round'));
        }
		$this->respond(new Response(Response::OK, 'Qualification Removed From Round'));
    }


	/**
     * Updates the QualificationForRound state.
     * 
     * @param \Model\QualForRound[] data Must exist in the POST request body
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleUpdateQualificationForRound() {
        // Ensure the required parameters exist
        $this->requireParam('data');

        $body = $this->requestBody['data'];

        // Get QualificationForRound from database
        $qualification = $this->qualificationDao->getQualification($body[0]['qual']);

        if(!$qualification) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification Not Found'));
        }

        // Check if the user is allowed to update an instance
        $this->verifyUserRole('Search Chair', $qualification->getPositionID());

        // Update each qualForRound
        foreach($body as $qualForRound) {
            $ok = true;
            // Store the new qualForRound in the database
            if($qualForRound['value'] && !$this->qualificationForRoundDao->getQualForRound($qualForRound['qual'], $qualForRound['round'])) {
                $qfr = new QualificationForRound($qualForRound['round'], $qualForRound['qual']);
                $ok = $this->qualificationForRoundDao->createQualificationForRound($qfr);
            } else if(!$qualForRound['value'] && $this->qualificationForRoundDao->getQualForRound($qualForRound['qual'], $qualForRound['round'])) {
                // Check that interviewing hasn't begun yet
                // $position = $this->positionDao->getPosition($qualification->getPositionID());
                // if($position->getStatus() != 'Open' && $position->getStatus() != 'Requested') {
                //     $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Interviewing has already begun; you cannot unlink qualifications from rounds'));
                // }

                $ok = $this->qualificationForRoundDao->removeQualificationForRound($qualForRound['round'], $qualForRound['qual']);
            }

            // Use Response object to send DAO action results 
            if(!$ok) {
                $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Qualification Failed to Update', $qualForRound));
            }
        }
        
		$this->respond(new Response(Response::OK, 'Updated Qualification Evaluation Rounds'));
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
            case 'addQualForRound':
                $this->handleCreateQualificationForRound();
                break;
            case 'removeQualForRound':
                $this->handleRemoveQualificationForRound();
                break;
            
            case 'updateQualForRound':
                $this->handleUpdateQualificationForRound();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on QualificationForRound resource'));
        }
    }
}
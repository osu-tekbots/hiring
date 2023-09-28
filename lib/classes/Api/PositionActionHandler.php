<?php
namespace Api;

use Model\User;
use Model\Position;
use DataAccess\PositionDao;
use DataAccess\RoleDao;
use Email\HiringMailer;
use Email\Mailer;

/**
 * Defines the logic for how to handle API requests made to modify Position information.
 */
class PositionActionHandler extends ActionHandler {

    /** @var \DataAccess\PositionDao */
    private $positionDao;

    /** @var \DataAccess\CandidateDao */
    private $candidateDao;

    /** @var \DataAccess\CandidateFileDao */
    private $candidateFileDao;

    /** @var \DataAccess\CandidateRoundNoteDao */
    private $candidateRoundNoteDao;

    /** @var \DataAccess\QualificationDao */
    private $qualificationDao;

    /** @var \DataAccess\RoundDao */
    private $roundDao;

    /** @var \DataAccess\RoleDao */
    private $roleDao;

    /** @var \DataAccess\FeedbackDao */
    private $feedbackDao;

    /** @var \DataAccess\FeedbackForQualDao */
    private $ffqDao;

    /** @var \DataAccess\FeedbackFileDao */
    private $feedbackFileDao;

    /** @var \DataAccess\UserDao */
    private $userDao;

    /** @var \Util\ConfigManager */
    private $configManager;
	
    /**
     * Constructs a new instance of the Position action handler.
     * 
     * The handler will decode the JSON body and the query string associated with the request and store the results
     * internally.
     *
     * @param \DataAccess\PositionDao $positionDao The class for accessing the Position database table
     * @param \DataAccess\CandidateDao $candidateDao The class for accessing the Candidate database table
     * @param \DataAccess\CandidateFileDao $candidateFileDao The class for accessing the CandidateFile database table
     * @param \DataAccess\CandidateRoundNoteDao $candidateRoundNoteDao The class for accessing the CandidateRoundNote database table
     * @param \DataAccess\QualificationDao $qualificationDao The class for accessing the Qualification database table
     * @param \DataAccess\RoundDao $roundDao The class for accessing the Round database table
     * @param \DataAccess\RoleDao $roleDao The class for accessing the Role database table
     * @param \DataAccess\FeedbackDao $feedbackDao The class for accessing the Feedback database table
     * @param \DataAccess\FeedbackForQualDao $ffqDao The class for accessing the FeedbackForQualification database table
     * @param \DataAccess\FeedbackFileDao $feedbackFileDao The class for accessing the FeedbackFile database table
     * @param \DataAccess\UserDao $userDao The class for accessing the User database table
     * @param \Util\ConfigManager $configManager The class for accessing information in `config.ini`
     * @param \Util\Logger $logger The class for logging execution details
     */
	public function __construct($positionDao, $candidateDao, $candidateFileDao, $candidateRoundNoteDao, $qualificationDao, $roundDao, $roleDao, $feedbackDao, $ffqDao, $feedbackFileDao, $userDao, $configManager, $logger)
    {
        parent::__construct($logger);
		$this->positionDao = $positionDao;
        $this->candidateDao = $candidateDao;
        $this->candidateFileDao = $candidateFileDao;
        $this->candidateRoundNoteDao = $candidateRoundNoteDao;
        $this->qualificationDao = $qualificationDao;
        $this->roundDao = $roundDao;
        $this->roleDao = $roleDao;
        $this->feedbackDao = $feedbackDao;
        $this->ffqDao = $ffqDao;
        $this->feedbackFileDao = $feedbackFileDao;
        $this->userDao = $userDao;
        $this->configManager = $configManager;
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
     * Sends all Position information in an email to the current user.
     * 
     * @param string id Must exist in the POST request body.
     * 
     * @return \Api\Response HTTP response for whether the API call successfully completed
     */
    public function handleExportPosition() {
        // Ensure the required parameters exist
        $this->requireParam('id');

        $body = $this->requestBody;

        // Check if the user is allowed to approve an instance
        $this->verifyUserRole('Search Chair', $body['id']);
        
        // Get the position
        $position = $this->positionDao->getPosition($body['id']);
        if(!$position) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Position not found'));
        }
        // Get the user
        $user = $this->userDao->getUserByID($_SESSION['userID']);
        // Get the info tied to the position
        $candidates = $this->candidateDao->getCandidatesByPositionId($body['id']);
        $qualifications = $this->qualificationDao->getQualificationsForPosition($body['id']);
        $rounds = $this->roundDao->getAllRoundsByPositionId($body['id']);
        $members = $this->roleDao->getAllPositionMembers($body['id']);

        // Generate the email data
        $message = '
            Position Information:<br>
            &emsp;Position Title: '.$position->getTitle().'<br>
            &emsp;Current Status: '.$position->getStatus().'<br>
            &emsp;Internal Posting Link: '.$position->getPostingLink().'<br>
            &emsp;Committee Email Address: '.$position->getCommitteeEmail().'<br>
            &emsp;Date Created: '.$position->getDateCreated()?->format('l, m/d/Y \a\t H:i:s T').'<br>
            <br>
            <hr>
            <br>
            Candidates:<br>
        ';
        foreach($candidates as $candidate) {
            $candidateStatus = $candidate->getCandidateStatus();
            $candidateFiles = $this->candidateFileDao->getAllFilesForCandidate($candidate->getID());
            $message .= '
                &emsp;Name: '.$candidate->getFirstName().' '.$candidate->getLastName().'<br>
                &emsp;Email: '.$candidate->getEmail().'<br>
                &emsp;Phone: '.$candidate->getPhoneNumber().'<br>
                &emsp;Location: '.$candidate->getLocation().'<br>
                &emsp;Application Date: '.$candidate->getDateApplied()?->format('l, m/d/Y').'<br>
                &emsp;Files:<br>
                ';
            foreach($candidateFiles as $candidateFile) {
                $message .= '
                    &emsp;&emsp;<a href="'.$this->configManager->getBaseURL().'uploads/candidate/'.$candidateFile->getFileName().'">'.$candidateFile->getPurpose().'</a> (Added on: '.$candidateFile->getDateCreated()->format('l, m/d/Y \a\t H:i:s T').')<br>
                ';
            }
            if($candidateStatus) {
                $message .= '
                    &emsp;Status: '.$candidateStatus->getName().'<br>
                    &emsp;&emsp;Decided by: '.'TODO: Add role of decider '.'('.$candidateStatus->getResponsiblePartyDescription().')<br>
                    &emsp;&emsp;Specific Disposition Reason: '.$candidateStatus->getSpecificDispositionReason().'<br>
                    &emsp;&emsp;Comments: '.$candidateStatus->getComments().'<br>
                    &emsp;&emsp;Notified Via: '.$candidateStatus->getHowNotified().'<br>
                    &emsp;&emsp;Date Decided: '.$candidateStatus->getDateDecided()->format('l, m/d/Y \a\t H:i:s T').'<br>
                ';
            }

            foreach($rounds as $index=>$round) {
                $message .= '&emsp;<table style="border: 1px solid; width: 80%; margin-left: 20px; border-collapse: collapse">
                    <tr>
                        <th colspan="100%" style="border: 1px solid;">'.($index + 1).': '.$round->getName().'</th>
                    </tr>
                    <tr">
                        <th style="border: 1px solid;">Qualification</th>
                ';
                foreach($members as $member) {
                    $message .= '<th style="border: 1px solid;">'.$member->getUser()->getFirstName().' '.$member->getUser()->getLastName().'</th>'."\n";
                }
                $message .= '</tr>'."\n";

                // Output each qualification row in table
                foreach($qualifications as $qualification) {
                    $message .= '<tr">'."\n";

                    $message .= '<td style="border: 1px solid;">
                        <span>
                            '.$qualification->getDescription().'
                        </span></td>
                    ';
                    foreach($members as $member) {
                        $rating = $this->ffqDao->getQualStatusName($member->getUser()->getID(), $candidate->getID(), $round->getID(), $qualification->getID());
                        $message .= '<td style="border: 1px solid;">'.($rating ? $rating : '--').'</td>'."\n";
                    }

                    $message .= '</tr>'."\n";
                }
                
                // Output notes row in table
                $message .= '<tr>
                                <td style="border: 1px solid;">Notes</td>
                ';
                foreach($members as $member) {
                    $feedback = $this->feedbackDao->getFeedbackForUser($member->getUser()->getID(), $candidate->getID(), $round->getID());
                    if($feedback) {
                        $feedbackFiles = $this->feedbackFileDao->getAllFilesForFeedback($feedback->getID());
                        $message .= '<td style="border: 1px solid;">'.$feedback->getNotes()."\n";
                        foreach($feedbackFiles as $feedbackFile) {
                            $message .= '<br><a target="_blank" href="uploads/feedback/'.$feedbackFile->getFileName().'">'.$feedbackFile->getFileName().'</a>'."\n";
                        }
                        $message .= '</td>'."\n";
                    } else {
                        $message .= '<td style="border: 1px solid;"></td>'."\n";
                    }
                }
                $message .= '</tr>'."\n";

                // Output group notes row in table
                $roundNote = $this->candidateRoundNoteDao->getCandidateNotesForRound($candidate->getID(), $round->getID());
                $message .= '<tr>
                                <td style="border: 1px solid;">Group Notes</td>
                                <td colspan="100%" style="border: 1px solid;">'.($roundNote ? $roundNote->getNotes() : '').'</td>
                </tr>'."\n";

                $message .= '</table>'."\n";
            }

            $message .= '
                <br>
            ';
        }
        $message .= '
            <hr>
            <br>
            Qualifications:<br>
        ';
        foreach($qualifications as $qualification) {
            $message .= '
                &emsp;Description: '.$qualification->getDescription().'<br>
                &emsp;Screening Criteria: '.$qualification->getScreeningCriteria().'<br>
                &emsp;Strength Indicators: '.$qualification->getStrengthIndicators().'<br>
                &emsp;Level: '.$qualification->getLevel().'<br>
                &emsp;Priority: '.$qualification->getPriority().'<br>
                &emsp;Tranferable: '.($qualification->getTransferable() ? 'Yes' : 'No').'<br>
                &emsp;Date Created: '.$qualification->getDateCreated()->format('l, m/d/Y \a\t H:i:s T').'<br>
                <br>
            ';
        }
        $message .= '
            <hr>
            <br>
            Rounds:<br>
        ';
        foreach($rounds as $round) {
            $message .= '
                &emsp;Name: '.$round->getName().'<br>
                &emsp;Interview Questions: <a href="'.$round->getInterviewQuestionLink().'">'.$round->getInterviewQuestionLink().'<a><br>
                &emsp;Date Created: '.$round->getDateCreated()->format('l, m/d/Y \a\t H:i:s T').'<br>
                <br>';
        }
        $message .= '
            <hr>
            <br>
            Committee Members:<br>
        ';
        foreach($members as $member) {
            $message .= '
                &emsp;'.$member->getUser()->getFirstName().' '.$member->getUser()->getLastName().' ('.$member->getRole()->getName().')<br>
            ';
        }
        $message .= '
            <br>
            <hr>
            <br>
            <!-- Next thing goes here -->
        ';
        

        $mailer = new Mailer($this->configManager->get('email.admin_address'), $this->configManager->get('email.admin_subject_tag'), $this->logger);

        $ok = $mailer->sendEmail($user->getEmail(), 'Search Committee Export', $message, true);
        
        // Use Response object to send email action results
        if(!$ok) {
            $this->respond(new Response(Response::INTERNAL_SERVER_ERROR, 'Data Not Exported'));
        }
		$this->respond(new Response(Response::OK, 'Data Emailed To You'));
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
            case 'exportPosition':
                $this->handleExportPosition();
                break;

            default:
                $this->respond(new Response(Response::BAD_REQUEST, 'Invalid action on Position resource'));
        }
    }
}
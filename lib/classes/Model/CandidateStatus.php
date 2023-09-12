<?php
// Updated 11/5/2019
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a candidate's status
 */
class CandidateStatus {

    /** @var string */
    private $id;

    /** @var int */
    private $statusID;

    /** @var string */
    private $statusName;

    /** @var string */
    private $specificDispositionReason;

    /** @var string */
    private $userID;

    /** @var string */
    private $responsiblePartyDescription;

    /** @var string */
    private $comments;

    /** @var string */
    private $howNotified;

    /** @var \DateTime */
    private $dateDecided;

    /**
     * Constructs a new instance of a candidate status in the hiring system.
     * 
     * If no ID is provided, the alphanumeric ID will be generated using a random, cryptographically secure approach.
     *
     * @param string|null $id the ID of the candidate status. If null, a new ID will be generated for the candidate.
     */
    public function __construct($id = null) {
        if ($id == null) {
            $id = IdGenerator::generateSecureUniqueId();
            $this->setID($id);
            $this->setDateDecided(new \DateTime());
        } else {
			$this->setID($id);
        }
    }
    /** 
     * Getters and Setters
    */
    
	public function getID(){
		return $this->id;
	}

	public function setID($id){
		$this->id = $id;
	}
    
    public function getStatusID() {
        return $this->statusID;
    }

    public function setStatusID($statusID) {
        $this->statusID = $statusID;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($statusName) {
        $this->name = $statusName;
    }

    public function getSpecificDispositionReason() {
        return $this->specificDispositionReason;
    }

    public function setSpecificDispositionReason($specificDispositionReason) {
        $this->specificDispositionReason = $specificDispositionReason;
    }

    //TODO: Update for user
	public function getUserID(){
		return $this->userID;
	}

	public function setUserID($userID){
		$this->userID = $userID;
	}

	public function getResponsiblePartyDescription(){
		return $this->responsiblePartyDescription;
	}

	public function setResponsiblePartyDescription($responsiblePartyDescription){
		$this->responsiblePartyDescription = $responsiblePartyDescription;
	}

	public function getComments(){
		return $this->comments;
	}

	public function setComments($comments){
		$this->comments = $comments;
	}

	public function getHowNotified(){
		return $this->howNotified;
	}

	public function setHowNotified($howNotified){
		$this->howNotified = $howNotified;
	}

	public function getDateDecided(){
		return $this->dateDecided;
	}

	public function setDateDecided($dateDecided){
		$this->dateDecided = $dateDecided;
	}

}
?>
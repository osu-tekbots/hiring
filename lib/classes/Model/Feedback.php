<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing an Feedback
 */
class Feedback {
    
	/** @var int */
	private $id;
    /** @var string */
	private $userID;
    /** @var string */
    private $candidateID;
    /** @var string */
	private $roundID;
    /** @var string */
	private $notes;

    /** @var \DateTime */
    private $lastUpdated;
		
    /**
     * Creates a new instance of an Feedback.
     * 
     * @param string|null $idKey the ID of the Feedback. If null, a random ID will be generated.
    */
    public function __construct($id = null) {
        if ($id == null) {
            $id = IdGenerator::generateSecureUniqueId();
            $this->setID($id);
        } else {
			$this->setID($id);
        }
    }
    
    /**
     * Getter and Setters
     */
    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

    public function getUserID(){
		return $this->userID;
	}

	public function setUserID($userid){
		$this->userID = $userid;
	}

    public function getCandidateID(){
		return $this->candidateID;
	}

    public function setCandidateID($candidateid){
		$this->candidateID = $candidateid;
	}

    public function getRoundID(){
		return $this->roundID;
	}

	public function setRoundID($roundid){
		$this->roundID = $roundid;
	}

    public function getNotes(){
		return $this->notes;
	}

	public function setNotes($notes){
		$this->notes = $notes;
	}

    public function getLastUpdated(){
		return $this->lastUpdated;
	}

	public function setLastUpdated($lastUpdated){
		$this->lastUpdated = $lastUpdated;
	}

}
?>
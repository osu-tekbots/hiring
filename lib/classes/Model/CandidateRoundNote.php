<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a CandidateRoundNote
 */
class CandidateRoundNote {
    
	/** @var int */
	private $id;

    /** @var string */
    private $candidateID;

    /** @var string */
    private $roundID;

    /** @var string */
    private $notes;

    /** @var \DateTime */
    private $dateUpdated;
		
    /**
     * Creates a new instance of a CandidateRoundNote.
     * 
     * @param string|null $idKey the ID of the CandidateRoundNote. If null, a random ID will be generated.
    */
    public function __construct($id = null) {
        if ($id == null) {
            $id = IdGenerator::generateSecureUniqueId();
            $this->setID($id);
            $this->setDateUpdated(new \DateTime());
        } else {
			$this->setID($id);
        }
    }
    
    /**
     * Getter and Setters
     */
    public function getID(){
		return $this->id;
	}

	public function setID($id){
		$this->id = $id;
	}

    public function getCandidateID(){
		return $this->candidateID;
	}

	public function setCandidateID($candidateID){
		$this->candidateID = $candidateID;
	}

    public function getRoundID(){
		return $this->roundID;
	}

	public function setRoundID($roundID){
		$this->roundID = $roundID;
	}

    public function getNotes(){
		return $this->notes;
	}

	public function setNotes($notes){
		$this->notes = $notes;
	}

    public function getDateUpdated(){
		return $this->dateUpdated;
	}

	public function setDateUpdated($dateUpdated){
		$this->dateUpdated = $dateUpdated;
	}

}
?>
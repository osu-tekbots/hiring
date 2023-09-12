<?php
// Updated 11/5/2019
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a round
 */
class Round {

    /** @var string */
    private $id;

    /** @var string */
    private $positionID;

    /** @var string */
    private $description;

    /** @var string */
    private $interviewQLink;

    /** @var \DateTime */
    private $dateCreated;

    /**
     * Constructs a new instance of a round in the hiring system.
     * 
     * If no ID is provided, the alphanumeric ID will be generated using a random, cryptographically secure approach.
     *
     * @param string|null $id the ID of the round. If null, a new ID will be generated for the round.
     */
    public function __construct($id = null) {
        if ($id == null) {
            $id = IdGenerator::generateSecureUniqueId();
            $this->setID($id);
            $this->setDateCreated(new \DateTime());
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
    
	public function getPositionID(){
		return $this->positionID;
	}

	public function setPositionID($positionID){
		$this->positionID = $positionID;
	}

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getInterviewQuestionLink() {
        return $this->interviewQLink;
    }

    public function setInterviewQuestionLink($interviewQLink) {
        $this->interviewQLink = $interviewQLink;
    }

	public function getDateCreated(){
		return $this->dateCreated;
	}

	public function setDateCreated($dateCreated){
		$this->dateCreated = $dateCreated;
	}

}
?>
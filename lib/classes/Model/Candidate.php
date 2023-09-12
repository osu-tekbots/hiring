<?php
// Updated 11/5/2019
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a candidate
 */
class Candidate {

    /** @var string */
    private $id;

    /** @var string */
    private $fName;

    /** @var string */
    private $lName;

    /** @var string */
    private $location;

    /** @var string */
    private $email;

    /** @var string */
    private $phone;

    /** @var CandidateStatus */
    private $candidateStatus;

    /** @var string */
    private $positionID;

    /** @var \DateTime */
    private $dateCreated;

    /** @var \DateTime */
    private $dateApplied;

    /**
     * Constructs a new instance of a candidate in the hiring system.
     * 
     * If no ID is provided, the alphanumeric ID will be generated using a random, cryptographically secure approach.
     *
     * @param string|null $id the ID of the candidate. If null, a new ID will be generated for the candidate.
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

    public function getFirstName() {
        return $this->fName;
    }

    public function setFirstName($fName) {
        $this->fName = $fName;
    }

    public function getLastName() {
        return $this->lName;
    }

    public function setLastName($lName) {
        $this->lName = $lName;
    }

	public function getLocation(){
		return $this->location;
	}

	public function setLocation($location){
		$this->location = $location;
	}

	public function getEmail(){
		return $this->email;
	}

	public function setEmail($email){
		$this->email = $email;
	}

	public function getPhoneNumber(){
		return $this->phone;
	}

	public function setPhoneNumber($phone){
		$this->phone = $phone;
	}

	public function getCandidateStatus(){
		return $this->candidateStatus;
	}

	public function setCandidateStatus($candidateStatus){
		$this->candidateStatus = $candidateStatus;
	}

	public function getPositionID(){
		return $this->positionID;
	}

	public function setPositionID($positionID){
		$this->positionID = $positionID;
	}

	public function getDateCreated(){
		return $this->dateCreated;
	}

	public function setDateCreated($dateCreated){
		$this->dateCreated = $dateCreated;
	}

	public function getDateApplied(){
		return $this->dateApplied;
	}

	public function setDateApplied($dateApplied){
		$this->dateApplied = $dateApplied;
	}

}
?>
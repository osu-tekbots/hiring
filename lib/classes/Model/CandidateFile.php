<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing an CandidateFile
 */
class CandidateFile {
    
	/** @var int */
	private $id;

    /** @var int */
	private $candidateid;

    /** @var string */
	private $filename;

    /** @var string */
	private $purpose;

    /** @var \DateTime */
    private $dateCreated;
		
    /**
     * Creates a new instance of an CandidateFile.
     * 
     * @param string|null $idKey the ID of the CandidateFile. If null, a random ID will be generated.
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
     * Getter and Setters
     */
    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

    public function getCandidateId(){
		return $this->candidateid;
	}

	public function setCandidateId($candidateid){
		$this->candidateid = $candidateid;
	}

    public function getFileName(){
		return $this->filename;
	}

	public function setFileName($filename){
		$this->filename = $filename;
	}

    public function getPurpose(){
		return $this->purpose;
	}

	public function setPurpose($purpose){
		$this->purpose = $purpose;
	}

    public function getDateCreated(){
		return $this->dateCreated;
	}

	public function setDateCreated($dateCreated){
		$this->dateCreated = $dateCreated;
	}

}
?>
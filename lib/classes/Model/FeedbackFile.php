<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing an FeedbackFile
 */
class FeedbackFile {
    
	/** @var int */
	private $id;

    /** @var int */
	private $feedbackID;

    /** @var string */
	private $fileName;

    /** @var \DateTime */
    private $dateCreated;
		
    /**
     * Creates a new instance of an FeedbackFile.
     * 
     * @param string|null $idKey the ID of the FeedbackFile. If null, a random ID will be generated.
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

    public function getFeedbackId(){
		return $this->feedbackID;
	}

	public function setFeedbackId($feedbackid){
		$this->feedbackID = $feedbackid;
	}

    public function getFileName(){
		return $this->fileName;
	}

	public function setFileName($fileName){
		$this->fileName = $fileName;
	}

    public function getDateCreated(){
		return $this->dateCreated;
	}

	public function setDateCreated($dateCreated){
		$this->dateCreated = $dateCreated;
	}

}
?>
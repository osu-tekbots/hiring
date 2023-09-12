<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing an FeedbackForQual
 */
class FeedbackForQual {
    
	
    /** @var int */
	private $feedbackID;
    /** @var int */
    private $feedbackQualificationStatusID;
    /** @var int */
	private $qualificationID;
		
    /**
     * Creates a new instance of an FeedbackForQual.
     * 
     * @param string|null $idKey the ID of the FeedbackForQual. If null, a random ID will be generated.
    */
    public function __construct() {

    }

    public function getFeedbackID(){
		return $this->feedbackID;
	}

	public function setFeedbackID($feedbackID){
		$this->feedbackID = $feedbackID;
	}

    public function getFeedbackQualificationStatusID(){
		return $this->feedbackQualificationStatusID;
	}

    public function setFeedbackQualificationStatusID($statusid){
		$this->feedbackQualificationStatusID = $statusid;
	}

    public function getQualificationID(){
		return $this->qualificationID;
	}

	public function setQualificationID($qualificationID){
		$this->qualificationID = $qualificationID;
	}

	

}
?>
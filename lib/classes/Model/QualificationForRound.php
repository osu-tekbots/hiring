<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing an QualificationForRound
 */
class QualificationForRound {
    
	/** @var int */
	private $roundid;
    /** @var int */
	private $qualificationid;
		
    /**
     * Creates a new instance of an QualificationForRound.
     * 
     * @param string|null $idKey the ID of the QualificationForRound. If null, a random ID will be generated.
    */
    public function __construct($roundid, $qualificationid) {
        $this->setRoundID($roundid);
        $this->setQualificationID($qualificationid);
        
    }
    
    /**
     * Getter and Setters
     */
    public function getRoundID(){
		return $this->roundid;
	}

	public function setRoundID($roundid){
		$this->roundid = $roundid;
	}

    public function getQualificationID(){
		return $this->qualificationid;
	}

	public function setQualificationID($qualificationid){
		$this->qualificationid = $qualificationid;
	}

}
?>
<?php
// Updated 11/5/2019
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a qualification
 */
class Qualification {

    /** @var string */
    private $id;

    /** @var string */
    private $positionID;

    /** @var string */ //-- Is that right?? Is there anything you do for a SQL enum?
    private $level;

    /** @var string */
    private $description;

    /** @var bool */ 
    private $transferable;

    /** @var string */
    private $screeningCriteria;

    /** @var string */ //-- Is that right?? Is there anything you do for a SQL enum?
    private $priority;

    /** @var string */
    private $strengthIndicators;

    /** @var \DateTime */
    private $dateCreated;

    /**
     * Constructs a new instance of a qualification in the hiring system.
     * 
     * If no ID is provided, the alphanumeric ID will be generated using a random, cryptographically secure approach.
     *
     * @param string|null $id the ID of the qualification. If null, a new ID will be generated for the qualification.
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

    public function getPositionID() {
        return $this->positionID;
    }

    public function setPositionID($positionID) {
        $this->positionID = $positionID;
    }

    public function getLevel() {
        return $this->level;
    }

    public function setLevel($level) {
        $this->level = $level;
    }

	public function getDescription(){
		return $this->description;
	}

	public function setDescription($description){
		$this->description = $description;
	}

	public function getTransferable(){
		return $this->transferable;
	}

	public function setTransferable($transferable){
		$this->transferable = $transferable;
	}

	public function getScreeningCriteria(){
		return $this->screeningCriteria;
	}

	public function setScreeningCriteria($screeningCriteria){
		$this->screeningCriteria = $screeningCriteria;
	}

	public function getPriority(){
		return $this->priority;
	}

	public function setPriority($priority){
		$this->priority = $priority;
	}

	public function getStrengthIndicators(){
		return $this->strengthIndicators;
	}

	public function setStrengthIndicators($strengthIndicators){
		$this->strengthIndicators = $strengthIndicators;
	}

	public function getDateCreated(){
		return $this->dateCreated;
	}

	public function setDateCreated($dateCreated){
		$this->dateCreated = $dateCreated;
	}

}
?>
<?php
// REMOVE BELOW
/**
 * This is a template for our Model class logic. These classes each represent a table in our SQL database. Duplicate 
 * this page and replace __ModelName__ with the name of the model you are creating a Model for.
 */
die(); 
// REMOVE UNTIL HERE
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a __ModelName__
 */
class __ModelName__ {
    
	/** @var int */
	private $id;

    /**
     * [Insert Model Variables]
     */

    /** @var \DateTime */
    private $dateCreated;
		
    /**
     * Creates a new instance of a __ModelName__.
     * 
     * @param string|null $idKey the ID of the __ModelName__. If null, a random ID will be generated.
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

    /**
     * [Insert model fields here]
     */

    public function getDateCreated(){
		return $this->dateCreated;
	}

	public function setDateCreated($dateCreated){
		$this->dateCreated = $dateCreated;
	}

}
?>
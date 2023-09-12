<?php
// Updated 11/5/2019
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a role
 */
class Role {

    /** @var string */
    private $id;

    /** @var string */
    private $name;

    /**
     * Constructs a new instance of a role in the hiring system.
     * 
     * If no ID is provided, the alphanumeric ID will be generated using a random, cryptographically secure approach.
     *
     * @param string|null $id the ID of the role. If null, a new ID will be generated for the role.
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
     * Getters and Setters
    */
    
	public function getID(){
		return $this->id;
	}

	public function setID($id){
		$this->id = $id;
	}

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

}
?>
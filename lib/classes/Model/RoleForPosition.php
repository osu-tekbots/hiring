<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a RoleForPosition
 */
class RoleForPosition {
    
	/** @var int */
	private $id;

    /** @var User */
    private $user;

    /** @var Position */
    private $position;

    /** @var Role */
    private $role;
		
    /**
     * Creates a new instance of an RoleForPosition.
     * 
     * @param string|null $idKey the ID of the RoleForPosition. If null, a random ID will be generated.
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
     * Getter and Setters
     */
    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

    public function getUser(){
		return $this->user;
	}

	public function setUser($user){
		$this->user = $user;
	}

    public function getPosition(){
		return $this->position;
	}

	public function setPosition($position){
		$this->position = $position;
	}

    public function getRole(){
		return $this->role;
	}

	public function setRole($role){
		$this->role = $role;
	}

}
?>
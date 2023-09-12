<?php
// Updated 11/5/2019
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a user
 */
class User {

    /** @var string */
    private $id;

    /** @var string */
    private $accessLevel;

    /** @var string */
    private $firstName;

    /** @var string */
    private $lastName;

    /** @var string */
    private $email;

    /** @var string */
    private $phone;

    /** @var \DateTime */
    private $dateUpdated;

    /** @var \DateTime */
    private $dateCreated;

     /**
     * Constructs a new instance of a user in the hiring system.
     * 
     * If no ID is provided, the alphanumeric ID will be generated using a random, cryptographically secure approach.
     *
     * @param string|null $id the ID of the user. If null, a new ID will be generated for the user.
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

	public function getAccessLevel(){
		return $this->accessLevel;
	}

	public function setAccessLevel($accessLevel){
		$this->accessLevel = $accessLevel;
	}

	public function getFirstName(){
		return $this->firstName;
	}

	public function setFirstName($firstName){
		$this->firstName = $firstName;
	}

	public function getLastName(){
		return $this->lastName;
	}

	public function setLastName($lastName){
		$this->lastName = $lastName;
	}

	public function getEmail(){
		return $this->email;
	}

	public function setEmail($email){
		$this->email = $email;
	}

	public function getPhone(){
		return $this->phone;
	}

	public function setPhone($phone){
		$this->phone = $phone;
	}

	public function getDateUpdated(){
		return $this->dateUpdated;
	}

	public function setDateUpdated($dateUpdated){
		$this->dateUpdated = $dateUpdated;
	}

	public function getDateCreated(){
		return $this->dateCreated;
	}

	public function setDateCreated($dateCreated){
		$this->dateCreated = $dateCreated;
	}

}
?>
<?php
// Updated 11/5/2019
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a position
 */
class Position {

    /** @var string */
    private $id;

    /** @var string */
    private $title;

    /** @var string */
    private $postingLink;

    /** @var \DateTime */
    private $dateCreated;

    /** @var string */
    private $committeeEmail;

    /** @var string */
    private $status;

    /**
     * Constructs a new instance of a position in the hiring system.
     * 
     * If no ID is provided, the alphanumeric ID will be generated using a random, cryptographically secure approach.
     *
     * @param string|null $id the ID of the position. If null, a new ID will be generated for the position.
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

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getPostingLink() {
        return $this->postingLink;
    }

    public function setPostingLink($postingLink) {
        $this->postingLink = $postingLink;
    }

	public function getDateCreated(){
		return $this->dateCreated;
	}

	public function setDateCreated($dateCreated){
		$this->dateCreated = $dateCreated;
	}

	public function getCommitteeEmail(){
		return $this->committeeEmail;
	}

	public function setCommitteeEmail($committeeEmail){
		$this->committeeEmail = $committeeEmail;
	}

	public function getStatus(){
		return $this->status;
	}

	public function setStatus($status){
		$this->status = $status;
	}

}
?>
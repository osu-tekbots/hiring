<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing a Message
 */
class Message {
    
	/** @var int */
	private $id;

    /** @var string */
    private $subject;

    /** @var string */
    private $body;

    /** @var string */
    private $purpose;
		
    /**
     * Creates a new instance of a Message.
     * 
     * @param string|null $idKey the ID of the Message. If null, a random ID will be generated.
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

    public function getSubject(){
		return $this->subject;
	}

	public function setSubject($subject){
		$this->subject = $subject;
	}

    public function getBody(){
		return $this->body;
	}

	public function setBody($body){
		$this->body = $body;
	}

    public function getPurpose(){
		return $this->purpose;
	}

	public function setPurpose($purpose){
		$this->purpose = $purpose;
	}

}
?>
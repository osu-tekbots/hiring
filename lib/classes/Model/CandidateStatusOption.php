<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing an CandidateStatusOption object
 */
class CandidateStatusOption {
    
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var bool */
    private $active;
        
    /**
     * Creates a new instance of an CandidateStatusOption.
     * 
     * @param string|null $idKey the ID of the CandidateStatusOption. If null, a random ID will be generated.
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
    
    public function getName(){
        return $this->name;
    }

    public function setName($name){
        $this->name = $name;
    }
    
    public function getActive(){
        return $this->active;
    }

    public function setActive($active){
        $this->active = $active;
    }

}
?>
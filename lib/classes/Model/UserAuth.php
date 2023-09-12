<?php
namespace Model;

use Util\IdGenerator;

/**
 * Data structure representing an UserAuth object (links provider, auth ID, and user)
 */
class UserAuth {
    
    /** @var int */
    private $id;

    /** @var string */
    private $userID;

    /** @var UserAuthProvider */
    private $authProvider;

    /** @var string The ID returned by the auth provider */
    private $providerID;
        
    /**
     * Creates a new instance of an UserAuth.
     * 
     * @param string|null $idKey the ID of the UserAuth. If null, a random ID will be generated.
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
    
    public function getUserID(){
        return $this->userID;
    }

    public function setUserID($userID){
        $this->userID = $userID;
    }
    
    public function getAuthProvider(){
        return $this->authProvider;
    }

    public function setAuthProvider($authProvider){
        $this->authProvider = $authProvider;
    }
    
    public function getProviderID(){
        return $this->providerID;
    }

    public function setProviderID($providerID){
        $this->providerID = $providerID;
    }
    

}
?>
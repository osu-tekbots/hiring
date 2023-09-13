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

    /** @var string */
    private $inserts;
        
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

    public function getInserts(){
        return $this->inserts;
    }

    public function setInserts($inserts){
        $this->inserts = $inserts;
    }

    
    /**
     * Replaces keywords (based on the pattern {{replace_me}}) in the message subject using the given array of keywords
     *
     * @param string[] $keywords An associative array with keywords to replace as indices and their replacements as values
     * 
     * @return string The message subject with keywords replaced
     */
    public function fillTemplateSubject($keywords){
        $result = $this->subject;
        if ($keywords != '')
            foreach ($keywords as $k => $v) {	
                $result =  str_replace('{{' . $k . '}}', $v ?? '', $result);
            }
        return $result;
    }

    /**
     * Replaces keywords (based on the pattern {{replace_me}}) in the message body using the given array of keywords
     * 
     * @param string[] $keywords An associative array with keywords to replace as indices and their replacements as values
     * 
     * @return string The message body with keywords replaced
     */
    public function fillTemplateBody($keywords){
        $result = $this->body;
        if ($keywords != '')
            foreach ($keywords as $k => $v) {	
                $result =  str_replace('{{' . $k . '}}', $v ?? '', $result);
            }
        return $result;
    }
}
?>
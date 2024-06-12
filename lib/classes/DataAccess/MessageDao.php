<?php
namespace DataAccess;

use Model\Message;

/**
 * Handles all of the logic related to queries on loading/editing Messages in the database.
 */
class MessageDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a Message Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform Message-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching Message data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the Messages from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Message[]|boolean an array of Message objects if the fetch succeeds, false otherwise
     */
    public function getAllMessages() {
        try {
            $sql = 'SELECT * FROM hiring_Message;';
            $result = $this->conn->query($sql);

            if(!$result)
                return false;

            return \array_map('self::ExtractMessageFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Messages: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches the Message with a specific id from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $messageID The Message to get
     *
     * @return \Model\Message|boolean a Message object if the fetch succeeds, false otherwise
     */
    public function getMessageByID($messageID) {
        try {
            $sql = 'SELECT * FROM hiring_Message WHERE m_id = :messageID;';
            $params = array(':messageID' => $messageID);

            $result = $this->conn->query($sql, $params);

            if(!$result)
                return false;
            if(count($result) > 1)
                $this->logger->warn('More than one Message found for ID: ' . $messageID);

            return self::ExtractMessageFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Message by ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Updates a Message in the database.
     *
     * @param \Model\Message $message the Message to update in the database
     * 
     * @return boolean true if the update execution succeeds, false otherwise.
     */
    public function updateMessage($message) {
        try {
            $sql = 'UPDATE hiring_Message
                SET `m_subject` = :subject,
                    `m_body` = :body
                WHERE `m_id` = :id;';
            $params = array(
                ':subject' => $message->getSubject(),
                ':body' => $message->getBody(),
                ':id' => $message->getID()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update Message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a new Message object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing Message information
     * 
     * @return \Model\Message
     */
    public static function ExtractMessageFromRow($row) {
        $message = new Message($row['m_id']);
        $message->setSubject($row['m_subject']);
        $message->setBody($row['m_body']);
        $message->setPurpose($row['m_purpose']);
        $message->setInserts($row['m_inserts']);

        return $message;
    }

    /**
     * Logs an error if a logger was provided to the class when it was constructed.
     * 
     * Essentially a wrapper around the error logging so we don't cause the equivalent of a null pointer exception.
     *
     * @param string $message the message to log.
     * 
     * @return void
     */
    private function logError($message) {
        if ($this->logger != null) {
            $this->logger->error($message);
        }
        if ($this->echoOnError) {
            echo "$message\n";
        }
    }
    
}

?>
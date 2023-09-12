<?php
// REMOVE BELOW
/**
 * This is a template for our DataAccessObject logic. These classes control all interactions with our SQL database. 
 * Duplicate this page and replace __ModelName__ and __modelName__ with the name of the model you are creating a 
 * DataAccessObject for.
 * NOTE: Use a case-sensitive replacement to preserve proper casing for variables and classes.
 */
die(); 
// REMOVE UNTIL HERE
namespace DataAccess;

use Model\__ModelName__;

/**
 * Handles all of the logic related to queries on loading/editing __ModelName__s in the database.
 */
class __ModelName__Dao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a __ModelName__ Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform __ModelName__-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching __ModelName__ data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the __ModelName__s from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return __ModelName__[]|boolean an array of __ModelName__ objects if the fetch succeeds, false otherwise
     */
    public function getAll__ModelName__s() {
        try {
            $sql = 'SELECT * FROM __modelName__;';
            $result = $this->conn->query($sql);

            if(!$result)
                return false;

            return \array_map('self::Extract__ModelName__FromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch __ModelName__s: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches the __ModelName__ with a specific id from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $__modelName__ID The __ModelName__ to get
     *
     * @return __ModelName__|boolean a __ModelName__ object if the fetch succeeds, false otherwise
     */
    public function get__ModelName__ByID($__modelName__ID) {
        try {
            $sql = 'SELECT * FROM __ModelName__ WHERE _id = :__modelName__ID;';
            $params = array(':__modelName__ID' => $__modelName__ID);

            $result = $this->conn->query($sql, $params);

            if(!$result)
                return false;
            if(count($result) > 1)
                $this->logger->warn('More than one __ModelName__ found for ID: ' . $__modelName__ID);

            return self::Extract__ModelName__FromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch __ModelName__ by ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Updates a __ModelName__ in the database.
     *
     * @param \Model\__ModelName__ $__modelName__ the __ModelName__ to update in the database
     * 
     * @return boolean true if the update execution succeeds, false otherwise.
     */
    public function update__ModelName__($__modelName__) {
        try {
            $sql = 'UPDATE __ModelName__
                SET `_lastUpdated` = :__modelName__LastUpdated
                WHERE `_id` = :__modelName__ID;';
            $params = array(
                ':__modelName__LastUpdated' => $__modelName__->getLastUpdated()->format('Y-m-d H:i:s'),
                ':__modelName__ID' => $__modelName__->getID()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update __ModelName__: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a new __ModelName__ object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing __ModelName__ information
     * 
     * @return \Model\__ModelName__
     */
    public static function Extract__ModelName__FromRow($row) {
        $__modelName__ = new __ModelName__($row['_id']);
        $__modelName__->setDateCreated(new \DateTime(($row['_dateCreated'] == '' ? 'now' : $row['dateCreated'])));

        return $__modelName__;
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
<?php
// Updated 11/5/2019
namespace DataAccess;

use Model\QualificationStatus;


/**
 * Contains logic for database interactions with qualification status data in the database. 
 * 
 * DAO stands for 'Data Access Object'
 */
class QualificationStatusDao {

    /** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;

    /** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a Qualification Status Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform qualification status-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching qualification status data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the qualification statuses from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return QualificationStatus[]|boolean an array of QualificationStatus objects if the fetch succeeds, false otherwise
     */
    public function getAllStatuses() {
        try {
            $sql = 'SELECT * FROM hiring_QualificationStatusEnum';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractStatusFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch qualification statuses: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a new QualificationStatus object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing qualification status information
     * @return \Model\QualificationStatus
     */
    public static function ExtractStatusFromRow($row) {
        $qualificationStatus = new QualificationStatus($row['qse_id']);
        $qualificationStatus->setName($row['qse_name']);

        return $qualificationStatus;
    }

    /**
     * Logs an error if a logger was provided to the class when it was constructed.
     * 
     * Essentially a wrapper around the error logging so we don't cause the equivalent of a null pointer exception.
     *
     * @param string $message the message to log.
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

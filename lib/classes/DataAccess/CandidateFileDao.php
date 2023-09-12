<?php
namespace DataAccess;

use Model\CandidateFile;

/**
 * Handles all of the logic related to queries on loading/editing CandidateFiles in the database.
 */
class CandidateFileDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a CandidateFile Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform CandidateFile-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching CandidateFile data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the CandidateFiles from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param integer $candidateID The ID for the candidate to fetch files for
     *
     * @return CandidateFile[]|boolean an array of CandidateFile objects if the fetch succeeds, false otherwise
     */
    public function getAllFilesForCandidate($candidateID) {
        try {
            $sql = 'SELECT * FROM hiring_CandidateFiles
                WHERE `cf_c_id` = :candidateID
                ORDER BY `cf_id` ASC;';
            $params = array(
                ':candidateID' => $candidateID
            );
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractCandidateFileFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch all files for candidate: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a specific CandidateFile from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param integer $fileID The ID for the file to fetch
     *
     * @return CandidateFile|boolean a CandidateFile object if the fetch succeeds, false otherwise
     */
    public function getFile($fileID) {
        try {
            $sql = 'SELECT * FROM hiring_CandidateFiles
                WHERE `cf_id` = :id;';
            $params = array(
                ':id' => $fileID
            );
            $result = $this->conn->query($sql, $params);

            if(!count($result))
                return false;

            return self::ExtractCandidateFileFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch CandidateFile by ID: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Inserts new CandidateFiles row into the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param $cf candidate file model
     *
     * @return integer|boolean the new row ID if insert query succeeds, false otherwise
     */
    public function createCandidateFile($cf) {
        try {
            $sql = 'INSERT INTO `hiring_CandidateFiles`(`cf_c_id`, `cf_fileName`, `cf_purpose`, `cf_dateCreated`) VALUES (:candidateid,:filename,:purpose,:datecreated);';
            $params = array(
                ':candidateid' => $cf->getCandidateId(),
                ':filename' => $cf->getFileName(),
                ':purpose' => $cf->getPurpose(),
                ':datecreated' => $cf->getDateCreated()->format('Y-m-d H:i:s')
            );
            $result = $this->conn->execute($sql, $params, true);

            return $result;
        } catch (\Exception $e) {
            $this->logError('Failed to add CandidateFile: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Updates a CandidateFile in the database.
     *
     * @param \Model\CandidateFile $candidateFile the CandidateFile to update in the database
     * 
     * @return boolean true if the update execution succeeds, false otherwise.
     */
    public function updateCandidateFile($candidateFile) {
        try {
            $sql = 'UPDATE hiring_CandidateFiles
                SET `cf_purpose` = :purpose
                WHERE `cf_id` = :id;';
            $params = array(
                ':purpose' => $candidateFile->getPurpose(),
                ':id' => $candidateFile->getID()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update CandidateFile: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Removes a CandidateFile row into the database.
     * 
     * If an error occurs during the removal, the function will return `false`.
     * 
     * @param $cf candidate file model
     *
     * @return boolean Whether the deletion succeeds
     */
    public function removeCandidateFile($cf) {
        try {
            $sql = 'DELETE FROM `hiring_CandidateFiles`
                WHERE `cf_id` = :id;';
            $params = array(
                ':id' => $cf->getId()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to remove CandidateFile: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a new CandidateFile object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * @return \Model\CandidateFile
     */
    public static function ExtractCandidateFileFromRow($row) {
        $cf = new CandidateFile($row['cf_id']);
        $cf->setDateCreated(new \DateTime(($row['cf_dateCreated'] == '' ? 'now' : $row['cf_dateCreated'])));
        $cf->setCandidateId($row['cf_c_id']);
        $cf->setFileName($row['cf_fileName']);
        $cf->setPurpose($row['cf_purpose']);


        return $cf;
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
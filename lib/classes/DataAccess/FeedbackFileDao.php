<?php
namespace DataAccess;

use Model\FeedbackFile;

/**
 * Handles all of the logic related to queries on loading/editing FeedbackFiles in the database.
 */
class FeedbackFileDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a FeedbackFile Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform FeedbackFile-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching FeedbackFile data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the FeedbackFiles for a Round from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param integer $roundID The ID for the round to fetch files for
     *
     * @return FeedbackFile[]|boolean an array of FeedbackFile objects if the fetch succeeds, false otherwise
     */
    public function getAllFilesForRound($roundID) {
        try {
            $sql = 'SELECT * FROM hiring_FeedbackFiles
                INNER JOIN hiring_Feedback ON hiring_FeedbackFiles.ff_f_id = hiring_Feedback.f_id
                INNER JOIN hiring_Round ON hiring_Feedback.f_r_id = hiring_Round.r_id
                WHERE hiring_Round.`r_id` = :roundID;';
            $params = array(
                ':roundID' => $roundID
            );
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractFeedbackFileFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch FeedbackFiles for Round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the FeedbackFiles for a Candidate from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param integer $candidateID The ID for the candidate to fetch files for
     *
     * @return FeedbackFile[]|boolean an array of FeedbackFile objects if the fetch succeeds, false otherwise
     */
    public function getAllFilesForCandidate($candidateID) {
        try {
            $sql = 'SELECT * FROM hiring_FeedbackFiles
                INNER JOIN hiring_Feedback ON hiring_FeedbackFiles.ff_f_id = hiring_Feedback.f_id
                WHERE hiring_Feedback.`f_c_id` = :candidateID;';
            $params = array(
                ':candidateID' => $candidateID
            );
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractFeedbackFileFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch FeedbackFiles for Candidate: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the FeedbackFiles for a Feedback from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param integer $feedbackID The ID for the feedback to fetch files for
     *
     * @return FeedbackFile[]|boolean an array of FeedbackFile objects if the fetch succeeds, false otherwise
     */
    public function getAllFilesForFeedback($feedbackID) {
        try {
            $sql = 'SELECT * FROM hiring_FeedbackFiles
                WHERE `ff_f_id` = :feedbackID;';
            $params = array(
                ':feedbackID' => $feedbackID
            );
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractFeedbackFileFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch FeedbackFiles for Feedback: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the FeedbackFiles for a Feedback from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param integer $feedbackID The ID for the feedback to fetch files for
     *
     * @return FeedbackFile|boolean a FeedbackFile object if the fetch succeeds, false otherwise
     */
    public function getFeedbackFileById($id) {
        try {
            $sql = 'SELECT * FROM hiring_FeedbackFiles WHERE `ff_id` = :id;';
            $params = array(
                ':id' => $id
            );
            $result = $this->conn->query($sql, $params);

            if(!count($result))
                return false;

            return self::ExtractFeedbackFileFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch FeedbackFiles by ID: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Inserts a new FeedbackFile row into the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * @param $ff feedback file model
     *
     * @return integer|boolean The FeedbackFile object's database ID if the insertion succeeds, false otherwise
     */
    public function createFeedbackFile($ff) {
        try {
            $sql = "INSERT INTO `hiring_FeedbackFiles`(`ff_f_id`, `ff_fileName`, `ff_dateCreated`)
            VALUES (:feedbackid,:filename,:dateCreated);";
            $params = array(
                ':feedbackid' => $ff->getFeedbackId(),
                ':filename' => $ff->getFileName(),
                ':dateCreated' => $ff->getDateCreated()->format('Y-m-d H:i:s')
            );
            $result = $this->conn->execute($sql, $params, true);
            $this->logger->info("FeedbackFile ID: ".$result);
            
            return $result;
        } catch (\Exception $e) {
            $this->logError('Failed to create FeedbackFile: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Removes a FeedbackFile row from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param $ff feedback file model
     *
     * @return boolean Whether the removal succeeds
     */
    public function removeFeedbackFile($id) {
        try {
            $sql = "DELETE FROM `hiring_FeedbackFiles` WHERE ff_id=:id;";
            $params = array(':id' => $id);
            $this->conn->execute($sql, $params);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to remove FeedbackFile: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a new FeedbackFile object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * @return \Model\FeedbackFile
     */
    public static function ExtractFeedbackFileFromRow($row) {
        $ff = new FeedbackFile($row['ff_id']);
        $ff->setDateCreated(new \DateTime(($row['ff_dateCreated'] == '' ? 'now' : $row['ff_dateCreated'])));
        $ff->setFeedbackId($row['ff_f_id']);
        $ff->setFileName($row['ff_fileName']);

        return $ff;
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
<?php
namespace DataAccess;

use Model\FeedbackForQual;

/**
 * Handles all of the logic related to queries on loading/editing FeedbackForQuals in the database.
 */
class FeedbackForQualDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a FeedbackForQual Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform FeedbackForQual-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching FeedbackForQual data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the FeedbackForQuals from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return FeedbackForQual[]|boolean an array of FeedbackForQual objects if the fetch succeeds, false otherwise
     */
    public function getAllFeedbackForQuals() {
        try {
            $sql = 'SELECT * FROM hiring_FeedbackForQual;';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractFeedbackForQualFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch all FeedbackForQuals: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the FeedbackForQuals for a Qualification from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $qualificationID The qualification to fetch FeedbackForQuals for
     *
     * @return FeedbackForQual[]|boolean an array of FeedbackForQual objects if the fetch succeeds, false otherwise
     */
    public function getFeedbackForQualByQualID($qualificationID) {
        try {
            $sql = 'SELECT * FROM hiring_FeedbackForQual WHERE ffq_q_id=:qualificationID;';
            $params = array(':qualificationID' => $qualificationID);
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractFeedbackForQualFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch FeedbackForQual for Qualification: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the FeedbackForQuals for a Feedback from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $feedbackID The feedback to fetch FeedbackForQuals for
     *
     * @return FeedbackForQual[]|boolean an array of FeedbackForQual objects if the fetch succeeds, false otherwise
     */
    public function getFeedbackForQualByFeedbackID($feedbackID) {
        try {
            $sql = 'SELECT * FROM hiring_FeedbackForQual WHERE ffq_f_id=:feedbackID;';
            $params = array(':feedbackID' => $feedbackID);
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractFeedbackForQualFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch FeedbackForQual for Qualification: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a FeedbackForQual from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param integer $feedbackID The feedback to fetch the FeedbackForQual for
     * @param string $qualificationID The qualification to fetch the FeedbackForQual for
     *
     * @return FeedbackForQual|boolean a FeedbackForQual object if the fetch succeeds and there's a match, false otherwise
     */
    public function getFeedbackForQual($feedbackID, $qualificationID) {
        try {
            $sql = 'SELECT * FROM hiring_FeedbackForQual 
                WHERE ffq_q_id=:qualificationID
                    AND ffq_f_id=:feedbackID;';
            $params = array(
                ':qualificationID' => $qualificationID,
                ':feedbackID' => $feedbackID
            );
            $result = $this->conn->query($sql, $params);

            if(!$result)
                return false;

            return self::ExtractFeedbackForQualFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch FeedbackForQual: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a name from a FeedbackForQual in the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $userID The user to fetch a FeedbackForQual for
     * @param string $candidateID The candidate to fetch a FeedbackForQual for
     * @param string $roundID The round to fetch a FeedbackForQual for
     * @param string $qualificationID The qualification to fetch a FeedbackForQual for
     *
     * @return FeedbackForQual|boolean a FeedbackForQual object if the fetch succeeds and there's a match, false otherwise
     */
    public function getQualStatusName($userID, $candidateID, $roundID, $qualificationID) {
        try {
            $sql = 'SELECT * FROM hiring_FeedbackForQual
                INNER JOIN hiring_QualificationStatusEnum ON hiring_QualificationStatusEnum.qse_id = hiring_FeedbackForQual.ffq_fqe_id
                INNER JOIN hiring_Feedback ON hiring_Feedback.f_id = hiring_FeedbackForQual.ffq_f_id
                WHERE ffq_q_id = :qualificationID
                    AND f_c_id = :candidateID
                    AND f_u_id = :userID
                    AND f_r_id = :roundID;';
            $params = array(
                ':qualificationID' => $qualificationID,
                ':candidateID' => $candidateID,
                ':userID' => $userID,
                ':roundID' => $roundID
            );
            $result = $this->conn->query($sql, $params);

            if(!$result)
                return false;

            if(count($result) > 1) {
                throw new \Exception('Got more objects than expected from params: '.var_export($params, true));
            }

            return $result[0]['qse_name'];
        } catch (\Exception $e) {
            $this->logError('Failed to fetch qualStatusName: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Adds a new FeedbackForQual to the database.
     * 
     * If an error occurs during the insertion, the function will return `false`.
     *
     * @param integer $feedbackID The feedback to add a FeedbackForQual for
     * @param string $qualificationID The qualification to add a FeedbackForQual for
     * @param integer $qualStatusID The qualification status to set for the FeedbackForQual
     * 
     * @return boolean whether the insertion succeeds
     */
    public function addFeedbackForQual($feedbackID, $qualificationID, $qualStatusID) {
        try {
            $sql = 'INSERT INTO hiring_FeedbackForQual(
                    ffq_f_id,
                    ffq_q_id,
                    ffq_fqe_id
                )
                VALUES(
                    :feedbackID,
                    :qualificationID,
                    :qualStatusID
                )';
            $params = array(
                ':feedbackID' => $feedbackID,
                ':qualificationID' => $qualificationID,
                ':qualStatusID' => $qualStatusID
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to add FeedbackForQual: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Updates a FeedbackForQual in the database.
     * 
     * If an error occurs during the update, the function will return `false`.
     * 
     * @param integer $feedbackID The feedback to update a FeedbackForQual for
     * @param string $qualificationID The qualification to update a FeedbackForQual for
     * @param integer $qualStatusID The qualification status to set for the FeedbackForQual
     *
     * @return boolean whether the update succeeds
     */
    public function updateFeedbackForQual($feedbackID, $qualificationID, $qualStatusID) {
        try {
            $sql = 'UPDATE hiring_FeedbackForQual
                SET ffq_fqe_id = :qualStatusID
                WHERE ffq_f_id = :feedbackID
                    AND ffq_q_id = :qualificationID';
            $params = array(
                ':qualStatusID' => $qualStatusID,
                ':feedbackID' => $feedbackID,
                ':qualificationID' => $qualificationID
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to add FeedbackForQual: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a new FeedbackForQual object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * @return \Model\FeedbackForQual
     */
    public static function ExtractFeedbackForQualFromRow($row) {
        $ffq = new FeedbackForQual();
        $ffq->setFeedbackID($row['ffq_f_id']);
        $ffq->setQualificationID($row['ffq_q_id']);
        $ffq->setFeedbackQualificationStatusID($row['ffq_fqe_id']);

        return $ffq;
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
<?php
namespace DataAccess;

use Model\QualificationForRound;

/**
 * Handles all of the logic related to queries on loading/editing QualificationForRounds in the database.
 */
class QualificationForRoundDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a QualificationForRound Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform QualificationForRound-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching QualificationForRound data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    public function getAllQualForRoundsForPosition($positionID) {
        try {
            $sql = 'SELECT * FROM hiring_QualificationForRound 
                INNER JOIN hiring_Qualification ON hiring_Qualification.q_id = hiring_QualificationForRound.qfr_q_id
                WHERE q_p_id=:positionID;';
            $params = array(':positionID' => $positionID);
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractQualificationForRoundFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch QualificationForRounds by position: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the QualificationForRounds for a round from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $roundID The round to fetch QualificationForRounds for
     *
     * @return QualificationForRound[]|boolean an array of QualificationForRound objects if the fetch succeeds, false otherwise
     */
    public function getAllQualificationsForRound($roundID) {
        try {
            $sql = 'SELECT * FROM hiring_QualificationForRound 
                INNER JOIN hiring_Qualification ON hiring_Qualification.q_id = hiring_QualificationForRound.qfr_q_id
                WHERE qfr_r_id=:roundid
                ORDER BY hiring_Qualification.q_level, hiring_Qualification.q_dateCreated ASC;';
            $params = array(':roundid' => $roundID);
            $result = $this->conn->query($sql, $params);

            return \array_map('DataAccess\QualificationDao::ExtractQualificationFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch QualificationForRounds by round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the QualificationForRounds for a qualification from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $qualificationID The qualification to fetch QualificationForRounds for
     *
     * @return QualificationForRound[]|boolean an array of QualificationForRound objects if the fetch succeeds, false otherwise
     */
    public function getAllRoundsForQualification($qualificationID) {
        try {
            $sql = 'SELECT * FROM `hiring_QualificationForRound`
                INNER JOIN `hiring_Round` ON `hiring_Round`.`r_id` = `hiring_QualificationForRound`.`qfr_r_id`
                WHERE `hiring_QualificationForRound`.`qfr_q_id` = :qualificationid
                ORDER BY `hiring_Round`.`r_dateCreated` ASC;';
            $params = array(':qualificationid' => $qualificationID);
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractQualificationForRoundFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch QualificationForRounds by qualification: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a QualificationForRound from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $qualificationID The qualification to fetch QualificationForRounds for
     * @param string $roundID The round to fetch QualificationForRounds for
     *
     * @return QualificationForRound|boolean a QualificationForRound object if the fetch succeeds, false otherwise
     */
    public function getQualForRound($qualificationID, $roundID) {
        try {
            $sql = 'SELECT * FROM `hiring_QualificationForRound`
                INNER JOIN `hiring_Round` ON `hiring_Round`.`r_id` = `hiring_QualificationForRound`.`qfr_r_id`
                WHERE `hiring_QualificationForRound`.`qfr_q_id` = :qualificationid
                    AND `hiring_QualificationForRound`.`qfr_r_id` = :roundid;';
            $params = array(
                ':qualificationid' => $qualificationID,
                ':roundid' => $roundID
            );
            $result = $this->conn->query($sql, $params);

            if(!$result)
                return false;

            return self::ExtractQualificationForRoundFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch QualificationForRound: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a new QualificationForRounds in the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param \Model\QualificationForRound $qfr The qualificationForRound to add to the database
     *
     * @return boolean true if Insert QualificationForRound object succeeds false otherwise
     */
    public function createQualificationForRound($qfr) {
        try {
            $sql = 'INSERT INTO `hiring_QualificationForRound`(
                    `qfr_r_id`, 
                    `qfr_q_id`
                )
                VALUES (
                    :roundid, 
                    :qualificationid
                );';
            $params = array(
                ':roundid' => $qfr->getRoundID(),
                ':qualificationid' => $qfr->getQualificationID()
            );
            $result = $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to add QualificationForRound: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Deletes row with matching round ID and qualification ID and all FeedbackForQuals that are associated with it
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $roundID The round to delete a QualificationForRound for
     * @param string $qualificaitonID The qualification to delete a QualificationForRound for
     *
     * @return boolean true if Insert QualificationForRound object succeeds false otherwise
     */
    public function removeQualificationForRound($roundID, $qualificationID) {
        try {
            // Delete FeedbackForQuals
            $sql = 'DELETE `hiring_FeedbackForQual` FROM `hiring_Round`
                INNER JOIN `hiring_Feedback` ON `hiring_Round`.`r_id` = `hiring_Feedback`.`f_r_id`
                INNER JOIN `hiring_FeedbackForQual` ON `hiring_Feedback`.`f_id` = `hiring_FeedbackForQual`.`ffq_f_id`
                WHERE `hiring_Round`.`r_id` = :roundID 
                    AND `hiring_FeedbackForQual`.`ffq_q_id` = :qualificationID;';
            $params = array(
                ':roundID' => $roundID,
                ':qualificationID' => $qualificationID
            );
            $this->conn->execute($sql, $params);
            
            // Delete QualForRound
            $sql = 'DELETE FROM `hiring_QualificationForRound` WHERE `qfr_r_id`=:roundID AND `qfr_q_id`=:qualificationID;';
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to remove QualificationForRound: ' . $e->getMessage());

            return false;
        }
    }
    
    /**
     * Creates a new QualificationForRound object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * @return \Model\QualificationForRound
     */
    public static function ExtractQualificationForRoundFromRow($row) {
        $qfr = new QualificationForRound($row['qfr_r_id'], $row['qfr_q_id']);
        return $qfr;
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
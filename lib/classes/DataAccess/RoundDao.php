<?php
namespace DataAccess;

use Model\Round;

/**
 * Handles all of the logic related to queries on loading/editing Rounds in the database.
 */
class RoundDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a Round Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform Round-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching Round data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the Rounds from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Round[]|boolean an array of Round objects if the fetch succeeds, false otherwise
     */
    public function getAllRounds() {
        try {
            $sql = 'SELECT * FROM hiring_Round;';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractRoundFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the Rounds from the database for specified position.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Round[]|boolean an array of Round objects if the fetch succeeds, false otherwise
     */
    public function getAllRoundsByPositionId($positionId) {
        try {
            $sql = 'SELECT * FROM hiring_Round WHERE r_p_id=:positionId ORDER BY r_dateCreated ASC;';
            $params = array(':positionId' => $positionId);
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractRoundFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch rounds: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a single round from the database
     * 
     * @param string $roundID  The ID of the round to fetch
     * 
     * @return Round|boolean a Round object if the fetch succeeds, false otherwise
     */
    public function getRound($roundID) {
        try {
            $sql = 'SELECT * FROM hiring_Round WHERE r_id=:roundID;';
            $params = array(':roundID' => $roundID);
            $result = $this->conn->query($sql, $params);

            if(!$result)
                return false;

            return self::ExtractRoundFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fully updates the given round in the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $roundID primary key r_id of round being updated
     * @param string $description updated textarea of round description
     * @param string $link updated input for link to interview questions
     *
     * @return boolean true if update execution succeeds, false otherwise
     */
    public function updateRound($roundID, $description, $link) {
        try {
            $sql = 'UPDATE `hiring_Round`
                    SET `r_description`=:description,`r_interviewQLink`=:link
                    WHERE `r_id`=:id;';
            $params = array(
                ':id' => $roundID,
                ':description' => $description,
                ':link' => $link
            );
            $result = $this->conn->execute($sql, $params, true);
            return true;
            
        } catch (\Exception $e) {
            $this->logError('Failed to update Round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Inserts new round into the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return boolean true if update execution succeeds, false otherwise
     */
    public function createRound($round) {
        try {
            $sql = 'INSERT INTO `hiring_Round` (`r_id`, `r_p_id`, `r_description`, `r_interviewQLink`, `r_dateCreated`)
            VALUES (:id, :p_id, :description, :link, :dateCreated);';
            $params = array(
                ':id' => $round->getId(),
                ':p_id' => $round->getPositionId(),
                ':description' => $round->getDescription(),
                ':link' => $round->getInterviewQuestionLink(),
                ':dateCreated' => $round->getDateCreated()->format('Y-m-d H:i:s')
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Deletes a round and all associated data from the database.
     * 
     * If an error occurs during the deletion, the function will return `false`.
     * 
     * @param string $roundID The round to delete
     *
     * @return boolean true if deletion succeeds, false otherwise
     */
    public function deleteRound($roundID) {
        try {
            $sql = 'DELETE 
                        `hiring_QualificationForRound`, 
                        `hiring_FeedbackForQual`, 
                        `hiring_FeedbackFiles`, 
                        `hiring_CandidateRoundNote`
                    FROM `hiring_Round`
                LEFT JOIN `hiring_QualificationForRound`
                    ON `hiring_Round`.`r_id` = `hiring_QualificationForRound`.`rf_r_id`
                LEFT JOIN `hiring_Feedback`
                    ON `hiring_Round`.`r_id` = `hiring_Feedback`.`f_r_id`
                LEFT JOIN `hiring_FeedbackForQual`
                    ON `hiring_Feedback`.`f_id` = `hiring_FeedbackForQual`.`ffq_f_id`
                LEFT JOIN `hiring_FeedbackFiles`
                    ON `hiring_Feedback`.`f_id` = `hiring_FeedbackFiles`.`ff_f_id`
                LEFT JOIN `hiring_CandidateRoundNote`
                    ON `hiring_Round`.`r_id` = `hiring_CandidateRoundNote`.`crn_r_id`
                WHERE `hiring_Round`.`r_id` = :id;';
            $params = array(
                ':id' => $roundID
            );
            $this->conn->execute($sql, $params);

            // Have to delete after FeedbackForQual and FeedbackFiles
            $sql = 'DELETE `hiring_Feedback`
                FROM `hiring_Round`
                LEFT JOIN `hiring_Feedback`
                    ON `hiring_Round`.`r_id` = `hiring_Feedback`.`f_r_id`
                WHERE `hiring_Round`.`r_id` = :id;';
            $this->conn->execute($sql, $params);

            // Have to delete last
            $sql = 'DELETE FROM `hiring_Round` WHERE `hiring_Round`.`r_id` = :id;';
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to delete Round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a new Round object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * @return \Model\Round
     */
    public static function ExtractRoundFromRow($row) {
        $round = new Round($row['r_id']);
        $round->setDateCreated(new \DateTime(($row['r_dateCreated'] == '' ? 'now' : $row['r_dateCreated'])));
        $round->setPositionID($row['r_p_id']);
        $round->setDescription($row['r_description']);
        $round->setInterviewQuestionLink($row['r_interviewQLink']);
        return $round;
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
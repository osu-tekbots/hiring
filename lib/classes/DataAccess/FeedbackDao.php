<?php
namespace DataAccess;

use Model\Feedback;

/**
 * Handles all of the logic related to queries on loading/editing Feedbacks in the database.
 */
class FeedbackDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a Feedback Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform Feedback-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching Feedback data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the Feedback objects for a candidate and round from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $candidateID The candidate to get feedback for
     * @param string $roundID The round to get feedback for
     *
     * @return Feedback[]|boolean an array of Feedback objects if the fetch succeeds, false otherwise
     */
    public function getAllFeedbackForCandidateAndRound($candidateID, $roundID) {
        try {
            $sql = 'SELECT * FROM hiring_Feedback WHERE f_c_id=:candidateID AND f_r_id=:roundID;';
            $params = array(
                ':candidateID' => $candidateID,
                ':roundID' => $roundID
            );
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractFeedbackFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch all Feedback for Candidate and Round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches the Feedback object for a user, candidate, and round from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $userID The user to get feedback for
     * @param string $candidateID The candidate to get feedback for 
     * @param string $roundID The round to get feedback for
     *
     * @return Feedback[]|boolean an array of Feedback objects if the fetch succeeds and a feedback exists, false otherwise
     */
    public function getFeedbackForUser($userID, $candidateID, $roundID) {
        try {
            $sql = 'SELECT * FROM hiring_Feedback WHERE f_u_id=:userID AND f_c_id=:candidateID AND f_r_id=:roundID;';
            $params = array(
                ':userID' => $userID,
                ':candidateID' => $candidateID,
                ':roundID' => $roundID
            );
            $result = $this->conn->query($sql, $params);

            if(!count($result))
                return false;

            return self::ExtractFeedbackFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Feedback for user: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches the feedback with a specific id from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param integer $feedbackID The feedback to fetch
     *
     * @return Feedback|boolean a Feedback object if the fetch succeeds, false otherwise
     */
    public function getFeedbackById($feedbackID) {
        try {
            $sql = 'SELECT * FROM hiring_Feedback WHERE f_id=:feedbackID;';
            $params = array(':feedbackID' => $feedbackID);
            $result = $this->conn->query($sql, $params);
            return self::ExtractFeedbackFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Feedback by ID: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Adds the feedback to the database.
     * 
     * If an error occurs during the insertion, the function will return `false`.
     * 
     * @param \Model\Feedback The feedback to add to the database
     *
     * @return integer|boolean The Feedback object's database ID if the insertion succeeds, false otherwise
     */
    public function addFeedback($feedback) {
        try {
            $sql = 'INSERT INTO hiring_Feedback(
                    `f_u_id`, 
                    `f_c_id`, 
                    `f_r_id`, 
                    `f_notes`, 
                    `f_lastUpdated`)
                VALUES(
                    :userID,
                    :candidateID,
                    :roundID,
                    :notes,
                    :lastUpdated
                );';
            $params = array(
                ':userID' => $feedback->getUserID(),
                ':candidateID' => $feedback->getCandidateID(),
                ':roundID' => $feedback->getRoundID(),
                ':notes' => $feedback->getNotes(),
                ':lastUpdated' => $feedback->getLastUpdated()
            );
            $result = $this->conn->execute($sql, $params, true);

            return $result;
        } catch (\Exception $e) {
            $this->logError('Failed to add Feedback: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Updates the feedback in the database.
     * 
     * If an error occurs during the update, the function will return `false`.
     * 
     * @param \Model\Feedback The feedback to update in the database
     *
     * @return boolean Whether the update succeeds
     */
    public function updateFeedback($feedback) {
        try {
            $sql = 'UPDATE hiring_Feedback
                SET `f_u_id` = :userID, 
                    `f_c_id` = :candidateID, 
                    `f_r_id` = :roundID,
                    `f_notes` = :notes,
                    `f_lastUpdated` = :lastUpdated
                WHERE `f_id` = :feedbackID;';
            $params = array(
                ':userID' => $feedback->getUserID(),
                ':candidateID' => $feedback->getCandidateID(),
                ':roundID' => $feedback->getRoundID(),
                ':notes' => $feedback->getNotes(),
                ':lastUpdated' => $feedback->getLastUpdated()->format('Y-m-d H:i:s'),
                ':feedbackID' => $feedback->getID()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update Feedback: ' . $e->getMessage());
            return false;
        }
    }
    

    /**
     * Creates a new Feedback object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * @return \Model\Feedback
     */
    public static function ExtractFeedbackFromRow($row) {
        $feedback = new Feedback($row['f_id']);
        $feedback->setUserID($row['f_u_id']);
        $feedback->setCandidateID($row['f_c_id']);
        $feedback->setRoundID($row['f_r_id']);
        $feedback->setNotes($row['f_notes']);
        $feedback->setLastUpdated(new \DateTime(($row['f_lastUpdated'] == '' ? 'now' : $row['f_lastUpdated'])));

        return $feedback;
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
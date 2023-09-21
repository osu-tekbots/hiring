<?php
namespace DataAccess;

use Model\Qualification;

/**
 * Handles all of the logic related to queries on loading/editing Qualifications in the database.
 */
class QualificationDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a Qualification Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform Qualification-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching Qualification data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Inserts a new Qualification in the database
     * 
     * @param Model\Qualification $qualification The qualification to insert
     *
     * @return boolean Whether the insertion succeeded
     */
    public function createQualification($qualification) {
        try {
            $sql = '
                INSERT INTO `hiring_Qualification`(
                    `q_id`, 
                    `q_p_id`, 
                    `q_level`, 
                    `q_description`, 
                    `q_transferable`, 
                    `q_screeningCriteria`, 
                    `q_priority`, 
                    `q_strengthIndicators`, 
                    `q_dateCreated`) 
                VALUES (
                    :id,
                    :p_id,
                    :level,
                    :description,
                    :transferable,
                    :screeningCriteria,
                    :priority,
                    :strengthIndicators,
                    :dateCreated
                );';
            $params = array(
                'id'=>$qualification->getID(),
                'p_id'=>$qualification->getPositionID(),
                'level'=>$qualification->getLevel(),
                'description'=>$qualification->getDescription(),
                'transferable'=>$qualification->getTransferable(),
                'screeningCriteria'=>$qualification->getScreeningCriteria(),
                'priority'=>$qualification->getPriority(),
                'strengthIndicators'=>$qualification->getStrengthIndicators(),
                'dateCreated'=>$qualification->getDateCreated()->format('Y-m-d H:i:s')
            );
            
            $this->conn->execute($sql, $params);

            return true;

        } catch (\Exception $e) {
            $this->logError('Failed to insert new Qualification: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the Qualifications from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Qualification[]|boolean an array of Qualification objects if the fetch succeeds, false otherwise
     */
    public function getAllQualifications() {
        try {
            $sql = 'SELECT * FROM hiring_Qualification;';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractQualificationFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch all Qualifications: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the Qualifications for a Position from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $positionID  The position to fetch qualifications for
     *
     * @return Qualification[]|boolean an array of Qualification objects if the fetch succeeds, false otherwise
     */
    public function getQualificationsForPosition($positionID) {
        try {
            $sql = 'SELECT * FROM hiring_Qualification
                WHERE q_p_id=:p_id
                ORDER BY q_dateCreated ASC;';
            $params = array(
                ":p_id"=>$positionID
            );
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractQualificationFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Qualifications for Position: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the Qualifications for a Round through the QualificationForRound join table.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $positionID  The position to fetch qualifications for
     *
     * @return Qualification[]|boolean an array of Qualification objects if the fetch succeeds, false otherwise
     */
    public function getQualificationsForRound($roundID) {
        try {
            $sql = 'SELECT * FROM hiring_Qualification
            INNER JOIN hiring_QualificationForRound
                ON hiring_Qualification.q_id = hiring_QualificationForRound.qfr_q_id
            WHERE hiring_QualificationForRound.qfr_r_id=:r_id
            ORDER BY hiring_Qualification.q_dateCreated ASC;';
            $params = array(
                ":r_id"=>$roundID
            );
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractQualificationFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Qualifications for Round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a Qualification from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $qualificationID  The qualification to fetch
     *
     * @return Qualification|boolean a Qualification object if the fetch succeeds, false otherwise
     */
    public function getQualification($qualificationID) {
        try {
            $sql = '
                SELECT * FROM hiring_Qualification
                WHERE q_id=:q_id';
            $params = array(
                ":q_id"=>$qualificationID
            );
            $result = $this->conn->query($sql, $params);

            if(!$result) 
                return false;

            return self::ExtractQualificationFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Qualification by ID: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Updates a new Qualification in the database
     * 
     * @param Model\Qualification $qualification The qualification to update
     *
     * @return boolean Whether the update succeeded
     */
    public function updateQualification($qualification) {
        try {
            $sql = '
                UPDATE `hiring_Qualification`
                SET `q_level` = :level,
                    `q_description` = :description,
                    `q_transferable` = :transferable,
                    `q_screeningCriteria` = :screeningCriteria,
                    `q_priority` = :priority,
                    `q_strengthIndicators` = :strengthIndicators
                WHERE `q_id` = :id;';
            $params = array(
                'level'=>$qualification->getLevel(),
                'description'=>$qualification->getDescription(),
                'transferable'=>$qualification->getTransferable(),
                'screeningCriteria'=>$qualification->getScreeningCriteria(),
                'priority'=>$qualification->getPriority(),
                'strengthIndicators'=>$qualification->getStrengthIndicators(),
                'id'=>$qualification->getID(),
            );
            
            $this->conn->execute($sql, $params);

            return true;

        } catch (\Exception $e) {
            $this->logError('Failed to update Qualification: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Deletes a Qualification and all data associated with it from the database
     * 
     * @param string $qualificationID The qualification to deletion
     *
     * @return boolean Whether the deletion succeeded
     */
    public function deleteQualification($qualificationID) {
        try {
            $sql = 'DELETE hiring_QualificationForRound, hiring_FeedbackForQual
                FROM `hiring_Qualification`
                LEFT JOIN `hiring_QualificationForRound` 
                    ON `hiring_Qualification`.`q_id` = `hiring_QualificationForRound`.`qfr_q_id`
                LEFT JOIN `hiring_FeedbackForQual`
                    ON `hiring_Qualification`.`q_id` = `hiring_FeedbackForQual`.`ffq_q_id`
                WHERE `hiring_Qualification`.`q_id` = :id;';
            $params = array(
                ':id'=>$qualificationID
            );
            $this->conn->execute($sql, $params);

            $sql = 'DELETE FROM `hiring_Qualification` WHERE `hiring_Qualification`.`q_id` = :id;';
            $this->conn->execute($sql, $params);

            return true;

        } catch (\Exception $e) {
            $this->logError('Failed to delete Qualification: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a new Qualification object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing qualification information
     * @return \Model\Qualification
     */
    public static function ExtractQualificationFromRow($row) {
        $qualification = new Qualification($row['q_id']);
        $qualification->setPositionID($row['q_p_id']);
        $qualification->setLevel($row['q_level']);
        $qualification->setDescription($row['q_description']);
        $qualification->setTransferable($row['q_transferable']);
        $qualification->setScreeningCriteria($row['q_screeningCriteria']);
        $qualification->setPriority($row['q_priority']);
        $qualification->setStrengthIndicators($row['q_strengthIndicators']);
        $qualification->setDateCreated(new \DateTime(($row['q_dateCreated'] == '' ? 'now' : $row['q_dateCreated'])));

        return $qualification;
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
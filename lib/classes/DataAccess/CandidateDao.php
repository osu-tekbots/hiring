<?php
namespace DataAccess;

use Model\Candidate;
use Model\CandidateStatus;
use Model\CandidateStatusOption;

/**
 * Handles all of the logic related to queries on loading/editing Candidates in the database.
 */
class CandidateDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a Candidate Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform Candidate-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching Candidate data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the Candidates from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Candidate|boolean an array of Candidate objects if the fetch succeeds, false otherwise
     */
    public function getAllCandidates() {
        try {
            $sql = 'SELECT * FROM hiring_Candidate
                LEFT JOIN hiring_CandidateStatus ON hiring_Candidate.c_cs_id = hiring_CandidateStatus.cs_id
                LEFT JOIN hiring_CandidateStatusEnum ON hiring_CandidateStatus.cs_cse_id = hiring_CandidateStatusEnum.cse_id;';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractCandidateFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch all Candidates: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches the Candidate by Id PK from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $id The candidate's ID
     *
     * @return Candidate|boolean a Candidate object if the fetch succeeds, false otherwise
     */
    public function getCandidateById($id) {
        try {
            $sql = 'SELECT * FROM hiring_Candidate 
                LEFT JOIN hiring_CandidateStatus ON hiring_Candidate.c_cs_id = hiring_CandidateStatus.cs_id
                LEFT JOIN hiring_CandidateStatusEnum ON hiring_CandidateStatus.cs_cse_id = hiring_CandidateStatusEnum.cse_id
                WHERE c_id=:id;';
            $params = array (':id' => $id);
            $result = $this->conn->query($sql, $params);

            if(!\count($result))
                return false;

            return self::ExtractCandidateFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Candidate by ID: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches the Candidates by Position.p_id Foreign Key from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $positionId The position ID to fetch candidates for
     *
     * @return Candidate[]|boolean an array of Candidate objects if the fetch succeeds, false otherwise
     */
    public function getCandidatesByPositionId($positionId) {
        try {
            $sql = 'SELECT * FROM hiring_Candidate 
                LEFT JOIN hiring_CandidateStatus ON hiring_Candidate.c_cs_id = hiring_CandidateStatus.cs_id
                LEFT JOIN hiring_CandidateStatusEnum ON hiring_CandidateStatus.cs_cse_id = hiring_CandidateStatusEnum.cse_id
                WHERE c_p_id=:positionid
                ORDER BY `hiring_Candidate`.c_fName ASC;';
            $params = array (':positionid' => $positionId);
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractCandidateFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Candidates by Position: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Updates candidate by Id in the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param mixed[] $candidateInfo The information to update the candidate with
     *
     * @return boolean true if update succeeds, false otherwise
     */
    public function updateCandidate($candidateInfo) {
        try {
            $sql = 'UPDATE `hiring_Candidate` 
            SET `c_fName`=:firstname,`c_lName`=:lastname,`c_location`=:location,`c_email`=:email,`c_phone`=:phone, `c_dateApplied`=:dateApplied
            WHERE c_id=:id';
            $params = array (
                ':firstname' => $candidateInfo['firstname'],
                ':lastname' => $candidateInfo['lastname'],
                ':location' => $candidateInfo['location'],
                ':email' => $candidateInfo['email'],
                ':phone' => $candidateInfo['phone'],
                ':dateApplied' => $candidateInfo['dateApplied'],
                ':id' => $candidateInfo['id']
            );
            $result = $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update Candidate: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Insert new Candidate row into the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param \Model\Candidate $candidate The candidate to insert into the database
     *
     * @return boolean true if insert succeeds, false otherwise
     */
    public function createCandidate($candidate) {
        try {
            $sql = 'INSERT INTO `hiring_Candidate` 
            (
                `c_id`,
                `c_fName`,
                `c_lName`,
                `c_location`,
                `c_email`,
                `c_phone`,
                `c_p_id`, 
                `c_dateCreated`
            )
            VALUES (
                :id,
                :firstname,
                :lastname,
                :location,
                :email,
                :phone,
                :positionId,
                :dateCreated
            );';
            $params = array (
                ':firstname' => $candidate->getFirstName(),
                ':lastname' => $candidate->getLastName(),
                ':location' => $candidate->getLocation(),
                ':email' => $candidate->getEmail(),
                ':phone' => $candidate->getPhoneNumber(),
                ':positionId' => $candidate->getPositionID(),
                ':dateCreated' => $candidate->getDateCreated()->format('Y-m-d H:i:s'),
                ':id' => $candidate->getId()
            );
            $result = $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to create Candidate: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Deletes candidate by ID in the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $candidateID The candidate to delete
     *
     * @return boolean true if update succeeds, false otherwise
     */
    public function deleteCandidate($candidateID) {
        try {
            // Delete everything possible
            $sql = 'DELETE 
                        `hiring_CandidateNotes`,
                        `hiring_CandidateRoundNote`,
                        `hiring_CandidateStatus`, 
                        `hiring_CandidateFiles`, 
                        `hiring_FeedbackFiles`, 
                        `hiring_FeedbackForQual`
                    FROM `hiring_Candidate`
                LEFT JOIN `hiring_CandidateNotes` ON `hiring_CandidateNotes`.`cn_c_id` = `hiring_Candidate`.`c_id`
                LEFT JOIN `hiring_CandidateRoundNote` ON `hiring_CandidateRoundNote`.`crn_c_id` = `hiring_Candidate`.`c_id`
                LEFT JOIN `hiring_CandidateStatus` ON `hiring_Candidate`.`c_cs_id` = `hiring_CandidateStatus`.`cs_id`
                LEFT JOIN `hiring_CandidateFiles` ON `hiring_CandidateFiles`.`cf_c_id` = `hiring_Candidate`.`c_id`
                LEFT JOIN `hiring_Feedback` ON `hiring_Feedback`.`f_c_id` = `hiring_Candidate`.`c_id`
                LEFT JOIN `hiring_FeedbackFiles` ON `hiring_FeedbackFiles`.`ff_f_id` = `hiring_Feedback`.`f_id`
                LEFT JOIN `hiring_FeedbackForQual` ON `hiring_FeedbackForQual`.`ffq_f_id` = `hiring_Feedback`.`f_id`
                WHERE c_id=:id';
            $params = array (
                ':id' => $candidateID
            );
            $this->conn->execute($sql, $params);

            // Delete feedback afterwards to avoid deletion order issues
            $sql = 'DELETE FROM `hiring_Feedback`
                WHERE f_c_id=:id';
            $this->conn->execute($sql, $params);

            // Delete candidate last to avoid deletion order issues
            $sql = 'DELETE FROM `hiring_Candidate`
                WHERE c_id=:id';
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to delete Candidate: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a candidate status in the database and links it to a candidate.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string  The candidate to set the status for
     * @param Model\CandidateStatus  The candidateStatus object to create
     *
     * @return boolean true if update succeeds, false otherwise
     */
    public function setCandidateStatus($candidateID, $candidateStatus) {
        try {
            $sql = 'INSERT INTO `hiring_CandidateStatus`(
                    `cs_cse_id`, 
                    `cs_specificDispositionReason`, 
                    `cs_u_id`, 
                    `cs_responsiblePartyDescription`, 
                    `cs_comments`, 
                    `cs_howNotified`, 
                    `cs_dateDecided`
                )
                VALUES (
                    :statusID,
                    :reason,
                    :userID,
                    :rpd,
                    :comments,
                    :howNotified,
                    :dateDecided
                )';
            $params = array (
                ':statusID' => $candidateStatus->getStatusID(),
                ':reason' => $candidateStatus->getSpecificDispositionReason(),
                ':userID' => $candidateStatus->getUserID(),
                ':rpd' => $candidateStatus->getResponsiblePartyDescription(),
                ':comments' => $candidateStatus->getComments(),
                ':howNotified' => $candidateStatus->getHowNotified(),
                ':dateDecided' => $candidateStatus->getDateDecided()->format('Y-m-d H:i:s')
            );
            $result = $this->conn->execute($sql, $params, true);

            $sql = 'UPDATE `hiring_Candidate`
                SET `c_cs_id` = :statusID
                WHERE `c_id` = :id;';
            $params = array(
                ':statusID' => $result,
                ':id' => $candidateID
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to set candidate status: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Updates candidate status by Id in the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string  The candidate to update the status for
     * @param Model\CandidateStatus  The candidateStatus object to update
     *
     * @return boolean true if update succeeds, false otherwise
     */
    public function updateCandidateStatus($candidateID, $candidateStatus) {
        try {
            $sql = 'UPDATE `hiring_CandidateStatus` 
                INNER JOIN `hiring_Candidate` ON `hiring_Candidate`.`c_cs_id` = `hiring_CandidateStatus`.`cs_id`
                SET `cs_cse_id` = :statusID,
                    `cs_specificDispositionReason` = :reason,
                    `cs_u_id` = :userID,
                    `cs_responsiblePartyDescription` = :rpd,
                    `cs_comments` = :comments,
                    `cs_howNotified` = :howNotified,
                    `cs_dateDecided` = :dateDecided
                WHERE c_id=:id';
            $params = array (
                ':statusID' => $candidateStatus->getStatusID(),
                ':reason' => $candidateStatus->getSpecificDispositionReason(),
                ':userID' => $candidateStatus->getUserID(),
                ':rpd' => $candidateStatus->getResponsiblePartyDescription(),
                ':comments' => $candidateStatus->getComments(),
                ':howNotified' => $candidateStatus->getHowNotified(),
                ':dateDecided' => $candidateStatus->getDateDecided()->format('Y-m-d H:i:s'),
                ':id' => $candidateID
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update candidate status: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Removes a candidate status in the database and unlinks it from a candidate.
     * 
     * If an error occurs during the deletion, the function will return `false`.
     * 
     * @param string  The candidate to remove the status for
     *
     * @return boolean true if deletion succeeds, false otherwise
     */
    public function deleteCandidateStatus($candidateID) {
        try {
            $sql = 'SELECT `c_cs_id` FROM `hiring_Candidate`
                WHERE `c_id` = :id;';
            $params = array(
                ':id' => $candidateID
            );
            $result = $this->conn->query($sql, $params);
            $statusID = $result[0]['c_cs_id'];

            $sql = 'UPDATE `hiring_Candidate`
                SET `c_cs_id` = NULL
                WHERE `c_id` = :id;';
            $params = array(
                ':id' => $candidateID
            );
            $this->conn->execute($sql, $params);

            $sql = 'DELETE FROM `hiring_CandidateStatus` 
                WHERE `cs_id` = :statusID';
            $params = array (
                ':statusID' => $statusID
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to delete candidate status: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all active Candidate Status options from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Model\CandidateStatusOption[]|boolean an array of CandidateStatusOption objects if the fetch succeeds, false otherwise
     */
    public function getActiveCandidateStatusOptions() {
        try {
            $sql = 'SELECT * FROM hiring_CandidateStatusEnum WHERE cse_active = 1;';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractCandidateStatusOptionFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch all CandidateStatusOptions: ' . $e->getMessage());

            return false;
        }
    }
    

    /**
     * Creates a new Candidate object by extracting the information from a row in the database.
     *
     * @param string[] $row A row from the database containing user information
     * 
     * @return \Model\Candidate A Candidate object representing the given row
     */
    public static function ExtractCandidateFromRow($row) {
        $candidate = new Candidate($row['c_id']);
        $candidate->setDateCreated(new \DateTime(($row['c_dateCreated'] == '' ? 'now' : $row['c_dateCreated'])));
        if(isset($row['c_dateApplied'])) $candidate->setDateApplied(new \DateTime($row['c_dateApplied']));
        $candidate->setFirstName($row['c_fName']);
        $candidate->setLastName($row['c_lName']);
        $candidate->setLocation($row['c_location']);
        $candidate->setEmail($row['c_email']);
        $candidate->setPhoneNumber($row['c_phone']);
        $candidate->setCandidateStatus(self::ExtractCandidateStatusFromRow($row));
        $candidate->setPositionID($row['c_p_id']);
        return $candidate;
    }
    

    /**
     * Creates a new CandidateStatus object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * 
     * @return \Model\CandidateStatus A Candidate object representing the given row
     */
    public static function ExtractCandidateStatusFromRow($row) {
        if(!is_null($row['c_cs_id'])) {
            $candidateStatus = new CandidateStatus($row['c_cs_id']);
            $candidateStatus->setStatusID('cse_id');
            $candidateStatus->setName($row['cse_name']);
            $candidateStatus->setSpecificDispositionReason($row['cs_specificDispositionReason']);
            $candidateStatus->setUserID($row['cs_u_id']);
            $candidateStatus->setResponsiblePartyDescription($row['cs_responsiblePartyDescription']);
            $candidateStatus->setComments($row['cs_comments']);
            $candidateStatus->setHowNotified($row['cs_howNotified']);
            $candidateStatus->setDateDecided(new \DateTime($row['cs_dateDecided']));
            return $candidateStatus;
        } else {
            return NULL;
        }
    }
    

    /**
     * Creates a new CandidateStatusOption object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * 
     * @return \Model\CandidateStatusOption A Candidate object representing the given row
     */
    public static function ExtractCandidateStatusOptionFromRow($row) {
        $candidateStatusOption = new CandidateStatusOption($row['cse_id']);
        $candidateStatusOption->setName($row['cse_name']);
        $candidateStatusOption->setActive($row['cse_active']);
        return $candidateStatusOption;
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
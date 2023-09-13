<?php
namespace DataAccess;

use Model\CandidateRoundNote;

/**
 * Handles all of the logic related to queries on loading/editing CandidateRoundNotes in the database.
 */
class CandidateRoundNoteDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a CandidateRoundNote Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform CandidateRoundNote-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching CandidateRoundNote data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches the CandidateRoundNote for a round from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $candidateID The candidate to fetch the notes for
     * @param string $roundID The round to fetch the notes for
     *
     * @return CandidateRoundNote|boolean a CandidateRoundNote object if the fetch succeeds, false otherwise
     */
    public function getCandidateNotesForRound($candidateID, $roundID) {
        try {
            $sql = 'SELECT * FROM hiring_CandidateRoundNote
                WHERE crn_c_id = :cID
                    AND crn_r_id = :rID;';
            $params = array(
                ':cID'=>$candidateID,
                ':rID'=>$roundID
            ); 
            $result = $this->conn->query($sql, $params);

            if(!$result)
                return false;

            return self::ExtractCandidateRoundNoteFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch CandidateRoundNote for round: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches the CandidateRoundNote with a specific id from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $candidateRoundNoteID The CandidateRoundNote to get
     *
     * @return CandidateRoundNote|boolean a CandidateRoundNote object if the fetch succeeds, false otherwise
     */
    public function getCandidateRoundNoteByID($candidateRoundNoteID) {
        try {
            $sql = 'SELECT * FROM hiring_CandidateRoundNote WHERE c_id = :candidateRoundNoteID;';
            $params = array(':candidateRoundNoteID' => $candidateRoundNoteID);

            $result = $this->conn->query($sql, $params);

            if(!$result)
                return false;
            if(count($result) > 1)
                $this->logger->warn('More than one CandidateRoundNote found for ID: ' . $candidateRoundNoteID);

            return self::ExtractCandidateRoundNoteFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch CandidateRoundNote by ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Updates a CandidateRoundNote in the database.
     *
     * @param \Model\CandidateRoundNote $candidateRoundNote the CandidateRoundNote to update in the database
     * 
     * @return boolean true if the update execution succeeds, false otherwise.
     */
    public function updateCandidateRoundNote($candidateRoundNote) {
        try {
            $sql = 'UPDATE hiring_CandidateRoundNote
                SET `crn_notes` = :notes
                WHERE `crn_id` = :crnID;';
            $params = array(
                ':notes' => $candidateRoundNote->getNotes(),
                ':crnID' => $candidateRoundNote->getID()
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update CandidateRoundNote: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Creates a CandidateRoundNote in the database.
     *
     * @param \Model\CandidateRoundNote $candidateRoundNote the CandidateRoundNote to add to the database
     * 
     * @return boolean true if the insertion succeeds, false otherwise.
     */
    public function createCandidateRoundNote($candidateRoundNote) {
        try {
            $sql = 'INSERT INTO hiring_CandidateRoundNote (
                    `crn_c_id`, 
                    `crn_r_id`, 
                    `crn_notes`
                )
                VALUES (
                    :cID,
                    :rID,
                    :notes
                );';
            $params = array(
                ':cID' => $candidateRoundNote->getCandidateID(),
                ':rID' => $candidateRoundNote->getRoundID(),
                ':notes' => $candidateRoundNote->getNotes()
            );
            $result = $this->conn->execute($sql, $params, true);

            return $result;
        } catch (\Exception $e) {
            $this->logError('Failed to insert CandidateRoundNote: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a new CandidateRoundNote object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing CandidateRoundNote information
     * 
     * @return \Model\CandidateRoundNote
     */
    public static function ExtractCandidateRoundNoteFromRow($row) {
        $candidateRoundNote = new CandidateRoundNote($row['crn_id']);
        $candidateRoundNote->setCandidateID($row['crn_c_id']);
        $candidateRoundNote->setRoundID($row['crn_r_id']);
        $candidateRoundNote->setNotes($row['crn_notes']);
        $candidateRoundNote->setDateUpdated($row['crn_dateUpdated']);

        return $candidateRoundNote;
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
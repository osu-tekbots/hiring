<?php
namespace DataAccess;

use Model\Position;
use Util\IdGenerator;

/**
 * Handles all of the logic related to queries on loading/editing Positions in the database.
 */
class PositionDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a Position Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform Position-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching Position data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Creates a new position in the database
     * 
     * @param Model\Position  The position to add
     * @param string  The user to automatically set as the Search Chair
     * 
     * @return bool  Whether the creation was successful
     */
    public function createPosition($position, $userID) {
        try {
            $sql = '
                INSERT INTO hiring_Position (
                    `p_id`, 
                    `p_title`, 
                    `p_postingLink`, 
                    `p_dateCreated`, 
                    `p_committeeEmail`, 
                    `p_status`
                )
                VALUES (
                    :id,
                    :title,
                    :postingLink,
                    :dateCreated,
                    :committeeEmail,
                    :status
                );';
            $params = array(
                ':id' => $position->getID(),
                ':title' => $position->getTitle(),
                ':postingLink' => $position->getPostingLink(),
                ':dateCreated' => $position->getDateCreated()->format('Y-m-d H:i:s'),
                ':committeeEmail' => $position->getCommitteeEmail(),
                ':status' => $position->getStatus(),
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to create Position: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the Positions from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Position[]|boolean an array of Position objects if the fetch succeeds, false otherwise
     */
    public function getAllPositions() {
        try {
            $sql = 'SELECT * FROM hiring_Position ORDER BY p_dateCreated DESC;';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractPositionFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch all Positions: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all unapproved Positions from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Position[]|boolean an array of Position objects if the fetch succeeds, false otherwise
     */
    public function getUnapprovedPositions() {
        try {
            $sql = 'SELECT * FROM hiring_Position WHERE `p_status` = "Requested" ORDER BY p_dateCreated DESC;';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractPositionFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch unapproved Positions: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all approved Positions from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Position[]|boolean an array of Position objects if the fetch succeeds, false otherwise
     */
    public function getApprovedPositions() {
        try {
            $sql = 'SELECT * FROM hiring_Position WHERE `p_status` <> "Requested" ORDER BY p_dateCreated DESC;';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractPositionFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch approved Positions: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a Position from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $id The ID for the position to fetch
     *
     * @return Position|boolean a Position object if the fetch succeeds, false otherwise
     */
    public function getPosition($id) {
        try {
            $sql = '
                SELECT * FROM hiring_Position
                WHERE p_id=:id;';
            $params = array(
                ':id'=>$id
            );
            $result = $this->conn->query($sql, $params);

            if(!$result)
                return false;
            if(count($result) > 1)
                $this->logger->warn('More than one Position found for ID: ' . $id);

            return self::ExtractPositionFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Position: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all Positions that a user is affiliated with from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $userID The ID for the user to fetch positions for
     *
     * @return Position|boolean a Position object if the fetch succeeds, false otherwise
     */
    public function getPositionsForUser($userID) {
        try {
            $sql = '
                SELECT * FROM hiring_Position, hiring_RoleForPosition
                WHERE hiring_RoleForPosition.rfp_u_id=:userID
                    AND hiring_RoleForPosition.rfp_r_id<>5
                    AND hiring_Position.p_id=hiring_RoleForPosition.rfp_p_id
                ORDER BY p_dateCreated DESC;'; // rfp_r_id=5 means inactive
            $params = array(
                ':userID'=>$userID
            );
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractPositionFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch Positions for user: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Updates a Position in the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param \Model\Position $position The position to update 
     *
     * @return boolean Whether the update succeeds
     */
    public function updatePosition($position) {
        try {
            $sql = '
                UPDATE hiring_Position
                SET `p_title` = :title,
                    `p_postingLink` = :postingLink,
                    `p_committeeEmail` = :committeeEmail,
                    `p_status` = :status
                WHERE `p_id` = :id;';
            $params = array(
                ':title' => $position->getTitle(),
                ':postingLink' => $position->getPostingLink(),
                ':committeeEmail' => $position->getCommitteeEmail(),
                ':status' => $position->getStatus(),
                ':id' => $position->getID(),
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch(\Exception $e) {
            $this->logError('Failed to update position: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a new Position object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * @return \Model\Position
     */
    public static function ExtractPositionFromRow($row) {
        $position = new Position($row['p_id']);
        $position->setTitle($row['p_title']);
        $position->setPostingLink($row['p_postingLink']);
        $position->setDateCreated(new \DateTime(($row['p_dateCreated'] == '' ? 'now' : $row['p_dateCreated'])));
        $position->setCommitteeEmail($row['p_committeeEmail']);
        $position->setStatus($row['p_status']);

        return $position;
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
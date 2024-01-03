<?php
namespace DataAccess;

use DataAccess\UserDao;
use DataAccess\PositionDao;
use Model\Role;
use Model\RoleForPosition;

/**
 * Handles all of the logic related to queries on loading/editing Roles in the database.
 */
class RoleDao {
	
	/** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;
	
	/** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a Role Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform role-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching role data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the Roles from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return Role[]|boolean an array of Role objects if the fetch succeeds, false otherwise
     */
    public function getAllRoles() {
        try {
            $sql = 'SELECT * FROM hiring_Role;';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractRoleFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch all roles: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches the matching Role from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $name The role to fetch
     *
     * @return Role|boolean a Role object if the fetch succeeds, false otherwise
     */
    public function getRoleByName($name) {
        try {
            $sql = 'SELECT * FROM hiring_Role WHERE r_name=:name;';
            $params = array(':name'=>$name);
            $result = $this->conn->query($sql, $params);

            return self::ExtractRoleFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch role by name: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches the matching Role from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $id The role to fetch
     *
     * @return Role|boolean a Role object if the fetch succeeds, false otherwise
     */
    public function getRoleByID($id) {
        try {
            $sql = 'SELECT * FROM hiring_Role WHERE r_id=:id;';
            $params = array(':id'=>$id);
            $result = $this->conn->query($sql, $params);

            return self::ExtractRoleFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch role by id: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Sets a user's role for a position in the database.
     * 
     * @param string $roleID  The role to set the user as
     * @param string $userID  The user to set the role for
     * @param string $positionID  The position to set the role for
     *
     * @return integer|boolean The database ID if the the function executed successfully, false otherwise
     */
    public function addUserRoleForPosition($roleID, $userID, $positionID) {
        try {
            $sql = '
                INSERT INTO `hiring_RoleForPosition` (
                    `rfp_u_id`, 
                    `rfp_r_id`, 
                    `rfp_p_id`
                )
                VALUES (
                    :userID,
                    :roleID,
                    :positionID
                )';
            $params = array(
                ':userID'=>$userID,
                ':roleID'=>$roleID,
                ':positionID'=>$positionID
            );
            $result = $this->conn->execute($sql, $params, true);
            
            return $result;
        } catch (\Exception $e) {
            $this->logError('Failed to fetch role by name: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Updates a user's role for a position in the database.
     * 
     * @param string $roleForPositionID  The row to update
     * @param string $roleID  The role to set the user as
     *
     * @return boolean Whether the function executed successfully
     */
    public function updateUserRoleForPosition($roleForPositionID, $roleID) {
        try {
            $sql = '
                UPDATE `hiring_RoleForPosition` 
                SET `rfp_r_id`=:roleID
                WHERE `rfp_id`=:roleForPositionID';
            $params = array(
                ':roleID'=>$roleID,
                ':roleForPositionID'=>$roleForPositionID
            );
            $this->conn->execute($sql, $params);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update user roleForPosition: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Gets a UserRoleForPosition in the database.
     * 
     * @param string $userID  The user to get the role for
     * @param string $positionID  The position to get the role for
     *
     * @return Model\RoleForPosition|boolean  The user's role or `false` if the fetch fails or the user has no role for the position
     */
    public function getUserRoleForPosition($userID, $positionID) {
        try {
            $sql = "
                SELECT * FROM `hiring_RoleForPosition` 
                INNER JOIN `hiring_Users` ON `hiring_RoleForPosition`.`rfp_u_id`=`hiring_Users`.`u_id`
                INNER JOIN `hiring_Position` ON `hiring_RoleForPosition`.`rfp_p_id`=`hiring_Position`.`p_id`
                INNER JOIN `hiring_Role` ON `hiring_RoleForPosition`.`rfp_r_id`=`hiring_Role`.`r_id`
                WHERE `hiring_RoleForPosition`.`rfp_p_id`=:positionID
                    AND hiring_RoleForPosition.rfp_u_id=:userID
                ORDER BY `hiring_RoleForPosition`.`rfp_r_id` ASC";
            $params = array(
                ':userID'=>$userID,
                ':positionID'=>$positionID
            );
            $result = $this->conn->query($sql, $params);

            if(!count($result)) {
                return false;
            }
            
            return self::ExtractRoleForPositionFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch get user role for position: ' . $e->getMessage());

            return false;
        }
    }


    /**
     * Gets all members for a position in the database.
     * 
     * @param string $positionID  The position to get the members for
     *
     * @return Model\RoleForPosition|boolean  The members or `false` if the fetch fails or the position has no roles
     */
    public function getAllPositionMembers($positionID) {
        try {
            $sql = '
                SELECT * FROM `hiring_RoleForPosition` 
                INNER JOIN `hiring_Users` ON `hiring_RoleForPosition`.`rfp_u_id`=`hiring_Users`.`u_id`
                INNER JOIN `hiring_Position` ON `hiring_RoleForPosition`.`rfp_p_id`=`hiring_Position`.`p_id`
                INNER JOIN `hiring_Role` ON `hiring_RoleForPosition`.`rfp_r_id`=`hiring_Role`.`r_id`
                WHERE `hiring_RoleForPosition`.`rfp_p_id`=:positionID
                ORDER BY `hiring_RoleForPosition`.`rfp_r_id` ASC;
                ';
            $params = array(
                ':positionID'=>$positionID
            );
            $result = $this->conn->query($sql, $params);

            if(!count($result)) {
                return false;
            }
            
            return \array_map('self::ExtractRoleForPositionFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch members by position: ' . $e->getMessage());

            return false;
        }
    }

    public function getUsersByPositionRole($positionID, $role) {
        try {
            $sql = 'SELECT * FROM `hiring_RoleForPosition` 
                    INNER JOIN `hiring_Users` ON `hiring_RoleForPosition`.`rfp_u_id`=`hiring_Users`.`u_id`
                    INNER JOIN `hiring_Position` ON `hiring_RoleForPosition`.`rfp_p_id`=`hiring_Position`.`p_id`
                    INNER JOIN `hiring_Role` ON `hiring_RoleForPosition`.`rfp_r_id`=`hiring_Role`.`r_id`
                WHERE `hiring_RoleForPosition`.`rfp_p_id`=:positionID
                    AND `hiring_RoleForPosition`.`rfp_r_id`=:roleID
                ORDER BY `hiring_RoleForPosition`.`rfp_r_id` ASC;
                ';
            $params = array(
                ':positionID'=>$positionID,
                ':roleID'=>$role->getID()
            );
            $result = $this->conn->query($sql, $params);

            if(!count($result)) {
                return false;
            }
            
            return \array_map('self::ExtractRoleForPositionFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch members by position role: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Creates a new Role object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing role information
     * @return \Model\Role
     */
    public static function ExtractRoleFromRow($row) {
        $role = new Role($row['r_id']);
        $role->setName($row['r_name']);

        return $role;
    }

    /**
     * Creates a new Role object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing role information
     * @return \Model\Role
     */
    public static function ExtractRoleForPositionFromRow($row) {
        $role = self::ExtractRoleFromRow($row);
        $user = UserDao::ExtractUserFromRow($row);
        $position = PositionDao::ExtractPositionFromRow($row);

        $roleForPosition = new RoleForPosition($row['rfp_id']);
        $roleForPosition->setRole($role);
        $roleForPosition->setUser($user);
        $roleForPosition->setPosition($user);

        return $roleForPosition;
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
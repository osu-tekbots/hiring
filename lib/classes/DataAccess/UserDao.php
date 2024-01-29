<?php
namespace DataAccess;

use Model\User;
use Model\UserAuth;
use Model\UserAuthProvider;

// TODO: Functions in block comments are not updated for this site

/**
 * Contains logic for database interactions with user data in the database. 
 * 
 * DAO stands for 'Data Access Object'
 */
class UserDao {

    /** @var DatabaseConnection */
    private $conn;

    /** @var \Util\Logger */
    private $logger;

    /** @var boolean */
    private $echoOnError;

    /**
     * Constructs a new instance of a User Data Access Object.
     *
     * @param DatabaseConnection $connection the connection used to perform user-related queries on the database
     * @param \Util\Logger $logger the logger to use for logging messages and errors associated with fetching user data
     * @param boolean $echoOnError determines whether to echo an error whether or not a logger is present
     */
    public function __construct($connection, $logger = null, $echoOnError = false) {
        $this->logger = $logger;
        $this->conn = $connection;
        $this->echoOnError = $echoOnError;
    }

    /**
     * Fetches all the users from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     *
     * @return User[]|boolean an array of User objects if the fetch succeeds, false otherwise
     */
    public function getAllUsers() {
        try {
            $sql = 'SELECT * FROM hiring_Users 
                ORDER BY `hiring_Users`.`u_lName` ASC';
            $result = $this->conn->query($sql);

            return \array_map('self::ExtractUserFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch all users: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all the users for a position from the database.
     * 
     * If an error occurs during the fetch, the function will return `false`.
     * 
     * @param string $positionID the ID of the position to fetch users for
     *
     * @return User[]|boolean an array of User objects if the fetch succeeds, false otherwise
     */
    public function getUsersForPosition($positionID) {
        try {
            $sql = 'SELECT * FROM hiring_Users 
                INNER JOIN hiring_RoleForPosition ON hiring_RoleForPosition.rfp_u_id = hiring_Users.u_id
                WHERE hiring_RoleForPosition.rfp_p_id = :positionID
                    AND hiring_RoleForPosition.rfp_r_id <> 5
                ORDER BY `hiring_Users`.`u_lName` ASC'; // rfp_r_id = 5 means inactive
            $params = array(':positionID' => $positionID);
            $result = $this->conn->query($sql, $params);

            return \array_map('self::ExtractUserFromRow', $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch users for position: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a single user with the given ID from the database.
     *
     * @param string $id the ID of the user to fetch
     * 
     * @return User|boolean the corresponding User from the database if the fetch succeeds and the user exists, 
     * false otherwise
     */
    public function getUserByID($id) {
        try {
            $sql = 'SELECT * 
                FROM hiring_Users
                WHERE hiring_Users.u_id = :id
            ';
            $params = array(':id' => $id);
            $result = $this->conn->query($sql, $params);
            if (\count($result) == 0) {
                return false;
            }

            return self::ExtractUserFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch single user by ID: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a single user with the given authentication credentials from the database.
     *
     * @param Model\UserAuthProvider $authProvider  the auth provider to check credentials for
     * @param string $id  the ID from the auth provider of the user to fetch
     * 
     * @return User|boolean  the corresponding User from the database if the fetch succeeds and the user exists, 
     *  false otherwise
     */
    public function getUserFromAuth($authProvider, $id) {
        try {
            $sql = 'SELECT * 
                FROM hiring_Users, hiring_UserAuth
                WHERE hiring_UserAuth.ua_providerId = :id
                    AND hiring_UserAuth.ua_uap_id = :pId
                    AND hiring_UserAuth.ua_u_id = hiring_Users.u_id
            ';
            $params = array(
                ':id' => $id,
                ':pId' => $authProvider->getID()
            );
            $result = $this->conn->query($sql, $params);
            if (\count($result) == 0) {
                return false;
            }
            
            return self::ExtractUserFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch single user by auth info: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a single user with the given email address from the database.
     *
     * @param string $emailAddress  the email of the user to fetch
     * 
     * @return User|boolean  the corresponding User from the database if the fetch succeeds and the user exists, 
     *  false otherwise
     */
    public function getUserByEmail($emailAddress) {
        try {
            $sql = 'SELECT * 
                FROM hiring_Users
                WHERE u_email = :email
            ';
            $params = array(
                ':email' => $emailAddress
            );
            $result = $this->conn->query($sql, $params);
            if (\count($result) == 0) {
                return false;
            }
            
            return self::ExtractUserFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch single user by email: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a single user's local ID from the database.
     *
     * @param string $userID  the ID of the user to fetch
     * @param Model\Provider $provider  the provider to fetch and ID for
     * 
     * @return User|boolean  the corresponding User from the database if the fetch succeeds and the user exists, 
     *  false otherwise
     */
    public function getProviderUserID($userID, $provider) {
        try {
            $sql = 'SELECT `ua_providerID` 
                FROM hiring_UserAuth
                WHERE hiring_UserAuth.ua_uap_id = :providerID
                    AND hiring_UserAuth.ua_u_id = :userID
            ';
            $params = array(
                ':providerID' => $provider->getID(),
                ':userID' => $userID
            );
            $result = $this->conn->query($sql, $params);
            if (\count($result) == 0) {
                return false;
            }
            
            return $result[0]['ua_providerID'];
        } catch (\Exception $e) {
            $this->logError('Failed to fetch a single user\'s database ID by auth info: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches all auth providers that a given users has set up
     *
     * @param string $emailAddress the email address for the user to fetch data for
     * 
     * @return AuthProvider[]|boolean the corresponding AuthProviders from the database if the fetch succeeds and the
     *  user exists, false otherwise
     */
    public function getAuthProvidersForUserByEmail($emailAddress) {
        try {
            $sql = 'SELECT * 
                FROM hiring_UserAuth
                INNER JOIN hiring_Users ON `hiring_Users`.`u_id` = `hiring_UserAuth`.`ua_u_id`
                INNER JOIN hiring_UserAuthProvider ON `hiring_UserAuth`.`ua_uap_id` = `hiring_UserAuthProvider`.`uap_id`
                WHERE hiring_Users.u_email = :email;
            ';
            $params = array(':email' => $emailAddress);
            $result = $this->conn->query($sql, $params);

            if (\count($result) == 0) {
                return false;
            }

            return \array_map("self::ExtractAuthProviderFromRow", $result);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch auth providers for user by email: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a single auth provider with the given name from the database.
     *
     * @param string $name the name of the auth provider to fetch
     * 
     * @return AuthProvider|boolean the corresponding AuthProvider from the database if the fetch succeeds and the auth
     *  provider exists, false otherwise
     */
    public function getAuthProviderByName($name) {
        try {
            $sql = 'SELECT * 
                FROM hiring_UserAuthProvider
                WHERE uap_name = :name
            ';
            $params = array(':name' => $name);
            $result = $this->conn->query($sql, $params);
            if (\count($result) == 0) {
                return false;
            }

            return self::ExtractAuthProviderFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch auth provider by name: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Fetches a single user with the given local credentials from the database.
     *
     * @param string $emailAddress  the email of the user to fetch
     * @param string $password  the password of the user to fetch
     * 
     * @return User|boolean  the corresponding User from the database if the fetch succeeds and the user has the given 
     *  credentials, false otherwise
     */
    public function getLocalUserWithCredentials($emailAddress, $password) {
        try {
            $sql = 'SELECT * 
                FROM hiring_Users
                INNER JOIN hiring_UserAuth ON `u_id` = `hiring_UserAuth`.`ua_u_id`
                INNER JOIN hiring_LocalAuth ON `hiring_UserAuth`.ua_providerId = `hiring_LocalAuth`.la_id 
                WHERE hiring_Users.u_email = :email
            ';
            $params = array(
                ':email' => $emailAddress
            );
            $result = $this->conn->query($sql, $params);

            // Check that a user was found
            if (\count($result) == 0) {
                return false;
            }

            // Check if salted & hashed password matches $password
            if(!\password_verify($password, $result[0]['la_password'])) {
                return false;
            }
            
            return self::ExtractUserFromRow($result[0]);
        } catch (\Exception $e) {
            $this->logError('Failed to fetch single user by local credentials: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Checks if the user has a reset code that matches
     * 
     * @param string $emailAddress  The email address for the user to check
     * @param string $code  The code to check matches
     * 
     * @return bool  Whether the user exists and the code matches or not
     */
    public function checkLocalResetAttempt($emailAddress, $code) {
        try {
            $sql =  "SELECT * FROM hiring_LocalAuth
                WHERE la_id = (
                    SELECT `hiring_UserAuth`.`ua_providerId` FROM `hiring_UserAuth`
                    INNER JOIN `hiring_Users` ON `hiring_Users`.`u_id` = `hiring_UserAuth`.`ua_u_id`
                    INNER JOIN `hiring_UserAuthProvider` ON `hiring_UserAuthProvider`.`uap_id` = `hiring_UserAuth`.`ua_uap_id`
                    WHERE `hiring_Users`.`u_email` = :userEmail
                        AND `hiring_UserAuthProvider`.`uap_name` = 'Local'
                    )
                    AND la_resetCode = :code
                    AND la_resetExpires > NOW();";

            $params = array(
                ':userEmail' => $emailAddress,
                ':code' => $code
            );

            $result = $this->conn->query($sql, $params);
            
            if(!\count($result)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to check local password reset code: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adds a new user to the database.
     *
     * @param \Model\User $user the user to add to the database
     * @return boolean true if the query execution succeeds, false otherwise.
     */
    public function addNewUser($user) {
        try {
            $sql = 'INSERT INTO hiring_Users(
                    u_id, 
                    u_accessLevel,
                    u_fName, 
                    u_lName, 
                    u_email, 
                    u_phone,
                    u_dateCreated
                ) 
                VALUES (
                    :id,
                    :accessLevel,
                    :fname,
                    :lname,
                    :email,
                    :phone,
                    :datec
                )';
            $params = array(
                ':id' => $user->getID(),
                ':accessLevel' => $user->getAccessLevel(),
                ':fname' => $user->getFirstName(),
                ':lname' => $user->getLastName(),
                ':email' => $user->getEmail(),
                ':phone' => $user->getPhone(),
                ':datec' => $user->getDateCreated()->format('Y-m-d H:i:s')
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to add new user: ' . $e->getMessage());

            return false;
        }
    }
    
    /**
     * Updates a user in the database.
     *
     * @param \Model\User $user the user to update in the database
     * 
     * @return boolean true if the update execution succeeds, false otherwise.
     */
    public function updateUser($user) {
        try {
            $sql = 'UPDATE hiring_Users
                SET u_accessLevel = :accessLevel,
                    u_fName = :fname,
                    u_lName = :lname,
                    u_email = :email,
                    u_phone = :phone,
                    u_dateCreated = :datec,
                    u_dateUpdated = :dateu
                WHERE u_id = :id';
            $params = array(
                ':accessLevel' => $user->getAccessLevel(),
                ':fname' => $user->getFirstName(),
                ':lname' => $user->getLastName(),
                ':email' => $user->getEmail(),
                ':phone' => $user->getPhone(),
                ':datec' => $user->getDateCreated()->format('Y-m-d H:i:s'),
                ':dateu' => $user->getDateUpdated()->format('Y-m-d H:i:s'),
                ':id' => $user->getID()
            );
            $this->conn->execute($sql, $params);
            $this->logger->warn('Updating user information:');
            $this->logger->info(var_export($params, true));

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update user: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Updates a single user's local ID from the database.
     *
     * @param string $userID  the ID of the user to update the local ID for
     * @param string $newID   the new local ID
     * @param Model\Provider $provider  the provider to update the ID for
     * 
     * @return boolean  Whether the update succeeds
     */
    public function updateProviderUserID($userID, $newID, $provider) {
        try {
            $sql = 'UPDATE `hiring_UserAuth` 
                SET hiring_UserAuth.ua_providerId = :newID
                WHERE hiring_UserAuth.ua_uap_id = :providerID
                    AND hiring_UserAuth.ua_u_id = :userID
            ';
            $params = array(
                ':newID' => $newID,
                ':providerID' => $provider->getID(),
                ':userID' => $userID
            );
            $this->conn->execute($sql, $params);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to update a single user\'s provider ID: ' . $e->getMessage());

            return false;
        }
    }

    
    /**
     * Adds a new user authentication token to the database.
     *
     * @param \Model\User $userAuth the user authentication token to add to the database
     * 
     * @return boolean true if the query execution succeeds, false otherwise.
     */
    public function addNewUserAuth($userAuth) {
        try {
            $sql = '
                INSERT INTO hiring_UserAuth(
                    ua_u_id,
                    ua_uap_id,
                    ua_providerId
                ) 
                VALUES (
                    :uId,
                    :uapId,
                    :providerId
                )';
            $params = array(
                ':uId' => $userAuth->getUserID(),
                ':uapId' => $userAuth->getAuthProvider()->getID(),
                ':providerId' => $userAuth->getProviderID(),
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to add new user authentication token: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Adds a new user authentication token to the database.
     *
     * @param \Model\User $userAuth the user authentication token to add to the database
     * 
     * @return boolean true if the query execution succeeds, false otherwise.
     */
    public function addNewLocalAuth($userID, $password) {
        try {
            $laPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = '
                INSERT INTO hiring_LocalAuth(
                    la_id, 
                    la_password
                ) 
                VALUES (
                    :id,
                    :pass
                )';
            $params = array(
                ':id' => $userID,
                ':pass' => $laPassword
            );
            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to add new local authentication token: ' . $e->getMessage());

            return false;
        }
    }

    /** 
     * Sets a new password for a local user
     * 
     * @param string $emailAddress  The email address for the user to set a password for
     * @param string $password  The password to set for the user
     * 
     * @return bool  Whether the SQL call succeeded
     */
    public function setLocalPassword($emailAddress, $password) {
        try {
            $laPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $sql =  "UPDATE hiring_LocalAuth
                SET la_password = :pass
                WHERE la_id = (
                    SELECT `hiring_UserAuth`.`ua_providerId` FROM `hiring_UserAuth`
                    INNER JOIN `hiring_Users` ON `hiring_Users`.`u_id` = `hiring_UserAuth`.`ua_u_id`
                    INNER JOIN `hiring_UserAuthProvider` ON `hiring_UserAuthProvider`.`uap_id` = `hiring_UserAuth`.`ua_uap_id`
                    WHERE `hiring_Users`.`u_email` = :userEmail
                        AND `hiring_UserAuthProvider`.`uap_name` = 'Local'
                );";

            $params = array(
                ':userEmail' => $emailAddress,
                ':pass' => $laPassword
            );

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to set local password: ' . $e->getMessage());
            return false;
        }
    }

    /** 
     * Sets a new password reset code for a local user
     * 
     * @param string $userEmail  The email address for the user to set a code for
     * @param string $code       The reset code to set for the user
     * 
     * @return bool  Whether the SQL call succeeded
     */
    public function setLocalResetAttempt($userEmail, $code) {
        try {
            $expire_minutes = 45;

            $sql =  "UPDATE hiring_LocalAuth
                SET la_resetCode = :code,
                    la_resetExpires = NOW() + INTERVAL $expire_minutes MINUTE
                WHERE la_id = (
                    SELECT `hiring_UserAuth`.`ua_providerId` FROM `hiring_UserAuth`
                    INNER JOIN `hiring_Users` ON `hiring_Users`.`u_id` = `hiring_UserAuth`.`ua_u_id`
                    INNER JOIN `hiring_UserAuthProvider` ON `hiring_UserAuthProvider`.`uap_id` = `hiring_UserAuth`.`ua_uap_id`
                    WHERE `hiring_Users`.`u_email` = :userEmail
                        AND `hiring_UserAuthProvider`.`uap_name` = 'Local'
                );";

            $params = array(
                ':userEmail' => $userEmail,
                ':code' => $code
            );

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to add new password reset attempt: ' . $e->getMessage());
            return false;
        }
    }

    /** 
     * Clears the new password reset code for a user
     * 
     * @param string $userEmail  The email address for the user to clear the code for
     * 
     * @return bool  Whether the SQL call succeeded
     * */
    public function clearLocalResetAttempt($userEmail) {
        try {
            $sql =  "UPDATE hiring_LocalAuth
                SET la_resetCode = NULL,
                    la_resetExpires = NULL
                WHERE la_id = (
                    SELECT `hiring_UserAuth`.`ua_providerId` FROM `hiring_UserAuth`
                    INNER JOIN `hiring_Users` ON `hiring_Users`.`u_id` = `hiring_UserAuth`.`ua_u_id`
                    INNER JOIN `hiring_UserAuthProvider` ON `hiring_UserAuthProvider`.`uap_id` = `hiring_UserAuth`.`ua_uap_id`
                    WHERE `hiring_Users`.`u_email` = :userEmail
                        AND `hiring_UserAuthProvider`.`uap_name` = 'Local'
                );";

            $params = array(
                ':userEmail' => $userEmail,
            );

            $this->conn->execute($sql, $params);

            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to remove password reset code: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a new User object by extracting the information from a row in the database.
     *
     * @param string[] $row a row from the database containing user information
     * @return \Model\User
     */
    public static function ExtractUserFromRow($row) {
        $user = new User($row['u_id']);
        $user->setAccessLevel($row['u_accessLevel']);
        $user->setFirstName($row['u_fName']);
        $user->setLastName($row['u_lName']);
        $user->setEmail($row['u_email']);
        $user->setPhone($row['u_phone']);
        $user->setDateCreated(new \DateTime(($row['u_dateCreated'] == '' ? 'now' : $row['u_dateCreated'])));
        $user->setDateUpdated(new \DateTime(($row['u_dateUpdated'] == '' 
            ? ($row['u_dateCreated'] == '' ? 'now' : $row['u_dateCreated'])
            : $row['u_dateUpdated'])));

        return $user;
    }

    /**
     * Creates a new UserAuthProvider object by extracting the necessary information from a row in a database.
     *
     * @param mixed[] $row the row from the database
     * @return \Model\UserType the UserAuthProvider object extracted from the row
     */
    public static function ExtractAuthProviderFromRow($row) {
        $userAuthProvider = new UserAuthProvider(\intval($row['uap_id']));
        $userAuthProvider->setName($row['uap_name']);
        $userAuthProvider->setActive($row['uap_active']);
        return $userAuthProvider;
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

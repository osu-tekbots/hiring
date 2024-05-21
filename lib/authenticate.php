<?php
/**
 * This script ensures that the user is properly authenticated for the SPT. It is designed
 * to prevent session collisions with other sites TekBots manages, such as the TekBots 
 * storefront, Project Portal, and SPT.
 * 
 * This script should be included in every script the user can potentially call, including
 * all pages/ and api/ files. The best spot for it is in bootstrap.php
 */


use DataAccess\UserDao;
use DataAccess\RoleDao;
$userDao = new UserDao($dbConn, $logger);
$roleDao = new RoleDao($dbConn, $logger);

if (!session_id()) session_start();

$user = NULL;

// Get user & set $_SESSION user variables for this site
if(isset($_SESSION['site']) && $_SESSION['site'] == 'hiring') {
    // $_SESSION["site"] is this one! User info should be correct
} else {
    if(isset($_SESSION['auth']['method'])) {
        switch($_SESSION['auth']['method']) {
            case 'onid':
                // Logged in with ONID on another site; storing this site's user info in $_SESSION...
                $onidProvider = $userDao->getAuthProviderByName('ONID');
                $user = $userDao->getUserFromAuth($onidProvider, $_SESSION['auth']['id']);
                
                if($user) {
                    $_SESSION['site'] = 'hiring';
                    $_SESSION['userID'] = $user->getID();
                    $_SESSION['userAccessLevel'] = $user->getAccessLevel();
                }
                
                break;

            case 'hiring':
                // Logged in with local credentials before moving to another site; storing this site's user info in $_SESSION...
                $hiringProvider = $userDao->getAuthProviderByName('Local');
                $user = $userDao->getUserFromAuth($hiringProvider, $_SESSION['auth']['id']);
                
                if($user) {
                    $_SESSION['site'] = 'hiring';
                    $_SESSION['userID'] = $user->getID();
                    $_SESSION['userAccessLevel'] = $user->getAccessLevel();
                }
                
                break;
            
            default:
                // Logged in with something not valid for this site; setting as not logged in
                $logger->info('Authentication provider is '.$_SESSION['auth']['method'].', not something this site recognizes');

                $_SESSION['site'] = NULL;
                $_SESSION['userID'] = NULL;
                $_SESSION['userAccessLevel'] = NULL;
        }
    } else {
        // Not logged in; best to make sure everything's clear
        $_SESSION['site'] = NULL;
        $_SESSION['userID'] = NULL;
        $_SESSION['userAccessLevel'] = NULL;
    }
}

/**
 * Checks if the person who initiated the current request has one of the given access levels
 * 
 * @param string|string[] $allowedAccessLevels  The access level(s) that should be accepted. Options are:
 *      * "public"
 *      * "user"
 *      * "admin"
 * 
 * @return bool Whether the person who initiated the current request has one of the given access levels
 */
function verifyPermissions($allowedAccessLevels) {
    try {
        $isLoggedIn = isset($_SESSION['userID']) && !empty($_SESSION['userID']);
        $isAdmin = $isLoggedIn && isset($_SESSION['userAccessLevel']) && $_SESSION['userAccessLevel'] == 'Admin';

        $allowPublic    = (gettype($allowedAccessLevels)=='string') ? $allowedAccessLevels=='public' : in_array('public', $allowedAccessLevels);
        $allowUsers     = (gettype($allowedAccessLevels)=='string') ? $allowedAccessLevels=='user'   : in_array('user',   $allowedAccessLevels);
        $allowAdmin     = (gettype($allowedAccessLevels)=='string') ? $allowedAccessLevels=='admin'  : in_array('admin',  $allowedAccessLevels);
        
        if($allowPublic) {
            return true;
        }
        if($allowUsers && $isLoggedIn) {
            return true;
        }
        if($allowAdmin && $isAdmin) {
            return true;
        }
    } catch(\Exception $e) {
        $logger->error('Failure while verifying user permissions: '.$e->getMessage());
    } 
    
    return false;
}

/**
 * Checks if the person who initiated the current request has one of the given roles for the given position. This assumes
 *  that the request initiator is already known to be logged in.
 * 
 * @param string|string[] $allowedRoles  The role(s) that should be accepted. Options are:
 *      * Any name in the Roles database table
 *      * "Any" (ensures the user is associated with the position and not inactive)
 * @param string          $positionID    The position to check the current user's role in
 * 
 * @return bool Whether the person who initiated the current request has one of the given roles for the given position
 */
function checkRoleForPosition($allowedRoles, $positionID) {
    try {
        global $roleDao;

        $isAdmin = isset($_SESSION['userAccessLevel']) && $_SESSION['userAccessLevel'] == 'Admin';
        if($isAdmin) {
            return true; // Admins should always be able to do anything necessary
        }


        $currentRoleObject = $roleDao->getUserRoleForPosition($_SESSION['userID'], $positionID);
        if(is_null($currentRoleObject) || !$currentRoleObject) {
            return false;
        }
        $currentRole = $currentRoleObject->getRole()->getName();

        $allowAny      = (gettype($allowedRoles)=='string') ? $allowedRoles=='Any'             : in_array('Any',             $allowedRoles);
        $allowOther    = (gettype($allowedRoles)=='string') ? $allowedRoles=='Other'           : in_array('Other',           $allowedRoles);
        $allowMember   = (gettype($allowedRoles)=='string') ? $allowedRoles=='Member'          : in_array('Member',          $allowedRoles);
        $allowAdvocate = (gettype($allowedRoles)=='string') ? $allowedRoles=='Search Advocate' : in_array('Search Advocate', $allowedRoles);
        $allowChair    = (gettype($allowedRoles)=='string') ? $allowedRoles=='Search Chair'    : in_array('Search Chair',    $allowedRoles);
        
        if($allowAny && $currentRole!="Inactive") {
            return true; // Already validated that there is a role object for this user
        }
        if($allowOther && $currentRole=="Other") {
            return true;
        }
        if($allowMember && $currentRole=="Member") {
            return true;
        }
        if($allowAdvocate && $currentRole=="Search Advocate") {
            return true;
        }
        if($allowChair && $currentRole=="Search Chair") {
            return true;
        }
    } catch(\Exception $e) {
        $logger->error('Failure while verifying user role: '.$e->getMessage());
    } 
    
    return false;
}
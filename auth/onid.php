<?php
include_once '../bootstrap.php';

use DataAccess\UserDao;
use Model\User;
use Model\UserAuth;

/**
 * Uses ONID to authenticate the user. 
 * 
 * When the function returns, the user will have been authenticated and the $_SESSION variable will have been set
 * with their information.
 *
 * @return void
 */
function authenticateStudent() {
    global $dbConn, $logger;

    include_once PUBLIC_FILES . '/auth/onidfunctions.php';
    $onid = authenticateWithONID();

    $dao = new UserDao($dbConn, $logger);
    
    $onidProvider = $dao->getAuthProviderByName('ONID');
    $u = $dao->getUserFromAuth($onidProvider, $onid);

    if ($u) {
        $_SESSION['site'] = 'hiring';
        $_SESSION['userID'] = $u->getID();
        $_SESSION['userAccessLevel'] = $u->getAccessLevel();
        $_SESSION['newUser'] = false;
    } else {
        $u = new User();
        $u->setAccessLevel('User');
        $u->setFirstName($_SESSION['auth']['firstName']);
        $u->setLastName($_SESSION['auth']['lastName']);
        $u->setEmail($_SESSION['auth']['email']);
        $ok = $dao->addNewUser($u);
        // TODO: handle error

        $ua = new UserAuth();
        $ua->setUserID($u->getID());
        $ua->setAuthProvider($onidProvider);
        $ua->setProviderID($_SESSION['auth']['id']);
        $ok = $dao->addNewUserAuth($ua);

        $_SESSION['site'] = 'hiring';
        $_SESSION['userID'] = $u->getID();
        $_SESSION['userAccessLevel'] = $u->getAccessLevel();
        $_SESSION['newUser'] = true;
    }
    return true;
}

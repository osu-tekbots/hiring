<?php
include_once '../bootstrap.php';

use DataAccess\UsersDao;
use Model\User;
use Model\UserAuthProvider;
use Model\UserType;

if (!isset($_SESSION)) {
    session_start();
}

/**
 * Uses the local login system to authenticate the user.
 */
function authenticateWithLocal() {
	global $configManager;
	
    // Redirect to homepage if already logged in
    $isLoggedIn = verifyPermissions(['user', 'admin']);
    if ($isLoggedIn) {
        $redirect = $configManager->getBaseUrl() . 'pages/userDashboard.php';
        echo "<script>window.location.replace('$redirect');</script>";
        die();
    }

    // Redirect to local login page
	$pageURL = $configManager->getBaseUrl();
    $url = $pageURL . 'pages/localLogin.php';
    echo "<script>location.replace('" . $url . "');</script>";
    die();

}


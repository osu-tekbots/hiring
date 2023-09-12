<?php
/**
 * Attempts to authenticate a user with the credentials given. On successful authentication, this script will set 
 *  $_SESSION and $_SESSION['auth'] with the user's information. On failure, it redirects to the error landing page.
 */
include_once '../bootstrap.php';

use DataAccess\UserDao;

if(!isset($_SESSION)) {
    session_start();
}

// Redirect to homepage if already logged in
$isLoggedIn = verifyPermissions(['user', 'admin']);
if ($isLoggedIn) {
    // Redirect to their dashboard
    $redirect = $configManager->getBaseUrl() . 'pages/userDashboard.php';
    echo "<script>alert('Already logged in.');window.location.replace('$redirect');</script>";
    die();
}

// Ensure request contains login credentials÷
if ( !isset($_POST) || (isset($_POST) && !isset($_POST['localEmail'])) || (isset($_POST) && !isset($_POST['localPassword'])) ) {
    $_SESSION['error'] = 'Your login request did not provide all necessary authentication information';
    $redirect = $configManager->getBaseUrl() . 'pages/error.php';
    echo "<script>location.replace('" . $redirect . "');</script>";
	die();
} 

// Try to get a user using the login credentials
$userDao = new UserDao($dbConn, $logger);
$u = $userDao->getLocalUserWithCredentials($_POST['localEmail'], $_POST['localPassword']);
if ($u) {
    // Set $_SESSION with the user's information
    $hiringProvider = $userDao->getAuthProviderByName('Local');
    $_SESSION['auth']['method'] = 'hiring';
    $_SESSION['auth']['id'] = $userDao->getProviderUserID($u->getID(), $hiringProvider);

    $_SESSION['site'] = 'hiring';
    $_SESSION['userID'] = $u->getID();
    $_SESSION['userAccessLevel'] = $u->getAccessLevel();
    $_SESSION['newUser'] = false;
    
    $redirect = $configManager->getBaseUrl() . 'pages/userDashboard.php';
    echo "<script>location.replace('" . $redirect . "');</script>";
	die();
}

// No matching user found; tell the user that authentication failed
$_SESSION['error'] = 'We were unable to locate an account with those credentials. Either your username/password was incorrect, or you need to create a new account.';
$redirect = $configManager->getBaseUrl() . 'pages/error.php';
echo "<script>location.replace('" . $redirect . "');</script>";
die();


?>
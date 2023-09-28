<?php
/**
 * This script handles creating new local users and letting existing local users reset their passwords.
 * Note: An Api\--ActionHandler is not used because Api\ActionHandler expects JSON, not form-encoded data
 */

include_once '../bootstrap.php';

// Setup our data access and handler classes
use DataAccess\UserDao;
use DataAccess\MessageDao;
use Model\User;
use Model\UserAuth;
use Util\IdGenerator;
use Email\Mailer;
use Email\HiringMailer;

if(!session_id()) {
    session_start();
}

$userDao = new UserDao($dbConn, $logger);
$messageDao = new MessageDao($dbConn, $logger);

// Verify parameters
if (!isset($_POST) || (isset($_POST) && !isset($_POST['userEmail'])) || (isset($_POST) && !isset($_POST['action'])) ) {
    displayError('It looks like you submitted a form without the required data. Please be sure to enter an email address.');
} 

// Call the appropriate handler function
switch($_POST['action']) {
    case 'login':
        login($userDao, $configManager);
        break;

    case 'addUser': 
        addUser($userDao, $messageDao, $configManager, $logger);
        break;

    case 'forgotPassword':
        forgotPassword($userDao, $messageDao, $configManager, $logger);
        break;
    
    case 'resetPassword':
        resetPassword($userDao, $configManager, $logger, $dbConn);
        break;
    
    default:
        // displayError('Your request to the local user API is invalid.');

}

/**
 * Generates a redirect to the error landing page to display the given message
 * 
 * @global \Util\ConfigManager $configManager
 * 
 * @param string $message The message to display to the user on the error landing page
 * 
 * @return void
 */
function displayError($message) {
    global $configManager;

    $_SESSION['error'] = $message;
    $redirect = $configManager->getBaseUrl() . 'pages/error.php';
    echo "<script>window.location.replace('$redirect');</script>";
    die();    
}

/**
 * Attempts to authenticate a user with the credentials given. On successful authentication, this script will set 
 *  $_SESSION and $_SESSION['auth'] with the user's information. On failure, it redirects to the error landing page.
 * 
 * @param \DataAccess\UserDao $userDao The class for communicating with the user database table
 * @param \Util\ConfigManager $configManager The class for accessing info in /config.ini
 * 
 * @return void
 */
function login($userDao, $configManager) {
    // Redirect to homepage if already logged in
    if (verifyPermissions(['user', 'admin'])) {
        // Redirect to their dashboard
        $redirect = $configManager->getBaseUrl() . 'pages/user/dashboard.php';
        echo "<script>window.location.replace('$redirect');</script>";
        die();
    }

    // Ensure request contains login credentials
    if (!isset($_POST['localPassword'])) {
        displayError('Your login request did not provide all necessary authentication information. Please be sure to enter a password!');
    } 

    // Try to get a user using the login credentials
    $u = $userDao->getLocalUserWithCredentials($_POST['userEmail'], $_POST['localPassword']);
    if ($u) {
        // Set $_SESSION with the user's information
        $hiringProvider = $userDao->getAuthProviderByName('Local');
        $_SESSION['auth']['method'] = 'hiring';
        $_SESSION['auth']['id'] = $userDao->getProviderUserID($u->getID(), $hiringProvider);

        $_SESSION['site'] = 'hiring';
        $_SESSION['userID'] = $u->getID();
        $_SESSION['userAccessLevel'] = $u->getAccessLevel();
        $_SESSION['newUser'] = false;
        
        // Redirect to homepage
        $redirect = $configManager->getBaseUrl() . 'pages/user/dashboard.php';
        echo "<script>location.replace('" . $redirect . "');</script>";
        die();
    }

    // No matching user found; tell the user that authentication failed
    displayError('We were unable to locate an account with those credentials. Either your username/password was incorrect, or you need to create a new account.');
}

/**
 * Handles the process of creating a new local user
 * 
 * @param \DataAccess\UserDao $userDao The class for communicating with the user database table
 * @param \Util\ConfigManager $configManager The class for accessing info in /config.ini
 * @param \Util\Logger $logger The class for logging execution details
 * 
 * @return void
 */
function addUser($userDao, $messageDao, $configManager, $logger) {
    $user = $userDao->getUserByEmail($_POST['userEmail']);

    // Verify that there's no already-existing user & all necessary information is given
    if ($user) { 
        displayError('The email you entered is already registered. Please authenticate through the login page.');
    }
    if ($_POST['userFirst'] == '' || $_POST['userLast'] == ''){
        displayError('Please enter a first and last name for your account.');
    }

    $localProvider = $userDao->getAuthProviderByName('Local');

    //
    // Create a new user
    //

    // Create a user with the given information
    $u = new User();
    $u->setAccessLevel('User');
    $u->setFirstName($_POST['userFirst']);
    $u->setLastName($_POST['userLast']);
    $u->setEmail($_POST['userEmail']);
    $ok = $userDao->addNewUser($u);
    if(!$ok) {
        displayError('We failed to create a new user with your credentials.');
    }

    // Set local credentials
    $localAuthID = IdGenerator::generateSecureUniqueId();
    $ok = $userDao->addNewLocalAuth($localAuthID, IdGenerator::generateSecureUniqueId(24)); // Generate random password to protect the account until the user creates a password. Increase `24` for greater security.
    if(!$ok) {
        displayError('We failed to save your password. Please contact the site admins for help.');
    }

    // Connect user with local credentials
    $ua = new UserAuth();
    $ua->setUserID($u->getID());
    $ua->setAuthProvider($localProvider);
    $ua->setProviderID($localAuthID);
    $ok = $userDao->addNewUserAuth($ua);
    if(!$ok) {
        displayError('We failed to link your credentials with your account. Please contact the site admins for help.');
    }

    $logger->info("Added local user ". $_POST['userFirst'] . " " . $_POST['userLast']);


    //
    // Force the user to reset their password to verify their email address is correct
    //

    // Generate and save a reset code (increase `24` for greater security)
    $resetCode = IdGenerator::generateSecureUniqueId(24);
    $ok = $userDao->setLocalResetAttempt($_POST['userEmail'], $resetCode);
    if(!$ok) {
        displayError("We were unable to generate a code for setting your password. Please contact the site admins for support.");
    }

    // Generate an email with the user's password-set code & information on how to set their password
    $message = $messageDao->getMessageByID(2);
    $mailer = new HiringMailer($configManager->get('email.admin_address'), $configManager->get('email.admin_subject_tag'));
    $link = $configManager->getBaseUrl() . 'pages/local/resetPassword.php?email='.$_POST['userEmail'].'&resetCode=' .$resetCode;
    
    // Send the email to the user
    $ok = $mailer->sendLocalPasswordEmail($u, $message, $link, $resetCode);
    if(!$ok) {
        displayError("We were unable to send you a password creation email.");
    }

    // Redirect back to the base login page
    $redirect = $configManager->getBaseUrl() . 'pages/index.php';
    echo "<script>
            alert('You have been sent an email to verify your account and set your password. Follow the link in the email to finish setting up your account.');
            location.replace('" . $redirect . "');
        </script>";
    die();
}

/**
 * Handles the process of generating a reset code for a local user
 * 
 * @param \DataAccess\UserDao $userDao The class for communicating with the user database table
 * @param \Util\ConfigManager $configManager The class for accessing info in /config.ini
 * @param \Util\Logger $logger The class for logging execution details
 * 
 * @return void
 */
function forgotPassword($userDao, $messageDao, $configManager, $logger) {
    // Check that the user uses local authentication
    $authProviders = $userDao->getAuthProvidersForUserByEmail($_POST['userEmail']);
    if($authProviders === false) {
        displayError('It looks like there\'s no account associated with that email address.');
    }
    $providerNames = [];
    foreach($authProviders as $authProvider) {
        $providerNames[] = $authProvider->getName();
    }
    if(array_search('Local', $providerNames) === false) {
        displayError('It looks like there\'s no local account associated with that email address.');
    }

    // Generate and save a reset code (increase `24` for greater security)
    $resetCode = IdGenerator::generateSecureUniqueId(24);
    $ok = $userDao->setLocalResetAttempt($_POST['userEmail'], $resetCode);
    if(!$ok) {
        displayError("We were unable to generate a password reset code. Please contact the site admins for support.");
    }

    // Generate an email with the user's reset code & information on how to reset their password
    $message = $messageDao->getMessageByID(3);
    $mailer = new HiringMailer($configManager->get('email.admin_address'), $configManager->get('email.admin_subject_tag'));
    $link = $configManager->getBaseUrl() . 'pages/local/resetPassword.php?email='.$_POST['userEmail'].'&resetCode=' .$resetCode;
    
    // Send the email to the user
    $ok = $mailer->sendLocalPasswordEmail($userDao->getUserByEmail($_POST['userEmail']), $message, $link, $resetCode);
    if(!$ok) {
        displayError("We were unable to send you a password reset email.");
    }

    // Redirect back to the base login page
    $redirect = $configManager->getBaseUrl() . 'pages/local/login.php';
    echo "<script>alert('Password reset email has been sent. Follow the link in the email.');location.replace('" . $redirect . "');</script>";
    die();
}

/**
 * Handles the process of resetting a local user's password
 * 
 * @param \DataAccess\UserDao $userDao The class for communicating with the user database table
 * @param \Util\ConfigManager $configManager The class for accessing info in /config.ini
 * @param \Util\Logger $logger The class for logging execution details
 * @param \Util\DatabaseConnection $dbConn The class for communicating directly with the database (used for including header.php)
 * 
 * @return void
 */
function resetPassword($userDao, $configManager, $logger, $dbConn) {
    // Check that the user uses local authentication
    $authProviders = $userDao->getAuthProvidersForUserByEmail($_POST['userEmail']);
    $providerNames = [];
    foreach($authProviders as $authProvider) {
        $providerNames[] = $authProvider->getName();
    }
    if(array_search('Local', $providerNames) === false) {
        displayError('It looks like there\'s no local account associated with that email address.');
    }

    // Verify that request parameters exist
    if (!isset($_REQUEST['newUserPassword'])) {
        displayError('It looks like you didn\'t provide a new password.');
    } 
    if (!isset($_REQUEST['resetCode']) || !isset($_REQUEST['userEmail'])) {
        displayError("Your request seems to be missing information.");
    }

    // Check if reset code is valid
    if (!$userDao->checkLocalResetAttempt($_REQUEST['userEmail'], $_REQUEST['resetCode'])) {
        displayError("Your reset code doesn't seem to be valid.");
    } 
    
    // Update password
    $ok = $userDao->setLocalPassword($_REQUEST['userEmail'], $_REQUEST['newUserPassword']);
    if(!$ok) {
        displayError("We were unable to save your new password.");
    }

    // Clear the reset requests for the supplied email
    $userDao->clearLocalResetAttempt($_REQUEST['userEmail']);

    // Log in the user with their new credentials
    include_once PUBLIC_FILES . '/modules/header.php';
    echo "
        <form style='visibility: hidden' action='./auth/localEndpoint.php' method='POST'>
            <input type='hidden' name='action' value='login'>
            <input type='hidden' name='userEmail' value='".$_REQUEST['userEmail']."'>
            <input type='hidden' name='localPassword' value='".$_REQUEST['newUserPassword']."'>
            <button id='clickMe' class='btn btn-primary btn-lg' type='submit'>Login</button>
        <script>setTimeout(() => {document.getElementById('clickMe').click();}, 0);</script>";
    die();
}

?>
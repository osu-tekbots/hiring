<?php
/**
 * This script handles creating new local users and letting existing local users reset their passwords.
 * Note: An Api\--ActionHandler is not used because Api\ActionHandler expects JSON, not form-encoded data
 */

include_once '../bootstrap.php';

// Setup our data access and handler classes
use DataAccess\UserDao;
use Model\User;
use Model\UserAuth;
use Util\IdGenerator;
use Email\Mailer;

if(!session_id()) {
    session_start();
}

$userDao = new UserDao($dbConn, $logger);

// Verify parameters
if (!isset($_POST) || (isset($_POST) && !isset($_POST['userEmail'])) ) {
    displayError('It looks like you submitted a form without the required data. Please be sure to enter an email address.');
} 

// Call the appropriate handler function
switch($_POST['action']) {
    case 'addUser': 
        addUser($userDao, $configManager, $logger);
        break;

    case 'forgotPassword':
        forgotPassword($userDao, $configManager, $logger);
        break;
    
    case 'resetPassword':
        resetPassword($userDao, $configManager, $logger);
        break;
    
    default:
        displayError('Your request to the local user API is invalid.');

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
 * Handles the process of creating a new local user
 * 
 * @param \DataAccess\UserDao $userDao The class for communicating with the user database table
 * @param \Util\ConfigManager $configManager The class for accessing info in /config.ini
 * @param \Util\Logger $logger The class for logging execution details
 * 
 * @return void
 */
function addUser($userDao, $configManager, $logger) {
    $user = $userDao->getUserByEmail($_POST['userEmail']);

    // Verify that there's no already-existing user & all necessary information is given
    if ($user) { 
        displayError('The email you entered is already registered. Please authenticate through the login page.');
    }
    if ($_POST['userFirst'] == '' || $_POST['userLast'] == ''){
        displayError('Please enter a first and last name for your account.');
    }
    if($_POST['userPassword'] == '') {
        displayError('Please enter a password for your account.');
    }

    $localProvider = $userDao->getAuthProviderByName('Local');

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
    $ok = $userDao->addNewLocalAuth($localAuthID, $_POST['userPassword']);
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
        displayError('We failed to link your password with your account. Please contact the site admins for help.');
    }

    // Set the user as logged in
    $_SESSION['site'] = 'hiring';
    $_SESSION['userID'] = $u->getID();
    $_SESSION['userAccessLevel'] = $u->getAccessLevel();
    $_SESSION['newUser'] = true;

    $logger->info("Added local user ". $_POST['userFirst'] . " " . $_POST['userLast']);

    // Redirect the user to their newly-generated dashboard
    $redirect = $configManager->getBaseUrl() . 'pages/userDashboard.php';
    echo "<script>location.replace('" . $redirect . "');</script>";
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
function forgotPassword($userDao, $configManager, $logger) {
    // Check that the user uses local authentication
    $authProviders = $userDao->getAuthProvidersForUserByEmail($_POST['userEmail']);
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
    $mailer = new Mailer($configManager->get('email.admin_address'), 'TekBots Admin');
    $link = $configManager->getBaseUrl() . 'pages/localResetPassword.php?email='.$_POST['userEmail'].'&resetCode=' .$resetCode;
    $to = $_POST['userEmail'];
    $subject = "EECS Hiring Password Reset";
    $message = "<p>There was a request to reset a password for this email on the EECS Hiring site. 
        If this was not you or was in error, you may safely ignore this email. If you would like to reset your password,
        follow the link below within 45 minutes. If the link does not work, you can copy and past the link into your 
        address bar.</p>
        
        <a href='$link'>$link</a>

        <h1>Password Reset Code: $resetCode</h1>";
    
    // Send the email to the user
    $ok = $mailer->sendEmail($to, $subject, $message, true);
    if(!$ok) {
        displayError("We were unable to send you a password reset email.");
    }

    // Redirect back to the base login page
    $redirect = $configManager->getBaseUrl() . 'pages/localLogin.php';
    echo "<script>alert('Password reset email has been sent. Follow the link in the email.');location.replace('" . $redirect . "');</script>";
    die();
}

/**
 * Handles the process of resetting a local user's password
 * 
 * @param \DataAccess\UserDao $userDao The class for communicating with the user database table
 * @param \Util\ConfigManager $configManager The class for accessing info in /config.ini
 * @param \Util\Logger $logger The class for logging execution details
 * 
 * @return void
 */
function resetPassword($userDao, $configManager, $logger) {
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
        <form style='visibility: hidden' action='./auth/localAttempt.php' method='POST'>
            <input type='hidden' name='localEmail' value='".$_REQUEST['userEmail']."'>
            <input type='hidden' name='localPassword' value='".$_REQUEST['newUserPassword']."'>
            <button id='clickMe' class='btn btn-primary btn-lg' type='submit'>Login</button>
        <script>setTimeout(() => {document.getElementById('clickMe').click();}, 0);</script>";
    die();
}

?>
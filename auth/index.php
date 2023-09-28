<?php
/*
 * This script handles authentication for the hiring website. The request body to this page should include the desired
 *  authentication provider. This script will then redirect to the appropriate authentication provider and be redirected
 *  back to by that authentication provider. At that point, this script will redirect to the appropriate home page.
 */
include_once '../bootstrap.php';

 if (!isset($_SESSION)) {
     session_start();
 }

$provider = isset($_GET['provider']) 
                ? $_GET['provider'] 
                : (
                    isset($_SESSION['provider'])
                        ? $_SESSION['provider']
                        : false
                );
if ($provider) {
    $_SESSION['provider'] = $provider;
    switch ($provider) {

        case 'onid':
            include_once PUBLIC_FILES . '/auth/onid.php';
    
            $ok = authenticateStudent();
            if (!$ok) {
                renderErrorMessage();
            }
            break;
    
        // case 'google':
        //     include_once PUBLIC_FILES . '/auth/google.php';
    
        //     $ok = authenticateWithGoogle();
        //     if (!$ok) {
        //         renderErrorMessage();
        //     }
        //     break;
    
        // case 'microsoft':
        //     include_once PUBLIC_FILES . '/auth/microsoft.php';
    
        //     $ok = authenticateWithMicrosoft();
        //     if (!$ok) {
        //         renderErrorMessage();
        //     }
        //     break;
    
        // case 'github':
        //     include_once PUBLIC_FILES . '/auth/github.php';
    
        //     $ok = authenticateWithGitHub();
        //     if (!$ok) {
        //         renderErrorMessage();
        //     }
        //     break;

        case 'local':
            include_once PUBLIC_FILES . '/auth/local.php';
    
            $ok = authenticateWithLocal();
            if (!$ok) {
                renderErrorMessage();
            }
            break;
    
        default:
            renderErrorMessage();
    }
} else {
    renderErrorMessage();
}

// If we get to this point, we have authenticated successfully. Redirect back to the appropriate page.
switch ($_SESSION['userAccessLevel']) {
    case 'User':
        $redirect = $configManager->getBaseUrl() . 'pages/user/dashboard.php';
        break;

    case 'Admin':
        $redirect = $configManager->getBaseUrl() . 'pages/user/dashboard.php';
        break;

    default:
        $redirect = $configManager->getBaseUrl() . 'pages/index.php';
}

unset($_SESSION['provider']);
echo "<script>window.location.replace('$redirect');</script>";
die();





/**
 * Displays the header and footer with an error message informing the user that they were not authenticated successfully
 *
 * @return void
 */
function renderErrorMessage() {
    global $configManager;

    $_SESSION['error'] = "Looks like we weren't able to successfully authenticate you using the method you chose. Please 
        try choosing another authentication.";

    echo "<script>window.location.replace('".$configManager->getBaseUrl()."/error.php');</script>";

    die();
}

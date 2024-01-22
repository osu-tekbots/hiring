<?php
/**
 * Error page for the hiring site. When redirecting to this page, you should set the `$_SESSION['error']`
 * variable to the message you want to display on this page. This provides one place for all errors to be redirected,
 * giving the user a better experience with the site when things go wrong.
 */
include_once '../bootstrap.php';

if(!isset($_SESSION)) {
    session_start();
}

$baseUrl = $configManager->getBaseUrl();
$adminEmail = $configManager->getAdminEmail();

// Get the error message. If there isn't one, redirect to the home page
if(isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
} else {
    $message = "It looks like you encountered an error on one of our pages.";
}

unset($_SESSION['error']);

$title = 'An Error Occurred';
include_once PUBLIC_FILES . '/modules/header.php';

echo "

<br><br><br>
<div class='container'>
    <div class='row'>
        <div class='col'>
            <h1>Whoops!</h1>
            <p style='white-space: initial'>$message</p>
            <p class='d-inline'><b>NOTE: </b>If you believe this site contains an error, please click the red button to submit an issue report.</p>
            <a href='$baseUrl' class='btn btn-primary float-right'>Home Page</a>
            <a href='mailto:$adminEmail' class='btn btn-outline-danger float-right mx-2'>Report to Admins</a>
        </div>
    </div>
</div>

";

include_once PUBLIC_FILES . '/modules/footer.php';
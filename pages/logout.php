<?php
include_once '../bootstrap.php';

if(!isset($_SESSION)) {
    session_start();
}

unset($_SESSION['site']);
unset($_SESSION['userID']);
unset($_SESSION['accessLevel']);
unset($_SESSION['newUser']);
session_unset();
session_destroy();

$redirect = $configManager->getBaseUrl();
echo "<script>window.location.replace('$redirect');</script>";
die();

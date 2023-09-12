<?php

/**
 * Redirects the user if the condition is not true
 *
 * @param boolean $condition    the condition to check
 * @param string  $failure      the URL to redirect to if the condition fails OR the error message to display
 * @param boolean $displayError if true, redirects to the error page and displays the $failure message
 * @return void
 */
function allowIf($condition, $failure = 'index.php', $displayError = false) {
    if(!$condition) {
        if($displayError) {
            if(!isset($_SESSION)) {
                session_start();
            }
            if($failure != 'index.php') {
                $_SESSION['error'] = $failure;
            }
            echo "<script>window.location.replace('error.php');</script>";
        } else {
            echo "<script>window.location.replace('$failure');</script>";
        }
        die();
    }
}
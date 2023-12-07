<?php
/**
 * Defines exception and error handlers to log all uncaught errors that occur while the site is in use.
 * This will help with debugging new features, as well as providing more details on what happened when 
 * end users report issues.
 */

function handleException(\Throwable $ex) {
    global $logger;

    if($logger == NULL)
        return;

    $logger->error("Uncaught exception: ".$ex->getCode().": ".$ex->getMessage()." on line ".$ex->getLine()." in ".$ex->getFile().
        "\n    Stack trace: ".$ex->getTraceAsString());
}

function handleError(int $errno, string $errstr, string $errfile, int $errline) {
    global $logger;

    if($logger == NULL)
        return;

    $logger->error("Uncaught error: $errno: $errstr on line $errline in $errfile");
}

// Defines an exception handler to catch and log any unhandled exceptions.
set_exception_handler('handleException');

// Defines an exception handler to catch and log any unhandled errors.
set_error_handler('handleError');
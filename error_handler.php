<?php
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $message = date("Y-m-d H:i:s") . " - Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($message, 3, "error.log");
    
    if (ini_get("display_errors")) {
        printf("<pre>Error: %s\nFile: %s\nLine: %d</pre>", $errstr, $errfile, $errline);
    } else {
        echo "An error occurred. Please try again later.";
    }
}

set_error_handler("customErrorHandler");
?>
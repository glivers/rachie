<?php

//define the 'E_FATAL' error type variable, set to all error types
define('E_FATAL',  E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | 
        E_COMPILE_ERROR | E_RECOVERABLE_ERROR);

//define the development environmrnt if true or false
define('ENV', 'dev');

//Set whether system should display errors
define('DISPLAY_ERRORS', TRUE);

//Define the type of errors to set for reporting
define('ERROR_REPORTING', E_ALL | E_STRICT);

//Define whether errors logs should be written
define('LOG_ERRORS', TRUE);

//set the shutdown function
register_shutdown_function('shut');

//set the error handler
set_error_handler('handler');

/**
 *This function catches unhandles errors
 *
 *@param null
 *@return void
 */
function shut(){

    //get record for the last error that led to this shutdown instance
    $error = error_get_last();

    //check if the error type is one of those defined in 'E_FATAL' constant
    if($error && ($error['type'] & E_FATAL)){
        handler($error['type'], $error['message'], $error['file'], $error['line']);
    }

}

/**
 *This function handles all catched errors
 *
 *@param int $errno The error number associated with this type of error
 *@param string $errMsg Error message assiciated with this error
 *@param string $errFile File name where this error occurred
 *@param int $errLine The file line where this error occurred
 *@return void
 */
function handler( $errNo, $errMsg, $errFile, $errLine ) {

    //set the paramter againsy which to test and get the error type
    switch ($errNo){

        case E_ERROR:
            // 1 //
            $errorType = 'E_ERROR'; 

            break;
        case E_WARNING: 
            // 2 //
            $errorType = 'E_WARNING'; 

            break;
        case E_PARSE:
            // 4 //
            $errorType = 'E_PARSE'; 

            break;
        case E_NOTICE: 
            // 8 //
            $errorType = 'E_NOTICE'; 

            break;
        case E_CORE_ERROR: 
            // 16 //
            $errorType = 'E_CORE_ERROR'; 

            break;
        case E_CORE_WARNING: 
            // 32 //
            $errorType = 'E_CORE_WARNING'; 

            break;
        case E_COMPILE_ERROR: 
            // 64 //
            $errorType = 'E_COMPILE_ERROR'; 

            break;
        case E_CORE_WARNING: 
            // 128 //
            $errorType = 'E_COMPILE_WARNING'; 

            break;
        case E_USER_ERROR: 
            // 256 //
            $errorType = 'E_USER_ERROR';

            break;
        case E_USER_WARNING: 
            // 512 //
            $errorType = 'E_USER_WARNING'; 

            break;
        case E_USER_NOTICE: 
            // 1024 //
            $errorType = 'E_USER_NOTICE'; 

            break;
        case E_STRICT: 
            // 2048 //
            $errorType = 'E_STRICT'; 

            break;
        case E_RECOVERABLE_ERROR: 
            // 4096 //
            $errorType = 'E_RECOVERABLE_ERROR'; 

            break;
        case E_DEPRECATED: 
            // 8192 //
            $errorType = 'E_DEPRECATED'; 

            break;
        case E_USER_DEPRECATED: 
            // 16384 //
            $errorType = 'E_USER_DEPRECATED'; 

            break;

    }

    //compose an error message to display
    $message = '<b>'.$errorType.': </b>'.$errMsg.' in <b>'.$errFile.'</b> on line <b>'.$errLine.'</b><br/>';

    //check if the error number return is also defined in the E_FATAL constant
    //if envionment is production, redirect to predifined error page
    if(($errNo & E_FATAL) && ENV === 'production'){

        header('Location: 500.html');
        header('Status: 500 Internal Server Error');

    }

    //if thi is not part of the erorrs to be reported, exit and return
    if(!($errNo & ERROR_REPORTING))
        return;

    //Display error message if development mode
    if(DISPLAY_ERRORS)
    {

        $error =<<<ERROR
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="description" content="">
                <meta name="author" content="">

                <title>Gliver MCV Application Launch Error </title>

            </head>
            <body>

            <style type="text/css">
                
                .container {

                    width : 960px;
                    min-height: 100px;
                    background-color: rgba(0,0,0,0.08);
                    font-size: 16px;
                    margin: auto;
                    color: rgba(0, 128, 0, 1);
                }

            </style>    

            <div class="container">
                
                <p>{$message}</p>

            </div>
            </body>
            </html>
ERROR;

    echo $error;

    }

    //Logg error message to file if logging has been set to true
    if(LOG_ERRORS)
        error_log(strip_tags($message), 0);

}

//ob_start();

//$tar = include 'start.php';

//ob_end_flush();
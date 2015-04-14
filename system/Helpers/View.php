<?php namespace Core\Helpers;

use Core\Helpers\Url;
use Core\Helpers\Path;

class View {

	protected static $variables = array();
    protected $_controller;
    protected $_action;
     
    function __construct($controller,$action) {
        $this->_controller = $controller;
        $this->_action = $action;
    }
 
    /** Set Variables **/
 
    public static function  set($name,$value) 
    {

        //self::variables[$name] = $value;
    }
 
    /** Display Template **/
     
   public static function  render($filePath, array $data = null) 
   {
        $viewFileArray = array();
        $viewFileArray = explode("/", $filePath);

        //compose the file full path
        $path = Path::app() . 'views' . DIRECTORY_SEPARATOR;


        //check if there multiple directories in the $file path
        if(count($viewFileArray) > 1)
        {
            //set the number of iterations
            $itr = count($viewFileArray);

            //loop though the array concatenating the file path
            for ($i=0; $i < $itr; $i++) 
            { 
                //if this is the last item, dont add directory separator, instead, add the .php extension
                if($i ==  ($itr - 1))
                {
                    $path .= $viewFileArray[$i] . '.php';

                }
                //append the directory separator at the end
                else
                {
                    $path .= $viewFileArray[$i] . DIRECTORY_SEPARATOR;

                }

            }

        }
        //no sub-directories, add the file name and extension
        else
        {
            $path .= $viewFileArray[0] . '.php';

        }

        //load this file into view
        if (file_exists($path)) 
        {
            include ($path);

        } 
        //throw throw the appropriate error message
        else 
        {
            //

        }
 
    }

}
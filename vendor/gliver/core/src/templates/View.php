<?php namespace Drivers\Templates;

/**
 *This class handles rendering of view files
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright Copyright (c) 2015 - 2020 Geoffrey Oliver
 *@link http://libraries.gliver.io
 *@category Core
 *@package Core\Helpers\View
 */

use Drivers\Registry\Registry;
use Helpers\Path\Path;
use Drivers\Templates\BaseTemplateClass;
use Drivers\Templates\TemplateException;

class View {

    /**
    *@var \Object The template parser class instance
    */
    private static $BaseTemplateObject = null;

    /**
    *@var array The variables to be injected into the view files
    */
    private static $variables = array();
     
    /**
     *This is the constructor class. We make this private to avoid creating instances of
     *this object
     *
     *@param null
     *@return void
     */
    private function __construct() {}

    /**
     *This method stops creation of a copy of this object by making it private
     *
     *@param null
     *@return void
     *
     */
    private function __clone(){}

    /**
    *This method returns the template parser object
    *
    *@param null
    *@return 
    */
    public static function getBaseTemplateObject()
    {
        //check if the BaseTemplateClass has been defined, if not create instance and return
        if( is_null(self::$BaseTemplateObject))
        {
            self::$BaseTemplateObject = new BaseTemplateClass();

            return self::$BaseTemplateObject;
        }

        else return self::$BaseTemplateObject;

    }

    /**
    *This method gets the header content for importing classes
    *
    *@param null
    *@return string The content of the header section
    */
    private static function getHeaderContent()
    {
        //get the content of the aliases array
        $class_aliases_namespaces = Registry::getConfig()['aliases'];

        //define array to contain numeric class_alias_namesplace
        $class_alias_array = array();

        //convert associative array to indexed array
        foreach($class_aliases_namespaces as $namespace => $alias)
        {
            //add elements to the class_alias_array array
            $class_alias_array[] = "use $namespace as $alias;";

        }

        //convert aliases array to string
        $class_alias_string = implode('', $class_alias_array);

        return sprintf('<?php %s function request_exec_time(){return microtime(true) - Registry::$gliver_app_start;}?>', $class_alias_string);

    } 

    /**
     * This method sets the view file variables to be injected in view file.
     * @param string $key The key with which to store the data
     * @param mixed $data The data in any format to inject into the view file
     * @return static class
     */
    public  static function with($key, $data)
    {
        //set the value of the $variables property
        self::$variables[] = array($key => $data);

        //return the static class
        return new static;

    }
 
    /**
     *This method parses the input variables and loads the specified views
     *
     *@param string $filePath the string that specifies the view file to load
     *@param array $data an array with variables to be passed to the view file
     *@return void This method does not return anything, it directly loads the view file
     *@throws 
     */     
   public static function  render($fileName, array $data = null) 
   {

        //set the value of the variables property, if data is provided
        if( $data !== null) self::$variables[] = $data;

        //this try block is excecuted to enable throwing and catching of errors as appropriate
        try {

            //loop through the $variables property setting the respective variable values
            foreach (self::$variables as $variable) 
            {
                //extact the variables
                foreach($variable as $key => $value){

                    $$key = $value;

                }

            }


            //get the parsed contents of the template file
            $contents = self::getHeaderContent() . self::getContents($fileName, false);

            //write contents to file
            $file_write_path  = Registry::getConfig()['root'] . '/bin/tmp/' . md5(Registry::getConfig()['title']);

            file_put_contents($file_write_path, $contents);

            if($load_view = include $file_write_path) unlink($file_write_path);

        }

        catch(HelperException $HelperExceptionObjectInstance) {

            //display the error message
            $HelperException->errorShow();

        }

    }

    /**
    *This method returns the parsed contents of a template view code in valid html.
    *@param string The filename whose contents to parse
    *@return string The string contents of the file
    */
    public static function get($fileName)
    {
        try{

            //get the parsed contents of the template file
            $contents = self::getContents($fileName, false);

            //return the contents
            return $contents;

        }

        catch(HelperException $HelperExceptionObjectInstance) {

            //display the error message
            $HelperException->errorShow();

        }

    }

    /**
     *This method converts the code into valid php code
     *
     *@param string $file The name of the view whose content is to be parsed
     *@param boolean true|false True if this is an embeded view into another view file
     *@return string $parsedContent The parsed content of the template file
     */
    public static function getContents($fileName, $embeded)
    {
        //compose the file full path
        $filePath = Path::view($fileName); 

        //get an instance of the view template class
        $template = self::getBaseTemplateObject();
        
        //get the compiled file contents
        $contents = $template->compiled($filePath, $embeded, $fileName);

        //return the compiled template file contents
        return $contents;

    }

    /**
    *This method returns result object as json object
    *@param array $data The data to send in the form of json object
    *@return json/header
    */
    public static function renderJson(array $data = null)
    {
        //set the json headers
        header('Content-Type: application/json');

        //echo out the array/object in json format
        echo json_encode($data);

    }

}
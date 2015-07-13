<?php namespace Helpers;

/**
 *This class handles rendering of view files
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright Copyright (c) 2015 - 2020 Geoffrey Oliver
 *@link http://libraries.gliver.io
 *@category Core
 *@package Core\Helpers\View
 */

use Drivers\Templates\Implementation;
use Exceptions\BaseException;
use Drivers\Cache\CacheBase;
use Drivers\Registry;

class View {

     
    public function __construct() {}
 
    /**
     *This method parses the input variables and loads the specified views
     *
     *This method gets the string $filePath and splits it into array separated by
     *the '/'. It then composes a directory path with these fragments and loads the
     *view file
     *
     *@param string $filePath the string that specifies the view file to load
     *@param array $data an array with variables to be passed to the view file
     *@return void This method does not return anything, it directly loads the view file
     *@throws 
     */     
   public static function  render($filePath, array $data = null) 
   {
        //this try block is excecuted to enable throwing and catching of errors as appropriate
        try {

            //get the variables passed and make them available to the view
            if ( $data != null)
            {
                //loop through the array setting the respective variables
                foreach ($data as $key => $value) 
                {
                    $$key = $value;

                }

            }

            //compose the file full path
            $path = Path::view($filePath);

            //get an instance of the view template class
            $template = Registry::get('template');
            
            //get the compiled file contents
            $contents = $template->compiled($path);

            //start the output buffer
            ob_start();

            //evaluate the contents of this view file
            eval("?>" . $contents . "<?");

            //get the evaluated contents
            $contents = ob_get_contents();

            //clean the output buffer
            ob_end_clean();

            //return the evaluated contents
            echo $contents;

            //stop further script execution
            exit();
   
        }

        catch(BaseException $e) {

            //echo $e->getMessage();
            $e->show();

        }

        catch(Exception $e) {

          echo $e->getMessage();
          
        }

    }

}
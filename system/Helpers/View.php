<?php namespace Core\Helpers;

/**
 *This class handles rendering of view files
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright Copyright (c) 2015 - 2020 Geoffrey Oliver
 *@link http://libraries.gliver.io
 *@category Core
 *@package Core\Helpers\View
 */

use Core\Drivers\Templates\Implementation;
use Core\Exceptions\FileErrorException;

class View {

     
    public function __construct() 
    {
        //    

    }
 
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

            $viewFileArray = array();
            $viewFileArray = explode("/", $filePath);

            //compose the file full path
            $path = Path::app() . 'views' . DIRECTORY_SEPARATOR;


            //check if there multiple directories in the $file path
            if(sizeof($viewFileArray) > 1)
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

                ob_start(); // Start output buffering

               $source = file_get_contents($path);

               /*
               $template = new Implementation();

               $template->parse($source, $template);

               $template->process(array(
                                "name" =>  "Chris",
                                "address" =>  "Planet Earth!",
                                "stack" =>  array(
                                    "one" =>  array(1, 2, 3),
                                    "two" =>  array(4, 5, 6)
                                ),
                                "empty" =>  array()
                        )
                );

                */
                
                eval("?>" . $source . "<?");

                $contents = ob_get_contents(); // Get the contents of the buffer
                ob_end_clean(); // End buffering and discard
                echo $contents; // Return the contents
 

            } 
            //throw throw the appropriate error message
            else 
            {
                //throw FileErrorExcepton
                throw new FileErrorException($filePath);

            }
     
        }

        catch(FileErrorException $e) {

            //echo $e->getMessage();
            $e->show();

        }

        catch(Exception $e) {

          echo $e->getMessage();
          
        }

    }

}
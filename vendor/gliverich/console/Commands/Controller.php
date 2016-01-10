<?php namespace Gliverich\Console\Commands;

/**
 *This class defines command options that are available for controller command
 *
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Gliverich
 *@package Gliverich\Console\Command\Controller
 *@link https://github.com/gliver-mvc/console
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Drivers\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;

class Controller extends Command
{
    protected function configure()
    { 
        $this
            ->setName('controller')
            ->setDescription('Create controllers class and method templates, with associated views and models.')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'What is the name of the controller class that you would like to create or modify?'
            )
            ->addArgument(
                    'methods',
                    InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                    'What methods would you like to create  for this controller class (separate multiple method names with a space)?'
                )
            ->addOption(
               'init',
               null,
               InputOption::VALUE_NONE,
               'Specify this option when you are creating a controller class for the first time.'
            )
            ->addOption(
               'append',
               null,
               InputOption::VALUE_NONE,
               'Specify this option when you would like to modify a controller class that already exists.'
            )
            ->addOption(
               'complete',
               null,
               InputOption::VALUE_NONE,
               'Specify this option if you want to create a controller class together with associated model and views all in once command.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        //define array to contain all the controllers names
        $controllers = array();

        //get the controllers directory
        $controllerDirName = Registry::getConfig()['root'] . "/application/controllers";

        //create an instance of the Finder() class
        $finder = new Finder();

        //create an instance of the FileSystem Class
        $FileSystem = new Filesystem();

        //look for all files in this directory that comply with the rules defined
        $finder->files()->in($controllerDirName)->name('*.php')->contains('extends')->notContains('use Drivers\Controllers\Implementation');

        //loop through the result set getting the class names one at a time
        foreach ($finder as $file) {

            //add controller name to the controller array
            $controllers[substr($file->getRelativePathname(), 0, -14)] = array(

                'RealPath' => $file->getRealpath(),
                'ResultObject' => $file

            );

        }




        //check if a controller name was specified
        if( $name = $input->getArgument('name') ){

            //check if this is a create request
            if ($input->getOption('init')) {

                //check if this class already exists
                if( isset( $controllers[$name]) ){

                    //composer to send to output
                    $output->writeln('A Controller with this name already exists! Do you want to overwrite it?');

                }

                //check if a file with a similar name already exists
                elseif(   $FileSystem->exists( $controllerDirName . "/" . $name . "Controller.php") ){

                    //composer to send to output
                    $output->writeln('A File with similar name already exists! Do you want to overwrite it?');

                }

                //this is a completely new controller, go ahead and get parameters and create controller class
                else{


                }

            }

            //this is an append request
            elseif ( $input->getOption('append') ) {

                //first, begin by checking if this controller exists
                if( isset( $controllers[$name]) ){


                    //check if the methods to be appended were provided
                    if( $methods = $input->getArgument('methods') ){

                        //compose method template code
                        $methodTemplateCode = "\n\t/**\n\t*Describe what this method does here...\n\t*\n\t*@param dataType \$varName Description\n\t*@return dataType Description\n\t*/\n\tpublic function ";
                        //define variable to contain appended method contents
                        $methodAppendString = '';

                        //loop through the methods composing the method content
                        foreach($methods as $methodName ){

                            //compose the method append string
                            $methodAppendString .= $methodTemplateCode . 'get' . ucfirst(strtolower($methodName)) . "() {\n\t\n\t\t#...Your code goes in here\n\n\t}\n" . 
                                                    $methodTemplateCode . 'post' . ucfirst(strtolower($methodName) ). "() {\n\t\n\t\t#...Your code goes in here\n\n\t}\n\n}";

                        } 

                        //get the result object for this controller from the controllers array
                        $ResultObject = $controllers[$name]['ResultObject'];

                        //get the contents of this file
                        $FileContents = $ResultObject->getContents();

                        //file the position of the last closing curly brace in the file contents
                        $pos = strrpos($FileContents, '}');

                        //check if closing tag was found
                        if($pos !== false){

                            //append the new file contents
                            $FileContents = substr_replace($FileContents, $methodAppendString, $pos, strlen('}'));

                            //append new file contents
                            //$FileContents .= $methodAppendString; 

                            //enclose this action in a try catch block to be able to handle errors
                            try {

                                //append contents to controller in style...
                                $FileSystem->dumpFile( $controllers[$name]['RealPath'], $FileContents);

                                //send this to the output
                                $output->writeln("Roger! Appending new methods to class successful!");

                            } 
                            catch (IOExceptionInterface $e) {

                                //send this to the output
                                $output->writeln("An error occured while appending methods to your controller class!");

                            }


                        }

                        //the last closing brace was not found, seems like this file has syntax errors
                        else{

                            //write to the output with error message
                            $output->writeln('Seems like your controller class $name has syntax errors! Fix and try again.');

                        }

                    }

                    //there were not methods provided to be appended, write error message to output
                    else{

                        //send out put with missign methods message to the console
                        $output->writeln("You did not provide the methods to append to this $name Controller class!");

                    }


                }

                //this controller is not defined yet
                else{

                    //compose output string
                    $outputString = "You are trying to modify a controller named '$name' which is undefined!";

                    //send to output stream
                    $output->writeln($outputString);

                }

            }

            //if none of the options were specified, list all the methods in this controller class
            else{

                //first, begin by checking if this controller exists
                if( isset( $controllers[$name]) ){



                }

                //this controller is not defined yet
                else{

                    //compose output string
                    $outputString = "Controller '$name'   is undefined!";

                    //send to output stream
                    $output->writeln($outputString);

                }

            }

        }

        //controller name not specified, just list all the controllers and their respective methods currently defined in this application
        else{

            //define string to contain controller names
            $controllerNames = array_keys($controllers);

            //convert the key names to a string
            $controllerNamesString = join("\n", $controllerNames);

            //output string
            $outputString = "Controller Class(es) [" . count($controllers) . "] \n" . $controllerNamesString;
            
            //send to output stream
            $output->writeln($outputString);

        }

    }

}
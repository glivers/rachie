<?php namespace Helpers\Form;

/**
 *This class handles Form related functionlity
 *
 *@author M.Mudassar Qureshi <mudassar66@gmail.com>
 *@copyright Copyright (c) 2015 - 2020 M.Mudassar Qureshi
 *@link http://libraries.gliver.io
 *@category Helper
 *@package Core\Helpers\Form
 */
use Exceptions\BaseException;
use Helpers\Exceptions\HelperException;

class Form {
	
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
         * Ths method create form open tag with attributes in view. 
         * 
         * @param array $form_attr   Form attributes e.g. $form_attr = array('name'=>'form1','action'=>'post')
         * 
         */

	public static function open($form_attr=array()) 
        {
            //this try block is excecuted to enable throwing and catching of errors as appropriate
            try {
                
                     //this block throwing exception if method argument is not an array
                    if(! is_array($form_attr)){
                        throw new HelperException(sprintf('Expecting array of form attributes.e.g.$form_attr = array(\'name\'=>\'form1\',\'action\'=>\'post\')',$form_attr));
                    }
                    $form_attr_str='';
                    foreach($form_attr as $key=>$val){
                        $form_attr_str .= $key.'="'.$val.'" ';
                    }
                    //$form_attr_str = implode('= ',$form_attr);
                    return "<form ".$form_attr_str.' >';
            }

            catch(BaseException $e) {

                //echo $e->getMessage();
                $e->show();

            }

            catch(Exception $e) {

              echo $e->getMessage();

            }

        }
        
        /**
         * Ths method create form close tag in view. 
         * 
         * @param null
         * * 
         */

	public static function close()
        {
            //this try block is excecuted to enable throwing and catching of errors as appropriate
            try {
                     
                return '</form>';
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
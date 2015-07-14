<?php 

/**
 *This file contains all the defined routes for this application.
 *Routes as not mandatory, but in order to make custom url names different from your controller names, you can de
 *define a custom route. To define a route, follow the example below. Define the route name as index and the 
 *controller name as value.
 */
return array(

/**
 *The home route.
 *This route loads the home controller
 *@param null
 *@return void
 */
'homepage' => 'Home@index',
/**
 *This routes loads the search users page.
 *@param null
 *@return void
 */
'work' => 'Home@index'

);
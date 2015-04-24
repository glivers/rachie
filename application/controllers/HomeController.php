<?php namespace Controllers;

use Core\Helpers\View;

class HomeController extends Controller {

    public function index()
    {
    	//echo $this->sayHallo();
        
        View::render('index', array('title' => 'This is the homepage'));

    }

}
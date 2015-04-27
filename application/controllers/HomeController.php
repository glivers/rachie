<?php namespace Controllers;

use Core\Helpers\View;
use Core\Helpers\Url;

class HomeController extends Controller {

    public function index()
    {
        
        View::render('sample', array('title' => 'This is the homepage'));

    }

}
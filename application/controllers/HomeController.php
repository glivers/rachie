<?php namespace Controllers;

use Core\Helpers\View;

class HomeController extends Controller {

    public function index()
    {
        //load the homepage
        View::render('index');

    }

}
<?php namespace Controllers;

use Core\Helpers\View;

class HomeController extends Controller {

    public function index()
    {

        View::render('index', array('title' => 'This is the homepage'));

    }

}
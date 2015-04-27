<?php namespace Controllers;

use Core\Helpers\View; 

class DostoController extends Controller {

	public function index()
	{

        View::render('dosto/home', array('title' => 'Dosto Homepage'));
        
	}
}
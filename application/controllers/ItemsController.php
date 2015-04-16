<?php namespace Controllers;

use Core\Helpers\View;


class ItemsController extends Controller {

	function view($id = null,$name = null) 
	{
     
        $this->set('title',$name.' - My Todo List App');
        $this->set('todo',$this->Item->select($id));
 
    }
     
    function viewall() 
    {
 
        //$this->set('title','All Items - My Todo List App');
        //$this->set('todo',$this->Item->selectAll());
        View::render('items/viewalll', array('title' => 'This is the homepage'));
    }
     
    function add() 
    {
        $todo = $_POST['todo'];
        $this->set('title','Success - My Todo List App');
        $this->set('todo',$this->Item->query('insert into items (item_name) values (\''.mysql_real_escape_string($todo).'\')'));  

    }
     
    function delete($id = null) 
    {
        $this->set('title','Success - My Todo List App');
        $this->set('todo',$this->Item->query('delete from items where id = \''.mysql_real_escape_string($id).'\''));  

    }
 
}
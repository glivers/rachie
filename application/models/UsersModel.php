<?php namespace Models;

/**
 * 
 */
class UsersModel extends Model{
	
	public static function store()
	{
		echo "We are here. Stop!";

	}	

	public static function testmethod()
	{
		$data = array(
			'name' => 'BadGirl',
			'email' => 'Birdgal@example.com',
			'datecreated' => '2121212121'
			);

		$results  = static::Query()->from('model')->save($data);

	}

}
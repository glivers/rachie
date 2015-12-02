<?php namespace Exceptions\Debug;

interface BaseExceptionInterface {

	/**
	 *This method displays the error message passed from the thrown error
	 *
	 * @param null
	 * @return void
	 * @throws This method does not throw an error
	 *
	 */
	public function errorShow();
	
}
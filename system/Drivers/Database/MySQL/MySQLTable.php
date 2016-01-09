<?php namespace Drivers\Database\MySQL;

/**
 *This class parses doc blocks into valid table columns.
 *@author Geoffrey Oliver <geoffrey.oliver2@gmail.com>
 *@copyright 2015 - 2020 Geoffrey Oliver
 *@category Drivers\Database
 *@package Drivers\Database\MySQL\MySQLTable
 *@link https://github.com/gliver-mvc/gliver
 *@license http://opensource.org/licenses/MIT MIT License
 *@version 1.0.1
 */

use Helpers\Path\Path;
use Helpers\File\FileInfo;
use Helpers\File\FileHandler;
use Drivers\Database\MySQL\MySQLResponseObject;
use Drivers\Database\MySQL\MySQLException;

class MySQLTable {

	/**
	 * @var object Resource Instance of the mysqli database object
	 */
	protected $connection;

	/**
	 * @var string Database table name
	 */
	protected $table;

	/**
	 * @var string The path to the model table file
	 */
	protected $table_file_path;

	/**
	 * @var string The name of the directory for model table file
	 */
	protected $table_dir;

	/**
	 * @var string Name of model class calling this table class
	 */
	protected $model;

	/**
	 * @var string The query string for creating table
	 */
	protected $query_string;

	/**
	 * @var string The table engine
	 */
	protected $engine;

	/**
	 * @var string The database character set
	 */
	protected $charset;

	/**
	 * @var array Of the datatype
	 */
	protected $data_types = array(
		'autonumber',
		'text',
		'integer',
		'decimal',
		'boolean',
		'datetime'
	);

	/**
	 * @var array The table columns in array format
	 */
	protected $columns = array();

	/**
	 * @var array The primary key fields
	 */
	protected $primaries = array();

	/**
	 * This method sets the default dependnacies for this class.
	 * @param string $table_name The name of the table to be created
	 * @param string $model_name The name of the model class calling this instance
	 * @param resource $service The mysqli connector
	 * @return object $this
	 */
	public function __construct($table_name, $model_name, MySQL $service)
	{
		$this->table 		= $table_name;
		$this->model 		= $model_name;
		$this->connection 	= $service;
		$this->table_dir 	= Path::app()."database/";
		$this->table_file_path 	= $this->table_dir.str_replace('\\', '/', $this->model)."Table.php";

		return $this;

	}

	/**
	*This method creates a new table associated with this model.
	*@param string $table_name The name of the table associated with this class
	*@param string $model_name The name of the model class associated with this table
	*@return bool true if table create success
	*/
	public function createTable()
	{

		//get the instance of the FileInfo class
		$file_info = FileInfo::Create($this->table_file_path);

		//this file already exists
		if($file_info->isFile())
		{
			//load the clas into momeory
			include $file_info->getPathname();

			$class_name = substr($file_info->getFilename(), 0,-4);

			//get the database table class
			$table_object = new $class_name();

			//get the table columns
			$this->getColumns($table_object)->createSQL()->execute();

			//echo "<pre>";print_r($table_object);


		}

		//this file has not been created yet
		else
		{


		}
		//create instance of the FileHandler
		$file_handle = FileHandler::Create($this->table_file_path);

	}

	/**
	*This model updates a table structure in the database.
	*@param null
	*@return bool true if update structure success
	*/
	public function updateTable()
	{
		//call the method to update table structure in the database
		return static::Query()->updateTable(static::$table, __CLASS__);


	}

	/**
	 *This method returns the columns as defined with all their metadata.
	 *@param object $table_column The instance of table class
	 *@return $this
	 */
	public function getColumns($table_object)
	{
		try {

			//get new inspector class instance
			$inspector = new \ReflectionClass($table_object);

			//get all the class properties for this class
			$properties = $inspector->getProperties();

			//loop through each property in properties array performing the associated function
			foreach ($properties as $property ) 
			{
				$comment_string = $property->getdoccomment(); 
				$column_name =  $property->getName();
				$comment_array = $this->parseComment($comment_string);

				if ( array_key_exists("@column", $comment_array) )
				{
					//compose column property
					$this->columns[$column_name]['primary'] = (array_key_exists("@primary", $comment_array)) ? true : null;
					$this->columns[$column_name]['type'] 	= (isset($comment_array["@type"])) ? $comment_array["@type"] : null;
					$this->columns[$column_name]['length'] 	= (isset($comment_array["@length"])) ? $comment_array["@length"] : null;
					$this->columns[$column_name]['index'] 	= (array_key_exists("@index", $comment_array)) ? true : null;

					if ( ! in_array($this->columns[$column_name]['type'], $this->data_types))	throw new MySQLException(get_class(new MySQLException)." {$this->columns[$column_name]['type']} is not a valid table field data type");

					if ($this->columns[$column_name]['primary'] !== null) $this->primaries[$column_name] = array();

				}
					
			}

			if (count($this->primaries) !== 1)	throw new MySQLException(get_class(new MySQLException)." {$this->table} table must have exactly one @primary column");

			return $this;
		
		} catch (MySQLException $e) {

			$e->errorShow();
			
		}

	}

	/**
	 *This method will parse the comment string return into an array of key/value pairs.
	 *@param string $comment The comment string to be parsed
	 *@return array Associative array if comment substrings
	 */
	protected function parseComment($comment_string)
	{	

		//define the regular expression pattern to use for string matching
		$pattern = "#(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_]*)#";

		//perform the regular expression on the string provided
		preg_match_all($pattern, $comment_string, $matches, PREG_PATTERN_ORDER);


		//call String utility method to fetch for string matches
		$matches = $matches[0];

		//process return data if matches were found
		if( $matches != null )
		{
			$output = array();
			//loop through each substring cleaning and triming
			foreach ($matches as $match) 
			{
				$parts_array = explode(' ', $match);
				
				//loop throught the input array removing empty elements and return resultant array
				$parts_array = array_values(array_filter($parts_array, function($item){

						return ! empty($item);

				}));

				//loop the through the array elements removing the whitespaces
				$parts_array = array_map(function($item){

					return trim($item);

				}, $parts_array);


				//assign the value to $metaArray
				$output[$parts_array[0]] = (isset($parts_array[1])) ? $parts_array[1] : null;

			}

			return $output;
		}

		else return array();

	}

	/**
	*This method generates query string for creating the database table.
	*@param null
	*@return $this
	*/
	public function createSQL()
	{
		try {

			$lines = array();

			$indices = array();

			$columns = $this->columns;

			$template = "CREATE TABLE %s (\n%s,\n%s\n) ENGINE=%s DEFAULT CHARSET=%s;";

			foreach ($this->columns as $column_name => $column)
			{

				//$column_name = $column["name"];//set the column name

				$data_type = $column["type"];//set the column data type

				$length = $column["length"];//set the lenght of the data type

				if ($column["primary"])	$indices[] = "PRIMARY KEY ({$column_name})"; //add primary key field

				if ($column["index"]) $indices[] = "KEY {$column_name} ({$column_name})"; //add index field

				switch ($data_type)
				{
					case "autonumber":

						$lines[] = "{$column_name} INT(11) NOT NULL AUTO_INCREMENT";

						break;

					case "text":

						if ($length !== null && $length <= 255)	$lines[] = "{$column_name} VARCHAR({$length}) DEFAULT NULL";

						else $lines[] = "{$column_name} text";

						break;

					case "integer":

						$lines[] = "{$column_name} INT(11) DEFAULT NULL";

						break;

					case "decimal":

						$lines[] = "{$column_name} FLOAT DEFAULT NULL";

						break;

					case "boolean":

						$lines[] = "{$column_name} TINYINT(4) DEFAULT NULL";

						break;

					case "datetime":

						$lines[] = "{$column_name} DATETIME DEFAULT NULL";

						break;

				}

			}

			$this->query_string = sprintf($template, $this->table, join(",\n", $lines), join(",\n", $indices), 'InnoDB', 'utf8');//$this->connection->character_set_name()
			
			return $this;

		} catch (MySQLException $e) {

			$e->errorShow();

		}
		
	}

	/**
	*This method executes the query string created.
	*@param null
	*@return void
	*/
	public function execute()
	{
		
		try {

			$result = $this->connection->execute("DROP TABLE IF EXISTS {$this->table};");

			if ($result === false)	throw new MySQLException(get_class(new MySQLException)." There was an error in the query: {$this->connection->lastError()}");

			$result = $this->connection->execute($this->query_string);

			if ($result === false) throw new MySQLException(get_class(new MySQLException)." There was an error in the query: {$this->connection->lastError()} <span class=\"query-string\">".$this->query_string."</span>");

				
		}
		 catch (MySQLException $e) {
			
			$e->errorShow();	
		}	
	}

}

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
	 * @var array Whether the table is to be renamed or dropped
	 */
	protected $update_table = array();


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

			//get the table columns, create SQL and execute
			return $this->getColumns($table_object)->createSQL()->executeCreate();

		}

		//this file has not been created yet
		else
		{


		}

	}

	/**
	*This model updates a table structure in the database.
	*@param null
	*@return bool true if update structure success
	*/
	public function updateTable()
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

			//get the table columns, create SQL and execute
			$this->getColumns($table_object)->updateSQL()->executeUpdate();

			//echo "{$this->query_string}";exit(0);

		}

		//this file has not been created yet
		else
		{


		}

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

				//get meta data for table property
				if($column_name == 'table')
				{
					$class_name = $property->getDeclaringClass()->getName();
					$this->update_table['rename'] 	= (array_key_exists("@rename", $comment_array)) ? $property->getValue(new $class_name()) : null;
					$this->update_table['drop'] 	= (array_key_exists("@drop", $comment_array)) ? true : null;

				}

				elseif ( array_key_exists("@column", $comment_array) )
				{
					//compose column property
					$this->columns[$column_name]['primary'] = (array_key_exists("@primary", $comment_array)) ? true : null;
					$this->columns[$column_name]['type'] 	= (isset($comment_array["@type"])) ? $comment_array["@type"] : null;
					$this->columns[$column_name]['length'] 	= (isset($comment_array["@length"])) ? $comment_array["@length"] : null;
					$this->columns[$column_name]['index'] 	= (array_key_exists("@index", $comment_array)) ? true : null;

					if(array_key_exists("@add", $comment_array)){

						$this->columns[$column_name]['add'] = ($comment_array["@add"]) ? $comment_array["@add"] : 'id';

					}

					else $this->columns[$column_name]['add'] = null;

					$this->columns[$column_name]['rename'] 	= (array_key_exists("@rename", $comment_array)) ? $comment_array["@rename"] : null;
					$this->columns[$column_name]['update'] 	= (array_key_exists("@update", $comment_array)) ? true : null;
					$this->columns[$column_name]['drop'] 	= (array_key_exists("@drop", $comment_array)) ? $column_name : null;

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
	*This method creates sql string for update queries.
	*@param null
	*@return $this
	*/
	public function updateSQL()
	{
		try{

			//check if table update values are set
			if($this->update_table['drop'] || $this->update_table['rename'])
			{
				//compose query string
				$this->query_string = ($this->update_table['drop']) ? "DROP TABLE IF EXISTS {$this->table};" : "ALTER TABLE {$this->table} RENAME TO {$this->update_table['rename']};";

				return $this;

			}

			//do column updates
			else
			{
				$lines = array();
				$template = "ALTER TABLE %s\n %s;";
				//loop through the columns available
				foreach($this->columns as $column_name => $column)
				{
					//start with less trivial goin upward ADD COLUMN complete DECIMAL(2,1) NULL AFTER description;
					if($column['add'])
					{
						$data_type = $column["type"];//set the column data type

						$length = $column["length"];//set the lenght of the data type

						$primary = ($column["primary"])	? "PRIMARY KEY " : null;//({$column_name})"; //add primary key field

						$index = ($column["index"]) ? "KEY" : null;// {$column_name} ({$column_name})"; //add index field

						switch ($data_type)
						{
							case "autonumber":

								$lines[] = "ADD COLUMN {$column_name} INT(11) NOT NULL AUTO_INCREMENT $primary AFTER {$column['add']}\n";

								break;

							case "text":

								if ($length !== null && $length <= 255)	$lines[] = "ADD COLUMN {$column_name} VARCHAR({$length}) DEFAULT NULL AFTER {$column['add']}\n";

								else $lines[] = "ADD COLUMN {$column_name} text DEFAULT NULL  AFTER {$column['add']}\n";

								break;

							case "integer":

								$lines[] = "ADD COLUMN {$column_name} INT(11) DEFAULT NULL AFTER {$column['add']}\n";

								break;

							case "decimal":

								$lines[] = "ADD COLUMN {$column_name} FLOAT DEFAULT NULL AFTER {$column['add']}\n";

								break;

							case "boolean":

								$lines[] = "ADD COLUMN {$column_name} TINYINT(4) DEFAULT NULL AFTER {$column['add']}\n";

								break;

							case "datetime":

								$lines[] = "ADD COLUMN {$column_name} DATETIME DEFAULT NULL AFTER {$column['add']}\n";

								break;

						}

					}

					elseif($column['update'])
					{

						$data_type = $column["type"];//set the column data type

						$length = $column["length"];//set the lenght of the data type

						$primary = ($column["primary"])	? "PRIMARY KEY " : null;//({$column_name})"; //add primary key field

						$index = ($column["index"]) ? "KEY" : null;// {$column_name} ({$column_name})"; //add index field

						switch ($data_type) //CHANGE COLUMN task_id task_id INT(11) NOT NULL AUTO_INCREMENT;
						{
							case "autonumber":

								$lines[] = "CHANGE COLUMN {$column_name} {$column_name} INT(11) NOT NULL AUTO_INCREMENT $primary\n";

								break;

							case "text":

								if ($length !== null && $length <= 255)	$lines[] = "ADD COLUMN {$column_name} VARCHAR({$length}) DEFAULT NULL\n";

								else $lines[] = "CHANGE COLUMN {$column_name} {$column_name} text DEFAULT NULL\n";

								break;

							case "integer":

								$lines[] = "CHANGE COLUMN {$column_name} {$column_name} INT(11) DEFAULT NULL\n";

								break;

							case "decimal":

								$lines[] = "CHANGE COLUMN {$column_name} {$column_name} FLOAT DEFAULT NULL\n";

								break;

							case "boolean":

								$lines[] = "CHANGE COLUMN {$column_name} {$column_name} TINYINT(4) DEFAULT NULL\n";

								break;

							case "datetime":

								$lines[] = "CHANGE COLUMN {$column_name} {$column_name} DATETIME DEFAULT NULL\n";

								break;

						}
	
					}

					elseif($column['rename'])
					{
						//$lines[] = "CHANGE COLUMN `{$column_name}` `{$column['rename']}`\n";
					}

					elseif($column['drop'])
					{
						$lines[] = "DROP COLUMN {$column['drop']}\n";


					}

				}

				$this->query_string = sprintf($template, $this->table, join(',',$lines));

				return $this;

			}

		}

		catch (MySLQException $err){

			$err->errorShow();

		}

	}

	/**
	*This method executes the query string created.
	*@param null
	*@return void
	*/
	public function executeCreate()
	{
		
		try {

			$result = $this->connection->execute("DROP TABLE IF EXISTS {$this->table};");

			if ($result === false)	throw new MySQLException(get_class(new MySQLException)." There was an error in the query: {$this->connection->lastError()}");

			$result = $this->connection->execute($this->query_string);

			if ($result === false) throw new MySQLException(get_class(new MySQLException)." There was an error in the query: {$this->connection->lastError()} <span class=\"query-string\">".$this->query_string."</span>");

			return true;
				
		}
		 catch (MySQLException $e) {
			
			$e->errorShow();	
		}	
	}	

	/**
	*This method executes the query string created.
	*@param null
	*@return void
	*/
	public function executeUpdate()
	{
		
		try {

			$result = $this->connection->execute($this->query_string);

			if ($result === false) throw new MySQLException(get_class(new MySQLException)." There was an error in the query: {$this->connection->lastError()} <span class=\"query-string\">".$this->query_string."</span>");

			return true;
				
		}
		 catch (MySQLException $e) {
			
			$e->errorShow();	
		}	
	}

}

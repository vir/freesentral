<?
/**
* the base classes for the database framework
*/

require_once("config.php");

// class for defining a variable that will be mapped to a column in a sql table
// name of variable must not be a numer or a numeric string
class Variable
{
	public $_type;
	public $_value;
	public $_key;
	public $_owner;
	public $_critical;
	public $_matchkey;
	/**
	 * Constructor for Variable class. Name of variable must not be a string
	 * @param $type Text representing the type of object: serial, text, int2, bool, interval etc
	 * @param $def_value Text or number representing the default value
	 * @param $foreign_key Name of the table this column is a foreign key to. Unless $match_key is defined, this is a foreign
	 * key to a column with the same name in the $foreign_key table
	 * @param $critical Bool value. 
	 * true for ON DELETE CASCADE, if referenced row is deleted then this one will also be deleted
	 * false for ON DELETE SET NULL, if referenced is deleted then this column will be set to null
	 * @param $match_key Referenced variable name (Text representing the name of the column from the $foreign_key table to which this variable(column) will
	 * @param $join_type Usually setted when extending objects. Possible values: LEFT, RIGHT, FULL, INNER. Default is LEFT is queries.
	 * be a foreign key to).If not given the name is the same as this variable's
	 */
	function __construct($type, $def_value = NULL, $foreign_key = NULL, $critical = false, $match_key = NULL, $join_type = NULL)
	{
		$this->_type = $type;
		$this->_value = $def_value;
		$this->_key = $foreign_key;
		$this->_critical = $critical;
		$this->_owner = null;
		$this->_matchkey = $match_key;
		$this->_join_type = $join_type;
	}

	/**
	 * Returns the correctly formated value of a Variable object. Value will be used inside a query. Formating is done 
	 * according to the type of the object
	 * @param $value Instance of Variable class
	 * @return Text or number to be used inside a query
	 */
	public function escape($value)
	{
		if (!strlen($value) && $this->_type != "bool")
			return 'NULL';

		$value = trim($value);
		switch ($this->_type)
		{
			case "bool":
				if($value === true || $value == 't')
					return "'t'";
				else
					return "'f'";
				break;
			case "int":
			case "int2":
			case "int4":
			case "float4":
			case "float8":
			case "serial":
			case "bigserial":
				$value = str_replace(',','',$value);
				return 1*$value;
				break;
			case "interval":
				if($value) {
					return "interval '".$value." s'";
					break;
				}
			default:
				return "'" . addslashes($value) . "'";
		}
	}
}

// class that does the operations with the database 
class Database
{
	protected static $_connection = true;

	/**
	 * Make database connection
	 * @return The connection to the database. If the connection is not possible, page dies
	 */
	public static function connect()
	{
		global $db_host,$db_user,$db_database,$db_passwd;
		if (self::$_connection === true)
			self::$_connection = pg_connect("host='$db_host' dbname='$db_database' user='$db_user' password='$db_passwd'") or die("Could not connect to the database");
		return self::$_connection;
	}

	/**
	 * Start transaction
	 */
	public static function transaction()
	{
		return Database::query("BEGIN WORK");
	}

	/**
	 * Perform roolback on the current transaction
	 */
	public static function rollback()
	{
		return Database::query("ROLLBACK");
	}

	/**
	 * Commit current tranction
	 */
	public static function commit()
	{
		return Database::query("COMMIT");
	}


	/**
	 * Perform query.If query fails, unless query is a ROLLBACK, it is supposed that the database structure changed. Try to 
	 * modify database structure, then perform query again using the  queryRaw() method
	 * @param $query Text representing the query to perform
	 * @return Result received after the performing of the query 
	 */
	public static function query($query)
	{
		if (!self::connect())
			return false;

		if(isset($_SESSION["debug_all"]))
			print "<br/>\n<br/>\nquery :'.$query.'<br/>\n<br/>\n";

		if (function_exists("pg_result_error_field"))
		{
			// happy, happy, joy, joy!
			/*if (!pg_connection_busy(self::$_connection)) 
			{
				$ok = pg_send_query(self::$_connection,$query);
			}
			$res = pg_get_result(self::$_connection);
			
			if ($ok)*/
			$res = pg_query(self::$_connection,$query);
			if($res && $query != "ROLLBACK") //if query is a ROLLBACK then we got an error somewhere
				return $res;
			else
			{
				if(!Model::modified())
				{
					Model::updateAll();
					return self::queryRaw($query);
				}
				else
					return $res;
			}
		}else{
			// we'll do our best which is not much...
			$res = pg_query(self::$_connection,$query);
			if ($res  && $query != "ROLLBACK")
				return $res;
			else
			{
				if(!Model::modified())
				{
					Model::updateAll();
					return self::queryRaw($query);
				}
				else
					return $res;
			}
		}
	}

	/**
	 * Perform query without verifying if it failed or not
	 * @param $query Text representing the query to perform
	 * @return Result received after the performinng of the query 
	 */
	public static function queryRaw($query)
	{
		if (!self::connect())
			return false;
		if(isset($_SESSION["debug_all"]))
			print "queryRaw: $query\n<br/>\n<br/>\n";
		return pg_query(self::$_connection,$query);
	}

	/**
	 * Create corresponding sql table for this object
	 * @param $table Name of the table
	 * @param $vars Array of variables for this object
	 * @return Result returned by performing the query to create this $table  
	 */
	public static function createTable($table,$vars)
	{
		if (!self::connect())
			return false;
		$query = "";

		$nr_serial_fields = 0;

		foreach ($vars as $name => $var)
		{
			if(is_numeric($name))
			{
				//  do not allow numeric names of columns
				exit("You are trying to add a column named $name, numeric names of columns are not allowed.");
			}
			$type = $var->_type;
			switch ($type)
			{
				case "serial":
					if ($var->_key != "")
						$type = "int4";
					break;
				case "bigserial":
					if ($var->_key != "")
						$type = "int8";
					break;
			}
			if ($query != "")
				$query .= ",";

			if($type == "serial" || $type == "bigserial")
				$nr_serial_fields++;

			$query .= "\"$name\" $type";
		}

		// do not allow creation of tables with more than one serial field
		// protection is inforced here because i rely on the fact that there is just one numeric id or none
		if($nr_serial_fields > 1)
			exit("Error: Table $table has $nr_serial_fields serial or bigserial fields. You can use 1 serial or bigserial field per table or none.");

		$query = "CREATE TABLE $table ($query) WITH OIDS";
		return self::queryRaw($query) !== false;
	}

	/**
	 * Update the structure of the table
	 * @param $table Name of the table
	 * @param $vars Array of variables for this object
	 * @return Bool value showing if the update succeeded or not
	 */
	public static function updateTable($table,$vars)
	{
		if (!self::connect())
			return false;
		$query = "SELECT * FROM $table WHERE false";
		$res = self::queryRaw($query);
		if (!$res)
		{
		//	print "Table '$table' does not exist so we'll create it\n";
			return self::createTable($table,$vars);
		}

		foreach ($vars as $name => $var)
		{
			$type = $var->_type;
			$field = pg_field_num($res,$name);
			if ($field < 0) 
				$field = pg_field_num($res,"\"$name\"");
			if ($field < 0)
			{
				if($type == "serial")
					$type = "int4";
				if($type == "bigserial")
					$type = "int8";
			//	print "No field '$name' in table '$table', we'll create it\n";
				$query = "ALTER TABLE $table ADD COLUMN \"$name\" $type";
				if (!self::queryRaw($query))
					return false;
				if ($var->_value !== null)
				{
					$val = $var->escape($var->_value);
					$query = "UPDATE $table SET \"$name\"=$val";
					if (!self::queryRaw($query))
						return false;
				}
			}
			else
			{
				// we need to adjust what we expect for some types
				switch ($type)
				{
					case "serial":
						$type = "int4";
						break;
					case "bigserial":
						$type = "int8";
						break;
				}
				$dbtype = pg_field_type($res,$field);
				if ($dbtype == $type)
					continue;
				self::warning("Field '$name' in table '$table' is of type '$dbtype' but should be '$type'\n");
				return false;
			}
		}
		return true;
	}

	/**
	 * Creates one or more btree indexs on the specified table
	 * @param $table Name of the table
	 * @param $columns Array with the columns for defining each index
	 * Ex: array("time") will create an index with the name "$table-time" on column "time"
	 * array("index-time"=>"time", "comb-time-user_id"=>"time, user_id") creates an index called "index-time" on column "time"
	 * and an index called "comb-time-user_id" using both "time" and "user_id" columns
	 * @return Bool value showing if the creating succeeded or not 
	 */
	public static function createIndex($table, $columns)
	{
		$no_error = true;
		$make_vacuum = false;
		foreach($columns as $index_name=>$index_columns)
		{
			if ($index_columns == '' || !$index_columns)
				continue;
			if(is_numeric($index_name))
				$index_name = "$table-index";
			$query = "CREATE INDEX \"$index_name\" ON $table USING btree ($index_columns)";
			$res = self::queryRaw($query);
			if (!$res)
			{
				$no_error = false;
				continue;
			}
			$make_vacuum = true;
			$res = self::queryRaw($query);
		}
		if($make_vacuum)
		{
			$query = "VACUUM ANALYZE $table";
			$res = self::queryRaw($query);
		}
		return $no_error;
	}
}

// general model for defining an object that will be mapped to an sql table 
class Model
{
	protected $_model;
	//if $_invalid is setted to true, this object can't be setted as a key for update or delete clauses 
	protected $_invalid;

	protected static $_models = false;
	protected static $_modified = false;
	// array with name of objects that are performers when using the ActionLog class
	protected static $_performers = array();

	/**
	 * Base class constructor, populates the list of variables of this object
	 */
	function __construct()
	{
		$this->_invalid = false;
		$this->_model = self::getVariables(get_class($this));
		foreach ($this->_model as $name => $var)
			$this->$name = $var->_value;
	}

	/**
	 * Creates an array of objects by selecting rows that match the conditions.
	 * @param $class Name of the class of the returned object
	 * @param $conditions Array of conditions of type $key=>$value
	 * @param $order Array used for specifing order options OR just Text with options
	 * @param $limit Number used for setting the LIMIT clause of the query
	 * @param $offset Number used for seeting the OFFSET clause of the query
	 * @param $given_where Text with the conditions correctly formatted
	 * @param $inner_query Array used for defining an inner query inside the current query.
	 * See method @ref makeInnerQuery() for more detailed explanation
	 * @return array of objects of type $class.
	 */
	public static function selection($class, $conditions=array(), $order= NULL, $limit=NULL, $offset=0, $given_where = NULL, $inner_query=NULL)
	{
		$vars = self::getVariables($class);
		if (!$vars) 
		{
			print '<font style="weight:bold;">You haven\'t included file for class '.$class.' try looking in ' . $class . 's.php</font>';
			return null;
		}
		$table = self::getClassTableName($class);
		$obj = new $class;
		$where = ($given_where) ? $given_where : $obj->makeWhereClause($conditions,true);
		if ($inner_query)
			$where = $obj->makeInnerQuery($inner_query, $table, $where);
		$query = self::buildSelect("*", $table, $where, $order, $limit, $offset);
		$res = Database::query($query);
		if(!$res)
		{ 
			self::warning("Could not select $class from database in selection.");
			return null;
		}
		$object = new $class;
		return $object->buildArrayOfObjects($res);
	}

	/**
	 * Perform query with fields given as a string.
	 * Allows using sql functions on the results being returned
	 * @param $fields String containing the items to select. 
	 * Example: count(user_id), max(price), (case when $condition then value1 else value2 end) as field_name
	 * @param $conditions Array of conditions
	 * @param $group_by String for creating GROUP BY clause
	 * @param $order Array or String for creating ORDER clause
	 * @param $inner_query Array for using an inner query inside the WHERE clause
	 * @param $extend_with Array of $key=>$value pair, $key is the name of a column, $value is the name of the table
	 * Example: class of $this is Post and $extended=array("category_id"=>"categories") 
	 * becomes AND post.category_id=categories.category_id 
	 * @param $given_where String representing the WHERE clause
	 * @param $given_from String representing the FROM clause
	 * @return Value for single row, sigle column in sql result/ Array of results : $result[row][field_name] 
	 * if more rows or columns where returned 
	 */
	public function fieldSelect($fields, $conditions=array(), $group_by=NULL, $order=NULL, $inner_query=NULL, $extend_with=NULL, $given_where=NULL, $given_from=NULL)
	{
		$table = $this->getTableName();
		$class = get_class($this);

		$where = ($given_where) ? $given_where : $this->makeWhereClause($conditions, true);
		$where = $this->makeInnerQuery($inner_query, $table, $where);

		$from_clause = ($given_from) ? $given_from : $table;
		$clause = '';
		$tables = str_replace(' ', '',$from_clause);
		$tables = explode(',',$tables);
		if(count($extend_with))
		{
			foreach($extend_with as $column_name=>$table_name)
			{
				if(!in_array($table_name, $tables))
				{
					$from_clause .= ', '.$table_name;
					array_push($tables, $table_name);
				}
				if ($clause != '')
					$clause .= ' AND ';
				$clause .= "\"$table\".\"$column_name\"=\"$table_name\".\"$column_name\"";
			}
		}
		if ($where != '' && $clause != '')
			$where .= ' AND '.$clause;
		elseif($clause != '' && $where == '')
			$where = "WHERE $clause";

		$query = self::buildSelect($fields, $from_clause, $where, $order, NULL, NULL, $group_by);
		$res = Database::query($query);
		if(!$res)
		{ 
			self::warning("Could not select $class from database");
			return null;
		}elseif(!pg_num_rows($res))
			return null;

		if (pg_num_rows($res) == 1 && pg_num_fields($res) == 1)
			return pg_fetch_result($res,0,0);
		for($i=0; $i<pg_num_rows($res); $i++)
		{
			$array[$i] = array();
			for($j=0; $j<pg_num_fields($res); $j++)
				$array[$i][pg_field_name($res,$j)] = stripslashes(pg_fetch_result($res,$i,$j));
		}
		return $array; 
	}

	/**
	 * Fill $this object from it's corresponsing row in the database
	 * @param $condition 
	 * $condition should be NULL if we wish to use the value of the numeric id for the corresponding table
	 * $condition should be a STRING representing the name of a column that could act as primary key
	 * In both the above situations $this->{$condition} must be setted before calling this method
	 * $condition should be an ARRAY formed by pairs of $key=>$value for more complex conditions 
	 * This is the $conditions array that will be passed to the method @ref makeWhereClause()
	 */
	public function select($condition = NULL)
	{
		$vars = self::getVariables(get_class($this));
		$table = $this->getTableName();
		$class = get_class($this);
		if (!is_array($condition))
		{
			if(!$condition)
			{
				if(!($id = $this->getIdName()))
				{
					$this->invalidate();
					return;
				}
				$condition = $id;
			}
			$var = $this->variable($condition);
			$value_condition = $var->escape($this->{$condition});
			if (!isset($this->{$condition}) || !$value_condition)
			{
				$this->invalidate();
				return;
			}
			$where_clause = " WHERE \"$condition\"=".$value_condition;
		} else
			$where_clause = $this->makeWhereClause($condition, true);

		$query = self::buildSelect("*", $table, $where_clause);
		$res = Database::query($query);
		if(!$res)
		{ 
			self::warning("Could not select $class from database. Query: $query");
			$this->invalidate();
			return ;
		} elseif(!pg_num_rows($res)) {
			$this->invalidate();
			return;
		} elseif(pg_num_rows($res)>1) {
			$this->invalidate();
			self::warning('More results for a single id.');
			return;
		} else
			$this->populateObject($res);
	}

	/**
	 * Populates $this object, that could have been previously extended, with the fields returned by the generated query or Returns an array of objects 
	 * @param $conditions Array of conditions or pairs field_name=>value
	 * @param $order String representing the order or Array of pairs field_name=>"ASC"/"DESC"
	 * @param $limit Integer representing the maximum number or objects to be returned
	 * @param $offset Integer representing the offset
	 * @param $inner_query Array used for defining an inner query
	 * @param $given_where	WHERE clause already formated
	 * @return NULL if a single row was returned and method was called without any of the above params, $this object is populated with the results of the query / Array of objects having the same variables as $this object
	 */
	public function extendedSelect($conditions = array(), $order = NULL, $limit = NULL, $offset = NULL, $inner_query = array(), $given_where = NULL)
	{
		// the array of variables from the object
		$vars = $this->_model;
		$table = $this->getTableName();
		$id = $this->getIdName();
		// array holding the columns that were added to the query
		$added_fields = array();
		// array of the tables that will appear in the FROM clause 
		$from_tables = array();
		// 
		$from_tables[$table] = true;

		$from_clause = '';
		$columns = '';
		foreach($vars as $var_name => $var)
		{
			// name of table $var_name is a foreign key to
			$foreign_key_to = $var->_key;
			// name of variable in table $foreign_key_to $var_name references 
			$references_var = $var->_matchkey;
			$join_type = $var->_join_type;
			if(!$join_type)
				$join_type = "LEFT";
			if ($columns != '')
				$columns .= ', ';
			$added_fields[$var_name] = true;

			if($foreign_key_to)
			{
				if($from_clause == '')
					$from_clause = " \"$table\" ";
				else
					$from_clause = " ($from_clause) ";
			}

			// if this is not a foreign key to another table and is a valid variable inside the corresponding object
			if(!$foreign_key_to && self::inTable($var_name,$table))
				$columns .= " \"$table\".\"$var_name\"";
			// if this is a foreign key to another table, but does not define a recursive relation inside the $table
			elseif($foreign_key_to && $foreign_key_to != $table) {
				if($references_var)
					// when extending one might need to use another name than the one used in the actual table
					// prime reason: common field names as status or date are found in both tables
					$columns .= " \"$foreign_key_to\".\"$references_var\" as \"$var_name\" ";
				else
					$columns .= " \"$foreign_key_to\".\"$var_name\"";
				// this table was already added in the FROM clause
				if(isset($from_tables[$foreign_key_to])) {
					if($join_type != "LEFT") {
						// must overrite old join type with new join type
						$from_clause = str_replace("LEFT OUTER JOIN \"$foreign_key_to\"", "$join_type OUTER JOIN \"$foreign_key_to\"", $from_clause);
					}
					continue;
				}
				// if $var_name was not added by extending the object, but it's inside the $class of the object 

				$from_tables[$foreign_key_to] = true;
				if(self::inTable($var_name,$table))
				{
					// must add table inside the FROM clause and build the join
					$key = ($references_var) ? $references_var : $var_name;
					$from_clause .= "$join_type OUTER JOIN \"$foreign_key_to\" ON \"$table\".\"$var_name\"=\"$foreign_key_to\".\"$key\"";
					continue;
				}
				// keeping in mind that the $var_name fields that were added using the extend method are always at the end 
				// in the $vars array: 
				// if we reach here then $var_name is a field in the table represented by foreign_key_to and that table has a foreign key to the table corresponding to this object, and not the other way around
				// Example: i have an instance of the group class, i extend that object sending 
				// array("user"=>"users") as param to extend() method, but when query is made i need to look in the
				// user object to find the key that links the two tables 

				$obj = self::getObject($foreign_key_to);
				$obj_vars = $obj->_model;

				$continue = false;
				foreach($obj_vars as $var_name => $obj_var)
				{
					if($obj_var->_key != $table)
						continue;
					$referenced = ($obj_var->_matchkey) ? $obj_var->_matchkey : $var_name;
					$from_clause .= "$join_type OUTER JOIN \"$foreign_key_to\" ON \"$table\".\"$referenced\"=\"$foreign_key_to\".\"$var_name\"";
					//found the condition for join=> break from this for and the continue in the loop to pass to the next var 
					$continue = true;
					break;
				}
				if($continue)
					continue;
				// if i get here then the object was wrongly extended OR user wanted to get a cartesion product of the two tables: 
				// $table does not have a foreign key to $foreign_key_to table
				// $foreign_key_to table oes not have a foreign key to $table
				self::warning("No rule for extending table '$table' with field '$var_name' from table '$foreign_key_to'. Generating cartesian product.");
				$from_clause .= ", $foreign_key_to ";
			}elseif($foreign_key_to && $foreign_key_to == $table) {
				// this defines a recursive relation inside the same table, just 1 level
				if(self::inTable($var_name,$table)) {
					$columns .= " $table".'1'.".\"$references_var\" as \"$var_name\"";
					$from_clause .= "$join_type OUTER JOIN \"$foreign_key_to\" as $foreign_key_to" . "1" . " ON \"$table\".\"$var_name\"=$foreign_key_to"."1".".\"$references_var\"";
				} else {
					// 1 level recursive relation
					$columns .= " $table".'1'.".\"$references_var\" as \"$var_name\"";
				}
			}
		}
		if ($from_clause == '')
			$from_clause = $table;

		if(!$given_where) 
		{
			$where = $this->makeWhereClause($conditions);
			$where = $this->makeInnerQuery($inner_query, $table, $where);
		}else
			$where = $given_where;

		if(!is_array($order))
			if(substr($order,0,3) == 'oid')
				// if we need to order by the oid then add oid to the columns
				$columns = 'distinct('.$my_table.'.oid),'.$columns;

		// we asume that this query will return more than one row
		$single_object = false;
		if($id)
		{
			$var = $this->variable($id);
			$value_id = $var->escape($this->{$id});
			//if this object has a numeric id defined, no conditions were given then i want that object to be returned
			if (!count($conditions) && !$given_where && !$order && !$limit && !$offset && $value_id)
			{
				// one expectes a single row to be returned from the resulted query
				$where = "WHERE \"$table\".\"$id\"=".$value_id;
				$single_object =true;
			}
		}

		$query = self::buildSelect($columns, $from_clause,$where,$order,$limit,$offset); 
		$res = Database::query($query);
		if(!$res)
		{
			$this->invalidate(); 
			self::warning("Could not select ".get_class($this)." from database. Query: $query");
			return ;
		}

		if (pg_num_rows($res) == 1 && $single_object)
			$this->populateObject($res);
		else
			return $this->buildArrayOfObjects($res);
	}


	/**
	 * Insert this object in the database
	 * @param $retrieve_id BOOL value marking if we don't wish the numeric id of the inserted object to be returned
	 * By default the numeric id of the object is retrieved
	 * @param $keep_log BOOL value marking whether to log this operation or not
	 * @return Array(Bool value,String), bool shows whether the inserting succeded or not, String is a default message that could be printed 
	 */
	public function insert($retrieve_id = true, $keep_log = true)
	{
		$columns = "";
		$values = "";
		$serials = array();
		$insert_log = "";
		foreach ($this->_model as $var_name => $var)
		{
			$value = $this->$var_name;
			if (!strlen($value))
			{
				// some types have defaults assigned by DB server so we don't set them
				switch ($var->_type)
				{
					case "serial":
						if ($var->_key == "")
							$serials[$var_name] = true;
						$var = null;
						break;
					case "bigserial":
						if ($var->_key == "")
							$serials[$var_name] = true;
						$var = null;
						break;
				}
			}
			if (!$var)
				continue;
			if ($columns != "")
			{
				$columns .= ",";
				$values .= ",";
				$insert_log .= ", ";
			}
			$columns .= "\"$var_name\"";
			$values .= $var->escape($value);
			$insert_log .= "$var_name='$value'";
		}
		if ($columns == "")
			return;
		$table = $this->getTableName();
		$query = "INSERT INTO $table($columns) VALUES($values)";
		$res = Database::query($query);
		if (!$res)
			return array(false,"Failed to insert into $table");
		if($retrieve_id)
			if (count($serials))
			{
				$oid = pg_last_oid($res);
				if ($oid === false)
					return array(false,"There are no OIDs on table $table");
				$columns = implode(",",array_keys($serials));
				$query = "SELECT $columns FROM $table WHERE oid=$oid";
				$res = Database::query($query);
				if (!$res)
					return array(false,"Failed to select serials");
				foreach (array_keys($serials) as $var_name)
					$this->$var_name = pg_fetch_result($res,0,$var_name);
			}
		$log = "inserted ".$this->getNameInLogs().": $insert_log";
		if($keep_log === true)
			self::writeLog($log,$query);
		return array(true,"Succesfully inserted into ".ucwords(str_replace("_"," ",$table)));
	}
	
	/**
	 * Update object (!! Use this after you selected the object or else the majority of the fields will be set to null)
	 * @param $conditions Array of conditions for making an update
	 * if no parameter is sent when method is called it will try to update based on the numeric id od the object, unless is was invalidated
	 * @return Array(BOOL value, String, Int), boolean markes whether the update succeeded or not, String is a default message that might be printed, Int shows the number of affected rows
	 */
	public function update($conditions = array())
	{
		$where = "";
		$variables = "";
		$update_log = "";

		if (count($conditions)) 
			$where = $this->makeWhereClause($conditions, true);
		else{
			if($this->isInvalid())
				return array(false, "Update was not made. Object was invalidated previously.",0);

			if($id = $this->getIdName()) {
				$var = $this->variable($id);
				$value_id = $var->escape($this->{$id});
				if ($value_id)
					$where = "where $id=".$value_id;
			}
		} 
		$vars = self::getVariables(get_class($this));
		if (!$vars)
			return null;
		foreach($vars as $var_name=>$var) 
		{
			if ($variables != '')
			{
				$variables .= ", ";
				$update_log .= ", ";
			}
			$variables .= "\"$var_name\""."=".$var->escape($this->{$var_name})."";
			$update_log .= "$var_name='".$this->{$var_name}."'"; 
		}
		$query = "UPDATE ".$this->getTableName()." SET $variables $where";
		//print "query-update:$query";
		$res = Database::query($query);
		if(!$res) 
			return array(false,'Failed to update '.strtolower(str_replace("_"," ",get_class($this))),0);
		else{
			$message = 'Succesfully updated '.pg_affected_rows($res).' ' .strtolower(str_replace("_"," ",get_class($this)));
			if (pg_affected_rows($res) != 1)
				$message .= 's';
			$update_log = "updated ".$this->getNameInLogs().": $update_log $where";
			self::writeLog($update_log,$query);
			return array(true,$message,pg_affected_rows($res));
		}
	}

	/**
	 * Update only the specified fields for this object
	 * @param $conditions NULL for using the id / Array on pairs $key=>$value
	 * @param $fields Array($field1, $field2 ..)
	 * @return Array(Bool,String,Int) Bool whether the update succeeded or not, String is a default message to print, 
	 * Int is the number of affected rows 
	 */
	public function fieldUpdate($conditions = array(),$fields = array())
	{
		$where = "";
		$variables = "";
		$update_log = "";

		if (count($conditions)) 
			$where = $this->makeWhereClause($conditions, true);
		else{
			if($this->invalidate())
				return array(false, "Update was not made. Object was invalidated previously.",0);
			if(($id = $this->getIdName())) {
				$var = $this->variable($id);
				$value_id = $var->escape($this->{$id});
				if ($value_id)
					$where = "where $id=".$value_id;
			}
		}
 
		$vars = self::getVariables(get_class($this));
		if (!count($fields))
			return array(false,"Update was not made. No fields were specified.",0);
		foreach($vars as $var_name=>$var) 
		{
			if(!in_array($var_name,$fields))
				continue;
			if (!isset($vars[$var_name]))
				continue;

			if ($variables != '')
			{
				$variables .= ", ";
				$update_log .= ", ";
			}

			$value = $this->{$var_name};
			if(substr($value,0,3) == "sql_")
			{
				//Value is an sql function or other column from the same table
				//When using this and referring to a column named the same as a reserved word 
				//in PostgreSQL "" must be used inside the $value field
				$value = substr($value,3,strlen($value));
				$variables .= "\"$var_name\""."="."$value";
			}else{
				$variables .= "\"$var_name\""."=".$var->escape($value)."";
			}
			$update_log .= "$var_name='$value'";
		}

		$query = "UPDATE ".$this->getTableName()." SET $variables $where";
		$res = Database::query($query);
		if(!$res) 
			return array(false,'Failed to update '.strtolower(str_replace("_"," ",get_class($this))),0);
		else
		{
			$mess = 'Succesfully updated '.pg_affected_rows($res).' ' .strtolower(str_replace("_"," ",get_class($this)));
			if (pg_affected_rows($res) != 1)
				$mess .= 's';
			$update_log = "update ".$this->getNameInLogs().": $update_log $where";
			self::writeLog($update_log,$query);
			return array(true,$mess,pg_affected_rows($res), pg_affected_rows($res));
		}
	}
	
	 /**
	  * Verify if there is an entry in the database for the object of the given class
	  * @param $param Name of the field that we don't want to have duplicates
	  * @param $value Value of the field @ref $param
	  * @param $class Object class that we want to check for
	  * @param $id Name of the id field for the type of @ref $class object
	  * @param $value_id Value of the id
	  * @param $additional Other conditions that will be written directly in the query
	  * @return true if the object exits, false if not
	  */
	public static function rowExists($param, $value, $class, $id, $value_id = NULL, $additional = NULL)
	{
		$table = self::getClassTableName($class);

		$value_id = pg_escape_string($value_id);
		if ($value_id)
			$query = "SELECT $id FROM $table WHERE \"$param\"='".addslashes($value)."' AND $id!='$value_id' $additional";
		else
			$query = "SELECT $id FROM $table WHERE \"$param\"='".addslashes($value)."' $additional";
		$res = Database::query($query);
		if(!$res)
			exit("Could not do: $query");
		if(pg_num_rows($res))
			return true;
		return false;
	}

	 /**
	  * Verify if an object has an entry in the database. Before this method is called one must set the values of the fields that will build the WHERE clause. Variables that have a default value will be ignored
	  * @param $id_name Name of the id of this object
	  * @return id or the object that matches the conditions, false otherwise
	  */
	public function objectExists($id_name = NULL)
	{
		$vars = self::getVariables(get_class($this));
		if (!$vars)
			return null;

		if(!$id_name) 
			$id_name = $this->getIdName();

		$class = get_class($this);
		if(!$this->variable($id_name))
		{
			self::warning("$id_name is not a defined variable inside the $class object.");
			exit();
		}

		$fields = '';
		$table = $this->getTableName();

		//get an object of the same class as $this
		$clone = new $class;

		foreach($vars as $var_name=>$var) 
		{
			// ignoring fields that have a default value and the numeric id of the object
			if ($this->{$var_name} != '' && $var_name != $id_name) 
			{
				if($clone->{$var_name} != '')
					continue;
				if ($fields != '')
					$fields .= ' AND ';
				$fields .= "\"$var_name\""."=".$var->escape($this->{$var_name})."";
			}
		}
		if($fields == '')
			exit("Don't have setted variables for this object");

		$var = $this->variable($id_name);
		$value_id = $var->escape($this->{$id_name});
		$where = ($value_id && $value_id != "NULL") ? "WHERE $fields AND \"$id_name\"!='$value_id'" : "WHERE $fields";
		$query = self::buildSelect($id_name,$table,$where);
		$res = Database::query($query);

		if(!$res)
			exit("Could not do: $query");

		if(pg_num_rows($res)) {
			return pg_fetch_result($res,0,0);
		}
		return false;
	}

	/**
	 * Recursive function that deletes the object(s) matching the condition and all the objects having foreign keys to
	 * this one with _critical=true, the other ones having _critical=false with the associated column set to NULL
	 * @param $conditions Array of conditions for deleting (if count=0 then we look for the id of the object) 
	 * @param $seen Array of classes from were we deleted
	 * @return array(true/false,message) if the object(s) were deleted or not
	 */
	public function objDelete($conditions=array(), $seen=array())
	{
		$vars = self::getVariables(get_class($this));
		if(!$vars)
			return null;

		$table = $this->getTableName();
		if(!count($conditions)) 
		{
			if($this->isInvalid())
				return array(false, "Could not delete object of class ".get_class($this).". Object was previously invalidated.");
			
			if(($id_name = $this->GetIdName()))
			{
				$var = $this->variable($id_name);
				$id_value = $var->escape($this->{$id_name});
				if(!$id_value)
					$where = '';
				else
					$where = " where \"$id_name\"='$id_value'";
			}else
				$where = '';
		}else
			$where = $this->makeWhereClause($conditions, true);

		// array of pairs object_name=>array(var_name=>var_value) in which we have to check for deleting on cascade
		$to_delete = array();
		foreach($vars as $var_name=>$var)
		{
			$value = $this->{$var_name};
			if (!$value)
				continue;
			//search inside the other objects if there are column that reference $var_name column
			foreach(self::$_models as $class_name=>$class_vars)
			{
				foreach($class_vars as $class_var_name=>$class_var)
				{
					if ($class_var->_key == $table && ($class_var_name == $var_name || $class_var->_matchkey == $var_name))
						// if variable $class_var_name from object $class_name points to $var_name
						$class_var_name = ($class_var->_matchkey) ? $class_var->_matchkey : $var_name;
					else
						continue;

					if (strtolower($class_name) == strtolower(get_class($this)))
						continue;

					$obj = new $class_name;
					$obj->{$class_var_name} = $value;
					if ($class_var->_critical)
						// if relation is critical equivalent to delete on cascade, add $class_name to array of classes on which same method will be applied 
						$to_delete[$class_name] = array($class_var_name=>$value);
					else
					{
						// relation is not critical. we just need to set to NULL the fields pointing to this one
						$nr = $obj->fieldSelect('count(*)',array($class_var_name=>$value));
						if($nr)
						{
							//set column $class_var_name to NULL in all rows that have the value $value 
							$obj->{$class_var_name} = NULL;
							$obj->fieldUpdate(array($class_var_name=>$value),array($class_var_name));
						}
					}
				}
			}
		}
		$query = "DELETE FROM $table $where";
		$res = Database::query($query);
		$cnt = count($seen);
		array_push($seen,strtolower(get_class($this)));

		foreach($to_delete as $object_name=>$condition)
		{
			$obj = new $object_name;
			$obj->objDelete($condition,$seen);
		}
		if($res)
			if ($cnt) 
			{
				self::writeLog("deleted ".$this->getNameInLogs()." $where");
				print "<br/>\nSuccesfully deleted ".pg_affected_rows($res)." object";
				if(pg_affected_rows($res) != 1)
					print "s";
				print " of type ".get_class($this).'<br/>'."\n";
			}
			else
				return array(true, "Succesfully deleted ".pg_affected_rows($res)." objects of type ".get_class($this));
		else
			if ($cnt)
				print "<br/>\nCould not delete object of class ".get_class($this).'<br/>'."\n";
			else
				return array(false, "Could not delete object of class ".get_class($this));
		return;
	}

	/**
	 * Recursive function that checks how many rows will be affected in the database, if objDelete will be called on this object using the same $conditions param.
	 * If table doesn't have references from other tables then this table will be the only one affected.
	 * @param $conditions Array of conditions for deleting (if count=0 then we look for the id of the object) 
	 * @param $message String representing the message 
	 * @return The message with the number affected row (deleted or set to NULL) in tables 
	 */
	public function ackDelete($conditions=array(), $message = "")
	{
		$vars = self::getVariables(get_class($this));
		if(!$vars)
			return null;
		$original_message = $message;
		$table = $this->getTableName();
		if(!count($conditions))
		{
			if($this->isInvalid())
				return "Could not try to delete object of class ".get_class($this).". Object was previously invalidated.";
			
			if(($id_name = $this->GetIdName()))
			{
				$var = $this->variable($id_name);
				$id_value = $var->escape($this->{$id_name});
				if(!$id_value)
					$where = '';
				else
					$where = " where \"$id_name\"='$id_value'";
			}else
				$where = '';
		}else
			$where = $this->makeWhereClause($conditions, true);

		// array of pairs object_name=>array(var_name=>var_value) in which we have to check for deleting on cascade
		$to_delete = array();
		foreach($vars as $var_name=>$var)
		{
			$value = $this->{$var_name};
			if (!$value)
				continue;
			foreach(self::$_models as $class_name=>$class_vars)
			{
				foreach($class_vars as $class_var_name=>$class_var)
				{
					if ($class_var->_key == $this->getTableName() && ($class_var_name == $var_name || $class_var->_matchkey == $var_name))
						$class_var_name = ($class_var->_matchkey) ? $class_var->_matchkey : $var_name;
					else
						continue;
					if (strtolower($class_name) == strtolower(get_class($this)))
						continue;
					$obj = new $class_name;
					$obj->{$class_var_name} = $value;
					if ($class_var->_critical) 
						$to_delete[$class_name] = array($class_var_name=>$value);
					else
					{
						$nr = $obj->fieldSelect('count(*)',array($class_var_name=>$value));
						if($nr) 
							$message .= strtolower($class_name)."s, ";
					}
				}
			}
		}
		$message .= $this->getTableName().', ';
		foreach($to_delete as $object_name=>$condition)
		{
			$obj = new $object_name;
			$message .= $obj->ackDelete($condition);
		}
		if($original_message == "")
		{
			$message = substr($message,0,strlen($message)-2);
			$message .= ".";
		}
		return $message;
	}

	/**
	 * Extend the calling object with the variables provided
	 * @param $vars Array of pairs : $var_name=>$table_name that will be added to this objects model
	 * If $var_name = "var_name_in_calling_table:referenced_var_name" then the new variable will be called  var_name_in_calling_table and will point to referenced_var_name in $table_name
	 */
	public function extend($vars)
	{
		foreach($vars as $var_name=>$table_name) 
		{
			$break_var_name = explode(":",$var_name);
			if(count($break_var_name) == "2") 
			{
				$var_name = $break_var_name[0];
				$references = $break_var_name[1];
			}else
				$references = NULL;

			if(isset($this->_model[$var_name]))
			{
				self::warning("Trying to override existing variable $var_name. Ignoring this field when extending.");
				continue;
			}
			// don't let user extend the object using a numeric key
			if(is_numeric($var_name))
			{
				exit("$var_name is not a valid variable name. Please do not use numbers or numeric strings as names for variables.");
			}
			if(!is_array($table_name))
				$this->_model[$var_name] = new Variable("text",null,$table_name,false,$references);
			else{
				$this->_model[$var_name] = new Variable("text",null,$table_name["table"],false,$references,$table_name["join"]);}
			$this->{$var_name} = NULL;
		}
	}

	/**
	 * Merge two objects (that have a single field common, usually an id)
	 * Function was done to make easier using 1-1 relations
	 * @param $object is the object whose properties will be merged with those of the calling object: $this
	 */
	public function merge($object)
	{
		$party_vars = self::getVariables(get_class($object));
		$vars = self::getVariables(get_class($this));
		$party_table = $object->getTableName();

		foreach($party_vars as $var_name=>$value)
		{
			// fields that are named the same as one of the calling object will be ignored
			// for fields that have the same name in both objects please use the extend function : extend(array("key_in_original_table:rename_key_for_calling_table"=>"original_table"));
			if(isset($vars[$var_name]))
				continue;
			$this->_model[$var_name] = new Variable("text",null,$party_table);
		}
	}


	/**
	 * Sets the value of each variable inside this object to NULL
	 */
	public function nulify()
	{
		$vars = self::getVariables(get_class($this));
		foreach($vars as $var_name=>$var)
			$this->{$var_name} = NULL;
	}

	/**
	 * Exports an array of objects to an array of array.
	 * @param $objects Array of objects to be exported
	 * @param $formats Array of pairs $var_name(s)=>$value
	 * $var_name is a name of a variable or more variable names separated by commas ','
	 * $value can be '' if column will be added in the array with the same name and the value resulted from query 
	 * $value can be 'function_nameOfFunction' the $nameOfFunction() will be applied on the value resulted from query  and that result will be added in the array to be returned
	 * $value can be 'name_to_appear_under' if  column will be added in the array with the name name_to_appear_under and the value resulted from query 
	 * $value can be 'function_nameOfFunction:name_to_appear_under' in order to have name_to_appear_under and value returned from calling the function nameOfFunction
	 * The most complex usage is: $var_name is 'var1,var2..' and $value is 'function_nameOfFunction:name_to_appear_under', then nameOfFunction(var1,var2,..) will be called and the result will be added in the array to be returned under name_to_appear_under
	 * $var_name can start with ('1_', '2_' ,'3_' , '4_', '5_', '6_', '7_', '8_', '9_', '0_'), that will be stripped in order to have 
	 * multiple fields in the array generated from the same $variable 
	 * @param $block Bool value. If true then only the $
	 */
	public static function objectsToArray($objects, $formats, $block = false)
	{
		if (!count($objects))
			return array();
		// array of beginnings that will be stripped
		// usage is motivated by need of having two columns in the array generated from a single variable
		// Example: we have a timestamp field called date, but we need two fields in the array, one for date and the other for time
		// $formats=array("1_date"=>"function_get_date:date", "2_date"=>"function_get_time:time")
		$begginings = array('1_', '2_' ,'3_' , '4_', '5_', '6_', '7_', '8_', '9_', '0_');
		$i = 0;
		if (!isset($objects[$i])) {
			while(!isset($objects[$i])) {
				$i++;
				if ($i>200) 
				{
					print "<br/>\n<br/>\nInfinit loop<br/>\n<br/>\n";
					return;
				}
			}	
		}
		$vars = $objects[$i]->_model;
		if(!$vars)
			return null;
		$array = array();

		if(count($objects))
			$id =$objects[0]->getIdName(); 
		for($i=0; $i<count($objects); $i++) 
		{
			if(!isset($objects[$i]))
				continue;
			$vars = $objects[$i]->_model;
			$keep = array();
			if ($formats != 'all')
				foreach($formats as $key=>$value)
				{
					if(in_array(substr($key,0,2), $begginings))
						$key = substr($key,2,strlen($key));
					$name = ($value == '') ? $key : $value;
					if(substr($name,0,9) == "function_") 
					{
						$name = substr($name,9,strlen($name));
						$arr = explode(':',$name);
						if(count($arr)>1)
						{
							$newname = $arr[1];
							$name = $arr[0];
						}else
							$newname = $key;
						if(str_replace(',','',$key) == $key)
							$array[$i]["$newname"] = call_user_func($name,$objects[$i]->{$key});
						else
						{
							$key = explode(',',$key);
							$params = array();
							for($x=0; $x<count($key); $x++)
								$params[trim($key[$x])] = $objects[$i]->{trim($key[$x])};
							$array[$i]["$newname"] = call_user_func_array($name,$params);
							$key = implode(":",$key);
						}
					}else
						$array[$i]["$name"] = $objects[$i]->{$key};
					$keep[$key] = true;
				}
			//by default if $block is not true then the id of this object will be added to the result
			if (!$block) 
			{
				foreach($vars as $key=>$value)
				{
					if ($formats != 'all' && $key!=$id)
						continue;
					$array[$i]["$key"] = $objects[$i]->{$key};
					$keep[$key] = true;
				}
			}
		}
		return $array;
	}

	/**
	 * Perform vacuum on this object's associated table
	 */
	public function vacuum()
	{
		$table = $this->getTableName();
		$query = "VACUUM ANALYZE $table";
		Database::query($query);
	}

	/**
	* Convert a boolean or SQL bool representation to a SQL bool
	* @param $value Value to convert, can be true, false, 't' or 'f'
	* @param $defval Default to return if $value doesn't match
	*/
	public static function sqlBool($value, $defval = 'NULL')
	{
		if (($value === true) || ($value === 't'))
			return 't';
		if (($value === false) || ($value === 'f'))
			return 'f';
		return $defval;
	}

	/**
	 * Creates a WHERE clause for a query
	 * @param $conditions Array defining the condtions
	 * @return Text representing the WHERE clause
	 */
	public function exportWhereClause($conditions)
	{
		return $this->makeWhereClause($conditions);
	}

	/**
	 * Return whether the model was modified or not
	 */
	public static function modified()
	{
		return self::$_modified;
	}

	/**
	 * Get a list of all classes derived from Model
	 * @return Array of strings that represent the names of Model classes
	 */
	static function getModels()
	{
		$models = array();
		$classes = get_declared_classes();
		foreach ($classes as $class)
		{
			if (get_parent_class($class) == "Model")
				$models[] = $class;
		}
		return $models;
	}

	/**
	 * One-time initialization of the static array of model variables.
	 * This method is called internally from any methods that need access
	 *  to the variables of any derived class.
	 * IMPORTANT: All derived classes must be defined when this method is
	 *  called for the first time.
	 */
	static function init()
	{
		if (self::$_models)
			return;
		$classes = get_declared_classes();
		foreach ($classes as $class)
		{
			// calling static class methods is done using an array("class","method")		
			if (get_parent_class($class) == "Model")
			{
				$vars = null;
				$vars = @call_user_func(array($class,"variables"));
				if (!$vars)
					continue;
				foreach ($vars as &$var)
					$var->_owner = $class;
				self::$_models[strtolower($class)] = $vars;
				$obj = new $class;
				// check to see if this object is a performer for the ActionLog class
				$performer = $obj->isPerformer();
				if($performer && count($performer))
					self::$_performers[strtolower($class)] = $performer;
			}
		}
	}

	/**
	 * Update the database to match all the models
	 * @param $class Name of class whose variables will be updated
	 * @return True if the database was synchronized with the model
	 */
	static function updateModel($class)
	{
		if (!Database::connect())
			return false;
		$class = strtolower($class);
		$vars = self::getVariables($class);
		if (!$vars)
			return false;

		$object = new $class;
		$table = $object->getTableName();

		if (!Database::updateTable($table,$vars))
		{
			self::warning("Could not update table of class $class\n");
			return false;
		}
		self::$_modified = true;
		$res = @call_user_func(array($class,"defaultObject"));
		return true;
	}

	/**
	 * Update the database to match all the models
	 * @return True if the database was synchronized with all the models
	 */
	static function updateAll()
	{
		if (!Database::connect())
			return false;
		self::init();
		foreach (self::$_models as $class => $vars)
		{
			$object = new $class;
			$table = $object->getTableName();
			if (!Database::updateTable($table,$vars))
			{
				self::warning("Could not update table of class $class\n");
				return false;
			}
			else
				self::$_modified = true;

			if ($index = @call_user_func(array($class,"index")))
				Database::createIndex($table,$index);
		}
		if(self::$_modified)
			foreach(self::$_models as $class => $vars) 
				$res = call_user_func(array($class,"defaultObject"));
		return true;
	}

	/**
	 * Get the database mapped variables of a Model derived class
	 * @param $class Name of class whose variables will be described
	 * @return Array of objects of type Variable that describe the
	 *  database mapped variables of any of the @ref $class objects
	 */
	public static function getVariables($class)
	{
		self::init();
		$class = strtolower($class);
		if (isset(self::$_models[$class]))
			return self::$_models[$class];
		return null;
	}

	/**
	 * Get the Variable object with the name specified by $name from class $class, if valid variable name in class
	 * @param $class Name of the class
	 * @param $name Name of the variable in the object
	 * @return Object of type Variable or null if not found
	 */
	public static function getVariable($class,$name)
	{
		$vars = self::getVariables($class);
		if (!$vars)
			return null;
		return isset($vars[$name]) ? $vars[$name] : null;
	}

	/**
	 * Gets the variables of a certian object(including thoses that were added using the extend function)
	 * @return Array of objects of type Variable that describe the current extended object
	 */
	public function extendedVariables()
	{
		return $this->_model;
	}

	/**
	 * Returns the variable with the specified name or NULL if variable is not in the object
	 * @param $name Name of the variable
	 * @return Variable object or NULL is variable is not defined
	 */
	public function variable($name)
	{
		return isset($this->_model[$name]) ? $this->_model[$name] : null;
	}

	/**
	 * Get the name of a table corresponding to this object. Method can be overwritted from derived class when other name for the table is desired. 
	 * @return Name of table corresponding to $this object
	 */
	public function getTableName()
	{
		$class = strtolower(get_class($this));
		if(substr($class,-1) != "y")
			return $class . "s";
		else
			return substr($class,0,strlen($class)-1) . 'ies';
	}

	/**
	 * Get the name of the table associated to the given class
	 * @param $class Name of the class to get the table for
	 * @return Table name 
	 */
	public static function getClassTableName($class)
	{
		if(!isset(self::$_models[strtolower($class)]))
			return null;

		$obj = new $class;
		return $obj->getTableName();
	}

	/**
	 * Get an object by giving the name of the sql table
	 * @param $table Name of table in sql
	 * @return Object or NULL
	 */
	public static function getObject($table)
	{
		if(!$table)
			return NULL;

		foreach(self::$_models as $class=>$vars)
		{
			if(self::getClassTableName($class) == $table)
				return new $class;
		}

		return NULL;
	}

	/**
	 * Print warning if warnings where setted as enabled in $_SESSION
	 * @param $warn String representing the warning
	 */
	public static function warning($warn)
	{
		if(isset($_SESSION["warnings_on"]))
			print "<br/>\nWarning : $message<br/>\n";
	}

	/**
	 * Print notice if notices were enabled in $_SESSION
	 * @param $note The notice to be printed
	 */
	public static function notice($note)
	{
		if(isset($_SESSION["notice_on"]))
			print "<br/>\nNotice : $message<br/>\n";
	}

	/**
	 * Checks if $name is a valid column inside the specified table
	 * @param $column_name Name of column(variable) to check
	 * @param $table Name of table
	 * @return BOOL value: true if $table is associated to an object and $column_name is a valid variable for that object, false otherwise
	 */
	protected static function inTable($column_name, $table)
	{
		if(!($obj = self::getObject($table)))
			return false;
		if($obj->variable($column_name))
			return true;

		return false;
	}

	/**
	 * Get the name of the variable representing the numeric id for this object
	 * @return Name of id variable or NULL if object was defined without a numeric id
	 */
	public function getIdName()
	{
		$vars = self::getVariables(get_class($this));
		foreach($vars as $name => $var)
		{
			//the id of a table can only be serial or bigserial
			if($var->_type != "serial" && $var->_type != "bigserial")
				continue;
			//if it's a foreign key to another table,we ignore that it was defined and serial or bigserial
			if($var->_key && $var->_key != '')
				continue;
			return $name;
		}
		//it might be possible that the object was defined without a numeric id
	}

	/**
	 * Invalidate object. Object can't be used for generating WHERE clause for DELETE or UPDATE statements
	 */
	protected function invalidate()
	{
		self::warning("Invalidating object.");
		$this->_invalid = true;
	}

	/**
	 * Checks to see if an object is invalid(can't be used to generated WHERE clause for DELETE or UPDATE statements)
	 * @return Bool: true is object is invalid, false otherwise
	 */
	protected function isInvalid()
	{
		return $this->_invalid;
	}

	/**
	 * Creates SELECT statement from given clauses
	 * @param $columns String representing what to select
	 * @param $from_clause String telling where to select from
	 * @param $where_clause String with conditions
	 * @param $order String/Array of pairs of field=>"ASC"/"DESC" defining order
	 * @param $limit Number representing the maximum number of fields to be selected
	 * @param $offset Number representing the offset to be used in the query
	 */
	protected static function buildSelect($columns, $from_clause=NULL, $where_clause=NULL, $order=NULL, $limit=NULL, $offset=0, $group_by = NULL, $having = NULL)
	{
		$ord = self::makeOrderClause($order);
		$order_clause = ($ord) ? " ORDER BY $ord" : NULL;
		$limit_clause = ($limit) ? " LIMIT $limit" : NULL;
		$offset_clause = ($offset) ? " OFFSET $offset" : NULL;
		$group_by = ($group_by) ? "GROUP BY $group_by" : NULL;
		$having = ($having) ? " HAVING $having" : NULL;

		$query = "SELECT $columns FROM $from_clause $where_clause $group_by $having $order_clause $limit_clause $offset_clause";
		return $query;
	}

	/**
	 * Returns a WHERE clause for a query
	 * @param $conditions Array defining the conditions for a query
	 * Array is formed by pairs of $key=>$value. $value can also be an array
	 * Ex to buid AND : "date"=>(">2008-07-07 00:00:00", "<2008-07-07 12:00:00") means WHERE date>'2008-07-07 00:00:00' AND date<'2008-07-07 12:00:00'
	 * EX to build OR : ("date"=>"<2008-07-07 00:00:00", "date"=>">2008-07-07 12:00:00") means WHERE date<'2008-07-07 00:00:00' OR date>'2008-07-07 12:00:00'
	 * @param $only_one_table Bool value specifing if inside the query only one table is referred
	 * Value is true when method is called from within a method that never returns extended objects.
	 * @param $without_table 
	 * @param $null_exception Enables a verification 
	 * @return Text representing the WHERE clause or '' if the count($conditions) is 0
	 */
	protected function makeWhereClause($conditions, $only_one_table = false, $without_table = false)
	{
		$where = ' WHERE ';
		if(!count($conditions))
			return '';
		$obj_table = $this->getTableName();
		foreach($conditions as $key=>$value)
		{
			if ($value === NULL && (!strlen($value) || !is_array($value)))
				continue;

			if ($where != " WHERE ")
				$where .= " AND ";
			if(is_array($value) && is_numeric($key))
				$clause = $this->buildOR($value, $obj_table, $only_one_table, $without_table);
			elseif(is_array($value)) {
				$clause = $this->buildAND($key, $value, $obj_table, $only_one_table, $without_table);
			} else
				$clause = $this->makeCondition($key, $value, $obj_table, $only_one_table, $without_table);

			$where .= $clause;
		}
		if($where == " WHERE ")
			return '';
		return $where;
	}

	/**
	 * Build part of a WHERE clause (conditions will be linked by AND)
	 * @param $key name of the column on which the conditions are set
	 * @param $allowed_values Array with the allowed values for the $key field
	 * @param $obj_table Name of the table associated to the object on which method is called
	 * @param $only_one_table Bool value specifing if inside the query only one table is referred
	 * Value is true when method is called from within a method that never returns extended objects.
	 * @param $without_table The name of the tables won't be specified in the query: Ex: we won't have table_name.column, just column
	 */
	protected function buildAND($key, $allowed_values, $obj_table, $only_one_table = false, $without_table = false)
	{
		$t_k = $this->getColumnName($key, $obj_table, $only_one_table, $without_table);

		$clause = "";
		for($i=0; $i<count($allowed_values); $i++)
		{
			if($clause != "")
				$clause .= " AND "; 
			$clause .= $this->makeCondition($t_k, $allowed_values[$i], $obj_table, $only_one_table, true);
		}
		return $clause;
	}

	/**
	 * Build part of a WHERE clause (conditions will be linked by AND)
	 * @param $conditions Array of type $key=>$value representing the clauses that will be separated by OR
	 * @param $obj_table Name of the table associated to the object on which method is called
	 * @param $only_one_table Bool value specifing if inside the query only one table is referred
	 * Value is true when method is called from within a method that never returns extended objects.
	 * @param $without_table The name of the tables won't be specified in the query: Ex: we won't have table_name.column, just column
	 */
	protected function buildOR($conditions, $obj_table, $only_one_table = false, $without_table = false)
	{
		$clause = "";
		foreach($conditions as $column_name=>$value)
		{
			if($clause != "")
				$clause .= " OR "; 
			$t_k = $this->getColumnName($column_name, $obj_table, $only_one_table, $without_table);
			$clause .= $this->makeCondition($t_k, $value, $obj_table, $only_one_table, true);
		}
		return " (" . $clause. ") ";
	}

	/**
	 * Return the name of a column in form "table_name"."column" that will be used inside a query
	 * @param $key Name of the column
	 * @param $obj_table Table associated to $this object
	 * @param $only_one_table Bool value specifing if inside the query only one table is referred(if true, "table_name" won't be added)
	 * @param $without_table Bool value, if true "table_name" won't be specified automatically (it might be that it was already specified in the $key)
	 */
	protected function getColumnName($key, $obj_table, $only_one_table, $without_table)
	{
		if(!$without_table) 
		{
			// If $key starts with "unblock_" then use of function inside the clause is allowed.
			// Example: $key can be date(tablename.timestamp_field) or length(tablename.text_field)
			// Developer has the responsibility to add the name of the table if necessary and to add "" 
			// in case reserved words in PostgreSQL were used as column names or table names
			if (substr($key,0,8) == "unblock_")
				$t_k = substr($key,8,strlen($key));
			else
			{ 
				$look_for_other_table = true;
				//if we use only one table and $key is a variable inside this object
				if($only_one_table)
				{
					$var = self::getVariable(get_class($this), $key);
					//this condition should always be valid, if methods were used in the right way
					//if condition won't be verified because this object was extended and a method for objects that 
					//were not extended was called WHERE clause will be correct but query will most likely fail in
					//the FROM section
					if($var)
					{
						$table = $obj_table;
						$look_for_other_table = false;
					}
				}
				if($look_for_other_table)
				{
					$var = $this->_model[$key];
					$table = $var->_key;
					$matchkey = $var->_matchkey;
					//if matchkey is not specified 
					if(!$table || $table == '')
						$table = $obj_table;
					if(!Model::getVariable(get_class($this),$key)) {

					/*  ex: status field is in the both classes and i put condition on the field that was inserted with method extend*/
					if($table != $obj_table && $matchkey)
						$key = $matchkey;
					}else
						$table = $obj_table;
				}
				$t_k = "\"$table\".\"$key\"";
			}
		}else
			$t_k = "$key";

		return $t_k;
	}

	/**
	 * Build a condition like table_name.column='$value' or table_name.column>'$value'
	 * @param $key Represents the table_name.column part of the condition
	 * @param $value String representing the operator and the value, or just the value when then default operator = will be used
	 * @param $obj_table Table associated to $this object
	 * @param $only_one_table Bool value specifing if inside the query only one table is referred(if true, "table_name" won't be added)
	 * @param $without_table Bool value, if true "table_name" won't be specified automatically (it might be that it was already specified in the $key)
	 */
	protected function makeCondition($key, $value, $obj_table, $only_one_table = false, $without_table = false)
	{
		$t_k = $this->getColumnName($key, $obj_table, $only_one_table, $without_table);
		// Arrays of operators that should be put at the beggining in $value 
		// If none of this operators is used and $value does not have a special value then 
		// the default operator is =
		$two_dig_operators = array("<=",">=","!=","==");
		$one_dig_operators = array(">","<","=");

		$first_two = substr($value,0,2);
		$first_one = substr($value,0,1);
		$clause = '';

		if ($value === false)
			$clause .= " $t_k IS NOT TRUE ";
		elseif($value === true)
			$clause .= " $t_k IS TRUE ";
		elseif($value === "empty")
			$clause .= " $t_k IS NULL ";
		elseif($value === "non_empty")
			$clause .= " $t_k IS NOT NULL ";
		elseif(in_array($first_two, $two_dig_operators)){
			$value = substr($value,2,strlen($value));
			if (substr($value,0,4) == "sql_")
			{
				// If $value starts with "sql_" then $value is not actually a value but 
				// refers to a column from a table
				$value = substr($value, 4, strlen($value));
				$clause .= " $t_k" . $first_two . "$value ";
			}else{
				$value = addslashes($value);
				$clause .= " $t_k" . $first_two . "'$value' ";
			}
		}elseif (in_array($first_one, $one_dig_operators)) {
			$value = substr($value,1,strlen($value));
			if (substr($value,0,4) == "sql_")
			{
				$value = substr($value, 4, strlen($value));
				$clause .= " $t_k" . $first_one . "$value ";
			}else{
				$value = addslashes($value);
				$clause .= " $t_k" . $first_one . "'$value' ";
			}
		}elseif (substr($value,0,4) == "LIKE") {
			$value = addslashes(substr($value,4,strlen($value)));
			if (substr($value,0,1) != '%' && substr($value,-1) != '%')
				$clause .= " $t_k LIKE '$value%' ";
			else
				$clause .= " $t_k LIKE '$value' ";
		}elseif (substr($value,0,8) == "NOT LIKE") {
			$value = addslashes(substr($value,8,strlen($value)));
			if (substr($value,0,1) != '%' && substr($value,-1) != '%')
				$clause .= " $t_k NOT LIKE '$value%' ";
			else
				$clause .= " $t_k NOT LIKE '$value' ";
		}elseif(substr($value,0,4) == "sql_") {
			$value = substr($value,4,strlen($value));
			$clause .= " $t_k=$value";
		}else{
			if ($value != '' && $value)
				$clause .= " $t_k='".addslashes($value)."'";
			else
				// it should never get here
				// if verification for NULL is needed set $value = 'empty' 
				$clause .= " $t_k=NULL";
		}
		return $clause;
	}

	/**
	 * Creates an ORDER clause
	 * @param $order Array for building clause array("name"=>"DESC", "created_on"=>"ASC") or String with 
	 * clause already inserted "name DESC, created_on"
	 * string can also be "rand()", for getting the results in random order
	 * @return ORDER clause 
	 */
	protected static function makeOrderClause($order)
	{
		// When writting the String one must pay attention to use "" for fields and tables that are in
		// the special words in PostgreSQL
		if(!count($order))
			return;
		if (!is_array($order))
			return $order;
		$clause = '';
		foreach($order as $key=>$value) 
		{
			if ($clause != '')
				$clause .= ',';
			if ($value == "DESC")
			{
				if (substr($key,0,1) == "\"")
					$clause = " $key $value";
				else
					$clause .= " \"$key\" $value";
			}else{
				if (substr($key,0,1) == "\"")
					$clause .= " $key";
				else
					$clause .= " \"$key\"";
			}
		}
		return $clause;
	}

	/**
	 * Adding an inner query to a WHERE clause
	 * @param $inner_query Array of params for defining the inner query
	 * @param $table Table to use for the column on which the inner query is applied
	 * @param $where Clause to append to 
	 * @return WHERE clause
	 */
	protected function makeInnerQuery($inner_query=array(), $table = NULL, $where='')
	{
		if(!is_array($inner_query) || !count($inner_query))
			return $where;

		if(!$table || $table == '')
			$table = $this->getTableName();

		// Verifying the compulsory $keys 
		$compulsory = array("column", "relation");
		$error = '';
		for($i=0; $i<count($compulsory); $i++)
		{
			if (!isset($inner_query[$compulsory[$i]]))
				$error .= 'Field '.$compulsory[$i].' is not defined. ';
		}
		if ($error != '')
			exit($error);

		if (!isset($inner_query["other_table"]) && !isset($inner_query["inner_table"]))
			exit("You must either insert 'other_table' or 'inner_table'");

		if ($where == '')
			$where = ' WHERE ';
		else
			$where .= ' AND ';

		if (isset($inner_query['table']))
			$table = $inner_query['table'];

		$inner_table = (isset($inner_query["inner_table"])) ? $inner_query["inner_table"] : $inner_query["other_table"];
		$inner_column = (isset($inner_query["inner_column"])) ? $inner_query["inner_column"] : $inner_query["column"];
		$column = $inner_query["column"];
		$relation = $inner_query["relation"];

		$where .= " \"$table\".\"$column\" $relation (SELECT \"$inner_column\" from \"$inner_table\" ";
		$inner_where = '';

		if(!($obj = self::getObject($inner_table)))
			exit("Quit when wanting to create object from table $inner_table");

		if(isset($inner_query["conditions"]))
			$inner_where .= $obj->makeWhereClause($inner_query["conditions"],true);

		if(isset($inner_query["inner_query"]))
			$inner_where .=$obj->makeInnerQuery($inner_query["inner_query"]);

		$group_by = (isset($inner_query['group_by'])) ? 'group by '.$inner_query['group_by'] : '';
		$having = (isset($inner_query['having'])) ? 'having '.$inner_query['having'] : '';

		$where .= $inner_where ." $group_by $having )";
		
		return $where;
	}

	/**
	 * Populates the variables of $this object with the fields from a query result
	 * @param $result Query result
	 */
	protected function populateObject($result)
	{
		if(pg_num_rows($result) != 1)
		{
			self::warning("Trying to build single object from sql that has ".pg_num_rows($result)." rows. Invalidating object.");
			$this->invalidate();
			return;
		}

		foreach(pg_fetch_array($result,0) as $var_name=>$value) 
			$this->{$var_name} = stripslashes($value);
	}

	/**
	 * Builds an array of objects that have the same variables as $this object from a result of a query
	 * @param $result Query result to build objects from
	 * @return Array of objects
	 */
	protected function buildArrayOfObjects($result)
	{
		if(!pg_num_rows($result))
			return array();

		$objects = array();
		//get the name of the class of $this object
		$class_name = get_class($this);
		for($i=0; $i<pg_num_rows($result); $i++) {
			// create a clone of $this object, not just having the same class, but also the same variables
			// (in case $this object was extended previously)
			$clone = new $class_name;
			$clone->_model = $this->_model;
			foreach(pg_fetch_array($result,$i) as $var_name=>$value) 
				$clone->{$var_name} = stripslashes($value);
			$objects[$i] = $clone;
		} 
		return $objects;
	}

	/**
	 * Write a log entry in the database coresponding to a certain operation
	 * Note!! Only insert, delete, update queries are logged
	 * Other operations should be implemented in the classes or directly in the code
	 */
	static function writeLog($log, $query = NULL)
	{
		global $enable_logging;

		if($enable_logging !== true && $enable_logging != "yes" && $enable_logging != "on")
			return;
		// it's important that the next line is placed here
		// in case no object was created then self::$_performers won't be setted
		// self::$_performers is set when the first object derived from model is created
		$actionlog = new ActionLog;
		$performers = self::$_performers;
		$object = '';
		$performer_id = '';
		$performer = '';
		foreach($performers as $object_name=>$performing_columns)
		{
			// check that the necessary fields were defined correctly 
			if(!isset($performing_columns["performer"]) || !isset($performing_columns["performer_id"]))
				continue;
			if($object != '')
				$object .= ",";
			$object .= $object_name;
			$perf_id = (isset($_SESSION[$performing_columns["performer_id"]])) ? $_SESSION[$performing_columns["performer_id"]] : '';
			$perf = (isset($_SESSION[$performing_columns["performer"]])) ? $_SESSION[$performing_columns["performer"]] : '';
			if($performer_id != '')
				$performer_id .= ',';
			$performer_id .= $perf_id;
			if($performer != '')
				$performer .= ',';
			$performer .= $perf;
		}
		$actionlog->date = "now()";
		$actionlog->log = $log;
		$actionlog->performer_id = $performer_id;
		$actionlog->performer = $performer;
		$actionlog->object = $object;
		$actionlog->query = $query;
		// insert  the log entry whitout trying to retrive the id and without going into a loop of inserting log for log
		$actionlog->insert(false,false);
	}

	/**
	 * Verify if an object is a performer or not
	 * This function should be reimplemented in the classes that you wish to mark as performers
	 * Example: for class User, function should return array("performer_id"=>"user_id", "performer"=>"username"), "performer_id" and "performer" are constants and their values will be taken from the coresponding variables kept in $_SESSION: user_id and "username"
	 * @return Bool false
	 */
	protected function isPerformer()
	{
		return false;
	}

	/**
	 * Get the name of the object that should be used when writting logs
	 * This function returns the class of the object. If other name is desired one should reimplement it in the derived classes
	 * @return Name to be used when writting logs for this object 
	 */
	public function getNameInLogs()
	{
		return get_class($this);
	}
}

// Default class used for logging 
class ActionLog extends Model
{
	public static function variables()
	{
		return array(
					"date" => new Variable("timestamp"),
					"log" => new Variable("text"), // log in human readable form meant to be displayed
					"performer_id" => new Variable("text"), // id of the one performing the action (taken from $_SESSION)
					"performer" => new Variable("text"), // name of the one performing the action (taken from $_SESSION)
					"object" => new Variable("text"),  // name of class that was marked as performer for actions
					"query" => new Variable("text") //query that was performed
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	public static function index()
	{
		return array(
					"date"
				);
	}
}
?>
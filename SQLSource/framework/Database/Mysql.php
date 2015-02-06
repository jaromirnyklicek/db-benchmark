<?php
/**
 * MySQL Database Driver
 *
 *
 * @package    Database
 * @author	   Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team, upravil Ondrej Novak
 * @license    http://kohanaphp.com/license.html
 *
 *
 *
 * $config = array(
 *(
	'benchmark'		=> TRUE,
	'persistent'	=> FALSE,
	'connection'	=> array
	(
		'type'	   => 'mysql',
		'user'	   => 'dbuser',
		'pass'	   => 'p@ssw0rd',
		'host'	   => 'localhost',
		'port'	   => FALSE,
		'socket'   => FALSE,
		'database' => 'test'
	),
	'character_set' => 'utf8',
	'table_prefix'	=> '',
	'object'		=> TRUE,
	'cache'			=> FALSE,
	'escape'		=> TRUE
);
*/

class Database_Mysql_Driver extends Database_Driver {

	/**
	 * Database connection link
	 */
	protected $link;

	/**
	 * Database configuration
	 */
	protected $db_config;

	/**
	 * Sets the config for the class.
	 *
	 * @param  array  database configuration
	 */
	public function __construct($config)
	{
		$this->db_config = $config;

		//Log::log('debug', 'MySQL Database Driver Initialized');
	}

	/**
	 * Closes the database connection.
	 */
	public function __destruct()
	{
		is_resource($this->link) and mysql_close($this->link);
	}

	public function connect($force = false)
	{
		// Check if link already exists
		if (!$force && is_resource($this->link))
			return $this->link;

		// Import the connect variables
		extract($this->db_config['connection']);

		// Persistent connections enabled?
		$connect = ($this->db_config['persistent'] == TRUE) ? 'mysql_pconnect' : 'mysql_connect';

		// Build the connection info
		$host = isset($host) ? $host : $socket;
		$port = isset($port) ? ':'.$port : '';



		// Make the connection and select the database
		if (($this->link = $connect($host.$port, $user, $pass, TRUE)) AND mysql_select_db($database, $this->link))
		{
			if ($charset = $this->db_config['character_set'])
			{
				$this->set_charset($charset);
			}

			$this->query('SET sql_auto_is_null = 0');

			return $this->link;
		}

		return FALSE;
	}

	public function query($sql)
	{
		// Only cache if it's turned on, and only cache if it's not a write statement
		if ($this->db_config['cache'] AND ! preg_match('#\b(?:INSERT|UPDATE|REPLACE|SET)\b#i', $sql))
		{
			$hash = $this->query_hash($sql);

			if ( ! isset(self::$query_cache[$hash]))
			{
				// Set the cached object
				self::$query_cache[$hash] = new Mysql_Result(mysql_query($sql, $this->link), $this->link, $this->db_config['object'], $sql);
			}

			// Return the cached query
			return self::$query_cache[$hash];
		}
		return new Mysql_Result(mysql_query($sql, $this->link), $this->link, $this->db_config['object'], $sql);
	}

	public function set_charset($charset)
	{
		$this->query('SET NAMES '.$this->escape_str($charset));
	}

	public function escape_table($table)
	{
		if (!$this->db_config['escape'])
			return $table;

		if (stripos($table, ' AS ') !== FALSE)
		{
			// Force 'AS' to uppercase
			$table = str_ireplace(' AS ', ' AS ', $table);

			// Runs escape_table on both sides of an AS statement
			$table = array_map(array($this, __FUNCTION__), explode(' AS ', $table));

			// Re-create the AS statement
			return implode(' AS ', $table);
		}
		return '`'.str_replace('.', '`.`', $table).'`';
	}

	public function escape_column($column)
	{
		if (!$this->db_config['escape'])
			return $column;

		if (strtolower($column) == 'count(*)' OR $column == '*')
			return $column;

		// This matches any modifiers we support to SELECT.
		if ( ! preg_match('/\b(?:rand|all|distinct(?:row)?|high_priority|sql_(?:small_result|b(?:ig_result|uffer_result)|no_cache|ca(?:che|lc_found_rows)))\s/i', $column))
		{
			if (stripos($column, ' AS ') !== FALSE)
			{
				// Force 'AS' to uppercase
				$column = str_ireplace(' AS ', ' AS ', $column);

				// Runs escape_column on both sides of an AS statement
				$column = array_map(array($this, __FUNCTION__), explode(' AS ', $column));

				// Re-create the AS statement
				return implode(' AS ', $column);
			}

			return preg_replace('/[^.*]+/', '`$0`', $column);
		}

		$parts = explode(' ', $column);
		$column = '';

		for ($i = 0, $c = count($parts); $i < $c; $i++)
		{
			// The column is always last
			if ($i == ($c - 1))
			{
				$column .= preg_replace('/[^.*]+/', '`$0`', $parts[$i]);
			}
			else // otherwise, it's a modifier
			{
				$column .= $parts[$i].' ';
			}
		}
		return $column;
	}

	public function regex($field, $match = '', $type = 'AND ', $num_regexs)
	{
		$prefix = ($num_regexs == 0) ? '' : $type;

		return $prefix.' '.$this->escape_column($field).' REGEXP \''.$this->escape_str($match).'\'';
	}

	public function notregex($field, $match = '', $type = 'AND ', $num_regexs)
	{
		$prefix = $num_regexs == 0 ? '' : $type;

		return $prefix.' '.$this->escape_column($field).' NOT REGEXP \''.$this->escape_str($match) . '\'';
	}

	public function merge($table, $keys, $values)
	{
		// Escape the column names
		foreach ($keys as $key => $value)
		{
			$keys[$key] = $this->escape_column($value);
		}
		return 'REPLACE INTO '.$this->escape_table($table).' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).')';
	}

	public function limit($limit, $offset = 0)
	{
		return 'LIMIT '.$offset.', '.$limit;
	}

	public function compile_select($database)
	{
		$sql = 'SELECT ';
		if($database['distinct']) $sql .= ' DISTINCT ';
		if($database['calc_found_rows'] === true) $sql .= ' SQL_CALC_FOUND_ROWS ';
		$sql .= (count($database['select']) > 0) ? implode(', ', $database['select']) : '*';

		if (count($database['from']) > 0)
		{
			// Escape the tables
			$froms = array();
			foreach ($database['from'] as $from)
			{
				$froms[] = $this->escape_column($from);
			}
			$sql .= "\nFROM ";
			$sql .= implode(', ', $froms);
		}

		if (count($database['join']) > 0)
		{
			foreach($database['join'] AS $join)
			{
				$sql .= "\n".$join['type'].'JOIN '.implode(', ', $join['tables']).' ON '.$join['conditions'];
			}
		}

		if (count($database['where']) > 0)
		{
			$sql .= "\nWHERE ";
		}

		$sql .= implode("\n", $database['where']);

		if (count($database['groupby']) > 0)
		{
			$sql .= "\nGROUP BY ";
			$sql .= implode(', ', $database['groupby']);
		}

		if (count($database['having']) > 0)
		{
			$sql .= "\nHAVING ";
			$sql .= implode("\n", $database['having']);
		}

		if (count($database['orderby']) > 0)
		{
			$sql .= "\nORDER BY ";
			$sql .= implode(', ', $database['orderby']);
		}

		if (is_numeric($database['limit']))
		{
			$sql .= "\n";
			$sql .= $this->limit($database['limit'], $database['offset']);
		}

		return $sql;
	}

	public function escape_str($str)
	{
		if (!$this->db_config['escape'])
			return $str;

		is_resource($this->link) or $this->connect();

		return mysql_real_escape_string($str, $this->link);
	}

	public function list_tables(Database $db)
	{
		static $tables;

		if (empty($tables) AND $query = $db->query('SHOW TABLES FROM '.$this->escape_table($this->db_config['connection']['database'])))
		{
			foreach ($query->result(FALSE) as $row)
			{
				$tables[] = current($row);
			}
		}

		return $tables;
	}

	public function show_error()
	{
		if ($this->link) {
			return mysql_error($this->link);
		}
		else {
			return mysql_error();
		}
	}

	public function list_fields($table)
	{
		static $tables;

		if (empty($tables[$table]))
		{
			foreach ($this->field_data($table) as $row)
			{
				// Make an associative array
				$tables[$table][$row->Field] = $this->sql_type($row->Type);

				if ($row->Key === 'PRI' AND $row->Extra === 'auto_increment')
				{
					// For sequenced (AUTO_INCREMENT) tables
					$tables[$table][$row->Field]['sequenced'] = TRUE;
				}

				if ($row->Null === 'YES')
				{
					// Set NULL status
					$tables[$table][$row->Field]['null'] = TRUE;
				}

				// pridal jsem nacteni i Default hoddnoty
				if (!empty($row->Default)) {
					// Set Default value
					$tables[$table][$row->Field]['default'] = $row->Default;
				}
			}
		}

		if (!isset($tables[$table]))
			throw new Database_Exception(sprintf('Table %s does not exist in your database.', $table));

		return $tables[$table];
	}

	public function field_data($table)
	{
		$columns = array();

		if ($query = mysql_query('SHOW COLUMNS FROM '.$this->escape_table($table), $this->link))
		{
			if (mysql_num_rows($query) > 0)
			{
				while ($row = mysql_fetch_object($query))
				{
					$columns[] = $row;
				}
			}
		}

		return $columns;
	}

} // End Database_Mysql_Driver Class

/**
 * MySQL Result
 */
class Mysql_Result extends Database_Result {

	// Fetch function and return type
	protected $fetch_type  = 'mysql_fetch_object';
	protected $return_type = MYSQL_ASSOC;

	/**
	 * Sets up the result variables.
	 *
	 * @param  resource  query result
	 * @param  resource  database link
	 * @param  boolean	 return objects or arrays
	 * @param  string	 SQL query that was run
	 */
	public function __construct($result, $link, $object = TRUE, $sql)
	{
		$this->result = $result;
		// If the query is a resource, it was a SELECT, SHOW, DESCRIBE, EXPLAIN query
		if (is_resource($result))
		{
			$this->current_row = 0;
			$this->total_rows  = mysql_num_rows($this->result);
			$this->fetch_type = ($object === TRUE) ? 'mysql_fetch_object' : 'mysql_fetch_array';
			if(strpos($sql, 'SQL_CALC_FOUND_ROWS'))
			{
				$dbRes = mysql_query('SELECT FOUND_ROWS()', $link);
				$dbRow = mysql_fetch_array($dbRes);
				$this->found_rows = $dbRow[0];
			}
		}
		elseif (is_bool($result))
		{
			if ($result == FALSE)
			{
				// SQL error
				$err = sprintf('There was SQL error: %s', mysql_error($link).' - '.$sql);
				$code = mysql_errno($link);
				if ($code == 1452) {
						// Cannot add or update a child row: a foreign key constraint fails
						$e = new DatabaseConstraintFails($err, $code);
				}
				elseif ($code == 1451) {
						// Cannot delete or update a parent row: a foreign key constraint fails
						$e = new DatabaseConstraintFails($err, $code);
				}
				else {
					$e = new Database_Exception($err, $code);
				}

				// kaskadni referencni integrita se neloguje +
				// Cannot add or update a child row: a foreign key constraint fails
				if($code != 1451 && $code != 1452) {
					log::write(MessageLogger::ERROR , $err);
					Debug::processException($e, FALSE, FALSE);
				}
				throw $e;
			}
			else
			{
				// Its an DELETE, INSERT, REPLACE, or UPDATE query
				$this->insert_id  = mysql_insert_id($link);
				$this->total_rows = mysql_affected_rows($link);
			}
		}

		// Set result type
		$this->result($object);

		// Store the SQL
		$this->sql = $sql;
	}

	/**
	 * Destruct, the cleanup crew!
	 */
	public function __destruct()
	{
		if (is_resource($this->result))
		{
			mysql_free_result($this->result);
		}
	}

	public function isOk()
	{
		return $this->result != FALSE;
	}

	public function result($object = TRUE, $type = MYSQL_ASSOC)
	{
		$this->fetch_type = ((bool) $object) ? 'mysql_fetch_object' : 'mysql_fetch_array';

		// This check has to be outside the previous statement, because we do not
		// know the state of fetch_type when $object = NULL
		// NOTE - The class set by $type must be defined before fetching the result,
		// autoloading is disabled to save a lot of stupid overhead.
		if ($this->fetch_type == 'mysql_fetch_object' AND $object === TRUE)
		{
			$this->return_type = (is_string($type)) ? $type : 'stdClass';
		}
		else
		{
			$this->return_type = $type;
		}

		return $this;
	}

	public function as_array($object = NULL, $type = MYSQL_ASSOC)
	{
		return $this->result_array($object, $type);
	}

	public function result_array($object = NULL, $type = MYSQL_ASSOC)
	{
		$rows = array();

		if (is_string($object))
		{
			$fetch = $object;
		}
		elseif (is_bool($object))
		{
			if ($object === TRUE)
			{
				$fetch = 'mysql_fetch_object';

				$type = (is_string($type)) ? $type : 'stdClass';
			}
			else
			{
				$fetch = 'mysql_fetch_array';
			}
		}
		else
		{
			// Use the default config values
			$fetch = $this->fetch_type;

			if ($fetch == 'mysql_fetch_object')
			{
				$type = (is_string($this->return_type)) ? $this->return_type : 'stdClass';
			}
		}

		if (mysql_num_rows($this->result))
		{
			// Reset the pointer location to make sure things work properly
			mysql_data_seek($this->result, 0);
			while ($row = $fetch($this->result, $type))
			{
				$rows[] = $row;
			}
		}

		return isset($rows) ? $rows : array();
	}

	public function list_fields()
	{
		$field_names = array();
		while ($field = mysql_fetch_field($this->result))
		{
			$field_names[] = $field->name;
		}

		return $field_names;
	}

	public function seek($offset)
	{
		if ($this->offsetExists($offset) AND mysql_data_seek($this->result, $offset))
		{
			// Set the current row to the offset
			$this->current_row = $offset;

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

} // End Mysql_Result Class

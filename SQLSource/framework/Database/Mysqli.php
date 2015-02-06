<?php
/**
 * Adapter pro pripojeni k mysql pomoci mysqli extension.
 *
 * Vytvoreno dle mysql driveru z Core
 *
 * @author     Petr Pliska <petr.pliska@viaaurea.cz>
 * @copyright  Copyright Via Aurea s. r. o.
*/

class Database_Mysqli_Driver extends \Database_Driver
{
	/** Defaultni mysql port  */
	const DEFAULT_MYSQL_PORT = 3306;

	/**
	 * @var Databázové připojení
	 */
	protected $dbConnection;

	/**
	 * @var Konfigirace pro připojení
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
	}

	/**
     * Uzavre db pripojeni
	 */
	public function __destruct()
	{
		if($this->dbConnection !== NULL)  {
			$this->dbConnection->close();
		}
	}

	public function connect($force = false)
	{
		/*
			Pokud je db již připojena, jen vratime instanci pripojeni
		*/
		if (!$force && $this->dbConnection !== NULL) {
			return $this->dbConnection;
		}

		/*
			Ziskani konfigurace pro pripojeni
		 */
		$connectionConfiguration = $this->db_config['connection'];

		/*
			Pokud chceme vyuzivate persistentni pripojeni je treba pripojit prefix "p:" před hostname
		*/
		if ($this->db_config['persistent'] == TRUE) {
			$connectionConfiguration['host'] = 'p:' . $connectionConfiguration['host'];
		}

		/*
			Pripojeni k db
		 */
		$this->dbConnection = new Mysqli(
			$connectionConfiguration['host'],
			$connectionConfiguration['user'],
			$connectionConfiguration['pass'],
			$connectionConfiguration['database'],
			$connectionConfiguration['port'] ?: NULL
		);

		/*
			Nastavení znakove sady
		 */
		if ($charset = $this->db_config['character_set'])
		{
			$this->set_charset($charset);
		}

		$this->query('SET sql_auto_is_null = 0');

		return $this->dbConnection;

	}

	public function query($sql)
	{
		/**
		 * Pouze pokud je povolena cache a nejedna se o dotaz, který mění stav databaze
		 */
		if ($this->db_config['cache'] && !preg_match('#\b(?:INSERT|UPDATE|REPLACE|SET)\b#i', $sql))
		{
			$hash = $this->query_hash($sql);

			if (!isset(self::$query_cache[$hash]))
			{
				// Set the cached object
				self::$query_cache[$hash] = new Mysqli_Result_Kohana(mysql_query($this->dbConnection, $sql), $this->dbConnection, $this->db_config['object'], $sql);
			}

			// Return the cached query
			return self::$query_cache[$hash];
		}

		return new Mysqli_Result_Kohana($this->dbConnection->query($sql), $this->dbConnection, $this->db_config['object'], $sql);
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
		if (!$this->db_config['escape']) {
			return $str;
		}

		if(!$this->dbConnection) {
			$this->connect();
		}

		return $this->dbConnection->escape_string($str);
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
		return $this->dbConnection->error;
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

		if ($query = $this->dbConnection->query('SHOW COLUMNS FROM '.$this->escape_table($table)))
		{
			if ($query->num_rows > 0)
			{
				while ($row = $query->fetch_object())
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
class Mysqli_Result_Kohana extends Database_Result {

	// Fetch function and return type
	protected $fetch_type  = 'fetch_object';
	protected $return_type = MYSQLI_ASSOC;

	/**
	 * Sets up the result variables.
	 *
	 * @param  resource  query result
	 * @param  resource  database dbConnection
	 * @param  boolean	 return objects or arrays
	 * @param  string	 SQL query that was run
	 */
	public function __construct($result, $dbConnection, $object = TRUE, $sql)
	{
		$this->result = $result;

		// If the query is a resource, it was a SELECT, SHOW, DESCRIBE, EXPLAIN query
		if (is_object($result))
		{
			$this->current_row = 0;
			$this->total_rows  = $this->result->num_rows;
			$this->fetch_type = ($object === TRUE) ? 'fetch_object' : 'fetch_array';
			if(strpos($sql, 'SQL_CALC_FOUND_ROWS'))
			{
				$dbRes = $dbConnection->query('SELECT FOUND_ROWS()');
				$dbRow = $dbRes->fetch_array();
				$this->found_rows = $dbRow[0];
			}
		}
		elseif (is_bool($result))
		{
			if ($result == FALSE)
			{
				// SQL error
				$err = sprintf('There was SQL error: %s', $dbConnection->error.' - '.$sql);
				$code = $dbConnection->errno;
				$e = new Database_Exception($err, $code);

				// if($code != 1451) { // kaskadni referencni integrita se neloguje
				// 	log::write(MessageLogger::ERROR , $err);
				// 	Debug::processException($e);
				// }
				throw $e;
			}
			else
			{
				// Its an DELETE, INSERT, REPLACE, or UPDATE query
				$this->insert_id  = $dbConnection->insert_id;
				$this->total_rows = $dbConnection->affected_rows;
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
			$this->result->close();
		}
	}

	public function isOk()
	{
		return $this->result != FALSE;
	}

	public function result($object = TRUE, $type = MYSQLI_ASSOC)
	{
		$this->fetch_type = ((bool) $object) ? 'fetch_object' : 'fetch_array';

		// This check has to be outside the previous statement, because we do not
		// know the state of fetch_type when $object = NULL
		// NOTE - The class set by $type must be defined before fetching the result,
		// autoloading is disabled to save a lot of stupid overhead.
		if ($this->fetch_type == 'fetch_object' AND $object === TRUE)
		{
			$this->return_type = (is_string($type)) ? $type : 'stdClass';
		}
		else
		{
			$this->return_type = $type;
		}

		return $this;
	}

	public function as_array($object = NULL, $type = MYSQLI_ASSOC)
	{
		return $this->result_array($object, $type);
	}

	public function result_array($object = NULL, $type = MYSQLI_ASSOC)
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
				$fetch = 'fetch_object';

				$type = (is_string($type)) ? $type : 'stdClass';
			}
			else
			{
				$fetch = 'fetch_array';
			}
		}
		else
		{
			// Use the default config values
			$fetch = $this->fetch_type;

			if ($fetch == 'fetch_object')
			{
				$type = (is_string($this->return_type)) ? $this->return_type : 'stdClass';
			}
		}

		if ($this->result->num_rows)
		{
			// Reset the pointer location to make sure things work properly
			$this->result->data_seek(0);

			while ($row = $this->result->{$fetch}($type))
			{
				$rows[] = $row;
			}
		}

		return isset($rows) ? $rows : array();
	}

	public function list_fields()
	{
		$field_names = array();
		while ($field = $this->result->fetch_field())
		{
			$field_names[] = $field->name;
		}

		return $field_names;
	}

	public function seek($offset)
	{
		if ($this->offsetExists($offset) AND $this->result->data_seek($offset))
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

	/**
	 * ArrayAccess: offsetGet
	 */
	public function offsetGet($offset)
	{
		if ( ! $this->seek($offset))
			return FALSE;

		// Return the row by calling the defined fetching callback
		return $this->result->{$this->fetch_type}($this->return_type);
	}

} // End Mysql_Result Class
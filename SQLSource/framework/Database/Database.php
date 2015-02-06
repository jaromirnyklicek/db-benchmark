<?php
// Define database error constant
define('E_DATABASE_ERROR', 44);

/**
 * Provides database access in a platform agnostic way, using simple query building blocks.
 *
 * $Id: Database.php 3796 2008-12-17 02:36:17Z zombor $
 *
 * @package    Database
 * @author	   Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team, upravy Ondrej Novak
 * @license    http://kohanaphp.com/license.html
 */
class Database {

	/** Maximalni delka firebug logu */
	const MAX_FIREBUG_DATA_LOG = 16384;

	/** @var array */
	public static $table = array(array('Time', 'SQL Statement', 'Rows'));

	/** @var array */
	public static $totalTime = 0;
	/** @var array */
	public static $numQueries = 0;

	/** Posilani dotazu do Firebugu **/
	public static $useFirebug = FALSE;

	/** Pocet dat odeslanych do firebugu **/
	public static $firebugLogDataQuery = 0;



	// Database instances
	public static $instances = array();

	// Global benchmark
	public static $benchmarks = array();

	/** callback event po sql dotazu pro ucely na logovani **/
	public static $onQuery = NULL;

	// Configuration
	protected $config = array
	(
		'benchmark'		=> FALSE,
		'persistent'	=> FALSE,
		'connection'	=> '',
		'character_set' => 'utf8',
		'table_prefix'	=> '',
		'object'		=> TRUE,
		'cache'			=> FALSE,
		'escape'		=> TRUE,
		'type'			=> 'mysql',
	);

	// Database driver object
	protected $driver;
	protected $link;

	// Un-compiled parts of the SQL query
	protected $select	  = array();
	protected $set		  = array();
	protected $from		  = array();
	protected $join		  = array();
	protected $where	  = array();
	protected $orderby	  = array();
	protected $order	  = array();
	protected $groupby	  = array();
	protected $having	  = array();
	protected $distinct   = FALSE;
	protected $limit	  = FALSE;
	protected $offset	  = FALSE;
	public $calc_found_rows = null;
	protected $last_query = '';

	// Stack of queries for push/pop
	protected $query_history = array();

	/**
	 * Returns a singleton instance of Database.
	 *
	 * @param	mixed	configuration array or DSN
	 * @return	Database
	 */
	public static function & instance($name = 'default', $config = NULL)
	{
		if ( ! isset(Database::$instances[$name]))
		{
			// Create a new instance
			Database::$instances[$name] = new Database($config);
		}

		return Database::$instances[$name];
	}

	/**
	 * Vrati defaultni instanci
	 *
	 * @return	Database
	 */
	public static function & singleton()
	{
		return Database::instance();
	}

	/**
	 * Returns the name of a given database instance.
	 *
	 * @param	Database  instance of Database
	 * @return	string
	 */
	public static function instance_name(Database $db)
	{
		return array_search($db, Database::$instances, TRUE);
	}

	/**
	 * Sets up the database configuration, loads the Database_Driver.
	 *
	 * @throws	Database_Exception
	 */
	public function __construct($config = array())
	{
		if (class_exists(/*Nette::*/'Debug', FALSE)) {
			/*Nette::*/Debug::addColophon(array('Database', 'getColophon'));
		}

		if (empty($config))
		{
			// Load the default group
			 $config = array(
				'benchmark'		=> FALSE,
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
			$project_config = Environment::getConfig();

			$dbconfig = $project_config['database'];


			if (is_object($dbconfig)) {
				if (isset($dbconfig['dsn']) && strpos($dbconfig['dsn'], '://') !== FALSE) {
					/*
						Pokud konfigurace obsahuje indes dsn a dany index a obsahuje :// jedna se o konfiguraci pres DSN string.
					*/
					$config['connection'] = $this->parseDsnString($dbconfig['dsn']);
				} else {
					/*
						Pokud je konfigurace objeckt(Konfigurace u vsech projektu doposud)
					*/
					$config['connection']['type'] = $dbconfig['type'];
					$config['connection']['user'] = $dbconfig['username'];
					$config['connection']['pass'] = $dbconfig['password'];
					$config['connection']['host'] = $dbconfig['host'];
					$config['connection']['port'] = $dbconfig['port'];
					$config['connection']['database'] = $dbconfig['db'];
				}
			}

			$config['table_prefix'] = $dbconfig['table_prefix'];

			$config['cache'] = $dbconfig['cache'];
		}
		elseif (is_array($config) AND count($config) > 0)
		{
			if ( ! array_key_exists('connection', $config))
			{
				$config = array('connection' => $config);
			}
		}
		elseif (is_string($config))
		{
			// Konfigurace pomoci DSN stringu
			if (strpos($config, '://') !== FALSE)
			{
				$dsn                  = $config;
				$config               = array();
				$config['connection'] = $this->parseDsnString($dsn);
			}

			else {
				throw new Exception('Neznamy typ konfigurace.');
			}

		}

		// Merge the default config with the passed config
		$this->config = array_merge($this->config, $config);
		if(!isset($this->config['connection']['type'])) $this->config['connection']['type'] = $this->config['type'];
		if (is_string($this->config['connection']))
		{
			// Make sure the connection is valid
			if (strpos($this->config['connection'], '://') === FALSE)
				throw new Database_Exception(sprintf('The DSN you supplied is not valid: %s', $this->config['connection']));

			// Parse the DSN, creating an array to hold the connection parameters
			$db = array
			(
				'type'	   => FALSE,
				'user'	   => FALSE,
				'pass'	   => FALSE,
				'host'	   => FALSE,
				'port'	   => FALSE,
				'socket'   => FALSE,
				'database' => FALSE
			);

			// Get the protocol and arguments
			list ($db['type'], $connection) = explode('://', $this->config['connection'], 2);

			if (strpos($connection, '@') !== FALSE)
			{
				// Get the username and password
				list ($db['pass'], $connection) = explode('@', $connection, 2);
				// Check if a password is supplied
				$logindata = explode(':', $db['pass'], 2);
				$db['pass'] = (count($logindata) > 1) ? $logindata[1] : '';
				$db['user'] = $logindata[0];

				// Prepare for finding the database
				$connection = explode('/', $connection);

				// Find the database name
				$db['database'] = array_pop($connection);

				// Reset connection string
				$connection = implode('/', $connection);

				// Find the socket
				if (preg_match('/^unix\([^)]++\)/', $connection))
				{
					// This one is a little hairy: we explode based on the end of
					// the socket, removing the 'unix(' from the connection string
					list ($db['socket'], $connection) = explode(')', substr($connection, 5), 2);
				}
				elseif (strpos($connection, ':') !== FALSE)
				{
					// Fetch the host and port name
					list ($db['host'], $db['port']) = explode(':', $connection, 2);
				}
				else
				{
					$db['host'] = $connection;
				}
			}
			else
			{
				// File connection
				$connection = explode('/', $connection);

				// Find database file name
				$db['database'] = array_pop($connection);

				// Find database directory name
				$db['socket'] = implode('/', $connection).'/';
			}

			// Reset the connection array to the database config
			$this->config['connection'] = $db;
		}
		// Set driver name
		$driver = 'Database_'.ucfirst($this->config['connection']['type']).'_Driver';

		// Initialize the driver
		$this->driver = new $driver($this->config);

		// Validate the driver
		if ( ! ($this->driver instanceof Database_Driver))
			throw new Database_Exception('core.driver_implements', $this->config['connection']['type'], get_class($this), 'Database_Driver');

		//Kohana::log('debug', 'Database Library initialized');
	}

	/**
	 * @param string $dsnString DSN konfiguracni retezec
	 *
	 * @return array pole
	 */
	protected function parseDsnString($dsnString)
	{
		$parsedDsnString             = parse_url($dsnString);
		$parsedDsnString['type']     = $parsedDsnString['scheme'];
		$parsedDsnString['database'] = str_replace('/', '', $parsedDsnString['path']);

		return $parsedDsnString;
	}

	/**
	 * Simple connect method to get the database queries up and running.
	 * @param	bool $force Vynuti pripojeni i kdyz existuje. (Stane se, ze je pripojenu a uplyne timeout
	 *						a je potreba pripojit znova)
	 * @return	void
	 */
	public function connect($force = true)
	{
		// A link can be a resource or an object
		if ($force || (!is_resource($this->link) && !is_object($this->link)))
		{

			$this->link = @$this->driver->connect($force);

			if ( ! is_resource($this->link) AND ! is_object($this->link))
			{
				throw new Database_Exception('database.connection', $this->driver->show_error());
			}
		}
		return $this->link;
	}

	public function disconnect()
	{
		// A link can be a resource or an object
		if ((is_resource($this->link) || is_object($this->link)))
		{

			$this->driver->__destruct();
		}
	}

	/**
	 * Runs a query into the driver and returns the result.
	 *
	 * @param	string	SQL query to execute
	 * @return	Database_Result
	 */
	public function query($sql = '')
	{
		if ($sql == '') return FALSE;

		// No link? Connect!
		$this->link or $this->connect();


		if (func_num_args() > 1) //if we have more than one argument ($sql)
		{
			$argv = func_get_args();
			$binds = (is_array(next($argv))) ? current($argv) : array_slice($argv, 1);
		}

		// Compile binds if needed
		if (isset($binds))
		{
			$sql = $this->compile_binds($sql, $binds);
		}
		// Start the benchmark
		$start = microtime(TRUE);

		// Fetch the result
		$result = $this->driver->query($this->last_query = $sql);

		// Stop the benchmark
		$stop = microtime(TRUE);
		$time = $stop - $start;
		self::$totalTime += $time;
		self::$numQueries++;

		$this->logQuery($time, $sql, $result);

		if ($this->config['benchmark'] == TRUE)
		{
			// Benchmark the query
			self::$benchmarks[] = array('query' => $sql, 'time' => $stop - $start, 'rows' => count($result));
		}

		return $result;
	}

	public function logQuery($time, $sql, $result)
	{
		if (self::$onQuery) {
			call_user_func_array(self::$onQuery, array($sql, $result, $time, $this));
		}

		if(self::$useFirebug && self::$firebugLogDataQuery < self::MAX_FIREBUG_DATA_LOG - strlen($sql)) {
			self::$firebugLogDataQuery += strlen($sql);

			self::$table[] = array(
				sprintf('%0.3f', ($time) * 1000).' ms',
				trim($sql),
				$result instanceof Database_Result ? count($result) : '-'
			);

			header('X-Wf-Protocol-dibi: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
			header('X-Wf-dibi-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');
			header('X-Wf-dibi-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');

			$payload = array(
				array(
					'Type' => 'TABLE',
					'Label' => 'Database profiler ('.self::$numQueries.' dotazů zabralo '.sprintf('%0.3f', (self::$totalTime) * 1000).' ms)',
				),
				self::$table,
			);
			$payload = @json_encode($payload);
			foreach (str_split($payload, 4990) as $num => $s) {
				$num++;
				header("X-Wf-dibi-1-1-d$num: |$s|\\"); // protocol-, structure-, plugin-, message-index
			}
			header("X-Wf-dibi-1-1-d$num: |$s|");
		}
	}

	/**
	 * Selects the column names for a database query.
	 *
	 * @param	string	string or array of column names to select
	 * @return	Database_Core  This Database object.
	 */
	public function select($sql = '*')
	{
		if (func_num_args() > 1)
		{
			$sql = func_get_args();
		}
		elseif (is_string($sql))
		{
			$sql = explode(',', $sql);
		}
		else
		{
			$sql = (array) $sql;
		}

		foreach ($sql as $val)
		{
			if (($val = trim($val)) === '') continue;

			if (strpos($val, '(') === FALSE AND $val !== '*')
			{
				if (preg_match('/^DISTINCT\s++(.+)$/i', $val, $matches))
				{
					// Only prepend with table prefix if table name is specified
					$val = (strpos($matches[1], '.') !== FALSE) ? $this->config['table_prefix'].$matches[1] : $matches[1];
					$this->distinct = TRUE;
				}
				else
				{
					$val = (strpos($val, '.') !== FALSE) ? $this->config['table_prefix'].$val : $val;
				}

				$val = $this->driver->escape_column($val);
			}

			$this->select[] = $val;
		}

		return $this;
	}

	/**
	 * Selects the from table(s) for a database query.
	 *
	 * @param	string	string or array of tables to select
	 * @return	Database_Core  This Database object.
	 */
	public function from($sql)
	{
		if (func_num_args() > 1)
		{
			$sql = func_get_args();
		}
		elseif (is_string($sql))
		{
			$sql = explode(',', $sql);
		}
		else
		{
			$sql = array($sql);
		}

		foreach ($sql as $val)
		{
			if (($val = trim($val)) === '') continue;

			if (is_string($val))
			{
				// TODO: Temporary solution, this should be moved to database driver (AS is checked for twice)
				if (stripos($val, ' AS ') !== FALSE)
				{
					$val = str_ireplace(' AS ', ' AS ', $val);

					list($table, $alias) = explode(' AS ', $val);

					// Attach prefix to both sides of the AS
					$val = $this->config['table_prefix'].$table.' AS '.$this->config['table_prefix'].$alias;
				}
				else
				{
					$val = $this->config['table_prefix'].$val;
				}
			}

			$this->from[] = $val;
		}

		return $this;
	}

	/**
	 * Generates the JOIN portion of the query.
	 *
	 * @param	string		  table name
	 * @param	string|array  where key or array of key => value pairs
	 * @param	string		  where value
	 * @param	string		  type of join
	 * @return	Database_Core		 This Database object.
	 */
	public function join($table, $key, $value = NULL, $type = '')
	{
		$join = array();

		if ( ! empty($type))
		{
			$type = strtoupper(trim($type));

			if ( ! in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'), TRUE))
			{
				$type = '';
			}
			else
			{
				$type .= ' ';
			}
		}

		$cond = array();
		$keys  = is_array($key) ? $key : array($key => $value);
		foreach ($keys as $key => $value)
		{
			$key	= (strpos($key, '.') !== FALSE) ? $this->config['table_prefix'].$key : $key;

			if (is_string($value))
			{
				// Only escape if it's a string
				$value = $this->driver->escape_column($this->config['table_prefix'].$value);
			}

			$cond[] = $this->driver->where($key, $value, 'AND ', count($cond), FALSE);
		}

		if ( ! is_array($this->join))
		{
			$this->join = array();
		}

		if ( ! is_array($table))
		{
			$table = array($table);
		}

		foreach ($table as $t)
		{
			if (is_string($t))
			{
				// TODO: Temporary solution, this should be moved to database driver (AS is checked for twice)
				if (stripos($t, ' AS ') !== FALSE)
				{
					$t = str_ireplace(' AS ', ' AS ', $t);

					list($table, $alias) = explode(' AS ', $t);

					// Attach prefix to both sides of the AS
					$t = $this->config['table_prefix'].$table.' AS '.$this->config['table_prefix'].$alias;
				}
				else
				{
					$t = $this->config['table_prefix'].$t;
				}
			}

			$join['tables'][] = $this->driver->escape_column($t);
		}

		$join['conditions'] = '('.trim(implode(' ', $cond)).')';
		$join['type'] = $type;

		$this->join[] = $join;

		return $this;
	}


	/**
	 * Selects the where(s) for a database query.
	 *
	 * @param	string|array  key name or array of key => value pairs
	 * @param	string		  value to match with key
	 * @param	boolean		  disable quoting of WHERE clause
	 * @return	Database_Core		 This Database object.
	 */
	public function where($key, $value = NULL, $quote = TRUE)
	{
		$quote = (func_num_args() < 2 AND ! is_array($key)) ? -1 : $quote;
		$keys  = is_array($key) ? $key : array($key => $value);

		foreach ($keys as $key => $value)
		{
			$key		   = (strpos($key, '.') !== FALSE) ? $this->config['table_prefix'].$key : $key;
			$this->where[] = $this->driver->where($key, $value, 'AND ', count($this->where), $quote);
		}
		return $this;
	}

	/**
	 * Selects the or where(s) for a database query.
	 *
	 * @param	string|array  key name or array of key => value pairs
	 * @param	string		  value to match with key
	 * @param	boolean		  disable quoting of WHERE clause
	 * @return	Database_Core		 This Database object.
	 */
	public function orwhere($key, $value = NULL, $quote = TRUE)
	{
		$quote = (func_num_args() < 2 AND ! is_array($key)) ? -1 : $quote;
		$keys  = is_array($key) ? $key : array($key => $value);

		foreach ($keys as $key => $value)
		{
			$key		   = (strpos($key, '.') !== FALSE) ? $this->config['table_prefix'].$key : $key;
			$this->where[] = $this->driver->where($key, $value, 'OR ', count($this->where), $quote);
		}

		return $this;
	}

	/**
	 * Selects the like(s) for a database query.
	 *
	 * @param	string|array  field name or array of field => match pairs
	 * @param	string		  like value to match with field
	 * @param	boolean		  automatically add starting and ending wildcards
	 * @return	Database_Core		 This Database object.
	 */
	public function like($field, $match = '', $auto = TRUE)
	{
		$fields = is_array($field) ? $field : array($field => $match);

		foreach ($fields as $field => $match)
		{
			$field		   = (strpos($field, '.') !== FALSE) ? $this->config['table_prefix'].$field : $field;
			$this->where[] = $this->driver->like($field, $match, $auto, 'AND ', count($this->where));
		}

		return $this;
	}

	/**
	 * Selects the or like(s) for a database query.
	 *
	 * @param	string|array  field name or array of field => match pairs
	 * @param	string		  like value to match with field
	 * @param	boolean		  automatically add starting and ending wildcards
	 * @return	Database_Core		 This Database object.
	 */
	public function orlike($field, $match = '', $auto = TRUE)
	{
		$fields = is_array($field) ? $field : array($field => $match);

		foreach ($fields as $field => $match)
		{
			$field		   = (strpos($field, '.') !== FALSE) ? $this->config['table_prefix'].$field : $field;
			$this->where[] = $this->driver->like($field, $match, $auto, 'OR ', count($this->where));
		}

		return $this;
	}

	/**
	 * Selects the not like(s) for a database query.
	 *
	 * @param	string|array  field name or array of field => match pairs
	 * @param	string		  like value to match with field
	 * @param	boolean		  automatically add starting and ending wildcards
	 * @return	Database_Core		 This Database object.
	 */
	public function notlike($field, $match = '', $auto = TRUE)
	{
		$fields = is_array($field) ? $field : array($field => $match);

		foreach ($fields as $field => $match)
		{
			$field		   = (strpos($field, '.') !== FALSE) ? $this->config['table_prefix'].$field : $field;
			$this->where[] = $this->driver->notlike($field, $match, $auto, 'AND ', count($this->where));
		}

		return $this;
	}

	/**
	 * Selects the or not like(s) for a database query.
	 *
	 * @param	string|array  field name or array of field => match pairs
	 * @param	string		  like value to match with field
	 * @return	Database_Core		 This Database object.
	 */
	public function ornotlike($field, $match = '', $auto = TRUE)
	{
		$fields = is_array($field) ? $field : array($field => $match);

		foreach ($fields as $field => $match)
		{
			$field		   = (strpos($field, '.') !== FALSE) ? $this->config['table_prefix'].$field : $field;
			$this->where[] = $this->driver->notlike($field, $match, $auto, 'OR ', count($this->where));
		}

		return $this;
	}

	/**
	 * Selects the like(s) for a database query.
	 *
	 * @param	string|array  field name or array of field => match pairs
	 * @param	string		  like value to match with field
	 * @return	Database_Core		 This Database object.
	 */
	public function regex($field, $match = '')
	{
		$fields = is_array($field) ? $field : array($field => $match);

		foreach ($fields as $field => $match)
		{
			$field		   = (strpos($field, '.') !== FALSE) ? $this->config['table_prefix'].$field : $field;
			$this->where[] = $this->driver->regex($field, $match, 'AND ', count($this->where));
		}

		return $this;
	}

	/**
	 * Selects the or like(s) for a database query.
	 *
	 * @param	string|array  field name or array of field => match pairs
	 * @param	string		  like value to match with field
	 * @return	Database_Core		 This Database object.
	 */
	public function orregex($field, $match = '')
	{
		$fields = is_array($field) ? $field : array($field => $match);

		foreach ($fields as $field => $match)
		{
			$field		   = (strpos($field, '.') !== FALSE) ? $this->config['table_prefix'].$field : $field;
			$this->where[] = $this->driver->regex($field, $match, 'OR ', count($this->where));
		}

		return $this;
	}

	/**
	 * Selects the not regex(s) for a database query.
	 *
	 * @param	string|array  field name or array of field => match pairs
	 * @param	string		  regex value to match with field
	 * @return	Database_Core		 This Database object.
	 */
	public function notregex($field, $match = '')
	{
		$fields = is_array($field) ? $field : array($field => $match);

		foreach ($fields as $field => $match)
		{
			$field		   = (strpos($field, '.') !== FALSE) ? $this->config['table_prefix'].$field : $field;
			$this->where[] = $this->driver->notregex($field, $match, 'AND ', count($this->where));
		}

		return $this;
	}

	/**
	 * Selects the or not regex(s) for a database query.
	 *
	 * @param	string|array  field name or array of field => match pairs
	 * @param	string		  regex value to match with field
	 * @return	Database_Core		 This Database object.
	 */
	public function ornotregex($field, $match = '')
	{
		$fields = is_array($field) ? $field : array($field => $match);

		foreach ($fields as $field => $match)
		{
			$field		   = (strpos($field, '.') !== FALSE) ? $this->config['table_prefix'].$field : $field;
			$this->where[] = $this->driver->notregex($field, $match, 'OR ', count($this->where));
		}

		return $this;
	}

	/**
	 * Chooses the column to group by in a select query.
	 *
	 * @param	string	column name to group by
	 * @return	Database_Core  This Database object.
	 */
	public function groupby($by)
	{
		if ( ! is_array($by))
		{
			$by = explode(',', (string) $by);
		}

		foreach ($by as $val)
		{
			$val = trim($val);

			if ($val != '')
			{
				// Add the table prefix if we are using table.column names
				if(strpos($val, '.'))
				{
					$val = $this->config['table_prefix'].$val;
				}

				$this->groupby[] = $this->driver->escape_column($val);
			}
		}

		return $this;
	}

	/**
	 * Selects the having(s) for a database query.
	 *
	 * @param	string|array  key name or array of key => value pairs
	 * @param	string		  value to match with key
	 * @param	boolean		  disable quoting of WHERE clause
	 * @return	Database_Core		 This Database object.
	 */
	public function having($key, $value = '', $quote = TRUE)
	{
		$this->having[] = $this->driver->where($key, $value, 'AND', count($this->having), TRUE);
		return $this;
	}

	/**
	 * Selects the or having(s) for a database query.
	 *
	 * @param	string|array  key name or array of key => value pairs
	 * @param	string		  value to match with key
	 * @param	boolean		  disable quoting of WHERE clause
	 * @return	Database_Core		 This Database object.
	 */
	public function orhaving($key, $value = '', $quote = TRUE)
	{
		$this->having[] = $this->driver->where($key, $value, 'OR', count($this->having), TRUE);
		return $this;
	}

	/**
	 * Chooses which column(s) to order the select query by.
	 *
	 * @param	string|array  column(s) to order on, can be an array, single column, or comma seperated list of columns
	 * @param	string		  direction of the order
	 * @return	Database_Core		 This Database object.
	 */
	public function orderby($orderby, $direction = NULL)
	{
		if ( ! is_array($orderby))
		{
			$orderby = array($orderby => $direction);
		}

		foreach ($orderby as $column => $direction)
		{
			$direction = strtoupper(trim($direction));

			// Add a direction if the provided one isn't valid
			if ( ! in_array($direction, array('ASC', 'DESC', 'RAND()', 'RANDOM()', 'NULL')))
			{
				$direction = 'ASC';
			}

			// Add the table prefix if a table.column was passed
			if (strpos($column, '.'))
			{
				$column = $this->config['table_prefix'].$column;
			}

			$this->orderby[] = $this->driver->escape_column($column).' '.$direction;
		}

		return $this;
	}

	/**
	 * Selects the limit section of a query.
	 *
	 * @param	integer  number of rows to limit result to
	 * @param	integer  offset in result to start returning rows from
	 * @return	Database_Core	This Database object.
	 */
	public function limit($limit, $offset = NULL)
	{
		$this->limit  = (int) $limit;

		if ($offset !== NULL OR ! is_int($this->offset))
		{
			$this->offset($offset);
		}

		return $this;
	}

	/**
	 * Zapne pocitani celkoveho poctu radku
	 *
	 * @param	set
	 * @return	Database   This Database object.
	 */
	public function calc_found_rows($set = true)
	{
		$this->calc_found_rows = $set;

		return $this;
	}



	/**
	 * Sets the offset portion of a query.
	 *
	 * @param	integer  offset value
	 * @return	Database_Core	This Database object.
	 */
	public function offset($value)
	{
		$this->offset = (int) $value;

		return $this;
	}

	/**
	 * Allows key/value pairs to be set for inserting or updating.
	 *
	 * @param	string|array  key name or array of key => value pairs
	 * @param	string		  value to match with key
	 * @return	Database_Core		 This Database object.
	 */
	public function set($key, $value = '')
	{
		if ( ! is_array($key))
		{
			$key = array($key => $value);
		}

		foreach ($key as $k => $v)
		{
			// Add a table prefix if the column includes the table.
			if (strpos($k, '.'))
				$k = $this->config['table_prefix'].$k;

			$this->set[$k] = $this->driver->escape($v);
		}

		return $this;
	}

	/**
	 * Compiles the select statement based on the other functions called and runs the query.
	 *
	 * @param	string	table name
	 * @param	string	limit clause
	 * @param	string	offset clause
	 * @return	Database_Result
	 */
	public function get($table = '', $limit = NULL, $offset = NULL)
	{

		if ($table != '')
		{
			$this->from($table);
		}

		if ( ! is_null($limit))
		{
			$this->limit($limit, $offset);
		}

		$sql = $this->driver->compile_select(get_object_vars($this));

		$this->reset_select();

		$result = $this->query($sql);

		$this->last_query = $sql;

		return $result;
	}

	/**
	 * Compiles the select statement based on the other functions called and runs the query.
	 *
	 * @param	string	table name
	 * @param	array	where clause
	 * @param	string	limit clause
	 * @param	string	offset clause
	 * @return	Database_Core  This Database object.
	 */
	public function getwhere($table = '', $where = NULL, $limit = NULL, $offset = NULL)
	{
		if ($table != '')
		{
			$this->from($table);
		}

		if ( ! is_null($where))
		{
			$this->where($where);
		}

		if ( ! is_null($limit))
		{
			$this->limit($limit, $offset);
		}

		$sql = $this->driver->compile_select(get_object_vars($this));

		$this->reset_select();

		$result = $this->query($sql);

		return $result;
	}

	/**
	 * Compiles the select statement based on the other functions called and returns the query string.
	 *
	 * @param	string	table name
	 * @param	string	limit clause
	 * @param	string	offset clause
	 * @return	string	sql string
	 */
	public function compile($table = '', $limit = NULL, $offset = NULL)
	{
		if ($table != '')
		{
			$this->from($table);
		}

		if ( ! is_null($limit))
		{
			$this->limit($limit, $offset);
		}

		$sql = $this->driver->compile_select(get_object_vars($this));

		$this->reset_select();

		return $sql;
	}

	/**
	 * Compiles an insert string and runs the query.
	 *
	 * @param	string	table name
	 * @param	array	array of key/value pairs to insert
	 * @return	Database_Result  Query result
	 */
	public function insert($table = '', $set = NULL)
	{
		if ( ! is_null($set))
		{
			$this->set($set);
		}

		if ($this->set == NULL)
			throw new Database_Exception('database.must_use_set');

		if ($table == '')
		{
			if ( ! isset($this->from[0]))
				throw new Database_Exception('database.must_use_table');

			$table = $this->from[0];
		}

		// If caching is enabled, clear the cache before inserting
		($this->config['cache'] === TRUE) and $this->clear_cache();

		$sql = $this->driver->insert($this->config['table_prefix'].$table, array_keys($this->set), array_values($this->set));

		$this->reset_write();

		return $this->query($sql);
	}

	/**
	 * Adds an "IN" condition to the where clause
	 *
	 * @param	string	Name of the column being examined
	 * @param	mixed	An array or string to match against
	 * @param	bool	Generate a NOT IN clause instead
	 * @return	Database_Core  This Database object.
	 */
	public function in($field, $values, $not = FALSE)
	{
		if (is_array($values))
		{
			$escaped_values = array();
			foreach ($values as $v)
			{
				if (is_numeric($v))
				{
					$escaped_values[] = $v;
				}
				else
				{
					$escaped_values[] = "'".$this->driver->escape_str($v)."'";
				}
			}
			$values = implode(",", $escaped_values);
		}

		$where = $this->driver->escape_column(((strpos($field,'.') !== FALSE) ? $this->config['table_prefix'] : ''). $field).' '.($not === TRUE ? 'NOT ' : '').'IN ('.$values.')';
		$this->where[] = $this->driver->where($where, '', 'AND ', count($this->where), -1);

		return $this;
	}

	/**
	 * Adds a "NOT IN" condition to the where clause
	 *
	 * @param	string	Name of the column being examined
	 * @param	mixed	An array or string to match against
	 * @return	Database_Core  This Database object.
	 */
	public function notin($field, $values)
	{
		return $this->in($field, $values, TRUE);
	}

	/**
	 * Compiles a merge string and runs the query.
	 *
	 * @param	string	table name
	 * @param	array	array of key/value pairs to merge
	 * @return	Database_Result  Query result
	 */
	public function merge($table = '', $set = NULL)
	{
		if ( ! is_null($set))
		{
			$this->set($set);
		}

		if ($this->set == NULL)
			throw new Database_Exception('database.must_use_set');

		if ($table == '')
		{
			if ( ! isset($this->from[0]))
				throw new Database_Exception('database.must_use_table');

			$table = $this->from[0];
		}

		$sql = $this->driver->merge($this->config['table_prefix'].$table, array_keys($this->set), array_values($this->set));

		$this->reset_write();
		return $this->query($sql);
	}

	/**
	 * Compiles an update string and runs the query.
	 *
	 * @param	string	table name
	 * @param	array	associative array of update values
	 * @param	array	where clause
	 * @return	Database_Result  Query result
	 */
	public function update($table = '', $set = NULL, $where = NULL)
	{
		if ( is_array($set))
		{
			$this->set($set);
		}

		if ( ! is_null($where))
		{
			$this->where($where);
		}

		if ($this->set == FALSE)
			throw new Database_Exception('database.must_use_set');

		if ($table == '')
		{
			if ( ! isset($this->from[0]))
				throw new Database_Exception('database.must_use_table');

			$table = $this->from[0];
		}

		$sql = $this->driver->update($this->config['table_prefix'].$table, $this->set, $this->where);

		$this->reset_write();
		return $this->query($sql);
	}

	/**
	 * Compiles a delete string and runs the query.
	 *
	 * @param	string	table name
	 * @param	array	where clause
	 * @return	Database_Result  Query result
	 */
	public function delete($table = '', $where = NULL)
	{
		if ($table == '')
		{
			if ( ! isset($this->from[0]))
				throw new Database_Exception('database.must_use_table');

			$table = $this->from[0];
		}
		else
		{
			$table = $this->config['table_prefix'].$table;
		}

		if (! is_null($where))
		{
			$this->where($where);
		}

		if (count($this->where) < 1)
			throw new Database_Exception('database.must_use_where');

		$sql = $this->driver->delete($table, $this->where);

		$this->reset_write();
		return $this->query($sql);
	}

	/**
	 * Returns the last query run.
	 *
	 * @return	string SQL
	 */
	public function last_query()
	{
	   return $this->last_query;
	}

	/**
	 * Count query records.
	 *
	 * @param	string	 table name
	 * @param	array	 where clause
	 * @return	integer
	 */
	public function count_records($table = FALSE, $where = NULL)
	{
		if (count($this->from) < 1)
		{
			if ($table == FALSE)
				throw new Database_Exception('database.must_use_table');

			$this->from($table);
		}

		if ($where !== NULL)
		{
			$this->where($where);
		}

		$query = $this->select('COUNT(*) AS '.$this->escape_column('records_found'))->get()->result(TRUE);

		return (int) $query->current()->records_found;
	}

	/**
	 * Resets all private select variables.
	 *
	 * @return	void
	 */
	public function reset_select()
	{
		$this->select	= array();
		$this->from		= array();
		$this->join		= array();
		$this->where	= array();
		$this->orderby	= array();
		$this->groupby	= array();
		$this->having	= array();
		$this->distinct = FALSE;
		$this->limit	= FALSE;
		$this->offset	= FALSE;
		$this->calc_found_rows = null;
	}

	/**
	 * Resets all private insert and update variables.
	 *
	 * @return	void
	 */
	protected function reset_write()
	{
		$this->set	 = array();
		$this->from  = array();
		$this->where = array();
	}

	/**
	 * Lists all the tables in the current database.
	 *
	 * @return	array
	 */
	public function list_tables()
	{
		$this->link or $this->connect();

		return $this->driver->list_tables($this);
	}

	/**
	 * See if a table exists in the database.
	 *
	 * @param	string	 table name
	 * @param	boolean  True to attach table prefix
	 * @return	boolean
	 */
	public function table_exists($table_name, $prefix = TRUE)
	{
		if ($prefix)
			return in_array($this->config['table_prefix'].$table_name, $this->list_tables());
		else
			return in_array($table_name, $this->list_tables());
	}

	/**
	 * Combine a SQL statement with the bind values. Used for safe queries.
	 *
	 * @param	string	query to bind to the values
	 * @param	array	array of values to bind to the query
	 * @return	string
	 */
	public function compile_binds($sql, $binds)
	{
		foreach ((array) $binds as $val)
		{
			// If the SQL contains no more bind marks ("?"), we're done.
			if (($next_bind_pos = strpos($sql, '?')) === FALSE)
				break;

			// Properly escape the bind value.
			$val = $this->driver->escape($val);

			// Temporarily replace possible bind marks ("?"), in the bind value itself, with a placeholder.
			$val = str_replace('?', '{%B%}', $val);

			// Replace the first bind mark ("?") with its corresponding value.
			$sql = substr($sql, 0, $next_bind_pos).$val.substr($sql, $next_bind_pos + 1);
		}

		// Restore placeholders.
		return str_replace('{%B%}', '?', $sql);
	}

	/**
	 * Get the field data for a database table, along with the field's attributes.
	 *
	 * @param	string	table name
	 * @return	array
	 */
	public function field_data($table = '')
	{
		$this->link or $this->connect();

		return $this->driver->field_data($this->config['table_prefix'].$table);
	}

	/**
	 * Get the field data for a database table, along with the field's attributes.
	 *
	 * @param	string	table name
	 * @return	array
	 */
	public function list_fields($table = '')
	{

		$this->link or $this->connect();

		return $this->driver->list_fields($this->config['table_prefix'].$table);
	}

	/**
	 * Escapes a value for a query.
	 *
	 * @param	mixed	value to escape
	 * @return	string
	 */
	public function escape($value)
	{
		return $this->driver->escape($value);
	}

	/**
	 * Escapes a string for a query.
	 *
	 * @param	string	string to escape
	 * @return	string
	 */
	public function escape_str($str)
	{
		return $this->driver->escape_str($str);
	}

	/**
	 * Escapes a table name for a query.
	 *
	 * @param	string	string to escape
	 * @return	string
	 */
	public function escape_table($table)
	{
		return $this->driver->escape_table($table);
	}

	/**
	 * Escapes a column name for a query.
	 *
	 * @param	string	string to escape
	 * @return	string
	 */
	public function escape_column($table)
	{
		return $this->driver->escape_column($table);
	}

	/**
	 * Returns table prefix of current configuration.
	 *
	 * @return	string
	 */
	public function table_prefix()
	{
		return $this->config['table_prefix'];
	}

	/**
	 * Clears the query cache.
	 *
	 * @param	string|TRUE  clear cache by SQL statement or TRUE for last query
	 * @return	Database_Core		This Database object.
	 */
	public function clear_cache($sql = NULL)
	{
		if ($sql === TRUE)
		{
			$this->driver->clear_cache($this->last_query);
		}
		elseif (is_string($sql))
		{
			$this->driver->clear_cache($sql);
		}
		else
		{
			$this->driver->clear_cache();
		}

		return $this;
	}

	/**
	 * Pushes existing query space onto the query stack.  Use push
	 * and pop to prevent queries from clashing before they are
	 * executed
	 *
	 * @return Database_Core This Databaes object
	 */
	public function push()
	{
		array_push($this->query_history, array(
			$this->select,
			$this->from,
			$this->join,
			$this->where,
			$this->orderby,
			$this->order,
			$this->groupby,
			$this->having,
			$this->distinct,
			$this->limit,
			$this->offset
		));

		$this->reset_select();

		return $this;
	}

	/**
	 * Pops from query stack into the current query space.
	 *
	 * @return Database_Core This Databaes object
	 */
	public function pop()
	{
		if (count($this->query_history) == 0)
		{
			// No history
			return $this;
		}

		list(
			$this->select,
			$this->from,
			$this->join,
			$this->where,
			$this->orderby,
			$this->order,
			$this->groupby,
			$this->having,
			$this->distinct,
			$this->limit,
			$this->offset
		) = array_pop($this->query_history);

		return $this;
	}

	/**
	 * Count the number of records in the last query, without LIMIT or OFFSET applied.
	 *
	 * @return	integer
	 */
	public function count_last_query()
	{
		if ($sql = $this->last_query())
		{
			if (stripos($sql, 'LIMIT') !== FALSE)
			{
				// Remove LIMIT from the SQL
				$sql = preg_replace('/\sLIMIT\s+[^a-z]+/i', ' ', $sql);
			}

			if (stripos($sql, 'OFFSET') !== FALSE)
			{
				// Remove OFFSET from the SQL
				$sql = preg_replace('/\sOFFSET\s+\d+/i', '', $sql);
			}

			// Get the total rows from the last query executed
			$result = $this->query
			(
				'SELECT COUNT(*) AS '.$this->escape_column('total_rows').' '.
				'FROM ('.trim($sql).') AS '.$this->escape_table('counted_results')
			);

			// Return the total number of rows from the query
			return (int) $result->current()->total_rows;
		}

		return FALSE;
	}


	public function getState()
	{
		return array(
			$this->select,
			$this->from,
			$this->join,
			$this->where,
			$this->orderby,
			$this->order,
			$this->groupby,
			$this->having,
			$this->distinct,
			$this->limit,
			$this->offset
		);
	}

	public function setState($state)
	{
		list(
			$this->select,
			$this->from,
			$this->join,
			$this->where,
			$this->orderby,
			$this->order,
			$this->groupby,
			$this->having,
			$this->distinct,
			$this->limit,
			$this->offset
		) = $state;
		return $this;
	}

	/**
	 * Create a prepared statement (experimental).
	 *
	 * @param	string	SQL query
	 * @return	object
	 */
	public function stmt_prepare($sql)
	{
		return $this->driver->stmt_prepare($sql, $this->config);
	}

	/**
	 * Returns brief descriptions.
	 * @return string
	 * @return array
	 */
	public static function getColophon($sender = NULL)
	{
		$arr = array(
			'Number of SQL queries: xx'
		);
		/*if ($sender === 'bluescreen') {
			$arr[] = 'dibi ' . dibi::VERSION . ' (revision ' . dibi::REVISION . ')';
		}*/
		//return $arr;
	}

	/**
	 * @return Configurace pripojeni k DB
	 */
	public function getConfig()
	{
		return $this->config;
	}

} // End Database Class


/**
 * Sets the code for a Database exception.
 */
class Database_Exception extends Exception {

	protected $code = E_DATABASE_ERROR;

	/**
	 * Set exception message.
	 *
	 * @param  string  message
	 * @param  array   addition line parameters
	 */
	public function __construct($error, $code = null)
	{
		$args = array_slice(func_get_args(), 1);
		if(!is_null($code)) $this->code = $code;
		parent::__construct($error);
	}
}

class DatabaseConstraintFails extends Database_Exception {

	protected $column;
	protected $table;

	const DELETE = 1451; // Cannot delete or update a parent row: a foreign key constraint fails
	const ADD = 1452; // Cannot add or update a child row: a foreign key constraint fails

	public function __construct($error, $code = null)
	{
		parent::__construct($error, $code);
		if(preg_match('#FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)`#', $error, $m)) {
			$this->column = $m[1];
			$this->table = $m[2];
		}
	}

	public function getColumn()
	{
		return $this->column;
	}

	public function getTable()
	{
		return $this->table;
	}
}

class DBValue
{
	public $value;

	public function __construct($value)
	{
		$this->value = $value;
	}

	public static function factory($value)
	{
		return new self($value);
	}

	public function __toString()
	{
		return '"'.$this->value.'"';
	}
}

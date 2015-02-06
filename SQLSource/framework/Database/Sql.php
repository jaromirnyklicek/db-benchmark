<?php
/**
 * Database SQL Layer
 *
 *
 * @package    Database
 * @author	   Ondrej Novak
 * @copyright  (c) 2009 Ondrej Novak
 *
 * Umoznuje staticke volani databazovych dotazu.
 *
 */
class sql {

	/**
	 * spojeni na DB
	 */
	protected $db;

	/**
	 * Konfigurace pripojeni k db
	 */
	protected static $config;

	/**
	 * Adapter pro pripojeni
	 */
	protected static $adapter;

	//
	//	Public methods
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Konstruktor
	 */
	public function __construct()
	{
		$this->db = Database::singleton();
		self::initialize($this->db);
	}

	protected static function initialize($db)
	{
		/**
		 * Zjisteni konfigurace k db. Zjisteni typu pripojeni je nutne pro pouziti spravnych konstant pro typy vysledku funkce fetch
		 */
		self::$config  = $db->getConfig();

		/**
		 * Ziskani adapteru
		 */
		self::$adapter = self::$config['connection']['type'];
	}

	/**
	 * Vrátí první řádek výsledku do asociativního pole.
	 * Vhodné použití na dotazy, které vrací jeden řádek.
	 * @example $dbRow = dbi_mysql::toRow('SELECT * FROM table WHERE id = 1')
	 *
	 * @param string $sql
	 * @param Database $db
	 * @return array
	 */
	public static function toRow($sql, $db = NULL)
	{
		if(empty($db)) $db = Database::singleton();
		$dbRes = $db->query($sql)->as_array();
		return isset($dbRes[0]) ? $dbRes[0] : NULL;
	}

	/**
	 * Vrátí pole hodnot. Mozne seskupit jako klic do asociativniho pole.
	 * @example $values = dbi_mysql::toArray('SELECT * FROM files', 'article', true) // soubory ke clanku
	 * array(2) {
				  [4]=>  // id clanku 4
				  array(2) {
					[0]=>
					 array(3)
					 {
						 'name' => "a",
						'size' => "100",
						'article' => "4",
					 }
					 [1]=>
					 array(3)
					 {
						 'name' => "b",
						'size' => "200",
						'article' => "4",
					 }
				  }
				  [7]=> // id clanku 7
				  array(2) {
					[0]=>
					 array(3)
					 {
						 'name' => "c",
						'size' => "300",
						'article' => "7",
					 }
					 [1]=>
					 array(3)
					 {
						 'name' => "d",
						'size' => "400",
						'article' => "7",
					 }
				  }
				}
	 *
	 *
	 * @param string $sql
	 * @param $key - sloupec pro pouziti do klice k asociativnimu polu
	 * @param $groupBy - seskupuje podle klice. Pouziti pro vazbu 1:N @see example
	 * @param Database $db
	 * @param $groupkey - sloupec pro pouziti do klice k vnorenemu asociativnimu polu
	 * @return array
	 */
	public static function toArray($sql, $key = NULL, $groupBy = FALSE, $db = NULL, $groupkey = NULL)
	{
		if(empty($db)) $db = Database::singleton();
		$dbRes = $db->query($sql);
		$arr = $dbRes->as_array();
		if(!isset($key)) return $arr;
		else	{
			$a = array();
			foreach ($arr as $dbRow) {
				$o = (object)$dbRow;
				if(!$groupBy) $a[$dbRow->$key] = $o;
				else {
					if(!$groupkey) $a[$dbRow->$key][] = $o;
					else $a[$dbRow->$key][$dbRow->$groupkey] = $o;
				}
			}
			return $a;
		}
	}

	/**
	 * Vrátí pole hodnot. Mozne seskupit jako klic do asociativniho pole.
	 * Prvni sloupec dotazu, se pouzije jako hodnota. Dalsi sloupec muze byt volitelny ke klicovani.
	 * @example $values = dbi_mysql::toValues('SELECT name, article FROM files', 'article') // jmena souboru ke clanku
	 *
	 * array(2) {
				  [4]=>  // id clanku 4
				  array(3) {
					[0]=>
					string(1) "a"
					[1]=>
					string(1) "b"
					[2]=>
					string(1) "c"
				  }
				  [7]=> // id clanku 7
				  array(2) {
					[0]=>
					string(1) "a"
					[1]=>
					string(1) "b"
				  }
				}
	 *
	 *
	 * @param string $sql
	 * @param $key - sloupec pro pouziti do klice k asociativnimu polu
	 * @param $column - index sloupce, který se vrátí. Default nultý
	 * @param Database $db
	 * @return array | false
	 */
	public static function toValues($sql, $key = NULL, $db = NULL)
	{
		if(empty($db)) $db = Database::singleton();

		self::initialize($db);

		/**
		 * Pouziti konstant podle pouziteho adapteru
		 */
		$fetchType = (self::$adapter == 'mysqli' ? MYSQLI_NUM | MYSQLI_ASSOC : MYSQL_NUM | MYSQL_ASSOC );

		$dbRes = $db->query($sql);
		$arr = $dbRes->as_array(false, $fetchType);
		$a = array();
		if(isset($key)) foreach ($arr as $dbRow) $a[$dbRow[$key]] = $dbRow[0];
		else foreach ($arr as $dbRow) $a[] = $dbRow[0];
		return $a;
	}

	/**
	 * Vrátí sloupec z databázového dotazu v poli
	 *
	 * @see self::toValue()
	 */
	public static function toColumn($sql, $column = 0, $db = null)
	{
		return self::toValues($sql, null, $column, $db);
	}

	/**
	* Vrati prvni a druhy sloupec dotazu jako asociativni pole, kde klic je prvni sloupec a hodnota drugy sloupec.
	* Pouziti vhodne jako dbinfo generovane z databaze.
	*
	* @param string $sql
	* @param array $db
	*/
	public static function toPairs($sql, $db = NULL)
	{
		if(empty($db)) $db = Database::singleton();

		self::initialize($db);

		/**
		 * Pouziti konstant podle pouziteho adapteru
		 */
		$fetchType = (self::$adapter == 'mysqli' ? MYSQLI_NUM : MYSQL_NUM);

		$dbRes = $db->query($sql);
		$arr = $dbRes->as_array(false, $fetchType);
		$a = array();
		foreach ($arr as $dbRow) $a[$dbRow[0]] = $dbRow[1];
		return $a;
	}

	/**
	 * Vrátí hodnotu z dotazu (implicitně první řádek a prvni sloupec výsledku).
	 * Vhodné použití na dotazy, které vrací jednu hodnotu.
	 * @example $count = dbi_mysql::toScalar('SELECT COUNT(*) FROM table')
	 *
	 * @param string $sql
	 * @param $column - index sloupce, který se vrátí. Default nultý
	 * @param Database $db
	 * @return array | false
	 */
	 public static function toScalar($sql, $db = NULL)
	{
		if(empty($db)) $db = Database::singleton();

		self::initialize($db);

		/**
		 * Pouziti konstant podle pouziteho adapteru
		 */
		$fetchType = (self::$adapter == 'mysqli' ? MYSQLI_NUM : MYSQL_NUM);

		$dbRes = $db->query($sql);
		$arr = $dbRes->as_array(false, $fetchType);
		if(count($arr) > 0)
		{
			return $arr[0][0];
		}
		else return false;
	}

	/**
	 * Volani SQL dotazu, ktery nepotrebuji nikam ulozit ani s ním dále pracovat
	 * @example dbi_mysql::query('UPDATE table SET column = 1')
	 *
	 * @param string $sql
	 * @param Database $db
	 * @return Mysql_Result
	 */
	public static function query($sql, $db = NULL)
	{
		if(empty($db)) $db = Database::singleton();
		return $db->query($sql);
	}

	/**
	 * Vrati ID vlozeneho dotazu. Pouziti pri INSERTy
	 * @example dbi_mysql::insert_id('INSERT INTO table VALUES (1)')
	 *
	 * @param string $sql
	 * @param Database $db
	 * @return int | false
	 */
	public static function insert_id($sql, $db = NULL)
	{
		if(empty($db)) $db = Database::singleton();
		$dbRes = $db->query($sql);
		return $dbRes->insert_id();
	}


	/**
	* Podobna metoda jako toArray, ale vrati objekt s poctem celkovych zaznamu.
	* Vhodne pro stankovaci vypisy, kde je potreba vedet, jestli seznam pokracuje na dalsi strance
	*
	* @param string $sql
	* @param Database $db
	* @return stdClass
	*/
	public static function toPagingList($sql, $db = NULL)
	{
		if(empty($db)) $db = Database::singleton();
		$dbRes = $db->query($sql);
		$arr = $dbRes->as_array();
		$items = $arr;

		$result = new stdClass();
		$result->items = $items;
		$result->count = $dbRes->found_rows();

		return $result;
	}


	/** Pouziti v DataListech
	*	---------------------
	*/

	/**
	* provede substituci zastupneho symbolu [WHERE] za SQL string, ktery se sesklada
	* z formcontrolu filtrovaciho formulare
	*
	* @param string $sql
	* @param Form $form
	* @return string
	*/
	public static function where($sql, $form)
	{
		// substituce WHERE (z filtru)
		if(isset($form)) {
			$where = self::toWhere($form);	// vytvori SQL podminku
		}
		else $where = 1;
		return preg_replace('/\[WHERE\]|\[FILTER\]/i', $where, $sql);
	}

	/**
	* Filtrovaci formular (resp. jeho formcontroly) preveda na SQL dotaz, ktery pak lze dosadit do WHERE klausule
	*
	* @param Form $form
	* @return string
	*/
	public static function toWhere($form) {
	   $where = array();
	   foreach($form->getComponents() as $control) {
		   if($control->getOption('sql')) {
			   $value = $control->sqlWhere();
			   if(!empty($value)) $where[] = $value;
		   }
	   }
	   return empty($where) ? 1 : join(' AND ', $where);
	}

	public static function startTransaction()
	{
		sql::query('START TRANSACTION');
	}

	public static function commit()
	{
		sql::query('COMMIT');
	}

	public static function rollback()
	{
		sql::query('ROLLBACK');
	}
}

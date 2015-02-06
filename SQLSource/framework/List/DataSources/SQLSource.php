<?php
/**
* SQL Source pracuje s SQL dotazem.
* Na konec dotazu se pridat ORDER a LIMIT.	Za [WHERE] se dosadi parametr $where,
* predany v loadData.
* Pro pouziti v DataListech vraci pole radku, kde sloupce jsou skalarnich hodnoty indexovane
* podle aliasu SQL dotazu
*
*
* @package DataSource
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version $1.0$
*/

class SQLSource extends BaseSource implements IDataSource, Countable
{

	/** SQL dotaz **/
	protected $sql;

	/** spojeni na databazi **/
	protected $db;

	/** vazebni dotazy **/
	protected $bind = array();

	/** vazebni dotazy pro jeden radek a sloupec**/
	protected $bindSimple = array();

	/** trida pro razeni aliasem */
	protected $sqlOrderAlias;

	//protected $filterClass = 'SQLFormFilter';

	/** nalezene zaznamy celkem **/
	protected $foundRows;

	protected $filter;
	protected $page = 1;
	protected $limit;
	protected $offset = 0;
	protected $order;

	protected $sqlCalcFoundRows = FALSE;

	protected $defaultOrder;

	protected $orderAlias = array();

	/**
	 * Konstruktor.
	 * SQL dotaz muze obsahovat zastupne znaky [FILTER] nebo [WHERE], za ktere se dosadi filtrovaci
	 * formular.
	 *
	 * @param
	 */
	public function __construct($sql, $db = NULL)
	{
		$this->sql = $sql;
		if($db === NULL) $db = Database::singleton();
		$this->db = $db;
		$this->sqlOrderAlias = new SQLOrder();
	}

	/** settery a gettery **/

	/*public function setFilterClass($value)
	{
		$this->filterClass = $value;
	}

	public function getFilterClass()
	{
		return $this->filterClass;
	} */

	public function setFilter($value)
	{
		$this->filter = $value;
		return $this;
	}

	public function getFilter()
	{
		return $this->filter;
	}

	public function setSqlCalcFoundRows($value)
	{
		$this->sqlCalcFoundRows = $value;
		return $this;
	}

	public function getSqlCalcFoundRows()
	{
		return $this->sqlCalcFoundRows;
	}

	public function setSql($value)
	{
		$this->sql = $value;
		return $this;
	}


	public function setPage($value)
	{
		if(empty($value) || $value < 0) $value = 1;
		$this->page = $value;
		return $this;
	}

	public function getPage()
	{
		return $this->page;
	}

	public function setDefaultOrder($value)
	{
		$this->defaultOrder = $value;
		return $this;
	}

	public function getDefaultOrder()
	{
		return $this->defaultOrder;
	}

	public function setOrder($value)
	{
		$this->order = $value;
		return $this;
	}

	public function getOrder()
	{
		return $this->order;
	}

	public function setLimit($value)
	{
		$this->limit = $value;
		return $this;
	}

	public function getLimit()
	{
		return $this->limit;
	}

	public function setOffset($value)
	{
		$this->offset = $value;
		return $this;
	}

	public function getOffset()
	{
		return $this->offset;
	}

	public function getBinds()
	{
		return $this->bind;
	}

	public function getSimpleBinds()
	{
		return $this->bindSimple;
	}


	protected function where()
	{
		if(isset($this->filter)) {
			if($this->filter instanceof IDataSourceFilter) {
				$sql = $this->filter->buildSql();
			}
			else $sql = $this->filter;
			return $sql;
		}
		else return 1;
	}

	/**
	* Sestavi SQL dotaz.
	*
	* @param array $params - @see loadData();
	*/
	public function getSql()
	{
		$sql = $this->sql;

		// pokud neni SQL_CALC_FOUND_ROWS prida ho do dotazu
		if($this->sqlCalcFoundRows && !preg_match('/SELECT.*SQL_CALC_FOUND_ROWS/ims', $sql)) {
			$sql = preg_replace('/SELECT./ims', 'SELECT SQL_CALC_FOUND_ROWS ', $sql, 1);
		}
		// pokud je evedeny SQL_CALC_FOUND_ROWS, tak se nasvtavy priznak $sqlCalcFoundRows,
		// na ktery se jeste bere ohled pri vykonani dotazu
		if(preg_match('/SELECT.*SQL_CALC_FOUND_ROWS/i', $sql)) {
			$this->sqlCalcFoundRows = TRUE;
		}

		// substituce WHERE (z filtru)
		$sql = preg_replace('/\[WHERE\]|\[FILTER\]/i', $this->where(), $sql);

		// razeni se prida na konec SQL dotazu
		$sql .= $this->getSqlOrder();

		// limit se prida na konec SQL dotazu
		if(isset($this->page) && isset($this->limit)) {
		   $sql .= ' LIMIT '.(int)(($this->page-1)*$this->limit + $this->offset).', '.(int)$this->limit;
		}
		return $sql;
	}

	/**
	* Nastaveni aliasu pro razeni
	*
	* @param string $alias Nazev radiciho aliasu
	* @param string $asc SQL pro ASC
	* @param string $desc SQL pro DESC
	*/
	public function setOrderAlias($alias, $asc, $desc = NULL)
	{
		$this->sqlOrderAlias->addAlias($alias, $asc, $desc);
		return $this;
	}

	protected function getSqlOrder()
	{
		$sql = '';
		// razeni se prida na konec SQL dotazu
		if(!isset($this->order) && isset($this->defaultOrder)) {
		   $this->order = $this->defaultOrder;
		}
		// razeni podle stringu SQL
		if(isset($this->order) && is_string($this->order)) {
		  $sql .= ' ORDER BY '.$this->order;
		}
		// razeni podle sloupce
		elseif(isset($this->order) && isset($this->order->column) && $this->order->column instanceof Column) {

				$sqlorder = $this->order->column->getOption('sql') ?
									$this->order->column->getOption('sql') : $this->order->column->member;

				$sqlorderAlias = $this->sqlOrderAlias->getOrder($sqlorder, $this->order->direction);
				if($sqlorderAlias == NULL) {
					$sqlorder .= ' '.($this->order->direction == 'a' ? 'ASC' : 'DESC');
				}
				else {
					$sqlorder = $sqlorderAlias;
				}
				$sql .= ' ORDER BY '.$sqlorder;
		}
		// razeni "aliasem"
		elseif(isset($this->order) && isset($this->order->column) && is_string($this->order->column)) {
				$sqlorder = $this->sqlOrderAlias->getOrder($this->order->column, $this->order->direction);
				$sql .= ' ORDER BY '.$sqlorder;
		}
		return $sql;
	}

	/**
	 * Nacte parametry do atributu objektu
	 * Parametr pole obsahuje nepovinne indexy:
	 * - where - filtrovaci formular
	 * - page - pozadovana stranka
	 * - limit - pocet zaznamu na stranku
	 * - order - textova reprezentace razeni (napr.: table.id DESC)
	 * alternativa k textovemu $order:
	 * - order->column - instance Column - sloupec, podle ktere se bude radit | mozno uvest alias a zapojit funkci $sqlOrderAlias
	 * - order->direction - emun('a','d') - smer razeni
	 *
	 * @param array $params
	 * @return SQLSource
	 */
	 public function loadParams($params = array())
	 {
		 extract($params);
		 if(isset($where)) $this->filter = $where;
		 if(isset($page)) $this->page = $page;
		 if(isset($limit)) $this->limit = $limit;
		 if(isset($order)) $this->order = $order;
		return $this;
	 }

	/******** Interface IDataSource **********/


	/**
	 * Nacte data k zobrazeni
	 * Parametr obsahuje nepovinne indexy:
	 * - where - filtrovaci formular
	 * - page - pozadovana stranka
	 * - limit - pocet zaznamu na stranku
	 * - order - textova reprezentace razeni (napr.: table.id DESC)
	 * aletrenativa k textovemu $order:
	 * - order->column - instance Column - sloupec, podle ktere se bude radit
	 * - order->direction - emun('a','d') - smer razeni
	 *
	 * @param array $params
	 * @return SQLSource
	 */
	public function loadData($params = array())
	{
		if($params != NULL) $this->loadParams($params);
		$sql = $this->getSql();

		// provedeni
		$dbRes = $this->db->query($sql);
		$this->data = new DataTable($dbRes->as_array());
		if($this->sqlCalcFoundRows) {
			$this->foundRows = $dbRes->found_rows();
		}

		// dotazeni vazeb
		foreach($this->bind as $bind) $this->processBind($bind);
		foreach($this->bindSimple as $bind) $this->processBindSimple($bind);
		$this->data = $this->applyCallbacks($this->data);
		$this->loaded = TRUE;
		return $this;
	}

	/**
	 * Vrati data
	 *
	 * @return DataTable
	 */
	 public function getItems()
	{
		if(!$this->loaded) {
			$argv = func_get_args();
			$this->loadData(isset($argv[0]) ? $argv[0] : array());
		}
		return $this->data;
	}

	public function count()
	{
		return $this->getItems()->count();
	}

	/**
	* Vrati prvni zaznam
	*
	*/
	public function getFirst()
	{
		$items = $this->getItems();
		return isset($items[0]) ? $items[0] : NULL;
	}

	/**
	 * Vrati data, ale pouze specifikovane sloupce
	 *
	 * @return DataTable
	 */
	 public function getItemsByColumns($columns = array())
	{
		if(!$this->loaded) {
			$this->loadData();
		}
		$out = array();
		foreach($this->data as $item) {
			$item = (array)$item;
			$row = array();
			foreach($columns as $column) {
				$row[$column] = $item[$column];
			}
			$out[] = $row;
		}
		return new DataTable($out);
	}

	/**
	 * Vrati celkovy pocet zaznamu. Pouziti pro vypocet celkoveho poctu stranek
	 *
	 * @return int
	 */
	 public function getAllRows()
	{
		if(!$this->loaded) {
			$argv = func_get_args();
			$this->loadData(isset($argv[0]) ? $argv[0] : array());
		}
		return $this->foundRows;
	}

	/**
	* Nastaveni vazanych dotazu. (multilist)
	* Ve vazaznem dotazu vyhleda $child = $id
	* @example $list->bind('comments', 'SELECT * FROM comments WHERE %s', 'id', 'article');
	* // Pro kazdy clanek k nemu naparuje vsechny jeho komentare jako pole
	*
	* @param mixed $name - jmeno noveno datoveho atributu
	* @param mixed $sql - sql dotaz k provedeni, kde za %s se dosadi IDcka z master dotazu.
	* @param mixed $parent - sloupec z master dotazu, ktery se pouzije pro IDcka do SQL
	* @param mixed $child - sloupec ve vazanem dotazu, ktery se porovnava na hodnoty z $parent.
	*						muze byt array pro pripad sql aliasu, kde dochazi ke kolizi, pokud je
	*						nazev klicove slovo v SQL jazyku.
	*	 Pr.: $source->bind('products', 'SELECT oi.title, oi.`order` FROM orders_items oi WHERE %s', 'id', array('oi.`order`' => 'order'));
	*/

	public function bind($name, $sql, $parent, $child)
	{
		$this->bind[$name] = (object) array('name' => $name, 'sql' => $sql, 'parent' => $parent, 'child' => $child);
		return $this;
	}

	/**
	* Nastaveni vazanych dotazu. (multilist)
	* Rozdil oproti Bind() je ze z vazaneho dotazu veme pouze prvni sloupec. Vysledkem tedy neni pole.
	*
	* Ve vazaznem dotazu vyhleda $child = $id
	* @example $list->bind('comments', 'SELECT COUNT(*), article FROM comments WHERE %s', 'id', 'article');
	* // Pro kazdy clanek k nemu naparuje pocet komentaru jako hodnotu (nikoliv pole jako u bind())
	*
	* @param mixed $name - jmeno noveno datoveho atributu
	* @param mixed $sql - sql dotaz k provedeni, kde za %s se dosadi IDcka z master dotazu.
	* @param mixed $parent - sloupec z master dotazu, ktery se pouzije pro IDcka do SQL
	* @param mixed $child - sloupec ve vazanem dotazu, ktery se porovnava na hodnoty z $parent
	* @param mixed $default - vychozi hodnota pokud nedojde k naparovani z duvodu neexistence zadneho vazaneho zaznamu
	*/
	public function bindSimple($name, $sql, $parent, $child, $default = NULL)
	{
		$this->bindSimple[$name] = (object) array(
			'name' => $name,
			'sql' => $sql,
			'parent' => $parent,
			'child' => $child,
			'default' => $default);
		return $this;
	}

	/**
	* Provedene vazaneho dotazu a jeho sparovani pro kazdy radek. @see bind()
	*
	* @param object $bind
	*/
	protected function processBind($bind)
	{
		if(!is_array($bind->child)) {
			$sql = $bind->child;
			$alias = $bind->child;
		}
		else {
			$sql = key($bind->child);
			$alias = current($bind->child);
		}

		$idArr = $this->data->getValues($bind->parent);
		if(empty($idArr)) {
			foreach($this->data as &$item) {
				$item->{$bind->name} = array();
			}
			return NULL;
		}
		$sub = $sql.' IN ('.join(',', $idArr).')';
		$source = new SQLSource(sprintf($bind->sql, $sub), $this->db);
		foreach($this->data as &$item) {
			$item->{$bind->name} = $source->getItems()->findBy($alias, $item->{$bind->parent});
		}
	}

	/**
	* Provedene vazaneho dotazu a jeho sparovani pro kazdy radek. @see bind()
		*
	* @param object $bind
	*/
	protected function processBindSimple($bind)
	{
		if(!is_array($bind->child)) {
			$sql = $bind->child;
			$alias = $bind->child;
		}
		else {
			$sql = key($bind->child);
			$alias = current($bind->child);
		}


		$idArr = $this->data->getValues($bind->parent);
		if(empty($idArr)) {
			foreach($this->data as &$item) {
				$item->{$bind->name} = $bind->default;
			}
			return NULL;
		}
		$sub = $sql.' IN ('.join(',', $idArr).')';
		$source = new SQLSource(sprintf($bind->sql, $sub));
		foreach($this->data as &$item) {
			$items = $source->getItems()->findBy($alias, $item->{$bind->parent});
			if($items->count()) $item->{$bind->name} = current($items[0]);
			else $item->{$bind->name} = $bind->default;
		}
	}
}

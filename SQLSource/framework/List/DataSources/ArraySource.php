<?php
/**
* Array Source pracuje s polem resp. s objekty implementujici interface Iterator a Countable
* Pro pouziti v DataListech vraci pole radku, kde sloupce jsou hodnoty indexovane
* podle klice vychoziho pole
*
*
* @package DataSource
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version $1.0$
*/

class ArraySource extends BaseSource implements IDataSource
{
	/**
	* Zdrojove pole
	*
	* @var array
	*/
	protected $source;

	/**
	 * @var array
	 */
	protected $data;

	/**
	* Celkem zaznamu
	*
	* @var int
	*/
	protected $foundRows;

	/**
	* Nacteno?
	*
	* @var boolean
	*/
	protected $loaded = false;

	/**
	 * Konstruktor
	 *
	 * @param
	 */
	public function __construct($source)
	{
		$this->source = $source;
	}

	/**
	* Pro kazdy filtrovaci input, se provede callback, ktery vrati boolean, jestli zaznam odpovida parametrum filtru
	* Metoda vrati true, pokud zaznam prosel filtrem.
	*
	* @param Form $form
	* @param object $item riadok/row
	* @return boolean
	*/
	protected function where($form, $item)
	{
		if ($form == NULL) {
			return TRUE;
		}
		$where = true;
		if ($form instanceof Filter) {
			$components = $form->getForm()->getComponents();
		} else {
			$components = $form->getComponents();
		}
		foreach ($components as $control) {
			$m = $control->getOption('member');
			if ($m) {
				$callback = $control->getOption('callback');
				if (empty($callback)) {
					$callback = 'ArraySource::contain';
				}
				if ($control->getValue()) {
					$where &= call_user_func($callback, $control->getValue(), $item->$m, $item);
				}
			}
		}
		return $where;
	}


	/********* Callbacky pro filtrovani *****
	* Kazdy callback vraci boolean, jestli hodnota vyhovuje nebo ne
	*/

	public static function isEqual($value, $item)
	{
		return $value == $item;
	}

	public static function contain($value, $item)
	{
		$pos = strpos($item, $value);

		if ($pos === FALSE) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	public static function dateFromTo($value, $item)
	{
		$showItem = TRUE;

		$from = Timestamp::factory($value['from']);
		$to = Timestamp::factory($value['to']);

		if ($value['from']) {
			if (strtotime($item) < $from->getAsTs()) {
				$showItem = FALSE;
			}
		}

		if ($value['to']) {
			if (strtotime($item) > $to->getAsTs()) {
				$showItem = FALSE;
			}
		}

		return $showItem;
	}

	/******** Interface IDataSource **********/


	/**
	 * Nacte data k zobrazeni
	 * Parametr obsahuje nepovinne indexy:
	 * - where - filtrovaci formular
	 * - page - pozadovana stranka
	 * - limit - pocet zaznamu na stranku
	 * - order->column - instance Column - sloupec, podle ktere se bude radit
	 * - order->direction - emun('a','d') - smer razeni
	 *
	 * @param array $params
	 */
	public function loadData($params = NULL)
	{
		if($params !== NULL) extract($params);
		if(!isset($limit) || $limit == 0) $limit = NULL;

		$data = array();
		foreach($this->source as $item) {
		  $obj = is_array($item) ? (object) $item : $item;
		  if(!isset($where) || $this->where($where, $obj)) $data[] = $obj;
		}

		// order
		if(!empty($data) && isset($order) && isset($order->column)) {
			$sqlorder = $order->column->member;
			foreach ($data as $key => $row) {
				$sort[$key]  = $row->$sqlorder;
			}
			array_multisort($sort, $order->direction == 'a' ? SORT_ASC : SORT_DESC, $data);
		}

		// limit
		if(isset($page) && isset($limit)) {
			$data = array_slice($data, ($page-1)*$limit, $limit);
		}

		$this->data = new DataTable($data);
		$this->data = $this->applyCallbacks($this->data);
		$this->foundRows = count($this->source);
		$this->loaded = TRUE;
		return $this;
	}

	/**
	 * Vrati data k zobrazeni
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
}
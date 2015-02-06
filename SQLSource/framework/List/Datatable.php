<?php
/**
* DataTable je datova struktura reprezentujici tabulku pripravenou
* pro pouziti v Listech (DataGrid, DataList, DataView)
*
*
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version 1.0
*/


class DataTable extends ArrayList implements IDataSource
{

	/**
	 * Celkový počet záznamů bez ohledu na stránkování.
	 *
	 * @var int
	 */
	private $foundRows;

	protected $loaded = FALSE;

	/**
	 * Konstruktor
	 *
	 * @param $array - pole nebo object implementujici interface Iterator
	 */
	public function __construct($array = NULL)
	{
		parent::__construct();
		if($array != NULL) {
			$this->import($array);
			$this->loadData();
		}
	}


	/// ******* Interface iDataSource **********

	public function loadData($params = NULL)
	{
		$this->loaded = TRUE;
	}

	/**
	* Celkovy pocet zaznamu (pres vsechny stranky)
	* @return int
	*/
	public function getAllRows()
	{
		return $this->foundRows;
	}

	/**
	* Vrati tabulku dat (zaznamy)
	* @return DataTable
	*/
	public function getItems()
	{
		if(!$this->loaded) $this->loadData();
		return $this;
	}

	public function asArray()
	{
		$arr = array();
		foreach($this as $item) $arr[] = $item;
		return $arr;
	}

	/**
	* Vrati pole vsech unikatnich hodnot pro dany sloupec
	* @return array
	*/
	public function getValues($column)
	{
		$arr = array();
		foreach($this as $item) {
			if($item->$column !== NULL) {
				$arr[(string)$item->$column] = $item->$column;
			}
		}
		return array_values($arr);
	}

	/**
	* Najde (vyfiltruje) zaznamy podle sloupce a vrati novy DataTable;
	*
	* @param string $column
	* @param mixed $value
	*/
	public function findBy($column, $value)
	{
		$arr = array();
		foreach($this as $item) {
			if($item->$column == $value) $arr[] = $item;
		}
		return new DataTable($arr);
	}


	public function __toString()
	{
		return get_class($this);
	}

}
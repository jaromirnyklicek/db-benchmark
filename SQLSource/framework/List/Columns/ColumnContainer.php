<?php
/**
* Column je popis sloupce pro pouziti v Listech (DataGrid, DataList, DataView)
*
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version 1.0*
*/

class ColumnContainer extends Column implements ArrayAccess
{

	/**
	* Oddelovac vlozenych sloupcu
	*
	* @var string
	*/
	protected $separator = '';

	protected $sortable = FALSE;

	public $columnArr = array();

	public function setSeparator($value)
	{
		$this->separator = $value;
		return $this;
	}

	public function getSeparator()
	{
		return $this->separator;
	}

	public function getVisibleColumns()
	{
		$cArr = array();
		foreach ($this->columnArr as $column) {
			if($column->getVisible()) $cArr[] = $column;
		}
		return $cArr;
	}

	public function getColumns()
	{
		return $this->columnArr;
	}

	public function render($applyCallback = true)
	{
		$xml = '';
		foreach ($this->getVisibleColumns() as $column)
		{
			$m = $column->member;
			$column->setRow($this->row);
			$xml .= $column->render($applyCallback);
			if ($this->separator) {
				$xml .= $this->separator;
			}
		}
		return $xml;
	}

	public function setDataList($datalist)
	{
		parent::setDataList($datalist);
		foreach ($this->columnArr as $column) {
			$column->setDataList($datalist);
		}
	}

	public function addColumn($column)
	{
		$this->columnArr[$column->name] = $column;
		$column->setParent($this);
		$column->setDataList($this->getDataList());
		return $column;
	}

	public function offsetExists($offset) {
	   return isset($this->columnArr[$offset]);
	}

	public function offsetGet($offset) {
		return $this->columnArr[$offset];
	}

	public function offsetSet($offset, $value) {
		$this->columnArr[$offset] = $value;
	}

	public function offsetUnset($offset) {
	   unset($this->columnArr[$offset]);
	}

}
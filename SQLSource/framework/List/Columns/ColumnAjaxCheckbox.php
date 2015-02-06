<?php
/**
* Column je popis sloupce pro pouziti v Listech (DataGrid, DataList, DataView)
*
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version 1.0
*/

class ColumnAjaxCheckbox extends Column
{

	public $action;
	public $table;
	public $column;
	public $idColumn = 'id';
	public $readonly = FALSE;
	protected $escaping = FALSE;

	public function setAction($action, $table = NULL, $column = NULL)
	{
		$this->action = $action;
		$this->table = $table;
		$this->column = $column;
		return $this;
	}

	public function setReadonly($s = TRUE)
	{
		$this->readonly = $s;
		return $this;
	}

	public function render($applyCallback = TRUE)
	{
		$value = $this->getValue();
		$action = $this->action;
		$value = parent::render($applyCallback);
		$dbRow = $this->row;
		$id = $this->dataList->name.'_'.$this->name.'_'.$dbRow->id;

		$ssid = $this->createSession();

		$url = $this->dataList->getPresenter()->Link($action, array(
				'id' => $dbRow->{$this->idColumn},
				'el' => $id,
				'token' => $ssid)
			   );
		if($this->readonly) {
			return $value;
		}
		else {
			return '<a style="'.$this->style.'" id="'.$id.'_link" rel="'.$this->getStatus().'" href="#"
			onclick="nette.action(\''.$url.'&status=\' + $(\'#'.$id.'_link\').attr(\'rel\'), this); return false">'.$value.'</a>';
		}
	}

	public function getStatus()
	{
		return parent::getValue() == 1 ? 'on' : 'off';
	}

	public function getValue()
	{
		$baseUri = Environment::getVariable('baseUri');
		$id = $this->dataList->name.'_'.$this->name.'_'.$this->row->id;
		return '<img id="'.$id.'" src="'.$baseUri.'img/core/blank.gif" width="13" height="13" class="column_checkbox_'.$this->getStatus().'"/>';
	}

	protected function createSession()
	{
		$session = Environment::getSession('ajaxCheckbox');
		$ssid = uniqid();
		$session->$ssid = array(
			'column' => $this->column,
			'table' => $this->table
		);

		return $ssid;
	}
}

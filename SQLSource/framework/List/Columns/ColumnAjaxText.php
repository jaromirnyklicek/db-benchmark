<?php
/**
* Column je popis sloupce pro pouziti v Listech (DataGrid, DataList, DataView)
*
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version 1.0
*/

class ColumnAjaxText extends Column
{

	public $action;
	public $inputCss = "";
	public $inputStyle = "";
	public $readonly = FALSE;

	public function setAction($action)
	{
		$this->action = $action;
		return $this;
	}

	public function setInputCss($s)
	{
		$this->inputCss = $s;
		return $this;
	}

	public function setInputStyle($s)
	{
		$this->inputStyle = $s;
		return $this;
	}

	public function setReadonly($s)
	{
		$this->readonly = $s;
		return $this;
	}

	public function render($applyCallback = true)
	{
		$action	= $this->action;

		$value = parent::render($applyCallback);
		$dbRow = $this->row;
		$id = $this->name.'_'.$dbRow->id;
		$url = $this->dataList->getPresenter()->Link($action, array('id' => $dbRow->id));
		if(strpos($url, '?') === FALSE) $char = '?';
		else $char = '&';

		if($this->readonly) {
			return $value;
		}
		else {
			return '<div class="pencil" onclick="$(\'#'.$id.'_1\').hide(); $(\'#'.$id.'_2\').show(); $(\'#'.$id.'_3\').focus()" id="'.$id.'_1">'.(empty($value) ? '&nbsp;' : $value).'</div>'.
					'<div id="'.$id.'_2" style="display:none">
					<input onblur="nette.action(\''.$url.$char.'value=\'+escape(this.value)+\'&el='.$id.'\', this);" value="'.$value.'" id="'.$id.'_3" class="'.$this->inputCss.'" style="'.$this->inputStyle.'"/>
					</div>';
		}
	}


}

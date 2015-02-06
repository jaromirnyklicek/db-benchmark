<?php
/**
* Tlacitko pro hromadne akce.
* V defaultnim nastaveni generuje tlacitko pro hromadne mazani z datagridu
*/
class ActionButton extends Object implements IDataListControl
{
	protected $datalist;
	public $title;
	public $action = 'delete';
	
	public function setDataList($dataList)
	{
		$this->datalist = $dataList;
	}
	
	public function __construct()
	{
		$this->title = _('Smazat vybrané');
	}
	
	protected function xml()
	{
		$link = $this->datalist->jsMultiLink($this->datalist->getPresenter()->link($this->action));
		return '<input type="button" value="'.$this->title.'" class="actionbutton" onclick="'.$link.'"/>';
	}
	
	public function __toString()
	{
		return $this->xml();
	}
}
<?php
/**
* Komponenta pro zobrazeni dat.
* Speciální případ DataView. TableView skládá položky do tabulky. 
* Do sablony se navic predava $item, který je jiz zpracovám pres filtry a callbacky 
* Lze definovat počet sloupců do kterých se záznamy zobrazí. 
* Cele tabulce TABLE resp. kazde bunce TD lze definovat HTML attributy elementu.
* 
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
*/

class TableView extends DataView
{	 
	protected $columns = 1;    
	
	protected $tableAttrib = array();
	protected $tdAttrib = array();
	protected $itemTemplate = 'item.phtml';
	
	public function getColumns()
	{
		return $this->columns;
	}
	
	public function setColumns($value)
	{
		$this->columns = $value;
		$this->tdAttrib['width'] = round(100 / $this->columns).'%';
	}
	
	public function getTdAttrib($value)
	{
		return $this->tdAttrib;
	}
	
	public function setTdAttrib($value)
	{
		$this->tdAttrib = $value;
	}
	
	public function setTableAttrib($value)
	{
		$this->tableAttrib = $value;
	}
	
	public function getTemplatesDir()
	{
		// Vychozi sablony pro dataview
		if($this->templateDir == NULL) $this->templateDir = dirname(__FILE__).'/../Templates/TableView';
		return parent::getTemplatesDir();
	}
	
	public function getItemTemplate()
	{
		return $this->itemTemplate;
	}
	
	public function setItemTemplate($value)
	{
		$this->itemTemplate = $value;
	}
	
	public function renderGrid()
	{

		if(!$this->loaded) $this->loadData();		 
		$order = $this->getOrderInfo();

		$template = $this->createTemplate();
		$template->useAjax = $this->useAjax;
		$template->rows = $this->rows;
		$template->items = $this->items;
		$template->columns = $this->getColumnModel()->getVisibleColumns();
		$template->order = isset($order) ? array($order->column->name => $order->direction) : '';
		$template->tdContainer = Html::el('td');
		$template->itemtemplate = $this->getItemTemplate();
		foreach($this->tdAttrib as $key => $attr) $template->tdContainer->$key($attr);
		//$template->gridid = $this->getUniqueId().'_grid';
		$template->tableContainer = Html::el('table');
		foreach($this->tableAttrib as $key => $attr) $template->tableContainer->$key($attr);
		
		$template->setFile($this->getTemplates()->body);
		$template->registerFilter(/*Nette\Templates\*/'CurlyBracketsFilter::invoke');
		$template->render();
	}
}
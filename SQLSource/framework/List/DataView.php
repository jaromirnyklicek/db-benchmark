<?php
/**
* Komponenta pro zobrazeni dat, které umožnuje přesně přes šablonu definovat vzhled 
* jedně položky. Používá se hlavně na frontendu pro různé výpisy produktů, článků atd…
* Do sablony se navic predava $item, který je jiz zpracovám pres filtry a callbacky 
*  
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
*/

class DataView extends DataList
{	 
	protected $itemsEnabled = TRUE;
	
	/**
	 * Renders table grid
	 */
	public function renderGrid()
	{
		if(!$this->loaded) $this->loadData();
		$template = $this->createTemplate();
		$template->columns = $this->columnModel;
		$order = $this->getOrderInfo();
		$c = isset($order->column->name) ? $order->column->name : NULL;
		$template->order = isset($order) ? array($c => $order->direction) : '';
		$template->filter = $this->getFilterForm();
		$template->setFile($this->getTemplates()->body);
		$template->rows = $this->rows;
		$template->items = $this->items;
		$template->registerFilter(/*Nette\Templates\*/'CurlyBracketsFilter::invoke');
		$template->render();
	}
	
	public function getTemplatesDir()
	{
		// Vychozi sablony pro dataview		  
		if($this->templateDir == NULL) $this->templateDir = dirname(__FILE__).'/../Templates/DataView';
		return parent::getTemplatesDir();
	}
}
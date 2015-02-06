<?php
/**
* jQuery UI Tab
* 
*/
class TabbedControl extends Control
{		
	protected $tabbedArr = array();
	
	/** aplikacne vybrany tabbed **/
	public $selectedTab;
	
	/** zapamatuje si posledni vybrany tabbed do cookies **/
	public $useCookies = TRUE;
	
	/**
	* Pridani tabbedu
	* 
	* @param string $name
	* @param string $title
	* @param string $item 
	* @param string $param
	* @return Tabbed
	*/
	public function addTab($name, $title, $item = NULL, $param = NULL)
	{
		$tabbed = new Tabbed($name, $title);
		$this->tabbedArr[] = $tabbed;		 
		if($item != NULL) $tabbed->addItem($item, $param);
		return $tabbed;
	}
	
	/**
	* Vybere tabbed podle jmena
	* 
	* @param string $name
	*/
	public function selectTab($name) {
		$this->selectedTab = $name;
	}
	
	/**
	* Inicializacni objekt do parametru
	* 
	*/
	public function getInitOption()
	{
		$options = new stdClass();
		if($this->useCookies) $options->cookie = new stdClass();
		return json_encode($options);
	}
	
	public function renderHeader()
	{
		$template = $this->createTemplate();
		$template->setFile(dirname(__FILE__).'/header.phtml');
		$template->registerFilter('CurlyBracketsFilter::invoke');
		$template->tabs = $this->tabbedArr;
		$template->render();
	}
	
	public function renderTabs()
	{
		$template = $this->createTemplate();
		$template->setFile(dirname(__FILE__).'/tabs.phtml');
		$template->registerFilter('CurlyBracketsFilter::invoke');
		$template->tabs = $this->tabbedArr;
		$template->render();		
	}
	
	public function render()
	{
		$template = $this->createTemplate();
		$template->setFile(dirname(__FILE__).'/template.phtml');
		$template->registerFilter('CurlyBracketsFilter::invoke');
		$template->tabs = $this->tabbedArr;
		$template->render();
	}
}

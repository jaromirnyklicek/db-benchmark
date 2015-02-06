<?php


class Tabbed extends Object
{	 
	private $title;
	private $name;
	
	private $childs = array();
	
	public function __construct($name = '', $title = '')
	{
		$this->title = $title;
		$this->name = $name;
	}
	
	public function setTitle()
	{
		$this->title = $title;
		return $this;
	}
	
	public function getTitle()
	{
		return $this->title;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function addItem($item, $param = NULL)
	{
		$this->childs[] = (object) array('object' => $item, 'param' => $param);
		return $this;
	}	
  
			
	public function renderContent()
	{
		foreach($this->childs as $item) {
			if($item->param == NULL) $item->object->render();
			else $item->object->render($item->param);
		}
	}
	
}
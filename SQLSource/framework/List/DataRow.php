<?php
/**
* Datovy radek pouzivany pri callbackovem volani
*	
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version 1.0
*/
class DataRow  
{	
	/**
	* Reference na rodice.
	* Typicky je rodic Column, protoze Column zabali data do typu DataRow pro callbackove volani
	* 
	* @var mixed
	*/
	private $_parent;
	
	/**
	* Data zaznamu
	* 
	* @var mixed
	*/
	protected $data;
	
	/**
	* @param mixed $data
	* @param mixed $parent
	* @return DataRow
	*/
	public function __construct($data, $parent)
	{
		$this->_parent = $parent;
		$this->data = $data;
	}
	
	public function __get($name)
	{
		return $this->data->$name;
	}
	
	public function __isset($name) 
	{
	  if (isset($this->data->$name)) {
		return true;
	  }
	  return false;
	}			  
	
	public function __set($name, $value)
	{
		$this->data->$name = $value;
		//throw new NotImplementedException('Objekt je readonly');
	}
	
	/**
	* Vrati referenci na rodice
	* @return object 
	*/
	public function getParent()
	{
		return $this->_parent;
	}	 
	
	public function getData()
	{
		return $this->data;
	}
	
}
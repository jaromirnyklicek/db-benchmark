<?php

class DbInfoBase extends Object 
{	
	
	/**
	* Hashtable
	*/
	protected $items;
	
	 /**
	 * Uchovani instance pro singleton
	 */
	protected static $instance = NULL;

	
	public function __construct()
	{
	   $this->items = new Hashtable();		 
	}
	
	
	/**** Interni funkce ***/
	
	public function add($key, $value)
	{
		$this->items->add($key, $value);
	}
	
	public static function singleton() {
		if (!isset(self::$instance)) {
			$class = __CLASS__;
			self::$instance = new $class();
		}
		return self::$instance;
	}	 
	
	public static function get($key, $value = NULL)
	{
		$dbinfo = self::singleton();
		if (func_num_args() == 1) {
			 return $dbinfo->items[$key]; 
		}
		if($value == NULL) return NULL;
		return $dbinfo->items[$key][$value];
	}
}

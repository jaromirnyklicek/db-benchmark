<?php
/**
* Trida pro vytvareni instanci trid
* 
* @author Ondrej Novak
* @link http://livedocs.adobe.com/flex/2/langref/mx/core/ClassFactory.html
* @copyright Copyright Adobe Systems Incorporated 
* Z ActionScript3 do PHP prepsal Ondrej Novak
*/
class ClassFactory implements IFactory
{	
	private $generator;
	private $properties;
	
	public function __construct($generator = NULL, $properties = NULL)
	{
		$this->generator = $generator;
		$this->properties = $properties;
	}
	
	public function setProperties($arr = array())
	{
		$this->properties = $arr;
	}

	public function newInstance()
	{
		$obj = new $this->generator(); 
		if($this->properties != NULL) {
			foreach($this->properties as $key => $value) {
				$obj->$key = $value;
			}
		}				
		return $obj;
	}	
} 
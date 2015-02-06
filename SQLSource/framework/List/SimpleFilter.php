<?php
/**
* Jednoduchy filtr, ktery je bez interakce uzivatele.
* Slouzi pro skladani SQL dotazu. Tvari se jako pole, protoze implementuje rozhrani ArrayAccess  
* Do pole lze pridavat jednotlive SQL podminky, ktere jsou nakonec spojeny logickou spoujkou AND
* 
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
*/

class SimpleFilter implements IDataSourceFilter, ArrayAccess
{
	
	protected $items = array();
	
	/**
	* Vrati SQL dotaz. Spoji vyrazy spojkou AND
	* 
	*/
	public function buildSql()
	{			
		if(empty($this->items)) return 1;
		else {
			$items = array();
			foreach($this->items as $item) {
				$items[] = '('.$item.')';
			}
			return join(' AND ', $items);
		}
	}
	
	/********************* interface \ArrayAccess ****************d*g**/

	final public function offsetSet($name, $sql)
	{
		if($name == NULL) $this->items[] = $sql; 
		else $this->items[$name] = $sql;
	}

	final public function offsetGet($name)
	{
		return $this->items[$name];
	}
	 
	final public function offsetExists($name)
	{
		return isset($this->items[$name]);
	}	  

	final public function offsetUnset($name)
	{
		unset($this->items[$name]);
	}
}
<?php
/**
* Bázová třída pro DataSource. Defunuje přidávání helperů a callbacků pro sloupce.
* Implementuje rozhraní Iterator, takže lze snadno záznamy procházet přes foreach. Pokud se iterátor
* inicializuje před načtením dat, je automaticky volána funkce loadData()
*
*
* @package DataSource
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version $1.0$
*/


abstract class BaseSource extends Object implements Iterator
{

	 /** jiz nacteno? **/
	protected $loaded = FALSE;

	/**
	 * @var array
	 */
	protected $data;


	/**
	 * Pole pro callback funkce
	 * @see addCallback()
	 * @var array
	 */
	protected $callbackArr = array();


   public function loadData($params = NULL)
   {
		$this->loaded = TRUE;
   }

   /**
   * Aplikace jednoho helperu(callbacku) na jeden zaznam
   *
   * @param array $callback
   * @param object $row
   */
   protected function applyCallback($callback, $row)
   {
	   if(is_callable($callback['func'])) {
	   	   		if(property_exists($row, $callback['column'])) {
					$value = $row->{$callback['column']};
				}
				else $value = '';

				if($callback['type'] == 'simple') {
					if($callback['params'] != NULL) $args = array_merge(array($value), $callback['params']);
					else $args = array($value);
				}
				if($callback['type'] == 'function') {
					$dataRow = new DataRow($row, $this);
					if($callback['params'] != NULL) $args = array_merge(array($value, $dataRow), $callback['params']);
					else $args = array_merge(array($value, $dataRow));
				}
				$value = call_user_func_array($callback['func'], $args);
				$row->{$callback['column']}= $value;
		}
		else throw new Exception('Invalid callback '.$callback['func'].'()');
   }

   /**
   * Aplikace vsech helperu a callbacku na data
   *
   * @param mixed $data
   */
   protected function applyCallbacks($data)
   {
		if(empty($this->callbackArr)) return $data;

		foreach ($this->data as $row) {
			foreach ($this->callbackArr as $callback) {
				   $this->applyCallback($callback, $row);
			}
		}
		return $data;
	}

	/**
	* Pridani helperu. Na kazdy zaznam se aplikuje helper pro specifikovany sloupec $column.
	* Helpery lze aplikovat i zpetne po nacteni zaznamu (z databaze)
	*
	* @param string $column		sloupec, na ktery se helper aplikuje
	* @param mixed $function	callbackova funkce
	* @param array $args		parametry callbackove funkce
	*/
	public function addHelper($column, $function, $args = NULL)
	{
		if (func_num_args() > 3) {
			$argv = func_get_args();
			$args = array_merge(array($args), array_slice($argv, 3));
		}
		$f = array();
		$f['column'] = $column;
		$f['type'] = 'simple';
		$f['func'] = $function;
		$f['params'] = $args == NULL || is_array($args) ? $args : array($args);
		$this->callbackArr[] = $f;
		if($this->loaded) {
			foreach ($this->data as $row) $this->applyCallback($f, $row);
			return $this;
		}
		return $this;
	}

	/**
	* Pridani callbacku. Na kazdy zaznam se aplikuje helper pro specifikovany sloupec $column.
	* Callbacky lze aplikovat i zpetne po nacteni zaznamu (z databaze)
	*
	* @param string $column		sloupec, na ktery se callback aplikuje
	* @param mixed $function	callbackova funkce
	* @param array $args		parametry callbackove funkce
	*/
	public function addCallback($column, $function, $args = NULL)
	{
		if (func_num_args() > 3) {
			$argv = func_get_args();
			$args = array_merge(array($args), array_slice($argv, 3));
		}
		$f = array();
		$f['column'] = $column;
		$f['type'] = 'function';
		$f['func'] = $function;
		$f['params'] = $args == NULL || is_array($args) ? $args : array($args);
		$this->callbackArr[] = $f;
		if($this->loaded) {
			foreach ($this->data as $row) $this->applyCallback($f, $row);
			return $this;
		}
		return $this;
	}


	/** Interface Iterator	 */

	public function key()
	{
		return key($this->data);
	}

	public function current()
	{
		return current($this->data);
	}

	public function next()
	{
		return next($this->data);
	}

	public function rewind()
	{
		if(!$this->loaded) $this->loadData();
		return reset($this->data);
	}

	public function valid()
	{
		return (bool) $this->current();
	}
}
<?php

/**
 * Sloupec pro CSV Reader
 * 
 * @author karel.kolask@viaaurea.cz
 * @copyright (c) Via Aurea, s.r.o.
 */
class CSVColumn extends Object
{
	/**
	 * Jméno sloupce
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Index sloupce
	 *
	 * @var mixed
	 */
	private $index;

	/**
	 * Pole pro callback funkce
	 * @see addCallback()
	 * @var array
	 */
	protected $callbackArr = array();

	/**
	 * Pole pro validační pravidla
	 * 
	 * @var array 
	 */
	protected $ruleArr = array();


	/**
	 * Konstruktor
	 *
	 * @param string $name		 Jméno sloupce
	 * @return Column
	 */
	public function __construct($name, $index = NULL)
	{
		$this->name = $name;
		$this->index = $index;
	}


	/**
	 * Vrati jmeno sloupce.
	 *
	 * @return type
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * Vrati index sloupce.
	 *
	 * @return type
	 */
	public function getIndex()
	{
		return $this->index;
	}


	/**
	 * Vrátí hodnotu sloupce s aplikovanýma callbackama a helperama
	 * 
	 * @param type $value
	 * @param type $row
	 * @param type $callback
	 * @return type
	 * @throws Exception 
	 */
	public function applyCallbacks($value, $row)
	{
		if (isset($this->callbackArr)) {
			foreach ($this->callbackArr as $callback) {

				if (is_callable($callback['func'])) {
					if ($callback['type'] == 'simple') {
						if ($callback['params'] != NULL) {
							$args = array_merge(array($value), $callback['params']);
						} else {
							$args = array($value);
						}
					}
					if ($callback['type'] == 'function') {
						$dataRow = new DataRow($row, $this);
						if ($callback['params'] != NULL) {
							$args = array_merge(array($value, $dataRow), $callback['params']);
						} else {
							$args = array_merge(array($value, $dataRow));
						}
					}
					$value = call_user_func_array($callback['func'], $args);
				} else {
					throw new Exception('Invalid callback ' . $callback['func'] . '()');
				}
			}

			return $value;
		}
	}


	/**
	 * Aplikuje validační pravidla, při neúspěchu vrací chybovou hlášku
	 * 
	 * @param type $value
	 * @return type 
	 */
	public function applyRules($value, $row)
	{
		if (isset($this->ruleArr)) {
			foreach ($this->ruleArr as $rule) {

				if (!$rule['regexp']) {
					$args = array_merge(array($value), array((array) $row));
					if ($rule['args'] != NULL) {
						$args = array_merge($args, (array) $rule['args']);
					}
					$valid = call_user_func_array($rule['operation'], $args);
				} else {
					$valid = preg_match($rule['operation'], $value);
				}

				if (!$valid) {
					return $rule['message'];
				}
			}
		}
	}


	public function addHelper($function, $args = NULL)
	{
		if (func_num_args() > 2) {
			$argv = func_get_args();
			$args = array_merge(array($args), array_slice($argv, 2));
		}
		$f = array();
		$f['type'] = 'simple';
		$f['func'] = $function;
		$f['params'] = $args == NULL || is_array($args) ? $args : array($args);
		$this->callbackArr[] = $f;
		return $this;
	}


	public function addCallback($function, $args = NULL)
	{
		if (func_num_args() > 2) {
			$argv = func_get_args();
			$args = array_merge(array($args), array_slice($argv, 2));
		}
		$f = array();
		$f['type'] = 'function';
		$f['func'] = $function;
		$f['params'] = $args == NULL || is_array($args) ? $args : array($args);
		$this->callbackArr[] = $f;
		return $this;
	}


	public function addRule($operation, $message, $args = NULL)
	{
		$rule = array();
		$rule['operation'] = $operation;
		$rule['message'] = $message;
		$rule['regexp'] = false;
		$rule['args'] = $args;
		$this->ruleArr[] = $rule;
		return $this;
	}


	public function addRuleRegExp($operation, $message, $args = NULL)
	{
		$rule = array();
		$rule['operation'] = $operation;
		$rule['message'] = $message;
		$rule['regexp'] = true;
		$rule['args'] = $args;
		$this->ruleArr[] = $rule;
		return $this;
	}

}

<?php
/**
 * Column je popis sloupce pro pouziti v Listech (DataGrid, DataList, DataView)
 * Definuje zejména zobrazení buňky a její vlastnosti v DataGridu. Ke sloupcům lze
 * přídávat Helpery a Callbacky stejně jako pro datové zdroje.
 * S tím rozdílem, že callback navázaný na sloupec se aplikuje v době vykreslení,
 * zatímco u datového zdroje se aplikuje při předání dat komponentě, tedy před vykreslením.
 *
 * @package Lists
 * @author Ondrej Novak
 * @copyright Copyright (c) 2009, Ondrej Novak
 * @version 1.0
 */

class Column extends Object
{

	/**
	 * Název sloupce (obvykle v záhlaví)
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Navázání na datový atribut záznamu
	 *
	 * @var string
	 */
	private $member;

	/**
	 * Jméno sloupce
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Šířka sloupce v px
	 *
	 * @var int
	 */
	private $width = '';

	/**
	 * Obalení buňky pres funkci sprintf
	 *
	 * @var string
	 */
	private $envelope = '%s';

	/**
	 * CSS style přiřazený buňce
	 *
	 * @var string
	 */
	private $style = '';

	/**
	 * CSS třída buňky
	 *
	 * @var string
	 */
	private $cssClass = '';

	/**
	 * Povolení řazení podle sloupce
	 *
	 * @var bool
	 */
	protected $sortable = TRUE;

	/**
	 * Zobrazit sloupec
	 *
	 * @var bool
	 */
	private $visible = TRUE;

	/** @var array user options */
	private $options = array();

	/**
	 * Reference na ColumnModel, do ktereho sloupec patri
	 *
	 * @var ColumnModel
	 */
	private $parent;

	/**
	 * Reference na DataList, do ktereho sloupec patri
	 *
	 * @var DatList
	 */
	private $dataList;

	/**
	 * Aktuálně zobrazovaný řádek
	 *
	 * @var mixed
	 */
	private $row;

	/**
	 * Pole pro callback funkce
	 * @see addCallback()
	 * @var array
	 */
	protected $callbackArr = array();

	/**
	 * Pole pro callback funkce
	 * @see addCellCallback()
	 * @var array
	 */
	protected $cellCallbackArr = array();

	/**
	 * Ma se obsah pred vypisem escapovat?
	 * @var bool
	 */
	protected $escaping = FALSE;

	/**
	 * Konstruktor
	 *
	 * @param string $name         Jméno sloupce
	 * @param string $member         Navázání na datový atribut záznamu
	 * @param mixed $title         Název sloupce (obvykle v záhlaví)
	 * @param mixed $width         Šířka sloupce v px
	 * @param mixed $style         CSS style přiřazený buňce
	 * @param mixed $cssClass     CSS třída buňky
	 * @param mixed $envelope     Obalení buňky pres funkci sprintf
	 * @return Column
	 */
	public function __construct(
		$name,
		$member = '',
		$title = '',
		$width = '',
		$style = '',
		$cssClass = '',
		$envelope = '%s')
	{
		$this->name = $name;
		$this->member = $member;
		$this->width = $width;
		$this->title = $title;
		$this->style = $style;
		$this->cssClass = $cssClass;
		$this->envelope = $envelope;
	}

	public function setVisible($value = TRUE)
	{
		$this->visible = $value;
		return $this;
	}

	public function getVisible()
	{
		return $this->visible;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setMember($value)
	{
		$this->member = $value;
		return $this;
	}

	public function getMember()
	{
		return $this->member;
	}

	public function setTitle($value)
	{
		$this->title = $value;
		return $this;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function setWidth($value)
	{
		$this->width = $value;
		return $this;
	}

	public function getWidth()
	{
		return $this->width;
	}

	public function setStyle($value)
	{
		$this->style = $value;
		return $this;
	}

	public function getStyle()
	{
		return $this->style;
	}

	public function setEnvelope($value)
	{
		$this->envelope = $value;
		return $this;
	}

	public function getEnvelope()
	{
		return $this->envelope;
	}

	public function setCssClass($value)
	{
		$this->cssClass = $value;
		return $this;
	}

	public function getCssClass()
	{
		return $this->cssClass;
	}

	public function setSortable($value)
	{
		$this->sortable = $value;
		return $this;
	}

	public function getSortable()
	{
		return $this->sortable;
	}

	public function setParent($parent)
	{
		$this->parent = $parent;
	}

	public function setDataList($parent)
	{
		$this->dataList = $parent;
	}

	public function getDataList()
	{
		return $this->dataList;
	}

	public function getEscaping()
	{
		return $this->escaping;
	}

	public function setEscaping($escaping)
	{
		$this->escaping = $escaping;
		return $this;
	}

	/**
	 * Nastavi aktualni zpracovavany radek
	 *
	 * @param mixed $value
	 */
	public function setRow($value)
	{
		$this->row = $value;
	}

	public function getRow()
	{
		return $this->row;
	}

	/**
	 * SQL slouzi pro predani informare jak se na v SQL radit.
	 * Nesouvisi se sloupcem ($member) , ktery se do bunky zobrazuje
	 *
	 * @param mixed $value
	 * @return Column
	 */
	public function setSql($value)
	{
		return $this->setOption('sql', $value);
	}

	public function getSql($value)
	{
		return $this->getOption('sql');
	}

	/**
	 * Call to undefined method.
	 *
	 * @param  string  method name
	 * @param  array   arguments
	 * @return mixed
	 * @throws \MemberAccessException
	 */
	public function __call($name, $args)
	{
		$class = get_class($this);

		if ($name === '') {
			throw new /*\*/
			MemberAccessException("Call to class '$class' method without name.");
		}

		// settery
		if (preg_match('/^set([A-Za-z0-9]+)/', $name, $m)) {
			$name = strtolower($m[1]);
			$rp = new /*\*/
			ReflectionProperty($class, $name);
			if (!$rp->isStatic()) {
				$this->$name = $args[0];
				return $this;
			}
		}
		throw new /*\*/
		MemberAccessException("Call to undefined method $class::$name().");
	}


	/**
	 * Sets user-specific option.
	 *
	 * Common options:
	 * - 'sql' - for ordering
	 *
	 * @param  string key
	 * @param  mixed  value
	 * @return Column  provides a fluent interface
	 */
	public function setOption($key, $value)
	{
		if ($value === NULL) {
			unset($this->options[$key]);

		} else {
			$this->options[$key] = $value;
		}
		return $this;
	}

	/**
	 * Returns user-specific option.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	final public function getOption($key, $default = NULL)
	{
		return isset($this->options[$key]) ? $this->options[$key] : $default;
	}

	/**
	 * Nastavi priznak pro minimalni rozliseni displaye, od kdy se bude sloupec zobrazovat.
	 * Jak se to bude chovat trida Column neresi.
	 * Vetsinou se to bude chovat, tak ze se ke sloupci vygeneruje css trida (mediaquery)
	 */
	public function setMinViewport($value)
	{
		$this->setOption('viewport', $value);
		return $this;
	}


	/**
	 * Vyrenderuje obsah sloupce
	 *
	 * @param bool $applyCallback
	 * @return string
	 */
	public function render($applyCallback = TRUE)
	{
		$value = $this->getValue();
		if ($this->escaping && is_string($value)) {
			$value = htmlspecialchars($value, ENT_QUOTES);
		}
		// aplikovani helperu a callbacku
		if (isset($this->callbackArr) && $applyCallback) {
			foreach ($this->callbackArr as $callback) {
				$value = $this->applyCallback($value, $callback);
			}
		}
		if ($this->envelope == '%s') return $value;
		if ($value !== '' && $value !== NULL) return sprintf($this->envelope, $value);
	}

	public function getValue()
	{
		$m = $this->member;
		// jde o atribut objektu ?
		if (strpos($m, '->')) {
			$arr = explode('->', $m);
			$i = 0;
			$obj = $this->row;
			while ($i < count($arr)) {
				$m = $arr[$i];
				$obj = $obj->$m;
				$i++;
			}
			$value = $obj;
		} elseif (!empty($m)) {
			$ex = new MemberAccessException('Undefined index \'' . $m . '\' in data source.');
			if ($this->row instanceof \LeanMapper\Entity) {
				try {
					$value = $this->row->$m;
				} catch (\LeanMapper\MemberAccessException $e) {
					throw new $ex;
				}
			} else {
				if ($this->row instanceof ORM) {
					$arr = $this->row->as_array();
				} else {
					$arr = (array)$this->row;
				}

				if (!array_key_exists($m, $arr)) {
					throw $ex;
				} else {
					$value = $this->row->$m;
				}
			}
		} else $value = '';
		return $value;
	}

	public function applyCallback($value, $callback)
	{
		if (is_callable($callback['func'])) {
			if ($callback['type'] == 'simple') {
				if ($callback['params'] != NULL) $args = array_merge(array($value), $callback['params']);
				else $args = array($value);
			}
			if ($callback['type'] == 'function') {
				$dataRow = new DataRow($this->row, $this);
				if ($callback['params'] != NULL) $args = array_merge(array($value, $dataRow), $callback['params']);
				else $args = array_merge(array($value, $dataRow));
			}
			return call_user_func_array($callback['func'], $args);
		} else {
			throw new Exception('Invalid callback ' . $callback['func'] . '()');
		}
	}

	public function addHelper($function, $args = NULL)
	{
		if (func_num_args() > 1) {
			$argv = func_get_args();
			$args = array_slice($argv, 1);
		}
		$f = array();
		$f['type'] = 'simple';
		$f['func'] = $function;
		$f['params'] = $args;
		$this->callbackArr[] = $f;
		return $this;
	}

	public function addFilter($function, $args = NULL)
	{
		trigger_error('Deprecated. Use addHelper() instead.');
		return $this->addHelper($function, $args);
	}

	public function addCallback($function, $args = NULL)
	{
		if (func_num_args() > 1) {
			$argv = func_get_args();
			$args = array_slice($argv, 1);
		}
		$f = array();
		$f['type'] = 'function';
		$f['func'] = $function;
		$f['params'] = $args;
		$this->callbackArr[] = $f;
		return $this;
	}

	public function addCellCallback($function, $args = NULL)
	{
		if (func_num_args() > 2) {
			$argv = func_get_args();
			$args = array_merge(array($args), array_slice($argv, 2));
		}
		$f = array();
		$f['func'] = $function;
		$f['params'] = $args == NULL || is_array($args) ? $args : array($args);
		$this->cellCallbackArr[] = $f;
		return $this;
	}

	/**
	 * Vrati metainformace o zpracovavane bunce.
	 * Vysledek je asociaticni pole, ktere se v sablone zpracuje do atributu <td>
	 *
	 * @param mixed $item
	 * @return array
	 */
	public function getCellMeta()
	{
		$value = $this->getValue();
		$res = array();
		foreach ($this->cellCallbackArr as $callback) {
			if (is_callable($callback['func'])) {
				$dataRow = new DataRow($this->row, $this);
				if ($callback['params'] != NULL) $args = array_merge(array($value, $dataRow), $callback['params']);
				else $args = array_merge(array($value, $dataRow));
				$value = call_user_func_array($callback['func'], $args);
				if ($value != NULL) $res = array_merge($res, $value);
			} else {
				throw new Exception('Invalid callback ' . $callback['func'] . '()');
			}
		}
		return $res;
	}


	public function __toString()
	{
		try {
			return (String)$this->render();

		} catch ( /*\*/
		Exception $e) {
			trigger_error($e->getMessage(), E_USER_WARNING);
			return '';
		}
	}
}

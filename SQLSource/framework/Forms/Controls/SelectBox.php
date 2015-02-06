<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license	Nette license
 * @link	   http://nettephp.com
 * @category   Nette
 * @package    Nette\Forms
 * @version    $Id: SelectBox.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/


/**
 * Select box control that allows single item selection.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl, Ondrej NOvak
 * @package    Nette\Forms
 */
class SelectBox extends FormControl
{
	/** @var array */
	protected $items = array();

	/** @var array */
	protected $allowed = array();

	/** @var bool */
	protected $skipFirst = FALSE;

	/** @var bool */
	protected $useKeys = TRUE;

	protected $nullable = TRUE;
	protected $nullOption = NULL;
	protected $autoSubmit = FALSE;
	protected $autoSubmitAjax = FALSE;


	/**
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  int	   number of rows that should be visible
	 */
	public function __construct($label, $items = NULL, $size = NULL)
	{
		parent::__construct($label);
		$this->control->setName('select');
		$this->control->size = $size > 1 ? (int) $size : NULL;
		$this->control->onfocus = 'this.onmousewheel=function(){return false}';  // prevents accidental change in IE
		$this->label->onclick = 'return false';  // prevents deselect in IE 5 - 6
		if($this->nullOption === NULL) {
			$this->nullOption(' — '._('vyberte hodnotu').' — ');
		}
		if ($items !== NULL) {
			$this->setItems($items);
		}
	}


	/**
	 * Returns selected item key.
	 * @return mixed
	 */
	public function getValue()
	{
		$allowed = $this->allowed;
		if ($this->skipFirst) {
			$allowed = array_slice($allowed, 1, count($allowed), TRUE);
		}
		$iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($allowed));
		$keys = array();
		foreach ($iterator as $key => $value) {
			$keys[] = $key;
		}
		if($this->value === "" && !in_array($this->value, $keys, TRUE)) return NULL;
		return is_scalar($this->value) && in_array($this->value, $keys) ? $this->value : NULL;
	}


	/**
	 * Returns selected item key (not checked).
	 * @return mixed
	 */
	public function getRawValue()
	{
		return is_scalar($this->value) ? $this->value : NULL;
	}



	/**
	 * Ignores the first item in select box.
	 * @param  bool
	 * @return SelectBox  provides a fluent interface
	 */
	public function skipFirst($value = TRUE)
	{
		$this->skipFirst = (bool) $value;
		return $this;
	}



	/**
	 * Is first item in select box ignored?
	 * @return bool
	 */
	final public function isFirstSkipped()
	{
		return $this->skipFirst;
	}



	/**
	 * Are the keys used?
	 * @return bool
	 */
	final public function areKeysUsed()
	{
		return $this->useKeys;
	}


	public function nullOption($value)
	{
		$this->nullOption = $value;
		return $this;
	}

	public function setNullOption($value)
	{
		$this->nullOption = $value;
		return $this;
	}

	public function setAutoSubmit($value = TRUE, $ajax = FALSE)
	{
		$this->autoSubmit = $value;
		$this->autoSubmitAjax = $ajax;
		return $this;
	}


	/**
	 * Sets items from which to choose.
	 * @param  array
	 * @return SelectBox  provides a fluent interface
	 */
	public function setItems($items, $useKeys = TRUE)
	{
		$this->items = $items;
		$this->allowed = array();
		$this->useKeys = (bool) $useKeys;

		foreach ($items as $key => $value) {
			if (!is_array($value)) {
				$value = array($key => $value);
			}

			foreach ($value as $key2 => $value2) {
				if (!$this->useKeys) {
					if (!is_scalar($value2)) {
						throw new /*\*/InvalidArgumentException("All items must be scalars.");
					}
					$key2 = $value2;
				}

				if (isset($this->allowed[$key2])) {
					throw new /*\*/InvalidArgumentException("Items contain duplication for key '$key2'.");
				}

				$this->allowed[$key2] = $value2;
			}
		}
		return $this;
	}



	/**
	 * Returns items from which to choose.
	 * @return array
	 */
	final public function getItems()
	{
		return $this->items;
	}



	/**
	 * Returns selected value.
	 * @return string
	 */
	public function getSelectedItem()
	{
		if (!$this->useKeys) {
			return $this->getValue();

		} else {
			$value = $this->getValue();
			return $value === NULL ? NULL : $this->allowed[$value];
		}
	}

	public function nullable($value = true)
	{
		$this->nullable = $value;
		return $this;
	}

	public function setNullable($value = true)
	{
		$this->nullable = $value;
		return $this;
	}


	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$control = clone parent::getControl();
		if($this->autoSubmit) {
			if($this->autoSubmitAjax) {
				$control->onchange($control->onchange.';this.form.onsubmit()');
			}
			else {
				$control->onchange('this.form.submit()');
			}
		}
		$selected = $this->getValue();
		$selected = is_array($selected) ? array_flip($selected) : array($selected => TRUE);
		$option = /*Nette\Web\*/Html::el('option');

		$items = $this->prepareItems();
		foreach ($items as $key => $value) {
			if (!is_array($value)) {
				$value = array($key => $value);
				$dest = $control;

			} else {
				$dest = $control->create('optgroup')->label($key);
			}

			foreach ($value as $key2 => $value2) {
				if ($value2 instanceof /*Nette\Web\*/Html) {
					$dest->add((string) $value2->selected(isset($selected[$key2])));

				} elseif ($this->useKeys) {
					$dest->add((string) $option->value($key2)->selected(isset($selected[$key2]))->setHtml($value2));

				} else {
					$dest->add((string) $option->selected(isset($selected[$value2]))->setHtml($value2));
				}
			}
		}
		return $control;
	}


	protected function prepareItems()
	{
		$items = array();
		if ($this->nullable) {
			$items[''] = $this->nullOption;
		}
		return $items + $this->_prepareItems($this->items);
	}


	protected function _prepareItems(array $items)
	{
		foreach ($items as $k => $i) {
			if (is_array($i)) {
				$group = $this->_prepareItems($i);
				if (!empty($group)) {
					$items[$k] = $group;
				}
			} else {
				if (isset($this->allowed[$k])) {
					$items[$k] = $i;
				}
			}
		}
		return $items;
	}


	/**
	 * Filled validator: has been any item selected?
	 * @param  IFormControl
	 * @return bool
	 */
	public static function validateFilled(IFormControl $control)
	{
		$value = $control->getValue();
		return is_array($value) ? count($value) > 0 : $value !== NULL;
	}



	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		if(empty($column)) return;
		$value = $this->getValue();
		if($value === NULL) return NULL;
		if(!is_array($column)) $column = array($column);
		$s = array();
		foreach($column as $c) {
			$s[] = $c.' = "'.Database::instance()->escape_str($value).'"';
		}
		return '('.join(' OR ', $s).')';
	}

	public function getTextValue()
	{
		$value = $this->getValue();
		if($value === NULL) return NULL;
		return isset($this->items[$value]) ? $this->items[$value] : NULL;
	}
}

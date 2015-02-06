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
 * @version    $Id: Checkbox.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/



/**
 * Check box control. Allows the user to select a true or false condition.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl, Ondrej Novak
 * @package    Nette\Forms
 */
class Checkbox extends FormControl
{
	/**
	* Prepisy hodnot true a false na jinde hodnoty. Pr. 1 => "y", 0 => "n"
	* Pouze ke zpetne kompatabilite se starym adminen (DB).
	* Nove pouzivame datovy typ TINYINT
	*
	* @var array
	*/
	protected $items;

	protected $cssClass = '';

	protected $autoSubmit = FALSE;

	/**
	 * @param  string  label
	 */
	public function __construct($label = '', $items = array(1 => 1, 0 => 0))
	{
		parent::__construct($label);
		$this->control->type = 'checkbox';
		$this->value = FALSE;
		$this->items = $items;
	}



	/**
	 * Sets control's value.
	 * @param  bool
	 * @return void
	 */
	public function setValue($value)
	{
		$this->value = is_scalar($value) ? (bool) $value : FALSE;
		$this->valueDefault = $this->value;
		return $this;
	}

	public function setAutoSubmit($value = TRUE)
	{
		$this->autoSubmit = $value;
		return $this;
	}

	public function items($value)
	{
		$this->items = $value;
	}

	public function setValueIn($value)
	{
		$arr = array_flip($this->items);
		if($this->items != array(1 => 1, 0 => 0)) {
			if($value === NULL || $value === false) $value = $this->items[0];
			elseif($value === TRUE) $value = $this->items[1];
			if(isset($arr[$value])) $this->value = ((bool)$arr[$value]);
			else $this->value = FALSE;
		}
		else {
			$this->value = ((bool)$value);
		}
		return $this;
	}

	public function getValueOut()
	{
		if($this->items != array(1 => 1, 0 => 0)) {
			return $this->items[(int)$this->value];
		}
		else return (int)$this->value;
	}

	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	public function loadHttpData($data)
	{
		$name = $this->getHtmlName();
		if(isset($data[$name.'_sent'])) {
			$this->value = isset($data[$name]) ? $data[$name] : NULL;
		}
		elseif($this->nullIfNotSent) $this->value = NULL;
	}

	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$control = parent::getControl()->checked($this->value)->value(1);
		if($this->autoSubmit) {
			$f = $this->getForm();
			if($f->useAjax) $control->onclick('this.form.onsubmit()');
			else $control->onclick('this.form.submit()');
		}
		$control = Html::el()->add($control);
		$control->add(Html::el('input')->type('hidden')->value(1)->name($this->getHtmlName().'_sent')->id($this->getId().'_sent'));
		return $control;
	}

	public static function validateJSFilled(IFormControl $control)
	{
		$js = $control->validateJsBase();
		$js .= "res = element.checked;";
		return $js;
	}

	public static function validateJSEqual(IFormControl $control, $arg)
	{
		$arg = (bool) $arg;
		$js = $control->validateJsBase();
		$tmp = array();
		foreach ((is_array($arg) ? $arg : array($arg)) as $item) {
				if ($item instanceof IFormControl) { // compare with another form control?
					$name = $item->getName();
					$form = $control->getSubForm();
					$item = $form[$name];
					$tmp[] = "val==document.getElementById('" . $item->getId() . "').checked";
				} else {
					$tmp[] = "val==" . json_encode($item);
				}
		}
		return $js ."res = (" . implode(' || ', $tmp) . ");";
	}

	public function validateJsBase()
	{
		$tmp = "element = document.getElementById('" . $this->getId() . "');\n\t";
		$tmp2 = "var val = element.checked\n\t";
		return $tmp.$tmp2;
	}

	/**
	* Vráti javascript, ktery po provedeni vrati do promenne $res stav, jestli byl control zmenen od vychoziho stavu.
	* Pouziva se pri odstranovani "prazdnych" subformu z multiformu.
	*/
	public function checkEmptyJs()
	{
		$js = $this->validateJsBase();
		$js .= "res = val == ".json_encode((bool)$this->getDefaultValue())."; \n\t";
		return $js;
	}

	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValueOut();
		if(empty($value) || empty($column)) return null;
		if(!is_array($column)) $column = array($column);
		$s = array();
		foreach($column as $c) {
			$s[] = $c.' = "'. Database::instance()->escape_str($value).'"';
		}
		return '('.join(' OR ', $s).')';
	}
}

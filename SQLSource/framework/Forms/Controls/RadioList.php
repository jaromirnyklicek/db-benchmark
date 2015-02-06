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
 * @version    $Id: RadioList.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/


/**
 * Set of radio button controls.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
class RadioList extends FormControl
{
	/** @var Nette\Web\Html  separator element template */
	public $separator;

	/** @var Nette\Web\Html  container element template */
	protected $container;

	/** @var array */
	protected $items = array();

	protected $cssClass = '';


	/**
	 * @param  string  label
	 * @param  array   options from which to choose
	 */
	public function __construct($label, array $items = NULL)
	{
		parent::__construct($label);
		$this->control->type = 'radio';
		$this->container = /*Nette\Web\*/Html::el();
		$this->separator = /*Nette\Web\*/Html::el()->setHtml(' ');
		if ($items !== NULL) $this->setItems($items);
	}



	/**
	 * Returns selected radio value.
	 * @param  bool
	 * @return mixed
	 */
	public function getValue($raw = FALSE)
	{
		return is_scalar($this->value) && ($raw || isset($this->items[$this->value])) ? $this->value : NULL;
	}



	/**
	 * Sets options from which to choose.
	 * @param  array
	 * @return RadioList  provides a fluent interface
	 */
	public function setItems(array $items)
	{
		$this->items = $items;
		return $this;
	}



	/**
	 * Returns options from which to choose.
	 * @return array
	 */
	final public function getItems()
	{
		return $this->items;
	}



	/**
	 * Returns separator HTML element template.
	 * @return Nette\Web\Html
	 */
	final public function getSeparatorPrototype()
	{
		return $this->separator;
	}



	/**
	 * Returns container HTML element template.
	 * @return Nette\Web\Html
	 */
	final public function getContainerPrototype()
	{
		return $this->container;
	}



	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$container = clone $this->container;
		$separator = (string) $this->separator;
		$control = parent::getControl();
		$id = $control->id;
		$counter = 0;
		$value = $this->value === NULL ? NULL : (string) $this->getValue();
		$label = /*Nette\Web\*/Html::el('label');

		foreach ($this->items as $key => $val) {
			$control->id = $label->for = $id . '-' . $key;
			$control->checked = (string) $key == $value;
			$control->value = $key;

			/* Novak - vse bude HTML */
			/*if ($val instanceof Html) {
				$label->setHtml($val);
			} else {
				$label->setText($val);
			}*/
			$label->setHtml($val);

			// za posledni polozkou nebude oddelovac
			if($counter == count($this->items)-1) $separator = "";

			$container->add((string) $control . (string) $label . $separator);
			$counter++;

		}

		return $container;
	}

	/**
	* Vrati pouze jeden radio input
	*
	* @param mixed $key
	* @return Nette\Web\Html
	*/
    public function getRadioInput($key)
	{
		$control = parent::getControl();
		$id = $control->id;
		$value = $this->value === NULL ? NULL : (string) $this->getValue();
		$control->id = $id . '-' . $key;
		$control->checked = (string) $key == $value;
		$control->value = $key;
		return $control;
	}

	/**
	* Vrati pouze jeden radio label k inputu
	* @param mixed $key
	* @return Nette\Web\Html
	*/
    public function getRadioLabel($key)
	{
		$label = Html::el('label');
		$control = parent::getControl();
		$id = $control->id;
		$label->for = $id . '-' . $key;
		$label->setHtml($this->items[$key]);
		return $label;
	}

	/**
	 * Generates label's HTML element.
	 * @return void
	 */
	public function getLabel()
	{
		$label = parent::getLabel();
		$label->for = $this->getId();
		return $label;
	}



	/**
	* Prida do controlu Live validaci
	*
	* @param Html $source
	* @return Html
	*/
	public function addLiveValidation($source)
	{
		if(!$this->liveValidation) return $source;

		$id = $this->getId();
		$c = Html::el()->add($source);
		$js = $this->getValidateScript();
		$onblur = '$("input[name='.$id.']").blur(function(){var valid = function(){'.$js.'}; res=valid(); if(!res.ok){$(this).invalid(res)} else {$(this).valid(res)}})';
		$c->add(Html::el('script')->type('text/javascript')->setHtml('/* <![CDATA[ */ '.$onblur.' /*]]>*/'));

		$js = $this->getIsRequiredScript();
		if($js) {
			$onblur = '$("input[name='.$id.']").data("isRequiredFnc", function(){'.$js.'})';
			$c->add(Html::el('script')->type('text/javascript')->setHtml('/* <![CDATA[ */ '.$onblur.' /*]]>*/'));
		}
		return $c;
	}

	/**
	 * Filled validator: has been any radio button selected?
	 * @param  IFormControl
	 * @return bool
	 */
	public static function validateFilled(IFormControl $control)
	{
		return $control->getValue() !== NULL;
	}

	public static function validateJSFilled(IFormControl $control)
	{
		$id = $control->getId();
		$js = "res = false; element=null;\n\t res = 0 ";
		foreach($control->getItems() as $key => $item) {
			$key = '"'.$key.'"';
			$js .= "|| (document.getElementById('" . $id . "-'+".$key.") != undefined && document.getElementById('" . $id . "-'+".$key.").checked) ";
		}
		return $js;
	}

	public static function validateJSEqual(IFormControl $control, $arg)
	{
		$id = $control->getId();
		$js = $control->validateJsBase();
		$tmp3 = array();
		foreach ((is_array($arg) ? $arg : array($arg)) as $item) {
				if (is_object($item)) { // compare with another form control?
					$name = $item->getName();
					$form = $control->getSubForm();
					$item = $form[$name];
					$tmp3[] = get_class($item) === $control->getClass()
						? "element.value==document.getElementById('" . $item->getId() . "').value" // missing trim
						: 'false';
				} else {
					$tmp3[] = "element.value==" . json_encode((string) $item);
				}
		}

		$js = "arr = new Array();";
		foreach($control->getItems() as $key => $item) {
			$js .= 'arr["'.$key.'"] = "'.$key.'";';
		}
		return "res = false;\n\t"
				. $js
				. "for (i in arr) {\n\t\t"
				. "element = document.getElementById('" . $id . "-'+i);\n\t\t"
				. "if (element && element.checked && (" . implode(' || ', $tmp3) . ")) { res = true; break; }\n\t"
				. "}\n\telement = null;";
	}

	/**
	* Vráti javascript, ktery po provedeni vrati do promenne $res stav, jestli byl control zmenen od vychoziho stavu.
	* Pouziva se pri odstranovani "prazdnych" subformu z multiformu.
	*/
	public function checkEmptyJs()
	{
		$id = $this->getId();
		$js = "arr = new Array();";
		foreach($this->getItems() as $key => $item) {
			$js .= 'arr["'.$key.'"] = "'.$key.'";';
		}
		$js .= "for (i in arr) {\n\t\t"
				. "element = document.getElementById('" . $id . "-'+i);\n\t\t"
				. "if (element.checked) { val = element.value; break; }\n\t"
				. "}\n\t";
		$js .= "res = val == ".json_encode($this->getDefaultValue())."; \n\t";
		return $js;
	}
}
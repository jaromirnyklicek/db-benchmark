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
 * @version    $Id: TextInput.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/



/**
 * Single line text input control.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
class TextInput extends TextBase
{

	/** maximalni delka vstupu **/
	protected  $maxLenght;

	/** doplnovani predvyplnenych prvku prohlizecem **/
	protected  $autocomplete = TRUE;

	/** ukazatel poctu znaku **/
	protected  $scalebar = FALSE;

	/**
	 * @param  string  control name
	 * @param  string  label
	 * @param  int	width of the control
	 * @param  int	maximum number of characters the user may enter
	 */
	public function __construct($label = NULL, $cols = NULL, $maxLenght = NULL)
	{
		parent::__construct($label);
		$this->control->type = 'text';
		$this->control->size = $cols;
		$this->maxLenght = $maxLenght;
		$this->filters[] = 'trim';
		$this->value = '';
	}

	public function getMaxLenght()
	{
		return $this->maxLenght;
	}

	public function setMaxLenght($value)
	{
		$this->maxLenght = $value;
		return $this;
	}

	public function getScalebar()
	{
		return $this->scalebar;
	}

	public function setScalebar($value = TRUE)
	{
		$this->scalebar = $value;
		return $this;
	}

	public function getAutocomplete()
	{
		return $this->autocomplete;
	}

	public function setAutocomplete($value)
	{
		$this->autocomplete = $value;
		return $this;
	}


	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	public function loadHttpData($data)
	{
		parent::loadHttpData($data);

		if ($this->control->maxlength && iconv_strlen($this->value, 'UTF-8') > $this->control->maxlength) {
			$this->value = iconv_substr($this->value, 0, $this->control->maxlength, 'UTF-8');
		}
	}



	/**
	 * Sets or unsets the password mode.
	 * @param  bool
	 * @return TextInput  provides a fluent interface
	 */
	public function setPasswordMode($mode = TRUE)
	{
		$this->control->type = $mode ? 'password' : 'text';
		return $this;
	}



	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		if ($this->control->type === 'password') {
			$this->tmpValue = '';
		}
		if(!$this->autocomplete) $control->autocomplete = 'off';
		$control->maxlength = $this->maxLenght;
		$control->class = $this->cssClass;
		$control->value = $this->value === '' ? $this->emptyValue : $this->tmpValue;

		if($this->maxLenght && $this->scalebar)  {
			$el = Html::el();
			$el->add($control);
			$control->onkeyup = 'update_scale_string(this)';
			$control->onchange = $control->onkeyup;
			$control->onfocus = $control->onkeyup;
			$control->onblur = $control->onkeyup;

			$c = mb_strlen($this->value);
			$proc = round($c / $this->maxLenght * 100);
			$chars = Html::el()->add('<span id="'.$this->getId().'_scale_text" class="scale-text" name="categories_name_scale_text">'.$c.' / '.$this->maxLenght.' znaků</span>');

			$scale = Html::el('div')->class('scale-bar-border');
			$scale->add('<div id="'.$this->getId().'_scale_bar" class="scale-bar" style="width: '.$proc.'%;" name="'.$this->getId().'"></div>');

			$el->add($chars);
			$el->add($scale);
		}
		else $el = $control;

		return $el;
	}



	public function notifyRule(Rule $rule)
	{
		if (is_string($rule->operation) && strcasecmp($rule->operation, ':length') === 0) {
			$this->control->maxlength = is_array($rule->arg) ? $rule->arg[1] : $rule->arg;

		} elseif (is_string($rule->operation) && strcasecmp($rule->operation, ':maxLength') === 0) {
			$this->control->maxlength = $rule->arg;
		}

		parent::notifyRule($rule);
	}

	/**
	* Pokud je SQL pole tak hleda ve vsech sloupcich (takovy fulltext)
	*
	*/
	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValue();
		if($value === NULL || $value === '') return NULL;
		if(!is_array($column)) $column = array($column);
		$s = array();
		foreach($column as $c) {
			if($this->collate !== NULL) $c .= ' COLLATE '.$this->collate;
			$s[] = '(LOWER('.$c.') LIKE "%'.Database::instance()->escape_str(strtolower($value)).'%")';
		}
		return '('.join(' OR ', $s).')';
	}

	public function getTextValue()
	{
		$value = $this->getValue();
		if($value === NULL || $value === '') return NULL;
		return $value;
	}

	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri.'js/core/update.scale.js';
		return array_merge(parent::getJavascript(), $js);
	}
}

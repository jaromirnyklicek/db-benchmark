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
 * @version    $Id: HiddenField.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/


/**
 * Hidden form control used to store a non-displayed value.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
class HiddenField extends FormControl
{
	/** @var string */
	private $forcedValue;



	public function __construct($forcedValue = NULL)
	{
		parent::__construct(NULL);
		$this->control->type = 'hidden';
		$this->value = (string) $forcedValue;
		$this->forcedValue = $forcedValue;
		$this->valueDefault = $this->value;
		//$this->setId(FALSE);
	}



	/**
	 * Bypasses label generation.
	 * @return void
	 */
	public function getLabel()
	{
		return NULL;
	}



	/**
	 * Sets control's value.
	 * @param  string
	 * @return void
	 */
	public function setValue($value)
	{
		parent::setValue(is_scalar($value) ? (string) $value : '');
		return $this;
	}

	/**
	 * Sets control's value.
	 * @param  string
	 * @return void
	 */
	public function setValueIn($value)
	{
		parent::setValueIn(is_scalar($value) ? (string) $value : '');
		return $this;
	}



	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		return parent::getControl()->value($this->forcedValue === NULL ? $this->value : $this->forcedValue);
	}

	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValue();
		if($value === "" || $value === NULL) return null;
		$s = $column.' = "'.Database::instance()->escape_str($value).'"';
		return '('.$s.')';
	}
}

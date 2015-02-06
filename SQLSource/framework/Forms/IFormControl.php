<?php

/**
 * Defines method that must be implemented to allow a component to act like a form control.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
interface IFormControl
{

	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	function loadHttpData($data);

	/**
	 * Sets control's value.
	 * @param  mixed
	 * @return void
	 */
	function setValue($value);

	/**
	 * Returns control's value.
	 * @return mixed
	 */
	function getValue();
	
	/**
	 * Sets control's value.
	 * @param  mixed
	 * @return void
	 */
	function setValueIn($value);

	/**
	 * Returns control's value.
	 * @return mixed
	 */
	function getValueOut();

	/**
	 * @return Rules
	 */
	function getRules();

	/**
	 * Returns errors corresponding to control.
	 * @return array
	 */
	function getErrors();

	/**
	 * Is control disabled?
	 * @return bool
	 */
	function isDisabled();
	
	
	function getSource();
	

}

<?php

/**
* Nevizualni Input.
* Vzdy se bere hodnota predana v konstruktoru nebo nactena z databaze.
*/
class ValueField extends FormControl
{

	/** @var string */
	private $forcedValue;

	public function __construct($forcedValue = NULL)
	{
		parent::__construct(NULL);
		$this->value = (string) $forcedValue;
		$this->valueDefault = $this->value;
		$this->forcedValue = $forcedValue;
		$this->visible = FALSE;
	}

	/**
	 * Bypasses label generation.
	 * @return void
	 */
	public function getLabel()
	{
		return NULL;
	}

	public function checkEmptyJs()
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
		return parent::setValue($value);
	}

	/**
	 * Sets control's value.
	 * @param  string
	 * @return void
	 */
	public function setValueIn($value)
	{
		return parent::setValueIn($value);
	}


	public function getValueOut()
	{
		return $this->forcedValue;
	}

	/**
	* Nevizualni control nenacita data z requestu
	*
	* @param mixed $data
	* @return void
	*/
	public function loadHttpData($data)
	{

	}

	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		return '';
	}

	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValue();
		if(empty($value)) return null;
		$s = $column.' = "'.Database::instance()->escape_str($value).'"';
		return '('.$s.')';
	}
}

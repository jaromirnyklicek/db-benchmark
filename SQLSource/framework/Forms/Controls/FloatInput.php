<?php
/**
 * Textovy control pro zadaji desetinneho cisla.
 * Akceptuje desetinnou carku i tecku
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class FloatInput extends TextInput
{

	const COMMA_FLOAT = 0;
	const COMMA_THOUSAND = 1;

	public static $mode = self::COMMA_FLOAT;

	/**
	* @param  string  control name
	* @param  string  label
	* @param  int  width of the control
	* @param  int  maximum number of characters the user may enter
	*/
	public function __construct($label, $cols = 4, $maxLenght = NULL)
	{
		parent::__construct($label, $cols, $maxLenght);
		$this->addCondition(Form::FILLED)
				->addRule(Form::FLOAT, _('Zadejte číslo!'));
	}

	public function setValueIn($value)
	{
		if(!empty($value)) {
			// pro cestinu, zobrazuje desetinnou carku
			if(self::$mode == self::COMMA_FLOAT) $value = str_replace('.', ',', (float) str_replace(',', '.', $value));
		}
		return parent::setValueIn($value);
	}

	public function getValueOut()
	{
		$value = str_replace(' ', '', $this->getValue());
		if(self::$mode == self::COMMA_FLOAT) return str_replace(',', '.', $value);
		if(self::$mode == self::COMMA_THOUSAND) return str_replace(',', '', $value);
	}

	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValue();
		if(empty($value)) return null;
		$s = $column.' = "'.Database::instance()->escape_str($value).'"';
		return '('.$s.')';
	}

	public static function validateFloat(TextBase $control)
	{
		$value = str_replace(' ', '', $control->getValue());
		if(self::$mode == self::COMMA_FLOAT) {
			return preg_match('/^-?[0-9]*[.,]?[0-9]+$/', $value);
		}
		if(self::$mode == self::COMMA_THOUSAND) {
			return preg_match('/^-?[0-9.,]+$/', $value);
		}
	}

	public static function validateJSFloat(TextBase $control)
	{
		$js = $control->validateJsBase();
		if(self::$mode == self::COMMA_FLOAT) {
			$js .= "if(val != undefined) res = /^-?[0-9]*[.,]?[0-9]+$/.test(val.replace(/ /g, ''));";
		}
		if(self::$mode == self::COMMA_THOUSAND) {
			$js .= "if(val != undefined) res = /^-?[0-9.,]+$/.test(val.replace(/ /g, ''));";
		}
		return $js;
	}
}

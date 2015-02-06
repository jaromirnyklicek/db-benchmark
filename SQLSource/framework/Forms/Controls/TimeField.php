<?php
/**
* control pro zadání času ve formátu HH:mm
*
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms
*/
class TimeField extends FormControl
{
	/**
	 * @param  string  label
	 */
	public function __construct($label)
	{
		parent::__construct($label);
		$this->addCondition(Form::FILLED)
			 ->addRule(Form::REGEXP, _('Neplatný formát času!'), '/^-?([0-9]*)([:]([0-5][0-9]|60)){0,1}$/');
	}


	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$id = $this->getId();
		$el = Html::el();
		$el->add(parent::getControl()
				->type('text')
				->value($this->getValue())
				->size(5));
		return $el;
	}

	public function setValueIn($value)
	{
		if($value === NULL) {
			$this->value = NULL;
		}
		if(!empty($value)) {
			$sign = $value < 0;
			$value = abs($value);
			$this->value = ($sign ? '-' : '') . floor($value / 60).':'.str_pad($value % 60,2,'0', STR_PAD_LEFT);
		}
	}

	public function getValueOut()
	{
		$value = $this->getValue();
		if($value === NULL || $value === '') return NULL;
		$arr = explode(':', $value);
		$hours = $arr[0];
		$sign = isset($hours[0]) && $hours[0] == '-';
		$mins = isset($arr[1]) ? $arr[1] : 0;
		return ($sign ? -1 : 1) * (abs($hours) * 60 + $mins);
	}


	/**
	 * Regular expression validator: matches control's value regular expression?
	 * @param  TextBase
	 * @param  string
	 * @return bool
	 */
	public static function validateRegexp(FormControl $control, $regexp)
	{
		return preg_match($regexp, $control->getValue());
	}

	public static function validateJSRegexp(FormControl $control, $arg)
	{
		if (strncmp($arg, '/', 1)) {
				throw new /*\*/InvalidStateException("Regular expression '$arg' must be JavaScript compatible.");
			}
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = $arg.test(val);";
		return $js;
	}
}
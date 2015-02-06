<?php
/**
 * Control pro zadání datumu.
 * Při napojení na databázový form, se provádí konverze Timestampu na interní zápis podle jazykové mutace.
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class DateField extends FormControl
{
	/**
	* Jazykova verze kalendare
	*/
	public static $lang = 'cs';

	/**
	* Format pro date()
	*/
	public static $format = 'd.m.Y';

	/**
	* Format pro javascriptovy kalendar
	*/
	public static $formatjs = '%d.%m.%Y';

	/**
	* Regularni vyraz pro validaci
	*/
	public static $regexp = '/^([1-9]|0[1-9]|[12][0-9]|3[01])[\.]([1-9]|0[1-9]|1[012])[\.](19|20)\d\d$/';

	/** @var Html */
	public $imgEl;
	
	/**
	 * @param  string  label
	 */
	public function __construct($label)
	{
		parent::__construct($label);

		$this->addCondition(Form::FILLED)
			 ->addRule(Form::REGEXP, _('Neplatný formát data!'), self::$regexp);
		$this->imgEl = Html::el('img')
					->src(Environment::getVariable('baseUri').'img/core/ico/calendar.gif')
					->class('calimg')
					->style('cursor:pointer; position: relative; left: 1px; top: 2px;');
	}

	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$id = $this->getId();
	   $js = 'Calendar.setup({
				  inputField  : "'.$id.'",
				  ifFormat	  : "'.self::$formatjs.'",
				  button	  : "'.$id.'_img",
				  onClose	  : function(cal) {$("#'.$id.'").blur(); cal.hide();}
			   });';
		$el = Html::el();
		$el->add(parent::getControl()
				->value($this->getValue())
				->type('text')
				->size(12));
		if(!$this->readonly) {
			$el->add($this->imgEl->id($id.'_img'));
			$el->add(Html::el('script')->type('text/javascript')->setHtml($js));
		}
		return $el;
	}

	public function setValueIn($value)
	{
		if(!$value instanceof Timestamp)  {
			$value = Timestamp::factory($value);
		}
		if(!$value->isNull())  {
			$value = date(self::$format, $value->getAsTs());
		}
		$this->value = $value;
	}

	public function getValueOut()
	{
		$value = $this->getValue();
		if(empty($value)) return null;
		return Timestamp::factory($value);
	}

	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri.'js/core/calendar/calendar.js';
		$js[] = $baseUri.'js/core/calendar/lang/calendar-'.self::$lang.'.js';
		$js[] = $baseUri.'js/core/calendar/calendar-setup.js';
		return array_merge(parent::getJavascript(), $js);
	}

	public function getCSS()
	{
		$baseUri = Environment::getVariable('baseUri');
		$css = array();
		$css[] = $baseUri.'js/core/calendar/calendar-blue.css';
		return array_merge(parent::getCSS(), $css);
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
	
	/**
	 * Is entered values within allowed range?
	 * @author   Jan Tvrdík
	 * @author   Jakub Kocourek
	 * @param    DateField
	 * @param    array             0 => minDate, 1 => maxDate
	 * @return   bool
	 */
	public static function validateRange(self $control, array $range)
	{
		return ($range[0] === NULL || strtotime($control->getValue()) >= strtotime($range[0])) && ($range[1] === NULL || strtotime($control->getValue()) <= strtotime($range[1]));
	}
	
	public static function validateJSRange(self $control, array $range)
	{
		$js = $control->validateJsBase();
		$js .= "var dateArr_{$control->getName()} = val.split('.');" 
				. "var date_{$control->getName()} = new Date(dateArr_{$control->getName()}[2], dateArr_{$control->getName()}[1]-1, dateArr_{$control->getName()}[0]);"
				. "if(val != undefined) res = " . ($range[0] === NULL ? "true" : "date_{$control->getName()}.getTime() >= " . strtotime($range[0]) . "000") . " && "
				. ($range[1] === NULL ? "true" : "date_{$control->getName()}.getTime() <= " . strtotime($range[1]) . "000") . ";";
		return $js;	
	}
}

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
 * @version    $Id: TextBase.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/


/**
 * Implements the basic functionality common to text input controls.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
abstract class TextBase extends FormControl
{
	/** @var string */
	protected $emptyValue = '';

	/** @var string */
	protected $tmpValue;

	/** @var array */
	protected $filters = array();



	/**
	 * Sets control's value.
	 * @param  string
	 * @return void
	 */
	public function setValue($value)
	{
		$value = (string) $value;
		foreach ($this->filters as $filter) {
			$value = (string) call_user_func($filter, $value);
		}
		$this->tmpValue = $value === $this->emptyValue ? '' : $value;
		parent::setValue($value);
		return $this;
	}

	public function setValueIn($value)
	{
		$value = (string) $value;
		foreach ($this->filters as $filter) {
			$value = (string) call_user_func($filter, $value);
		}
		$this->tmpValue = $value === $this->emptyValue ? '' : $value;
		parent::setValueIn($value);
		return $this;
	}

	public function getValue()
	{
		return trim($this->value);
	}

	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	public function loadHttpData($data)
	{
		$name = $this->getHtmlName();
		$this->tmpValue = isset($data[$name]) && is_scalar($data[$name]) ? $data[$name] : ($this->nullIfNotSent ? NULL : $this->tmpValue);
		$this->value = $this->tmpValue;
	}



	/**
	 * Sets the special value which is treated as empty string.
	 * @param  string
	 * @return TextBase  provides a fluent interface
	 */
	public function setEmptyValue($value)
	{
		$this->emptyValue = $value;
		return $this;
	}



	/**
	 * Returns the special value which is treated as empty string.
	 * @return string
	 */
	final public function getEmptyValue()
	{
		return $this->emptyValue;
	}



	/**
	 * Appends input string filter callback.
	 * @param  callback
	 * @return TextBase  provides a fluent interface
	 */
	public function addFilter($filter)
	{
		/**/callback($filter);/**/
		$this->filters[] = $filter;
		return $this;
	}


	public function notifyRule(Rule $rule)
	{
		if (is_string($rule->operation) && strcasecmp($rule->operation, ':float') === 0) {
			$this->addFilter(array(__CLASS__, 'filterFloat'));
		}

		parent::notifyRule($rule);
	}



	/**
	 * Filled validator: is control filled?
	 * @param  IFormControl
	 * @return bool
	 */
	public static function validateJSFilled(IFormControl $control)
	{
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = val!='' && val!=" . json_encode((string) $control->getEmptyValue()) . ";";
		return $js;
	}

	/**
	 * Min-length validator: has control's value minimal length?
	 * @param  TextBase
	 * @param  int	length
	 * @return bool
	 */
	public static function validateMinLength(TextBase $control, $length)
	{
		// bug #33268 iconv_strlen works since PHP 5.0.5
		return iconv_strlen($control->getValue(), 'UTF-8') >= $length;
	}

	public static function validateJSMinLength(TextBase $control, $length)
	{
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = val.length>=" . (int) $length . ";";
		return $js;
	}

	/**
	 * Max-length validator: is control's value length in limit?
	 * @param  TextBase
	 * @param  int	length
	 * @return bool
	 */
	public static function validateMaxLength(TextBase $control, $length)
	{
		return iconv_strlen($control->getValue(), 'UTF-8') <= $length;
	}

	public static function validateJSMaxLength(TextBase $control, $length)
	{
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = val.length<=" . (int) $length . ";";
		return $js;
	}

	/**
	 * Length validator: is control's value length in range?
	 * @param  TextBase
	 * @param  array  min and max length pair
	 * @return bool
	 */
	public static function validateLength(TextBase $control, $range)
	{
		if (!is_array($range)) {
			$range = array($range, $range);
		}
		$len = iconv_strlen($control->getValue(), 'UTF-8');
		return ($range[0] === NULL || $len >= $range[0]) && ($range[1] === NULL || $len <= $range[1]);
	}

	public static function validateJSLength(TextBase $control, $arg)
	{
		if (!is_array($arg)) {
				$arg = array($arg, $arg);
			}
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = " . ($arg[0] === NULL ? "true" : "val.length>=" . (int) $arg[0]) . " && "
				. ($arg[1] === NULL ? "true" : "val.length<=" . (int) $arg[1]) . ";";
		return $js;
	}

	/**
	 * Unique validator: is control's unique in the table?
	 * @param  TextBase
	 * @param  array  sql, table; pokud neni argument zadany nacte ze sveho formu pres ORM
	 * @return bool
	 */
	public static function validateUnique($control, $arg, $idColumn = 'id')
	{
		$id = 0;
		if(is_array($arg)) {
		 extract($arg);
		}

		if($arg == NULL) {
			$column = $control->getOption('sql');
			$orm = $control->getForm()->orm;
			if(is_string($orm)) $orm = ORM::factory($orm);
			$table = $orm->getTableName();
			$id = $orm->{$orm->primary_key()};
			$value = $control->getValue();
			$idColumn = $orm->primary_key();
		}

		if(empty($value)) return TRUE;

		$db = Database::singleton();
		return !sql::toScalar('SELECT count(*) FROM `'.$table.'` WHERE '.$column.' = "'.$db->escape_str($value).'" AND '.$idColumn.' <> '.$id);
	}

	/**
	 * Unique validator: is value unique in the table?
	 * @param  TextBase
	 * @param  array  sql, table; pokud neni argument zadany nacte ze sveho formu pres ORM
	 * @return bool
	 */
	public static function validateJsUnique(TextBase $control, $arg)
	{
		if($control->getForm()->orm == NULL) return;

		$id = 0;
		$handler = 'ajax:unique';
		if(is_array($arg)) {
		 extract($arg);
		 unset($arg['handler']);
		}

		if($arg == NULL) {
			$column = $control->getOption('sql');
			$orm = $control->getForm()->orm;
			if(is_string($orm)) $orm = ORM::factory($orm);
			$table = $orm->getTableName();
			$id = $orm->{$orm->primary_key()};
			$idColumn = $control->getOption('id_column');
			if(empty($idColumn)) $idColumn = 'id';
		}

	   $js = $control->validateJsBase();
	   $presenter  = $control->getForm()->getMainForm()->lookup('Nette\Application\Presenter', true);
	   $token = md5(rand());
	   $session = Environment::getSession($token);
	   $session->table = $table;
	   $session->id = $id;
	   $session->column = $column;
	   $session->idColumn = $idColumn;
	   $url = $presenter->link($handler, $token);
	   $msg = self::getUniqueRule($control->getRules());
	   $js .= 'res = $.getJSON("'.$url.'&value="+$("#'.$control->getHtmlName().'").attr("value"),
				function(data){
					if($("#'.$control->getHtmlName().'").length > 0) {
						if(data.res) {$("#'.$control->getHtmlName().'").valid({})}
						else {$("#'.$control->getHtmlName().'").invalid('.json_encode(array('message' => $msg)).')}
					}
				}
		);';
	   return $js;
	}

	public static function getUniqueRule($rules)
	{
		foreach($rules as $rule) {
		   if($rule->operation == ':unique') {
			   return $rule->message;
		   }
		   if(isset($rule->subRules)) return self::getUniqueRule($rule->subRules);
	   }
	}

	/**
	 * Email validator: is control's value valid email address?
	 * @param  TextBase
	 * @return bool
	 */
	public static function validateEmail(TextBase $control)
	{
		return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/i', $control->getValue());
	}

	public static function validateJSEmail(TextBase $control)
	{
		$js = $control->validateJsBase();
		$js .= 'if(val != undefined) res = /^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/i.test(val);';
		return $js;
	}

	/**
	 * URL validator: is control's value valid URL?
	 * @param  TextBase
	 * @return bool
	 */
	public static function validateUrl(TextBase $control)
	{
		return preg_match('/^.+\.[a-z]{2,6}(\\/.*)?$/i', $control->getValue());
	}

	public static function validateJSUrl(TextBase $control)
	{
		$js = $control->validateJsBase();
		$js .= 'if(val != undefined) res = /^.+\.[a-z]{2,6}(\\/.*)?$/i.test(val);';
		return $js;
	}



	/**
	 * Regular expression validator: matches control's value regular expression?
	 * @param  TextBase
	 * @param  string
	 * @return bool
	 */
	public static function validateRegexp(TextBase $control, $regexp)
	{
		return preg_match($regexp, $control->getValue());
	}

	public static function validateJSRegexp(TextBase $control, $arg)
	{
		if (strncmp($arg, '/', 1)) {
				throw new /*\*/InvalidStateException("Regular expression '$arg' must be JavaScript compatible.");
			}
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = $arg.test(val);";
		return $js;
	}



	/**
	 * Integer validator: is a control's value decimal number?
	 * @param  TextBase
	 * @return bool
	 */
	public static function validateInteger(TextBase $control)
	{
		return preg_match('/^-?[0-9]+$/', $control->getValue());
	}

	public static function validateJSInteger(TextBase $control)
	{
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = /^-?[0-9]+$/.test(val);";
		return $js;
	}


	/**
	 * Float validator: is a control's value float number?
	 * @param  TextBase
	 * @return bool
	 */
	public static function validateFloat(TextBase $control)
	{
		return preg_match('/^-?[0-9]*[.,]?[0-9]+$/', $control->getValue());
	}

	public static function validateJSFloat(TextBase $control)
	{
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = /^-?[0-9]*[.,]?[0-9]+$/.test(val);";
		return $js;
	}


	/**
	 * Rangle validator: is a control's value number in specified range?
	 * @param  TextBase
	 * @param  array  min and max value pair
	 * @return bool
	 */
	public static function validateRange(TextBase $control, $range)
	{
		return ($range[0] === NULL || $control->getValue() >= $range[0]) && ($range[1] === NULL || $control->getValue() <= $range[1]);
	}

	public static function validateJSRange(TextBase $control, $arg)
	{
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = " . ($arg[0] === NULL ? "true" : "parseFloat(val)>=" . json_encode((float) $arg[0])) . " && "
				. ($arg[1] === NULL ? "true" : "parseFloat(val)<=" . json_encode((float) $arg[1])) . ";";
		return $js;
	}


	/**
	 * Float string cleanup.
	 * @param  string
	 * @return string
	 */
	public static function filterFloat($s)
	{
		return str_replace(array(' ', ','), array('', '.'), $s);
	}

}

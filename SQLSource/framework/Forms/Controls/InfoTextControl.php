<?php

/* Control neni interaktivni. 
*  Zobrazi informacni text. Lze pridavat callbacky, ktery hodnotu k zobrazeni nachystaji. 
* Callback je ve definovan ve tvaru ($value, $subForm, ...)
* 
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms
*/

class InfoTextControl extends FormControl
{	
	 
	/** css trida controlu **/
	protected $cssClass = '';
									  
	/**
	 * Pole pro callback funkce
	 * @see addCallback()
	 * @var array
	 */
	protected $callbackArr = array();
	
	public function __construct($label = '')
	{
		parent::__construct($label);
		$this->control = /*Nette\Web\*/Html::el('span');
	}	 

	public function getValueOut()
	{
		return FALSE;
	}	 
	
	public function getControl()
	{
		$this->rendered = true;
		$control = $this->control;
		$control->name = $this->getHtmlName();
		$control->disabled = $this->disabled;
		if(isset($this->cssClass)) $control->class = $this->cssClass;
		if(isset($this->style)) $control->style = $this->style;
		$control->id = $this->getId();			
		$value = $this->value;
		foreach ($this->callbackArr as $callback) {
			if(is_callable($callback['func'])) {								
				$args = array_merge(array($value, $this->getSubForm()), $callback['params']);
				$value = call_user_func_array($callback['func'], $args);
			}
			else throw new Exception('Invalid callback '.$callback['func'].'()');
		}
		$control->setHtml($value);
		return $control;
	}
	
	public function addLiveValidation($source)
	{	
		return $source;
	}
	
	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	public function loadHttpData($data)
	{
		
	}
		
	public function addCallback($function, $args = null) {
		if (func_num_args() > 2) {
			$argv = func_get_args();
			$args = array_merge(array($args), array_slice($argv, 2));
		}
		$f = array();
		$f['type'] = 'function';
		$f['func'] = $function;
		$f['params'] = is_array($args) ? $args : array($args);
		$this->callbackArr[] = $f;		  
		return $this;
	}
	
	
	public function checkEmptyJs()
	{
		return 'res = true;';
	}
}	
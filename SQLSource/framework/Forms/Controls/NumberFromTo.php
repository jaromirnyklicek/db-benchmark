<?php
/**
 * Control pro zadání dvou cisel.
 * Do filtru dává podmínku aby cislo bylo rovno nebo mezi zadanýma hodnotama
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 * 
 * @todo i18n
 */
 
class NumberFromTo extends FormControl
{	 
	 protected $value = array('from' => NULL, 'to' => NULL);
	 
	 protected $separator = ' ';
	
	/**
	 * @param  string  label
	 */
	public function __construct($label)
	{
		parent::__construct($label);
		$this->addCondition(Form::FILLED)
				->addRule(Form::NUMERIC, _('Zadejte číslo!'));
	}	 
	 
	public function setSeparator($value)
	{
		
		$this->separator = $value;
		return $this;
	}
   
	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
	   $id = $this->getId();
		$v = $this->getValue();
		$this->rendered = true;
		$control = Html::el('input');
		$control->name = $this->getHtmlName();
		$control->disabled = $this->disabled;
		if(isset($this->cssClass)) $control->class = $this->cssClass;
		if(isset($this->style)) $control->style = $this->style;
		$control->id = $this->getId();	 
		$el = Html::el();	   
		$el->add($control
				->value($v['from'])
				->size(4));		   
		$control = Html::el('input');
		$control->name = $this->getHtmlName().'_to';
		$control->disabled = $this->disabled;
		if(isset($this->cssClass)) $control->class = $this->cssClass;
		if(isset($this->style)) $control->style = $this->style;
		$control->id = $this->getId().'_to';   
		$el2 = Html::el();		
		$el2->add($control
				->value($v['to'])
				->size(4));
		return _('od').'&nbsp;'.$el.$this->separator._('do').'&nbsp;'.$el2;
	}
	
	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	public function loadHttpData($data)
	{
		$name = $this->getHtmlName();
		$from = isset($data[$name]) ? $data[$name] : NULL;
		$to = isset($data[$name.'_to']) ? $data[$name.'_to'] : NULL;
		$this->setValue(array('from' => $from, 'to' => $to));
	}
	
	public function setValueIn($value)
	{
		$this->value = $value;		  
	}
	
	public function getValueOut()
	{
		$value = $this->getValue();				   
		return $value;		
	}
	
	public function addLiveValidation($source)
	{	
		if(!$this->liveValidation) return $source;
		$c = Html::el('div')->add($source);
		$id = $this->getId();
		$c->id($id.'_c');
		$js = $this->getValidateScript();		 
		$onblur  = '$(\'#'.$id.'\').blur(function(){var valid = function(){'.$js.'}; res=valid(); if(!res.ok){$("#'.$id.'_c").invalid(res)} else {$("#'.$id.'_c").valid(res)}});';
		$onblur .= '$(\'#'.$id.'_to\').blur(function(){var valid = function(){'.$js.'}; res=valid(); if(!res.ok){$("#'.$id.'_c").invalid(res)} else {$("#'.$id.'_c").valid(res)}});';
		$c->add(Html::el('script')->type('text/javascript')->setHtml($onblur));
		return $c;
	}
	
	/**
	 * Integer validator: is a control's value decimal number?
	 * @param  TextBase
	 * @return bool
	 */
	public static function validateInteger($control)
	{
		$v = $control->getValue();
		return preg_match('/^-?[0-9]+$/', $v['from']) && preg_match('/^-?[0-9]+$/', $v['to']);
	}

	public static function validateJSInteger($control)
	{
		$js = '';
		$js .= "element = document.getElementById('" . $control->getId() . "');\n\t";
		$js .= "if(element != undefined) { 
				var val = element.value.replace(/^\\s+/, '').replace(/\\s+\$/, '');\n\t";
		$js .= "res = val=='' || /^-?[0-9]+$/.test(val);";
		$js .= "if(res) { element = document.getElementById('" . $control->getId() . "_to');\n\t";
		$js .= "var val = element.value.replace(/^\\s+/, '').replace(/\\s+\$/, '');\n\t";
		$js .= "res = val=='' || /^-?[0-9]+$/.test(val); }
				}";
		return $js;
	}
	
	/**
	 * Filled validator: is control filled?
	 * @param  IFormControl
	 * @return bool
	 */
	public static function validateFilled(IFormControl $control)
	{
		// protoze ma control dva inputy, tak se neda aplikovat pravidlo jen ten vypneny, 
		// tak vzdy vraci false
		return false;
	}
	
	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValueOut();
		if(empty($value['to']) && empty($value['from'])) return null;
		if(!is_array($column)) $column = array($column);
		
		$s = array();
		foreach($column as $col) {
			$c = array();
			if(!empty($value['from'])) $c[] = ''.$col.' >= "'.($value['from']).'"';
			if(!empty($value['to'])) $c[] = ''.$col.' <= "'.($value['to']).'"';
			$s[] = '('.join(' AND ', $c).')';
		}
		return '('.join(' OR ', $s).')';
	}
}
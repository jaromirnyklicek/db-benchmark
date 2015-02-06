<?php

/**
 * Vytvori napovedny text vedle Controlu 
 * 
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms 
 */
 
class SimpleTip extends Object implements ITip
{
	
	const MASK = '%c %t';
	protected $text = '';
	protected $style = '';
	protected $class = '';
	protected $mask = '';
	
	public function __construct($text = '', $class = '', $style = '', $mask = self::MASK)
	{
		$this->text = $text;
		$this->mask = $mask;
		$this->class = $class;
		$this->style = $style;
	}
	
	/**
	* Vytvori:
	*	<span class="" style="">
	*		Napoveda
	*  </span>
	* 
	* @param FormControl $control
	* @return mixed
	*/
	public function wrap(FormControl $control)
	{
		$mask = $this->mask;		
		$s = str_replace('%t', '/%%HELP%%/', $mask);
		$s = str_replace('%c', $control->getControlWithEnvelope(), $s);
		$el = Html::el('span')->setHtml($this->text);
		if(!empty($this->class)) $el->class($this->class);
		if(!empty($this->style)) $el->style($this->style);
		$s = str_replace('/%%HELP%%/', $el, $s);		
		return $s;
	}
}
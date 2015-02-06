<?php

/**
 * Vytvori tooltip nad celym controlem.
 * Vyuziva jquery.tooltip.js
 * 
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms 
 */
 

/**
 * Control's help
 *
 * @author Ondrej Novak
 */
class OverTip extends Object implements ITip
{
	
	protected $text = '';
	
	public function __construct($text = '')
	{
		$this->text = $text;
	}
	
	/**
	* Obali control do <span> a nad tim zavola tooltip() pro vsechny childs elementy
	* 
	* @param FormControl $control
	* @return string
	*/
	public function wrap(FormControl $control)
	{
		$control->getControlPrototype()->title($this->text);
		$s = $control->getControlWithEnvelope(); 
		$id = $control->getHtmlName().'_tip';
		$js = "$(document).ready(function(){ $('#".$id." *').tooltip()})"; 
		$el = Html::el('span')->id($id)->setHtml($s)
					->add(Html::el('script')->setHtml($js)->type('text/javascript'));
		return (string) $el;
	}
}

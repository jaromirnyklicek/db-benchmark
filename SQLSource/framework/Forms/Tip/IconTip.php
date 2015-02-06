<?php

/**
 * Vytvori ikonku vedle Controlu, ktera pro prejeti kurzorem zobrazi HTML tooltip
 * Vyuziva jquery.tooltip.js
 * 
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms 
 **/ 
 
class IconTip extends Object implements ITip
{
	
	/**
	* Formatovaci maska
	* %c = control
	* $t = tooltip icon
	*/
	protected $mask = '%c %t';
	
	/**
	* Styl ikonky
	* 
	* @var string
	*/
	protected $style = '';
	
	/**
	* Cesta k ikonce
	* 
	* @var string
	*/
	protected $ico = 'img/core/help.png';
	
	/**
	* HTML tooltip
	* 
	* @var string
	*/
	protected $text = '';	 
	
	public function __construct($text = '', $ico = NULL)
	{
		$this->text = $text;		
		$baseUri = Environment::getVariable('baseUri');
		$this->ico = $baseUri.$this->ico;
		if($ico != NULL) $this->ico = $ico;
		
	}
	
	/**
	* Vytvori vedle controlu:
	*  <a id="tool_tip" title="html tooltip text">
	*	<img src="/img/core/help.png"/>
	*	<script>
	*		$('#tool_tip').tooltip();
	* </script>
	* </a>
	* 
	* @param FormControl $control
	* @return mixed
	*/
	public function wrap(FormControl $control)
	{
		$s = str_replace('%t', '/%%HELP%%/', $this->mask);
		$s = str_replace('%c', $control->getControlWithEnvelope(), $s);
		$id = $control->getHtmlName().'_tip';
		$js = "$(document).ready(function(){ $('#".$id."').tooltip()})";
		$el = Html::el('a')->id($id)->title($this->text)->id($id)				 
				->add(Html::el('img')->alt('help')->src($this->ico)->style($this->style))
				->add(Html::el('script')->setHtml($js)->type('text/javascript'));
		$s = str_replace('/%%HELP%%/', $el, $s);
		return $s;
	}
}
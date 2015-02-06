<?php

/**
 * Control's help
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms 
 */
 

class LightboxTip extends Object implements ITip
{
	
	protected $url = '';
	public $class = 'tooltip';
	public $mask = '%c %t';
	public $width = 700;
	public $height = 450;
	
	/**
	* Cesta k ikonce
	* 
	* @var string
	*/
	protected $ico = '/img/core/help.png';
	
	 /**
	* Styl ikonky
	* 
	* @var string
	*/
	protected $style = 'vertical-align:middle';
	
	public function __construct($url = '', $width = 450, $height = 700)
	{
		$this->url = $url;
		$this->width = $width;
		$this->height = $height;
	}
	
	public function wrap(FormControl $control)
	{
		$mask = $this->mask;		
		$s = str_replace('%t', '/%%HELP%%/', $mask);
		$s = str_replace('%c', $control->getControlWithEnvelope(), $s);
		$el = Html::el('img')->src($this->ico)->style($this->style);
		if(!empty($this->class)) $el->class($this->class);

		$el = '<a href="'.$this->url.'?keepThis=true&TB_iframe=true&height='.$this->height.'&width='.$this->width.'" title="Nápověda" class="thickbox">'.$el.'</a>';

		$s = str_replace('/%%HELP%%/', $el, $s);		

		return $s;
	}
}
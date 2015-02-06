<?php
/**
 * jqMultiSelectBoxDb
 * 
 * jQuery multiSelect plugin aplikovany na MultiSelectBoxDb
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class jqMultiSelectBoxDb extends MultiSelectBoxDb
{
	public function getControl()
	{
		$html = parent::getControl();
		$js = '$(document).ready( function() {
			$("#'.$this->getId().'").multiSelect({
				  oneOrMoreSelected: "*",
				  selectAllText : "'._('vybrat vše').'",
				  noneSelected : "- '._('vyberte hodnoty').' -"
				});
		});';
		$js = Html::el('script')->setHtml($js);
		return $html.$js;
	}	 
	
	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri.'js/core/jquery.multiSelect.js';
		return array_merge(parent::getJavascript(), $js);  
	}
	
	public function getCSS()
	{
		$baseUri = Environment::getVariable('baseUri');			
		$css = array();
		$css[] = $baseUri.'css/core/jquery.multiSelect.css';
		return array_merge(parent::getCSS(), $css);
	}
}
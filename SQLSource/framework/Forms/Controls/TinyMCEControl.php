<?php
/**
 * TinyMCE Editor
 * 
 * Rozpreacovane!
 * 
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class TinyMCEControl extends TextArea
{		
	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{	
		$js = 'tinyMCE.init({
	mode : "exact",
	elements : "'.$this->getHtmlName().'",
	theme : "advanced",
	language : "cs",
	content_css : "/css/tinymce.css", 
	width : "100%",
	
	// Theme options
	theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,bullist,numlist,separator,undo,redo,|,forecolor,backcolor,|,code",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left"
});';			   
		
		return parent::getControl().Html::el('script')->setText($js);
	}
}
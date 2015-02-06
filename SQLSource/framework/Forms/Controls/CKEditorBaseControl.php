<?php
/**
 * CK editor
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

abstract class CKEditorBaseControl extends TextArea
{	
	
	public function setCkOption($key, $value) 
	{
		$this->options[$key] = $value;
		return $this;
	}
	
	protected function getConfig()
	{
		return json_encode($this->options);
	}
	
	public function getValueOut()
	{
		$allowedTags = '<img><object><script><style><hr><iframe><ins><embed>';
		$a = trim(strip_tags($this->value, $allowedTags));
		$a = str_replace(array(' ', "\n", "\r", "\t", chr(194), chr(160)), array('','','','','', ''), $a);
		if(empty($a)) $this->value = '';
		
		preg_match_all('#<param(.*?)>|<embed(.*?)>#', $this->value, $m);
		foreach($m[0] as $value) {
			$convert = str_replace('&amp;', '&', $value);
			$this->value = str_replace($value, $convert, $this->value);
		}
		
		// nahrazeni ../../ pri kopirovani obrazku 
		$this->value = str_replace('"../../data/', '"/data/', $this->value);
		return $this->value;
	}
	
	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');			
		$js = array();
		$js[] = $baseUri.'js/ckeditor/ckeditor.js';		   
		return array_merge(parent::getJavascript(), $js);  
	}
}
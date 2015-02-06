<?php

class CssMin {

   protected $file;
	
   public function __construct($file)
   {
	   $this->file = $file;
   } 
	
   public function minify() {	 
	$buffer = file_get_contents($this->file);	 
	
	// remove comments // nefunguje dobre
	//$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	// remove tabs, spaces, newlines, etc.
	$buffer = str_replace(array("  ", "   ", "	  ", "	   "), ' ', $buffer);
	$buffer = str_replace(array("\r\n", "\r", "\n", "\t"), '', $buffer);
	/* remove unnecessary spaces */		   
	$buffer = str_replace('{ ', '{', $buffer);
	$buffer = str_replace(' }', '}', $buffer);
	$buffer = str_replace('; ', ';', $buffer);
	$buffer = str_replace(', ', ',', $buffer);
	$buffer = str_replace(' {', '{', $buffer);
	$buffer = str_replace('} ', '}', $buffer);
	$buffer = str_replace(': ', ':', $buffer);
	$buffer = str_replace(' ,', ',', $buffer);
	$buffer = str_replace(' ;', ';', $buffer);
	return $buffer;
  }

}
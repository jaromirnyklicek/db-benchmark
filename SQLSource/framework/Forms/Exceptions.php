<?php
/* Formulářové vyjímky
 * 
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms 
 */

   
class RollbackException extends Exception
{		
	 function __construct($message = NULL, $code = 0)
	 {
		 if($message === NULL) $this->message = _('Nastala interní chyba systému. Operace nebyla provedena.');
		 else $this->message = $message;
	 }	
}

class EmptyPostException extends Exception
{

}
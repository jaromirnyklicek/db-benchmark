<?php
/**
* Odkazani na jiny FormControl, napr. v jinem formulari.
* 
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms
*/

class Symlink extends FormControl
{	 
	private $symlink;
	
	/**
	 * @param  FormControl		Reference na formcontrol
	 */
	public function __construct($symlink)
	{
		parent::__construct();
		$this->symlink = $symlink;
		$this->symlink->setRendered(TRUE);
	}	   
	
	public function getSource()
	{	
		$this->rendered = TRUE;						   
		return $this->symlink->getSource();
	}
	
	public function getOption($key, $default = NULL)
	{
		if($key == 'required') return $this->symlink->getOption($key, $default);
		else parent::getOption($key, $default);
	}	 
	
	/**
	 * Generates label's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getLabel()
	{
		return $this->symlink->getLabel();
	}
	
	/**
	 * Returns control's HTML element template.
	 * @return Nette\Web\Html
	 */
	public function getControlPrototype()
	{
		return $this->symlink->control;
	}

	public function setRendered($value = TRUE)
	{
		parent::setRendered($value);		
	}

	/**
	 * Returns label's HTML element template.
	 * @return Nette\Web\Html
	 */
	public function getLabelPrototype()
	{
		return $this->symlink->label;
	}
	
	public function addRule($operation, $message = NULL, $arg = NULL)
	{
		throw new Exception('Symlink doesn\'t accept validations rules.');
	}
}
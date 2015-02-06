<?php
/**
* Virtualni formular (Obdoba Tabbetu ve VAAdminu 1.0)
* Slouzi pro graficke oddeleni formularovych controlu.
* Controly se pridavaji pres Symlink. 
* 
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms 
*/

class FormVirtual extends SubForm 
{	 
	
	public function __construct($name, $parent = NULL, $group = NULL)
	{
		$this->parentForm = $parent;	
		parent::__construct($parent, $name);
		// vychozi renderer pro subformy
		$this->setRenderer(new TemplateRenderer(dirname(__FILE__).'/../Templates/subform.phtml'));
		
		if($group != NULL) {
			// skupina nepodporuje containery 
			foreach($group->getControls() as $control) {
				$this->addSymlink($control->getName(), $control);
			}
		}
	}
	
   
	/**
	 * Vrati nazev multiformu
	 *
	 * @return string
	 */
	public function getFormName()
	{
		return $this->getName();
	}
	
	public function getMainForm()
	{
		return $this->parentForm;
	}			 
	
	
}
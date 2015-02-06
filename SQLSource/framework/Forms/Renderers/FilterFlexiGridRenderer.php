<?php
 /*
 * Renderer pro filtrovaci formular, ktery je pouzity s FlexiGridem
 * 
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2010 Ondrej Novak
 * @package    Forms
 * */



class FilterFlexiGridRenderer extends FilterRenderer
{
	
	protected $flexiGrid;
	
	public function __construct($columns = 3, $parent)
	{
		parent::__construct($columns);
		$this->flexiGrid = $parent;
	}
	
	public function renderBegin()
	{
		$this->counter = 0;

		foreach ($this->form->getControls() as $control) {
			$control->setRendered(FALSE);
		}

		$element = $this->form->getElementPrototype();
		$element->onsubmit =  '$(\'#fxdg_'.$this->flexiGrid->getName().'\').flexOptions({ newp: 1}).flexReload();return false;';
								
		return $element->startTag().
			   Html::el('input')->type('hidden')->name('__form[]')->value($this->form->getName()); // identifikace formulare

	}
}
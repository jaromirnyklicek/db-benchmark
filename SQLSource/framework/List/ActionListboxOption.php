<?php
class ActionListboxOption extends Object
{
	public $title;
	public $js;
	public $class;
	public $disabled = FALSE;
	
	public function render()
	{
		$el = Html::el('option')->setHtml($this->title);
		if($this->class != NULL) $el->class($this->class);
		if($this->disabled) $el->disabled('disabled');
		return $el;
	}	 
}
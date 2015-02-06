<?php

/** 
*	Pole skrytych inputu 
*	Neni urcen pro databazove ukladani a nacitani.
*	Neni prispusoben pro multiformy.
*/


class HiddenFieldMulti extends FormControl
{

	public function __construct($forcedValue = NULL)
	{
		parent::__construct(NULL);
		$this->value = $forcedValue;
		$this->valueDefault = $this->value;
	}

	/**
	 * Sets control's value.
	 * @param  string
	 * @return void
	 */
	public function setValue($value)
	{
		parent::setValue($value);
		return $this;
	}
	
	/**
	 * Sets control's value.
	 * @param  string
	 * @return void
	 */
	public function setValueIn($value)
	{
		parent::setValueIn($value);
		return $this;
	}


	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$xml = Html::el();
		foreach($this->value as $value) {
			$i = Html::el('input')->type('hidden')->name($this->getHtmlName().'[]')->value($value);
			$xml->add($i);
		}
		return $xml;
	}
}
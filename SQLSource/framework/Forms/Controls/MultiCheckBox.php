<?php

class MultiCheckBox extends FormControl
{

	protected $items = array();
	protected $separator = '<br/>';

	public function setItems($value)
	{
		$this->items = $value;
		return $this;
	}

	public function setSeparator($value)
	{
		$this->separator = $value;
		return $this;
	}

	public function getItems()
	{
		return $this->items;
	}

	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	public function loadHttpData($data)
	{
		$name = $this->getHtmlName();
		$this->value = isset($data[$name]) ? $data[$name] : NULL;
	}

	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$html = Html::el();
		foreach($this->items as $key => $item) {
			$checkbox = Html::el('input')
							->type('checkbox')
							->value($key)
							->id($this->getHtmlName().'_'.$key)
							->name($this->getHtmlName().'[]');

			if($this->value !== NULL && in_array($key, $this->value)) {
				$checkbox->checked(true);
			}
			$checkbox .= '<label for="'.$this->getHtmlName().'_'.$key.'">'.$item.'</label>'.$this->separator;
			$html->add($checkbox);
		}
		return $html;
	}

}

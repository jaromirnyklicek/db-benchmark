<?php

/**
 * FormControl pro vyber pozice vodoznaku.
 * 
 * @author Igor Lamos <igor.lamos@viaaurea.cz>
 *
 */

class WatermarkPosition extends RadioList 
{
	const TOP_LEFT = 0;
	const TOP_CENTER = 1;
	const TOP_RIGHT = 2;
	
	const MIDDLE_LEFT = 3;
	const MIDDLE_CENTER = 4;
	const MIDDLE_RIGHT = 5;
	
	const BOTTOM_LEFT = 6;
	const BOTTOM_CENTER = 7;
	const BOTTOM_RIGHT = 8;
	
	public function __construct($label) 
	{	
		parent::__construct($label);	
		$this->separator = '</tr><tr>';

		$items = array(
			self::TOP_LEFT => _('Vlevo nahoře'), 
			self::TOP_CENTER => _('Nahoře uprostřed'), 
			self::TOP_RIGHT => _('Vpravo nahoře'),
			
			self::MIDDLE_LEFT => _('Vlevo uprostřed'), 
			self::MIDDLE_CENTER => _('Uprostřed'), 
			self::MIDDLE_RIGHT => _('Vpravo uprostřed'),
			
			self::BOTTOM_LEFT => _('Vlevo dole'), 
			self::BOTTOM_CENTER => _('Dole uprostřed'), 
			self::BOTTOM_RIGHT => _('Vpravo dole') 
		);		
		
		$this->setItems($items);
	}
	
	public function getControl()
	{
		$container = clone $this->container;
		$control = FormControl::getControl();
		$id = $control->id;
		$counter = 0;
		$value = $this->value === NULL ? NULL : (string) $this->getValue();
		$label = /*Nette\Web\*/Html::el('label');

		$container->add('<table class="watermark_position"><tr>');
		
		foreach ($this->items as $key => $val) {
			$separator = (($counter + 1) % 3 == 0) ? (string) $this->separator : '';
			
			$control->id = $label->for = $id . '-' . $counter;
			$control->checked = (string) $key === $value;
			$control->value = $key;
			$control->title = $val;
			$control->onclick = 'document.getElementById("watermark-position-info").innerHTML = "'.$val.'";';

			$label->setHtml($val);

			// Za posledni polozkou nebude oddelovac
			if($counter == count($this->items)-1) 
				$separator = "";
			
			$container->add('<td>' . (string) $control . '</td>' . $separator);
			$counter++;
		}
		
		$container->add('</tr></table>');
		if($this->value) $info = $this->items[$this->value];
		else $info = '';
		$container->add('<div id="watermark-position-info">'.$info.'</div>');

		return $container;
	}
}
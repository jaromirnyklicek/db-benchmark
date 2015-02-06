<?php
/**
* Vygenerovaní tabulkovym layoutem bez labelu. 
* Pouziva se v CheckboxForm
* 
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms
*/

class TableRenderer extends ConventionalRenderer
{
	
	public $wrappers = array(
		'form' => array(
			'container' => 'tr',
			'errors' => false,
		),

		'error' => array(
			'container' => 'ul class=controlerror',
			'item' => 'li',
		),

		'group' => array(
			'container' => 'fieldset',
			'label' => 'legend',
			'description' => 'p',
		),
		
		'buttons' => array(
			'container' => 'div class=formbuttons',
			'description' => 'p',
		),

		'controls' => array(
			'container' => null,
		),

		'pair' => array(
			'container' => 'td',
			'.required' => 'required',
			'.optional' => NULL,
			'.odd' => NULL,
		),

		'control' => array(
			'container' => 'td',
			'.odd' => NULL,

			'errors' => true,
			'description' => 'small',

			'.required' => 'required',
			'.text' => 'text',
			'.password' => 'text',
			'.file' => 'text',
			'.submit' => 'button',
			'.image' => 'imagebutton',
			'.button' => 'button',
		),

		'label' => array(
			'container' => 'th',
			'suffix' => NULL,
		),

		'hidden' => array(
			'container' => null,
		),
	);
	
	public function renderBody()
	{
		
		
		$s = $this->renderControls($this->form);
		//$s .= '<td>'.$this->renderHidden().'</td>';		 
										
		$container = $this->getWrapper('form container');
		$container->setHtml($s);	   
		return $container->render(0);
	}
	
	/**
	 * Renders group of controls.
	 * @param  FormContainer|FormGroup
	 * @return string
	 */
	public function renderControls($parent)
	{
		if (!($parent instanceof FormContainer || $parent instanceof FormGroup)) {
			throw new /*\*/InvalidArgumentException("Argument must be FormContainer or FormGroup instance.");
		}

		$container = $this->getWrapper('controls container');

		$buttons = NULL; 
		$i = 0;								 
		foreach ($parent->getControls() as $control) {
			if ($control->isRendered() || $control instanceof HiddenField) {
				// skip

			} elseif ($control instanceof Button) {
				$buttons[] = $control;

			} else {
				if ($buttons) {
					$container->add($this->renderPairMulti($buttons));
					$buttons = NULL;
				}											  
				$container->add($this->renderControl($control, TRUE, $i));
			}
			$i++;
		}

		if ($buttons) {
			$container->add($this->renderPairMulti($buttons));
		}

		$s = '';
		if (count($container)) {
			$s .= "\n" . $container . "\n";
		}

		return $s;
	}


	public function renderControl($control, $wrapper = TRUE, $count = NULL)
	{
		if($wrapper) $body = $this->getWrapper('control container');
		else $body = Html::el();
		if ($this->counter % 2) $body->class($this->getValue('control .odd'), TRUE);
		$body->class('cell'.$count, TRUE);

		$description = $control->getOption('description');
		if ($this->getValue('control errors')) {
			$description .= $this->renderErrors($control);
		}

		if ($control instanceof Button) {
			$td = (string) $control->getSource() . (string) $control->getLabel() . $description;

		} else {
			$td = (string) $control->getSource() . $description;
		}
		
		if($count === 0) $td .= $this->renderHidden();
		return $body->setHtml($td);
	}
   
}

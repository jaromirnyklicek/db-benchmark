<?php
 /*
 * Renderer pro filtrovaci formular
 * 
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 * */

/*namespace Nette\Forms;*/

/*use Nette\Web\Html;*/


/**
 * Converts a Form into the HTML output.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
class FilterRenderer extends /*Nette\*/Object implements IFormRenderer
{
	/**
	 *	/--- form.container
	 *
	 *	  /--- if (form.errors) error.container
	 *		.... error.item [.class]
	 *	  \---
	 *
	 *	  /--- hidden.container
	 *		.... HIDDEN CONTROLS
	 *	  \---
	 *
	 *	  /--- group.container
	 *		.... group.label
	 *		.... group.description
	 *
	 *		/--- controls.container
	 *
	 *		  /--- pair.container [.required .optional .odd]
	 *
	 *			/--- label.container
	 *			  .... LABEL
	 *			  .... label.suffix
	 *			\---
	 *
	 *			/--- control.container [.odd]
	 *			  .... CONTROL [.required .text .password .file .submit .button]
	 *			  .... control.description
	 *			  .... if (control.errors) error.container
	 *			\---
	 *		  \---
	 *		\---
	 *	  \---
	 *	\--
	 *
	 * @var array of HTML tags */
	public $wrappers = array(
		'form' => array(
			'container' => NULL,
			'errors' => false,
		),

		'error' => array(
			'container' => 'ul class=controlerror',
			'item' => 'li',
		),

		'filter' => array(
			'container' => 'fieldset',
			'label' => 'legend',
			'description' => 'p',
		),
		
		'buttons' => array(
			'container' => 'tr',
			'description' => 'p',
		),

		'controls' => array(
			'container' => 'table class=filter',
		),

		'pair' => array(
			'container' => '',
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
			'container' => 'div',
		),
	);

	/** @var Form */
	protected $form;
	
	/** @var Filter */
	protected $filter;

	/** @var object */
	protected $clientScript = TRUE; // means autodetect

	/** @var int */
	protected $counter;
	
	/** 
	* Pocet sloupcu
	* 
	* @var mixed
	*/
	protected $columns = 3;

	/**
	* Oddelene funkcni controly
	* 
	* @var mixed
	*/
	public $separateButtons = TRUE;
	
	protected $jsCalled = FALSE;


	public function __construct($columns = 3)
	{
		$this->columns = $columns;
	}
	
	public function setColumns($value)
	{
		$this->columns = $value;
		return $this;
	}
	
	public function getColumns()
	{
		return $this->columns;
	}
	
	public function setForm(Form $form)
	{
		$this->form = $form;		
		return $this;
	}
	
	/**
	 * Provides complete form rendering.
	 * @param  Form
	 * @param  string
	 * @return string
	 */
	public function render(Form $form, $mode = NULL)
	{
		if ($this->form !== $form) {
			$this->form = $form;
			$this->filter = $form->parent;
			$this->init();
		}

		$s = '';
		if (!$mode || $mode === 'begin') {
			$s .= $this->renderBegin();
		}
		if ((!$mode && $this->getValue('form errors')) || $mode === 'errors') {
			$s .= $this->renderErrors();
		}
		if (!$mode || $mode === 'body') {
			$s .= $this->renderBody();
		}
		if (!$mode || $mode === 'buttons') {
			$s .= $this->renderButtons();
		}
		if (!$mode || $mode === 'end') {
			$s .= $this->renderEnd();
		}
		if (!$mode || $mode === 'js') {
			$s .= $this->renderJs();
		}
		return $s;
	}



	/**
	 * Sets JavaScript handler.
	 * @param  object
	 * @return void
	 */
	public function setClientScript($clientScript = NULL)
	{
		$this->clientScript = $clientScript;
	}



	/**
	 * Returns JavaScript handler.
	 * @return mixed
	 */
	public function getClientScript()
	{
		if ($this->clientScript === TRUE) {
			$this->clientScript = new InstantClientScript($this->form);
		}
		return $this->clientScript;
	}



	/**
	 * Initializes form.
	 * @return void
	 */
	protected function init()
	{
		$clientScript = $this->getClientScript();
		if ($clientScript !== NULL) {
			$clientScript->enable();
		}

		// TODO: only for back compatiblity - remove?
		$wrapper = & $this->wrappers['control'];
		foreach ($this->form->getControls() as $control) {
			if ($control->getOption('required') && isset($wrapper['.required'])) {
				$control->getLabelPrototype()->class($wrapper['.required'], TRUE);
			}

			$el = $control->getControlPrototype();
			if ($el->getName() === 'input' && isset($wrapper['.' . $el->type])) {
				$el->class($wrapper['.' . $el->type], TRUE);
			}
		}
	}



	/**
	 * Renders form begin.
	 * @return string
	 */
	public function renderBegin()
	{
		$this->counter = 0;

		foreach ($this->form->getControls() as $control) {
			$control->setRendered(FALSE);
		}

		$element = $this->form->getElementPrototype();
		if($this->form->useAjax) {			  
			$ajax = '!AjaxMask.Show(\''.$this->filter->parentList->getUniqueId().'_grid\') && !nette.formAction(this, event);';
			if (empty($element->attrs['onsubmit']))		
									$element->onsubmit = 'return '.$ajax;
							else  {				
									$s = $element->onsubmit;
									$element->onsubmit = $s.' && '.$ajax;
							}
		}
								
		return $element->startTag().
			   Html::el('input')->type('hidden')->name('__form[]')->value($this->form->getName()); // identifikace formulare

	}



	/**
	 * Renders form end.
	 * @return string
	 */
	public function renderEnd()
	{	
		//$s = '';		
		$s = $this->renderHidden(); 
		$s .= $this->form->getElementPrototype()->endTag() . "\n";

		$s .= $this->renderJs(); 

		return $s;
	}

	public function renderJs()
	{
		if(!$this->jsCalled) {
			$s = '';		
			
			$clientScript = $this->getClientScript();
			if ($clientScript !== NULL) {
				$s .= $clientScript->renderClientScript() . "\n";
			}

			$this->jsCalled = TRUE;
			return $s;
		}
	}

	/**
	 * Renders validation errors (per form or per control).
	 * @param  IFormControl
	 * @return void
	 */
	public function renderErrors(IFormControl $control = NULL)
	{
		$errors = $control === NULL ? $this->form->getErrors() : $control->getErrors();
		if (count($errors)) {
			$ul = $this->getWrapper('error container');
			$li = $this->getWrapper('error item');

			foreach ($errors as $error) {
				$item = clone $li;
				if ($error instanceof Html) {
					$item->add($error);
				} else {
					$item->setText($error);
				}
				$ul->add($item);
			}
			return "\n" . $ul->render(0);
		}
	}


	public function renderHidden()
	{
		$s = '';
		foreach ($this->form->getControls() as $control) {
			if ($control instanceof HiddenField && !$control->isRendered()) {
				$s .= (string) $control->getControl();
			}
		}
		if ($s) {
			$s = $this->getWrapper('hidden container')->setHtml($s) . "\n";
		}
		return $s;
	}

	/**
	 * Renders form body.
	 * @return string
	 */
	public function renderBody()
	{
		$s = $remains = '';    
		
		$s .= $this->renderHidden();		
		
		$container = $this->getWrapper('filter container');
		$s .= "\n" . $container->startTag();
		$text = $this->filter->title;
		if ($text instanceof Html) {
			$s .= $text;

		} elseif (is_string($text)) {
			
			$s .= "\n" . $this->getWrapper('filter label')->setText($text) . "\n";
		}
		// ostatni nevyrenderovane controly 
		$s .= $this->renderControls($this->form);
		$s .= $container->endTag();
										
		$container2 = $this->getWrapper('form container');
		$container2->setHtml($s);
		$s = $container2->render(0);
		return $s;
	}

	/**
	 * Renders form Buttons.
	 * @return string
	 */
	public function renderButtons()
	{
		$s = $remains = '';    
		
		$defaultContainer = $this->getWrapper('buttons container');   
		//$translator = $this->form->getTranslator();

		foreach ($this->form->getGroups() as $group) {
			if (!$group->getControls() || !$group->getOption('buttons')) continue;

			$container = $this->getWrapper('buttons container');
			$container = $container instanceof Html ? clone $container : Html::el($container);

			$s .= "\n" . $container->startTag();

			$text = $group->getOption('description');
			if ($text instanceof Html) {
				$s .= $text;

			} elseif (is_string($text)) {
				if ($translator !== NULL) {
					$text = $translator->translate($text);
				}
				$s .= $this->getWrapper('group description')->setText($text) . "\n";
			}

			foreach ($group->getControls() as $control) {
					$s .= (string)$control->getControl();				  
			}

			$s .= $container->endTag() . $remains . "\n";
		}

										
		$container = Html::el();
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

			} elseif ($this->separateButtons && $control instanceof Button) {
				$buttons[] = $control;

			} else {
				if ($buttons) {
					$container->add($this->renderPairMulti($buttons));
					$buttons = NULL;
				}
				
				$htmlcontrol = $this->renderPair($control);

				$s = '';
				if($control->getOption('colspan') > 1) {                	
					$i += $control->getOption('colspan')-1;
				}
				
                if($i > 0 && ($i % $this->columns) == 0) {
					$s .= '</tr>';             	
					$i = 0;
                }
				if($i % $this->columns == 0) {					
					$s .= '<tr>';
				}
				$container->add($s.$htmlcontrol);
				$i++;	 
			}
		   
		}
		if($i > 0) $container->add('</tr>');  
		if ($buttons) {
			$container->add($this->renderPairMulti($buttons));
		}

		$s = '';
		if (count($container)) {
			$s .= "\n" . $container . "\n";
		}

		return $s;
	}



	/**
	 * Renders single visual row.
	 * @param  IFormControl
	 * @return string
	 */
	public function renderPair(IFormControl $control)
	{
		$pair = $this->getWrapper('pair container');
		$pair->add($this->renderLabel($control));
		$pair->add($this->renderControl($control));
		$pair->class($this->getValue($control->getOption('required') ? 'pair .required' : 'pair .optional'), TRUE);
		$pair->class($control->getOption('class'), TRUE);
		if (++$this->counter % 2) $pair->class($this->getValue('pair .odd'), TRUE);
		$pair->id = $control->getOption('id');
		return $pair->render(0);
	}



	/**
	 * Renders single visual row of multiple controls.
	 * @param  array of IFormControl
	 * @return string
	 */
	public function renderPairMulti(array $controls)
	{
		$s = array();
		foreach ($controls as $control) {
			if (!($control instanceof IFormControl)) {
				throw new /*\*/InvalidArgumentException("Argument must be array of IFormControl instances.");
			}
			$s[] = (string) $control->getControl();
		}
		$pair = $this->getWrapper('buttons container');
		$pair->add($this->getWrapper('control container')->setHtml(implode(" ", $s))->colspan($this->columns*2)->class('buttons'));
		return $pair->render(0);
	}



	/**
	 * Renders 'label' part of visual row of controls.
	 * @param  IFormControl
	 * @return string
	 */
	public function renderLabel(IFormControl $control)
	{
		$label = (string) $control->getLabel();
		if(empty($label)) return Html::el();
		
		$head = $this->getWrapper('label container');

		if ($control instanceof Button) {
			return $head->setHtml('&nbsp;');

		} else {
			if($control->getOption('required')) {
				$required = '<span class="required">*</span>';
			}
			else $required = '';
			return $head->setHtml((string) $control->getLabel() . $required . $this->getValue('label suffix'));
		}
	}



	/**
	 * Renders 'control' part of visual row of controls.
	 * @param  IFormControl
	 * @return string
	 */
	public function renderControl(IFormControl $control)
	{
		$body = $this->getWrapper('control container');
		if ($this->counter % 2) $body->class($this->getValue('control .odd'), TRUE);

		$description = $control->getOption('description');
		if($colspan = $control->getOption('colspan')) {
			$body->colspan($colspan*2-1);
		}
		if ($description instanceof Html) {
			$description = ' ' . $control->getOption('description');

		} elseif (is_string($description)) {
			$description = ' ' . $this->getWrapper('control description')->setText($description);

		} else {
			$description = '';
		}
		if ($this->getValue('control errors')) {
			$description .= $this->renderErrors($control);
		}

		if ($control instanceof Button) {
			return $body->setHtml((string) $control->getSource() . (string) $control->getLabel() . $description);

		} else {
			return $body->setHtml((string) $control->getSource() . $description);
		}
	}



	/**
	 * @param  string
	 * @return Nette\Web\Html
	 */
	protected function getWrapper($name)
	{
		$data = $this->getValue($name);
		return $data instanceof Html ? clone $data : Html::el($data);
	}



	/**
	 * @param  string
	 * @return string
	 */
	protected function getValue($name)
	{
		$name = explode(' ', $name);
		$data = & $this->wrappers[$name[0]][$name[1]];
		return $data;
	}

}

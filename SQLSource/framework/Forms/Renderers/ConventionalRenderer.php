<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license	Nette license
 * @link	   http://nettephp.com
 * @category   Nette
 * @package    Nette\Forms
 * @version    $Id: ConventionalRenderer.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/

/*use Nette\Web\Html;*/


/**
 * Converts a Form into the HTML output.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
class ConventionalRenderer extends /*Nette\*/Object implements IFormRenderer
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
			'container' => 'table class=maintable',
		),

		'pair' => array(
			'container' => 'tr',
			'.required' => 'required',
			'.optional' => NULL,
			'.odd' => 'odd',
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

	/** @var object */
	protected $clientScript = TRUE; // means autodetect

	/** @var int */
	protected $counter;

	protected $jsCalled = FALSE;

	protected $renderedHidden = FALSE;
	protected $initialized = FALSE;

	/** @var string */
	public $onsubmit;


	/**
	 * Provides complete form rendering.
	 * @param  Form
	 * @param  string
	 * @return string
	 */
	public function render(Form $form, $mode = NULL)
	{
		if ($this->form !== $form || !$this->initialized) {
			$this->form = $form;
			$this->init();
		}

		$s = '';
		if (!$mode || $mode === 'begin') {
			$s .= $this->renderBegin();
		}
		if ((!$mode && $this->getValue('form errors')) || $mode === 'errors') {
			$s .= $this->renderErrors();
		}
		if (!$mode || $mode === 'body' || $mode == 'subform') {
			$s .= $this->renderBody();
		}
		if (!$mode || $mode === 'buttons') {
			$s .= $this->renderButtons();
		}
		if (!$mode || $mode === 'hidden') {
			$s .= $this->renderHidden();
		}
		if (!$mode || $mode === 'end') {
			$s .= $this->renderEnd();
		}
		if (!$mode || $mode === 'js') {
			$s .= $this->renderJs();
		}
		if ($mode === 'jsMulti') {
			$s .= $this->renderJsMulti();
		}
		return $s;
	}

	public function clear()
	{
		$this->renderedHidden = false;
	}

	public function setForm(Form $form)
	{
		$this->form = $form;
		return $this;
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
		$this->initialized = TRUE;
	}



				  /**
	 * Renders form begin.
	 * @return string
	 */
	public function renderBegin()
	{
		$this->counter = 0;

		foreach ($this->form->getControls() as $control) {
			//$control->setRendered(FALSE);
		}

		if (strcasecmp($this->form->getMethod(), 'get') === 0) {
			$el = clone $this->form->getElementPrototype();
			$uri = explode('?', (string) $el->action, 2);
			$el->action = $uri[0];
			$s = '';
			if (isset($uri[1])) {
				foreach (explode('&', $uri[1]) as $param) {
					$parts = explode('=', $param, 2);
					$s .= Html::el('input', array('type' => 'hidden', 'name' => urldecode($parts[0]), 'value' => urldecode($parts[1])));
				}
				$s = "\n\t" . $this->getWrapper('hidden container')->setHtml($s);
			}
			$hidden = $s;
			$element = $el;
		}
		else {
			$element = clone $this->form->getElementPrototype();
			$hidden = '';
		}

		if($this->form->checkUnsave) {
			$hidden .= '<script>
						var '.$this->form->getName().'Unsave = new form_unsave("'.$this->form->getName().'", "form_validated");
						window.onload = function(){'.$this->form->getName().'Unsave.init()};
						window.onbeforeunload = function(){return '.$this->form->getName().'Unsave.onunload()};
						var form_validated = false;
						</script>';
		}


		if($this->form->useAjax) {
			$ajax = '';
			if($this->onsubmit != NULL) $ajax .= $this->onsubmit.' && ';
			$ajax .= '!nette.formAction(this, event);';
			if (empty($element->attrs['onsubmit'])) {
					$element->onsubmit('return '.$ajax);
			}
			else  {
					$s = $element->onsubmit;
					$element->onsubmit($s.' && '.$ajax);
			}
		}
		else {
			$ajax = '';
			if($this->onsubmit != NULL) $ajax = $this->onsubmit;
			if(!empty($ajax)) {
				if (empty($element->attrs['onsubmit'])) {
						$element->onsubmit('return '.$ajax);
				}
				else  {
						$s = $element->onsubmit;
						$element->onsubmit($s.' && '.$ajax);
				}
			}
		}

		return $element->startTag().$hidden;
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
		$s = '';
		if(!$this->jsCalled) {
			$clientScript = $this->getClientScript();
			if ($clientScript !== NULL) {
				$s .= $clientScript->renderClientScript() . "\n";
			}
			$this->jsCalled = TRUE;
		}
		return $s;
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
		if($this->renderedHidden) return;
		$this->renderedHidden = true;
		// identifikace formulare
		$s = Html::el('input')->type('hidden')->name('__form[]')->value($this->form->getName());
		// hidden controly
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

		$defaultContainer = $this->getWrapper('group container');
		//$translator = $this->form->getTranslator();

		foreach ($this->form->getGroups() as $group) {
			if ($group->getOption('buttons')) {
				// nastavi jako, ze uz jsou vykreslene, ale je nutne je pak jeste
				// zpracovat v renderButtons();
				 foreach ($group->getControls() as $control) {
					$control->setRendered();
				 }
			}
			if (!$group->getControls() || !$group->getOption('visual')) continue;
			$i = 0;
			foreach($group->getControls() as $control) if(!$control->isRendered()) $i++;
			if($i == 0) continue;

			$container = $group->getOption('container', $defaultContainer);
			$container = $container instanceof Html ? clone $container : Html::el($container);

			$s .= "\n" . $container->startTag();

			$text = $group->getOption('label');
			if ($text instanceof Html) {
				$s .= $text;

			} elseif (is_string($text)) {
				$s .= "\n" . $this->getWrapper('group label')->setHtml($text) . "\n";
			}

			$text = $group->getOption('description');
			if ($text instanceof Html) {
				$s .= $text;

			} elseif (is_string($text)) {
				$s .= $this->getWrapper('group description')->setHtml($text) . "\n";
			}

			$s .= $this->renderControls($group);

			if ($group->getOption('embedNext')) {
				$remains .= $container->endTag() . "\n";

			} else {
				$s .= $container->endTag() . $remains . "\n";
				$remains = '';
			}
		}

		// ostatni nevyrenderovane controly
		$s .= $remains . $this->renderControls($this->form);

		$container = $this->getWrapper('form container');
		$container->setHtml($s);
		return $container->render(0);
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

	public function renderJsMulti()
	{
		$s = '';
		$clientScript = $this->getClientScript();
		if ($clientScript !== NULL) {
				$s .= $clientScript->renderJsMulti() . "\n";
		}
		return $s;
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

		foreach ($x = $parent->getComponents() as $control) {
			if ($control instanceof ComponentContainer) {
				 // Container
				 if (!$control->getOption('rendered')) $container->add($this->renderContainer($control));
			}
			else if ($control->isRendered() || $control instanceof HiddenField || !$control->getVisible()) {
				// skip

			} elseif ($control instanceof Button) {
				$buttons[] = $control;

			} else {
				if ($buttons) {
					$container->add($this->renderPairMulti($buttons));
					$buttons = NULL;
				}
				$container->add($this->renderPair($control));
			}
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

	public function renderContainer($parent)
	{
		$parent->setOption('rendered', TRUE);
		$pair = $this->getWrapper('pair container');
		$iter = $parent->getComponents();
		if($parent->getLabel() == NULL) {
			$first = current($iter);
			$pair->add($this->renderLabel($first));
		}
		else {
			$first = NULL;
			$pair->add($this->renderLabel($parent));
		}
		$body = $this->getWrapper('control container');
		if(method_exists($parent, 'render')) {
			$description = $parent->getOption('description');
			if ($description instanceof Html) {
				$description = ' ' . $control->getOption('description');
			} elseif (is_string($description)) {
				$description = ' ' . $this->getWrapper('control description')->setHtml($description);
			} else {
				$description = '';
			}
			if ($this->getValue('control errors')) {
				foreach($parent->getControls() as $control) {
					$description .= $this->renderErrors($control);
				}
			}
			$body->add((string)$parent->render().$description);
		}
		else {
			foreach($iter as $control) {
				if($first != $control) {
					$body->add($this->renderLabel($control, FALSE));
				}
				$body->add($this->renderControl($control, FALSE));
			}
		}
		$pair->add($body);
		$pair->class($this->getValue($parent->getOption('required') ? 'pair .required' : 'pair .optional'), TRUE);
		$pair->class($parent->getOption('class'), TRUE);
		if (++$this->counter % 2) $pair->class($this->getValue('pair .odd'), TRUE);
		$pair->id = $parent->getOption('id');
		$s = $pair->render(0);
		return $pair->render(0);
	}

	/**
	 * Renders single visual row.
	 * @param  IFormControl
	 * @return string
	 */
	public function renderPair($control)
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
			$s[] = (string) $control->getSource();
		}
		$pair = $this->getWrapper('pair container');
		$pair->add($this->getWrapper('label container')->setHtml('&nbsp;'));
		$pair->add($this->getWrapper('control container')->setHtml(implode(" ", $s)));
		return $pair->render(0);
	}



	/**
	 * Renders 'label' part of visual row of controls.
	 * @param  IFormControl
	 * @return string
	 */
	public function renderLabel($control, $wrapper = TRUE)
	{
		if($wrapper) $head = $this->getWrapper('label container');
		else $head = Html::el('strong');

		if ($control instanceof Button) {
			return $head->setHtml('&nbsp;');

		} else {
			if($control && $control->getOption('required')) {
				$required = '<span class="required">*</span>';
			}
			else $required = '';
			return $head->setHtml(($control ? (string) $control->getLabel() . $required :'' ). $this->getValue('label suffix'));
		}
	}



	/**
	 * Renders 'control' part of visual row of controls.
	 * @param  IFormControl
	 * @return string
	 */
	public function renderControl($control, $wrapper = TRUE)
	{
		if($wrapper) $body = $this->getWrapper('control container');
		else $body = Html::el();
		if ($this->counter % 2) $body->class($this->getValue('control .odd'), TRUE);

		$description = $control->getOption('description');
		if ($description instanceof Html) {
			$description = ' ' . $control->getOption('description');

		} elseif (is_string($description)) {
			$description = ' ' . $this->getWrapper('control description')->setHtml($description);

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

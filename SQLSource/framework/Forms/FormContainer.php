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
 * @version    $Id: FormContainer.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */



/**
 * Container for form controls.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
class FormContainer extends /*Nette\*/ComponentContainer implements /*\*/ArrayAccess, INamingContainer
{
	/** @var FormGroup */
	protected $currentGroup;

	protected $label;
   
	/** @var array user options */
	protected $options = array();
	
	/**
	 * @param  FormGroup
	 * @return void
	 */
	public function setCurrentGroup(FormGroup $group = NULL)
	{
		$this->currentGroup = $group;
	}
	
	public function getCurrentGroup()
	{
		return $this->currentGroup;
	}



	/**
	 * Adds the specified component to the IComponentContainer.
	 * @param  IComponent
	 * @param  string
	 * @param  string
	 * @return IComponent
	 * @throws \InvalidStateException
	 */
	public function addComponent(/*Nette\*/IComponent $component, $name, $insertBefore = NULL)
	{
		parent::addComponent($component, $name, $insertBefore);
		if ($this->currentGroup !== NULL /*&& $component instanceof IFormControl*/) { //5.11.2009 Novak
			$this->currentGroup->add($component);
		}
		return $component;
	}	 

	/**
	 * Iterates over all form controls.
	 * @return \ArrayIterator
	 */
	public function getControls()
	{			   
		return $this->getComponents(TRUE, 'Nette\Forms\IFormControl');
	}


	/**
	* Pro pripad containeru ve formu
	*/
	public function setRendered($value = TRUE)
	{
		$this->setOption('rendered', $value);
		return $this;
	}
	
	public function isRendered()
	{
		return $this->getOption('rendered');
	}
	
	
	/***** Pridano kvuli kontejnerum ***/
	
	/**
	 * Sets user-specific option.
	 *
	 * Common options:
	 * - 'rendered' - indicate if method getControl() have been called
	 * - 'required' - indicate if ':required' rule has been applied
	 * - 'description' - textual or Html object description (recognized by ConventionalRenderer)
	 *
	 * @param  string key
	 * @param  mixed  value
	 * @return FormControl	provides a fluent interface
	 */
	public function setOption($key, $value)
	{
		if ($value === NULL) {
			unset($this->options[$key]);

		} else {
			$this->options[$key] = $value;
		}
		return $this;
	}

	/**
	 * Returns user-specific option.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	final public function getOption($key, $default = NULL)
	{
		return isset($this->options[$key]) ? $this->options[$key] : $default;
	}
	
	/**
	 * Generates label's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getLabel()
	{			
		return $this->label;
	}


	/********************* interface \ArrayAccess ****************d*g**/



	/**
	 * Adds the component to the container.
	 * @param  string  component name
	 * @param  Nette\IComponent
	 * @return void.
	 */
	final public function offsetSet($name, $component)
	{
		$this->addComponent($component, $name);
	}



	/**
	 * Returns component specified by name. Throws exception if component doesn't exist.
	 * @param  string  component name
	 * @return Nette\IComponent
	 * @throws \InvalidArgumentException
	 */
	final public function offsetGet($name)
	{
		return $this->getComponent($name, TRUE);
	}



	/**
	 * Does component specified by name exists?
	 * @param  string  component name
	 * @return bool
	 */
	final public function offsetExists($name)
	{
		return $this->getComponent($name, FALSE) !== NULL;
	}



	/**
	 * Removes component from the container. Throws exception if component doesn't exist.
	 * @param  string  component name
	 * @return void
	 */
	final public function offsetUnset($name)
	{
		$component = $this->getComponent($name, FALSE);
		if ($component !== NULL) {
			$this->removeComponent($component);
		}
	}
	/********************* control factories ****************d*g**/



	/**
	 * Adds single-line text input control to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int	width of the control
	 * @param  int	maximum number of characters the user may enter
	 * @return TextInput
	 */
	public function addText($name, $label = '', $cols = NULL, $maxLength = NULL)
	{
		return $this[$name] = new TextInput($label, $cols, $maxLength);
	}
	
	/**
	 * Adds single-line text float number input control to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int	width of the control
	 * @param  int	maximum number of characters the user may enter
	 * @return TextInput
	 */
	public function addFloat($name, $label = '', $cols = 4, $maxLength = NULL)
	{
		return $this[$name] = new FloatInput($label, $cols, $maxLength);
	}



	/**
	 * Adds single-line text input control used for sensitive input such as passwords.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int	width of the control
	 * @param  int	maximum number of characters the user may enter
	 * @return TextInput
	 */
	public function addPassword($name, $label, $cols = NULL, $maxLength = NULL)
	{
		$control = new TextInput($label, $cols, $maxLength);
		$control->setPasswordMode(TRUE);
		$this->addComponent($control, $name);
		return $control;
	}



	/**
	 * Adds multi-line text input control to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int	width of the control
	 * @param  int	height of the control in text lines
	 * @return TextArea
	 */
	public function addTextArea($name, $label, $cols = 40, $rows = 10)
	{
		return $this[$name] = new TextArea($label, $cols, $rows);
	}



	/**
	 * Adds control that allows the user to upload files.
	 * @param  string  control name
	 * @param  string  label
	 * @return FileUpload
	 */
	public function addFile($name, $label)
	{
		return $this[$name] = new FileUpload($label);
	}
	
	public function addFileDb($name, $label)
	{
		return $this[$name] = new FileUploadDb($label);
	}




	/**
	 * Adds hidden form control used to store a non-displayed value.
	 * @param  string  control name
	 * @return HiddenField
	 */
	public function addHidden($name, $value = NULL)
	{
		return $this[$name] = new HiddenField($value);
	}
	
	/**
	 * Adds hidden form control used to store a non-displayed value.
	 * @param  string  control name
	 * @return ValueField
	 */
	public function addValue($name, $value)
	{
		return $this[$name] = new ValueField($value);
	}

	
   

	/**
	 * Adds check box control to the form.
	 * @param  string  control name
	 * @param  string  caption
	 * @return Checkbox
	 */
	public function addCheckbox($name, $caption, $items = array(1 => 1, 0 => 0))
	{
		return $this[$name] = new Checkbox($caption, $items);
	}



	/**
	 * Adds set of radio button controls to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   options from which to choose
	 * @return RadioList
	 */
	public function addRadioList($name, $label, array $items = NULL)
	{
		return $this[$name] = new RadioList($label, $items);
	}



	/**
	 * Adds select box control that allows single item selection.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  int	   number of rows that should be visible
	 * @return SelectBox
	 */
	public function addSelect($name, $label, $items = NULL, $size = NULL)
	{
		return $this[$name] = new SelectBox($label, $items, $size);
	}

	/**
	 * Adds select box control that allows single item selection with bind to parent Select.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  int	   number of rows that should be visible
	 * @return SelectBox
	 */
	public function addSelectBind($name, $label, $items = NULL, $parent, $size = NULL)
	{
		return $this[$name] = new SelectBoxBind($label, $items, $parent, $size);
	}


	/**
	 * Adds select box control that allows multiple item selection.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   options from which to choose
	 * @param  int	   number of rows that should be visible
	 * @return MultiSelectBox
	 */
	public function addMultiSelect($name, $label, array $items = NULL, $size = NULL)
	{
		return $this[$name] = new MultiSelectBox($label, $items, $size);
	}



	/**
	 * Adds button used to submit form.
	 * @param  string  control name
	 * @param  string  caption
	 * @return SubmitButton
	 */
	public function addSubmit($name, $caption)
	{
		return $this[$name] = new SubmitButton($caption);
	}



	/**
	 * Adds push buttons with no default behavior.
	 * @param  string  control name
	 * @param  string  caption
	 * @return Button
	 */
	public function addButton($name, $caption)
	{
		return $this[$name] = new Button($caption);
	}



	/**
	 * Adds graphical button used to submit form.
	 * @param  string  control name
	 * @param  string  URI of the image
	 * @param  string  alternate text for the image
	 * @return ImageButton
	 */
	public function addImage($name, $src = NULL, $alt = NULL)
	{
		return $this[$name] = new ImageButton($src, $alt);
	}



	/**
	 * Adds naming container to the form.
	 * @param  string  name
	 * @return FormContainer
	 */
	public function addContainer($name, $label = NULL, $required = FALSE)
	{
		$control = new FormContainer;
		$control->label = $label;
		//$control->setOption('inline') = TRUE; // vnoreny container
		if($required) $control->setOption('required', TRUE);
		//$control->currentGroup = $this->currentGroup;
		return $this[$name] = $control;
	}


	public function addDate($name, $label)
	{
		return $this[$name] = new DateField($label);
	}
	
	public function addDateTime($name, $label)
	{
		return $this[$name] = new DateTimeField($label);
	}
	
	public function addTime($name, $label)
	{
		return $this[$name] = new TimeField($label);
	}
	
	public function addCKEditor($name, $label)
	{
		return $this[$name] = new CKEditorControl($label);
	}
	
	public function addLookUp($name, $label, $sql, $sqllist, $cols = 40)
	{
		return $this[$name] = new LookUpControl($label, $sql, $sqllist, $cols-4);
	}
	
	public function addInfoText($name, $label = '')
	{
		return $this[$name] = new InfoTextControl($label);
	}
	
	public function addSymlink($name, $control)
	{
		return $this[$name] = new Symlink($control);
	}

	/**
	 * @return NumberInput
	 */
	public function addNumber($name, $label = '', $cols = 4)
	{
		return $this[$name] = new NumberInput($label, $cols);
	}
	
	public function addColorPicker($name, $label)
	{
		return $this[$name] = new ColorPicker($label);
	}

	public function addSelectImage($name, $label, $dir, $orig, $zoom = true, $width = 70, $height = 70) 
	{
		$preview = new SelectImage($label);
		$preview->zoom = $zoom;
		$preview->dir = $dir;
		$preview->thumbConfig['width'] = $width;
		$preview->thumbConfig['height'] = $height;
		$preview->thumbConfig['url_orig'] = $orig;
		
		return $this[$name] = $preview;
	}

}

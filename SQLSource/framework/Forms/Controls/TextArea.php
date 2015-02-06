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
 * @version    $Id: TextArea.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/

 
/**
 * Multiline text input control.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
class TextArea extends TextBase
{

	/**
	 * @param  string  control name
	 * @param  string  label
	 * @param  int	width of the control
	 * @param  int	height of the control in text lines
	 */
	public function __construct($label, $cols = NULL, $rows = NULL)
	{
		parent::__construct($label);
		$this->control->setName('textarea');
		$this->control->cols = $cols;
		$this->control->rows = $rows;
		$this->value = '';
	}



	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		$control->setText($this->value === '' ? $this->emptyValue : $this->tmpValue);
		return $control;
	}

	public function getSource()
	{	
		if($this->help == NULL) $source = $this->getControlWithEnvelope();		  
		else $source = $this->help->wrap($this);		
		return $this->addLiveValidation($source);
	}
}

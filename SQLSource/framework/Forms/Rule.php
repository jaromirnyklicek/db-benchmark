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
 * @version    $Id: Rule.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */


/**
 * Single validation rule or condition represented as value object.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
final class Rule extends /*Nette\*/Object
{
	/** type */
	const CONDITION = 1;

	/** type */
	const VALIDATOR = 2;

	/** type */
	const FILTER = 3;

	/** type */
	const TERMINATOR = 4;

	/** @var IFormControl */
	public $control;

	/** @var mixed */
	public $operation;

	/** @var mixed */
	public $arg;

	/** @var int (CONDITION, VALIDATOR, FILTER) */
	public $type;

	/** @var bool */
	public $isNegative = FALSE;

	/** @var string (only for VALIDATOR type) */
	public $message;

	/** @var bool (only for VALIDATOR type) */
	public $breakOnFailure = TRUE;

	/** @var Rules (only for CONDITION type)  */
	public $subRules;

	
	public function __clone()
	{	 
		/*if(isset($this->subRules)) {
			for($i = 0; $i < count($this->subRules->rules);$i++)	{	 
				$this->subRules->rules[$i] = clone $this->subRules->rules[$i];
			}
		} */
		if(isset($this->subRules)) $this->subRules = clone $this->subRules;
	}
	
	/**
	 * Nevolat primo. Pouziva se pri naklonovani controlu.
	 */
	public function setControl(IFormControl $control)
	{
		 $this->control = $control;		 
		 if(isset($this->subRules)) {		 			 	 
			foreach ($this->subRules->rules as $rule)	 {				 				
				$rule->setControl($control);
			}
		}	 
	}
}

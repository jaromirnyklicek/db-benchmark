<?php

/**
 * Nette Framework
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Security
 */



/**
 * Authentication exception.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl,
 * uprava Ondrej Novak 10/2012
 * @package    Nette\Security
 */
class AuthenticationException extends Exception
{
	private $banned = FALSE;
	private $fails = NULL;

	public function __construct($message = NULL, $code = NULL, $fails = NULL, $banned = FALSE)
	{
		parent::__construct($message, $code);
		$this->banned = $banned;
		$this->fails = $fails;
	}

	public function isBanned()
	{
		return $this->banned;
	}

	public function getFails()
	{
		return $this->fails;
	}
}
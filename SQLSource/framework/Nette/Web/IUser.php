<?php

/**
 * Nette Framework
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Web
 */



/**
 * Authentication and authorization.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Nette\Web
 */
interface IUser
{

	/**
	 * Conducts the authentication process.
	 * @param  string
	 * @param  string
	 * @param  mixed
	 * @return void
	 * @throws AuthenticationException if authentication was not successful
	 */
	function authenticate($username, $password, $extra = NULL);

	/**
	 * Logs off the user from the current session.
	 * @return void
	 */
	function signOut($clearIdentity = FALSE);

	/**
	 * Is this user authenticated?
	 * @return bool
	 */
	function isAuthenticated();

	/**
	 * Returns current user identity, if any.
	 * @return IIdentity
	 */
	function getIdentity();

	/**
	 * Sets authentication handler.
	 * @param  IAuthenticator
	 * @return void
	 */
	function setAuthenticationHandler(IAuthenticator $handler);

	/**
	 * Returns authentication handler.
	 * @return IAuthenticator
	 */
	function getAuthenticationHandler();

	/**
	 * Changes namespace; allows more users to share a session.
	 * @param  string
	 * @return void
	 */
	function setNamespace($namespace);

	/**
	 * Returns current namespace.
	 * @return string
	 */
	function getNamespace();

	/**
	 * Returns a list of roles that a user has been granted.
	 * @return array
	 */
	function getRoles();

	/**
	 * Is a user in the specified role?
	 * @param  string
	 * @return bool
	 */
	function isInRole($role);

	/**
	 * Has a user access to the Resource?
	 * @return bool
	 */
	function isAllowed();

	/**
	 * Sets authorization handler.
	 * @param  IAuthorizator
	 * @return void
	 */
	function setAuthorizationHandler(IAuthorizator $handler);

	/**
	 * Returns current authorization handler.
	 * @return IAuthorizator
	 */
	function getAuthorizationHandler();

}

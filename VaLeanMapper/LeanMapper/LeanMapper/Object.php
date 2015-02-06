<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace LeanMapper;

use LeanMapper\Exception;
use LeanMapper\ObjectMixin;

/**
 * 
 * @author     David Grudl
 */
abstract class Object
{
	/**
	 * Access to reflection.
	 * @return Nette\Reflection\ClassType
	 */
	/* public static function getReflection()
	  {
	  return new Reflection\ClassType(get_called_class());
	  } */

	/**
	 * Call to undefined method.
	 * @param  string  method name
	 * @param  array   arguments
	 * @return mixed
	 * @throws Exception\MemberAccessException
	 */
	public function __call($name, $args)
	{
		return ObjectMixin::call($this, $name, $args);
	}

	/**
	 * Call to undefined static method.
	 * @param  string  method name (in lower case!)
	 * @param  array   arguments
	 * @return mixed
	 * @throws Exception\MemberAccessException
	 */
	public static function __callStatic($name, $args)
	{
		return ObjectMixin::callStatic(get_called_class(), $name, $args);
	}

	/**
	 * Adding method to class.
	 * @param  string  method name
	 * @param  callable
	 * @return mixed
	 */
	/* public static function extensionMethod($name, $callback = NULL)
	  {
	  if (strpos($name, '::') === FALSE) {
	  $class = get_called_class();
	  } else {
	  list($class, $name) = explode('::', $name);
	  }
	  $class = new Reflection\ClassType($class);
	  if ($callback === NULL) {
	  return $class->getExtensionMethod($name);
	  } else {
	  $class->setExtensionMethod($name, $callback);
	  }
	  } */

	/**
	 * Returns property value. Do not call directly.
	 * @param  string  property name
	 * @return mixed   property value
	 * @throws Exception\MemberAccessException if the property is not defined.
	 */
	public function &__get($name)
	{
		return ObjectMixin::get($this, $name);
	}

	/**
	 * Sets value of a property. Do not call directly.
	 * @param  string  property name
	 * @param  mixed   property value
	 * @return void
	 * @throws Exception\MemberAccessException if the property is not defined or is read-only
	 */
	public function __set($name, $value)
	{
		return ObjectMixin::set($this, $name, $value);
	}

	/**
	 * Is property defined?
	 * @param  string  property name
	 * @return bool
	 */
	public function __isset($name)
	{
		return ObjectMixin::has($this, $name);
	}

	/**
	 * Access to undeclared property.
	 * @param  string  property name
	 * @return void
	 * @throws Exception\MemberAccessException
	 */
	public function __unset($name)
	{
		ObjectMixin::remove($this, $name);
	}

}


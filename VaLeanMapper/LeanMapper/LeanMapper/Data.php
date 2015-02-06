<?php

namespace LeanMapper;

use Closure;
use DibiConnection;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\MemberAccessException;
use LeanMapper\Exception\InvalidMethodCallException;

/**
 * Trida pro ulozeni entitnich dat
 *
 * @author	  Ondrej Novak
 * @copyright  Copyright (c) Via Aurea, s.r.o.
 */
class Data
{

	/** @var array */
	private $data;

	/** @var array */
	private $modified = array();

	/** @var Property[] */
	private $properties;

	/** @var array */
	private $populated = array();

	/** @var array of function($name, $value) Je vyvolán při změně hodnoty */
	public $onChange;

	/** @var array of function($name, $value) Je vyvolán při přidání hodnoty */
	public $onAdd;

	/** @var array of function($name, $value) Je vyvolán při odebrání hodnoty */
	public $onRemove;

	/**
	 * @param Result $result
	 * @param Property[] $properties
	 * @param bool $hydrated
	 */
	public function __construct($arg = NULL, $properties = NULL, $storePopulated = FALSE)
	{
		$this->properties = $properties;
		$this->assign($arg, $storePopulated, FALSE);
	}

	/**
	 * 
	 * @param scalar $field
	 * @param mixed $value
	 * @param boolean $storePopulated
	 * @param boolean $storeModified
	 */
	public function setDataValue($field, $value, $storePopulated = TRUE, $storeModified = TRUE)
	{
		$this->data[$field] = $value;

		if (!empty($this->onChange)) {
			$this->onChange($field, $value);
		}
		if ($storePopulated) {
			$this->populated[$field] = TRUE;
		}
		if ($storeModified) {
			$this->modified[$field] = TRUE;
		}
	}

	/**
	 * Returns value of given field
	 * 
	 * @param string $field
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public function getDataValue($field)
	{
		if (!array_key_exists($field, $this->data)) {		
			throw new MemberAccessException("Undefined property: " . $field);
		}
		
		return $this->data[$field];
	}

	/**
	 * Performs a mass value assignment (using setters)
	 *
	 * @param array|Traversable $values
	 * @param array|null $whitelist
	 * @throws InvalidArgumentException
	 */
	public function assign($values, $storePopulated = TRUE, $storeModified = TRUE)
	{
		if (!is_array($values) and !($values instanceof Traversable)) {
			throw new InvalidArgumentException('Argument $values must be either array or instance of Traversable, ' . gettype($values) . ' given.');
		}
		foreach ($values as $field => $value) {
			$this->setDataValue($field, $value, $storePopulated, $storeModified);
		}
	}

	/**
	 * Je naplnena hodnota?
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isPopulated($name)
	{
		return isset($this->populated[$name]);
	}

	/**
	 * Returns value of given field
	 *
	 * @param string $name
	 * @return mixed
	 * 
	 * @throws MemberAccessException
	 */
	public function __get($name)
	{
		return $this->getDataValue($name);
	}

	/**
	 * Sets value of given field
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->setDataValue($name, $value);
	}

	public function clearModified()
	{
		$this->modified = array();
	}

	public function isModified()
	{
		return !empty($this->modified);
	}

	/**
	 * Hi-level modifikovana data.
	 *
	 * @return array
	 */
	public function getModifiedData()
	{
		$d = array();
		foreach ($this->properties as $property) {
			if (isset($this->modified[$property->getName()])) {

				$d[$property->getName()] = $this->data[$property->getName()];

				/* if (!$property->hasRelationship()) {
				  $d[$property->getName()] = $this->data[$property->getName()];
				  } else {
				  $type = explode('\\', get_class($property->getRelationship()));
				  $type = array_pop($type);
				  if ($type == 'HasOne' || $type == 'belongsToOne') {
				  $d[$property->getName()] = $this->data[$property->getName()];
				  }
				  } */
			}
		}
		return $d;
	}

	/**
	 * Calls __get() or __set() method when get<$name> or set<$name> methods don't exist
	 *
	 * @param string $name
	 * @param array $arguments
	 * @param array $arguments
	 * @return mixed
	 * @throws InvalidMethodCallException
	 */
	public function __call($name, array $arguments)
	{
		$e = new InvalidMethodCallException("Method '$name' is not callable.");
		if (strlen($name) < 4) {
			throw $e;
		}
		if (substr($name, 0, 3) === 'get') {
			return $this->__get(lcfirst(substr($name, 3)), $arguments);
		} elseif (substr($name, 0, 3) === 'set') {
			$this->__set(lcfirst(substr($name, 3)), $arguments);
		} elseif (substr($name, 0, 5) === 'addTo' and strlen($name) > 5) {
			$this->addTo(lcfirst(substr($name, 5)), array_shift($arguments));
		} elseif (substr($name, 0, 10) === 'removeFrom' and strlen($name) > 10) {
			$this->removeFrom(lcfirst(substr($name, 10)), array_shift($arguments));
		} else if (substr($name, 0, 2) === 'on' and strlen($name) > 2) { // Podpora událostí...			
			return ObjectMixin::call($this, $name, $arguments);
		} else {
			throw $e;
		}
	}

	/**
	 * @param string $name
	 * @param mixed $arg
	 */
	private function addTo($name, $arg)
	{
		if (!array_key_exists($name, $this->data) || $this->data[$name] === NULL) {
			$this->data[$name] = array();
		}
		$this->data[$name][] = $arg;

		if (!empty($this->onAdd)) {
			$this->onAdd($name, $arg);
		}

		$this->modified[$name] = TRUE;
	}

	/**
	 * @param string $name
	 * @param mixed $arg
	 */
	private function removeFrom($name, $arg)
	{
		$toRemove = array();
		foreach ($this->data[$name] as $key => $item) {
			if ($item->compareTo($arg)) {
				$toRemove[] = $key;
			}
		}
		foreach ($toRemove as $key) {
			unset($this->data[$name][$key]);

			if (!empty($this->onRemove)) {
				$this->onRemove($name, $arg);
			}

			$this->modified[$name] = TRUE;
		}
	}

	/**
	 * Returns array of fields with values
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

}
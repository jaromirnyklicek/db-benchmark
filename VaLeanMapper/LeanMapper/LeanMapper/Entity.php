<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper;

use Closure;
use DibiConnection;
use DibiFluent;
use LeanMapper\Data;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Exception\InvalidStateException;
use LeanMapper\Exception\InvalidValueException;
use LeanMapper\Exception\MemberAccessException;
use LeanMapper\Exception\RuntimeException;
use LeanMapper\ObjectMixin;
use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\Property;
use LeanMapper\Relationship;
use LeanMapper\Row;
use Traversable;

/**
 * Base class for custom entities
 *
 * @author Vojtěch Kohout
 */
abstract class Entity
{

	public static $counter = 0;
	public static $staticCounter = 0;
	const ACTION_ADD = 'add';

	const ACTION_REMOVE = 'remove';

	/** @var Data */
	protected $data;

	public $stub = FALSE;

	/** @var Row */
	protected $row;

	/** @var IMapper */
	protected $mapper;

	/** @var EntityReflection[] */
	protected static $reflections = array();

	/** @var EntityReflection */
	private $currentReflection;

	/**
	 * Neni naplnena z databaze => bude se delat insert
	 * @var bool
	 */
	private $isDetached;

	/** @var array of function($name, $value) Je vyvolán při změně hodnoty v entitě */
	public $onChange;

	/** @var array of function($name, $value) Je vyvolán při přidání hodnoty do entity */
	public $onAdd;

	/** @var array of function($name, $value) Je vyvolán při odebrání hodnoty z entity */
	public $onRemove;

	/**
	 * @param Row|Traversable|array|null $arg
	 * @param null $mapper
	 * @throws Exception\InvalidArgumentException
	 * @param IMapper|null $mapper
	 */
	public function __construct($arg = NULL, $mapper = NULL)
	{
		$this->mapper = $mapper;
		if ($arg instanceof Row) {
			$this->row = $arg;
			if (!$mapper) {
				$this->mapper = $arg->getMapper();
			}

			$data = $arg->getData();
			$hiLevelData = array();
			foreach ($this->getProperties() as $property) {
				$v = isset($data[$property->getColumn()]) ? $data[$property->getColumn()] : NULL;
				if (!$property->hasRelationship()) {
					//if ($property->isWritable()) {
					$hiLevelData[$property->getName()] = $v;
					//}
				} else {
					if ($property->getRelationship() instanceof Relationship\HasOne) {
						if ($data[$property->getColumn()] !== NULL) {
							$entityClass = $property->getType();
							$entity = new $entityClass(NULL, $this->mapper);
							$entity->setPK($data[$property->getColumn()]);
							$entity->stub = TRUE;
							$entity->hydrate();
							$hiLevelData[$property->getName()] = $entity;
						} else {
							$hiLevelData[$property->getName()] = $data[$property->getColumn()];
						}
					}
				}
			}
			$this->isDetached = FALSE;
			$this->data = new Data($hiLevelData, $this->getProperties(), TRUE);
		} else {
			$this->isDetached = TRUE;
			//$this->mapper = \Environment::getVariable('mapper');
			// TODO: call fields initialization that would use default values from annotations
			$this->initDefaults();
			if ($arg !== null) {
				if (!is_array($arg) and !($arg instanceof Traversable)) {
					throw new InvalidArgumentException('Argument $arg in entity constructor must be either null, array, instance of LeanMapper\Row or instance of Traversable, ' . gettype($arg) . ' given.');
				}
				$this->assign($arg);
			}
		}

		$thisEntity = $this;

		$this->data->onChange[] = function ($field, $value) use ($thisEntity) {
			if (!empty($thisEntity->onChange)) {
				$thisEntity->onChange($field, $value);
			}
		};

		$this->data->onAdd[] = function ($field, $value) use ($thisEntity) {
			if (!empty($thisEntity->onAdd)) {
				$thisEntity->onAdd($field, $value);
			}
		};

		$this->data->onRemove[] = function ($field, $value) use ($thisEntity) {
			if (!empty($thisEntity->onRemove)) {
				$thisEntity->onRemove($field, $value);
			}
		};
	}

	/**
	 * Returns value of given field
	 *
	 * @param string $name
	 * @return mixed
	 * @throws InvalidValueException
	 * @throws MemberAccessException
	 * @throws RuntimeException
	 * @throws InvalidMethodCallException
	 */

	public function __get($name)
	{
		return $this->get($name);
	}

	public function get($name /*, array $filterArgs*/)
	{
		self::$counter++;
		$id = null;
		if ($this->row !== null) {
			$id = $this->row->id;
		}
		//startTimer('GET' . self::$counter, get_class($this) . '_' . $id . '_' . $name);
		$reflection = $this->getCurrentReflection();
		$nativeGetter = $reflection->getGetter('get' . ucfirst($name));
		if ($nativeGetter !== null) {
			return $nativeGetter->invoke($this); // filters are not relevant here
		}
		$property = $reflection->getEntityProperty($name);
		if ($property === NULL) {
			throw new MemberAccessException("Undefined property: $name");
		}
		$customGetter = $property->getGetter();
		if ($customGetter !== null) {
			$customGetterReflection = $reflection->getGetter($customGetter);
			if ($customGetterReflection === null) {
				throw new InvalidMethodCallException("Missing getter method '$customGetter'.");
			}
			return $customGetterReflection->invoke($this); // filters are not relevant here
		}

		if (!$this->data->isPopulated($name) &&
			$name != $this->getPKField() &&
			!$this->isDetached &&
			(!$property->hasRelationship() || $property->getRelationship() instanceof \LeanMapper\Relationship\HasOne)
		) {
			$entity = $this->findEntityByPK($this->{$this->getPKField()});
			foreach ($this->getProperties() as $p) {
				if (!$this->data->isPopulated($p->getName()) && $entity) {
					//$this->data->{$p->getName()} = $entity->{$p->getName()}; OPTIMALIZACE !
					$this->data->setDataValue($p->getName(), $entity->{$p->getName()});
				}
			}
		}

		$pass = $property->getGetterPass();
		if ($property->isBasicType()) {
			// $value = $this->data->$name; OPTIMALIZACE !
			$value = $this->data->getDataValue($name);
			if ($value === null) {
				if (!$property->isNullable()) {
					//throw new InvalidValueException("Property '$name' cannot be null.");
				}
			} else {
				if (!settype($value, $property->getType())) {
					throw new InvalidValueException("Cannot convert value '$value' to " . $property->getType() . '.');
				}
				if ($property->containsEnumeration() and !$property->isValueFromEnum($value)) {
					throw new InvalidValueException("Value '$value' is not from possible values enumeration.");
				}
			}
		} else {
			if ($property->hasRelationship()) {
				// OPTIMALIZACE !
				$isStub = $this->data->isPopulated($name) && $this->data->getDataValue($name) instanceof Entity && $this->data->$name->stub;
				if (!$this->data->isPopulated($name) || $isStub) {
					if (!$this->isDetached()) {
						$filter = ($set = $property->getFilters(0)) ? $this->getFilterCallback($set, func_get_args()) : null;
						$relationship = $property->getRelationship();

						$method = explode('\\', get_class($relationship));
						$method = 'get' . array_pop($method) . 'Value';
						$args = array($property, $filter);

						if ($method === 'getHasManyValue') {
							$args[] = ($set = $property->getFilters(1)) ? $this->getFilterCallback($set, func_get_args()) : null;
						}
						$value = call_user_func_array(array($this, $method), $args);
						if ($value) {
							$this->data->setDataValue($name, $value, TRUE, FALSE);
						} else {
							if ($relationship instanceof Relationship\BelongsToMany ||
								$relationship instanceof Relationship\HasMany
							) {
								$this->data->setDataValue($name, array(), TRUE, FALSE);
							}
						}
					} else {
						$relationship = $property->getRelationship();
						if ($relationship instanceof Relationship\BelongsToMany ||
							$relationship instanceof Relationship\HasMany
						) {
							$this->data->setDataValue($name, array(), TRUE, FALSE);
						}
					}
				}
				// $value = $this->data->$name; OPTIMALIZACE !
				$value = $this->data->getDataValue($name);
			} else {
				// $value = $this->data->$name; OPTIMALIZACE !
				$value = $this->data->getDataValue($name);
				if ($value === null) {
					/*if (!$property->isNullable()) {
						throw new InvalidValueException("Property '$name' cannot be null.");
					}*/
				} else {
					if (!$property->containsCollection()) {
						$type = $property->getType();
						if (!($value instanceof $type)) {
							throw new InvalidValueException("Property '$name' is expected to contain an instance of '$type', instance of '" . get_class($value) . "' given.");
						}
					} else {
						if (!is_array($value)) {
							throw new InvalidValueException("Property '$name' is expected to contain an array of '{$property->getType()}' instances.");
						}
					}
				}
			}
		}

		if ($pass !== null) {
			$value = $this->$pass($value);
		}

		//stopTimer('GET' . self::$counter);
		return $value;
	}

	/**
	 * Sets value of given field
	 *
	 * @param string $name
	 * @param mixed $value
	 * @throws InvalidMethodCallException
	 * @throws InvalidValueException
	 * @throws MemberAccessException
	 */
	function __set($name, $value)
	{
		$this->set($name, $value);
	}

	function set($name, $value)
	{
		$reflection = $this->getCurrentReflection();
		$nativeSetter = $reflection->getSetter('set' . ucfirst($name));
		if ($nativeSetter !== null) {
			$nativeSetter->invoke($this, $value);
		} else {
			$property = $reflection->getEntityProperty($name);
			if ($property === null) {
				throw new MemberAccessException("Undefined property: $name");
			}

			if (!$property->isWritable()) {
				throw new MemberAccessException("Cannot write to read only property '$name'.");
			}
			$customSetter = $property->getSetter();
			if ($customSetter !== null) {
				$customSetterReflection = $reflection->getSetter($customSetter);
				if ($customSetterReflection === null) {
					throw new InvalidMethodCallException("Missing setter method '$customSetter'.");
				}
				$customSetterReflection->invoke($this, $value);
			} else {
				$pass = $property->getSetterPass();
				$column = $property->getColumn();
				if ($value === null) {
					$relationship = $property->getRelationship();
					if ($relationship !== null) {
						if (!($relationship instanceof Relationship\HasOne)) {
							throw new InvalidMethodCallException('Only fields with m:hasOne relationship can be set to null.');
						}
					}
				} else {
					$type = $property->getType();
					if ($property->isBasicType()) {
						if (!settype($value, $property->getType())) {
							throw new InvalidValueException("Cannot convert value '$value' to " . $property->getType() . '.');
						}
						if ($property->containsEnumeration() and !$property->isValueFromEnum($value)) {
							throw new InvalidValueException("Value '$value' is not from possible values enumeration.");
						}
					} elseif ($property->getType() === 'VADateTime' && (is_numeric($value) || is_string($value))) {
						$value = \VADateTime::factory($value);
					} else {
						if (is_numeric($value)) {
							// prirazeni entity na zaklade ID => vytvori hydratovanou entitu
							if ($property->hasRelationship()) {
								$entityClass = $property->getType();
								$entity = new $entityClass(NULL, $this->mapper);
								$entity->setPK($value);
								$entity->hydrate();
								$value = $entity;
							}
						} elseif (!is_array($value) && !($value instanceof $type)) {
							throw new InvalidValueException("Unexpected value type: " . $property->getType() . " expected, " . get_class($value) . " given.");
						}
					}
				}
			}
			if ($pass !== null) {
				$value = $this->$pass($value);
			}
			//$this->data->$name = $value; OPTIMALIZACE !
			$this->data->setDataValue($name, $value);
		}
	}

	public function __isset($name)
	{
		try {
			return $this->get($name) !== null;
		} catch (MemberAccessException $e) {
			return false;
		}
	}

	/**
	 * Vrati mapper
	 * @return IMapper
	 */
	public function getMapper()
	{
		return $this->mapper;
	}

	/**
	 * Najde entitu sveho typu se zadanym ID
	 *
	 * @param Entity $property
	 */
	private function findEntityByPK($id)
	{
		$repositoryClass = $this->getRepositoryClass();
		$repository = new $repositoryClass;
		return $repository->find($id);
	}

	/**
	 * Vrati nazev property, ktera znaci identifikator entity (primarni klic)
	 * Potrebuje k tomu mapper, ale ten ma entita jen v pripade, ze byla vytvorena z databaze pres Row
	 *
	 */
	public function getPKField()
	{
		$pk = NULL;
		foreach ($this->getProperties() as $property) {
			if ($property->hasCustomFlag('primary')) {
				$pk = $property->getName();
				break;
			}
		}

		/*
		 * Pokud neni zadna property oznacena jako m:primary, precte
		 * si ji z repository.
		 *
		 * @todo: Je to tak spravne? Nevznika tam dualita? Potrebujeme vubec anotaci?
		 */
		if ($pk === NULL) {
			$repositoryClass = $this->getRepositoryClass();
			$r = new $repositoryClass();
			$pk = $r->getIdField();
		}

		return $pk;
	}

	/**
	 * Nastavi ID entity.
	 *
	 * @param mixed $value
	 */
	public function setPK($value)
	{
		$this->{$this->getPKField()} = $value;
	}

	/**
	 * Vrati primarni klic (id) entity
	 *
	 * @return mixed
	 */
	public function getPK()
	{
		return $this->{$this->getPKField()};
	}

	/**
	 * Porovna dve entity na zakalde primarniho klice
	 *
	 * @param Entity $entity
	 * @return bool
	 */
	public function compareTo(Entity $entity)
	{
		$idField = $this->getPKField();
		return get_class($entity) == get_class($this) && $this->get($idField) == $entity->get($idField) && $this->get($idField) != NULL;
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
			return $this->get(lcfirst(substr($name, 3)), $arguments);

		} elseif (substr($name, 0, 3) === 'set') {
			$this->set(lcfirst(substr($name, 3)), $arguments);

		} elseif (substr($name, 0, 5) === 'addTo' and strlen($name) > 5) {
			$this->addToOrRemoveFrom(self::ACTION_ADD, lcfirst(substr($name, 5)), array_shift($arguments));

		} elseif (substr($name, 0, 10) === 'removeFrom' and strlen($name) > 10) {
			$this->addToOrRemoveFrom(self::ACTION_REMOVE, lcfirst(substr($name, 10)), array_shift($arguments));

		} else if (substr($name, 0, 2) === 'on' and strlen($name) > 2) { // Podpora událostí...
			return ObjectMixin::call($this, $name, $arguments);
		} else {
			throw $e;
		}
	}

	/**
	 * Performs a mass value assignment (using setters)
	 *
	 * @param array|Traversable $values
	 * @param array|null $whitelist
	 * @throws InvalidArgumentException
	 */
	public function assign($values, array $whitelist = null)
	{
		if ($whitelist !== null) {
			$whitelist = array_flip($whitelist);
		}
		if (!is_array($values) and !($values instanceof Traversable)) {
			throw new InvalidArgumentException('Argument $values must be either array or instance of Traversable, ' . gettype($values) . ' given.');
		}
		foreach ($values as $field => $value) {
			if ($whitelist === null or isset($whitelist[$field])) {
				$this->set($field, $value);
			}
		}
	}


	/**
	 * Vraci vsechny property entity
	 * @return Property[]
	 */
	public function getProperties()
	{
		return $this->getCurrentReflection()->getEntityProperties();
	}

	/**
	 * Je entita cela naplnena?
	 * Bere v potaz zakladni property + relaci HasOne
	 * ostatni typy relaci se nezapocitavaji.
	 * @return bool
	 */
	public function isPopulated()
	{
		$populated = TRUE;
		foreach ($this->getProperties() as $property) {
			if (!$property->hasRelationship() ||
				$property->getRelationship() instanceof Relationship\HasOne
			) {
				$populated &= $this->data->isPopulated($property->getName());
			}
		}
		return $populated;
	}

	/**
	 * Returns array of high-level fields with values
	 *
	 * @param array|null $whitelist
	 * @return array
	 */
	public function getData(array $whitelist = null)
	{
		$data = array();
		if ($whitelist !== null) {
			$whitelist = array_flip($whitelist);
		}
		$usedGetters = array();
		foreach ($this->getProperties() as $property) {
			$field = $property->getName();
			if ($whitelist !== null and !isset($whitelist[$field])) {
				continue;
			}
			$data[$field] = $this->get($property->getName());
			$getter = $property->getGetter();
			if ($getter !== null) {
				$usedGetters[$getter] = true;
			}
		}

		// chceme toto? dela promlem metoda getProperties, coz neni getter na nejakou propery
		/*foreach ($reflection->getGetters() as $name => $getter) {
			if (isset($usedGetters[$getter->getName()])) {
				continue;
			}

			$field = lcfirst(substr($name, 3));
			if ($whitelist !== null and !isset($whitelist[$field])) {
				continue;
			}

			if ($getter->getNumberOfRequiredParameters() === 0) {
				$data[$field] = $getter->invoke($this);
			}
		}*/
		return $data;
	}


	/**
	 * Tells whether entity is in modified state
	 *
	 * @return bool
	 */
	public function isModified()
	{
		// TODO
		return $this->data->isModified();
	}

	// TODO
	public function getModifiedData()
	{
		return $this->data->getModifiedData();
	}

	/**
	 * Tells whether entity is in detached state (like newly created entity)
	 *
	 * @return bool
	 */
	public function isDetached()
	{
		return $this->isDetached;
	}

	/**
	 * Nastavi entitu jako novou
	 *
	 */
	public function detach()
	{
		$this->isDetached = TRUE;
	}

	/**
	 * Nastavi entitu jako svazanoou s databazi => bude se delat update
	 *
	 */
	public function hydrate()
	{

		$this->isDetached = FALSE;
	}

	/**
	 * Provides an mapper for entity
	 *
	 * @param IMapper $mapper
	 * @throws InvalidMethodCallException
	 * @throws InvalidStateException
	 */
	public function useMapper(IMapper $mapper)
	{
		if (!$this->isDetached()) {
			throw new InvalidMethodCallException('Mapper can only be provided to detached entity.');
		}
		$newProperties = $this->getReflection($mapper)->getEntityProperties();
		foreach ($this->getCurrentReflection()->getEntityProperties() as $oldProperty) {
			$oldColumn = $oldProperty->getColumn();
			if ($oldColumn !== null) {
				$name = $oldProperty->getName();
				if (!isset($newProperties[$name]) or $newProperties[$name]->getColumn() === null) {
					throw new InvalidStateException('Inconsistent sets of properties.');
				}
				if (isset($this->row->$oldColumn)) {
					$newColumn = $newProperties[$name]->getColumn();
					$value = $this->row->$oldColumn;
					unset($this->row->$oldColumn);
					$this->row->$newColumn = $value;
				}
			}
		}
		$this->mapper = $mapper;
		if ($this->row !== NULL) {
			$this->row->setMapper($mapper);
		}
		$this->currentReflection = null;
	}

	/**
	 * Marks entity as persisted
	 *
	 * @param int $id
	 * @param string $table
	 * @param DibiConnection $connection
	 */
	public function markAsCreated($id)
	{
		$this->id = $id;
		$this->hydrate();
		$this->data->clearModified();
	}

	/**
	 * Marks entity as non-updated (isModified() returns false right after this method call)
	 */
	public function markAsUpdated()
	{
		$this->data->clearModified();
	}

	public function markAsDeleted()
	{
		$this->detach();
		$this->data->clearModified();
	}

	/**
	 * @param IMapper|null $mapper
	 * @return EntityReflection
	 */
	protected static function getReflection(IMapper $mapper = null)
	{
		$class = get_called_class();
		$mapperClass = $mapper !== null ? get_class($mapper) : '';
		if (!isset(static::$reflections[$class][$mapperClass])) {
			static::$reflections[$class][$mapperClass] = new EntityReflection($class, $mapper);
		}

		return static::$reflections[$class][$mapperClass];
	}

	/**
	 * @return EntityReflection
	 */
	protected function getCurrentReflection()
	{
		if ($this->currentReflection === null) {
			$this->currentReflection = $this->getReflection($this->mapper);
		}
		return $this->currentReflection;
	}

	/**
	 * @param array $entities
	 * @return array
	 */
	protected function createCollection(array $entities)
	{
		return $entities;
	}

	/**
	 * Inicialiace prazdných dat s nastavením defaultních hodnot
	 *
	 */
	protected function initDefaults()
	{
		$values = array();
		//$values[$this->getPKField()] = NULL;
		foreach ($this->getProperties() as $property) {
			$values[$property->getName()] = NULL;
		}
		$this->data = new Data($values, $this->getProperties(), FALSE);
	}

	/**
	 * @param Property $property
	 * @param Row $row
	 * @return string
	 */
	protected function getEntityClass(Property $property, Row $row = null)
	{
		return $this->mapper->getEntityClass($property->getRelationship()->getTargetTable(), $row);
	}

	////////////////////
	////////////////////

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return Entity
	 * @throws InvalidValueException
	 */
	private function getHasOneValue(Property $property, Closure $filter = null)
	{
		if (!$this->row) {
			return NULL;
		}

		$relationship = $property->getRelationship();
		$row = $this->row->referenced($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingTargetTable());
		if ($row === null) {
			/*if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null.");
			}*/
			return null;
		} else {
			$class = $this->getEntityClass($property, $row);
			$entity = new $class($row, $this->mapper);
			$this->checkConsistency($property, $class, $entity);
			return $entity;
		}
	}

	/**
	 * @param Property $property
	 * @param Closure|null $relTableFilter
	 * @param Closure|null $targetTableFilter
	 * @return Entity[]
	 * @throws InvalidValueException
	 */
	private function getHasManyValue(Property $property, Closure $relTableFilter = null, Closure $targetTableFilter = null)
	{
		$relationship = $property->getRelationship();
		$rows = $this->row->referencing($relationship->getRelationshipTable(), $relTableFilter, $relationship->getColumnReferencingSourceTable(), $relationship->getStrategy());
		$value = array();
		$type = $property->getType();
		foreach ($rows as $row) {
			$valueRow = $row->referenced($relationship->getTargetTable(), $targetTableFilter, $relationship->getColumnReferencingTargetTable());
			if ($valueRow !== null) {
				$class = $this->getEntityClass($property, $valueRow);
				$entity = new $class($valueRow, $this->mapper);
				$this->checkConsistency($property, $class, $entity);
				$value[] = $entity;
			}
		}
		return $this->createCollection($value);
	}

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return Entity
	 * @throws InvalidValueException
	 */
	private function getBelongsToOneValue(Property $property, Closure $filter = null)
	{
		$relationship = $property->getRelationship();
		$rows = $this->row->referencing($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingSourceTable(), $relationship->getStrategy());
		$count = count($rows);
		if ($count > 1) {
			throw new InvalidValueException('There cannot be more than one entity referencing to entity with m:belongToOne relationship.');
		} elseif ($count === 0) {
			if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null.");
			}
			return null;
		} else {
			$row = reset($rows);
			$class = $this->getEntityClass($property, $row);
			$entity = new $class($row, $this->mapper);
			$this->checkConsistency($property, $class, $entity);
			return $entity;
		}
	}

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return Entity[]
	 */
	private function getBelongsToManyValue(Property $property, Closure $filter = null)
	{
		//startTimerCollect('getBelongsToManyValue');
		if (!$this->row) {
			return NULL;
		}

		//startTimerCollect('referencing');
		$relationship = $property->getRelationship();
		$rows = $this->row->referencing($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingSourceTable(), $relationship->getStrategy());
		$value = array();
		//stopTimer('referencing');

		//startTimerCollect('getBelongsToManyValue-foreach');
		foreach ($rows as $row) {
			$class = $this->getEntityClass($property, $row);
			//startTimerCollect('getBelongsToManyValue-entity');
			self::$staticCounter++;
			$entity = new $class($row, $this->mapper);
			//stopTimer('getBelongsToManyValue-entity');
			$this->checkConsistency($property, $class, $entity);
			$value[] = $entity;
		}
		//stopTimer('getBelongsToManyValue-foreach');
		//stopTimer('getBelongsToManyValue');
		return $this->createCollection($value);
	}

	/**
	 * @param array $propertyFilters
	 * @param array $filterArgs
	 * @return callable|null
	 */
	private function getFilterCallback(array $propertyFilters, array $filterArgs)
	{
		$filterCallback = null;
		if (!empty($propertyFilters)) {
			$filterArgs = isset($filterArgs[1]) ? $filterArgs[1] : array();
			$filterCallback = function (DibiFluent $statement) use ($propertyFilters, $filterArgs) {
				foreach ($propertyFilters as $propertyFilter) {
					call_user_func_array($propertyFilter, array_merge(array($statement), $filterArgs));
				}
			};
		}
		return $filterCallback;
	}

	/**
	 * @param string $action
	 * @param string $name
	 * @param mixed $arg
	 * @throws InvalidMethodCallException
	 * @throws InvalidArgumentException
	 * @throws InvalidValueException
	 */
	private function addToOrRemoveFrom($action, $name, $arg)
	{
		$method = $action === self::ACTION_ADD ? 'addTo' : 'removeFrom';
		if ($arg === null) {
			throw new InvalidArgumentException("Invalid argument given.");
		}
		$property = $this->getCurrentReflection()->getEntityProperty($name);
		if ($property === null or !$property->hasRelationship() or !($property->getRelationship() instanceof Relationship\HasMany)) {
			//throw new InvalidMethodCallException("Cannot call $method method with $name property. Only properties with m:hasMany relationship can be managed this way.");
		}
		if ($property->getFilters()) {
			throw new InvalidMethodCallException("Cannot call $method method with $name property. Only properties with no filters can be managed this way."); // deliberate restriction
		}
		$relationship = $property->getRelationship();

		$type = $property->getType();
		if (!($arg instanceof $type)) {
			throw new InvalidValueException("Unexpected value type: " . $property->getType() . " expected, " . (is_object($arg) ? get_class($arg) : gettype($arg)) . " given.");
		}

		// zavolani, aby se nacetly zaznamy pokud existuji
		$this->get($name);
		$this->data->__call($method . $name, array($arg));
	}

	/**
	 * @param Property $property
	 * @param string $mapperClass
	 * @param Entity $entity
	 * @throws InvalidValueException
	 */
	private function checkConsistency(Property $property, $mapperClass, Entity $entity)
	{
		$type = $property->getType();
		if (!($entity instanceof $type)) {
			throw new InvalidValueException("Inconsistency found: property '{$property->getName()}' is supposed to contain an instance of '$type' (due to type hint), but mapper maps it to '$mapperClass'. Please fix getEntityClass() method in mapper, property annotation or entities inheritance.");
		}
	}

	/**
	 * Vrati tridu repozitare
	 * return class
	 */
	public function getRepositoryClass()
	{
		$repositoryClass = $this->getReflection()->getRepository();
		return $repositoryClass;
	}

	/**
	 * Vrati repozitar entity
	 * @return Repository
	 *
	 */
	public function getRepository()
	{
		$repositoryClass = $this->getRepositoryClass();
		return new $repositoryClass();
	}

	/**
	 * Vraci true, pokud entita ma property s danym nazvem, jinak false.
	 *
	 * @param string $propertyName nazev hledane property
	 * @return bool
	 */
	public function hasProperty($propertyName)
	{
		foreach ($this->getProperties() as $property) {
			if ($property->getName() === $propertyName) {
				return TRUE;
			}
		}

		return FALSE;
	}
}

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

use dibi;
use DibiConnection;
use DibiRow;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidStateException;
use LeanMapper\Reflection\AnnotationsParser;
use ReflectionClass;

/**
 * Base class for custom repositories
 *
 * @author Vojtěch Kohout
 *
 * @property-read mixed $idField Property entity, která značí primární klíč
 */
abstract class Repository extends Object
{

	const ACTION_INSERT = 'insert';
	const ACTION_UPDATE = 'update';
	const ACTION_DELETE = 'delete';
	const PROPERTY_CREATED = 'created';
	const PROPERTY_UPDATED = 'updated';
	const PROPERTY_CREATED_BY = 'createdBy';
	const PROPERTY_UPDATED_BY = 'updatedBy';
	const STATUS_MISSING = 'missing';

	/** @var DibiConnection */
	protected $connection;

	/** @var IMapper */
	protected $mapper;

	/** @var string */
	protected $table;

	/** @var string */
	protected $entityClass;

	/** @var string */
	private $docComment;

	/** @var bool */
	private $entityAnnotationChecked = false;

	/** @var array of function(Entity $entity, boolean $new) Je vyvolán po dokončení persistence entity */
	public $onPersist;

	/** @var array of function($deletedId) Je vyvolán po odstranění entity z databáze */
	public $onDelete;

	/**
	 * @param DibiConnection $connection
	 * @param IMapper $mapper
	 */
	public function __construct(\DibiConnection $connection = NULL, \LeanMapper\IMapper $mapper = NULL)
	{
		if ($connection !== NULL) {
			$this->connection = $connection;
		} else {
			$this->connection = \Environment::getVariable('dibiConnection');
		}

		if ($mapper !== NULL) {
			$this->mapper = $mapper;
		} else {
			$this->mapper = \Environment::getVariable('mapper');
		}
	}

	/**
	 * Stores modified fields of entity into database or creates new row in database when entity is in detached state
	 *
	 * @param Entity $entity
	 * @param boolean $transaction True for enabling transaction, false for not enabling
	 * @return mixed
	 */
	public function persist(Entity $entity, $transaction = TRUE)
	{
		if ($transaction) {
			$this->connection->begin();
		}

		try {
			$result = NULL;
			$primaryKey = $this->mapper->getPrimaryKey($this->getTable());

			$this->checkEntityType($entity);
			$new = $entity->isDetached();
			if ($entity->isModified()) {
				if ($new) {
					$result = $this->insertEntity($entity, $primaryKey);
				} else {
					$result = $this->updateEntity($entity, $primaryKey);
				}
			}
			if (!empty($this->onPersist)) {
				$this->onPersist($entity, $new);
			}
			$this->persistHasManyChanges($entity);
			if ($transaction) {
				$this->connection->commit();
			}
			return $result;
		} catch (\DibiException $e) {
			if ($transaction) {
				$this->connection->rollback();
			}
			throw $e;
		}
	}

	protected function insertEntity(Entity $entity, $primaryKey)
	{
		if ($entity->hasProperty(self::PROPERTY_CREATED) && $entity->{self::PROPERTY_CREATED} === NULL) {
			$entity->{self::PROPERTY_CREATED} = time();
		}

		if ($entity->hasProperty(self::PROPERTY_UPDATED)) {
			$entity->{self::PROPERTY_UPDATED} = time();
		}

		$entity->useMapper($this->mapper);

		$columnData = $this->getColumnsData($entity);
		$values = $this->beforeCreate($columnData);

		$this->connection->query(
				  'INSERT INTO %n %v', $this->getTable(), $values
		);
		$id = isset($values[$primaryKey]) ? $values[$primaryKey] : $this->connection->getInsertId();
		$entity->markAsCreated($id);
		$result = $id;

		return $result;
	}

	protected function updateEntity(Entity $entity, $primaryKey)
	{
		if ($entity->hasProperty(self::PROPERTY_UPDATED)) {
			$entity->{self::PROPERTY_UPDATED} = time();
		}

		$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);
		$columnData = $this->getColumnsData($entity);
		$values = $this->beforeUpdate($columnData);
		$result = $this->connection->query(
				  'UPDATE %n SET %a WHERE %n = ?', $this->getTable(), $values, $primaryKey, $entity->$idField
		);
		$entity->markAsUpdated();

		return $result;
	}

	/**
	 * Persitujeentity na zaklade jeji repozitare
	 *
	 * @param Entity $entity
	 */
	private function persistEntity($entity)
	{
		$repositoryClass = $entity->getRepositoryClass();
		$repository = new $repositoryClass();
		$repository->persist($entity, FALSE);
	}

	/**
	 * Vrati premapovane property entity na sloupce
	 *
	 * @param Entity $entity
	 * @return array
	 */
	public function getColumnsData($entity)
	{
		$result = array();
		$data = $entity->getData();
		foreach ($entity->getProperties() as $property) {
			if (!$property->isWritable()) {
				continue;
			}

			if ($property->hasRelationship()) {
				$type = explode('\\', get_class($property->getRelationship()));
				$type = array_pop($type);
				if ($type == 'HasOne') {
					if ($data[$property->getName()] !== NULL) {
						$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
						$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);
						if ($data[$property->getName()]->isDetached()) {
							$this->persistEntity($data[$property->getName()]);
						}
						$result[$property->getColumn()] = $data[$property->getName()]->$idField;
					} else {
						$result[$property->getColumn()] = NULL;
					}
				}
			} else {
				$result[$property->getColumn()] = $data[$property->getName()];
			}
		}
		return $result;
	}

	/**
	 * Removes given entity (or entity with given id) from database
	 *
	 * @param Entity|int $arg
	 * @throws InvalidStateException
	 */
	public function delete($arg)
	{
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);

		$id = $arg;
		if ($arg instanceof Entity) {
			$this->checkEntityType($arg);
			if ($arg->isDetached()) {
				throw new InvalidStateException('Cannot delete detached entity.');
			}
			$id = $arg->$idField;
			$arg->markAsDeleted();
		}
		$this->connection->query(
				  'DELETE FROM %n WHERE %n = ?', $this->getTable(), $primaryKey, $id
		);
		if (!empty($this->onDelete)) {
			$this->onDelete($id);
		}
	}

	/**
	 * @param Entity $entity
	 */
	protected function persistHasManyChanges(Entity $entity)
	{
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);

		foreach ($entity->getProperties() as $property) {
			if ($property->hasRelationship() && $property->isWritable()) {
				$relationship = $property->getRelationship();
				// vazba 1:N
				if ($relationship instanceof Relationship\BelongsToMany) {
					$column = $relationship->getColumnReferencingSourceTable();
					$ids = array();
					foreach ($entity->{$property->getName()} as $relEntity) {
						$parentProperty = NULL;
						foreach ($relEntity->getProperties() as $relProperty) {
							if ($relProperty->getColumn() == $column) {
								$parentProperty = $relProperty->getName();
							}
						}
						if ($parentProperty) {
							$relEntity->$parentProperty = $entity->$idField;
							$this->persistEntity($relEntity);
							$ids[] = $relEntity->id;
						} else {
							throw new InvalidStateException('Foreign key property $' . $column . ' is not defined in entity ' . get_class($relEntity));
						}
					}
					// smazani prebyvajicich zaznamu
					$ids[] = 0;
					$this->connection->query(
							  'DELETE FROM %n WHERE %n = %i AND %n NOT IN (%i)', $relationship->getTargetTable(), $relationship->getColumnReferencingSourceTable(), $entity->$idField, $primaryKey, $ids
					);
				}
				// vazba M:N
				if ($relationship instanceof Relationship\HasMany) {
					$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
					$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);
					// prvne ulozeni vazanych entit
					$values = array();
					foreach ($entity->{$property->getName()} as $relEntity) {
						$values[] = $relEntity->getPK();
						$this->persistEntity($relEntity);
					}
					// ulozeni do vazebni tabulky
					$column1 = $relationship->getColumnReferencingSourceTable();
					$value1 = $entity->$idField;
					$column2 = $relationship->getColumnReferencingTargetTable();
					$relationshipTable = $relationship->getRelationshipTable();

					// zjisteni, co uz v DB je
					$dbValues = array();
					$dbValuesArr = $this->connection->fetchAll(
							  'SELECT ' . $column2 . ' FROM ' . $relationshipTable . ' WHERE ' . $column1 . ' = ' . $value1);
					foreach ($dbValuesArr as $v) {
						$dbValues[] = $v->$column2;
					}
					// insert novych
					$multiInsert = array();
					foreach ($values as $value) {
						if (!in_array($value, $dbValues)) {
							$multiInsert[] = array(
								$column1 => $value1,
								$column2 => $value,
							);
						}
					}
					if (!empty($multiInsert)) {
						$this->connection->query(
								  'INSERT INTO %n %ex', $relationshipTable, $multiInsert
						);
					}
					// smazani prebyvajicich zaznamu
					$values[] = 0;
					$this->connection->query(
							  'DELETE FROM %n WHERE %n = %i AND %n NOT IN (%i)', $relationshipTable, $column1, $value1, $column2, $values
					);
				}
			}
		}
		/* $primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		  $idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);

		  $multiInsert = array();
		  foreach ($entity->getHasManyRowDifferences() as $key => $difference) {
		  list($columnReferencingSourceTable, $relationshipTable, $columnReferencingTargetTable) = explode(':', $key);
		  foreach ($difference as $value => $count) {
		  if ($count > 0) {
		  for ($i = 0; $i < $count; $i++) {
		  $multiInsert[] = array(
		  $columnReferencingSourceTable => $entity->$idField,
		  $columnReferencingTargetTable => $value,
		  );
		  }
		  } else {
		  $this->connection->query(
		  'DELETE FROM %n WHERE %n = ? AND %n = ? %lmt', $relationshipTable, $columnReferencingSourceTable, $entity->$idField, $columnReferencingTargetTable, $value, - $count
		  );
		  }
		  }
		  }
		  if (!empty($multiInsert)) {
		  $this->connection->query(
		  'INSERT INTO %n %ex', $relationshipTable, $multiInsert
		  );
		  } */
	}

	/**
	 * Vrati property entity, ktera znaci primarni klic
	 *
	 */
	public function getIdField()
	{
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		return $this->mapper->getEntityField($this->getTable(), $primaryKey);
	}

	/**
	 * Adjusts prepared values before database insert call
	 *
	 * @param array $values
	 * @return array
	 */
	protected function beforeCreate(array $values)
	{
		return $this->beforePersist($values);
	}

	/**
	 * Adjusts prepared values before database update call
	 *
	 * @param array $values
	 * @return array
	 */
	protected function beforeUpdate(array $values)
	{
		return $this->beforePersist($values);
	}

	/**
	 * Adjusts prepared values before database insert or update call
	 *
	 * @param array $values
	 * @return array
	 */
	protected function beforePersist(array $values)
	{
		return $values;
	}

	/**
	 * Helps to create entity instance from given DibiRow instance
	 *
	 * @param DibiRow $dibiRow
	 * @param string|null $entityClass
	 * @param string|null $table
	 * @return mixed
	 */
	protected function createEntity(DibiRow $dibiRow, $entityClass = null, $table = null)
	{
		if ($table === null) {
			$table = $this->getTable();
		}
		$result = Result::getInstance($dibiRow, $table, $this->connection, $this->mapper);
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());

		$row = $result->getRow($dibiRow->$primaryKey);
		if ($entityClass === null) {
			$entityClass = $this->getEntityClass($row);
		}
		return new $entityClass($row);
	}

	/**
	 * Helps to create array of entities from given array of DibiRow instances
	 *
	 * @param DibiRow[] $rows
	 * @param string|null $entityClass
	 * @param string|null $table
	 * @return array
	 */
	protected function createEntities(array $rows, $entityClass = null, $table = null)
	{
		if ($table === null) {
			$table = $this->getTable();
		}
		$entities = array();
		$collection = Result::getInstance($rows, $table, $this->connection, $this->mapper);
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		if ($entityClass !== null) {
			foreach ($rows as $dibiRow) {
				$entities[$dibiRow->$primaryKey] = new $entityClass($collection->getRow($dibiRow->$primaryKey));
			}
		} else {
			foreach ($rows as $dibiRow) {
				$entityClass = $this->getEntityClass($row = $collection->getRow($dibiRow->$primaryKey));
				$entities[$dibiRow->$primaryKey] = new $entityClass($row);
			}
		}
		return $this->createCollection($entities);
	}

	/**
	 * Returns name of database table related to entity which repository can handle
	 *
	 * @return string
	 * @throws InvalidStateException
	 */
	protected function getTable()
	{
		if ($this->table === null) {
			$name = AnnotationsParser::parseSimpleAnnotationValue('table', $this->getDocComment());
			$this->table = $name !== null ? $name : $this->mapper->getTableByRepositoryClass(get_called_class());
		}
		return $this->table;
	}

	/**
	 * Returns fully qualified name of entity class which repository can handle
	 *
	 * @param Row|null $row
	 * @return string
	 * @throws InvalidStateException
	 */
	public function getEntityClass(Row $row = null)
	{
		if ($this->entityClass === null) {
			if (!$this->entityAnnotationChecked) {
				$this->entityAnnotationChecked = true;
				$entityClass = AnnotationsParser::parseSimpleAnnotationValue('entity', $this->getDocComment());
				if ($entityClass !== null) {
					return $this->entityClass = $entityClass;
				}
			}
			return $this->mapper->getEntityClass($this->mapper->getTableByRepositoryClass(get_called_class()), $row);
		}
		return $this->entityClass;
	}

	/**
	 * @param Entity $entity
	 * @throws InvalidArgumentException
	 */
	protected function checkEntityType(Entity $entity)
	{
		$entityClass = $this->getEntityClass();
		if (!($entity instanceof $entityClass)) {
			throw new InvalidArgumentException('Repository ' . get_called_class() . ' cannot handle ' . get_class($entity) . ' entity.');
		}
	}

	/**
	 * @param array $entities
	 * @return array
	 */
	protected function createCollection(array $entities)
	{
		return $entities;
	}

	////////////////////
	////////////////////

	/**
	 * @return string
	 */
	private function getDocComment()
	{
		if ($this->docComment === null) {
			$reflection = new ReflectionClass(get_called_class());
			$this->docComment = $reflection->getDocComment();
		}
		return $this->docComment;
	}

	public function beginTransaction()
	{
		return $this->connection->begin();
	}

	public function commitTransaction()
	{
		return $this->connection->commit();
	}

	public function rollbackTransaction()
	{
		return $this->connection->rollback();
	}

}

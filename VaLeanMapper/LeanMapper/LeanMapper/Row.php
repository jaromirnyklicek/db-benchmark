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

/**
 * Pointer to specific position inside LeanMapper\Result instance
 *
 * @author Vojtěch Kohout
 */
class Row
{

	/** @var Result */
	private $result;

	/** @var int */
	private $id;


	/**
	 * @param Result $result
	 * @param int $id
	 */
	public function __construct(Result $result, $id)
	{
		$this->result = $result;
		$this->id = $id;
	}

	/**
	 * Returns value of given field
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->result->getDataEntry($this->id, $name);
	}

	/**
	 * Sets value of given field
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->result->setDataEntry($this->id, $name, $value);
	}

	/**
	 * Tells whether Row has given field
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->result->hasDataEntry($this->id, $name);
	}

	/**
	 * Unsets given field
	 *
	 * @param string $name
	 */
	public function __unset($name)
	{
		$this->result->unsetDataEntry($this->id, $name);
	}

	/**
	 * @param IMapper $mapper
	 */
	public function setMapper(IMapper $mapper)
	{
		$this->result->setMapper($mapper);
	}

	/**
	 * @return IMapper|null
	 */
	public function getMapper()
	{
		return $this->result->getMapper();
	}

	/**
	 * Returns array of fields with values
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->result->getData($this->id);
	}

	/**
	 * Returns referenced LeanMapper\Row instance
	 *
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @return Row|null
	 */
	public function referenced($table, Closure $filter = null, $viaColumn = null)
	{
		return $this->result->getReferencedRow($this->id, $table, $filter, $viaColumn);
	}

	/**
	 * Returns array of LeanMapper\Row instances referencing current row
	 *
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @param string|null $strategy
	 * @return Row[]
	 */
	public function referencing($table, Closure $filter = null, $viaColumn = null, $strategy = null)
	{
		return $this->result->getReferencingRows($this->id, $table, $filter, $viaColumn, $strategy);
	}


}
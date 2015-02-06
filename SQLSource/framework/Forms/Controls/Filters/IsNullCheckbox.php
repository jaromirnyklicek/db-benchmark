<?php

/**
 * Checkbox, ktery ve filtru provadi při zaškrtnutí dotaz IS NULL nad sloupcem.
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */
 
class IsNullCheckbox extends Checkbox
{	
	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValue();
		if(empty($value)) return null;
		$s = $column.' IS NULL';
		return '('.$s.')';
	}
}
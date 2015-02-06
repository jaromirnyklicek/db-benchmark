<?php
/**
 * MultiSelectBoxDb
 *
 * MultiSelect s ulozenim do databaze pres vazabni tabulku.
 * Metodou setBindSql se nastavi vazebni SQL parametry. Pri pouziti v databazovem formu
 * se volanim loadFromDb() nactou data z vazebni tabulky do controlu.
 * Pri ulozeni si zse resi ulozeni control sam v metode save()
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class MultiSelectBoxDb extends MultiSelectBox
{

	protected $sqlTable;
	protected $sqlChild;
	protected $sqlParent;

	public function __construct($label, $items = NULL, $size = NULL)
	{
		parent::__construct($label, $items, $size);
	}

	/**
	* Nevstupuje do hlavniho dotazu
	*
	*/
	public function getValueOut()
	{

	}

	public function setBindSql($table, $child, $parent)
	{
		$this->sqlTable = $table;
		$this->sqlChild = $child;
		$this->sqlParent = $parent;
	}

	/**
	* Hodnoty je potreba nacist z vazebni tabulky
	*
	*/
	public function loadFromDb($orm)
	{
		$values = sql::toValues('SELECT '.$this->sqlChild.' FROM '.$this->sqlTable.' WHERE '.$this->sqlParent.'='.$orm->getId());
		$this->setValue($values);
	}

	/**
	* Ulozeni do vazebni tabulky.
	*
	* @param mixed $orm
	*/
	public function save($orm)
	{
		$parent = $orm->getId();
		$values = $this->getValue();
		$sql = $this->sqlChild;
		$foreignKey = $this->sqlParent;

		// vlozeni zaznamu, pokud jiz ve vazebni tabulce neexistuji
		foreach($values as $value) {
			$exists = sql::toScalar('SELECT id FROM '.$this->sqlTable.' WHERE '.$foreignKey.'='.$parent.' AND '.$sql.' = '.$value);
			if(!$exists) {
				sql::query('INSERT INTO '.$this->sqlTable.' ('.$sql.', '.$foreignKey.') VALUES ('.$value.', '.$parent.')');
			}
		}
		// vymazani prebytecnych zaznanu ve vazebni tabilce
		if($values) {
			$in = join(',', $values);
			sql::query('DELETE FROM '.$this->sqlTable.' WHERE '.$foreignKey.'='.$parent.' AND '.$sql.' NOT IN ('.$in.')');
		}
		else {
			sql::query('DELETE FROM '.$this->sqlTable.' WHERE '.$foreignKey.'='.$parent);
		}

	}
}
<?php
/**
* Ke každé komponentě pro zobrazení dat lze připojit ColumnModel, který ma největší význam v DataGridu.
* Column model definuje sloupce v DataGridu.
* Do column modelu lze přidávat instance objektů odvozených ze třídy Column.
*
*
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
*/

class ColumnModel extends Hashtable
{
	/**
	* @var DataList
	*/
	protected $parent;


	public function setParent($parent)
	{
		$this->parent = $parent;
	}

	/**
	 * Pridani sloupce do modelu
	 *
	 * @param Column $column
	 */
	public function addColumn($column, $key = null)
	{
		if($this->getColumn($column->name)) {
			throw new Exception('Column `'.$column->name.'` is already in the ColumnModel');
		}
		if (strpos($column->name, '--') !== FALSE) {
			throw new Exception('Column `'.$column->name.'` can\'t contain -- in name');
		}
		parent::add(!isset($key) ? $column->name : $key, $column);
		$column->setParent($this);
		$column->setDataList($this->parent);
		return $column;
	}

	/**
	 * Nalezne sloupec podle jmena
	 *
	 * @param string $name
	 * @return Column
	 */
	public function getColumn($name)
	{
		// pripad vnoreneho sloupce do ColumnContaineru
		if (strpos($name, '--') !== FALSE) {
			list($parent, $name) = explode('--', $name);
			$column = $this->getColumn($parent);
			return isset($column[$name]) ? $column[$name] : NULL;
		}
		else {
			foreach($this as $value) {
				if($value->name == $name) return $value;
			}
		}
	}

	public function getVisibleColumns()
	{
		$visible = array();
		foreach($this as $column) {
			if($column->getVisible()) $visible[] = $column;
		}
		return $visible;
	}

	/**
	 * Inserts item (\ArrayAccess implementation).
	 * @param  string key
	 * @param  Culumn
	 * @return void
	 * @throws \NotSupportedException, \InvalidArgumentException
	 */
	public function offsetSet($key, $column)
	{
		$this->addColumn($column, $key);
	}

	/**
	 * Returns item (\ArrayAccess implementation).
	 * @param  string key
	 * @return mixed
	 * @throws KeyNotFoundException, \InvalidArgumentException
	 */
	public function offsetGet($key)
	{
		if (!is_scalar($key)) {
			throw new /*\*/InvalidArgumentException("Key must be either a string or an integer, " . gettype($key) ." given.");
		}
		if (parent::offsetExists($key)) {
			return parent::offsetGet($key);
		} else {
			throw new KeyNotFoundException('Key `'.$key.'` not found.');
		}
	}
}
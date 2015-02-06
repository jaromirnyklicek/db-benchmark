<?php
/**
*  Textovy input do filtru, kde lze zadavat vice slov oddelenych carkou, podle kterych se pak filtruje
 *
 * @author	   Ondrej Novak
 * @package    Forms
 */
class TextFilterSeparator extends TextInput
{
	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValue();
		if($value === NULL || $value === '') return NULL;
		if(!is_array($column)) $column = array($column);
		$values = explode(',', $value);
		foreach($values as &$value) {
			$value = '"'.Database::instance()->escape_str((trim($value))).'"';
		}
		$s = array();
		foreach($column as $c) {
			if($this->collate !== NULL) $c .= ' COLLATE '.$this->collate;
			$s[] = $c.' IN ('.join(',', $values).')';
		}
		return '('.join(' OR ', $s).')';
	}
}
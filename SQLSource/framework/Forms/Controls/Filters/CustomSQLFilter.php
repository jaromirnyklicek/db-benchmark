<?php

/**
* SQL filtr s vlasnim podminkou do SQL
*/
class CustomSQLFilter extends HiddenField
{
	public function sqlWhere()
	{
		$sql = $this->getOption('sql');
		$value = $this->getValue();
		if(empty($value)) return null;
		$s = sprintf($sql, Database::instance()->escape_str($value));
		return '('.$s.')';
	}
}

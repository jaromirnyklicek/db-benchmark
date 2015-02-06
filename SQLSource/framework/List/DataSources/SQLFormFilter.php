<?php


/**
 * Filtrovaci trida, ktera z fitlrovaciho formulare sestavi SQL dotaz. Tato trida je vychozi pro SQLSource.
 * 
 * @package Lists
 * @author Ondrej Novak
 * @copyright Copyright (c) 2009, Ondrej Novak
 * @version $1.0$
 */
class SQLFormFilter implements IFormFilter
{
	/**
	 * @var callback na vlastni filtovani 
	 */
	protected $where;


	/**
	 * @param $callback nastaveni funkce, ktera vrati SQL podminku
	 */
	public function setWhere($callback)
	{
		$this->where = $callback;
	}


	/**
	 * Vrati SQL konstrukci do SQL dotazu. Lze vyuzit vlastni funkci pro filtrovani.
	 * Defaultni chovani spoji vsechny inputy filtru spojkou AND
	 *
	 * @param Filter $form
	 * @return string
	 */
	public function getFilter($filter)
	{
		$form = $filter->getForm();
		// vlastni filtrovani
		if ($this->where != NULL) {
			return call_user_func($this->where, $form);
		} else { // defaultni filtrovani. Sloupce spojene AND
			return self::buildSql($form);
		}
	}


	public static function buildSql($filterForm)
	{
		$where = array();
		foreach ($filterForm->getComponents() as $control) {
			if (method_exists($control, 'sqlWhere')) { // todo: resit pres interface
				$value = $control->sqlWhere();
				if ($value !== "" && $value !== NULL) {
					$where[] = '(' . $value . ')';
				}
			}
		}
		return empty($where) ? 1 : join(' AND ', $where);
	}

}

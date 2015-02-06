<?php
/**
* Trida reprezentuji razeni pro SQL source. Meziclanek mezi DataListem a SQLSourcem.
* SQLSource obdrzi napr. pozadavek: serad podle oblibenosti. Ve skutecnosti toto razeni muze byt 
* podle vice sloupcu, takze tato trida prevadi "alias" do realne SQL podminky
*/
class SQLOrder extends Object
{
	
	const ASC = 'a';
	const DESC = 'd';
	
	protected $aliases = array();
	
	public function getOrder($alias, $direction = self::ASC)
	{
		if(empty($this->aliases)) return NULL;
		if(!isset($this->aliases[$alias])) return NULL;
		$sql = $this->aliases[$alias];
		return $sql[$direction];
	}
	
	public function addAlias($alias, $asc, $desc = NULL)
	{
		if($desc == NULL) {
			$simple = TRUE;
			$desc = $asc;
		}
		else $simple = FALSE;
		$this->aliases[$alias][self::ASC] = $asc;
		$this->aliases[$alias][self::DESC] = $desc;		   
		if($simple) {
			$this->aliases[$alias][self::ASC] .= ' ASC';
			$this->aliases[$alias][self::DESC] .= ' DESC';
		}
	}
}
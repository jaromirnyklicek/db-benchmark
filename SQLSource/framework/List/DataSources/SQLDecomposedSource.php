<?php
/**
* SQL Decomposed Source pracuje s dvema SQL dotazy.
* Trida je chodna pro optimalizaci narocneho SQL, ktery je mozne rozdelit na dva samostatne dotazy.
* Hlavni SQL dotaz ($idSql) slouzi k ziskani vsech ID zaznamu a druhym dotazem je k IDckam 
* dotahnou potrebne data (titulek, foto...)
* Do WHERE sekundarniho dotazu se dotazuje zastupny znak ve tvaru [id/a.id]. Dulezita je cast za lomitkem, 
* ktera oznacuje sloupec, ktery se rozbali jako: a.id IN (1,2,3)
* 
* @package DataSource
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
*/

class SQLDecomposedSource extends SQLSource
{	
	
	private $idSql;    
	
	public function __construct($sql, $idSql)
	{
		parent::__construct($sql);
		$this->idSql = $idSql;
	}
	
	/**
	 * Nacte data k zobrazeni
	 * Parametr obsahuje nepovinne indexy:
	 * - where - filtrovaci formular   
	 * - page - pozadovana stranka
	 * - limit - pocet zaznamu na stranku
	 * - order - textova reprezentace razeni (napr.: table.id DESC)
	 * aletrenativa k textovemu $order:
	 * - order->column - instance Column - sloupec, podle ktere se bude radit
	 * - order->direction - emun('a','d') - smer razeni
	 *
	 * @param array $params
	 * @return SQLSource
	 */
	public function loadData($params = array())
	{
			   
		
		if($params != NULL) $this->loadParams($params);
		$sql = $this->getSql();
		
		$idSql = $this->getIdSql();
		
		$dbRes = $this->db->query($idSql);
		if($this->sqlCalcFoundRows) {
			$this->foundRows = $dbRes->found_rows();
		}
		
		foreach($dbRes as $item) {
			$idArr[] = $item->id;
		}
		preg_match('#\[id/(.*?)\]#', $sql, $m);
		if(isset($m[1])) {
			$column = $m[1];
			if(empty($idArr)) $s = '0';
			else $s = $column.' IN ('.join(',', $idArr).')';
			$sql = str_replace('[id/'.$column.']', $s, $sql);	 
		}							  

		// provedeni
		$dbRes = $this->db->query($sql);		
		$this->data = new DataTable($dbRes->as_array());
		
		
		// dotazeni vazeb
		foreach($this->bind as $bind) $this->processBind($bind);		
		foreach($this->bindSimple as $bind) $this->processBindSimple($bind);		
		$this->data = $this->applyCallbacks($this->data);
		$this->loaded = TRUE;
		return $this;
	}	
	
	public function getSql()
	{ 
		$sql = $this->sql;				 
	  
		// razeni se prida na konec SQL dotazu
		$sql .= $this->getSqlOrder();			   
		
		return $sql;
	}

	protected function getIdSql()	 
	{
		// substituce WHERE (z filtru)
		$idSql = $this->idSql;
		
		 // pokud neni SQL_CALC_FOUND_ROWS prida ho do dotazu
		if($this->sqlCalcFoundRows && !preg_match('/SELECT.*SQL_CALC_FOUND_ROWS/i', $idSql)) {
			$idSql = preg_replace('/SELECT./i', 'SELECT SQL_CALC_FOUND_ROWS ', $idSql, 1);
		}		 
		
		$idSql = preg_replace('/\[WHERE\]|\[FILTER\]/i', $this->where(), $idSql);
		
		$idSql = $this->join($idSql);
				
		// razeni se prida na konec SQL dotazu
		$idSql .= $this->getSqlOrder();
			  
		// limit se prida na konec SQL dotazu
		if(isset($this->page) && isset($this->limit)) {
		   $idSql .= ' LIMIT '.(($this->page-1)*$this->limit + $this->offset).', '.$this->limit;
		}
		return $idSql;
	}
	
	/* toto lze vyuzit pri dynamycke tvoreni SQL s JOINy podle nastaveni filtru */
	protected function join($idSql)
	{
		
		/*
		Example:
		$value = $this->filter['tag']->getValue();
		if(!empty($value)) {
			$join = 'LEFT JOIN articles_tags ag ON ag.article = a.id';
			$idSql = preg_replace('/WHERE/i', ' '.$join.' WHERE', $idSql);			  
		}
		*/
		return $idSql;
	}
}
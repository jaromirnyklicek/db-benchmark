<?php

/**
* Trida je zavadi Cache pro provedeni dotazu a jeho callbacku
* Parametrem v konstruktoru je cas expirace a pripadne tagy pro invalidaci.
* 
* @package DataSource
* @author Ondrej Novak
* @copyright Copyright (c) 2010, Ondrej Novak
*/
class SQLCachedSource extends SQLSource
{			
	
	protected $cache;
	protected $expire;
	protected $tags;
	
	public function __construct($sql, $expire = NULL, $tags = NULL)
	{
		parent::__construct($sql);
		$this->expire = $expire;
		$this->tags = $tags;
	}
	
	public function getCache()
	{
		if($this->cache === NULL)  {
			$this->cache = Environment::getCache('SQLCachedSource');
		}
		return $this->cache;
	}
	
	/**
	* put your comment there...
	* 
	* @param Cache $cache
	*/
	public function setCache($cache)
	{
		$this->cache = $cache;
	}
		
	/******** Interface IDataSource **********/
	
	
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

		$cache = $this->getCache();
    	if (isset($cache[md5($sql)])) {    		
		    list($this->data, $this->foundRows) = $cache[md5($sql)];
		}
		else {
		    // provedeni
			$dbRes = $this->db->query($sql);		
			$this->data = new DataTable($dbRes->as_array());
			if($this->sqlCalcFoundRows) {
				$this->foundRows = $dbRes->found_rows();
			}		
			// dotazeni vazeb
			foreach($this->bind as $bind) $this->processBind($bind);		
			foreach($this->bindSimple as $bind) $this->processBindSimple($bind);		
			$this->data = $this->applyCallbacks($this->data);
		    $cache->save(md5($sql), array($this->data, $this->foundRows), 
		    	array(
		    		 'expire' => $this->expire,
		    		 'tags' => $this->tags,
		    ));
		}		
		$this->loaded = TRUE;
		return $this;
	}
}
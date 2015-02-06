<?php
/**
* ORM Source poskytuje pristup k ORM objektum. 
* Pro pouziti v DataListech vraci seznam ORM objektu ORM_List
*  
* 
* @package DataSource
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version $0.1$
*/


class ORMSource extends SQLSource
{	
	
	/**
	* ORM zdroj
	* 
	* @var object
	*/
	protected $source;
	
		
	/**
	 * Konstruktor
	 *
	 * @param 
	 */
	public function __construct($orm)
	{
		$this->source = $orm;
	}	  
	
	
	/******** Interface IDataSource **********/
	
	
	/**
	 * Nacte data k zobrazeni
	 * Parametr obsahuje nepovinne indexy:
	 * - where - filtrovaci formular
	 * - page - pozadovana stranka
	 * - limit - pocet zaznamu na stranku
	 * - order->column - instance Column - sloupec, podle ktere se bude radit
	 * - order->direction - emun('a','d') - smer razeni
	 *
	 * @param array $params
	 */
	public function loadData($params = array())
	{
		if($params != NULL) $this->loadParams($params);
		$orm = clone $this->source;
		if(isset($this->filter)) $orm->where($this->where());
		if(isset($this->page) && isset($this->limit)) $orm->limit($this->limit, ($this->page-1)*$this->limit);
		if(isset($this->order) && isset($this->order->column)) {
			$member = $this->order->column->member;
			$member = str_replace('->', ':', $member);
			$orm->orderby($member , $this->order->direction == 'a' ? 'ASC' : 'DESC');
		}
		$this->data = $orm->find_all();		   
		$this->foundRows = $this->data->getAllRows();			
		$this->data = new DataTable($this->data);	 
		
		foreach($this->bind as $bind) $this->processBind($bind);
		$this->loaded = true;
		return $this; 
	}
}
<?php
/**
* Nevizualni DataGrid. Neco mezi DataGridem a DataSource.
* Metoda getData() vrátí strukturu složenou s výsledkem dotazu s informacemi o počtu záznamů a počru stránek.
* Hodí se pro "vzdálené" datagridy, které k datům přistupují přes webovou službu.
* 
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
*/

class PureGrid extends Control {
	
	public $page = 1;	 

	public $order = NULL;	 
  
	public $limit = 25;   
	
	private $source;
	
	/**
	* Seznam radku pro zobrazeni
	* 
	* @var array
	*/
	protected $rows;
	
	/**
	* Celkovy pocet radku bez ohledu na strankovani
	* 
	* @var int
	*/
	protected $allRows;
	
	/**
	* Pripojeny filtr
	* 
	* @var Filter
	*/
	protected $filter;
	
	/**
	* Strankovadlo
	* 
	* @var Paginator
	*/
	protected $paginator;
	
	/**
	* Nactene data?
	* 
	* @var bool
	*/
	protected $loaded = FALSE;
	
	 /**
	* Vychozi razeni
	* 
	* @var mixed
	*/
	public $defaultOrder;
	
	
	public $columns = array();	
	
	public $onLoadData = array();  
	
	
	 /** Settery a gettery **/
	
	public function setLimit($value)
	{
		$this->limit = $value;
		return $this;
	}

	protected function getLimit()
	{
		return $this->limit;
	}
	
	public function setPage($value)
	{
		$this->page = $value;
		return $this;
	}

	protected function getPage()
	{
		return $this->page;
	}
	
	public function setFilter($value)
	{
		$this->filter = $value;
		return $this;
	}
	
	public function getFilter()
	{
		return $this->filter;
	}
	
	public function setOrder($value)
	{
		$this->order = $value;
		return $this;
	}
	
	protected function getOrder()
	{
		return $this->order;		
	}
	
	public function getSource()
	{
		return $this->source;
	}
	
	/**
	 * Navazani datove zdroje
	 *
	 * @param DataTable $source
	 */
	public function bindSource($source)
	{
		// konverze na typ DataTable
		if(!($source instanceof IDataSource)) {
			$this->source = new DataTable($source);			   
		}
		else $this->source = $source;					 
		if($source instanceof SQLSource) {
			$source->sqlCalcFoundRows = TRUE;
		}
		return $this;
	}
	
	public function getCount()
	{
		if(!$this->loaded) $this->loadData(); 
		return count($this->rows);
	}
	
	/**
	* Nacteni dat do $rows z datoveho zdroje.
	*/
	protected function loadData()
	{		 
		$this->source->loadData(array(
				'where' => $this->getFilter(), 
				'order' => $this->getOrder(), 
				'page' => $this->getPage(), 
				'limit' => $this->getLimit())
		);
		$this->rows = $this->source->getItems();
		// vybrana strana je vetsi nez jich ve skutecnosti je => novy dotaz na prvni stranu
		if(count($this->rows) == 0 && $this->source->getAllRows() != 0) {
			$this->page = 1;			
			$this->source->loadData(array(
					'where' => $this->getFilter(), 
					'order' => $this->getOrder(), 
					'page' => 1, 
					'limit' => $this->getLimit())
			);
			$this->rows = $this->source->getItems();
		}
		$this->loaded = TRUE;
		$this->allRows = $this->source->getAllRows();
		// Dispach Event
		$this->onLoadData($this);		 
		
	}
	
	public function getData()
	{
		if(!$this->loaded) $this->loadData();		 
		$items = array();
		if(empty($this->columns)) $items = $this->rows;
		else {
			foreach($this->rows as $row) {
				$r = new stdClass();
				foreach($this->columns as $column) {
					$r->$column = $row->$column;
				}
				$items[] = $r;
			}
		}
		$data = new PureGridData();
		$data->items = $items;
		$data->count = $this->allRows;
		$data->page = $this->page;
		if($this->limit != NULL) $data->pages = ceil($this->allRows / $this->limit); 
		return $data;
	}
}

class PureGridData extends Object
{
	public $items;
	public $count;
	public $page;
	public $pages = 1;
}
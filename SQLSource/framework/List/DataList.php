<?php
/**
* Komponenty pro zobrazení dat odvozené od abstrakní třídy DataList, pracují tak,
* že komunikují s DataSource a vyžadují si od něj data na základě stránkování, řazení a filtru.
* Pro zobrazení těchto dat použijí šablonu, která se pro každou komponentu liší.
*
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
*/

abstract class DataList extends ControlGettext
{
	/** @persistent int */
	public $page = 1;

	/** @persistent */
	public $order = NULL;

	/** @persistent int */
	public $limit = NULL;

	/**
	* Priznak jestli je signal pro tuto komponentu.
	*
	* @var bool
	*/
	private $signalReceived;

	/**
	 * @var DataTable
	 * */
	private $source;

	/**
	* @var ColumnModel
	*/
	protected $columnModel = array();

	/**
	* Nactene data?
	*
	* @var bool
	*/
	protected $loaded = FALSE;

	/**
	* Senzma radku pro zobrazeni
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
	* Radkove callbacky
	*
	* @var mixed
	*/
	protected $rowCallback = array();

	/**
	* Je jiz strankovadlo vykresleno?
	*
	* @var bool
	*/
	protected $paginatorRendered = 0;

	/**
	* Vychozi razeni
	*
	* @var mixed
	*/
	public $defaultOrder;

	/**
	* Adresar ke vsem sablonam komponenty
	*
	* @var mixed
	*/
	protected $templateDir;

	/**
	* Cesty k subsablonam
	*
	* @var array
	*/
	protected $templates = array();

	/**
	* Povoleni inicializace pole $items. Tzn. aplikuji se callbacky jeste pred vykreslenim.
	*
	* @var mixed
	*/
	protected $itemsEnabled = FALSE;

	/**
	* Pole radku zpracovane i s callbacky
	*
	* @var mixed
	*/
	protected $items = NULL;

	/**
	* Ajaxove strankovani, razeni a filtrovani
	*
	* @var bool
	*/
	protected $useAjax = FALSE;

	/**
	* Ulozeni stavu strankovani, razeni a filtru do session
	*
	* @var bool
	*/
	protected $saveState = FALSE;

	/**
	* Seznam ovladavich controlu
	*
	* @var array
	*/
	protected $controls = array();

	 /** events **/
	public $onFilter = array();
	public $onLimit = array();
	public $onPage = array();
	public $onOrder = array();
	public $onLoadData = array();

	//protected $limitForm;
	public $limitSelect = TRUE;
	//public $limitLabel;

	protected $limitControl;

	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);
		$this->getComponent('limit')->exec();
	}

	public function createComponent($name)
	{
		switch ($name) {
			case 'limit':
				$this->limitControl = new LimitControlInput($this, 'limit');
				break;
		}
		return $this;
	}

	/** Settery a gettery **/
	public function setLimit($value)
	{
		if(!$this->getComponent('limit')->isSubmitted()) {
			$lc = $this->getComponent('limit');
			if($value != $lc->getDefaultValue()) $this->limit = $value;
			//if($value === NULL) $value = $lc->getDefaultValue();
			$lc->setValue($value);
		}
		return $this;
	}

	public function setDefaultLimit($value)
	{
		$lc = $this->getComponent('limit');
		$lc->setDefaultValue($value);
		return $this;
	}

	public function getLimitControl()
	{
		return $this->limitControl;
	}

	protected function getLimit()
	{
		return $this->limitControl->getValue();
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

	public function setUseAjax($value)
	{
		$this->useAjax = $value;
		return $this;
	}

	public function getUseAjax()
	{
		return $this->useAjax;
	}

	public function setSaveState($value)
	{
		$this->saveState = $value;
		return $this;
	}

	public function getSaveState()
	{
		return $this->saveState;
	}

	public function setFilter($value)
	{
		$this->filter = $value;
		if($value === NULL) return;
		$this->filter->onSubmitted = array(array($this, 'handleFilter'));
		if($value instanceof Filter) {
			$this->filter->setParentList($this);
		}
		return $this;
	}


	public function getFilter()
	{
		return $this->filter;
	}

	protected function getOrder()
	{
		return $this->order;
	}

	public function setPaginator($value)
	{
		$this->paginator = $value;
		return $this;
	}

	public function setColumnModel($model)
	{
		$this->columnModel = $model;
		$this->columnModel->setParent($this);
		foreach ($this->columnModel as $column) $column->setDataList($this);
		return $this;
	}

	public function getColumnModel()
	{
		return $this->columnModel;
	}

	public function getPaginator()
	{
		if($this->paginator == NULL) $this->paginator = new AdminPaginator();
		return $this->paginator;
	}

	public function setTemplateBody($value)
	{
		$this->templates['body'] = $value;
		return $this;
	}

	public function setTemplatePaginator($value)
	{
		$this->templates['paginator'] = $value;
		return $this;
	}

	public function setTemplateLimit($value)
	{
		$this->templates['paginator'] = $value;
		return $this;
	}

	public function getTemplatesDir()
	{
		return $this->templateDir;
	}

	public function setTemplatesDir($value)
	{
		$this->templateDir = $value;
		return $this;
	}

	public function setTemplate($value)
	{
		$this->templates['control'] = $value;
		return $this;
	}

	public function disableLimitSelect()
	{
		$this->limitSelect = FALSE;
		return $this;
	}


	/**
	 * Je zpetne volani pri signalu?
	 * Napr. odstrankovani.
	 *
	 * @return bool
	 */
	public function isSignalReceived()
	{
		if($this->signalReceived == NULL) {
			$this->signalReceived = $this->getPresenter()->isSignalReceiver($this);
		}
		return $this->signalReceived;
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

	public function getSource()
	{
		return $this->source;
	}

	/**
	* Nalezeni sloupce podle jmena
	*
	* @param string $name
	* @return Column
	*/
	public function getColumn($name)
	{
		return $this->getColumnModel()->getColumn($name);
	}

	/**
	* Vrati stav razeni. Bud z persistetni promenne nebo z vychoziho stavu
	* @return object
	*/
	public function getOrderInfo()
	{
		if($this->getOrder() != NULL) {
			parse_str($this->getOrder(), $list);
			$column = $this->getColumn(key($list));
			if($column != NULL) $res = array('column' => $column, 'direction' => current($list));
			else return NULL;
		}
		else {
			if(isset($this->defaultOrder)) {
				if(!is_array($this->defaultOrder)) {
					$this->defaultOrder = array($this->defaultOrder => 'd');
				}
				$s = key($this->defaultOrder);
				$column = $this->getColumn($s);
				if($column == NULL && $this->getColumn($s) == NULL) {
					return NULL;
				}
				$res = array('column' => $column != NULL ? $column : $s, 'direction' => current($this->defaultOrder));
			}
			else return NULL;
		}
		return (object) $res;
	}


	/**
	* Pridani radkoveho callbacku. Provadi se nad celym radkem.
	* Napr pro barevne zvyrazeni radku podle stavu.
	*
	* @param mixed $function
	* @param array $args
	* @return DataList
	*/
	public function addRowCallback($function, $args = null) {
		if (func_num_args() > 2) {
			$argv = func_get_args();
			$args = array_merge(array($args), array_slice($argv, 2));
		}
		$f = array();
		$f['type'] = 'function';
		$f['func'] = $function;
		$f['params'] = is_array($args) ? $args : array($args);
		$this->rowCallback[] = $f;
		return $this;
	}

	/**
	* Nacteni dat do $rows z datoveho zdroje.
	*/
	protected function loadData()
	{
		$this->source->loadData(array(
				'where' => $this->getFilter(),
				'order' => $this->getOrderInfo(),
				'page' => $this->getPage(),
				'limit' => $this->getLimit())
		);
		$this->rows = $this->source->getItems();
		// vybrana strana je vetsi nez jich ve skutecnosti je => novy dotaz na prvni stranu
		if(count($this->rows) == 0 && $this->source->getAllRows() != 0) {
			$this->page = 1;
			$this->source->loadData(array(
					'where' => $this->getFilter(),
					'order' => $this->getOrderInfo(),
					'page' => 1,
					'limit' => $this->getLimit())
			);
			$this->rows = $this->source->getItems();
		}
		$this->loaded = TRUE;
		$this->allRows = $this->source->getAllRows();
		// Dispach Event
		$this->onLoadData($this);

		$cm = $this->getColumnModel();
		// experimental - vytvoreni defaultniho column modelu pokud nema zadany svuj
		if(empty($cm) && isset($this->rows[0])) {
			 $cm = new ColumnModel();
			 foreach($this->rows[0] as $key => $column) {
				 $col = new Column($key, $key, $key);
				 $cm->add($key, $col);
			 }
			 $this->setColumnModel($cm);
		}

		if($this->itemsEnabled) {
			// aplikuji se callbacky jeste pred vykreslenim a v sablone jsou k dispozici v $items
			$this->items = array();
			foreach($this->rows as $row) {
				$item = new stdClass();
				foreach ($cm as $column) {
					$column->setRow($row);
					$item->{$column->name} = $column->render();
				}
				$this->items[] = $item;
			}
		}

	}

	/**
	* Vrati metainformace o zpracovavanem radku.
	* Vysledek je asociaticni pole, ktere se v sablone zpracuje do atributu <tr>
	*
	* @param mixed $item
	* @return array
	*/
	public function getRowMeta($item)
	{
		$res = array();
		foreach ($this->rowCallback as $callback) {
				if(is_callable($callback['func'])) {
					if($callback['params']) $args = array_merge(array(clone $item), $callback['params']);
					else $args = array(clone $item);
					$value = call_user_func_array($callback['func'], $args);
					if($value != NULL) $res = array_merge($res, $value);
				}
				else {
					throw new Exception('Invalid callback '.$callback['func'].'()');
				}
		}
		return $res;
	}

	/**
	* Vrati celkovy pocet radku bez ohledu na strankovani
	* @return int
	*/
	public function getCount()
	{
		if(!$this->loaded) $this->loadData();
		return $this->allRows;
	}


	/**
	* Vrati sablony pro prvky
	*
	*/
	protected function getTemplates()
	{
		$out = array();
		$out['complete'] = !isset($this->templates['control'])	?
								 $this->getTemplatesDir() . '/control.phtml' :
								 $this->templates['control'];
		$out['body'] = !isset($this->templates['body'])  ?
								 $this->getTemplatesDir() . '/body.phtml' :
								 $this->templates['body'];
		$out['paginator'] = !isset($this->templates['paginator'])  ?
								 $this->getTemplatesDir() . '/paginator.phtml' :
								 $this->templates['paginator'];
		$out['limit'] = !isset($this->templates['limit'])  ?
								 $this->getTemplatesDir() . '/limit.phtml' :
								 $this->templates['limit'];
		return (object) $out;
	}

	/**
	* Vrati filtrovaci formular z pripojeneho filtru
	*
	*/
	public function getFilterForm()
	{
		if($f = $this->getFilter()) {
			return $f->getForm();
		}
	}



	/**** Signaly ***/


	/**
	 * Zmena stranky
	 */
	public function handlePage($page)
	{
		$this->onPage($this);
		$this->invalidateControl();
		return $this;
	}

	public function handleRefresh()
	{
	   $this->invalidateControl();
		return $this;
	}

	/**
	 * Zmena limitu
	 */
	public function handleLimit($limit)
	{
		$this->limit = $limit;
		$this->page = 1;
		$this->invalidateControl();
		$this->onLimit($this);
		return $this;
	}

	/**
	 * Zmena filtru
	 */
	public function handleFilter()
	{
		$this->page = 1;
		$this->invalidateControl();
		$this->onFilter($this);
		return $this;
	}

	/**
	* Zmena razeni
	*
	* @param string $orderBy - nazev spoulce pro zarezi
	* @param char $dir - smer razeni [a|d], pokud neni uveden dojde ke zmene prechoziho smeru
	*/
	public function handleOrder($orderBy, $dir = NULL)
	{
		if($dir == NULL) {
			parse_str($this->order, $list);
			$nlist = array();
			if (!isset($list[$orderBy]) || $list[$orderBy] === 'd') {
				$nlist[$orderBy] = 'a';
			} else {
				$nlist[$orderBy] = 'd';
			}
		}
		else $nlist[$orderBy] = $dir;
		$this->order = http_build_query($nlist, '', '&');
		$this->onOrder($this);
		$this->invalidateControl();
		return $this;
	}

	/**
	 * Save params
	 * @param  array
	 * @return void
	 */
	public function saveState(array & $params)
	{
		/// session stav
		if($this->saveState) {
			$session = Environment::getSession($this->getPresenter()->getAction(true).$this->getUniqueId());
			$session->order = $this->order;
			$session->page = $this->page;
			$session->limit = $this->getLimit();
		}
		parent::saveState($params);
		return $this;
	}


	/**
	 * Loads params
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		if($this->saveState && !$this->isSignalReceived()) {
			$session = Environment::getSession($this->getPresenter()->getAction(TRUE).$this->getUniqueId());
			if(isset($session->order)) $this->order = $session->order;
			if(isset($session->page)) $this->page = $session->page;
			if(isset($session->limit)) $this->setLimit($session->limit);
			else $this->setLimit($this->limit);
		}
		else $this->setLimit($this->limit);
		return $this;
	}

	/**
	* Vypise HTML input pro vyber vsech zaznamu
	*
	*/
	public function selectAllButton()
	{
		return '<input type="checkbox" onclick="'.$this->getName().'_selectAll(this.checked)"/>';
	}

	/**
	* Js funkce pro hromadnou akci s presmerovani vybranych radku do URL
	*
	* @param string $link
	*/
	public function jsMultiLink($link)
	{
		return 'MultiLink(\''.$link.'\', \''.$this->getName().'\')';
	}

	public function addControl($control)
	{
		$control->setDataList($this);
		$this->controls[] = $control;
		return $this;
	}

	/**
	* Vrati sloupce, ktere se zobrazi v sablone
	*
	*/
	protected function getVisibleColumns()
	{
		$cm = $this->getColumnModel();
		if(!empty($cm)) return $cm->getVisibleColumns();
		else return array();
	}

	/**
	 * Renders table grid
	 */
	public function renderGrid()
	{
		if(!$this->loaded) $this->loadData();
		$template = $this->createTemplate();
		$template->filter = $this->filter;
		$template->useAjax = $this->useAjax;
		$template->rows = $this->rows;
		$template->name = $this->getName();
		$template->columns = $this->getVisibleColumns();
		$template->controls = $this->controls;
		$order = $this->getOrderInfo();
		$c = isset($order->column->name) ? $order->column->name : NULL;
		$template->order = isset($order) ? array($c => $order->direction) : '';
		$template->setFile($this->getTemplates()->body);
		$template->registerFilter(/*Nette\Templates\*/'CurlyBracketsFilter::invoke');
		$template->render();
	}

	/**
	 * Renders paginator
	 */
	public function renderPaginator()
	{
		if(!$this->loaded) $this->loadData();
		$paginator = $this->getPaginator();
		$paginator->itemsPerPage = $this->getLimit() == NULL ? $this->allRows : $this->getLimit();
		$paginator->itemCount = $this->allRows;
		$paginator->page = $this->getPage();

		$template = $this->createTemplate();
		$template->useAjax = $this->useAjax;
		$template->paginator = $paginator;
		$template->setFile($this->getTemplates()->paginator);
		$template->registerFilter(/*Nette\Templates\*/'CurlyBracketsFilter::invoke');
		$template->render();
	}

	public function exec()
	{
		if(!$this->loaded) $this->loadData();
		$paginator = $this->getPaginator();
		$paginator->itemsPerPage = $this->getLimit() == NULL ? $this->allRows : $this->getLimit();
		$paginator->itemCount = $this->allRows;
		$paginator->page = $this->getPage();
	}
	/**
	 * Renders paginator
	 */
	public function renderLimit()
	{
		if(!$this->loaded) $this->loadData();

		$this->limitControl->setUseAjax($this->useAjax);
		$this->limitControl->render();
	}

	/**
	 * Renders filter
	 */
	public function renderFilter($file = null)
	{
		if($f = $this->getFilter()) {
			$f->useAjax($this->useAjax);
			$f->render();
		}
	}

	 /**
	 * Renders complete component
	 */
	public function render()
	{
		$this->getPresenter()->payload->eval[] = $this->name.'_refreshUrl = \''.$this->link('refresh').'\'';

		if(!$this->loaded) $this->loadData();
		$template = $this->createTemplate();
		$template->useAjax = $this->useAjax;
		$template->setFile($this->getTemplates()->complete);
		$template->registerFilter(/*Nette\Templates\*/'CurlyBracketsFilter::invoke');
		$template->render();
	}
}
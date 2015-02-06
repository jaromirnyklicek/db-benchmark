<?php


/**
 *  Datagrid, ktery se vypise pres jQuery FlexiGrid.
 *  Pro spravnou funknost musi byt do stranky includovany flexigrid.js a flexigrid.css
 */
class FlexiGrid extends PureGrid
{
	/**
	 * Zaznamu na stranku
	 *
	 * @var mixed
	 */
	public $limit = 25;

	/**
	 * Sloupec, ktery je primarni klic pro tabulku.
	 *
	 * @var string
	 */
	protected $primaryKey;

	/**
	 * @var ColumnModel
	 */
	protected $columnModel = array();

	/**
	 * Seznam ovladavich controlu
	 *
	 * @var array
	 */
	protected $controls = array();

	/**
	 * Nadpis datagridu
	 *
	 * @var string
	 */
	protected $title;

	/** events * */
	public $onJsonLoad = array();
	public $onGetRowLoad = array();

	/**/
	public $options = array();

	/** Vyska FlexiGridu
	 *
	 * @var int
	 */
	public $height = 400;

	/**
	 * Sablona pro cely render()
	 *
	 * @var mixed
	 */
	protected $template;

	/**
	 * Rozsireny filtr
	 *
	 * @var bool
	 */
	public $extFilter = TRUE;

	/**/
	protected $settedDefaults = FALSE;

	/**
	 * Radkove callbacky
	 *
	 * @var mixed
	 */
	protected $rowCallback = array();


	/**
	 * Nastaveni sloupce, ktery je primarni klic zobrazovane tabulky
	 *
	 * @param string $column 	Nazev sloupce
	 */
	public function setPrimaryKey($column)
	{
		$this->primaryKey = $column;
		return $this;
	}

	/**
	 * Priznak jestli je signal pro tuto komponentu.
	 *
	 * @var bool
	 */
	private $signalReceived;


	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}


	public function setTitle($value)
	{
		$this->title = $value;
		return $this;
	}


	public function getTitle()
	{
		return $this->title;
	}


	public function setColumnModel($model)
	{
		$this->columnModel = $model;
		$this->columnModel->setParent($this);
		foreach ($this->columnModel as $column) {
			$column->setDataList($this);
		}
	}


	public function getColumnModel()
	{
		return $this->columnModel;
	}


	public function getControls()
	{
		return $this->controls;
	}


	/**
	 * Ziskani dat z datoveho zdroje
	 *
	 */
	public function getData()
	{
		$data = parent::getData();
		$items = array();
		$cm = $this->getColumnModel();
		$i = 0;
		foreach ($data->items as $row) {
			$item = new stdClass();
			$item->__META = $this->getRowMeta($row);
			if ($this->getPrimaryKey() != NULL) {
				$item->__ID = $row->{$this->getPrimaryKey()};
			} else {
				$item->__ID = $i + ($this->getPage() - 1) * $this->limit;
			}
			foreach ($cm as $column) {
				$column->setRow($row);
				$item->{$column->name} = (string) $column->render();
			}
			$items[] = $item;
			$i++;
		}
		$data->items = $items;
		return $data;
	}


	/**
	 * JSON vysledek pro AJAX
	 *
	 */
	public function getJSON()
	{
		if ($this->getSource() instanceof SQLSource) {
			$this->getSource()->sqlCalcFoundRows = TRUE;
		}
		$data = $this->getData();
		$result = new stdClass();
		$result->total = $data->count;
		$result->page = $data->page;
		$result->rows = array();
		$cmodel = $this->getColumnModel();
		foreach ($data->items as $item) {
			$row = new stdClass();
			$row->id = $item->__ID;
			$row->meta = $item->__META;
			$row->cell = array();
			foreach ($cmodel as $column) {
				$row->cell[] = $item->{$column->name};
			}
			$result->rows[] = $row;
		}
		$result->data = new stdClass();

		// sumacni radek
		$cm = $this->getColumnModel();
		$a = array();
		//$sum = FALSE;
		foreach ($cm as $column) {
			//if($column->getOption('sum') != NULL) $sum = TRUE;
			$a[] = $column->getOption('sum');
		}
		//if($sum)
		$result->footer = $a;

		$this->onJsonLoad($result, $this);

		return $result;
	}


	/**
	 * Ma sumacni radek?
	 *
	 */
	public function hasSummary()
	{
		foreach ($this->getColumnModel() as $column) {
			if ($column->getOption('sum') !== NULL) {
				return TRUE;
			}
		}
		return FALSE;
	}


	public function getControlTemplate()
	{
		if ($this->template == NULL) {
			$this->template = dirname(__FILE__) . '/../Templates/FlexiGrid/control.phtml';
		}
		return $this->template;
	}


	/**
	 * Nalezeni sloupce podle jmena
	 *
	 * @param string $name
	 * @return Column
	 */
	public function getColumn($name)
	{
		foreach ($this->getColumnModel() as $column) {
			if ($column->name == $name) {
				return $column;
			}
		}
	}


	/**
	 * Vrati stav razeni. Bud z persistetni promenne nebo z vychoziho stavu
	 * @return object
	 */
	public function getOrder()
	{
		if ($this->order != NULL) {
			parse_str($this->order, $list);
			$column = $this->getColumn(key($list));
			if ($column != NULL) {
				$res = array('column' => $column, 'direction' => current($list));
			} else {
				return NULL;
			}
		} else {
			if (isset($this->defaultOrder)) {
				if (!is_array($this->defaultOrder)) {
					$this->defaultOrder = array($this->defaultOrder => 'd');
				}
				$s = key($this->defaultOrder);
				$column = $this->getColumn($s);
				$res = array('column' => $column != NULL ? $column : $s, 'direction' => current($this->defaultOrder));
			} else {
				return NULL;
			}
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
	public function addRowCallback($function, $args = null)
	{
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
	 * Vrati metainformace o zpracovavanem radku.
	 * Vysledek je asociaticni pole, ktere se do JSON posle jako `meta`
	 *
	 * @param mixed $item
	 * @return array
	 */
	public function getRowMeta($item)
	{
		$res = array();
		foreach ($this->rowCallback as $callback) {
			if (is_callable($callback['func'])) {
				if ($callback['params']) {
					$args = array_merge(array(clone $item), $callback['params']);
				} else {
					$args = array(clone $item);
				}
				$value = call_user_func_array($callback['func'], $args);
				if ($value != NULL) {
					$res = array_merge($res, $value);
				}
			} else {
				throw new Exception('Invalid callback ' . $callback['func'] . '()');
			}
		}
		return $res;
	}


	protected function loadHttpData()
	{
		$post = Environment::getHttpRequest()->getPost();
		if (isset($post['page'])) {
			$this->setPage($post['page']);
		}
		if (isset($post['rp'])) {
			$this->setLimit($post['rp']);
		}
		if (isset($post['sortname'])) {
			$order = $post['sortname'] . '=' . ($post['sortorder'] == 'asc' ? 'a' : 'd');
			$this->setOrder($order);
		}
		if ($this->getFilter()) {
			$this->getFilter()->saveFilterState();
		}
	}


	/**
	 * Signal pro AJAXove nacteni dat
	 *
	 */
	public function handleGetData()
	{
		$this->loadHttpData();
		echo json_encode($this->getJSON());
		$this->getPresenter()->terminate();
	}


	public function addControl($control)
	{
		$control->setDataList($this);
		$this->controls[] = $control;
		return $this;
	}


	/**
	 * Vypise HTML input pro vyber vsech zaznamu
	 *
	 */
	public function selectAllButton()
	{
		return '<input type="checkbox" onclick="' . $this->getName() . '_selectAll(this.checked)"/>';
	}


	/**
	 * Js funkce pro hromadnou akci s presmerovani vybranych radku do URL
	 *
	 * @param string $link
	 */
	public function jsMultiLink($link)
	{
		return 'MultiLink(\'' . $link . '\', \'' . $this->getName() . '\')';
	}


	/**
	 * Save params
	 * @param  array
	 * @return void
	 */
	public function saveState(array & $params)
	{
		$session = Environment::getSession($this->getStateSessionIdentifier());
		$session->order = $this->order;
		$session->page = $this->page;
		$session->limit = $this->getLimit();
		parent::saveState($params);
	}


	/**
	 * Loads params
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		if (!$this->isSignalReceived()) {
			$session = Environment::getSession($this->getStateSessionIdentifier());
			if (isset($session->order)) {
				$this->order = $session->order;
			}
			if (isset($session->page)) {
				$this->page = $session->page;
			}
			if (isset($session->limit)) {
				$this->setLimit($session->limit);
			} else {
				$this->setLimit($this->limit);
			}
		} else {
			$this->setLimit($this->limit);
		}
	}


	/**
	 * Vrati identifikator pro session k ukladani stavu.
	 *
	 * @return string
	 */
	protected function getStateSessionIdentifier()
	{
		return $this->getPresenter()->getAction(true) . $this->getUniqueId();
	}


	/**
	 * Je zpetne volani pri signalu?
	 * Napr. odstrankovani.
	 *
	 * @return bool
	 */
	public function isSignalReceived()
	{
		if ($this->signalReceived == NULL) {
			$this->signalReceived = $this->getPresenter()->isSignalReceiver($this);
		}
		return $this->signalReceived;
	}


	public function setDefaultRenderer()
	{
		if ($this->settedDefaults) {
			$this->getFilter()->setRenderer(new FilterFlexiGridRenderer($this->getFilter()->columns, $this));
			$f = $this->getFilter();
			if ($f) {
				$f->useAjax(FALSE);
			}
			$this->getFilter()->beforeRender();
		}
		$this->settedDefaults = TRUE;
	}


	/**
	 * Renders complete component
	 */
	public function render()
	{
		$this->getPresenter()->payload->eval[] = $this->name . '_refreshUrl = \'' . $this->link('refresh') . '\'';

		if (!$this->loaded) {
			$this->loadData();
		}

		$template = $this->createTemplate();
		$template->setFile($this->getControlTemplate());
		$template->registerFilter(new LatteFilter());

		$this->setDefaultRenderer();

		$template->filter = $this->getFilter()->getForm();

		$template->render();
	}


	public function handleRefresh()
	{
		$this->invalidateControl();
	}


	/**
	 * Renders filter
	 */
	public function renderFilter()
	{
		$this->setDefaultRenderer();
		$this->getFilter()->setRenderer(new FilterFlexiGridRenderer($this->getFilter()->columns, $this));
		$f = $this->getFilter();
		if ($f) {
			$f->useAjax(FALSE);
			$f->render();
		}
	}


	/**
	 * Zpracovani signalu pro update jednoho zaznamu
	 *
	 */
	public function handleGetRow()
	{
		$id = $this->presenter->getParam('rowid');
		$row = $this->getRow($id);
		if ($row) {
			echo json_encode((object) array('id' => $id, 'data' => $row));
			$this->getPresenter()->terminate();
		}
	}


	/**
	 * Nacte z databaze zaznam podle ID
	 *
	 * @param int $id
	 * @return object
	 */
	public function getRow($id)
	{
		$idCol = $this->getPrimaryKey();
		$cm = $this->getColumnModel();
		$idSql = $cm[$idCol]->getOption('sql');
		if (empty($idSql)) {
			$idSql = $cm[$idCol]->getName();
		}

		$this->source->loadData(array('where' => $idSql . ' = ' . $id));
		$rows = $this->source->getItems();
		if (!isset($rows[0])) {
			return;
		}
		$row = $rows[0];

		$item = array();
		foreach ($cm as $column) {
			$column->setRow($row);
			$item[] = (string) $column->render();
		}
		if (!empty($this->onGetRowLoad)) {
			foreach ($this->onGetRowLoad as $callback) {
				call_user_func_array($callback, array($row, &$item, $this));
			}
		}
		return $item;
	}


	protected function getFxColumnModel()
	{
		$cm = array();
		$cmodel = $this->getColumnModel();
		foreach ($cmodel as $column) {
			if (empty($column->width)) {
				$column->width = 60;
			}
			if (preg_match('#text-align.*?:.*?(right|left|center);?#i', $column->style, $m)) {
				$align = $m[1];
			} else {
				$align = 'left';
			}
			$obj = new stdClass();
			$obj->display = $column->title;
			$obj->name = $column->name;
			$obj->width = $column->width;
			$obj->sortable = (bool) $column->sortable;
			$obj->align = $align;
			$obj->hide = !$column->visible;
			$cm[] = $obj;
		}
		return $cm;
	}


	public function renderGrid()
	{
		$pageurl = $this->getPresenter()->link('this');
		$url = $this->link('getData');
		if ($this->getFilter()) {
			$form = $this->getFilter()->form;
			$fname = $form->getName();
		}

		$options = new stdClass();
		foreach ($this->options as $key => $option) {
			$options->$key = $option;
		}
		$options->pageurl = $pageurl;
		$options->url = $url;
		$options->urlGetRow = $this->link('getRow');
		$options->dataType = "json";
		$options->colModel = $this->getFxColumnModel();
		$o = $this->getOrder();
		if ($o != NULL) {
			$options->sortname = $o->column->getName();
			$options->sortorder = ($o->direction == 'a' ? 'asc' : 'desc');
		}
		$options->usepager = true;
		$options->singleSelect = true;
		$options->useRp = true;
		$options->autoload = false;
		$options->rp = $this->limit;
		$options->footer = ((int) $this->hasSummary());
		$options->height = $this->height;
		if (!empty($this->controls)) {
			$options->actions = array();
			foreach ($this->controls as $control) {
				$options->actions[] = (string) $control;
			}
		}
		if ($this->getFilter() && $this->extFilter) {
			$options->extfilter = 'e_' . $this->getFilter()->getName();
		}

		$tableId = 'fxdg_' . $this->getName();
		$js = '<script type="text/javascript">
		//<![CDATA
	   $(document).ready(function () {

		   var options = ' . json_encode($options) . ';
		   ' . (isset($fname) ? 'options[\'onSubmit\'] = ' . $fname . '_addFormData;' : '') . '
		   $("#' . $tableId . '").flexigrid(options);

		   $("#fxdg_' . $this->getName() . '").flexAddData(' . @json_encode($this->getJSON()) . ');

		});
		' . (isset($fname) ? '
		function ' . $fname . '_addFormData()
		{
			//passing a form object to serializeArray will get the valid data from all the objects, but, if the you pass a non-form object, you have to specify the input elements that the data will come from
			var dt = $(\'#' . $fname . '\').serializeArray();
			$("#' . $tableId . '").flexOptions({params: dt});
			return true;
		}' : '') . '

		' . $this->getName() . '_selectAll = function (value) {
			jQuery.each($(\'.' . $this->getName() . '_select_chb\'), function() {
				this.checked = value;
			});
		}
		' . $this->getName() . '_selectedId = function() {
			s = new Array();
			arr = $(\'.' . $this->getName() . '_select_chb\');
			for(j=0, i=0; i < arr.length; i++) if(arr[i].checked) s[j++] = arr[i].value;
			return s;
		}

		function ' . $this->getName() . '_getData()   {
			p = $("#fxdg_' . $this->getName() . '").flexParams();
			return p.data;
	   }
	   //]]>
	   </script>';
		$table = '<table id="' . $tableId . '" style=""></table>';
		if (SnippetHelper::$outputAllowed) {
			echo $js . $table;
		}
	}

}

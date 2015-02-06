<?php


/**
 * Ke každé komponentě pro zobrazení dat lze připojit uživatelský filtr.
 * Filtr je v podstatě obálka na formulářem, interně obsahující speciální tlačítka pro filtr.
 * Do filtru lze přídávat jakékoliv FormControly. Pokud filtr slouží pro SQL dotazy (což je
 * jeho nejčastější funkce), musí FormControly implementovat metodu sqlWhere(), která vrátí
 * konstrukci do SQL klausule WHERE.
 *
 *
 * @author Ondrej Novak
 * @copyright Copyright (c) 2009, Ondrej Novak
 */
class Filter extends ControlGettext implements IDataSourceFilter, ArrayAccess
{
	/** @persistent */
	public $filter = NULL;

	/** Uložení filtru do session * */
	public $saveState = TRUE;

	/* Ajaxove odesilani * */
	public $useAjax = FALSE;

	/** Formular filtru * */
	public $form;

	/** nadpis filtru * */
	protected $title;

	/** zobrazit tlacitka * */
	protected $showButtons = TRUE;

	/**/
	private $signalReceived = FALSE;

	/** Pocet sloupcu filtru * */
	protected $columns = 4;

	/**/
	protected $filterClass = 'SQLFormFilter';

	/**/
	public $parentList;

	/**/
	protected $renderer;

	/**/
	public $onSubmitted = array();

	/** nacteny stav ze session */
	protected $loadFromSession = FALSE;


	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);
		$this->form = new Form('fl' . $name, $this);
		$this->form->onSubmit[] = array($this, 'FormSubmitted');
		/* if(!$this->isSignalReceived()) {
		  $this->unserializeValues($this->filter);
		  } */
		$this->title = _('Filtr'); // vychozi nazev
	}


	public function getTitle()
	{
		return $this->title;
	}


	public function setTitle($value)
	{
		$this->title = $value;
	}


	public function getForm()
	{
		$this->getFilterValues();
		return $this->form;
	}


	public function setParentList($list)
	{
		$this->parentList = $list;
	}


	public function setColumns($value)
	{
		$this->columns = $value;
	}


	public function getColumns()
	{
		return $this->columns;
	}


	public function setFilterClass($value)
	{
		$this->filterClass = $value;
	}


	public function getFilterClass()
	{
		return $this->filterClass;
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


	public function disableButtons()
	{
		$this->showButtons = FALSE;
	}


	/**
	 * Zachyceni udalosti Filter.
	 * Probubla do nadrazeneho DataListu
	 *
	 */
	public function handleFilter()
	{
		$this->signalReceived = TRUE;
		if ($this->parentList != NULL) {
			$this->parentList->handleFilter();
		}
	}


	/**
	 * Sets fitler renderer.
	 * @param  IFormRenderer
	 * @return void
	 */
	public function setRenderer(IFormRenderer $renderer)
	{
		$this->renderer = $renderer;
	}


	/**
	 * Returns filter renderer.
	 * @return IFormRenderer
	 */
	public function getRenderer()
	{
		if ($this->renderer === NULL) {
			$this->renderer = new FilterRenderer($this->columns);
		}
		return $this->renderer;
	}


	/**
	 * Defaultni tlacitka filtru.
	 * - Hledat - odesle filtrovaci formular
	 * - Reset - vymaze formular
	 */
	protected function setDefaults()
	{
		$c = 0;
		foreach ($this->form->getControls() as $control) {
			if (!($control instanceof HiddenField)) {
				$c++;
			}
		}
		if ($c && $this->showButtons) {
			$this->form->addSubmit('filter', _('hledat'));
			$this->form->addButton('reset', _('vynulovat'))
					->setCssClass('button reset')->getControlPrototype()
					->onclick('reset' . ucfirst($this->form->getName()) . '()');
		}
		$this->form->setCssClass('cmsfilter');
		$this->form->setRenderer($this->getRenderer());
	}


	public function useAjax($value)
	{
		$this->form->useAjax = $value;
	}


	/**
	 * Vykresneni filtru, pokud ma v sobe nejaky formcontrol
	 *
	 */
	public function render()
	{
		$this->beforeRender();
		if ($this->form->getComponents()->count()) {
			$this->form->render();
		}
	}


	public function beforeRender()
	{
		$this->setDefaults();
		$this->form->setAction($this->link('filter'));
	}


	/**
	 * Vrati hodnoty formcontrolu z filtrovaciho formulare
	 *
	 */
	protected function getFilterValues()
	{
		if (!$this->form->isSubmitted()) {
			if (($this->loadFromSession && $this->parentList !== NULL &&
					($this->parentList->isSignalReceived() ||
					$this->parentList->getLimitControl()->limitForm->isSubmitted()))) {
				$this->filter = NULL;
			}
			$this->setFilterValues($this->filter);
		}
		return $this->form->getValues();
	}


	protected function setFilterValues($string = NULL)
	{
		if ($this->filter === NULL || !$this->loadFromSession) {
			$this->form->setDefaults();
		} // vychozi hodnoty controlu
		if ($this->getParam('url') != 1) {
			parse_str($string, $list);
			$this->form->setDefaults($list);  // nacteni stavu ze session
		} else {
			$list = $this->getParam('value');
			$this->form->setDefaults($list);  // nacteni stavu z url
		}
	}


	/** podstreceni jako z URL. Cely system je tak slozity, ze by to chtelo velkou refactorizaci filtru * */
	public function setValues($arr)
	{
		$this->params['url'] = 1;
		$this->params['value'] = $arr;
		$this->setFilterValues();
	}


	/**
	 * Nacteni hodnot z requestu
	 *
	 */
	public function exec()
	{
		$this->getFilterValues();
	}


	public function getValues()
	{
		return $this->form->getValues();
	}


	/**
	 * Vrati SQL dotaz za pomoci filterClass, treba je defaultne nastavena na tridu SQLFormFilter
	 *
	 */
	public function buildSql()
	{
		if (is_string($this->filterClass)) {
			$this->filterClass = new $this->filterClass();
		}
		$sql = $this->filterClass->getFilter($this);
		return $sql;
	}


	public function FormSubmitted(Form $form)
	{
		$this->onSubmitted();
	}


	/**
	 * Deserializuje URL zakodovany retezec.
	 * Zaroven nastavi hodnoty formcontrolu ve fitrovacim formulari
	 *
	 * @param string $string
	 */
	private function unserializeValues($string)
	{
		$this->setFilterValues($string);
	}


	/**
	 * Serializuje hodnoty formcontrolu do jednoho retezce pouziteho v URL
	 *
	 */
	private function serializeValues()
	{
		$arr = array();
		foreach ($this->getFilterValues() as $name => $v) {
			$e = TRUE;
			if (is_array($v)) {
				foreach ($v as $vv) {
					if ($vv != NULL && $vv !== '') {
						$e &= FALSE;
					}
				}
			} else {
				$e = empty($v);
			}
			$def = $this->form[$name]->getDefaultValue();
			$x = (isset($this->form[$name]) && $v != $def);
			if (($v === '0' || $x || !$e)) {
				$arr[$name] = $v;
			}
		}
		unset($arr['reset']);
		$this->null2empty($arr);
		if (!empty($arr)) {
			return http_build_query($arr, '', '&');
		} else {
			return '';
		}
	}


	/**
	 * Vraceni serializovane URL s moznosti zadat vlastni hodnotu, ktera prepise hodnotu ve formulari.
	 * Vhodne pro fitry typu odkaz
	 *
	 * @param mixed $force
	 * @return string
	 */
	public function getUrlValues($force = array())
	{
		$arr = array();
		$values = $this->getFilterValues();
		foreach ($values as $name => $v) {
			if (isset($force[$name])) {
				$v = $force[$name];
			}
			$e = TRUE;
			if (is_array($v)) {
				foreach ($v as $vv) {
					if ($vv != NULL && $vv !== '') {
						$e &= FALSE;
					}
				}
			} else {
				$e = empty($v);
			}
			$def = $this->form[$name]->getDefaultValue();
			$x = (isset($this->form[$name]) && $v != $def);
			$arr[$name] = $v;
		}
		unset($arr['reset']);
		$this->null2empty($arr);
		if (!empty($arr)) {
			return http_build_query($arr, '', '&');
		} else {
			return '';
		}
	}


	/**
	 * NULL hodnoty pole prevede na prazdne retezce
	 *
	 * @param mixed $arr
	 */
	protected function null2empty(array &$arr)
	{
		foreach ($arr as &$value) {
			if ($value === NULL) {
				$value = '';
			}
			if (is_array($value)) {
				$this->null2empty($value);
			}
		}
	}


	/**
	 * Je zpetne volani pri signalu?
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


	public function saveState(array & $params)
	{
		$this->saveFilterState();
		parent::saveState($params);
	}


	/**
	 * Resetovani nastaveni filtru (v session)
	 *
	 */
	public function reset()
	{
		$this->filter = NULL;
	}


	public function isEmpty()
	{
		$this->exec();

		$arr = array();
		foreach ($this->getFilterValues() as $name => $v) {
			$e = TRUE;
			if (is_array($v)) {
				foreach ($v as $vv) {
					if ($vv != NULL && $vv !== '')
						$e &= FALSE;
				}
			} else {
				$e = empty($v);
			}
			if (($v === '0' || !$e)) {
				$arr[$name] = $v;
			}
		}
		unset($arr['reset']);
		$this->null2empty($arr);
		return empty($arr);
	}


	/**
	 * Ulozeni stavu filtru do session
	 *
	 * @param mixed $params
	 */
	public function saveFilterState()
	{
		$this->filter = $this->serializeValues();
		/// session stav
		if ($this->saveState) {
			$session = Environment::getSession($this->getStateSessionIdentifier());
			$session->filter = $this->filter;
		}
	}


	/**
	 * Loads params
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		if ($this->saveState && !$this->isSignalReceived() && !isset($params['filter'])) {
			$session = Environment::getSession($this->getStateSessionIdentifier());
			if (isset($session->filter)) {
				$this->filter = $session->filter;
				$this->loadFromSession = TRUE;
			}
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
	 * pridani formcontrolu do filtru
	 * @param mixed $component
	 * @param string
	 */
	public function addFilter($component, $name)
	{
		$this->form->addComponent($component, $name);
		return $component;
	}


	/**
	 * Vraceni formcontrolu z filtru podle jmena
	 *
	 * @param string $name
	 * @param bool $need
	 */
	public function getFilter($name, $need = TRUE)
	{
		return $this->form->getComponent($name, $need);
	}


	public function removeFilter($component)
	{
		$this->form->removeComponent($component);
	}

	/*	 * ******************* interface \ArrayAccess ****************d*g* */


	/**
	 * Adds the component to the container.
	 * @param  string  component name
	 * @param  Nette\IComponent
	 * @return void.
	 */
	final public function offsetSet($name, $component)
	{
		$this->addFilter($component, $name);
	}


	/**
	 * Returns component specified by name. Throws exception if component doesn't exist.
	 * @param  string  component name
	 * @return Nette\IComponent
	 * @throws \InvalidArgumentException
	 */
	final public function offsetGet($name)
	{
		return $this->getFilter($name, TRUE);
	}


	/**
	 * Does component specified by name exists?
	 * @param  string  component name
	 * @return bool
	 */
	final public function offsetExists($name)
	{
		return $this->getFilter($name, FALSE) !== NULL;
	}


	/**
	 * Removes component from the container. Throws exception if component doesn't exist.
	 * @param  string  component name
	 * @return void
	 */
	final public function offsetUnset($name)
	{
		$component = $this->getFilter($name, FALSE);
		if ($component !== NULL) {
			$this->removeFilter($component);
		}
	}

	/** Tovarny * */


	/**
	 * Adds single-line text input control to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int	width of the control
	 * @param  int	maximum number of characters the user may enter
	 * @return TextInput
	 */
	public function addText($name, $label = '', $cols = NULL, $maxLength = NULL)
	{
		return $this[$name] = new TextInput($label, $cols, $maxLength);
	}


	/**
	 * Adds multi-line text input control to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  int	width of the control
	 * @param  int	height of the control in text lines
	 * @return TextArea
	 */
	public function addTextArea($name, $label, $cols = 40, $rows = 10)
	{
		return $this[$name] = new TextArea($label, $cols, $rows);
	}


	/**
	 * Adds hidden form control used to store a non-displayed value.
	 * @param  string  control name
	 * @return HiddenField
	 */
	public function addHidden($name)
	{
		return $this[$name] = new HiddenField;
	}


	/**
	 * Adds hidden form control used to store a non-displayed value.
	 * @param  string  control name
	 * @return ValueField
	 */
	public function addValue($name, $value)
	{
		return $this[$name] = new ValueField($value);
	}


	/**
	 * Adds check box control to the form.
	 * @param  string  control name
	 * @param  string  caption
	 * @return Checkbox
	 */
	public function addCheckbox($name, $caption)
	{
		return $this[$name] = new Checkbox($caption);
	}


	/**
	 * Adds set of radio button controls to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   options from which to choose
	 * @return RadioList
	 */
	public function addRadioList($name, $label, array $items = NULL)
	{
		return $this[$name] = new RadioList($label, $items);
	}


	/**
	 * Adds select box control that allows single item selection.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  int	   number of rows that should be visible
	 * @return SelectBox
	 */
	public function addSelect($name, $label, $items = NULL, $size = NULL)
	{
		return $this[$name] = new SelectBox($label, $items, $size);
	}


	/**
	 * Adds select box control that allows multiple item selection.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   options from which to choose
	 * @param  int	   number of rows that should be visible
	 * @return MultiSelectBox
	 */
	public function addMultiSelect($name, $label, array $items = NULL, $size = NULL)
	{
		return $this[$name] = new MultiSelectBox($label, $items, $size);
	}


	public function addDate($name, $label)
	{
		return $this[$name] = new DateField($label);
	}


	public function addTime($name, $label)
	{
		return $this[$name] = new TimeField($label);
	}


	public function addLookUp($name, $label, $sql, $sqllist, $cols)
	{
		return $this[$name] = new LookUpControl($label, $sql, $sqllist, $cols - 4);
	}


	public function addNumber($name, $label = '', $cols = 4)
	{
		return $this[$name] = new NumberInput($label, $cols);
	}


	/**
	 * Adds button used to submit form.
	 * @param  string  control name
	 * @param  string  caption
	 * @return SubmitButton
	 */
	public function addSubmit($name, $caption)
	{
		return $this[$name] = new SubmitButton($caption);
	}

}

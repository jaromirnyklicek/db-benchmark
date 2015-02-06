<?php


/**
 * Checkbox form je specialni druh multiformu. Neobsahuje tlacitka pro DHTML pridani a odebrani subformu.
 * Zobrazi vsechny zaznamy, ktere lze do vybrat a checkboxem se urcuje jestli vazba ve vazebni tabulce existuje nebo ne.
 * Typicky se pouziva pro vyber z omezene skupiny zaznamu, kde kazdy zaznam muze byt ve vazebni tabulce pouze jednou.
 * (Napr. Resitele v projektu, Zarazeni do kategorii)
 *
 * @author	  Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package	  Forms
 */
class MultiFormCheckbox extends MultiForm
{
	const CHECKED_ALL = NULL;

	/**
	 * Checkbox bude jako prvni input jeste pred labelem
	 *
	 * @var bool
	 */
	public $checkboxFirst = FALSE;

	/**
	 * Pole ID, ktere budou defaultne "checked". Je mozne nastavit na self::CHECKED_ALL.
	 * @var array|NULL
	 */
	public $checkedDefaults = array();

	/**
	 * Seznam, ze ktereho se vytvori instance Subformu
	 *
	 * @var array
	 */
	protected $list = array();


	//protected $newSubforms = array();

	public function __construct($name, $parent = null)
	{
		parent::__construct($name, $parent);
		$this->setRenderer(new TableRenderer());
	}


	public function getTemplate()
	{
		// defaultni sablona
		if ($this->template == NULL) {
			$this->template = dirname(__FILE__) . '/../Templates/checkboxform.phtml';
		}
		return $this->template;
	}


	public function setList($value)
	{
		$this->list = $value;
		return $this;
	}


	public function getList()
	{
		return $this->list;
	}


	protected function cloneComponets($source, $dest, $parent, $container = FALSE)
	{
		$iterator = $source->getComponents();
		foreach ($iterator as $name => $control) {
			// naklonuje formcontroly a pravidla
			$newControl = clone $control;
			if ($control instanceof IFormControl) {
				$newControl->setParentMulti($parent);
				$rules = $control->getRules();
				$newRules = clone $rules;
				$newRules->setControl($newControl);
				$newControl->setRules($newRules);
				if ($container) {
					unset($dest[$name]);
				}
				$dest[$name] = $newControl;
			} else {
				// naklonuje containery
				$dest[$name] = $newControl;
				$this->cloneComponets($control, $newControl, $parent, TRUE);
			}
		}
	}


	/**
	 * Vytvoreni instance SubFormu
	 *
	 * @param int $id			ID subformu
	 * @param string $label		Label subformu
	 * @return SubForm
	 */
	public function createSubform($id = NULL, $label = '')
	{
		$subform = new SubForm($this);
		if ($this->checkboxFirst) {
			$subform->addComponent(new Checkbox(), '__active')->setParentMulti($subform);
		}
		$subform->addComponent(new InfoTextControl(), '__label')->setValue($label);
		$subform['__label']->setValue($label);
		if (!$this->checkboxFirst) {
			$subform->addComponent(new Checkbox(), '__active')->setParentMulti($subform);
		}
		$subform['__active']->getControlPrototype()->__set('data-form-id', $id);
		$this->cloneComponets($this, $subform, $subform);
		// nastaveni ID pro subform
		$subform->setFormId($id);
		// nastaveni Delete buttonu
		$subform->setRenderer($this->getRenderer());
		return $subform;
	}


	/** Vrati ID vybranych subformu * */
	public function getChecked()
	{
		$res = array();
		foreach ($this->getSubforms() as $sf) {
			if ($sf['__active']->getValue()) {
				$res[] = $sf->getFormId();
			}
		}
		return $res;
	}


	/**
	 * Nacteni z requestu pro vsechny subformy, ktere se musi vytvorit.
	 *
	 * @param array $data
	 */
	protected function loadHttpData(array $data)
	{
		// vytvoreni subformu
		//$this->subformArr = array();
		foreach ($data as $key => $item) {
			// hledani vsech subformu podle ID inputu
			if (preg_match('/' . $this->name . '___fid_(-?[0-9]+)_$/', $key, $m)) {
				$id = $m[1];
				// naklonovani prototypovych inputu do noveho subformu
				$subform = $this->createSubform($id, $this->list[$id]);

				// zavolani loadHttpData na novy subform a nacteni jeho hodnot
				$subform->isSubmitted();
				// nove pridane prejmenuje na kladne ID
				if ($id < 0) {
					$subform->setFormId(count($this->subformArr));
				}
				// nastaveni hodnoty ID inputu
				$subform['__fid']->setValue(count($this->subformArr));

				// Smazane odstani a poznamena
				if (!$subform['__active']->getValue()) {
					//$this->deletedSubforms[] = $id;
				} else {
					if ($id < 0) {
						$this->insertedSubforms[] = $subform->getFormId();
					} else {
						$this->updatedSubforms[] = $subform->getFormId();
					}
				}
				$this->subformArr[$subform->getFormId()] = $subform;
			}
		}
	}


	/**
	 * Nastavi pole ID, ktere budou defaultne "checked".
	 * @param array|CHECKED_ALL $values pole ID, self::CHECKED_ALL pro vsechny, array() pro zadne
	 * @return \MultiFormCheckbox
	 */
	public function setChecked($values = self::CHECKED_ALL)
	{
		$this->checkedDefaults = is_array($values) ? $values : ($values !== self::CHECKED_ALL ? array($values) : self::CHECKED_ALL);
		return $this;
	}


	/**
	 * Vytvori vsechny subformy, ktere teoreticky mohou byt ve vazebni tabulce z vazaneo pole $list,
	 * kde jako ID subformu se pouzije klic z pole a jako label se pouzije hodnota.
	 */
	public function loadRows()
	{
		foreach ($this->list as $key => $item) {
			$id = $key;
			$label = $item;

			if (!isset($this->subformArr[$id])) {
				$subform = $this->createSubform($id, $label);
				$values = array();
				$subform->setDefaults($values, true);
				$this->subformArr[$subform->getFormId()] = $subform;
			} else {
				$subform = $this->subformArr[$id];
				$subform['__label']->setValue($label);
			}
			if ($this->checkedDefaults === self::CHECKED_ALL || in_array($id, $this->checkedDefaults)) {
				$subform['__active']->setValue(1);
			}
		}
	}


	public function render()
	{
		$args = func_get_args();
		// pokud je volano na prototyp ($this) s parametrem tak se pouzije puvodni parent::render()
		if (count($args) > 0) {
			parent::render($args[0]);
		}
		// render multiformu podle sablony
		else {

			$template = new Template();
			$template->setFile($this->getTemplate());
			$template->js = $this->getJs();
			$template->name = $this->name;

			$subforms = (string) $subforms = Html::el('input')->type('hidden')->name('__form[]')->value($this->getName());
			foreach ($this->getSubforms() as $sf) {
				$sf->getRenderer()->clear();
				$subforms .= $sf->getHtmlBody();
			}
			$template->subforms = $subforms;
			// default helpers
			$template->registerHelper('translate', 'HelpersCore::gettext');
			$template->registerHelper('escape', 'Nette\Templates\TemplateHelpers::escapeHtml');
			$template->registerHelper('escapeJs', 'Nette\Templates\TemplateHelpers::escapeJs');
			$template->registerHelper('escapeCss', 'Nette\Templates\TemplateHelpers::escapeCss');
			$template->registerFilter('CurlyBracketsFilter::invoke');
			$template->render();
		}
	}


	/**
	 * Vrati javacripty pro novy subform a validaci subformů
	 *
	 * @return string
	 */
	public function getJs()
	{
		$s = $this->jsValidate();
		return $s;
	}

}

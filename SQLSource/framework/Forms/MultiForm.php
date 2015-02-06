<?php

/**
* MultiForm vychází z klasického formu, ale jeho funkcionalitu rozšiřuje o možnost zpracování více záznamů najednou.
* Multiform také obsahuje Javascriptové funkce, které umožňují dynamicky přidávat nové položky ve formuláři.
* O generovani těchto javasciptů se stará Renderer resp. jeho InstantClientScript.
* Oproti klasickému formu má multi form více instancí SubFormů ($subformArr) pro každý záznam
*
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms
*/

require_once dirname(__FILE__) . '/SubForm.php';


class MultiForm extends SubForm
{

	const FORM_ID_HIDDEN = '__fid';
	/**
	* Kazdy zaznam ma svuj SubForm
	*
	* @var SubForm
	*/
	protected $subformArr = array();

	/**
	* Smazane Subformy
	*
	* @var mixed
	*/
	protected $deletedSubforms = array();

	/**
	* Nove vlozene subformy
	*
	* @var mixed
	*/
	protected $insertedSubforms = array();

	/**
	* Updatovane subformy
	*
	* @var mixed
	*/
	protected $updatedSubforms = array();

	/**
	* Nove subformy pridane aplikacne @see addSubform()
	*
	* @var SubForm
	*/
	protected $newSubforms = array();

	/**
	* Popisek k pridavacimu tlacitku
	*
	* @var string
	*/
	private $addLabel;

	/**
	* Sablona pro vykresleni multiformu
	*
	* @var mixed
	*/
	protected $template;

	/**
	 * Zakaze zobrazeni pridavaciho buttonu
	 *
	 * @var bool
	 */
	protected $disabledAddButton = FALSE;

	/**
	* nové subformy se zaporným ID prevede na kladne.
	*
	* @var bool
	*/
	protected $newAsPositive = TRUE;


	public function __construct($name, $parent = null)
	{
		$this->parentForm = $parent;
		$this->addHidden(self::FORM_ID_HIDDEN);
		parent::__construct($parent, $name);
		// vychozi renderer pro subformy
		$this->setRenderer(new TemplateRenderer(dirname(__FILE__).'/../Templates/subform.phtml'));
	}

	public function getAddLabel()
	{
		if($this->addLabel == NULL) $this->addLabel = _('Přidat');
		return $this->addLabel;
	}

	public function setAddLabel($value)
	{
		$this->addLabel = $value;
		return $this;
	}

	public function getTemplate()
	{
		if($this->template == NULL) $this->template = dirname(__FILE__).'/../Templates/multiform.phtml';
		return $this->template;
	}

	public function setTemplate($value)
	{
		$this->template = $value;
		return $this;
	}

	public function setSubformTemplate($value)
	{
		$this->setRenderer(new TemplateRenderer($value));
		return $this;
	}

	public function setNewAsPositive($value)
	{
		$this->newAsPositive = $value;
		return $this;
	}
	/**
	* Vrati hlavni HTML element <form>
	*
	*/
	public function getElementPrototype()
	{
		return $this->parentForm->getElementPrototype();
	}

	/**
	 * Vrati a nastavi prototypovy Subform. To je subform, ktery fyzicky neni videt,
	 * ale slouzi k popisu multiformu a pro generovani javascriptu pro pridani noveho
	 * a validaci
	 */
	public function getPrototype()
	{
		$this->setDefaults();
		$this->setButtons();
		$iterator = $this->getControls();
		foreach ($iterator as $name => $control) {
			$control->setParentMulti($this);
		}
		return $this;
	}

	/**
	* Vrati hlavni Form do ktereho Multiform patri
	*
	*/
	public function getMainForm()
	{
		return $this->parentForm;
	}

	/**
	 * Nastavi formular. do ktereho multiform patri
	 *
	 */
	public function setMainForm($form)
	{
		$this->parentForm = $form;
	}

	/**
	 * Vrati vsechny fyzicke subformy
	 *
	 * @return array of SubForm
	 */
	public function getSubforms()
	{
		return $this->subformArr;
	}

	public function resetSubforms()
	{
		$this->subformArr = array();
	}

	public function getDeletedSubforms()
	{
		return $this->deletedSubforms;
	}

	/**
	 * Vrati nazev multiformu
	 *
	 * @return string
	 */
	public function getFormName()
	{
		// je stejny jako nazev komponenty
		return $this->getName();
	}


	protected function cloneComponents($source, $dest, $parent, $container = FALSE)
	{
		$iterator = $source->getComponents();
		foreach ($iterator as $name => $control) {
			// naklonuje formcontroly a pravidla
			$newControl = clone $control;
			if($control instanceof IFormControl) {
				$newControl->setParentMulti($parent);
				if($container) unset($dest[$name]);
				$dest[$name] = $newControl;
			}
			else {
				// naklonuje containery
				$dest[$name] = $newControl;
				$this->cloneComponents($control, $newControl, $parent, TRUE);
			}
		}

		foreach ($dest->getComponents() as $name => $control) {
			// naklonuje pravidla
			if($control instanceof IFormControl) {
				$rules = $control->getRules();
				$newRules = clone $rules;
				$newRules->setControl($control);
				$control->setRules($newRules);
			}
		}
	}

	/**
	* Vytvori novy subform
	*
	* @param int $id
	* @return SubForm
	*/
	protected function createSubform($id = NULL)
	{
		$subform = $this->_createSubform();
		$subform->subformElm = $this->subformElm;
		$subform->subformClass = $this->subformClass;
		$this->cloneComponents($this, $subform, $subform);
		// nastaveni ID pro subform
		$subform->setFormId($id);
		// nastaveni Delete buttonu
		$subform->setButtons(FALSE);
		$subform->setRenderer($this->getRenderer());
		$subform->setMethod($this->parentForm instanceof Form && !$this->parentForm->isPost ? 'get' : 'post');
		return $subform;
	}


	protected function _createSubform()
	{
		return new SubForm($this);
	}

	/**
	* Pridani subformu aplikacne. Slouzi jako nahrada javascriptoveho pridani.
	* Interne se vola az po uspesnem zpracovani signalu.
	*
	*/
	public function addSubform($id = NULL)
	{
		// naklonovani prototypovych inputu do noveho subformu
		$subform = $this->createSubform();
		// id noveho subformu
		if($id === NULL) $id = -(count($this->newSubforms)+1);
		$subform->setFormId($id);
		$subform->setButtons(TRUE);
		// pridani k ostatnim
		$this->newSubforms[] = $subform;
		return $subform;
	}

	/**
	* Pridani aplikacne vlozenych subformu do hlavniho pole se subformy
	*
	*/
	public function joinNewSubforms()
	{
		$this->subformArr = array_merge($this->subformArr, $this->newSubforms);
		$this->newSubforms = array();
	}

	/**
	 * Nacteni z requestu pro vsechny subformy, ktere se musi vytvorit.
	 *
	 * @param array $data
	 */
	protected function loadHttpData(array $data)
	{
		// vytvoreni subformu
		foreach ($data as $key => $item) {
			// hledani vsech subformu podle ID inputu
			if(preg_match('/^'.$this->name.'_'.self::FORM_ID_HIDDEN.'_(-?[0-9]+)_$/', $key, $m)) {
				$id = $m[1];
				// naklonovani prototypovych inputu do noveho subformu
				$subform = $this->createSubform($id);

				// zavolani loadHttpData na novy subform a nacteni jeho hodnot
				$subform->isSubmitted();
				// nove pridane prejmenuje na kladne ID
				if($id < 0 && $this->newAsPositive) $subform->setFormId(count($this->subformArr));

				$subform->setButtons(FALSE);

				// nastaveni hodnoty ID inputu
				$subform[self::FORM_ID_HIDDEN]->setValue(count($this->subformArr));

				// Smazane odstrani a poznamena
				if($subform[SubForm::DELETE_ID]->getValue()) {
					 $this->deletedSubforms[] = $id;
				}
				else {
				   if($id < 0) $this->insertedSubforms[] = $subform->getFormId();
				   else $this->updatedSubforms[] = $subform->getFormId();
				   $this->subformArr[] = $subform;
				}
			}
		}
	}


	public function getValues()
	{
		$arr = array();
		foreach($this->getSubforms() as $subform) {
			$arr[$subform->getFormId()] = (object) $subform->getValues();
		}
		return $arr;
	}

	/**
	 * Is form valid?
	 * @return bool
	 */
	public function isValid()
	{
		if ($this->valid === NULL) {
			$this->validate();
		}
		return $this->valid;
	}


	/**
	 * Performs the server side validation.
	 * @return void
	 */
	public function validate()
	{
		$this->valid = true;
		foreach ($this->getSubforms() as $subform) {
			$this->valid &= $subform->isValid();
		}
	}

   /**
	* Returns validation errors.
	* @return array
	*/
	public function getErrors()
	{
		$errors = array();
		foreach ($this->getSubforms() as $subform) $errors = array_merge($errors, $subform->getErrors());
		return $errors;
	}

	/**
	 * Vrati javacripty pro novy subform a validaci subformů
	 *
	 * @return string
	 */
	public function getJs()
	{
		$this->getPrototype();
		$name = $this->getName();
		$this->formId = '\' + '.$name.'Index + \'';
		$this->setButtons();  // volani az po prirazeni ID
		$s = $this->render('jsMulti');
		$s .= $this->jsValidate();
		return $s;
	}


	/**
	 * Vykresli cely multiform
	 *
	 * @return string
	 */
	public function render()
	{
		$args = func_get_args();
		// pokud je volano na prototyp ($this) s parametrem tak se pouzije puvodni parent::render()
		if(count($args) > 0) parent::render($args[0]);

		// render multiformu podle sablony
		else {
			$js = $this->getJs(); // Musi se volat drive nez renderSubform.
			$subforms =  (string) Html::el('input')->type('hidden')->name('__form[]')->value($this->getName());
			foreach ($this->getSubforms() as $sf) $subforms .= $sf->renderSubform();

			$template = new Template();
			$template->setFile($this->getTemplate());
			$template->js = $js; // javascript pro pridavani novych subformu
			$template->subforms = $subforms; // vyrenderovane html pro subformy
			$template->control = $this;

			// default helpers
			$template->registerHelper('escape', 'Nette\Templates\TemplateHelpers::escapeHtml');
			$template->registerHelper('escapeJs', 'Nette\Templates\TemplateHelpers::escapeJs');
			$template->registerHelper('escapeCss', 'Nette\Templates\TemplateHelpers::escapeCss');
			$template->registerHelper('translate', 'helpersCore::gettext');
			$template->registerFilter('CurlyBracketsFilter::invoke');

			$this->prepareTemplate($template);

			$template->render();
		}
	}

	/**
	* Prida do multiformove sablony atributy.
	* Pri dedeni lze pridat takto do sablony vlasni atributy.
	*
	* @param ITemplate $template
	*/
	protected function prepareTemplate($template)
	{
		$template->name = $this->name;
		$template->addLabel = $this->getAddLabel();
		$template->control = $this;
	}

	/**
	 * Renders form to string.
	 * @return string
	 */
	public function __toString()
	{
		try {
			return (string)$this->render();

		} catch (/*\*/Exception $e) {
			if (func_get_args()) {
				throw $e;
			} else {
				trigger_error($e->getMessage(), E_USER_WARNING);
				return '';
			}
		}
	}

	/**
	 * Zakáže zobrazení přidávacího buttonu
	 *
	 * @return \MultiForm
	 */
	public function disableAddButton()
	{
		$this->disabledAddButton = TRUE;
		return $this;
	}

	/**
	 * Stav zobrazeni pridavacího buttonu
	 *
	 * @return Bool
	 */
	public function getDisabledAddButton()
	{
		return $this->disabledAddButton;
	}
}
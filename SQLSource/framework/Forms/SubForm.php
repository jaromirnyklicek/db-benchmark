<?php

/**
 * trida Subform slouzi pro pouziti v multiformech. Je to rozsireny Form o specialni
 * fukncke pro zpracovani multiformů.
 * Kazdy subform ma svoje interni ID. Pro databazove subformy odpovida pro ID sloupci v databazi. Pro
 * simple multiformy ma kazdy subform ID vygenerovane.
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class SubForm extends Form
{
	const DELETE_ID = '__delete';

	/**
	* Kazdy subform ma svoje interni ID. (Pro databazove subformy odpovida pro ID sloupci v databazi)
	*
	* @var mixed
	*/
	protected $formId = 0;


	/**
	* Puvodni ID. Vrati se pri Rollbacku
	*
	* @var int
	*/
	protected $oldId;


	/**
	* Reference na rodicovsky form
	*
	* @var mixed
	*/
	protected $parentForm;

	/**
	* FormControl pro odstraneni Subformu. Obvykle checkbox.
	*
	* @var mixed
	*/
	protected $deleteButton;

	/**
	* Je subform validni?
	*
	* @var bool
	*/
	protected $valid;

	/**
	* Element pro obalenu subdormu
	*
	* @var string
	*/
	public $subformElm = "div";

	/**
	* CSS trida pro $subformElm
	*
	* @var string
	*/
	public $subformClass = '';

	/**
	* Pozice v multiformu po odeslani formulare. (lze zjistit v Sortable Multiformu, jake ma poradi)
	* @var int
	*/
	public $position;

	public $orm;

	/**
	* Uzivatelsky objekt, lze si sem ulozit pomocne data
	*/
	public $tag;

	public function __construct($parent = NULL, $name = NULL)
	{
		$this->parentForm = $parent;
		parent::__construct($name);
	}

	/**
	* Tlacitko pro odstraneni subformu
	*
	*/
	public function getDeleteButton()
	{
		if($this->deleteButton == NULL) $this->deleteButton = new Checkbox(_('odstranit'));
		return $this->deleteButton;
	}


	/**
	* Nastaveni tlacitka pro odstraneni subformu
	*
	*/
	public function setDeleteButton($value)
	{
		$this->deleteButton = $value;
	}

	/**
	 * Nastavi ID subformu
	 *
	 * @return void
	 */
	public function setFormId($value)
	{
		$this->formId = $value;
	}

	public function setFormOldId($value)
	{
		$this->oldId = $value;
	}

	/**
	 * Vrati ID subformu
	 *
	 * @return string | int
	 */
	public function getFormId()
	{
		return $this->formId;
	}

	/**
	 * Vrati predchozi ID subformu
	 *
	 * @return string | int
	 */
	public function getFormOldId()
	{
		return $this->oldId;
	}

	/**
	* Oznaceni subformu do HTML, slozeno z nazvu rodice a id subformu
	*
	*/
	public function getHtmlId()
	{
		return $this->getFormName().'_'.$this->getFormId().'_';
	}

	/**
	 * Vrati nazev multiformu
	 *
	 * @return string
	 */
	public function getFormName()
	{
		return $this->parentForm->getFormName();
	}

	/**
	* Vrati nadrazeny form
	*
	*/
	public function getParentForm()
	{
		return $this->parentForm;
	}

	/**
	* Vrati nejhlavnejsi form
	*
	*/
	public function getMainForm()
	{
		return $this->parentForm->parentForm;
	}

	public function getPresenter()
	{
		return $this->getMainForm()->getPresenter();
	}

	/**
	 * Nastaveni vychozich internich tlacitek (Delete checkbox)
	 *
	 * @param bool $instantDelete - okamzite odstraneni subformu z DOMu
	 */
	public function setButtons($instantDelete = TRUE)
	{
		if(!isset($this[self::DELETE_ID])) {
			$deleteButton = $this->getDeleteButton();
			$deleteButton->setParentMulti($this);
			$this->addGroup('buttons')->setButtonsGroup();
			$this[self::DELETE_ID] = $deleteButton;
		}
		else {
			$deleteButton = $this[self::DELETE_ID];
		}

		$unremoveJs = $this->getFormName().'UnRemove('.$this->getFormId().')';
		if($instantDelete) {
			$removeJs = $this->getFormName().'Remove('.$this->getFormId().', true)';
		}
		else {
			$removeJs = $this->getFormName().'Remove('.$this->getFormId().', false)';
		}
		$deleteButton->getControlPrototype()->onClick('if(this.checked) {'.$removeJs.'} else {'.$unremoveJs.'}');
		$deleteButton->getControlPrototype()->__set('data-type', 'delete');
	}

	/**
	* Naplneni ORM objektu z formulare
	*
	*/
	public function populateORM()
	{
		$data = array();
		foreach($this->getControls() as $control) {
			if($sql = $control->getOption('sql')) {
				$value = $control->getValueOut();
				if($value === false || $control->readonly || $control->disabled) continue;
				if(!is_array($sql)) $sql = array($sql => $sql);
				foreach ($sql as $key => $item) {
					if(!is_array($value)) $v = $value;
					else $v = $value[$key];
					$data[$item] = $v;
				}
			}
		}
		$this->orm->setData($data);
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
	* Vrati ID
	*
	*/
	public function rollback()
	{
		if($this->oldId) $this->formId = $this->oldId;
	}

	/**
	 * Performs the server side validation.
	 * @return void
	 */
	public function validate()
	{
		$controls = $this->getControls();

		$this->valid = TRUE;
		foreach ($controls as $control) {
			if (!$control->getRules()->validate()) {
				$this->valid = FALSE;
			}
		}
		foreach ($this->getMultiForms() as $multi) {
				$this->valid &= $multi->isValid();
		}
	}

	protected function getPrototype()
	{
		// blank function
	}

	/**
	 * Vygeneruje validacni funkci pro controly v subformu
	 *
	 * @return string
	 */
	public function jsValidate()
	{
		$this->getPrototype();
		ob_start();
		$this->render('js');
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/**
	 * Renders form.
	 * @return void
	 */
	public function render()
	{
		$args = func_get_args();
		if(count($args) == 0) {$args = array('subform'); }
		parent::render($args[0]);
	}

	/**
	* Vygenerovani obsahu subformu
	* @return string
	*/
	public function getHtmlBody()
	{
		ob_start();
		$this->render('body');
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/**
	* Obali obsah subformu do <div id="getHtmlId">, aby slo manipulovat javascriptem s celym subformem
	* @return string
	*/
	public function renderSubform()
	{
		$s = $this->getHtmlBody();
		return (string) Html::el($this->subformElm)->class($this->subformClass)->id($this->getHtmlId())->setHtml($s);
	}
}
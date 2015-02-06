<?php
/**
* Databazovy formular navazany an hlavni formular.
* Typicky se pouziva na jazykove mutace, kde je potreba editovat jednim formularem dve tabulky
*
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms
*/


class FormBindDB extends SubForm implements IMultiFormDb
{

	/**
	* Jmeno ORM modelu, nad kterym multiform pracuje
	*
	* @var string
	*/
	public $model;

	/**
	* Cizi klic do rodicovskeho formu. Sloupec s kterym je multiform spojen.
	*
	* @var string
	*/
	protected $parentColumn;

	/**
	* Podminka pro SELECT
	*
	* @var string
	*/
	protected $where = '';

	  /** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully validated; */
	public $onBeforeSave;

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully saved; */
	public $onSave;

	/*public function getTemplate()
	{
		if($this->template == NULL) $this->template = dirname(__FILE__).'/../Templates/checkboxform.phtml';
		return $this->template;
	}	*/

	public function __construct($name, $parent = NULL)
	{
		$this->parentForm = $parent;
		//$this->addHidden(self::FORM_ID_HIDDEN);
		parent::__construct($parent, $name);
		// vychozi renderer pro subformy
		$this->setRenderer(new TemplateRenderer(dirname(__FILE__).'/../Templates/subform.phtml'));
	}

	/**
	* Svazani multiformu s hlavnim formen
	*
	* @param string|ORM $orm	Jmeno ORM modelu nebo hotovy ORM objekt
	* @param string $parent  Sloupec s kterym je multiform spojen. Cizi klic do rodicovskeho formu.
	* @param string $where	 Podminka pro SELECT
	*/
	public function bind($orm, $parent, $where = null)
	{
		$this->parentColumn = $parent;
		$this->where = $where;
		$this->model = $orm;
	}

	public function setSubformTemplate($value)
	{
		$this->setRenderer(new TemplateRenderer($value));
	}

	/**
	 * Vrati nazev multiformu
	 *
	 * @return string
	 */
	public function getFormName()
	{
		return $this->getName();
	}

	public function getMainForm()
	{
		return $this->parentForm;
	}

	public function getJs()
	{
		$s = $this->jsValidate();
		return $s;
	}

	public function load($parentOrm)
	{
		$parentId = $parentOrm->getId();
		if($this->model instanceof ORM) {
			$this->orm = $this->model;
		}
		else {
			if(empty($this->model)) throw new InvalidStateException('Formulář není navázán na ORM model. Použij bind()');
			// SELECT aktualni vazany
			// pokud neni, vytvori novy
			$list = ORM::factory($this->model)->where($this->parentColumn.'='.$parentId);
			if(!empty($this->where)) $list->where($this->where);
			$list = $list->find_all();
			if(isset($list[0])) $this->orm = $list[0];
			else $this->orm = ORM::factory($this->model);
		}


		// ORM -> Form
		$data = $this->orm->getData();
		$values = array();
		if($parentId || $this->model instanceof ORM) {
			foreach($this->getControls() as $control) {
				if($sql = $control->getOption('sql')) {
					$v = array();
					if(is_array($sql)) {
						foreach ($sql as $key => $value) {
								$v[$key] = $data[$value];
						}
					}
					else $v = $data[$sql];
					$values[$control->getName()] = $v;
				}
			}
		}
		// prirazeni hodnot
		$this->setDefaults($values, TRUE);
		$this->setMeta();
	}

	 /**
	 * Nastavi DbMeta pro formular
	 *
	 */
	 protected function setMeta()
	 {
		 $meta = $this->orm->getColumnsMetadata();
		 foreach($this->getControls(TRUE) as $control) {
			if($sql = $control->getOption('sql')) {
				if(is_array($sql)) {
					foreach ($sql as $key => $item) {
						if(isset($meta[$item])) {
							$this->processMeta($control, $meta[$item]);
						}
					}
				}
				else {
					if(isset($meta[$sql])) {
							$this->processMeta($control, $meta[$sql]);
					}
				}
			}
		 }
	 }

	 /**
	 * Nastavi maximalni delku TextInputum
	 *
	 * @param FOrmControl $control
	 * @param array $meta
	 */
	 protected function processMeta($control, $meta)
	 {
		if($control instanceof TextInput && isset($meta['length'])) {
		   $control->maxLenght = $meta['length'];
		}
	 }

	/**
	* Povede akci (INSERT, UPDATE)
	*
	* @param int $parentOrm		   ORM nadrazeneho zaznamu
	* @param bool $forceInsert	   Pro kopirovani jse vse prevedeno na insert
	*/
	public function save($parentOrm = NULL, $forceInsert = FALSE)
	{
		$parentId = $parentOrm->getId();
		$subform = $this;
		//$id = $subform->getFormId();
		if($forceInsert) $subform->orm->clear();
		$subform->populateORM();
		$obj = $subform->orm;
		$foreign_key = $this->parentColumn;
		$obj->$foreign_key = $parentId;
		// dispatch event
		$this->onBeforeSave($obj, $subform);
		// zavolani beforeSave() na vsechny formcontroly
		$this->beforeSave($obj, $subform);
		// Ulozeni subformu
		$result = $obj->save();
		if($result === FALSE) throw new RollbackException();
		$key = $obj->primary_key();
		// nastaveni noveho ID
		$subform->setFormId($obj->$key);
		// dispatch event
		$this->onSave($obj, $subform);
		// zavolani Save() na vsechny formcontroly
		$this->inSave($obj, $subform);
	}

	 /**
	* zavolani beforeSave() na vsechny formcontroly
	*
	* @param ORM $orm
	* @param SubForm $subform
	*/
	public function beforeSave($orm, $subform)
	{
		 foreach($subform->getControls() as $control) {
			if(method_exists ($control, 'beforeSave')) {
				$control->beforeSave($orm);
			}
		}
	}

	/**
	* zavolani Save() na vsechny formcontroly
	*
	* @param ORM $orm
	* @param SubForm $subform
	*/
	public function inSave($orm, $subform)
	{
		 foreach($subform->getControls() as $control) {
			if(method_exists ($control, 'save')) $control->save($orm);
		}
	}


	public function render()
	{
		parent::render();
		parent::render('js');
	}
}
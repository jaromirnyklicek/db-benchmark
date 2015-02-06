<?php


/**
 * Databázový formulář je nadstavba na klasickým formem. Přidáva podporu pro databazové operace SELECT, INSERT a UPDATE.
 * Databázové operace se provadí přes ORM.
 * Formulář obsahuje vychozí tlačitka Save, SaveToList, Delete, Copy.
 * V presenteru se musí zavolat metoda formulaře exec(). Tím se provede SELECT z databáze a pokud je zárověn ve stavu
 * Submit, tak se ve formSubmitted() provede načtení ORM atributu z formuláře a nasledně uloží do ORM objektu a databáze.
 * Formulář sám nemaže! A to z důvodu, že lze mazat z více mist, např. ze seznamu, který ani nemusí mit detail s formulářem
 * implementovaný. Ve vychozim stavu odeslání tlačítka Delete se přesměruje na akci delete v presenteru. Přeměrování lze
 * ovlivnit v tlačítku Delete nastavením atributu redirect.
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */
class FormDB extends Form
{
	const SAVE = '_save';
	const SAVE_AND_GO = '_save_go';
	const SAVE_AND_NEW = '_save_go_new';
	const DELETE = '_delete';
	const COPY = '_copy';

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully validated; */
	public $onBeforeSave;

	/** @var array of event handlers; zavola se pouze pri kopirovani po onBeforeSave */
	public $onBeforeCopy;

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully saved
	  but transaction is not commited. You can cancel transaction by throw RollbackException */
	public $onSaving;

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully saved */
	public $onSave;

	/** @var array of event handlers; Zavola se po onSave v pripade kopirovani  */
	public $onCopy;

	/** @var array of event handlers; Occurs when ORM is successfully loaded from database; */
	public $onLoad;

	/** @var array of event handlers; Occurs when form is successfully loaded from ORM; */
	public $onFormLoad;

	/**
	 * ID editovaneho zaznamu v DB
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * ID editovaneho zaznamu predany v exec()
	 *
	 * @var int
	 */
	protected $execId;

	/**
	 * ORM objekt nebo jeho nazev
	 *
	 * @var mixed
	 */
	public $orm;

	/**
	 * Stavove zpravy po dokonceni akce
	 * Povolene indexy pole: insert, update
	 * Formular delete neprovadi!
	 *
	 * @var array
	 */
	public $msg;

	/** Nastala chyba pri nacitani? => nepujde update * */
	public $errorLoad = FALSE;

	/**
	 * Udela redirect po ulozeni
	 *
	 * @var bool
	 */
	public $redirect = TRUE;

	/** Vynuti INSERT pri ukladani navazanych MultiFormu * */
	protected $forceInsertMultiForms = FALSE;

	/**
	 * Ulozeni probehne v db transakci
	 *
	 * @var mixed
	 */
	protected $transaction = TRUE;

	/**
	 * Ukladat modely modifikovane pomoci -> v 'sql' ?
	 * @var boolean
	 */
	public $saveRelatedModels = TRUE;


	public function __construct($name = NULL, $parent = NULL)
	{
		parent::__construct($name, $parent);
		// vychozi oznamovaci hlasky
		$this->msg['insert'] = _('Záznam ID=%s byl vytvořen.');
		$this->msg['update'] = _('Záznam ID=%s byl upraven.');
	}


	public function getId()
	{
		return $this->id;
	}


	/**
	 * Vrati ID subformu
	 *
	 * @return string | int
	 */
	public function getFormId()
	{
		return $this->getId();
	}


	public function getPresenter($need = TRUE)
	{
		return $this->parent;
	}


	public function setTransaction($value)
	{
		$this->transaction = $value;
	}


	/**
	 * Svazani formulare s ORM objektem.
	 * Muze byt nazev ORM modelu nebo jeho instance
	 *
	 * @param mixed $orm
	 */
	public function bind($orm)
	{
		if (is_string($orm)) {
			$this->orm = ORM::factory($orm);
		} elseif (!$orm instanceof ORM) {
			throw new Exception('Svázat formulář lze pouze s ORM objektem nebo jménem třídy reprezentují ORM objekt');
		} else {
			$this->orm = $orm;
		}
	}


	/**
	 * Defaultni SAVE button
	 *
	 */
	public function addSaveButton()
	{
		$this->addSubmit(self::SAVE, _('Uložit'))
						->setCssClass('button button-save')
				->onClick[] = array($this, 'formSubmitted');
	}


	/**
	 * Defaultni SAVE AND GO TO LIST button
	 *
	 */
	public function addSaveToListButton()
	{
		$this->addSubmit(self::SAVE_AND_GO, _('Uložit a přejít na seznam'))
						->setOption('redirect', 'list')
						->setCssClass('button button-savelist')
				->onClick[] = array($this, 'formSubmitted');
	}


	/**
	 * Defaultni SAVE AND GO TO FORM button
	 *
	 */
	public function addSaveToNewButton()
	{
		$this->addSubmit(self::SAVE_AND_NEW, _('Uložit a přidat další'))
						->setOption('redirect', 'edit')
						->setCssClass('button button-savenew')
				->onClick[] = array($this, 'formSubmitted');
	}


	/**
	 * Defaultni DELETE button
	 *
	 */
	public function addDeleteButton()
	{
		$this->addSubmit(self::DELETE, _('Odstranit'))
						->setValidationScope(FALSE)
						->setCssClass('button button-delete')
						->setOption('redirect', array('delete', 'form'))   // delete = action, form = extra parametr funkce
				->onClick[] = array($this, 'deleteSubmitted');
		$this[self::DELETE]->getControlPrototype()->onclick("form_validated = true");
	}


	/**
	 * Defaultni COPY button
	 *
	 */
	public function addCopyButton()
	{
		$this->addSubmit(self::COPY, _('Kopírovat'))
						->setCssClass('button button-copy')
				->onClick[] = array($this, 'formSubmitted');
	}


	/**
	 * Defaultni tlacitka formulare
	 *
	 */
	public function setDefaultButtons()
	{
		$this->addSaveButton();
		$this->addSaveToListButton();
		$this->addCopyButton();
		$this->addDeleteButton();
	}


	/**
	 * Provedeni databazove operace pro zaznam $id
	 * $id = 0 znamena novy zaznam (Insert)
	 *
	 * @param int $id
	 */
	public function exec($id = 0)
	{
		$this->onInvalidSubmit[] = array($this, 'invalidSubmit');
		$this->load($id);
		$this->execId = $id ? $id : $this->orm->getId();
		$this->id = $this->orm->getId();

		/**
		 * Nacteni vsech multiformu
		 */
		foreach ($this->getMultiForms() as $multi) {
			// Pouze databazove ma smysl nacitat
			if ($multi instanceof IMultiFormDb) {
				$multi->load($this->orm);
			}
		}

		$this->onFormLoad($this);
		$this->isSubmitted(); // processHttpRequest
	}


	/**
	 * pridani mezi ostatni subformy aplikacne pridane subformy
	 *
	 */
	public function joinNewSubforms()
	{
		if (!$submited || $submited && $this->isValid()) {
			foreach ($this->getMultiForms() as $multi) {
				if ($multi instanceof MultiForm) {
					$multi->joinNewSubforms();
				}
			}
		}
	}


	/**
	 * Nacteni formulare z databaze.
	 *
	 * @param int $id
	 */
	public function load($id)
	{
		$this->onLoad($this);
		if ($id > 0 || $this->orm->getId() > 0) {

			if ($this->orm->getId() == NULL) {
				// SELECT
				$this->orm->find($id);
			}

			// objekt v db neexistuje => nepujde ulozit
			if ($id) {
				$this->errorLoad = $this->orm->id != $id;
			}

			// Formularove controly si prectou data z ORM objektu
			$values = array();
			$data = $this->orm->getData();
			foreach ($this->getControls() as $control) {
				$sql = $control->getOption('sql');
				if ($sql) {
					$v = array();
					if (is_array($sql)) {
						foreach ($sql as $key => $item) {
							if (isset($data[$item])) {
								$v[$key] = $data[$item];
							} else {
								try {
									$v[$key] = $this->getOrmChainResult($item);
								} catch (MemberAccessException $e) {
									$v[$key] = $data[$item];
								}
							}
						}
					} else {
						if (isset($data[$sql])) {
							$v = $data[$sql];
						} else {
							try {
								$v = $this->getOrmChainResult($sql);
							} catch (MemberAccessException $e) {
								$v = $data[$sql];
							}
						}
					}
					$values[$control->getName()] = $v;
				}
			}
			$this->loadFromDb();
			$this->setDefaults($values, TRUE);
		} else {
			// Novy zaznam, pouze nastavi defaultni hodnoty
			$this->setDefaults();
		}
		$this->setMeta();
	}


	/**
	 * Nastavi DbMeta pro formular
	 */
	protected function setMeta()
	{
		$meta = $this->orm->getColumnsMetadata();
		$metaRelated = array();
		foreach ($this->getControls(TRUE) as $control) {
			$sql = $control->getOption('sql');
			if ($sql) {
				if (is_array($sql)) {
					foreach ($sql as $item) {
						if (isset($meta[$item])) {
							$this->processMeta($control, $meta[$item]);
						} else {
							$this->setMetaRelated($item, $control, $metaRelated);
						}
					}
				} else {
					if (isset($meta[$sql])) {
						$this->processMeta($control, $meta[$sql]);
					} else {
						$this->setMetaRelated($sql, $control, $metaRelated);
					}
				}
			}
		}
	}


	/**
	 * Nastavi DbMeta z navazanych objektu.
	 *
	 * @param string $chain 'sql' retez atributu
	 * @param FormControl $control
	 * @param array $metaCache pole jiz ziskanych DbMeta z navaznych objektu (optimalizace)
	 */
	private function setMetaRelated($chain, $control, array &$metaCache)
	{
		$chainPieces = explode(IFormData::MEMBER_SEPARATOR, $chain);
		if (count($chainPieces) > 1) {
			$tail = array_pop($chainPieces);
			$key = implode(IFormData::MEMBER_SEPARATOR, $chainPieces);
			if (!isset($metaCache[$key])) {
				$metaCache[$key] = $this->getOrmChainResult($key)->getColumnsMetadata();
			}
			if (isset($metaCache[$key][$tail])) {
				$this->processMeta($control, $metaCache[$key][$tail]);
			}
		}
	}


	/**
	 * Nastavi maximalni delku TextInputum
	 *
	 * @param FormControl $control
	 * @param array $meta
	 */
	protected function processMeta($control, $meta)
	{
		if ($control instanceof FloatInput && isset($meta['length'])) {
			$control->maxLenght = (int) $meta['length'] + 1;
		} elseif ($control instanceof TextInput && isset($meta['length'])) {
			$control->maxLenght = $meta['length'];
		}
	}


	/**
	 * Metoda vytvori zretezene volani atributu ORM modelu z retezce.
	 *
	 * Umozni vytvorit volani $this->orm->[jmeno_navazaneho_objektu]->[jmeno_objektu]->[atribut],
	 * napr. $this->orm->BaseText->title_cs ze 'sql' parametru formularoveho prvku.
	 *
	 * @param string $chain
	 * @return mixed hodnota volani zretezenych atributu
	 */
	private function getOrmChainResult($chain)
	{
		$chainPieces = explode(IFormData::MEMBER_SEPARATOR, $chain);
		$currentHead = $this->orm;
		foreach ($chainPieces as $memberName) {
			$currentHead = $currentHead->$memberName;
		}
		return $currentHead;
	}


	/**
	 * Ulozeni formulate do ORM objektu
	 *
	 * @param Form $form
	 */
	public function load2ORM($form)
	{
		$this->populateORM($form, $this->orm);
	}


	/**
	 * Ulozeni formulate do ciloveho objektu. (typicky do ORM)
	 *
	 * @param Form $source
	 * @param ORM $dest
	 */
	public function populateORM(Form $source, $dest)
	{
		$data = array();
		foreach ($source->getControls() as $control) {
			$sql = $control->getOption('sql');
			if ($sql) {
				$value = $control->getValueOut();
				if ($value === false || $control->readonly || $control->disabled) {
					continue;
				}
				if (!is_array($sql)) {
					$sql = array($sql => $sql);
				}
				foreach ($sql as $key => $item) {
					if (!is_array($value)) {
						$v = $value;
					} else {
						$v = $value[$key];
					}
					$data[$item] = $v;
				}
			}
		}
		$dest->setData($data);
	}


	/**
	 * Nevalidni formular
	 *
	 * @param Form $form
	 */
	public function invalidSubmit(Form $form)
	{
		$err = $form->getErrors();
		foreach ($this->getMultiForms() as $multi) {
			$err = array_merge($err, $multi->getErrors());
		}
		if (!empty($err)) {
			$e = join('<br/>', $err);
			$presenter = $form->getPresenter();
			$presenter->flashMessage(_('Nastala chyba:') . '<br/>' . $e, 'error');
		}
	}


	/**
	 * Alias pro formSubmitted
	 *
	 * @param SubmitButton|Form $button
	 */
	public function saveForm($buttonOrForm = NULL)
	{
		if ($buttonOrForm === NULL) {
			$buttonOrForm = $this;
		}
		return $this->formSubmitted($buttonOrForm);
	}


	/**
	 * Formular je validni a bude se ukladat do DB
	 *
	 * @param SubmitButton|Form $button
	 */
	public function formSubmitted($buttonOrForm)
	{
		$presenter = $this->getPresenter();

		if ($buttonOrForm instanceof Form) {
			$form = $buttonOrForm;
		} else {
			$form = $buttonOrForm->getForm();
		}

		$copy = FALSE;
		// kopie nastavi id jako pro novy zaznam
		if (isset($form[self::COPY]) && $form[self::COPY]->isSubmittedBy()) {
			$copy = TRUE;
			$sourceOrm = clone $this->orm;
			$this->orm->clear();
			$this->id = 0;
		} elseif ($this->errorLoad) {
			return FALSE;
		}

		// ulozeni hodnot do ORM
		$this->load2ORM($form);
		// zavolani beforeSave() na vsechny formControly
		$this->beforeSave($this->orm);

		// event dispach
		try {
			$this->onBeforeSave($this, $this->orm);
			if ($copy) {
				$this->onBeforeCopy($this, $this->orm, $sourceOrm);
			}
		} catch (RollbackException $e) {
			// nastala chyba -> rollback transakce
			foreach ($this->getMultiForms() as $multi) {
				// pouze databazove maji smysl
				if ($multi instanceof IMultiFormDb) {
					$multi->rollback();
				}
			}
			$this->addError($e->getMessage());
			$presenter->flashMessage($e->getMessage(), 'error');
			return FALSE;
		}

		if ($this->transaction) {
			sql::startTransaction();
		}
		try {
			// Ulozeni do DB
			$result = $this->orm->save();
			if ($result === FALSE) {
				throw new RollbackException();
			}
			// Ulozeni navazanych modelu
			if ($this->saveRelatedModels) {
				$relatedModelsSaved = array();
				foreach ($this->getControls() as $control) {
					$sql = $control->getOption('sql');
					if (!is_array($sql)) {
						$sql = array($sql);
					}
					foreach ($sql as $key) {
						$pos = strrpos($key, IFormData::MEMBER_SEPARATOR);
						if ($pos !== FALSE) {
							$modelKey = substr($key, 0, $pos - strlen($key));
							if (!isset($relatedModelsSaved[$modelKey])) {
								$model = $this->getOrmChainResult($modelKey);
								$model->save();
								$relatedModelsSaved[$modelKey] = $model;
							}
						}
					}
				}
				unset($relatedModelsSaved);
			}

			// zjisteni ID ulozeneo zaznamu
			$idColumn = $this->orm->primary_key();
			$newId = $this->orm->$idColumn;

			// zpracovani multiformu
			foreach ($this->getMultiForms() as $multi) {
				// pouze databazove maji smysl
				if ($multi instanceof IMultiFormDb) {
					if ($multi->isPopulated()) {
						$multi->save($this->orm, $this->id == 0 || $this->forceInsertMultiForms);
					}
				}
			}
			// dispach event
			$this->onSaving($this, $this->orm);
			// zavolani save() pro vsechny form controly
			$this->inSave($this->orm);
			// comitnuti transakce
			if ($this->transaction) {
				sql::commit();
			}
		} catch (Database_Exception $e) {
			// nastala databazova vyjimka -> rollback transakce
			if ($this->transaction) {
				sql::rollback();
			}
			throw $e; // probublani
		} catch (RollbackException $e) {
			// nastala chyba -> rollback transakce
			if ($this->transaction) {
				sql::rollback();
			}
			foreach ($this->getMultiForms() as $multi) {
				// pouze databazove maji smysl
				if ($multi instanceof IMultiFormDb) {
					$multi->rollback();
				}
			}
			$this->addError($e->getMessage());
			$presenter->flashMessage($e->getMessage(), 'error');
			return FALSE;
		}
		// dispach event
		$this->afterSave($newId, $this->id == 0, $form);
		$this->onSave($this, $this->orm);
		if ($copy) {
			$this->onCopy($this, $this->orm, $sourceOrm);
		}
		if (!$this->useAjax) {
			$this->redirect($form, $newId);
		}
	}


	/**
	 * Po ulozeni
	 *
	 * @param int $id - ID zaznamu
	 * @param bool $new - jde o novy zaznam (INSERT).
	 */
	protected function afterSave($id, $new, $form)
	{
		$presenter = $this->getPresenter();
		// Message uzivateli
		if (!$new) {
			if (!empty($this->msg['update'])) {
				$presenter->flashMessage(sprintf($this->msg['update'], $id));
			}
		} else {
			if (!empty($this->msg['insert'])) {
				$presenter->flashMessage(sprintf($this->msg['insert'], $id));
			}
		}
	}


	/**
	 * Poznamka: ORM je dostupne cez $form->orm alebo aj $this->orm.
	 */
	public function redirect($form, $id)
	{
		if (!$this->redirect) {
			return;
		}

		$presenter = $this->getPresenter();

		// presmerovani po ulozeni
		if (isset($form[self::SAVE]) && $form[self::SAVE]->isSubmittedBy()) {
			$presenter->redirect($presenter->getAction(), $this->getRedirectParams($form[self::SAVE], array('id' => $id)));
		}
		if (isset($form[self::COPY]) && $form[self::COPY]->isSubmittedBy()) {
			$presenter->redirect($presenter->getAction(), $this->getRedirectParams($form[self::COPY], array('id' => $id)));
		}
		foreach ($form->getControls() as $control) {
			if ($control instanceof SubmitButton && $control->isSubmittedBy() && $control->getOption('redirect')) {
				$presenter->redirect($control->getOption('redirect'), $this->getRedirectParams($control));
			}
		}
	}


	/**
	 * Nacita prametre pre redirect zo submitu.
	 *
	 * @param FormControl $button submit control
	 * @param array $implicitParams pociatocne parametre
	 * @return array
	 */
	protected function getRedirectParams(FormControl $button, array $implicitParams = array())
	{
		$rdp = $button->getOption('redirectParams');
		if ($rdp === NULL || $rdp === FALSE) {
			return $implicitParams;
		}
		return array_merge($implicitParams, is_array($rdp) ? $rdp : array($rdp));
	}


	/**
	 * Formular sam nemaze. Ve vychozim stavu presmeruje na akci delete.
	 * Premerovani lze ovlivtit v tlacitku Delete nastavenim redirect.
	 *
	 * @param SubmitButton $button
	 */
	public function deleteSubmitted(SubmitButton $button)
	{
		$form = $button->getForm();
		$presenter = $this->getPresenter();
		$dr = $form[self::DELETE]->getOption('redirect');
		if (is_array($dr)) {
			$r = $dr[0];
			$arg = $dr[1];
			$args = array($this->id, 'extra' => $arg);
		} else {
			$r = $dr;
			$args = array($this->id);
		}
		$presenter->redirect($r, $args);
	}


	/**
	 * Zavola beforeSave na vsechny formcontroly
	 *
	 * @param mixed $orm
	 */
	protected function beforeSave($orm)
	{
		foreach ($this->getControls(true) as $control) {
			if (method_exists($control, 'beforeSave')) {
				$control->beforeSave($orm);
			}
		}
	}


	/**
	 * Zavola save() na vsechny formcontroly
	 *
	 * @param mixed $orm
	 */
	protected function inSave($orm)
	{
		foreach ($this->getControls(true) as $control) {
			if (method_exists($control, 'save')) {
				$control->save($orm);
			}
		}
	}


	/**
	 * Zavola loadFromDb() na vsechny formcontroly
	 */
	protected function loadFromDb()
	{
		foreach ($this->getControls(true) as $control) {
			if (method_exists($control, 'loadFromDb')) {
				$control->loadFromDb($this->orm);
			}
		}
	}


	/**
	 * Nastavi flag pro vynuteni INSERT operace pri ukladaniu navazanych MultiFormu.
	 *
	 * @param bool $value
	 * @return FormDB self
	 */
	public function setForceInsertMultiForms($value = TRUE)
	{
		$this->forceInsertMultiForms = (bool) $value;
		return $this;
	}

}

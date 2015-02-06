<?php


/**
 * Datbazovy checkbox form.
 * Neobsahuje tlacitka pro DHTML pridani a odebrani subformu.
 * Zobrazi vsechny zaznamy, ktere lze do vybrat a checkboxem se urcuje jestli vazba ve vazebni tabulce existuje nebo ne.
 * Typicky se pouziva pro vyber z omezene skupiny zaznamu, kde kazdy zaznam muze byt ve vazebni tabulce pouze jednou.
 * (Napr. Resitele v projektu, Zarazeni do kategorii)
 *
 * @author	  Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package	  Forms
 */
class MultiFormCheckboxDB extends MultiFormCheckbox implements IMultiFormDb
{
	/**
	 * Jmeno ORM modelu, nad kterym multiform pracuje
	 *
	 * @var string
	 */
	public $orm_model;

	/**
	 * Cizi klic do rodicovskeho formu. Sloupec s kterym je multiform spojen.
	 *
	 * @var string
	 */
	protected $parentColumn;

	/**
	 * Sloupec pro ID Subformu.
	 *
	 * @var mixed
	 */
	protected $rowColumn;

	/**
	 * Podminka pro SELECT
	 *
	 * @var string
	 */
	protected $where = '';

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully validated; */
	public $onBeforeSave;

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully saved;
	 * 	Jednotnive subformy
	 */
	public $onSave;

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully saved;
	 * 	Za cely multiform
	 */
	public $onMultiSave;

	/** @var array of models */
	protected $orms = array();


	/**
	 * Svazani multiformu z rodicem
	 *
	 * @param string $orm		ORM model
	 * @param string $parent		Sloupec cizi klic do rodice
	 * @param string $row		Sloupec, ktery urci ID pro subform
	 * @param mixed $list		Seznam polozek, ze kterych se vytvori subformy
	 * @param mixed $where		Podminka pro SELECT akticnich zaznamu
	 */
	public function bind($orm, $parent, $row, $list, $where = null)
	{
		$this->parentColumn = $parent;
		$this->rowColumn = $row;
		$this->list = $list;
		$this->where = $where;
		$this->orm_model = $orm;
	}


	protected function loadHttpData(array $data)
	{
		// vytvoreni subformu
		//$this->subformArr = array();
		foreach ($data as $key => $item) {
			// hledani vsech subformu podle ID inputu
			if (preg_match('/' . $this->name . '___fid_(-?[0-9]+)_$/', $key, $m)) {
				$id = $m[1];
				// naklonovani prototypovych inputu do noveho subformu
				$subform = NULL;
				$index = NULL;
				$new = FALSE;
				if (!isset($this->subformArr[$id])) {
					continue;
				}

				$subform = $this->subformArr[$id];

				// zavolani loadHttpData na novy subform a nacteni jeho hodnot
				$subform->isSubmitted();
				// nove pridane prejmenuje na kladne ID
				if ($id < 0) {
					$subform->setFormId(count($this->subformArr));
				}
				// nastaveni hodnoty ID inputu
				$subform['__fid']->setValue(count($this->subformArr));

				// Smazane ostatni a poznamena
				if (!$subform['__active']->getValue()) {
					if (isset($subform->orm)) {
						$this->deletedSubforms[] = $subform->orm->id;
					}
				} else {
					//$this->updatedSubforms[] = $subform->getFormId();
					if (!isset($subform->orm)) {
						$this->insertedSubforms[] = $subform->getFormId();
					} else {
						$this->updatedSubforms[] = $subform->getFormId();
					}
				}
				//$this->subformArr[$subform->getFormId()] = $subform;
			}
		}
	}


	public function load($parentOrm)
	{
		$parentId = $parentOrm->getId();
		$this->loadRows();
		// SELECT aktualnich vazanych zaznamu
		$listORM = ORM::factory($this->orm_model)->where($this->parentColumn . '=' . $parentId);
		if (!empty($this->where)) {
			$listORM->where($this->where);
		}
		$list = $listORM->find_all();

		foreach ($list as $item) {
			$id = $item->{$this->rowColumn};
			// pokud k zaznamu existuje pripraveny SubForm, naplni jej a oznaci checkbox jako aktivni.
			if (isset($this->subformArr[$id])) {
				$subform = $this->subformArr[$id];
				$subform->orm = $item;
				$values['__active'] = TRUE;
				$values['__label'] = $subform['__label']->getValue();
				foreach ($subform->getControls() as $control) {
					$sql = $control->getOption('sql');
					if ($sql) {
						$v = array();
						if (is_array($sql)) {
							foreach ($sql as $key => $item) {
								$v[$key] = $subform->orm->$item;
							}
						} else {
							$v = $item->$sql;
						}
						$values[$control->getName()] = $v;
					}
				}
				$subform->setDefaults($values, true);
				$this->setMeta($subform);
			}
		}
	}


	/**
	 * Nastavi DbMeta pro subform
	 *
	 * @param SurbForm $control
	 * @param ORM $orm
	 */
	protected function setMeta($subform)
	{
		$meta = $subform->orm->getColumnsMetadata();
		foreach ($subform->getControls() as $control) {
			$sql = $control->getOption('sql');
			if ($sql) {
				if (is_array($sql)) {
					foreach (array_keys($sql) as $key) {
						if (isset($meta[$key])) {
							$this->processMeta($control, $meta[$key]);
						}
					}
				} else {
					if (isset($meta[$sql])) {
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
		if ($control instanceof TextInput && isset($meta['length'])) {
			$control->maxLenght = $meta['length'];
		}
	}


	/**
	 * Projede vsechny subformy a provede akci (INSERT, DELETE, UPDATE)
	 *
	 * @param ORM $parentOrm		   ORM nadrazeneho zaznamu (pri ID ciziho klice)
	 * @param bool $forceInsert	   Pro kopirovani jse vse prevedeno na insert
	 */
	public function save($parentOrm = NULL, $forceInsert = FALSE)
	{
		$parentId = $parentOrm->getId(); // id ciziho klice
		// Naplneni ORM na vsech subformach
		$this->populateORMs($parentId, $forceInsert);
		$this->saveORMs($parentId, $forceInsert);
	}


	/**
	 * ulozeni vsech ORM na subformech
	 *
	 */
	protected function saveORMs($parentId, $forceInsert)
	{
		$savedIdArr = array(); // ulozene ID vazavych zaznamu
		foreach ($this->getSubforms() as $subform) {
			$id = $subform->getFormId();
			$obj = $subform->orm;
			if ($obj != NULL) {
				if (in_array($id, $this->insertedSubforms) || ($forceInsert && $subform['__active']->getValue())) {
					$this->orms[] = array(MultiFormDB::INSERTED => $obj);
				} elseif (in_array($id, $this->updatedSubforms)) {
					$this->orms[] = array(MultiFormDB::UPDATED => $obj);
				}
				$this->onBeforeSave($obj, $subform);
				$this->beforeSave($obj, $subform);
				$obj->save();
				$newid = $obj->{$this->rowColumn};
				$subform->setFormId($newid);
				$this->onSave($obj, $subform);
				$this->inSave($obj, $subform);
				$savedIdArr[] = $id;
			}
		}
		foreach ($this->deletedSubforms as $id) {
			$obj = ORM::factory($this->orm_model, $id);
			if ($obj->id) {
				$this->orms[] = array(MultiFormDB::DELETED => $obj);
			}
			$result = $obj->delete();
			if ($result === FALSE) {
				throw new RollbackException();
			}
		}

		$result = new stdClass();
		$result->parent = $parentId;
		$result->saved = $savedIdArr;
		$result->deletedSubforms = $this->deletedSubforms;
		$this->onMultiSave($this, $result);
	}


	/**
	 * Naplneni ORM na vsech subformach
	 *
	 * @param bool $forceInsert	   Pro kopirovani je vse prevedeno na insert
	 */
	public function populateORMs($parentId, $forceInsert = FALSE)
	{
		$this->orms = array();
		foreach ($this->getSubforms() as $subform) {
			$id = $subform->getFormId();
			$obj = NULL;
			if (in_array($id, $this->insertedSubforms) || ($forceInsert && $subform['__active']->getValue())) {
				$obj = ORM::factory($this->orm_model);
			} elseif (in_array($id, $this->updatedSubforms)) {
				$obj = ORM::factory($this->orm_model)->find($subform->orm->id);
			}
			if ($obj != NULL) {
				$subform->orm = $obj;
				$subform->populateORM();
				$obj->{$this->parentColumn} = $parentId;
				$obj->{$this->rowColumn} = $id;
			}
		}
	}


	/**
	 * Vrati vsechny ORM objekty s priznakem INSERTED, UPDATED, DELETED
	 */
	public function getORMs()
	{
		return $this->orms;
	}


	/**
	 * zavolani beforeSave() na vsechny formcontroly
	 *
	 * @param ORM $orm
	 * @param SubForm $subform
	 */
	public function beforeSave($orm, $subform)
	{
		foreach ($subform->getControls() as $control) {
			if (method_exists($control, 'beforeSave')) {
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
		foreach ($subform->getControls() as $control) {
			if (method_exists($control, 'save')) {
				$control->save($orm);
			}
		}
	}

}

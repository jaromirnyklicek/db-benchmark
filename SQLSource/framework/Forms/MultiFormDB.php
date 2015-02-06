<?php
/**
* Databazovy multiform pracuje s ORM modelem, kde kazdemu subformu je prirazeny jeden ORM objekt,
* ktery reprezentuje zaznam v DB. V metode load() se pro kazdy zaznam v DB vytvori instance subformu.
*
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms
*/


class MultiFormDB extends /*Nette\Forms\*/MultiForm implements IMultiFormDb
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
	* Podminka pro SELECT
	*
	* @var string
	*/
	protected $where = '';

	/**
	* ORDER BY pro SELECT
	*
	* @var string
	*/
	protected $order = '';
	protected $order_direction = 'ASC';



	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully validated; */
	public $onBeforeSave;

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully saved; */
	public $onSave;

	public $onLoad;

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully validated; */
	public $onItemBeforeSave;

	/** @var array of event handlers; Occurs when the button SAVE is clicked and form is successfully saved; */
	public $onItemSave;

	public $onItemLoad;

	const INSERTED = 1;
	const UPDATED = 2;
	const DELETED = 3;
	protected $orms = array();

	/**
	 * @var string odkial ziskat z modelu nadradeneho formulara ID parent modelu v metode load(), v pripade NULL sa pouzije metoda getId(), teda ID modelu formulara
	 */
	protected $loadParentIDFrom = NULL;

	/**
	* Svazani multiformu s hlavnim formen
	*
	* @param string $orm	Jmeno ORM modelu
	* @param string $parent  Sloupec s kterym je multiform spojen. Cizi klic do rodicovskeho formu.
	* @param string $where	 Podminka pro SELECT
	*/
	public function bind($orm, $parent, $where = NULL, $order = NULL, $order_direction = 'ASC')
	{
		$this->parentColumn = $parent;
		$this->where = $where;
		$this->order = $order;
		$this->order_direction = $order_direction;
		$this->orm_model = $orm;
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
		$position = 0;
		foreach ($data as $key => $item) {
			// hledani vsech subformu podle ID inputu
			if(preg_match('/^'.$this->name.'_'.self::FORM_ID_HIDDEN.'_(-?[0-9]+)_$/', $key, $m)) {
				$id = $m[1];
				// naklonovani prototypovych inputu do noveho subformu
				$subform = NULL;
				$index = NULL;
				$new = false;
				foreach($this->subformArr as $key => $sf) {
					if($sf->getFormId() == $id) {
						$subform = $sf;
						$index = $key;
					}
				}

				if($subform == NULL) {
					$subform = $this->createSubform($id);
					$subform->orm = ORM::factory($this->orm_model);
					$new = TRUE;
				}

				// zavolani loadHttpData na novy subform a nacteni jeho hodnot
				$subform->isSubmitted();

				// nove pridanym nastavi stare ID pro pripad rollbacku
				if($id < 0) $subform->setFormOldId($id);
				$subform->setButtons(FALSE);

				// nastaveni hodnoty ID inputu
				//$subform['__fid']->setValue(count($this->subformArr));
				$subform->position = $position;

				// Smazane odstrani a poznamena
				if($subform[SubForm::DELETE_ID]->getValue()) {
					 $this->deletedSubforms[] = $id;
					 if(!$new) {
						unset($this->subformArr[$index]);
					 }
				}
				else {
				   if($id < 0) $this->insertedSubforms[] = $subform->getFormId();
				   else $this->updatedSubforms[] = $subform->getFormId();
				   if($new) $this->subformArr[] = $subform;
				}
				$position++;
			}
		}

		// vypocet jestli v databazi nejake subformy neprebyvaji (napr. je nekdo mezitim vlozil)
		// => ty se budou muset smazat, i kdyz je uzivatel nemaze. (Podle hesla kdo pozdeji prijde, ten uklada)
		$actualId = array_merge($this->insertedSubforms, $this->updatedSubforms);
		$loadedId = array();
		foreach($this->subformArr as $key => $sf) $loadedId[] = $sf->getFormId();
		$exces = array_diff($loadedId, $actualId);
		$this->deletedSubforms = array_merge($this->deletedSubforms, $exces);
	}

	/**
	* Vrati ID vsech subformu
	*
	*/
	public function rollback()
	{
		foreach($this->subformArr as $key => $sf) $sf->rollback();
	}

	/**
	* Nacte subformy k zobrazeni.
	* Musi vratit pole ORM objektu do kterych se bude ukladat.
	*
	*/
	protected function getItems($id)
	{
		// SELECT aktualnich vazanych zaznamu
		$list = ORM::factory($this->orm_model)->where('`'.$this->parentColumn.'`='.$id);
		if(!empty($this->where)) $list->where($this->where);
		if(!empty($this->order)) $list->orderby($this->order, $this->order_direction);
		return $list->find_all();
	}

	/**
	* Nacteni subformu
	*
	* @param ORM $parentOrm
	*/
	public function load($parentOrm)
	{
		$ormlist = $this->getItems($this->getParentId($parentOrm));
		$this->createSubforms($ormlist);
	}
	
	
	/**
	 * Ziska ID parent modelu.
	 * 
	 * @param ORM $parentOrm
	 * @return int ID parent modelu
	 */
	protected function getParentId($parentOrm)
	{
		if ($this->loadParentIDFrom !== NULL) {
			return $parentOrm->{$this->loadParentIDFrom} !== NULL ? $parentOrm->{$this->loadParentIDFrom} : 0;
		}
		return $parentOrm->getId();
	}


	protected function createSubform($id = NULL)
	{
		$subform = parent::createSubform($id);
		$subform->orm = ORM::factory($this->orm_model);
		$this->setMeta($subform, $subform->orm);
		return $subform;
	}


	protected function createSubforms($list)
	{
		 // naklonovani prototypovych inputu do noveho subformu
		// pro kazdy zaznam se vytvori instance SubFormu
		foreach($list as $item) {
			$subform = $this->createSubform($item->id);
			$this->subformArr[] = $subform;
			$subform->orm = $item;
			$this->onItemLoad($subform, $subform->orm);
			$values = array();
			$data = $item->getData();
			// nasatveni hodnot controlu z ORM
			foreach($subform->getControls() as $control) {
				if($sql = $control->getOption('sql')) {
					$v = array();
					if(is_array($sql)) {
						foreach ($sql as $key => $value) {
							$v[$key] = $data[$value];
						}
					}
					else {
						 /*TODO:
							if(strpos($sql, '->')) {
							$arr = split('->', $sql);
							$i = 0;
							$obj = $item;
							while($i < count($arr)) {
								$sql = $arr[$i];
								$obj = $obj->$sql;
								$i++;
							}
							$v = $obj;
						}
						else */
						if(!array_key_exists($sql, $data)) throw new InvalidStateException(get_class($item).'::getData() neobsahuje `'.$sql.'`');
						$v = $data[$sql];
					}
					$values[$control->getName()] = $v;
				}
				$this->loadFromDb($subform);
			}
			// prirazeni hodnot
			$subform->setDefaults($values, true);
			$this->setMeta($subform, $subform->orm);
		}
		$this->setMeta($this, ORM::factory($this->orm_model));
		$this->onLoad($this);
	}

	 /**
	 * Nastavi DbMeta pro subform
	 *
	 * @param SurbForm $control
	 * @param ORM $orm
	 */
	 protected function setMeta($subform, $orm)
	 {
		 $meta = $orm->getColumnsMetadata();
		 foreach($subform->getControls() as $control) {
			if($sql = $control->getOption('sql')) {
				if(is_array($sql)) {
					foreach ($sql as $key => $item) {
						$this->processMeta($control, @$meta[$key]);
					}
				}
				else {
					/* Todo?
					if(strpos($sql, '->')) {
							$arr = split('->', $sql);
							$i = 0;
							$obj = $orm;
							while($i < count($arr)) {
								$sql = $arr[$i];
								$obj = $obj->$sql;
								$i++;
							}
							$v = $obj;
					}
					else
					*/
					$this->processMeta($control, @$meta[$sql]);
				}
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
		if($control instanceof TextInput && isset($meta['length'])) {
		   $control->maxLenght = $meta['length'];
		}
	 }



	/**
	* Projede vsechny subformy a provede akci (INSERT, DELETE, UPDATE)
	*
	* @param ORM $parentOrm		   ORM nadrazeneho zaznamu
	* @param bool $forceInsert	   Pro kopirovani je vse prevedeno na insert
	*/
	public function save($parentOrm = NULL, $forceInsert = FALSE)
	{
		// id ciziho klice
		if($this->loadParentIDFrom !== NULL){
			$parentId = $parentOrm->{$this->loadParentIDFrom};
		}else{
			$parentId = $parentOrm->getId();
		}

		// Naplneni ORM na vsech subformach
		$this->populateORMs($parentId, $forceInsert);

		// ulozeni vsech ORM na subformech
		$this->saveORMs();
	}

	protected function saveORMs()
	{
		// zavolani onItemBeforeSave na vsechny subformy
		$this->beforeSaveORMs();

		foreach($this->getSubforms() as $subform) {
			$obj = $subform->orm;
			// Ulozeni subformu
			$result = $obj->save();
			if($result === FALSE) throw new RollbackException();
			$key = $obj->primary_key();
			// nastaveni noveho ID
			$subform->setFormId($obj->$key);
			// dispatch event
			$this->onItemSave($subform, $obj);
			// zavolani Save() na vsechny formcontroly
			$this->inSave($obj, $subform);

			if(in_array($subform->getFormId(), $this->updatedSubforms)) {
				$this->orms[] = array(self::UPDATED => $obj);
			}
			else {
				$this->orms[] = array(self::INSERTED => $obj);
			}
		}

		// subformy ke smazani => DELETE
		foreach($this->deletedSubforms as $id) {
			if($id > 0) {
				$obj = ORM::factory($this->orm_model, $id);
				if($obj->id) $this->orms[] = array(self::DELETED => $obj);
				$result = $obj->delete();
				if($result === FALSE) throw new RollbackException();
			}
		}
		$this->onSave($this);
	}

	/**
	* Vrati vsechny ORM objekty s priznakem INSERTED, UPDATED, DELETED
	*
	*/
	public function getORMs()
	{
		return $this->orms;
	}

	protected function beforeSaveORMs()
	{
		foreach($this->getSubforms() as $subform) {
			$obj = $subform->orm;
			// dispatch event
			$this->onItemBeforeSave($subform, $obj);
			// zavolani beforeSave() na vsechny formcontroly
			$this->beforeSave($obj, $subform);
		}
		// dispach event
		$this->onBeforeSave($this);
	}

	/**
	* Naplneni ORM na vsech subformach
	*
	* @param bool $forceInsert	   Pro kopirovani je vse prevedeno na insert
	*/
	public function populateORMs($parentId, $forceInsert = FALSE)
	{
		// naplneni vsech ORM
		foreach($this->getSubforms() as $subform) {
			$id = $subform->getFormId();
			$exists = TRUE;
			if(in_array($id, $this->insertedSubforms) || $forceInsert) {
				$obj = ORM::factory($this->orm_model);
				$subform->orm = $obj;
			}
			elseif(in_array($id, $this->updatedSubforms)) {
				//$obj = ORM::factory($this->orm)->find($id);  // jde o UPDATE => prvne SELECT
				$obj = $subform->orm;
			}
			else {
				$exists = FALSE;
			}
			if($exists) {
				$subform->populateORM();
				$foreign_key = $this->parentColumn;
				$obj->$foreign_key = $parentId;
			}
		}
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
				$control->beforeSave($orm, $subform);
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
			if(method_exists ($control, 'save')) $control->save($orm, $subform);
		}
	}

	/**
	* Zavola loadFromDb() na vsechny formcontroly
	*/
	protected function loadFromDb($subform)
	{
		 foreach($subform->getControls(true) as $control) {
			if(method_exists ($control, 'loadFromDb')) $control->loadFromDb($subform->orm);
		}
	}


	/**
	 * Nastavi, odkial sa z prilozeneho modelu z hlavneho formulara ziska ID parent modelu (ID zaznamu).
	 *
	 * @param string|NULL $member
	 * @return \RecordMultiFormDB
	 */
	public function setLoadParentIDFrom($member = NULL)
	{
		$this->loadParentIDFrom = $member;
		return $this;
	}
	
}
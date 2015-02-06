<?php
class CMSForm extends AppFormDB {

	protected $disableInsert = FALSE;
	protected $disableUpdate = FALSE;

	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
	  parent::__construct($parent, $name);
	  $this->addGroup('buttons')->setButtonsGroup();
	  $this->setDefaultButtons();
	  $this->setCurrentGroup();
	  $this->setCssClass('cmsform');
	  $this->setRenderer(new TemplateRenderer(INCLUDE_DIR.'/Core/Templates/cmsform.phtml'));
	  $this->getRenderer()->setForm($this);
	  $this->checkUnsave = TRUE;
	}

	/** Skryti pouze tlacitek **/

	public function disableCopy()
	{
		  unset($this[FormDB::COPY]);
	}

	public function disableSaveGo()
	{
		  unset($this[FormDB::SAVE_AND_GO]);
	}

	public function disableSaveNew()
	{
		  unset($this[FormDB::SAVE_AND_NEW]);
	}

	public function disableSave()
	{
		$this->disableUpdate();
		unset($this[FormDB::SAVE]);
		$this->disableSaveGo();
	}

	public function disableDelete()
	{
		  unset($this[FormDB::DELETE]);
	}

	/** Prava, ktera se kontroluji v execu **/

	public function disableInsert()
	{
		  $this->disableCopy();
		  $this->disableInsert = TRUE;
	}

	public function disableUpdate()
	{
		  $this->disableUpdate = TRUE;
	}

	/**
	* Po dokonceni akce formualre
	*
	* @param int $id  nove/upravene id zaznamu
	* @param bool $new TRUE jestlize jde o novy zaznam (INSERT)
	* @param mixed $form formular
	*/
	public function afterSave($id, $new, $form)
	{
		// Zalogovani
		if(!$new) Log::write(Log::NOTICE, 'Záznam ID='.$id.' byl upraven.');
		else Log::write(Log::NOTICE, 'Záznam ID='.$id.' byl vytvořen.');

		parent::afterSave($id, $new, $form);
	}


	public function exec($id = 0)
	{
		if($id == 0) {
			$this->disableCopy();
			$this->disableDelete();
		}
		if($this->disableInsert) $this->disableCopy();
		if($this->disableUpdate && $id) $this->disableSave();

		parent::exec($id);
	}

	public function renderRow($controlname)
	{
		return $this->getRenderer()->getRenderer()->renderPair($this[$controlname]);
	}

	public function formSubmitted($buttonOrForm)
	{
		$id = $this->orm->{$this->orm->primary_key()};
		if($buttonOrForm instanceof Form) $form = $buttonOrForm;
		else $form = $buttonOrForm->getForm();
		$copy = isset($form[self::COPY]) && $form[self::COPY]->isSubmittedBy();
		if($copy || $id == 0) $insert = TRUE;
		else $insert = FALSE;

		if($this->disableInsert && $insert) return NULL;
		if($this->disableUpdate && !$insert) return NULL;

		parent::formSubmitted($buttonOrForm);
	}

}
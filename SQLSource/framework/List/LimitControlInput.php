<?php

/**
* Komponenta pro vyber poctu zaznamu na strance. Vybernik je formularovy input s rucnim zadanim cisla
* Vyuziva se v DataListu.
*/
class LimitControlInput extends Control implements ILimitControl
{
	public $default = 25;
	public $limitForm;
	protected $value;
	public $limitLabel;
	protected $useAjax = TRUE;

	protected $templateFile;

	public function __construct(/*Nette\*/IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);
		$this->createLimitForm();
		$this->limitLabel = _('Na stranu').':';
	}

	public function createLimitForm()
	{
		$this->value = $this->default;
		$this->limitForm = new Form($this->parent->getName().'_lmt', $this);
		$this->limitForm->addText('limit')
							->setValue($this->value)
							->setCssClass('limitInput');
		$this->limitForm->addSubmit('OK', 'OK');
		$this->limitForm->onSubmit[] = array($this, 'limitSubmit');
	}

	public function setValue($value)
	{
		$this->value = $value;
		$this->limitForm['limit']->setValue($value);
		return $this;
	}

	public function setUseAjax($value)
	{
		$this->useAjax = $value;
		return $this;
	}

	public function getValue()
	{
		if($this->value == NULL) $this->setValue($this->default);
		return $this->value;
	}

	public function getDefaultValue()
	{
		return $this->default;
	}

	public function setDefaultValue($value)
	{
		$this->default = $value;
		if(!$this->isSubmitted() && $this->value == NULL) $this->setValue($value);
		return $this;
	}

	public function setTemplateFile($value)
	{
		$this->templateFile = $value;
		return $this;
	}

	public function getTemplateFile()
	{
		if($this->templateFile === NULL) $this->templateFile = dirname(__FILE__).'/../Templates/DataGrid/limit.phtml';
		return $this->templateFile;
	}

	public function handleLimit()
	{

	}

	public function limitSubmit($form)
	{
		$this->value = (int)$form['limit']->getValue();
		if($this->value == 0) $this->value = NULL;
		$this->getParent()->handleLimit($this->value);
	}

	public function exec()
	{
		$this->limitForm->isSubmitted();
	}

	public function isSubmitted()
	{
		return $this->limitForm->isSubmitted();
	}

	public function render()
	{
		$this->limitForm->action = $this->link('limit');
		$this->limitForm->useAjax = $this->useAjax;
		if($this->useAjax) {
			$mask = $this->getParent()->getUniqueId().'_grid';
			$this->limitForm->getElementPrototype()->onsubmit('return !AjaxMask.Show("'.$mask.'")');
		}
		$template = $this->createTemplate();
		$template->useAjax = $this->useAjax;
		$template->limit = $this->value;
		$template->limitForm = $this->limitForm;
		$template->limitLabel = $this->limitLabel;
		$template->setFile($this->getTemplateFile());
		$template->registerFilter('CurlyBracketsFilter::invoke');
		$template->render();
	}
}
<?php

abstract class ControlGettext extends Control
{
	protected $lang;
	
	public function __construct(IComponentContainer $parent, $name)
	{
		parent::__construct($parent, $name);
		
		$this->lang = $this->getPresenter()->lang;
	}
		
	protected function createTemplate()
	{
		$template = parent::createTemplate();
		$template->registerHelper('translate', 'helpersCore::gettext');
		return $template;
	}
}
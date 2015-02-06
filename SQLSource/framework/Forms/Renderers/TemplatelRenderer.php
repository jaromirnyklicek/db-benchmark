<?php
/**
 * Converts a Form into the HTML output by Template.
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */


class TemplateRenderer extends /*Nette\*/object implements IFormRenderer
{
	protected $file;

	protected $form;

	/** @var IFormRenderer */
	private $renderer;

	public function __construct($file = NULL)
	{
		$this->file = $file;
	}

	public function setFile($file)
	{
		$this->file = $file;
		return $this;
	}

	public function setForm(Form $form)
	{
		$this->form = $form;
		$this->getRenderer()->setForm($form);
		return $this;
	}

	public function getFile()
	{
		return $this->file;
	}

	/**
	 * I sablonovemu vykreslovani lze nastavit Renderer pro mody Body, End, Start...
	 * @param  IFormRenderer
	 * @return void
	 */
	public function setRenderer(IFormRenderer $renderer)
	{
		$this->renderer = $renderer;
	}

	/**
	 * Returns form renderer.
	 * @return IFormRenderer|NULL
	 */
	final public function getRenderer()
	{
		if ($this->renderer === NULL) {
			$this->renderer = new ConventionalRenderer;
		}
		return $this->renderer;
	}

	public function getClientScript()
	{
		return $this->getRenderer()->getClientScript();
	}

	public function setClientScript($scriptClass)
	{
		return $this->getRenderer()->setClientScript($scriptClass);
	}

	/**
	 * Provides complete form rendering. Pokud neni zadany mod, vygeneruje se cely form ze pres sablonu.
	 * V sablone lze volat render('body'), na to se pouzije ConventionalRenderer @see setRenderer()
	 * @param  Form
	 * @param  string
	 * @return string
	 */
	 function render(Form $form, $mode = NULL)
	 {
		if($mode != NULL && (!$form instanceof SubForm || $mode != 'body' )) {
			if($mode == 'subform') $mode = 'body';
			$renderer = $this->getRenderer();
			$renderer->clear();
			return $renderer->render($form, $mode);
		}
		else {
			$template = $this->createTemplate($form);
			$template->setFile($this->file);
			$template->presenter = $form->getPresenter();
			ob_start();
			$template->render();
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}

	public function renderErrors(IFormControl $control = NULL)
	{
		 return $this->getRenderer()->renderErrors($control);
	}

	protected function createTemplate($form)
	{
		$template = new /*Nette\Templates\*/Template();

		// default parameters
		$template->form = $form;
		$template->baseUri = /*Nette\*/Environment::getVariable('baseUri');
		$template->registerFilter('CurlyBracketsFilter::invoke');

		// default helpers
		$template->registerHelper('escape', 'Nette\Templates\TemplateHelpers::escapeHtml');
		$template->registerHelper('escapeJs', 'Nette\Templates\TemplateHelpers::escapeJs');
		$template->registerHelper('escapeCss', 'Nette\Templates\TemplateHelpers::escapeCss');
		$template->registerHelper('cache', 'Nette\Templates\CachingHelper::create');
		$template->registerHelper('snippet', 'Nette\Templates\SnippetHelper::create');
		$template->registerHelper('lower', 'Nette\String::lower');
		$template->registerHelper('upper', 'Nette\String::upper');
		$template->registerHelper('capitalize', 'Nette\String::capitalize');
		$template->registerHelper('stripTags', 'strip_tags');
		$template->registerHelper('strip', 'Nette\Templates\TemplateHelpers::strip');
		$template->registerHelper('date', 'Nette\Templates\TemplateHelpers::date');
		$template->registerHelper('nl2br', 'nl2br');
		$template->registerHelper('truncate', 'Nette\String::truncate');
		$template->registerHelper('bytes', 'Nette\TemplateHelpers::bytes');
		$template->registerHelper('translate', 'HelpersCore::gettext');

		return $template;
	}
}

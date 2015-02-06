<?php

/**
 * Lookup Text Control
 * Ajaxový vyhledávací control.
 * Narozdil od klasickeho lookupu neplni skryte ID ale pouze text, ktery lze editovat.
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class LookUpTextControl extends FormControl
{
	 /** @var string */
	private $forcedValue;

	protected $cols;
	private $sqllist;
	public $onSelectScript = '';
	protected $showAll = true;
	protected $height = 200;
	protected $width = 270;
	protected $action = 'ajax:lookup';

	public $ico_drop = '/img/core/ico/drop.gif';
	public $ico_reset = '/img/core/ico/reset.gif';

	public function __construct($label, $sqllist, $cols = 50)
	{
		parent::__construct($label);
		$this->control = /*Nette\Web\*/Html::el('input');
		$this->control->type = 'text';
		$this->label = /*Nette\Web\*/Html::el('label');
		$this->caption = $label;
		$this->cols = $cols;
		$this->sqllist = $sqllist;
		$this->rules = new Rules($this);
	}

	public function height($value = 200)
	{
		$this->height = $value;
	}

	public function width($value = 270)
	{
		$this->width = $value;
	}

	public function action($value)
	{
		$this->action = $value;
	}


	/**
	 * Sets control's value.
	 * @param  string
	 * @return void
	 */
	public function setValue($value)
	{
		parent::setValue(is_scalar($value) ? (string) $value : '');
	}


	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$i = $this->getHtmlName(true);
		if($this->lookup('Form') instanceof MultiForm) $i .= '-multi-';
		$namespace = Environment::getSession('controls');
		$namespace->$i = array(
				'sql' => $this->sqllist,
				'class' => $this->getclass(),
				);

		if(!$this->readonly) {
			$f = $this->getForm()->getMainForm();
			$onSelect = $this->onSelectScript;
			$url = $f->lookup('Presenter')->Link($this->action, array('id' => $i));
			$showAll = $this->showAll ? 'true' : 'false';
			$control = Html::el();
			$control->add(parent::getControl()
						->value($this->forcedValue === NULL ? $this->value : $this->forcedValue)
						->size($this->cols)
			);
			$resetJs = '';

			$control->add(Html::el('a')->add(Html::el('img')
					->class('dropdown')
					->src($this->ico_drop)
					->onclick('LookUpControl.url = \''.$url.'\';a = LookUpControl.findPosByObj(document.getElementById(\''.$this->getHtmlName().'\')); LookUpControl.Show({x: a[0], y: a[1] +20, control: \''.$this->getHtmlName().'\', width: '.$this->width.', height: '.$this->height.', showAll: '.$showAll.', onSelect: function(result){'.$onSelect.'}});')
					));

			if($this->ico_reset) {
				$control->add(Html::el('img')
						->class('dropdown')
						->src($this->ico_reset)
						->onclick($resetJs.'document.getElementById(\''.$this->getHtmlName().'\').value = \'\'; document.getElementById(\''.$this->getHtmlName().'\').value = \'\';')
						);
			}
		}
		else $control = Html::el()->add($title);
		return $control;
	}

	public function addLiveValidation($source)
	{
		$id = $this->getHtmlName();
		$id2 = $id;
		$c = Html::el()->add($source);
		$c->add(Html::el('span')->id($id.'_lv')->class('live'));
		$js = $this->getValidateScript();
		$invalidScript = '$(\'#'.$id2.'\').addClass(\'invalidInput\');$(\'#'.$id.'_lv\').html(\'<div>\' + res.message + \'</div>\')';
		$validScript = '$(\'#'.$id2.'\').removeClass(\'invalidInput\');$(\'#'.$id.'_lv\').html(\'\')';
		$onblur = '$(\'#'.$id2.'\').blur(function(){valid = function(){'.$js.'}; res=valid(); if(!res.ok){'.$invalidScript.'} else {'.$validScript.'}})';
		$c->add(Html::el('script')->type('text/javascript')->setHtml($onblur));
		return $c;
	}

	/**
	* Ajaxovy minilist. Najde zaznamy v databazi a vrati HTML text k zobrazeni
	*
	* @param mixed $option
	* @param mixed $text
	* @param mixed $presenter
	*/
	public static function ajaxlist($option, $text, $presenter)
	{
		$sql = $option['sql'];
		$sql = sprintf($sql, 'LIKE "%'.Database::instance()->escape_str($text).'%"');
		$list = sql::toPairs($sql);
		$xml = '<table id="lookuplist" width="100%" class="lookuplist" cellspacing="0">';
		$i=0;
		$js = '';
		foreach($list as $key => $value) {
			 $value2 = htmlspecialchars($value);
			 if(!empty($text)) $value2 = preg_replace('/('.$text.')/i', '<b>\\1</b>', $value);
			 $res = array(
					'id' => str_replace("\n",'',$value)
			 );
			 $res = (json_encode($res));
			 $xml .= '<tr><td '.($i % 2 == 0 ? 'class="odd"' : '').'><a href="#" id="c'.$i.'">'.$value2.'</a></td></tr>';
			 $js .= 'document.getElementById(\'c'.$i.'\').onclick=function(){LookUpControl.Update('.$res.'); return false};';
			 $i++;
		}
		$xml .= '</table>';
		$xml .= '<script>'.$js.'</script>';
		return $xml;
	}

   public function sqlWhere()
   {
		$column = $this->getOption('sql');
		$value = $this->getValue();
		if(empty($value)) return null;
		if($this->collate !== NULL) $column .= ' COLLATE '.$this->collate;
		$s = $column.' = "'.Database::instance()->escape_str($value).'"';
		return '('.$s.')';
   }

   public function getJavascript()
   {
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri.'js/core/lookup.js';
		return array_merge(parent::getJavascript(), $js);
   }

	public function getCSS()
	{
		$baseUri = Environment::getVariable('baseUri');
		$css = array();
		$css[] = $baseUri.'css/core/lookup.css';
		return array_merge(parent::getCSS(), $css);
	}

}

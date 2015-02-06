<?php

/**
 * Lookup control
 * Ajaxový vyhledávací control.
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */
class LookUpControl extends FormControl
{
	/** @var string */
	protected $forcedValue;

	/** sirka textoveho inputu * */
	protected $cols;

	/** SQL pro nacteni teztu polozky * */
	protected $sql;

	/** SQL pro zorbazni seznamu ajaxem * */
	protected $sqllist;
	protected $nullable = true;
	protected $info = false;

	/** provede javascript po vybrani polozky. Lze pristoupik k objektu result.
	  Example: $onSelectScript = "alert(result.id)"	* */
	public $onSelectScript = '';

	/** Zobrazi vsechny polozku bez vyberu prvniho pismenka. Pro versi seznamy se doporucuje vypnout * */
	protected $showAll = TRUE;
	protected $height = 200;
	protected $cbwidth = 270;

	/**
	 * Url pro ajaxovy pozadavek, lze javascriptem pred volanim upravit
	 * @example Navazani na jiny input: 'function(url) { return url + "&parent=" + $("#element").val()}'
	 *
	 * @var string
	 */
	protected $constructUrlCallback;

	/** ajaxovy handler * */
	protected $action = 'ajax:lookup';

	/** link na editace polozky * */
	protected $link;

	/** Automaticky submit formulare po vyberu polozky * */
	protected $autoSubmit = FALSE;
	protected $autoSubmitAjax = FALSE;
	public $ico_new = 'img/core/ico/newpage.gif';
	public $ico_edit = 'img/core/ico/pencil.gif';
	public $ico_info = 'img/core/ico/info.gif';
	public $ico_drop = 'img/core/ico/drop.gif';
	public $ico_reset = 'img/core/ico/reset.gif';


	public function __construct($label, $sql, $sqllist, $cols = 50)
	{
		parent::__construct($label);
		$this->control = /* Nette\Web\ */Html::el('input');
		$this->control->type = 'hidden';
		$this->label = /* Nette\Web\ */Html::el('label');
		$this->caption = $label;
		$this->cols = $cols;
		$this->sql = $sql;
		$this->sqllist = $sqllist;
		$this->rules = new Rules($this);

		$baseUri = Environment::getVariable('baseUri');
		$this->ico_new = $baseUri . $this->ico_new;
		$this->ico_edit = $baseUri . $this->ico_edit;
		$this->ico_info = $baseUri . $this->ico_info;
		$this->ico_drop = $baseUri . $this->ico_drop;
		$this->ico_reset = $baseUri . $this->ico_reset;
	}


	/** deprecated * */
	public function nullable($value = true)
	{
		$this->nullable = $value;
	}


	public function setNullable($value = true)
	{
		$this->nullable = $value;
		return $this;
	}


	public function disableDropIcon()
	{
		$this->ico_drop = NULL;
		return $this;
	}


	/** deprecated * */
	public function link($value)
	{
		$this->link = $value;
	}


	public function setEditLink($value)
	{
		$this->link = $value;
		return $this;
	}


	public function height($value = 200)
	{
		$this->height = $value;
		return $this;
	}


	public function cbwidth($value = 270)
	{
		$this->cbwidth = $value;
		return $this;
	}


	public function action($value)
	{
		$this->action = $value;
	}


	public function setAutoSubmit($value = TRUE, $ajax = FALSE)
	{
		$this->autoSubmit = $value;
		$this->autoSubmitAjax = $ajax;
		return $this;
	}


	/**
	 * Sets control's value.
	 * @param  string
	 * @return void
	 */
	public function setValue($value)
	{
		return parent::setValue(is_scalar($value) ? (string) $value : '');
	}


	public function setValueIn($value)
	{
		if ($value) {
			$sql = sprintf($this->sql, $value);
			if ($sql) {
				$exists = sql::toRow($sql);
				if ($exists) {
					$this->value = $value;
				}
			} else {
				$this->value = $value;
			}
		} else {
			$this->value = $value;
		}
	}


	public function setConstructUrlCallback($value)
	{
		$this->constructUrlCallback = $value;
		return $this;
	}


	public function getConstructUrlCallback($value)
	{
		return $this->constructUrlCallback;
	}


	protected function getTitle()
	{
		$sql = sprintf($this->sql, $this->getValue());
		$title = sql::toScalar($sql);
		return $title;
	}


	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$i = $this->getHtmlName(true);
		if ($this->lookup('Form') instanceof MultiForm) {
			$i .= '-multi-';
		}
		$namespace = Environment::getSession('controls');
		$namespace->$i = array(
			'sql' => $this->sqllist,
			'class' => $this->getclass(),
			'autosubmit' => $this->autoSubmit,
			'link' => $this->link
		);

		if (!empty($this->value)) {
			$title = $this->getTitle();
		} else {
			$title = '';
		}
		if (!$this->readonly) {
			if ($this->nullable) {
				$this->cols -= 4;
			}
			if (!empty($this->link)) {
				$this->cols -= 4;
			}
			if ($this->info) {
				$this->cols -= 4;
			}
			if (empty($this->ico_drop)) {
				$this->cols += 4;
			}

			$f = $this->getForm()->getMainForm();
			$onSelect = $this->onSelectScript;
			$url = $f->lookup('Presenter')->Link($this->action, array('id' => $i));
			$link = $f->lookup('Presenter')->Link($this->link, array('id' => $this->value));
			$newLink = $f->lookup('Presenter')->Link($this->link, array('id' => 0));
			$callback = empty($this->constructUrlCallback) ? 'null' : $this->constructUrlCallback;

			$showAll = $this->showAll ? 'true' : 'false';
			$control = Html::el();
			$control->add(parent::getControl()->value($this->forcedValue === NULL ? $this->value : $this->forcedValue));
			$input = Html::el('input')
					->id($this->getHtmlName() . '_preview')
					->type('text')
					->readonly('readonly')
					->class($this->cssClass . ' lookupcontrol')
					->style($this->style)
					->value($title)
					->size($this->cols)
					->rel($url);
			if (!$this->isDisabled()) {
				$input->onclick('LookUpControl.constructUrlCallback = ' . $callback . '; LookUpControl.url = \'' . $url . '\'; a = LookUpControl.findPosByObj(this); LookUpControl.Show({x: a[0], y: a[1] +20, control: \'' . $this->getHtmlName() . '\', height: ' . $this->height . ', width: ' . $this->cbwidth . ',showAll: ' . $showAll . ', onSelect: function(result){' . $onSelect . '}});');
			}
			$control->add($input);

			$resetJs = '';
			if (!empty($this->link)) {
				$editImg = !empty($this->value) ? $this->ico_edit : $this->ico_new;
				$control->add(Html::el('a')->href($link)->target('_blank')->id($this->getHtmlName() . '_edit')
								->add(Html::el('img')
										->id($this->getHtmlName() . '_eimg')
										->class('lookupedit')
										->src($editImg)
								));
				$resetJs = 'document.getElementById(\'' . $this->getHtmlName() . '_eimg\').src = \'' . $this->ico_new . '\';document.getElementById(\'' . $this->getHtmlName() . '_edit\').href = \'' . $newLink . '\';';
			}
			if ($this->info) {
				$control->add(Html::el('img')
								->class('dropdown')
								->src($this->ico_info)
								->onclick('document.getElementById(\'' . $this->getHtmlName() . '_preview\').value = \'\'; document.getElementById(\'' . $this->getHtmlName() . '\').value = \'\';')
				);
			}
			if (!empty($this->ico_drop) && !$this->isDisabled()) {
				$control->add(Html::el('a')->add(Html::el('img')
										->class('dropdown')
										->src($this->ico_drop)
										->onclick('LookUpControl.constructUrlCallback = ' . $callback . '; LookUpControl.url = \'' . $url . '\';a = LookUpControl.findPosByObj(document.getElementById(\'' . $this->getHtmlName() . '_preview\')); LookUpControl.Show({x: a[0], y: a[1] +20, control: \'' . $this->getHtmlName() . '\', width: ' . $this->cbwidth . ', height: ' . $this->height . ', showAll: ' . $showAll . ', onSelect: function(result){' . $onSelect . '}});')
						));
			}
			if ($this->nullable && !$this->isDisabled()) {
				$autoSubmitJs = '';
				if ($this->autoSubmit) {
					$autoSubmitJs = 'document.getElementById(\'' . $this->getHtmlName() . '\').form.onsubmit()';
				}
				$control->add(Html::el('img')
								->class('dropdown')
								->src($this->ico_reset)
								->onclick($resetJs . 'document.getElementById(\'' . $this->getHtmlName() . '_preview\').value = \'\'; document.getElementById(\'' . $this->getHtmlName() . '\').value = \'\';' . $autoSubmitJs)
				);
			}
		} else {
			$control = Html::el()->add($title);
		}
		return $control;
	}


	public function addLiveValidation($source)
	{
		$id = $this->getHtmlName();
		$id2 = $id . '_preview';
		$c = Html::el()->add($source);
		$c->add(Html::el('span')->id($id . '_lv')->class('live'));
		$js = $this->getValidateScript();
		$invalidScript = '$(\'#' . $id2 . '\').addClass(\'invalidInput\');$(\'#' . $id . '_lv\').html(\'<div>\' + res.message + \'</div>\')';
		$validScript = '$(\'#' . $id2 . '\').removeClass(\'invalidInput\');$(\'#' . $id . '_lv\').html(\'\')';
		//$onblur = '$(\'#'.$id2.'\').blur(function(){valid = function(){'.$js.'}; res=valid(); if(!res.ok){'.$invalidScript.'} else {'.$validScript.'}})';
		//$c->add(Html::el('script')->type('text/javascript')->setHtml($onblur));
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
		$link = $option['link'];
		$autosubmit = $option['autosubmit'];
		$sql = sprintf($sql, 'LIKE "%' . Database::instance()->escape_str($text) . '%"');
		$list = sql::toArray($sql);
		$xml = '<table id="lookuplist" width="100%" class="lookuplist" cellspacing="0">';
		$i = 0;
		$js = '';
		foreach ($list as $row) {
			// zpetna kompatabilita
			if (!isset($row->title) || !isset($row->id)) {
				$row->id = current($row);
				$row->title = next($row);
			}

			$value2 = htmlspecialchars($row->title);
			if (!empty($text)) {
				$value2 = preg_replace('/(' . str_replace('/', '\/', preg_quote($text)) . ')/i', '<b>\\1</b>', $row->title);
			}
			$res = array(
				'id' => $row->id,
				'autosubmit' => (int) $autosubmit,
				'value' => str_replace("\n", '', $row->title)
			);
			if (!empty($link)) {
				$res['href'] = $presenter->Link($link, array('id' => $row->id));
			}
			$res = (json_encode($res));
			$xml .= '<tr><td ' . ($i % 2 == 0 ? 'class="odd"' : '') . '><a href="#" id="c' . $i . '">' . $value2 . '</a></td></tr>';
			$js .= 'document.getElementById(\'c' . $i . '\').onclick=function(){LookUpControl.Update(' . $res . '); return false};';
			$i++;
		}
		$xml .= '</table>';
		$xml .= '<script>' . $js . '</script>';
		return $xml;
	}


	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValue();
		if (empty($value)) {
			return NULL;
		}
		$s = $column . ' = "' . Database::instance()->escape_str($value) . '"';
		return '(' . $s . ')';
	}


	public function getTextValue()
	{
		$value = $this->getValue();
		if (empty($value)) {
			return NULL;
		}
		return $this->getTitle();
	}


	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri . 'js/core/lookup.js';
		return array_merge(parent::getJavascript(), $js);
	}


	public function getCSS()
	{
		$baseUri = Environment::getVariable('baseUri');
		$css = array();
		$css[] = $baseUri . 'css/core/lookup.css';
		return array_merge(parent::getCSS(), $css);
	}


	public function setWidth($value)
	{
		$c = 2;
		if (empty($this->ico_drop)) {
			$c--;
		}
		return parent::setWidth($value - 20 * $c);
	}

}

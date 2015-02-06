<?php
/**
 * Control pro zadání dvou datumů.
 * Do filtru dává podmínku aby datum bylo rovno nebo mezi zadanýma hodnotama
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 *
 * @todo i18n
 */

class DateFieldFromTo extends FormControl
{
	 /**
	* Jazykova verze kalendare
	*/
	public static $lang = 'cs';

	protected $value = array('from' => NULL, 'to' => NULL);

	protected $separator = ' ';
	protected $format = '%d.%m.%Y';

	/**
	 * @param  string  label
	 */
	public function __construct($label)
	{
		parent::__construct($label);
		$this->addRule(Form::REGEXP, _('Neplatný formát data!'), '/^([1-9]|0[1-9]|[12][0-9]|3[01])[\.]([1-9]|0[1-9]|1[012])[\.](19|20|18|21)?\d\d$/');
	}

	public function setSeparator($value)
	{
	   $this->separator = $value;
	   return $this;
	}

	public function setFormat($value)
	{
	   $this->format = $value;
	   return $this;
	}

	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
	   $id = $this->getId();
	   $js = 'Calendar.setup({
				  inputField  : "'.$id.'",		   // ID of the input field
				  ifFormat	  : "'.$this->format.'",	// the date format
				  button	  : "'.$id.'_img",		 // ID of the button
				  onClose	  : function(cal) {$("#'.$id.'").blur(); cal.hide();}
			   });
			  ';
		$v = $this->getValue();
		$this->rendered = true;
		$control = Html::el('input');
		$control->name = $this->getHtmlName();
		$control->disabled = $this->disabled;
		if(isset($this->cssClass)) $control->class = $this->cssClass;
		if(isset($this->style)) $control->style = $this->style;
		$control->id = $this->getId();
		$el = Html::el();
		$el->add($control
				->value($v['from'])
				->type('text')
				->size(12));
		$el->add('&nbsp;');
		$el->add(Html::el('img')
				->id($id.'_img')
				->class('calimg')
				->src(Environment::getVariable('baseUri').'img/core/ico/calendar.gif')
				->style('cursor:pointer; position: relative; left: 1px; top: 2px;'));
		$el->add(Html::el('script')->type('text/javascript')->setHtml($js));

		 $js = 'Calendar.setup({
				  inputField  : "'.$id.'_to",		  // ID of the input field
				  ifFormat	  : "'.$this->format.'",	// the date format
				  button	  : "'.$id.'_img2",		  // ID of the button
				  onClose	  : function(cal) {$("#'.$id.'_to").blur(); cal.hide();}
			   });
			  ';
		$control = Html::el('input');
		$control->name = $this->getHtmlName().'_to';
		$control->disabled = $this->disabled;
		if(isset($this->cssClass)) $control->class = $this->cssClass;
		if(isset($this->style)) $control->style = $this->style;
		$control->id = $this->getId().'_to';
		$el2 = Html::el();
		$el2->add($control
				->value($v['to'])
				->type('text')
				->size(12));
		$el2->add('&nbsp;');
		$el2->add(Html::el('img')
				->id($id.'_img2')
				->class('calimg')
				->src(Environment::getVariable('baseUri').'img/core/ico/calendar.gif')
				->style('cursor:pointer; position: relative; left: 1px; top: 2px;'));
		$el2->add(Html::el('script')->type('text/javascript')->setHtml($js));

		$labels = $this->getLabels();
		return $labels['from'] . $el . $this->separator . $labels['to'] . $el2;
	}


	protected function getLabels()
	{
		return array('from' => _('od') . ' ', 'to' => _('do') . ' ');
	}


	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	public function loadHttpData($data)
	{
		$name = $this->getHtmlName();
		$from = isset($data[$name]) ? $data[$name] : NULL;
		$to = isset($data[$name.'_to']) ? $data[$name.'_to'] : NULL;
		$this->value = array('from' => $from, 'to' => $to);
	}

	public function setValueIn($value)
	{
		if($value == NULL) $value = $this->value;
		if(!isset($value['from'])) $value['from'] = '';
		if(!isset($value['to'])) $value['to'] = '';

		if(!$value['from'] instanceof Timestamp)  {
			$value['from'] = Timestamp::factory($value['from']);
		}
		if(!$value['to'] instanceof Timestamp)	{
			$value['to'] = Timestamp::factory($value['to']);
		}
		$format = str_replace('%', '', $this->format);
		if(!$value['from']->isNull())  {
			$value['from'] = date($format,	$value['from']->getAsTs());
		}
		else $value['from'] = '';
		if(!$value['to']->isNull())  {
			$value['to'] = date($format,  $value['to']->getAsTs());
		}
		else $value['to'] = '';
		$this->value = $value;
	}

	public function getValueOut()
	{
		$value = $this->getValue();
		if(!empty($value['from'])) {
			$v = Timestamp::factory($value['from']);
			if(($y = $v->getYear()) < 100) $v->setYear(($y > 29 ? 1900 : 2000) + $y);
			$value['from'] = $v->getAsIso();
		}
		if(!empty($value['to'])) {
			$v = Timestamp::factory($value['to']);
			if(($y = $v->getYear()) < 100) $v->setYear(($y > 29 ? 1900 : 2000) + $y);
			$value['to'] = $v->getAsIso();
		}
		return $value;
	}

	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri.'js/core/calendar/calendar.js';
		$js[] = $baseUri.'js/core/calendar/lang/calendar-'.self::$lang.'.js';
		$js[] = $baseUri.'js/core/calendar/calendar-setup.js';
		return array_merge(parent::getJavascript(), $js);
	}

	public function getCSS()
	{
		$baseUri = Environment::getVariable('baseUri');
		$css = array();
		$css[] = $baseUri.'js/core/calendar/calendar-blue.css';
		return array_merge(parent::getCSS(), $css);
	}

	/**
	 * Regular expression validator: matches control's value regular expression?
	 * @param  TextBase
	 * @param  string
	 * @return bool
	 */
	public static function validateRegexp(FormControl $control, $regexp)
	{
		$v = $control->getValue();
		return (preg_match($regexp, $v['from']) || empty($v['from'])) && (preg_match($regexp, $v['to']) || empty($v['to']));
	}

	public static function validateJSRegexp(FormControl $control, $arg)
	{
		if (strncmp($arg, '/', 1)) {
				throw new /*\*/InvalidStateException("Regular expression '$arg' must be JavaScript compatible.");
			}
		$js = '';
		$js .= "element = document.getElementById('" . $control->getId() . "');\n\t";
		$js .= "if(element != undefined) {
				var val = element.value.replace(/^\\s+/, '').replace(/\\s+\$/, '');\n\t";
		$js .= "res = val=='' || $arg.test(val);";
		$js .= "if(res) { element = document.getElementById('" . $control->getId() . "_to');\n\t";
		$js .= "var val = element.value.replace(/^\\s+/, '').replace(/\\s+\$/, '');\n\t";
		$js .= "res = val=='' || $arg.test(val); }
				}
				else res = true;";
		return $js;
	}

	public function addLiveValidation($source)
	{
		if(!$this->liveValidation) return $source;
		$c = Html::el('div')->add($source);
		$id = $this->getId();
		$c->id($id.'_c');
		$js = $this->getValidateScript();
		$onblur  = '$(\'#'.$id.'\').blur(function(){var valid = function(){'.$js.'}; res=valid(); if(!res.ok){$("#'.$id.'_c").invalid(res)} else {$("#'.$id.'_c").valid(res)}});';
		$onblur .= '$(\'#'.$id.'_to\').blur(function(){var valid = function(){'.$js.'}; res=valid(); if(!res.ok){$("#'.$id.'_c").invalid(res)} else {$("#'.$id.'_c").valid(res)}});';
		$c->add(Html::el('script')->type('text/javascript')->setHtml($onblur));
		return $c;
	}

	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValueOut();
		if(empty($column) || (empty($value['to']) && empty($value['from']))) return NULL;
		if(!is_array($column)) $column = array($column);

		$s = array();
		foreach($column as $col) {
			$c = array();
			if(!empty($value['from'])) $c[] = ''.$col.' >= "'.Database::instance()->escape_str($value['from']).'"';
			if(!empty($value['to'])) $c[] = ''.$col.' <= "'.Database::instance()->escape_str($value['to']).' 23:59:59"';
			$s[] = '('.join(' AND ', $c).')';
		}
		return '('.join(' OR ', $s).')';
	}

	public function getTextValue()
	{
		$value = $this->getValue();
		if(empty($value['to']) && empty($value['from'])) return NULL;

		$s = '';
		if(!empty($value['from'])) $s .= 'od '.$value['from'].' ';
		if(!empty($value['to'])) $s .= 'do '.$value['to'];

		return $s;
	}
}

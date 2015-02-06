<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license	Nette license
 * @link	   http://nettephp.com
 * @category   Nette
 * @package    Nette\Forms
 * @version    $Id: FormControl.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/

/**
 * Base class that implements the basic functionality common to form controls.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
abstract class FormControl extends /*Nette\*/Component implements IFormControl
{
	/** @var string */
	public static $idMask = '%s';

	/** @var string textual caption or label */
	public $caption;

	/** @var mixed unfiltered control value */
	protected $value;

	/** @var Nette\Web\Html  control element template */
	protected $control;

	/** @var Nette\Web\Html  label element template */
	protected $label;

	/** @var array */
	private $errors = array();

	/** @var bool */
	private $disabled = FALSE;

	/** @var int */
	protected $tabIndex;

	/** @var string */
	private $htmlId;

	/** @var string */
	private $htmlName;

	/** @var Rules */
	private $rules;

	/** @var array user options */
	private $options = array();

	/** @var string */
	protected $rendered = FALSE;

	/** @var vychozi hodnota */
	protected $valueDefault;

	/** reference na subform, do ktereho control patri **/
	protected $parentSubFormMulti;

	/** css trida controlu **/
	protected $cssClass = 'input';

	/** styl controlu **/
	protected $style;

	/** maska pro obaleni controlu pres funkci sprintf **/
	protected $envelope = '%s';

	/** control pouze pro cteni **/
	protected $readonly = FALSE;

	/** delka textoveho inputu	**/
	protected $length;

	/** reference na objekt s rozhranim ITip **/
	protected $help;

	/** Live validace controlu **/
	protected $liveValidation = TRUE;

	/** control je videt pri renderovani rendererem **/
	protected $visible = TRUE;

	/** control je videt v novem subformu pridany javascriptem **/
	protected $newVisible = TRUE;

	/** temp hodnota, pouziva prii generovani js subformu **/
	public $tmpVisible;

	/** velikost pro css style width */
	protected $width;

	/** pokud se input neobjevi v requestu, tak defaultne nastavuje na NULL */
	protected $nullIfNotSent = FALSE;

	/** Porovnani pro SQL where **/
	protected $collate = NULL;

	/**
	 * @param  string  label
	 */
	public function __construct($label = '')
	{
		parent::__construct();
		$this->control = /* Nette\Web\ */Html::el('input');
		$this->label = /* Nette\Web\ */Html::el('label');
		$this->caption = $label;
		$this->rules = new Rules($this);
	}


	public function setNewVisible($value)
	{
		$this->newVisible = $value;
		return $this;
	}


	public function setVisible($value)
	{
		$this->visible = $value;
		return $this;
	}


	public function getNewVisible()
	{
		return $this->newVisible;
	}


	public function getVisible()
	{
		return $this->visible;
	}


	public function setCssClass($value)
	{
		$this->cssClass = $value;
		return $this;
	}


	public function getCssClass()
	{
		return $this->cssClass;
	}


	public function addCssClass($value)
	{
		$classes = explode(" ", $this->cssClass);
		if (!in_array($value, $classes)) {
			$classes[] = $value;
		}

		$this->cssClass = implode(" ", $classes);
		return $this;
	}


	public function removeCssClass($value)
	{
		$classes = explode(" ", $this->cssClass);
		if (in_array($value, $classes)) {
			unset($classes[array_search($value, $classes)]);
		}

		$this->cssClass = implode(" ", $classes);
		return $this;
	}


	public function setHelp($value)
	{
		$this->help = $value;
		return $this;
	}


	public function getHelp()
	{
		return $this->help;
	}


	public function setStyle($value)
	{
		$this->style = $value;
		return $this;
	}


	public function getStyle()
	{
		return $this->style;
	}


	public function setEnvelope($value)
	{
		$this->envelope = $value;
		return $this;
	}


	public function getEnvelope()
	{
		return $this->envelope;
	}


	/**
	 * Alias k setEnvelope
	 *
	 * @param mixed $value
	 */
	public function setWrapper($value)
	{
		return $this->setEnvelope($value);
	}


	/**
	 * Alias k getEnvelope
	 *
	 */
	public function getWrapper()
	{
		return $this->getEnvelope();
	}


	public function setLength($value)
	{
		$this->length = $value;
		return $this;
	}


	public function getLength()
	{
		return $this->length;
	}


	public function setReadonly($value = TRUE)
	{
		$this->readonly = $value;
		return $this;
	}


	public function getReadonly()
	{
		return $this->readonly;
	}


	public function setWidth($width)
	{
		$this->width = $width;
		return $this;
	}


	public function getWidth()
	{
		return $this->width;
	}


	public function setTabIndex($value)
	{
		$this->tabIndex = $value;
		return $this;
	}


	public function getTabIndex()
	{
		return $this->tabIndex;
	}


	public function setSql($value)
	{
		return $this->setOption('sql', $value);
	}


	public function sql($value)
	{
		return $this->setSql($value);
	}


	public function getSql()
	{
		return $this->getOption('sql');
	}


	public function setLiveValidation($value)
	{
		$this->liveValidation = $value;
		return $this;
	}


	public function getLiveValidation($value)
	{
		return $this->liveValidation;
	}


	public function setNullIfNotSent($value)
	{
		$this->nullIfNotSent = $value;
		return $this;
	}


	public function getNullIfNotSent($value)
	{
		return $this->nullIfNotSent;
	}


	/**
	 * Sets control's default value.
	 * @param  mixed
	 * @return FormControl
	 */
	public function setDefaultValue($value)
	{
		$this->valueDefault = $value;
		return $this;
	}


	public function getDefaultValue()
	{
		return $this->valueDefault;
	}


	/**
	 * Pridani validacnich pravidel. Pouziva se pri naklonovani controlu
	 * @param  Rules
	 * @return void
	 */
	public function setRules($rules)
	{
		$this->rules = $rules;
	}


	/**
	 * Overloaded parent setter. This method checks for invalid control name.
	 * @param  IComponentContainer
	 * @param  string
	 * @return void
	 */
	public function setParent(/* Nette\ */IComponentContainer $parent = NULL, $name = NULL)
	{
		if ($name === 'submit') {
			throw new /* \ */InvalidArgumentException("Name 'submit' is not allowed due to JavaScript limitations.");
		}
		parent::setParent($parent, $name);
	}


	/**
	 * Reference na subform v multiformu.
	 *
	 * @param SubForm $parent
	 */
	public function setParentMulti($parent)
	{
		$this->parentSubFormMulti = $parent;
	}


	/**
	 * Returns form.
	 * @param  bool   throw exception if form doesn't exist?
	 * @return Form
	 */
	public function getForm($need = TRUE)
	{
		return $this->lookup('Nette\Forms\Form', $need);
	}


	/**
	 * Returns form.
	 * @return Form
	 */
	protected function getMultiForm()
	{
		return $this->parentSubFormMulti;
	}


	/**
	 * Vrati subform, do ktereho control patri.
	 *
	 */
	public function getSubForm()
	{
		$multi = $this->getMultiForm();
		if ($multi) {
			return $multi;
		} else {
			return $this->getForm();
		}
	}


	/**
	 * Returns name of control within a Form & INamingContainer scope.
	 *
	 * @param simple - pokud chcem jem nazev bez pridani _id_, ktery se dava pro controly v multi formu
	 * @return string
	 */
	public function getHtmlName($simple = FALSE)
	{
		$this->htmlName = NULL;
		$s = '';
		$m = '';
		$formName = $this->getForm()->getName();
		$name = $this->getName();
		$multi = $this->getMultiForm(FALSE);
		if ($multi) {
			if (!$simple) {
				$m = '_' . $multi->getFormId() . '_';
			}
			$formName = $multi->getFormName();
		}
		$obj = $this->lookup('Nette\Forms\INamingContainer', TRUE);
		while (!($obj instanceof Form)) {
			$s = "____$name$s";
			$name = $obj->getName();
			$obj = $obj->lookup('Nette\Forms\INamingContainer', TRUE);
		}
		$this->htmlName = "$formName" . '_' . "$name$s$m";
		return $this->htmlName;
	}


	/**
	 * Changes control's HTML id.
	 * @param  string new ID, or FALSE or NULL
	 * @return void
	 */
	public function setId($id)
	{
		$this->htmlId = $id;
	}


	/**
	 * Returns control's HTML id.
	 * @return string
	 */
	public function getId()
	{
		$this->htmlId = NULL;
		$this->htmlId = sprintf(self::$idMask, $this->getHtmlName());
		return $this->htmlId;
	}


	/**
	 * Sets user-specific option.
	 *
	 * Common options:
	 * - 'rendered' - indicate if method getControl() have been called
	 * - 'required' - indicate if ':required' rule has been applied
	 * - 'description' - textual or Html object description (recognized by ConventionalRenderer)
	 *
	 * @param  string key
	 * @param  mixed  value
	 * @return FormControl	provides a fluent interface
	 */
	public function setOption($key, $value)
	{
		if ($value === NULL) {
			unset($this->options[$key]);
		} else {
			$this->options[$key] = $value;
		}
		return $this;
	}


	/**
	 * Returns user-specific option.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	public function getOption($key, $default = NULL)
	{
		return isset($this->options[$key]) ? $this->options[$key] : $default;
	}


	/**
	 * Returns user-specific options.
	 * @return array
	 */
	final public function getOptions()
	{
		return $this->options;
	}

	/*	 * ******************* interface IFormControl ****************d*g* */


	/**
	 * Sets control's value.
	 * @param  mixed
	 * @return void
	 */
	public function setValue($value)
	{
		$this->value = $value;
		$this->valueDefault = $value;
		return $this;
	}


	/**
	 * Returns control's value.
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}


	/** vyuzite pri dedeni.
	 * 	Lze vystup konvertovat.
	 */
	public function getValueOut()
	{
		return $this->getValue();
	}

	/*
	  Textova citelna user-friedly hodnota pro zobrazeni uzivateli, kdyz treba filtr nevidi.
	 */


	public function getTextValue()
	{
		return NULL;
	}


	/** vyuzite pri dedeni.
	 * 	Lze vstup konvertovat.
	 */
	public function setValueIn($value)
	{
		$this->value = $value;
	}


	/**
	 * Sets control's value by default value.
	 * @param  mixed
	 * @return void
	 */
	public function loadDefaultValue()
	{
		$this->setValueIn($this->valueDefault);
	}


	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	public function loadHttpData($data)
	{
		$name = $this->getHtmlName();
		$this->value = isset($data[$name]) ? $data[$name] : ($this->nullIfNotSent ? NULL : $this->value);
	}


	/**
	 * Disables or enables control.
	 * @param  bool
	 * @return FormControl	provides a fluent interface
	 */
	public function setDisabled($value = TRUE)
	{
		$this->disabled = (bool) $value;
		return $this;
	}


	/**
	 * Is control disabled?
	 * @return bool
	 */
	public function isDisabled()
	{
		return $this->disabled;
	}

	/*	 * ******************* rendering ****************d*g* */


	/**
	 * Generates control's HTML element.
	 * Vygeneruje pouze hlavni control.
	 * K nemu se pak pozdeji prida live validace, envelope	a napoveda
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$this->rendered = TRUE;
		$control = $this->control;
		$control->name = $this->getHtmlName();
		$control->disabled = $this->disabled;
		$control->readonly = $this->readonly;
		$control->tabIndex = $this->tabIndex;
		if (isset($this->cssClass)) {
			$control->class = $this->cssClass;
		}
		if ($this->width != NULL) {
			if (!preg_match('/([^-]|^)width:/i', $this->style)) {
				$this->style .= ';width:' . $this->width . 'px';
			}
		}
		if (isset($this->style)) {
			$control->style = $this->style;
		}
		$control->id = $this->getId();
		return $control;
	}


	/**
	 * Prevede XHTML envelope na vicerozmerne pole
	 *
	 * @param mixed $node
	 */
	function xmlToArray($node)
	{
		$res = array();
		if ($node->nodeType == XML_TEXT_NODE) {
			$res = $node->nodeValue;
		} else {
			if ($node->hasAttributes()) {
				$attributes = $node->attributes;
				if (!is_null($attributes)) {
					$res['@attributes'] = array();
					foreach ($attributes as $index => $attr) {
						$res['@attributes'][$attr->name] = $attr->value;
					}
				}
			}
			if ($node->hasChildNodes()) {
				$children = $node->childNodes;
				for ($i = 0; $i < $children->length; $i++) {
					$child = $children->item($i);
					$res['childs'][] = array('el' => $child->nodeName, 'content' => $this->xmlToArray($child));
				}
			}
		}
		return $res;
	}


	/**
	 * Prevede vicerozmerne pole na Nette\Html objekt, kde za %s dosadi Control
	 *
	 * @param array $arr
	 * @param Html $control
	 */
	function arr2html($arr, $control)
	{
		$el = Html::el();
		foreach ($arr as $e => $item) {
			if ($item['el'] == '@attributes') {
				continue;
			}
			if ($item['el'] == '#text') {
				if (strpos($item['content'], '%s') !== false) {
					$a = explode('%s', $item['content']);
					$el->add($a[0]);
					$el->add($control);
					$el->add($a[1]);
				} else {
					$el->add($item['content']);
				}
			} else {
				$n = Html::el($item['el'], isset($item['content']['@attributes']) ? $item['content']['@attributes'] : '');
				$el->add($n);
				if (!empty($item['content']['childs'])) {
					$n->add($this->arr2html($item['content']['childs'], $control));
				}
			}
		}
		return $el;
	}


	/**
	 * Obali control do $envelope
	 *
	 */
	final public function getControlWithEnvelope()
	{
		// zakladi obalka, neni treba nic parsovat
		if ($this->envelope == '%s') {
			return $this->getControl();
		}

		// prevedeni $envelope na DOM a sestaveni noveho Html objektu
		$dom = new DOMDocument();
		$dom->loadXML('<env>' . htmlspecialchars($this->envelope) . '</env>');
		$envArr = $this->xmlToArray($dom);
		$control = $this->arr2html($envArr['childs'][0]['content']['childs'], $this->getControl());
		return $control;
	}


	/**
	 * Vygenereuje kompletni zdroj jako Nette\Html objekt.
	 * Obsahuje envelope, napovedu s pridanim live validace
	 */
	public function getSource()
	{
		if ($this->help == NULL) {
			$source = $this->getControlWithEnvelope();
		} else {
			$source = $this->help->wrap($this);
		}
		return $this->addLiveValidation($source);
	}


	/**
	 * Prida do controlu Live validaci
	 *
	 * @param Html $source
	 * @return Html
	 */
	public function addLiveValidation($source)
	{
		if (!$this->liveValidation) {
			return $source;
		}

		$id = $this->getId();
		$c = Html::el()->add($source);
		$js = $this->getValidateScript();

		$click = '$(\'#' . $id . '\').click(function(){
			var type = $(this).attr("type");
			if (type == "checkbox" || type == "radio") {
				$(this).focus();
			}
		});';

		$onblur = '$(\'#' . $id . '\').blur(function(){var valid = function(){' . $js . '}; res=valid(); if(!res.ok){$(this).invalid(res)} else {$(this).valid(res)}})';
		$c->add(Html::el('script')->type('text/javascript')->setHtml('/* <![CDATA[ */ ' . $click . $onblur . ' /*]]>*/'));

		$js = $this->getIsRequiredScript();
		if ($js) {
			$onblur = '$("#' . $this->getId() . '").data("isRequiredFnc", function(){' . $js . '})';
			$c->add(Html::el('script')->type('text/javascript')->setHtml('/* <![CDATA[ */ ' . $click . $onblur . ' /*]]>*/'));
		}
		return $c;
	}


	/**
	 * Generates label's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getLabel()
	{
		$label = clone $this->label;
		$label->for = $this->getId();
		$text = $this->caption;
		if (is_string($text) || $text === NULL) {
			$label->setHtml($text);
		} else {
			$label->add($text);
		}
		return $label;
	}


	/**
	 * Returns control's HTML element template.
	 * @return Nette\Web\Html
	 */
	public function getControlPrototype()
	{
		return $this->control;
	}


	/**
	 * Returns label's HTML element template.
	 * @return Nette\Web\Html
	 */
	public function getLabelPrototype()
	{
		return $this->label;
	}


	/**
	 * Sets 'rendered' indicator.
	 * @param  bool
	 * @return FormControl	provides a fluent interface
	 */
	public function setRendered($value = TRUE)
	{
		$this->rendered = $value;
		return $this;
	}


	/**
	 * Does method getControl() have been called?
	 * @return bool
	 */
	public function isRendered()
	{
		return $this->rendered;
	}

	/*	 * ******************* rules ****************d*g* */


	/**
	 * Adds a validation rule.
	 * @param  mixed	  rule type
	 * @param  string	  message to display for invalid data
	 * @param  mixed	  optional rule arguments
	 * @return FormContainer  provides a fluent interface
	 */
	public function addRule($operation, $message = NULL, $arg = NULL)
	{
		if ($message == NULL && $operation == Form::FILLED) {
			$message = _('Vyplňte povinnou položku') . ': ' . $this->caption;
		}
		$this->rules->addRule($operation, $message, $arg);
		return $this;
	}


	/**
	 * Adds a validation condition a returns new branch.
	 * @param  mixed	 condition type
	 * @param  mixed	  optional condition arguments
	 * @return Rules	  new branch
	 */
	public function addCondition($operation, $value = NULL)
	{
		return $this->rules->addCondition($operation, $value);
	}


	/**
	 * Adds a validation condition based on another control a returns new branch.
	 * @param  IFormControl form control
	 * @param  mixed	  condition type
	 * @param  mixed	  optional condition arguments
	 * @return Rules	  new branch
	 */
	public function addConditionOn(IFormControl $control, $operation, $value = NULL)
	{
		return $this->rules->addConditionOn($control, $operation, $value);
	}


	/**
	 * @return Rules
	 */
	final public function getRules()
	{
		return $this->rules;
	}


	/**
	 * Makes control mandatory.
	 * @param  string  error message
	 * @return FormControl	provides a fluent interface
	 */
	public function setRequired($message = NULL)
	{
		if ($message == NULL) {
			$message = _('Vyplňte povinnou položku') . ': ' . str_replace('%', '%%', strip_tags($this->caption));
		}
		$this->rules->addRule(':Filled', $message);
		return $this;
	}


	/**
	 * Is control mandatory?
	 * @return bool
	 */
	final public function isRequired()
	{
		return !empty($this->options['required']);
	}


	/**
	 * New rule or condition notification callback.
	 * @param  Rule
	 * @return void
	 */
	public function notifyRule(Rule $rule)
	{
		if (is_string($rule->operation) && strcasecmp($rule->operation, ':filled') === 0) {
			$this->setOption('required', TRUE);
		}
	}

	/*	 * ******************* validation ****************d*g* */


	/**
	 * Equal validator: are control's value and second parameter equal?
	 * @param  IFormControl
	 * @param  mixed
	 * @return bool
	 */
	public static function validateEqual(IFormControl $control, $arg)
	{
		$value = $control->getValue();
		foreach ((is_array($arg) ? $arg : array($arg)) as $item) {
			if (is_object($item)) {
				$name = $item->getName();
				$form = $control->getSubForm();
				$item = $form[$name];
				//if (get_class($item) === get_class($control) && $value == $item->value) return TRUE; // intentionally ==
				if ($value == $item->value) {
					return TRUE;
				} // intentionally ==
			} else {
				if ($value == $item) {
					return TRUE;
				} // intentionally ==
			}
		}
		return FALSE;
	}


	public static function validateJSEqual(IFormControl $control, $arg)
	{
		$js = $control->validateJsBase();
		$tmp3 = array();
		foreach ((is_array($arg) ? $arg : array($arg)) as $item) {
			if (is_object($item)) { // compare with another form control?
				$name = $item->getName();
				$form = $control->getSubForm();
				$item = $form[$name];
				/* $tmp3[] = get_class($item) === $control->getClass()
				  ? "val==document.getElementById('" . $item->getId() . "').value" // missing trim
				  : 'false'; */
				$tmp3[] = "val==document.getElementById('" . $item->getId() . "').value";
			} else {
				$tmp3[] = "val==" . json_encode((string) $item);
			}
		}
		return $js . " if(val != undefined) res = (" . implode(' || ', $tmp3) . ");";
	}


	/**
	 * Filled validator: is control filled?
	 * @param  IFormControl
	 * @return bool
	 */
	public static function validateFilled(IFormControl $control)
	{
		return (string) $control->getValue() !== ''; // NULL, FALSE, '' ==> FALSE
	}


	public static function validateJSFilled(IFormControl $control)
	{
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = val!='';";
		return $js;
	}


	/**
	 * Valid validator: is control valid?
	 * @param  IFormControl
	 * @return bool
	 */
	public static function validateValid(IFormControl $control)
	{
		return $control->rules->validate(TRUE);
	}


	public static function validateJSValid(IFormControl $control, $instantClientScript)
	{
		$js = $control->validateJsBase();
		$js .= "if(val != undefined) res = function(){\n\t" . $instantClientScript->getValidateScript($control->getRules(), TRUE) . "return true; }();";
		return $js;
	}


	public function validateJsBase()
	{
		$tmp = "element = document.getElementById('" . $this->getId() . "');\n\t;";
		$tmp .= "if(element == undefined) {res = true; var val = undefined}\n\t";
		$tmp2 = "else var val = element.value.replace(/^\\s+/, '').replace(/\\s+\$/, '');\n\t";
		return $tmp . $tmp2;
	}


	/**
	 * Vráti javascript, ktery po provedeni vrati do promenne $res stav, jestli byl control zmenen od vychoziho stavu.
	 * Pouziva se pri odstranovani "prazdnych" subformu z multiformu.
	 */
	public function checkEmptyJs()
	{
		$js = $this->validateJsBase();
		$js .= "if(val != undefined) res = val == " . json_encode((string) $this->getDefaultValue()) . "; \n\t";
		return $js;
	}


	/**
	 * Adds error message to the list.
	 * @param  string  error message
	 * @return void
	 */
	public function addError($message)
	{
		if (!in_array($message, $this->errors, TRUE)) {
			$this->errors[] = $message;
			$this->getForm()->addError($message);
		}
	}


	public function getValidateScript()
	{
		$is = new InstantClientScript($this->getForm());
		return $is->getValidateScript($this->rules, true, true) . ' return {ok: true, message : \'\'}';
	}


	public function getIsRequiredScript()
	{
		$is = new InstantClientScript($this->getForm());
		$js = $is->getIsRequiredScript($this->rules, true, true);
		if ($js) {
			return $js . ' return res';
		}
	}


	/**
	 * Returns errors corresponding to control.
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}


	/**
	 * @return bool
	 */
	public function hasErrors()
	{
		return (bool) $this->errors;
	}


	/**
	 * @return void
	 */
	public function cleanErrors()
	{
		$this->errors = array();
	}


	public function getJavascript()
	{
		return array();
	}


	public function getCSS()
	{
		return array();
	}


	public function sqlWhere()
	{

	}


	/** nastaveni porovnani pro SQL WHERE * */
	public function setCollate($value = 'utf8_general_ci')
	{
		$this->collate = $value;
		return $this;
	}


	public function getCollate()
	{
		return $this->collate;
	}


	public function getCaption()
	{
		return $this->caption;
	}


	public function setCaption($caption)
	{
		$this->caption = $caption;
		return $this;
	}

}

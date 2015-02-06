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
 * @copyright  Copyright (c) 2004, 2009 David Grudl, Ondrej Novak
 * @license    http://nettephp.com/license	Nette license
 * @link	   http://nettephp.com
 * @category   Nette
 * @package    Nette\Forms
 * @version    $Id: Form.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/


/**
 * Creates, validates and renders HTML forms.
 *
 * @author	   David Grudl, Multiformy: Ondrej Novak
 */
class Form extends FormContainer
{
	/**#@+ operation name */
	const EQUAL = ':equal';
	const IS_IN = ':equal';
	const FILLED = ':filled';
	const VALID = ':valid';

	// button
	const SUBMITTED = ':submitted';

	// text
	const MIN_LENGTH = ':minLength';
	const MAX_LENGTH = ':maxLength';
	const LENGTH = ':length';
	const EMAIL = ':email';
	const URL = ':url';
	const REGEXP = ':regexp';
	const INTEGER = ':integer';
	const NUMERIC = ':integer';
	const FLOAT = ':float';
	const RANGE = ':range';
	const UNIQUE = ':unique';

	// file upload
	const MAX_FILE_SIZE = ':fileSize';
	const MIME_TYPE = ':mimeType';
	const EXTENSION = ':extension';

	// special case
	const SCRIPT = 'Nette\Forms\InstantClientScript::javascript';
	/**#@-*/

	/** tracker ID */
	const TRACKER_ID = '_form_';

	/** protection token ID */
	const PROTECTOR_ID = '_token_';

	/** @var array of event handlers; Occurs when the form is submitted and successfully validated; function(Form $sender) */
	public $onSubmit;

	/** @var array of event handlers; Occurs when the form is submitted and not validated; function(Form $sender) */
	public $onInvalidSubmit;

	/** @var bool */
	protected $isPost = TRUE;

	/** @var mixed */
	protected $submittedBy;

	/** @var Html  <form> element */
	private $element;

	/** @var IFormRenderer */
	private $renderer;


	/** @var array of FormGroup */
	private $groups = array();

	/** @var bool */
	private $isPopulated = FALSE;

	/** @var bool */
	private $valid;

	/** @var array */
	private $errors = array();

	/** @var array */
	private $encoding = 'UTF-8';

	public $useAjax = FALSE;

	protected  $checkUnsave = FALSE;

	/**
	* Navazany objekt implementujici rozhrani IFormData
	*/
	protected $bindObject;

	/** bylo volani render()? **/
	protected  $firstRender = FALSE;

	/** Štítek, tak ho známe z jiných prog. jazyků **/
	protected $tag;

	/**
	 * Pole multiformů
	 *
	 * @var array
	 */
	protected $multiArr = array();

	/**
	 * Form constructor.
	 */
	public function __construct($name = NULL, $parent = NULL)
	{
		$this->element = /*Nette\Web\*/Html::el('form');
		$this->element->action = ''; // RFC 1808 -> empty uri means 'this'
		$this->element->method = 'post';
		$this->element->name = $name;
		$this->element->id = $name;
		parent::__construct($parent, $name);
	}

	/**
	* Css class elementry <form>
	*
	* @param string $class
	*/
	public function setCssClass($class)
	{
		$this->element->class = $class;
	}


	/**
	* Vrati hlavni formular. Form je vzdy sam o sobe hlavnim formularem
	*
	*/
	public function getMainForm()
	{
		return $this;
	}

	public function getPresenter()
	{
		return $this->parent;
	}

	/**
	 * Vrati nazev multiformu
	 *
	 * @return string
	 */
	public function getFormName()
	{
		// je stejny jako nazev komponenty
		return $this->getName();
	}

	/**
	 * Sets form's action.
	 * @param  mixed URI
	 * @return void
	 */
	public function setAction($url)
	{
		$this->element->action = $url;
	}

	/**
	 * Returns form's action.
	 * @return mixed URI
	 */
	public function getAction()
	{
		return $this->element->action;
	}

	public function getCheckUnsave()
	{
		return $this->checkUnsave;
	}

	public function setCheckUnsave($value)
	{
		$this->checkUnsave = $value;
	}

	/**
	 * Sets form's method.
	 * @param  string get | post
	 * @return void
	 */
	public function setMethod($method)
	{
		$this->element->method = strtolower($method);
		$this->isPost = $this->element->method == 'post';
	}


	/**
	 * Returns form's method.
	 * @return string get | post
	 */
	public function getMethod()
	{
		return $this->element->method;
	}


	/**
	 * Adds distinguishing mark.
	 * @param  string
	 * @return void
	 */
	public function addTracker($name)
	{
		$this[self::TRACKER_ID] = new HiddenField($name);
	}


	 /**
	 * Navazany objekt implementujici rozhrani IFormData
	 */
	 /*public function bind($object)
	 {
		 $this->bindObject = $object;
	 }*/

	/**
	 * Cross-Site Request Forgery (CSRF) form protection.
	 * @param  string
	 * @param  int
	 * @return void
	 */
	public function addProtection($message = NULL, $timeout = NULL)
	{
		$session = $this->getSession()->getNamespace('Nette.Forms.Form/CSRF');
		$key = "key$timeout";
		if (isset($session->$key)) {
			$token = $session->$key;
		} else {
			$session->$key = $token = md5(uniqid('', TRUE));
		}
		$session->setExpiration($timeout, $key);
		$this[self::PROTECTOR_ID] = new HiddenField($token);
		$this[self::PROTECTOR_ID]->addRule(':equal', empty($message) ? 'Security token did not match. Possible CSRF attack.' : $message, $token);
	}

	public function setTag($value)
	{
		$this->tag = $value;
	}

	public function getTag()
	{
		return $this->tag;
	}

	/**
	 * Adds fieldset group to the form.
	 * @param  string  label
	 * @param  bool    set this group as current
	 * @return FormGroup
	 */
	public function addGroup($label = NULL, $setAsCurrent = TRUE)
	{
		$group = new FormGroup;
		$group->setOption('label', $label);
		$group->setOption('visual', TRUE);

		if ($setAsCurrent) {
			$this->setCurrentGroup($group);
		}

		if (isset($this->groups[$label])) {
			return $this->groups[] = $group;
		} else {
			return $this->groups[$label] = $group;
		}
	}

	/**
	* Zpracovani formulare a nastaveni data z/do objektu $bind
	*
	* @param IFormData $bind
	* @param bool $defaults
	*/
	public function process(IFormData $bind = NULL, $defaults = FALSE)
	{
		if($bind) $this->bindObject = $bind;

		if($this->bindObject != NULL) {
			if($this->isSubmitted()) {
					$data = array();
					foreach($this->getControls() as $control) {
						if($sql = $control->getOption('sql')) {
							$value = $control->getValueOut();
							if($value === false || $control->readonly || $control->disabled) continue;
							if(!is_array($sql)) $sql = array($sql => $sql);
							foreach ($sql as $key => $item) {
								if(!is_array($value)) $v = $value;
								else $v = $value[$key];
								$data[$item] = $v;
							}
						}
					}
					$this->bindObject->setData($data);
			}
			elseif (!$defaults) {
				$values = array();
				$data = $this->bindObject->getData();
				foreach($this->getControls() as $control) {
					if($sql = $control->getOption('sql')) {
						$v = array();
						if(is_array($sql)) {
							foreach ($sql as $key => $item) {
								$v[$key] = $data[$item];
							}
						}
						else $v = $data[$sql];
						$values[$control->getName()] = $v;
					}
				}
				$this->setDefaults($values, TRUE);
			}
			else {
				$this->setDefaults();
			}
		}
		else {
			if(!$this->isSubmitted()) $this->setDefaults();
		}
	}

	/**
	 * Returns all defined groups.
	 * @return array of FormGroup
	 */
	public function getGroups()
	{
		return $this->groups;
	}



	/**
	 * Returns the specified group.
	 * @param  string  name
	 * @return FormGroup
	 */
	public function getGroup($name)
	{
		return isset($this->groups[$name]) ? $this->groups[$name] : NULL;
	}



	/**
	 * Set the encoding for the values.
	 * @param  string
	 * @return void
	 */
	public function setEncoding($value)
	{
		$this->encoding = empty($value) ? 'UTF-8' : strtoupper($value);
		if ($this->encoding !== 'UTF-8' && !extension_loaded('mbstring')) {
			throw new /*\*/Exception("The PHP extension 'mbstring' is required for this encoding but is not loaded.");
		}
	}



	/**
	 * Returns the encoding.
	 * @return string
	 */
	final public function getEncoding()
	{
		return $this->encoding;
	}

	/********************* submission ****************d*g**/


	/**
	 * Tells if the form was submitted.
	 * @return ISubmitterControl|FALSE	submittor control
	 */
	public function isSubmitted()
	{
		if ($this->submittedBy === NULL) {
			$this->processHttpRequest();
		}
		return (bool) $this->submittedBy;
	}

	/**
	 * Sets the submittor control.
	 * @param  ISubmitterControl
	 * @return void
	 */
	public function setSubmittedBy(ISubmitterControl $by = NULL)
	{
		$this->submittedBy = $by === NULL ? FALSE : $by;
	}



	/**
	 * Detects form submission and loads HTTP values.
	 * @param  Nette\Web\IHttpRequest  optional request object
	 * @return void
	 */
	public function processHttpRequest($httpRequest = NULL)
	{
		$this->submittedBy = FALSE;

		if ($httpRequest === NULL) {
			$httpRequest = $this->getHttpRequest();
		}
		$httpRequest->setEncoding($this->encoding);

		if ($this->isPost) {
			if (!$httpRequest->isMethod('post')) return;
			$data = self::arrayAppend($httpRequest->getPost(), $httpRequest->getFiles());
		} else {
			if (!$httpRequest->isMethod('get')) return;
			$data = $httpRequest->getQuery();
		}


		$tracker = $this->getComponent(self::TRACKER_ID, FALSE);
		if ($tracker) {
			if (!isset($data[self::TRACKER_ID]) || $data[self::TRACKER_ID] !== $tracker->getValue()) return;

		} else {
			if (!count($data)) return;
		}

		// problem na
		// SAMSUNG-GT-i8000V/NXXIL5 (Windows CE; Opera Mobi; U; en) Opera 9.5
		// kde se form[] do postu odeslal jako string misto pole
		if(isset($data['__form']) && is_string($data['__form'])) $data['__form'] = array($data['__form']);

		if(isset($data['__form']) && in_array($this->getName(), $data['__form'])) {
			$this->submittedBy = TRUE;
			$this->loadHttpData($data);
			$this->isPopulated = TRUE;
			$this->submit();
		}
		// jedna se o subform v multiformu
		if($this->getName() == NULL) {
			$this->loadHttpData($data);
		}
	}



	/**
	 * Fires submit/click events.
	 * @return void
	 */
	protected function submit()
	{
		if (!$this->isSubmitted()) {
			return;

		} elseif ($this->submittedBy instanceof ISubmitterControl) {
			if (!$this->submittedBy->getValidationScope() || $this->isValid()) {
				$this->submittedBy->click();
				$this->onSubmit($this);
			} else {
				$this->submittedBy->onInvalidClick($this->submittedBy);
				$this->onInvalidSubmit($this);
			}

		} elseif ($this->isValid()) {
			$this->onSubmit($this);

		} else {
			$this->onInvalidSubmit($this);
		}
	}


	/********************* data exchange ****************d*g**/



	/**
	 * Fill-in with default values.
	 * @param  array	values used to fill the form
	 * @param  bool		erase other controls
	 * @return void
	 */
	public function setDefaults($values = NULL, $erase = FALSE)
	{
		if ($values instanceof ArrayObject) {
			$values = (array) $values;

		} elseif (!is_array($values)) {
			// nacte defaultni hodnoty controlu
			$iterator = $this->getControls();
			foreach ($iterator as $name => $control) {
				 $control->loadDefaultValue();
			}
		}
		else { // nastavi z pole $values
			$cursor = & $values;
			$iterator = $this->getControls();
			foreach ($iterator as $name => $control) {
				$sub = $iterator->getSubIterator();
				if (!isset($sub->cursor)) {
					$sub->cursor = & $cursor;
				}
				if ($control instanceof IFormControl) {
					if ((is_array($sub->cursor) || $sub->cursor instanceof /*\*/ArrayAccess) && array_key_exists($name, $sub->cursor)) {
						$control->setValueIn($sub->cursor[$name]);

					} elseif ($erase) {
						$control->loadDefaultValue();
					}
				}
			}
		}
		$this->isPopulated = TRUE;
	}



	/**
	 * Fill-in the form with HTTP data. Doesn't check if form was submitted.
	 * @param  array	user data
	 * @return void
	 */
	protected function loadHttpData(array $data)
	{
		$cursor = & $data;
		$iterator = $this->getComponents(TRUE);

		foreach ($iterator as $name => $control) {
			$sub = $iterator->getSubIterator();
			if (!isset($sub->cursor)) {
				$sub->cursor = & $cursor;
			}
			if ($control instanceof IFormControl && !$control->isDisabled()) {
				$control->loadHttpData($sub->cursor);
				if ($control instanceof ISubmitterControl && (!is_object($this->submittedBy) || $control->isSubmittedBy())) {
					$this->submittedBy = $control;
				}
			}
		}
		foreach ($this->getMultiForms() as $multi) {
			// pouze odeslane multiformy, formbindDb nema identifikaci v POSTu
			if($multi instanceof FormBindDB || in_array($multi->getName(), $data['__form'])) {
				$multi->loadHttpData($data);
				$multi->setPopulated();
			}
		}
		$this->isPopulated = TRUE;
	}



	/**
	 * Was form populated by setDefaults() or processHttpRequest() yet?
	 * @return bool
	 */
	public function isPopulated()
	{
		return $this->isPopulated;
	}

	/**
	* Nastavi, ze byl nacteny z reqestu. Ajaxove multiformy se pak nezpracovavaji
	*
	*/
	public function setPopulated()
	{
		$this->isPopulated = TRUE;
	}

	/**
	 * Returns the values submitted by the form.
	 *
	 *	if ($form->isSubmitted()) { // podmienka je nutna
	 *		dump($form->getValues()); // inak aplikacia na getValues() spadne
	 *	}
	 *
	 * @return array
	 */
	public function getValues()
	{
		if (!$this->isPopulated) {
			throw new /*\*/InvalidStateException('Form was not populated yet. Call method isSubmitted() or setDefaults(). if ($form->isSubmitted()) {$form->getValues();}');
		}

		$values = array();
		$cursor = & $values;
		$iterator = $this->getComponents(TRUE);
		foreach ($iterator as $name => $control) {
			$sub = $iterator->getSubIterator();
			if (!isset($sub->cursor)) {
				$sub->cursor = & $cursor;
			}
			if ($control instanceof IFormControl && !$control->isDisabled() && !($control instanceof ISubmitterControl)) {
				$sub->cursor[$name] = $control->getValueOut();
			}
			/*if ($control instanceof INamingContainer) {
				$cursor = & $sub->cursor[$name];
				$cursor = array();
			}*/
		}
		unset($values[self::TRACKER_ID], $values[self::PROTECTOR_ID]);
		return $values;
	}


	/**
	 * Recursively appends elements of remaining keys from the second array to the first.
	 * @param  array
	 * @param  array
	 * @return array
	 * @internal
	 */
	protected static function arrayAppend($arr1, $arr2)
	{
		$res = $arr1 + $arr2;
		foreach (array_intersect_key($arr1, $arr2) as $k => $v) {
			if (is_array($v) && is_array($arr2[$k])) {
				$res[$k] = self::arrayAppend($v, $arr2[$k]);
			}
		}
		return $res;
	}



	/********************* validation ****************d*g**/



	/**
	 * Is form valid?
	 * @return bool
	 */
	public function isValid()
	{
		if ($this->valid === NULL) {
			$this->validate();
		}
		return $this->valid;
	}



	/**
	 * Performs the server side validation.
	 * @return void
	 */
	public function validate()
	{
		if (!$this->isPopulated) {
			throw new /*\*/InvalidStateException('Form was not populated yet. Call method isSubmitted() or setDefaults().');
		}

		$controls = $this->getControls();

		$this->valid = TRUE;
		foreach ($controls as $control) {
			if (!$control->getRules()->validate()) {
				$this->valid = FALSE;
				$control->cssClass .= ' invalidInput';
			}
		}
		foreach ($this->getMultiForms() as $multi) {
				$this->valid &= $multi->isValid();
		}
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
			$this->valid = FALSE;
		}
	}



	/**
	 * Returns validation errors.
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
		return (bool) $this->getErrors();
	}



	/**
	 * @return void
	 */
	public function cleanErrors()
	{
		$this->errors = array();
		$this->valid = NULL;
	}




	/**
	 * Removes a component from the IComponentContainer.
	 * @param  IComponent
	 * @return void
	 */
	public function removeComponent(IComponent $component)
	{
		// odstrani prvne ze skupiny
		foreach($this->getGroups() as $group) {
			foreach($group->getControls() as $control) {
				if($control->getName() == $component->getName()) {
					$group->remove($component);
				}
			}
		}
	   parent::removeComponent($component);

	}

	/*********** Multiformy *************/

	public function addMulti($form)
	{
		$name = $form->getName();
		$this->multiArr[$name] = $form;
		return $this->multiArr[$name];
	}

	/**
	 * Prida Multiform do Formu
	 *
	 * @param string $name
	 * @param Form $parent
	 * @return Multiform
	 */
	public function addMultiForm($name)
	{
		$this->multiArr[$name] = new MultiForm($name, $this);
		return $this->multiArr[$name];
	}

	/**
	* Prida databazovy multiform
	* @param string $name
	* @return MultiFormDB
	*/
	public function addMultiFormDB($name)
	{
		$this->multiArr[$name] = new MultiFormDB($name, $this);
		return $this->multiArr[$name];
	}

   /**
   *  Prida galeriovy radici multiform
	* @param string $name
	* @return MultiFormSortableDB
	*/
	public function addMultiFormSortableDB($name)
	{
		$this->multiArr[$name] = new MultiFormSortableDB($name, $this);
		return $this->multiArr[$name];
	}

	/**
	* Prida checkboxovy multiform
	* @param string $name
	* @return MultiFormCheckbox
	*/
	public function addMultiFormCheckbox($name)
	{
		$this->multiArr[$name] = new MultiFormCheckbox($name, $this);
		return $this->multiArr[$name];
	}

	/**
	* @param string $name
	* @return MultiFormCheckboxDB
	*/
	public function addMultiFormCheckboxDB($name)
	{
		$this->multiArr[$name] = new MultiFormCheckboxDB($name, $this);
		return $this->multiArr[$name];
	}

	/**
	* @param string $name
	* @return FormBindDB
	*/
	public function addFormDB($name)
	{
		$this->multiArr[$name] = new FormBindDB($name, $this);
		return $this->multiArr[$name];
	}

	/**
	* @param string $name
	* @return FormVirtual
	*/
	public function addFormVirtual($name, $group = NULL)
	{
		$this->multiArr[$name] = new FormVirtual($name, $this, $group);
		return $this->multiArr[$name];
	}

	/**
	 * Vrati multiform podle nazvu
	 *
	 * @param string $name
	 * @return Multiform
	 */
	public function getMultiForm($name)
	{
		return isset($this->multiArr[$name]) ? $this->multiArr[$name] : null;
	}

	/**
	 * Vrati vsechny multiformy
	 *
	 * @return unknown
	 */
	public function getMultiForms()
	{
		return $this->multiArr;
	}


	/** nastavi vsem formcontrolu readonly */
	public function setReadonly()
	{
		foreach($this->getAllControls() as $control) {
			$control->setReadonly();
		}
		foreach($this->getMultiForms() as $form) {
			$form->disableAddButton();
		}
	}

	/**
	* Vrati vsechny controly ve formulari, vcetne controlu ve vazanych formularich
	*
	*/
	public function getAllControls()
	{
		$cArr = array();
		foreach($this->getControls() as $control) $cArr[] = $control;
		foreach($this->getMultiForms() as $form) {
			foreach($form->getControls() as $control) $cArr[] = $control;
		}
		return $cArr;
	}

	/**
	* Zjisti ze svych controlu javascripty, ktere je potreba nacist
	*
	*/
	public function getAllScripts()
	{
		$arr = array();
		foreach($this->getAllControls() as $control) {
			 $js = $control->getJavascript();
			 if(!is_array($js)) $js = array($js);
			 $arr = array_merge($arr, $js);
		}
		return array_unique($arr);
	}

	/**
	* Zjisti ze svych controlu css, ktere je potreba nacist
	*
	*/
	public function getAllCss()
	{
		$arr = array();
		foreach($this->getAllControls() as $control) {
			 $css = $control->getCss();
			 if(!is_array($css)) $css = array($css);
			 $arr = array_merge($arr, $css);
		}
		return array_unique($arr);
	}

	/********************* rendering ****************d*g**/



	/**
	 * Returns form's HTML element template.
	 * @return Nette\Web\Html
	 */
	public function getElementPrototype()
	{
		return $this->element;
	}



	/**
	 * Sets form renderer.
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



	/**
	 * Renders form.
	 * @return void
	 */
	public function render()
	{
		if(!$this->isPopulated()) $this->setDefaults();
		if($this->getMainForm() == $this) $this->beforeFirstRender();
		if(SnippetHelper::$outputAllowed) {
			$args = func_get_args();
			array_unshift($args, $this);
			$s = call_user_func_array(array($this->getRenderer(), 'render'), $args);
			if (strcmp($this->encoding, 'UTF-8')) {
				echo mb_convert_encoding($s, 'HTML-ENTITIES', 'UTF-8');
			} else {
				echo $s;
			}
		}
	}

	/**
	* Pred prvnim renderedovanim se aplikuji aplikacne pridane subformu
	*
	*/
	protected function beforeFirstRender()
	{
		if(!$this->firstRender && (!$this->isSubmitted() || $this->isSubmitted() && $this->isValid())) {
			foreach($this->getMultiForms() as $multi) {
				if($multi instanceof MultiForm) $multi->joinNewSubforms();
			}
			$this->firstRender = TRUE;
		}
	}

	/**
	 * Renders form to string.
	 * @return string
	 */
	public function __toString()
	{
		if(!$this->isPopulated()) $this->setDefaults();
		try {
			if (strcmp($this->encoding, 'UTF-8')) {
				return mb_convert_encoding($this->getRenderer()->render($this), 'HTML-ENTITIES', 'UTF-8');
			} else {
				return $this->getRenderer()->render($this);
			}

		} catch (/*\*/Exception $e) {
			if (func_get_args()) {
				throw $e;
			} else {
				trigger_error($e->getMessage(), E_USER_WARNING);
				return '';
			}
		}
	}

	public function renderRow($controlname)
	{
		return $this->getRenderer()->renderPair($this[$controlname]);
	}

	/********************* backend ****************d*g**/



	/**
	 * @return Nette\Web\IHttpRequest
	 */
	protected function getHttpRequest()
	{
		return class_exists(/*Nette\*/'Environment') ? /*Nette\*/Environment::getHttpRequest() : new /*Nette\Web\*/HttpRequest;
	}



	/**
	 * @return Nette\Web\Session
	 */
	protected function getSession()
	{
		return /*Nette\*/Environment::getSession();
	}

	/**
	 * @return boolean
	 */
	public function getUseAjax()
	{
		return $this->useAjax;
	}

	/**
	 * @param boolean $useAjax
	 */
	public function setUseAjax($useAjax)
	{
		$this->useAjax = $useAjax;
	}

}

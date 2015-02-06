<?php


/**
 * Instant validation JavaScript generator.
 *
 * @author	   David Grudl, Ondrej Novak
 * @package    Forms
 */
class InstantClientScript extends /* Nette\ */Object
{
	/** @var string  JavaScript event handler name */
	public $validateFunction;

	/** @var string  JavaScript event handler name */
	public $toggleFunction;

	/** @var string callbackova funkce volana pri nevalidnim inputu (az po odeslani formulare) */
	public $invalidJsCallback = 'function(element) {}';

	/** @var string  JavaScript event handler name */
	public $resetFunction;

	/** @var string  JavaScript event handler name */
	public $isEmptyFunction;

	/** @var string  JavaScript code */
	public $doAlert = 'if (element) element.focus(); alert(message);';

	/** @var string  JavaScript code */
	public $doToggle = 'if (element) element.style.display = visible ? "" : "none";';

	/** @var string */
	public $validateScript;

	/** @var string */
	public $toggleScript;

	/** @var string */
	public $resetScript;

	/** @var string */
	public $isEmptyScript;

	/** @var bool */
	private $central;

	/** @var Form */
	private $form;


	public function __construct(Form $form)
	{
		$this->form = $form;
		if ($this->form instanceof SubForm) {
			$name = 'Multi' . ucfirst($form->getFormName());
		} else {
			$name = ucfirst($form->getName());
		} //ucfirst(strtr($form->getUniqueId(), Form::NAME_SEPARATOR, '_'));

		$this->validateFunction = 'validate' . $name;
		$this->toggleFunction = 'toggle' . $name;
		$this->resetFunction = 'reset' . $name;
		$this->isEmptyFunction = 'isEmpty' . $name;
	}


	public function setForm($form)
	{
		$this->form = $form;
	}


	public function enable()
	{
		$this->validateScript = '';
		$this->toggleScript = '';
		$this->resetScript = '';
		$this->central = TRUE;
		$noreset = array();
		foreach ($this->form->getControls() as $control) {
			$script = $this->getValidateScript($control->getRules());
			if ($script) {
				if ($this->form instanceof MultiForm) {
					$name = $this->form->getFormName();
					$i = $control->getSubForm()->getFormId();
					$script = str_replace($i, "' + index + '", $script);
					$this->validateScript .= "for (index in " . $name . "IndexArray) {\n\t
						del = '" . $name . "___delete_' + index + '_';
						if(document.getElementById(del) != undefined && document.getElementById(del).checked) continue;
					$script};\n\n\t";
				} else {
					$this->validateScript .= "do {\n\t$script} while(0);\n\n\t";
				}
			}
			$this->toggleScript .= $this->getToggleScript($control->getRules());

			if ($control instanceof ISubmitterControl && $control->getValidationScope() !== TRUE) {
				$this->central = FALSE;
			}
			if ($control->getOption('reset') === 0) {
				$noreset[] = $control->getId();
			}
		}

		// validace Multiformu
		$multiForms = $this->form->getMultiForms();
		if (!empty($multiForms)) {
			$this->validateScript .= "multiValid = true;\n\t";
			foreach ($multiForms as $form) {
				if (!$form instanceof FormVirtual) {
					$name = 'Multi' . ucfirst($form->getFormName());
					//$this->validateScript .= "multiValid = validate$name();\n\t";

					$this->validateScript .= "if(typeof validate$name == 'function' && !validate$name()) return false;\n\t";
				}
			}
		}

		if (!$this->form instanceof SubForm) {
			//if ($this->validateScript || $this->toggleScript) {
			if ($this->central) {
				$this->form->getElementPrototype()->onsubmit("return $this->validateFunction(this, $this->invalidJsCallback)");
			} else {
				foreach ($this->form->getComponents(TRUE, 'Nette\Forms\ISubmitterControl') as $control) {
					if ($control->getValidationScope()) {
						$control->getControlPrototype()->onclick("return $this->validateFunction(this, $this->invalidJsCallback)", TRUE);
					}
				}
			}
			//}
		}

		// reset script

		$this->resetScript = "
			fil = document.getElementsByName('" . $this->form->getName() . "');
			fil = fil[0];
            noreset = " . json_encode($noreset) . ";
			if (fil && fil.elements) {
				for (i=0; i<fil.elements.length; i++) {
					e = fil.elements[i];
					if(jQuery.inArray(e.name, noreset) > -1) continue;
					if (e.name != '__form[]') {

						switch (e.type) {
							case 'text':
							case 'hidden':
							case 'password':
								e.value = '';
							break;

							case 'checkbox':
							case 'radio':
								e.checked = false;
							break;

							case 'select-one':
							case 'select-multiple':
								for (var j=0; j<e.options.length; j++) {
									e.options[j].selected = false;
								}

								if (e.options.length>0){
									//kvuli IE - bez nasledujiciho by i pres selected=false nechal vybranou posledni volbu.
									e.options[0].selected = true;
									e.options[0].selected = false;
								}
							break;
						}
					}
				}
		}
		if(fil.onsubmit != 'undefined') {
			fil.onsubmit();
		}
		else fil.submit()";
	}


	/**
	 * Generates the client side validation script.
	 * @return string
	 */
	public function renderClientScript()
	{
		$s = '';

		//if ($this->validateScript) {
		$s .= "$this->validateFunction = function(sender, callback) {\n\t"
				. "var element, message, res;\n\t"
				. $this->validateScript
				. "form_validated = true;\n"
				. "return true;\n"
				. "}\n\n";
		//}

		if ($this->toggleScript) {
			$s .= "$this->toggleFunction = function(sender) {\n\t"
					. "var element, visible, res;\n\t"
					. $this->toggleScript
					. "\n}\n\n"
					. "$this->toggleFunction(null);\n";
		}

		if ($this->resetScript) {
			$s .= "$this->resetFunction = function () {\n\t"
					. $this->resetScript
					. "\n}\n\n"
					. "";
		}

		if ($this->isEmptyScript) {
			$s .= "$this->isEmptyFunction = function (Index) {\n\t"
					. $this->isEmptyScript
					. "\n}\n\n"
					. "";
		}

		if ($s) {
			return "<script type=\"text/javascript\">\n"
					. "/* <![CDATA[ */\n"
					. $s
					. "/* ]]> */\n"
					. "</script>";
		}
	}


	/**
	 * Vygeneruje multiformove funkce AppendBlank() a Remove()
	 *
	 * @return string
	 */
	public function renderJsMulti()
	{
		$name = $this->form->getName();

		$minIndex = 0;
		$maxPriority = 0;
		$count = 0;
		if ($this->form->getSubforms()) {
			foreach ($this->form->getSubforms() as $form) {
				$id = $form->getFormId();
				$minIndex = ($id < $minIndex) ? $id : $minIndex;
				$arrayString[] = '"' . $id . '": "' . $id . '"';
				$count++;
			}
			$arrayString = implode(', ', $arrayString);
		} else {
			$arrayString = '';
		}

		$js = 'empty = true;';
		// nastaveni jen controlu, ktere maji byt videt v novem pridavacim subformu
		foreach ($this->form->getControls() as $control) {
			$control->tmpVisible = $control->visible;
			$control->visible = $control->newVisible;

			if ($control->getName() != MultiForm::FORM_ID_HIDDEN &&
					$control->getName() != SubForm::DELETE_ID) {
				$s = $control->checkEmptyJs();
				if (!empty($s)) {
					$js .= str_replace("' + " . $name . "Index + '", "' + Index + '", $s);
					$js .= 'empty = res && empty;';
				}
			}
		}
		$js .= 'return empty;';
		$this->isEmptyScript = $js;

		$slashedHtml = addcslashes($this->form->getHtmlBody(), "\r\n\\\"'");
		$innerHTMLtmp = str_replace(
				array("\' + " . $name . "Index + \'", "\' + " . $name . "Priority + \'"), //
				array("' + " . $name . "Index + '", "' + " . $name . "Priority + '"), //
				$slashedHtml
		);
		$innerHTML = str_replace('</script>', "<\/scr' + 'ipt>", $innerHTMLtmp);

		foreach ($this->form->getControls() as $control) {
			$control->visible = $control->tmpVisible;
		}

		// v IE se neinterpretuji javascripty vkladane pres innerHTML, tak se nactou do pole a provedou se evalem
		$matches = NULL;
		preg_match_all('/<script.*>(.*)<\/script>/smiU', $innerHTMLtmp, $matches, PREG_SET_ORDER);

		$scripts = array();
		$jsScripts = 'var ' . $name . 'jsArray = [';
		foreach ($matches as $match) {
			$scripts[] = $match[1];
			$jsScripts .= "'" . str_replace("\n", '', $match[1]) . "', ";
		}
		$jsScripts .= '];';

		$jsStr = '
 ' . $name . 'Index = ' . $minIndex . ';
 ' . $name . 'Count = ' . $count . ';
 ' . $name . 'IndexArray = {' . $arrayString . '};
 ' . $name . 'Priority = ' . $maxPriority . ';
 ' . $name . 'JsCalled = false;
 ' . $this->jsAppendBlank($name, $innerHTML, $jsScripts) . '
 ' . $this->jsAppendValues($name) . '
 ' . $this->jsRemove($name) . '
 ' . $this->jsUnRemove($name);
		$html = Html::el('script')->setHtml($jsStr);
		$html.= Html::el('script')->src(Environment::getVariable('baseUri') . 'js/core/setinput.js');
		return $html;
	}


	protected function jsRemove($name)
	{
		return $name . 'Remove = function(id, del)  {
	' . $name . 'Count--;
	var element = document.getElementById(\'' . $name . '_\' + id + \'_\');
	if(isEmptyMulti' . ucfirst($name) . '(id) || del) $(element).hide();
	else $(element).addClass("removed");
	$("#multi_' . $name . '").trigger("removeSubform", element);
	' . '}';
	}


	protected function jsUnRemove($name)
	{
		return $name . 'UnRemove = function(id)  {
	' . $name . 'Count++;
	var element = document.getElementById(\'' . $name . '_\' + id + \'_\');
	$(element).removeClass("removed");
	' . '}';
	}


	protected function jsAppendBlank($name, $html, $js)
	{
		return $name . 'AppendBlank = function()	{
			' . $name . 'JsCalled = false;
			' . $name . 'Index--;
			' . $name . 'Count++;
			' . $name . 'Priority++;
			' . $js . '
			var element = document.createElement("' . $this->form->subformElm . '");
			element.className = "' . $this->form->subformClass . '";
			newid = "' . $name . '_" + ' . $name . 'Index + "_";
			element.setAttribute("id", newid);
			element.style.display = "none";
			element.innerHTML = toEval = \'<script>' . $name . 'JsCalled = true;</scr\' + \'ipt>' . $html . '\';

			$("#multi_' . $name . '").append(element);
			' . $name . 'IndexArray[' . $name . 'Index] = ' . $name . 'Index;
			' . '
			if(!' . $name . 'JsCalled)
			{
				for (var i=0; i < ' . $name . 'jsArray.length; i++) {
					str = ' . $name . 'jsArray[i];
					eval(str);
				}
			}
			$("#"+newid).show();
			$("#multi_' . $name . '").trigger("append", [' . $name . 'Index, element]);
			return ' . $name . 'Index;
		}';
	}


	protected function jsAppendValues($name)
	{
		return $name . 'AppendValues = function(values)  {
			var newid = ' . $name . 'AppendBlank();
			for (i in values) {
				if(values[i].constructor.toString().indexOf("Array") > 0) {
					for (i2 in values[i]) {
						setInputByID("' . $name . '_" + i + "_" + ' . $name . 'Index + "_" + i2, values[i][i2]);
					}
				}
				else setInputByID("' . $name . '_" + i + "_" + ' . $name . 'Index + "_", values[i]);
			}
			$("#multi_' . $name . '").trigger("appendValues", newid);
			return newid;
		}
	';
	}


	public function getValidateScript(Rules $rules, $onlyCheck = FALSE, $returnObject = FALSE)
	{
		$res = '';
		foreach ($rules as $rule) {
			if (!is_string($rule->operation)) {
				continue;
			}

			if (strcasecmp($rule->operation, 'Nette\Forms\InstantClientScript::javascript') === 0) {
				$res .= "$rule->arg\n\t";
				continue;
			}

			$script = $this->getClientScript($rule->control, $rule->operation, $rule->arg);
			if (!$script) {
				continue;
			}

			if (!empty($rule->message)) { // this is rule
				if ($onlyCheck && $returnObject) {
					$ruleOperation = strtolower(str_replace(":", "", $rule->operation));
					$msg = json_encode((string) vsprintf($rule->message, (array) $rule->arg));
					$res .= "$script\n\tif (" . ($rule->isNegative ? '' : '!') . "res) { return {ok: false, message: $msg, className: '$ruleOperation'}; }\n\t";
				} elseif ($onlyCheck) {
					$res .= "$script\n\tif (" . ($rule->isNegative ? '' : '!') . "res) { return false; }\n\t";
				} else {
					$res .= "$script\n\t"
							. "if (" . ($rule->isNegative ? '' : '!') . "res) { "
							. "message = " . json_encode((string) vsprintf($rule->message, (array) $rule->arg)) . "; "
							. $this->doAlert
							. " if(callback != undefined) callback(element); return false; }\n\t";
				}
			}

			if ($rule->type === Rule::CONDITION) { // this is condition
				$innerScript = $this->getValidateScript($rule->subRules, $onlyCheck, $returnObject);
				if ($innerScript) {
					$res .= "$script\n\tif (" . ($rule->isNegative ? '!' : '') . "res) {\n\t\t" . str_replace("\n\t", "\n\t\t", rtrim($innerScript)) . "\n\t}\n\t";
					if (!$onlyCheck && $rule->control instanceof ISubmitterControl) {
						$this->central = FALSE;
					}
				}
			}
		}
		return $res;
	}


	public function getIsRequiredScript(Rules $rules)
	{
		$res = '';
		foreach ($rules as $rule) {

			if (!is_string($rule->operation)) {
				continue;
			}

			if (strcasecmp($rule->operation, 'Nette\Forms\InstantClientScript::javascript') === 0) {
				$res .= "$rule->arg\n\t";
				continue;
			}

			$script = $this->getClientScript($rule->control, $rule->operation, $rule->arg);
			if (!$script) {
				continue;
			}

			if (!empty($rule->message)) { // this is rule
				//if($conditionOn) $res .= $script;
			}

			if ($rule->type === Rule::CONDITION) { // this is condition
				if ($this->hasRuleFilled($rule)) {
					$innerScript = $this->getIsRequiredScript($rule->subRules);
					if ($rule->isNegative) {
						$script .= 'res = !res;';
					}
					$res .= $script;
					if ($innerScript) {
						$res .= "\n\tif (" . ($rule->isNegative ? '!' : '') . "res) {\n\t\t" . str_replace("\n\t", "\n\t\t", rtrim($innerScript)) . "\n\t}\n\t";
					}
				}
			}
		}
		return $res;
	}


	private function hasRuleFilled($rule)
	{
		$filled = FALSE;
		if ($rule->subRules) {
			foreach ($rule->subRules as $subrule) {
				if ($subrule->operation == Form::FILLED) {
					$filled = TRUE;
				} else {
					$filled |= $this->hasRuleFilled($subrule);
				}
			}
		}
		return $filled;
	}


	private function getToggleScript(Rules $rules, $cond = NULL)
	{
		$s = '';
		foreach ($rules->getToggles() as $id => $visible) {
			$s .= "visible = true; {$cond}element = document.getElementById('" . $id . "');\n\t"
					. ($visible ? '' : 'visible = !visible; ')
					. $this->doToggle
					. "\n\t";
		}
		foreach ($rules as $rule) {
			if ($rule->type === Rule::CONDITION && is_string($rule->operation)) {
				$script = $this->getClientScript($rule->control, $rule->operation, $rule->arg);
				if ($script) {
					$res = $this->getToggleScript($rule->subRules, $cond . "$script visible = visible && " . ($rule->isNegative ? '!' : '') . "res;\n\t");
					if ($res) {
						$el = $rule->control->getControlPrototype();
						if ($el->getName() === 'select') {
							$el->onchange("$this->toggleFunction(this)", TRUE);
						} else {
							$el->onclick("$this->toggleFunction(this)", TRUE);
							//$el->onkeyup("$this->toggleFunction(this)", TRUE);
						}
						$s .= $res;
					}
				}
			}
		}
		return $s;
	}


	private function getClientScript(IFormControl $control, $operation, $arg)
	{
		$res = null;
		if ($control->isDisabled()) {
			return NULL;
		}

		$operationLower = strtolower($operation);
		if (is_string($operationLower) && strncmp($operationLower, ':', 1) === 0) {
			$f = array($control->getClass(), Rules::VALIDATEJS_PREFIX . ltrim($operationLower, ':'));
			if (method_exists($control, $f[1])) {
				if ($operationLower === ':valid') {
					$arg = $this;
				}
				$res = call_user_func_array($f, array($control, $arg));
			}
		}
		return $res;

		////////////////////// original implementace
		/* $id = $control->getId();
		  $tmp = "element = document.getElementById('" . $id . "');\n\t";
		  $tmp2 = "var val = element.value.replace(/^\\s+/, '').replace(/\\s+\$/, '');\n\t";
		  $tmp3 = array();
		  $operation = strtolower($operation);

		  switch (TRUE) {

		  case $operation === ':submitted' && $control instanceof SubmitButton:
		  return "element=null; res=sender && sender.name==" . json_encode($control->getHtmlName()) . ";";
		  } */
	}


	public static function javascript()
	{
		return TRUE;
	}

}

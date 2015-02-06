<?php
/**
 * Textovy control pro zadaji cisla.
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class NumberInput extends TextInput
{
	/**
	* Zobrazi posunovaci sipky nahoru/dolu
	*
	* @var mixed
	*/
	protected $showSpin = FALSE;

	/**
	 * @param  string  control name
	 * @param  string  label
	 * @param  int	width of the control
	 * @param  int	maximum number of characters the user may enter
	 */
	 public function __construct($label, $cols = 4, $maxLenght = NULL)
	{
		parent::__construct($label, $cols, $maxLenght);
		$this->addCondition(Form::FILLED)
				->addRule(Form::NUMERIC, _('Zadejte číslo!'));

	}


	public function showSpin()
	{
		$this->showSpin = TRUE;
		return $this;
	}

	public function hideSpin()
	{
		$this->showSpin = FALSE;
		return $this;
	}

	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		$control->class = $this->cssClass;
		$control->value = $this->value === '' ? $this->emptyValue : (int)$this->tmpValue;
		if($this->showSpin) {
			$js = Html::el('script')->type('text/javascript')
					->setText('$(\'#'.$this->getHtmlName().'\').spin();');
		}
		else $js = '';
		return $control.$js;
	}

	 public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri.'js/core/jquery.spin.js';
		return array_merge(parent::getJavascript(), $js);
	}

	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValue();
		if(empty($value)) return null;
		if(!is_array($column)) $column = array($column);
		$s = array();
		foreach($column as $c) {
			$s[] = $c.' = "'.Database::instance()->escape_str($value).'"';
		}
		return '('.join(' OR ', $s).')';
	}
}

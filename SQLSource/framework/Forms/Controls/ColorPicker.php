<?php
/**
 * ColorPicker Control.
 *
 * @author	   Igor Lamos
 * @copyright  Copyright (c) 2009 Igor Lamos
 * @package    Forms
 */

class ColorPicker extends TextInput
{
	/**
	 * Whatever if the color picker is appended to the element or triggered by an event.
	 * Default false.
	 *
	 * @var bool
	 */
	protected $flat = FALSE;

	/**
	 * Whatever if the color values are filled in the fields while changing values on selector or a field.
	 * If false it may improve speed.
	 * Default true.
	 *
	 * @var bool
	 */
	protected $livePreview = TRUE;

	/**
	 * @param $label string	Label.
	 */
	 public function __construct($label)
	{
		parent::__construct($label);
	}

	/**
	 * Sets control's value.
	 *
	 * @param $value string
	 * @return void
	 */
	public function setValue($value)
	{
		parent::setValue($value);

	}

	/**
	 * Generates control's HTML element.
	 *
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		$control->class = $this->cssClass;
		$control->value = $this->value === '' ? $this->emptyValue : $this->tmpValue;

		$js = Html::el('script')->type('text/javascript')
			->setText('$(\'#'.$this->getHtmlName().'\').ColorPicker({
							onSubmit: function(hsb, hex, rgb, el) {
								$(el).val(hex);
								$(el).ColorPickerHide();
							},
							onBeforeShow: function () {
								$(this).ColorPickerSetColor(this.value);
							}
						})');

		return $control.$js;
	}

	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri.'js/core/jquery.colorpicker.js';
		return array_merge(parent::getJavascript(), $js);
	}

	public function getCSS()
	{
		$baseUri = Environment::getVariable('baseUri');

		$css = array();
		$css[] = $baseUri.'css/core/jquery.colorpicker.css';

		return array_merge(parent::getCSS(), $css);
	}

	public function sqlWhere()
	{
		$column = $this->getOption('sql');
		$value = $this->getValue();

		if(empty($value))
			return null;

		$s = $column.' = "'.Database::instance()->escape_str($value).'"';
		return '('.$s.')';
	}
}

<?php
/**
 * Slider form control.
 *
 * @author Jaromir Nyklicek <jaromir.nyklicek@viaaurea.cz>
 */
class Slider extends TextInput {

	public $min;
	public $max;
	public $step;


	/** moznost pripojit text za hodnotu napr. %**/
	protected $value_suffix = '';

	/**
	 *
	 * @param string $label popisek slideru
	 * @param int $min minimalni hodnota
	 * @param int $max maximalni hodnota
	 * @param int $step krok
	 */
	public function  __construct($label, $min = 0, $max = 100, $step = 1, $suffix = '') {
		parent::__construct($label);
		$this->min = $min;
		$this->max = $max;
		$this->step = $step;
		$this->value_suffix = $suffix;
	}

	public function setSuffix($value)
	{
		$this->value_suffix = $value;
		return $this;
	}


	public function getControl() {
		$script = '
			<script type="text/javascript">
			$(function() {
					$("#' . $this->getId() . '_slider").slider({
						 value: ' . $this->getValue() . ',
						 min: ' . $this->min . ',
						 max: ' . $this->max . ',
						 step: ' . $this->step . ',
						 slide: function(e, ui) {
										$("#' . $this->getId() . '_slider_value").html(ui.value);
										$("#' . $this->getId() . '").val(ui.value);
									}
					});
				});
			</script>
		';

		$slider = Html::el('div')->id($this->getId() . '_slider')->style('float:left; width: 180px; margin-right: 10px');
		$value = Html::el('div')->id($this->getId() . '_slider_value')->setText($this->getValue())->style('float:left');
		if($this->value_suffix != '') {
			$suffix = Html::el('div')->setText($this->value_suffix)->style('float:left');
		}
		else $suffix = '';

		$field = parent::getControl()->type('hidden');

		return $script . $slider . $value . $suffix . $field;
	}

	public function getValue()
	{
		return (int) parent::getValue();
	}

	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri.'js/jquery-ui.js';
		return array_merge(parent::getJavascript(), $js);
	}

}

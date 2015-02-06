<?php

/**
 * FormControl pro zadavani ceny vcetne DPH.
 * Pouziti ve formulari:
 *
 * $form->addComponent(new PriceInput('Cena'), 'price')
 * 			->sql(array('price' =>'nazev_sloupce_s_cenou', 'vat' => 'sloupec_s_dph'))
 *
 * @author Jaromir Nyklicek <jaromir.nyklicek@viaaurea.cz>
 */
class PriceInput extends FormControl
{
	protected $value = array('price' => null, 'vat' => null, 'withVat' => null);
	protected $vat = array(0, 10, 20);


	public function __construct($label = null, $vat = null)
	{
		parent::__construct($label);
		if ($vat !== null) {
			$this->vat = $vat;
		}
	}


	public function getControl()
	{
		$control1 = Html::el('input')->type('text')->name($this->getHtmlName())
				->value($this->value['price'])
				->id($this->getId())
				->size(16)
				->class($this->cssClass);
		$control1 .= Html::el('label')->setText(' ' . _('bez DPH'));

		$container = Html::el('span')->id($this->getId() . '_radios');
		foreach ($this->vat as $key => $value) {
			$radio = Html::el('input')
					->type('radio')
					->id($this->getId() . '_option' . $key)
					->value($value)
					->name($this->getHtmlName() . '_vat');

			if ($this->value['vat'] === null) {
				$radio->checked((string) $value == 0);
			} else {
				$radio->checked((string) $value == $this->value['vat']);
			}

			$label = Html::el('label')
					->for($this->getId() . '_option' . $key)
					->setText($value . '% ');

			$container->add($radio);
			$container->add($label);
		}

		$control3 = $container;

		$control2 = Html::el('input')->type('text')->name($this->getHtmlName() . '_withVat')
				->value($this->value['withVat'])
				->id($this->getId() . '_withVat')
				->size(16)
				->class($this->cssClass);
		$control2 .= Html::el('label')->setText(' ' . _('s DPH'));

		$script = '
			<script type="text/javascript">
				function calculateWithVat(val) {
					var noVat = parseFloat(val.replace(",","."));
					var vat = parseFloat($("#' . $this->getId() . '_radios input:checked").val())
					var withVat = Math.round(1000 * (noVat * ((vat * 0.01) + 1)))/1000;
					$("#' . $this->getId() . '_withVat").val(withVat);
				}

				function calculatePrice(val) {
					var withVat = parseFloat(val.replace(",","."));
					var vat = parseFloat($("#' . $this->getId() . '_radios input:checked").val())
					var noVat = Math.round(10000 * (withVat / ((vat * 0.01) + 1)))/10000;
					$("#' . $this->getId() . '").val(noVat);
				}

				$(document).ready(function() {
					var priceFirst = true;

					$("#' . $this->getId() . '").keypress(function() {
						priceFirst = true;
					});

					$("#' . $this->getId() . '_withVat").keypress(function() {
						priceFirst = false;
					});

					$("#' . $this->getId() . '").blur(function() {
						calculateWithVat($(this).val());
					});

					$("#' . $this->getId() . '_radios input").change(function() {
						if(priceFirst) {
							calculateWithVat($("#' . $this->getId() . '").val());
						} else {
							calculatePrice($("#' . $this->getId() . '_withVat").val())
						}
					});

					$("#' . $this->getId() . '_withVat").blur(function() {
						calculatePrice($(this).val());
					});
				})
			</script>
		';

		return $script .
		'<table>
				<tr>
					<td>' . $control1 . '</td>
					<td>&nbsp;&nbsp;</td>
					<td><b>' . _('DPH:') . '</b></td>
				</tr>
				<tr>
					<td>' . $control2 . '</td>
					<td>&nbsp;&nbsp;</td>
					<td>' . $control3 . '</td>
				</tr>
			</table>';
	}


	public function loadHttpData($data)
	{
		$name = $this->getHtmlName();
		$price = isset($data[$name]) ? $data[$name] : NULL;
		$vat = isset($data[$name . '_vat']) ? $data[$name . '_vat'] : NULL;
		$withVat = isset($data[$name . '_withVat']) ? $data[$name . '_withVat'] : NULL;
		$this->setValue(array('price' => $this->fixNumber($price), 'vat' => $this->fixNumber($vat), 'withVat' => $this->fixNumber($withVat)));
	}


	public function setValueIn($value)
	{
		if (isset($value['price']) && isset($value['vat'])) {
			$value['withVat'] = round($value['price'] * (($value['vat'] * 0.01) + 1), 2);
		} else {
			$value['price'] = isset($value['price']) ? $value['price'] : null;
			$value['vat'] = isset($value['vat']) ? $value['vat'] : null;
			$value['withVat'] = null;
		}
		$this->value = $value;
	}


	public function getValueOut()
	{
		$value = $this->value;
		return $value;
	}


	protected function fixNumber($numberWithCommas)
	{
		return str_replace(',', '.', $numberWithCommas);
	}

}

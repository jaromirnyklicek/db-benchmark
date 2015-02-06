<?php
/**
 * SelectBox navazani na jiny SelectBox v MULTIFORMU.
 * Javasriptove plneni podle nadrazeneho formu se NEDEJE automaticky.
 * Je to nutne resit nekoncepcne pres vlastni javascript napojeni v presenteru na rodice
 *
 * Umoznuje hierarchicke vybirani moznosti napr. rubrika->podrubrika
 * Pole $items musi mit strukturu ve tvaru
 *     array('id' => 1, 'parent' => 10, 'title' => 'title')
 * kde klic 'parent' je ID nadrazeneho selectu.
 *
 *
 * Example:
 * $makes = sql::toPairs('SELECT id, title FROM makes ORDER BY title');
 * $models = sql::toArray('SELECT id, make as parent, title FROM models ORDER BY title');
 * $form->addSelect('make', 'make', $makes);
 * $form->addComponent(new SelectBoxBindMulti('model', $models, $form['make']), 'model');
 *
 * @author       Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class SelectBoxBindMulti extends SelectBox
{

	protected $parent;
	protected $script;

	/**
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  FormControl     parent FormControl
	 * @param  int       number of rows that should be visible
	 */
	public function __construct($label, $items = NULL, $parent, $size = NULL)
	{
		$this->parent = $parent;
		parent::__construct($label, $items, $size);
	}

	public function getControl()
	{
		$this->rendered = TRUE;
		$control = clone $this->control;
		$control->name = $this->getHtmlName();
		$control->disabled = $this->disabled;
		$control->readonly = $this->readonly;
		$control->tabIndex = $this->tabIndex;
		$control->id = $this->getId();

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

		if ($this->autoSubmit) {
			if ($this->autoSubmitAjax) {
				$control->onchange($control->onchange . ';this.form.onsubmit()');
			} else {
				$control->onchange('this.form.submit()');
			}
		}

		$selected = $this->getValue();
		$selected = is_array($selected) ? array_flip($selected) : array($selected => TRUE);
		$option = Html::el('option');

		$items = $this->prepareItems();
		foreach ($items as $key => $value) {
			if (!is_array($value)) {
				$value = array($value);
				$dest = $control;
			} else {
				$dest = $control->create('optgroup')->label($key);
			}

			foreach ($value as $item) {
				if ($this->useKeys) {
					$option
						->value($item->id)
						->selected(isset($selected[$item->id]))
						->setHtml($item->title);
					$control->add((string)$option);
				} else {
					$option
						->selected(isset($selected[$item->id]))
						->setHtml($item->title);
					$dest->add((string)$option);
				}
			}
		}

		return $control;
	}

	public function setValue($value)
	{
		$pname = $this->parent->getName();
		$parent = $this->getSubForm()->getComponent($pname);
		foreach ($this->items as $item) {
			if ($value == $item->id) $parent->setValue($item->parent);
		}
		parent::setValue($value);
		$this->reloadItems();
		return $this;
	}

	public function setValueIn($value)
	{
		$pname = $this->parent->getName();
		$parent = $this->getSubForm()->getComponent($pname);
		foreach ($this->items as $item) {
			if ($value == $item->id) {
				$parent->setValue($item->parent);
			}
		}
		parent::setValueIn($value);
		$this->reloadItems();
		return $this;
	}

	public function loadHttpData($data)
	{
		parent::loadHttpData($data);
		$this->reloadItems();
	}

	protected function reloadItems()
	{
		$this->setItems($this->items);
	}

	/**
	 * Sets items from which to choose.
	 * @param  array
	 * @return SelectBox  provides a fluent interface
	 */
	public function setItems($items, $useKeys = FALSE)
	{
		$this->items = $items;
		$this->allowed = array();
		$pname = $this->parent->getName();
		try {
			$pId = $this->getSubForm()->getComponent($pname)->getValue();
		} catch (Exception $e) {
			$pId = null;
		}

		foreach ($items as $key => $value) {
			if (!is_object($value)) {
				throw new /*\*/
				InvalidArgumentException("Value must be object.");
			}
			if ($pId == $value->parent) {
				$this->allowed[$value->id] = $value->title;
			}
		}
		return $this;
	}

	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri . 'js/core/bselect.js';
		return array_merge(parent::getJavascript(), $js);
	}

}

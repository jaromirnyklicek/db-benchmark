<?php


/**
 * SelectBox navazani na jiny SelectBox. Umoznuje hierarchicke vybirani moznosti napr. rubrika->podrubrika
 * Pole $items musi mit strukturu ve tvaru
 * 	 array('id' => 1, 'parent' => 10, 'title' => 'title')
 * kde klic 'parent' je ID nadrazeneho selectu.
 * 
 * POZOR: nefunguje v multiformu. V multiformu se to musi resit jinak nekoncepcne. 
 * 
 * Example:
 * $makes = sql::toPairs('SELECT id, title FROM makes ORDER BY title');
 * $models = sql::toArray('SELECT id, make as parent, title FROM models ORDER BY title');
 * $form->addSelect('make', 'make', $makes);
 * $form->addSelectBind('model', 'model', $models, $form['make']);
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */
class SelectBoxBind extends SelectBox
{
	protected $parent;
	protected $bindItems;
	protected $parentIds = array();
	protected $script;


	/**
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  FormControl	 parent FormControl
	 * @param  int	   number of rows that should be visible
	 */
	public function __construct($label, $items = NULL, $parent = NULL, $size = NULL)
	{
		$this->parent = $parent;
		parent::__construct($label, $items, $size);
		$this->monitor('Form');
	}


	public function attached($obj)
	{
		$this->parent->getControlPrototype()->onchange(
				$this->parent->getControlPrototype()->onchange . ';' . $this->getId() . 'SetItems();'
		);
	}


	public function setValue($value)
	{
		if (key_exists($value, $this->parentIds)) {
			$this->parent->setValue($this->parentIds[$value]);
		}
		parent::setValue($value);
		$this->reloadItems();
		return $this;
	}


	public function setValueIn($value)
	{
		if (key_exists($value, $this->parentIds)) {
			$this->parent->setValue($this->parentIds[$value]);
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
		$this->setItems($this->bindItems);
	}


	/**
	 * Sets items from which to choose.
	 * @param  array
	 * @return SelectBox  provides a fluent interface
	 */
	public function setItems($items, $useKeys = TRUE)
	{
		$this->bindItems = $items;
		$this->items = array();
		$this->allowed = array();
		$this->parentIds = array();
		$this->useKeys = (bool) $useKeys;
		$parentValue = $this->parent->getValue();
		$scripts = array();
		foreach ($items as $value2) {
			if (!is_array($value2)) {
				$value = array($value2);
			}

			foreach ($value as $value) {
				if (!is_object($value)) {
					throw new InvalidArgumentException("Value must be object.");
				}
				$title = $value->title;
				$currentParent = $value->parent;
				if (!$this->useKeys) {
					if (!is_scalar($value->title)) {
						throw new InvalidArgumentException("All item titles must be scalars.");
					}
					$key = $title;
				} else {
					$key = $value->id;
				}
				if (isset($this->parentIds[$key]) && $this->parentIds[$key] != $currentParent) {
					throw new InvalidArgumentException("Items contain duplication for key '$key'.");
				}

				$scripts[] = '[\'' . addcslashes($key, "'") . '\',' . $currentParent . ', \'' . addcslashes($title, "'") . '\']';
				$this->parentIds[$key] = $currentParent;
				if ($parentValue == $currentParent) {
					$this->items[$key] = $this->allowed[$key] = $title;
				}
			}
		}
		$this->script = '[' . implode(',', $scripts) . ']';
		return $this;
	}


	public function getControl()
	{
		$this->reloadItems();
		$control = parent::getControl();
		$el = Html::el();
		$el->add($control);
		$el->add($this->getJsForControl());
		return $el;
	}


	public function getJsForControl()
	{
		$jsArr = $this->getId() . 'Arr';
		$setFnc = $this->getId() . 'SetItems';
		$js = Html::el('script')->type('text/javascript')->setHtml('/* <![CDATA[ */
			var ' . $jsArr . ' = ' . $this->script . '
		//]]>');
		$jsf = $setFnc . ' = function() {
			items = Array(); 
			j = 0;
			pId = document.getElementById(\'' . $this->parent->getId() . '\').value;
			for (var i=0; i < ' . $jsArr . '.length; i++) {
				if(' . $jsArr . '[i][1] == pId) {
					items[j] = [' . $jsArr . '[i][0], ' . $jsArr . '[i][2]];
					j++;
				}
			}
			var arr = [[\'\', \'' . $this->nullOption . '\']];
			for (var i=0; i<items.length; i++) {
				arr[i+1] = [items[i][0], items[i][1]];
			} 
			SetListboxOptions(arr, document.getElementById(\'' . $this->getId() . '\'));
		}';

		$js2 = Html::el('script')->type('text/javascript')->setHtml('/* <![CDATA[ */
		' . $jsf . '
		  /* ]]> */
		');

		$el = Html::el();
		$el->add($js);
		$el->add($js2);
		return $el;
	}


	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri . 'js/core/bselect.js';
		return array_merge(parent::getJavascript(), $js);
	}

}

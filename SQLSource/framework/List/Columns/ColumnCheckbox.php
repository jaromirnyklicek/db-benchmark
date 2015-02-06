<?php

/**
 * Column Checkbox
 *
 * @package Lists
 * @author Ondrej Novak
 * @copyright Copyright (c) 2009, Ondrej Novak
 * @version 1.0
 */
class ColumnCheckbox extends Column
{

	protected $sortable = FALSE;
	protected $checked = FALSE;

	public function setChecked($value = TRUE)
	{
		$this->checked = $value;
		return $this;
	}

	public function render($applyCallback = TRUE)
	{
		$m = $this->member;
		// jde o atribut objektu
		if (strpos($m, '->')) {
			$arr = split('->', $m);
			$i = 0;
			$obj = $this->row;
			while ($i < count($arr)) {
				$m = $arr[$i];
				$obj = $obj->$m;
				$i++;
			}
			$value = $obj;
		} else if (!empty($m)) {
			if (!isset($this->row->$m))
				$value = '';
			else
				$value = $this->row->$m;
		}
		else
			$value = '';
		if (isset($this->callbackArr) && $applyCallback) {
			foreach ($this->callbackArr as $callback) {
				if (is_callable($callback['func'])) {
					if ($callback['type'] == 'simple') {
						$args = array_merge(array($value), $callback['params']);
					}
					if ($callback['type'] == 'function') {
						$dataRow = new DataRow($this->row, $this);
						if ($callback['params'] != NULL)
							$args = array_merge(array($value, $dataRow), $callback['params']);
						else
							$args = array_merge(array($value, $dataRow));
					}
					$value = call_user_func_array($callback['func'], $args);
				}
				else {
					throw new Exception('Invalid callback ' . $callback['func'] . '()');
				}
			}
		}
		$list = $this->dataList->getName();
		$class = $list . '_select_chb';

		$id = $list . $this->name . '_' . $value;
		$value = '<input type="checkbox" data-name="' . $id . '" ' . ($this->checked ? 'checked="checked"' : "") . ' class="' . $class . '" ' . (empty($value) ? 'disabled="disabled"' : '') . ' value="' . $value . '" name="' . $this->name . '[]" id="' . $id . '"/>';
		if (!empty($value))
			return sprintf($this->envelope, $value);
	}

}

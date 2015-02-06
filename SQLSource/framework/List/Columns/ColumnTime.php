<?php
/**
* ...
*
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version 1.0
*/

class ColumnTime extends Column
{
	public function __construct($name, $member = '', $title = '', $width = '', $style = '', $cssClass = '', $envelope = '%s')
	{
		parent::__construct($name, $member, $title, $width, $style, $cssClass, $envelope);
		$this->addHelper('Helpers::minutesToTime');
	}

	public function render($applyCallback = true)
	{
		return parent::render($applyCallback);
	}

}
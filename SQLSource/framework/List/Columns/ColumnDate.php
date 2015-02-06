<?php
/**
* Column je popis sloupce pro pouziti v Listech (DataGrid, DataList, DataView)
*
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version 1.0
*/

class ColumnDate extends Column
{
	public static $format = 'j.n.Y';

	protected $dateFormat;

	public function __construct($name, $member = '', $title = '', $width = '', $style = '', $cssClass = '', $envelope = '%s')
	{
		parent::__construct($name, $member, $title, $width, $style, $cssClass, $envelope);
		$this->addHelper(array($this, 'date'));
	}

	public function setFormat($value)
	{
		$this->dateFormat = $value;
		return $this;
	}

	public function render($applyCallback = true)
	{
		return parent::render($applyCallback);
	}

	public function date($value)
	{
		if(!$value instanceof Timestamp) $value = new Timestamp($value);
		if($value->isNull()) return '';
		$ts = $value->getAsTs();
		return date($this->dateFormat ? $this->dateFormat : self::$format, $ts);
	}


}
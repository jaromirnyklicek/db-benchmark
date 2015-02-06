<?php
/**
* Column je popis sloupce pro pouziti v Listech (DataGrid, DataList, DataView)
*
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version 1.0
*/

class ColumnDateTime extends Column
{
	public static $format = 'j.n.Y H:i';

	/**
	* casova zona
	*
	* @var string
	*/
	public static $timeZone = NULL;

	protected $dateFormat = NULL;

	public function __construct($name, $member = '', $title = '', $width = '', $style = '', $cssClass = '', $envelope = '%s')
	{
		parent::__construct($name, $member, $title, $width, $style, $cssClass, $envelope);
		$this->addHelper(array($this, 'date'));
	}

	/**
	* Nastavi format datumu, ktery prebije defaultni format ve staticke property
	*
	* @param string $value
	* @return ColumnDateTime
	*/
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

		$dateTime = $value->getAsDateTime();
		if (self::$timeZone) {
			$dateTimeZone = new DateTimeZone(self::$timeZone);
			$dateTime->setTimezone($dateTimeZone);
		}
		$format = $this->dateFormat ? $this->dateFormat : self::$format;
		return $dateTime->format($format);
	}
}
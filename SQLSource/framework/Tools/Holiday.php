<?php

require_once('Easter.php');

/**
* Trida pro statni/velikonocni svatky
*/
class Holiday
{
	public static $nationalsDays;

	/**
	* Je svatek?
	*
	* @param int $day
	* @param int $month
	* @param int $year
	*/
	public static function isHoliday($day, $month, $year = NULL)
	{
		if(self::isNationalDay($day, $month)) return true;
		if($year !== NULL) {
			$easter = Easter::getMonday($year);
			return date('j', $easter) == $day && $month == date('n', $easter);
		}
		return false;
	}

	/**
	* Je statni svatek? periodicky kazdy rok ve stejny datum. (bez velikonoc)
	*
	* @param int $day
	* @param int $month
	*/
	public static function isNationalDay($day, $month)
	{
		if(self::$nationalsDays === NULL) {
			self::$nationalsDays = array();
			self::$nationalsDays[] = new NationalDay(1,1, "Den obnovy samostatného českého státu");
			self::$nationalsDays[] = new NationalDay(8,5, "Den osvobození");
			self::$nationalsDays[] = new NationalDay(5,7, "Den slovanských věrozvěstů Cyrila a Metoděje");
			self::$nationalsDays[] = new NationalDay(6,7, "Den upálení mistra Jana Husa");
			self::$nationalsDays[] = new NationalDay(28,9, "Den české státnosti");
			self::$nationalsDays[] = new NationalDay(28,10, "Den vzniku samostatného československého státu");
			self::$nationalsDays[] = new NationalDay(17,11, "Den boje za svobodu a demokracii");
			self::$nationalsDays[] = new NationalDay(1,5, "Svátek práce");
			self::$nationalsDays[] = new NationalDay(24,12, "Štědrý den");
			self::$nationalsDays[] = new NationalDay(25,12, "1. svátek vánoční");
			self::$nationalsDays[] = new NationalDay(26,12, "2. svátek vánoční");
		}
		foreach(self::$nationalsDays as $d) {
			if($d->isEqual($day, $month)) return true;
		}
		return false;
	}

	/**
	* Je vikend?
	*
	* @param int $day
	* @param int $month
	* @param int $year
	*/
	public static function isWeekend($day, $month, $year = NULL)
	{
		return date('N', mktime(0,0,0, $month, $day, $year)) >= 6;
	}
}

class NationalDay
{
	private $day;
	private $month;
	private $title;

	public function __construct($day, $month, $title = '')
	{
		$this->day = $day;
		$this->month = $month;
		$this->title = $title;
	}

	public function isEqual($day, $month)
	{
		return $day == $this->day && $month == $this->month;
	}

	public function getDay()
	{
		return $this->day;
	}

	public function getMonth()
	{
		return $this->month;
	}

	public function getTitle()
	{
		return $this->title;
	}
}
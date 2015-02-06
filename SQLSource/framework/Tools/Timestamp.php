<?php


/**
 * Trida pro praci s datumem a casem.
 *
 * Uprava 16.10.2014:
 * 		metody getAsCSN(), getAsISO() a getAsTs() vracaju NULL, ak je isNull() TRUE; vsetky set* metody vracaju $this
 *
 *
 * @author Ondrej Novak
 * @copyright Copyright (c) 2009, Ondrej Novak
 * @version 1.0
 */
class Timestamp
{
	protected $day;
	protected $month;
	protected $year;
	protected $hours = 0;
	protected $minutes = 0;
	protected $seconds = 0;


	public function __construct($date = null)
	{
		$this->set($date);
	}


	public static function factory($date)
	{
		return new Timestamp($date);
	}


	function getDay()
	{
		return $this->day;
	}


	function getMonth()
	{
		return $this->month;
	}


	function getYear()
	{
		return $this->year;
	}


	function getHours()
	{
		return $this->hours;
	}


	function getMinutes()
	{
		return $this->minutes;
	}


	function getSeconds()
	{
		return $this->seconds;
	}


	function setHours($hours)
	{
		$this->hours = (int) $hours;
		return $this;
	}


	function setMinutes($minutes)
	{
		$this->minutes = (int) $minutes;
		return $this;
	}


	function setSeconds($seconds)
	{
		$this->seconds = (int) $seconds;
		return $this;
	}


	function setDay($day)
	{
		$this->day = (int) $day;
		return $this;
	}


	function setMonth($month)
	{
		$this->month = (int) $month;
		return $this;
	}


	function setYear($year)
	{
		/* if (strlen($year) == 2)
		  {
		  if ($year >= $this->getCenturyDivider())
		  {
		  $year += 1900;
		  }
		  else
		  {
		  $year += 2000;
		  }
		  } */
		$this->year = (int) $year;
		return $this;
	}


	function getWeek()
	{
		return date('W', $this->getAsTs());
	}


	/**
	 * Sets date from given date-string, date-object or unix-timestamp
	 * return true on success, otherwise false
	 *
	 * @access public
	 * @param mixed $date
	 * @return string new date
	 * @since v1.0
	 * @version 2005-01-26
	 *
	 */
	function set($date = NULL)
	{
		/* if ( NULL === $date )   {
		  return $this->setFromTs( time() );
		  } */

		if (is_numeric($date)) {
			return $this->setFromTs($date);
		}

		if (is_object($date) && get_class($date) == 'Timestamp') {
			return $this->setFromIso($date->getAsIso(true));
		}

		if (preg_match('|\.|', $date)) {
			// date in form d.m.y
			return $this->setFromCSN($date);
		}

		if (preg_match('|\/|', $date)) {
			// date is in form m/d/y
			return $this->setFromAmi($date);
		}

		if (preg_match('|\-|', $date)) {
			// date is in form YYYY-MM-DD
			return $this->setFromIso($date);
		}

		if (empty($date)) {
			// date is '', so we use 0000-00-00
			return $this->setFromIso('0000-00-00');
		}

		//trigger_error( 'unknown date-format: ' . var_export( $date, true ) . '(' . $_SERVER['REQUEST_URI'] . ')', E_USER_WARNING );
		//return $this->setFromTs( time() );

		return $this;
	}


	/**
	 * Sets date from timestamp
	 *
	 * @access public
	 * @param integer $timestamp
	 * @since v1.3
	 * @version 2004-06-30
	 *
	 * @uses date()
	 * @uses Date::setYear()
	 * @uses Date::setMonth()
	 * @uses Date::setDay()
	 */
	function setFromTs($timestamp)
	{
		// Zruseno 13.3.2011 - hrajeme pouze na unix timestamp. Delalo problem napr. pri 1300022300

		/*
		  // value must be some sort of Timestamp UNIX or MySQL < 4.1
		  // MySQL-Timestamp Values
		  $YY   = '([0-9]{2,4})';					   // =   00 -	 9999
		  $MM   = '(0[0-9]{1}|1[0-2]{1})';		   // =   00 -	 12
		  $DD   = '([0-2]{1}[0-9]{1}|30|31)';		   // =   00 -	 31
		  $HH   = '([0-1]{1}[0-9]{1}|2[0-3]{1})';    // =   00 -	 23
		  $SS   = '([0-5]{1}[0-9]{1})';			   // =   00 -	 59

		  // MySQL-TIMESTAMP(14)	   YY(YY)MMDDHHMMSS
		  // MySQL-TIMESTAMP(12)	   YYMMDDHHMMSS
		  // MySQL-TIMESTAMP(10)	   YYMMDDHHMM
		  // MySQL-TIMESTAMP(8)	  YYYYMMDD
		  // MySQL-TIMESTAMP(6)	  YYMMDD
		  // MySQL-TIMESTAMP(4)	  YYMM
		  // MySQL-TIMESTAMP(2)	  YY
		  if ( preg_match('#^' . $YY . '(' . $MM . '(' . $DD . '(' . $HH . $SS . '(' . $SS . ')?)?)?)?$#', $timestamp, $date_parts ) )
		  {
		  $this->setDay($date_parts[3]);
		  $this->setMonth($date_parts[2]);
		  $this->setYear($date_parts[1]);
		  }
		 */
		// a UNIX-TimeStamp ... ?

		if ($timestamp > 0) {
			$this->setFromUnixTimestamp($timestamp);
		}

		return $this;
	}


	/**
	 * sets date from given unix timestamp
	 *
	 * @access public
	 * @param int $unix_timestamp
	 * @since 2004-06-30
	 *
	 * @uses date()
	 * @uses Date::setYear()
	 * @uses Date::setMonth()
	 * @uses Date::setDay()
	 */
	function setFromUnixTimestamp($unix_timestamp)
	{
		$this->setDay(date('d', $unix_timestamp));
		$this->setMonth(date('m', $unix_timestamp));
		$this->setYear(date('Y', $unix_timestamp));
		$this->setHours(date('G', $unix_timestamp));
		$this->setMinutes(date('i', $unix_timestamp));
		$this->setSeconds(date('s', $unix_timestamp));

		return $this;
	}


	/**
	 * Sets date from ČSN format (DD.MM.YYYY or D.M.YY)
	 *
	 * @access public
	 * @param string $datestring
	 * @since v1.0
	 *
	 * @uses explode()
	 * @uses Date::setYear()
	 * @uses Date::setMonth()
	 * @uses Date::setDay()
	 */
	function setFromCSN($datestring)
	{
		if ($datestring !== NULL && $datestring !== '') {
			// cut time
			$datePart = $this->cutTime($datestring);

			// split date parts  dd.mm.yyyy
			$date = explode('.', $datePart);

			$this->setDay(isset($date[0]) ? $date[0] : NULL);
			$this->setMonth(isset($date[1]) ? $date[1] : NULL);
			$this->_setYear(isset($date[2]) ? $date[2] : NULL);
		}
		return $this;
	}


	/**
	 * Sets date from US format: month/day/year
	 * / can be any non-numeric character
	 * m/d/y or m-d-y or m.d.y or m d y
	 *
	 * @access public
	 * @param string $datestring
	 * @since v1.0
	 * @version 2004-08-03
	 *
	 * @uses explode()
	 * @uses Date::setYear()
	 * @uses Date::setMonth()
	 * @uses Date::setDay()
	 */
	function setFromAmi($datestring)
	{
		if ($datestring !== NULL && $datestring !== '') {
			// cut time
			$datePart = $this->cutTime($datestring);

			// split date parts  mm/dd/yyyy
			$date = explode('/', $datePart);

			$this->setMonth(isset($date[0]) ? $date[0] : NULL);
			$this->setDay(isset($date[1]) ? $date[1] : NULL);
			$this->_setYear(isset($date[2]) ? $date[2] : NULL);
		}
		return $this;
	}


	/**
	 * Sets date from ISO format (YYYY-MM-DD)
	 */
	function setFromIso($datestring)
	{
		if ($datestring !== NULL && $datestring !== '') {
			// cut time
			$datePart = $this->cutTime($datestring);

			// split date parts  yyyy-mm-dd
			$date = explode('-', $datePart);

			$this->_setYear(isset($date[0]) ? $date[0] : NULL);
			$this->setMonth(isset($date[1]) ? $date[1] : NULL);
			$this->setDay(isset($date[2]) ? $date[2] : NULL);
		}
		return $this;
	}


	/**
	 * Odreze cast obsahujucu cas, nastavi cas (ak existuje) a vrati cast obsahujucu datum.
	 * Na vstup aplikuje trim().
	 * 
	 * @param string $datestring
	 * @return string cast datumu obsahujuca datum
	 */
	private function cutTime($datestring)
	{
		$dateParts = explode(' ', trim($datestring));
		if (isset($dateParts[1])) {
			if (isset($dateParts[2])) { // am/pm
				$dateParts[1] .= ' ' . $dateParts[2];
			}
			$this->setTimeFromString($dateParts[1]);
		}
		return isset($dateParts[0]) ? $dateParts[0] : NULL;
	}


	private function _setYear($year)
	{
		if ($year == 0) {
			$this->setYear(0);
		} else {
			$this->setYear($year > 100 ? $year : ($year + 2000));
		}
		return $this;
	}


	function setTimeFromSeconds($seconds = 0.0)
	{
		$seconds = (float) $seconds;
		if ($seconds < 0) {
			$seconds = $seconds * -1;
		}

		$this->setHours(floor($seconds / 60 / 60));
		$seconds = $seconds % ( 60 * 60 );
		$this->setMinutes(floor($seconds / 60));
		$seconds = $seconds % 60;
		$this->setSeconds($seconds);
		return $this;
	}


	/**
	 * sets time from string in form [H]HH:MM[:SS[.s]]
	 * returns new time
	 *
	 * @access public
	 * @uses Time::get() as return value
	 * @uses Time::setSign()
	 * @uses Time::setHours()
	 * @uses Time::setMinutes()
	 * @uses Time::setSeconds()
	 * @param string time
	 * @return Time::get()
	 * @todo implement support for micro-seconds
	 */
	function setTimeFromString($time)
	{
		preg_match("/(\-)?([0-9]*):([0-5]{1}[0-9]{1})(:([0-5]{1}[0-9]{1}(.[0-9]*)?))?(\s+(am|pm))?$/", $time, $time_split);

		if (isset($time_split[1]) && $time_split[1] == '-') {
			//$this->setSign(-1);
		} else {
			//$this->setSign(1);
		}

		if (isset($time_split[2])) {
			if (isset($time_split[8]) == 'pm') {
				$this->setHours($time_split[2] + 12);
			} else {
				$this->setHours($time_split[2]);
			}
		}

		if (isset($time_split[3])) {
			$this->setMinutes($time_split[3]);
		}

		if (isset($time_split[5])) {
			$this->setSeconds($time_split[5]);
		}
		return $this;
	}


	/**
	 * returns time-string in form [H]HH:MM[:SS[:s]]
	 */
	function getTime($seconds = TRUE)
	{
		if ($seconds) {
			return sprintf("%02d:%02d:%02d", $this->getHours(), $this->getMinutes(), $this->getSeconds());
		}
		return sprintf("%02d:%02d", $this->getHours(), $this->getMinutes());
	}


	function get($format = 'j.n.Y')
	{
		return date($format, $this->getAsTs());
	}


	/**
	 * returns Date as timestamp
	 */
	function getAsDateTime()
	{
		return new DateTime($this->getAsIso(TRUE));
	}


	/**
	 * returns Date as timestamp
	 */
	function getAsTs()
	{
		if ($this->isNull()) {
			return NULL;
		}
		return mktime($this->getHours(), $this->getMinutes(), $this->getSeconds(), $this->getMonth(), $this->getDay(), $this->getYear());
	}


	function getAsCSN($time = FALSE)
	{
		if ($this->isNull()) {
			return NULL;
		}
		$s = sprintf("%02d.%02d.%04d", $this->getDay(), $this->getMonth(), $this->getYear());
		if ($time) {
			$s .= ' ' . $this->getTime();
		}
		return $s;
	}


	function getAsIso($time = FALSE)
	{
		if ($this->isNull()) {
			return NULL;
		}
		$s = sprintf("%04d-%02d-%02d", $this->getYear(), $this->getMonth(), $this->getDay());
		if ($time) {
			$s .= ' ' . $this->getTime();
		}
		return $s;
	}


	public static function datetime2Iso($value)
	{
		return self::factory($value)->getAsIso(TRUE);
	}


	public static function date2Iso($value)
	{
		return self::factory($value)->getAsIso(FALSE);
	}


	public function __toString()
	{
		if ($this->isNull()) {
			return '';
		}
		return $this->getAsIso(TRUE);
	}


	public function isToday()
	{
		return $this->month == date('m') && $this->day == date('d') && $this->year == date('Y');
	}


	public function isYesterday()
	{
		$ts = time() - 3600 * 24;
		return $this->month == date('m', $ts) && $this->day == date('d', $ts) && $this->year == date('Y', $ts);
	}


	public function isTomorrow()
	{
		$ts = time() + 3600 * 24;
		return $this->month == date('m', $ts) && $this->day == date('d', $ts) && $this->year == date('Y', $ts);
	}


	public function isNull()
	{
		if ($this->day + $this->month + $this->year + $this->hours + $this->minutes + $this->seconds == 0) {
			return TRUE;
		}
		return FALSE;
	}


	/**
	 * Pondeli v tydnu $wk_num
	 *
	 * @param mixed $wk_num
	 * @param mixed $yr
	 * @return Timestamp
	 */
	public static function startOfWeek($wk_num, $yr)
	{
		$time = strtotime($yr . '0104 +' . ($wk_num - 1) . ' weeks');
		$mon_ts = strtotime('-' . (date('N', $time) - 1) . ' days', $time);
		return new self($mon_ts);
	}

}

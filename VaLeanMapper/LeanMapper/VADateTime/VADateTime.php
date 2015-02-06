<?php

use Carbon\Carbon;

class VADateTime extends Carbon
{
	public static function factory($date)
	{
		$dateTime = new self();
		$dateTime->set($date);
		return $dateTime;
	}

	public function getDay()
	{
		return $this->day;
	}

	public function getMonth()
	{
		return $this->month;
	}

	public function getYear()
	{
		return $this->year;
	}

	public function getHours()
	{
		return $this->hour;
	}

	public function getMinutes()
	{
		return $this->minute;
	}

	public function getSeconds()
	{
		return $this->second;
	}

	public function setHours($hours)
	{
		$this->hour($hours);
		return $this;
	}

	public function setMinutes($minutes)
	{
		$this->minute($minutes);
		return $this;
	}

	public function setSeconds($seconds)
	{
		$this->second($seconds);
		return $this;
	}

	public function setDay($day)
	{
		$this->day($day);
		return $this;
	}

	public function setMonth($month)
	{
		$this->month($month);
		return $this;
	}

	public function setYear($year)
	{
		$this->year($year);
		return $this;
	}

	public function getWeek()
	{
		return date('W', $this->getAsTs());
	}

	/**
	 * Detekuje predany parametr (timestamp, iso string, DateTime...) a nastavi
	 * podle nej datum (a cas).
	 *
	 * @param mixed $date
	 * @return string new date
	 */
	public function set($date = NULL)
	{
		if (is_numeric($date)) {
			return $this->setFromTs($date);
		}

		if (is_object($date) && get_class($date) == 'VADateTime') {
			return $this->setFromIso($date->getAsIso(true));
		}

		if (is_array($date) && array_key_exists('date', $date)) {
			$date = $date['date'];
		}

		// m.d.y
		if (preg_match('|\.|', $date)) {
			return $this->setFromCSN($date);
		}

		// m/d/y
		if (preg_match('|\/|', $date)) {
			return $this->setFromAmi($date);
		}

		// yyyy-mm-dd
		if (preg_match('|\-|', $date)) {
			return $this->setFromIso($date);
		}

		// pokud je datum prazdne, pouzijeme '0000-00-00;
		if (empty($date)) {
			return $this->setFromIso('0000-00-00');
		}
	}

	/**
	 * Nastavi datum z timestampu.
	 * V originalni implementaci nastavoval datum i z MySQL timestampu ve tvaru
	 * YYYYMMDDHHMMSS, vzhledem k problemum s implementaci to bylo vypusteno.
	 * Funguje tak pouze s unix timestampem.
	 *
	 * @param integer $timestamp
	 */
	public function setFromTs($timestamp)
	{
		if ($timestamp > 0) {
			$this->setFromUnixTimestamp($timestamp);
		}
	}

	/**
	 * Nastavi datum z unixoveho timestampu. Vzhledem k aktualnimu stavu
	 * se prakticky jedna o alias k self::setFromTs();
	 *
	 * @param int $unixTimestamp
	 */
	public function setFromUnixTimestamp($unixTimestamp)
	{
		$dateTime = parent::createFromTimestamp($unixTimestamp);
		$this->timestamp($dateTime->getTimestamp());
	}

	/**
	 * Nastavi datum z ceskeho narodniho formatu (dd.mm.yy(yy) nebo d.m.yy(yy))
	 *
	 * @param string $datestring
	 */
	public function setFromCSN($datestring)
	{
		$datestring = trim($datestring);

		// cut time
		$date = explode(' ', $datestring);
		if (isset($date[1])) {
			$time = $date[1];
			if (isset($date[2])) {
				$time = $date[1] . ' ' . $date[2];
			} //am/pm
			$this->setTimeFromString($time);
		} else {
			$this->setDayStartTime();
		}

		$date = explode('.', $date[0]);

		$this->setDay($date[0]);
		$this->setMonth($date[1]);
		if ($date[2] == 0) {
			$this->setYear(0);
		} else {
			$this->setYear($date[2] > 100 ? $date[2] : ($date[2] + 2000));
		}

	}

	/**
	 * Nastavi datum z americkeho formatu m/d/y, kde misto lomitka (/)
	 * muze byt jakykoliv neciselny znak, napr: m/d/y, m-d-y, m.d.y, m d y.
	 *
	 * @param string $datestring
	 */
	public function setFromAmi($datestring)
	{
		$datestring = trim($datestring);

		// cut time
		$date = explode(' ', $datestring);
		if (isset($date[1])) {
			$time = $date[1];
			if (isset($date[2])) {
				$time = $date[1] . ' ' . $date[2];
			} //am/pm
			$this->setTimeFromString($time);
		} else {
			$this->setDayStartTime();
		}

		$date = explode('/', $date[0]);
		$this->setDay($date[1]);
		$this->setMonth($date[0]);
		if ($date[2] == 0) {
			$this->setYear(0);
		} else {
			$this->setYear($date[2] > 100 ? $date[2] : ($date[2] + 2000));
		}
	}

	/**
	 * Nastavi datum z ISO formatu (YYYY-MM-DD).
	 */
	public function setFromIso($datestring)
	{
		$datestring = trim($datestring);

		$date = explode(' ', $datestring);
		if (isset($date[1])) {
			$time = $date[1];
			if (isset($date[2])) {
				$time = $date[1] .= ' ' . $date[2];
			} //am/pm
			$this->setTimeFromString($time);
		} else {
			$this->setDayStartTime();
		}

		$date = explode('-', $date[0]);
		$this->setDay($date[2]);
		$this->setMonth($date[1]);
		if ($date[0] == 0) {
			$this->setYear(0);
		} else {
			$this->setYear($date[0] > 100 ? $date[0] : ($date[0] + 2000));
		}
	}

	public function setTimeFromSeconds($seconds = 0.0)
	{
		$seconds = (float)$seconds;
		if ($seconds < 0) $seconds = $seconds * -1;

		$this->setHours(floor($seconds / 60 / 60));
		$seconds = $seconds % (60 * 60);
		$this->setMinutes(floor($seconds / 60));
		$seconds = $seconds % 60;
		$this->setSeconds($seconds);
		return $this;
	}

	/**
	 * Nastravi cas z formatu [H]HH:MM[:SS[.s]]
	 *
	 * @param string time
	 * @return VADateTime
	 */
	function setTimeFromString($time)
	{
		preg_match("/(\-)?([0-9]*):([0-5]{1}[0-9]{1})(:([0-5]{1}[0-9]{1}(.[0-9]*)?))?(\s+(am|pm))?$/", $time, $time_split);
		if (isset($time_split[2])) {
			if (isset($time_split[8]) == 'pm') $this->setHours($time_split[2] + 12);
			else $this->setHours($time_split[2]);
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
	 * Vrati cas ve formatu [H]HH:MM[:SS[:s]]
	 */
	public function getTime($seconds = TRUE)
	{
		if ($seconds) {
			return sprintf("%02d:%02d:%02d", $this->getHours(), $this->getMinutes(), $this->getSeconds());
		} else {
			return sprintf("%02d:%02d", $this->getHours(), $this->getMinutes());
		}
	}

	/**
	 * Vrati datum zformatovane podle zadaneho formatu.
	 *
	 * @param string $format
	 * @return bool|string
	 */
	public function get($format = 'j.n.Y')
	{
		return date($format, $this->getAsTs());
	}

	/**
	 * Vrati datum jako instanci zakladni tridy DateTime
	 *
	 * @return DateTime
	 */
	public function getAsDateTime()
	{
		return new DateTime($this->getAsIso(TRUE));
	}

	/**
	 * Vrati timestamp.
	 *
	 * @return int
	 */
	public function getAsTs()
	{
		return mktime($this->getHours(), $this->getMinutes(), $this->getSeconds(), $this->getMonth(), $this->getDay(), $this->getYear());
	}

	/**
	 * Vrati datum (a cas) ve formatu podle ceske normy tj. d.m.Y H:i:s
	 * @param bool $time zobrazit vcetne casu
	 * @return string
	 */
	public function getAsCSN($time = false)
	{
		$s = sprintf("%02d.%02d.%04d", $this->getDay(), $this->getMonth(), $this->getYear());
		if ($time) $s .= ' ' . $this->getTime();
		return $s;
	}

	/**
	 * Vrati datum (a cas) v ISO formatu pouzivanem napr v MySQL (Y-m-d H:i:s)
	 * @param bool $time
	 * @return string
	 */
	public function getAsIso($time = false)
	{
		$s = sprintf("%04d-%02d-%02d", $this->getYear(), $this->getMonth(), $this->getDay());
		if ($time) $s .= ' ' . $this->getTime();
		return $s;
	}

	public static function datetime2Iso($value)
	{
		$ts = self::factory($value);
		if ($ts->isNull()) return NULL;
		return $ts->getAsIso(TRUE);
	}

	public static function date2Iso($value)
	{
		$ts = self::factory($value);
		if ($ts->isNull()) return NULL;
		return $ts->getAsIso(FALSE);
	}

	public function __toString()
	{
		if ($this->isNull()) {
			return '';
		} else {
			return $this->getAsIso(true);
		}
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

	public function isNull()
	{
		if ($this->getYear() == 0 && $this->getMonth() == 12 && $this->getDay() == 30) {
			return true;
		}

		return false;
	}

	public function setDayStartTime()
	{
		$this->setHours(0);
		$this->setMinutes(0);
		$this->setSeconds(0);
		return $this;
	}
}

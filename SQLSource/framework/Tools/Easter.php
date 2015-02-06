<?php

/**
* Trida pro zjistovani velikonocnich svatku
*/
class Easter 
{
	/**
	* Vrátí velkonoční neděli pro daný rok
	*	  
	* @param int $year
	* @return int
	*/
	public static function getSunday($year)
	{
		return mktime(0, 0, 0, 3, 21 + easter_days($year), $year);
	}
	
	/**
	* Vrátí sváteční velikonoční pondělí
	*	  
	* @param int $year
	* @return int
	*/
	public static function getMonday($year)
	{
		return self::getSunday($year) + 3600 * 24;
	}
	
}

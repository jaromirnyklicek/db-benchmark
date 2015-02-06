<?php

/**
 * Trida generuje aktualni cas serveru. Pouziva se pro optimalizaci SQL dotazu 
 * misto funkce NOW(), ktera se necachuje. Aplikacnim zaokrouhlenim NOW() na vteriny nebo minuty 
 * se dotaz bude cacheovat.
 *
 */
class Now {
	
	const ROUND_DOWN = 0;
	const ROUND_UP = 1;    
	
	/**
	 * Zaokrouhleni aktualniho casu dolu na sekundy. 
	 * Vraci datum naformatovane pro vstup do DB
	 *
	 * @return string 
	 */
	public static function seconds()
	{
		return date('Y-m-d H:i:s');
	}

	/**
	 * Zaokrouhleni aktualniho casu dolu na minuty. 
	 * Vraci datum naformatovane pro vstup do DB
	 *
	 * @return string 
	 */
	public static function minutes($minutes = 1)
	{	
		if($minutes != 1) {			   
			$m = date('i', time());
			if($m % $minutes != 0) $x = $minutes - 1;
			else $x = 0;
		}
		else $x = 0;
		return date('Y-m-d H:i:00', time() - (60 * $x));
	}

	/**
	 * Zaokrouhleni aktualniho casu dolu na hodiny. 
	 * Vraci datum naformatovane pro vstup do DB
	 *
	 * @return string 
	 */
	public static function hours()
	{
		return date('Y-m-d H:00:00');
	}
	
	/**
	 * Zaokrouhleni aktualniho casu dolu na dny. 
	 * Vraci datum naformatovane pro vstup do DB
	 *
	 * @param int $round Zaokrouhleni dolu nebo nahoru.
	 * @return string 
	 */
	public static function days($round = self::ROUND_DOWN)
	{
		if($round == self::ROUND_DOWN) return date('Y-m-d');
		else return date('Y-m-d', mktime(date('H'), date('i'), date('s'), date('m'), date('j') + 1, date('Y')));
	}

	
	public static function months($months = 1)
	{			
		return date('Y-m-d', mktime(date('H'), date('i'), date('s'), date('m') - $months, date('j'), date('Y')));
	}
	
	/**
	 * Vytvori dotaz pro vyber celeho dne. 
	 * MySql dotaz typu DATE_FORMAT(timestamp, '%Y-%c-%e' ) = "yyyy-mm-dd" 
	 * aplikacne prevede na (timestamp >= "yyyy-mm-dd" AND timestamp <= "yyyy-mm-(dd+1)")
	 *
	 * @param int $ts timestamp
	 * @param string $column Sloupec s datumem v databazi
	 */
	public static function day($ts, $column)
	{
		return '('.$column.' >= "'.date("Y-m-d", $ts).'" AND '.$column.' < "'.date("Y-m-d", $ts + 24 * 3600).'")';
	}
}
<?php
/**
 * Log Layer
 *
 *
 * @package    Log
 * @author	   Ondrej Novak
 * @copyright  (c) 2009 Ondrej Novak
 *
 * Umoznuje staticke volani logovacich funkci.
 *
 */
class Log {

	const STATUS   = 0;  // Stavove zpravy
	const NOTICE   = 1;  // Oznameni
	const WARNING  = 2;  // Uporozneni na provedeni nejakych akci
	const MISTAKE  = 3;  // Chyba od uzivatele na vstupu
	const RIGHTS   = 4;  // Nedostatecna opravneni
	const ERROR    = 5;  // Chyba programu
	const FATAL    = 6;  // Fatalni chyba programu, nelze pokracovat dale
	const DEBUG    = 7;  // Ladici zpravy

	 /**
	 * Log a message at a priority
	 *
	 * @param  integer	$priority  Priority of message
	 * @param  string	$message   Message to log
	 * @return void
	 */
	public static function write($priority, $message, $extra = array())
	{
		$log = Environment::getService('Logger');
		$log->log($priority, $message, $extra);
	}
}

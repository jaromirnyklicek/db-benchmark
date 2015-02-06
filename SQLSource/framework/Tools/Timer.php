<?php

/**
 * Trida pro mereni casu mezi volanimi.
 * Aktivuje se pres Timer::start();
 * Vystup se zobrazuje pouze pri $enabled = TRUE
 * Vystup lze presmerovat do firebugu pres $useFirebug = TRUE
 *
 */
class Timer {

	/**
	* Je zobrazovani casu na vystup aktivni?
	*
	* @var bool
	*/
	public static $enabled = FALSE;

	/**
	* Logovani probiha do firebugu. V opacnem pripade na standardni vystup.
	*
	* @var bool
	*/
	public static $useFirebug = FALSE;

	/**
	* pocatecni cas
	*/
	private static $start;

	/**
	* cas posledniho volani
	*
	*/
	private static $lastCall;

	/**
	* zapnuti mereni
	* @deprecated
	*/
	public static function start()
	{
		self::$start = microtime(TRUE);
		self::$lastCall = self::$start;
	}

	private static $table = array(array('Name', 'Delta time'));

	/**
	* Pridani mericiho bodu
	*
	* @param string $name - pojmenovani bodu
	*/
	public static function add($name = NULL)
	{
		if(!self::$enabled) return;

		if(empty($name)) {
			$bt = debug_backtrace();
			if(isset($bt[1]['function'])) $name = $bt[1]['function'].'()';
		}

		$now = microtime(TRUE);
		$delta = round(($now - self::$lastCall) * 1000);
		self::$lastCall = $now;
		$out = '';
		if($name) {
			$out .= $name.': ';
		}
		$out .= $delta.' ms';
		if(self::$useFirebug) {
			self::$table[] = array(
				$name,
				$delta.' ms'
			);

			header('X-Wf-Protocol-timer: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
			header('X-Wf-timer-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');
			header('X-Wf-timer-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');

			$payload = array(
				array(
					'Type' => 'TABLE',
					'Label' => 'Timer profiler',
				),
				self::$table,
			);
			$payload = @json_encode($payload);
			foreach (str_split($payload, 4990) as $num => $s) {
				$num++;
				header("X-Wf-timer-1-1-t$num: |$s|\\"); // protocol-, structure-, plugin-, message-index
			}
			header("X-Wf-timer-1-1-t$num: |$s|");
		}
		else {
			echo $out.'<br/>';
		}
	}

	/**
	* Vrátí celkový čas od startu v milisekundach
	*
	*/
	public static function getDuration()
	{
		return round((microtime(TRUE) - self::$start) * 1000);
	}
}

<?php
/**
 * Logovací třída
 *
 * @copyright  This solution is mostly based on Zend_Log (c) Zend Technologies USA Inc.
 *			   Zbytek Copyright (c) Ondrej Novák
 * @package    Log
 *
 * Třída pro logování operací. Logovací záznam má příznak priorita (status, notice, warning, error…)
 * Pro každou prioritu lze nastavit logovací writer. Lze připojit více writerů pro stejnou prioritu
 * nebo nastavit jeden writer pro všechny priority společný. Výchozí stav je nastavení pro kazdou
 * prioritu StreamWriter, který ukláda logy do samostatných souborů.
 */

class MessageLogger
{
	const STATUS   = 0;  // Stavove zpravy
	const NOTICE   = 1;  // Oznameni
	const WARNING  = 2;  // Uporozneni na provedeni nejakych akci
	const MISTAKE  = 3;  // Chyba od uzivatele na vstupu
	const RIGHTS   = 4;  // Nedostatecna opravneni
	const ERROR    = 5;  // Chyba programu
	const FATAL    = 6;  // Fatalni chyba programu, nelze pokracovat dale
	const DEBUG    = 7;  // Ladici zpravy


	 /**
	 * @var array of priorities kde klic je id a hodnota jmeno priority
	 */
	protected $priorities = array();

	/**
	 * Uchovani instance pro singleton
	 */
	private static $instance = NULL;

	/**
	 * @var array of Log_Writer
	 */
	protected $writers = array();



	public static function singleton($writer = NULL) {
		if (!isset(self::$instance)) {
			self::$instance = Environment::getService('Logger');
		}
		return self::$instance;
	}

	/**
	 * @param Log_Writer|null  $writer	default writer
	 */
	public function __construct(LogWriter $writer = NULL, $defaultWriters = TRUE)
	{
		$r = new ReflectionClass($this);
		$this->priorities = array_flip($r->getConstants());

		if ($writer !== NULL) {
			$this->addWriter($writer);
		}

		if($defaultWriters) {
			$logDir = Environment::getVariable('logDir').'/';
			$this->addWriter(new LogWriterStream($logDir.'status.log'), self::STATUS );
			$this->addWriter(new LogWriterStream($logDir.'notice.log'), self::NOTICE );
			$this->addWriter(new LogWriterStream($logDir.'warning.log'), self::WARNING );
			$this->addWriter(new LogWriterStream($logDir.'mistake.log'), self::MISTAKE );
			$this->addWriter(new LogWriterStream($logDir.'rights.log'), self::RIGHTS);
			$this->addWriter(new LogWriterStream($logDir.'error.log'), self::ERROR);
			$this->addWriter(new LogWriterStream($logDir.'fatal.log'), self::FATAL );
			$this->addWriter(new LogWriterStream($logDir.'debug.log'), self::DEBUG );
		}
	}

	/**
	 * Class destructor.  Shutdown log writers
	 *
	 * @return void
	 */
	public function __destruct()
	{
		foreach($this->writers as $i) {
			foreach($i as $writer) {
				$writer->shutdown();
			}
		}
	}

	/**
	 * Undefined method handler allows a shortcut:
	 *	 $log->priorityName('message')
	 *	   instead of
	 *	 $log->log('message', Log::PRIORITY_NAME)
	 *
	 * @param  string  $method	priority name
	 * @param  string  $params	message to log
	 * @return void
	 * @throws Log_Exception
	 */
	public function __call($method, $params)
	{
		$priority = strtoupper($method);
		if (($priority = array_search($priority, $this->_priorities)) !== false) {
			$this->log(array_shift($params), $priority);
		} else {
			throw new Log_Exception('Bad log priority');
		}
	}

	protected function makeEvent($priority, $message, $extra)
	{
		$requests = Environment::getApplication()->requests;
		$p = array();

		foreach ($requests as $request)  $p[] = $request->getPresenterName();
		$event = array_merge(array('timestamp'	  => time(),
						'message'	   => $message,
						'IP'	 => IP::detectIP(),
						'presenter' => join(',',$p),
						'URL'	  => Environment::getHttpRequest()->getOriginalUri()->getAbsoluteUri(),
						'request'	  => $this->formatArr($_REQUEST),
						'priority'	   => $priority,
						'priorityName' => $this->priorities[$priority]),
						$extra);
		return $event;
	}

	/**
	 * Log a message at a priority
	 *
	 * @param  integer	$priority  Priority of message
	 * @param  string	$message   Message to log
	 * @return void
	 * @throws Log_Exception
	 */
	public function log($priority, $message, $extra = array())
	{
		// sanity checks
		if (empty($this->writers[$priority])) {
			throw new Log_Exception('No writers were added for '.$this->priorities[$priority].' priority');
		}

		if (! isset($this->priorities[$priority])) {
			throw new Log_Exception('Bad log priority');
		}

		// pack into event required by writers
		$event = $this->makeEvent($priority, $message, $extra);

		// send to each writer
		if(isset($this->writers[-1]))
		{
			foreach ($this->writers[-1] as $writer) {
				$writer->write($event);
			}
		}

		foreach ($this->writers[$priority] as $writer) {
			$writer->write($event);
		}
	}

	public function formatArr($arr)
	{
		$ignore = '/pass|PHPSESSID/';
		$s = '';
		foreach($arr as $var => $value) {
			if (is_array($value)){
					if(!preg_match($ignore, $var)) {
						$s .= $var.'='.$this->formatArr($value).'&';
					}
					else {
						$s .= $var.'=*******&';
					}
			}else{
				if(!preg_match($ignore, $var)) {
					$s .= $var.'='.$value.'&';
				}
				else {
					$s .= $var.'=*******&';
				}
			}
		}
		return $s;
	}

	/**
	 * Pridani uzivatelske priority
	 *
	 * @param  string	$name	   Name of priority
	 * @param  integer	$priority  Numeric priority
	 * @throws Log_InvalidArgumentException
	 */
	public function addPriority($name, $priority)
	{
		// Priority names must be uppercase for predictability.
		$name = strtoupper($name);

		if (isset($this->_priorities[$priority])
			|| array_search($name, $this->priorities)) {
			throw new Log_Exception('Existing priorities cannot be overwritten');
		}

		$this->priorities[$priority] = $name;
	}



	/**
	 * Přidá logovací zapisovač pro kontrétní prioritu. Není-li zadaná úroveň, je zapisovač
	 * použit pro všechny priority.
	 *
	 * @param  LogWriter $writer
	 * @param  int $priority
	 * @return void
	 */
	public function addWriter(LogWriter $writer, $priority = -1)
	{
		$this->writers[$priority][] = $writer;
	}
}

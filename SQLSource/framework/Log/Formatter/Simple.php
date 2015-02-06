<?php

class LogFormatterSimple implements ILogFormatter
{

	/**
	 * @var string
	 */
	protected $format;

	const DEFAULT_FORMAT = '%timestamp%; %IP%; %message%; %presenter%; %URL%; REQUEST:%request%';

	/**
	 * Class constructor
	 *
	 * @param  null|string	$format  Format specifier for log messages
	 * @throws Zend_Log_Exception
	 */
	public function __construct($format = NULL)
	{
		if($format === NULL) {
			$format = self::DEFAULT_FORMAT . PHP_EOL;
		}

		if(!is_string($format)) {
			throw new Log_Exception('Format must be a string');
		}

		$this->format = $format;
	}

	/**
	 * Formats data into a single line to be written by the writer.
	 *
	 * @param  array	$event	  event data
	 * @return string			  formatted line to write to the log
	 */
	public function format($event)
	{
		$output = $this->format;
		$event['timestamp'] = date('d.m.Y H:i:s', $event['timestamp']);
		$event['IP'] = HelpersCore::IP($event['IP']);
		foreach($event as $name => $value) {

			if((is_object($value) && !method_exists($value, '__toString'))
				   || is_array($value)) {

				$value = gettype($value);
			}

			$output = str_replace("%$name%", $value, $output);
		}
		return $output;
	}

}

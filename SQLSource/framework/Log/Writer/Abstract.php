<?php

abstract class LogWriter
{
	
	/**
	 * Formats the log message before writing.
	 * @var ILogFormatter
	 */
	protected $formatter;
	
	/**
	 * Log a message to this writer.
	 *
	 * @param  array	 $event  log data event
	 * @return void
	 */
	public function write($event)
	{
		// exception occurs on error
		$this->_write($event);
	}

	/**
	 * Set a new formatter for this writer
	 *
	 * @param  ILogFormatter $formatter
	 * @return void
	 */
	public function setFormatter($formatter) 
	{
		$this->formatter = $formatter;
	}

	/**
	 * Perform shutdown activites such as closing open resources
	 *
	 * @return void
	 */
	public function shutdown()
	{}

	/**
	 * Write a message to the log.
	 *
	 * @param  array  $event  log data event
	 * @return void
	 */
	abstract protected function _write($event);

}
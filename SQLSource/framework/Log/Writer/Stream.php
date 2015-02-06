<?php

class LogWriterStream extends LogWriter
{
	/**
	 * Holds the PHP stream to log to.
	 * @var null|stream
	 */
	protected $stream = null;

	/**
	 * Class Constructor
	 *
	 * @param  streamOrUrl	   Stream or URL to open as a stream
	 * @param  mode			   Mode, only applicable if a URL is given
	 */
	public function __construct($streamOrUrl, $formatter = NULL, $mode = 'a')
	{
		if (is_resource($streamOrUrl)) {
			if (get_resource_type($streamOrUrl) != 'stream') {
				throw new Log_Exception('Resource is not a stream');
			}

			if ($mode != 'a') {
				throw new Log_Exception('Mode cannot be changed on existing streams');
			}

			$this->stream = $streamOrUrl;
		} else {
			if (! $this->stream = @fopen($streamOrUrl, $mode, false)) {
				$msg = "\"$streamOrUrl\" cannot be opened with mode \"$mode\"";
				throw new Log_Exception($msg);
			}
		}

		if($formatter == NULL) $this->formatter = new LogFormatterSimple();
		else $this->formatter = $formatter;
	}

	/**
	 * Close the stream resource.
	 *
	 * @return void
	 */
	public function shutdown()
	{
		if (is_resource($this->stream)) {
			fclose($this->stream);
		}
	}

	/**
	 * Write a message to the log.
	 *
	 * @param  array  $event  event data
	 * @return void
	 */
	public function _write($event)
	{
		$line = $this->formatter->format($event);

		if (false === @fwrite($this->stream, $line)) {
			throw new Log_Exception("Unable to write to stream");
		}
	}
}
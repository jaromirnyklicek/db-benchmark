<?php
/**
 * Logovací třída, ktera loguje i prihlaseneho uzivatele v namespace
 *
 */

class MessageLoggerWithUser extends MessageLogger
{
	const FORMAT = '%timestamp%; %IP%; %message%; USER:%user%; %presenter%; %URL%; REQUEST:%request%;';

	/**
	 * @param Log_Writer|null  $writer	default writer
	 */
	public function __construct(LogWriter $writer = NULL, $defaultWriters = TRUE)
	{

		parent::__construct($writer, FALSE);

		if($defaultWriters) {
			$logDir = Environment::getVariable('logDir').'/';
			$this->addWriter(new LogWriterStream($logDir.'status.log', new LogFormatterSimple(self::FORMAT)), self::STATUS );
			$this->addWriter(new LogWriterStream($logDir.'notice.log', new LogFormatterSimple(self::FORMAT)), self::NOTICE );
			$this->addWriter(new LogWriterStream($logDir.'warning.log', new LogFormatterSimple(self::FORMAT)), self::WARNING );
			$this->addWriter(new LogWriterStream($logDir.'mistake.log', new LogFormatterSimple(self::FORMAT)), self::MISTAKE );
			$this->addWriter(new LogWriterStream($logDir.'rights.log', new LogFormatterSimple(self::FORMAT)), self::RIGHTS);
			$this->addWriter(new LogWriterStream($logDir.'error.log', new LogFormatterSimple(self::FORMAT)), self::ERROR);
			$this->addWriter(new LogWriterStream($logDir.'fatal.log', new LogFormatterSimple(self::FORMAT)), self::FATAL );
			$this->addWriter(new LogWriterStream($logDir.'debug.log', new LogFormatterSimple(self::FORMAT)), self::DEBUG );
		}
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
		if(Environment::getUser()->isAuthenticated()) {
			$extra['user'] = Environment::getUser()->getIdentity()->getId();
		}
		parent::log($priority, $message, $extra);
	}
}
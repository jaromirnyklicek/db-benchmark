<?php

/**
*	Posila zpravy na email. Prvni zpravu posle hned a dalsi dava do bufferu, ktery se odesle pokud cas prekroci
*	nastaveny limit.
*/
class LogWriterEmail extends LogWriter
{
	/**
	 * Holds the PHP stream to log to.
	 * @var null|stream
	 */
	protected $stream = null;

	/**
	* Email, komu se ma zprava posilat
	*
	* @var string
	*/
	protected $email;

	/**
	* Predmet emailu
	*
	* @var string
	*/
	protected $subject;

	/**
	* Delka bufferu po ktery se zpravy hromadi k odeslani
	*
	* @var int
	*/
	protected $minutes;

	/**
	* Maximální počet emailů na časovou jednotku
	*
	* @var mixed
	*/
	protected $emailsLimit;


	public function __construct($email, $subject = 'Error report', $minutes = 5, $emailsLimit = 100)
	{
		$this->email = $email;
		$this->subject = $subject;
		$this->minutes = $minutes;
		$this->emailsLimit = $emailsLimit;

		$format = '%timestamp%; %IP%;
%message%;
Presenter: %presenter%;
URL: %URL%;
REQUEST:%request%

';
		$this->formatter = new LogFormatterSimple($format);
	}

	/**
	* Soubor pro unikatni chybu
	*
	*/
	protected function getFile($message)
	{
		return Environment::getVariable('logDir').'/email-'.md5($message).'.log';
	}

	protected function getGlobalFile()
	{
		return Environment::getVariable('logDir').'/email.log';
	}

	/**
	 * Write a message to the log.
	 *
	 * @param  array  $event  event data
	 * @return void
	 */
	public function _write($event)
	{

		$message = $this->formatter->format($event);
		$attachment = isset($event['file']) ? $event['file'] : NULL;

		$file = $this->getFile($event['message']);

		if (file_exists($file)) {
			if (! $this->stream = @fopen($file, 'a', false)) {
				$msg = "\"$file\" cannot be opened with mode a";
				throw new Log_Exception($msg);
			}
			if (false === @fwrite($this->stream, $message)) {
				throw new Log_Exception("Unable to write to stream");
			}
			fclose($this->stream);
			$f = @fopen($file, 'r');
			$ts = (int) trim(fgets($f));
			fclose($f);
			if($ts + 60 * $this->minutes < time()) {
				$message = file_get_contents($file);
				$lines = explode("\n", $message);
				unset($lines[0]);
				$message = join("\n", $lines);
				$this->writeTime($file);
				$this->sendMail($message, $attachment);
			}
		}
		else {
			$this->writeTime($file);
			$this->sendMail($message, $attachment);
		}
	}

	private function getGlobalCount()
	{
		$gfile = $this->getGlobalFile();
		if (file_exists($gfile)) {
			$f = @fopen($gfile, 'r');
			$ts = (int) trim(fgets($f));
			$count = (int) trim(fgets($f));
			fclose($f);
		}
		else {
			$ts = time();
			$count = 0;
		}
		if($ts + 60 * $this->minutes < time()) {
			$count = 0;
			$this->resetGlobalLog();
		}
		return $count;
	}

	private function incGlobalLog()
	{
		$gfile = $this->getGlobalFile();
		if (file_exists($gfile)) {
			$f = @fopen($gfile, 'r');
			$ts = (int) trim(fgets($f));
			$count = (int) trim(fgets($f));
			fclose($f);
		}
		else {
			$ts = time();
			$count = 0;
		}
		$count++;
		$f = @fopen($gfile, 'w');
		fputs($f, $ts."\n");
		fputs($f, $count);
		fclose($f);
	}

	private function resetGlobalLog()
	{
	   $gfile = $this->getGlobalFile();
	   $f = @fopen($gfile, 'w');
	   fputs($f, time()."\n");
	   fputs($f, 0);
	   fclose($f);
	}

	public function sendMail($message, $attachment)
	{
		if($this->getGlobalCount() < $this->emailsLimit) {
			$this->incGlobalLog();

			$mail = new Mail();
			$emails = preg_split('#[,;\s]#', $this->email);
			foreach ($emails as $email) {
				if (trim($email)) {
					$mail->addTo(trim($email));
				}
			}
			if ($attachment) {
				$mail->addAttachment(basename($attachment), file_get_contents($attachment), 'text/html');
			}
			$mail->setSubject($this->subject)
				->setBody($message)
				->send();
		}
	}

	public function writeTime($file)
	{
		if (! $this->stream = @fopen($file, 'w', false)) {
				$msg = "\"$file\" cannot be opened with mode a";
				throw new Log_Exception($msg);
		}
		if (false === @fwrite($this->stream, time().PHP_EOL)) {
			throw new Log_Exception("Unable to write to stream");
		}
		fclose($this->stream);
	}
}
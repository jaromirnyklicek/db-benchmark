<?php
/**
* Logovaní do tabulky:
*
*	CREATE TABLE `logs` (
  `id` int(10) NOT NULL auto_increment,
  `date` date default NULL,
  `priority` int(11) default NULL,
  `ip` int(11) default NULL,
  `message` text collate utf8_czech_ci,
  `url` varchar(255) collate utf8_czech_ci default NULL,
  `presenter` varchar(255) collate utf8_czech_ci default NULL,
  `request` text collate utf8_czech_ci,
  `user` int(11) DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `ip` (`ip`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

*/
class LogWriterDatabase extends LogWriter
{

	/**
	* Název tabulky pro logovaní
	*
	* @var string
	*/
	protected $table;

	public function __construct($table = 'logs')
	{
		$this->table = $table;
	}
	/**
	 * Write a message to the log.
	 *
	 * @param  array  $event  event data
	 * @return void
	 */
	public function _write($event)
	{
		$sql = 'INSERT INTO '.$this->table.' SET
			date = NOW(),
			priority = '.$event['priority'].',
			ip = "'.$event['IP'].'",
			url = "'.Database::instance()->escape_str($event['URL']).'",
			message = "'.Database::instance()->escape_str($event['message']).'",
			presenter = "'.Database::instance()->escape_str($event['presenter']).'",
			request = "'.Database::instance()->escape_str($event['request']).'"
		';
		if(isset($event['user'])) {
			$sql .= ', user = '.$event['user'];
		}

		try {
			sql::query($sql);
		}
		catch (Exception $e){

		}
	}
}

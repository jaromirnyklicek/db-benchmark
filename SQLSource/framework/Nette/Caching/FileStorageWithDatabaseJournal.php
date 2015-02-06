<?php


/**
 * File Storage, ktory vyuziva defaultne DatabaseJournal.
 *
 *
 * @author Andrej Rypak <andrej.rypak@viaaurea.cz>
 * @copyright (c)	Via Aurea, s.r.o.
 */
class FileStorageWithDatabaseJournal extends FileStorage implements ICacheStorage
{


	public function __construct($dir, ICacheJournal $journal = NULL)
	{
		if ($journal === NULL) {
			$journal = new DatabaseJournal();
		}
		parent::__construct($dir, $journal);
	}


	public static function factory($options)
	{
		return new self($options['dir']);
	}

}
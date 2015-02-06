<?php


/**
 * Cache Journal vyuzivajuci Database triedu.
 *
 * Sam si vytvori tabulku v pripade potreby.
 *
 *
 * @author Andrej Rypak <andrej.rypak@viaaurea.cz>
 * @copyright (c)	Via Aurea, s.r.o.
 */
class DatabaseJournal extends Object implements ICacheJournal
{
	protected $tableName = NULL;
	protected $entryColumn = NULL;
	protected $priorityColumn = NULL;
	protected $tagColumn = NULL;


	public function __construct($tableName = 'cache_journal', $entryColumn = 'entry', $priorityColumn = 'priority', $tagColumn = 'tag')
	{
		$this->configure($tableName, $entryColumn, $priorityColumn, $tagColumn);
	}


	public function prepareTable()
	{
		$sql = '
			CREATE TABLE IF NOT EXISTS `' . $this->tableName . '` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				`' . $this->entryColumn . '` varchar(255) DEFAULT NULL,
				`' . $this->priorityColumn . '` int(10) UNSIGNED NOT NULL,
				`' . $this->tagColumn . '` varchar(255) DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY (`' . $this->entryColumn . '`),
				KEY (`' . $this->priorityColumn . '`),
				KEY (`' . $this->tagColumn . '`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT "tabulka pro cache journal"';
		Database::instance()->query($sql);
		return $this;
	}


	public function configure($tableName = NULL, $entryColumn = NULL, $priorityColumn = NULL, $tagColumn = NULL)
	{
		$this->tableName = $tableName !== NULL ? $tableName : $this->tableName;
		$this->entryColumn = $entryColumn !== NULL ? $entryColumn : $this->entryColumn;
		$this->priorityColumn = $priorityColumn !== NULL ? $priorityColumn : $this->priorityColumn;
		$this->tagColumn = $tagColumn !== NULL ? $tagColumn : $this->tagColumn;
		return $this;
	}


	/**
	 * Writes entry information into the journal.
	 *
	 * @param  string $key
	 * @param  array  $dependencies
	 * @return bool
	 */
	public function write($key, array $dependencies)
	{
		$entry = Database::instance()->escape_str($key);
		$query = array();
		if (!empty($dependencies[Cache::TAGS])) {
			foreach ((array) $dependencies[Cache::TAGS] as $tag) {
				$query [] = 'INSERT INTO `' . $this->tableName . '` (`' . $this->entryColumn . '`, `' . $this->tagColumn . '`) VALUES ("' . $entry . '", "' . Database::instance()->escape_str($tag) . '"); ';
			}
		}
		if (!empty($dependencies[Cache::PRIORITY])) {
			$query [] = 'INSERT INTO `' . $this->tableName . '` (`' . $this->entryColumn . '`, `' . $this->priorityColumn . '`) VALUES ("' . $entry . '", "' . ((int) $dependencies[Cache::PRIORITY]) . '"); ';
		}

		Database::instance()->query('START TRANSACTION');
		try {
			Database::instance()->query('DELETE FROM `' . $this->tableName . '` WHERE `' . $this->entryColumn . '` = "' . $entry . '"; ');
			foreach ($query as $sql) {
				Database::instance()->query($sql);
			}
			Database::instance()->query('COMMIT;');
		} catch (Database_Exception $e) {
			if ($e->getCode() == 1146) {
				$this->prepareTable();
				return $this->write($key, $dependencies);
			}
			Database::instance()->query('ROLLBACK');
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * Cleans entries from journal.
	 *
	 * @param  array  $conditions
	 * @return array of removed items or NULL when performing a full cleanup
	 */
	public function clean(array $conditions)
	{
		if (!empty($conditions[Cache::ALL])) {
			Database::instance()->query('DELETE FROM `' . $this->tableName . '` WHERE 1;');
			return;
		}

		$query = array();
		if (!empty($conditions[Cache::TAGS])) {
			$tags = array();
			foreach ((array) $conditions[Cache::TAGS] as $tag) {
				$tags[] = '"' . Database::instance()->escape_str($tag) . '"';
			}
			$query[] = '`' . $this->tagColumn . '` IN(' . implode(', ', $tags) . ')';
		}

		if (isset($conditions[Cache::PRIORITY])) {
			$query[] = '`' . $this->priorityColumn . '` <= ' . ((int) $conditions[Cache::PRIORITY]);
		}

		$entries = array();
		if (!empty($query)) {
			$query = implode(' OR ', $query);
			try {
				$result = Database::instance()->query('SELECT `' . $this->entryColumn . '` FROM `' . $this->tableName . '` WHERE ' . $query);
			} catch (Database_Exception $e) {
				if ($e->getCode() == 1146) {
					$this->prepareTable();
					return $this->clean($conditions);
				}
				throw $e;
			}
			$array = $result->result_array();
			if (!empty($array)) {
				foreach ($array as $row) {
					$entries[] = $row->{$this->entryColumn};
				}
			}
			Database::instance()->query('DELETE FROM `' . $this->tableName . '` WHERE ' . $query);
		}
		return $entries;
	}

}

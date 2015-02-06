<?php
/**
 * Database SQL Layer
 * Trida pro zpozdene SQL dotazy. Vyuziva mechanizmus razeni dotazu do fronty, ktere se provedou naraz nejcasteji cronem
 * Fronta se uklada do databaze do tabulky "lazyqueries"
 *
 * @package    Database
 * @author	   Ondrej Novak
 * @copyright  (c) 2009 Ondrej Novak
 *
 *
 */
class lazySql {

	/**
	* Zaradi dotaz do fronty
	*
	*/
	public static function query($sql)
	{
		sql::query('INSERT INTO lazyqueries (`sql`) VALUES ("'.Database::instance()->escape_str($sql).'")');
	}

	/**
	* Provede dotazy ve fronte
	*
	*/
	public static function flush($limit = NULL)
	{
		$queries = sql::toArray('SELECT * FROM lazyqueries '.($limit != NULL ? 'LIMIT '.$limit : ''));
		foreach($queries as $query) {
			sql::query($query->sql);
			sql::query('DELETE FROM lazyqueries WHERE id = '.$query->id);
		}
	}
}

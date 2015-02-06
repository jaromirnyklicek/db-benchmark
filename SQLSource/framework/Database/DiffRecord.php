<?php
/**
 * Trida, ktera sleduje co v databazi prebyva pri rucnim ukaldani vazebni tabulky.
 * Priklad: projekt ma nekolik resitelu v tabulce projects_users.
 * Pracuji s projektem id = 1 ten ma ted 2 resitele, ale z formulare mi prijde ze ma 3 resitele.
 * Z formulare nevim, ze byl jeden odstranen a dva novy pridany.
 * Tato trida zajisti, ze po ulozeni tech 3 resitelu budu vedet, ze mam jednoho smazat.
 * $d = new DiffRecord('projects_users', 'user', 'project = 2');
 *
 * @author	   Ondrej Novak
 */
class DiffRecord extends Object {

	/**
	 * spojeni na DB
	 */
	protected $db;

	/**
	* Polozky v db pred zpracovanim
	*
	* @var array
	*/
	protected $itemsDb;

	/**
	* Sledovany sloupec
	*
	* @var string
	*/
	protected $column;

	/**
	* Sledovana tabulka
	*
	* @var string
	*/
	protected $table;

	/**
	* Sledovana podminka
	*
	* @var string
	*/
	protected $where;

	/**
	* ID sledovanych zaznamu v db
	*
	* @var array
	*/
	protected $idArrDb = array();

    /**
    * Ulozene id
    *
    * @var array
    */
	protected $idArrSaved = array();

	/**
	 * Konstruktor
	 */
	public function __construct($table, $column, $where = 1, $db = NULL)
	{
		if ($db === NULL) {
			$this->db = Database::singleton();
		}

		$this->column = $column;
		$this->table = $table;
		$this->where = $where;
		$this->itemsDb = sql::toArray('SELECT * FROM `'.$this->db->escape_str($table).'` WHERE '.$where, $column);
		foreach ($this->itemsDb as $item) {
			$this->idArrDb[] = $item->$column;
		}
	}

	/**
	* Oznaci, ze byl vazebni zaznam s $id ulozen
	*
	* @param int $id
	*/
	public function setSaved($id)
	{
       $this->idArrSaved[] = $id;
	}

	/**
	* @return array
	*/
	public function getSaved()
	{
       return $this->idArrSaved;
	}

	/**
	* @return array
	*/
	public function getDbIdArr()
	{
       return $this->idArrDb;
	}

	/**
	* @return array
	*/
	public function getDbItems()
	{
       return $this->itemsDb;
	}

	/**
	* Vrati ID zaznamu z vazebni tabulky
	*
	* @param int $id
	* @return int
	*/
	public function getId($id)
	{
       foreach ($this->itemsDb as $item) {
		   if ($item->{$this->column} == $id) {
			   return $item->id;
		   }
       }
	}

	/**
	* Smaze prebytecne zaznamy
	*
	* @return array
	*/
	public function delete()
	{
		$toDel = array_diff($this->idArrDb, $this->idArrSaved);
        if (join(',', $toDel)) {
			sql::query('DELETE FROM `'.$this->db->escape_str($this->table).'` WHERE '.$this->column.' IN ('.join(',', $toDel).') AND '.$this->where);
        }
        return $toDel;
	}



}
<?php

/* Trida mapujici retezec ve tvaru 2-0,21-2,20-2,62-2,22-2,65-2,63-0,6-0,3-0,4-0,5-0,7-0
   (id potomka-id rodice, id potomka-id rodice,....)
   do stromove struktury s moznosti updatovat va databazi sloupec `position` podle vytvorene struktury.
   Pouziva se pri drag & drop operacich nad stromovou strukturou
*/

class String2Tree extends Object {

	protected $items;
	protected $tree;
	protected $charsPerLevel = 3;

	public function __construct($s, $prefix = '')
	{
		$items = explode(',', $s);
		foreach($items as $item)  {
			list($id, $parent) = explode('-', $item);
			$this->items[(int)$parent][] = array('id' => (int)$id);
		}
		$tree = new TreeNode();

		foreach($this->items[0] as $item) {
			$tree->addNode(new TreeNode($item));
		}
		unset($this->items[0]);

		foreach($this->items as $parent => $items) {
			$node = $this->findNodeById($parent, $tree);
			foreach($items as $item) {
				$node->addNode(new TreeNode($item));
			}
		}
		$this->setPositions($tree, $prefix);
		$this->tree = $tree;
	}

	/**
	* Nalezeni uzlu podle ID
	*
	* @param int $id
	* @param object $tree
	*/
	protected function findNodeById($id, $tree)
	{
		foreach($tree->getNodes() as $item) {
			if($item->id == $id) return $item;
			$node = $this->findNodeById($id, $item);
			if($node != NULL) return $node;
		}
	}

	/**
	* Nastaveni hierarchickeho retezce pro pozici
	*
	* @param object $tree
	* @param string $prefix
	*/
	protected function setPositions($tree, $prefix = '')
	{
		$i = 1;
		foreach($tree->getNodes() as $item) {
			$pos = $prefix.str_pad((string)$i, $this->charsPerLevel, '0', STR_PAD_LEFT);
			$item->position = $pos;
			$this->setPositions($item, $pos);
			$i++;
		}
	}

	/**
	* Ulozeni do databaze
	*
	* @param string $table nazev tabulky
	*/
	public function save($table)
	{
		sql::query('START TRANSACTION');
		$items = $this->tree->getLinear();
		foreach($items as $item) {
			$exists = sql::toScalar('SELECT id FROM '.$table.' WHERE id = '.$item->id);
			if($exists) {
				$sql = 'UPDATE '.$table.' SET position = "'.$item->position.'" WHERE id = '.$item->id;
				sql::query($sql);
			}
			else {
				sql::query('ROLLBACK');
				return FALSE;
			}
		}
		sql::query('COMMIT');
	}
}
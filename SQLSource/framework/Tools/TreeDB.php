<?php

class TreeDB extends Tree {

	public $table;

	public $idColumn = 'id';


	/**
	 * Posun cele vetve, urcene posici dolu nebo nahoru o jeden krok.
	 *
	 * @param string $position - (napr. 00100030002
	 * @param bool $down - posun dolu nebo nahoru
	 */
	public function move($position, $down = TRUE)
	{
		$node = $this->getNodeByPosition($position);
		$this->swap($node, $down ? $node->nextSibling() : $node->prevSibling());
	}

	/**
	 * Vymena dvou vetvi.
	 *
	 * @param array $a
	 * @param array $b
	 */
	protected function swap($a, $b)
	{
		if($b == NULL) return FALSE;

		$level = $this->level($a->{$this->hierarColumn});
		$lastPosA = $this->subPosition($a->{$this->hierarColumn}, $level);
		$lastPosB = $this->subPosition($b->{$this->hierarColumn}, $level);
		try {
			sql::query('START TRANSACTION');
			$sql = 'UPDATE '.$this->table.' SET '.$this->hierarColumn.' = \''.$a->{$this->hierarColumn}.'\' WHERE '.$this->idColumn.' = '.$b->{$this->idColumn};
			sql::query($sql);
			$sql = 'UPDATE '.$this->table.' SET '.$this->hierarColumn.' = \''.$b->{$this->hierarColumn}.'\' WHERE '.$this->idColumn.' = '.$a->{$this->idColumn};
			sql::query($sql);
			$this->changePosition($a->getNodes(), $lastPosB, $level);
			$this->changePosition($b->getNodes(), $lastPosA, $level);
			sql::query('COMMIT');
		}
		catch (Database_Exception $ex) {
			sql::query('ROLLBACK');
		}
		return TRUE;
	}

	/**
	 * Nahrazeni stare posize za novou.
	 * Napr. 002001003 pri posunu dolu na urovni 2 pro novou posici 004 => 002001004
	 *
	 * @param array $uzly
	 * @param string $newPosition
	 * @param int $level
	 */
	protected function changePosition($nodes, $newPosition, $level)
	{
		foreach ($nodes as $node) {
			$oldPos = $node->{$this->hierarColumn};
			$newPos = '';
			for($i = 0; $i < $this->level($oldPos) + 1; $i++) {
			  if ($i != $level) $newPos .= $this->subPosition($oldPos, $i);
			  else $newPos .= $newPosition;
			}
			$sql = 'UPDATE '.$this->table.' SET '.$this->hierarColumn.' = \''.$newPos.'\' WHERE '.$this->idColumn.' = '.$node->{$this->idColumn};
			sql::query($sql);
			$this->changePosition($node->getNodes(), $newPosition, $level);
		}
	}

	 /**
	 * Zjisteni pozice, do niz bude vetev presunuta - zpravidla za posledni prvek v dane vetvi.
	 * @param $parent
	 * @return string
	 */
	public function getNewChildPosition($position)
	{
		$node = $this->getNodeByPosition($position);
		if(!$node) return '';
		$arr = $node->getNodes();
		$lastNode = end($arr);
		if($lastNode != NULL) $lastPosition = $lastNode->{$this->hierarColumn};
		else $lastPosition = FALSE;

		if ($lastPosition) {
			$newPosition =	str_pad(substr($lastPosition, 0, strlen($lastPosition) - $this->charsPerLevel).
							str_pad(strval(intval(substr($lastPosition, - $this->charsPerLevel)) + 1), $this->charsPerLevel , '0', STR_PAD_LEFT),
							strlen($lastPosition), '0', STR_PAD_LEFT);
		} else {
			$newPosition = $position.'001';
		}
		return $newPosition;
	}

}
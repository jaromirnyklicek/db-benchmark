<?php

/** 
*	Modernejsi verze hierardb.
*	Trida prevede databazove data v poli do stromove struktury podle hierarchickeho sloupce.
*	Serazeni podle hierarchickeho sloupce se dela pri inicializaci.
*	
* 
*	Puziti:
*	$sql = 'SELECT m.id, mt.title, m.position FROM menu m 
			JOIN menu_text mt ON mt.menu = m.id  AND mt.language = "cs"
			WHERE m.mgroup = 1';
	$data = sql::toArray($sql);		   
	$tree = new Tree($data);	
	$tree->getTree(); // vrati stromovou strukturu db dotazu		
	// nebo graficke linearni zobrazeni
	foreach($tree->getLinear() as $item) {
			foreach($item->__IMG as $imgtype) {
				$path = '/img/core/';
				switch($imgtype) {
					case 'I': $img = 'li.gif'; break;
					case 'L': $img = 'll.gif'; break;
					case 'T': $img = 'lt.gif'; break;
					case 'b': $img = 'lb.gif'; break;
				}
				echo '<img src="'.$path.$img.'"/> ';
			}			 
			echo $item->title.'<br/>';
	}	 
*/

class Tree {

	// pocet znaku na jednu uroven
	protected $charsPerLevel;

	const IMG_I = 'I';
	const IMG_L = 'L';
	const IMG_T = 'T';
	const IMG_BLANK = 'b';

	// sloupec, ktery urcuje hierarchii
	protected $hierarColumn = 'position';
	protected $tree;
	
	/**
	 *
	 * @param string $hierarColumn Sloupec, ktery v DB identifikuje hierarchii.
	 * @param int $charsPerLevel Počet znaků na jednu úrověň. Výchozí hodnota jsou 3 znaky.
	 */
	public function __construct(
	 $data,
	 $hierarColumn = 'position',
	 $charsPerLevel = 3
	)
	{
		$this->charsPerLevel = $charsPerLevel;
		$this->hierarColumn = $hierarColumn;		
		$this->tree = $this->arr2tree($this->data2arr($data), new TreeNode());
	}
	
	private function data2arr($data)
	{
		if(empty($data)) return array();
		foreach ($data as $key => $row) {
			$row = (array)$row;					   
			$position[$key]  = (string)$row[$this->hierarColumn];
		}
		array_multisort($position, SORT_ASC, SORT_STRING, $data);		 
		$i = 0;
		foreach($data as $dbRow) {
					$i++;
					$dbRow = (array)$dbRow;					   
					$position = $dbRow[$this->hierarColumn];
					// prevede na hierarchicke pole
					$id = substr($position, 0, $this->charsPerLevel);
					$arrItem = '$treeArr["'.$id.'"]';					 
					while (strlen($position) > $this->charsPerLevel) {
						$position = substr($position, $this->charsPerLevel);
						$id .= substr($position, 0, $this->charsPerLevel);
						$arrItem .= '["__CHILDS"]["'.$id.'"]';
					}					
					$arrItem .= ' = array ( ';
					if (count($dbRow)) foreach ($dbRow as $key => $item) {
						$arrItem .=    '"'.$key.'" => \''.addcslashes($item, "'").'\',';
					}
					$arrItem .= ');';
					eval($arrItem);
		}		 
		return $treeArr;
	}	 
	
	/**
	* Prevede pole na objektovy model
	* 
	* @param object $items
	* @param object $tree
	*/
	private function arr2tree($items, $tree)
	{			
		foreach($items as $item) {
			   $node = $tree->addNode(new TreeNode($item));
			   if(isset($item['__CHILDS'])) {						
				   $this->arr2tree($item['__CHILDS'], $node);
			   }
			   unset($node->__CHILDS);				 
		}
		return $tree;
	}
	
	public function getTree()
	{
		return $this->tree;
	}
	
	/**
	 * Vrati strom do sekvencniho jednorozmerneho pole
	 * Prida metainformace:
	 *	__LEVEL - udava hloubku zanoreni
	 *	__NODE	- reference na cely uzel
	 *	__IMG	- pole typu obrazku ktere se hodi pro graficke zobrazeni stormu 
	 *			  @see /img/core/ll.gif, /img/core/lt.gif, /img/core/li.gif, /img/core/lb.gif
	 *
	 * @return array
	 */
	public function getLinear()
	{
		$arr = array();
		$seq = $this->getTree()->getLinear();
		foreach($seq as $item) {
			$x = array();
			foreach($item as $k => $v) $x[$k] = $v;
			if(!isset($x[$this->hierarColumn])) continue;
			$level = $this->level($x[$this->hierarColumn]);
			$x['__LEVEL'] = $level;
			
			$imgs = array();
			for ($i = 0; $i < $level - 1; $i++) {
				$parent = $item->getParent();				 
				for ($j = 0; $j < $level - $i - 2; $j++) {
					$parent = $parent->getParent();
				}
				if (!$this->isLast($parent)) $imgs[] = self::IMG_I;
				else $imgs[] = self::IMG_BLANK;
			}
			if ($level != 0) {
				if(!$this->isLast($item)) {
					$imgs[] = self::IMG_T;		  
				}
				else {
					$imgs[] = self::IMG_L;
				}
			}
			$x['__IMG'] = $imgs;
			$x['__NODE'] = $item;
			$arr[] = (object)$x;
		}
		return $arr;
	}	 
	
	public function getNodeByPosition($position)
	{
		if(empty($position)) return $this->getTree();
		$l = $this->getTree()->getLinear();
		foreach($l as $node) {
			$np = $node->{$this->hierarColumn};
			if($np === $position) return $node;
		}
		return FALSE;
	}
	
	/**
	 * Test, zda jde o prvni uzel ve sve vetvy.
	 *
	 * @param mixed $node
	 * @return bool
	 */
	protected function isFirst($node)
	{
		$parent = $node->getParent(); 
		if ($parent !== false) {
			$arr = $parent->getNodes();
			reset($arr);
			return current($arr) === $node;
		}
		return false;
	}
	
	/**
	* Je posledni uzel ve vetvy?
	* 
	* @param mixed $node
	* @return bool
	*/
	protected function isLast($node)
	{
		$parent = $node->getParent();
		if ($parent !== FALSE) {
			$arr = $parent->getNodes();
			$a = $arr[count($arr)-1];
			return $a === $node;
		}
		return FALSE;
	}	 
	
	/**
	 * Uroven zanoreni
	 *
	 * @param string $str
	 * @return int
	 */
	public function level($str)
	{
		return (strlen($str) / $this->charsPerLevel) - 1;
	}
	
	/**
	 * Vrati 3 znakovy retezec pro danou uroven. Pr. 002003004 -> 003 pro $level=2
	 *
	 * @param string $position
	 * @param int $level
	 * @return string
	 */
	protected function subPosition ($position, $level)
	{
		return substr($position, $this->charsPerLevel * $level, $this->charsPerLevel);
	}
   
}
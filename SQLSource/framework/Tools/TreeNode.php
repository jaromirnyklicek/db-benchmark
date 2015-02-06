<?php


/**
 * Uzel stromu pro tridu Tree
 */
class TreeNode
{
	private $__nodes = array();
	private $__parent;
	private $__cache = array();
	private $__cacheLinear;


	public function __construct($data = array())
	{
		foreach ($data as $key => $item) {
			$this->$key = $item;
		}
	}


	public function addNode($node)
	{
		$node->setParent($this);
		$this->__nodes[] = $node;
		return $node;
	}


	public function getNodes()
	{
		return $this->__nodes;
	}


	/**
	 * Odstrani uzol.
	 * 
	 * @param int|self $keyOrNode kluc do pola uzlov alebo instancia uzla
	 * @return TreeNode odstraneny uzol
	 */
	public function removeNode($keyOrNode)
	{
		if ($keyOrNode instanceof self) {
			foreach ($this->getNodes() as $key => $node) {
				if ($keyOrNode === $node) {
					$keyOrNode = $key;
					break;
				}
			}
		}
		$node = $this->__nodes[$keyOrNode];
		unset($this->__nodes[$keyOrNode]);
		return $node;
	}


	public function setParent($node)
	{
		$this->__parent = $node;
	}


	public function getParent()
	{
		return $this->__parent;
	}


	/**
	 * Vrati strom do sekvencniho jednorozmerneho pole
	 *
	 * @return array
	 */
	public function getLinear()
	{
		if ($this->__cacheLinear == NULL) {
			$this->__cacheLinear = $this->linear_item($this);
		}
		return $this->__cacheLinear;
	}


	/**
	 * Otoci svoje uzly pozpatku.
	 *
	 */
	public function reverse()
	{
		$this->__nodes = array_reverse($this->__nodes);
		foreach ($this->__nodes as $node) {
			$node->reverse();
		}
	}


	/**
	 * Vrati uzly po ceste od korene.
	 *
	 */
	public function getPath()
	{
		$res = array();
		$res[] = $this;
		$parent = $this->getParent();
		if ($parent) {
			while ($parent->getParent()) {
				$res[] = $parent;
				$parent = $parent->getParent();
			}
		}
		$res = array_reverse($res);
		return $res;
	}


	/**
	 * Vrátí pole požadovaného atributu ze všech jeho poduzlů.
	 *
	 * @param mixed $attrib
	 */
	public function getAttribs($attrib)
	{
		$items = array();
		if (isset($this->$attrib)) {
			$items[] = $this->$attrib;
		}
		foreach ($this->getLinear() as $item) {
			$items[] = $item->$attrib;
		}
		return $items;
	}


	public function getByAttrib($attrib, $value)
	{
		if (isset($this->$attrib) && $this->$attrib == $value) {
			return $this;
		}
		foreach ($this->getLinear() as $item) {
			if (isset($item->$attrib) && $item->$attrib == $value) {
				return $item;
			}
		}
	}


	/**
	 * logictejsi alias pro getByAttrib
	 *
	 * @param mixed $attrib  - atribut
	 * @param mixed $value	- hodnota atributu
	 */
	public function find($attrib, $value)
	{
		return $this->getByAttrib($attrib, $value);
	}


	public function contain($attrib, $value)
	{
		if (!isset($this->__cache[$attrib])) {
			$this->__cache[$attrib] = $this->getAttribs($attrib);
		}
		$items = $this->__cache[$attrib];
		return in_array($value, $items);
	}


	protected function linear_item($tree)
	{
		$items = array();
		foreach ($tree->getNodes() as $item) {
			$items[] = $item;
			if ($item->getNodes() != array()) {
				$items = array_merge($items, $this->linear_item($item));
			}
		}
		return $items;
	}


	/**
	 * Test, zda jde o prvni uzel ve sve vetvy.
	 *
	 * @param mixed $node
	 * @return bool
	 */
	public function isFirst()
	{
		$parent = $this->getParent();
		if ($parent !== FALSE) {
			$arr = $parent->getNodes();
			reset($arr);
			return current($arr) === $this;
		}
		return FALSE;
	}


	/**
	 * Je posledni uzel ve vetvy?
	 *
	 * @param mixed $node
	 * @return bool
	 */
	public function isLast()
	{
		$parent = $this->getParent();
		if ($parent !== FALSE) {
			$arr = $parent->getNodes();
			end($arr);
			return current($arr) === $this;
		}
		return FALSE;
	}


	public function nextSibling()
	{
		$parent = $this->getParent();
		if ($parent !== FALSE) {
			$i = 0;
			$arr = $parent->getNodes();
			foreach ($arr as $node) {
				$i++;
				if ($node === $this && isset($arr[$i])) {
					return $arr[$i];
				}
			}
		}
		return FALSE;
	}


	public function prevSibling()
	{
		$parent = $this->getParent();
		if ($parent !== FALSE) {
			$i = 0;
			$arr = $parent->getNodes();
			foreach ($arr as $node) {
				if ($node === $this && isset($arr[$i - 1])) {
					return $arr[$i - 1];
				}
				$i++;
			}
		}
		return FALSE;
	}


	public function removeNodes()
	{
		$this->__nodes = array();
	}

}
<?php
/**
* Multiform, jehož subformy lze řadit podle priority.
* Využívá drag&drop jQuery
*
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms
*/

class MultiFormSortableDB extends MultiFormDB
{
	public $subformClass = 'sortable';

	/**
	* Sloupec v databazi, podle kterého se řadí
	*
	* @var string
	*/
	public $columnPriority = 'priority';

	/**
	* Volby pro jQuery.sortable()
	*
	* @var array
	*/
	protected $options = array('cursor' => 'move');


	public function __construct($name, $parent = null)
	{
		parent::__construct($name, $parent);
		$this->onBeforeSave[] = array($this, 'setPriority');
	}

	public function getJs()
	{
		$s = parent::getJs();
		$o = array();
		foreach($this->options as $key=>$value) {
			$o[] = $key.': "'.$value.'"';
		}
		$s .= '<script type="text/javascript">
			$(document).ready(function(){
				$(\'#multi_'.$this->name.'\').sortable({'.join(', ', $o).'});
			});
			</script>';
		return $s;
	}

	/**
	* Databázový SELECT rozšírí o ORDER BY priority
	*
	* @param int $id
	* @return mixed
	*/
	protected function getItems($id)
	{
		$this->order = $this->columnPriority;
		return parent::getItems($id);
	}

	/**
	* Pred ulozenim se nastavi priority, tak v jakem poradi byly subformy odeslanu na server
	*
	* @param Form $form
	*/
	public function setPriority($form)
	{
		foreach($form->getSubforms() as $subform) $subform->orm->{$this->columnPriority} = $subform->position;
	}
}
<?php

class ActionListbox extends Object implements IDataListControl
{
	protected $datalist;
	public $title;
	protected $options = array();
	protected $name;

	public function setDataList($dataList)
	{
		$this->datalist = $dataList;
	}

	public function __construct($title = NULL, $name = NULL)
	{
		if($title === NULL) $this->title = _('Další akce').' ...';
		else $this->title = $title;
		$this->name = $name;
	}

	public function addOption($title, $js, $class = "", $disabled = FALSE)
	{
		$opt = new ActionListboxOption();
		$opt->title = $title;
		$opt->js = $js;
		$opt->class = $class;
		$opt->disabled = $disabled;
		$this->options[] = $opt;
	}

	protected function xml()
	{
		//$link = $this->datalist->jsMultiLink($this->datalist->getPresenter()->link($this->action));
		if($this->name === NULL) $this->name = uniqid($this->datalist->getName());
		$f = $this->datalist->getName().'_'.$this->name;
		$opt = '';
		$optJs = '';
		$i = 0;
		foreach($this->options as $option) {
			$i++;
			$opt .= $option->render()->value($i);
			$optJs .= 'case "'.$i.'":
				'.$option->js.';
				break;';
		}
		$js = '<script>
			'.$f.'_fnc = function(value) {
				if(value != "") {
					switch(value) {
						'.$optJs.'
					}

				}
				document.getElementById("'.$f.'").value = "";
			}
		</script>';

		$xml = '<select data-title="'.$this->title.'" name="action" class="actionbutton" id="'.$f.'" onChange="'.$f.'_fnc(this.value)">
		  <option value="" class="bold-black">'.$this->title.'</option>
		  '.$opt.'
		  </select>';
		return $js.$xml;
	}

	public function __toString()
	{
		return $this->xml();
	}
}
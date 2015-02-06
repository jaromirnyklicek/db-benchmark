<?php
/**
* Column je popis sloupce pro pouziti v Listech (DataGrid, DataList, DataView)
*   
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version 1.0
*/

class ColumnAjaxSelect extends Column
{	
    
    protected $action;
    protected $inputCss = "";
    protected $inputStyle = "";
    protected $nullOption;
    protected $items = array();
    protected $columnId = 'id';
    protected $readonly = FALSE;       
    
    public function setAction($action)
    {           
        $this->action = $action;
        return $this;
    }
    
    public function setInputCss($s)
    {           
        $this->inputCss = $s;
        return $this;
    }
    
    public function setInputStyle($s)
    {           
        $this->inputStyle = $s;
        return $this;
    }
    
    public function setItems($arr)
    {           
        $this->items = $arr;
        return $this;
    }
    
    public function setNullOption($s)
    {           
        $this->nullOption = $s;
        return $this;
    }
    
    public function setColumnId($s)
    {           
        $this->columnId = $s;
        return $this;
    }
    
    public function setReadonly($s = TRUE)
    {           
        $this->readonly = $s;
        return $this;
    }
	
	public function render($applyCallback = TRUE)
	{	
        $action	= $this->action;
	    $value = parent::render($applyCallback);      
        
        if($this->readonly) {
            return $value;
        }
        else {
            $dbRow = $this->row;        
            $id = $this->dataList->name.'_'.$this->name.'_'.$dbRow->id;
            $url = $this->dataList->getPresenter()->Link($action, array('id' => $dbRow->id));        
            
            if(strpos($url, '?') === FALSE) $char = '?';
            else $char = '&';
            
            if($this->getWidth() != NULL && strpos($this->inputStyle, 'width:') === FALSE) {
                $this->inputStyle .= ';width:'.$this->getWidth().'px';
            }
            $select = Html::el('select')
                            ->id($id.'_3')
                            ->style($this->inputStyle)
                            ->class($this->inputCss)
                            ->onchange('nette.action(\''.$url.$char.'value=\'+this.value+\'&el='.$id.'\', this)')
                            ->onblur('$(\'#'.$id.'_2\').hide(); $(\'#'.$id.'_1\').show();');        
            
            if(isset($this->nullOption)) $select->add(Html::el('option')->setText($this->nullOption));
            
            foreach($this->items as $item) {
                $option = Html::el('option')
                                ->value($item->id)
                                ->setText($item->title);
                if($dbRow->{$this->columnId} == $item->id) $option->selected(true);
                $select->add($option);
            }
            return '<div class="pencil" onclick="$(\'#'.$id.'_1\').hide(); $(\'#'.$id.'_2\').show(); $(\'#'.$id.'_3\').focus()" id="'.$id.'_1">'.(empty($value) ? '&nbsp;' : $value).'</div>'.
                    '<div id="'.$id.'_2" style="display:none">
                    '.(string)$select.'
                    </div>';
        }
	}
	
	
}

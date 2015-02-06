<?php
/**
* 
* @author Ondrej Novak
* @copyright Copyright (c) 2010, Ondrej Novak
*/


class MultiGrid extends DataGrid
{	 
	/** @persistent */
	public $view = 1;	 
	
	protected $viewForm;	
	protected $views = array();
	
	public function __construct(/*Nette\*/IComponentContainer $parent = NULL, $name = NULL)
	{		 
		parent::__construct($parent, $name);	
		$this->viewForm = new Form('v'.$name, $this);
		$this->viewForm->useAjax = TRUE;
		$this->viewForm->addSelect('scene', '')
					->nullable(false)
					->setValue($this->view)
					->setCssClass('viewselect');
		$snippet = $this->getName().'_grid';
		$this->viewForm['scene']->getControlPrototype()
				->onchange("AjaxMask.Show('$snippet'); this.form.onsubmit()");
				
	}
				 
				 
	/**
	* Vrati sloupce, ktere se zobrazi v sablone
	* 
	*/
	protected function getVisibleColumns()
	{
		if($this->viewForm->isSubmitted()) {
			$this->view = $this->viewForm['scene']->getValue();
		}
		else $this->viewForm->setDefaults();
		$cm = $this->getColumnModel();		  
		$columns = array();
		foreach($cm->getVisibleColumns() as $column) {			  
			if($column->getOption('view') === NULL || ($column->getOption('view') & $this->view) == $this->view) {				  
				$columns[] = $column;
				if($column instanceof ColumnContainer) {
					  foreach($column->getVisibleColumns() as $ccolumn) {						 
						if($ccolumn->getOption('view') === NULL || ($ccolumn->getOption('view') & $this->view) == $this->view) {
							$ccolumn->setVisible();
						}
						else {
							$ccolumn->setVisible(FALSE);
						}
					  }
				}
			}
		}				   
		return $columns;
	}
	
	public function setViews($items)
	{
		$this->views = $items;
		$this->viewForm['scene']->setItems($this->views);
		return $this;
	}
	
	public function saveState(array & $params)
	{
		parent::saveState($params);
		/// session stav
		if($this->saveState) {
			$session = Environment::getSession($this->getPresenter()->getAction(false).$this->getName());
			$session->view = $this->view;
		}
	}
	
	public function loadState(array $params)
	{		  
		if($this->saveState && !$this->isSignalReceived()) {
			$session = Environment::getSession($this->getPresenter()->getAction(false).$this->getName());		 
			if(isset($session->view)) $this->view = $session->view;
		}
		parent::loadState($params);
	}
	
	/** zmena sablony **/
	public function handleView($view)
	{		 
		$this->invalidateControl(); 
	} 
	
	// Vychozi sablony pro datagrid 
	public function getTemplatesDir()
	{
		if($this->templateDir == NULL) $this->templateDir = dirname(__FILE__).'/../Templates/MultiGrid';
		return parent::getTemplatesDir();
	}	 
	
	public function renderViewSelect()
	{
		$this->viewForm->setAction($this->link('view'));
		$template = $this->createTemplate();
		$template->viewForm = $this->viewForm;		  
		$template->setFile($this->getTemplatesDir().'/'.'viewselect.phtml');
		$template->registerFilter(/*Nette\Templates\*/'CurlyBracketsFilter::invoke');
		$template->render();		
	}
}
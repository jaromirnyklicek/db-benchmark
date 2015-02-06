<?php
/**
* DataGrid je komponenta využívaná zejména pro administrační část.
* Zobrazuje záznamy formou tabulky, jednotlívé záznamy tvoří řádky.
* Pokud sloupec obsahuje volbu "sum" pres $columns->setOption('sum', value),
* tak se zobrazi posledni radek s temito sumacnimi daty
*
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
*/


class DataGrid extends DataList
{
	protected $saveState = TRUE;

	/** Ajaxove strankovani, razeni, filtrovani */
	protected $useAjax = TRUE;

	/* Text do praveho rohu vedle strankovani
	   Lze pouzit pro navigacni tlacika. Napr. export do excelu
	 */
	protected $rightHtml;

	/**
	* Sloupec, ktery je primarni klic pro tabulku.
	*
	* @var string
	*/
	protected $primaryKey;

	/**
	 * Nastaví hodnotu nowrap u sloupců.
	 *
	 * @var bool
	 */
	public $nowrap = TRUE;

	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);
		// pro kazdy radek se provede callback pro prideleni unikatniho ID radku
		$this->addRowCallback(array($this, 'attrRowID'));
	}

	/**
	* Nastaveni sloupce, ktery je primarni klic zobrazovane tabulky
	*
	* @param Column $column
	*/
	public function setPrimaryKey($column)
	{
		$this->primaryKey = $column;
		return $this;
	}

	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	public function getRightHtml()
	{
		return $this->rightHtml;
	}

	public function setRightHtml($value)
	{
		$this->rightHtml = $value;
		return $this;
	}

	/**
	* Nastavni metadata pro radek, konkretne prida vytvori unikatni ID pro kazdy radek, ktery pak lze vyuzit
	* napr. pri ajaxovych popup oknech.
	*
	* @param $dbRow
	* @return array
	*/
	public function attrRowID($dbRow)
	{
	   if(isset($this->primaryKey)) {
	   	   return array('data-id' => $dbRow->{$this->primaryKey}, 'id' => $dbRow->{$this->primaryKey}.'_'.$this->name);
	   }
	   else return array();
	}

	public function getTemplatesDir()
	{
		// Vychozi sablony pro datagrid
		if($this->templateDir == NULL) $this->templateDir = dirname(__FILE__).'/../Templates/DataGrid';
		return parent::getTemplatesDir();
	}

}

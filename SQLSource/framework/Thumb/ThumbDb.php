<?php
/**
* Třída pro generovaní nahledů uložených v databázi
* Třída poskytuje inicializaci objektu Thumb z výsledku SQL dotazu, kde se název souboru dynamicky 
* sestaví z ID a typu přípony. I konfigurace vodoznaků je uložena v databázi. 
* 
* @package Thumb
* @link http://viaaurea.viaaurea.cz/dokuwiki/doku.php?id=thumb
* @copyright  Copyright (c) 2009 Ondrej Novak
* @author Ondrej Novak
*/

class ThumbDb extends Thumb
{	
	public function __construct($dbRow, $config = NULL)
	{
		
		
		if($dbRow instanceof DataRow) $dbRow = (array)$dbRow->getData();
		else if(is_object($dbRow)) $dbRow = (array)$dbRow;
		if($config != NULL) $this->setConfig($config);
		$id = $dbRow[$this->config->id_sql];
		$type = $dbRow[$this->config->type_sql];
		if(isset($dbRow[$this->config->watermark_sql_type]) && 
		   !empty($this->config->watermark_sql) && 
		   isset($dbRow[$this->config->watermark_sql])) {
			   
			$ext = Thumb::$IMG_EXT[$dbRow[$this->config->watermark_sql_type]];
			$this->config->watermark = IMAGES_DIR.'/'.$dbRow[$this->config->watermark_sql].'.'.$ext;
			$this->config->watermark_x = $dbRow[$this->config->watermark_x_sql];
			$this->config->watermark_y = $dbRow[$this->config->watermark_y_sql];
			$this->config->watermark_ratio = $dbRow[$this->config->watermark_ratio_sql] / 100;
			$this->config->watermark_opacity = $dbRow[$this->config->watermark_opacity_sql];
		}
		if(!empty($type)) { 
			$ext = Thumb::$IMG_EXT[$type];
			$file = $id.'.'.$ext;
		}
		else {
			$file = 'nophoto';
		}
		//if($this->config->source_dir != NULL) $file = $this->config->source_dir.'/'.$file;
		
		 
		parent::__construct($file);

		/*$this->file = $file;		
		if(file_exists($this->file)) {
			$this->image = Image::fromFile($this->file);		
		}
		else $this->image = new Image();*/
	}
	
	/**
	* put your comment there...
	* 
	* @param mixed $dbRow
	* @param mixed $config
	* @return ThumbDb
	*/
	public static function factory($dbRow, $config = NULL)
	{
		return new self($dbRow, $config);
	}	 
}
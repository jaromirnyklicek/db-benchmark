<?php
/**
 * Náhled obrázku.
 * Neinteraktivní control
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */
 
class ImagePreviewDb extends ImagePreview
{		   
	public $columnType = 'type';
	public $columnImage = 'id';
	
	protected $dir;
	protected $readonly = TRUE;
	
	public function setDir($value)
	{
		$this->dir = $value;
		return $this;
	}
	
	public function getDir()
	{
		return $this->dir;
	}
	
	
	public function __construct($label = '', $config = NULL, $dir = IMAGES_DIR)
	{
		parent::__construct($label, $config);
		$this->dir = $dir;
		$this->addCallback(array($this, 'getFile'));		
	}						   
	
	public function getFile($value, $form)
	{
		if(!is_object($form->orm) || empty($form->orm->{$this->columnType})) return '';
		$id = $form->orm->{$this->columnImage};
		$ext = $form->orm->{$this->columnType};
		if(is_int($ext)) $ext = Thumb::$IMG_EXT[$ext];
		$file = $id.'.'.$ext;		 
		
		if(isset($form->orm->watermark) && $form->orm->watermark) {
			$watermark = ORM::factory('Image', $form->orm->watermark);
			$this->thumbConfig->watermark = $watermark->getFilename();
			$this->thumbConfig->watermark_x = $form->orm->watermark_x;
			$this->thumbConfig->watermark_y = $form->orm->watermark_y;
			$this->thumbConfig->watermark_opacity = $form->orm->watermark_opacity;
			$this->thumbConfig->watermark_ratio = $form->orm->watermark_ratio / 100;	
		}
		return $this->dir.'/'.$file;
	}
	
}
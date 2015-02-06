<?php
/**
* Odvozený z MultiFormSortableDB. Lze přidávat nové položky pouze výběrem z popup gallerie.
*  
* @author	  Ondrej Novak
* @copyright  Copyright (c) 2009 Ondrej Novak
* @package	  Forms 
*/


class MultiFormGalleryDB extends MultiFormSortableDB
{
	public $link;
	
	public function getTemplate()
	{
		if($this->template == NULL) $this->template = dirname(__FILE__).'/../Templates/multiform_gallery.phtml';
		return $this->template;
	}
}
<?php
/**
 * CK editor
 * Odlehcena verze CK Editoru, obsahuje pouze zakladni tlacitka v toolbaru
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2010 Ondrej Novak
 * @package    Forms
 */

class CKEditorLiteControl extends CKEditorBaseControl
{	
	public $options = array(
		'height' => 100,
		'width' => 300,
		'plugins' => 'basicstyles,blockquote,button,clipboard,contextmenu,enterkey,format,htmldataprocessor,indent,justify,keystrokes,link,list,popup,pastefromword,preview,print,removeformat,scayt,showblocks,sourcearea,stylescombo,table,tabletools,specialchar,tab,templates,toolbar,undo,wysiwygarea,wsc',
		'toolbar' => array(array('Source','-', 'Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'))
	);
	
	//'toolbar' => "[['Source','-', 'Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink']]"
	
	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{				  
		$c = parent::getControl();				  
		$config = $this->getConfig();
		$s = 'if(CKEDITOR.instances[\''.$this->getHtmlName().'\']) delete CKEDITOR.instances[\''.$this->getHtmlName().'\'];';
		$s .= 'CKEDITOR.replace(\''.$this->getHtmlName().'\', '.$config.');';
		return $c.Html::el('script')->type('text/javascript')->add($s);
	}
}
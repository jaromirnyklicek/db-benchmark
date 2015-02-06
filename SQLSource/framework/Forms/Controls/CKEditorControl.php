<?php
/**
 * CK editor
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class CKEditorControl extends CKEditorBaseControl
{
	public $options = array('height' => 300);

	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$c = parent::getControl();
		$config = $this->getConfig();
		$s = 'var instance = CKEDITOR.instances[\''.$this->getHtmlName().'\'];
		if(instance != undefined) { try { instance.destroy(); } catch (err) { instance.destroy(); }; instance = null; }';
		$s .= 'CKEDITOR.replace(\''.$this->getHtmlName().'\', '.$config.');';
		return $c.Html::el('script')->type('text/javascript')->add($s);
	}
}
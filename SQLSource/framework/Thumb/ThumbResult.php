<?php
/**
* Třída s výsledkem vygenerovaného náhledu
* 
* @package Thumb
* @copyright  Copyright (c) 2009 Ondrej Novak
* @author Ondrej Novak
*/	
class ThumbResult 
{
	/**
	* URL k nahledu
	* 
	* @var string
	*/
	public $url;
	
	/**
	* Sirka nahledu
	* 
	* @var int
	*/
	public $width;

	/**
	* Vyska nahledu
	* 
	* @var int
	*/
	public $height;
	
	/**
	* Jde o flashovy nahled
	* 
	* @var bool
	*/
	public $flash = FALSE;
	
	public function __construct($url, $width, $height)
	{
		$this->url = $url;
		$this->width = $width;
		$this->height = $height;
	}
	
	public function getWidth()
	{
		return $this->width;
	}
	
	public function getHeight()
	{
		return $this->height;
	}
	
	/**
	* Sestavi Html element
	* 
	*/
	public function getHtml()
	{
		if(!$this->flash) {
			return Html::el('img')->src($this->url)->width($this->width)->height($this->height)->alt('');
		}
		else {
			$html = '<object id="myId" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$this->width.'" height="'.$this->height.'">
				<param name="movie" value="'.$this->url.'" />
				<!--[if !IE]>-->
				<object type="application/x-shockwave-flash" data="'.$this->url.'" width="'.$this->width.'" height="'.$this->height.'">
				<!--<![endif]-->
				<div>
					<h1>Alternative content</h1>
					<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
				</div>
				<!--[if !IE]>-->
			</object>
			 <!--<![endif]-->
			</object>';
			return $html;
		}
	}
	
	public function __toString()
	{
		return (string)$this->getHtml();
	}
}

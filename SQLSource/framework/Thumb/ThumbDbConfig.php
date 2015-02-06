<?php
/**
* Konfigurační třída pro generovaní nahledů z obkazku v databazi.
* Definuje nazvy sloupcu v tabulce. 
* 
* @package Thumb
* @copyright  Copyright (c) 2009 Ondrej Novak
* @author Ondrej Novak
*/
class ThumbDbConfig extends ThumbConfig
{
	public $id_sql = 'img';
	public $type_sql = 'type';
	public $watermark_sql = 'watermark'; // ID do galerie obrazku
	public $watermark_sql_type = 'watermark_type';
	public $watermark_x_sql = 'watermark_x';
	public $watermark_y_sql = 'watermark_y';
	public $watermark_ratio_sql = 'watermark_ratio';
	public $watermark_opacity_sql = 'watermark_opacity';
}

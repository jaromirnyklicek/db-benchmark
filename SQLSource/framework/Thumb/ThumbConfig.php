<?php


/**
 * Konfigurační třída pro generovaní nahledů
 * 
 * @package Thumb
 * @link http://viaaurea.viaaurea.cz/dokuwiki/doku.php?id=thumb
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @author Ondrej Novak
 */
class ThumbConfig
{
	/**
	 * Adresar pro ulozeni vygenerovaneho nahledu
	 *
	 * @var string
	 */
	public $dir;

	/**
	 * Cesta ke zdrojovemu obrazku.
	 * 
	 * @var string
	 */
	public $source_dir;

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
	 * Jemne doostreni
	 *
	 * @var bool
	 */
	public $sharpen = FALSE;

	/**
	 * Prevedeni do odstinu sede
	 *
	 * @var bool
	 */
	public $grayscale = FALSE;

	/**
	 * Vytvoreni negativu
	 *
	 * @var bool
	 */
	public $negative = FALSE;

	/**
	 * Povolit zvetseni
	 *
	 * @var bool
	 */
	public $enlarge = FALSE;

	/**
	 * Povolit neproporcionalni zmeny rozmenu
	 *
	 * @var bool
	 */
	public $stretch = FALSE;

	/**
	 * Soubor vodoznaku
	 *
	 * @var string
	 */
	public $watermark;

	/**
	 * X pozice vodoznaku v nahledu. Moznosts zadat v %.
	 *
	 * @var mixed
	 */
	public $watermark_x;

	/**
	 * Y pozice vodoznaku v nahledu. Moznosts zadat v %.
	 *
	 * @var mixed
	 */
	public $watermark_y;

	/**
	 * Pruhlednost vodoznaku
	 *
	 * @var int
	 */
	public $watermark_opacity;

	/**
	 * Pomer vodoznaku vuci sirce obrazku. NULL = zachova originalni velikost vodoznaku
	 *
	 * @var float
	 */
	public $watermark_ratio = 0.3;

	/**
	 * URL cesta k adresari nahledu
	 *
	 * @var string
	 */
	public $url;

	/**
	 * URL cesta k originalnim obrazkum. Pouziti pro Flash thumby, ktere negeneruje nahledy
	 *
	 * @var string
	 */
	public $url_orig;

	/**
	 * Sifrovat textovou reprezentaci konfigurace?
	 *
	 * @var bool
	 */
	public $md5 = TRUE;

	/**
	 * Obrazek pro obrazek, ktery neni k dispozici
	 *
	 * @var bool
	 */
	public $nophoto = NULL;

	/**
	 * Kvalita vysledného náhledu
	 *
	 * @var integer
	 */
	public $quality = 85;

	/**
	 * Mod orezani obrazku. Musi byt vyplnena sirka i vyska.
	 *
	 * @var integer
	 */
	public $crop = Thumb::CROP_NONE;


	public function __construct($config = NULL)
	{
		if ($config != NULL) {
			$this->loadFromArray($config);
		}
	}


	/**
	 * Nacteni atributu z pole
	 *
	 * @param array $config
	 */
	public function loadFromArray($config)
	{
		if (is_array($config)) {
			foreach ($config as $key => $var) {
				$this->$key = $var;
			}
		} else {
			throw Exception('Thumb configuration must be array');
		}
	}


	/**
	 * stringova reprezentace konfigurace
	 *
	 */
	public function __toString()
	{
		$arr = array(
			$this->width,
			$this->height,
			(int) $this->sharpen,
			(int) $this->grayscale,
			(int) $this->negative,
			(int) $this->enlarge,
			(int) $this->stretch,
			(int) $this->watermark_x,
			(int) $this->watermark_y,
			(int) ($this->watermark_ratio * 100),
			(int) $this->watermark_opacity,
			(int) $this->quality,
			basename($this->watermark),
		);
		if ($this->crop) {
			$arr[] = $this->crop;
		}
		$s = join('x', $arr);
		if (!$this->md5) {
			return $s;
		} else {
			return substr(md5($s), 0, 10);
		}
	}

}

<?php


/**
 * Třída pro generovaní nahledů
 *
 * Ke generování náhledů slouží třída Thumb. V kontruktoru se předá zdrojový soubor a konfigurace pro
 * vygenerovaný náhled. K vytvoření objektu je možné použít továrničku.
 * Konfiguraci lze předat asociativním polem, objektem ThumbConfig nebo název konfigurační třidy.
 * Objektová konfigurace má přínos v možnosti dědění konfigurací.

  Struktura konfiguračního objektu/pole:
 * dir - adresář pro vygenerování nahledu
 * source_dir - cesta ke zdrojovému souboru. Pokud není zadána, měl by být parametr souboru v konstruktoru s absolutní cestou
 * width - šířka náhledu
 * height - výška náhledu
 * url - URL cesta k náhledu
 * url_orig - URL cesta ke zdrojovým souborům. Povinné pro Flash náhledy, které se negenerují do cashe
 * stretch - povolit neproporcionální změnu velikosti
 * enlarge - zvětšení náhledu pokud bude original menší
 * sharpen - jemné doostření
 * grayscale - odstiny sede
 * negative - negativ
 * md5 - povolit hashování konfigurace algoritmem MD5
 * watermark - soubor s vodoznakem
 * watermark_x - X pozice vodoznaku. Lze uvést relativně v procentech nebo absolutně v pixelech.
 * watermark_y - Y pozice vodoznaku. Lze uvést relativně v procentech nebo absolutně v pixelech.
 * watermark_ratio - poměr vodoznaku vůči šířce už vygenerovaného náhledu
 * watermark_opacity - průhlednost vodoznaku
 *
 * @author	  Ondrej Novak
 * @copyright  Copyright (c) Via Aurea, s.r.o.
 * @package	  Thumb
 */
class Thumb extends Object
{
	/*	 * #@+ resizing flags */
	const ENLARGE = 1;
	const STRETCH = 2;

	/*	 * #@+ cropping flags */
	const CROP_NONE = 0;
	const CROP_CENTER = 1;
	const CROP_TOP_LEFT = 2;
	const CROP_TOP_RIGHT = 3;
	const CROP_BOTTOM_LEFT = 4;
	const CROP_BOTTOM_RIGHT = 5;

	public static $logErrors = TRUE;

	/*	 * #@- */

	/**
	 * Zdrojovy soubor
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * Jmeno skutecneho obrazku, ze ktereho se bude delat nahled. Muze byt rozdilny oroti
	 * originalu, kvuli nophoto.
	 * @var string
	 */
	protected $image;

	/**
	 * Jmeno nahledoveho souboru
	 *
	 * @var string
	 */
	protected $thumb;

	/**
	 * Nahledovy soubor vcetne cele cesty
	 *
	 * @var string
	 */
	protected $thumbFile;

	/**
	 * Konfigurace nahledy
	 *
	 * @var ThumbConfig
	 */
	protected $config;

	/**
	 * Jde o Flash
	 *
	 * @var bool
	 */
	protected $flash = FALSE;

	/**
	 * Sirka Flashe
	 *
	 * @var int
	 */
	protected $flash_width;

	/**
	 * Vyska Flashe
	 *
	 * @var int
	 */
	protected $flash_height;

	/**
	 * Originalni zdrojovy obrazek (predany v konstruktoru)
	 *
	 * @var string
	 */
	protected $source;

	/** jestli uz byl zpracovan pres $this->get(); */
	protected $proccess = FALSE;

	/** jedna se o nophoto obrazek? */
	protected $nophoto = FALSE;
	protected $readable = TRUE;

	/**
	 * Typy obrazku
	 *
	 * @var array
	 */
	public static $IMG_EXT = array(
		1 => 'gif',
		2 => 'jpg',
		3 => 'png',
		4 => 'swf',
		5 => 'psd',
		6 => 'bmp',
		7 => 'tiff',
		8 => 'tiff',
		9 => 'jpc',
		10 => 'jp2',
		11 => 'jpx',
		12 => 'jb2',
		13 => 'swc',
		14 => 'aiff',
		15 => 'wbmp',
		16 => 'xbm'
	);


	/**
	 * Konstruktor
	 *
	 * @param string $file	Originalni soubor
	 * @param ThumbConfig|array|string $config  Konfigurace pro nahled
	 * @return Thumb
	 */
	public function __construct($file, $config = NULL)
	{

		$this->source = $sourceFile = $file;
		if ($config != NULL) {
			$this->setConfig($config);
		}
		if ($this->config->source_dir != NULL && strpos($file, '/') === FALSE && strpos($file, '\\') === FALSE) {
			$sourceFile = $this->config->source_dir . '/' . $file;
		}
		$this->file = $sourceFile;

		if (file_exists($this->file) && is_file($this->file)) {
			$info = pathinfo($this->file);
			$ext = $info['extension'];
			if ($ext == 'swf' || $ext == 'SWF') {
				$perms = fileperms($this->file);
				$this->readable = (bool) ($perms & 0x0100);
				if (!$this->readable) {
					$this->unlockFile();
				}
				list($width, $height, $type, $attr) = @getimagesize($this->file);
				if (!$this->readable) {
					$this->lockFile();
				}

				// neplatny format
				if ($type == NULL) {
					return NULL;
				}

				// flash
				if ($type == IMAGETYPE_SWC || $type == IMAGETYPE_SWF) {
					$this->flash = TRUE;
					$this->flash_width = $width;
					$this->flash_height = $height;
				} else {
					$this->image = $this->file;
				}
			} else {
				$this->image = $this->file;
			}
		} else {
			if ($this->config->nophoto != NULL) {
				$this->image = $this->config->nophoto;
				$this->nophoto = TRUE;
				if ($file != 'nophoto' && self::$logErrors) {
					Log::write(Log::ERROR, 'Obrázek pro náhled není dispozici: ' . $this->file);
				}
			} else {
				throw new Exception(_('Obrázek pro náhled není dispozici') . ': ' . $this->file);
			}
		}
	}


	private function unlockFile()
	{
		@chmod($this->file, 0666);
	}


	private function lockFile()
	{
		@chmod($this->file, 0000);
	}


	/**
	 * Tovarnicka
	 *
	 * @param string $file	Originalni soubor
	 * @param ThumbConfig|array $config	Konfigurace pro nahled
	 * @return Thumb
	 */
	public static function factory($file, $config = NULL)
	{
		return new self($file, $config);
	}


	/**
	 * Vygeneruje nahled na disk
	 *
	 */
	protected function generate()
	{
		if ($this->image == NULL) {
			return NULL;
		}
		if (!$this->readable) {
			$this->unlockFile();
		}

		$img = @Image::fromFile($this->image);
		$flag = ((int) $this->config->stretch) * 2 + (int) $this->config->enlarge;

		// pokud se nebude delat zadna uprava velikosti a parametru, tak se obrazek jen zkopiruje
		$resize = ($this->config->width !== NULL && $img->getWidth() > $this->config->width) ||
				($this->config->height !== NULL && $img->getHeight() > $this->config->height);
		if (
				!$this->config->sharpen &&
				!$this->config->grayscale &&
				!$this->config->negative &&
				!$this->config->enlarge &&
				!$this->config->stretch &&
				!$this->config->watermark &&
				!$resize
		) {

			$ret = copy($this->image, $this->getThumbFile());
			if (!$this->readable) {
				$this->lockFile();
			}
			return $ret;
		}

		if ($this->config->watermark && $this->config->watermark_ratio > 0) {
			// pridani vodoznaku
			$img->resize($this->config->width, $this->config->height, $flag, $this->config->crop);
			$watermark = Image::fromFile($this->config->watermark);
			$watermark->resizeAlpha($img->getWidth() * $this->config->watermark_ratio, NULL, Image::ENLARGE);

			$img->place($watermark, $this->config->watermark_x, $this->config->watermark_y, $this->config->watermark_opacity);
		} else {
			// pri nevodoznakovem nahledu se zachova PNG pruhlednost
			if ($resize) {
				$img->resizeAlpha($this->config->width, $this->config->height, $flag, $this->config->crop);
			}
		}
		// jemne doostreni
		if ($this->config->sharpen) {
			$img->sharpen();
		}
		// prevedeni do odstinu sede
		if ($this->config->grayscale) {
			$img->grayscale();
		}
		// negtiv
		if ($this->config->negative) {
			$img->negative();
		}
		// ulozeni do cache na disk
		$img->save($this->getThumbFile(), $this->config->quality);

		if (!$this->readable) {
			$this->lockFile();
		}
	}


	/**
	 * Vytvori nazev nahledu ve tvaru jmeno_200x100x0x1.jpg
	 *
	 */
	protected function makeFilename()
	{
		$ext = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));
		$extLen = strlen($ext);
		if ($this->isNophoto() /* && $extLen <= 0 */) {
			$ext = strtolower(pathinfo($this->image, PATHINFO_EXTENSION));
		}
		if ($extLen <= 0) {
			$name = basename($this->file);
		} else {
			$name = substr(basename($this->file), 0, - $extLen - 1);
		}
		$this->thumb = $name . '_' . $this->config . '.' . $ext;
		return $this->thumb;
	}


	/**
	 * Vrati nahledovy soubor
	 * @return string
	 */
	public function getThumbFile()
	{
		if (!$this->proccess) {
			$this->get();
		}
		return $this->config->dir . '/' . $this->makeFilename();
	}


	/**
	 * Vrati URL nahledu
	 * @return string
	 */
	public function getUrl()
	{
		if (!$this->proccess) {
			$this->get();
		}
		if (!$this->flash) {
			return $this->config->url . '/' . $this->thumb;
		} else {
			return $this->config->url_orig . '/' . $this->thumb;
		}
	}


	public function getUrlOrig()
	{
		if (!$this->proccess) {
			$this->get();
		}
		return $this->config->url_orig . '/' . basename($this->file);
	}


	public function isNophoto()
	{
		return $this->nophoto;
	}


	/**
	 * Vrati nahledovy objekt, v kterem je ulozeno URL na nahled. Pokud nahled existuje nedela znova generovani
	 *
	 * @param bool $equalTime - vygeneruje nahled i kdyz se cas originalu rovna casu nahledu
	 * @return ThumbResult
	 */
	public function get($equalTime = FALSE, $withToken = TRUE)
	{

		$this->proccess = TRUE;
		if (!$this->flash) {
			if (!$this->exists()) {
				$token = '';
				$this->generate();
			} else {
				@$origTime = filemtime($this->file);
				$token = $origTime;
				$thumbTime = filemtime($this->getThumbFile());
				if ($origTime > $thumbTime || ($equalTime && $origTime >= $thumbTime)) {
					$this->generate();
				}
			}
			if ($withToken == FALSE) {
				$token = '';
			}
			if ($this->image == NULL) {
				return NULL;
			}
			list($width, $height, $type, $attr) = getimagesize($this->getThumbFile());
			if (empty($token)) {
				$url = $this->getUrl();
			} else {
				$url = $this->getUrl() . '?' . $token;
			}
			return new ThumbResult($url, $width, $height);
		} else {
			$this->thumb = basename($this->source);
			$flag = ((int) $this->config->stretch) * 2 + (int) $this->config->enlarge;
			list($w, $h) = $this->calculateSize($this->config->width, $this->config->height, $flag);
			$res = new ThumbResult($this->getUrl(), $w, $h);
			$res->flash = TRUE;
			return $res;
		}
	}


	/**
	 * Alias pro nevhodne pojmenovanou funkci get()
	 *
	 */
	public function getThumb($equalTime = FALSE, $withToken = TRUE)
	{
		return $this->get($equalTime, $withToken);
	}


	/**
	 * Vrati HTML pro nahled
	 *
	 */
	public function getHtml()
	{
		if (($th = $this->get()) == NULL) {
			return Html::el();
		}
		return $th->getHtml();
	}


	/**
	 * Existence vytvoreneho nahledu
	 *
	 */
	protected function exists()
	{
		return file_exists($this->getThumbFile());
	}


	/**
	 * Vrati aktualni konfigurace tridy
	 *
	 * @return ThumbConfig
	 */
	public function getConfig()
	{
		return $this->config;
	}


	/**
	 * Nastavi konfiguracni tridu.
	 * Pokud je zadane pole, je prevedeno na konfikuracni tridu.
	 * Pokud je zadan string, je z neho vytvorena instance intridy
	 *
	 * @param Thumb $config
	 */
	public function setConfig($config)
	{
		if (is_array($config)) {
			$this->config = new ThumbConfig($config);
		} elseif (is_string($config)) {
			$this->config = new $config();
		} else {
			$this->config = $config;
		}
		if (!$this->config instanceof ThumbConfig) {
			throw Exception('Thumb configuration must be string, array or ThumbConfig object');
		}
		return $this;
	}


	/**
	 * Calculates dimensions of resized FLASH image.
	 * @param  mixed  width in pixels or percent
	 * @param  mixed  height in pixels or percent
	 * @param  int	flags
	 * @return array
	 */
	public function calculateSize($newWidth, $newHeight, $flags = 0)
	{
		$width = $this->flash_width;
		$height = $this->flash_height;

		if (substr($newWidth, -1) === '%') {
			$newWidth = round($width / 100 * $newWidth);
			$flags |= self::ENLARGE;
			$percents = TRUE;
		} else {
			$newWidth = (int) $newWidth;
		}

		if (substr($newHeight, -1) === '%') {
			$newHeight = round($height / 100 * $newHeight);
			$flags |= $percents ? self::STRETCH : self::ENLARGE;
		} else {
			$newHeight = (int) $newHeight;
		}

		if ($flags & self::STRETCH) { // non-proportional
			if ($newWidth < 1 || $newHeight < 1) {
				throw new /* \ */InvalidArgumentException('For stretching must be both width and height specified.');
			}

			if (($flags & self::ENLARGE) === 0) {
				$newWidth = round($width * min(1, $newWidth / $width));
				$newHeight = round($height * min(1, $newHeight / $height));
			}
		} else {  // proportional
			if ($newWidth < 1 && $newHeight < 1) {
				throw new /* \ */InvalidArgumentException('At least width or height must be specified.');
			}

			$scale = array();
			if ($newWidth > 0) { // fit width
				$scale[] = $newWidth / $width;
			}

			if ($newHeight > 0) { // fit height
				$scale[] = $newHeight / $height;
			}

			if (($flags & self::ENLARGE) === 0) {
				$scale[] = 1;
			}

			$scale = min($scale);
			$newWidth = round($width * $scale);
			$newHeight = round($height * $scale);
		}
		return array($newWidth, $newHeight);
	}


	public function __toString()
	{
		return (string) $this->getHtml();
	}

}

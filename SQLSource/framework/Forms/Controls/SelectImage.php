<?php

/**
 * Výběr obrázku z databáze.
 *
 * @author	 Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package	Forms
 */
class SelectImage extends FormControl
{

	public $columnType = 'type';
	public $columnImage = 'id';
	public $imagesTable = 'images';
	public $link = 'image:list';
	protected $frameWidth = 770;
	protected $frameHeight = 600;
	protected $dir;
	public $thumbConfig = array(
		'dir' => THUMB_DIR,
		'width' => 100,
		'height' => 100,
		'url' => THUMB_URL,
		'url_orig' => '/data',
	);
	public $zoom = TRUE;

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
		$this->thumbConfig['nophoto'] = WWW_DIR . '/img/no_photo.jpg';
		parent::__construct($label);
		if($config != NULL) $this->thumbConfig = $config;
		$this->dir = $dir;
	}

	public function setConfig($value)
	{
		$this->thumbConfig = $value;
		return $this;
	}

	public function getConfig()
	{
		return $this->thumbConfig;
	}

	public function getFile()
	{
		if(empty($this->value)) return '';
		$dbRow = sql::toRow('SELECT ' . $this->columnImage . ' as id, ' . $this->columnType . ' as type 
								FROM ' . $this->imagesTable . '
								WHERE id=' . $this->value . ' ');
		if(!$dbRow) return NULL;
		$id = $dbRow->id;
		$ext = $dbRow->type;
		$ext = Thumb::$IMG_EXT[$ext];
		$file = $id . '.' . $ext;
		return $this->dir . '/' . $file;
	}

	public function getControlPreview()
	{
		$id = $this->getId();
		$value = $this->getFile();
		if(empty($value)) {
			// prazdy obrazek, prichystany pro javascriptove naplneni src
			return '<a class="highslide" id="' . $id . 'orig" onclick="return hs.expand (this)">' .
				   '<img id="' . $id . 'img" style="background-color: #888;" />
					</a>';
		}
		if(empty($value)) {
			$origUrl = '';
			$src = '';
		}
		else {
			$thumb = Thumb::factory($value, $this->thumbConfig);
			if($thumb->get() == NULL) $src = '';
			else {
				$src = $thumb->get()->url;
				$origUrl = $thumb->getUrlOrig();
			}
		}

		$el = Html::el();
		$el->add(Html::el('img')
					 ->id($id . 'img')
					 ->style('background-color: #888')
					 ->src($src));
		if(!$this->zoom) return $el;
		else return '<a class="highslide" id="' . $id . 'orig" onclick="return hs.expand (this)" href="' . $origUrl . '">' .
				   $el .
				   '</a>';
	}

	public function getControl()
	{
		$this->rendered = TRUE;
		$preview = $this->getControlPreview();
		$id = $this->getId();
		$value = $this->value;
		$xml = '<input type="hidden" name="' . $id . '" id="' . $id . '" value="' . $this->value . '"/>
			<table> 
			<tr>
				<td>
					<div id="' . $id . '_p" style="display:' . (empty($value) ? 'none' : '') . '">' . $preview . '</div>
					<div id="' . $id . '_n" style="display:' . (empty($value) ? '' : 'none') . '">-- ' . _('není vybrán') . ' --</div>
				</td>
			</tr>
			<tr>
				<td valign="top" style="margin-top: 2px">					   
					' . $this->getButtons() . '
				</td>
			</tr>
		</table>
		<script>
			update_' . $this->getId() . ' = function(arr) {
				document.getElementById("' . $id . 'img").src = arr["preview"]["img"];
				if(document.getElementById("' . $id . 'orig")) document.getElementById("' . $id . 'orig").href = arr["preview"]["orig"];
				document.getElementById("' . $id . '").value = arr["image"];
				document.getElementById("' . $id . '_p").style.display = "block";
				document.getElementById("' . $id . '_n").style.display = "none";
			}
			delete_' . $this->getId() . ' = function() {
				document.getElementById("' . $id . '_p").style.display = "none";
				document.getElementById("' . $id . '_n").style.display = "block";
				document.getElementById("' . $id . 'img").src = "";
				if(document.getElementById("' . $id . 'orig")) document.getElementById("' . $id . 'orig").href = "";
				document.getElementById("' . $id . '").value = "";
			}
		</script>';
		return $xml;
	}

	protected function getButtons()
	{
		if($this->readonly) {
			return '';
		}
		$presenter = $this->getParent()->getPresenter();
		$link = $presenter->link($this->link, array('type' => 1, 'fnc' => 'update_' . $this->getId()));
		$xml = '<div class="selectbutton">
				<a href="' . $link . '" rel="superbox[iframe][' . $this->frameWidth . 'x' . $this->frameHeight . ']">' . _('Vybrat') . '</a><br/>
			</div>
			<div class="selectbutton">
				<a href="#" onclick="delete_' . $this->getId() . '(); return false;">' . _('Odstranit') . '</a>
			</div>';
		return $xml;
	}

	public function setFrameWidth($width)
	{
		$this->frameWidth = $width;
		return $this;
	}

	public function setFrameHeight($height)
	{
		$this->frameHeight = $height;
		return $this;
	}

	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri . 'js/core/highslide/highslide-full.js';
		return array_merge(parent::getJavascript(), $js);
	}

	public function getCSS()
	{
		$baseUri = Environment::getVariable('baseUri');
		$css = array();
		$css[] = $baseUri . 'js/core/highslide/highslide.css';
		return array_merge(parent::getCSS(), $css);
	}

}

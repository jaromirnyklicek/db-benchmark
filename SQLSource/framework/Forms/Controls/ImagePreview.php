<?php
/**
 * Náhled obrázku.
 * Neinteraktivní control
 *
 * @author	   Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */
 
class ImagePreview extends FormControl 
{		
	public $thumbConfig = array(	
		  'dir' => THUMB_DIR,  
		  'width' => 100,
		  'height' => 100,
		  'url' => THUMB_URL,
		  'url_orig' => '/data',
	);
	
	public $zoom = FALSE;
	
	public $origImg;
	public $thumbImg;
	
	 /**
	 * Pole pro callback funkce
	 * @see addCallback()
	 * @var array
	 */
	protected $callbackArr = array();	 
		
	public function __construct($label = '', $config = NULL)
	{
		parent::__construct($label);
		if($config != NULL) $this->setConfig($config);
	}
	
	public function getConfig()
	{
		return $this->thumbConfig;
	}
	
	public function exec()
	{
		$value = $this->value;
		if(empty($value)) return;
		foreach ($this->callbackArr as $callback) {
			if(is_callable($callback['func'])) {								
				$args = array_merge(array($value, $this->getSubForm()), $callback['params']);
				$value = call_user_func_array($callback['func'], $args);
			}
			else throw new Exception('Invalid callback '.$callback['func'].'()');
		}
		if(empty($value) || !file_exists($value)) return;
		try {													  
			$thumb = Thumb::factory($value, $this->thumbConfig);
			if($thumb == NULL) return '';			 
			$this->thumbImg = $thumb->getThumb(TRUE)->url;
			if(!$this->zoom) return;
			else {
				$this->origImg = $thumb->getUrlOrig();
			}
		}
		catch (Exception $ex) {return '';}
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
		if(is_array($config)) $this->thumbConfig = new ThumbConfig($config);
		elseif(is_string($config)) $this->thumbConfig = new $config();
		else $this->thumbConfig = $config;
		if(!$this->thumbConfig instanceof ThumbConfig) {
			throw Exception('Thumb configuration must be string, array or ThumbConfig object');
		}		 
		return $this;
	}
		 
	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{								 
		$id = $this->getId();		
		$value = $this->value;
		if(empty($value)) {
			// prazdy obrazek, prichystany pro javascriptove naplneni src
			$el = '<img id="'.$id.'img" style="background-color: #888" />';
			if(!$this->zoom) return $el;   
			return '<a class="highslide" id="'.$id.'orig" onclick="return hs.expand (this)" href="#">'.
						''.$el.'
					</a>';
		}
		foreach ($this->callbackArr as $callback) {
			if(is_callable($callback['func'])) {								
				$args = array_merge(array($value, $this->getSubForm()), $callback['params']);
				$value = call_user_func_array($callback['func'], $args);
			}
			else throw new Exception('Invalid callback '.$callback['func'].'()');
		}
		if(empty($value) || !file_exists($value)) return '';
		try {													  
			$thumb = Thumb::factory($value, $this->thumbConfig);
			if($thumb == NULL) return '';
			$el = Html::el();			   
			$this->thumbImg = $thumb->get()->url;
			$el->add(Html::el('img')
					->id($id.'img')
					->style('background-color: #888')
					->src($this->thumbImg));
					//->style('cursor:pointer; position: relative; left: 1px; top: 2px;'));
					
			if(!$this->zoom) return $el;
			else {
				$this->origImg = $thumb->getUrlOrig();
				return '<div style="float:left"><a class="highslide" id="'.$id.'orig" onclick="return hs.expand (this)" href="'.$this->origImg.'">'.
							$el.
						'</a></div>';
			}
		}
		catch (Exception $ex) {return '';}

	}
	
	/**
	 * Loads HTTP data.
	 * @param  array
	 * @return void
	 */
	public function loadHttpData($data)
	{
		
	}
	
	public function addCallback($function, $args = null) {
		if (func_num_args() > 2) {
			$argv = func_get_args();
			$args = array_merge(array($args), array_slice($argv, 2));
		}
		$f = array();
		$f['type'] = 'function';
		$f['func'] = $function;
		$f['params'] = is_array($args) ? $args : array($args);
		$this->callbackArr[] = $f;		  
		return $this;
	}	 
	
	public function getJavascript()
	{
		$baseUri = Environment::getVariable('baseUri');
		$js = array();
		$js[] = $baseUri.'js/core/highslide/highslide-full.js';
		return array_merge(parent::getJavascript(), $js);  
	}
	
	public function getCSS()
	{
		$baseUri = Environment::getVariable('baseUri');			
		$css = array();
		$css[] = $baseUri.'js/core/highslide/highslide.css';
		return array_merge(parent::getCSS(), $css);  
	}
	
	public function checkEmptyJs()
	{
		return 'res = true;';
	}
}
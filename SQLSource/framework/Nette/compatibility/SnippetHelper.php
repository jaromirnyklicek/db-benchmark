<?php

/**
 * Nette Framework
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Templates
 */



/**
 * Control snippet template helper.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Nette\Templates
 * @deprecated
 */
class SnippetHelper extends Object
{
	/** @var bool */
	public static $outputAllowed = TRUE;

	/** @var string */
	private $id;

	/** @var string */
	private $tag;

	/** @var ArrayObject */
	private $payload;

	/** @var int */
	private $level;



	/**
	 * Starts conditional snippet rendering. Returns SnippetHelper object if snippet was started.
	 * @param  Control control
	 * @param  string  snippet name
	 * @param  string  start element
	 * @return SnippetHelper
	 */
	public static function create(Control $control, $name = NULL, $tag = 'div')
	{
		if (self::$outputAllowed) { // rendering flow or non-AJAX request
			$obj = new self;
			$obj->tag = trim($tag, '<>');
			if ($obj->tag) {
				echo '<', $obj->tag, ' id="', $control->getSnippetId($name), '">';	
			}
			return $obj; // or string?

		} elseif ($control->isControlInvalid($name)) { // start snippet buffering
			$obj = new self;
			$obj->id = $control->getSnippetId($name);
			$obj->payload = $control->getPresenter()->getPayload();
			ob_start();
			$obj->level = ob_get_level();
			self::$outputAllowed = TRUE;
			return $obj;

		} else {
			return FALSE;
		}
	}
	
	public static function create2(Control $control, $name = NULL, $link = NULL, $params = NULL, $tag = 'div')
	{
		if (self::$outputAllowed) { // rendering flow or non-AJAX request
			$obj = new self;
			$obj->tag = trim($tag, '<>');
			$params['fa'] = 1;
			if ($obj->tag) {
				echo '<div style="position:relative">';
				echo '<a onclick="return hs.htmlExpand(this, { width: \'900\', height: \'700\', objectType: \'iframe\', snippet: \''.$name.'\' } )"
						class="highslide" href="'.Environment::getApplication()->getPresenter()->link($link, $params).'&snippet='.$name.'" style="position:absolute; right:0px; z-index:10000; opacity: 0.9">
							<img src="/img/buttons/pencil.gif" />
						</a>';
				echo '<', $obj->tag, ' id="', $control->getSnippetId($name), '">';	
			}
			return $obj; // or string?

		} elseif ($control->isControlInvalid($name)) { // start snippet buffering
			$obj = new self;
			$obj->id = $control->getSnippetId($name);
			$obj->payload = $control->getPresenter()->getPayload();
			ob_start();
			$obj->level = ob_get_level();
			self::$outputAllowed = TRUE;
			return $obj;

		} else {
			return FALSE;
		}
	}



	/**
	 * Finishes and saves the snippet.
	 * @return void
	 */
	public function finish()
	{
		if ($this->tag !== NULL) { // rendering flow or non-AJAX request
			if ($this->tag) {
				echo "</$this->tag>";	
			}

		} else {  // finish snippet buffering
			if ($this->level !== ob_get_level()) {
				throw new InvalidStateException("Snippet '$this->id' cannot be ended here.");
			}
			$this->payload->snippets[$this->id] = ob_get_clean();
			self::$outputAllowed = FALSE;
		}
	}

	public function finish2()
	{
		if ($this->tag !== NULL) { // rendering flow or non-AJAX request
			if ($this->tag) {
				echo "</$this->tag>";	
				echo '</div>';
			}

		} else {  // finish snippet buffering
			if ($this->level !== ob_get_level()) {
				throw new InvalidStateException("Snippet '$this->id' cannot be ended here.");
			}
			$this->payload->snippets[$this->id] = ob_get_clean();
			self::$outputAllowed = FALSE;
		}
	}


}

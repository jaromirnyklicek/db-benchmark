<?php
/**
* Trida pro nastaveni prostedi pro GNU Gettext
* 
* Textove domeny jsou prejmenovany podle timestampu, aby si je webserver necacheoval.
* Viz. http://blog.ghost3k.net/articles/php/11/gettext-caching-in-php
*/

class GettextLocale extends Object {

	protected $localeDir = LOCALE_DIR;
	protected $textdomain = 'messages';
	protected $languages;
	protected $lang;
	
	
	public function __construct($lang = NULL)
	{
		$this->lang = $lang;
	}
	
	public function setLang($value)
	{
		$this->lang = $value;
		return $this;
	}
	
	public function getLang()
	{
		return $this->lang;
	}
	
	public function setLanguages($value)
	{
		$this->languages = $value;
		return $this;
	}
	
	public function getLanguages()
	{
		return $this->languages;
	}
	
	public function setTextDomain($value)
	{
		$this->textdomain = $value;
		return $this;
	}
	
	public function getTextDomain()
	{
		return $this->textdomain;
	}
	
	/** 
	* absolutni cesta k .mo souboru
	*/
	public function getMOFile()
	{
		return $this->localeDir.'/'.$this->lang.'/LC_MESSAGES/'.$this->textdomain.'.mo';
	}
	
	/**
	* Inicializace prostredi pro gettext
	* 
	* @param string $lang
	*/
	public function init()
	{
		// workaround for gettext
		// @see http://blog.ghost3k.net/articles/php/11/gettext-caching-in-php
		$mo = $this->getMOFile();
		$time = filemtime($mo);
		$this->setTextDomain('messages_'.$time);
		$newmo = $this->getMOFile();
		if (!file_exists($newmo)) copy($mo, $newmo);
		
		if(array_key_exists($this->lang, $this->languages)) {
			$this->setLocaleEnv($this->languages[$this->lang]);
		}
		elseif($this->lang == NULL) {
			throw new InvalidLanguage('Undefined language: '.$this->lang);
		}
		else throw new InvalidLanguage('Invalid language: '.$this->lang);
	}
	
	/**
	 * Nastaveni lokalniho prostredi pro Gettext
	 *
	 * @param string $lang napriklad en_US.UTF-8
	 * @return false|string
	 */
	private function setLocaleEnv($lang)
	{			
		putenv("LANG=$lang");
		$ret = setlocale(LC_ALL, $lang);
		bindtextdomain($this->textdomain, $this->localeDir);
		textdomain($this->textdomain);
		return $ret;
	}
}

class InvalidLanguage extends Exception
{
	
}
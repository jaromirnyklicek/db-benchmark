<?php

abstract class Advantage extends Control
{
	/**
	 * Nastavení stránky.
	 * 
	 * @var int
	 */
	public $page;
	
	/**
	 * Cesta k XML souborům adVantage.
	 */
	protected $path;
	
	/**
	 * Názvy XML souborů.
	 * 
	 * @var array
	 */
	protected $files;
	
	/**
	 * XML soubor s pozicemi.
	 * 
	 * @var DOMDocument
	 */
	protected $xml;

	/**
	 * Konstruktor.
	 * 
	 * @param IComponentContainer $parent
	 * @param $name
	 */
	public function __construct(IComponentContainer $parent = NULL, $name = NULL) 
	{
		parent::__construct($parent, $name);
	}	
	
	/**
	 * Nastaví aktuální typ stránky.
	 * 
	 * @param $page int Typ stránky
	 * @return void
	 */
	public function setPage($page) 
	{
		$this->page = $page;
	}

	/**
	 * Načte XML soubor s reklamou do DOMDocument.
	 *
	 * @return void
	 */
	private function readXML() 
	{
		if(empty($this->xml)) {
			$this->xml = new DOMDocument();
			$this->xml->load($this->path . $this->files[$this->page]);
		}
	}

	/**
	 * Render hlavičky a patičky pro adVantage.
	 * 
	 * @param $name string	Název elementu, který generovat.
	 * @return void
	 */
	public function render($name) 
	{
		$this->readXML();
		
		$html = new DOMDocument;
		$html->formatOutput = true;

		$node = $this->xml->getElementsByTagName($name);
		for($i = 0; $i <= $node->item(0)->childNodes->length; $i++) {
			if($node->item(0)->childNodes->item($i)) {
				$n = $html->importNode($node->item(0)->childNodes->item($i), true);
				$html->appendChild($n);
			}
		}

		echo $this->setCDATA($html->saveHTML());
	}

	/**
	 * Render konkrétního banneru (pozice).
	 * 
	 * @param $ad string	Název pozice z XML.	
	 * @return void
	 */
	public function renderAd($ad) 
	{
		$this->readXML();
		
		$html = new DOMDocument;
		$html->formatOutput = true;
		
		$node = $this->xml->getElementsByTagName('position');
		$i = 0;
		while($node->item($i) && $node->item($i)->getAttribute('AVname') != $ad) {
			$i++;	
		}
		
		if($node->item($i)) {
			for($j = 0; $j <= $node->item($i)->childNodes->length; $j++) {
				if($node->item($i)->childNodes->item($j)) {
					$n = $html->importNode($node->item($i)->childNodes->item($j), true);
					$html->appendChild($n);
				}			
			}
		}
		
		echo $this->setCDATA($html->saveHTML());
	}
	
	private function setCDATA($html) 
	{
		$html = str_replace('<script type="text/javascript">', '<script type="text/javascript">//<![CDATA[', $html);
		$html = str_replace('</script>', '//]]></script>', $html);
		$html = str_replace('<script src="/js/advantage.js" type="text/javascript">//]]></script>', '<script src="/js/advantage.js" type="text/javascript"></script>', $html);
		
		return $html;
	}
		
}
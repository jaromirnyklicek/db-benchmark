<?php

/**
 * GettextExtractor
 *
 * Cool tool for automatic extracting gettext strings for translation
 *
 * Works best with Nette Framework
 *
 * This source file is subject to the New BSD License.
 *
 * @copyright  Copyright (c) 2009 Karel Klíma
 * @license    New BSD License
 * @package    Nette Extras
 */


/**
 * Filter to parse curly brackets syntax in Nette Framework templates
 * @author Karel Klíma
 * @copyright  Copyright (c) 2009 Karel Klíma
 */
class NetteLatteFilter implements iFilter
{
	/** regex to match the curly brackets syntax */
	const LATTE_REGEX = '#{(__PREFIXES__)("[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\')+(\|[a-z]+(:[a-z0-9]+)*)*(\s*,(\s*.*?))*}#u';
	const LATTE_REGEX_N = '#{(_n|!n)\s*("[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\')\s*,\s*("[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\')\s*,[^{]*(\|[a-z]+(:[a-z0-9]+)*)*}#u';
	/** @var array */
	protected $prefixes = array('!_', '_', '_ ');

	/**
	 * Mandatory work...
	 */
	public function __construct()
	{
		// Flips the array so we can use it more effectively
		$this->prefixes = array_flip($this->prefixes);
	}

	/**
	 * Includes a prefix to match in { }
	 * @param string $prefix
	 * @return NetteLatteFilter
	 */
	public function addPrefix($prefix) {
		$this->prefixes[$prefix] = TRUE;
		return $this;
	}

	/**
	 * Excludes a prefix from { }
	 * @param string $prefix
	 * @return NetteLatteFilter
	 */
	public function removePrefix($prefix) {
		unset($this->prefixes[$prefix]);
		return $this;
	}

	/**
	 * Parses given file and returns found gettext phrases
	 * @param string $file
	 * @return array
	 */
	public function extract($file)
	{
		$pInfo = pathinfo($file);
		if (!count($this->prefixes)) return;
		$data = array();
		// parse file by lines
		foreach (file($file) as $line => $contents) {
			$prefixes = join('|', array_keys($this->prefixes));
			// match all {!_ ... } or {_ ... } tags if prefixes are "!_" and "_"
			preg_match_all(str_replace('__PREFIXES__', $prefixes, self::LATTE_REGEX), $contents, $matches);
			if (empty($matches) || empty($matches[2])) {
				preg_match_all(self::LATTE_REGEX_N, $contents, $matches2);
				foreach ($matches2[2] as $i => $m) {
					// strips trailing apostrophes or double quotes
					$s1 = substr($m, 1, -1);
					$s2 = substr($matches2[3][$i], 1, -1);
					$data[$s1.'\\00'.$s2][] = $pInfo['basename'] . ':' . $line;
				}
			}
			else {
				foreach ($matches[2] as $m) {
					// strips trailing apostrophes or double quotes
					$data[substr($m, 1, -1)][] = $pInfo['basename'] . ':' . $line;
				}
			}
		}
		return $data;
	}
}
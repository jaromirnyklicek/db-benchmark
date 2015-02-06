<?php


/**
 * Rozsirenie Helperu pre cache v sablonach tak, aby umoznoval kesovat bloky s vyuzitim TAGov, vlastneho casu expiracie a priority.
 *
 * Mimikuje spravanie makra {cache} v NETTE 2.x.x
 *
 * Tvar makra:
 * {cacheExtended $key, Cache::EXPIRE => $expire, Cache::TAGS => $tags, Cache::PRIORITY => $priority} ...obsah... {/cacheExtended}
 *
 * Pred pouzitim je potrebne zaregistrovat makro metodou CachingHelperExtended::registerMacro();
 * 
 *
 * @author Andrej Rypak <andrej.rypak@viaaurea.cz>
 * @copyright (c)	Via Aurea, s.r.o.
 */
class CachingHelperExtended extends CachingHelper
{


	/**
	 * Sluzi pre ziskanie kluca ($key) pre makra v tvare {cacheExtended $key, Cache::EXPIRE => $expire, Cache::TAGS => $tags, Cache::PRIORITY => $priority}.
	 * $key musi byt prvy, ak je uvedeny, ostatne parametre su nepovinne.
	 *
	 * @param array $macroParams
	 * @return string|NULL
	 */
	public static function getKey(array $macroParams)
	{
		$key = reset($macroParams);
		$paramName = key($macroParams);
		if ($paramName === 0) {
			// prvy parameter je bez kluca => $key
			return $key;
		}
		return NULL;
	}


	/**
	 * Caching pomocou makra v tvare {cacheExtended $key, Cache::EXPIRE => $expire, Cache::TAGS => $tags, Cache::PRIORITY => $priority}.
	 * Kluc $key musi byt prvy, ak je uvedeny, ostatne parametre su nepovinne.
	 * Kluc nemusi byt unikatny, vytvara sa pre kazde pouzitie makra zvlast.
	 *
	 * @return FALSE|\CachingHelperExtended self
	 */
	public static function createExtended($key, $file, array $macroParams)
	{
		$cache = self::getCache();
		if (isset($cache[$key])) {
			echo $cache[$key];
			return FALSE;
		} else {
			$expire = isset($macroParams[Cache::EXPIRE]) ? $macroParams[Cache::EXPIRE] : rand(86400 * 4, 86400 * 7);
			$tags = isset($macroParams[Cache::TAGS]) ? $macroParams[Cache::TAGS] : NULL;
			$priority = isset($macroParams[Cache::PRIORITY]) ? $macroParams[Cache::PRIORITY] : NULL;
			$obj = new self;
			$obj->key = $key;
			$obj->frame = array(
				Cache::FILES => array($file),
				Cache::TAGS => $tags,
				Cache::EXPIRE => $expire,
				Cache::PRIORITY => $priority,
			);
			ob_start();
			return $obj;
		}
	}


	public static function registerMacro()
	{
		LatteMacros::$defaultMacros['cacheExtended'] = '<?php if ($_cb->foo = CachingHelperExtended::createExtended($_cb->key = md5(__FILE__) . __LINE__.CachingHelperExtended::getKey(array(%%)), $template->getFile(), array(%%))) { $_cb->caches[] = $_cb->foo ?>';
		LatteMacros::$defaultMacros['/cacheExtended'] = '<?php array_pop($_cb->caches)->save(); } if (!empty($_cb->caches)) end($_cb->caches)->addItem($_cb->key) ?>';
	}

}